<?php

namespace App\Support;

use Illuminate\Support\Facades\RateLimiter;

class RamseyGemini
{
    public function __construct(
        private RamseyPrivacy $privacy,
        private GeminiClient $client,
    ) {}

    /**
     * @return array{reply: string, links: array<int, array{label: string, href: string}>}|null
     */
    public function complete(string $raw): ?array
    {
        $message = trim($raw);
        if ($message === '') {
            return null;
        }

        $blocked = $this->privacy->block($message);
        if ($blocked !== null) {
            return ['reply' => $blocked, 'links' => []];
        }

        if (! $this->enabled()) {
            return null;
        }

        $limitKey = 'ramsey-gemini:'.(auth()->id() ?: 'guest');
        $limit = max(1, (int) config('gemini.rate_limit', 12));
        if (RateLimiter::tooManyAttempts($limitKey, $limit)) {
            return ['reply' => 'Please wait a moment before asking another question.', 'links' => []];
        }

        RateLimiter::hit($limitKey, 60);

        $staff = app(ClinicAccess::class)->isStaff();
        $result = $this->client->generate($message, $staff);

        if ($result['blocked']) {
            return ['reply' => RamseyPrivacy::SAFETY_REFUSAL, 'links' => []];
        }

        if (! $result['ok']) {
            return null;
        }

        if (! $this->privacy->outputAllowed($result['text'])) {
            return ['reply' => RamseyPrivacy::MEDICATION_REFUSAL, 'links' => []];
        }

        $text = $this->privacy->plain($result['text']);
        if ($text === '') {
            return null;
        }

        if (! $staff && $this->privacy->studentLeak($text)) {
            return [
                'reply' => 'Inventory, reports, and staff notifications are limited to clinic staff. I can help with Appointments, Visit History, and Profile.',
                'links' => [
                    ['label' => 'Appointments', 'href' => '/appointments'],
                    ['label' => 'Visit History', 'href' => '/visit-history'],
                    ['label' => 'Profile', 'href' => '/profile'],
                ],
            ];
        }

        return ['reply' => $text, 'links' => $this->links($text, $staff)];
    }

    public function enabled(): bool
    {
        return trim((string) config('gemini.api_key')) !== '';
    }

    /**
     * @return array<int, array{label: string, href: string}>
     */
    private function links(string $reply, bool $staff): array
    {
        $text = strtolower($reply);
        $links = [];

        $add = function (string $label, string $href) use (&$links): void {
            foreach ($links as $link) {
                if ($link['href'] === $href) {
                    return;
                }
            }

            $links[] = ['label' => $label, 'href' => $href];
        };

        if (str_contains($text, 'appointment')) {
            $add('Appointments', '/appointments');
        }

        if (str_contains($text, 'visit history') || str_contains($text, 'medical record')) {
            $add(
                $staff ? 'Medical Records' : 'Visit History',
                $staff ? '/medical-records' : '/visit-history'
            );
        }

        if (str_contains($text, 'dashboard')) {
            $add('Dashboard', '/dashboard');
        }

        if (str_contains($text, 'profile')) {
            $add('Profile', '/profile');
        }

        if ($staff && str_contains($text, 'inventory')) {
            $add('Inventory', '/inventory');
        }

        if ($staff && str_contains($text, 'analytics')) {
            $add('Analytics', '/analytics');
        }

        if ($staff && str_contains($text, 'notification')) {
            $add('Notifications', '/notifications');
        }

        return array_slice($links, 0, 3);
    }
}
