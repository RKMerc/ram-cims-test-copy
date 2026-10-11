<?php

namespace App\Support;

class RamseyPrivacy
{
    public const MEDICATION_REFUSAL = "I'm here to help with clinic services and system navigation, but I cannot recommend medication or provide medical consultations. Please consult authorized clinic personnel.";

    public const PHI_REFUSAL = 'Please do not include personal or health details in this chat. I can help with clinic services and where to find pages in RAM-CIMS, and I cannot read medical records.';

    public const INJECTION_REFUSAL = 'I can only help with clinic services and RAM-CIMS navigation.';

    public const ROLE_REFUSAL = 'Your access follows the role signed in to RAM-CIMS. I cannot change that from this chat.';

    public const ACTION_REFUSAL = 'I cannot change clinic records or run system actions from this chat.';

    public const SAFETY_REFUSAL = 'I cannot answer that here. I can help with clinic services and RAM-CIMS navigation.';

    public function block(string $raw): ?string
    {
        $text = $this->text($raw);

        if ($this->injection($text)) {
            return self::INJECTION_REFUSAL;
        }

        if ($this->roleSpoof($text)) {
            return self::ROLE_REFUSAL;
        }

        if ($this->unauthorizedAction($text)) {
            return self::ACTION_REFUSAL;
        }

        if ($this->healthOrIdentity($text)) {
            return self::PHI_REFUSAL;
        }

        return null;
    }

    public function outputAllowed(string $text): bool
    {
        $key = trim((string) config('gemini.api_key'));
        if ($key !== '' && str_contains($text, $key)) {
            return false;
        }

        $lower = strtolower($text);

        if (preg_match('/\b\d+(?:\.\d+)?\s*(mg|ml|mcg|milligram|milliliters?)\b/u', $lower)) {
            return false;
        }

        foreach ([
            'you should take',
            'i recommend taking',
            'i recommend you take',
            'diagnosis is',
            'prescribed',
            'patient id',
            'select *',
            'drop table',
            'api key',
            'system prompt',
            'medrec_',
        ] as $needle) {
            if (str_contains($lower, $needle)) {
                return false;
            }
        }

        return true;
    }

    public function plain(string $text): string
    {
        $text = strip_tags($text);
        $text = (string) preg_replace('#https?://\S+#i', '', $text);
        $text = trim((string) preg_replace('/\s+/', ' ', $text));

        if (strlen($text) > 500) {
            $text = rtrim(substr($text, 0, 500));
        }

        return $text;
    }

    public function studentLeak(string $text): bool
    {
        return (bool) preg_match('/\b(inventory|analytics|notification|other patients|patient list)\b/i', $text);
    }

    private function text(string $raw): string
    {
        $text = strtolower(trim($raw));
        $text = str_replace(["\r", "\n", "\t"], ' ', $text);

        return trim((string) preg_replace('/\s+/', ' ', $text));
    }

    private function injection(string $text): bool
    {
        return (bool) preg_match('/\b(ignore (all |the )?(previous|prior|above) (instructions|rules|prompts)|disregard (the )?(rules|instructions|previous)|you are now|system prompt|jailbreak|developer mode|reveal (your )?(prompt|instructions|api key)|pretend (you are|to be)|act as|bypass (the )?(privacy|filter|rules))\b/u', $text);
    }

    private function roleSpoof(string $text): bool
    {
        return (bool) preg_match('/\b(i am (the |an |a )?(administrator|admin|clinic staff|doctor)|grant me (staff|admin)|switch (me|my role) to staff|ignore your role)\b/u', $text);
    }

    private function unauthorizedAction(string $text): bool
    {
        return (bool) preg_match('/\b(delete all|drop table|truncate|select \*|union select|update all)\b/u', $text)
            || str_contains($text, ';--');
    }

    private function healthOrIdentity(string $text): bool
    {
        if (preg_match('/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/i', $text)) {
            return true;
        }

        if (preg_match('/\b(?:\+63|0)9\d{9}\b/', $text)) {
            return true;
        }

        if (preg_match('/\b\d{6,}\b/', $text)) {
            return true;
        }

        if (preg_match('/\b\d{2,3}\s*\/\s*\d{2,3}\b/', $text)) {
            return true;
        }

        if (preg_match('/\b\d+(?:\.\d+)?\s*(mg|ml|mcg)\b/', $text)) {
            return true;
        }

        foreach ([
            'diagnosis', 'diagnosed', 'prognosis', 'laboratory', 'lab result', 'medical history',
            'my illness', 'blood type', 'blood pressure', 'i have asthma', 'i have diabetes',
            'i am pregnant', 'allergic to', 'allergies to', 'asthma', 'diabetes', 'covid',
        ] as $needle) {
            if (str_contains($text, $needle)) {
                return true;
            }
        }

        return false;
    }
}
