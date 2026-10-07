@extends('layouts.app')

@section('title', 'RAM-CIMS - Analytics')

@section('content')
<div class="mb-4">
    <p class="text-uppercase fw-bold mb-1" style="color:#003B7A; letter-spacing:.08em; font-size:.78rem;">Asia Pacific College</p>
    <h1 class="h3 fw-bold mb-1" style="color:#003B7A;">Clinic Analytics</h1>
    <p class="text-muted mb-0">A snapshot of today’s queue, visits on record, and stock that needs a refill.</p>
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
