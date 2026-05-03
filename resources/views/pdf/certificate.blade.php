<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
body { font-family: DejaVu Sans; text-align: center; }
.title { font-size: 22px; margin-top: 20px; }
.content { margin-top: 30px; font-size: 16px; }
</style>
</head>

<body>
 

<h2>Certificate of Internship</h2>

<div class="content">
    <p>This is to certify that</p>

    <h3>{{ optional($internship->student->user)->name }}</h3>

    <p>has successfully completed an internship at</p>

    <h3>{{optional($internship->company)->name }}</h3>

    <p>for the position:</p>

    <h4>{{ optional($internship->offer)->title}}</h4>

    <p>Date: {{ now()->format('Y-m-d') }}</p>
</div>

<br><br>

</body>
</html>