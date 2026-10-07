@php
    $returnTo = request()->getRequestUri();
    $dutyDay = \Carbon\Carbon::parse($dutyDate ?? today())->format('l, F j, Y');
@endphp

<div class="card mb-4 shadow-sm border-0 rounded-3 overflow-hidden" id="duty-board"
    data-practitioner="{{ $dutyPractitioner }}"
    data-date="{{ $dutyDate }}"
    data-return="{{ $returnTo }}"
    data-dev="{{ ($developerMode ?? false) ? '1' : '0' }}">
    <div class="card-header bg-dark text-white py-3">
        <h5 class="mb-1 fw-bold">Doctor &amp; Nurse Duty Schedule &amp; Availability</h5>
        <p class="mb-0 small" style="color:#E1B11A;">
            30-minute slots from 7:00 AM to 7:00 PM
            @if($dutyPractitionerLabel)
                · {{ $dutyPractitionerLabel }} · {{ $dutyDay }}
            @endif
        </p>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ url()->current() }}" id="duty-board-form" class="row g-3 align-items-end">
            @foreach(request()->except(['practitioner', 'duty_date']) as $key => $value)
                @if(is_string($value))
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <div class="col-lg-6">
                <label for="duty-practitioner" class="form-label fw-semibold">Select Medical Staff / Practitioner</label>
                <select name="practitioner" id="duty-practitioner" class="form-select" @if(empty($dutyPractitioners)) disabled @endif>
                    @forelse($dutyPractitioners as $person)
                        <option value="{{ $person['name'] }}" @selected($dutyPractitioner === $person['name'])>{{ $person['label'] }}</option>
                    @empty
                        <option value="">No doctors or nurses are registered</option>
                    @endforelse
                </select>
            </div>
            <div class="col-lg-3">
                <label for="duty-date" class="form-label fw-semibold">Duty Date</label>
                <input type="date" name="duty_date" id="duty-date" class="form-control" value="{{ $dutyDate }}" required>
            </div>
            <div class="col-lg-3">
                <button type="submit" class="btn btn-primary fw-semibold w-100">Show Schedule</button>
            </div>
        </form>
        @if($developerMode ?? false)
            <p class="small text-muted mt-3 mb-0">Developer mode: click a time-slot row to cycle Available, Off Duty, and On Break. Edit Slot can assign a test appointment.</p>
        @endif
    </div>

    <div class="table-responsive border-top">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark text-uppercase" style="font-size:.78rem;">
                <tr>
                    <th class="ps-4">Time Slot</th>
                    <th>Patient Assigned</th>
                    <th>Availability Status</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($dutySlots as $slot)
                    @php
                        $open = $slot['status'] === 'available';
                    @endphp
                    <tr class="{{ ($developerMode ?? false) ? 'duty-row-live js-duty-row' : '' }}" data-start="{{ $slot['start'] }}">
                        <td class="ps-4 fw-semibold">{{ $slot['label'] }}</td>
                        <td>{{ $slot['patient'] }}</td>
                        <td>
                            <span class="badge rounded-pill px-2 py-1 {{ $open ? 'duty-badge-available' : 'duty-badge-closed' }}">{{ $slot['badge'] }}</span>
                        </td>
                        <td class="text-end pe-4">
                            <div class="d-flex flex-wrap justify-content-end gap-1">
                                @if($open)
                                    @if($isClinicStaff)
                                        <button type="button" class="btn btn-sm text-white fw-semibold" style="background:#003B7A;"
                                            data-bs-toggle="modal" data-bs-target="#bookSlotModal"
                                            data-start="{{ $slot['start'] }}" data-label="{{ $slot['label'] }}">Book Appointment</button>
                                    @else
                                        <form method="POST" action="{{ route('duty-slots.book') }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="practitioner" value="{{ $dutyPractitioner }}">
                                            <input type="hidden" name="duty_date" value="{{ $dutyDate }}">
                                            <input type="hidden" name="start" value="{{ $slot['start'] }}">
                                            <input type="hidden" name="return_to" value="{{ $returnTo }}">
                                            <button type="submit" class="btn btn-sm text-white fw-semibold" style="background:#003B7A;">Book Appointment</button>
                                        </form>
                                    @endif
                                @endif
                                @if($isClinicStaff && (($developerMode ?? false) || $slot['appointment_id']))
                                    <button type="button" class="btn btn-sm btn-outline-primary fw-semibold"
                                        data-bs-toggle="modal" data-bs-target="#editSlotModal"
                                        data-start="{{ $slot['start'] }}"
                                        data-label="{{ $slot['label'] }}"
                                        data-patient="{{ $slot['patient'] === 'None' ? '' : $slot['patient'] }}"
                                        data-patient-id="{{ $slot['patient_id'] }}"
                                        data-reason="{{ $slot['reason'] }}">Edit Slot</button>
                                @endif
                                @if($developerMode ?? false)
                                    <button type="button" class="btn btn-sm btn-outline-secondary fw-semibold js-duty-toggle" data-start="{{ $slot['start'] }}">Toggle Status</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">Select a practitioner to see that day's time slots.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($dutyPractitioner)
    @if($isClinicStaff)
        <div class="modal fade" id="bookSlotModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('duty-slots.book') }}" class="modal-content">
                    @csrf
                    <div class="modal-header text-white" style="background:#003B7A;">
                        <h5 class="modal-title fw-bold">Book Appointment</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body row g-3">
                        <input type="hidden" name="practitioner" value="{{ $dutyPractitioner }}">
                        <input type="hidden" name="duty_date" value="{{ $dutyDate }}">
                        <input type="hidden" name="start" id="book_start" value="">
                        <input type="hidden" name="return_to" value="{{ $returnTo }}">
                        <div class="col-12">
                            <p class="mb-0 fw-semibold" id="book_slot_label" style="color:#003B7A;"></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="book_patient_id">Patient ID</label>
                            <input type="text" name="patient_id" id="book_patient_id" class="form-control" value="{{ \App\Models\Appointment::nextPatientId() }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="book_patient_name">Patient Name</label>
                            <input type="text" name="patient_name" id="book_patient_name" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" for="book_reason">Reason</label>
                            <input type="text" name="reason" id="book_reason" class="form-control" value="Booked from duty schedule">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary fw-semibold">Book Appointment</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal fade" id="editSlotModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('duty-slots.assign') }}" class="modal-content" id="editSlotForm">
                    @csrf
                    <div class="modal-header text-white" style="background:#003B7A;">
                        <h5 class="modal-title fw-bold">Edit Slot</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body row g-3">
                        <input type="hidden" name="practitioner" value="{{ $dutyPractitioner }}">
                        <input type="hidden" name="duty_date" value="{{ $dutyDate }}">
                        <input type="hidden" name="start" id="edit_slot_start" value="">
                        <input type="hidden" name="return_to" value="{{ $returnTo }}">
                        <div class="col-12">
                            <p class="mb-0 fw-semibold" id="edit_slot_label" style="color:#003B7A;"></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="edit_slot_patient_id">Patient ID</label>
                            <input type="text" name="patient_id" id="edit_slot_patient_id" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="edit_slot_patient_name">Patient Name</label>
                            <input type="text" name="patient_name" id="edit_slot_patient_name" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" for="edit_slot_reason">Reason</label>
                            <input type="text" name="reason" id="edit_slot_reason" class="form-control">
                        </div>
                        @if($developerMode ?? false)
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="clear" value="1" id="edit_slot_clear">
                                    <label class="form-check-label" for="edit_slot_clear">Clear the appointment in this slot</label>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary fw-semibold">Save Slot</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endif

