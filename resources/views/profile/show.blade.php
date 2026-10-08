@extends('layouts.app')

@section('title', 'RAM-CIMS - Profile')

@section('content')
<div class="mb-4">
    <p class="text-uppercase fw-bold mb-1" style="color:#003B7A; letter-spacing:.08em; font-size:.78rem;">Asia Pacific College</p>
    <h1 class="h3 fw-bold mb-1" style="color:#003B7A;">Profile</h1>
    <p class="text-muted mb-0">The account linked to your APC sign-in.</p>
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
@endsection
