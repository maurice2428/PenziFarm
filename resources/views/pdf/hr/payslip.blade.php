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

    $farmName = setting('farm.name', 'Penzi Farm');
    $farmTagline = setting('farm.tagline', 'Nurturing Quality, Inspiring Global Standards');
    $farmPhone = setting('farm.phone', '+254 700 000 000');
    $farmEmail = setting('farm.email', 'hr@penzifarm.co');
    $farmCounty = setting('farm.county', 'Kenya');

    $primaryColor = setting('theme.primary', '#014a12');
    $secondaryColor = setting('theme.secondary', '#14532d');
    $successColor = setting('theme.success', '#16a34a');

    $logoBase64 = pdfImageBase64(setting('branding.logo_light'));

    $monthName = \Carbon\Carbon::create()->month((int) $payroll->month)->format('F');

    $basicPay = (float) ($employee->basic_salary ?? 0);
    $allowances =
        (float) (($employee->house_allowance ?? 0) +
            ($employee->transport_allowance ?? 0) +
            ($employee->other_allowance ?? 0));
    $nssf =
        (float) ($payslip->statutory_deductions ?? 0) > 0
            ? (float) ($payslip->payroll?->items?->firstWhere('employee_id', $employee->id)?->nssf ?? 0)
            : 0;
    $sha = (float) ($payslip->payroll?->items?->firstWhere('employee_id', $employee->id)?->sha ?? 0);
    $housingLevy = (float) ($payslip->payroll?->items?->firstWhere('employee_id', $employee->id)?->housing_levy ?? 0);
    $taxablePay = (float) $payslip->taxable_pay;
    $paye = (float) $payslip->paye;
    $salaryAdvanceRecovery = (float) $payslip->other_deductions;
    $remainingApprovedAdvances = (float) ($payslip->remaining_approved_advances ?? 0);
    $totalDeductions = $nssf + $sha + $housingLevy + $paye + $salaryAdvanceRecovery;
    $netComputed = (float) $payslip->gross_pay - $totalDeductions;
    $netRecorded = (float) $payslip->net_pay;
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Payslip</title>
    <style>
        @page {
            margin: 100px 32px 85px 32px;
        }

        body {
            font-family: Courier, sans-serif;
            font-size: 11px;
            color: #222;
        }

        header {
            position: fixed;
            top: -82px;
            left: 0;
            right: 0;
            height: 75px;
            border-bottom: 2px solid {{ $primaryColor }};
        }

        footer {
            position: fixed;
            bottom: -60px;
            left: 0;
            right: 0;
            height: 50px;
            border-top: 1px solid #d1d5db;
            font-size: 10px;
            color: #4b5563;
        }

        .header-table,
        .footer-table,
        .detail-table,
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .logo {
            width: 140px;
        }

        .title {
            font-size: 20px;
            font-weight: bold;
            color: {{ $primaryColor }};
            text-align: center;
        }

        .tagline {
            text-align: center;
            color: #6b7280;
            font-style: italic;
            font-size: 10px;
        }

        .section {
            margin-top: 14px;
        }

        .box {
            border: 1px solid #dbe4d3;
            border-radius: 8px;
            padding: 10px 12px;
            background: #fbfdf9;
        }

        .detail-table td {
            padding: 4px 6px;
            vertical-align: top;
        }

        .label {
            font-weight: bold;
            width: 180px;
            color: #374151;
        }

        .summary-table td {
            padding: 6px 4px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }

        .right {
            text-align: right;
        }

        .strong {
            font-weight: bold;
        }

        .net {
            font-size: 14px;
            font-weight: bold;
            color: {{ $successColor }};
        }

        .notes-box {
            margin-top: 14px;
            border: 1px solid #dbe4d3;
            background: #f8fbf7;
            border-radius: 8px;
            padding: 10px 12px;
            line-height: 1.55;
        }

        .notes-title {
            font-weight: bold;
            color: {{ $secondaryColor }};
            margin-bottom: 6px;
        }
    </style>
