@php
    $eatNow = now('Africa/Nairobi');

    $farmName = setting('farm.name', 'PENZI FARM LIMITED');
    $farmTagline = setting('farm.tagline', 'Nurturing Quality, Inspiring Global Standards');
    $farmPhone = setting('farm.phone', '+254 700 000 000');
    $farmEmail = setting('farm.email', 'hr@penzifarm.co');
    $farmCounty = setting('farm.county', 'Kenya');
    $employerPin = setting('company.kra_pin', 'P052354902V');

    $primaryColor = setting('theme.primary', '#014a12');
    $secondaryColor = setting('theme.secondary', '#14532d');

    $logoPath = setting('branding.logo_light');
    $logoFullPath = $logoPath ? public_path('storage/' . ltrim($logoPath, '/')) : null;

    $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    ];

    $totalBasic = 0;
    $totalBenefits = 0;
    $totalQuarters = 0;
    $totalGross = 0;
    $totalAhl = 0;
    $totalShif = 0;
    $totalPrmf = 0;
    $totalOwnerOcc = 0;
    $totalDeductions = 0;
    $totalChargeable = 0;
    $totalTaxCharged = 0;
    $totalPersRelief = 0;
    $totalInsRelief = 0;
    $totalPaye = 0;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>KRA P9 Form</title>
    <style>
        @page { margin: 115px 20px 85px 20px; }

        body {
            font-family: Courier, sans-serif;
            font-size: 8.5px;
            color: #222;
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
            bottom: -65px;
            left: 0;
            right: 0;
            height: 55px;
            border-top: 1px solid #d1d5db;
            font-size: 9px;
            color: #4b5563;
        }

        .header-table,
        .footer-table,
        .info-table,
        .report {
            width: 100%;
            border-collapse: collapse;
        }

        .header-left { text-align: left; }
        .header-center { text-align: center; }
        .header-right { text-align: right; font-size: 10px; line-height: 1.5; color: #374151; }

        .logo { width: 150px; }

        .company-title {
            font-size: 20px;
            font-weight: bold;
            color: {{ $primaryColor }};
            text-align: center;
        }

        .tagline {
            font-size: 10px;
            color: #6b7280;
            font-style: italic;
            text-align: center;
        }

        .title-block {
            text-align: center;
            margin-bottom: 10px;
        }

        .title-block h1 {
            font-size: 13px;
            margin: 0;
        }

        .subtitle {
            font-size: 10px;
            color: #555;
            margin-top: 3px;
        }

        .info-box {
            border: 1px solid #dbe4d3;
            background: #f8fbf7;
            border-radius: 8px;
            padding: 8px 10px;
            margin-bottom: 10px;
        }

        .info-table td {
            padding: 4px 6px;
            vertical-align: top;
        }

        .label {
            font-weight: bold;
            width: 180px;
        }

        table.report thead th {
            background: {{ $primaryColor }};
            border: 1px solid {{ $primaryColor }};
            color: #fff;
            padding: 6px 4px;
            font-size: 7.8px;
            text-align: center;
            line-height: 1.2;
        }

        table.report tbody td {
            border: 1px solid #ddd;
            padding: 5px 4px;
            text-align: center;
            vertical-align: middle;
        }

        table.report tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .left {
            text-align: left !important;
        }

        .notes-box {
            margin-top: 10px;
            border: 1px solid #dbe4d3;
            background: #fbfdf9;
            border-radius: 8px;
            padding: 10px 12px;
            line-height: 1.5;
        }

        .notes-title {
            font-weight: bold;
            color: {{ $secondaryColor }};
            margin-bottom: 4px;
        }
    </style>
</head>
<body>
    <header>
        <table class="header-table">
            <tr>
                <td class="header-left" width="150">
                    @if ($logoFullPath && file_exists($logoFullPath))
                        <img src="{{ $logoFullPath }}" class="logo" alt="Logo">
                    @endif
                </td>
                <td class="header-center">
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
                <td>Generated on {{ $eatNow->format('d M Y, H:i') }} EAT</td>
                <td style="text-align:center;">KRA P9A Form</td>
                <td style="text-align:right;">Prepared by {{ $generatedBy->name ?? 'System' }}</td>
            </tr>
        </table>
    </footer>

    <main>
        <div class="title-block">
            <h1>KENYA REVENUE AUTHORITY - DOMESTIC TAXES DEPARTMENT</h1>
            <div class="subtitle">TAX DEDUCTION CARD P9A FOR YEAR: {{ $year }}</div>
        </div>

        <div class="info-box">
            <table class="info-table">
                <tr>
                    <td class="label">Employer's Name</td>
                    <td>{{ $farmName }}</td>
                    <td class="label">Employer's PIN</td>
                    <td>{{ $employerPin }}</td>
                </tr>
                <tr>
                    <td class="label">Employee's Name</td>
                    <td>{{ $employee->full_name }}</td>
                    <td class="label">Employee's PIN</td>
                    <td>{{ $employee->kra_pin ?? '-' }}</td>
                </tr>
            </table>
        </div>

        <table class="report">
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Basic Salary<br>A</th>
                    <th>Benefits / Non Cash<br>B</th>
                    <th>Value of Quarters<br>C</th>
                    <th>Total Gross Pay<br>D</th>
                    <th>Affordable H. Levy</th>
                    <th>SHIF / SHA</th>
                    <th>PRMF</th>
                    <th>Owner-Occupied Interest</th>
                    <th>Deductions<br>J</th>
                    <th>Chargeable Pay<br>(D - J)</th>
                    <th>Tax Charged</th>
                    <th>Personal Relief</th>
                    <th>Insurance Relief</th>
                    <th>PAYE Tax</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($months as $mNum => $mName)
                    @php
                        $row = $byMonth[$mNum] ?? null;

                        if ($row) {
                            $basic = (float) ($row->basic_salary ?? 0);
                            $benef = (float) ($row->allowances_total ?? 0);
                            $quarter = 0.0;
                            $gross = (float) ($row->gross_pay ?? 0);
                            $ahl = (float) ($row->housing_levy ?? 0);
                            $shif = (float) ($row->sha ?? 0);
                            $prmf = 0.0;
                            $ownerOcc = 0.0;

                            $totalDeduct = $ahl + $shif + $prmf + $ownerOcc + (float) ($row->nssf ?? 0);
                            $chargeablePay = (float) ($row->taxable_pay ?? 0);
                            $personalRel = 0.0;
                            $insuranceRel = 0.0;
                            $paye = (float) ($row->paye ?? 0);
                            $taxCharged = $paye + $personalRel + $insuranceRel;
                        } else {
                            $basic = $benef = $quarter = $gross = 0.0;
                            $ahl = $shif = $prmf = $ownerOcc = 0.0;
                            $totalDeduct = 0.0;
                            $chargeablePay = 0.0;
                            $taxCharged = 0.0;
                            $personalRel = 0.0;
                            $insuranceRel = 0.0;
                            $paye = 0.0;
                        }

                        $totalBasic += $basic;
                        $totalBenefits += $benef;
                        $totalQuarters += $quarter;
                        $totalGross += $gross;
                        $totalAhl += $ahl;
                        $totalShif += $shif;
                        $totalPrmf += $prmf;
                        $totalOwnerOcc += $ownerOcc;
                        $totalDeductions += $totalDeduct;
                        $totalChargeable += $chargeablePay;
                        $totalTaxCharged += $taxCharged;
                        $totalPersRelief += $personalRel;
                        $totalInsRelief += $insuranceRel;
                        $totalPaye += $paye;
                    @endphp
                    <tr>
                        <td class="left">{{ $mName }}</td>
                        <td>{{ number_format($basic, 2) }}</td>
                        <td>{{ number_format($benef, 2) }}</td>
                        <td>{{ number_format($quarter, 2) }}</td>
                        <td>{{ number_format($gross, 2) }}</td>
                        <td>{{ number_format($ahl, 2) }}</td>
                        <td>{{ number_format($shif, 2) }}</td>
                        <td>{{ number_format($prmf, 2) }}</td>
                        <td>{{ number_format($ownerOcc, 2) }}</td>
                        <td>{{ number_format($totalDeduct, 2) }}</td>
                        <td>{{ number_format($chargeablePay, 2) }}</td>
                        <td>{{ number_format($taxCharged, 2) }}</td>
                        <td>{{ number_format($personalRel, 2) }}</td>
                        <td>{{ number_format($insuranceRel, 2) }}</td>
                        <td>{{ number_format($paye, 2) }}</td>
                    </tr>
                @endforeach

                <tr style="font-weight:bold; background:#f3f4f6;">
                    <td class="left">Total</td>
                    <td>{{ number_format($totalBasic, 2) }}</td>
                    <td>{{ number_format($totalBenefits, 2) }}</td>
                    <td>{{ number_format($totalQuarters, 2) }}</td>
                    <td>{{ number_format($totalGross, 2) }}</td>
                    <td>{{ number_format($totalAhl, 2) }}</td>
                    <td>{{ number_format($totalShif, 2) }}</td>
                    <td>{{ number_format($totalPrmf, 2) }}</td>
                    <td>{{ number_format($totalOwnerOcc, 2) }}</td>
                    <td>{{ number_format($totalDeductions, 2) }}</td>
                    <td>{{ number_format($totalChargeable, 2) }}</td>
                    <td>{{ number_format($totalTaxCharged, 2) }}</td>
                    <td>{{ number_format($totalPersRelief, 2) }}</td>
                    <td>{{ number_format($totalInsRelief, 2) }}</td>
                    <td>{{ number_format($totalPaye, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="notes-box">
            <div class="notes-title">Notes:</div>
            1. Total Gross Pay includes all earnings before deductions.<br>
            2. Chargeable Pay is income after allowable deductions.<br>
            3. PAYE shown is monthly tax deducted and remitted.<br>
            4. Personal Relief and Insurance Relief are shown as applicable based on current payroll setup.<br>
            5. This P9A summarises the employee's payroll records for the year selected.
        </div>
    </main>
</body>
</html>
