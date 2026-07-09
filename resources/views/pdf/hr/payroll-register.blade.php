@php
    if (!function_exists('pdfImageBase64')) {
        function pdfImageBase64(?string $path): ?string
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
                        'ico' => 'image/x-icon',
                        default => 'image/png',
                    };

                    return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullPath));
                }
            }

            return null;
        }
    }
    $eatNow = now('Africa/Nairobi');

    $monthName = \Carbon\Carbon::create()->month((int) $payroll->month)->format('F');

    $farmName = setting('farm.name', 'Penzi Farm');
    $farmTagline = setting('farm.tagline', 'Nurturing Quality, Inspiring Global Standards');
    $farmPhone = setting('farm.phone', '+254 700 000 000');
    $farmEmail = setting('farm.email', 'hr@penzifarm.co');
    $farmCounty = setting('farm.county', 'Kenya');

    $primaryColor = setting('theme.primary', '#014a12');
    $secondaryColor = setting('theme.secondary', '#14532d');
    $accentColor = setting('theme.accent', '#f59e0b');
    $dangerColor = setting('theme.danger', '#dc2626');
    $successColor = setting('theme.success', '#16a34a');

    $logoBase64 = pdfImageBase64(setting('branding.logo_light'));

    $totalBasic = $items->sum(fn($item) => (float) $item->basic_salary);
    $totalAllowances = $items->sum(fn($item) => (float) $item->allowances_total);
    $totalGross = $items->sum(fn($item) => (float) $item->gross_pay);
    $totalPaye = $items->sum(fn($item) => (float) $item->paye);
    $totalNssf = $items->sum(fn($item) => (float) $item->nssf);
    $totalSha = $items->sum(fn($item) => (float) $item->sha);
    $totalHousingLevy = $items->sum(fn($item) => (float) $item->housing_levy);
    $totalOtherDeductions = $items->sum(
        fn($item) => (float) $item->salary_advance_deduction + (float) $item->other_deductions,
    );
    $totalNet = $items->sum(fn($item) => (float) $item->net_pay);
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Payroll Register</title>
    <style>
        @page {
            margin: 115px 22px 90px 22px;
        }

        body {
            font-family: Courier, sans-serif;
            font-size: 9px;
            color: #222;
        }

        .watermark {
            position: fixed;
            top: 30%;
            left: 12%;
            width: 75%;
            opacity: 0.05;
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
            height: 88px;
            border-bottom: 2px solid {{ $primaryColor }};
        }

        footer {
            position: fixed;
            bottom: -72px;
            left: 0;
            right: 0;
            height: 62px;
            border-top: 1px solid #d1d5db;
            font-size: 10px;
            color: #4b5563;
        }

        .header-table,
        .footer-table,
        .summary-table,
        .report {
            width: 100%;
            border-collapse: collapse;
        }

        .header-left {
            text-align: left;
            vertical-align: middle;
        }

        .header-center {
            text-align: center;
            vertical-align: middle;
        }

        .header-right {
            text-align: right;
            vertical-align: middle;
            font-size: 10px;
            line-height: 1.5;
            color: #374151;
        }

        .logo {
            width: 165px;
        }

        .company-title {
            font-size: 21px;
            font-weight: 700;
            color: {{ $primaryColor }};
            margin-bottom: 2px;
            text-align: center;
        }

        .tagline {
            font-size: 11px;
            color: #4b5563;
            font-style: italic;
            text-align: center;
        }

        .report-title {
            margin-bottom: 10px;
        }

        .report-title h1 {
            font-size: 18px;
            margin: 0 0 4px 0;
            color: #111827;
        }

        .report-title p {
            margin: 0;
            color: {{ $primaryColor }};
            font-size: 10px;
        }

        .summary-wrap {
            margin: 10px 0 14px 0;
            border: 1px solid #dbe4d3;
            background: #f8fbf7;
            border-radius: 8px;
            padding: 8px 10px;
        }

        .summary-table td {
            padding: 5px 7px;
            font-size: 9.5px;
            vertical-align: top;
        }

        .summary-label {
            font-weight: bold;
            color: #374151;
        }

        table.report thead th {
            background: {{ $primaryColor }};
            border: 1px solid {{ $primaryColor }};
            color: #fff;
            padding: 8px 4px;
            font-size: 8px;
            text-align: left;
            line-height: 1.25;
        }

        table.report tbody td {
            border: 1px solid #e5e7eb;
            padding: 6px 4px;
            vertical-align: top;
            line-height: 1.3;
        }

        table.report tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .right {
            text-align: right;
        }

        .strong {
            font-weight: bold;
        }

        .muted {
            color: #6b7280;
        }

        .footer-left {
            text-align: left;
            width: 33%;
        }

        .footer-center {
            text-align: center;
            width: 34%;
        }

        .footer-right {
            text-align: right;
            width: 33%;
        }

        .small-muted {
            color: #6b7280;
            font-size: 9px;
        }

        .net-total {
            font-weight: bold;
            color: {{ $successColor }};
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
                <td class="header-left" width="110">
                    @if ($logoBase64)
                        <img src="{{ $logoBase64 }}" class="logo" alt="Logo">
                    @endif
                </td>
                <td class="header-center">
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
                <td class="footer-left">
                    Generated on {{ $eatNow->format('d M Y, H:i') }} EAT
                </td>
                <td class="footer-center">
                    Payroll Register
                </td>
                <td class="footer-right">
                    Prepared by {{ $generatedBy->name ?? 'System' }}
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
        <div class="report-title">
            <h1>Payroll Register - {{ $monthName }} {{ $payroll->year }}</h1>
            <p>
                Period: {{ optional($payroll->period_start)->format('d M Y') }} -
                {{ optional($payroll->period_end)->format('d M Y') }}
            </p>
        </div>

        <div class="summary-wrap">
            <table class="summary-table">
                <tr>
                    <td><span class="summary-label">Total Employees:</span> {{ $items->count() }}</td>
                    <td><span class="summary-label">Total Basic:</span> KSh {{ number_format($totalBasic, 2) }}</td>
                    <td><span class="summary-label">Total Allowances:</span> KSh
                        {{ number_format($totalAllowances, 2) }}</td>
                    <td><span class="summary-label">Total Gross:</span> KSh {{ number_format($totalGross, 2) }}</td>
                </tr>
                <tr>
                    <td><span class="summary-label">Total PAYE:</span> KSh {{ number_format($totalPaye, 2) }}</td>
                    <td><span class="summary-label">Total NSSF:</span> KSh {{ number_format($totalNssf, 2) }}</td>
                    <td><span class="summary-label">Total SHA:</span> KSh {{ number_format($totalSha, 2) }}</td>
                    <td><span class="summary-label">Total Housing Levy:</span> KSh
                        {{ number_format($totalHousingLevy, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="2"><span class="summary-label">Total Other Deductions:</span> KSh
                        {{ number_format($totalOtherDeductions, 2) }}</td>
                    <td colspan="2"><span class="summary-label">Total Net Pay:</span> <span class="net-total">KSh
                            {{ number_format($totalNet, 2) }}</span></td>
                </tr>
            </table>
        </div>

        <table class="report">
            <thead>
                <tr>
                    <th width="3%">#</th>
                    <th width="11%">Employee</th>
                    <th width="7%">Employee No.</th>
                    <th width="8%">Department</th>
                    <th width="8%">Job Title</th>
                    <th width="7%" class="right">Basic</th>
                    <th width="7%" class="right">Allowances</th>
                    <th width="7%" class="right">Gross</th>
                    <th width="6%" class="right">PAYE</th>
                    <th width="6%" class="right">NSSF</th>
                    <th width="6%" class="right">SHA</th>
                    <th width="7%" class="right">Housing Levy</th>
                    <th width="8%" class="right">Advance / Other Deductions</th>
                    <th width="7%" class="right">Net Pay</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <span class="strong">{{ $item->employee->full_name ?? '-' }}</span><br>
                            <span class="muted">{{ $item->employee->phone ?? '-' }}</span>
                        </td>
                        <td>{{ $item->employee->employee_number ?? '-' }}</td>
                        <td>{{ $item->employee->department->name ?? '-' }}</td>
                        <td>{{ $item->employee->jobTitle->name ?? '-' }}</td>
                        <td class="right">{{ number_format((float) $item->basic_salary, 2) }}</td>
                        <td class="right">{{ number_format((float) $item->allowances_total, 2) }}</td>
                        <td class="right strong">{{ number_format((float) $item->gross_pay, 2) }}</td>
                        <td class="right">{{ number_format((float) $item->paye, 2) }}</td>
                        <td class="right">{{ number_format((float) $item->nssf, 2) }}</td>
                        <td class="right">{{ number_format((float) $item->sha, 2) }}</td>
                        <td class="right">{{ number_format((float) $item->housing_levy, 2) }}</td>
                        <td class="right">
                            {{ number_format((float) $item->salary_advance_deduction + (float) $item->other_deductions, 2) }}
                        </td>
                        <td class="right net-total">{{ number_format((float) $item->net_pay, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </main>

    <script type="text/php">
        if (isset($pdf)) {
            $pdf->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) {
                $font = $fontMetrics->getFont('Helvetica', 'normal');
                $size = 9;

                $text = "Page {$pageNumber} of {$pageCount}";
                $width = $fontMetrics->getTextWidth($text, $font, $size);

                $x = 420 - ($width / 2);
                $y = 565;

                $canvas->text($x, $y, $text, $font, $size, [0.42, 0.45, 0.50]);
            });
        }
    </script>
</body>

</html>
