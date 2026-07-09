@php
    ini_set('max_execution_time', 300);
    ini_set('memory_limit', '1024M');

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
    $farmCounty = setting('farm.county', 'Nakuru County');

    $primaryColor = trim(setting('theme.primary', '#014a12'));
    $secondaryColor = trim(setting('theme.secondary', '#14532d'));
    $accentColor = trim(setting('theme.accent', '#f59e0b'));
    $dangerColor = trim(setting('theme.danger', '#dc2626'));
    $successColor = trim(setting('theme.success', '#16a34a'));

    $logoBase64 = pdfImageBase64(setting('branding.logo_light'));

    $generatedByName = $generatedBy->name ?? 'System';
    $generatedByRole = $generatedByRole ?? 'User';

    $verificationText =
        $farmName .
        ' Sales Dashboard Report | Period: ' .
        $dateFrom .
        ' to ' .
        $dateTo .
        ' | Sales: ' .
        number_format($totalSales, 2) .
        ' | Paid: ' .
        number_format($amountPaid, 2) .
        ' | Generated: ' .
        $eatNow->format('Y-m-d H:i:s') .
        ' EAT';

    $qrImage = null;

    if (class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
        try {
            $qrImage =
                'data:image/png;base64,' .
                base64_encode(
                    \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                        ->size(120)
                        ->margin(1)
                        ->generate($verificationText),
                );
        } catch (\Throwable $e) {
            $qrImage = null;
        }
    }

    $maxSaleType = max(1, (float) $saleTypeSummary->max('total'));
    $maxPaymentMethod = max(1, (float) $paymentMethodSummary->max('total'));
