<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RAM-CIMS - Create an account</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    @include('partials.apc-theme')
</head>
<body class="guest-shell">
    <div class="guest-card">
        <img src="{{ asset('images/apc-logo.png') }}" alt="Asia Pacific College">
        <p class="eyebrow mb-2">Asia Pacific College</p>
        <h1 class="h3 fw-bold mb-3">Create an account</h1>

        @if($errors->any())
            <div class="alert alert-danger mb-3 text-start">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('register') }}" class="sign-in-form text-start">
            @csrf
            <label class="form-label fw-semibold" for="registerName">Full name</label>
            <input type="text" class="form-control mb-3" id="registerName" name="name" value="{{ old('name') }}" autocomplete="name" required>
            <label class="form-label fw-semibold" for="registerEmail">Email</label>
            <input type="email" class="form-control mb-3" id="registerEmail" name="email" value="{{ old('email') }}" autocomplete="email" required>
            <label class="form-label fw-semibold" for="registerPassword">Password</label>
            <input type="password" class="form-control mb-3" id="registerPassword" name="password" autocomplete="new-password" minlength="8" required>
            <label class="form-label fw-semibold" for="registerPasswordConfirmation">Confirm password</label>
            <input type="password" class="form-control mb-3" id="registerPasswordConfirmation" name="password_confirmation" autocomplete="new-password" minlength="8" required>
            <button type="submit" class="btn btn-primary w-100 fw-semibold">Create an account</button>
        </form>

        <a href="{{ route('login') }}" class="btn btn-outline-primary w-100 mt-3 fw-semibold">Back to log in</a>
    </div>
</body>
</html>
