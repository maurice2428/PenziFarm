@php
    $farmName = setting('farm.name', 'Lelekwe Farms');
    $farmTagline = setting('farm.tagline', 'Nurturing Nature, Feeding The Future');
    $farmPhone = setting('farm.phone', '+254 743 487 186');
    $farmEmail = setting('farm.email', 'jambo@lelekwefarms.co.ke');
    $farmCounty = setting('farm.county', 'Ravine, Kambi Moto');

    $primaryColor = trim(setting('theme.primary', '#014a12'));
    $secondaryColor = trim(setting('theme.secondary', '#14532d'));
    $accentColor = trim(setting('theme.accent', '#f59e0b'));
    $dangerColor = trim(setting('theme.danger', '#dc2626'));
    $successColor = trim(setting('theme.success', '#16a34a'));

    $logoPath = setting('branding.logo_light');
    $logoUrl = $logoPath ? asset('storage/' . ltrim($logoPath, '/')) : null;

    $monthName = \Carbon\Carbon::create()->month((int) $payroll->month)->format('F');
    $year = $payroll->year;

    $payrollItem = $payroll?->items?->firstWhere('employee_id', $employee->id);

    $grossPay = (float) ($payslip->gross_pay ?? 0);
    $taxablePay = (float) ($payslip->taxable_pay ?? 0);
    $netPay = (float) ($payslip->net_pay ?? 0);

    $nssf = (float) ($payrollItem->nssf ?? 0);
    $sha = (float) ($payrollItem->sha ?? 0);
    $housingLevy = (float) ($payrollItem->housing_levy ?? 0);
    $paye = (float) ($payslip->paye ?? 0);
    $otherDeductions = (float) ($payslip->other_deductions ?? 0);

    $totalStatutories = $nssf + $sha + $housingLevy + $paye;
    $totalDeductions = $totalStatutories + $otherDeductions;
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip Email</title>
    <style>
        body, table, td, a {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        table, td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }

        img {
            -ms-interpolation-mode: bicubic;
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
            max-width: 100%;
        }

        body {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            height: 100% !important;
            background-color: #f3f4f6;
            font-family: 'Courier New', Courier, monospace;
            color: #1f2937;
        }

        .email-wrapper {
            width: 100%;
            background-color: #f3f4f6;
            padding: 24px 0;
        }

        .email-container {
            width: 100%;
            max-width: 760px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.08);
        }

        .hero {
            background: linear-gradient(135deg, {{ $secondaryColor }}, {{ $primaryColor }});
            padding: 30px 30px 24px;
            color: #ffffff;
        }

        .hero-title {
            font-size: 28px;
            line-height: 1.1;
            font-weight: 800;
            margin: 0;
            letter-spacing: -0.02em;
        }

        .hero-tagline {
            font-size: 13px;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.9);
            margin-top: 6px;
        }

        .hero-contact {
            font-size: 12px;
            line-height: 1.8;
            color: rgba(255, 255, 255, 0.88);
            text-align: right;
        }

        .content {
            padding: 30px;
        }

        .eyebrow {
            display: inline-block;
            padding: 6px 12px;
            background: #ecfdf5;
            border: 1px solid #bbf7d0;
            color: #166534;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .section-title {
            font-size: 28px;
            line-height: 1.15;
            font-weight: 800;
            color: #111827;
            margin: 14px 0 10px;
            letter-spacing: -0.03em;
        }

        .section-copy {
            font-size: 15px;
            line-height: 1.8;
            color: #4b5563;
            margin: 0 0 24px;
        }

        .card {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 22px;
        }

        .card-title {
            font-size: 12px;
            font-weight: 800;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 12px;
        }

        .details-table td {
            font-size: 14px;
            line-height: 1.7;
            color: #374151;
            padding: 6px 0;
            vertical-align: top;
        }

        .details-label {
            font-weight: 700;
            color: #111827;
        }

        .metric-cell {
            padding: 6px;
        }

        .metric-box {
            border-radius: 20px;
            padding: 20px 14px;
            text-align: center;
            border: 1px solid transparent;
        }

        .metric-green {
            background: #ecfdf5;
            border-color: #bbf7d0;
        }

        .metric-amber {
            background: #fefce8;
            border-color: #fde68a;
        }

        .metric-blue {
            background: #eff6ff;
            border-color: #bfdbfe;
        }

        .metric-slate {
            background: #f8fafc;
            border-color: #e2e8f0;
        }

        .metric-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 800;
        }

        .metric-value {
            margin-top: 8px;
            font-size: 24px;
            line-height: 1.15;
            font-weight: 800;
        }

        .metric-sub-value {
            margin-top: 6px;
            font-size: 18px;
            line-height: 1.15;
            font-weight: 800;
            color: #0f172a;
        }

        .metric-green .metric-label,
        .metric-green .metric-value {
            color: #166534;
        }

        .metric-amber .metric-label,
        .metric-amber .metric-value {
            color: #92400e;
        }

        .metric-blue .metric-label,
        .metric-blue .metric-value {
            color: #1d4ed8;
        }

        .metric-slate .metric-label {
            color: #475569;
        }

        .breakdown-wrap {
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            overflow: hidden;
            margin-bottom: 22px;
        }

        .breakdown-head td {
            background: #f9fafb;
            padding: 15px 18px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #374151;
        }

        .breakdown-row td {
            padding: 15px 18px;
            font-size: 14px;
            border-top: 1px solid #f3f4f6;
            color: #374151;
        }

        .breakdown-row td:last-child {
            text-align: right;
            font-weight: 700;
        }

        .breakdown-soft td {
            background: #f9fafb;
            font-weight: 800;
            color: #374151;
        }

        .breakdown-warm td {
            background: #fff7ed;
            font-weight: 800;
            color: #9a3412;
        }

        .breakdown-total td {
            background: #eff6ff;
            font-weight: 800;
        }

        .breakdown-total td:last-child {
            color: {{ $primaryColor }};
        }

        .note-box {
            background: #f8fbf7;
            border: 1px solid #dbe4d3;
            border-radius: 20px;
            padding: 18px 20px;
            margin-bottom: 22px;
        }

        .note-title {
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: {{ $secondaryColor }};
            margin-bottom: 8px;
        }

        .note-copy {
            font-size: 14px;
            line-height: 1.75;
            color: #4b5563;
            margin: 0;
        }

        .cta-box {
            background: linear-gradient(135deg, rgba(20, 83, 45, 0.06), rgba(22, 163, 74, 0.04));
            border: 1px solid #d1fae5;
            border-radius: 20px;
            padding: 18px 20px;
            margin-bottom: 8px;
        }

        .cta-copy {
            font-size: 14px;
            line-height: 1.75;
            color: #374151;
            margin: 0;
        }

        .footer {
            background: #111827;
            color: #e5e7eb;
            padding: 24px 30px;
        }

        .footer-left {
            font-size: 13px;
            line-height: 1.8;
        }

        .footer-right {
            text-align: right;
            font-size: 12px;
            line-height: 1.7;
            color: #9ca3af;
        }

        @media screen and (max-width: 640px) {
            .email-wrapper {
                padding: 10px 0;
            }

            .email-container {
                border-radius: 0;
            }

            .hero,
            .content,
            .footer {
                padding-left: 18px !important;
                padding-right: 18px !important;
            }

            .hero-title {
                font-size: 22px !important;
            }

            .section-title {
                font-size: 22px !important;
            }

            .hero-contact {
                text-align: left !important;
                padding-top: 14px !important;
            }

            .stack,
            .stack tbody,
            .stack tr,
            .stack td {
                display: block !important;
                width: 100% !important;
            }

            .metric-cell {
                display: block !important;
                width: 100% !important;
                padding: 6px 0 !important;
            }

            .footer-right {
                text-align: left !important;
                padding-top: 16px !important;
            }

            .details-table td {
                display: block !important;
                width: 100% !important;
                padding: 4px 0 !important;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <table width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr>
                <td align="center">
                    <table class="email-container" width="100%" cellpadding="0" cellspacing="0" border="0">

                        <tr>
                            <td class="hero">
                                <table class="stack" width="100%" cellpadding="0" cellspacing="0" border="0">
                                    <tr>
                                        <td valign="middle" style="vertical-align: middle;">
                                            @if($logoUrl)
                                                <img src="{{ $logoUrl }}" alt="Logo" style="max-height: 68px; display: block;">
                                            @endif
                                        </td>
                                        <td class="hero-contact" valign="middle" style="vertical-align: middle;">
                                            <div class="hero-title">{{ $farmName }}</div>
                                            <div class="hero-tagline">{{ $farmTagline }}</div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        <tr>
                            <td class="content">
                                <span class="eyebrow">Payroll Communication</span>

                                <div class="section-title">
                                    Payslip for {{ $monthName }} {{ $year }}
                                </div>

                                <p class="section-copy">
                                    Dear <strong>{{ $employee->full_name }}</strong>,<br>
                                    Please find attached your payslip for <strong>{{ $monthName }} {{ $year }}</strong>.
                                    Below is a  summary of your payroll breakdown for this period.
                                </p>

                                <div class="card">
                                    <div class="card-title">Employee Details</div>

                                    <table class="details-table" width="100%" cellpadding="0" cellspacing="0" border="0">
                                        <tr>
                                            <td width="50%">
                                                <span class="details-label">Employee:</span> {{ $employee->full_name }}
                                            </td>
                                            <td width="50%">
                                                <span class="details-label">Employee No:</span> {{ $employee->employee_number ?? '-' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td width="50%">
                                                <span class="details-label">Department:</span> {{ $employee->department->name ?? '-' }}
                                            </td>
                                            <td width="50%">
                                                <span class="details-label">Job Title:</span> {{ $employee->jobTitle->name ?? '-' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td width="50%">
                                                <span class="details-label">Pay Period:</span>
                                                {{ optional($payslip->pay_period_start)->format('d M Y') }}
                                                -
                                                {{ optional($payslip->pay_period_end)->format('d M Y') }}
                                            </td>
                                            <td width="50%">
                                                <span class="details-label">Status:</span> Generated
                                            </td>
                                        </tr>
                                    </table>
                                </div>

                                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 22px;">
                                    <tr>
                                        <td class="metric-cell" width="33.33%">
                                            <div class="metric-box metric-green">
                                                <div class="metric-label">Gross Pay</div>
                                                <div class="metric-value">KES {{ number_format($grossPay, 2) }}</div>
                                            </div>
                                        </td>
                                        <td class="metric-cell" width="33.33%">
                                            <div class="metric-box metric-amber">
                                                <div class="metric-label">Taxable Pay</div>
                                                <div class="metric-value">KES {{ number_format($taxablePay, 2) }}</div>
                                            </div>
                                        </td>
                                        <td class="metric-cell" width="33.33%">
                                            <div class="metric-box metric-blue">
                                                <div class="metric-label">Net Pay</div>
                                                <div class="metric-value">KES {{ number_format($netPay, 2) }}</div>
                                            </div>
                                        </td>
                                    </tr>
                                </table>

                                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 22px;">
                                    <tr>
                                        <td class="metric-cell" width="25%">
                                            <div class="metric-box metric-slate">
                                                <div class="metric-label">NSSF</div>
                                                <div class="metric-sub-value">KES {{ number_format($nssf, 2) }}</div>
                                            </div>
                                        </td>
                                        <td class="metric-cell" width="25%">
                                            <div class="metric-box metric-slate">
                                                <div class="metric-label">SHA</div>
                                                <div class="metric-sub-value">KES {{ number_format($sha, 2) }}</div>
                                            </div>
                                        </td>
                                        <td class="metric-cell" width="25%">
                                            <div class="metric-box metric-slate">
                                                <div class="metric-label">Housing Levy</div>
                                                <div class="metric-sub-value">KES {{ number_format($housingLevy, 2) }}</div>
                                            </div>
                                        </td>
                                        <td class="metric-cell" width="25%">
                                            <div class="metric-box metric-slate">
                                                <div class="metric-label">PAYE</div>
                                                <div class="metric-sub-value">KES {{ number_format($paye, 2) }}</div>
                                            </div>
                                        </td>
                                    </tr>
                                </table>

                                <div class="breakdown-wrap">
                                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                        <tr class="breakdown-head">
                                            <td colspan="2">Payroll Breakdown Summary</td>
                                        </tr>

                                        <tr class="breakdown-row">
                                            <td style="font-weight:700; color:#111827;">NSSF</td>
                                            <td>KES {{ number_format($nssf, 2) }}</td>
                                        </tr>

                                        <tr class="breakdown-row">
                                            <td style="font-weight:700; color:#111827;">SHA</td>
                                            <td>KES {{ number_format($sha, 2) }}</td>
                                        </tr>

                                        <tr class="breakdown-row">
                                            <td style="font-weight:700; color:#111827;">Housing Levy</td>
                                            <td>KES {{ number_format($housingLevy, 2) }}</td>
                                        </tr>

                                        <tr class="breakdown-row">
                                            <td style="font-weight:700; color:#111827;">PAYE</td>
                                            <td>KES {{ number_format($paye, 2) }}</td>
                                        </tr>

                                        <tr class="breakdown-row">
                                            <td style="font-weight:700; color:#111827;">Other Deductions</td>
                                            <td>KES {{ number_format($otherDeductions, 2) }}</td>
                                        </tr>

                                        <tr class="breakdown-row breakdown-soft">
                                            <td>Total Statutory Deductions</td>
                                            <td>KES {{ number_format($totalStatutories, 2) }}</td>
                                        </tr>

                                        <tr class="breakdown-row breakdown-warm">
                                            <td>Total Deductions</td>
                                            <td>KES {{ number_format($totalDeductions, 2) }}</td>
                                        </tr>

                                        <tr class="breakdown-row breakdown-total">
                                            <td style="font-size:15px;">Net Pay</td>
                                            <td style="font-size:15px;">KES {{ number_format($netPay, 2) }}</td>
                                        </tr>
                                    </table>
                                </div>

                                <div class="note-box">
                                    <div class="note-title">Important Note</div>
                                    <p class="note-copy">
                                        Kindly review the attached PDF payslip for the full details.
                                        If you notice any discrepancy, please contact the HR or Finance office for clarification.
                                    </p>
                                </div>

                                <div class="cta-box">
                                    <p class="cta-copy">
                                        This message was generated automatically by the payroll system.
                                        For support, please use the official contacts below. Treat the attached payslip as confidential.
                                    </p>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td class="footer">
                                <table class="stack" width="100%" cellpadding="0" cellspacing="0" border="0">
                                    <tr>
                                        <td class="footer-left" width="50%">
                                            <strong style="color:#ffffff;">{{ $farmName }}</strong><br>
                                            {{ $farmCounty }}<br>
                                            {{ $farmPhone }}<br>
                                            {{ $farmEmail }}
                                        </td>
                                        <td class="footer-right" width="50%">
                                            This is an official automated payroll communication.<br>
                                            Please do not share your payslip with unauthorized persons.
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                    </table>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
