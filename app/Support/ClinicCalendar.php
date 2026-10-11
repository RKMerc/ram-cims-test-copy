<?php

namespace App\Support;

use Carbon\Carbon;

class ClinicCalendar
{
    public static function now(): Carbon
    {
        return Carbon::now(self::timezone());
    }

    public static function timezone(): string
    {
        return (string) config('clinic.timezone', 'Asia/Manila');
    }

    public static function weekStart(?Carbon $moment = null): Carbon
    {
        return ($moment ?? self::now())->copy()->timezone(self::timezone())->startOfWeek(Carbon::MONDAY)->startOfDay();
    }

    public static function parse(string $value): Carbon
    {
        return Carbon::parse($value, self::timezone());
    }
}