@once
    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const board = document.getElementById('duty-board');
        const form = document.getElementById('duty-board-form');
        if (form) {
            document.getElementById('duty-practitioner')?.addEventListener('change', function () {
                form.submit();
            });
        }

        document.getElementById('bookSlotModal')?.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button) return;
            document.getElementById('book_start').value = button.getAttribute('data-start') || '';
            document.getElementById('book_slot_label').textContent = button.getAttribute('data-label') || '';
        });

        document.getElementById('editSlotModal')?.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button) return;
            document.getElementById('edit_slot_start').value = button.getAttribute('data-start') || '';
            document.getElementById('edit_slot_label').textContent = button.getAttribute('data-label') || '';
            document.getElementById('edit_slot_patient_name').value = button.getAttribute('data-patient') || '';
            document.getElementById('edit_slot_patient_id').value = button.getAttribute('data-patient-id') || '';
            document.getElementById('edit_slot_reason').value = button.getAttribute('data-reason') || '';
            const clear = document.getElementById('edit_slot_clear');
            if (clear) clear.checked = false;
            document.getElementById('edit_slot_patient_name').required = true;
        });

        document.getElementById('edit_slot_clear')?.addEventListener('change', function () {
            const name = document.getElementById('edit_slot_patient_name');
            if (name) name.required = !this.checked;
        });

        if (!board || board.dataset.dev !== '1') return;

        const token = document.querySelector('meta[name="csrf-token"]')?.content || '';

        function toggleSlot(start) {
            fetch(@json(route('duty-slots.toggle')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({
                    practitioner: board.dataset.practitioner,
                    duty_date: board.dataset.date,
                    start: start,
                    return_to: board.dataset.return
                })
            }).then(async function (response) {
                if (response.ok) {
                    window.location.reload();
                    return;
                }
                const result = await response.json().catch(function () { return {}; });
                console.error(result.message || 'Unable to toggle this slot.');
            });
        }

        board.querySelectorAll('.js-duty-toggle').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.stopPropagation();
                toggleSlot(button.getAttribute('data-start'));
            });
        });

        board.querySelectorAll('.js-duty-row').forEach(function (row) {
            row.addEventListener('click', function (event) {
                if (event.target.closest('button, a, input, select, label, form')) return;
                toggleSlot(row.getAttribute('data-start'));
            });
        });
    });
    </script>
    @endpush
@endonce
