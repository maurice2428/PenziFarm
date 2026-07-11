@php
    use App\Models\Settings\PaymentSetting;

    if (! function_exists('salesReceiptImageBase64')) {
        function salesReceiptImageBase64(mixed $path): ?string
        {
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

            $path = trim($path);

            if ($path === '') {
                return null;
            }

            if (str_starts_with($path, 'data:image/')) {
                return $path;
            }

            if (
                str_starts_with($path, '[')
                || str_starts_with($path, '{')
            ) {
                try {
                    $decoded = json_decode(
                        $path,
                        true,
                        flags: JSON_THROW_ON_ERROR
                    );

                    return salesReceiptImageBase64($decoded);
                } catch (\Throwable) {
                    // Continue with the original value.
                }
            }

            $urlPath = parse_url($path, PHP_URL_PATH);

            if (
                is_string($urlPath)
                && $urlPath !== ''
            ) {
                $path = $urlPath;
            }

            $cleanPath = preg_replace(
                '#^storage/#',
                '',
                ltrim($path, '/')
            );

            $possiblePaths = array_filter([
                storage_path('app/public/' . $cleanPath),
                public_path('storage/' . $cleanPath),
                public_path($cleanPath),
                is_file($path) ? $path : null,
            ]);

            foreach ($possiblePaths as $fullPath) {
                if (
                    ! is_file($fullPath)
                    || ! is_readable($fullPath)
                ) {
                    continue;
                }

                $extension = strtolower(
                    pathinfo($fullPath, PATHINFO_EXTENSION)
                );

                $mime = match ($extension) {
                    'jpg', 'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    'webp' => 'image/webp',
                    'svg' => 'image/svg+xml',
                    default => mime_content_type($fullPath)
                        ?: 'image/png',
                };

                return 'data:' . $mime . ';base64,'
                    . base64_encode(
                        file_get_contents($fullPath)
                    );
            }

            return null;
        }
    }

    if (! function_exists('salesReceiptEnumValue')) {
        function salesReceiptEnumValue(mixed $value): string
        {
            if ($value instanceof \BackedEnum) {
                return (string) $value->value;
            }

            if ($value instanceof \UnitEnum) {
                return $value->name;
            }

            return (string) ($value ?? '');
        }
    }

    try {
        $paymentSettings = PaymentSetting::current();
    } catch (\Throwable) {
        $paymentSettings = null;
    }

    $eatNow = now('Africa/Nairobi');

    $farmName = setting(
        'farm.name',
        setting(
            'company.name',
            config('app.name', 'Farm Management System')
        )
    );

    $farmTagline = setting(
        'farm.tagline',
        'Nurturing Quality, Inspiring Global Standards'
    );

    $farmPhone = setting('farm.phone', '');
    $farmEmail = setting('farm.email', '');
    $farmCounty = setting('farm.county', 'Kenya');
    $farmAddress = setting('farm.address', $farmCounty);

    $kraPin = setting(
        'farm.kra_pin',
        setting('company.kra_pin', '')
    );

    $primaryColor = trim(
        setting('theme.primary', '#014a12')
    );

    $secondaryColor = trim(
        setting('theme.secondary', '#14532d')
    );

    $accentColor = trim(
        setting('theme.accent', '#f59e0b')
    );

    $dangerColor = trim(
        setting('theme.danger', '#dc2626')
    );

    $successColor = trim(
        setting('theme.success', '#16a34a')
    );

    $logoBase64 = salesReceiptImageBase64(
        setting('branding.logo_light')
        ?: setting('branding.logo')
        ?: setting('farm.logo')
        ?: data_get($paymentSettings, 'company_logo')
    );

    $signatureBase64 = salesReceiptImageBase64(
        data_get($paymentSettings, 'authorized_signature_image')
        ?: data_get($paymentSettings, 'invoice_signature_path')
        ?: data_get($paymentSettings, 'signature_path')
        ?: data_get($paymentSettings, 'authorized_signature_path')
        ?: setting('branding.signature')
    );

    $stampBase64 = salesReceiptImageBase64(
        data_get($paymentSettings, 'payment_stamp_image')
        ?: data_get($paymentSettings, 'invoice_stamp_path')
        ?: data_get($paymentSettings, 'stamp_path')
        ?: data_get($paymentSettings, 'official_stamp_path')
        ?: setting('branding.stamp')
    );

    $authorizedName = setting(
        'farm.authorized_signatory_name',
        'Authorised Signatory'
    );

    $authorizedTitle = setting(
        'farm.authorized_signatory_title',
        'Finance / Management'
    );

    $generatedByName = $generatedBy?->name ?? 'System';
    $generatedByRole = $generatedByRole ?? 'User';

    $paymentMethodValue = strtolower(
        salesReceiptEnumValue($payment->payment_method)
    );

    $paymentStatusValue = strtolower(
        salesReceiptEnumValue($payment->status)
    );

    $methodLabel = $payment->payment_method_label
        ?? str($paymentMethodValue)
            ->replace('_', ' ')
            ->title();

    $statusLabel = $payment->status_label
        ?? str($paymentStatusValue)
            ->replace('_', ' ')
            ->title();

    $statusColor = match ($paymentStatusValue) {
        'successful',
        'confirmed',
        'completed',
        'paid' => $successColor,

        'pending',
        'processing' => $accentColor,

        'failed',
        'cancelled',
        'reversed',
        'refunded' => $dangerColor,

        default => $primaryColor,
    };

    $transactionReference =
        $payment->mpesa_receipt_number
        ?: $payment->reference_number
        ?: '-';

    $invoiceTotal = (float) ($invoice?->grand_total ?? 0);
    $amountReceived = (float) $payment->amount;

    $totalPaidToDate = (float) (
        $invoice?->amount_paid
        ?? $amountReceived
    );

    $balanceDue = (float) (
        $invoice?->balance_due
        ?? max($invoiceTotal - $totalPaidToDate, 0)
    );

    $paidBeforeReceipt = max(
        $totalPaidToDate - $amountReceived,
        0
    );

    $balanceBeforeReceipt = max(
        $invoiceTotal - $paidBeforeReceipt,
        0
    );

    $payerName = $payment->paid_by_name
        ?: $customer?->name
        ?: '-';

    $payerPhone = $payment->paid_by_phone
        ?: $customer?->phone
        ?: '-';

    $receivedByName = $payment->receivedBy?->name
        ?: $generatedByName;

    $verifiedByName = $payment->verifiedBy?->name
        ?: '-';

    $verifiedAt = $payment->verified_at
        ?->timezone('Africa/Nairobi')
        ?->format('d M Y, H:i')
        ?: '-';

    $verificationText = implode(' | ', [
        $farmName,
        'Official Sales Payment Receipt',
        'Receipt: ' . ($payment->payment_number ?: '-'),
        'Invoice: ' . ($invoice?->invoice_number ?: '-'),
        'Customer: ' . ($customer?->name ?: '-'),
        'Amount: KES ' . number_format($amountReceived, 2),
        'Method: ' . $methodLabel,
        'Reference: ' . $transactionReference,
        'Generated: ' . $eatNow->format('Y-m-d H:i:s') . ' EAT',
    ]);

    $qrDataUri = null;

    if (
        class_exists(
            \SimpleSoftwareIO\QrCode\Facades\QrCode::class
        )
    ) {
        try {
            $qrPng =
                \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                    ->size(116)
                    ->margin(1)
                    ->generate($verificationText);

            $qrDataUri = 'data:image/png;base64,'
                . base64_encode($qrPng);
        } catch (\Throwable) {
            try {
                $qrSvg =
                    \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                        ->size(116)
                        ->margin(1)
                        ->generate($verificationText);

                $qrDataUri = 'data:image/svg+xml;base64,'
                    . base64_encode($qrSvg);
            } catch (\Throwable) {
                $qrDataUri = null;
            }
        }
    }
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>
        {{ $payment->payment_number ?: 'Sales Payment Receipt' }}
    </title>

    <style>
        @page {
            margin: 112px 32px 78px 32px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Courier, monospace;
            font-size: 9.3px;
            line-height: 1.30;
            color: #1f2937;
            background: #ffffff;
        }

        .watermark {
            position: fixed;
            top: 32%;
            left: 16%;
            width: 68%;
            opacity: 0.022;
            z-index: -10;
            text-align: center;
        }

        .watermark img {
            width: 340px;
            max-height: 230px;
            object-fit: contain;
        }

        header {
            position: fixed;
            top: -92px;
            left: 0;
            right: 0;
            height: 84px;
            border-bottom: 3px solid {{ $primaryColor }};
        }

        footer {
            position: fixed;
            bottom: -57px;
            left: 0;
            right: 0;
            height: 45px;
            border-top: 1px solid #d1d5db;
            color: #4b5563;
            font-size: 7.6px;
        }

        .header-table,
        .footer-table,
        .receipt-title-table,
        .receipt-grid,
        .allocation-grid,
        .items-table,
        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: middle;
        }

        .logo-cell {
            width: 145px;
        }

        .logo {
            width: 138px;
            max-height: 68px;
            object-fit: contain;
        }

        .logo-fallback {
            width: 58px;
            height: 58px;
            border: 2px solid {{ $primaryColor }};
            border-radius: 50%;
            color: {{ $primaryColor }};
            font-size: 17px;
            font-weight: bold;
            line-height: 58px;
            text-align: center;
        }

        .company-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            color: {{ $primaryColor }};
            text-transform: uppercase;
            letter-spacing: .35px;
        }

        .tagline {
            margin-top: 3px;
            text-align: center;
            font-size: 8.8px;
            color: #4b5563;
            font-style: italic;
        }

        .header-right {
            width: 220px;
            text-align: right;
            font-size: 8.4px;
            line-height: 1.45;
            white-space: normal;
            overflow-wrap: break-word;
            word-wrap: break-word;
        }

        .footer-table {
            margin-top: 6px;
            table-layout: fixed;
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
            padding-top: 3px;
            color: #6b7280;
            font-size: 7px;
            text-align: center;
        }

        .small-muted {
            color: #6b7280;
            font-size: 7.4px;
        }

        .receipt-top {
            margin-bottom: 9px;
            border: 1px solid #dbe4d3;
            border-left: 7px solid {{ $primaryColor }};
            background: #fbfdf9;
            padding: 10px 12px;
            page-break-inside: avoid;
        }

        .receipt-title-table td {
            vertical-align: middle;
        }

        .receipt-title {
            margin: 0;
            color: #111827;
            font-size: 20px;
            line-height: 1.06;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .55px;
        }

        .receipt-subtitle {
            margin-top: 4px;
            color: {{ $primaryColor }};
            font-size: 8.8px;
            font-weight: bold;
        }

        .badge {
            display: inline-block;
            padding: 3px 7px;
            border-radius: 999px;
            color: #ffffff;
            font-size: 7.4px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .amount-title-box {
            width: 230px;
            text-align: right;
        }

        .amount-title-label {
            color: #6b7280;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .amount-title-value {
            margin-top: 2px;
            color: {{ $primaryColor }};
            font-size: 18px;
            font-weight: bold;
            white-space: nowrap;
        }

        .receipt-top,
        .receipt-grid,
        .payment-confirmation,
        .items-table,
        .allocation-grid,
        .proof-note,
        .signature-table {
            width: 100%;
            margin-left: 0;
            margin-right: 0;
        }

        .receipt-grid {
            margin-bottom: 9px;
            border-spacing: 0;
            border-collapse: collapse;
            table-layout: fixed;
            page-break-inside: avoid;
        }

        .receipt-card {
            width: 50%;
            height: 116px;
            border: 1px solid #dbe4d3;
            background: #ffffff;
            padding: 8px 9px;
            vertical-align: top;
        }

        .receipt-card:first-child {
            border-left: 5px solid {{ $primaryColor }};
        }

        .receipt-card:last-child {
            border-left: 5px solid {{ $accentColor }};
        }

        .receipt-card + .receipt-card,
        .allocation-card + .allocation-card,
        .signature-card + .signature-card {
            border-left-width: 1px;
        }

        .card-heading {
            margin-bottom: 5px;
            color: {{ $primaryColor }};
            font-size: 9.7px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .info-row {
            border-bottom: 1px solid #edf2ea;
            padding: 3.7px 0;
        }

        .info-row:last-child {
            border-bottom: 0;
        }

        .info-label {
            display: inline-block;
            width: 42%;
            color: #6b7280;
            font-size: 8px;
            vertical-align: top;
        }

        .info-value {
            display: inline-block;
            width: 56%;
            color: #111827;
            font-weight: bold;
            text-align: right;
            vertical-align: top;
            white-space: normal;
            overflow-wrap: break-word;
            word-wrap: break-word;
        }

        .payment-confirmation {
            margin-bottom: 9px;
            border: 1px solid #dbe4d3;
            border-left: 5px solid {{ $secondaryColor }};
            background: #f9fafb;
            padding: 7px 9px;
            page-break-inside: avoid;
        }

        .payment-confirmation-title {
            margin-bottom: 5px;
            color: {{ $secondaryColor }};
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .payment-confirmation-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .payment-confirmation-table td {
            width: 25%;
            height: 45px;
            padding: 3px 5px;
            border-right: 1px solid #e5e7eb;
            vertical-align: middle;
            white-space: normal;
            overflow-wrap: break-word;
            word-wrap: break-word;
        }

        .payment-confirmation-table td:last-child {
            border-right: 0;
        }

        .confirmation-label {
            display: block;
            color: #6b7280;
            font-size: 7.1px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .confirmation-value {
            display: block;
            margin-top: 2px;
            color: #111827;
            font-weight: bold;
        }

        .section-title {
            margin: 11px 0 5px;
            color: {{ $primaryColor }};
            font-size: 10.5px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .items-table {
            table-layout: fixed;
            font-size: 7.6px;
            line-height: 1.2;
        }

        .items-table thead {
            display: table-header-group;
        }

        .items-table tr {
            page-break-inside: avoid;
        }

        .items-table th {
            border: 1px solid {{ $primaryColor }};
            background: {{ $primaryColor }};
            color: #ffffff;
            padding: 4.5px 4px;
            font-size: 6.8px;
            line-height: 1.15;
            text-align: left;
            text-transform: uppercase;
            white-space: normal;
            overflow-wrap: break-word;
            word-wrap: break-word;
        }

        .items-table td {
            border: 1px solid #e5e7eb;
            padding: 4px;
            vertical-align: top;
            white-space: normal;
            overflow-wrap: break-word;
            word-wrap: break-word;
            word-break: normal;
        }

        .items-table tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .right {
            text-align: right !important;
        }

        .center {
            text-align: center !important;
        }

        .nowrap {
            white-space: nowrap !important;
        }

        .allocation-grid {
            margin-top: 9px;
            border-spacing: 0;
            border-collapse: collapse;
            table-layout: fixed;
            page-break-inside: avoid;
        }

        .allocation-card {
            width: 50%;
            height: 96px;
            border: 1px solid #dbe4d3;
            background: #ffffff;
            padding: 8px;
            vertical-align: top;
        }

        .allocation-title {
            margin-bottom: 5px;
            color: {{ $primaryColor }};
            font-size: 9.5px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .allocation-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .allocation-label {
            width: 62%;
        }

        .allocation-amount {
            width: 38%;
            text-align: right;
            white-space: nowrap;
        }

        .allocation-table td {
            padding: 3.5px 3px;
            border-bottom: 1px solid #edf2ea;
        }

        .allocation-table tr:last-child td {
            border-bottom: 0;
        }

        .allocation-total td {
            padding-top: 5px;
            border-top: 2px solid {{ $primaryColor }};
            background: #f0fdf4;
            color: #111827;
            font-weight: bold;
        }

        .proof-note {
            margin-top: 8px;
            border: 1px solid #e5e7eb;
            border-left: 5px solid {{ $accentColor }};
            background: #fffdf7;
            padding: 7px 9px;
            font-size: 8px;
            line-height: 1.35;
            page-break-inside: avoid;
        }

        .signature-block {
            margin-top: 11px;
            page-break-inside: avoid;
        }

        .signature-table {
            border-spacing: 0;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .signature-card {
            width: 25%;
            height: 116px;
            border: 1px solid #dbe4d3;
            background: #fbfdf9;
            padding: 7px;
            vertical-align: top;
        }

        .signature-title {
            margin-bottom: 5px;
            color: {{ $secondaryColor }};
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .signature-name {
            color: #111827;
            font-size: 9.3px;
            font-weight: bold;
            white-space: normal;
            overflow-wrap: break-word;
            word-wrap: break-word;
        }

        .signature-line {
            margin-top: 9px;
            border-top: 1px solid #4b5563;
            padding-top: 3px;
        }

        .signature-img {
            display: block;
            width: auto;
            max-width: 108px;
            height: 42px;
            max-height: 42px;
            margin: 1px auto 2px;
            object-fit: contain;
        }

        .signature-fallback {
            margin: 7px 0;
            color: {{ $successColor }};
            font-size: 11px;
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
            width: auto;
            max-width: 76px;
            height: 64px;
            max-height: 64px;
            margin: 0 auto 3px;
            object-fit: contain;
        }

        .stamp-circle {
            width: 68px;
            height: 68px;
            margin: 0 auto 3px;
            border: 1.5px dashed {{ $primaryColor }};
            border-radius: 50%;
            color: {{ $primaryColor }};
            font-size: 6.7px;
            font-weight: bold;
            line-height: 1.3;
            padding-top: 22px;
            text-align: center;
        }

        .qr-image-wrap,
        .qr-fallback {
            width: 72px;
            height: 72px;
            margin: 0 auto 3px;
            border: 1.5px solid {{ $primaryColor }};
            background: #ffffff;
            padding: 3px;
        }

        .qr-image {
            width: 64px;
            height: 64px;
        }

        .qr-fallback {
            padding-top: 18px;
            color: {{ $primaryColor }};
            font-size: 6px;
            line-height: 1.25;
            overflow-wrap: anywhere;
            word-wrap: break-word;
        }

        .receipt-note {
            margin-top: 4px;
            text-align: center;
            color: #6b7280;
            font-size: 6.8px;
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
                                    preg_split('/\s+/', $farmName)
                                )
                                    ->filter()
                                    ->take(3)
                                    ->map(
                                        fn ($word) =>
                                            mb_substr($word, 0, 1)
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
                    Printed {{ $eatNow->format('d M Y, H:i') }} EAT
                </td>

                <td class="footer-center">
                    Sales Receipt -
                    {{ $payment->payment_number ?: '-' }}
                </td>

                <td class="footer-right">
                    Created by {{ $generatedByName }}
                    ({{ $generatedByRole }})
                </td>
            </tr>

            <tr>
                <td
                    colspan="3"
                    class="footer-details"
                >
                    {{ $farmName }} - {{ $farmCounty }}
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

    <main>
        <div class="receipt-top">
            <table class="receipt-title-table">
                <tr>
                    <td>
                        <h1 class="receipt-title">
                            Official Payment Receipt
                        </h1>

                        <div class="receipt-subtitle">
                            {{ $payment->payment_number ?: '-' }}
                            &nbsp;&bull;&nbsp;
                            {{ $methodLabel }}
                            &nbsp;&bull;&nbsp;

                            <span
                                class="badge"
                                style="background:
                                    {{ $statusColor }};"
                            >
                                {{ $statusLabel }}
                            </span>
                        </div>
                    </td>

                    <td class="amount-title-box">
                        <div class="amount-title-label">
                            Amount Received
                        </div>

                        <div class="amount-title-value">
                            KES {{ number_format($amountReceived, 2) }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <table class="receipt-grid">
            <colgroup>
                <col style="width: 50%;">
                <col style="width: 50%;">
            </colgroup>

            <tr>
                <td class="receipt-card">
                    <div class="card-heading">
                        Receipt Details
                    </div>

                    <div class="info-row">
                        <span class="info-label">
                            Receipt Number
                        </span>
                        <span class="info-value">
                            {{ $payment->payment_number ?: '-' }}
                        </span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">
                            Payment Date
                        </span>
                        <span class="info-value">
                            {{
                                $payment->payment_date
                                    ?->format('d M Y')
                                ?: '-'
                            }}
                        </span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">
                            Method
                        </span>
                        <span class="info-value">
                            {{ $methodLabel }}
                        </span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">
                            Reference
                        </span>
                        <span class="info-value">
                            {{ $transactionReference }}
                        </span>
                    </div>
                </td>

                <td class="receipt-card">
                    <div class="card-heading">
                        Customer / Invoice
                    </div>

                    <div class="info-row">
                        <span class="info-label">
                            Invoice Number
                        </span>
                        <span class="info-value">
                            {{ $invoice?->invoice_number ?: '-' }}
                        </span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">
                            Customer
                        </span>
                        <span class="info-value">
                            {{ $customer?->name ?: '-' }}
                        </span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">
                            Paid By
                        </span>
                        <span class="info-value">
                            {{ $payerName }}
                        </span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">
                            Phone
                        </span>
                        <span class="info-value">
                            {{ $payerPhone }}
                        </span>
                    </div>
                </td>
            </tr>
        </table>

        <div class="payment-confirmation">
            <div class="payment-confirmation-title">
                Payment Confirmation
            </div>

            <table class="payment-confirmation-table">
                <colgroup>
                    <col style="width: 25%;">
                    <col style="width: 25%;">
                    <col style="width: 25%;">
                    <col style="width: 25%;">
                </colgroup>

                <tr>
                    <td>
                        <span class="confirmation-label">
                            Transaction
                        </span>
                        <span class="confirmation-value">
                            {{ $transactionReference }}
                        </span>
                    </td>

                    <td>
                        <span class="confirmation-label">
                            Received By
                        </span>
                        <span class="confirmation-value">
                            {{ $receivedByName }}
                        </span>
                    </td>

                    <td>
                        <span class="confirmation-label">
                            Verified By
                        </span>
                        <span class="confirmation-value">
                            {{ $verifiedByName }}
                        </span>
                    </td>

                    <td>
                        <span class="confirmation-label">
                            Verified At
                        </span>
                        <span class="confirmation-value">
                            {{ $verifiedAt }}
                        </span>
                    </td>
                </tr>
            </table>
        </div>

        @if ($invoice?->items?->count())
            <div class="section-title">
                Animals / Items Bought
            </div>

            <table class="items-table">
                <colgroup>
                    <col style="width: 5%;">
                    <col style="width: 15%;">
                    <col style="width: 21%;">
                    <col style="width: 8%;">
                    <col style="width: 12%;">
                    <col style="width: 13%;">
                    <col style="width: 13%;">
                    <col style="width: 13%;">
                </colgroup>

                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tag</th>
                        <th>Breed / Item</th>
                        <th>Sex</th>
                        <th class="right">Weight</th>
                        <th>Price Mode</th>
                        <th class="right">Unit Price</th>
                        <th class="right">Line Total</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($invoice->items as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>

                            <td class="nowrap">
                                <strong>
                                    {{
                                        $item->tag_number
                                        ?? $item->animal?->tag_number
                                        ?? '-'
                                    }}
                                </strong>
                            </td>

                            <td>
                                {{
                                    $item->breed_name
                                    ?? $item->animal?->breed?->breed_name
                                    ?? $item->description
                                    ?? '-'
                                }}
                            </td>

                            <td class="center">
                                {{
                                    $item->sex
                                    ?? $item->animal?->sex
                                    ?? '-'
                                }}
                            </td>

                            <td class="right nowrap">
                                {{
                                    number_format(
                                        (float) ($item->sale_weight ?? 0),
                                        2
                                    )
                                }} KG
                            </td>

                            <td>
                                {{
                                    strtoupper(
                                        str_replace(
                                            '_',
                                            ' ',
                                            (string) (
                                                $item->price_mode
                                                ?? '-'
                                            )
                                        )
                                    )
                                }}
                            </td>

                            <td class="right nowrap">
                                KES
                                {{
                                    number_format(
                                        (float) ($item->unit_price ?? 0),
                                        2
                                    )
                                }}
                            </td>

                            <td class="right nowrap">
                                <strong>
                                    KES
                                    {{
                                        number_format(
                                            (float) ($item->line_total ?? 0),
                                            2
                                        )
                                    }}
                                </strong>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <table class="allocation-grid">
            <colgroup>
                <col style="width: 50%;">
                <col style="width: 50%;">
            </colgroup>

            <tr>
                <td class="allocation-card">
                    <div class="allocation-title">
                        Payment Allocation
                    </div>

                    <table class="allocation-table">
                        <colgroup>
                            <col style="width: 62%;">
                            <col style="width: 38%;">
                        </colgroup>

                        <tr>
                            <td class="allocation-label">
                                Invoice Total
                            </td>
                            <td class="allocation-amount">
                                KES {{ number_format($invoiceTotal, 2) }}
                            </td>
                        </tr>

                        <tr>
                            <td class="allocation-label">
                                Paid Before Receipt
                            </td>
                            <td class="allocation-amount">
                                KES {{ number_format($paidBeforeReceipt, 2) }}
                            </td>
                        </tr>

                        <tr class="allocation-total">
                            <td class="allocation-label">
                                Current Receipt
                            </td>
                            <td class="allocation-amount">
                                KES {{ number_format($amountReceived, 2) }}
                            </td>
                        </tr>
                    </table>
                </td>

                <td class="allocation-card">
                    <div class="allocation-title">
                        Invoice Position
                    </div>

                    <table class="allocation-table">
                        <colgroup>
                            <col style="width: 62%;">
                            <col style="width: 38%;">
                        </colgroup>

                        <tr>
                            <td class="allocation-label">
                                Balance Before Receipt
                            </td>
                            <td class="allocation-amount">
                                KES {{ number_format($balanceBeforeReceipt, 2) }}
                            </td>
                        </tr>

                        <tr>
                            <td class="allocation-label">
                                Total Paid To Date
                            </td>
                            <td class="allocation-amount">
                                KES {{ number_format($totalPaidToDate, 2) }}
                            </td>
                        </tr>

                        <tr class="allocation-total">
                            <td class="allocation-label">
                                Balance Due
                            </td>
                            <td class="allocation-amount">
                                KES {{ number_format($balanceDue, 2) }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <div class="proof-note">
            <strong>Receipt Confirmation:</strong>
            This document confirms that
            <strong>KES {{ number_format($amountReceived, 2) }}</strong>
            was received against invoice
            <strong>{{ $invoice?->invoice_number ?: '-' }}</strong>.
            Keep this receipt for your records.
            @if ($payment->notes)
                <br>
                <strong>Notes:</strong> {{ $payment->notes }}
            @endif
        </div>

        <div class="signature-block">
            <table class="signature-table">
                <colgroup>
                    <col style="width: 25%;">
                    <col style="width: 25%;">
                    <col style="width: 25%;">
                    <col style="width: 25%;">
                </colgroup>

                <tr>
                    <td
                        class="signature-card"
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
                            {{ $eatNow->format('d M Y, H:i') }} EAT
                        </div>
                    </td>

                    <td
                        class="signature-card"
                    >
                        <div class="signature-title">
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

                        <div class="signature-line"></div>

                        <div class="signature-name">
                            {{ $authorizedName }}
                        </div>

                        <div class="small-muted">
                            {{ $authorizedTitle }}
                        </div>
                    </td>

                    <td
                        class="signature-card stamp-wrap"
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
                                OFFICIAL<br>
                                RECEIPT<br>
                                STAMP
                            </div>
                        @endif

                        <div class="small-muted">
                            {{ $farmName }}
                        </div>
                    </td>

                    <td
                        class="signature-card"
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
                                    {{ $payment->payment_number ?: '-' }}
                                </div>
                            @endif

                            <div class="receipt-note">
                                Scan to verify receipt metadata
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

                $size = 7.5;

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
                    - 23;

                $canvas->text(
                    $x,
                    $y,
                    $text,
                    $font,
                    $size,
                    [0.42, 0.45, 0.50]
                );
            });
        }
    </script>
</body>
</html>
