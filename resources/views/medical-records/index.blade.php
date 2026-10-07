@extends('layouts.app')

@section('title', 'RAM-CIMS - Medical Records')

@section('content')
<div class="container-fluid px-0">

    <!-- Medical Records Table Section -->
    <div class="card mb-4 shadow-sm border-0 rounded-3 overflow-hidden">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0 fw-bold">Medical Records Management</h5>
            <button class="btn btn-sm btn-light fw-semibold rounded-2" data-bs-toggle="modal" data-bs-target="#addMedicalRecordModal">+ Add Medical Record</button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Record ID</th>
                            <th>Consultation Date</th>
                            <th>Patient ID</th>
                            <th>Appointment ID</th>
                            <th>Diagnosis</th>
                            <th>Medicine Dosage</th>
                            <th>Notes</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($medicalRecords as $record)
                        <tr>
                            <td class="fw-bold">#{{ $record->MEDREC_ID }}</td>
                            <td>{{ \Carbon\Carbon::parse($record->MEDREC_CONSUL_DATE)->format('M d, Y') }}</td>
                            <td>{{ $record->PATIENT_ID }}</td>
                            <td>
                                @if($record->APPT_ID)
                                    <span class="badge bg-secondary">#{{ $record->APPT_ID }}</span>
                                @else
                                    <span class="text-muted">Walk-in</span>
                                @endif
                            </td>
                            <td class="fw-semibold text-primary">{{ $record->MEDREC_DIAGNOSIS }}</td>
                            <td>{{ $record->MEDREC_MEDICINE_DOSAGE ?? 'None' }}</td>
                            <td class="text-muted">{{ $record->MEDREC_NOTES ?? 'N/A' }}</td>
                            <td class="text-center">
                                <form action="/medical-records/{{ $record->MEDREC_ID }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this medical record?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger fw-semibold rounded-2">Remove</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No medical records found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Medical Record Modal -->
<div class="modal fade" id="addMedicalRecordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="/medical-records" method="POST" class="modal-content">
            @csrf
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold">Add New Medical Record</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Patient ID</label>
                    <input type="number" name="PATIENT_ID" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Appointment ID</label>
                    <input type="number" name="APPT_ID" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Consultation Date</label>
                    <input type="date" name="MEDREC_CONSUL_DATE" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Diagnosis</label>
                    <textarea name="MEDREC_DIAGNOSIS" class="form-control" rows="2" maxlength="250" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Medicine Dosage</label>
                    <input type="text" name="MEDREC_MEDICINE_DOSAGE" class="form-control" maxlength="100">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Notes</label>
                    <input type="text" name="MEDREC_NOTES" class="form-control" maxlength="45">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Record</button>
            </div>
        </form>
    </div>
</div>
@endsection