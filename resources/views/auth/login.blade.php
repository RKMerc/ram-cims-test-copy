<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RAM-CIMS - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    @include('partials.apc-theme')
</head>
<body class="guest-shell">
    <div class="guest-card">
        <img src="{{ asset('images/apc-logo.png') }}" alt="Asia Pacific College">
        <p class="eyebrow mb-2">Asia Pacific College</p>
        <h1 class="h3 fw-bold mb-2">Sign In to RAM-CIMS</h1>
        <p class="text-muted mb-0">Clinic Information Management System</p>

        @if(session('status'))
            <div class="alert alert-success mt-3 mb-0 text-start">{{ session('status') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger mt-3 mb-0 text-start">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger mt-3 mb-0 text-start">{{ $errors->first() }}</div>
        @endif

        <section class="sign-in-panel">
            <form method="POST" action="{{ route('login.attempt') }}" class="sign-in-form text-start" novalidate>
                @csrf
                <label class="form-label fw-semibold" for="loginEmail">Email</label>
                <input type="email" class="form-control mb-2" id="loginEmail" name="email" value="{{ old('email') }}" autocomplete="username">
                <label class="form-label fw-semibold" for="loginPassword">Password</label>
                <input type="password" class="form-control" id="loginPassword" name="password" autocomplete="current-password">
                <button type="submit" class="btn btn-primary w-100 mt-3 fw-semibold">Sign in</button>
            </form>

            <a href="{{ route('register.show') }}" class="btn btn-outline-primary py-2 fw-semibold">Create an account</a>
            <a href="{{ route('password.request') }}" class="btn btn-link py-1">Forgot password</a>

            <a href="{{ route('auth.microsoft') }}" class="btn btn-outline-primary py-2 d-flex align-items-center justify-content-center gap-2 fw-semibold">
        <div class="guest-actions">
            @if(app()->environment('production'))
                <a href="{{ route('preview', 'student') }}" class="btn btn-primary py-2 fw-semibold">Preview student portal</a>
                <a href="{{ route('preview', 'staff') }}" class="btn btn-outline-primary py-2 fw-semibold">Preview clinic staff portal</a>
            @endif
            <a href="{{ route('auth.microsoft') }}" class="btn btn-primary py-2 d-flex align-items-center justify-content-center gap-2 fw-semibold">
                <svg width="20" height="20" viewBox="0 0 21 21" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <rect x="1" y="1" width="9" height="9" fill="#f25022"/>
                    <rect x="1" y="11" width="9" height="9" fill="#00a4ef"/>
                    <rect x="11" y="1" width="9" height="9" fill="#7fba00"/>
                    <rect x="11" y="11" width="9" height="9" fill="#ffb900"/>
                </svg>
                Continue with Microsoft
            </a>
        </section>
    </div>
</body>
</html>
