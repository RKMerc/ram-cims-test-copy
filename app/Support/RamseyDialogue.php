<?php

namespace App\Support;

class RamseyDialogue
{
    private const CONTEXT_MINUTES = 30;

    public function answer(string $raw): ?array
    {
        $message = $this->normalize($raw);
        if ($message === '') {
            return $this->unknown();
        }

        if ($this->isOperationalChip($message) || $this->isOperational($message)) {
            return null;
        }

        $context = $this->context();
        $filipino = $this->usesFilipino($message);

        if ($this->contains($message, ['emergency', 'not breathing', 'hindi humihinga', 'atake', 'unconscious', 'nahimatay'])) {
            $this->remember('emergency', 'emergency');

            return $this->pack('If this may be an emergency, get medical help now. Contact the clinic personnel or emergency services. I cannot diagnose or treat you.');
        }

        if ($this->isSymptom($message)) {
            $this->remember('symptom', 'symptom');

            if ($filipino || $this->contains($message, ['masakit', 'ulo', 'lagnat'])) {
                return $this->pack('Pasensya na at masakit ang ulo mo. Para sa tamang assessment, maaari kang magpakonsulta sa clinic personnel.', [
                    ['label' => 'Appointments', 'href' => '/appointments'],
                ]);
            }

            return $this->pack('I am sorry you are not feeling well. I cannot diagnose that or prescribe medicine. Please consult the clinic personnel.', [
                ['label' => 'Appointments', 'href' => '/appointments'],
            ]);
        }

        if ($this->contains($message, ['salamat', 'maraming salamat'])) {
            $this->remember(null, 'thanks');

            return $this->pack('Walang anuman! Nandito lang ako kung kailangan mo ng tulong.');
        }

        if ($this->contains($message, ['thank you', 'thanks', 'thankyou'])) {
            $this->remember(null, 'thanks');

            return $this->pack("You're welcome! Feel free to ask if you need assistance.");
        }

        if ($this->contains($message, ['bye', 'goodbye', 'paalam', 'good night'])) {
            session()->forget('clinic.ramsey_context');

            return $this->pack($filipino ? 'Paalam. Nandito ang clinic kung kailangan mo ulit ng tulong.' : 'Goodbye. The clinic team is here if you need help later.');
        }

        if ($this->isHelp($message)) {
            $this->remember('help', 'help');

            if ($filipino) {
                return $this->pack('Sure! Ano po ang kailangan ninyong tulong?');
            }

            return $this->pack('Sure! What do you need help with?');
        }

        if ($this->isWhere($message) && ! $this->isRecordsRequest($message) && ! $this->isAppointmentRequest($message)) {
            if (($context['topic'] ?? null) === 'appointment') {
                $this->remember('appointment', 'appointment-where');

                return $this->pack(
                    $filipino
                        ? 'Makikita mo ang Appointments section sa navigation menu.'
                        : 'You can find the Appointments section in your navigation menu.',
                    [['label' => 'Appointments', 'href' => '/appointments']]
                );
            }

            if (($context['topic'] ?? null) === 'records') {
                $this->remember('records', 'records-where');

                return $this->pack('You can view your available medical records through the Medical Records section.', [
                    ['label' => 'Visit History', 'href' => '/visit-history'],
                ]);
            }

            $this->remember(null, 'clarify');

            return $this->pack($filipino
                ? 'Alin po ang hinahanap ninyo? Appointments, o ang sarili mong visit history?'
                : 'Which section do you mean? I can point you to Appointments or your own visit history.');
        }

        if ($this->isGreeting($message)) {
            $this->remember(null, 'greeting');

            return $this->pack('Hello! How can I help you today?');
        }

        if ($this->isAmbiguousAppointment($message)) {
            $this->remember('appointment', 'clarify');

            return $this->pack('Are you asking how to schedule an appointment, or where to find the Appointments section?');
        }

        if ($this->isAppointmentRequest($message)) {
            $this->remember('appointment', 'appointment');

            if ($filipino) {
                return $this->pack('Maaari kang mag-schedule ng appointment sa Appointments section ng RAM-CIMS.', [
                    ['label' => 'Appointments', 'href' => '/appointments'],
                ]);
            }

            return $this->pack('You can schedule an appointment through the Appointments section.', [
                ['label' => 'Appointments', 'href' => '/appointments'],
            ]);
        }

        if ($this->isRecordsRequest($message)) {
            $this->remember('records', 'records');

            return $this->pack('You can view your available medical records through the Medical Records section.', [
                ['label' => 'Visit History', 'href' => '/visit-history'],
            ]);
        }

        if ($this->contains($message, ['clinic service', 'clinic services', 'serbisyo', 'services'])) {
            $this->remember('services', 'services');

            return $this->pack($filipino
                ? 'Ang clinic ay tumutulong sa check-up, first aid, at student health concerns. Sa Appointments ka magbo-book ng visit.'
                : 'The clinic helps with check-ups, first aid, and student health concerns. Use Appointments to book a visit.', [
                    ['label' => 'Appointments', 'href' => '/appointments'],
                ]);
        }

        if ($this->contains($message, ['grade', 'grades', 'homework', 'tuition', 'enrollment', 'password'])) {
            $this->remember(null, 'unsupported');

            return $this->pack('I cannot help with that. I can help with clinic services, appointments, and your own visit history.');
        }

        return $this->unknown();
    }

