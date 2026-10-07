<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\DoctorSchedule;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Appointment::query();

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

        $appointments = $query->orderBy('SCHEDULED_AT', 'asc')->get();

        // Fetch upcoming doctor availability
        $schedules = DoctorSchedule::where('AVAILABLE_DATE', '>=', Carbon::today())
            ->orderBy('AVAILABLE_DATE', 'asc')
            ->get();

        return view('appointments.index', compact('appointments', 'schedules'));
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

        DoctorSchedule::create($validated);

        return redirect('/appointments')->with('success', 'Doctor availability schedule added!');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
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
        $appointment = Appointment::findOrFail($id);

        $validated = $request->validate([
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
        $appointment = Appointment::findOrFail($id);
        $appointment->delete();

        return redirect('/appointments')->with('success', 'Appointment removed successfully!');
    }
}