<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RAM-CIMS - Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    @include('partials.apc-theme')
</head>
<body class="guest-shell">
    <div class="guest-card">
        <img src="{{ asset('images/apc-logo.png') }}" alt="Asia Pacific College">
        <p class="eyebrow mb-2">Asia Pacific College</p>
        <h1 class="h4 fw-bold mb-2">Choose a new password</h1>

        @if($errors->any())
            <div class="alert alert-danger text-start">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="text-start">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <label class="form-label fw-semibold" for="resetEmail">Email</label>
            <input type="email" class="form-control mb-2" id="resetEmail" name="email" value="{{ old('email', $email) }}" required>
            <label class="form-label fw-semibold" for="newPassword">Password</label>
            <input type="password" class="form-control mb-2" id="newPassword" name="password" required>
            <label class="form-label fw-semibold" for="confirmPassword">Confirm password</label>
            <input type="password" class="form-control" id="confirmPassword" name="password_confirmation" required>
            <button type="submit" class="btn btn-primary w-100 mt-3 fw-semibold">Reset password</button>
        </form>
    </div>
</body>
</html>
