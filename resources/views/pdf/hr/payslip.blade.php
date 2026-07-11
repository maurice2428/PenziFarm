@php
    if (! function_exists('payslipPdfImageBase64')) {
        function payslipPdfImageBase64(
            ?string $path
        ): ?string {
            if (blank($path)) {
                return null;
            }

            $cleanPath = preg_replace(
                '#^storage/#',
                '',
                ltrim(trim($path), '/')
            );

            foreach ([
                storage_path(
                    'app/public/' . $cleanPath
                ),
                public_path(
                    'storage/' . $cleanPath
                ),
                public_path($cleanPath),
            ] as $fullPath) {
                if (! is_file($fullPath)) {
                    continue;
                }

                $extension = strtolower(
                    pathinfo(
                        $fullPath,
                        PATHINFO_EXTENSION
                    )
                );

                $mime = match ($extension) {
                    'jpg', 'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    'webp' => 'image/webp',
                    'svg' => 'image/svg+xml',
                    default => 'image/png',
                };

                return 'data:'
                    . $mime
                    . ';base64,'
                    . base64_encode(
                        file_get_contents($fullPath)
                    );
            }

            return null;
        }
    }

    $eatNow = now('Africa/Nairobi');

    $farmName = setting(
        'farm.name',
        setting('company.name', 'Penzi Farm')
    );

    $farmTagline = setting(
        'farm.tagline',
        'Nurturing Quality, Inspiring Global Standards'
    );

    $farmPhone = setting(
        'farm.phone',
        '+254 700 000 000'
    );

    $farmEmail = setting(
        'farm.email',
        'hr@penzifarm.co'
    );

    $farmCounty = setting(
        'farm.county',
        'Kenya'
    );

    $primaryColor = setting(
        'theme.primary',
        '#014a12'
    );

    $secondaryColor = setting(
        'theme.secondary',
        '#14532d'
    );

    $successColor = setting(
        'theme.success',
        '#16a34a'
    );

    $logoBase64 = payslipPdfImageBase64(
        setting('branding.logo_light')
    );

    $paymentSettings =
        \App\Models\Settings\PaymentSetting::current();

    $signatureBase64 = payslipPdfImageBase64(
        data_get(
            $paymentSettings,
            'authorized_signature_image'
        )
        ?: data_get(
            $paymentSettings,
            'invoice_signature_path'
        )
        ?: setting('branding.signature')
        ?: setting('farm.signature')
    );

    $stampBase64 = payslipPdfImageBase64(
        data_get(
            $paymentSettings,
            'payment_stamp_image'
        )
        ?: data_get(
            $paymentSettings,
            'invoice_stamp_path'
        )
        ?: setting('branding.stamp')
        ?: setting('farm.stamp')
    );

    $authorizedName = setting(
        'farm.authorized_signatory_name',
        setting(
            'company.authorized_signatory_name',
            'Authorised Signatory'
        )
    );

    $authorizedTitle = setting(
        'farm.authorized_signatory_title',
        'Finance / Human Resource'
    );

    $monthName = \Carbon\Carbon::create()
        ->month((int) $payroll->month)
        ->format('F');

    $payrollItem = $payslip->payroll?->items
        ?->firstWhere(
            'employee_id',
            $employee->id
        );

    $basicPay = (float) (
        $payrollItem?->basic_salary
        ?? $employee->basic_salary
        ?? 0
    );

    $allowances = (float) (
        $payrollItem?->allowances_total
        ?? (
            (float) ($employee->house_allowance ?? 0)
            + (float) (
                $employee->transport_allowance ?? 0
            )
            + (float) (
                $employee->other_allowance ?? 0
            )
        )
    );

    $nssf = (float) ($payrollItem?->nssf ?? 0);
    $sha = (float) ($payrollItem?->sha ?? 0);
    $housingLevy = (float) (
        $payrollItem?->housing_levy ?? 0
    );

    $taxablePay = (float) $payslip->taxable_pay;
    $paye = (float) $payslip->paye;

    $salaryAdvanceRecovery = (float) (
        $payrollItem?->salary_advance_deduction
        ?? $payslip->other_deductions
        ?? 0
    );

    $otherDeductions = (float) (
        $payrollItem?->other_deductions ?? 0
    );

    $remainingApprovedAdvances = (float) (
        $payslip->remaining_approved_advances ?? 0
    );

    $totalDeductions =
        $nssf
        + $sha
        + $housingLevy
        + $paye
        + $salaryAdvanceRecovery
        + $otherDeductions;

    $netRecorded = (float) $payslip->net_pay;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $monthName }} {{ $payroll->year }} Payslip</title>
    <style>
        @page {
            margin: 112px 32px 92px 32px;
        }

        body {
            font-family: Courier, sans-serif;
            font-size: 10.5px;
            color: #1f2937;
        }

        header {
            position: fixed;
            top: -94px;
            left: 0;
            right: 0;
            height: 84px;
            border-bottom: 2px solid {{ $primaryColor }};
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

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .logo {
            width: 135px;
            max-height: 66px;
        }

        .title {
            text-align: center;
            font-size: 19px;
            font-weight: bold;
            color: {{ $primaryColor }};
        }

        .tagline {
            text-align: center;
            font-size: 9px;
            color: #6b7280;
            font-style: italic;
        }

        .company {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            color: {{ $secondaryColor }};
        }

        .section {
            margin-top: 13px;
            border: 1px solid #dbe4d3;
            background: #fbfdf9;
            padding: 10px 12px;
        }

        .details td,
        .summary td {
            padding: 5px 6px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }

        .label {
            font-weight: bold;
            color: #374151;
        }

        .right {
            text-align: right;
        }

        .net {
            color: {{ $successColor }};
            font-size: 14px;
            font-weight: bold;
        }

        .approval {
            margin-top: 17px;
            page-break-inside: avoid;
        }

        .approval td {
            width: 50%;
            text-align: center;
            vertical-align: bottom;
            padding: 8px;
        }

        .approval-image {
            max-width: 150px;
            max-height: 70px;
        }

        .stamp-image {
            max-width: 125px;
            max-height: 90px;
        }

        .approval-line {
            border-top: 1px solid #374151;
            margin: 5px auto 3px;
            width: 75%;
        }

        .note {
            margin-top: 12px;
            border-left: 4px solid {{ $primaryColor }};
            padding: 8px 10px;
            background: #f8fbf7;
            line-height: 1.45;
        }
    </style>
</head>
<body>
<header>
    <table>
        <tr>
            <td width="150">
                @if ($logoBase64)
                    <img
                        src="{{ $logoBase64 }}"
                        class="logo"
                        alt="Logo"
                    >
                @endif
            </td>
            <td>
                <div class="company">{{ $farmName }}</div>
                <div class="title">
                    {{ $monthName }} {{ $payroll->year }} PAYSLIP
                </div>
                <div class="tagline">{{ $farmTagline }}</div>
            </td>
            <td width="210" style="text-align:right;font-size:9px;line-height:1.5;">
                <strong>Phone:</strong> {{ $farmPhone }}<br>
                <strong>Email:</strong> {{ $farmEmail }}<br>
                <strong>County:</strong> {{ $farmCounty }}
            </td>
        </tr>
    </table>
</header>

<footer>
    <table>
        <tr>
            <td>
                Generated {{ $eatNow->format('d M Y, H:i') }} EAT
            </td>
            <td style="text-align:center;">
                Confidential Employee Payslip
            </td>
            <td style="text-align:right;">
                Prepared by {{ $generatedBy->name ?? 'System' }}
            </td>
        </tr>
        <tr>
            <td colspan="3" style="text-align:center;padding-top:4px;">
                {{ $farmName }} • {{ $farmPhone }} • {{ $farmEmail }}
            </td>
        </tr>
    </table>
</footer>

<main>
    <div class="section">
        <table class="details">
            <tr>
                <td class="label">Employee</td>
                <td>{{ $employee->full_name }}</td>
                <td class="label">Employee No.</td>
                <td>{{ $employee->employee_number ?: '—' }}</td>
            </tr>
            <tr>
                <td class="label">Department</td>
                <td>{{ $employee->department->name ?? '—' }}</td>
                <td class="label">Job Title</td>
                <td>{{ $employee->jobTitle->name ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">Pay Period</td>
                <td>
                    {{ optional($payslip->pay_period_start)->format('d M Y') }}
                    –
                    {{ optional($payslip->pay_period_end)->format('d M Y') }}
                </td>
                <td class="label">Payroll Status</td>
                <td>{{ ucfirst($payroll->statusValue()) }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <table class="summary">
            <tr>
                <td class="label">Basic Pay</td>
                <td class="right">KES {{ number_format($basicPay, 2) }}</td>
            </tr>
            <tr>
                <td>Allowances</td>
                <td class="right">KES {{ number_format($allowances, 2) }}</td>
            </tr>
            <tr>
                <td>NSSF</td>
                <td class="right">KES {{ number_format($nssf, 2) }}</td>
            </tr>
            <tr>
                <td>SHIF</td>
                <td class="right">KES {{ number_format($sha, 2) }}</td>
            </tr>
            <tr>
                <td>Affordable Housing Levy</td>
                <td class="right">KES {{ number_format($housingLevy, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Taxable Pay</td>
                <td class="right">KES {{ number_format($taxablePay, 2) }}</td>
            </tr>
            <tr>
                <td>PAYE</td>
                <td class="right">KES {{ number_format($paye, 2) }}</td>
            </tr>
            <tr>
                <td>Salary Advance Recovery</td>
                <td class="right">
                    KES {{ number_format($salaryAdvanceRecovery, 2) }}
                </td>
            </tr>
            <tr>
                <td>Other Deductions</td>
                <td class="right">
                    KES {{ number_format($otherDeductions, 2) }}
                </td>
            </tr>
            <tr>
                <td>Remaining Approved Advances</td>
                <td class="right">
                    KES {{ number_format($remainingApprovedAdvances, 2) }}
                </td>
            </tr>
            <tr>
                <td class="label">Total Deductions</td>
                <td class="right">
                    KES {{ number_format($totalDeductions, 2) }}
                </td>
            </tr>
            <tr>
                <td class="label">NET PAY</td>
                <td class="right net">
                    KES {{ number_format($netRecorded, 2) }}
                </td>
            </tr>
        </table>
    </div>

    <table class="approval">
        <tr>
            <td>
                @if ($signatureBase64)
                    <img
                        src="{{ $signatureBase64 }}"
                        class="approval-image"
                        alt="Authorised Signature"
                    >
                @else
                    <div style="height:55px;"></div>
                @endif

                <div class="approval-line"></div>
                <strong>{{ $authorizedName }}</strong><br>
                {{ $authorizedTitle }}<br>
                Authorised Signature
            </td>

            <td>
                @if ($stampBase64)
                    <img
                        src="{{ $stampBase64 }}"
                        class="stamp-image"
                        alt="Official Stamp"
                    >
                @else
                    <div style="height:70px;"></div>
                @endif

                <div class="approval-line"></div>
                Official Stamp
            </td>
        </tr>
    </table>

    <div class="note">
        This is a confidential payroll document generated by
        {{ $farmName }}. Contact Finance or Human Resource for
        clarification. Statutory deductions and salary advance
        recoveries are based on the approved payroll run.
    </div>
</main>
</body>
</html>
