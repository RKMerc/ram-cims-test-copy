<div class="mb-4">
    <p class="text-uppercase fw-bold mb-1" style="color:#003B7A; letter-spacing:.08em; font-size:.78rem;">Asia Pacific College</p>
    <h1 class="h3 fw-bold mb-1" style="color:#003B7A;">Welcome, {{ $clinicAccount->FirstName ?? 'there' }}</h1>
    <p class="text-muted mb-0">Your check-ups, clinic visits, and the notes from your latest visit.</p>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card h-100 border-start border-4" style="border-color:#003B7A !important;">
            <div class="card-body">
                <h6 class="text-muted fw-semibold text-uppercase mb-2" style="font-size:.78rem; letter-spacing:.04em;">My Upcoming Appointments</h6>
                <h2 class="fw-bold mb-0" style="color:#003B7A;">{{ $upcomingCount ?? 0 }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100 border-start border-4" style="border-color:#E1B11A !important;">
            <div class="card-body">
                <h6 class="text-muted fw-semibold text-uppercase mb-2" style="font-size:.78rem; letter-spacing:.04em;">Total Clinic Visits</h6>
                <h2 class="fw-bold mb-0" style="color:#003B7A;">{{ $visitCount ?? 0 }}</h2>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0" style="color:#003B7A;">My Scheduled Appointments</h5>
        <a href="{{ url('/appointments') }}" class="btn btn-sm btn-outline-primary fw-semibold">+ Schedule Check-Up</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-uppercase" style="font-size:.78rem;">
                <tr>
                    <th class="ps-4">Date</th>
                    <th>Time</th>
                    <th>Attending Staff</th>
                    <th>Category</th>
                    <th class="text-end pe-4">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($upcomingAppointments ?? [] as $apt)
                    @php
                        $when = \Carbon\Carbon::parse($apt->SCHEDULED_AT);
                        $status = strtolower((string) $apt->STATUS);
                        $badge = $status === 'completed' ? 'bg-success' : ($status === 'cancelled' ? 'bg-danger' : 'bg-warning text-dark');
                    @endphp
                    <tr>
                        <td class="ps-4 fw-semibold">{{ $when->format('M d, Y') }}</td>
                        <td>{{ $when->format('h:i A') }}</td>
                        <td>{{ $apt->ATTENDING_PHYSICIAN }}</td>
                        <td><span class="badge bg-secondary bg-opacity-10 text-secondary">{{ $apt->APPOINTMENT_TYPE }}</span></td>
                        <td class="text-end pe-4">
                            <span class="badge {{ $badge }} fw-semibold px-2 py-1">{{ $apt->STATUS }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No scheduled appointments yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 border-0">
        <h5 class="fw-bold mb-0" style="color:#003B7A;">Latest Visit Summary</h5>
    </div>
    <div class="card-body">
        @if(!empty($latestVisit))
            <p class="text-muted mb-3">{{ \Carbon\Carbon::parse($latestVisit->MEDREC_CONSUL_DATE)->format('F j, Y') }}</p>
            <div class="row g-3">
                <div class="col-md-4">
                    <h6 class="text-uppercase fw-semibold mb-1" style="color:#003B7A; font-size:.78rem;">Diagnosed symptoms</h6>
                    <p class="mb-0">{{ $latestVisit->MEDREC_DIAGNOSIS }}</p>
                </div>
                <div class="col-md-4">
                    <h6 class="text-uppercase fw-semibold mb-1" style="color:#003B7A; font-size:.78rem;">Doctor notes</h6>
                    <p class="mb-0">{{ $latestVisit->MEDREC_NOTES ?: 'No notes recorded.' }}</p>
                </div>
                <div class="col-md-4">
                    <h6 class="text-uppercase fw-semibold mb-1" style="color:#003B7A; font-size:.78rem;">Prescribed medicine</h6>
                    <p class="mb-0">{{ $latestVisit->MEDREC_MEDICINE_DOSAGE ?: 'None recorded.' }}</p>
                </div>
            </div>
        @else
            <p class="text-muted mb-0">No clinic visit has been recorded yet.</p>
        @endif
    </div>
</div>
