<?php

namespace App\Support;

use App\Mail\SlotOpenAlert;
use App\Models\ScheduleReminder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ScheduleAlerts
{
    public static function watch(string $email, ?string $patientName, string $physician, string $date): array
    {
        $date = Carbon::parse($date)->toDateString();
        $reminder = ScheduleReminder::create([
            'email' => $email,
            'patient_name' => $patientName,
            'note' => 'slot-watch|'.$physician.'|'.$date,
        ]);

        $open = self::hasOpenSlot($physician, $date);
        $sent = $open && self::send($reminder, $physician, $date);

        return [
            'open' => $open,
            'sent' => $sent,
        ];
    }

    public static function release(string $physician, string $date, bool $published = false): int
    {
        $date = Carbon::parse($date)->toDateString();

        if (! $published && ! self::hasOpenSlot($physician, $date)) {
            return 0;
        }

        $sent = 0;

        foreach (ScheduleReminder::query()->whereNull('notified_at')->get() as $reminder) {
            $target = self::target($reminder);
            $matches = $target === null
                ? $published
                : strcasecmp($target['physician'], $physician) === 0 && $target['date'] === $date;

            if (! $matches) {
                continue;
            }

            if (self::send($reminder, $target['physician'] ?? $physician, $target['date'] ?? $date)) {
                $sent++;
            }
        }

        return $sent;
    }

    public static function hasOpenSlot(string $physician, string $date): bool
    {
        $day = Carbon::parse($date)->startOfDay();

        if ($day->isWeekend()) {
            return false;
        }

        $week = app(DutyBoard::class)->week($physician, $day->copy()->startOfWeek(Carbon::MONDAY));

        foreach ($week['rows'] as $row) {
            foreach ($row['cells'] as $cell) {
                if ($cell['date'] === $day->toDateString() && $cell['kind'] === 'available') {
                    return true;
                }
            }
        }

        return false;
    }

    private static function send(ScheduleReminder $reminder, string $physician, string $date): bool
    {
        try {
            Mail::to($reminder->email)->send(new SlotOpenAlert(
                physician: $physician,
                when: Carbon::parse($date)->format('F j, Y'),
                appointmentsUrl: url('/appointments'),
            ));
            $reminder->forceFill(['notified_at' => now()])->save();

            return true;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }

    private static function target(ScheduleReminder $reminder): ?array
    {
        $note = (string) $reminder->note;

        if (! str_starts_with($note, 'slot-watch|')) {
            return null;
        }

        $parts = explode('|', $note, 3);

        if (count($parts) < 3 || $parts[1] === '' || $parts[2] === '') {
            return null;
        }

        return [
            'physician' => $parts[1],
            'date' => Carbon::parse($parts[2])->toDateString(),
        ];
    }
}
