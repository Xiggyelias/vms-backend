<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 560px; margin: 40px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
        .header { background: #1d4ed8; color: #fff; padding: 24px 32px; }
        .header h1 { margin: 0; font-size: 20px; }
        .body { padding: 32px; color: #374151; line-height: 1.6; }
        .btn { display: inline-block; margin: 24px 0; padding: 14px 28px; background: #1d4ed8; color: #fff; text-decoration: none; border-radius: 6px; font-weight: bold; }
        .footer { padding: 16px 32px; background: #f9fafb; font-size: 12px; color: #6b7280; border-top: 1px solid #e5e7eb; }
        .note { background: #fef9c3; border-left: 4px solid #f59e0b; padding: 12px 16px; border-radius: 4px; margin-top: 16px; font-size: 13px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Vehicle Registration System</h1>
    </div>
    <div class="body">
        <p>Hello <strong>{{ $user->fullName }}</strong>,</p>
        <p>We received a request to reset your VRS account password. Click the button below to set a new password:</p>

        <a href="{{ $resetUrl }}" class="btn">Reset My Password</a>

        <div class="note">
            This link expires in <strong>1 hour</strong>. If you did not request a password reset, you can safely ignore this email — your password will not change.
        </div>

        <p style="margin-top: 24px; font-size: 13px; color: #6b7280;">
            If the button above does not work, copy and paste this URL into your browser:<br>
            <span style="word-break: break-all;">{{ $resetUrl }}</span>
        </p>
    </div>
    <div class="footer">
        &copy; {{ date('Y') }} Vehicle Registration System &mdash; Africa University
    </div>
</div>
</body>
</html>
