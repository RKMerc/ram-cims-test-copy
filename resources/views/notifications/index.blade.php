@extends('layouts.app')

@section('title', 'RAM-CIMS - Notifications')

@section('content')
<div class="mb-4">
    <p class="text-uppercase fw-bold mb-1" style="color:#003B7A; letter-spacing:.08em; font-size:.78rem;">Clinic personnel</p>
    <h1 class="h3 fw-bold mb-1" style="color:#003B7A;">Notifications</h1>
    <p class="text-muted mb-0">Low-stock and out-of-stock alerts. Marking one as read does not restock the item.</p>
</div>

<div class="card border-0 shadow-sm">
    <div class="list-group list-group-flush">
        @forelse($notifications as $note)
            @php $read = $readIds->contains($note->id); @endphp
            <div class="list-group-item px-4 py-3">
                <div class="d-flex justify-content-between gap-3 flex-wrap">
                    <div>
                        <div class="fw-semibold">{{ $note->title }}</div>
                        <p class="mb-1">{{ $note->body }}</p>
                        <small class="text-muted">{{ $note->created_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</small>
                    </div>
                    <div class="d-flex align-items-start gap-2">
                        @if($note->item_code)
                            <a class="btn btn-sm btn-outline-primary" href="{{ url('/inventory') }}">Inventory</a>
                        @endif
                        @if($read)
                            <span class="badge text-bg-light border">Read</span>
                        @else
                            <form method="POST" action="{{ route('notifications.read', $note) }}">
                                @csrf
                                <button class="btn btn-sm btn-primary">Mark as read</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="list-group-item px-4 py-5 text-muted">No clinic alerts yet.</div>
        @endforelse
    </div>
</div>
@endsection