    private function unknown(): array
    {
        session()->forget('clinic.ramsey_context');

        return $this->pack('Sorry, hindi ko naintindihan ang message mo. Maaari mo bang linawin?');
    }

    private function pack(string $reply, array $links = []): array
    {
        return ['reply' => $reply, 'links' => $links];
    }

    private function context(): array
    {
        $context = session('clinic.ramsey_context');
        if (! is_array($context) || (int) ($context['expires'] ?? 0) < now()->timestamp) {
            return ['topic' => null, 'intent' => null];
        }

        return $context;
    }

    private function remember(?string $topic, string $intent): void
    {
        session([
            'clinic.ramsey_context' => [
                'topic' => $topic,
                'intent' => $intent,
                'expires' => now()->addMinutes(self::CONTEXT_MINUTES)->timestamp,
            ],
        ]);
    }

    private function normalize(string $raw): string
    {
        $text = strtolower(trim($raw));
        $text = str_replace(['-', '?', '!', '.', ',', '/', '(', ')'], ' ', $text);

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

    private function isOperationalChip(string $message): bool
    {
        return in_array($message, [
            'schedule check up',
            'schedule checkup',
            'view my latest visit summary',
            'view my active prescriptions',
            'view my vitals contact info',
            'show items below minimum threshold',
            'daily queue summary',
            'view daily queue',
            'open inventory manager',
            'generate clinic visit report',
            'doctor duty schedule quick check',
            'treatment first mode tfm',
        ], true);
    }

    private function isOperational(string $message): bool
    {
        return $this->contains($message, [
            'inventory', 'stock', 'supply', 'supplies',
            'analytics', 'clinic report', 'generate report',
            'prescribe', 'prescription', 'medication', 'medicine',
            'queue', 'treatment first', 'tfm',
            'duty', 'threshold', 'below minimum',
            'other patient', 'other patients', 'another patient', 'all patients', 'patient list',
            'vital', 'contact info',
            'slot', 'availability',
            'who are you', 'what can you',
        ]);
    }

    private function isGreeting(string $message): bool
    {
        return (bool) preg_match('/^(hi|hello|hey|good morning|good afternoon|good evening|kumusta|kamusta|magandang umaga|magandang hapon|magandang gabi)$/', $message);
    }

    private function isHelp(string $message): bool
    {
        return $message === 'help' || $this->contains($message, [
            'pwede pa help', 'paki help', 'pa help', 'help me', 'can you help', 'could you help', 'need help', 'tulong',
        ]);
    }

    private function isWhere(string $message): bool
    {
        return $this->contains($message, ['saan', 'nasaan', 'where', 'makikita', 'find it', 'find that']);
    }

    private function isSymptom(string $message): bool
    {
        return $this->contains($message, ['masakit ulo', 'headache', 'sakit ng ulo', 'masakit ang ulo', 'lagnat', 'fever']);
    }

    private function isAmbiguousAppointment(string $message): bool
    {
        return in_array($message, ['appointment', 'appointments', 'check up', 'checkup'], true);
    }

    private function isAppointmentRequest(string $message): bool
    {
        return $this->contains($message, [
            'magpa appointment', 'pa appointment', 'paano magpa',
            'book an appointment', 'book appointment', 'schedule an appointment',
            'make an appointment', 'make an apointment',
            'how can i book', 'how do i book', 'how can i schedule',
            'apointment', 'appoinment', 'appointmant',
        ]);
    }

    private function isRecordsRequest(string $message): bool
    {
        return $this->contains($message, [
            'my records', 'my record', 'medical record', 'medical records',
            'visit history', 'rekord', 'records ko', 'where are my records',
        ]);
    }

    private function usesFilipino(string $message): bool
    {
        return $this->contains($message, [
            'pwede', 'paano', 'saan', 'salamat', 'po', 'magpa', 'makikita', 'tulong', 'kumusta', 'kamusta', 'ano', 'ninyo',
        ]);
    }
}
