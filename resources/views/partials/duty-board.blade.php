@php
    $returnTo = request()->getRequestUri();
    $grid = $dutyGrid ?? ['days' => [], 'rows' => []];
@endphp

<div class="card mb-4 shadow-sm border-0 rounded-3 overflow-hidden" id="duty-board">
    <div class="card-header bg-dark text-white py-3">
        <h5 class="mb-1 fw-bold">Doctor Duty Schedule &amp; Availability</h5>
        <p class="mb-0 small" style="color:#E1B11A;">
            {{ $weekLabel ?? '' }}
            @if($dutyPractitionerLabel)
                · {{ $dutyPractitionerLabel }}
            @endif
        </p>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ url()->current() }}" id="duty-board-form" class="row g-3 align-items-end">
            @foreach(request()->except(['practitioner', 'duty_date', 'week']) as $key => $value)
                @if(is_string($value))
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <input type="hidden" name="week" value="{{ $weekStart }}">
            <div class="col-lg-8">
                <label for="duty-practitioner" class="form-label fw-semibold">Select Doctor / Attending Physician</label>
                <select name="practitioner" id="duty-practitioner" class="form-select" @if(empty($dutyPractitioners)) disabled @endif>
                    @forelse($dutyPractitioners as $person)
                        <option value="{{ $person['name'] }}" @selected($dutyPractitioner === $person['name'])>{{ $person['label'] }}</option>
                    @empty
                        <option value="">No attending physicians are registered</option>
                    @endforelse
                </select>
            </div>
            <div class="col-lg-4 d-flex gap-2">
                <a class="btn btn-outline-primary fw-semibold flex-fill" href="{{ url()->current() }}?practitioner={{ urlencode((string) $dutyPractitioner) }}&week={{ $previousWeek }}">Previous week</a>
                <a class="btn btn-outline-primary fw-semibold flex-fill" href="{{ url()->current() }}?practitioner={{ urlencode((string) $dutyPractitioner) }}&week={{ $nextWeek }}">Next week</a>
            </div>
        </form>
        <p class="small text-muted mt-3 mb-0">Click a cell to book an open hour, review a booked visit, or see why a slot is closed.</p>
    </div>

    <div class="table-responsive border-top">
        <table class="table table-bordered align-middle mb-0 duty-grid">
            <thead class="table-dark text-uppercase" style="font-size:.78rem;">
                <tr>
                    <th class="ps-3">TIMESLOT</th>
                    @foreach($grid['days'] as $day)
                        <th class="text-center">{{ $day['name'] }}<br><span class="fw-normal text-lowercase">{{ \Carbon\Carbon::parse($day['date'])->format('M j') }}</span></th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($grid['rows'] as $row)
                    <tr>
                        <td class="ps-3 fw-semibold text-nowrap">{{ $row['label'] }}</td>
                        @foreach($row['cells'] as $cell)
                            @php
                                $slotLabel = $row['label'].' · '.\Carbon\Carbon::parse($cell['date'])->format('l, M j');
                            @endphp
                            <td class="text-center p-1 {{ $cell['open'] ? 'duty-open' : 'duty-closed' }}">
                                <button type="button"
                                    class="duty-cell-btn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#dutyCellModal"
                                    data-kind="{{ $cell['kind'] }}"
                                    data-text="{{ $cell['text'] }}"
                                    data-date="{{ $cell['date'] }}"
                                    data-start="{{ $cell['start'] }}"
                                    data-label="{{ $slotLabel }}"
                                    data-patient="{{ $cell['patient'] }}"
                                    data-patient-id="{{ $cell['patient_id'] }}"
                                    data-reason="{{ $cell['reason'] }}"
                                    data-appointment="{{ $cell['appointment_id'] }}"
                                    aria-label="{{ $slotLabel }}: {{ $cell['text'] }}">
                                    <span class="d-block">{{ $cell['text'] }}</span>
                                    <span class="duty-cell-hint">{{ $cell['hint'] }}</span>
                                </button>
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">Select a doctor to see the weekly schedule.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($dutyPractitioner)
    <div class="modal fade" id="dutyCellModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header text-white" style="background:#003B7A;">
                    <div>
                        <h5 class="modal-title fw-bold" id="duty_cell_title">Duty slot</h5>
                        <p class="mb-0 small" id="duty_cell_status"></p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3" id="duty_cell_closed" hidden>This physician is not on duty during this hour, so the cell stays closed.</p>
                    <div id="duty_cell_occupied" hidden>
                        <p class="mb-1"><span class="text-muted">Patient</span><br><strong id="duty_cell_patient"></strong></p>
                        <p class="mb-1"><span class="text-muted">Patient ID</span><br><strong id="duty_cell_patient_id"></strong></p>
                        <p class="mb-0"><span class="text-muted">Reason</span><br><strong id="duty_cell_reason"></strong></p>
                    </div>
                    <form method="POST" action="{{ route('duty-slots.book') }}" id="duty_cell_book" class="row g-3" hidden>
                        @csrf
                        <input type="hidden" name="practitioner" value="{{ $dutyPractitioner }}">
                        <input type="hidden" name="duty_date" id="book_duty_date" value="">
                        <input type="hidden" name="start" id="book_start" value="">
                        <input type="hidden" name="return_to" value="{{ $returnTo }}">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="book_patient_id">Patient ID</label>
                            <input type="text" name="patient_id" id="book_patient_id" class="form-control" value="{{ ($clinicAccount && ! $clinicAccount->isClinicStaff()) ? ($clinicAccount->Student_Employee_No ?: $clinicAccount->Id) : \App\Models\Appointment::nextPatientId() }}" @readonly($clinicAccount && ! $clinicAccount->isClinicStaff())>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="book_patient_name">Patient Name</label>
                            <input type="text" name="patient_name" id="book_patient_name" class="form-control" value="{{ ($clinicAccount && ! $clinicAccount->isClinicStaff()) ? $clinicAccount->fullName() : '' }}" @readonly($clinicAccount && ! $clinicAccount->isClinicStaff()) required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" for="book_reason">Reason</label>
                            <input type="text" name="reason" id="book_reason" class="form-control" value="Booked from duty schedule">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    @if($isClinicStaff)
                        <form method="POST" action="{{ route('duty-slots.cancel') }}" id="duty_cell_cancel" hidden>
                            @csrf
                            <input type="hidden" name="practitioner" value="{{ $dutyPractitioner }}">
                            <input type="hidden" name="duty_date" id="cancel_duty_date" value="">
                            <input type="hidden" name="start" id="cancel_start" value="">
                            <input type="hidden" name="appointment_id" id="cancel_appointment" value="">
                            <input type="hidden" name="return_to" value="{{ $returnTo }}">
                            <button type="submit" class="btn btn-outline-danger fw-semibold">Cancel this visit</button>
                        </form>
                    @endif
                    <button type="submit" class="btn btn-primary fw-semibold" id="duty_cell_book_button" form="duty_cell_book" hidden>Book Appointment</button>
                </div>
            </div>
        </div>
    </div>
