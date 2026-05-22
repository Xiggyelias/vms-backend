<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Registration Expiring Soon</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 560px; margin: 40px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
        .header { background: #b45309; color: #fff; padding: 24px 32px; }
        .header h1 { margin: 0; font-size: 20px; }
        .body { padding: 32px; color: #374151; line-height: 1.6; }
        .vehicle-card { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 16px; margin: 20px 0; }
        .vehicle-card p { margin: 4px 0; font-size: 14px; }
        .vehicle-card strong { color: #111827; }
        .btn { display: inline-block; margin: 24px 0 8px; padding: 14px 28px; background: #1d4ed8; color: #fff; text-decoration: none; border-radius: 6px; font-weight: bold; }
        .footer { padding: 16px 32px; background: #f9fafb; font-size: 12px; color: #6b7280; border-top: 1px solid #e5e7eb; }
        @if($daysLeft <= 3)
        .header { background: #dc2626; }
        @endif
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>⚠ Registration Expiring in {{ $daysLeft }} Day(s)</h1>
    </div>
    <div class="body">
        <p>Hello <strong>{{ $owner->fullName }}</strong>,</p>

        <p>This is a reminder that your vehicle registration is expiring soon. Please renew it before the deadline to avoid suspension and penalties.</p>

        <div class="vehicle-card">
            <p><strong>Plate Number:</strong> {{ $vehicle->PlateNumber }}</p>
            <p><strong>Vehicle:</strong> {{ $vehicle->make }} {{ $vehicle->model }}</p>
            <p><strong>Registration No:</strong> {{ $vehicle->regNumber }}</p>
            <p><strong>Expiry Date:</strong> <span style="color: #dc2626; font-weight: bold;">{{ $vehicle->registration_expiry?->format('F d, Y') }}</span></p>
        </div>

        <a href="{{ url('/vehicle-details.php?id=' . $vehicle->vehicle_id) }}" class="btn">Renew Registration Now</a>

        <p style="font-size: 13px; color: #6b7280; margin-top: 8px;">
            If you have already renewed, please disregard this message.
        </p>
    </div>
    <div class="footer">
        &copy; {{ date('Y') }} Vehicle Registration System &mdash; Africa University
    </div>
</div>
</body>
</html>
