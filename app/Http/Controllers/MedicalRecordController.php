<?php

namespace App\Http\Controllers;

use App\Models\MedicalRecord;
use Illuminate\Http\Request;

class MedicalRecordController extends Controller
{
    public function index()
    {
        $medicalRecords = MedicalRecord::orderBy('MEDREC_CONSUL_DATE', 'desc')->get();
        return view('medical-records.index', compact('medicalRecords')); // Updated view path
    }

    public function store(Request $request)
    {
        $request->validate([
            'PATIENT_ID' => 'required|integer',
            'APPT_ID' => 'required|integer', // Changed from nullable to required
            'MEDREC_CONSUL_DATE' => 'required|date',
            'MEDREC_DIAGNOSIS' => 'required|string|max:250',
            'MEDREC_MEDICINE_DOSAGE' => 'nullable|string|max:100',
            'MEDREC_NOTES' => 'nullable|string|max:45',
        ]);

        MedicalRecord::create($request->all());

        return redirect()->back()->with('success', 'Medical record added successfully.');
    }

    public function destroy($id)
    {
        $record = MedicalRecord::findOrFail($id);
        $record->delete();

        return redirect()->back()->with('success', 'Medical record removed successfully.');
    }
}