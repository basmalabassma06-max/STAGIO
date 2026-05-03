<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
body { font-family: DejaVu Sans; }
h1 { text-align:center; }
</style>
</head>
<body>

<h1>📊 Admin Statistics</h1>

<p><strong>Students:</strong> {{ $data['students'] }}</p>
<p><strong>Companies:</strong> {{ $data['companies'] }}</p>
<p><strong>Admins:</strong> {{ $data['admins'] }}</p>

<p><strong>Validated:</strong> {{ $data['validated'] }}</p>
<p><strong>Pending:</strong> {{ $data['pending'] }}</p>
<p><strong>Rejected:</strong> {{ $data['rejected'] }}</p>
<p><strong>Total:</strong> {{ $data['total'] }}</p>

<p><strong>Placement Rate:</strong> {{ $data['placement_rate'] }}%</p>

<p>Generated at: {{ $data['generated_at'] }}</p>

</body>
</html>