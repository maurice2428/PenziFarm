@php
    use App\Models\Settings\PaymentSetting;

    if (! function_exists('progenyPdfImageBase64')) {
        function progenyPdfImageBase64(?string $path): ?string
        {
            if (! $path) {
                return null;
            }

            $cleanPath = ltrim(trim((string) $path), '/');
            $cleanPath = preg_replace('#^storage/#', '', $cleanPath);

            $possiblePaths = [
                storage_path('app/public/' . $cleanPath),
                public_path('storage/' . $cleanPath),
                public_path($cleanPath),
            ];

            foreach ($possiblePaths as $fullPath) {
                if (! is_file($fullPath)) {
                    continue;
                }

                $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
                $mime = match ($extension) {
                    'jpg', 'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    'webp' => 'image/webp',
                    'svg' => 'image/svg+xml',
                    default => 'image/png',
                };

                return 'data:' . $mime . ';base64,'
                    . base64_encode(file_get_contents($fullPath));
            }

            return null;
        }
    }

    $paymentSettings = class_exists(PaymentSetting::class)
        ? PaymentSetting::current()
        : null;

    $eatNow = $generatedAt ?? now('Africa/Nairobi');

    $farmName = setting('farm.name', 'Penzi Farm');
    $farmTagline = setting('farm.tagline', 'Nurturing Nature, Feeding The Future');
    $farmPhone = setting('farm.phone', '-');
    $farmEmail = setting('farm.email', '-');
    $farmCounty = setting('farm.county', 'Nakuru County');
    $farmAddress = setting('farm.address', $farmCounty);

    $primaryColor = trim(setting('theme.primary', '#14532d'));
    $secondaryColor = trim(setting('theme.secondary', '#166534'));
    $accentColor = trim(setting('theme.accent', '#f59e0b'));
    $dangerColor = trim(setting('theme.danger', '#dc2626'));
    $successColor = trim(setting('theme.success', '#16a34a'));

    $logoBase64 = progenyPdfImageBase64(setting('branding.logo_light'));

    $signatureBase64 = progenyPdfImageBase64(
        data_get($paymentSettings, 'authorized_signature_image')
        ?: data_get($paymentSettings, 'signature_path')
        ?: data_get($paymentSettings, 'authorized_signature_path')
        ?: setting('branding.signature')
    );

    $stampBase64 = progenyPdfImageBase64(
        data_get($paymentSettings, 'payment_stamp_image')
        ?: data_get($paymentSettings, 'stamp_path')
        ?: data_get($paymentSettings, 'official_stamp_path')
        ?: setting('branding.stamp')
    );

    $generatedByName = $generatedBy?->name ?? 'System';
    $generatedByRole = $generatedByRole ?? 'User';

    $recommendation = $metrics['recommendation'] ?? 'insufficient_data';
    $recommendationLabel = str($recommendation)
        ->replace('_', ' ')
        ->title()
        ->toString();

    $recommendationColor = match ($recommendation) {
        'retain' => $successColor,
        'monitor' => $accentColor,
        'sell' => '#ea580c',
        'cull' => $dangerColor,
        default => '#64748b',
    };

    $recommendationBackground = match ($recommendation) {
        'retain' => '#f0fdf4',
        'monitor' => '#fffbeb',
        'sell' => '#fff7ed',
        'cull' => '#fef2f2',
        default => '#f8fafc',
    };

    $reportModeLabel = $mode === 'ancestors'
        ? 'Ancestral Heredity Report'
        : 'Progeny and Descendant Report';
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $reportModeLabel }} - {{ $animal->tag_number }}</title>

    <style>
        @page {
            margin: 118px 34px 94px 34px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Courier, monospace;
            font-size: 9.4px;
            line-height: 1.38;
            color: #1f2937;
            background: #ffffff;
        }

        .watermark {
            position: fixed;
            top: 28%;
            left: 12%;
            width: 76%;
            opacity: .032;
            z-index: -10;
            text-align: center;
        }

        .watermark img {
            width: 420px;
            max-height: 420px;
            object-fit: contain;
        }

        header {
            position: fixed;
            top: -97px;
            left: 0;
            right: 0;
            height: 88px;
            border-bottom: 3px solid {{ $primaryColor }};
        }

        footer {
            position: fixed;
            bottom: -71px;
            left: 0;
            right: 0;
            height: 60px;
            border-top: 1px solid #d1d5db;
            color: #4b5563;
            font-size: 8.4px;
        }

        .header-table,
        .footer-table,
        .title-table,
        .subject-table,
        .metric-table,
        .decision-table,
        .signature-table,
        .pdf-node-card,
        .pdf-child-row {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: middle;
        }

        .logo {
            width: 140px;
            max-height: 70px;
            object-fit: contain;
        }

        .company-title {
            text-align: center;
            color: {{ $primaryColor }};
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .35px;
        }

        .tagline {
            margin-top: 3px;
            text-align: center;
            color: #4b5563;
            font-size: 9px;
            font-style: italic;
        }

        .header-right {
            width: 230px;
            text-align: right;
            font-size: 8.5px;
            line-height: 1.5;
        }

        .footer-table {
            margin-top: 7px;
        }

        .footer-left {
            width: 33%;
            text-align: left;
        }

        .footer-center {
            width: 34%;
            text-align: center;
        }

        .footer-right {
            width: 33%;
            text-align: right;
        }

        .small-muted {
            color: #6b7280;
            font-size: 8px;
        }

        .report-top {
            margin-bottom: 11px;
            padding: 11px 13px;
            border: 1px solid #dbe4d3;
            border-left: 7px solid {{ $primaryColor }};
            background: #fbfdf9;
        }

        .report-title {
            margin: 0;
            color: #111827;
            font-size: 20px;
            line-height: 1.1;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .report-subtitle {
            margin-top: 5px;
            color: {{ $primaryColor }};
            font-size: 9.5px;
            font-weight: bold;
        }

        .report-score-box {
            width: 185px;
            text-align: right;
        }

        .report-score-label {
            color: #6b7280;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .report-score-value {
            margin-top: 3px;
            color: {{ $primaryColor }};
            font-size: 20px;
            font-weight: bold;
        }

        .badge {
            display: inline-block;
            padding: 3px 7px;
            border-radius: 999px;
            background: {{ $primaryColor }};
            color: #ffffff;
            font-size: 7.8px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .subject-table,
        .metric-table,
        .decision-table,
        .signature-table {
            border-spacing: 7px 0;
            border-collapse: separate;
        }

        .subject-card {
            width: 25%;
            vertical-align: top;
            padding: 9px;
            border: 1px solid #dbe4d3;
            border-top: 3px solid {{ $primaryColor }};
            background: #ffffff;
        }

        .label {
            color: #6b7280;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .3px;
        }

        .value {
            margin-top: 3px;
            color: #111827;
            font-size: 11px;
            font-weight: bold;
        }

        .score {
            color: {{ $primaryColor }};
        }

        .small {
            margin-top: 2px;
            color: #6b7280;
            font-size: 7.8px;
        }

        .section-title {
            margin: 13px 0 7px;
            padding: 6px 8px;
            border-left: 5px solid {{ $primaryColor }};
            background: #f3f7f1;
            color: {{ $primaryColor }};
            font-size: 10.5px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .metric-card {
            vertical-align: top;
            padding: 8px 5px;
            border: 1px solid #dbe4d3;
            border-top: 3px solid {{ $secondaryColor }};
            background: #fbfdf9;
            text-align: center;
        }

        .metric-card .value {
            color: {{ $primaryColor }};
            font-size: 12px;
        }

        .decision-card {
            vertical-align: top;
            padding: 10px;
            border: 1px solid #e5e7eb;
            background: #ffffff;
        }

        .decision-system {
            border-left: 5px solid {{ $recommendationColor }};
            background: {{ $recommendationBackground }};
        }

        .recommendation {
            margin-top: 4px;
            color: {{ $recommendationColor }};
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .tree-summary {
            margin-bottom: 7px;
            padding: 7px 9px;
            border: 1px solid #dbe4d3;
            background: #fbfdf9;
            color: #4b5563;
        }

        .tree-summary strong {
            color: {{ $primaryColor }};
        }

        .tree-wrap {
            padding: 8px;
            border: 1px solid #dbe4d3;
            background: rgba(255, 255, 255, .96);
        }

        .pdf-tree-node {
            page-break-inside: avoid;
        }

        .pdf-node-card {
            page-break-inside: avoid;
            border: 1px solid #d1d5db;
            border-left: 5px solid #64748b;
            background: #ffffff;
        }

        .pdf-node-main {
            padding: 7px 8px;
            vertical-align: top;
        }

        .pdf-node-tag {
            color: #111827;
            font-size: 10px;
            font-weight: bold;
        }

        .pdf-node-detail {
            margin-top: 2px;
            color: #6b7280;
            font-size: 7.8px;
        }

        .pdf-node-generation {
            width: 42px;
            padding: 7px;
            vertical-align: top;
            text-align: center;
            font-size: 8px;
            font-weight: bold;
        }

        .pdf-children {
            margin-left: 14px;
            padding-left: 12px;
            border-left: 2px solid #cbd5e1;
        }

        .pdf-child-row {
            page-break-inside: avoid;
            margin-top: 5px;
        }

        .pdf-connector {
            width: 13px;
            border-top: 2px solid #cbd5e1;
        }

        .pdf-child-content {
            padding-top: 5px;
        }

        .signature-block {
            margin-top: 16px;
        }

        .signature-card {
            min-height: 104px;
            vertical-align: top;
            padding: 9px;
            border: 1px solid #dbe4d3;
            background: #fbfdf9;
        }

        .signature-title {
            margin-bottom: 6px;
            color: {{ $secondaryColor }};
            font-size: 8.7px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .signature-name {
            color: #111827;
            font-size: 11px;
            font-weight: bold;
        }

        .signature-line {
            margin-top: 13px;
            padding-top: 5px;
            border-top: 1px solid #4b5563;
        }

        .signature-img {
            display: block;
            max-width: 135px;
            max-height: 50px;
            margin: 3px 0 4px;
        }

        .signature-fallback {
            margin: 7px 0;
            color: {{ $successColor }};
            font-size: 14px;
            font-weight: bold;
            font-style: italic;
        }

        .stamp-wrap,
        .qr-box {
            text-align: center;
        }

        .stamp-img {
            display: block;
            max-width: 105px;
            max-height: 84px;
            margin: 0 auto 5px;
        }

        .stamp-circle {
            width: 86px;
            height: 86px;
            margin: 0 auto 5px;
            border: 2px dashed {{ $primaryColor }};
            border-radius: 50%;
            display: table;
        }

        .stamp-text {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
            color: {{ $primaryColor }};
            font-size: 9px;
            font-weight: bold;
            line-height: 1.3;
        }

        .qr-image-wrap,
        .qr-fallback {
            width: 88px;
            height: 88px;
            margin: 0 auto 5px;
            padding: 4px;
            border: 2px solid {{ $primaryColor }};
            background: #ffffff;
        }

        .qr-image {
            width: 76px;
            height: 76px;
        }
    </style>
</head>

<body>
    @if ($logoBase64)
        <div class="watermark">
            <img src="{{ $logoBase64 }}" alt="Watermark">
        </div>
    @endif

    <header>
        <table class="header-table">
            <tr>
                <td width="150">
                    @if ($logoBase64)
                        <img src="{{ $logoBase64 }}" class="logo" alt="Logo">
                    @endif
                </td>

                <td>
                    <div class="company-title">{{ $farmName }}</div>
                    <div class="tagline">{{ $farmTagline }}</div>
                </td>

                <td class="header-right">
                    <strong>Phone:</strong> {{ $farmPhone }}<br>
                    <strong>Email:</strong> {{ $farmEmail }}<br>
                    <strong>Location:</strong> {{ $farmAddress }}
                </td>
            </tr>
        </table>
    </header>

    <footer>
        <table class="footer-table">
            <tr>
                <td class="footer-left">
                    Printed {{ $eatNow->format('d M Y, H:i') }} EAT
                </td>
                <td class="footer-center">
                    {{ $reportModeLabel }} | {{ $animal->tag_number }}
                </td>
                <td class="footer-right">
                    Created by {{ $generatedByName }} ({{ $generatedByRole }})
                </td>
            </tr>
            <tr>
                <td colspan="3" class="footer-center small-muted">
                    {{ $farmName }} | {{ $farmCounty }} | {{ $farmPhone }} | {{ $farmEmail }}
                </td>
            </tr>
        </table>
    </footer>

    <main>
        <div class="report-top">
            <table class="title-table">
                <tr>
                    <td>
                        <h1 class="report-title">{{ $reportModeLabel }}</h1>
                        <div class="report-subtitle">
                            Animal {{ $animal->tag_number }} |
                            {{ $generations }} generation{{ $generations === 1 ? '' : 's' }} |
                            <span class="badge">{{ strtoupper($mode) }}</span>
                        </div>
                    </td>

                    <td class="report-score-box">
                        <div class="report-score-label">Breeding Performance</div>
                        <div class="report-score-value">
                            {{ number_format((float) ($metrics['score'] ?? 0), 1) }}/100
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <table class="subject-table">
            <tr>
                <td class="subject-card">
                    <div class="label">Animal</div>
                    <div class="value">{{ $animal->tag_number }}</div>
                    <div class="small">
                        {{ $animal->breed?->breed_name ?? '-' }} |
                        {{ $animal->species }} |
                        {{ $animal->sex }}
                    </div>
                </td>

                <td class="subject-card">
                    <div class="label">Registered Sire</div>
                    <div class="value">{{ $animal->sire?->tag_number ?? '-' }}</div>
                    <div class="small">
                        {{ $animal->sire?->breed?->breed_name ?? 'Not recorded' }}
                    </div>
                </td>

                <td class="subject-card">
                    <div class="label">Registered Dam</div>
                    <div class="value">{{ $animal->dam?->tag_number ?? '-' }}</div>
                    <div class="small">
                        {{ $animal->dam?->breed?->breed_name ?? 'Not recorded' }}
                    </div>
                </td>

                <td class="subject-card">
                    <div class="label">Current Record</div>
                    <div class="value">{{ $animal->status }}</div>
                    <div class="small">
                        {{ $animal->location?->name ?? 'No location' }}
                        @if ($animal->breed_purity_percent !== null)
                            | {{ number_format((float) $animal->breed_purity_percent, 2) }}% purity
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        <div class="section-title">Performance Summary</div>

        <table class="metric-table">
            <tr>
                @if (($metrics['role'] ?? null) === 'dam')
                    @foreach ([
                        ['Services', $metrics['services'] ?? 0],
                        ['Deliveries', $metrics['deliveries'] ?? 0],
                        ['Abortions', $metrics['abortions'] ?? 0],
                        ['Live Births', $metrics['live_births'] ?? 0],
                        ['Weaned', $metrics['weaned'] ?? 0],
                        ['Mothering', number_format((float) ($metrics['mothering_score'] ?? 0), 2) . '/5'],
                    ] as [$label, $value])
                        <td class="metric-card">
                            <div class="label">{{ $label }}</div>
                            <div class="value">{{ $value }}</div>
                        </td>
                    @endforeach
                @else
                    @foreach ([
                        ['Direct Offspring', $metrics['direct_offspring'] ?? 0],
                        ['All Descendants', $metrics['all_descendants'] ?? 0],
                        ['Active', $metrics['active_offspring'] ?? 0],
                        ['Breeder Offspring', $metrics['breeder_offspring'] ?? 0],
                        ['Survival', number_format((float) ($metrics['survival_rate'] ?? 0), 1) . '%'],
                        ['Avg Purity', number_format((float) ($metrics['average_offspring_purity'] ?? 0), 1) . '%'],
                    ] as [$label, $value])
                        <td class="metric-card">
                            <div class="label">{{ $label }}</div>
                            <div class="value">{{ $value }}</div>
                        </td>
                    @endforeach
                @endif
            </tr>
        </table>

        <table class="decision-table" style="margin-top: 10px;">
            <tr>
                <td class="decision-card decision-system" style="width: 50%;">
                    <div class="label">System Decision Support</div>
                    <div class="recommendation">{{ $recommendationLabel }}</div>
                    <div class="small" style="margin-top: 4px;">
                        {{ $metrics['reason'] ?? '-' }}
                    </div>
                </td>

                <td class="decision-card" style="width: 50%;">
                    <div class="label">Latest Authorised Review</div>
                    @if ($latestReview)
                        <div class="value">{{ $latestReview->recommendation_label }}</div>
                        <div class="small" style="margin-top: 4px;">
                            {{ $latestReview->reason }}<br>
                            Reviewed {{ $latestReview->reviewed_at?->format('d M Y H:i') }}.
                        </div>
                    @else
                        <div class="value">No authorised decision recorded</div>
                        <div class="small">
                            The system indication is advisory until reviewed by authorised staff.
                        </div>
                    @endif
                </td>
            </tr>
        </table>

        <div class="section-title">
            {{ $mode === 'ancestors' ? 'Ancestral Heredity Tree' : 'Progeny and Descendant Tree' }}
        </div>

        <div class="tree-summary">
            <strong>Report scope:</strong>
            {{ $generations }} generation{{ $generations === 1 ? '' : 's' }} |
            Male lineage cards use blue borders |
            Female lineage cards use pink borders |
            Connector lines represent registered family relationships.
        </div>

        <div class="tree-wrap">
            @include('pdf.partials.progeny-tree-node', ['node' => $tree])
        </div>

        <div class="signature-block">
            <table class="signature-table">
                <tr>
                    <td class="signature-card" style="width: 24%;">
                        <div class="signature-title">Prepared By</div>
                        <div class="signature-name">{{ $generatedByName }}</div>
                        <div class="small-muted">{{ $generatedByRole }}</div>
                        <div class="signature-line"></div>
                        <div class="small-muted">
                            Generated {{ $eatNow->format('d M Y, H:i') }} EAT
                        </div>
                    </td>

                    <td class="signature-card" style="width: 28%;">
                        <div class="signature-title">Authorised Signature</div>

                        @if ($signatureBase64)
                            <img src="{{ $signatureBase64 }}" class="signature-img" alt="Signature">
                        @else
                            <div class="signature-fallback">Digitally Prepared</div>
                        @endif

                        <div class="small-muted">Management breeding review</div>
                        <div class="signature-line"></div>
                        <div class="small-muted">Name, signature and date</div>
                    </td>

                    <td class="signature-card stamp-wrap" style="width: 22%;">
                        <div class="signature-title">Official Stamp</div>

                        @if ($stampBase64)
                            <img src="{{ $stampBase64 }}" class="stamp-img" alt="Official Stamp">
                        @else
                            <div class="stamp-circle">
                                <div class="stamp-text">OFFICIAL<br>STAMP</div>
                            </div>
                        @endif

                        <div class="small-muted">{{ $farmName }}</div>
                    </td>

                    <td class="signature-card" style="width: 26%;">
                        <div class="qr-box">
                            <div class="signature-title">Verification</div>

                            @if ($qrImage)
                                <div class="qr-image-wrap">
                                    <img src="{{ $qrImage }}" class="qr-image" alt="QR Code">
                                </div>
                            @else
                                <div class="qr-fallback">
                                    <span class="small-muted">QR not available</span>
                                </div>
                            @endif

                            <div class="small-muted">Scan to verify report metadata</div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </main>

    <script type="text/php">
        if (isset($pdf)) {
            $pdf->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) {
                $font = $fontMetrics->getFont('Courier', 'normal');
                $size = 8.2;
                $text = "Page {$pageNumber} of {$pageCount}";
                $width = $fontMetrics->getTextWidth($text, $font, $size);
                $x = ($canvas->get_width() - $width) / 2;
                $y = $canvas->get_height() - 28;
                $canvas->text($x, $y, $text, $font, $size, [0.42, 0.45, 0.50]);
            });
        }
    </script>
</body>
</html>
