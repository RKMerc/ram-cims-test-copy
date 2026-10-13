@extends('layouts.app')

@section('title', 'RAM-CIMS - Appointments')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-primary mb-1">{{ $isClinicStaff ? 'Clinic Appointments' : 'My Appointments' }}</h2>
            <p class="text-muted mb-0">{{ $isClinicStaff ? 'Queue management for clinic visits.' : 'Schedule a check-up and review your bookings.' }}</p>
        </div>
        @unless($isClinicStaff)
            <button class="btn btn-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#addAppointmentModal">+ Schedule Check-Up</button>
        @endunless
    </div>

    @if($errors->any())
        <div class="alert alert-warning">{{ $errors->first() }}</div>
    @endif

    @if($isClinicStaff)
    <!-- Filter/Search Bar -->
    <div class="card mb-4 shadow-sm border-0 rounded-3">
        <div class="card-body">
            <form method="GET" action="/appointments" class="row g-3">
                @if($showingHistory)
                    <input type="hidden" name="history" value="1">
                @endif
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
    @endif

    <!-- Appointments Table -->
    <div class="card mb-4 shadow-sm border-0 rounded-3 overflow-hidden">
        <div class="card-header bg-dark text-white d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
            <div>
                <h5 class="mb-0 fw-bold">{{ $showingHistory ? 'Previous weeks' : ($isClinicStaff ? 'Clinic Appointments' : 'My Scheduled Appointments') }}</h5>
                <div class="small" style="color:#E1B11A;">{{ $showingHistory ? 'Earlier visits stay on file, in appointment time order.' : 'This week and later, in appointment time order.' }}</div>
            </div>
            <a class="btn btn-sm btn-outline-light fw-semibold" href="{{ route('appointments.index', $showingHistory ? request()->except('page', 'history') : array_merge(request()->except('page'), ['history' => 1])) }}">{{ $showingHistory ? 'Current and upcoming' : 'Previous weeks' }}</a>
            @if($isClinicStaff)
                <button class="btn btn-sm btn-light fw-semibold rounded-2" data-bs-toggle="modal" data-bs-target="#addAppointmentModal">+ Schedule Appointment</button>
            @endif
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
                                @if($isClinicStaff)
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
                                @elseif($app->studentCanCancel())
                                    <button type="button" class="btn btn-sm btn-outline-danger fw-semibold rounded-2"
                                        data-bs-toggle="modal" data-bs-target="#cancelAppointmentModal"
                                        data-action="{{ route('appointments.cancel', $app) }}"
                                        data-detail="#{{ $app->APPOINTMENT_ID }} · {{ \Carbon\Carbon::parse($app->SCHEDULED_AT)->format('M d, Y h:i A') }}">
                                        Cancel Appointment
                                    </button>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">{{ $showingHistory ? 'No appointments from previous weeks.' : 'No appointments for this week or later.' }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($appointments->hasPages())
                <div class="card-body border-top">{{ $appointments->links() }}</div>
            @endif
        </div>
    </div>

@unless($isClinicStaff)
<div class="modal fade" id="cancelAppointmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="cancelAppointmentForm" action="" class="modal-content">
            @csrf
            <div class="modal-header text-white" style="background:#003B7A;">
                <h5 class="modal-title fw-bold">Cancel Appointment?</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="fw-semibold mb-2" id="cancelAppointmentDetail"></p>
                <p class="mb-0">Are you sure you want to cancel this appointment? Your reserved time slot may become available for another patient.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Appointment</button>
                <button type="submit" class="btn btn-danger fw-semibold">Confirm Cancellation</button>
            </div>
        </form>
    </div>
</div>
@endunless

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
                    <label class="form-label fw-semibold">Appointment ID</label>
                    <input type="text" class="form-control" value="Auto-assigned (#{{ $nextAppointmentId }})" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Patient ID</label>
                    @if($isClinicStaff)
                        @php
                            $ownPatientId = $clinicAccount?->Student_Employee_No ?: $clinicAccount?->Id;
                            $patientIdValue = ($clinicAccount && ! $clinicAccount->isClinicStaff()) ? $ownPatientId : $nextPatientId;
                        @endphp
                        <input type="text" name="PATIENT_ID" class="form-control" value="{{ $patientIdValue }}" required>
                        <div class="form-text">Filled from the signed-in account. Replace it when booking for someone else.</div>
                    @else
                        <input type="text" name="PATIENT_ID" class="form-control" value="{{ $clinicAccount?->Student_Employee_No ?: $clinicAccount?->Id }}" readonly>
                    @endif
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Patient Name</label>
                    @if($isClinicStaff)
                        <input type="text" name="PATIENT_NAME" class="form-control" placeholder="John Doe" required>
                    @else
                        <input type="text" name="PATIENT_NAME" class="form-control" value="{{ $clinicAccount?->fullName() }}" readonly>
                    @endif
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
                    <label class="form-label fw-semibold" for="createPhysician">Attending Physician</label>
                    <select name="ATTENDING_PHYSICIAN" id="createPhysician" class="form-select duty-physician" required>
                        @include('partials.physician-options')
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="createDate">Duty Date</label>
                    <select id="createDate" class="form-select duty-date" required>
                        <option value="" selected disabled>Choose a physician first</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="createSlot">Duty Time Slot</label>
                    <select name="SCHEDULED_AT" id="createSlot" class="form-select duty-slot" required>
                        <option value="" selected disabled>Choose a physician and date first</option>
                    </select>
                    <div class="form-text" id="createSlotHint">Times follow the physician on duty that day.</div>
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

