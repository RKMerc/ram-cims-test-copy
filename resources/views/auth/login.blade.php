<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RAM-CIMS - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">
    <div class="card shadow-sm border-0 rounded-4 p-4 text-center" style="width: 100%; max-width: 400px;">
        <div class="card-body">
            <h3 class="fw-bold text-primary mb-2">RAM-CIMS</h3>
            <p class="text-muted mb-4">Clinic Information Management System</p>

            @if(session('error'))
                <div class="alert alert-danger text-sm">{{ session('error') }}</div>
            @endif

            <a href="{{ route('auth.microsoft') }}" class="btn btn-dark w-100 py-2 d-flex align-items-center justify-content-center gap-2 rounded-2 fw-semibold">
                <!-- Microsoft Icon SVG -->
                <svg width="20" height="20" viewBox="0 0 21 21" xmlns="http://www.w3.org/2000/svg">
                    <rect x="1" y="1" width="9" height="9" fill="#f25022"/>
                    <rect x="1" y="11" width="9" height="9" fill="#00a4ef"/>
                    <rect x="11" y="1" width="9" height="9" fill="#7fba00"/>
                    <rect x="11" y="11" width="9" height="9" fill="#ffb900"/>
                </svg>
                Sign in with Microsoft
            </a>
        </div>
    </div>
</body>
</html>