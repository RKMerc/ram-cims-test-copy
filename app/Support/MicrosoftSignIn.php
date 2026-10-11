<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\LinkedIdentity;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class MicrosoftSignIn
{
    public function fromCallback(SocialiteUser $microsoftUser): RedirectResponse
    {
        $subject = trim((string) $microsoftUser->getId());
        $email = PortalGrant::normalize($microsoftUser->getEmail());
        $name = $microsoftUser->getName() ?: 'APC User';
        $tenant = (string) config('services.microsoft.tenant', 'organizations');

        if ($subject === '' || $email === '' || ! PortalGrant::allowsEmail($email)) {
            return redirect()->route('login')->with('error', 'Use an APC email or a Gmail address to sign in.');
        }

        $linked = $this->findLink($subject, $tenant);
        if ($linked?->user) {
            return $this->login($linked->user, $name);
        }

        $existing = User::query()->whereRaw('lower(email) = ?', [$email])->first();
        $current = Auth::user();

        if ($current && $existing && $current->id !== $existing->id) {
            AuditLog::record('microsoft.link.conflict', $current, [
                'microsoft_email' => $email,
                'existing_user_id' => $existing->id,
            ]);

            return redirect()->route('profile')->with('error', 'That Microsoft account matches a different RAM-CIMS account. The records were not merged.');
        }

        if ($current && PortalGrant::normalize($current->email) === $email) {
            $this->link($current, $subject, $tenant, $email);

            return redirect()->route('profile')->with('success', 'Microsoft account connected.');
        }

        if ($existing) {
            session([
                'microsoft.pending_link' => [
                    'subject' => $subject,
                    'tenant' => $tenant,
                    'email' => $email,
                    'name' => $name,
                ],
            ]);

            return redirect()->route('microsoft.link')->with('error', 'Confirm the password for this RAM-CIMS account before connecting Microsoft.');
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Str::password(32),
        ]);
        $this->link($user, $subject, $tenant, $email);

        return $this->login($user, $name);
    }

    public function confirmPending(string $password): RedirectResponse
    {
        $pending = session('microsoft.pending_link');
        if (! is_array($pending) || empty($pending['email']) || empty($pending['subject'])) {
            return redirect()->route('login')->with('error', 'The Microsoft connection request expired. Start again from Continue with Microsoft.');
        }

        $user = User::query()->whereRaw('lower(email) = ?', [PortalGrant::normalize($pending['email'])])->first();
        if (! $user || ! Hash::check($password, $user->password)) {
            return back()->withErrors(['password' => 'That password does not match the existing account.']);
        }

        $this->link($user, $pending['subject'], (string) ($pending['tenant'] ?? ''), $pending['email']);
        session()->forget('microsoft.pending_link');

        return $this->login($user, $pending['name'] ?? $user->name);
    }

    private function findLink(string $subject, string $tenant): ?LinkedIdentity
    {
        return LinkedIdentity::query()
            ->with('user')
            ->where('provider', 'microsoft')
            ->where('provider_subject_id', $subject)
            ->where('provider_tenant_id', $tenant)
            ->first();
    }

    private function link(User $user, string $subject, string $tenant, string $email): void
    {
        DB::transaction(function () use ($user, $subject, $tenant, $email) {
            $existing = $this->findLink($subject, $tenant);
            if ($existing && $existing->user_id !== $user->id) {
                abort(409, 'That Microsoft account is already connected to another RAM-CIMS user.');
            }

            LinkedIdentity::query()->updateOrCreate(
                [
                    'provider' => 'microsoft',
                    'provider_tenant_id' => $tenant,
                    'provider_subject_id' => $subject,
                ],
                [
                    'user_id' => $user->id,
                    'provider_email' => $email,
                    'linked_at' => now(),
                ]
            );

            AuditLog::record('microsoft.linked', $user, [
                'provider_email' => $email,
                'provider_subject_id' => $subject,
            ], $user->id);
        });
    }

    private function login(User $user, ?string $name): RedirectResponse
    {
        Auth::login($user);
        request()->session()->regenerate();

        try {
            $account = PortalGrant::provision($user->email, $name ?: $user->name);
        } catch (\Throwable $exception) {
            report($exception);
            $account = null;
        }

        if ($account) {
            app(ClinicAccess::class)->remember($account);
        }

        if (PortalGrant::isStaffOnly($user->email)) {
            session(['clinic.portal_view' => 'staff']);
        } elseif (PortalGrant::isDualView($user->email)) {
            session(['clinic.portal_view' => 'student']);
        }

        return redirect()->intended('/dashboard');
    }
}
