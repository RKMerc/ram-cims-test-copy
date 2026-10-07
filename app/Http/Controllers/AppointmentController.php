<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\DoctorSchedule;
use App\Models\ScheduleReminder;
use App\Support\ClinicAccess;
use App\Support\ScheduleAlerts;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    public function __construct(private ClinicAccess $access)
    {
    }

    public function index(Request $request)
    {
        $query = Appointment::query();
        $isClinicStaff = $this->access->isStaff();

        if ($isClinicStaff) {
            if ($request->filled('search_id')) {
                $query->where('APPOINTMENT_ID', $request->search_id);
            }
            if ($request->filled('search_patient')) {
                $query->where('PATIENT_NAME', 'LIKE', '%' . $request->search_patient . '%');
            }
            if ($request->filled('search_type')) {
                $query->where('APPOINTMENT_TYPE', $request->search_type);
            }
            if ($request->filled('search_doctor')) {
                $query->where('ATTENDING_PHYSICIAN', 'LIKE', '%' . $request->search_doctor . '%');
            }
        } else {
            $account = $this->access->account();
            $account
                ? $query->forPatient($account)
                : $query->whereRaw('0 = 1');
        }

        $appointments = $query->orderBy('SCHEDULED_AT', 'asc')->get();

        $schedules = $isClinicStaff
            ? DoctorSchedule::where('AVAILABLE_DATE', '>=', Carbon::today())
                ->orderBy('AVAILABLE_DATE', 'asc')
                ->get()
            : collect();

        return view('appointments.index', [
            'appointments' => $appointments,
            'schedules' => $schedules,
            'isClinicStaff' => $isClinicStaff,
            'nextPatientId' => Appointment::nextPatientId(),
            'nextAppointmentId' => Appointment::nextAppointmentId(),
        ]);
    }

    public function storeSchedule(Request $request)
    {
        $validated = $request->validate([
            'DOCTOR_NAME'    => 'required|string|max:255',
            'AVAILABLE_DATE' => 'required|date|after_or_equal:today',
            'START_TIME'     => 'required',
            'END_TIME'       => 'required',
            'NOTES'          => 'nullable|string'
        ]);

        $waiting = ScheduleReminder::whereNull('notified_at')->count();
        DoctorSchedule::create($validated);
        $sent = ScheduleAlerts::release($validated['DOCTOR_NAME'], $validated['AVAILABLE_DATE'], true);

        $message = 'Doctor availability schedule added!';
        if ($sent > 0) {
            $message .= ' '.$sent.' email alert'.($sent === 1 ? ' was' : 's were').' sent.';
        } elseif ($waiting > 0) {
            $message .= ' '.$waiting.' email reminder'.($waiting === 1 ? '' : 's').' are waiting for an opening.';
        }

        return redirect('/appointments')->with('success', $message);
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
            'PATIENT_ID'          => 'required|string|max:255',
            'PATIENT_NAME'        => 'required|string|max:255',
            'APPOINTMENT_TYPE'    => 'required|string|max:255',
            'APPOINTMENT_REASON'  => 'required|string',
            'ATTENDING_PHYSICIAN' => 'required|string|max:255',
            'SCHEDULED_AT'        => 'required|date',
            'STATUS'              => 'nullable|string'
        ]);

        Appointment::create($validated);

        return redirect('/appointments')->with('success', 'Appointment scheduled successfully!');
    }

    public function update(Request $request, $id)
    {
        $this->ensureStaff();

        $appointment = Appointment::findOrFail($id);

        $validated = $request->validate([
            'PATIENT_ID'          => 'required|string|max:255',
            'PATIENT_NAME'        => 'required|string|max:255',
            'APPOINTMENT_TYPE'    => 'required|string|max:255',
            'APPOINTMENT_REASON'  => 'required|string',
            'ATTENDING_PHYSICIAN' => 'required|string|max:255',
            'SCHEDULED_AT'        => 'required|date',
            'STATUS'              => 'required|string'
        ]);

        $appointment->update($validated);

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
}