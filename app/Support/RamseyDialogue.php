<?php

namespace App\Support;

class RamseyDialogue
{
    public function answer(string $raw): ?array
    {
        $message = $this->normalize($raw);
        if ($message === '') {
            return null;
        }

        $topic = session('clinic.ramsey_topic');

        if ($this->contains($message, ['emergency', 'not breathing', 'hindi humihinga', 'atake', 'unconscious'])) {
            session(['clinic.ramsey_topic' => 'emergency']);

            return $this->pack('If this may be an emergency, get medical help now. Contact the clinic personnel or emergency services. I cannot diagnose or treat you.');
        }

        if ($this->contains($message, ['masakit ulo', 'headache', 'sakit ng ulo', 'masakit ang ulo', 'lagnat', 'fever'])) {
            session(['clinic.ramsey_topic' => 'symptom']);

            return $this->pack('I cannot diagnose that or prescribe medicine. Please talk with the clinic personnel. You can also book a check-up in Appointments.', [
                ['label' => 'Appointments', 'href' => '/appointments'],
            ]);
        }

        if ($this->contains($message, ['thank you', 'thanks', 'salamat', 'thankyou'])) {
            return $this->pack("You're welcome! Feel free to ask if you need assistance.");
        }

        if ($this->contains($message, ['bye', 'goodbye', 'paalam', 'good night'])) {
            session()->forget('clinic.ramsey_topic');

            return $this->pack('Goodbye. The clinic team is here if you need help later.');
        }

        if ($this->isWhere($message) && $topic === 'appointment') {
            return $this->pack('You can find the Appointments section in your navigation menu.', [
                ['label' => 'Appointments', 'href' => '/appointments'],
            ]);
        }

        if ($this->isWhere($message) && $topic === 'records') {
            return $this->pack('Your own visit history is in the navigation menu. Clinic staff keep the full medical record.', [
                ['label' => 'Visit History', 'href' => '/visit-history'],
            ]);
        }

        if ($this->contains($message, ['hi', 'hello', 'hey', 'good morning', 'good afternoon', 'kumusta', 'kamusta', 'magandang'])) {
            return $this->pack("Hello! Welcome to RAM-CIMS. I'm RAMsey, your clinic assistant. How can I help you today?");
        }

        if ($this->contains($message, ['appointment', 'appoint', 'apointment', 'appoinment', 'appointmant', 'magpa appointment', 'pa appointment'])) {
            session(['clinic.ramsey_topic' => 'appointment']);

            return $this->pack('You can schedule an appointment through the Appointments section.', [
                ['label' => 'Appointments', 'href' => '/appointments'],
            ]);
        }

        if ($this->contains($message, ['medical record', 'visit history', 'rekord', 'records ko', 'my records'])) {
            session(['clinic.ramsey_topic' => 'records']);

            return $this->pack('You can open Visit History to see your own clinic visits. I will not show another person\'s record.', [
                ['label' => 'Visit History', 'href' => '/visit-history'],
            ]);
        }

        return null;
    }

    private function pack(string $reply, array $links = []): array
    {
        return ['reply' => $reply, 'links' => $links];
    }

    private function normalize(string $raw): string
    {
        $text = strtolower(trim($raw));
        $text = str_replace(['-', '?', '!', '.', ','], ' ', $text);

        return trim((string) preg_replace('/\s+/', ' ', $text));
    }

    private function contains(string $message, array $needles): bool
    {
        foreach ($needles as $needle) {
            $pattern = '/\b'.preg_quote($needle, '/').'\b/u';
            if (preg_match($pattern, $message)) {
                return true;
            }
        }

        return false;
    }

    private function isWhere(string $message): bool
    {
        return $this->contains($message, ['saan', 'nasaan', 'where', 'makikita', 'find it', 'find that']);
    }
}
