<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RAM-CIMS - Forgot Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    @include('partials.apc-theme')
</head>
<body class="guest-shell">
    <div class="guest-card">
        <img src="{{ asset('images/apc-logo.png') }}" alt="Asia Pacific College">
        <p class="eyebrow mb-2">Asia Pacific College</p>
        <h1 class="h4 fw-bold mb-2">Forgot Password</h1>
        <p class="text-muted">Enter the email on your RAM-CIMS account.</p>

        @if(session('status'))
            <div class="alert alert-success text-start">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger text-start">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="text-start">
            @csrf
            <label class="form-label fw-semibold" for="resetEmail">Email</label>
            <input type="email" class="form-control" id="resetEmail" name="email" value="{{ old('email') }}" autocomplete="username" required>
            <button type="submit" class="btn btn-primary w-100 mt-3 fw-semibold">Send reset link</button>
        </form>
        <a href="{{ route('login') }}" class="btn btn-link mt-2">Back to sign in</a>
    </div>
</body>
</html>