</head>

<body>

    <header>
        <table class="header-table">
            <tr>
                <td width="150">
                    @if ($logoBase64)
                        <img src="{{ $logoBase64 }}" class="logo" alt="Logo">
                    @endif
                </td>
                <td>
                    <div class="title">{{ $monthName }} {{ $payroll->year }} PAYSLIP</div>
                    <div class="tagline">{{ $farmName }} — {{ $farmTagline }}</div>
                </td>
                <td width="210" style="text-align:right; font-size:10px;">
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
                <td>Generated on {{ $eatNow->format('d M Y, H:i') }} EAT</td>
                <td style="text-align:center;">Employee Payslip</td>
                <td style="text-align:right;">Prepared by {{ $generatedBy->name }}</td>
            </tr>
        </table>
    </footer>

    <main>
        <div class="section box">
            <table class="detail-table">
                <tr>
                    <td class="label">Employee Name</td>
                    <td>{{ $employee->full_name }}</td>
                    <td class="label">Employee No.</td>
                    <td>{{ $employee->employee_number ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Department</td>
                    <td>{{ $employee->department->name ?? '-' }}</td>
                    <td class="label">Job Title</td>
                    <td>{{ $employee->jobTitle->name ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Pay Period</td>
                    <td>{{ optional($payslip->pay_period_start)->format('d M Y') }} -
                        {{ optional($payslip->pay_period_end)->format('d M Y') }}</td>
                    <td class="label">Status</td>
                    <td>Generated</td>
                </tr>
            </table>
        </div>

        <div class="section box">
            <table class="summary-table">
                <tr>
                    <td class="strong">BASIC PAY:</td>
                    <td class="right">KSh {{ number_format($basicPay, 2) }}</td>
                </tr>
                <tr>
                    <td>Allowances:</td>
                    <td class="right">KSh {{ number_format($allowances, 2) }}</td>
                </tr>
                <tr>
                    <td>NSSF:</td>
                    <td class="right">KSh {{ number_format($nssf, 2) }}</td>
                </tr>
                <tr>
                    <td>NHIF/SHA:</td>
                    <td class="right">KSh {{ number_format($sha, 2) }}</td>
                </tr>
                <tr>
                    <td>Housing Levy:</td>
                    <td class="right">KSh {{ number_format($housingLevy, 2) }}</td>
                </tr>
                <tr>
                    <td class="strong">TAXABLE PAY:</td>
                    <td class="right">KSh {{ number_format($taxablePay, 2) }}</td>
                </tr>
                <tr>
                    <td>INCOME TAX (PAYE):</td>
                    <td class="right">KSh {{ number_format($paye, 2) }}</td>
                </tr>
                <tr>
                    <td>Salary Advance Recovery:</td>
                    <td class="right">KSh {{ number_format($salaryAdvanceRecovery, 2) }}</td>
                </tr>
                <tr>
                    <td>Remaining Approved Advances (post-run):</td>
                    <td class="right">KSh {{ number_format($remainingApprovedAdvances, 2) }}</td>
                </tr>
                <tr>
                    <td class="strong">Total Deductions:</td>
                    <td class="right">KSh {{ number_format($totalDeductions, 2) }}</td>
                </tr>
                <tr>
                    <td class="strong">NET PAY (computed):</td>
                    <td class="right net">KSh {{ number_format($netComputed, 2) }}</td>
                </tr>
                <tr>
                    <td class="strong">NET PAY (recorded):</td>
                    <td class="right net">KSh {{ number_format($netRecorded, 2) }}</td>
                </tr>
            </table>
        </div>

        <div class="notes-box">
            <div class="notes-title">Notes:</div>
            Advance recoveries shown above were applied in this payroll run and respect statutory deductions and the 2/3
            rule (Employment Act).<br>
            Remaining approved advances (if any) will be recovered in subsequent payroll runs as per HR schedule.<br>
            Contact Finance for queries: hr@lelekwefarm.com
        </div>
    </main>

</body>

</html>
