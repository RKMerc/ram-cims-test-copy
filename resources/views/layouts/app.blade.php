<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'RAM-CIMS')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    @include('partials.apc-theme')
</head>
<body class="app-shell">
    <header class="app-nav">
        <div class="container-xl py-2 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <a class="brand-lockup" href="{{ url('/dashboard') }}">
                <img class="brand-mark" src="{{ asset('images/apc-logo.png') }}" alt="Asia Pacific College">
                <span>
                    <strong>RAM-CIMS</strong>
                    <small>Asia Pacific College Clinic</small>
                </span>
            </a>
            <nav class="app-links" aria-label="Primary">
                <a class="{{ request()->is('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a>
                <a class="{{ request()->is('appointments') || request()->is('appointments/*') ? 'active' : '' }}" href="{{ url('/appointments') }}">Appointments</a>
                @if($isClinicStaff)
                    <a class="{{ request()->is('inventory') || request()->is('inventory/*') ? 'active' : '' }}" href="{{ url('/inventory') }}">Inventory</a>
                    <a class="{{ request()->is('medical-records') || request()->is('records') || request()->is('records/*') ? 'active' : '' }}" href="{{ url('/medical-records') }}">Medical Records</a>
                    <a class="{{ request()->is('analytics') ? 'active' : '' }}" href="{{ route('analytics') }}">Analytics</a>
                @else
                    <a class="{{ request()->is('visit-history') ? 'active' : '' }}" href="{{ route('visit-history') }}">Visit History</a>
                    <a class="{{ request()->is('profile') ? 'active' : '' }}" href="{{ route('profile') }}">Profile</a>
                @endif
            </nav>
        </div>
    </header>

    <main class="app-main">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-warning alert-dismissible fade show shadow-sm" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="app-footer">Asia Pacific College · Clinic Information Management System</footer>

    @include('partials.ramsey')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
