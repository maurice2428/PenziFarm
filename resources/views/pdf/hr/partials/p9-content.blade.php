@php
    $eatNow = now('Africa/Nairobi');

    $farmName = setting('farm.name', 'FArm Name');
    $employerPin = setting('company.kra_pin', 'XXX');

    $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
    ];

    $totalBasic = 0.0;
    $totalBenefits = 0.0;
    $totalQuarters = 0.0;
    $totalGross = 0.0;
    $totalAhl = 0.0;
    $totalShif = 0.0;
    $totalPrmf = 0.0;
    $totalOwnerOcc = 0.0;
    $totalDeductions = 0.0;
    $totalChargeable = 0.0;
    $totalTaxCharged = 0.0;
    $totalPersRelief = 0.0;
    $totalInsRelief = 0.0;
    $totalPaye = 0.0;

    $logoCandidates = [
        public_path('KRA-P9Form.png'),
        public_path('images/KRA-P9Form.png'),
        public_path('assets/KRA-P9Form.png'),
    ];

    $kraLogo = null;
    foreach ($logoCandidates as $candidate) {
        if (file_exists($candidate)) {
            $kraLogo = $candidate;
            break;
        }
    }
@endphp

<style>
    @page {
        margin: 18mm 12mm 18mm 12mm;
    }

    body {
        font-family: Courier, monospace;
        font-size: 8px;
        color: #111;
    }

    .logo-wrap {
        text-align: center;
        margin-bottom: 4px;
    }

    .kra-logo {
        width: 68mm;
    }

    .title-main {
        text-align: center;
        font-size: 12px;
        font-weight: bold;
        margin: 0;
    }

    .title-sub {
        text-align: center;
        font-size: 9px;
        color: #777;
        margin: 3px 0 0 0;
    }

    .title-year {
        text-align: center;
        font-size: 10px;
        color: #777;
        margin: 4px 0 8px 0;
    }

    .meta-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 8px;
    }

    .meta-table td {
        font-size: 8px;
        vertical-align: top;
        padding: 1px 2px;
    }

    .meta-left {
        text-align: left;
    }

    .meta-right {
        text-align: right;
    }

    .report {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .report th,
    .report td {
        border: 1px solid #777;
        padding: 3px 2px;
        text-align: center;
        vertical-align: middle;
        word-wrap: break-word;
    }

    .report th {
        font-weight: bold;
        background: #f5f5f5;
        line-height: 1.2;
    }

    .left {
        text-align: left !important;
    }

    .bold {
        font-weight: bold;
    }

    .notes-cell {
        text-align: left !important;
        font-size: 8px;
        line-height: 1.5;
        padding: 6px 8px !important;
    }

    .footer {
        margin-top: 18px;
        width: 100%;
        border-collapse: collapse;
    }

    .footer td {
        font-size: 9px;
    }

    .footer-left {
        text-align: left;
    }

    .footer-right {
        text-align: right;
    }
</style>

@if ($kraLogo)
    <div class="logo-wrap">
        <img src="{{ $kraLogo }}" class="kra-logo" alt="KRA Logo">
    </div>
@endif

<p class="title-main">KENYA REVENUE AUTHORITY - DOMESTIC TAXES DEPARTMENT</p>
<p class="title-sub">ISO 9001:2015 CERTIFIED</p>
<p class="title-year">TAX DEDUCTION CARD P9A FOR YEAR: {{ $year }}</p>

<table class="meta-table">
    <tr>
        <td class="meta-left">
            Employer's Name: {{ $farmName }}<br>
            Employee's Name: {{ $employee->full_name }}
        </td>
        <td class="meta-right">
            Employer's PIN: {{ $employerPin }}<br>
            Employee's PIN: {{ $employee->kra_pin ?? '-' }}
        </td>
    </tr>
</table>

<table class="report">
    <thead>
        <tr class="bold">
            <th style="width: 8%;">Month</th>
            <th style="width: 7%;">Basic Salary<br>Kshs.<br>A</th>
            <th style="width: 8%;">Benefits-<br>Non Cash<br>Kshs.<br>B</th>
            <th style="width: 7%;">Value of<br>Quarters<br>Kshs.<br>C</th>
            <th style="width: 8%;">Total<br>Gross Pay<br>Kshs.<br>D</th>
            <th style="width: 7%;">Affordable<br>H.Levy(<br>AHL)<br>Kshs.</th>
            <th style="width: 6%;">SHIF<br>Kshs.</th>
            <th style="width: 6%;">PRMF<br>Kshs.</th>
            <th style="width: 8%;">Owner-<br>Occupied<br>Interest<br>Kshs.</th>
            <th style="width: 7%;">Deduction<br>s<br>(Lower E+<br>F+G+H+I)</th>
            <th style="width: 8%;">Chargeabl<br>e Pay<br>(D - J)<br>Kshs.</th>
            <th style="width: 7%;">Tax<br>Charged<br>Kshs.</th>
            <th style="width: 7%;">Personal<br>Relief<br>Kshs.</th>
            <th style="width: 7%;">Insurance<br>Relief<br>Kshs.</th>
            <th style="width: 7%;">PAYE Tax<br>(L - M -<br>N)<br>Kshs.</th>
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

                    $chargeablePay = (float) ($row->taxable_pay ?? 0);
                    $totalDeduct = $gross - $chargeablePay;
                    if ($totalDeduct < 0) {
                        $totalDeduct = 0.0;
                    }

                    $paye = (float) ($row->paye ?? 0);
                    $personalRel = 2400.00;
                    $insuranceRel = 0.0;
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

        <tr class="bold">
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

        <tr>
            <td colspan="15" class="notes-cell">
                <b>NOTES:</b><br>
                1. Total Gross Pay includes all earnings before deductions.<br>
                2. Chargeable Pay is the income after allowable deductions (NSSF, AHL, SHIF, PRMF, owner occupied interest, etc.).<br>
                3. PAYE is tax deducted per month as per KRA bands.<br>
                4. Personal Relief is currently KES 2,400 per month (KES 28,800 per year) unless updated by law.<br>
                5. Where applicable, Insurance Relief and PRMF should be computed as per current KRA guidelines.<br>
                6. This P9A form summarises the year's payroll and is used when filing the annual return (IT1).
            </td>
        </tr>
    </tbody>
</table>

<table class="footer">
    <tr>
        <td class="footer-left">
            GENERATED ON: {{ strtoupper($eatNow->format('Y-m-d H:i:s')) }} BY: {{ strtoupper($generatedBy->roles->first()->name ?? 'ADMIN') }}
        </td>
        <td class="footer-right">
            Page 1 of 1
        </td>
    </tr>
</table>
