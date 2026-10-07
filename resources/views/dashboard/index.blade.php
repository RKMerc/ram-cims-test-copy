@extends('layouts.app')

@section('content')
<div class="container py-4">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Clinic Dashboard</h1>
            <p class="text-muted mb-0">Overview of clinical metrics and inventory status.</p>
        </div>
    </div>

    <!-- Metric Cards Row -->
    <div class="row g-4 mb-4">
        <!-- Appointments Today -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
                <div class="card-body">
                    <h6 class="text-muted fw-semibold text-uppercase fs-7 mb-2">Appointments Today</h6>
                    <div class="d-flex align-items-center justify-content-between">
                        <h2 class="fw-bold mb-0 text-dark">{{ $data['totalAppointmentsToday'] ?? 0 }}</h2>
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3 text-primary">
                            📅
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Appointments -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                <div class="card-body">
                    <h6 class="text-muted fw-semibold text-uppercase fs-7 mb-2">Total Appointments</h6>
                    <div class="d-flex align-items-center justify-content-between">
                        <h2 class="fw-bold mb-0 text-dark">{{ $data['totalAppointments'] ?? 0 }}</h2>
                        <div class="rounded-circle bg-success bg-opacity-10 p-3 text-success">
                            📊
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upcoming Appointments Table -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark">Upcoming Appointments</h5>
            <span class="badge bg-light text-secondary border">Next 5 Scheduled</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase fs-7 text-muted">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Patient Name</th>
                        <th>Type</th>
                        <th>Reason</th>
                        <th>Scheduled At</th>
                        <th class="text-end pe-4">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data['upcomingAppointments'] ?? [] as $apt)
                        <tr>
                            <td class="ps-4 fw-semibold">#{{ $apt->APPOINTMENT_ID }}</td>
                            <td>{{ $apt->PATIENT_NAME }}</td>
                            <td><span class="badge bg-secondary bg-opacity-10 text-secondary">{{ $apt->APPOINTMENT_TYPE }}</span></td>
                            <td class="text-muted">{{ $apt->APPOINTMENT_REASON }}</td>
                            <td class="fw-medium text-dark">{{ $apt->SCHEDULED_AT }}</td>
                            <td class="text-end pe-4">
                                <span class="badge bg-info bg-opacity-10 text-info fw-semibold px-2 py-1">{{ $apt->STATUS }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No upcoming appointments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Low Stock Items Table -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark">Low Stock Inventory</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase fs-7 text-muted">
                    <tr>
                        <th class="ps-4">Item Code</th>
                        <th>Generic Name</th>
                        <th>Brand Name</th>
                        <th>Category</th>
                        <th>Expiration Date</th>
                        <th class="text-end pe-4">Quantity Remaining</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data['lowStockItems'] ?? [] as $item)
                        <tr>
                            <td class="ps-4 fw-semibold text-secondary">#{{ $item->ITEM_CODE }}</td>
                            <td class="fw-medium text-dark">{{ $item->GENERIC_NAME }}</td>
                            <td>{{ $item->BRAND_NAME }}</td>
                            <td><span class="badge bg-secondary bg-opacity-10 text-secondary">{{ $item->ITEM_CATEGORY }}</span></td>
                            <td class="text-muted">{{ $item->ITEM_EXPIRATION_DATE ? \Carbon\Carbon::parse($item->ITEM_EXPIRATION_DATE)->format('Y-m-d') : 'N/A' }}</td>
                            <td class="text-end pe-4">
                                <span class="badge bg-danger px-2 py-1">{{ $item->ITEM_QUANTITY }} units</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">All inventory stock levels are normal. ✨</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection