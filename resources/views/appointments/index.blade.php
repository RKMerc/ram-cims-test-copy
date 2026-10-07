@extends('layouts.app')

@section('title', 'RAM-CIMS - Appointments')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-primary">Clinic Appointments</h2>
    </div>

    <!-- Filter/Search Bar -->
    <div class="card mb-4 shadow-sm border-0 rounded-3">
        <div class="card-body">
            <form method="GET" action="/appointments" class="row g-3">
                <div class="col-md-2">
                    <label for="search_id" class="form-label fw-semibold">Appt ID</label>
                    <input type="text" name="search_id" id="search_id" class="form-control rounded-2" placeholder="ID" value="{{ request('search_id') }}">
                </div>
                <div class="col-md-3">
                    <label for="search_patient" class="form-label fw-semibold">Patient Name</label>
                    <input type="text" name="search_patient" id="search_patient" class="form-control rounded-2" placeholder="Search patient..." value="{{ request('search_patient') }}">
                </div>
                <div class="col-md-3">
                    <label for="search_type" class="form-label fw-semibold">Type</label>
                    <input type="text" name="search_type" id="search_type" class="form-control rounded-2" placeholder="e.g. Consultation" value="{{ request('search_type') }}">
                </div>
                <div class="col-md-2">
                    <label for="search_doctor" class="form-label fw-semibold">Attending Physician</label>
                    <input type="text" name="search_doctor" id="search_doctor" class="form-control rounded-2" placeholder="Doctor name" value="{{ request('search_doctor') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100 rounded-2">Filter</button>
                    <a href="/appointments" class="btn btn-outline-secondary rounded-2">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Appointments Table -->
    <div class="card mb-4 shadow-sm border-0 rounded-3 overflow-hidden">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0 fw-bold">Clinic Appointments</h5>
            <button class="btn btn-sm btn-light fw-semibold rounded-2" data-bs-toggle="modal" data-bs-target="#addAppointmentModal">+ Schedule Appointment</button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Patient ID</th>
                            <th>Patient Name</th>
                            <th>Type</th>
                            <th>Reason</th>
                            <th>Physician/Staff</th>
                            <th>Date & Time</th>
                            <th>Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($appointments as $app)
                        <tr>
                            <td class="fw-bold">#{{ $app->APPOINTMENT_ID }}</td>
                            <td>{{ $app->PATIENT_ID }}</td>
                            <td>{{ $app->PATIENT_NAME }}</td>
                            <td><span class="badge bg-info text-dark">{{ $app->APPOINTMENT_TYPE }}</span></td>
                            <td>{{ $app->APPOINTMENT_REASON }}</td>
                            <td>{{ $app->ATTENDING_PHYSICIAN }}</td>
                            <td>{{ \Carbon\Carbon::parse($app->SCHEDULED_AT)->format('Y-m-d h:i A') }}</td>
                            <td>
                                <span class="badge {{ $app->STATUS == 'Completed' ? 'bg-success' : ($app->STATUS == 'Cancelled' ? 'bg-danger' : 'bg-warning text-dark') }}">
                                    {{ $app->STATUS }}
                                </span>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-primary fw-semibold edit-btn rounded-2"
                                    data-bs-toggle="modal" data-bs-target="#editAppointmentModal"
                                    data-id="{{ $app->APPOINTMENT_ID }}"
                                    data-patient-id="{{ $app->PATIENT_ID }}"
                                    data-patient="{{ $app->PATIENT_NAME }}"
                                    data-type="{{ $app->APPOINTMENT_TYPE }}"
                                    data-reason="{{ $app->APPOINTMENT_REASON }}"
                                    data-doctor="{{ $app->ATTENDING_PHYSICIAN }}"
                                    data-schedule="{{ date('Y-m-d\TH:i', strtotime($app->SCHEDULED_AT)) }}"
                                    data-status="{{ $app->STATUS }}">
                                    Edit
                                </button>
                                <form action="/appointments/{{ $app->APPOINTMENT_ID }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to remove this appointment?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger fw-semibold rounded-2">Remove</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">No appointments found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Appointment Modal -->
<div class="modal fade" id="addAppointmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="/appointments" method="POST" class="modal-content">
            @csrf
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Schedule New Appointment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Patient ID</label>
                    <input type="text" name="PATIENT_ID" class="form-control" placeholder="e.g. 2024140159" pattern="[0-9]{10}" title="Please enter a valid 10-digit Patient ID" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Patient Name</label>
                    <input type="text" name="PATIENT_NAME" class="form-control" placeholder="John Doe" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Appointment Type</label>
                    <select name="APPOINTMENT_TYPE" class="form-select" required>
                        <option value="Consultation">Consultation</option>
                        <option value="Routine Checkup">Routine Checkup</option>
                        <option value="Emergency Care">Emergency Care</option>
                        <option value="Medical Clearance">Medical Clearance</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Attending Physician</label>
                    <input type="text" name="ATTENDING_PHYSICIAN" class="form-control" required>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Scheduled Date & Time</label>
                    <input type="datetime-local" name="SCHEDULED_AT" class="form-control" required>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Reason for Visit</label>
                    <textarea name="APPOINTMENT_REASON" class="form-control" rows="3" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Schedule</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Appointment Modal -->
<div class="modal fade" id="editAppointmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="editAppointmentForm" class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Modify Appointment Schedule</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <input type="hidden" id="edit_APPOINTMENT_ID">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Patient ID</label>
                    <input type="text" id="edit_PATIENT_ID" name="PATIENT_ID" class="form-control" placeholder="e.g. 2024140159" pattern="[0-9]{10}" title="Please enter a valid 10-digit Patient ID" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Patient Name</label>
                    <input type="text" id="edit_PATIENT_NAME" name="PATIENT_NAME" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Appointment Type</label>
                    <input type="text" id="edit_APPOINTMENT_TYPE" name="APPOINTMENT_TYPE" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Attending Physician</label>
                    <input type="text" id="edit_ATTENDING_PHYSICIAN" name="ATTENDING_PHYSICIAN" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Scheduled Date & Time</label>
                    <input type="datetime-local" id="edit_SCHEDULED_AT" name="SCHEDULED_AT" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Status</label>
                    <select id="edit_STATUS" name="STATUS" class="form-select" required>
                        <option value="Scheduled">Scheduled</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Reason for Visit</label>
                    <textarea id="edit_APPOINTMENT_REASON" name="APPOINTMENT_REASON" class="form-control" rows="3" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Schedule</button>
            </div>
        </form>
    </div>
</div>

