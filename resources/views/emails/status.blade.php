<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
</head>

<body>

<h2>Internship {{ $status }}</h2>

@if($status == 'validated')
    <p>Your internship has been validated successfully 🎉</p>
    <p>You will find your certificate attached to this email.</p>
@else
    <p>Your internship has been rejected ❌</p>
@endif

<p>Thank you.</p>

</body>
</html>