<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RAM-CIMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    @include('partials.apc-theme')
</head>
<body class="guest-shell">
    <div class="guest-card">
        <img src="{{ asset('images/apc-logo.png') }}" alt="Asia Pacific College">
        <p class="eyebrow mb-2">Asia Pacific College</p>
        <h1 class="h3 fw-bold mb-2">RAM-CIMS</h1>
        <p class="text-muted mb-0">The clinic information system for appointments, inventory, and medical records.</p>
        <div class="guest-actions">
            <a href="{{ url('/dashboard') }}" class="btn btn-primary">Enter the clinic</a>
            <a href="{{ route('login') }}" class="btn btn-outline-primary">Sign in with Microsoft</a>
        </div>
        <p class="guest-note">RAMsey can help once you are inside. User accounts live in AppUser.</p>
    </div>
</body>
</html>
