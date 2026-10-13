<?php

namespace App\Support;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DutySlotGuard
{
    public function run(string $physician, string $scheduledAt, callable $callback): mixed
    {
        $moment = ClinicCalendar::parse($scheduledAt)->seconds(0);
        $key = 'duty-slot:'.sha1($physician.'|'.$moment->format('Y-m-d H:i:s'));

        try {
            return Cache::lock($key, 10)->block(5, function () use ($callback) {
                return DB::transaction($callback);
            });
        } catch (LockTimeoutException) {
            return null;
        }
    }
}
