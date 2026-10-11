<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RAM-CIMS - Connect Microsoft</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    @include('partials.apc-theme')
</head>
<body class="guest-shell">
    <div class="guest-card">
        <img src="{{ asset('images/apc-logo.png') }}" alt="Asia Pacific College">
        <p class="eyebrow mb-2">Asia Pacific College</p>
        <h1 class="h4 fw-bold mb-2">Connect Microsoft</h1>
        <p class="text-muted">Enter the password for {{ session('microsoft.pending_link.email') }} to keep the existing patient record.</p>

        @if($errors->any())
            <div class="alert alert-danger text-start">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('microsoft.link.confirm') }}" class="text-start">
            @csrf
            <label class="form-label fw-semibold" for="linkPassword">Password</label>
            <input type="password" class="form-control" id="linkPassword" name="password" autocomplete="current-password" required>
            <button type="submit" class="btn btn-primary w-100 mt-3 fw-semibold">Connect Microsoft Account</button>
        </form>
    </div>
</body>
</html>
