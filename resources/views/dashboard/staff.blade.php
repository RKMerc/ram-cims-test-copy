<div class="mb-4">
    <p class="text-uppercase fw-bold mb-1" style="color:#003B7A; letter-spacing:.08em; font-size:.78rem;">Asia Pacific College</p>
    <h1 class="h3 fw-bold mb-1" style="color:#003B7A;">APC Clinic Operations Dashboard</h1>
    <p class="text-muted mb-0">Today’s queue, upcoming visits, and supplies that need attention.</p>
</div>

@if(!empty($treatmentFirst))
    <div class="alert border-0 shadow-sm mb-4" style="background:#fff8e1; color:#003B7A; border-left:4px solid #E1B11A !important;" role="status">
        Treatment-first mode is on. Emergency visits are called ahead of the regular schedule.
    </div>
@endif

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card h-100 border-start border-4" style="border-color:#003B7A !important;">
            <div class="card-body">
                <h6 class="text-muted fw-semibold text-uppercase mb-2" style="font-size:.78rem; letter-spacing:.04em;">Appointments Today</h6>
                <h2 class="fw-bold mb-0" style="color:#003B7A;">{{ $appointmentsToday ?? 0 }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 border-start border-4" style="border-color:#E1B11A !important;">
            <div class="card-body">
                <h6 class="text-muted fw-semibold text-uppercase mb-2" style="font-size:.78rem; letter-spacing:.04em;">Total Patients Queued</h6>
                <h2 class="fw-bold mb-0" style="color:#003B7A;">{{ $patientsQueued ?? 0 }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 border-start border-4" style="border-color:#b42318 !important;">
            <div class="card-body">
                <h6 class="text-muted fw-semibold text-uppercase mb-2" style="font-size:.78rem; letter-spacing:.04em;">Low Stock Alert</h6>
                <h2 class="fw-bold mb-2" style="color:#003B7A;">{{ ($lowStockItems ?? collect())->count() }}</h2>
                @if(($lowStockItems ?? collect())->isEmpty() && ($configurationItems ?? collect())->isEmpty())
                    <p class="text-muted mb-0">All inventory stock levels are normal.</p>
                @else
                    @if(($lowStockItems ?? collect())->count() > 3)
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-danger dropdown-toggle fw-semibold" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                View items
                            </button>
                            <ul class="dropdown-menu shadow-sm" style="max-height:16rem; overflow:auto; min-width:16rem;">
                                @foreach($lowStockItems as $item)
                                    <li>
                                        <span class="dropdown-item-text d-flex justify-content-between gap-3">
                                            <span class="fw-semibold">{{ $item->GENERIC_NAME }}</span>
                                            <span class="text-danger fw-semibold text-nowrap">{{ $item->ITEM_QUANTITY }} left</span>
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @elseif(($lowStockItems ?? collect())->isNotEmpty())
                        @foreach($lowStockItems as $item)
                            <div class="d-flex justify-content-between gap-2 py-1 border-top">
                                <span class="fw-semibold">{{ $item->GENERIC_NAME }}</span>
                                <span class="text-danger fw-semibold">{{ $item->ITEM_QUANTITY }} left</span>
                            </div>
                        @endforeach
                    @endif
                    @if(($configurationItems ?? collect())->isNotEmpty())
                        <p class="text-muted mb-0 {{ ($lowStockItems ?? collect())->isNotEmpty() ? 'mt-2' : '' }}">{{ $configurationItems->count() }} item{{ $configurationItems->count() === 1 ? ' is' : 's are' }} out of stock and {{ $configurationItems->count() === 1 ? 'has' : 'have' }} no recorded baseline. No quantity was invented.</p>
                        @foreach($configurationItems as $item)
                            <div class="d-flex justify-content-between gap-2 py-1 border-top">
                                <span class="fw-semibold">{{ $item->GENERIC_NAME }}</span>
                                <span class="text-danger fw-semibold">Out of stock</span>
                            </div>
                        @endforeach
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h5 class="fw-bold mb-0" style="color:#003B7A;">Upcoming Appointments &amp; Daily Queue</h5>
        <div class="d-flex flex-wrap gap-2">
            <form method="POST" action="{{ route('dashboard.next-patient') }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-primary fw-semibold">Next Patient</button>
            </form>
            <form method="POST" action="{{ route('dashboard.treatment-mode') }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-primary fw-semibold">Toggle Treatment-First Mode</button>
            </form>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-uppercase" style="font-size:.78rem;">
                <tr>
                    <th class="ps-4">ID</th>
                    <th>Patient Name</th>
                    <th>Type</th>
                    <th>Reason</th>
                    <th>Scheduled At</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($queue ?? [] as $apt)
                    @php
                        $status = strtolower((string) $apt->STATUS);
                        $badge = match ($status) {
                            'completed' => 'bg-success',
                            'cancelled' => 'bg-danger',
                            'in consultation' => 'bg-primary',
                            default => 'bg-warning text-dark',
                        };
                    @endphp
                    <tr>
                        <td class="ps-4 fw-semibold">#{{ $apt->APPOINTMENT_ID }}</td>
                        <td>{{ $apt->PATIENT_NAME }}</td>
                        <td><span class="badge bg-secondary bg-opacity-10 text-secondary">{{ $apt->APPOINTMENT_TYPE }}</span></td>
                        <td class="text-muted">{{ $apt->APPOINTMENT_REASON }}</td>
                        <td class="fw-medium">{{ \Carbon\Carbon::parse($apt->SCHEDULED_AT)->format('M d, Y h:i A') }}</td>
                        <td><span class="badge {{ $badge }} fw-semibold px-2 py-1">{{ $apt->STATUS }}</span></td>
                        <td class="text-end pe-4">
                            <a href="{{ url('/appointments?search_id='.$apt->APPOINTMENT_ID) }}" class="btn btn-sm btn-outline-primary fw-semibold">Open</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No upcoming appointments found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 border-0">
        <h5 class="fw-bold mb-0" style="color:#003B7A;">Low Stock Inventory Alert</h5>
    </div>
    @include('partials.low-stock-table', [
        'items' => $lowStockItems ?? collect(),
    ])
    @if(($configurationItems ?? collect())->isNotEmpty())
        <div class="px-4 py-3 border-top">
            <div class="fw-semibold">Out of stock</div>
            <p class="mb-2 text-muted">These items are out of stock and have no recorded baseline. No stock quantity was invented for them.</p>
            <ul class="mb-0">
                @foreach($configurationItems as $item)
                    <li>{{ $item->GENERIC_NAME }} ({{ $item->ITEM_CATEGORY }}) — Out of stock</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