@if($isClinicStaff)
<!-- Edit Appointment Modal -->
<div class="modal fade" id="editAppointmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="editAppointmentForm" class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Modify Appointment Schedule</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Appointment ID</label>
                    <input type="text" id="edit_APPOINTMENT_ID" class="form-control" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Patient ID</label>
                    <input type="text" id="edit_PATIENT_ID" name="PATIENT_ID" class="form-control" required>
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
                    <label class="form-label fw-semibold" for="edit_ATTENDING_PHYSICIAN">Attending Physician</label>
                    <select id="edit_ATTENDING_PHYSICIAN" name="ATTENDING_PHYSICIAN" class="form-select duty-physician" required>
                        @include('partials.physician-options')
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="editDate">Duty Date</label>
                    <select id="editDate" class="form-select duty-date" required>
                        <option value="" selected disabled>Choose a physician first</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="editSlot">Duty Time Slot</label>
                    <select id="editSlot" name="SCHEDULED_AT" class="form-select duty-slot" required>
                        <option value="" selected disabled>Choose a physician and date first</option>
                    </select>
                    <div class="form-text" id="editSlotHint">Times follow the physician on duty that day.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Status</label>
                    <select id="edit_STATUS" name="STATUS" class="form-select" required>
                        <option value="Pending">Pending</option>
                        <option value="Scheduled">Scheduled</option>
                        <option value="In Consultation">In Consultation</option>
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
@endif

@include('partials.duty-board')
</div>
@endsection


