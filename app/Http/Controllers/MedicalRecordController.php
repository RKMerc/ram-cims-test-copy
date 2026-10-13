<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppUser;
use App\Models\MedicalRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MedicalRecordController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $records = MedicalRecord::query()->orderByDesc('MEDREC_CONSUL_DATE');

        if ($search !== '') {
            $like = '%'.addcslashes($search, '%_\\').'%';
            $patientIds = AppUser::query()
                ->where(function ($query) use ($like) {
                    $query->where('FirstName', 'like', $like)
                        ->orWhere('MiddleName', 'like', $like)
                        ->orWhere('LastName', 'like', $like)
                        ->orWhere('Student_Employee_No', 'like', $like);
                })
                ->get()
                ->flatMap(fn (AppUser $account) => $account->recordIds())
                ->all();

            $appointmentPatientIds = Appointment::query()
                ->where('PATIENT_NAME', 'like', $like)
                ->pluck('PATIENT_ID')
                ->map(fn ($id) => (string) $id)
                ->all();

            $records->where(function ($query) use ($like, $search, $patientIds, $appointmentPatientIds) {
                $query->where('PATIENT_ID', 'like', $like);

                if (ctype_digit($search)) {
                    $query->orWhere('MEDREC_ID', (int) $search);
                }

                if (strtotime($search) !== false && preg_match('/\d{4}-\d{2}-\d{2}/', $search)) {
                    $query->orWhereDate('MEDREC_CONSUL_DATE', $search);
                }

                $ids = array_values(array_unique(array_merge($patientIds, $appointmentPatientIds)));
                if ($ids !== []) {
                    $query->orWhereIn('PATIENT_ID', $ids);
                }
            });
        }

        $medicalRecords = $records->paginate(20)->withQueryString();
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
            'label' => '#'.$appointment->APPOINTMENT_ID,
            'date' => Carbon::parse($appointment->SCHEDULED_AT)->format('Y-m-d'),
        ])->values();

        return view('medical-records.index', compact('medicalRecords', 'patients', 'appointmentChoices', 'search'));
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

        if ($record->APPT_ID) {
            $request->merge(['APPT_ID' => $record->APPT_ID]);
        }

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
            throw ValidationException::withMessages([
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