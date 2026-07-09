<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>KRA P9A Form</title>
</head>
<body>
    @include('pdf.hr.partials.p9-content', [
        'employee' => $employee,
        'year' => $year,
        'byMonth' => $byMonth,
        'generatedBy' => $generatedBy,
    ])
</body>
</html>