@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const cancelModal = document.getElementById('cancelAppointmentModal');
    if (cancelModal) {
        cancelModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button) return;
            const form = document.getElementById('cancelAppointmentForm');
            const detail = document.getElementById('cancelAppointmentDetail');
            if (form) form.action = button.getAttribute('data-action') || '';
            if (detail) detail.textContent = button.getAttribute('data-detail') || '';
        });
    }

    const editModal = document.getElementById('editAppointmentModal');

    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button) return;

            const doctor = button.getAttribute('data-doctor') || '';
            const status = button.getAttribute('data-status') || 'Scheduled';
            const doctorSelect = document.getElementById('edit_ATTENDING_PHYSICIAN');
            const statusSelect = document.getElementById('edit_STATUS');

            if (doctor && ![...doctorSelect.options].some(option => option.value === doctor)) {
                doctorSelect.add(new Option(doctor, doctor));
            }
            if (status && ![...statusSelect.options].some(option => option.value === status)) {
                statusSelect.add(new Option(status, status));
            }

            document.getElementById('edit_APPOINTMENT_ID').value = button.getAttribute('data-id') || '';
            document.getElementById('edit_PATIENT_ID').value = button.getAttribute('data-patient-id') || '';
            document.getElementById('edit_PATIENT_NAME').value = button.getAttribute('data-patient') || '';
            document.getElementById('edit_APPOINTMENT_TYPE').value = button.getAttribute('data-type') || '';
            doctorSelect.value = doctor;
            statusSelect.value = status;
            document.getElementById('edit_APPOINTMENT_REASON').value = button.getAttribute('data-reason') || '';

            const scheduled = (button.getAttribute('data-schedule') || '').replace('T', ' ');
            loadDutyDates(
                doctorSelect,
                document.getElementById('editDate'),
                document.getElementById('editSlot'),
                document.getElementById('editSlotHint'),
                scheduled.slice(0, 10),
                scheduled,
                button.getAttribute('data-id')
            );
        });
    }

    async function loadDutySlots(physicianSelect, dateInput, slotSelect, hint, selected, appointmentId) {
        slotSelect.innerHTML = '<option value="" selected disabled>Choose a physician and date first</option>';
        if (!physicianSelect.value || !dateInput.value) {
            hint.textContent = 'Times follow the physician on duty that day.';
            return;
        }

        hint.textContent = 'Checking the duty schedule…';
        const params = new URLSearchParams({
            physician: physicianSelect.value,
            date: dateInput.value
        });
        if (appointmentId) params.set('appointment_id', appointmentId);

        try {
            const response = await fetch('/appointments/slots?' + params.toString(), {
                headers: { 'Accept': 'application/json' }
            });
            const data = await response.json();
            const slots = data.slots || [];
            slotSelect.innerHTML = '';

            if (!response.ok || !slots.length) {
                slotSelect.add(new Option('No duty slot on this date', ''));
                hint.textContent = 'This physician is not on duty that day. Choose another date.';
                return;
            }

            const placeholder = new Option('Select a duty time slot', '');
            placeholder.disabled = true;
            placeholder.selected = true;
            slotSelect.add(placeholder);
            const wanted = (selected || '').slice(0, 16);
            slots.forEach(function (slot) {
                const option = new Option(slot.label, slot.value);
                if (wanted && slot.value.slice(0, 16) === wanted) option.selected = true;
                slotSelect.add(option);
            });
            hint.textContent = 'Only open times from this physician’s duty schedule are listed.';
        } catch (error) {
            hint.textContent = 'The duty schedule could not be loaded. Try again.';
        }
    }

    async function loadDutyDates(physicianSelect, dateSelect, slotSelect, hint, selectedDate, selectedSlot, appointmentId) {
        dateSelect.innerHTML = '<option value="" selected disabled>Choose a physician first</option>';
        slotSelect.innerHTML = '<option value="" selected disabled>Choose a duty date first</option>';
        if (!physicianSelect.value) {
            hint.textContent = 'Dates come from this physician’s duty schedule.';
            return;
        }

        hint.textContent = 'Checking duty dates…';
        const params = new URLSearchParams({ physician: physicianSelect.value });
        if (appointmentId) params.set('appointment_id', appointmentId);

        try {
            const response = await fetch('/appointments/duty-dates?' + params.toString(), {
                headers: { 'Accept': 'application/json' }
            });
            const data = await response.json();
            const dates = data.dates || [];
            dateSelect.innerHTML = '';

            if (!response.ok || !dates.length) {
                dateSelect.add(new Option('No open duty dates', ''));
                hint.textContent = 'This physician has no open duty dates in the current or upcoming weeks.';
                return;
            }

            const placeholder = new Option('Select a duty date', '');
            placeholder.disabled = true;
            dateSelect.add(placeholder);
            let matched = false;
            dates.forEach(function (day) {
                const option = new Option(day.label + ' (' + day.open_slots + ' open)', day.value);
                if (selectedDate && day.value === selectedDate) {
                    option.selected = true;
                    matched = true;
                }
                dateSelect.add(option);
            });
            if (!matched) {
                placeholder.selected = true;
                hint.textContent = 'Only dates with an open duty slot are listed.';
                return;
            }

            loadDutySlots(physicianSelect, dateSelect, slotSelect, hint, selectedSlot, appointmentId);
        } catch (error) {
            hint.textContent = 'The duty dates could not be loaded. Try again.';
        }
    }

    function watchDutyForm(physicianId, dateId, slotId, hintId, appointmentIdInput) {
        const physician = document.getElementById(physicianId);
        const date = document.getElementById(dateId);
        const slot = document.getElementById(slotId);
        const hint = document.getElementById(hintId);
        if (!physician || !date || !slot || !hint) return;
        const appointmentId = function () {
            return appointmentIdInput ? document.getElementById(appointmentIdInput)?.value : '';
        };
        physician.addEventListener('change', function () {
            loadDutyDates(physician, date, slot, hint, '', '', appointmentId());
        });
        date.addEventListener('change', function () {
            loadDutySlots(physician, date, slot, hint, '', appointmentId());
        });
        if (physician.value) {
            loadDutyDates(physician, date, slot, hint, '', '', appointmentId());
        }
    }

    watchDutyForm('createPhysician', 'createDate', 'createSlot', 'createSlotHint');
    watchDutyForm('edit_ATTENDING_PHYSICIAN', 'editDate', 'editSlot', 'editSlotHint', 'edit_APPOINTMENT_ID');

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
                    const errorMsg = result.errors
                        ? Object.values(result.errors).flat().join('\n')
                        : (result.message || 'Error updating appointment.');
                    alert(errorMsg);
                }
            })
            .catch(error => console.error('Error:', error));
        });
    }
});
</script>
@endpush