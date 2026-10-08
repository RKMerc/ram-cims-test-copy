<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Support\ClinicAccess;
use App\Support\DeveloperMode;
use App\Support\DutyBoard;
use App\Support\ScheduleAlerts;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DutySlotController extends Controller
{
    public function __construct(private DutyBoard $board, private ClinicAccess $access)
    {
    }

    public function book(Request $request)
    {
        $data = $this->validated($request);
        $slot = $this->board->findSlot($data['practitioner'], $data['duty_date'], $data['start']);

        if (! $slot) {
            return $this->back($request)->with('error', 'That time slot is not on the duty board.');
        }

        if ($slot['status'] !== 'available') {
            return $this->back($request)->with('error', 'That time slot is not available.');
        }

        if ($this->access->isStaff()) {
            $request->validate([
                'patient_name' => 'required|string|max:255',
                'patient_id' => 'nullable|string|max:255',
                'reason' => 'nullable|string|max:255',
            ]);
            $fields = [
                'patient_name' => $request->input('patient_name'),
                'patient_id' => $request->input('patient_id'),
                'reason' => $request->input('reason'),
            ];
        } else {
            $account = $this->access->account();
            abort_unless($account, 403);
            $fields = [
                'patient_name' => $account->fullName(),
                'patient_id' => $account->Student_Employee_No ?: (string) $account->Id,
                'reason' => $request->input('reason') ?: 'Booked from duty schedule',
            ];
        }

        $this->board->saveAppointment($data['practitioner'], $data['duty_date'], $data['start'], $fields);

        return $this->back($request)->with('success', 'Appointment booked for '.$fields['patient_name'].'.');
    }

    public function cancel(Request $request)
    {
        abort_unless($this->access->isStaff(), 403);

        $data = $this->validated($request);
        $request->validate([
            'appointment_id' => 'required|integer',
        ]);

        $appointment = Appointment::query()->findOrFail($request->integer('appointment_id'));

        if ($appointment->ATTENDING_PHYSICIAN !== $data['practitioner']) {
            return $this->back($request)->with('error', 'That visit is not on this physician\'s schedule.');
        }

        $appointment->update(['STATUS' => 'Cancelled']);
        ScheduleAlerts::release(
            (string) $appointment->ATTENDING_PHYSICIAN,
            Carbon::parse($appointment->SCHEDULED_AT)->toDateString()
        );

        return $this->back($request)->with('success', 'The visit for '.$appointment->PATIENT_NAME.' was cancelled. That hour is open again.');
    }

    public function toggle(Request $request)
    {
        abort_unless(DeveloperMode::enabled(), 403, 'Slot status can be toggled in developer mode.');

        $data = $this->validated($request);
        $slot = $this->board->findSlot($data['practitioner'], $data['duty_date'], $data['start']);

        if (! $slot) {
            return $this->respond($request, 'That time slot is not on the duty board.', false);
        }

        $next = $this->board->nextStatus($slot['status']);
        $this->board->setStatus($data['practitioner'], $data['duty_date'], $data['start'], $next);

        return $this->respond($request, 'Slot status updated.', true, ['status' => $next]);
    }

    public function assign(Request $request)
    {
        abort_unless(DeveloperMode::enabled() || $this->access->isStaff(), 403);

        $data = $this->validated($request);
        $request->validate([
            'patient_name' => 'required_unless:clear,1|nullable|string|max:255',
            'patient_id' => 'nullable|string|max:255',
            'reason' => 'nullable|string|max:255',
            'clear' => 'nullable|boolean',
        ]);

        $slot = $this->board->findSlot($data['practitioner'], $data['duty_date'], $data['start']);

        if (! $slot) {
            return $this->back($request)->with('error', 'That time slot is not on the duty board.');
        }

        if ($request->boolean('clear')) {
            abort_unless(DeveloperMode::enabled(), 403);
            $this->board->clearAppointments($data['practitioner'], $data['duty_date'], $data['start']);
            ScheduleAlerts::release($data['practitioner'], $data['duty_date']);

            return $this->back($request)->with('success', 'Appointment cleared from this slot.');
        }

        if (! DeveloperMode::enabled() && ! $slot['appointment_id']) {
            return $this->back($request)->with('error', 'That slot has no appointment to edit.');
        }

        $this->board->saveAppointment(
            $data['practitioner'],
            $data['duty_date'],
            $data['start'],
            [
                'patient_name' => $request->input('patient_name'),
                'patient_id' => $request->input('patient_id'),
                'reason' => $request->input('reason'),
            ],
            $slot['appointment_id'] ? (int) $slot['appointment_id'] : null
        );

        return $this->back($request)->with('success', 'Slot appointment saved.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'practitioner' => 'required|string|max:255',
            'duty_date' => 'required|date',
            'start' => 'required|date_format:H:i:s',
            'return_to' => 'nullable|string|max:2000',
        ]);
    }

    private function respond(Request $request, string $message, bool $ok, array $extra = [])
    {
        if ($request->expectsJson()) {
            return response()->json(array_merge(['message' => $message], $extra), $ok ? 200 : 422);
        }

        return $this->back($request)->with($ok ? 'success' : 'error', $message);
    }

    private function back(Request $request)
    {
        $target = (string) $request->input('return_to', '/appointments');

        if (! str_starts_with($target, '/') || str_starts_with($target, '//')) {
            $target = '/appointments';
        }

        return redirect()->to($target);
    }
}
