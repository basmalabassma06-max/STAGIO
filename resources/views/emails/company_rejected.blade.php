<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Company Registration Rejected</title>
</head>
<body style="font-family: Arial, sans-serif; padding: 20px; color: #333;">

    <h2>Sorry 😔</h2>

    {{-- FIX #13: Display the rejection reason passed from AdminController --}}
    <p>Your company registration has been rejected.</p>

    <p><strong>Reason:</strong> {{ $reason }}</p>

    <p>If you believe this is a mistake, please contact our support team.</p>

    <p style="margin-top: 30px; color: #888; font-size: 12px;">
        This is an automated message. Please do not reply directly to this email.
    </p>

</body>
</html>