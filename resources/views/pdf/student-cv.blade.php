<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Student CV</title>

<style>
body{
    font-family: DejaVu Sans;
    color:#222;
    font-size:14px;
    line-height:1.6;
}

h1{
    color:#2563eb;
    margin-bottom:5px;
}

h2{
    font-size:18px;
    color:#111;
    border-bottom:1px solid #ddd;
    padding-bottom:4px;
    margin-top:25px;
}

.small{
    color:#666;
    font-size:13px;
}

.section{
    margin-bottom:15px;
}

</style>
</head>

<body>

<h1>{{ $user->name }}</h1>

<div class="small">
{{ $student->university }} |
{{ $student->wilaya }} <br>

{{ $student->phone }} <br>

{{ $student->linkedin }} <br>
{{ $student->github_link }}
</div>

@if($student->cv_summary)
<h2>Profile Summary</h2>
<div class="section">
{{ $student->cv_summary }}
</div>
@endif

@if($student->education)
<h2>Education</h2>
<div class="section">
{!! nl2br(e($student->education)) !!}
</div>
@endif

@if($student->experience)
<h2>Experience</h2>
<div class="section">
{!! nl2br(e($student->experience)) !!}
</div>
@endif

@if($student->projects)
<h2>Projects</h2>
<div class="section">
{!! nl2br(e($student->projects)) !!}
</div>
@endif

@if($student->cv_languages)
<h2>Languages</h2>
<div class="section">
{{ $student->cv_languages }}
</div>
@endif

</body>
</html>