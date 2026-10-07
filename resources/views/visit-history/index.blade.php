@extends('layouts.app')

@section('title', 'RAM-CIMS - Visit History')

@section('content')
<div class="mb-4">
    <p class="text-uppercase fw-bold mb-1" style="color:#003B7A; letter-spacing:.08em; font-size:.78rem;">Asia Pacific College</p>
    <h1 class="h3 fw-bold mb-1" style="color:#003B7A;">Visit History</h1>
    <p class="text-muted mb-0">Symptoms, notes, and medicine from your own clinic visits.</p>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-uppercase" style="font-size:.78rem;">
                <tr>
                    <th class="ps-4">Date</th>
                    <th>Diagnosed Symptoms</th>
                    <th>Doctor Notes</th>
                    <th class="pe-4">Prescribed Medicine</th>
                </tr>
            </thead>
            <tbody>
                @forelse($visits as $visit)
                    <tr>
                        <td class="ps-4 fw-semibold">{{ \Carbon\Carbon::parse($visit->MEDREC_CONSUL_DATE)->format('M d, Y') }}</td>
                        <td>{{ $visit->MEDREC_DIAGNOSIS }}</td>
                        <td class="text-muted">{{ $visit->MEDREC_NOTES ?: 'No notes recorded.' }}</td>
                        <td class="pe-4">{{ $visit->MEDREC_MEDICINE_DOSAGE ?: 'None recorded.' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">No clinic visit has been recorded yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
