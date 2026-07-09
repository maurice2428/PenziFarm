@php
    if (! function_exists('pdfImageBase64')) {
        function pdfImageBase64(?string $path): ?string
        {
            if (! $path) {
                return null;
            }

            $cleanPath = trim((string) $path);
            $cleanPath = ltrim($cleanPath, '/');
            $cleanPath = preg_replace('#^storage/#', '', $cleanPath);
            $cleanPath = preg_replace('#^public/#', '', $cleanPath);

            foreach ([
                storage_path('app/public/' . $cleanPath),
                public_path('storage/' . $cleanPath),
                public_path($cleanPath),
            ] as $fullPath) {
                if (is_file($fullPath)) {
                    $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

                    $mime = match ($extension) {
                        'jpg', 'jpeg' => 'image/jpeg',
                        'png' => 'image/png',
                        'gif' => 'image/gif',
                        'webp' => 'image/webp',
                        'svg' => 'image/svg+xml',
                        default => 'image/png',
                    };

                    return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullPath));
                }
            }

            return null;
        }
    }

    $eatNow = now('Africa/Nairobi');

    $farmName = setting('farm.name', 'Lelekwe Farms');
    $farmTagline = setting('farm.tagline', 'Nurturing Nature, Feeding The Future');
    $farmPhone = setting('farm.phone', '+254 743 487 186');
    $farmEmail = setting('farm.email', 'jambo@lelekwefarms.co.ke');
    $farmCounty = setting('farm.county', 'Ravine, Kambi Moto');

    $primaryColor = setting('theme.primary', '#014a12');

    $logoBase64 = pdfImageBase64(setting('branding.logo_light'));

    $generatedByName = $generatedBy?->name ?? 'System';
    $generatedByRole = $generatedByRole ?? 'User';

    $totalAnimals = $transfers->sum(fn ($transfer) => $transfer->items->count());
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Animal Transfers Bulk Report</title>

    <style>
        @page {
            margin: 120px 35px 95px 35px;
        }

        body {
            font-family: Courier, "Courier New", monospace;
            font-size: 10px;
            color: #111827;
            background: #ffffff;
        }

        .watermark {
            position: fixed;
            top: 30%;
            left: 12%;
            width: 75%;
            opacity: 0.055;
            z-index: -1;
            text-align: center;
        }

        .watermark img {
            width: 420px;
        }

        header {
            position: fixed;
            top: -95px;
            left: 0;
            right: 0;
            height: 90px;
            border-bottom: 2px solid {{ $primaryColor }};
        }

        .header-table,
        .footer-table,
        .report {
            width: 100%;
            border-collapse: collapse;
        }

        .header-left {
            text-align: left;
        }

        .header-center {
            text-align: center;
        }

        .header-right {
            text-align: right;
            font-size: 10px;
            line-height: 1.5;
            color: #374151;
        }

        .logo {
            width: 180px;
            max-height: 75px;
            object-fit: contain;
        }

        .company-title {
            font-size: 22px;
            font-weight: 700;
            color: {{ $primaryColor }};
            text-align: center;
            text-transform: uppercase;
        }

        .tagline {
            font-size: 11px;
            color: #4b5563;
            font-style: italic;
            text-align: center;
        }

        footer {
            position: fixed;
            bottom: -72px;
            left: 0;
            right: 0;
            height: 62px;
            border-top: 1px solid #d1d5db;
            font-size: 10px;
            color: #4b5563;
        }

        .footer-table {
            margin-top: 8px;
        }

        .footer-left {
            text-align: left;
            width: 33%;
        }

        .footer-center {
            text-align: center;
            width: 34%;
        }

        .footer-right {
            text-align: right;
            width: 33%;
        }

        .small-muted {
            color: #6b7280;
            font-size: 9px;
        }

        .report-title {
            margin-bottom: 14px;
            border: 1px solid #dbe4d3;
            border-left: 7px solid {{ $primaryColor }};
            background: #fbfdf9;
            padding: 12px 14px;
        }

        .report-title h1 {
            font-size: 20px;
            margin: 0 0 4px 0;
            color: #111827;
            text-transform: uppercase;
        }

        .report-title p {
            margin: 0;
            color: {{ $primaryColor }};
            font-size: 10px;
            font-weight: bold;
        }

        .report th {
            background: {{ $primaryColor }};
            border: 1px solid {{ $primaryColor }};
            color: #fff;
            padding: 8px;
            text-align: left;
            text-transform: uppercase;
            font-size: 9px;
        }

        .report td {
            border: 1px solid #e5e7eb;
            padding: 7px;
            vertical-align: top;
        }

        .report tr:nth-child(even) {
            background: #fafafa;
        }
    </style>
