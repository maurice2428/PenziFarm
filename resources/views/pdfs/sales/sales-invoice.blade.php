@php
    use App\Models\Settings\PaymentSetting;

    if (! function_exists('pdfImageBase64')) {
        function pdfImageBase64(?string $path): ?string
        {
            if (! $path) {
                return null;
            }

            $cleanPath = trim((string) $path);
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

    $paymentSettings = PaymentSetting::current();

    $eatNow = now('Africa/Nairobi');

    $farmName = setting('farm.name', 'Lelekwe Farm Limited');
    $farmTagline = setting('farm.tagline', 'Nurturing Nature, Feeding The Future');
    $farmPhone = setting('farm.phone', '+254 743 487 186');
    $farmEmail = setting('farm.email', 'jambo@lelekwefarm.com');
    $farmCounty = setting('farm.county', 'Nakuru County');
    $farmAddress = setting('farm.address', $farmCounty);

    $primaryColor = trim(setting('theme.primary', '#014a12'));
    $secondaryColor = trim(setting('theme.secondary', '#14532d'));
    $accentColor = trim(setting('theme.accent', '#f59e0b'));
    $dangerColor = trim(setting('theme.danger', '#dc2626'));
    $successColor = trim(setting('theme.success', '#16a34a'));

    $logoBase64 = pdfImageBase64(setting('branding.logo_light'));

    $mpesaLogoBase64 = pdfImageBase64(data_get($paymentSettings, 'mpesa_logo'));
    $bankLogoBase64 = pdfImageBase64(data_get($paymentSettings, 'bank_logo'));

    /*$signatureBase64 = pdfImageBase64(
        data_get($paymentSettings, 'invoice_signature_path')
        ?: data_get($paymentSettings, 'signature_path')
        ?: data_get($paymentSettings, 'authorized_signature_path')
        ?: setting('branding.signature')
    );

    $stampBase64 = pdfImageBase64(
        data_get($paymentSettings, 'invoice_stamp_path')
        ?: data_get($paymentSettings, 'stamp_path')
        ?: data_get($paymentSettings, 'official_stamp_path')
        ?: setting('branding.stamp')
    );*/
    $signatureBase64 = pdfImageBase64(
    data_get($paymentSettings, 'authorized_signature_image')
    ?: data_get($paymentSettings, 'signature_path')
    ?: data_get($paymentSettings, 'authorized_signature_path')
    ?: setting('branding.signature')
);

$stampBase64 = pdfImageBase64(
    data_get($paymentSettings, 'payment_stamp_image')
    ?: data_get($paymentSettings, 'stamp_path')
    ?: data_get($paymentSettings, 'official_stamp_path')
    ?: setting('branding.stamp')
);

    $generatedByName = $generatedBy->name ?? 'System';
    $generatedByRole = $generatedByRole ?? 'User';

    $paymentStatus = strtolower((string) ($invoice->payment_status_label ?? ''));
    $paymentBadgeClass = str_contains($paymentStatus, 'paid') && ! str_contains($paymentStatus, 'partial')
        ? 'badge-success'
        : (str_contains($paymentStatus, 'partial') ? 'badge-warning' : 'badge-danger');

    $verificationText =
        $farmName .
        ' Sales Invoice | Invoice: ' .
        $invoice->invoice_number .
        ' | Customer: ' .
        ($invoice->customer?->name ?? '-') .
        ' | Total: ' .
        number_format((float) $invoice->grand_total, 2) .
        ' | Generated: ' .
        $eatNow->format('Y-m-d H:i:s') .
        ' EAT';

    $qrPng = null;

    if (class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
        try {
            $qrPng = base64_encode(
                \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                    ->size(120)
                    ->margin(1)
                    ->generate($verificationText)
            );
        } catch (\Throwable $e) {
            $qrPng = null;
        }
    }
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $invoice->invoice_number }}</title>

    <style>
        @page {
            margin: 118px 34px 92px 34px;
        }

        body {
            margin: 0;
            font-family: Courier, monospace;
            font-size: 10.5px;
            color: #1f2937;
            background: #ffffff;
        }

        .watermark {
            position: fixed;
            top: 30%;
            left: 12%;
            width: 76%;
            opacity: 0.035;
            z-index: -10;
            text-align: center;
        }

        .watermark img {
            width: 430px;
        }

        header {
            position: fixed;
            top: -96px;
            left: 0;
            right: 0;
            height: 88px;
            border-bottom: 3px solid {{ $primaryColor }};
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
        .invoice-grid,
        .kpi-table,
        .items,
        .totals,
        .payment-table,
        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: middle;
        }

        .logo {
            width: 145px;
            max-height: 72px;
            object-fit: contain;
        }

        .company-title {
            text-align: center;
            font-size: 15px;
            font-weight: bold;
            color: {{ $primaryColor }};
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .tagline {
            text-align: center;
            margin-top: 3px;
            font-size: 9.5px;
            color: #4b5563;
            font-style: italic;
        }

        .header-right {
            width: 230px;
            text-align: right;
            font-size: 9px;
            line-height: 1.55;
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
            font-size: 8.5px;
        }

        .invoice-top {
            margin-bottom: 14px;
            border: 1px solid #dbe4d3;
            border-left: 7px solid {{ $primaryColor }};
            background: #fbfdf9;
            padding: 12px 14px;
        }

        .invoice-title-table {
            width: 100%;
            border-collapse: collapse;
        }

        .invoice-title {
            margin: 0;
            color: #111827;
            font-size: 24px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .8px;
        }

        .invoice-subtitle {
            margin-top: 4px;
            color: {{ $primaryColor }};
            font-size: 10px;
            font-weight: bold;
        }

        .invoice-total-box {
            text-align: right;
        }

        .invoice-total-label {
            color: #6b7280;
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .invoice-total-value {
            margin-top: 3px;
            color: {{ $primaryColor }};
            font-size: 20px;
            font-weight: bold;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 999px;
            background: {{ $primaryColor }};
            color: #ffffff;
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-success {
            background: {{ $successColor }};
        }

        .badge-warning {
            background: {{ $accentColor }};
            color: #111827;
        }

        .badge-danger {
            background: {{ $dangerColor }};
        }

        .invoice-grid {
            border-spacing: 10px 0;
            border-collapse: separate;
            margin-bottom: 12px;
        }

        .invoice-card {
            width: 50%;
            vertical-align: top;
            border: 1px solid #dbe4d3;
            background: #ffffff;
            padding: 11px;
        }

        .card-heading {
            margin-bottom: 8px;
            color: {{ $primaryColor }};
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .info-row {
            border-bottom: 1px solid #edf2ea;
            padding: 5px 0;
        }

        .info-label {
            display: inline-block;
            width: 38%;
            color: #6b7280;
            font-size: 9px;
        }

        .info-value {
            color: #111827;
            font-weight: bold;
        }

        .kpi-table {
            border-spacing: 8px 0;
            border-collapse: separate;
            margin: 12px 0 14px;
        }

        .kpi-card {
            width: 25%;
            border: 1px solid #dbe4d3;
            border-top: 4px solid {{ $primaryColor }};
            background: #fbfdf9;
            padding: 9px 10px;
            text-align: center;
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
            font-size: 15px;
            font-weight: bold;
        }

        .items {
            margin-top: 10px;
            border-collapse: collapse;
        }

        .items th {
            border: 1px solid {{ $primaryColor }};
            background: {{ $primaryColor }};
            color: #ffffff;
            padding: 7px;
            font-size: 9.5px;
            text-align: left;
            text-transform: uppercase;
        }

        .items td {
            border: 1px solid #e5e7eb;
            padding: 7px;
            vertical-align: top;
        }

        .items tr:nth-child(even) {
            background: #fafafa;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .totals-wrap {
            width: 43%;
            margin-top: 14px;
            margin-left: auto;
        }

        .totals td,
        .totals th {
            border: 1px solid #e5e7eb;
            padding: 7px 8px;
        }

        .totals th {
            background: {{ $primaryColor }};
            color: #ffffff;
            font-size: 11px;
        }

        .balance-row td {
            background: #fff7ed;
            font-weight: bold;
            color: #111827;
        }

        .notes-box {
            margin-top: 14px;
            border: 1px solid #e5e7eb;
            border-left: 5px solid {{ $accentColor }};
            background: #fffdf7;
            padding: 10px;
            line-height: 1.55;
        }

        .payment-section {
            clear: both;
            margin-top: 16px;
            border: 1px solid #dbe4d3;
            background: #f9fafb;
            padding: 11px;
        }

        .section-title {
            color: {{ $primaryColor }};
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .payment-note {
            margin-bottom: 10px;
            border: 1px solid #e5e7eb;
            background: #ffffff;
            padding: 8px;
            font-size: 9.5px;
            line-height: 1.45;
        }

        .payment-table {
            border-spacing: 10px 0;
            border-collapse: separate;
        }

        .payment-table td {
            width: 50%;
            vertical-align: top;
        }

        .payment-card {
            min-height: 105px;
            border: 1px solid #dbe4d3;
            background: #ffffff;
            padding: 9px;
        }

        .payment-logo {
            max-width: 115px;
            max-height: 30px;
            margin-bottom: 5px;
        }

        .payment-card-title {
            margin-bottom: 6px;
            color: {{ $secondaryColor }};
            font-size: 9.5px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .signature-block {
            margin-top: 20px;
        }

        .signature-table {
            border-spacing: 8px 0;
            border-collapse: separate;
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
            font-size: 12px;
            font-weight: bold;
        }

        .signature-line {
            margin-top: 14px;
            border-top: 1px solid #4b5563;
            padding-top: 5px;
        }

        .signature-img {
            max-width: 145px;
            max-height: 54px;
            display: block;
            margin: 3px 0 4px;
        }

        .signature-fallback {
            margin: 7px 0;
            color: {{ $successColor }};
            font-size: 15px;
            font-weight: bold;
            font-style: italic;
        }

        .stamp-wrap {
            text-align: center;
        }

        .stamp-img {
            max-width: 112px;
            max-height: 92px;
            display: block;
            margin: 0 auto 5px;
        }

        .stamp-circle {
            width: 96px;
            height: 96px;
            margin: 0 auto 6px;
            border: 2px dashed {{ $primaryColor }};
            border-radius: 50%;
            display: table;
        }

        .stamp-text {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
            color: {{ $primaryColor }};
            font-size: 10px;
            font-weight: bold;
            line-height: 1.35;
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

        .qr-image {
            width: 86px;
            height: 86px;
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
                    Sales Invoice • {{ $invoice->invoice_number }}
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
        <div class="invoice-top">
            <table class="invoice-title-table">
                <tr>
                    <td>
                        <h1 class="invoice-title">Sales Invoice</h1>
                        <div class="invoice-subtitle">
                            {{ $invoice->invoice_number }}
                            • {{ $invoice->sale_type_label }}
                            • <span class="badge {{ $paymentBadgeClass }}">{{ $invoice->payment_status_label }}</span>
                        </div>
                    </td>

                    <td class="invoice-total-box" width="230">
                        <div class="invoice-total-label">Grand Total</div>
                        <div class="invoice-total-value">
                            KES {{ number_format((float) $invoice->grand_total, 2) }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <table class="invoice-grid">
            <tr>
                <td class="invoice-card">
                    <div class="card-heading">Invoice Details</div>

                    <div class="info-row">
                        <span class="info-label">Invoice No</span>
                        <span class="info-value">{{ $invoice->invoice_number }}</span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Invoice Date</span>
                        <span class="info-value">{{ $invoice->invoice_date?->format('d M Y') }}</span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Due Date</span>
                        <span class="info-value">{{ $invoice->due_date?->format('d M Y') ?? '-' }}</span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Status</span>
                        <span class="badge">{{ $invoice->status_label }}</span>
                    </div>
                </td>

                <td class="invoice-card">
                    <div class="card-heading">Customer Details</div>

                    <div class="info-row">
                        <span class="info-label">Customer</span>
                        <span class="info-value">{{ $invoice->customer?->name ?? '-' }}</span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Phone</span>
                        <span class="info-value">{{ $invoice->customer?->phone ?? '-' }}</span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Email</span>
                        <span class="info-value">{{ $invoice->customer?->email ?? '-' }}</span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">KRA PIN</span>
                        <span class="info-value">{{ $invoice->customer?->kra_pin ?? '-' }}</span>
                    </div>
                </td>
            </tr>
        </table>

        <table class="kpi-table">
            <tr>
                <td class="kpi-card">
                    <div class="kpi-label">Animals Sold</div>
                    <div class="kpi-value">{{ number_format((float) $invoice->total_animals) }}</div>
                </td>

                <td class="kpi-card">
                    <div class="kpi-label">Subtotal</div>
                    <div class="kpi-value">KES {{ number_format((float) $invoice->subtotal, 2) }}</div>
                </td>

                <td class="kpi-card">
                    <div class="kpi-label">Amount Paid</div>
                    <div class="kpi-value">KES {{ number_format((float) $invoice->amount_paid, 2) }}</div>
                </td>

                <td class="kpi-card">
                    <div class="kpi-label">Balance Due</div>
                    <div class="kpi-value">KES {{ number_format((float) $invoice->balance_due, 2) }}</div>
                </td>
            </tr>
        </table>

        <table class="items">
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="14%">Tag</th>
                    <th width="18%">Breed</th>
                    <th width="8%">Sex</th>
                    <th width="16%">Price Mode</th>
                    <th width="15%" class="right">Price</th>
                    <th width="12%" class="right">Premium</th>
                    <th width="12%" class="right">Total</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($invoice->items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td><strong>{{ $item->tag_number }}</strong></td>
                        <td>{{ $item->breed_name }}</td>
                        <td>{{ $item->sex }}</td>
                        <td>{{ strtoupper(str_replace('_', ' ', (string) $item->price_mode)) }}</td>
                        <td class="right">KES {{ number_format((float) $item->unit_price, 2) }}</td>
                        <td class="right">KES {{ number_format((float) $item->breeder_premium_amount, 2) }}</td>
                        <td class="right"><strong>KES {{ number_format((float) $item->line_total, 2) }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals-wrap">
            <table class="totals">
                <tr>
                    <td>Subtotal</td>
                    <td class="right">KES {{ number_format((float) $invoice->subtotal, 2) }}</td>
                </tr>

                <tr>
                    <td>Discount</td>
                    <td class="right">KES {{ number_format((float) $invoice->discount_amount, 2) }}</td>
                </tr>

                <tr>
                    <td>Tax</td>
                    <td class="right">KES {{ number_format((float) $invoice->tax_amount, 2) }}</td>
                </tr>

                <tr>
                    <td>Other Charges</td>
                    <td class="right">KES {{ number_format((float) $invoice->other_charges_amount, 2) }}</td>
                </tr>

                <tr>
                    <th>Grand Total</th>
                    <th class="right">KES {{ number_format((float) $invoice->grand_total, 2) }}</th>
                </tr>

                <tr>
                    <td>Amount Paid</td>
                    <td class="right">KES {{ number_format((float) $invoice->amount_paid, 2) }}</td>
                </tr>

                <tr class="balance-row">
                    <td>Balance Due</td>
                    <td class="right">KES {{ number_format((float) $invoice->balance_due, 2) }}</td>
                </tr>
            </table>
        </div>

        <div style="clear: both;"></div>

        @if ($invoice->notes || $invoice->terms || $invoice->other_charges_description)
            <div class="notes-box">
                @if ($invoice->other_charges_description)
                    <strong>Other Charges:</strong> {{ $invoice->other_charges_description }}<br>
                @endif

                @if ($invoice->notes)
                    <strong>Notes:</strong> {{ $invoice->notes }}<br>
                @endif

                @if ($invoice->terms)
                    <strong>Terms:</strong> {{ $invoice->terms }}
                @endif
            </div>
        @endif

        <div class="payment-section">
            <div class="section-title">Payment Instructions</div>

            @if ($paymentSettings?->invoice_payment_instructions)
                <div class="payment-note">
                    {{ $paymentSettings->invoice_payment_instructions }}
                </div>
            @endif

            <table class="payment-table">
                <tr>
                    @if ($paymentSettings?->enable_mpesa_paybill)
                        <td>
                            <div class="payment-card">
                                @if ($mpesaLogoBase64)
                                    <img src="{{ $mpesaLogoBase64 }}" class="payment-logo" alt="M-Pesa">
                                @endif

                                <div class="payment-card-title">M-Pesa Payment</div>
                                <div><strong>Paybill:</strong> {{ $paymentSettings->mpesa_paybill_number ?: '-' }}</div>
                                <div><strong>Till:</strong> {{ $paymentSettings->mpesa_till_number ?: '-' }}</div>
                                <div><strong>Account:</strong> {{ $invoice->invoice_number }}</div>
                                <div><strong>Name:</strong> {{ $paymentSettings->mpesa_account_name ?: '-' }}</div>
                            </div>
                        </td>
                    @endif

                    @if ($paymentSettings?->enable_bank_payment)
                        <td>
                            <div class="payment-card">
                                @if ($bankLogoBase64)
                                    <img src="{{ $bankLogoBase64 }}" class="payment-logo" alt="Bank">
                                @endif

                                <div class="payment-card-title">Bank Transfer</div>
                                <div><strong>Bank:</strong> {{ $paymentSettings->bank_name ?: '-' }}</div>
                                <div><strong>Branch:</strong> {{ $paymentSettings->bank_branch ?: '-' }}</div>
                                <div><strong>Account No:</strong> {{ $paymentSettings->bank_account_number ?: '-' }}</div>
                                <div><strong>Account Name:</strong> {{ $paymentSettings->bank_account_name ?: '-' }}</div>
                                <div><strong>Paybill:</strong> {{ $paymentSettings->bank_paybill_number ?: '-' }}</div>
                                <div><strong>Reference:</strong> {{ $paymentSettings->bank_account_reference ?: $invoice->invoice_number }}</div>
                            </div>
                        </td>
                    @endif
                </tr>
            </table>
        </div>

        <div class="signature-block">
            <table class="signature-table">
                <tr>
                    <td class="signature-card" style="width: 26%;">
                        <div class="signature-title">Prepared By</div>
                        <div class="signature-name">{{ $generatedByName }}</div>
                        <div class="small-muted">{{ $generatedByRole }}</div>
                        <div class="signature-line"></div>
                        <div class="small-muted">Generated {{ $eatNow->format('d M Y, H:i') }} EAT</div>
                    </td>

                    <td class="signature-card" style="width: 28%;">
                        <div class="signature-title">Authorized Signature</div>

                        @if ($signatureBase64)
                            <img src="{{ $signatureBase64 }}" class="signature-img" alt="Signature">
                        @else
                            <div class="signature-fallback">Digitally Approved</div>
                        @endif

                        <div class="small-muted">Approved {{ $eatNow->format('d M Y, H:i') }} EAT</div>
                        <div class="signature-line"></div>
                        <div class="small-muted">{{ $farmName }} Management Approval</div>
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

                    <td class="signature-card" style="width: 24%;">
                        <div class="qr-box">
                            <div class="signature-title">Verification</div>

                            @if (! empty($qrPng))
                                <div class="qr-image-wrap">
                                    <img src="data:image/png;base64,{{ $qrPng }}" class="qr-image" alt="QR Code">
                                </div>
                            @else
                                <div class="qr-fallback">
                                    <span class="small-muted">QR not available</span>
                                </div>
                            @endif

                            <div class="small-muted">Scan to verify invoice metadata</div>
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
                $size = 8.5;

                $text = "Page {$pageNumber} of {$pageCount}";
                $width = $fontMetrics->getTextWidth($text, $font, $size);

                $x = ($canvas->get_width() - $width) / 2;
                $y = $canvas->get_height() - 30;

                $canvas->text($x, $y, $text, $font, $size, [0.42, 0.45, 0.50]);
            });
        }
    </script>
</body>
</html>
