<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
body { font-family: DejaVu Sans; }
.header { text-align:center; }
.title { font-size:20px; margin-top:20px; }
</style>
</head>

<body>



<div class="header">
    <h2>{{ $uni['name'] }}</h2>
    <p>{{ $uni['faculty'] }} - {{ $uni['department'] }}</p>

    @if($uni['logo'])
        <img src="{{ $uni['logo'] }}" width="100">
    @endif
</div>

<div class="title">
    <strong>Convention de Stage</strong>
</div>

<p><strong>Étudiant:</strong> {{ optional($internship->student->user)->name }}</p>
<p><strong>Entreprise:</strong> {{ optional($internship->company)->name }}</p>
<p><strong>Offre:</strong> {{ optional($internship->offer)->title }}</p>
<p><strong>Date:</strong> {{ now()->format('Y-m-d') }}</p>

<br><br>


</body>
</html>