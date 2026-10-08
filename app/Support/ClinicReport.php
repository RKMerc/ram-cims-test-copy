<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\Inventory;
use App\Models\MedicalRecord;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ClinicReport
{
    public static function physicianOptions(): Collection
    {
        $fromAppointments = Appointment::query()
            ->whereNotNull('ATTENDING_PHYSICIAN')
            ->where('ATTENDING_PHYSICIAN', '!=', '')
            ->distinct()
            ->orderBy('ATTENDING_PHYSICIAN')
            ->pluck('ATTENDING_PHYSICIAN');

        return $fromAppointments
            ->merge(ClinicRoster::names())
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }

    public static function build(?string $from, ?string $to, ?string $physician, string $preparedBy): array
    {
        $start = Carbon::parse($from ?: now('Asia/Manila')->toDateString())->startOfDay();
        $end = Carbon::parse($to ?: now('Asia/Manila')->toDateString())->endOfDay();

        if ($end->lt($start)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        $physician = trim((string) $physician) ?: null;
        $queuedStatuses = ['pending', 'scheduled', 'queued', 'waiting'];

        $appointments = Appointment::query()
            ->whereBetween('SCHEDULED_AT', [$start, $end])
            ->when($physician, fn ($query) => $query->where('ATTENDING_PHYSICIAN', $physician))
            ->orderBy('SCHEDULED_AT')
            ->orderBy('PATIENT_NAME')
            ->get();

        $records = MedicalRecord::query()
            ->whereBetween('MEDREC_CONSUL_DATE', [$start->toDateString(), $end->toDateString()])
            ->orderBy('MEDREC_CONSUL_DATE')
            ->get();

        if ($physician) {
            $records = $records
                ->whereIn('APPT_ID', $appointments->pluck('APPOINTMENT_ID')->all())
                ->values();
        }

        $physicianByAppointment = Appointment::query()
            ->whereIn('APPOINTMENT_ID', $records->pluck('APPT_ID')->filter()->unique()->all())
            ->pluck('ATTENDING_PHYSICIAN', 'APPOINTMENT_ID');

        $lowStock = Inventory::query()
            ->where('ITEM_QUANTITY', '<=', 10)
            ->orderBy('ITEM_QUANTITY')
            ->orderBy('GENERIC_NAME')
            ->get();

        $generated = now('Asia/Manila');

        return [
            'reference' => 'APC-AR-'.$generated->format('Ymd-His'),
            'generated' => $generated,
            'prepared_by' => $preparedBy,
            'physician' => $physician ?: 'All physicians',
            'from' => $start->copy()->startOfDay(),
            'to' => $end->copy()->startOfDay(),
            'on_duty' => self::onDuty($start, $end, $physician),
            'appointments' => $appointments,
            'records' => $records,
            'physician_by_appointment' => $physicianByAppointment,
            'low_stock' => $lowStock,
            'summary' => [
                'appointments' => $appointments->count(),
                'patients' => $appointments->pluck('PATIENT_ID')->filter()->unique()->count(),
                'queued' => $appointments->filter(fn ($row) => in_array(strtolower((string) $row->STATUS), $queuedStatuses, true))->count(),
                'completed' => $appointments->filter(fn ($row) => strtolower((string) $row->STATUS) === 'completed')->count(),
                'cancelled' => $appointments->filter(fn ($row) => strtolower((string) $row->STATUS) === 'cancelled')->count(),
                'visits' => $records->count(),
                'low_stock' => $lowStock->count(),
            ],
            'by_status' => $appointments
                ->groupBy(fn ($row) => $row->STATUS ?: 'Unspecified')
                ->map->count()
                ->sortDesc(),
            'by_physician' => $appointments
                ->groupBy(fn ($row) => $row->ATTENDING_PHYSICIAN ?: 'Unassigned')
                ->map(function (Collection $rows, string $name) use ($queuedStatuses) {
                    return [
                        'name' => $name,
                        'total' => $rows->count(),
                        'queued' => $rows->filter(fn ($row) => in_array(strtolower((string) $row->STATUS), $queuedStatuses, true))->count(),
                        'completed' => $rows->filter(fn ($row) => strtolower((string) $row->STATUS) === 'completed')->count(),
                    ];
                })
                ->sortByDesc('total')
                ->values(),
        ];
    }

    private static function onDuty(Carbon $start, Carbon $end, ?string $physician): array
    {
        $rows = [];
        $day = $start->copy()->startOfDay();
        $last = $end->copy()->startOfDay();

        for ($guard = 0; $day->lte($last) && $guard < 31; $guard++) {
            foreach (ClinicRoster::people() as $person) {
                if ($physician && $person['name'] !== $physician) {
                    continue;
                }

                foreach ($person['shifts'] as $shift) {
                    if (in_array($day->dayOfWeekIso, $shift['weekdays'], true)) {
                        $rows[] = [
                            'date' => $day->toDateString(),
                            'name' => $person['name'],
                            'role' => $person['role'],
                            'hours' => $shift['hours'],
                        ];
                    }
                }
            }

            $day->addDay();
        }

        return $rows;
    }
}