@endphp

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Sales Dashboard Report</title>

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
            color: #1f2937;
        }

        .watermark {
            position: fixed;
            top: 30%;
            left: 12%;
            width: 75%;
            opacity: 0.045;
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
            font-size: 9px;
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

        .logo {
            width: 175px;
            max-height: 75px;
        }

        .company-title {
            font-size: 22px;
            font-weight: bold;
            color: {{ $primaryColor }};
            text-align: center;
        }

        .tagline {
            font-size: 11px;
            color: #4b5563;
            font-style: italic;
            text-align: center;
        }

        .header-right {
            text-align: right;
            font-size: 10px;
            line-height: 1.5;
        }

        .report-title h1 {
            margin: 0;
            font-size: 19px;
            color: #111827;
        }

        .report-title p {
            margin: 4px 0 0;
            color: {{ $primaryColor }};
            font-size: 10px;
        }

        .section-label {
            font-size: 12px;
            font-weight: bold;
            color: {{ $secondaryColor }};
            margin: 18px 0 8px;
            text-transform: uppercase;
        }

        .summary-card {
            border: 1px solid #dbe4d3;
            border-left: 5px solid {{ $primaryColor }};
            background: #fbfdf9;
            padding: 11px 12px;
            min-height: 75px;
        }

        .summary-card-title {
            font-size: 9px;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: bold;
        }

        .summary-card-value {
            font-size: 18px;
            font-weight: bold;
            color: {{ $primaryColor }};
            margin-top: 5px;
        }

        .summary-card-sub {
            font-size: 8.5px;
            color: #6b7280;
            margin-top: 5px;
        }

        .intelligence-box {
            border: 1px solid #dbe4d3;
            background: #fbfdf9;
            padding: 12px 14px;
            line-height: 1.65;
            margin-bottom: 12px;
        }

        .suggestion-box {
            border: 1px solid #f6dfb3;
            background: #fffaf0;
            padding: 12px 14px;
            line-height: 1.65;
            margin-bottom: 12px;
        }

        .bar-wrap {
            width: 100%;
            height: 9px;
            background: #e5e7eb;
            border-radius: 999px;
            overflow: hidden;
            margin-top: 4px;
        }

        .bar {
            height: 9px;
            background: {{ $primaryColor }};
        }

        table.report {
            margin-top: 8px;
        }

        table.report th {
            background: {{ $primaryColor }};
            color: #fff;
            border: 1px solid {{ $primaryColor }};
            padding: 7px;
            font-size: 8.7px;
            text-align: left;
        }

        table.report td {
            border: 1px solid #e5e7eb;
            padding: 6px;
            vertical-align: top;
            font-size: 8.7px;
        }

        table.report tr:nth-child(even) {
            background: #fafafa;
        }

        .right {
            text-align: right;
        }

        .pill {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 999px;
            font-size: 8px;
            font-weight: bold;
            color: #fff;
            background: {{ $primaryColor }};
        }

        .pill-success {
            background: {{ $successColor }};
        }

        .pill-warning {
            background: {{ $accentColor }};
            color: #111827;
        }

        .pill-danger {
            background: {{ $dangerColor }};
        }

        .signature-card {
            border: 1px solid #dbe4d3;
            background: #fbfdf9;
            padding: 12px;
            min-height: 105px;
        }

        .signature-title {
            font-size: 10px;
            font-weight: bold;
            color: {{ $secondaryColor }};
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .signature-handwritten {
            font-family: "ChopinScript" !important;
            font-size: 25px;
            color: {{ $successColor }};
            letter-spacing: 1px;
        }

        .signature-line {
            border-top: 1px solid #4b5563;
            margin-top: 16px;
            padding-top: 5px;
            font-size: 8.5px;
            color: #6b7280;
        }

        .stamp-circle {
            width: 105px;
            height: 105px;
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
            width: 104px;
            height: 104px;
            margin: 0 auto 6px;
            border: 2px solid {{ $primaryColor }};
            background: #fff;
            padding: 5px;
        }

        .qr-image {
            width: 94px;
            height: 94px;
        }

        .small-muted {
            color: #6b7280;
            font-size: 8.5px;
        }

        .page-break {
            page-break-before: always;
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
                <td style="text-align:left;">Printed {{ $eatNow->format('d M Y, H:i') }} EAT</td>
                <td style="text-align:center;">Sales Dashboard Executive Report</td>
                <td style="text-align:right;">Generated by {{ $generatedByName }} ({{ $generatedByRole }})</td>
            </tr>
            <tr>
                <td colspan="3" style="text-align:center;" class="small-muted">
                    {{ $farmName }} - {{ $farmCounty }} - {{ $farmPhone }} - {{ $farmEmail }}
                </td>
            </tr>
        </table>
    </footer>

    <main>
        <div class="report-title">
            <h1>Sales Dashboard Report</h1>
            <p>
                Period: {{ $dateFrom }} to {{ $dateTo }}
                | Invoices: {{ number_format($invoiceCount) }}
                | Payments: {{ number_format($paymentCount) }}
            </p>
        </div>

        <div class="section-label"> KPI Overview</div>

        <table class="cards-table">
            <tr>
                <td style="width:25%; padding-right:8px;">
                    <div class="summary-card">
                        <div class="summary-card-title">Total Sales</div>
                        <div class="summary-card-value">KES {{ number_format($totalSales, 2) }}</div>
                        <div class="summary-card-sub">Invoice value within selected period</div>
                    </div>
                </td>
                <td style="width:25%; padding-right:8px;">
                    <div class="summary-card">
                        <div class="summary-card-title">Amount Paid</div>
                        <div class="summary-card-value">KES {{ number_format($amountPaid, 2) }}</div>
                        <div class="summary-card-sub">Confirmed successful collections</div>
                    </div>
                </td>
                <td style="width:25%; padding-right:8px;">
                    <div class="summary-card">
                        <div class="summary-card-title">Balance Due</div>
                        <div class="summary-card-value">KES {{ number_format($balanceDue, 2) }}</div>
                        <div class="summary-card-sub">Outstanding customer balances</div>
                    </div>
                </td>
                <td style="width:25%;">
                    <div class="summary-card">
                        <div class="summary-card-title">Collection Rate</div>
                        <div class="summary-card-value">{{ number_format($collectionRate, 1) }}%</div>
                        <div class="summary-card-sub">Paid against invoiced value</div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="section-label">Sales Intelligence</div>

        <div class="intelligence-box">
            @foreach ($smartNotes as $note)
                <div>- {{ $note }}</div>
            @endforeach
        </div>

        <div class="section-label">Suggestions</div>

        <div class="suggestion-box">
            @foreach ($suggestions as $suggestion)
                <div>- {{ $suggestion }}</div>
            @endforeach
        </div>

        <div class="section-label">Sale Type Performance</div>

        <table class="report">
            <thead>
                <tr>
                    <th>Sale Type</th>
                    <th class="right">Invoices</th>
                    <th class="right">Total Sales</th>
                    <th class="right">Paid</th>
                    <th class="right">Balance</th>
                    <th>Performance</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($saleTypeSummary as $row)
                    <tr>
                        <td><span class="pill">{{ $row['type'] }}</span></td>
                        <td class="right">{{ number_format($row['count']) }}</td>
                        <td class="right">KES {{ number_format($row['total'], 2) }}</td>
                        <td class="right">KES {{ number_format($row['paid'], 2) }}</td>
                        <td class="right">KES {{ number_format($row['balance'], 2) }}</td>
                        <td>
                            <div class="bar-wrap">
                                <div class="bar"
                                    style="width: {{ max(3, ($row['total'] / $maxSaleType) * 100) }}%;"></div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">No sale type data available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="section-label">Payment Method Performance</div>

        <table class="report">
            <thead>
                <tr>
                    <th>Payment Method</th>
                    <th class="right">Payments</th>
                    <th class="right">Total Collected</th>
                    <th>Contribution</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($paymentMethodSummary as $row)
                    <tr>
                        <td><span class="pill pill-success">{{ $row['method'] }}</span></td>
                        <td class="right">{{ number_format($row['count']) }}</td>
                        <td class="right">KES {{ number_format($row['total'], 2) }}</td>
                        <td>
                            <div class="bar-wrap">
                                <div class="bar"
                                    style="width: {{ max(3, ($row['total'] / $maxPaymentMethod) * 100) }}%;"></div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">No payment method data available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="section-label">Top Customers</div>

        <table class="report">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Customer</th>
                    <th class="right">Invoices</th>
                    <th class="right">Total Sales</th>
                    <th class="right">Paid</th>
                    <th class="right">Balance</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($topCustomers as $index => $customer)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $customer['name'] }}</td>
                        <td class="right">{{ number_format($customer['invoice_count']) }}</td>
                        <td class="right">KES {{ number_format($customer['total'], 2) }}</td>
                        <td class="right">KES {{ number_format($customer['paid'], 2) }}</td>
                        <td class="right">KES {{ number_format($customer['balance'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">No customer data available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="page-break"></div>

        <div class="section-label">Invoice Register</div>

        <table class="report">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Invoice</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Sale Type</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th class="right">Total</th>
                    <th class="right">Paid</th>
                    <th class="right">Balance</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($invoices as $index => $invoice)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td><strong>{{ $invoice->invoice_number }}</strong></td>
                        <td>{{ $invoice->invoice_date?->format('d M Y') }}</td>
                        <td>{{ $invoice->customer?->name ?? '-' }}</td>
                        <td>{{ str($invoice->sale_type)->replace('_', ' ')->title() }}</td>
                        <td><span class="pill">{{ str($invoice->status)->title() }}</span></td>
                        <td><span class="pill pill-warning">{{ str($invoice->payment_status)->title() }}</span></td>
                        <td class="right">KES {{ number_format((float) $invoice->grand_total, 2) }}</td>
                        <td class="right">KES {{ number_format((float) $invoice->amount_paid, 2) }}</td>
                        <td class="right">KES {{ number_format((float) $invoice->balance_due, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10">No invoices found for the selected period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="section-label">Payment Register</div>

        <table class="report">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Payment No.</th>
                    <th>Invoice</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Method</th>
                    <th>M-Pesa Code</th>
                    <th>Status</th>
                    <th class="right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments as $index => $payment)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td><strong>{{ $payment->payment_number }}</strong></td>
                        <td>{{ $payment->invoice?->invoice_number ?? '-' }}</td>
                        <td>{{ $payment->customer?->name ?? ($payment->invoice?->customer?->name ?? ($payment->paid_by_name ?? '-')) }}
                        </td>
                        <td>{{ $payment->payment_date?->format('d M Y') }}</td>
                        <td>{{ str($payment->payment_method)->replace('_', ' ')->title() }}</td>
                        <td>{{ $payment->mpesa_receipt_number ?: '-' }}</td>
                        <td>
                            <span
                                class="pill {{ $payment->status === 'successful' ? 'pill-success' : 'pill-warning' }}">
                                {{ str($payment->status)->title() }}
                            </span>
                        </td>
                        <td class="right">KES {{ number_format((float) $payment->amount, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">No payments found for the selected period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="section-label">Approval & Verification</div>

        <table class="signature-table">
            <tr>
                <td style="width:26%; padding-right:8px;">
                    <div class="signature-card">
                        <div class="signature-title">Prepared By</div>
                        <strong>{{ $generatedByName }}</strong>
                        <div class="small-muted">{{ $generatedByRole }}</div>
                        <div class="signature-line">Generated {{ $eatNow->format('d M Y, H:i') }} EAT</div>
                    </div>
                </td>

                <td style="width:28%; padding-right:8px;">
                    <div class="signature-card">
                        <div class="signature-title">Authorized Signature</div>
                        <div class="signature-handwritten">
                            Digitally.Approved!
                        </div>
                        <div class="signature-line">{{ $farmName }} Management Approval</div>
                    </div>
                </td>

                <td style="width:22%; padding-right:8px; text-align:center;">
                    <div class="stamp-circle">
                        <div class="stamp-text">OFFICIAL<br>REPORT<br>STAMP</div>
                    </div>
                    <div class="small-muted">Company Stamp</div>
                </td>

                <td style="width:24%;">
                    <div class="qr-box">
                        @if ($qrImage)
                            <div class="qr-image-wrap">
                                <img src="{{ $qrImage }}" class="qr-image">
                            </div>
                        @else
                            <div class="small-muted">QR not available</div>
                        @endif

                        <div class="small-muted">Scan to verify report metadata</div>
                    </div>
                </td>
            </tr>
        </table>
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
