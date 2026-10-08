@extends('layouts.app')

@section('title', 'RAM-CIMS - Analytics')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <p class="text-uppercase fw-bold mb-1" style="color:#003B7A; letter-spacing:.08em; font-size:.78rem;">Asia Pacific College</p>
        <h1 class="h3 fw-bold mb-1" style="color:#003B7A;">Clinic Analytics</h1>
        <p class="text-muted mb-0">A snapshot of today’s queue, visits on record, and stock that needs a refill.</p>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h2 class="h6 fw-bold mb-3" style="color:#003B7A;">Generate analytics report</h2>
        <form method="GET" action="{{ route('analytics.report') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="report_from" class="form-label fw-semibold">From</label>
                <input type="date" class="form-control" id="report_from" name="from" value="{{ $reportFrom }}" required>
            </div>
            <div class="col-md-3">
                <label for="report_to" class="form-label fw-semibold">To</label>
                <input type="date" class="form-control" id="report_to" name="to" value="{{ $reportTo }}" required>
            </div>
            <div class="col-md-3">
                <label for="report_physician" class="form-label fw-semibold">Physician</label>
                <select class="form-select" id="report_physician" name="physician">
                    <option value="">All physicians</option>
                    @foreach($physicians as $name)
                        <option value="{{ $name }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary fw-semibold" formtarget="_blank">View PDF</button>
                <button type="submit" class="btn btn-outline-primary fw-semibold" formaction="{{ route('analytics.report.download') }}">Download PDF</button>
            </div>
        </form>
        <p class="text-muted small mb-0 mt-3">The PDF includes the APC logo, report date and time, physician, appointments, visits, and low stock.</p>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card h-100 border-start border-4" style="border-color:#003B7A !important;">
            <div class="card-body">
                <h6 class="text-muted fw-semibold text-uppercase mb-2" style="font-size:.78rem;">Appointments Today</h6>
                <h2 class="fw-bold mb-0" style="color:#003B7A;">{{ $appointmentsToday }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100 border-start border-4" style="border-color:#E1B11A !important;">
            <div class="card-body">
                <h6 class="text-muted fw-semibold text-uppercase mb-2" style="font-size:.78rem;">Patients Queued</h6>
                <h2 class="fw-bold mb-0" style="color:#003B7A;">{{ $patientsQueued }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100 border-start border-4" style="border-color:#003B7A !important;">
            <div class="card-body">
                <h6 class="text-muted fw-semibold text-uppercase mb-2" style="font-size:.78rem;">Clinic Visits</h6>
                <h2 class="fw-bold mb-0" style="color:#003B7A;">{{ $visits }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100 border-start border-4" style="border-color:#E1B11A !important;">
            <div class="card-body">
                <h6 class="text-muted fw-semibold text-uppercase mb-2" style="font-size:.78rem;">Low Stock Items</h6>
                <h2 class="fw-bold mb-0" style="color:#003B7A;">{{ $lowStock }}</h2>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 border-0">
        <h5 class="fw-bold mb-0" style="color:#003B7A;">Appointments by Status</h5>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light text-uppercase" style="font-size:.78rem;">
                <tr>
                    <th class="ps-4">Status</th>
                    <th class="text-end pe-4">Count</th>
                </tr>
            </thead>
            <tbody>
                @forelse($byStatus as $row)
                    <tr>
                        <td class="ps-4">{{ $row->STATUS }}</td>
                        <td class="text-end pe-4 fw-semibold">{{ $row->total }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="text-center py-4 text-muted">No appointments have been recorded yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
