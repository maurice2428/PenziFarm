@php
    use Carbon\Carbon;

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

    $logoBase64 = pdfImageBase64(setting('branding.logo_light'));

    $generatedByName = $generatedBy->name ?? 'System';
    $generatedByRole = $generatedByRole ?? 'User';

    $activeCount = $employees->where('status', 'active')->count();
    $inactiveCount = $employees->where('status', 'inactive')->count();
    $suspendedCount = $employees->where('status', 'suspended')->count();
    $exitedCount = $employees->where('status', 'exited')->count();
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Employees Bulk Report</title>
    <style>
        @font-face {
            font-family: "ChopinScript";
            src: url("{{ public_path('fonts/ChopinScript.ttf') }}") format("truetype");
        }

        @page {
            margin: 120px 24px 95px 24px;
        }

        body {
            font-family: Courier, sans-serif;
            font-size: 9.5px;
            color: #222;
        }

        .watermark {
            position: fixed;
            top: 28%;
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
            font-size: 10px;
            color: #4b5563;
        }

        .header-table,
        .footer-table,
        .report,
        .summary-table,
        .status-summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-left {
            text-align: left;
        }

        .header-center {
            text-align: center;
        }

        .header-right {
            text-align: right;
            font-size: 10px;
            line-height: 1.5;
            color: #374151;
        }

        .logo {
            width: 180px;
        }

        .company-title {
            font-size: 22px;
            font-weight: 700;
            color: {{ $primaryColor }};
            text-align: center;
            margin-bottom: 2px;
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

        .summary-box {
            margin-top: 8px;
            margin-bottom: 10px;
            border: 1px solid #dbe4d3;
            background: #f8fbf7;
            border-radius: 8px;
            padding: 8px 10px;
        }

        .summary-table td {
            font-size: 10px;
            padding: 4px 6px;
            vertical-align: top;
        }

        .summary-label {
            color: #374151;
            font-weight: bold;
            width: 120px;
        }

        .status-summary-wrap {
            margin-bottom: 12px;
        }

        .status-summary-table td {
            width: 25%;
            padding: 0 6px 0 0;
            vertical-align: top;
        }

        .status-card {
            border: 1px solid #dbe4d3;
            border-radius: 10px;
            padding: 10px 12px;
            background: #ffffff;
            min-height: 56px;
        }

        .status-card-title {
            font-size: 9px;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 4px;
            letter-spacing: 0.4px;
        }

        .status-card-value {
            font-size: 18px;
            font-weight: bold;
            color: #111827;
        }

        .status-card.active {
            border-left: 5px solid {{ $successColor }};
            background: #f6fff8;
        }

        .status-card.inactive {
            border-left: 5px solid #6b7280;
            background: #fafafa;
        }

        .status-card.suspended {
            border-left: 5px solid {{ $accentColor }};
            background: #fffaf1;
        }

        .status-card.exited {
            border-left: 5px solid {{ $dangerColor }};
            background: #fff7f7;
        }

        table.report {
            margin-top: 8px;
        }

        table.report thead th {
            background: {{ $primaryColor }};
            border: 1px solid {{ $primaryColor }};
            color: #fff;
            padding: 8px 5px;
            font-size: 8.5px;
            text-align: left;
            line-height: 1.25;
        }

        table.report tbody td {
            border: 1px solid #e5e7eb;
            padding: 6px 5px;
            vertical-align: top;
            line-height: 1.3;
        }

        table.report tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid #d1d5db;
        }

        .status-badge,
        .flag-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 999px;
            font-size: 8px;
            font-weight: bold;
            color: #fff;
            margin: 1px 2px 1px 0;
        }

        .status-active {
            background: {{ $successColor }};
        }

        .status-inactive {
            background: #6b7280;
        }

        .status-exited {
            background: {{ $dangerColor }};
        }

        .status-suspended {
            background: {{ $accentColor }};
        }

        .flag-on {
            background: {{ $successColor }};
        }

        .flag-off {
            background: #9ca3af;
        }

        .payment-box,
        .salary-box,
        .flags-box {
            font-size: 8.5px;
            line-height: 1.4;
        }

        .mini-title {
            font-weight: bold;
            color: {{ $secondaryColor }};
            margin-bottom: 2px;
            text-transform: uppercase;
            font-size: 8px;
        }

        .muted {
            color: #6b7280;
        }

        .money {
            font-weight: bold;
            color: #111827;
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
                    Printed on {{ $eatNow->format('d M Y, H:i') }} EAT
                </td>
                <td class="footer-center">
                    Employees Bulk Report
                </td>
                <td class="footer-right">
                    Created by {{ $generatedBy->name }} ({{ $generatedByRole }})
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
            <h1>Employees Bulk Report</h1>
            <p>Detailed HR summary for selected employees</p>
        </div>

        <div class="summary-box">
            <table class="summary-table">
                <tr>
                    <td><span class="summary-label">Total Selected:</span> {{ $employees->count() }}</td>
                    <td><span class="summary-label">Generated On:</span> {{ $eatNow->format('d M Y, H:i') }} EAT</td>
                    <td><span class="summary-label">Generated By:</span> {{ $generatedBy->name }}</td>
                </tr>
            </table>
        </div>

        <div class="status-summary-wrap">
            <table class="status-summary-table">
                <tr>
                    <td>
                        <div class="status-card active">
                            <div class="status-card-title">Active Employees</div>
                            <div class="status-card-value">{{ $activeCount }}</div>
                        </div>
                    </td>
                    <td>
                        <div class="status-card inactive">
                            <div class="status-card-title">Inactive Employees</div>
                            <div class="status-card-value">{{ $inactiveCount }}</div>
                        </div>
                    </td>
                    <td>
                        <div class="status-card suspended">
                            <div class="status-card-title">Suspended Employees</div>
                            <div class="status-card-value">{{ $suspendedCount }}</div>
                        </div>
                    </td>
                    <td>
                        <div class="status-card exited">
                            <div class="status-card-title">Exited Employees</div>
                            <div class="status-card-value">{{ $exitedCount }}</div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <table class="report">
            <thead>
                <tr>
                    <th width="3%">#</th>
                    <th width="5%">Avatar</th>
                    <th width="7%">Employee No.</th>
                    <th width="10%">Name</th>
                    <th width="7%">ID / Passport</th>
                    <th width="6%">DOB</th>
                    <th width="4%">Age</th>
                    <th width="7%">Phone</th>
                    <th width="7%">Department</th>
                    <th width="7%">Job Title</th>
                    <th width="9%">Payment Details</th>
                    <th width="11%">Gross Salary</th>
                    <th width="8%">Statutory Flags</th>
                    <th width="6%">Hire Date</th>
                    <th width="5%">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($employees as $index => $employee)
                    @php
                        $statusClass = match ($employee->status) {
                            'active' => 'status-active',
                            'inactive' => 'status-inactive',
                            'exited' => 'status-exited',
                            'suspended' => 'status-suspended',
                            default => 'status-inactive',
                        };

                        $basicSalary = (float) ($employee->basic_salary ?? 0);
                        $houseAllowance = (float) ($employee->house_allowance ?? 0);
                        $transportAllowance = (float) ($employee->transport_allowance ?? 0);
                        $otherAllowance = (float) ($employee->other_allowance ?? 0);

                        $grossSalary = $basicSalary + $houseAllowance + $transportAllowance + $otherAllowance;

                        $age = $employee->date_of_birth ? Carbon::parse($employee->date_of_birth)->age : null;

                        $paymentDetails = 'Not Set';

                        if (($employee->payment_method ?? null) === 'bank') {
                            $paymentDetails =
                                'Bank: ' .
                                ($employee->bank_name ?: '-') .
                                "\nA/C: " .
                                ($employee->account_number ?: '-') .
                                "\nBranch: " .
                                ($employee->bank_branch ?: '-');
                        } elseif (($employee->payment_method ?? null) === 'mpesa') {
                            $paymentDetails = 'M-Pesa' . "\nNo: " . ($employee->mpesa_number ?: '-');
                        } elseif (($employee->payment_method ?? null) === 'airtel_money') {
                            $paymentDetails = 'Airtel Money' . "\nNo: " . ($employee->airtel_money_number ?: '-');
                        } else {
                            if (!empty($employee->bank_name) || !empty($employee->account_number)) {
                                $paymentDetails =
                                    'Bank: ' .
                                    ($employee->bank_name ?: '-') .
                                    "\nA/C: " .
                                    ($employee->account_number ?: '-') .
                                    "\nBranch: " .
                                    ($employee->bank_branch ?: '-');
                            }
                        }
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            @php
                                $employeeAvatarBase64 = pdfImageBase64($employee->avatar_path ?? null);
                            @endphp

                            @if ($employeeAvatarBase64)
                                <img src="{{ $employeeAvatarBase64 }}" class="avatar" alt="Avatar">
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $employee->employee_number ?: '-' }}</td>
                        <td>
                            <strong>{{ $employee->full_name ?: '-' }}</strong><br>
                            <span class="muted">{{ $employee->email ?: 'No email' }}</span>
                        </td>
                        <td>{{ $employee->id_passport_number ?: '-' }}</td>
                        <td>{{ optional($employee->date_of_birth)->format('d M Y') ?: '-' }}</td>
                        <td>{{ $age !== null ? $age . ' yrs' : '-' }}</td>
                        <td>{{ $employee->phone ?: '-' }}</td>
                        <td>{{ $employee->department->name ?? '-' }}</td>
                        <td>{{ $employee->jobTitle->name ?? '-' }}</td>
                        <td>
                            <div class="payment-box">
                                {!! nl2br(e($paymentDetails)) !!}
                            </div>
                        </td>
                        <td>
                            <div class="salary-box">
                                <div class="mini-title">Breakdown</div>
                                <div>Basic: <span class="money">KES {{ number_format($basicSalary, 2) }}</span></div>
                                <div>House: <span class="money">KES {{ number_format($houseAllowance, 2) }}</span>
                                </div>
                                <div>Transport: <span class="money">KES
                                        {{ number_format($transportAllowance, 2) }}</span></div>
                                <div>Other: <span class="money">KES {{ number_format($otherAllowance, 2) }}</span>
                                </div>
                                <div style="margin-top: 3px;"><strong>Gross: <span class="money">KES
                                            {{ number_format($grossSalary, 2) }}</span></strong></div>
                            </div>
                        </td>
                        <td>
                            <div class="flags-box">
                                <div class="mini-title">Enabled</div>
                                <span
                                    class="flag-badge {{ $employee->tax_enabled ? 'flag-on' : 'flag-off' }}">PAYE</span>
                                <span
                                    class="flag-badge {{ $employee->nssf_enabled ? 'flag-on' : 'flag-off' }}">NSSF</span>
                                <span
                                    class="flag-badge {{ $employee->sha_enabled ? 'flag-on' : 'flag-off' }}">SHA</span>
                                <span
                                    class="flag-badge {{ $employee->housing_levy_enabled ? 'flag-on' : 'flag-off' }}">Housing</span>
                            </div>
                        </td>
                        <td>{{ optional($employee->hire_date)->format('d M Y') ?: '-' }}</td>
                        <td>
                            <span class="status-badge {{ $statusClass }}">
                                {{ ucfirst($employee->status ?? 'n/a') }}
                            </span>
                        </td>
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