</head>

<body>
    <div class="watermark">
        @if ($logoBase64)
            <img src="{{ $logoBase64 }}" alt="Watermark">
        @endif
    </div>

    <header>
        <table class="header-table">
            <tr>
                <td class="header-left" width="110">
                    @if ($logoBase64)
                        <img src="{{ $logoBase64 }}" class="logo" alt="Logo">
                    @endif
                </td>

                <td class="header-center">
                    <div class="company-title">{{ $farmName }}</div>
                    <div class="tagline">{{ $farmTagline }}</div>
                </td>

                <td class="header-right" width="230">
                    <strong>Phone:</strong> {{ $farmPhone }}<br>
                    <strong>Email:</strong> {{ $farmEmail }}<br>
                    <strong>County:</strong> {{ $farmCounty }}
                </td>
            </tr>
        </table>
    </header>

    <footer>
        <table class="footer-table">
            <tr>
                <td class="footer-left">
                    Printed on {{ $eatNow->format('d M Y, H:i') }} EAT
                </td>
                <td class="footer-center">
                    Animal Transfers Bulk Report
                </td>
                <td class="footer-right">
                    Created by {{ $generatedByName }} ({{ $generatedByRole }})
                </td>
            </tr>
            <tr>
                <td colspan="3" class="footer-center small-muted">
                    {{ $farmName }} • {{ $farmCounty }} • {{ $farmPhone }} • {{ $farmEmail }}
                </td>
            </tr>
        </table>
    </footer>

    <main>
        <div class="report-title">
            <h1>Animal Transfers Bulk Report</h1>
            <p>
                Transfers: {{ number_format($transfers->count()) }}
                · Total Animals: {{ number_format($totalAnimals) }}
            </p>
        </div>

        <table class="report">
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="16%">Transfer</th>
                    <th width="16%">From</th>
                    <th width="16%">To</th>
                    <th width="12%">Date</th>
                    <th width="10%">Animals</th>
                    <th width="12%">Status</th>
                    <th width="13%">Prepared By</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($transfers as $index => $transfer)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td><strong>{{ $transfer->transfer_number }}</strong></td>
                        <td>{{ $transfer->fromLocation?->name ?? 'Mixed / Current' }}</td>
                        <td>{{ $transfer->toLocation?->name ?? '-' }}</td>
                        <td>{{ $transfer->transfer_date?->format('d M Y') }}</td>
                        <td>{{ number_format($transfer->items->count()) }}</td>
                        <td>{{ $transfer->status_label }}</td>
                        <td>{{ $transfer->preparedBy?->name ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </main>

    <script type="text/php">
        if (isset($pdf)) {
            $pdf->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) {
                $font = $fontMetrics->getFont('Helvetica', 'normal');
                $size = 9;

                $text = "Page {$pageNumber} of {$pageCount}";
                $width = $fontMetrics->getTextWidth($text, $font, $size);

                $x = 590 - ($width / 2);
                $y = 565;

                $canvas->text($x, $y, $text, $font, $size, [0.42, 0.45, 0.50]);
            });
        }
    </script>
</body>
</html>
