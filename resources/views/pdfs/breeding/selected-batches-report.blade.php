@php
    if (! function_exists('breedingPdfImageBase64')) {
        function breedingPdfImageBase64(?string $path): ?string
        {
            if (! $path) {
                return null;
            }

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
                        default => 'image/png',
                    };

                    return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullPath));
                }
            }

            return null;
        }
    }

    if (! function_exists('breedingPdfDate')) {
        function breedingPdfDate($date, string $format = 'd M Y'): string
        {
            if (! $date) {
                return '-';
            }

            try {
                return \Carbon\Carbon::parse($date)->format($format);
            } catch (\Throwable $e) {
                return '-';
            }
        }
    }

    if (! function_exists('breedingPdfLabel')) {
        function breedingPdfLabel(?string $value): string
        {
            if (! $value) {
                return '-';
            }

            return str($value)->replace('_', ' ')->title()->toString();
        }
    }

    $eatNow = now('Africa/Nairobi');

    $farmName = setting('farm.name', config('app.name', 'Lelekwe Farms'));
    $farmTagline = setting('farm.tagline', 'Nurturing Nature, Feeding The Future');
    $farmPhone = setting('farm.phone', '+254 743 487 186');
    $farmEmail = setting('farm.email', 'jambo@lelekwefarms.co.ke');
    $farmCounty = setting('farm.county', 'Ravine, Kambi Moto');

    $primaryColor = setting('theme.primary', '#014a12');
    $secondaryColor = setting('theme.secondary', '#14532d');
    $accentColor = setting('theme.accent', '#f59e0b');
    $dangerColor = setting('theme.danger', '#dc2626');
    $successColor = setting('theme.success', '#16a34a');

    $logoBase64 = breedingPdfImageBase64(
        setting('branding.logo_light')
            ?: setting('branding.logo')
            ?: setting('farm.logo')
    );

    $generatedByName = $generatedBy->name ?? 'System';
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $reportNumber }}</title>

    <style>
        @font-face {
            font-family: "ChopinScript";
            src: url("{{ public_path('fonts/ChopinScript.ttf') }}") format("truetype");
            font-weight: normal;
            font-style: normal;
        }

        @page {
            margin: 112px 28px 82px 28px;
        }

        body {
            font-family: Courier, sans-serif;
            font-size: 9.5px;
            color: #222;
            position: relative;
        }

        .watermark {
            position: fixed;
            top: 22%;
            left: 20%;
            width: 60%;
            opacity: 0.045;
            z-index: -1;
            text-align: center;
        }

        .watermark img {
            width: 410px;
        }

        header {
            position: fixed;
            top: -92px;
            left: 0;
            right: 0;
            height: 82px;
            border-bottom: 2px solid {{ $primaryColor }};
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .header-table td {
            vertical-align: middle;
        }

        .header-left {
            width: 210px;
            text-align: left;
        }

        .header-center {
            text-align: center;
            width: auto;
        }

        .header-right {
            width: 230px;
            text-align: right;
            font-size: 9px;
            line-height: 1.45;
            color: #374151;
        }

        .logo {
            width: 140px;
            max-height: 68px;
            object-fit: contain;
        }

        .company-title {
            font-size: 21px;
            font-weight: 700;
            color: {{ $primaryColor }};
            margin-bottom: 2px;
            text-align: center;
            line-height: 1.15;
        }

        .tagline {
            font-size: 10px;
            color: #4b5563;
            font-style: italic;
            text-align: center;
            line-height: 1.3;
        }

        footer {
            position: fixed;
            bottom: -62px;
            left: 0;
            right: 0;
            height: 52px;
            border-top: 1px solid #d1d5db;
            font-size: 9px;
            color: #4b5563;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 7px;
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
            font-size: 8px;
        }

        .report-title {
            margin-top: 4px;
            margin-bottom: 10px;
        }

        .report-title h1 {
            font-size: 17px;
            margin: 0 0 4px 0;
            color: #111827;
        }

        .report-title p {
            margin: 0;
            color: {{ $primaryColor }};
            font-size: 9px;
        }

        .kpi-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0 12px 0;
        }

        .kpi-table td {
            padding: 0;
            vertical-align: top;
        }

        .kpi {
            border: 1px solid #dbe4d3;
            background: #ffffff;
            padding: 8px 9px;
            min-height: 50px;
        }

        .kpi-label {
            font-size: 8px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: .35px;
            font-weight: bold;
        }

        .kpi-value {
            margin-top: 4px;
            font-size: 14px;
            font-weight: bold;
            color: #111827;
        }

        .kpi-note {
            margin-top: 3px;
            font-size: 8px;
            color: #6b7280;
        }

        .success {
            color: {{ $successColor }};
        }

        .warning {
            color: {{ $accentColor }};
        }

        .danger {
            color: {{ $dangerColor }};
        }

        .section-heading {
            margin-top: 13px;
            margin-bottom: 7px;
            padding: 7px 9px;
            background: #f3f7ef;
            border-left: 4px solid {{ $primaryColor }};
            color: #111827;
            font-size: 10.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .3px;
        }

        .insight-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .insight-table td {
            vertical-align: top;
            padding: 0;
        }

        .insight-box {
            border: 1px solid #dbe4d3;
            background: #fbfdf9;
            padding: 9px;
            min-height: 78px;
        }

        .insight-title {
            font-size: 9px;
            color: {{ $primaryColor }};
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .35px;
            margin-bottom: 6px;
        }

        .insight-line {
            font-size: 9px;
            line-height: 1.55;
            color: #374151;
        }

        table.report {
            width: 100%;
            border-collapse: collapse;
            margin-top: 7px;
        }

        table.report thead th {
            background: {{ $primaryColor }};
            border: 1px solid {{ $primaryColor }};
            color: #ffffff;
            padding: 7px 5px;
            font-size: 8px;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: .2px;
        }

        table.report tbody td {
            border: 1px solid #e5e7eb;
            padding: 6px 5px;
            vertical-align: top;
            font-size: 8.5px;
            line-height: 1.35;
        }

        table.report tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .status-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 999px;
            font-size: 7.5px;
            font-weight: bold;
            color: #ffffff;
            text-transform: uppercase;
        }

        .badge-success {
            background: {{ $successColor }};
        }

        .badge-warning {
            background: {{ $accentColor }};
        }

        .badge-danger {
            background: {{ $dangerColor }};
        }

        .badge-info {
            background: {{ $primaryColor }};
        }

        .badge-gray {
            background: #64748b;
        }

        .batch-card {
            border: 1px solid #dbe4d3;
            margin-top: 12px;
            page-break-inside: avoid;
        }

        .batch-header {
            background: #f3f7ef;
            border-bottom: 1px solid #dbe4d3;
            padding: 8px 9px;
        }

        .batch-title {
            font-size: 12px;
            font-weight: bold;
            color: #111827;
        }

        .batch-subtitle {
            margin-top: 2px;
            font-size: 8.5px;
            color: #4b5563;
        }

        .batch-meta {
            width: 100%;
            border-collapse: collapse;
            margin-top: 7px;
        }

        .batch-meta td {
            font-size: 8.5px;
            padding: 3px 0;
            color: #374151;
        }

        .meta-label {
            font-weight: bold;
            color: #111827;
        }

        .recommendation {
            margin-top: 10px;
            border: 1px solid #dbe4d3;
            background: #fbfdf9;
            padding: 8px;
            font-size: 9px;
            line-height: 1.45;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .signature-table td {
            vertical-align: top;
        }

        .signature-card {
            border: 1px solid #dbe4d3;
            padding: 10px 12px;
            background: #fbfdf9;
            min-height: 86px;
        }

        .signature-card-title {
            font-size: 9px;
            font-weight: bold;
            color: {{ $secondaryColor }};
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: .35px;
        }

        .signature-name {
            font-size: 11px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 4px;
        }

        .signature-meta {
            font-size: 8px;
            color: #6b7280;
            margin-bottom: 10px;
        }

        .signature-line {
            border-top: 1px solid #4b5563;
            margin-top: 16px;
            padding-top: 5px;
        }

        .signature-handwritten {
            font-family: "ChopinScript" !important;
            font-size: 24px;
            color: {{ $successColor }};
            letter-spacing: 1px;
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
                <td class="header-left">
                    @if ($logoBase64)
                        <img src="{{ $logoBase64 }}" class="logo" alt="Logo">
                    @endif
                </td>

                <td class="header-center">
                    <div class="company-title">{{ $farmName }}</div>
                    <div class="tagline">{{ $farmTagline }}</div>
                </td>

                <td class="header-right">
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
                    Selected Breeding Batches Report
                </td>
                <td class="footer-right">
                    Generated by {{ $generatedByName }} ({{ $generatedByRole }})
                </td>
            </tr>
            <tr>
                <td colspan="3" class="footer-center small-muted">
                    {{ $farmName }} - {{ $farmCounty }} - {{ $farmPhone }} - {{ $farmEmail }}
                </td>
            </tr>
        </table>
    </footer>

    <main>
        <div class="report-title">
            <h1>Selected Breeding Batches Report</h1>
            <p>
                Report No: {{ $reportNumber }}
                | Selected Batches: {{ number_format($insights['total_batches']) }}
                | Females Covered: {{ number_format($insights['total_females']) }}
            </p>
        </div>

        <table class="kpi-table">
            <tr>
                <td style="width: 16.66%; padding-right: 5px;">
                    <div class="kpi">
                        <div class="kpi-label">Batches</div>
                        <div class="kpi-value">{{ number_format($insights['total_batches']) }}</div>
                        <div class="kpi-note">Selected for print</div>
                    </div>
                </td>

                <td style="width: 16.66%; padding: 0 5px;">
                    <div class="kpi">
                        <div class="kpi-label">Females</div>
                        <div class="kpi-value">{{ number_format($insights['total_females']) }}</div>
                        <div class="kpi-note">Breeding records</div>
                    </div>
                </td>

                <td style="width: 16.66%; padding: 0 5px;">
                    <div class="kpi">
                        <div class="kpi-label">Cross Breeding</div>
                        <div class="kpi-value warning">{{ number_format($insights['cross_breeding_records']) }}</div>
                        <div class="kpi-note">Different breed pairings</div>
                    </div>
                </td>

                <td style="width: 16.66%; padding: 0 5px;">
                    <div class="kpi">
                        <div class="kpi-label">Clear Inbreeding</div>
                        <div class="kpi-value success">{{ number_format($insights['clear_inbreeding']) }}</div>
                        <div class="kpi-note">No close relation</div>
                    </div>
                </td>

                <td style="width: 16.66%; padding: 0 5px;">
                    <div class="kpi">
                        <div class="kpi-label">Warnings</div>
                        <div class="kpi-value warning">{{ number_format($insights['warning_inbreeding']) }}</div>
                        <div class="kpi-note">Review required</div>
                    </div>
                </td>

                <td style="width: 16.66%; padding-left: 5px;">
                    <div class="kpi">
                        <div class="kpi-label">Due Window</div>
                        <div class="kpi-value" style="font-size: 10px;">
                            {{ $insights['due_from'] ?? '-' }}<br>
                            to {{ $insights['due_to'] ?? '-' }}
                        </div>
                        <div class="kpi-note">Expected delivery range</div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="section-heading">Smart Breeding Insights</div>

        <table class="insight-table">
            <tr>
                <td style="width: 33.33%; padding-right: 7px;">
                    <div class="insight-box">
                        <div class="insight-title">Operational View</div>
                        <div class="insight-line">
                            Natural batches: <strong>{{ number_format($insights['natural_batches']) }}</strong><br>
                            AI batches: <strong>{{ number_format($insights['ai_batches']) }}</strong><br>
                            Embryo transfer batches: <strong>{{ number_format($insights['embryo_batches']) }}</strong><br>
                            Next expected due: <strong>{{ $insights['next_due'] ?? '-' }}</strong>
                        </div>
                    </div>
                </td>

                <td style="width: 33.33%; padding: 0 7px;">
                    <div class="insight-box">
                        <div class="insight-title">Species Distribution</div>
                        <div class="insight-line">
                            @forelse ($insights['species_breakdown'] as $species => $count)
                                {{ $species }}: <strong>{{ number_format($count) }}</strong><br>
                            @empty
                                No species data available.
                            @endforelse
                        </div>
                    </div>
                </td>

                <td style="width: 33.33%; padding-left: 7px;">
                    <div class="insight-box">
                        <div class="insight-title">Top Female Breeds</div>
                        <div class="insight-line">
                            @forelse ($insights['breed_breakdown']->take(5) as $breed => $count)
                                {{ $breed }}: <strong>{{ number_format($count) }}</strong><br>
                            @empty
                                No breed data available.
                            @endforelse
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="recommendation">
            <strong>System recommendation:</strong>
            Review any record marked <strong>warning</strong> or <strong>blocked</strong> before final breeding approval.
            For cross-breeding batches, confirm the breeding objective, expected offspring purpose, and breed improvement plan.
            Delivery monitoring should begin before the earliest expected due date shown in this report.
        </div>

        <div class="section-heading">Selected Batch Summary</div>

        <table class="report">
            <thead>
                <tr>
                    <th width="10%">Batch No.</th>
                    <th width="16%">Batch Name</th>
                    <th width="9%">Type</th>
                    <th width="9%">Species</th>
                    <th width="11%">Sire</th>
                    <th width="11%">Sire Breed</th>
                    <th width="7%" class="center">Females</th>
                    <th width="9%">Mating</th>
                    <th width="9%">Due From</th>
                    <th width="9%">Due To</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($batches as $batch)
                    <tr>
                        <td>{{ $batch->batch_number }}</td>
                        <td>{{ $batch->name }}</td>
                        <td>{{ breedingPdfLabel($batch->breeding_type) }}</td>
                        <td>{{ $batch->species ?? '-' }}</td>
                        <td>{{ $batch->male?->tag_number ?? '-' }}</td>
                        <td>{{ $batch->maleBreed?->breed_name ?? $batch->male?->breed?->breed_name ?? '-' }}</td>
                        <td class="center">{{ number_format((int) $batch->records->count()) }}</td>
                        <td>{{ breedingPdfDate($batch->mating_date) }}</td>
                        <td>{{ breedingPdfDate($batch->expected_due_from) }}</td>
                        <td>{{ breedingPdfDate($batch->expected_due_to) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @foreach ($batches as $batch)
            @php
                $batchRecords = $batch->records;

                $batchWarnings = $batchRecords
                    ->whereIn('inbreeding_status', ['warning', 'blocked'])
                    ->count();

                $batchCross = $batchRecords
                    ->where('is_cross_breed', true)
                    ->count();
            @endphp

            <div class="batch-card">
                <div class="batch-header">
                    <div class="batch-title">
                        {{ $batch->batch_number }} - {{ $batch->name }}
                    </div>

                    <div class="batch-subtitle">
                        Sire:
                        <strong>{{ $batch->male?->tag_number ?? '-' }}</strong>
                        |
                        Type:
                        <strong>{{ breedingPdfLabel($batch->breeding_type) }}</strong>
                        |
                        Females:
                        <strong>{{ number_format($batchRecords->count()) }}</strong>
                        |
                        Cross:
                        <strong>{{ number_format($batchCross) }}</strong>
                        |
                        Relationship Warnings:
                        <strong>{{ number_format($batchWarnings) }}</strong>
                    </div>
                </div>

                <table class="batch-meta">
                    <tr>
                        <td style="width: 25%;">
                            <span class="meta-label">Species:</span>
                            {{ $batch->species ?? '-' }}
                        </td>
                        <td style="width: 25%;">
                            <span class="meta-label">Mating Date:</span>
                            {{ breedingPdfDate($batch->mating_date) }}
                        </td>
                        <td style="width: 25%;">
                            <span class="meta-label">Expected From:</span>
                            {{ breedingPdfDate($batch->expected_due_from) }}
                        </td>
                        <td style="width: 25%;">
                            <span class="meta-label">Expected To:</span>
                            {{ breedingPdfDate($batch->expected_due_to) }}
                        </td>
                    </tr>
                </table>

                <table class="report">
                    <thead>
                        <tr>
                            <th width="10%">Female</th>
                            <th width="13%">Female Breed</th>
                            <th width="10%">Male</th>
                            <th width="13%">Male Breed</th>
                            <th width="7%" class="center">Cross</th>
                            <th width="9%">Mating</th>
                            <th width="9%">Due Date</th>
                            <th width="7%" class="center">Days</th>
                            <th width="10%">Inbreeding</th>
                            <th width="12%">Pregnancy</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($batchRecords as $record)
                            @php
                                $inbreedingClass = match ($record->inbreeding_status) {
                                    'clear' => 'badge-success',
                                    'warning' => 'badge-warning',
                                    'blocked' => 'badge-danger',
                                    default => 'badge-gray',
                                };

                                $pregnancyClass = match ($record->pregnancy_status) {
                                    'confirmed' => 'badge-success',
                                    'delivered' => 'badge-info',
                                    'aborted' => 'badge-danger',
                                    'not_pregnant' => 'badge-gray',
                                    default => 'badge-warning',
                                };
                            @endphp

                            <tr>
                                <td>{{ $record->female?->tag_number ?? '-' }}</td>
                                <td>{{ $record->femaleBreed?->breed_name ?? $record->female?->breed?->breed_name ?? '-' }}</td>
                                <td>{{ $record->male?->tag_number ?? '-' }}</td>
                                <td>{{ $record->maleBreed?->breed_name ?? $record->male?->breed?->breed_name ?? '-' }}</td>
                                <td class="center">{{ $record->is_cross_breed ? 'Yes' : 'No' }}</td>
                                <td>{{ breedingPdfDate($record->mating_date) }}</td>
                                <td>{{ breedingPdfDate($record->expected_due_date) }}</td>
                                <td class="center">{{ $record->gestation_days }}</td>
                                <td>
                                    <span class="status-badge {{ $inbreedingClass }}">
                                        {{ breedingPdfLabel($record->inbreeding_status) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge {{ $pregnancyClass }}">
                                        {{ breedingPdfLabel($record->pregnancy_status) }}
                                    </span>
                                </td>
                            </tr>

                            @if ($record->relationship_notes && $record->relationship_notes !== 'No close relationship detected.')
                                <tr>
                                    <td colspan="10">
                                        <strong>Relationship note:</strong>
                                        {{ $record->relationship_notes }}
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach

        <table class="signature-table">
            <tr>
                <td style="width: 33.33%; padding-right: 10px;">
                    <div class="signature-card">
                        <div class="signature-card-title">Prepared By</div>
                        <div class="signature-name">{{ $generatedByName }}</div>
                        <div class="signature-meta">{{ $generatedByRole }}</div>
                        <div class="signature-line"></div>
                        <div class="small-muted">
                            Generated on {{ $eatNow->format('d M Y, H:i') }} EAT
                        </div>
                    </div>
                </td>

                <td style="width: 33.33%; padding: 0 10px;">
                    <div class="signature-card">
                        <div class="signature-card-title">Reviewed By</div>
                        <div class="signature-handwritten">Reviewed</div>
                        <div class="signature-meta">Farm Manager / Breeding Lead</div>
                        <div class="signature-line"></div>
                        <div class="small-muted">
                            Signature and date
                        </div>
                    </div>
                </td>

                <td style="width: 33.33%; padding-left: 10px;">
                    <div class="signature-card">
                        <div class="signature-card-title">Approved By</div>
                        <div class="signature-handwritten">Approved</div>
                        <div class="signature-meta">Director / Authorized Officer</div>
                        <div class="signature-line"></div>
                        <div class="small-muted">
                            Signature and date
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </main>

    <script type="text/php">
        if (isset($pdf)) {
            $pdf->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) {
                $font = $fontMetrics->getFont('Helvetica', 'normal');
                $size = 8;

                $text = "Page {$pageNumber} of {$pageCount}";
                $width = $fontMetrics->getTextWidth($text, $font, $size);

                $x = 420 - ($width / 2);
                $y = 570;

                $canvas->text($x, $y, $text, $font, $size, [0.42, 0.45, 0.50]);
            });
        }
    </script>
</body>
</html>
