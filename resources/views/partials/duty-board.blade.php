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
                            <td class="text-center {{ $cell['open'] ? 'duty-open' : 'duty-closed' }}">
                                @if($cell['open'])
                                    @if($isClinicStaff)
                                        <button type="button" class="duty-cell-btn"
                                            data-bs-toggle="modal" data-bs-target="#bookSlotModal"
                                            data-date="{{ $cell['date'] }}"
                                            data-start="{{ $cell['start'] }}"
                                            data-label="{{ $row['label'] }} · {{ \Carbon\Carbon::parse($cell['date'])->format('l, M j') }}">AVAILABLE</button>
                                    @else
                                        <form method="POST" action="{{ route('duty-slots.book') }}">
                                            @csrf
                                            <input type="hidden" name="practitioner" value="{{ $dutyPractitioner }}">
                                            <input type="hidden" name="duty_date" value="{{ $cell['date'] }}">
                                            <input type="hidden" name="start" value="{{ $cell['start'] }}">
                                            <input type="hidden" name="return_to" value="{{ $returnTo }}">
                                            <button type="submit" class="duty-cell-btn">AVAILABLE</button>
                                        </form>
                                    @endif
                                @else
                                    <span class="duty-cell-label">{{ $cell['text'] }}</span>
                                @endif
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

@if($dutyPractitioner && $isClinicStaff)
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
                    <input type="hidden" name="duty_date" id="book_duty_date" value="{{ $weekStart }}">
                    <input type="hidden" name="start" id="book_start" value="">
                    <input type="hidden" name="return_to" value="{{ $returnTo }}">
                    <div class="col-12">
                        <p class="mb-0 fw-semibold" id="book_slot_label" style="color:#003B7A;"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="book_patient_id">Patient ID</label>
                        <input type="text" name="patient_id" id="book_patient_id" class="form-control" value="{{ ($clinicAccount && ! $clinicAccount->isClinicStaff()) ? ($clinicAccount->Student_Employee_No ?: $clinicAccount->Id) : \App\Models\Appointment::nextPatientId() }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="book_patient_name">Patient Name</label>
                        <input type="text" name="patient_name" id="book_patient_name" class="form-control" value="{{ ($clinicAccount && ! $clinicAccount->isClinicStaff()) ? $clinicAccount->fullName() : '' }}" required>
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
@endif

@once
    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('duty-board-form');
        document.getElementById('duty-practitioner')?.addEventListener('change', function () {
            form?.submit();
        });

        document.getElementById('bookSlotModal')?.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button) return;
            const date = document.getElementById('book_duty_date');
            const start = document.getElementById('book_start');
            const label = document.getElementById('book_slot_label');
            if (date) date.value = button.getAttribute('data-date') || '';
            if (start) start.value = button.getAttribute('data-start') || '';
            if (label) label.textContent = button.getAttribute('data-label') || '';
        });
    });
    </script>
    @endpush
@endonce
