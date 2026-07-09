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
            $cleanPath = preg_replace('#^public/#', '', $cleanPath);
            $cleanPath = ltrim($cleanPath, '/');

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

    $logoBase64 = pdfImageBase64(setting('branding.logo_light'));

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

    $generatedByName = $generatedBy?->name ?? 'System';
    $generatedByRole = $generatedByRole ?? 'User';

    $gatePassNumber = 'GP-' . $invoice->invoice_number;

    $verificationText = $farmName
        . ' Gate Pass | Gate Pass: ' . $gatePassNumber
        . ' | Invoice: ' . $invoice->invoice_number
        . ' | Customer: ' . ($invoice->customer?->name ?? '-')
        . ' | Animals: ' . $invoice->items->pluck('tag_number')->filter()->implode(', ')
        . ' | Generated: ' . $eatNow->format('Y-m-d H:i:s') . ' EAT';

    $qrPng = null;

    if (class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
        try {
            $qrPng = base64_encode(
                \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                    ->size(115)
                    ->margin(1)
                    ->generate($verificationText)
            );
        } catch (\Throwable) {
            $qrPng = null;
        }
    }
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $gatePassNumber }}</title>

    <style>
        @page {
            margin: 105px 34px 85px 34px;
        }

        body {
            margin: 0;
            font-family: Courier, monospace;
            font-size: 10.5px;
            color: #1f2937;
        }

        .watermark {
            position: fixed;
            top: 30%;
            left: 13%;
            width: 74%;
            text-align: center;
            opacity: .04;
            z-index: -10;
        }

        .watermark img {
            width: 430px;
        }

        header {
            position: fixed;
            top: -85px;
            left: 0;
            right: 0;
            height: 78px;
            border-bottom: 3px solid {{ $primaryColor }};
        }

        footer {
            position: fixed;
            bottom: -62px;
            left: 0;
            right: 0;
            height: 52px;
            border-top: 1px solid #d1d5db;
            color: #6b7280;
            font-size: 8.8px;
        }

        .header-table,
        .footer-table,
        .info-grid,
        .items,
        .signatures {
            width: 100%;
            border-collapse: collapse;
        }

        .logo {
            width: 130px;
            max-height: 66px;
            object-fit: contain;
        }

        .company-title {
            text-align: center;
            color: {{ $primaryColor }};
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .tagline {
            text-align: center;
            margin-top: 3px;
            color: #4b5563;
            font-size: 9px;
            font-style: italic;
        }

        .header-right {
            width: 220px;
            text-align: right;
            font-size: 8.8px;
            line-height: 1.55;
        }

        .gate-top {
            border: 1px solid #dbe4d3;
            border-left: 8px solid {{ $primaryColor }};
            background: #fbfdf9;
            padding: 13px 14px;
            margin-bottom: 14px;
        }

        .gate-title-table {
            width: 100%;
            border-collapse: collapse;
        }

        .gate-title {
            color: #111827;
            font-size: 25px;
            font-weight: bold;
            letter-spacing: .7px;
            text-transform: uppercase;
        }

        .gate-subtitle {
            margin-top: 4px;
            color: {{ $primaryColor }};
            font-size: 10px;
            font-weight: bold;
        }

        .gate-number-box {
            text-align: right;
        }

        .gate-number-label {
            color: #6b7280;
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .gate-number-value {
            margin-top: 4px;
            color: {{ $primaryColor }};
            font-size: 18px;
            font-weight: bold;
        }

        .info-grid {
            border-spacing: 10px 0;
            border-collapse: separate;
            margin-bottom: 14px;
        }

        .info-card {
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

        .notice {
            margin-bottom: 14px;
            border: 1px solid #f5d28a;
            border-left: 6px solid {{ $accentColor }};
            background: #fffaf0;
            padding: 10px;
            line-height: 1.5;
        }

        .items {
            border-collapse: collapse;
            margin-top: 8px;
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

        .signature-block {
            margin-top: 22px;
        }

        .signatures {
            border-spacing: 8px 0;
            border-collapse: separate;
        }

        .signature-card {
            width: 25%;
            min-height: 110px;
            border: 1px solid #dbe4d3;
            background: #fbfdf9;
            padding: 10px;
            vertical-align: top;
            text-align: center;
        }

        .signature-title {
            color: {{ $secondaryColor }};
            font-size: 9.5px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .signature-line {
            margin-top: 35px;
            border-top: 1px solid #4b5563;
            padding-top: 5px;
            color: #6b7280;
            font-size: 8.5px;
        }

        .signature-img {
            max-width: 132px;
            max-height: 50px;
            margin: 8px auto 4px;
            display: block;
        }

        .stamp-img {
            max-width: 108px;
            max-height: 88px;
            margin: 4px auto;
            display: block;
        }

        .stamp-circle {
            width: 88px;
            height: 88px;
            margin: 5px auto 4px;
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
            line-height: 1.25;
        }

        .qr-image-wrap,
        .qr-fallback {
            width: 88px;
            height: 88px;
            margin: 5px auto 4px;
            border: 2px solid {{ $primaryColor }};
            background: #ffffff;
            padding: 4px;
        }

        .qr-image {
            width: 78px;
            height: 78px;
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
                <td width="145">
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
                <td class="footer-left">Printed {{ $eatNow->format('d M Y, H:i') }} EAT</td>
                <td class="footer-center">{{ $gatePassNumber }}</td>
                <td class="footer-right">Created by {{ $generatedByName }} ({{ $generatedByRole }})</td>
            </tr>
        </table>
    </footer>

    <main>
        <div class="gate-top">
            <table class="gate-title-table">
                <tr>
                    <td>
                        <div class="gate-title">Animal Gate Pass</div>
                        <div class="gate-subtitle">
                            Invoice {{ $invoice->invoice_number }} • {{ $invoice->customer?->name ?? '-' }}
                        </div>
                    </td>

                    <td class="gate-number-box" width="220">
                        <div class="gate-number-label">Gate Pass No.</div>
                        <div class="gate-number-value">{{ $gatePassNumber }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <table class="info-grid">
            <tr>
                <td class="info-card">
                    <div class="card-heading">Customer / Destination</div>

                    <div class="info-row">
                        <span class="info-label">Customer</span>
                        <span class="info-value">{{ $invoice->customer?->name ?? '-' }}</span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Phone</span>
                        <span class="info-value">{{ $invoice->customer?->phone ?? '-' }}</span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Location</span>
                        <span class="info-value">
                            {{ collect([$invoice->customer?->town, $invoice->customer?->county, $invoice->customer?->country])->filter()->implode(', ') ?: '-' }}
                        </span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Address</span>
                        <span class="info-value">{{ $invoice->customer?->address ?? '-' }}</span>
                    </div>
                </td>

                <td class="info-card">
                    <div class="card-heading">Movement Details</div>

                    <div class="info-row">
                        <span class="info-label">Invoice No</span>
                        <span class="info-value">{{ $invoice->invoice_number }}</span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Invoice Date</span>
                        <span class="info-value">{{ $invoice->invoice_date?->format('d M Y') }}</span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Released At</span>
                        <span class="info-value">{{ $eatNow->format('d M Y, H:i') }} EAT</span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Animals</span>
                        <span class="info-value">{{ number_format($invoice->items->count()) }}</span>
                    </div>
                </td>
            </tr>
        </table>

        <div class="notice">
            This gate pass authorizes the listed animals to leave the farm after sale confirmation.
            Security should verify animal tags, invoice number, customer name, and approval section before release.
        </div>

        <table class="items">
            <thead>
                <tr>
                    <th width="6%">#</th>
                    <th width="20%">Animal Tag</th>
                    <th width="24%">Breed</th>
                    <th width="12%">Sex</th>
                    <th width="18%">Sale Type</th>
                    <th width="20%">Remarks</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($invoice->items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td><strong>{{ $item->tag_number }}</strong></td>
                        <td>{{ $item->breed_name }}</td>
                        <td>{{ $item->sex }}</td>
                        <td>{{ strtoupper(str_replace('_', ' ', (string) $invoice->sale_type)) }}</td>
                        <td>{{ $item->remarks ?: 'Released against invoice' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="signature-block">
            <table class="signatures">
                <tr>
                    <td class="signature-card">
                        <div class="signature-title">Prepared By</div>
                        <div style="margin-top: 8px; font-weight: bold;">{{ $generatedByName }}</div>
                        <div style="font-size: 8.5px; color: #6b7280;">{{ $generatedByRole }}</div>
                        <div class="signature-line">Prepared Signature</div>
                    </td>

                    <td class="signature-card">
                        <div class="signature-title">Authorized Signature</div>

                        @if ($signatureBase64)
                            <img src="{{ $signatureBase64 }}" class="signature-img" alt="Signature">
                        @else
                            <div class="signature-line">Authorized Signature</div>
                        @endif

                        <div style="font-size: 8.5px; color: #6b7280;">Management Approval</div>
                    </td>

                    <td class="signature-card">
                        <div class="signature-title">Official Stamp</div>

                        @if ($stampBase64)
                            <img src="{{ $stampBase64 }}" class="stamp-img" alt="Stamp">
                        @else
                            <div class="stamp-circle">
                                <div class="stamp-text">OFFICIAL<br>STAMP</div>
                            </div>
                        @endif
                    </td>

                    <td class="signature-card">
                        <div class="signature-title">Verification</div>

                        @if (! empty($qrPng))
                            <div class="qr-image-wrap">
                                <img src="data:image/png;base64,{{ $qrPng }}" class="qr-image" alt="QR">
                            </div>
                        @else
                            <div class="qr-fallback">QR</div>
                        @endif

                        <div style="font-size: 8.5px; color: #6b7280;">Scan to verify</div>
                    </td>
                </tr>
            </table>
        </div>
    </main>
</body>
</html>
