@php
    if (!function_exists('pdfImageBase64')) {
        function pdfImageBase64(?string $path): ?string
        {
            if (!$path) {
                return null;
            }

            $cleanPath = preg_replace('#^storage/#', '', ltrim(trim($path), '/'));

            foreach (
                [
                    storage_path('app/public/' . $cleanPath),
                    public_path('storage/' . $cleanPath),
                    public_path($cleanPath),
                ]
                as $fullPath
            ) {
                if (is_file($fullPath)) {
                    $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

                    $mime = match ($ext) {
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

    $farmName = setting('farm.name', 'Lelekwe Farm Limited');
    $farmTagline = setting('farm.tagline', 'Nurturing Nature, Feeding The Future');
    $farmPhone = setting('farm.phone', '+254 743 487 186');
    $farmEmail = setting('farm.email', 'jambo@lelekwefarm.com');
    $farmCounty = setting('farm.county', 'Nakuru County');

    $primaryColor = trim(setting('theme.primary', '#014a12'));
    $secondaryColor = trim(setting('theme.secondary', '#14532d'));
    $successColor = trim(setting('theme.success', '#16a34a'));
    $dangerColor = trim(setting('theme.danger', '#dc2626'));
    $accentColor = trim(setting('theme.accent', '#f59e0b'));

    $logoBase64 = pdfImageBase64(setting('branding.logo_light'));

    $methodLabel = $payment->payment_method_label ?? str($payment->payment_method)->replace('_', ' ')->title();
    $statusLabel = $payment->status_label ?? str($payment->status)->replace('_', ' ')->title();

    $statusColor = match ($payment->status) {
        'successful' => $successColor,
        'pending' => $accentColor,
        'failed', 'cancelled', 'reversed' => $dangerColor,
        default => $primaryColor,
    };

    $mpesaCode = $payment->mpesa_receipt_number ?: $payment->reference_number;

    $verificationText =
        $farmName .
        ' Sales Receipt | Receipt: ' .
        $payment->payment_number .
        ' | Invoice: ' .
        ($invoice?->invoice_number ?? '-') .
        ' | Customer: ' .
        ($customer?->name ?? '-') .
        ' | Amount: ' .
        number_format((float) $payment->amount, 2) .
        ' | Method: ' .
        $methodLabel .
        ' | Ref: ' .
        ($mpesaCode ?: '-') .
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
                    ->generate($verificationText),
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
    <title>{{ $payment->payment_number }}</title>

    <style>
        @page {
            margin: 115px 35px 85px 35px;
        }

        body {
            font-family: Courier, sans-serif;
            font-size: 11px;
            color: #1f2937;
        }

        .watermark {
            position: fixed;
            top: 31%;
            left: 15%;
            width: 70%;
            opacity: 0.04;
            z-index: -1;
            text-align: center;
        }

        .watermark img {
            width: 390px;
        }

        header {
            position: fixed;
            top: -90px;
            left: 0;
            right: 0;
            height: 82px;
            border-bottom: 2px solid {{ $primaryColor }};
        }

        footer {
            position: fixed;
            bottom: -60px;
            left: 0;
            right: 0;
            height: 50px;
            border-top: 1px solid #d1d5db;
            font-size: 9px;
            color: #4b5563;
        }

        .header-table,
        .footer-table,
        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }

        .logo {
            width: 150px;
            max-height: 65px;
            object-fit: contain;
        }

        .company-title {
            text-align: center;
            font-size: 21px;
            font-weight: bold;
            color: {{ $primaryColor }};
        }

        .tagline {
            text-align: center;
            font-size: 10px;
            color: #4b5563;
            font-style: italic;
        }

        .header-right {
            text-align: right;
            font-size: 10px;
            line-height: 1.5;
        }

        .receipt-title {
            margin-bottom: 16px;
        }

        .receipt-title h1 {
            margin: 0;
            font-size: 24px;
            color: #111827;
        }

        .receipt-title p {
            margin: 4px 0 0;
            color: {{ $primaryColor }};
            font-size: 11px;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 9px;
            font-weight: bold;
            color: #fff;
            background: {{ $primaryColor }};
        }

        .card-grid {
            width: 100%;
            display: table;
            border-spacing: 10px 0;
            margin-bottom: 14px;
        }

        .card {
            display: table-cell;
            width: 50%;
            border: 1px solid #dbe4d3;
            border-left: 5px solid {{ $primaryColor }};
            background: #fbfdf9;
            padding: 12px;
            vertical-align: top;
        }

        .card-heading {
            font-size: 12px;
            font-weight: bold;
            color: {{ $primaryColor }};
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .info-row {
            border-bottom: 1px solid #e5e7eb;
            padding: 6px 0;
        }

        .info-row span {
            display: inline-block;
            width: 42%;
            color: #6b7280;
            font-size: 10px;
        }

        .info-row strong {
            color: #111827;
        }

        .amount-box {
            margin-top: 14px;
            border: 2px solid {{ $primaryColor }};
            background: #f0fdf4;
            padding: 16px;
            text-align: center;
        }

        .amount-label {
            text-transform: uppercase;
            color: #4b5563;
            font-size: 11px;
            font-weight: bold;
        }

        .amount-value {
            color: {{ $primaryColor }};
            font-size: 27px;
            font-weight: bold;
            margin-top: 5px;
        }

        .section-title {
            margin-top: 22px;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: bold;
            color: {{ $primaryColor }};
            text-transform: uppercase;
        }

        .items-table,
        .details-table,
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .items-table th,
        .details-table th,
        .summary-table th {
            background: {{ $primaryColor }};
            color: #fff;
            border: 1px solid {{ $primaryColor }};
            padding: 7px;
            font-size: 9.5px;
            text-align: left;
        }

        .items-table td,
        .details-table td,
        .summary-table td {
            border: 1px solid #e5e7eb;
            padding: 7px;
            vertical-align: top;
        }

        .items-table tr:nth-child(even),
        .details-table tr:nth-child(even) {
            background: #fafafa;
        }

        .right {
            text-align: right;
        }

        .payment-highlight {
            border: 1px solid #dbe4d3;
            background: #fbfdf9;
            padding: 10px;
            margin-top: 12px;
        }

        .payment-highlight-title {
            font-size: 11px;
            font-weight: bold;
            color: {{ $secondaryColor }};
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .signature-card {
            border: 1px solid #dbe4d3;
            background: #fbfdf9;
            padding: 12px;
            min-height: 95px;
        }

        .signature-title {
            font-size: 11px;
            font-weight: bold;
            color: {{ $secondaryColor }};
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .signature-line {
            border-top: 1px solid #4b5563;
            margin-top: 18px;
            padding-top: 6px;
        }

        .stamp-circle {
            width: 100px;
            height: 100px;
            border: 1px dashed {{ $primaryColor }};
            border-radius: 50%;
            display: table;
            margin: 0 auto;
        }

        .stamp-text {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
            color: {{ $primaryColor }};
            font-weight: bold;
            line-height: 1.4;
        }

        .qr-box {
            text-align: center;
        }

        .qr-image-wrap {
            width: 100px;
            height: 100px;
            margin: 0 auto 7px;
            border: 2px solid {{ $primaryColor }};
            padding: 4px;
            background: #fff;
        }

        .qr-image {
            width: 90px;
            height: 90px;
        }

        .small-muted {
            color: #6b7280;
            font-size: 9px;
        }

        .section-block {
            margin-top: 26px;
        }
    </style>
</head>

<body>
    <div class="watermark">
        @if ($logoBase64)
            <img src="{{ $logoBase64 }}">
        @endif
    </div>

    <header>
        <table class="header-table">
            <tr>
                <td width="110">
                    @if ($logoBase64)
                        <img src="{{ $logoBase64 }}" class="logo">
                    @endif
                </td>

                <td>
                    <div class="company-title">{{ $farmName }}</div>
                    <div class="tagline">{{ $farmTagline }}</div>
                </td>

                <td class="header-right" width="220">
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
                <td width="33%">Printed {{ $eatNow->format('d M Y, H:i') }} EAT</td>
                <td width="34%" style="text-align:center;">Sales Payment Receipt</td>
                <td width="33%" style="text-align:right;">Generated by {{ $generatedBy->name ?? 'System' }}</td>
            </tr>
        </table>
    </footer>

    <main>
        <div class="receipt-title">
            <h1>Official Payment Receipt</h1>
            <p style="display:flex; align-items:center; gap:6px; margin:4px 0 0;">
                <span>{{ $payment->payment_number }}</span>

                <span>•</span>

                <span>{{ $methodLabel }}</span>

                <span>•</span>

                <span class="badge"
                    style="
            background: {{ $statusColor }};
            display:inline-block;
            vertical-align:middle;
            line-height:1;
            padding:5px 9px;
            position:relative;
            top:-1px;
        ">
                    {{ $statusLabel }}
                </span>
            </p>
        </div>

        <div class="card-grid">
            <div class="card">
                <div class="card-heading">Receipt Details</div>

                <div class="info-row">
                    <span>Receipt No</span>
                    <strong>{{ $payment->payment_number }}</strong>
                </div>

                <div class="info-row">
                    <span>Payment Date</span>
                    <strong>{{ $payment->payment_date?->format('d M Y') }}</strong>
                </div>

                <div class="info-row">
                    <span>Method</span>
                    <strong>{{ $methodLabel }}</strong>
                </div>

                <div class="info-row">
                    <span>Status</span>
                    <strong>{{ $statusLabel }}</strong>
                </div>

                <div class="info-row">
                    <span>M-Pesa Code</span>
                    <strong>{{ $payment->mpesa_receipt_number ?: '-' }}</strong>
                </div>

                <div class="info-row">
                    <span>Reference</span>
                    <strong>{{ $payment->reference_number ?: '-' }}</strong>
                </div>
            </div>

            <div class="card">
                <div class="card-heading">Customer / Invoice</div>

                <div class="info-row">
                    <span>Invoice No</span>
                    <strong>{{ $invoice?->invoice_number ?? '-' }}</strong>
                </div>

                <div class="info-row">
                    <span>Customer</span>
                    <strong>{{ $customer?->name ?? '-' }}</strong>
                </div>

                <div class="info-row">
                    <span>Phone</span>
                    <strong>{{ $payment->paid_by_phone ?: $customer?->phone ?? '-' }}</strong>
                </div>

                <div class="info-row">
                    <span>Paid By</span>
                    <strong>{{ $payment->paid_by_name ?: $customer?->name ?? '-' }}</strong>
                </div>

                <div class="info-row">
                    <span>Invoice Total</span>
                    <strong>KES {{ number_format((float) ($invoice?->grand_total ?? 0), 2) }}</strong>
                </div>

                <div class="info-row">
                    <span>Balance Due</span>
                    <strong>KES {{ number_format((float) ($invoice?->balance_due ?? 0), 2) }}</strong>
                </div>
            </div>
        </div>

        <div class="amount-box">
            <div class="amount-label">Amount Received</div>
            <div class="amount-value">KES {{ number_format((float) $payment->amount, 2) }}</div>
        </div>

        @if ($payment->payment_method === 'mpesa_stk' || $payment->payment_method === 'mpesa_paybill')
            <div class="payment-highlight">
                <div class="payment-highlight-title">M-Pesa Payment Confirmation</div>
                <strong>Transaction Code:</strong> {{ $payment->mpesa_receipt_number ?: '-' }}<br>
                <strong>Phone Number:</strong> {{ $payment->paid_by_phone ?: '-' }}<br>
                <strong>Reference:</strong> {{ $payment->reference_number ?: '-' }}<br>
                <strong>Verified At:</strong>
                {{ $payment->verified_at?->timezone('Africa/Nairobi')->format('d M Y, H:i') ?? '-' }}
            </div>
        @endif

        @if ($invoice?->items?->count())
            <div class="section-title">Animals / Items Bought</div>

            <table class="items-table">
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th width="15%">Tag</th>
                        <th width="17%">Breed</th>
                        <th width="8%">Sex</th>
                        <th width="12%">Weight</th>
                        <th width="13%">Price Mode</th>
                        <th width="15%" class="right">Unit Price</th>
                        <th width="15%" class="right">Line Total</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($invoice->items as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><strong>{{ $item->tag_number ?? ($item->animal?->tag_number ?? '-') }}</strong></td>
                            <td>{{ $item->breed_name ?? ($item->animal?->breed?->breed_name ?? '-') }}</td>
                            <td>{{ $item->sex ?? ($item->animal?->sex ?? '-') }}</td>
                            <td>{{ number_format((float) $item->sale_weight, 2) }} KG</td>
                            <td>{{ strtoupper(str_replace('_', ' ', $item->price_mode ?? '-')) }}</td>
                            <td class="right">KES {{ number_format((float) $item->unit_price, 2) }}</td>
                            <td class="right"><strong>KES {{ number_format((float) $item->line_total, 2) }}</strong>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="section-title">Invoice Payment Summary</div>

        <table class="summary-table">
            <tr>
                <td width="55%">Invoice Grand Total</td>
                <td class="right">KES {{ number_format((float) ($invoice?->grand_total ?? 0), 2) }}</td>
            </tr>
            <tr>
                <td>Total Amount Paid</td>
                <td class="right">KES {{ number_format((float) ($invoice?->amount_paid ?? 0), 2) }}</td>
            </tr>
            <tr>
                <td>Current Receipt Amount</td>
                <td class="right"><strong>KES {{ number_format((float) $payment->amount, 2) }}</strong></td>
            </tr>
            <tr>
                <th>Balance Due</th>
                <th class="right">KES {{ number_format((float) ($invoice?->balance_due ?? 0), 2) }}</th>
            </tr>
        </table>

        <div class="section-title">Verification Details</div>

        <table class="details-table">
            <tr>
                <th width="35%">Field</th>
                <th>Details</th>
            </tr>
            <tr>
                <td>Received By</td>
                <td>{{ $payment->receivedBy?->name ?? '-' }}</td>
            </tr>
            <tr>
                <td>Verified By</td>
                <td>{{ $payment->verifiedBy?->name ?? '-' }}</td>
            </tr>
            <tr>
                <td>Verified At</td>
                <td>{{ $payment->verified_at?->timezone('Africa/Nairobi')->format('d M Y, H:i') ?? '-' }}</td>
            </tr>
            <tr>
                <td>System Generated By</td>
                <td>{{ $generatedBy->name ?? 'System' }} ({{ $generatedByRole }})</td>
            </tr>
            <tr>
                <td>Notes</td>
                <td>{{ $payment->notes ?: '-' }}</td>
            </tr>
        </table>

        <div class="section-block">
            <table class="signature-table">
                <tr>
                    <td style="width: 26%; padding-right: 8px;">
                        <div class="signature-card">
                            <div class="signature-title">Prepared By</div>
                            <strong>{{ $generatedBy->name ?? 'System' }}</strong>
                            <div class="small-muted">{{ $generatedByRole }}</div>
                            <div class="signature-line">Signature</div>
                        </div>
                    </td>

                    <td style="width: 26%; padding-right: 8px;">
                        <div class="signature-card">
                            <div class="signature-title">Received By</div>
                            <strong>{{ $payment->receivedBy?->name ?? '-' }}</strong>
                            <div class="small-muted">Payment receiving officer</div>
                            <div class="signature-line">Signature</div>
                        </div>
                    </td>

                    <td style="width: 22%; padding-right: 8px; text-align:center;">
                        <div class="stamp-circle">
                            <div class="stamp-text">OFFICIAL<br>RECEIPT<br>STAMP</div>
                        </div>
                        <div class="small-muted">Company Stamp</div>
                    </td>

                    <td style="width: 26%;">
                        <div class="qr-box">
                            @if ($qrPng)
                                <div class="qr-image-wrap">
                                    <img src="data:image/png;base64,{{ $qrPng }}" class="qr-image">
                                </div>
                            @else
                                <div class="small-muted">QR not available</div>
                            @endif

                            <div class="small-muted">Scan to verify receipt metadata</div>
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

                $x = ($canvas->get_width() - $width) / 2;
                $y = $canvas->get_height() - 32;

                $canvas->text($x, $y, $text, $font, $size, [0.42, 0.45, 0.50]);
            });
        }
    </script>
</body>

</html>
