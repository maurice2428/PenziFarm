@php
    if (! function_exists('payslipPdfImageBase64')) {
        function payslipPdfImageBase64(mixed $path): ?string
        {
            if (is_array($path)) {
                $path = collect($path)
                    ->flatten()
                    ->first(
                        fn (mixed $value): bool =>
                            is_string($value)
                            && trim($value) !== ''
                    );
            }

            if (! is_string($path) || blank($path)) {
                return null;
            }

            $cleanPath = preg_replace(
                '#^(storage/|public/)#',
                '',
                ltrim(trim($path), '/')
            );

            foreach ([
                storage_path('app/public/' . $cleanPath),
                public_path('storage/' . $cleanPath),
                public_path($cleanPath),
            ] as $fullPath) {
                if (! is_file($fullPath)) {
                    continue;
                }

                $extension = strtolower(
                    pathinfo($fullPath, PATHINFO_EXTENSION)
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
        setting(
            'company.name',
            setting(
                'organization.name',
                config('app.name', 'Penzi Farm')
            )
        )
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
        'branding.primary_color',
        setting(
            'theme.primary',
            setting(
                'theme.primary_color',
                '#166534'
            )
        )
    );

    $secondaryColor = setting(
        'branding.secondary_color',
        setting(
            'theme.secondary',
            setting(
                'theme.secondary_color',
                '#14532d'
            )
        )
    );

    $patternColor = preg_match(
        '/^#[0-9a-fA-F]{6}$/',
        (string) $primaryColor
    )
        ? $primaryColor
        : '#166534';

    $logoPath = setting('branding.logo_light')
        ?: setting('branding.logo')
        ?: setting('company.logo')
        ?: setting('farm.logo');

    $logoBase64 = payslipPdfImageBase64($logoPath);
    $avatarBase64 = payslipPdfImageBase64(
        $employee->avatar_path
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

    $payrollStatus = method_exists(
        $payroll,
        'statusValue'
    )
        ? $payroll->statusValue()
        : ($payroll->status ?? 'processed');

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

    $grossPay = $basicPay + $allowances;
    $netRecorded = (float) $payslip->net_pay;

    $employeeNumber = $employee->employee_number
        ?: 'Not assigned';

    $employeeName = $employee->full_name
        ?: trim(implode(' ', array_filter([
            $employee->first_name,
            $employee->middle_name,
            $employee->last_name,
        ])));

    $surname = mb_strtoupper(
        (string) (
            $employee->last_name
            ?: collect(
                preg_split(
                    '/\s+/',
                    trim($employeeName)
                ) ?: []
            )->last()
            ?: 'NOT PROVIDED'
        )
    );

    $givenNames = mb_strtoupper(
        trim(implode(' ', array_filter([
            $employee->first_name,
            $employee->middle_name,
        ])))
        ?: $employeeName
        ?: 'NOT PROVIDED'
    );

    $initials = collect([
        $employee->first_name,
        $employee->middle_name,
        $employee->last_name,
    ])
        ->filter()
        ->map(
            fn (mixed $name): string =>
                mb_strtoupper(
                    mb_substr((string) $name, 0, 1)
                )
        )
        ->take(3)
        ->implode('');

    $idNumberRaw = trim(
        (string) ($employee->id_passport_number ?? '')
    );

    $maskedId = $idNumberRaw === ''
        ? 'NOT PROVIDED'
        : (
            mb_strlen($idNumberRaw) <= 4
                ? str_repeat(
                    '*',
                    mb_strlen($idNumberRaw)
                )
                : str_repeat(
                    '*',
                    max(mb_strlen($idNumberRaw) - 4, 4)
                )
                    . mb_substr($idNumberRaw, -4)
        );

    $dateOfBirth = $employee->date_of_birth
        ? \Illuminate\Support\Carbon::parse(
            $employee->date_of_birth
        )->format('d.m.Y')
        : 'NOT PROVIDED';

    $issueDate = $employee->created_at
        ? \Illuminate\Support\Carbon::parse(
            $employee->created_at
        )->format('d.m.Y')
        : $eatNow->format('d.m.Y');

    $validUntil = $employee->contract_end_date
        ? \Illuminate\Support\Carbon::parse(
            $employee->contract_end_date
        )->format('d.m.Y')
        : 'WHILE EMPLOYED';

    $employeeStatus = mb_strtoupper(
        str_replace(
            '_',
            ' ',
            (string) (
                $employee->status
                ?: 'ACTIVE'
            )
        )
    );

    $payslipReference = sprintf(
        '%s-%04d-%02d',
        preg_replace(
            '/[^A-Z0-9]/i',
            '',
            $employeeNumber
        ) ?: 'EMPLOYEE',
        (int) $payroll->year,
        (int) $payroll->month
    );

    /*
     * DomPDF-friendly security pattern.
     *
     * This SVG is embedded as a data URI and placed behind the staff-card
     * content. It reproduces the fine curved lines used on identity cards
     * without relying on unsupported CSS gradients or color-mix().
     */
    $securityPatternSvg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="280" viewBox="0 0 1200 280">
    <rect width="1200" height="280" fill="#f8faf8"/>

    <!-- Dense left-side guilloche field -->
    <g fill="none" stroke="{$patternColor}" stroke-linecap="round">
        <g stroke-width="0.85" stroke-opacity="0.115">
            <ellipse cx="235" cy="152" rx="300" ry="122"/>
            <ellipse cx="235" cy="152" rx="280" ry="114"/>
            <ellipse cx="235" cy="152" rx="260" ry="106"/>
            <ellipse cx="235" cy="152" rx="240" ry="98"/>
            <ellipse cx="235" cy="152" rx="220" ry="90"/>
            <ellipse cx="235" cy="152" rx="200" ry="82"/>
            <ellipse cx="235" cy="152" rx="180" ry="74"/>
            <ellipse cx="235" cy="152" rx="160" ry="66"/>
            <ellipse cx="235" cy="152" rx="140" ry="58"/>
            <ellipse cx="235" cy="152" rx="120" ry="50"/>
            <ellipse cx="235" cy="152" rx="100" ry="42"/>
            <ellipse cx="235" cy="152" rx="80" ry="34"/>
        </g>

        <!-- Dense right-side guilloche field -->
        <g stroke-width="0.85" stroke-opacity="0.115">
            <ellipse cx="970" cy="146" rx="300" ry="122"/>
            <ellipse cx="970" cy="146" rx="280" ry="114"/>
            <ellipse cx="970" cy="146" rx="260" ry="106"/>
            <ellipse cx="970" cy="146" rx="240" ry="98"/>
            <ellipse cx="970" cy="146" rx="220" ry="90"/>
            <ellipse cx="970" cy="146" rx="200" ry="82"/>
            <ellipse cx="970" cy="146" rx="180" ry="74"/>
            <ellipse cx="970" cy="146" rx="160" ry="66"/>
            <ellipse cx="970" cy="146" rx="140" ry="58"/>
            <ellipse cx="970" cy="146" rx="120" ry="50"/>
            <ellipse cx="970" cy="146" rx="100" ry="42"/>
            <ellipse cx="970" cy="146" rx="80" ry="34"/>
        </g>

        <!-- Mid-field overlapping rings for the concentrated identity look -->
        <g stroke-width="0.6" stroke-opacity="0.08">
            <ellipse cx="425" cy="150" rx="235" ry="96"/>
            <ellipse cx="425" cy="150" rx="215" ry="88"/>
            <ellipse cx="425" cy="150" rx="195" ry="80"/>
            <ellipse cx="425" cy="150" rx="175" ry="72"/>
            <ellipse cx="425" cy="150" rx="155" ry="64"/>
            <ellipse cx="425" cy="150" rx="135" ry="56"/>
            <ellipse cx="775" cy="148" rx="235" ry="96"/>
            <ellipse cx="775" cy="148" rx="215" ry="88"/>
            <ellipse cx="775" cy="148" rx="195" ry="80"/>
            <ellipse cx="775" cy="148" rx="175" ry="72"/>
            <ellipse cx="775" cy="148" rx="155" ry="64"/>
            <ellipse cx="775" cy="148" rx="135" ry="56"/>
        </g>

        <!-- Top centre fan, inspired by the identity header security geometry -->
        <g stroke-width="0.75" stroke-opacity="0.07">
            <path d="M600 36 L520 118 L600 154 L680 118 Z"/>
            <path d="M600 24 L502 118 L600 170 L698 118 Z"/>
            <path d="M600 12 L484 118 L600 186 L716 118 Z"/>
            <path d="M600 0 L466 118 L600 202 L734 118 Z"/>
            <path d="M600 -12 L448 118 L600 218 L752 118 Z"/>
        </g>

        <!-- Flowing lower wave bed to soften the card and mimic secure paper curves -->
        <g stroke-width="0.7" stroke-opacity="0.065">
            <path d="M-100 78 C110 8 250 140 470 78 S830 8 1048 78 S1306 140 1450 58"/>
            <path d="M-120 104 C90 34 250 166 470 104 S830 34 1048 104 S1306 166 1470 84"/>
            <path d="M-140 130 C70 60 250 192 470 130 S830 60 1048 130 S1306 192 1490 110"/>
            <path d="M-160 156 C50 86 250 218 470 156 S830 86 1048 156 S1306 218 1510 136"/>
            <path d="M-180 182 C30 112 250 244 470 182 S830 112 1048 182 S1306 244 1530 162"/>
            <path d="M-200 208 C10 138 250 270 470 208 S830 138 1048 208 S1306 270 1550 188"/>
            <path d="M-220 234 C-10 164 250 296 470 234 S830 164 1048 234 S1306 296 1570 214"/>
        </g>

        <!-- Very subtle micro cross-lines -->
        <g stroke="#64748b" stroke-width="0.48" stroke-opacity="0.045">
            <path d="M0 20 L1200 260"/>
            <path d="M0 52 L1200 292"/>
            <path d="M0 -12 L1200 228"/>
            <path d="M1200 18 L0 258"/>
            <path d="M1200 50 L0 290"/>
            <path d="M1200 -14 L0 226"/>
        </g>
    </g>
</svg>
SVG;

    $securityPattern = 'data:image/svg+xml;base64,'
        . base64_encode($securityPatternSvg);
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>
        {{ $monthName }}
        {{ $payroll->year }}
        Payslip
    </title>

    <style>
        @page {
            margin: 108px 32px 86px 32px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Courier, sans-serif;
            font-size: 9.6px;
            color: #263129;
            background: #ffffff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        header {
            position: fixed;
            top: -92px;
            left: 0;
            right: 0;
            height: 80px;
            padding-bottom: 7px;
            border-bottom: 2px solid {{ $primaryColor }};
        }

        footer {
            position: fixed;
            bottom: -65px;
            left: 0;
            right: 0;
            height: 52px;
            padding-top: 6px;
            border-top: 1px solid #cbd5ce;
            color: #536057;
            font-size: 8px;
        }

        .logo {
            width: 128px;
            max-height: 60px;
        }

        .company {
            text-align: center;
            color: {{ $secondaryColor }};
            font-size: 11.5px;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .title {
            margin-top: 2px;
            text-align: center;
            color: {{ $primaryColor }};
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 0.7px;
        }

        .tagline {
            margin-top: 2px;
            text-align: center;
            color: #6b756e;
            font-size: 8px;
            font-style: italic;
        }

        .contact {
            text-align: right;
            color: #48534c;
            font-size: 8px;
            line-height: 1.45;
        }

        .document-strip {
            margin-bottom: 8px;
            border: 1px solid #cdd8cf;
            background: #f2f6f3;
        }

        .document-strip td {
            padding: 6px 9px;
            border-right: 1px solid #dbe2dc;
            vertical-align: middle;
        }

        .document-strip td:last-child {
            border-right: 0;
        }

        .document-label {
            color: #68746c;
            font-size: 7.2px;
            font-weight: bold;
            letter-spacing: 0.65px;
            text-transform: uppercase;
        }

        .document-value {
            margin-top: 2px;
            color: {{ $primaryColor }};
            font-size: 8.8px;
            font-weight: bold;
        }

        .section {
            margin-top: 9px;
            page-break-inside: avoid;
        }

        .section-heading {
            padding: 6px 9px;
            border-left: 5px solid {{ $primaryColor }};
            border-top: 1px solid #d4ded6;
            border-right: 1px solid #d4ded6;
            background: #eff4f0;
            color: {{ $primaryColor }};
            font-size: 8.6px;
            font-weight: bold;
            letter-spacing: 0.75px;
            text-transform: uppercase;
        }

        /*
         * Compact Kenyan-ID-inspired staff card.
         *
         * It uses the same field order as the employee profile card while
         * remaining small enough for a one-page payslip.
         */
        .staff-card-shell {
            position: relative;
            overflow: hidden;
            border: 1px solid #aab9ad;
            background: #f9fbf9;
        }

        /*
         * DomPDF renders a normal embedded image more consistently than a
         * CSS background-image. This image is the security-line layer.
         */
        .staff-card-security-pattern {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.98;
        }

        .staff-card {
            position: relative;
            width: 100%;
            border: 0;
            background: transparent;
        }

        .staff-card-header td {
            padding: 7px 11px;
            border-bottom: 1px solid rgba(22, 101, 52, 0.20);
            vertical-align: middle;
        }

        .staff-card-org {
            color: {{ $primaryColor }};
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 0.65px;
            text-transform: uppercase;
        }

        .staff-card-subtitle {
            margin-top: 2px;
            color: #6b756e;
            font-size: 6.7px;
            font-weight: bold;
            letter-spacing: 0.85px;
            text-transform: uppercase;
        }

        .staff-card-emblem {
            width: 42px;
            height: 42px;
            margin: 0 auto;
            border: 1px solid #99af9e;
            border-radius: 50%;
            background: #ffffff;
            text-align: center;
            overflow: hidden;
        }

        .staff-card-emblem-text {
            line-height: 42px;
            color: {{ $primaryColor }};
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 0.9px;
        }

        .staff-card-document {
            text-align: right;
        }

        .staff-card-document strong {
            display: block;
            color: {{ $primaryColor }};
            font-size: 9.6px;
            letter-spacing: 0.55px;
            text-transform: uppercase;
        }

        .staff-card-document span {
            display: block;
            margin-top: 2px;
            color: #6b756e;
            font-size: 6.7px;
            font-weight: bold;
            letter-spacing: 0.75px;
            text-transform: uppercase;
        }

        .staff-card-body > tbody > tr > td {
            vertical-align: top;
        }

        .staff-photo-cell {
            width: 132px;
            padding: 9px 10px 7px;
            border-right: 1px solid rgba(22, 101, 52, 0.18);
            text-align: center;
            background: rgba(231, 240, 233, 0.56);
        }

        .staff-photo-frame {
            width: 100px;
            height: 108px;
            margin: 0 auto;
            padding: 3px;
            border: 1px solid #9daf9f;
            background: rgba(255, 255, 255, 0.85);
        }

        .staff-photo {
            width: 94px;
            height: 102px;
            object-fit: cover;
            object-position: center 24%;
        }

        .staff-photo-placeholder {
            width: 94px;
            height: 102px;
            line-height: 102px;
            background: #dce7de;
            color: {{ $primaryColor }};
            font-size: 17px;
            font-weight: bold;
            text-align: center;
        }

        .staff-number {
            display: inline-block;
            margin-top: 7px;
            padding: 4px 8px;
            border: 1px solid {{ $primaryColor }};
            background: {{ $primaryColor }};
            color: #ffffff;
            font-size: 8.2px;
            font-weight: bold;
            letter-spacing: 0.6px;
        }

        .staff-status {
            margin-top: 5px;
            color: #506057;
            font-size: 6.7px;
            font-weight: bold;
            letter-spacing: 0.65px;
            text-transform: uppercase;
        }

        .staff-identity-cell {
            padding: 8px 10px 7px;
        }

        .staff-name-table td {
            width: 50%;
            padding: 0 7px 6px 0;
            vertical-align: top;
        }

        .staff-name-table td:last-child {
            padding-right: 0;
            padding-left: 7px;
        }

        .staff-field {
            padding-left: 7px;
            border-left: 2px solid rgba(22, 101, 52, 0.30);
        }

        .staff-label {
            color: #68746c;
            font-size: 6.5px;
            font-weight: bold;
            letter-spacing: 0.55px;
            text-transform: uppercase;
        }

        .staff-value {
            margin-top: 2px;
            color: #202a23;
            font-size: 8.2px;
            font-weight: bold;
            line-height: 1.18;
        }

        .staff-value-name {
            color: {{ $primaryColor }};
            font-size: 12px;
            letter-spacing: 0.2px;
        }

        .staff-details-table {
            margin-top: 2px;
        }

        .staff-details-table td {
            width: 25%;
            padding: 4px 7px 4px 0;
            vertical-align: top;
        }

        .staff-details-table td:last-child {
            padding-right: 0;
        }

        .staff-employment-strip {
            margin-top: 6px;
            border: 1px solid rgba(22, 101, 52, 0.16);
            background: rgba(22, 101, 52, 0.045);
        }

        .staff-employment-strip td {
            width: 33.333%;
            padding: 5px 7px;
            border-right: 1px solid rgba(22, 101, 52, 0.13);
            vertical-align: top;
        }

        .staff-employment-strip td:last-child {
            border-right: 0;
        }

        .staff-card-footer td {
            padding: 5px 10px;
            border-top: 1px solid rgba(22, 101, 52, 0.18);
            color: #667269;
            font-size: 6.6px;
            vertical-align: middle;
        }

        .staff-card-footer strong {
            color: {{ $primaryColor }};
            font-size: 6.8px;
            letter-spacing: 0.55px;
            text-transform: uppercase;
        }

        .staff-card-verified {
            text-align: right;
        }

        .staff-card-verified span {
            display: block;
            margin-top: 1px;
            color: {{ $primaryColor }};
            font-size: 7.1px;
            font-weight: bold;
        }

        .breakdown-wrap {
            border: 1px solid #ccd6ce;
            background: #ffffff;
        }

        .breakdown-wrap > tbody > tr > td {
            width: 50%;
            vertical-align: top;
        }

        .breakdown-wrap > tbody > tr > td:first-child {
            border-right: 1px solid #ccd6ce;
        }

        .pay-table {
            width: 100%;
        }

        .pay-table .panel-heading {
            padding: 7px 9px;
            color: #ffffff;
            font-size: 8.2px;
            font-weight: bold;
            letter-spacing: 0.65px;
            text-transform: uppercase;
        }

        .earnings-heading {
            background: {{ $primaryColor }};
        }

        .deductions-heading {
            background: #455249;
        }

        .pay-table td {
            padding: 5.5px 9px;
            border-bottom: 1px solid #e0e5e1;
        }

        .pay-table tr.item-alt td {
            background: #f8faf8;
        }

        .pay-label {
            color: #3e4941;
        }

        .pay-amount {
            width: 128px;
            text-align: right;
            color: #263129;
            font-weight: bold;
        }

        .sub-total td {
            border-top: 1px solid #9eaaa0;
            border-bottom: 0;
            background: #eef2ef;
            font-weight: bold;
        }

        .summary-cards {
            margin-top: 8px;
            border: 1px solid #b9c5bc;
        }

        .summary-cards td {
            width: 33.333%;
            padding: 8px 10px;
            border-right: 1px solid #ccd5ce;
            vertical-align: middle;
            background: #f5f7f5;
        }

        .summary-cards td:last-child {
            border-right: 0;
            background: #eaf3ec;
        }

        .summary-label {
            color: #69746c;
            font-size: 7.2px;
            font-weight: bold;
            letter-spacing: 0.6px;
            text-transform: uppercase;
        }

        .summary-value {
            margin-top: 3px;
            color: #263129;
            font-size: 11px;
            font-weight: bold;
        }

        .summary-value-net {
            color: {{ $primaryColor }};
            font-size: 13px;
        }

        .advance-note {
            margin-top: 7px;
            padding: 6px 9px;
            border: 1px solid #d7ded8;
            background: #fafbfa;
            color: #4e5951;
            font-size: 8px;
        }

        .approval {
            margin-top: 12px;
            page-break-inside: avoid;
        }

        .approval td {
            width: 50%;
            padding: 7px 10px;
            text-align: center;
            vertical-align: bottom;
        }

        .approval-box {
            padding: 7px;
            border: 1px solid #d4dbd5;
            background: #fbfcfb;
        }

        .approval-image {
            max-width: 145px;
            max-height: 60px;
        }

        .stamp-image {
            max-width: 115px;
            max-height: 74px;
        }

        .approval-line {
            width: 74%;
            margin: 5px auto 3px;
            border-top: 1px solid #374151;
        }

        .approval-caption {
            color: #677169;
            font-size: 7.1px;
            text-transform: uppercase;
        }

        .note {
            margin-top: 9px;
            padding: 7px 9px;
            border-left: 4px solid {{ $primaryColor }};
            border-top: 1px solid #dbe3dc;
            border-right: 1px solid #dbe3dc;
            border-bottom: 1px solid #dbe3dc;
            background: #f7f9f7;
            color: #465148;
            font-size: 8px;
            line-height: 1.38;
        }

        .confidential {
            color: {{ $primaryColor }};
            font-weight: bold;
        }
    </style>
</head>

<body>
<header>
    <table>
        <tr>
            <td width="145">
                @if ($logoBase64)
                    <img
                        src="{{ $logoBase64 }}"
                        class="logo"
                        alt="Logo"
                    >
                @endif
            </td>

            <td>
                <div class="company">
                    {{ $farmName }}
                </div>

                <div class="title">
                    {{ $monthName }}
                    {{ $payroll->year }}
                    PAYSLIP
                </div>

                <div class="tagline">
                    {{ $farmTagline }}
                </div>
            </td>

            <td width="205" class="contact">
                <strong>Phone:</strong>
                {{ $farmPhone }}<br>

                <strong>Email:</strong>
                {{ $farmEmail }}<br>

                <strong>County:</strong>
                {{ $farmCounty }}
            </td>
        </tr>
    </table>
</header>

<footer>
    <table>
        <tr>
            <td>
                Generated
                {{ $eatNow->format('d M Y, H:i') }}
                EAT
            </td>

            <td style="text-align:center;">
                <span class="confidential">
                    Confidential Employee Payslip
                </span>
            </td>

            <td style="text-align:right;">
                Prepared by
                {{ $generatedBy->name ?? 'System' }}
            </td>
        </tr>

        <tr>
            <td
                colspan="3"
                style="
                    padding-top: 4px;
                    text-align: center;
                "
            >
                {{ $farmName }}
                &bull;
                {{ $farmPhone }}
                &bull;
                {{ $farmEmail }}
            </td>
        </tr>
    </table>
</footer>

<main>
    <table class="document-strip">
        <tr>
            <td>
                <div class="document-label">
                    Payslip Reference
                </div>

                <div class="document-value">
                    {{ $payslipReference }}
                </div>
            </td>

            <td style="text-align:center;">
                <div class="document-label">
                    Pay Period
                </div>

                <div class="document-value">
                    {{ optional($payslip->pay_period_start)->format('d M Y') }}
                    -
                    {{ optional($payslip->pay_period_end)->format('d M Y') }}
                </div>
            </td>

            <td style="text-align:right;">
                <div class="document-label">
                    Payroll Status
                </div>

                <div class="document-value">
                    {{ ucfirst($payrollStatus) }}
                </div>
            </td>
        </tr>
    </table>

    <div class="section">
        <div class="section-heading">
            Staff Identification
        </div>

        <div class="staff-card-shell">
            <img
                src="{{ $securityPattern }}"
                class="staff-card-security-pattern"
                alt=""
            >

            <table class="staff-card">
                <tr class="staff-card-header">
                <td width="42%">
                    <div class="staff-card-org">
                        {{ $farmName }}
                    </div>

                    <div class="staff-card-subtitle">
                        S T A F F &nbsp; I D E N T I F I C A T I O N
                    </div>
                </td>

                <td width="16%" style="text-align:center;">
                    <div class="staff-card-emblem">
                        <div class="staff-card-emblem-text">
                            {{ $initials ?: 'HR' }}
                        </div>
                    </div>
                </td>

                <td width="42%" class="staff-card-document">
                    <strong>
                        Internal Staff Card
                    </strong>

                    <span>
                        H U M A N &nbsp; R E S O U R C E &nbsp; R E C O R D
                    </span>
                </td>
            </tr>

            <tr>
                <td colspan="3" style="padding:0;">
                    <table class="staff-card-body">
                        <tr>
                            <td class="staff-photo-cell">
                                <div class="staff-photo-frame">
                                    @if ($avatarBase64)
                                        <img
                                            src="{{ $avatarBase64 }}"
                                            class="staff-photo"
                                            alt="Employee Photo"
                                        >
                                    @else
                                        <div class="staff-photo-placeholder">
                                            {{ $initials ?: 'ID' }}
                                        </div>
                                    @endif
                                </div>

                                <div class="staff-number">
                                    {{ $employeeNumber }}
                                </div>

                                <div class="staff-status">
                                    {{ $employeeStatus }}
                                </div>
                            </td>

                            <td class="staff-identity-cell">
                                <table class="staff-name-table">
                                    <tr>
                                        <td>
                                            <div class="staff-field">
                                                <div class="staff-label">
                                                    Surname
                                                </div>

                                                <div class="staff-value staff-value-name">
                                                    {{ $surname }}
                                                </div>
                                            </div>
                                        </td>

                                        <td>
                                            <div class="staff-field">
                                                <div class="staff-label">
                                                    Given Names
                                                </div>

                                                <div class="staff-value staff-value-name">
                                                    {{ $givenNames }}
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </table>

                                <table class="staff-details-table">
                                    <tr>
                                        <td>
                                            <div class="staff-field">
                                                <div class="staff-label">
                                                    Sex
                                                </div>

                                                <div class="staff-value">
                                                    {{ mb_strtoupper($employee->gender ?: 'NOT PROVIDED') }}
                                                </div>
                                            </div>
                                        </td>

                                        <td>
                                            <div class="staff-field">
                                                <div class="staff-label">
                                                    Nationality
                                                </div>

                                                <div class="staff-value">
                                                    {{ mb_strtoupper($employee->nationality ?: 'NOT PROVIDED') }}
                                                </div>
                                            </div>
                                        </td>

                                        <td>
                                            <div class="staff-field">
                                                <div class="staff-label">
                                                    Date of Birth
                                                </div>

                                                <div class="staff-value">
                                                    {{ $dateOfBirth }}
                                                </div>
                                            </div>
                                        </td>

                                        <td>
                                            <div class="staff-field">
                                                <div class="staff-label">
                                                    Place of Birth
                                                </div>

                                                <div class="staff-value">
                                                    {{ mb_strtoupper($employee->place_of_birth ?: 'NOT PROVIDED') }}
                                                </div>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>
                                            <div class="staff-field">
                                                <div class="staff-label">
                                                    National ID / Passport
                                                </div>

                                                <div class="staff-value">
                                                    {{ $maskedId }}
                                                </div>
                                            </div>
                                        </td>

                                        <td>
                                            <div class="staff-field">
                                                <div class="staff-label">
                                                    County
                                                </div>

                                                <div class="staff-value">
                                                    {{ mb_strtoupper($employee->county ?: 'NOT PROVIDED') }}
                                                </div>
                                            </div>
                                        </td>

                                        <td>
                                            <div class="staff-field">
                                                <div class="staff-label">
                                                    Date of Issue
                                                </div>

                                                <div class="staff-value">
                                                    {{ $issueDate }}
                                                </div>
                                            </div>
                                        </td>

                                        <td>
                                            <div class="staff-field">
                                                <div class="staff-label">
                                                    Valid Until
                                                </div>

                                                <div class="staff-value">
                                                    {{ $validUntil }}
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </table>

                                <table class="staff-employment-strip">
                                    <tr>
                                        <td>
                                            <div class="staff-label">
                                                Job Title
                                            </div>

                                            <div class="staff-value">
                                                {{ $employee->jobTitle?->name ?: 'Not assigned' }}
                                            </div>
                                        </td>

                                        <td>
                                            <div class="staff-label">
                                                Department
                                            </div>

                                            <div class="staff-value">
                                                {{ $employee->department?->name ?: 'Not assigned' }}
                                            </div>
                                        </td>

                                        <td>
                                            <div class="staff-label">
                                                Work Station
                                            </div>

                                            <div class="staff-value">
                                                {{ $employee->work_station ?: 'Not assigned' }}
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr class="staff-card-footer">
                <td colspan="2">
                    <strong>
                        Internal Use Only
                    </strong><br>

                    Organization-issued staff identification.
                    Not a Kenyan National Identity Card.
                </td>

                <td class="staff-card-verified">
                    HR Verified Record

                    <span>
                        {{ $eatNow->format('d M Y') }}
                    </span>
                </td>
            </tr>
            </table>
        </div>
    </div>

    <div class="section">
        <div class="section-heading">
            Payroll Breakdown
        </div>

        <table class="breakdown-wrap">
            <tr>
                <td>
                    <table class="pay-table">
                        <tr>
                            <td
                                colspan="2"
                                class="panel-heading earnings-heading"
                            >
                                Earnings
                            </td>
                        </tr>

                        <tr>
                            <td class="pay-label">
                                Basic Pay
                            </td>

                            <td class="pay-amount">
                                KES
                                {{ number_format($basicPay, 2) }}
                            </td>
                        </tr>

                        <tr class="item-alt">
                            <td class="pay-label">
                                Allowances
                            </td>

                            <td class="pay-amount">
                                KES
                                {{ number_format($allowances, 2) }}
                            </td>
                        </tr>

                        <tr>
                            <td class="pay-label">
                                Taxable Pay
                            </td>

                            <td class="pay-amount">
                                KES
                                {{ number_format($taxablePay, 2) }}
                            </td>
                        </tr>

                        <tr class="sub-total">
                            <td>
                                Gross Earnings
                            </td>

                            <td class="pay-amount">
                                KES
                                {{ number_format($grossPay, 2) }}
                            </td>
                        </tr>
                    </table>
                </td>

                <td>
                    <table class="pay-table">
                        <tr>
                            <td
                                colspan="2"
                                class="panel-heading deductions-heading"
                            >
                                Deductions
                            </td>
                        </tr>

                        <tr>
                            <td class="pay-label">
                                NSSF
                            </td>

                            <td class="pay-amount">
                                KES
                                {{ number_format($nssf, 2) }}
                            </td>
                        </tr>

                        <tr class="item-alt">
                            <td class="pay-label">
                                SHIF
                            </td>

                            <td class="pay-amount">
                                KES
                                {{ number_format($sha, 2) }}
                            </td>
                        </tr>

                        <tr>
                            <td class="pay-label">
                                Affordable Housing Levy
                            </td>

                            <td class="pay-amount">
                                KES
                                {{ number_format($housingLevy, 2) }}
                            </td>
                        </tr>

                        <tr class="item-alt">
                            <td class="pay-label">
                                PAYE
                            </td>

                            <td class="pay-amount">
                                KES
                                {{ number_format($paye, 2) }}
                            </td>
                        </tr>

                        <tr>
                            <td class="pay-label">
                                Salary Advance Recovery
                            </td>

                            <td class="pay-amount">
                                KES
                                {{ number_format($salaryAdvanceRecovery, 2) }}
                            </td>
                        </tr>

                        <tr class="item-alt">
                            <td class="pay-label">
                                Other Deductions
                            </td>

                            <td class="pay-amount">
                                KES
                                {{ number_format($otherDeductions, 2) }}
                            </td>
                        </tr>

                        <tr class="sub-total">
                            <td>
                                Total Deductions
                            </td>

                            <td class="pay-amount">
                                KES
                                {{ number_format($totalDeductions, 2) }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="summary-cards">
            <tr>
                <td>
                    <div class="summary-label">
                        Gross Earnings
                    </div>

                    <div class="summary-value">
                        KES
                        {{ number_format($grossPay, 2) }}
                    </div>
                </td>

                <td>
                    <div class="summary-label">
                        Total Deductions
                    </div>

                    <div class="summary-value">
                        KES
                        {{ number_format($totalDeductions, 2) }}
                    </div>
                </td>

                <td>
                    <div class="summary-label">
                        Net Pay
                    </div>

                    <div class="summary-value summary-value-net">
                        KES
                        {{ number_format($netRecorded, 2) }}
                    </div>
                </td>
            </tr>
        </table>

        <div class="advance-note">
            <strong>
                Remaining approved salary advances:
            </strong>

            KES
            {{ number_format($remainingApprovedAdvances, 2) }}
        </div>
    </div>

    <table class="approval">
        <tr>
            <td>
                <div class="approval-box">
                    @if ($signatureBase64)
                        <img
                            src="{{ $signatureBase64 }}"
                            class="approval-image"
                            alt="Authorised Signature"
                        >
                    @else
                        <div style="height:52px;"></div>
                    @endif

                    <div class="approval-line"></div>

                    <strong>
                        {{ $authorizedName }}
                    </strong><br>

                    {{ $authorizedTitle }}<br>

                    <span class="approval-caption">
                        Authorised Signature
                    </span>
                </div>
            </td>

            <td>
                <div class="approval-box">
                    @if ($stampBase64)
                        <img
                            src="{{ $stampBase64 }}"
                            class="stamp-image"
                            alt="Official Stamp"
                        >
                    @else
                        <div style="height:64px;"></div>
                    @endif

                    <div class="approval-line"></div>

                    <span class="approval-caption">
                        Official Company Stamp
                    </span>
                </div>
            </td>
        </tr>
    </table>

    <div class="note">
        This confidential payroll document was generated by
        {{ $farmName }} for the employee identified by Staff ID
        <strong>{{ $employeeNumber }}</strong>.
        Contact Finance or Human Resource for clarification.
        Statutory deductions and salary-advance recoveries are
        based on the approved payroll run.
    </div>
</main>
</body>
</html>
