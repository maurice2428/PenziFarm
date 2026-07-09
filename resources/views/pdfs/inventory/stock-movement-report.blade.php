@php
    if (!function_exists('stockPdfImageBase64')) {
        function stockPdfImageBase64(?string $path): ?string
        {
            if (!$path) {
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

    if (!function_exists('stockReportDate')) {
        function stockReportDate($date, string $format = 'd M Y'): string
        {
            if (!$date) {
                return '-';
            }

            try {
                return \Carbon\Carbon::parse($date)->format($format);
            } catch (\Throwable $e) {
                return '-';
            }
        }
    }

    if (!function_exists('stockReportMoney')) {
        function stockReportMoney($amount): string
        {
            return 'KES ' . number_format((float) $amount, 2);
        }
    }

    if (!function_exists('stockReportQty')) {
        function stockReportQty($amount): string
        {
            return number_format((float) $amount, 3);
        }
    }

    $farmName = setting('farm.name', config('app.name', 'Lelekwe Farms Limited'));
    $farmTagline = setting('farm.tagline', 'Nurturing Nature, Feeding The Future');
    $farmPhone = setting('farm.phone', '+254 743 487 186');
    $farmEmail = setting('farm.email', 'info@lelekwefarms.co.ke');
    $farmCounty = setting('farm.county', 'Kenya');

    $primaryColor = setting('theme.primary', '#008f00');
    $secondaryColor = setting('theme.secondary', '#111827');
    $accentColor = setting('theme.accent', '#f59e0b');
    $dangerColor = setting('theme.danger', '#dc2626');
    $successColor = setting('theme.success', '#16a34a');

    $logoBase64 = stockPdfImageBase64(
        setting('branding.logo_light') ?: setting('branding.logo') ?: setting('farm.logo'),
    );

    $generatedAt = now('Africa/Nairobi');
    $generatedByName = $generatedBy?->name ?? 'System';
    $generatedByRole =
        isset($generatedBy) && method_exists($generatedBy, 'getRoleNames')
            ? ($generatedBy->getRoleNames()->first() ?:
            'User')
            : 'User';

    $stockInQty = $movements->where('direction', 'in')->sum('quantity');
    $stockOutQty = $movements->where('direction', 'out')->sum('quantity');
    $stockInValue = $movements->where('direction', 'in')->sum('total_cost');
    $stockOutValue = $movements->where('direction', 'out')->sum('total_cost');
    $stockValue = $movements->sum('total_cost');
    $netQuantity = $stockInQty - $stockOutQty;
    $periodFrom = $from ? stockReportDate($from) : 'Beginning';
    $periodTo = $to ? stockReportDate($to) : 'Today';
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Stock Movement Report</title>

    <style>
        @page {
            margin: 118px 30px 88px 30px;
        }

        body {
            font-family: Courier, sans-serif;
            font-size: 9px;
            color: #111827;
            position: relative;
        }

        .watermark {
            position: fixed;
            top: 28%;
            left: 14%;
            width: 72%;
            opacity: 0.045;
            z-index: -1;
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
            width: 135px;
            text-align: left;
        }

        .header-center {
            text-align: center;
        }

        .header-right {
            width: 230px;
            text-align: right;
            font-size: 9px;
            line-height: 1.45;
            color: #374151;
        }

        .logo {
            width: 112px;
            max-height: 72px;
            object-fit: contain;
        }

        .company-title {
            font-size: 21px;
            font-weight: bold;
            color: {{ $primaryColor }};
            line-height: 1.15;
        }

        .tagline {
            margin-top: 3px;
            font-size: 10px;
            color: #4b5563;
            font-style: italic;
        }

        footer {
            position: fixed;
            bottom: -66px;
            left: 0;
            right: 0;
            height: 54px;
            border-top: 1px solid #d1d5db;
            font-size: 8.5px;
            color: #4b5563;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
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

        .report-title {
            margin-bottom: 12px;
        }

        .report-title h1 {
            margin: 0;
            font-size: 18px;
            color: #111827;
        }

        .report-title p {
            margin: 4px 0 0 0;
            color: {{ $primaryColor }};
            font-size: 9.5px;
            line-height: 1.45;
        }

        .executive-strip {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .executive-strip td {
            vertical-align: top;
        }

        .executive-card {
            border: 1px solid #dbe4d3;
            background: #fbfdf9;
            padding: 9px 10px;
            min-height: 58px;
        }

        .executive-card-dark {
            background: {{ $primaryColor }};
            border-color: {{ $primaryColor }};
            color: #ffffff;
        }

        .executive-label {
            font-size: 8px;
            font-weight: bold;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: .35px;
        }

        .executive-card-dark .executive-label {
            color: #d1fae5;
        }

        .executive-value {
            margin-top: 5px;
            font-size: 13px;
            font-weight: bold;
            color: #111827;
        }

        .executive-card-dark .executive-value {
            color: #ffffff;
        }

        .executive-foot {
            margin-top: 3px;
            font-size: 8px;
            color: #6b7280;
        }

        .executive-card-dark .executive-foot {
            color: #e5e7eb;
        }

        .kpi-table {
            width: 100%;
            border-collapse: collapse;
            margin: 11px 0 13px 0;
        }

        .kpi-table td {
            width: 25%;
            padding-right: 6px;
            vertical-align: top;
        }

        .kpi {
            border: 1px solid #dbe4d3;
            background: #ffffff;
            padding: 8px;
            min-height: 54px;
        }

        .kpi-label {
            font-size: 8px;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: .35px;
        }

        .kpi-value {
            margin-top: 4px;
            font-size: 12px;
            font-weight: bold;
            color: #111827;
        }

        .kpi-foot {
            margin-top: 3px;
            font-size: 7.5px;
            color: #6b7280;
            line-height: 1.25;
        }

        .section-heading {
            margin-top: 12px;
            margin-bottom: 7px;
            padding: 7px 9px;
            background: #f3f7ef;
            border-left: 4px solid {{ $primaryColor }};
            color: #111827;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .3px;
        }

        table.report {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        table.report th {
            background: {{ $primaryColor }};
            color: #ffffff;
            border: 1px solid {{ $primaryColor }};
            padding: 6px 4px;
            font-size: 7.2px;
            text-transform: uppercase;
            letter-spacing: .15px;
            text-align: left;
        }

        table.report td {
            border: 1px solid #e5e7eb;
            padding: 5px 4px;
            font-size: 7.8px;
            vertical-align: top;
            line-height: 1.32;
        }

        table.report tr:nth-child(even) td {
            background: #fafafa;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .muted {
            color: #6b7280;
        }

        .success {
            color: {{ $successColor }};
            font-weight: bold;
        }

        .warning {
            color: {{ $accentColor }};
            font-weight: bold;
        }

        .danger {
            color: {{ $dangerColor }};
            font-weight: bold;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 999px;
            color: #ffffff;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-success {
            background: {{ $successColor }};
        }

        .badge-danger {
            background: {{ $dangerColor }};
        }

        .badge-warning {
            background: {{ $accentColor }};
        }

        .badge-gray {
            background: #6b7280;
        }

        .summary-wrap {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }

        .summary-box {
            border: 1px solid #dbe4d3;
            background: #fbfdf9;
            padding: 10px;
            min-height: 62px;
        }

        .summary-title {
            font-size: 9px;
            font-weight: bold;
            color: {{ $secondaryColor }};
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .summary-text {
            color: #4b5563;
            line-height: 1.45;
            font-size: 8.5px;
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
                    Printed {{ $generatedAt->format('d M Y, H:i') }} EAT
                </td>
                <td class="footer-center">
                    Stock Movement Ledger Report
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
            <h1>Stock Movement Ledger Report</h1>
            <p>
                Period: {{ $periodFrom }} to {{ $periodTo }}.
                This report summarises stock movement activity across purchases, feeding, treatments,
                crop input usage, adjustments, and other inventory ledger events.
            </p>
        </div>

        <table class="executive-strip">
            <tr>
                <td style="width: 50%; padding-right: 7px;">
                    <div class="executive-card">
                        <div class="executive-label">Movement Activity</div>
                        <div class="executive-value">
                            {{ number_format($movements->count()) }} Movement(s)
                        </div>
                        <div class="executive-foot">
                            Stock In: {{ stockReportQty($stockInQty) }} |
                            Stock Out: {{ stockReportQty($stockOutQty) }}
                        </div>
                    </div>
                </td>

                <td style="width: 50%; padding-left: 7px;">
                    <div class="executive-card executive-card-dark">
                        <div class="executive-label">Total Movement Value</div>
                        <div class="executive-value">{{ stockReportMoney($stockValue) }}</div>
                        <div class="executive-foot">
                            In: {{ stockReportMoney($stockInValue) }} |
                            Out: {{ stockReportMoney($stockOutValue) }}
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <table class="kpi-table">
            <tr>
                <td>
                    <div class="kpi">
                        <div class="kpi-label">Stock In Qty</div>
                        <div class="kpi-value success">{{ stockReportQty($stockInQty) }}</div>
                        <div class="kpi-foot">Inventory additions</div>
                    </div>
                </td>

                <td>
                    <div class="kpi">
                        <div class="kpi-label">Stock Out Qty</div>
                        <div class="kpi-value danger">{{ stockReportQty($stockOutQty) }}</div>
                        <div class="kpi-foot">Inventory consumption</div>
                    </div>
                </td>

                <td>
                    <div class="kpi">
                        <div class="kpi-label">Net Quantity</div>
                        <div class="kpi-value {{ $netQuantity >= 0 ? 'success' : 'danger' }}">
                            {{ stockReportQty($netQuantity) }}
                        </div>
                        <div class="kpi-foot">In minus out</div>
                    </div>
                </td>

                <td>
                    <div class="kpi">
                        <div class="kpi-label">Ledger Value</div>
                        <div class="kpi-value">{{ stockReportMoney($stockValue) }}</div>
                        <div class="kpi-foot">Total movement value</div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="section-heading">Detailed Stock Movements</div>

        <table class="report">
            <thead>
                <tr>
                    <th width="8%">Date</th>
                    <th width="10%">Movement No.</th>
                    <th width="17%">Item</th>
                    <th width="8%">Direction</th>
                    <th width="11%">Type</th>
                    <th width="9%" class="right">Qty</th>
                    <th width="7%">Unit</th>
                    <th width="10%" class="right">Unit Cost</th>
                    <th width="10%" class="right">Total</th>
                    <th width="10%">Reference</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($movements as $movement)
                    @php
                        $directionClass = match ($movement->direction) {
                            'in' => 'badge-success',
                            'out' => 'badge-danger',
                            'adjustment' => 'badge-warning',
                            default => 'badge-gray',
                        };
                    @endphp

                    <tr>
                        <td>{{ stockReportDate($movement->movement_date) }}</td>
                        <td>{{ $movement->movement_no_display }}</td>
                        <td>
                            <strong>{{ $movement->inventoryItem?->name ?? 'N/A' }}</strong><br>
                            <span class="muted">
                                Source: {{ $movement->source_label ?? 'N/A' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $directionClass }}">
                                {{ $movement->direction_label }}
                            </span>
                        </td>
                        <td>{{ $movement->type_label }}</td>
                        <td class="right">{{ stockReportQty($movement->quantity) }}</td>
                        <td>{{ $movement->unit ?: '-' }}</td>
                        <td class="right">{{ stockReportMoney($movement->unit_cost) }}</td>
                        <td class="right">{{ stockReportMoney($movement->total_cost) }}</td>
                        <td>{{ $movement->reference_label }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="center">No stock movements found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <table class="summary-wrap">
            <tr>
                <td style="width: 55%; padding-right: 10px; vertical-align: top;">
                    <div class="summary-box">
                        <div class="summary-title">Ledger Note</div>
                        <div class="summary-text">
                            Stock movements are generated from operational activities such as goods receiving,
                            animal feeding, veterinary treatment, crop input use, stock adjustments, and other
                            inventory-controlled transactions.
                        </div>
                    </div>
                </td>

                <td style="width: 45%; vertical-align: top;">
                    <div class="summary-box">
                        <div class="summary-title">Control Insight</div>
                        <div class="summary-text">
                            Large stock-out movements, frequent adjustments, missing references, or repeated consumption
                            should be reviewed for stock control, operational discipline, and procurement planning.
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
                $y = 565;

                $canvas->text($x, $y, $text, $font, $size, [0.35, 0.38, 0.42]);
            });
        }
    </script>
</body>

</html>
