<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — VRS</title>
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body class="auth-page">
<div class="auth-container">
    <div class="auth-card">
        <h2 class="auth-title">Forgot Password</h2>
        <p class="auth-subtitle">Enter your registered email address and we'll send you a reset link.</p>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-input" value="{{ old('email') }}" required autofocus placeholder="your@africau.edu">
            </div>
            <button type="submit" class="btn btn-primary w-full">Send Reset Link</button>
        </form>

        <p class="auth-footer">
            Remember your password? <a href="{{ route('auth.login') }}">Back to Login</a>
        </p>
    </div>
</div>
</body>
</html>