<div class="card mb-5 shadow-sm border-0 rounded-3 overflow-hidden">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0 fw-bold">Doctor Duty Schedule & Availability</h5>
        <button class="btn btn-sm btn-light fw-semibold rounded-2" data-bs-toggle="modal" data-bs-target="#addScheduleModal">+ Publish Doctor Availability</button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Doctor Name</th>
                        <th>Duty Date</th>
                        <th>Target Schedule Window</th>
                        <th>Advance Notice Status</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schedules as $sched)
                    @php
                        $daysDiff = \Carbon\Carbon::now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($sched->AVAILABLE_DATE)->startOfDay(), false);
                    @endphp
                    <tr>
                        <td class="fw-semibold">{{ $sched->DOCTOR_NAME }}</td>
                        <td>{{ \Carbon\Carbon::parse($sched->AVAILABLE_DATE)->format('M d, Y (l)') }}</td>
                        <td>{{ \Carbon\Carbon::parse($sched->START_TIME)->format('h:i A') }} - {{ \Carbon\Carbon::parse($sched->END_TIME)->format('h:i A') }}</td>
                        <td>
                            @if($daysDiff >= 2)
                                <span class="badge bg-success">Listed 2+ Days Ahead</span>
                            @elseif($daysDiff == 1)
                                <span class="badge bg-warning text-dark">1 Day Ahead</span>
                            @else
                                <span class="badge bg-secondary">Today</span>
                            @endif
                        </td>
                        <td class="text-muted">{{ $sched->NOTES ?? 'N/A' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No doctor schedules published for upcoming days.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Post Doctor Schedule -->
<div class="modal fade" id="addScheduleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="/doctor-schedules" method="POST" class="modal-content rounded-3 overflow-hidden">
            @csrf
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold">Publish Doctor Duty Schedule</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                
                <!-- Attending Physician mapped to AppUser / MedicalStaff -->
                <div class="col-12">
                    <label class="form-label fw-semibold">Attending Physician</label>
                    <select name="AttendingStaff_AppUser_Id" class="form-select rounded-2" required>
                        <option value="" selected disabled>Select doctor...</option>
                        @foreach($doctors as $doctor)
                            <option value="{{ $doctor->id }}">Dr. {{ $doctor->LastName }}, {{ $doctor->FirstName }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Appointment Type mapped to AppointmentType table ID -->
                <div class="col-12">
                    <label class="form-label fw-semibold">Appointment Type (Category)</label>
                    <select name="AppointmentType_Id" class="form-select rounded-2" required>
                        <option value="" selected disabled>Select type...</option>
                        @foreach($appointmentTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->Label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Duty Date</label>
                    <input type="date" name="AVAILABLE_DATE" class="form-control rounded-2" min="{{ date('Y-m-d') }}" value="{{ \Carbon\Carbon::today()->addDays(2)->format('Y-m-d') }}" required>
                </div>

                <!-- Time Slot selection matching 1-hour increments -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Start Time Slot</label>
                    <select name="START_TIME" class="form-select rounded-2" required>
                        <option value="08:00:00">8:00 AM</option>
                        <option value="09:00:00">9:00 AM</option>
                        <option value="10:00:00">10:00 AM</option>
                        <option value="11:00:00">11:00 AM</option>
                        <option value="13:00:00">1:00 PM</option>
                        <option value="14:00:00">2:00 PM</option>
                        <option value="15:00:00">3:00 PM</option>
                        <option value="16:00:00">4:00 PM</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">End Time Slot</label>
                    <select name="END_TIME" class="form-select rounded-2" required>
                        <option value="09:00:00">9:00 AM</option>
                        <option value="10:00:00">10:00 AM</option>
                        <option value="11:00:00">11:00 AM</option>
                        <option value="12:00:00">12:00 PM</option>
                        <option value="14:00:00">2:00 PM</option>
                        <option value="15:00:00">3:00 PM</option>
                        <option value="16:00:00">4:00 PM</option>
                        <option value="17:00:00">5:00 PM</option>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Details / Notes</label>
                    <input type="text" name="Details" class="form-control rounded-2" placeholder="General Consultation / Special notes">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary rounded-2" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-dark fw-bold rounded-2">Publish Schedule</button>
            </div>
        </form>
    </div>
</div>
@endsection


@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const editModal = document.getElementById('editAppointmentModal');

    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button) return;

            document.getElementById('edit_APPOINTMENT_ID').value = button.getAttribute('data-id') || '';
            document.getElementById('edit_PATIENT_ID').value = button.getAttribute('data-patient-id') || '';
            document.getElementById('edit_PATIENT_NAME').value = button.getAttribute('data-patient') || '';
            document.getElementById('edit_APPOINTMENT_TYPE').value = button.getAttribute('data-type') || '';
            document.getElementById('edit_ATTENDING_PHYSICIAN').value = button.getAttribute('data-doctor') || '';
            document.getElementById('edit_SCHEDULED_AT').value = button.getAttribute('data-schedule') || '';
            document.getElementById('edit_STATUS').value = button.getAttribute('data-status') || 'Scheduled';
            document.getElementById('edit_APPOINTMENT_REASON').value = button.getAttribute('data-reason') || '';
        });
    }

    const editForm = document.getElementById('editAppointmentForm');
    if (editForm) {
        editForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const id = document.getElementById('edit_APPOINTMENT_ID').value;
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            fetch(`${window.location.origin}/appointments/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify(data)
            })
            .then(async response => {
                const result = await response.json();
                if (response.status === 200) {
                    alert(result.message || 'Appointment updated successfully!');
                    location.reload();
                } else {
                    alert('Error updating appointment.');
                }
            })
            .catch(error => console.error('Error:', error));
        });
    }
});
</script>
@endpush