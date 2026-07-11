@php
    use App\Models\Settings\PaymentSetting;

    if (! function_exists('breedingPerformancePdfFirstSetting')) {
        function breedingPerformancePdfFirstSetting(
            array $keys,
            mixed $fallback = null
        ): mixed {
            foreach ($keys as $key) {
                $value = setting($key);

                if (filled($value)) {
                    return $value;
                }
            }

            return $fallback;
        }
    }

    if (! function_exists('breedingPerformancePdfImageBase64')) {
        function breedingPerformancePdfImageBase64(
            ?string $path
        ): ?string {
            if (blank($path)) {
                return null;
            }

            $path = trim((string) $path);

            if (str_starts_with($path, 'data:image/')) {
                return $path;
            }

            if (
                str_starts_with($path, 'http://')
                || str_starts_with($path, 'https://')
            ) {
                return $path;
            }

            $cleanPath = preg_replace(
                '#^/?storage/#',
                '',
                ltrim($path, '/')
            );

            $candidatePaths = [
                storage_path('app/public/' . $cleanPath),
                public_path('storage/' . $cleanPath),
                public_path($cleanPath),
            ];

            foreach ($candidatePaths as $fullPath) {
                if (! is_file($fullPath)) {
                    continue;
                }

                $extension = strtolower(
                    pathinfo($fullPath, PATHINFO_EXTENSION)
                );

                $mime = match ($extension) {
                    'jpg', 'jpeg' => 'image/jpeg',
                    'gif' => 'image/gif',
                    'webp' => 'image/webp',
                    'svg' => 'image/svg+xml',
                    default => 'image/png',
                };

                return 'data:' . $mime . ';base64,'
                    . base64_encode(
                        file_get_contents($fullPath)
                    );
            }

            return null;
        }
    }

    $paymentSettings = PaymentSetting::current();

    $eatNow = now('Africa/Nairobi');

    $farmName = setting(
        'farm.name',
        'Penzi Farm Limited'
    );

    $farmTagline = setting(
        'farm.tagline',
        'Nurturing Quality, Inspiring Global Standards'
    );

    $farmPhone = setting(
        'farm.phone',
        '+254 757 046 726'
    );

    $farmEmail = setting(
        'farm.email',
        'jambo@penzifarm.com'
    );

    $farmAddress = setting(
        'farm.address',
        setting(
            'farm.county',
            'Molo - Nakuru County'
        )
    );

    $primaryColor = trim(
        setting('theme.primary', '#14532d')
    );

    $secondaryColor = trim(
        setting('theme.secondary', '#166534')
    );

    $accentColor = trim(
        setting('theme.accent', '#b7791f')
    );

    $successColor = trim(
        setting('theme.success', '#16a34a')
    );

    $dangerColor = trim(
        setting('theme.danger', '#dc2626')
    );

    /*
     * Use the exact source order already proven to work in the sales
     * invoice PDF. Payment Settings are checked first because the
     * authorized signature and official stamp are stored there.
     */
    $logoBase64 = breedingPerformancePdfImageBase64(
        setting('branding.logo_light')
        ?: setting('branding.logo')
        ?: setting('farm.logo')
        ?: setting('logo')
    );

    $signatureBase64 = breedingPerformancePdfImageBase64(
        data_get($paymentSettings, 'authorized_signature_image')
        ?: data_get($paymentSettings, 'invoice_signature_path')
        ?: data_get($paymentSettings, 'signature_path')
        ?: data_get($paymentSettings, 'authorized_signature_path')
        ?: setting('branding.signature')
        ?: setting('farm.signature')
    );

    $stampBase64 = breedingPerformancePdfImageBase64(
        data_get($paymentSettings, 'payment_stamp_image')
        ?: data_get($paymentSettings, 'invoice_stamp_path')
        ?: data_get($paymentSettings, 'stamp_path')
        ?: data_get($paymentSettings, 'official_stamp_path')
        ?: setting('branding.stamp')
        ?: setting('farm.stamp')
    );

    $recommendation = str(
        data_get(
            $metrics,
            'recommendation',
            'insufficient_data'
        )
    )
        ->replace('_', ' ')
        ->title();

    $roleLabel = data_get($metrics, 'role') === 'dam'
        ? 'Dam / Mother'
        : 'Sire / Father';

    $score = (float) data_get(
        $metrics,
        'score',
        0
    );

    $reportNumber = 'BPR-'
        . $eatNow->format('Ymd-His')
        . '-'
        . ($animal->id ?? '0');

    $verificationText = $farmName
        . ' | Breeding Performance Report'
        . ' | Report: ' . $reportNumber
        . ' | Animal: '
        . ($animal->tag_number ?? $animal->id)
        . ' | Score: '
        . number_format($score, 2)
        . '/100 | Generated: '
        . $eatNow->format('Y-m-d H:i:s')
        . ' EAT';

    $qrPng = null;

    if (
        class_exists(
            \SimpleSoftwareIO\QrCode\Facades\QrCode::class
        )
    ) {
        try {
            $qrPng = base64_encode(
                \SimpleSoftwareIO\QrCode\Facades\QrCode
                    ::format('png')
                    ->size(120)
                    ->margin(1)
                    ->generate($verificationText)
            );
        } catch (\Throwable $exception) {
            $qrPng = null;
        }
    }

    $metricCards = data_get(
        $metrics,
        'role'
    ) === 'dam'
        ? [
            [
                'label' => 'Services',
                'value' => number_format(
                    (int) data_get(
                        $metrics,
                        'services',
                        0
                    )
                ),
            ],
            [
                'label' => 'Conception',
                'value' => number_format(
                    (float) data_get(
                        $metrics,
                        'conception_rate',
                        0
                    ),
                    1
                ) . '%',
            ],
            [
                'label' => 'Deliveries',
                'value' => number_format(
                    (int) data_get(
                        $metrics,
                        'deliveries',
                        0
                    )
                ),
            ],
            [
                'label' => 'Abortions',
                'value' => number_format(
                    (int) data_get(
                        $metrics,
                        'abortions',
                        0
                    )
                ),
            ],
            [
                'label' => 'Live Survival',
                'value' => number_format(
                    (float) data_get(
                        $metrics,
                        'live_birth_survival_rate',
                        0
                    ),
                    1
                ) . '%',
            ],
            [
                'label' => 'Weaning',
                'value' => number_format(
                    (float) data_get(
                        $metrics,
                        'weaning_rate',
                        0
                    ),
                    1
                ) . '%',
            ],
            [
                'label' => 'Mothering',
                'value' => number_format(
                    (float) data_get(
                        $metrics,
                        'mothering_score',
                        0
                    ),
                    2
                ) . '/5',
            ],
            [
                'label' => 'Retained',
                'value' => number_format(
                    (int) data_get(
                        $metrics,
                        'retained_breeding_offspring',
                        0
                    )
                ),
            ],
        ]
        : [
            [
                'label' => 'Direct Offspring',
                'value' => number_format(
                    (int) data_get(
                        $metrics,
                        'direct_offspring',
                        0
                    )
                ),
            ],
            [
                'label' => 'Descendants',
                'value' => number_format(
                    (int) data_get(
                        $metrics,
                        'all_descendants',
                        0
                    )
                ),
            ],
            [
                'label' => 'Survival',
                'value' => number_format(
                    (float) data_get(
                        $metrics,
                        'survival_rate',
                        0
                    ),
                    1
                ) . '%',
            ],
            [
                'label' => 'Breeder Conversion',
                'value' => number_format(
                    (float) data_get(
                        $metrics,
                        'breeder_conversion_rate',
                        0
                    ),
                    1
                ) . '%',
            ],
            [
                'label' => 'Breeder Offspring',
                'value' => number_format(
                    (int) data_get(
                        $metrics,
                        'breeder_offspring',
                        0
                    )
                ),
            ],
            [
                'label' => 'Average Purity',
                'value' => number_format(
                    (float) data_get(
                        $metrics,
                        'average_offspring_purity',
                        0
                    ),
                    1
                ) . '%',
            ],
            [
                'label' => 'Active Offspring',
                'value' => number_format(
                    (int) data_get(
                        $metrics,
                        'active_offspring',
                        0
                    )
                ),
            ],
            [
                'label' => 'Surviving',
                'value' => number_format(
                    (int) data_get(
                        $metrics,
                        'surviving_offspring',
                        0
                    )
                ),
            ],
        ];
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>
        Breeding Performance -
        {{ $animal->tag_number }}
    </title>

    <style>
        @page {
            margin: 108px 28px 88px 28px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #1f2937;
            background: #ffffff;
            font-family: Courier, monospace;
            font-size: 8.7px;
            line-height: 1.38;
        }

        .watermark {
            position: fixed;
            top: 28%;
            left: 12%;
            width: 76%;
            opacity: 0.032;
            z-index: -10;
            text-align: center;
        }

        .watermark img {
            width: 420px;
            max-height: 390px;
            object-fit: contain;
        }

        header {
            position: fixed;
            top: -90px;
            right: 0;
            left: 0;
            height: 74px;
            border-bottom: 3px solid {{ $primaryColor }};
        }

        footer {
            position: fixed;
            right: 0;
            bottom: -68px;
            left: 0;
            height: 55px;
            border-top: 1px solid #d1d5db;
            color: #4b5563;
            font-size: 7.5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
        }

        tr {
            page-break-inside: avoid;
        }

        .offspring-table tr,
        .timeline-table tr {
            page-break-inside: auto;
        }

        .notes-box {
            page-break-inside: auto;
        }

        th,
        td,
        div,
        span {
            overflow-wrap: anywhere;
            word-wrap: break-word;
        }

        .header-table td {
            vertical-align: middle;
        }

        .logo {
            display: block;
            width: 125px;
            max-height: 56px;
            object-fit: contain;
        }

        .company-name {
            color: {{ $primaryColor }};
            font-size: 15px;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
        }

        .tagline {
            margin-top: 3px;
            color: #6b7280;
            font-size: 8px;
            font-style: italic;
            text-align: center;
        }

        .header-right {
            width: 230px;
            font-size: 7.5px;
            line-height: 1.45;
            text-align: right;
        }

        .footer-table {
            margin-top: 7px;
        }

        .footer-left {
            width: 35%;
            text-align: left;
        }

        .footer-center {
            width: 30%;
            text-align: center;
        }

        .footer-right {
            width: 35%;
            text-align: right;
        }

        .hero {
            margin-bottom: 10px;
            padding: 10px 12px;
            border: 1px solid #dbe4d3;
            border-left: 7px solid {{ $primaryColor }};
            background: #fbfdf9;
            page-break-inside: avoid;
        }

        .hero-table td {
            vertical-align: middle;
        }

        .hero-title {
            color: #111827;
            font-size: 19px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .hero-subtitle {
            margin-top: 4px;
            color: {{ $primaryColor }};
            font-weight: bold;
        }

        .hero-report-number {
            margin-top: 4px;
            color: #6b7280;
            font-size: 7.5px;
        }

        .score-box {
            width: 185px;
            text-align: right;
        }

        .score-label {
            color: #6b7280;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .score-value {
            margin-top: 2px;
            color: {{ $primaryColor }};
            font-size: 21px;
            font-weight: bold;
        }

        .badge {
            display: inline-block;
            margin-top: 4px;
            padding: 3px 7px;
            border-radius: 999px;
            color: #ffffff;
            background: {{ $primaryColor }};
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .identity-table {
            margin-bottom: 9px;
            border-spacing: 7px 0;
            border-collapse: separate;
            table-layout: fixed;
        }

        .identity-card {
            width: 25%;
            padding: 8px;
            border: 1px solid #e5e7eb;
            background: #ffffff;
            vertical-align: top;
        }

        .label {
            color: #6b7280;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .value {
            margin-top: 3px;
            color: #111827;
            font-size: 10.5px;
            font-weight: bold;
        }

        .metric-table {
            margin-bottom: 10px;
            border-spacing: 6px 5px;
            border-collapse: separate;
            table-layout: fixed;
        }

        .metric-card {
            width: 25%;
            padding: 7px 5px;
            border: 1px solid #dbe4d3;
            border-top: 4px solid {{ $primaryColor }};
            background: #fbfdf9;
            text-align: center;
            vertical-align: middle;
        }

        .metric-label {
            color: #6b7280;
            font-size: 6.7px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .metric-value {
            margin-top: 3px;
            color: {{ $primaryColor }};
            font-size: 10.5px;
            font-weight: bold;
        }

        .section-title {
            margin: 9px 0 5px;
            padding-bottom: 4px;
            border-bottom: 2px solid {{ $primaryColor }};
            color: {{ $primaryColor }};
            font-size: 10.5px;
            font-weight: bold;
            text-transform: uppercase;
            page-break-after: avoid;
        }

        .reason-box,
        .risk-box,
        .notes-box {
            margin-bottom: 7px;
            padding: 7px 9px;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
            line-height: 1.45;
        }

        .risk-chip {
            display: inline-block;
            margin: 2px 4px 2px 0;
            padding: 3px 5px;
            border: 1px solid #fecaca;
            color: {{ $dangerColor }};
            background: #fef2f2;
            font-size: 7px;
            font-weight: bold;
        }

        /*
         * Important: the complete case is allowed to flow across pages.
         * Using page-break-inside: avoid on a long case can cause DomPDF
         * to clip the bottom of the case instead of continuing it.
         */
        .history-block {
            margin-bottom: 10px;
            border: 1px solid #dbe4d3;
            border-left: 5px solid {{ $primaryColor }};
            page-break-inside: auto;
        }

        .history-head {
            padding: 7px 9px;
            color: #111827;
            background: #f3f7f2;
            font-weight: bold;
            page-break-after: avoid;
        }

        .history-body {
            padding: 7px 8px 8px;
        }

        .case-table,
        .quality-table,
        .timeline-table,
        .offspring-table {
            width: 100%;
            margin-bottom: 7px;
            table-layout: fixed;
        }

        .case-table th,
        .quality-table th,
        .timeline-table th,
        .offspring-table th {
            padding: 4px;
            border: 1px solid {{ $primaryColor }};
            color: #ffffff;
            background: {{ $primaryColor }};
            font-size: 6.7px;
            text-align: left;
            text-transform: uppercase;
        }

        .case-table td,
        .quality-table td,
        .timeline-table td,
        .offspring-table td {
            padding: 4px;
            border: 1px solid #e5e7eb;
            background: #ffffff;
            vertical-align: top;
        }

        .case-table .field-name {
            color: #6b7280;
            background: #f8fafc;
            font-size: 6.7px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .case-table .field-value {
            color: #111827;
            font-weight: bold;
        }

        .timeline-date {
            width: 14%;
            white-space: nowrap;
        }

        .timeline-stage {
            width: 23%;
            color: {{ $primaryColor }};
            font-weight: bold;
        }

        .timeline-detail {
            width: 63%;
        }

        .offspring-index {
            width: 4%;
            text-align: center;
        }

        .offspring-tag {
            width: 16%;
            font-weight: bold;
        }

        .offspring-sex {
            width: 8%;
        }

        .offspring-breed {
            width: 14%;
        }

        .offspring-status {
            width: 10%;
        }

        .offspring-location {
            width: 16%;
        }

        .offspring-notes {
            width: 20%;
        }

        .case-subtitle {
            margin: 7px 0 4px;
            color: {{ $secondaryColor }};
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            page-break-after: avoid;
        }

        .status-ok {
            color: {{ $successColor }};
            font-weight: bold;
        }

        .status-risk {
            color: {{ $dangerColor }};
            font-weight: bold;
        }

        .status-warning {
            color: {{ $accentColor }};
            font-weight: bold;
        }

        .approval-table {
            width: 100%;
            margin-top: 18px;
            border-spacing: 8px 0;
            border-collapse: separate;
            table-layout: fixed;
            page-break-inside: avoid;
        }

        .approval-card {
            min-height: 112px;
            padding: 10px;
            border: 1px solid #dbe4d3;
            background: #fbfdf9;
            vertical-align: top;
        }

        .approval-card.generated {
            text-align: left;
        }

        .approval-title {
            margin-bottom: 7px;
            color: {{ $secondaryColor }};
            font-size: 9.5px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .approval-name {
            color: #111827;
            font-size: 12px;
            font-weight: bold;
        }

        .approval-line {
            margin-top: 14px;
            border-top: 1px solid #4b5563;
            padding-top: 5px;
        }

        .signature-img {
            display: block;
            max-width: 145px;
            max-height: 54px;
            margin: 3px auto 4px;
            object-fit: contain;
        }

        .signature-fallback {
            margin: 7px 0;
            color: {{ $successColor }};
            font-size: 15px;
            font-weight: bold;
            font-style: italic;
            text-align: center;
        }

        .stamp-wrap {
            text-align: center;
        }

        .stamp-img {
            display: block;
            max-width: 112px;
            max-height: 92px;
            margin: 0 auto 5px;
            object-fit: contain;
        }

        .stamp-circle {
            display: table;
            width: 96px;
            height: 96px;
            margin: 0 auto 6px;
            border: 2px dashed {{ $primaryColor }};
            border-radius: 50%;
        }

        .stamp-text {
            display: table-cell;
            color: {{ $primaryColor }};
            font-size: 10px;
            font-weight: bold;
            line-height: 1.35;
            text-align: center;
            vertical-align: middle;
        }

        .qr-box {
            text-align: center;
        }

        .qr-image-wrap,
        .qr-fallback {
            width: 96px;
            height: 96px;
            margin: 0 auto 6px;
            border: 2px solid {{ $primaryColor }};
            background: #ffffff;
            padding: 4px;
        }

        .qr-img {
            width: 86px;
            height: 86px;
        }

        .small {
            color: #6b7280;
            font-size: 7px;
        }
    </style>
</head>
<body>
    @if ($logoBase64)
        <div class="watermark">
            <img
                src="{{ $logoBase64 }}"
                alt="Watermark"
            >
        </div>
    @endif

    <header>
        <table class="header-table">
            <tr>
                <td width="135">
                    @if ($logoBase64)
                        <img
                            src="{{ $logoBase64 }}"
                            class="logo"
                            alt="Logo"
                        >
                    @endif
                </td>

                <td>
                    <div class="company-name">
                        {{ $farmName }}
                    </div>

                    <div class="tagline">
                        {{ $farmTagline }}
                    </div>
                </td>

                <td class="header-right">
                    <strong>Phone:</strong>
                    {{ $farmPhone }}<br>

                    <strong>Email:</strong>
                    {{ $farmEmail }}<br>

                    <strong>Location:</strong>
                    {{ $farmAddress }}
                </td>
            </tr>
        </table>
    </header>

    <footer>
        <table class="footer-table">
            <tr>
                <td class="footer-left">
                    {{ $reportNumber }}<br>
                    Printed
                    {{ $eatNow->format('d M Y, H:i') }}
                    EAT
                </td>

                <td class="footer-center">
                    Breeding Performance<br>
                    {{ $animal->tag_number }}
                </td>

                <td class="footer-right">
                    Generated by
                    {{ $generatedBy?->name ?? 'System' }}<br>
                    {{ $generatedByRole }}
                </td>
            </tr>
        </table>
    </footer>

    <main>
        <section class="hero">
            <table class="hero-table">
                <tr>
                    <td>
                        <div class="hero-title">
                            Breeding Performance History
                        </div>

                        <div class="hero-subtitle">
                            {{ $animal->tag_number }}
                            · {{ $roleLabel }}
                            ·
                            {{ $animal->breed?->breed_name
                                ?? 'Unknown breed' }}
                        </div>

                        <div class="hero-report-number">
                            Report {{ $reportNumber }}
                        </div>
                    </td>

                    <td class="score-box">
                        <div class="score-label">
                            Performance Score
                        </div>

                        <div class="score-value">
                            {{ number_format($score, 2) }}/100
                        </div>

                        <span class="badge">
                            {{ $recommendation }}
                        </span>
                    </td>
                </tr>
            </table>
        </section>

        <table class="identity-table">
            <tr>
                <td class="identity-card">
                    <div class="label">Animal</div>
                    <div class="value">
                        {{ $animal->tag_number }}
                    </div>
                    {{ $animal->sex }}
                    · {{ $animal->species ?? '-' }}
                </td>

                <td class="identity-card">
                    <div class="label">Breed & Purity</div>
                    <div class="value">
                        {{ $animal->breed?->breed_name ?? '-' }}
                    </div>
                    @if (
                        $animal->breed_purity_percent
                        !== null
                    )
                        {{ number_format(
                            (float) $animal
                                ->breed_purity_percent,
                            2
                        ) }}%
                    @else
                        Purity not recorded
                    @endif
                </td>

                <td class="identity-card">
                    <div class="label">Parents</div>
                    <div class="value">
                        Sire
                        {{ $animal->sire?->tag_number ?? '-' }}
                    </div>
                    Dam
                    {{ $animal->dam?->tag_number ?? '-' }}
                </td>

                <td class="identity-card">
                    <div class="label">Current Status</div>
                    <div class="value">
                        {{ $animal->status }}
                    </div>
                    {{ $animal->location?->name
                        ?? 'Location not assigned' }}
                </td>
            </tr>
        </table>

        <table class="metric-table">
            @foreach (
                collect($metricCards)->chunk(4)
                as $metricRow
            )
                <tr>
                    @foreach ($metricRow as $metric)
                        <td class="metric-card">
                            <div class="metric-label">
                                {{ $metric['label'] }}
                            </div>

                            <div class="metric-value">
                                {{ $metric['value'] }}
                            </div>
                        </td>
                    @endforeach

                    @for (
                        $empty = $metricRow->count();
                        $empty < 4;
                        $empty++
                    )
                        <td class="metric-card">&nbsp;</td>
                    @endfor
                </tr>
            @endforeach
        </table>

        <div class="section-title">
            Decision Support
        </div>

        <div class="reason-box">
            <strong>Recommendation:</strong>
            {{ $recommendation }}<br>

            {{ data_get(
                $metrics,
                'reason',
                'No analytical reason is available.'
            ) }}
        </div>

        <div class="risk-box">
            <strong>Risk flags:</strong><br>

            @forelse ($riskFlags as $flag)
                <span class="risk-chip">
                    {{ $flag }}
                </span>
            @empty
                No analytical risk flag.
            @endforelse
        </div>

        <div class="section-title">
            Complete Batch-by-Batch Breeding History
        </div>

        @forelse (
            data_get($history, 'records', collect())
            as $record
        )
            @php
                $pregnancyLabel = str(
                    $record['pregnancy_status']
                    ?? 'pending'
                )
                    ->replace('_', ' ')
                    ->title();

                $birthOutcomeLabel = str(
                    $record['birth_outcome']
                    ?? 'pending'
                )
                    ->replace('_', ' ')
                    ->title();

                $breedingTypeLabel = str(
                    $record['breeding_type']
                    ?? 'natural'
                )
                    ->replace('_', ' ')
                    ->title();

                $birthAssistanceLabel = filled(
                    $record['birth_assistance'] ?? null
                )
                    ? str($record['birth_assistance'])
                        ->replace('_', ' ')
                        ->title()
                    : 'Not recorded';

                $inbreedingLabel = str(
                    $record['inbreeding_status']
                    ?? 'clear'
                )
                    ->replace('_', ' ')
                    ->title();
            @endphp

            <section class="history-block">
                <div class="history-head">
                    {{ $record['batch_number'] }}
                    · {{ $record['batch_name'] }}

                    @if ($record['archived'])
                        · ARCHIVED
                    @endif
                </div>

                <div class="history-body">
                    <table class="case-table">
                        <tbody>
                            <tr>
                                <td class="field-name">
                                    Mating Date
                                </td>
                                <td class="field-value">
                                    {{ $record['mating_date']
                                        ?? 'Not recorded' }}
                                </td>

                                <td class="field-name">
                                    Breeding Pair
                                </td>
                                <td class="field-value">
                                    Sire {{ $record['sire'] }}
                                    × Dam {{ $record['dam'] }}
                                </td>

                                <td class="field-name">
                                    Type
                                </td>
                                <td class="field-value">
                                    {{ $breedingTypeLabel }}
                                    @if (
                                        $record['is_cross_breed']
                                        ?? false
                                    )
                                        · Cross breeding
                                    @endif
                                </td>
                            </tr>

                            <tr>
                                <td class="field-name">
                                    Pregnancy
                                </td>
                                <td class="field-value">
                                    {{ $pregnancyLabel }}
                                </td>

                                <td class="field-name">
                                    Checked
                                </td>
                                <td class="field-value">
                                    {{ $record[
                                        'pregnancy_checked_at'
                                    ] ?? 'Not recorded' }}
                                </td>

                                <td class="field-name">
                                    Expected Due
                                </td>
                                <td class="field-value">
                                    {{ $record[
                                        'expected_due_date'
                                    ] ?? 'Not recorded' }}
                                </td>
                            </tr>

                            <tr>
                                <td class="field-name">
                                    Delivery
                                </td>
                                <td class="field-value">
                                    {{ $record['delivery_date']
                                        ?? 'Not recorded' }}
                                </td>

                                <td class="field-name">
                                    Birth Outcome
                                </td>
                                <td class="field-value">
                                    {{ $birthOutcomeLabel }}
                                </td>

                                <td class="field-name">
                                    Assistance
                                </td>
                                <td class="field-value">
                                    {{ $birthAssistanceLabel }}
                                </td>
                            </tr>

                            <tr>
                                <td class="field-name">
                                    Gestation
                                </td>
                                <td class="field-value">
                                    {{ number_format(
                                        (int) (
                                            $record[
                                                'gestation_days'
                                            ] ?? 0
                                        )
                                    ) }}
                                    days
                                </td>

                                <td class="field-name">
                                    Inbreeding
                                </td>
                                <td class="field-value">
                                    {{ $inbreedingLabel }}
                                </td>

                                <td class="field-name">
                                    Batch Status
                                </td>
                                <td class="field-value">
                                    {{ str(
                                        $record['batch_status']
                                        ?? 'unknown'
                                    )
                                        ->replace('_', ' ')
                                        ->title() }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <table class="quality-table">
                        <thead>
                            <tr>
                                <th>Total</th>
                                <th>Live</th>
                                <th>Stillborn</th>
                                <th>Neonatal Deaths</th>
                                <th>Weaned</th>
                                <th>Retained</th>
                                <th>Mothering</th>
                                <th>Milk</th>
                                <th>Temperament</th>
                                <th>Offspring Vigour</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td>
                                    {{ number_format(
                                        (int) (
                                            $record[
                                                'offspring_count'
                                            ] ?? 0
                                        )
                                    ) }}
                                </td>
                                <td>
                                    {{ number_format(
                                        $record[
                                            'live_birth_count'
                                        ]
                                    ) }}
                                </td>
                                <td>
                                    {{ number_format(
                                        $record[
                                            'stillborn_count'
                                        ]
                                    ) }}
                                </td>
                                <td>
                                    {{ number_format(
                                        $record[
                                            'neonatal_death_count'
                                        ]
                                    ) }}
                                </td>
                                <td>
                                    {{ number_format(
                                        $record['weaned_count']
                                    ) }}
                                </td>
                                <td>
                                    {{ number_format(
                                        $record[
                                            'retained_breeding_count'
                                        ]
                                    ) }}
                                </td>
                                <td>
                                    {{ $record['mothering_score']
                                        !== null
                                            ? number_format(
                                                $record[
                                                    'mothering_score'
                                                ],
                                                2
                                            ) . '/5'
                                            : '-' }}
                                </td>
                                <td>
                                    {{ $record['milk_score']
                                        !== null
                                            ? number_format(
                                                $record[
                                                    'milk_score'
                                                ],
                                                2
                                            ) . '/5'
                                            : '-' }}
                                </td>
                                <td>
                                    {{ $record[
                                        'temperament_score'
                                    ] !== null
                                        ? number_format(
                                            $record[
                                                'temperament_score'
                                            ],
                                            2
                                        ) . '/5'
                                        : '-' }}
                                </td>
                                <td>
                                    {{ $record[
                                        'offspring_vigour_score'
                                    ] !== null
                                        ? number_format(
                                            $record[
                                                'offspring_vigour_score'
                                            ],
                                            2
                                        ) . '/5'
                                        : '-' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="case-subtitle">
                        Case Timeline
                    </div>

                    <table class="timeline-table">
                        <thead>
                            <tr>
                                <th class="timeline-date">
                                    Date
                                </th>
                                <th class="timeline-stage">
                                    Stage
                                </th>
                                <th class="timeline-detail">
                                    Complete Detail
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse (
                                $record['events']
                                as $event
                            )
                                <tr>
                                    <td class="timeline-date">
                                        {{ $event['date'] }}
                                    </td>
                                    <td class="timeline-stage">
                                        {{ $event['title'] }}
                                    </td>
                                    <td class="timeline-detail">
                                        {{ $event['detail'] }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3">
                                        No dated case events are
                                        available.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="case-subtitle">
                        Registered Offspring
                    </div>

                    <table class="offspring-table">
                        <thead>
                            <tr>
                                <th class="offspring-index">#</th>
                                <th class="offspring-tag">Tag</th>
                                <th class="offspring-sex">Sex</th>
                                <th class="offspring-breed">Breed</th>
                                <th>DOB</th>
                                <th class="offspring-status">Status</th>
                                <th>Purity</th>
                                <th>Breeder</th>
                                <th class="offspring-location">
                                    Location
                                </th>
                                <th class="offspring-notes">
                                    Survival / Disposition
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse (
                                $record['offspring']
                                as $offspring
                            )
                                <tr>
                                    <td class="offspring-index">
                                        {{ $loop->iteration }}
                                    </td>

                                    <td class="offspring-tag">
                                        {{ $offspring[
                                            'tag_number'
                                        ] }}
                                    </td>

                                    <td>
                                        {{ $offspring['sex'] }}
                                    </td>

                                    <td>
                                        {{ $offspring['breed']
                                            ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $offspring[
                                            'date_of_birth'
                                        ] ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $offspring['status'] }}
                                    </td>

                                    <td>
                                        {{ $offspring['purity']
                                            !== null
                                                ? number_format(
                                                    $offspring[
                                                        'purity'
                                                    ],
                                                    2
                                                ) . '%'
                                                : '-' }}
                                    </td>

                                    <td>
                                        {{ $offspring[
                                            'is_breeder'
                                        ]
                                            ? 'Yes'
                                            : 'No' }}
                                    </td>

                                    <td>
                                        {{ $offspring['location']
                                            ?? '-' }}
                                    </td>

                                    <td>
                                        @if (
                                            $offspring[
                                                'date_died'
                                            ]
                                        )
                                            Died
                                            {{ $offspring[
                                                'date_died'
                                            ] }}
                                            @if (
                                                filled(
                                                    $offspring[
                                                        'cause_of_death'
                                                    ]
                                                )
                                            )
                                                ·
                                                {{ $offspring[
                                                    'cause_of_death'
                                                ] }}
                                            @endif
                                        @elseif (
                                            $offspring[
                                                'date_culled'
                                            ]
                                        )
                                            Culled
                                            {{ $offspring[
                                                'date_culled'
                                            ] }}
                                            @if (
                                                filled(
                                                    $offspring[
                                                        'culling_reason'
                                                    ]
                                                )
                                            )
                                                ·
                                                {{ $offspring[
                                                    'culling_reason'
                                                ] }}
                                            @endif
                                        @else
                                            {{ $offspring[
                                                'surviving'
                                            ]
                                                ? 'Surviving'
                                                : 'Not surviving' }}
                                            ·
                                            {{ $offspring[
                                                'purpose'
                                            ] ?? 'Purpose not recorded' }}
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10">
                                        No registered offspring are
                                        linked to this breeding case.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    @if (
                        count(
                            $record['risk_flags']
                            ?? []
                        ) > 0
                    )
                        <div class="notes-box">
                            <strong>Case risk flags:</strong>

                            @foreach (
                                $record['risk_flags']
                                as $flag
                            )
                                <span class="risk-chip">
                                    {{ $flag }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                    @if (
                        filled(
                            $record[
                                'relationship_notes'
                            ] ?? null
                        )
                        || filled(
                            $record['delivery_notes']
                            ?? null
                        )
                        || filled(
                            $record['maternal_notes']
                            ?? null
                        )
                        || filled(
                            $record['notes']
                            ?? null
                        )
                        || filled(
                            $record['batch_notes']
                            ?? null
                        )
                    )
                        <div class="notes-box">
                            <strong>
                                Complete Case Notes
                            </strong><br>

                            @if (
                                filled(
                                    $record[
                                        'relationship_notes'
                                    ] ?? null
                                )
                            )
                                <strong>
                                    Relationship:
                                </strong>
                                {{ $record[
                                    'relationship_notes'
                                ] }}<br>
                            @endif

                            @if (
                                filled(
                                    $record['delivery_notes']
                                    ?? null
                                )
                            )
                                <strong>Delivery:</strong>
                                {{ $record[
                                    'delivery_notes'
                                ] }}<br>
                            @endif

                            @if (
                                filled(
                                    $record['maternal_notes']
                                    ?? null
                                )
                            )
                                <strong>Maternal:</strong>
                                {{ $record[
                                    'maternal_notes'
                                ] }}<br>
                            @endif

                            @if (
                                filled(
                                    $record['notes']
                                    ?? null
                                )
                            )
                                <strong>Record:</strong>
                                {{ $record['notes'] }}<br>
                            @endif

                            @if (
                                filled(
                                    $record['batch_notes']
                                    ?? null
                                )
                            )
                                <strong>Batch:</strong>
                                {{ $record[
                                    'batch_notes'
                                ] }}
                            @endif
                        </div>
                    @endif

                    <div class="small">
                        Record created:
                        {{ $record['created_at']
                            ?? 'Not available' }}
                        · Last updated:
                        {{ $record['updated_at']
                            ?? 'Not available' }}

                        @if (
                            filled(
                                $record[
                                    'evaluation_completed_at'
                                ] ?? null
                            )
                        )
                            · Evaluation completed:
                            {{ $record[
                                'evaluation_completed_at'
                            ] }}
                        @endif
                    </div>
                </div>
            </section>
        @empty
            <div class="reason-box">
                No breeding batch history is available.
            </div>
        @endforelse

        <table class="approval-table">
            <tr>
                <td
                    class="approval-card generated"
                    style="width: 26%;"
                >
                    <div class="approval-title">
                        Prepared By
                    </div>

                    <div class="approval-name">
                        {{ $generatedBy?->name ?? 'System' }}
                    </div>

                    <div class="small">
                        {{ $generatedByRole }}
                    </div>

                    <div class="approval-line"></div>

                    <div class="small">
                        Generated
                        {{ $eatNow->format('d M Y, H:i') }}
                        EAT
                    </div>

                    <div class="small">
                        {{ $reportNumber }}
                    </div>
                </td>

                <td
                    class="approval-card"
                    style="width: 28%;"
                >
                    <div class="approval-title">
                        Authorized Signature
                    </div>

                    @if ($signatureBase64)
                        <img
                            src="{{ $signatureBase64 }}"
                            class="signature-img"
                            alt="Authorized Signature"
                        >
                    @else
                        <div class="signature-fallback">
                            Digitally Approved
                        </div>
                    @endif

                    <div class="small">
                        Approved
                        {{ $eatNow->format('d M Y, H:i') }}
                        EAT
                    </div>

                    <div class="approval-line"></div>

                    <div class="small">
                        {{ $farmName }} Management Approval
                    </div>
                </td>

                <td
                    class="approval-card stamp-wrap"
                    style="width: 22%;"
                >
                    <div class="approval-title">
                        Official Stamp
                    </div>

                    @if ($stampBase64)
                        <img
                            src="{{ $stampBase64 }}"
                            class="stamp-img"
                            alt="Official Stamp"
                        >
                    @else
                        <div class="stamp-circle">
                            <div class="stamp-text">
                                OFFICIAL<br>
                                STAMP
                            </div>
                        </div>
                    @endif

                    <div class="small">
                        {{ $farmName }}
                    </div>
                </td>

                <td
                    class="approval-card"
                    style="width: 24%;"
                >
                    <div class="qr-box">
                        <div class="approval-title">
                            Verification
                        </div>

                        @if ($qrPng)
                            <div class="qr-image-wrap">
                                <img
                                    src="data:image/png;base64,{{ $qrPng }}"
                                    class="qr-img"
                                    alt="QR Code"
                                >
                            </div>
                        @else
                            <div class="qr-fallback">
                                <span class="small">
                                    QR not available
                                </span>
                            </div>
                        @endif

                        <div class="small">
                            Scan to verify report metadata
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </main>

    <script type="text/php">
        if (isset($pdf)) {
            $pdf->page_script(
                function (
                    $pageNumber,
                    $pageCount,
                    $canvas,
                    $fontMetrics
                ) {
                    $font = $fontMetrics->getFont(
                        'Courier',
                        'normal'
                    );

                    $size = 8;
                    $text = "Page {$pageNumber} of {$pageCount}";
                    $width = $fontMetrics->getTextWidth(
                        $text,
                        $font,
                        $size
                    );

                    $canvas->text(
                        ($canvas->get_width() - $width) / 2,
                        $canvas->get_height() - 22,
                        $text,
                        $font,
                        $size,
                        [0.42, 0.45, 0.50]
                    );
                }
            );
        }
    </script>
</body>
</html>
