<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\MedicalRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MedicalRecordController extends Controller
{
    public function index()
    {
        $medicalRecords = MedicalRecord::orderBy('MEDREC_CONSUL_DATE', 'desc')->get();
        $appointments = Appointment::query()->orderBy('PATIENT_NAME')->orderByDesc('SCHEDULED_AT')->get();
        $patients = $appointments
            ->unique(fn (Appointment $appointment) => (string) $appointment->PATIENT_ID)
            ->map(fn (Appointment $appointment) => [
                'id' => (string) $appointment->PATIENT_ID,
                'name' => $appointment->PATIENT_NAME ?: 'Patient '.$appointment->PATIENT_ID,
            ])
            ->sortBy('name')
            ->values();
        $appointmentChoices = $appointments->map(fn (Appointment $appointment) => [
            'id' => $appointment->APPOINTMENT_ID,
            'patient_id' => (string) $appointment->PATIENT_ID,
            'label' => '#'.$appointment->APPOINTMENT_ID.' — '.Carbon::parse($appointment->SCHEDULED_AT)->format('M d, Y h:i A'),
        ])->values();

        return view('medical-records.index', compact('medicalRecords', 'patients', 'appointmentChoices'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedRecord($request);

        MedicalRecord::create($validated);

        return redirect()->back()->with('success', 'Medical record added successfully.');
    }

    public function update(Request $request, $id)
    {
        $record = MedicalRecord::findOrFail($id);
        $record->update($this->validatedRecord($request));

        return redirect()->back()->with('success', 'Medical record updated.');
    }

    private function validatedRecord(Request $request): array
    {
        $validated = $request->validate([
            'PATIENT_ID' => 'required',
            'APPT_ID' => 'required|integer',
            'MEDREC_CONSUL_DATE' => 'required|date',
            'MEDREC_DIAGNOSIS' => 'required|string|max:250',
            'MEDREC_MEDICINE_DOSAGE' => 'nullable|string|max:100',
            'MEDREC_NOTES' => 'nullable|string|max:45',
        ]);

        $appointment = Appointment::query()->find($validated['APPT_ID']);

        if (! $appointment || (string) $appointment->PATIENT_ID !== (string) $validated['PATIENT_ID']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'APPT_ID' => 'Choose an appointment for that patient.',
            ]);
        }

        $validated['PATIENT_ID'] = (int) $appointment->PATIENT_ID;
        $validated['APPT_ID'] = $appointment->APPOINTMENT_ID;

        return $validated;
    }

    public function destroy($id)
    {
        $record = MedicalRecord::findOrFail($id);
        $record->delete();

        return redirect()->back()->with('success', 'Medical record removed successfully.');
    }
}