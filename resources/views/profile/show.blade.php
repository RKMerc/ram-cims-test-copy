@extends('layouts.app')

@section('title', 'RAM-CIMS - Profile')

@section('content')
<div class="mb-4">
    <p class="text-uppercase fw-bold mb-1" style="color:#003B7A; letter-spacing:.08em; font-size:.78rem;">Asia Pacific College</p>
    <h1 class="h3 fw-bold mb-1" style="color:#003B7A;">Profile</h1>
    @if($canManageProfiles ?? false)
        <p class="text-muted mb-0">Open Accounts to update clinic profiles. This page stays read-only.</p>
    @else
        <p class="text-muted mb-0">The account linked to your APC sign-in.</p>
    @endif
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <div class="row g-4">
            <div class="col-md-6">
                <h6 class="text-uppercase fw-semibold mb-1" style="color:#003B7A; font-size:.78rem;">Name</h6>
                <p class="mb-0 fw-semibold">{{ $account->fullName() }}</p>
            </div>
            <div class="col-md-6">
                <h6 class="text-uppercase fw-semibold mb-1" style="color:#003B7A; font-size:.78rem;">Role</h6>
                <p class="mb-0">{{ $account->roleLabel() }}</p>
            </div>
            <div class="col-md-6">
                <h6 class="text-uppercase fw-semibold mb-1" style="color:#003B7A; font-size:.78rem;">Student / Employee No.</h6>
                <p class="mb-0">{{ $account->Student_Employee_No ?: 'Not on file' }}</p>
            </div>
            <div class="col-md-6">
                <h6 class="text-uppercase fw-semibold mb-1" style="color:#003B7A; font-size:.78rem;">Email</h6>
                <p class="mb-0">{{ $account->EmailAddress }}</p>
            </div>
            <div class="col-md-6">
                <h6 class="text-uppercase fw-semibold mb-1" style="color:#003B7A; font-size:.78rem;">Contact</h6>
                <p class="mb-0">{{ $account->ContactNo ?: 'Not on file' }}</p>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-body p-4">
        <h2 class="h5 fw-bold mb-3" style="color:#003B7A;">Connected Accounts</h2>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <div class="fw-semibold">Microsoft Account</div>
                <div class="text-muted">{{ $microsoft ? 'Connected' : 'Not Connected' }}</div>
            </div>
            @unless($microsoft)
                <a class="btn btn-outline-primary" href="{{ route('auth.microsoft') }}">Connect Microsoft Account</a>
            @endunless
        </div>
        <hr>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <div class="fw-semibold">Email confirmation</div>
                <div class="text-muted">{{ auth()->user()?->hasVerifiedEmail() ? 'Confirmed' : 'Not confirmed yet. Existing sign-in still works.' }}</div>
            </div>
            @unless(auth()->user()?->hasVerifiedEmail())
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button class="btn btn-outline-primary">Resend confirmation</button>
                </form>
            @endunless
        </div>
    </div>
</div>
@endsection
