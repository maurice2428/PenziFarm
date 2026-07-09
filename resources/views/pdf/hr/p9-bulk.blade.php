@php
    $pageBreakClass = 'page-break';
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Bulk P9A Forms</title>
    <style>
        @page {
            margin: 0;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Courier, monospace;
            color: #111;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>
    @foreach ($rows as $row)
        @include('pdf.hr.partials.p9-content', [
            'employee' => $row['employee'],
            'year' => $row['year'],
            'byMonth' => $row['byMonth'],
            'generatedBy' => $generatedBy,
        ])

        @if (!$loop->last)
            <div class="{{ $pageBreakClass }}"></div>
        @endif
    @endforeach
</body>

</html>
