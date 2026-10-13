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
        <div class="card-body border-bottom">
            <form method="GET" action="{{ url('/medical-records') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-6">
                    <label for="record-search" class="form-label fw-semibold mb-1">Search records</label>
                    <input type="search" name="q" id="record-search" value="{{ $search ?? '' }}" class="form-control" placeholder="Patient name, number, record ID, or date">
                </div>
                <div class="col-12 col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary fw-semibold">Search</button>
                    @if(($search ?? '') !== '')
                        <a href="{{ url('/medical-records') }}" class="btn btn-outline-secondary">Clear</a>
                    @endif
                </div>
            </form>
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
                                <button type="button" class="btn btn-sm btn-outline-primary fw-semibold rounded-2"
                                    data-bs-toggle="modal" data-bs-target="#editMedicalRecordModal"
                                    data-id="{{ $record->MEDREC_ID }}"
                                    data-patient="{{ $record->PATIENT_ID }}"
                                    data-appointment="{{ $record->APPT_ID }}"
                                    data-date="{{ \Carbon\Carbon::parse($record->MEDREC_CONSUL_DATE)->format('Y-m-d') }}"
                                    data-diagnosis="{{ $record->MEDREC_DIAGNOSIS }}"
                                    data-dosage="{{ $record->MEDREC_MEDICINE_DOSAGE }}"
                                    data-notes="{{ $record->MEDREC_NOTES }}">Edit</button>
                                <form action="/medical-records/{{ $record->MEDREC_ID }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this medical record?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger fw-semibold rounded-2">Remove</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">{{ ($search ?? '') !== '' ? 'No medical records match that search.' : 'No medical records found.' }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($medicalRecords->hasPages())
                <div class="p-3 border-top">{{ $medicalRecords->links() }}</div>
            @endif
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
                    <label class="form-label fw-semibold" for="add_record_patient">Patient name</label>
                    <select name="PATIENT_ID" id="add_record_patient" class="form-select" required>
                        <option value="" selected disabled>Select a patient</option>
                        @foreach($patients as $patient)
                            <option value="{{ $patient['id'] }}">{{ $patient['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="add_record_appointment">Appointment ID</label>
                    <select name="APPT_ID" id="add_record_appointment" class="form-select" required>
                        <option value="" selected disabled>Select a patient first</option>
                    </select>
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
                    <label class="form-label fw-semibold">Prescription / Medicine Dosage</label>
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

<!-- Edit Medical Record Modal -->
<div class="modal fade" id="editMedicalRecordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="editMedicalRecordForm" action="/medical-records/0" class="modal-content">
            @csrf
            @method('PUT')
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold">Edit Medical Record</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="edit_record_patient">Patient name</label>
                    <select name="PATIENT_ID" id="edit_record_patient" class="form-select" required>
                        <option value="" disabled>Select a patient</option>
                        @foreach($patients as $patient)
                            <option value="{{ $patient['id'] }}">{{ $patient['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="edit_record_appointment">Appointment ID</label>
                    <input type="text" id="edit_record_appointment_display" class="form-control bg-light" readonly>
                    <input type="hidden" name="APPT_ID" id="edit_record_appointment_id">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="edit_record_date">Consultation Date</label>
                    <input type="date" name="MEDREC_CONSUL_DATE" id="edit_record_date" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="edit_record_diagnosis">Diagnosis</label>
                    <textarea name="MEDREC_DIAGNOSIS" id="edit_record_diagnosis" class="form-control" rows="2" maxlength="250" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="edit_record_dosage">Prescription / Medicine Dosage</label>
                    <input type="text" name="MEDREC_MEDICINE_DOSAGE" id="edit_record_dosage" class="form-control" maxlength="100">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="edit_record_notes">Notes</label>
                    <input type="text" name="MEDREC_NOTES" id="edit_record_notes" class="form-control" maxlength="45">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Record</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const appointmentChoices = @json($appointmentChoices);

function fillAppointmentOptions(patientSelect, appointmentSelect, selectedId) {
    const patientId = patientSelect.value;
    appointmentSelect.innerHTML = '';
    const placeholder = new Option(patientId ? 'Select an appointment' : 'Select a patient first', '');
    placeholder.disabled = true;
    placeholder.selected = !selectedId;
    appointmentSelect.add(placeholder);

    appointmentChoices
        .filter(function (item) { return String(item.patient_id) === String(patientId); })
        .forEach(function (item) {
            const option = new Option(item.label, item.id);
            if (String(item.id) === String(selectedId)) {
                option.selected = true;
            }
            appointmentSelect.add(option);
        });
}

document.getElementById('add_record_patient')?.addEventListener('change', function () {
    fillAppointmentOptions(this, document.getElementById('add_record_appointment'));
});

document.getElementById('edit_record_patient')?.addEventListener('change', function () {
    fillAppointmentOptions(this, document.getElementById('edit_record_appointment'));
});

document.getElementById('editMedicalRecordModal')?.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    if (!button) return;
    document.getElementById('editMedicalRecordForm').action = '/medical-records/' + button.getAttribute('data-id');
    
    const patientSelect = document.getElementById('edit_record_patient');
    patientSelect.value = button.getAttribute('data-patient') || '';
    const apptId = button.getAttribute('data-appointment');
    document.getElementById('edit_record_appointment_id').value = apptId;
    const matchedAppt = appointmentChoices.find(function(item) {
        return String(item.id) === String(apptId);
    });
    
    document.getElementById('edit_record_appointment_display').value = matchedAppt ? matchedAppt.label : ('#' + apptId);
    document.getElementById('edit_record_date').value = button.getAttribute('data-date') || '';
    document.getElementById('edit_record_diagnosis').value = button.getAttribute('data-diagnosis') || '';
    document.getElementById('edit_record_dosage').value = button.getAttribute('data-dosage') || '';
    document.getElementById('edit_record_notes').value = button.getAttribute('data-notes') || '';
});
</script>
@endpush