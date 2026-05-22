<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — VRS</title>
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body class="auth-page">
<div class="auth-container">
    <div class="auth-card">
        <h2 class="auth-title">Set New Password</h2>
        <p class="auth-subtitle">Your new password must be at least 12 characters long.</p>

        @if ($errors->any())
            <div class="alert alert-error">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">

            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" class="form-input" required minlength="12" placeholder="At least 12 characters">
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirm New Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="form-input" required minlength="12" placeholder="Repeat your new password">
            </div>

            <button type="submit" class="btn btn-primary w-full">Reset Password</button>
        </form>

        <p class="auth-footer">
            <a href="{{ route('auth.login') }}">Back to Login</a>
        </p>
    </div>
</div>
<script src="/assets/js/main.js"></script>
</body>
</html>
