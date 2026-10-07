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
    <div class="guest-card guest-card-roles">
        <img src="{{ asset('images/apc-logo.png') }}" alt="Asia Pacific College">
        <p class="eyebrow mb-2">Asia Pacific College</p>
        <h1 class="h3 fw-bold mb-2">RAM-CIMS</h1>
        <p class="text-muted mb-0">Clinic Information Management System</p>

        @if(session('error'))
            <div class="alert alert-danger mt-3 mb-0">{{ session('error') }}</div>
        @endif

        <div class="role-folders">
            <section class="role-folder" aria-labelledby="student-folder">
                <h2 id="student-folder" class="role-folder-tab">Student</h2>
                <div class="role-folder-body">
                    <h3>Welcome to APC Clinic</h3>
                    <p>Log in with your APC account to get started.</p>
                    <a href="{{ route('auth.microsoft') }}" class="btn btn-primary py-2 d-flex align-items-center justify-content-center gap-2 fw-semibold">
                        <svg width="20" height="20" viewBox="0 0 21 21" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <rect x="1" y="1" width="9" height="9" fill="#f25022"/>
                            <rect x="1" y="11" width="9" height="9" fill="#00a4ef"/>
                            <rect x="11" y="1" width="9" height="9" fill="#7fba00"/>
                            <rect x="11" y="11" width="9" height="9" fill="#ffb900"/>
                        </svg>
                        Log in with APC Microsoft Account
                    </a>
                </div>
            </section>

            <section class="role-folder role-folder-staff" aria-labelledby="staff-folder">
                <h2 id="staff-folder" class="role-folder-tab">Staff</h2>
                <div class="role-folder-body">
                    <h3>RAM-CIMS Medical Access</h3>
                    <p>Secure health information management portal for APC healthcare personnel.</p>
                    <a href="{{ route('auth.microsoft') }}" class="btn btn-outline-primary py-2 d-flex align-items-center justify-content-center gap-2 fw-semibold">
                        <svg width="20" height="20" viewBox="0 0 21 21" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <rect x="1" y="1" width="9" height="9" fill="#f25022"/>
                            <rect x="1" y="11" width="9" height="9" fill="#00a4ef"/>
                            <rect x="11" y="1" width="9" height="9" fill="#7fba00"/>
                            <rect x="11" y="11" width="9" height="9" fill="#ffb900"/>
                        </svg>
                        Sign in with Staff Microsoft Account
                    </a>
                </div>
            </section>
        </div>
    </div>
</body>
</html>
