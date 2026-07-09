@php
    if (!function_exists('assetPdfImageBase64')) {
        function assetPdfImageBase64(?string $path): ?string
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

    if (!function_exists('assetReportMoney')) {
        function assetReportMoney($amount): string
        {
            return 'KES ' . number_format((float) $amount, 2);
        }
    }

    if (!function_exists('assetReportDate')) {
        function assetReportDate($date, string $format = 'd M Y'): string
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

    if (!function_exists('assetReportNumber')) {
        function assetReportNumber($amount): string
        {
            return number_format((float) $amount);
        }
    }

    $farmName = setting('farm.name', config('app.name', 'Lelekwe Farms Limited'));
    $farmTagline = setting('farm.tagline', 'Nurturing Nature, Feeding The Future');
    $farmPhone = setting('farm.phone', '+254 743 487 186');
    $farmEmail = setting('farm.email', 'jambo@lelekwefarms.co.ke');
    $farmCounty = setting('farm.county', 'Kenya');

    $primaryColor = setting('theme.primary', '#014a12');
    $secondaryColor = setting('theme.secondary', '#14532d');
    $accentColor = setting('theme.accent', '#f59e0b');
    $dangerColor = setting('theme.danger', '#dc2626');
    $successColor = setting('theme.success', '#16a34a');

    $logoBase64 = assetPdfImageBase64(
        setting('branding.logo_light') ?: setting('branding.logo') ?: setting('farm.logo'),
    );

    $generatedAt = now('Africa/Nairobi');
    $generatedByName = $generatedBy?->name ?? 'System';
    $generatedByRole =
        isset($generatedBy) && method_exists($generatedBy, 'getRoleNames')
            ? ($generatedBy->getRoleNames()->first() ?:
            'User')
            : 'User';

    $totalAssets = $assets->count();
    $totalPurchaseCost = $assets->sum('purchase_cost');
    $totalCurrentValue = $assets->sum('current_value');
    $totalBookValue = $assets->sum(fn($asset) => $asset->estimated_book_value);
    $totalDepreciation = $assets->sum(fn($asset) => $asset->depreciation_to_date);
    $activeAssets = $assets->where('status', 'active')->count();
    $maintenanceAssets = $assets->where('status', 'under_maintenance')->count();
    $disposedAssets = $assets->where('status', 'disposed')->count();
    $valuationVariance = $totalCurrentValue - $totalBookValue;
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Asset Valuation Report</title>

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

        .badge-warning {
            background: {{ $accentColor }};
        }

        .badge-danger {
            background: {{ $dangerColor }};
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
                    Asset Valuation & Aging Report
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
            <h1>Asset Valuation & Aging Report</h1>
            <p>
                A consolidated view of registered assets, purchase value, current valuation, depreciation estimate,
                useful life position, asset condition, and aging exposure.
            </p>
        </div>

        <table class="executive-strip">
            <tr>
                <td style="width: 50%; padding-right: 7px;">
                    <div class="executive-card">
                        <div class="executive-label">Operational Asset Position</div>
                        <div class="executive-value">
                            {{ assetReportNumber($activeAssets) }} Active Asset(s)
                        </div>
                        <div class="executive-foot">
                            {{ assetReportNumber($maintenanceAssets) }} under maintenance |
                            {{ assetReportNumber($disposedAssets) }} disposed/lost
                        </div>
                    </div>
                </td>

                <td style="width: 50%; padding-left: 7px;">
                    <div class="executive-card executive-card-dark">
                        <div class="executive-label">Current Asset Value</div>
                        <div class="executive-value">{{ assetReportMoney($totalCurrentValue) }}</div>
                        <div class="executive-foot">
                            Book value: {{ assetReportMoney($totalBookValue) }}
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <table class="kpi-table">
            <tr>
                <td>
                    <div class="kpi">
                        <div class="kpi-label">Assets</div>
                        <div class="kpi-value">{{ assetReportNumber($totalAssets) }}</div>
                        <div class="kpi-foot">Registered records</div>
                    </div>
                </td>

                <td>
                    <div class="kpi">
                        <div class="kpi-label">Purchase Cost</div>
                        <div class="kpi-value">{{ assetReportMoney($totalPurchaseCost) }}</div>
                        <div class="kpi-foot">Historical acquisition value</div>
                    </div>
                </td>

                <td>
                    <div class="kpi">
                        <div class="kpi-label">Depreciation</div>
                        <div class="kpi-value danger">{{ assetReportMoney($totalDepreciation) }}</div>
                        <div class="kpi-foot">Estimated value consumed</div>
                    </div>
                </td>

                <td>
                    <div class="kpi">
                        <div class="kpi-label">Variance</div>
                        <div class="kpi-value {{ $valuationVariance >= 0 ? 'success' : 'danger' }}">
                            {{ assetReportMoney($valuationVariance) }}
                        </div>
                        <div class="kpi-foot">Current value vs book value</div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="section-heading">Asset Register Breakdown</div>

        <table class="report">
            <thead>
                <tr>
                    <th width="8%">Asset No.</th>
                    <th width="17%">Asset</th>
                    <th width="9%">Category</th>
                    <th width="8%">Acquired</th>
                    <th width="7%">Age</th>
                    <th width="10%" class="right">Cost</th>
                    <th width="10%" class="right">Book Value</th>
                    <th width="10%" class="right">Current Value</th>
                    <th width="8%">Condition</th>
                    <th width="8%">Status</th>
                    <th width="5%">Aging</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($assets as $asset)
                    @php
                        $agingClass = match ($asset->aging_status) {
                            'Healthy Life' => 'badge-success',
                            'Aging Soon' => 'badge-warning',
                            'Near End of Life', 'Fully Aged' => 'badge-danger',
                            'Disposed' => 'badge-gray',
                            default => 'badge-gray',
                        };

                        $conditionClass = match ($asset->condition) {
                            'excellent', 'good' => 'badge-success',
                            'fair' => 'badge-warning',
                            'poor', 'damaged' => 'badge-danger',
                            'disposed' => 'badge-gray',
                            default => 'badge-gray',
                        };

                        $statusClass = match ($asset->status) {
                            'active' => 'badge-success',
                            'under_maintenance' => 'badge-warning',
                            'idle' => 'badge-gray',
                            'disposed', 'lost' => 'badge-danger',
                            default => 'badge-gray',
                        };
                    @endphp

                    <tr>
                        <td>{{ $asset->asset_number ?? 'N/A' }}</td>
                        <td>
                            <strong>{{ $asset->name }}</strong><br>
                            <span class="muted">
                                {{ $asset->asset_type ?: 'Asset' }}
                                @if ($asset->tag_number)
                                    | Tag: {{ $asset->tag_number }}
                                @endif
                                @if ($asset->serial_number)
                                    | Serial: {{ $asset->serial_number }}
                                @endif
                            </span>
                        </td>
                        <td>{{ $asset->category_label }}</td>
                        <td>{{ assetReportDate($asset->acquisition_date) }}</td>
                        <td>{{ $asset->age_display }}</td>
                        <td class="right">{{ assetReportMoney($asset->purchase_cost) }}</td>
                        <td class="right">{{ assetReportMoney($asset->estimated_book_value) }}</td>
                        <td class="right success">{{ assetReportMoney($asset->current_value) }}</td>
                        <td>
                            <span class="badge {{ $conditionClass }}">
                                {{ $asset->condition_label }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $statusClass }}">
                                {{ $asset->status_label }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $agingClass }}">
                                {{ $asset->aging_status }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="center">No assets found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <table class="summary-wrap">
            <tr>
                <td style="width: 55%; padding-right: 10px; vertical-align: top;">
                    <div class="summary-box">
                        <div class="summary-title">Report Note</div>
                        <div class="summary-text">
                            This report compares original acquisition cost, estimated book value, and current valuation.
                            Straight-line assets use useful life to estimate depreciation, while manually valued assets
                            rely on recorded valuation/current value.
                        </div>
                    </div>
                </td>

                <td style="width: 45%; vertical-align: top;">
                    <div class="summary-box">
                        <div class="summary-title">Management Insight</div>
                        <div class="summary-text">
                            Assets flagged as aging soon, near end of life, under maintenance, damaged, idle, or overdue
                            for valuation should be prioritised for review, disposal, repair, or replacement planning.
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
