<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\DoctorSchedule;
use App\Models\DutySlotState;
use App\Support\ClinicAccess;
use App\Support\ClinicCalendar;
use App\Support\ClinicRoster;
use App\Support\DutySlotGuard;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{
    public function __construct(private ClinicAccess $access, private DutySlotGuard $slots) {}

    public function index(Request $request)
    {
        $query = Appointment::query();
        $isClinicStaff = $this->access->isStaff();

        if ($isClinicStaff) {
            if ($request->filled('search_id')) {
                $query->where('APPOINTMENT_ID', $request->search_id);
            }
            if ($request->filled('search_patient')) {
                $query->where('PATIENT_NAME', 'LIKE', '%'.$request->search_patient.'%');
            }
            if ($request->filled('search_type')) {
                $query->where('APPOINTMENT_TYPE', $request->search_type);
            }
            if ($request->filled('search_doctor')) {
                $query->where('ATTENDING_PHYSICIAN', 'LIKE', '%'.$request->search_doctor.'%');
            }
        } else {
            $account = $this->access->account();
            $account
                ? $query->forPatient($account)
                : $query->whereRaw('0 = 1');
        }

        $weekStart = ClinicCalendar::weekStart()->format('Y-m-d H:i:s');
        $showingHistory = $request->boolean('history');

        if ($showingHistory) {
            $query->where('SCHEDULED_AT', '<', $weekStart);
        } else {
            $query->where('SCHEDULED_AT', '>=', $weekStart);
        }

        $appointments = $query->orderBy('SCHEDULED_AT')->orderBy('APPOINTMENT_ID')->paginate(25)->withQueryString();

        return view('appointments.index', [
            'appointments' => $appointments,
            'isClinicStaff' => $isClinicStaff,
            'showingHistory' => $showingHistory,
            'nextPatientId' => Appointment::nextPatientId(),
            'nextAppointmentId' => Appointment::nextAppointmentId(),
        ]);
    }

    public function dutyDates(Request $request)
    {
        $data = $request->validate([
            'physician' => ['required', 'string', Rule::in(ClinicRoster::names())],
            'appointment_id' => 'nullable|integer',
        ]);

        $ignoreId = isset($data['appointment_id']) ? (int) $data['appointment_id'] : null;
        $start = ClinicCalendar::weekStart();
        $dates = [];

        for ($offset = 0; $offset < 56; $offset++) {
            $day = $start->copy()->addDays($offset);
            if ($day->isWeekend()) {
                continue;
            }

            $slots = $this->bookableSlots($data['physician'], $day->copy()->startOfDay(), $ignoreId);
            if ($slots === []) {
                continue;
            }

            $dates[] = [
                'value' => $day->toDateString(),
                'label' => $day->format('l, M j, Y'),
                'open_slots' => count($slots),
            ];
        }

        return response()->json(['dates' => $dates]);
    }

    public function slots(Request $request)
    {
        $data = $request->validate([
            'physician' => ['required', 'string', Rule::in(ClinicRoster::names())],
            'date' => 'required|date',
            'appointment_id' => 'nullable|integer',
        ]);

        $date = Carbon::parse($data['date'])->startOfDay();
        $slots = $this->bookableSlots(
            $data['physician'],
            $date,
            isset($data['appointment_id']) ? (int) $data['appointment_id'] : null
        );

        return response()->json(['slots' => $slots]);
    }

    public function storeSchedule(Request $request)
    {
        $validated = $request->validate([
            'DOCTOR_NAME' => 'required|string|max:255',
            'AVAILABLE_DATE' => 'required|date|after_or_equal:today',
            'START_TIME' => 'required',
            'END_TIME' => 'required',
            'NOTES' => 'nullable|string',
        ]);

        DoctorSchedule::create($validated);

        return redirect('/appointments')->with('success', 'Doctor availability schedule added!');
    }

    public function store(Request $request)
    {
        if (! $this->access->isStaff()) {
            $account = $this->access->account();
            abort_unless($account, 403);

            $request->merge([
                'PATIENT_ID' => $account->Student_Employee_No ?: (string) $account->Id,
                'PATIENT_NAME' => $account->fullName(),
                'STATUS' => 'Scheduled',
            ]);
        }

        $validated = $request->validate([
            'PATIENT_ID' => 'required|string|max:255',
            'PATIENT_NAME' => 'required|string|max:255',
            'APPOINTMENT_TYPE' => 'required|string|max:255',
            'APPOINTMENT_REASON' => 'required|string',
            'ATTENDING_PHYSICIAN' => ['required', 'string', Rule::in(ClinicRoster::names())],
            'SCHEDULED_AT' => 'required|date',
            'STATUS' => 'nullable|string',
        ]);

        $result = $this->slots->run(
            $validated['ATTENDING_PHYSICIAN'],
            $validated['SCHEDULED_AT'],
            function () use ($validated) {
                if ($message = $this->dutyError($validated['ATTENDING_PHYSICIAN'], $validated['SCHEDULED_AT'])) {
                    return $message;
                }

                Appointment::create($validated);

                return true;
            }
        );

        if ($result !== true) {
            $message = is_string($result)
                ? $result
                : 'That duty slot is already booked. Choose another time.';

            return back()->withErrors(['SCHEDULED_AT' => $message])->withInput();
        }

        return redirect('/appointments')->with('success', 'Appointment scheduled successfully!');
    }

    public function cancel(Appointment $appointment)
    {
        abort_if($this->access->isStaff(), 403);

        $account = $this->access->account();
        abort_unless($account, 403);
        abort_unless(
            Appointment::query()->forPatient($account)->whereKey($appointment->getKey())->exists(),
            403
        );

        $released = $this->slots->run(
            (string) $appointment->ATTENDING_PHYSICIAN,
            (string) $appointment->SCHEDULED_AT,
            function () use ($appointment) {
                $current = Appointment::query()->whereKey($appointment->getKey())->lockForUpdate()->first();

                if (! $current || ! $current->studentCanCancel()) {
                    return false;
                }

                $current->update(['STATUS' => 'Cancelled']);

                return true;
            }
        );

        if ($released !== true) {
            return redirect('/appointments')->with('error', 'This appointment can no longer be cancelled.');
        }

        return redirect('/appointments')->with('success', 'Your appointment has been cancelled successfully.');
    }

    public function update(Request $request, $id)
    {
        $this->ensureStaff();

        $appointment = Appointment::findOrFail($id);

        $validated = $request->validate([
            'PATIENT_ID' => 'required|string|max:255',
            'PATIENT_NAME' => 'required|string|max:255',
            'APPOINTMENT_TYPE' => 'required|string|max:255',
            'APPOINTMENT_REASON' => 'required|string',
            'ATTENDING_PHYSICIAN' => ['required', 'string', Rule::in(ClinicRoster::names())],
            'SCHEDULED_AT' => 'required|date',
            'STATUS' => 'required|string',
        ]);

        $result = $this->slots->run(
            $validated['ATTENDING_PHYSICIAN'],
            $validated['SCHEDULED_AT'],
            function () use ($appointment, $validated) {
                if ($message = $this->dutyError($validated['ATTENDING_PHYSICIAN'], $validated['SCHEDULED_AT'], (int) $appointment->APPOINTMENT_ID)) {
                    return $message;
                }

                $appointment->update($validated);

                return true;
            }
        );

        if ($result !== true) {
            $message = is_string($result)
                ? $result
                : 'That duty slot is already booked. Choose another time.';

            return response()->json([
                'message' => $message,
                'errors' => ['SCHEDULED_AT' => [$message]],
            ], 422);
        }

        return response()->json(['message' => 'Appointment schedule updated successfully!']);
    }

    public function destroy($id)
    {
        $this->ensureStaff();

        $appointment = Appointment::findOrFail($id);
        $appointment->delete();

        return redirect('/appointments')->with('success', 'Appointment removed successfully!');
    }

    private function ensureStaff(): void
    {
        abort_unless($this->access->isStaff(), 403, 'Clinic staff access only.');
    }

    private function dutyError(string $physician, string $scheduledAt, ?int $ignoreId = null): ?string
    {
        try {
            $start = Carbon::parse($scheduledAt)->seconds(0);
        } catch (\Throwable) {
            return 'Choose a time from this physician\'s duty schedule.';
        }

        if (! ClinicRoster::hasSlot($physician, $start)) {
            return 'That physician has no duty schedule at this date and time.';
        }

        if (! $this->access->isStaff() && $start->lessThanOrEqualTo(now())) {
            return 'Choose an upcoming duty time slot.';
        }

        $blocked = DutySlotState::query()
            ->where('PRACTITIONER_NAME', $physician)
            ->whereDate('SLOT_DATE', $start->toDateString())
            ->where('START_TIME', $start->format('H:i:s'))
            ->where('STATUS', 'unavailable')
            ->exists();

        if ($blocked) {
            return 'That duty slot was marked unavailable. Choose another time.';
        }

        $taken = Appointment::query()
            ->where('ATTENDING_PHYSICIAN', $physician)
            ->where('SCHEDULED_AT', $start->format('Y-m-d H:i:s'))
            ->whereRaw("LOWER(COALESCE(STATUS, '')) != 'cancelled'")
            ->when($ignoreId, fn ($query) => $query->where('APPOINTMENT_ID', '!=', $ignoreId))
            ->exists();

        if ($taken) {
            return 'That duty slot is already booked. Choose another time.';
        }

        return null;
    }

    private function openSlots(string $physician, Carbon $date, ?int $ignoreId = null): array
    {
        $taken = Appointment::query()
            ->where('ATTENDING_PHYSICIAN', $physician)
            ->whereDate('SCHEDULED_AT', $date->toDateString())
            ->whereRaw("LOWER(COALESCE(STATUS, '')) != 'cancelled'")
            ->when($ignoreId, fn ($query) => $query->where('APPOINTMENT_ID', '!=', $ignoreId))
            ->pluck('SCHEDULED_AT')
            ->map(fn ($at) => Carbon::parse($at)->format('Y-m-d H:i'))
            ->all();

        $blocked = DutySlotState::query()
            ->where('PRACTITIONER_NAME', $physician)
            ->whereDate('SLOT_DATE', $date->toDateString())
            ->where('STATUS', 'unavailable')
            ->pluck('START_TIME')
            ->map(fn ($time) => Carbon::parse($time)->format('H:i:s'))
            ->all();

        return array_values(array_filter(
            ClinicRoster::slotsOn($physician, $date),
            function (array $slot) use ($taken, $blocked) {
                $time = Carbon::parse($slot['value'])->format('H:i:s');

                return ! in_array(substr($slot['value'], 0, 16), $taken, true)
                    && ! in_array($time, $blocked, true);
            }
        ));
    }

    private function bookableSlots(string $physician, Carbon $date, ?int $ignoreId = null): array
    {
        $slots = $this->openSlots($physician, $date, $ignoreId);

        if ($this->access->isStaff()) {
            return $slots;
        }

        return array_values(array_filter(
            $slots,
            fn (array $slot) => ClinicCalendar::parse($slot['value'])->greaterThan(ClinicCalendar::now())
        ));
    }
}