@endif

@once
    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('duty-board-form');
        document.getElementById('duty-practitioner')?.addEventListener('change', function () {
            form?.submit();
        });

        document.getElementById('dutyCellModal')?.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button) return;

            document.querySelectorAll('.duty-cell-btn.is-selected').forEach(function (cell) {
                cell.classList.remove('is-selected');
            });
            button.classList.add('is-selected');

            const kind = button.getAttribute('data-kind') || 'closed';
            const date = button.getAttribute('data-date') || '';
            const start = button.getAttribute('data-start') || '';
            document.getElementById('duty_cell_title').textContent = button.getAttribute('data-label') || 'Duty slot';
            document.getElementById('duty_cell_status').textContent = button.getAttribute('data-text') || '';

            const closed = document.getElementById('duty_cell_closed');
            const occupied = document.getElementById('duty_cell_occupied');
            const book = document.getElementById('duty_cell_book');
            const bookButton = document.getElementById('duty_cell_book_button');
            const cancel = document.getElementById('duty_cell_cancel');

            if (closed) closed.hidden = kind !== 'closed';
            if (occupied) occupied.hidden = kind !== 'occupied';
            if (book) book.hidden = kind !== 'available';
            if (bookButton) bookButton.hidden = kind !== 'available';
            if (cancel) cancel.hidden = kind !== 'occupied';

            if (kind === 'occupied') {
                document.getElementById('duty_cell_patient').textContent = button.getAttribute('data-patient') || 'Patient';
                document.getElementById('duty_cell_patient_id').textContent = button.getAttribute('data-patient-id') || '—';
                document.getElementById('duty_cell_reason').textContent = button.getAttribute('data-reason') || '—';
                const cancelDate = document.getElementById('cancel_duty_date');
                const cancelStart = document.getElementById('cancel_start');
                const cancelId = document.getElementById('cancel_appointment');
                if (cancelDate) cancelDate.value = date;
                if (cancelStart) cancelStart.value = start;
                if (cancelId) cancelId.value = button.getAttribute('data-appointment') || '';
            }

            if (kind === 'available') {
                const bookDate = document.getElementById('book_duty_date');
                const bookStart = document.getElementById('book_start');
                if (bookDate) bookDate.value = date;
                if (bookStart) bookStart.value = start;
            }
        });
    });
    </script>
    @endpush
@endonce
