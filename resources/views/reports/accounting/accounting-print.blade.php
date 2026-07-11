@php
    use App\Models\Settings\PaymentSetting;

    if (! function_exists(
        'accountingPdfImageBase64'
    )) {
        function accountingPdfImageBase64(
            mixed $path
        ): ?string {
            if (is_array($path)) {
                $path = collect($path)
                    ->flatten()
                    ->first(
                        fn ($value): bool =>
                            is_string($value)
                            && trim($value) !== ''
                    );
            }

            if (! is_string($path)) {
                return null;
            }

            $cleanPath = trim($path);

            if ($cleanPath === '') {
                return null;
            }

            if (
                str_starts_with(
                    $cleanPath,
                    'data:image/'
                )
            ) {
                return $cleanPath;
            }

            $urlPath = parse_url(
                $cleanPath,
                PHP_URL_PATH
            );

            if (
                is_string($urlPath)
                && $urlPath !== ''
            ) {
                $cleanPath = $urlPath;
            }

            $cleanPath = ltrim(
                $cleanPath,
                '/'
            );

            $cleanPath = preg_replace(
                '#^storage/#',
                '',
                $cleanPath
            );

            $possiblePaths = array_filter([
                storage_path(
                    'app/public/'
                    . $cleanPath
                ),
                public_path(
                    'storage/'
                    . $cleanPath
                ),
                public_path($cleanPath),
                is_file($path)
                    ? $path
                    : null,
            ]);

            foreach (
                $possiblePaths
                as $fullPath
            ) {
                if (
                    ! is_file($fullPath)
                    || ! is_readable($fullPath)
                ) {
                    continue;
                }

                $extension = strtolower(
                    pathinfo(
                        $fullPath,
                        PATHINFO_EXTENSION
                    )
                );

                $mime = match ($extension) {
                    'jpg', 'jpeg' =>
                        'image/jpeg',

                    'png' =>
                        'image/png',

                    'gif' =>
                        'image/gif',

                    'webp' =>
                        'image/webp',

                    'svg' =>
                        'image/svg+xml',

                    default =>
                        mime_content_type(
                            $fullPath
                        ) ?: 'image/png',
                };

                return 'data:'
                    . $mime
                    . ';base64,'
                    . base64_encode(
                        file_get_contents(
                            $fullPath
                        )
                    );
            }

            return null;
        }
    }

    $paymentSettings =
        PaymentSetting::current();

    $eatNow =
        $generatedAt
        ?? now('Africa/Nairobi');

    $farmName = setting(
        'farm.name',
        setting(
            'company.name',
            config(
                'app.name',
                'Farm Management System'
            )
        )
    );

    $farmTagline = setting(
        'farm.tagline',
        'Nurturing Quality, Inspiring Global Standards'
    );

    $farmPhone = setting(
        'farm.phone',
        ''
    );

    $farmEmail = setting(
        'farm.email',
        ''
    );

    $farmCounty = setting(
        'farm.county',
        'Kenya'
    );

    $farmAddress = setting(
        'farm.address',
        $farmCounty
    );

    $kraPin = setting(
        'farm.kra_pin',
        setting(
            'company.kra_pin',
            ''
        )
    );

    $primaryColor = trim(
        setting(
            'theme.primary',
            '#014a12'
        )
    );

    $secondaryColor = trim(
        setting(
            'theme.secondary',
            '#14532d'
        )
    );

    $accentColor = trim(
        setting(
            'theme.accent',
            '#f59e0b'
        )
    );

    $dangerColor = trim(
        setting(
            'theme.danger',
            '#dc2626'
        )
    );

    $successColor = trim(
        setting(
            'theme.success',
            '#16a34a'
        )
    );

    $logoBase64 =
        accountingPdfImageBase64(
            setting(
                'branding.logo_light'
            )
            ?: setting(
                'branding.logo'
            )
            ?: setting(
                'farm.logo'
            )
            ?: data_get(
                $paymentSettings,
                'company_logo'
            )
        );

    $signatureBase64 =
        accountingPdfImageBase64(
            data_get(
                $paymentSettings,
                'authorized_signature_image'
            )
            ?: data_get(
                $paymentSettings,
                'invoice_signature_path'
            )
            ?: data_get(
                $paymentSettings,
                'signature_path'
            )
            ?: data_get(
                $paymentSettings,
                'authorized_signature_path'
            )
            ?: setting(
                'branding.signature'
            )
        );

    $stampBase64 =
        accountingPdfImageBase64(
            data_get(
                $paymentSettings,
                'payment_stamp_image'
            )
            ?: data_get(
                $paymentSettings,
                'invoice_stamp_path'
            )
            ?: data_get(
                $paymentSettings,
                'stamp_path'
            )
            ?: data_get(
                $paymentSettings,
                'official_stamp_path'
            )
            ?: setting(
                'branding.stamp'
            )
        );

    $authorizedName = setting(
        'farm.authorized_signatory_name',
        'Authorised Signatory'
    );

    $authorizedTitle = setting(
        'farm.authorized_signatory_title',
        'Finance / Management'
    );

    $generatedByName =
        $generatedBy?->name
        ?? 'System';

    $generatedByRole =
        $generatedByRole
        ?? 'User';

    $statusText = strtolower(
        (string) (
            $reportStatus
            ?? ''
        )
    );

    $statusBadgeClass =
        ($reportStatusTone ?? null)
        === 'danger'
            ? 'badge-danger'
            : (
                ($reportStatusTone ?? null)
                === 'warning'
                    ? 'badge-warning'
                    : 'badge-success'
            );

    $qrDataUri = null;

    if (
        class_exists(
            \SimpleSoftwareIO\QrCode\Facades\QrCode::class
        )
    ) {
        try {
            $qrPng =
                \SimpleSoftwareIO\QrCode\Facades\QrCode::format(
                    'png'
                )
                    ->size(120)
                    ->margin(1)
                    ->generate(
                        $verificationText
                    );

            $qrDataUri =
                'data:image/png;base64,'
                . base64_encode($qrPng);
        } catch (\Throwable) {
            try {
                $qrSvg =
                    \SimpleSoftwareIO\QrCode\Facades\QrCode::format(
                        'svg'
                    )
                        ->size(120)
                        ->margin(1)
                        ->generate(
                            $verificationText
                        );

                $qrDataUri =
                    'data:image/svg+xml;base64,'
                    . base64_encode($qrSvg);
            } catch (\Throwable) {
                $qrDataUri = null;
            }
        }
    }

    $summaryPerRow =
        $paperOrientation === 'portrait'
            ? 2
            : 4;

    $summaryRows = collect(
        $summary ?? []
    )->chunk($summaryPerRow);
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>
        {{ $reportTitle }}
    </title>

    <style>
        @page {
            margin: 118px 34px 92px 34px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Courier, monospace;
            font-size: 10.5px;
            line-height: 1.34;
            color: #1f2937;
            background: #ffffff;
        }

        .watermark {
            position: fixed;
            top: 30%;
            left: 12%;
            width: 76%;
            opacity: 0.025;
            z-index: -10;
            text-align: center;
        }

        .watermark img {
            width: 390px;
            max-height: 270px;
            object-fit: contain;
        }

        header {
            position: fixed;
            top: -96px;
            left: 0;
            right: 0;
            height: 88px;
            border-bottom: 3px solid
                {{ $primaryColor }};
        }

        footer {
            position: fixed;
            bottom: -70px;
            left: 0;
            right: 0;
            height: 58px;
            border-top: 1px solid #d1d5db;
            color: #4b5563;
            font-size: 9px;
        }

        .header-table,
        .footer-table,
        .report-title-table,
        .report-info-grid,
        .kpi-table,
        .notes-table,
        .report-table,
        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: middle;
        }

        .logo-cell {
            width: 150px;
        }

        .logo {
            width: 145px;
            max-height: 72px;
            object-fit: contain;
        }

        .logo-fallback {
            width: 62px;
            height: 62px;
            border: 2px solid
                {{ $primaryColor }};
            border-radius: 50%;
            color: {{ $primaryColor }};
            font-size: 18px;
            font-weight: bold;
            line-height: 62px;
            text-align: center;
        }

        .company-title {
            text-align: center;
            font-size: 15px;
            font-weight: bold;
            color: {{ $primaryColor }};
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .header-report-name {
            margin-top: 2px;
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            color: {{ $secondaryColor }};
            text-transform: uppercase;
        }

        .tagline {
            margin-top: 3px;
            text-align: center;
            font-size: 9.5px;
            color: #4b5563;
            font-style: italic;
        }

        .header-right {
            width: 230px;
            text-align: right;
            font-size: 9px;
            line-height: 1.55;
            white-space: normal;
            overflow-wrap: break-word;
            word-wrap: break-word;
        }

        .footer-table {
            margin-top: 7px;
            table-layout: fixed;
        }

        .footer-table td {
            vertical-align: top;
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

        .footer-details {
            padding-top: 4px;
            color: #6b7280;
            font-size: 8.2px;
            text-align: center;
            white-space: normal;
            overflow-wrap: break-word;
            word-wrap: break-word;
        }

        .small-muted {
            color: #6b7280;
            font-size: 8.5px;
        }

        .print-toolbar {
            margin-bottom: 10px;
            text-align: right;
        }

        .print-toolbar button {
            border: 0;
            border-radius: 4px;
            padding: 7px 12px;
            background:
                {{ $primaryColor }};
            color: #ffffff;
            font-family: Courier, monospace;
            font-weight: bold;
        }

        .report-top {
            margin-bottom: 12px;
            border: 1px solid #dbe4d3;
            border-left: 7px solid
                {{ $primaryColor }};
            background: #fbfdf9;
            padding: 12px 14px;
            page-break-inside: avoid;
        }

        .report-title-table td {
            vertical-align: middle;
        }

        .report-title {
            margin: 0;
            color: #111827;
            font-size: 22px;
            line-height: 1.08;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .7px;
        }

        .report-subtitle {
            margin-top: 5px;
            max-width: 96%;
            color: {{ $primaryColor }};
            font-size: 9.5px;
            font-weight: bold;
            line-height: 1.4;
            white-space: normal;
            overflow-wrap: break-word;
            word-wrap: break-word;
        }

        .report-status-box {
            width: 220px;
            text-align: right;
        }

        .report-status-label {
            color: #6b7280;
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge {
            display: inline-block;
            margin-top: 4px;
            padding: 4px 8px;
            border-radius: 999px;
            color: #ffffff;
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-success {
            background:
                {{ $successColor }};
        }

        .badge-warning {
            background:
                {{ $accentColor }};
            color: #111827;
        }

        .badge-danger {
            background:
                {{ $dangerColor }};
        }

        .report-reference {
            margin-top: 5px;
            color: #6b7280;
            font-size: 8px;
            overflow-wrap: anywhere;
            word-wrap: break-word;
        }

        .report-info-grid {
            margin-bottom: 12px;
            table-layout: fixed;
            page-break-inside: avoid;
        }

        .report-info-grid td {
            width: 20%;
            border: 1px solid #dbe4d3;
            padding: 8px;
            vertical-align: top;
            white-space: normal;
            overflow-wrap: break-word;
            word-wrap: break-word;
        }

        .info-label {
            color: #6b7280;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .info-value {
            display: block;
            margin-top: 3px;
            color: #111827;
            font-size: 9.2px;
            font-weight: bold;
        }

        .kpi-table {
            margin: 0 0 10px;
            border-spacing: 8px 0;
            border-collapse: separate;
            table-layout: fixed;
            page-break-inside: avoid;
        }

        .kpi-card {
            border: 1px solid #dbe4d3;
            border-top: 4px solid
                {{ $primaryColor }};
            background: #fbfdf9;
            padding: 9px 10px;
            vertical-align: top;
        }

        .kpi-success {
            border-top-color:
                {{ $successColor }};
        }

        .kpi-warning {
            border-top-color:
                {{ $accentColor }};
        }

        .kpi-danger {
            border-top-color:
                {{ $dangerColor }};
        }

        .kpi-label {
            color: #6b7280;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .kpi-value {
            margin-top: 4px;
            color: {{ $primaryColor }};
            font-size: 14px;
            line-height: 1.15;
            font-weight: bold;
            white-space: normal;
            overflow-wrap: break-word;
            word-wrap: break-word;
        }

        .notes-table {
            margin: 10px 0 12px;
            border-spacing: 8px 0;
            border-collapse: separate;
            page-break-inside: avoid;
        }

        .note-card {
            width: 50%;
            border: 1px solid #e5e7eb;
            background: #ffffff;
            padding: 9px 10px;
            vertical-align: top;
            font-size: 8.8px;
            line-height: 1.45;
            white-space: normal;
            overflow-wrap: break-word;
            word-wrap: break-word;
        }

        .note-card:first-child {
            border-left: 5px solid
                {{ $primaryColor }};
        }

        .note-card:last-child {
            border-left: 5px solid
                {{ $accentColor }};
        }

        .note-title {
            margin-bottom: 5px;
            color: {{ $secondaryColor }};
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .report-table {
            margin-top: 10px;
            border-collapse: collapse;
            table-layout: fixed;
            font-size:
                {{ $tableFontSize ?? '8px' }};
            line-height: 1.28;
        }

        .report-table thead {
            display: table-header-group;
        }

        .report-table tfoot {
            display: table-row-group;
        }

        .report-table tr {
            page-break-inside: avoid;
        }

        .report-table th {
            border: 1px solid
                {{ $primaryColor }};
            background:
                {{ $primaryColor }};
            color: #ffffff;
            padding: 6px;
            font-size:
                {{ $tableHeaderFontSize ?? '7.3px' }};
            line-height: 1.2;
            text-align: left;
            text-transform: uppercase;
            vertical-align: middle;
            white-space: normal;
            overflow-wrap: break-word;
            word-wrap: break-word;
        }

        .report-table td {
            border: 1px solid #e5e7eb;
            padding: 6px;
            vertical-align: top;
            white-space: normal;
            overflow-wrap: break-word;
            word-wrap: break-word;
            word-break: normal;
        }

        .report-table tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .report-table tfoot td {
            border-top: 2px solid
                {{ $accentColor }};
            background: #edf6ef;
            font-weight: bold;
        }

        .right {
            text-align: right !important;
        }

        .center {
            text-align: center !important;
        }

        .nowrap {
            white-space: nowrap !important;
            overflow: hidden;
        }

        .wrap {
            white-space: normal !important;
            overflow-wrap: break-word !important;
            word-wrap: break-word !important;
            word-break: normal !important;
        }

        .empty-state {
            padding: 18px !important;
            color: #6b7280;
            text-align: center;
        }

        .signature-block {
            margin-top: 20px;
            page-break-inside: avoid;
        }

        .signature-table {
            border-spacing: 8px 0;
            border-collapse: separate;
            table-layout: fixed;
        }

        .signature-card {
            min-height: 112px;
            border: 1px solid #dbe4d3;
            background: #fbfdf9;
            padding: 10px;
            vertical-align: top;
        }

        .signature-title {
            margin-bottom: 7px;
            color: {{ $secondaryColor }};
            font-size: 9.5px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .signature-name {
            color: #111827;
            font-size: 11px;
            font-weight: bold;
            white-space: normal;
            overflow-wrap: break-word;
            word-wrap: break-word;
        }

        .signature-line {
            margin-top: 14px;
            border-top: 1px solid #4b5563;
            padding-top: 5px;
        }

        .signature-img {
            display: block;
            max-width: 145px;
            max-height: 54px;
            margin: 3px auto 4px;
        }

        .signature-fallback {
            margin: 10px 0;
            color: {{ $successColor }};
            font-size: 13px;
            font-weight: bold;
            font-style: italic;
            text-align: center;
        }

        .stamp-wrap,
        .qr-box {
            text-align: center;
        }

        .stamp-img {
            display: block;
            max-width: 112px;
            max-height: 92px;
            margin: 0 auto 5px;
        }

        .stamp-circle {
            width: 92px;
            height: 92px;
            margin: 0 auto 6px;
            border: 2px dashed
                {{ $primaryColor }};
            border-radius: 50%;
            color: {{ $primaryColor }};
            font-size: 9px;
            font-weight: bold;
            line-height: 1.35;
            padding-top: 31px;
            text-align: center;
        }

        .qr-image-wrap,
        .qr-fallback {
            width: 96px;
            height: 96px;
            margin: 0 auto 6px;
            border: 2px solid
                {{ $primaryColor }};
            background: #ffffff;
            padding: 4px;
        }

        .qr-image {
            width: 86px;
            height: 86px;
        }

        .qr-fallback {
            padding-top: 26px;
            color: {{ $primaryColor }};
            font-size: 7.5px;
            line-height: 1.3;
            overflow-wrap: anywhere;
            word-wrap: break-word;
        }

        @media print {
            .print-toolbar {
                display: none;
            }
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
                <td class="logo-cell">
                    @if ($logoBase64)
                        <img
                            src="{{ $logoBase64 }}"
                            class="logo"
                            alt="{{ $farmName }} Logo"
                        >
                    @else
                        <div class="logo-fallback">
                            {{
                                collect(
                                    preg_split(
                                        '/\s+/',
                                        $farmName
                                    )
                                )
                                    ->filter()
                                    ->take(3)
                                    ->map(
                                        fn ($word) =>
                                            mb_substr(
                                                $word,
                                                0,
                                                1
                                            )
                                    )
                                    ->implode('')
                            }}
                        </div>
                    @endif
                </td>

                <td>
                    <div class="company-title">
                        {{ $farmName }}
                    </div>

                    <div class="header-report-name">
                        {{ $reportTitle }}
                    </div>

                    <div class="tagline">
                        {{ $farmTagline }}
                    </div>
                </td>

                <td class="header-right">
                    @if ($farmPhone)
                        <strong>Phone:</strong>
                        {{ $farmPhone }}<br>
                    @endif

                    @if ($farmEmail)
                        <strong>Email:</strong>
                        {{ $farmEmail }}<br>
                    @endif

                    @if ($farmAddress)
                        <strong>Location:</strong>
                        {{ $farmAddress }}<br>
                    @endif

                    @if ($kraPin)
                        <strong>KRA PIN:</strong>
                        {{ $kraPin }}
                    @endif
                </td>
            </tr>
        </table>
    </header>

    <footer>
        <table class="footer-table">
            <tr>
                <td class="footer-left">
                    Printed
                    {{ $eatNow->format('d M Y, H:i') }}
                    EAT
                </td>

                <td class="footer-center">
                    {{ $reportTitle }}
                    -
                    {{ $reportReference }}
                </td>

                <td class="footer-right">
                    Created by
                    {{ $generatedByName }}
                    ({{ $generatedByRole }})
                </td>
            </tr>

            <tr>
                <td
                    colspan="3"
                    class="footer-details"
                >
                    {{ $farmName }}
                    -
                    {{ $farmCounty }}

                    @if ($farmPhone)
                        - {{ $farmPhone }}
                    @endif

                    @if ($farmEmail)
                        - {{ $farmEmail }}
                    @endif
                </td>
            </tr>
        </table>
    </footer>

    @unless ($isPdf)
        <div class="print-toolbar">
            <button
                type="button"
                onclick="window.print()"
            >
                Print {{ $reportTitle }}
            </button>
        </div>
    @endunless

    <main>
        <div class="report-top">
            <table class="report-title-table">
                <tr>
                    <td>
                        <h1 class="report-title">
                            {{ $reportTitle }}
                        </h1>

                        <div class="report-subtitle">
                            {{ $reportSubtitle }}
                        </div>
                    </td>

                    <td class="report-status-box">
                        <div class="report-status-label">
                            Report Status
                        </div>

                        <span
                            class="badge {{
                                $statusBadgeClass
                            }}"
                        >
                            {{ $reportStatus }}
                        </span>

                        <div class="report-reference">
                            {{ $reportReference }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <table class="report-info-grid">
            <tr>
                <td>
                    <span class="info-label">
                        Report Code
                    </span>
                    <span class="info-value">
                        {{ $reportCode }}
                    </span>
                </td>

                <td>
                    <span class="info-label">
                        Reporting Period
                    </span>
                    <span class="info-value">
                        {{ $periodLabel }}
                    </span>
                </td>

                <td>
                    <span class="info-label">
                        Total Records
                    </span>
                    <span class="info-value">
                        {{ number_format($recordCount) }}
                    </span>
                </td>

                <td>
                    <span class="info-label">
                        Currency
                    </span>
                    <span class="info-value">
                        KES
                    </span>
                </td>

                <td>
                    <span class="info-label">
                        Generated
                    </span>
                    <span class="info-value">
                        {{ $eatNow->format('d M Y') }}
                    </span>
                </td>
            </tr>
        </table>

        @foreach ($summaryRows as $summaryRow)
            <table class="kpi-table">
                <tr>
                    @foreach ($summaryRow as $item)
                        <td
                            class="kpi-card kpi-{{
                                $item['tone']
                                    ?? 'primary'
                            }}"
                            style="width: {{
                                100
                                / max(
                                    1,
                                    $summaryRow->count()
                                )
                            }}%;"
                        >
                            <div class="kpi-label">
                                {{ $item['label'] }}
                            </div>

                            <div class="kpi-value">
                                @if (
                                    (
                                        $item['format']
                                        ?? 'money'
                                    ) === 'money'
                                )
                                    KES
                                    {{
                                        number_format(
                                            (float)
                                            $item['value'],
                                            2
                                        )
                                    }}
                                @else
                                    {{
                                        number_format(
                                            (float)
                                            $item['value']
                                        )
                                    }}
                                @endif
                            </div>
                        </td>
                    @endforeach
                </tr>
            </table>
        @endforeach

        <table class="notes-table">
            <tr>
                <td class="note-card">
                    <div class="note-title">
                        Management Interpretation
                    </div>

                    {{ $decisionNote }}
                </td>

                <td class="note-card">
                    <div class="note-title">
                        Accounting Control Note
                    </div>

                    {{ $controlNote }}
                </td>
            </tr>
        </table>

        <table class="report-table">
            @if (! empty($columnWidths))
                <colgroup>
                    @foreach ($columnWidths as $width)
                        <col style="width: {{ $width }}%;">
                    @endforeach
                </colgroup>
            @endif

            <thead>
                <tr>
                    @foreach (
                        $columns
                        as $columnIndex => $column
                    )
                        <th
                            class="{{
                                in_array(
                                    $columnIndex,
                                    $rightAlignedColumns
                                        ?? [],
                                    true
                                )
                                    ? 'right'
                                    : (
                                        in_array(
                                            $columnIndex,
                                            $centerAlignedColumns
                                                ?? [],
                                            true
                                        )
                                            ? 'center'
                                            : ''
                                    )
                            }}"
                        >
                            {{ $column }}
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        @foreach (
                            $row
                            as $cellIndex => $cell
                        )
                            @php
                                $classes = [];

                                if (
                                    in_array(
                                        $cellIndex,
                                        $rightAlignedColumns
                                            ?? [],
                                        true
                                    )
                                ) {
                                    $classes[] = 'right';
                                }

                                if (
                                    in_array(
                                        $cellIndex,
                                        $centerAlignedColumns
                                            ?? [],
                                        true
                                    )
                                ) {
                                    $classes[] = 'center';
                                }

                                $classes[] =
                                    in_array(
                                        $cellIndex,
                                        $nowrapColumns
                                            ?? [],
                                        true
                                    )
                                        ? 'nowrap'
                                        : 'wrap';
                            @endphp

                            <td class="{{ implode(' ', $classes) }}">
                                {{ $cell }}
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td
                            colspan="{{
                                max(
                                    1,
                                    count($columns)
                                )
                            }}"
                            class="empty-state"
                        >
                            No posted accounting records
                            were found for this reporting
                            scope.
                        </td>
                    </tr>
                @endforelse
            </tbody>

            @if (! empty($totals))
                <tfoot>
                    <tr>
                        @foreach (
                            $totals
                            as $cellIndex => $cell
                        )
                            <td
                                class="{{
                                    in_array(
                                        $cellIndex,
                                        $rightAlignedColumns
                                            ?? [],
                                        true
                                    )
                                        ? 'right nowrap'
                                        : 'wrap'
                                }}"
                            >
                                {{ $cell }}
                            </td>
                        @endforeach
                    </tr>
                </tfoot>
            @endif
        </table>

        <div class="signature-block">
            <table class="signature-table">
                <tr>
                    <td
                        class="signature-card"
                        style="width: 26%;"
                    >
                        <div class="signature-title">
                            Prepared By
                        </div>

                        <div class="signature-name">
                            {{ $generatedByName }}
                        </div>

                        <div class="small-muted">
                            {{ $generatedByRole }}
                        </div>

                        <div class="signature-line"></div>

                        <div class="small-muted">
                            Generated
                            {{ $eatNow->format('d M Y, H:i') }}
                            EAT
                        </div>
                    </td>

                    <td
                        class="signature-card"
                        style="width: 28%;"
                    >
                        <div class="signature-title">
                            Authorized Signature
                        </div>

                        @if ($signatureBase64)
                            <img
                                src="{{ $signatureBase64 }}"
                                class="signature-img"
                                alt="Signature"
                            >
                        @else
                            <div class="signature-fallback">
                                Digitally Approved
                            </div>
                        @endif

                        <div class="small-muted">
                            Approved
                            {{ $eatNow->format('d M Y, H:i') }}
                            EAT
                        </div>

                        <div class="signature-line"></div>

                        <div class="small-muted">
                            {{ $authorizedName }}
                            -
                            {{ $authorizedTitle }}
                        </div>
                    </td>

                    <td
                        class="signature-card stamp-wrap"
                        style="width: 22%;"
                    >
                        <div class="signature-title">
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
                                OFFICIAL<br>STAMP
                            </div>
                        @endif

                        <div class="small-muted">
                            {{ $farmName }}
                        </div>
                    </td>

                    <td
                        class="signature-card"
                        style="width: 24%;"
                    >
                        <div class="qr-box">
                            <div class="signature-title">
                                Verification
                            </div>

                            @if ($qrDataUri)
                                <div class="qr-image-wrap">
                                    <img
                                        src="{{ $qrDataUri }}"
                                        class="qr-image"
                                        alt="Verification QR"
                                    >
                                </div>
                            @else
                                <div class="qr-fallback">
                                    QR unavailable<br>
                                    {{ $reportCode }}
                                </div>
                            @endif

                            <div class="small-muted">
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
            $pdf->page_script(function (
                $pageNumber,
                $pageCount,
                $canvas,
                $fontMetrics
            ) {
                $font = $fontMetrics->getFont(
                    'Courier',
                    'normal'
                );

                $size = 8.5;

                $text =
                    "Page {$pageNumber} of {$pageCount}";

                $width =
                    $fontMetrics->getTextWidth(
                        $text,
                        $font,
                        $size
                    );

                $x =
                    (
                        $canvas->get_width()
                        - $width
                    ) / 2;

                $y =
                    $canvas->get_height()
                    - 30;

                $canvas->text(
                    $x,
                    $y,
                    $text,
                    $font,
                    $size,
                    [
                        0.42,
                        0.45,
                        0.50,
                    ]
                );
            });
        }
    </script>
</body>
</html>
