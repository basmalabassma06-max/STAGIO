<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
body { font-family: DejaVu Sans; }
table { width:100%; border-collapse: collapse; }
th, td { border:1px solid #000; padding:5px; }
</style>
</head>
<body>

<h1>📄 Internships Report</h1>

<table>
    <thead>
        <tr>
            <th>Student</th>
            <th>Company</th>
            <th>Offer</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($internships as $i)
        <tr>
            <td>{{ optional($i->student->user)->name }}</td>
            <td>{{ optional($i->company)->name }}</td>
            <td>{{ optional($i->offer)->title }}</td>
            <td>{{ $i->status }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

</body>
</html>