@php
    if (!function_exists('pdfImageBase64')) {
        function pdfImageBase64(?string $path): ?string
        {
            if (!$path) return null;

            $cleanPath = trim($path);
            $cleanPath = ltrim($cleanPath, '/');
            $cleanPath = preg_replace('#^storage/#', '', $cleanPath);

            $possiblePaths = [
                storage_path('app/public/' . $cleanPath),
                public_path('storage/' . $cleanPath),
                public_path($cleanPath),
            ];

            foreach ($possiblePaths as $fullPath) {
                if (is_file($fullPath)) {
                    $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

                    $mime = match ($extension) {
                        'jpg', 'jpeg' => 'image/jpeg',
                        'png' => 'image/png',
                        'gif' => 'image/gif',
                        'webp' => 'image/webp',
                        'svg' => 'image/svg+xml',
                        'ico' => 'image/x-icon',
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

    $primaryColor = trim(setting('theme.primary', '#014a12'));
    $secondaryColor = trim(setting('theme.secondary', '#14532d'));
    $accentColor = trim(setting('theme.accent', '#f59e0b'));
    $dangerColor = trim(setting('theme.danger', '#dc2626'));
    $successColor = trim(setting('theme.success', '#16a34a'));

    $logoBase64 = pdfImageBase64(setting('branding.logo_light'));

    $generatedByName = $generatedBy->name ?? 'System';
    $generatedByRole = $generatedByRole ?? 'User';

    $totalRecords = $weights->count();
    $totalAnimals = $weights->pluck('animal_id')->unique()->count();
    $averageWeight = $weights->avg('weight_kg');
    $highestWeight = $weights->max('weight_kg');
    $lowestWeight = $weights->min('weight_kg');
    $losingCount = $weights->filter(fn ($w) => $w->trend === 'losing')->count();
    $gainingCount = $weights->filter(fn ($w) => $w->trend === 'gaining')->count();
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Animal Weight Bulk Report</title>

    <style>
        @font-face {
            font-family: "ChopinScript";
            src: url("{{ public_path('fonts/ChopinScript.ttf') }}") format("truetype");
            font-weight: normal;
            font-style: normal;
        }

        @page {
            margin: 120px 35px 95px 35px;
        }

        body {
            font-family: Courier, sans-serif;
            font-size: 10px;
            color: #222;
            position: relative;
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

        .header-table,
        .footer-table,
        .cards-table,
        .signature-table,
        table.report {
            width: 100%;
            border-collapse: collapse;
        }

        .header-left {
            text-align: left;
            vertical-align: middle;
        }

        .header-center {
            text-align: center;
            vertical-align: middle;
        }

        .header-right {
            text-align: right;
            vertical-align: middle;
            font-size: 10px;
            line-height: 1.5;
            color: #374151;
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

        .logo {
            width: 180px;
        }

        .company-title {
            font-size: 22px;
            font-weight: 700;
            color: {{ $primaryColor }};
            margin-bottom: 2px;
            text-align: center;
        }

        .tagline {
            font-size: 11px;
            color: #4b5563;
            font-style: italic;
            text-align: center;
        }

        .report-title {
            margin-top: 5px;
            margin-bottom: 12px;
        }

        .report-title h1 {
            font-size: 18px;
            margin: 0 0 4px 0;
            color: #111827;
        }

        .report-title p {
            margin: 0;
            color: {{ $primaryColor }};
            font-size: 10px;
        }

        .section-label {
            font-size: 12px;
            font-weight: bold;
            color: {{ $secondaryColor }};
            margin: 18px 0 8px 0;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .summary-card {
            border: 1px solid #dbe4d3;
            border-radius: 10px;
            padding: 10px 12px;
            background: #fbfdf9;
            min-height: 68px;
        }

        .summary-card-green {
            border: 1px solid #cfe3bf;
            background: #f8fff2;
        }

        .summary-card-gold {
            border: 1px solid #f6dfb3;
            background: #fffaf0;
        }

        .summary-card-red {
            border: 1px solid #f2c4c4;
            background: #fff5f5;
        }

        .summary-card-title {
            font-size: 9px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 7px;
            font-weight: bold;
        }

        .summary-card-value {
            font-size: 18px;
            font-weight: bold;
            color: #111827;
            line-height: 1.2;
        }

        .summary-card-sub {
            font-size: 8px;
            color: #6b7280;
            margin-top: 4px;
        }

        table.report {
            margin-top: 10px;
        }

        table.report thead th {
            background: {{ $primaryColor }};
            border: 1px solid {{ $primaryColor }};
            color: #fff;
            padding: 8px 6px;
            font-size: 8.7px;
            text-align: left;
        }

        table.report tbody td {
            border: 1px solid #e5e7eb;
            padding: 6px;
            vertical-align: top;
            font-size: 8.7px;
        }

        table.report tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .pill {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 999px;
            font-size: 8px;
            font-weight: bold;
            color: #fff;
            white-space: nowrap;
        }

        .pill-green {
            background: {{ $successColor }};
        }

        .pill-red {
            background: {{ $dangerColor }};
        }

        .pill-yellow {
            background: {{ $accentColor }};
        }

        .pill-blue {
            background: {{ $secondaryColor }};
        }

        .pill-gray {
            background: #6b7280;
        }

        .section-block {
            margin-top: 22px;
        }

        .signature-table td {
            vertical-align: top;
        }

        .signature-card {
            border: 1px solid #dbe4d3;
            border-radius: 10px;
            padding: 12px 14px;
            background: #fbfdf9;
            min-height: 118px;
        }

        .signature-card-authorized {
            border: 1px solid #cfe3bf;
            background: #f8fff2;
        }

        .signature-card-title {
            font-size: 11px;
            font-weight: bold;
            color: {{ $secondaryColor }};
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .signature-name {
            font-size: 13px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 4px;
        }

        .signature-meta {
            font-size: 9px;
            color: #6b7280;
            margin-bottom: 12px;
        }

        .signature-handwritten {
            font-family: "ChopinScript" !important;
            font-size: 25px;
            color: {{ $successColor }};
            letter-spacing: 1px;
        }

        .signature-line {
            border-top: 1px solid #4b5563;
            margin-top: 18px;
            padding-top: 6px;
        }

        .signature-footer {
            font-size: 9px;
            color: #6b7280;
            margin-top: 4px;
        }

        .stamp-circle {
            width: 110px;
            height: 110px;
            margin: 0 auto 8px auto;
            border: 1px dashed {{ $primaryColor }};
            border-radius: 50%;
            display: table;
        }

        .stamp-text {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            color: {{ $primaryColor }};
            line-height: 1.4;
        }

        .stamp-caption {
            font-size: 9px;
            color: #6b7280;
            text-align: center;
        }

        .qr-box {
            text-align: center;
        }

        .qr-image-wrap {
            width: 104px;
            height: 104px;
            margin: 4px auto 8px auto;
            border: 2px solid {{ $primaryColor }};
            background: #fff;
            border-radius: 8px;
            padding-top: 6px;
        }

        .qr-image {
            width: 90px;
            height: 90px;
            display: block;
            margin: 0 auto;
        }

        .qr-fallback {
            width: 104px;
            height: 104px;
            margin: 4px auto 8px auto;
            border: 1px solid #999;
            border-radius: 8px;
            font-size: 9px;
            display: table;
            background: #fff;
        }

        .qr-fallback span {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
        }

        .qr-caption {
            font-size: 8px;
            color: #6b7280;
            margin-top: 6px;
            line-height: 1.4;
            text-align: center;
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
                    Animal Weight Bulk Report
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
            <h1>Animal Weight Bulk Report</h1>
            <p>Total selected weight records: {{ number_format($totalRecords) }}</p>
        </div>

        <div class="section-label">Weight Summary Overview</div>

        <table class="cards-table">
            <tr>
                <td style="width:16.66%; padding-right:6px; padding-bottom:8px;">
                    <div class="summary-card summary-card-green">
                        <div class="summary-card-title">Animals</div>
                        <div class="summary-card-value">{{ number_format($totalAnimals) }}</div>
                        <div class="summary-card-sub">Unique animals</div>
                    </div>
                </td>

                <td style="width:16.66%; padding-right:6px; padding-bottom:8px;">
                    <div class="summary-card">
                        <div class="summary-card-title">Records</div>
                        <div class="summary-card-value">{{ number_format($totalRecords) }}</div>
                        <div class="summary-card-sub">Selected entries</div>
                    </div>
                </td>

                <td style="width:16.66%; padding-right:6px; padding-bottom:8px;">
                    <div class="summary-card summary-card-gold">
                        <div class="summary-card-title">Average</div>
                        <div class="summary-card-value">{{ number_format((float) $averageWeight, 2) }}</div>
                        <div class="summary-card-sub">KG average</div>
                    </div>
                </td>

                <td style="width:16.66%; padding-right:6px; padding-bottom:8px;">
                    <div class="summary-card">
                        <div class="summary-card-title">Highest</div>
                        <div class="summary-card-value">{{ number_format((float) $highestWeight, 2) }}</div>
                        <div class="summary-card-sub">KG highest</div>
                    </div>
                </td>

                <td style="width:16.66%; padding-right:6px; padding-bottom:8px;">
                    <div class="summary-card">
                        <div class="summary-card-title">Gaining</div>
                        <div class="summary-card-value">{{ number_format($gainingCount) }}</div>
                        <div class="summary-card-sub">Improving</div>
                    </div>
                </td>

                <td style="width:16.66%; padding-bottom:8px;">
                    <div class="summary-card summary-card-red">
                        <div class="summary-card-title">Losing</div>
                        <div class="summary-card-value">{{ number_format($losingCount) }}</div>
                        <div class="summary-card-sub">Needs attention</div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="section-label">Animal Weight Details</div>

        <table class="report">
            <thead>
                <tr>
                    <th width="4%">#</th>
                    <th width="10%">Animal Tag</th>
                    <th width="12%">Breed</th>
                    <th width="7%">Species</th>
                    <th width="6%">Sex</th>
                    <th width="9%">Previous</th>
                    <th width="9%">Current</th>
                    <th width="13%">Trend</th>
                    <th width="12%">Recorded At</th>
                    <th width="8%">By</th>
                    <th width="10%">Remarks</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($weights as $index => $weight)
                    <tr>
                        <td>{{ $index + 1 }}</td>

                        <td>
                            <strong>{{ $weight->animal?->tag_number ?? '-' }}</strong>
                        </td>

                        <td>{{ $weight->animal?->breed?->breed_name ?? '-' }}</td>

                        <td>{{ $weight->animal?->species ?? '-' }}</td>

                        <td>{{ $weight->animal?->sex ?? '-' }}</td>

                        <td>
                            {{ $weight->previous_weight_kg ? number_format((float) $weight->previous_weight_kg, 2) . ' KG' : 'First Entry' }}
                        </td>

                        <td>
                            <strong>{{ number_format((float) $weight->weight_kg, 2) }} KG</strong>
                        </td>

                        <td>
                            @if ($weight->trend === 'gaining')
                                <span class="pill pill-green">
                                    Gained {{ number_format(abs((float) $weight->weight_difference), 2) }} KG
                                </span>
                            @elseif ($weight->trend === 'losing')
                                <span class="pill pill-red">
                                    Lost {{ number_format(abs((float) $weight->weight_difference), 2) }} KG
                                </span>
                            @elseif ($weight->trend === 'stable')
                                <span class="pill pill-yellow">Stable</span>
                            @else
                                <span class="pill pill-blue">First Entry</span>
                            @endif
                        </td>

                        <td>{{ $weight->recorded_at?->format('d M Y, h:i A') ?? '-' }}</td>

                        <td>{{ $weight->recorder?->name ?? 'System' }}</td>

                        <td>{{ $weight->remarks ?: '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="section-block">
            <div class="section-label">Approval & Verification</div>

            <table class="signature-table">
                <tr>
                    <td style="width: 26%; padding-top: 10px; padding-right: 8px;">
                        <div class="signature-card">
                            <div class="signature-card-title">Prepared By</div>
                            <div class="signature-name">{{ $generatedByName }}</div>
                            <div class="signature-meta">{{ $generatedByRole }}</div>
                            <div class="signature-line"></div>
                            <div class="signature-footer">
                                Generated on {{ $eatNow->format('d M Y, H:i') }} EAT
                            </div>
                        </div>
                    </td>

                    <td style="width: 28%; padding-top: 10px; padding-right: 8px;">
                        <div class="signature-card signature-card-authorized">
                            <div class="signature-card-title">Authorized Signature</div>

                            <div class="signature-handwritten">
                                Digitally.Approved!
                            </div>

                            <div class="signature-meta">
                                Signature Date: {{ $eatNow->format('d M Y, H:i') }} EAT
                            </div>

                            <div class="signature-line"></div>

                            <div class="signature-footer">
                                {{ $farmName }} Management Approval
                            </div>
                        </div>
                    </td>

                    <td style="width: 22%; padding-top: 10px; padding-right: 8px;">
                        <div class="stamp-circle">
                            <div class="stamp-text">OFFICIAL<br>STAMP</div>
                        </div>

                        <div class="stamp-caption">{{ $farmName }} Stamp</div>
                    </td>

                    <td style="width: 24%; padding-top: 10px;">
                        <div class="qr-box">
                            @if (!empty($qrImage))
                                <div class="qr-image-wrap">
                                    <img src="{{ $qrImage }}" alt="QR Code" class="qr-image">
                                </div>
                            @else
                                <div class="qr-fallback">
                                    <span>QR not available</span>
                                </div>
                            @endif

                            <div class="qr-caption">
                                Scan to verify report metadata
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </main>

    <script type="text/php">
        if (isset($pdf)) {
            $pdf->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) {
                $font = $fontMetrics->getFont('Helvetica', 'normal');
                $size = 9;

                $text = "Page {$pageNumber} of {$pageCount}";
                $width = $fontMetrics->getTextWidth($text, $font, $size);

                $x = 420 - ($width / 2);
                $y = 565;

                $canvas->text($x, $y, $text, $font, $size, [0.42, 0.45, 0.50]);
            });
        }
    </script>
</body>
</html>
