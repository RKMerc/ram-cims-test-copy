<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\AppUser;
use App\Models\DoctorSchedule;
use App\Models\DutySlotState;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DutyBoard
{
    public function practitioners(): array
    {
        ClinicRoster::ensureAccounts();

        $rosterOrder = array_flip(ClinicRoster::names());
        $roles = ['Physician', 'Doctor', 'Nurse', 'Dentist'];

        return AppUser::query()
            ->with(['medicalStaff.subUserType'])
            ->whereHas('medicalStaff.subUserType', function ($query) use ($roles) {
                $query->whereIn('Name', $roles);
            })
            ->get()
            ->map(function (AppUser $user) {
                $subtype = (string) $user->medicalStaff?->subUserType?->Name;
                $role = $subtype === 'Physician' ? 'Doctor' : ($subtype !== '' ? $subtype : 'Clinic Staff');
                $name = ClinicRoster::displayNameForEmail((string) $user->EmailAddress) ?? $user->fullName();

                return [
                    'id' => $user->Id,
                    'name' => $name,
                    'role' => $role,
                    'label' => $name.' - '.$role,
                ];
            })
            ->unique('name')
            ->sortBy(fn (array $person) => sprintf('%04d-%s', $rosterOrder[$person['name']] ?? 1000, $person['label']))
            ->values()
            ->all();
    }

    public function week(string $name, Carbon $monday): array
    {
        $monday = $monday->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $flags = $this->dayFlags($name);
        $rostered = ClinicRoster::isRostered($name);
        $days = [];

        for ($offset = 0; $offset < 5; $offset++) {
            $date = $monday->copy()->addDays($offset);
            $iso = $date->dayOfWeekIso;
            $days[] = [
                'name' => $date->format('l'),
                'date' => $date->toDateString(),
                'works' => (bool) ($flags[$iso] ?? false),
            ];
        }

        $appointments = Appointment::query()
            ->where('ATTENDING_PHYSICIAN', $name)
            ->whereDate('SCHEDULED_AT', '>=', $days[0]['date'])
            ->whereDate('SCHEDULED_AT', '<=', $days[4]['date'])
            ->whereRaw("LOWER(COALESCE(STATUS, '')) != 'cancelled'")
            ->orderBy('SCHEDULED_AT')
            ->get();

        $rows = [];

        for ($hour = 8; $hour <= 16; $hour++) {
            $sample = $monday->copy()->setTime($hour, 0);
            $cells = [];

            foreach ($days as $day) {
                $start = Carbon::parse($day['date'])->setTime($hour, 0);
                $end = $start->copy()->addHour();
                $booked = $this->bookedDuring($appointments, $start, $end);
                $onShift = $day['works'] && (! $rostered || ClinicRoster::covers($name, $start, $end));

                if ($booked->isNotEmpty()) {
                    $patient = $booked->pluck('PATIENT_NAME')->implode(', ');
                    $cells[] = [
                        'open' => false,
                        'text' => 'OCCUPIED - '.$patient,
                        'date' => $day['date'],
                        'start' => $start->format('H:i:s'),
                    ];
                } elseif (! $onShift) {
                    $cells[] = [
                        'open' => false,
                        'text' => 'NOT AVAILABLE',
                        'date' => $day['date'],
                        'start' => $start->format('H:i:s'),
                    ];
                } else {
                    $cells[] = [
                        'open' => true,
                        'text' => 'AVAILABLE',
                        'date' => $day['date'],
                        'start' => $start->format('H:i:s'),
                    ];
                }
            }

            $rows[] = [
                'label' => $sample->format('g:i A').' - '.$sample->copy()->addHour()->format('g:i A'),
                'cells' => $cells,
            ];
        }

        return [
            'days' => $days,
            'rows' => $rows,
        ];
    }

    private function dayFlags(string $name): array
    {
        $roster = ClinicRoster::weekdayFlags($name);
        $mapped = [
            1 => $roster['IsMonday'],
            2 => $roster['IsTuesday'],
            3 => $roster['IsWednesday'],
            4 => $roster['IsThursday'],
            5 => $roster['IsFriday'],
        ];

        if (! \Illuminate\Support\Facades\Schema::hasColumn('AppUser_MedicalStaff', 'IsMonday')) {
            return $mapped;
        }

        $staff = $this->medicalStaffFor($name);

        if (! $staff) {
            return $mapped;
        }

        return [
            1 => (bool) $staff->IsMonday,
            2 => (bool) $staff->IsTuesday,
            3 => (bool) $staff->IsWednesday,
            4 => (bool) $staff->IsThursday,
            5 => (bool) $staff->IsFriday,
        ];
    }

    private function medicalStaffFor(string $name): ?\App\Models\AppUserMedicalStaff
    {
        $email = null;

        foreach (ClinicRoster::accounts() as $account) {
            if ($account['display'] === $name) {
                $email = $account['email'];
                break;
            }
        }

        $user = $email
            ? AppUser::query()->with('medicalStaff')->where('EmailAddress', $email)->first()
            : AppUser::query()->with('medicalStaff')->get()->first(fn (AppUser $account) => $account->fullName() === $name);

        return $user?->medicalStaff;
    }

    public function slots(string $name, string $date): array
    {
        $day = Carbon::parse($date)->startOfDay();
        $appointments = Appointment::query()
            ->where('ATTENDING_PHYSICIAN', $name)
            ->whereDate('SCHEDULED_AT', $day->toDateString())
            ->whereRaw("LOWER(COALESCE(STATUS, '')) != 'cancelled'")
            ->orderBy('SCHEDULED_AT')
            ->get();

        $schedules = DoctorSchedule::query()
            ->where('DOCTOR_NAME', $name)
            ->whereDate('AVAILABLE_DATE', $day->toDateString())
            ->get();

        $overrides = DutySlotState::query()
            ->where('PRACTITIONER_NAME', $name)
            ->whereDate('SLOT_DATE', $day->toDateString())
            ->get()
            ->keyBy(fn (DutySlotState $state) => $this->clock($state->START_TIME));

        $slots = [];
        $cursor = $day->copy()->setTime(7, 0);
        $closing = $day->copy()->setTime(19, 0);

        while ($cursor->lessThan($closing)) {
            $slotEnd = $cursor->copy()->addMinutes(30);
            $startKey = $cursor->format('H:i:s');
            $booked = $this->bookedDuring($appointments, $cursor, $slotEnd);
            $onDuty = ClinicRoster::covers($name, $cursor, $slotEnd)
                || $this->publishedCovers($schedules, $cursor, $slotEnd);
            $override = $overrides->get($startKey)?->STATUS;
            $patient = $booked->isEmpty() ? 'None' : $booked->pluck('PATIENT_NAME')->implode(', ');

            $status = match (true) {
                $override === 'break' => 'break',
                $override === 'unavailable' => 'unavailable',
                $booked->isNotEmpty() => 'occupied',
                $override === 'available', $onDuty => 'available',
                default => 'off_duty',
            };

            $slots[] = [
                'start' => $startKey,
                'end' => $slotEnd->format('H:i:s'),
                'label' => $cursor->format('g:i A').' - '.$slotEnd->format('g:i A'),
                'patient' => $patient,
                'status' => $status,
                'badge' => $this->badge($status, $patient),
                'appointment_id' => $booked->first()?->APPOINTMENT_ID,
                'patient_id' => $booked->first()?->PATIENT_ID,
                'reason' => $booked->first()?->APPOINTMENT_REASON,
                'on_duty' => $onDuty,
            ];

            $cursor = $slotEnd;
        }

        return $slots;
    }

    public function findSlot(string $name, string $date, string $start): ?array
    {
        $start = $this->clock($start);

        foreach ($this->slots($name, $date) as $slot) {
            if ($slot['start'] === $start) {
                return $slot;
            }
        }

        return null;
    }

    public function setStatus(string $name, string $date, string $start, string $status): void
    {
        $start = $this->clock($start);
        $slot = $this->findSlot($name, $date, $start);
        $key = [
            'PRACTITIONER_NAME' => $name,
            'SLOT_DATE' => Carbon::parse($date)->toDateString(),
            'START_TIME' => $start,
        ];

        if ($status === 'available' && is_array($slot) && ! empty($slot['on_duty'])) {
            DutySlotState::query()->where($key)->delete();

            return;
        }

        DutySlotState::query()->updateOrCreate($key, ['STATUS' => $status]);
    }

    public function nextStatus(string $current): string
    {
        return match ($current) {
            'available', 'occupied' => 'unavailable',
            'break' => 'available',
            default => 'break',
        };
    }

    public function saveAppointment(string $name, string $date, string $start, array $fields, ?int $appointmentId = null): Appointment
    {
        $start = $this->clock($start);
        $scheduledAt = Carbon::parse($date.' '.$start);

        if ($appointmentId) {
            $appointment = Appointment::query()->findOrFail($appointmentId);
            $appointment->update([
                'PATIENT_ID' => $fields['patient_id'] ?: $appointment->PATIENT_ID,
                'PATIENT_NAME' => $fields['patient_name'],
                'APPOINTMENT_REASON' => $fields['reason'] ?: $appointment->APPOINTMENT_REASON,
                'ATTENDING_PHYSICIAN' => $name,
                'SCHEDULED_AT' => $scheduledAt,
            ]);

            return $appointment;
        }

        return Appointment::query()->create([
            'PATIENT_ID' => $fields['patient_id'] ?: Appointment::nextPatientId(),
            'PATIENT_NAME' => $fields['patient_name'],
            'APPOINTMENT_TYPE' => 'Consultation',
            'APPOINTMENT_REASON' => $fields['reason'] ?: 'Booked from duty schedule',
            'ATTENDING_PHYSICIAN' => $name,
            'SCHEDULED_AT' => $scheduledAt,
            'STATUS' => 'Scheduled',
        ]);
    }

    public function clearAppointments(string $name, string $date, string $start): void
    {
        $slot = $this->findSlot($name, $date, $start);

        if (! $slot || ! $slot['appointment_id']) {
            return;
        }

        $windowStart = Carbon::parse($date.' '.$slot['start']);
        $windowEnd = Carbon::parse($date.' '.$slot['end']);

        Appointment::query()
            ->where('ATTENDING_PHYSICIAN', $name)
            ->whereDate('SCHEDULED_AT', Carbon::parse($date)->toDateString())
            ->get()
            ->filter(function (Appointment $appointment) use ($windowStart, $windowEnd) {
                $at = Carbon::parse($appointment->SCHEDULED_AT);

                return $at->greaterThanOrEqualTo($windowStart) && $at->lessThan($windowEnd);
            })
            ->each->delete();
    }

    private function bookedDuring(Collection $appointments, Carbon $start, Carbon $end): Collection
    {
        return $appointments->filter(function (Appointment $appointment) use ($start, $end) {
            $at = Carbon::parse($appointment->SCHEDULED_AT);

            return $at->greaterThanOrEqualTo($start) && $at->lessThan($end);
        })->values();
    }

    private function publishedCovers(Collection $schedules, Carbon $start, Carbon $end): bool
    {
        foreach ($schedules as $schedule) {
            $windowStart = $start->copy()->setTimeFromTimeString((string) $schedule->START_TIME);
            $windowEnd = $start->copy()->setTimeFromTimeString((string) $schedule->END_TIME);

            if ($start->greaterThanOrEqualTo($windowStart) && $end->lessThanOrEqualTo($windowEnd)) {
                return true;
            }
        }

        return false;
    }

    private function badge(string $status, string $patient): string
    {
        return match ($status) {
            'available' => 'Available',
            'occupied' => 'Occupied - '.$patient,
            'break' => 'On Break',
            default => 'Off Duty / Not Available',
        };
    }

    private function clock(string $time): string
    {
        return Carbon::parse($time)->format('H:i:s');
    }
}
