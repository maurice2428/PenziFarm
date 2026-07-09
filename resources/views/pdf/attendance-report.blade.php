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

    $recordsCollection = collect($records ?? []);

    $present = $presentCount ?? $recordsCollection->where('status', 'present')->count();
    $late = $lateCount ?? $recordsCollection->where('status', 'late')->count();
    $absent = $absentCount ?? $recordsCollection->where('status', 'absent')->count();
    $leave = $leaveCount ?? $recordsCollection->where('status', 'on_leave')->count();

    $hours = $totalHoursWorked ?? $recordsCollection->sum('hours_worked');
    $overtime = $totalOvertimeHours ?? $recordsCollection->sum('overtime_hours');

    $start = $startDate ?? now('Africa/Nairobi')->toDateString();
    $end = $endDate ?? $start;

    $periodLabel =
        \Carbon\Carbon::parse($start)->format('d M Y') . ' - ' . \Carbon\Carbon::parse($end)->format('d M Y');
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Attendance Report</title>
    <style>
        @page {
            margin: 120px 30px 90px 30px;
        }

        body {
            font-family: Courier, sans-serif;
            font-size: 10px;
            color: #1f2937;
        }

        header {
            position: fixed;
            top: -100px;
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
            font-size: 9px;
            color: #4b5563;
        }

        .watermark {
            position: fixed;
            top: 28%;
            left: 13%;
            width: 74%;
            opacity: 0.05;
            z-index: -1;
            text-align: center;
        }

        .watermark img {
            width: 390px;
        }

        .header-table,
        .footer-table,
        .summary-table,
        .report-table,
        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-left {
            width: 110px;
            text-align: left;
            vertical-align: middle;
        }

        .header-center {
            text-align: center;
            vertical-align: middle;
        }

        .header-right {
            width: 220px;
            text-align: right;
            vertical-align: middle;
            font-size: 10px;
            color: #374151;
            line-height: 1.5;
        }

        .logo {
            width: 165px;
        }

        .company-title {
            font-size: 22px;
            font-weight: bold;
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
            margin-top: 4px;
            margin-bottom: 12px;
        }

        .report-title h1 {
            font-size: 18px;
            margin: 0 0 4px 0;
            color: #111827;
        }

        .report-title p {
            margin: 0;
            font-size: 10px;
            color: {{ $primaryColor }};
        }

        .summary-table {
            margin-top: 10px;
            margin-bottom: 16px;
        }

        .summary-table td {
            width: 16.66%;
            padding: 0 4px;
            vertical-align: top;
        }

        .summary-box {
            border: 1px solid #dbe4d3;
            border-radius: 8px;
            background: #fbfdf9;
            padding: 9px 6px;
            text-align: center;
            min-height: 44px;
        }

        .summary-title {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .summary-value {
            font-size: 14px;
            font-weight: bold;
            color: #111827;
        }

        .summary-present .summary-value {
            color: {{ $successColor }};
        }

        .summary-late .summary-value {
            color: {{ $accentColor }};
        }

        .summary-absent .summary-value {
            color: {{ $dangerColor }};
        }

        .summary-leave .summary-value {
            color: {{ $secondaryColor }};
        }

        .summary-hours .summary-value {
            color: {{ $primaryColor }};
        }

        .summary-overtime .summary-value {
            color: #7c3aed;
        }

        .report-table thead th {
            background: {{ $primaryColor }};
            color: #fff;
            border: 1px solid {{ $primaryColor }};
            padding: 8px 6px;
            font-size: 9px;
            text-align: left;
        }

        .report-table tbody td {
            border: 1px solid #e5e7eb;
            padding: 7px 6px;
            vertical-align: top;
            font-size: 9px;
        }

        .report-table tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .status-badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 999px;
            font-size: 8px;
            font-weight: bold;
            color: #fff;
            text-transform: uppercase;
        }

        .status-present {
            background: {{ $successColor }};
        }

        .status-late {
            background: {{ $accentColor }};
        }

        .status-absent {
            background: {{ $dangerColor }};
        }

        .status-on_leave {
            background: {{ $secondaryColor }};
        }

        .status-half_day {
            background: #7c3aed;
        }

        .status-holiday {
            background: #0891b2;
        }

        .status-off_day {
            background: #6b7280;
        }

        .signatures-wrap {
            margin-top: 28px;
        }

        .signature-table td {
            vertical-align: top;
            padding-right: 8px;
        }

        .signature-card {
            border: 1px solid #dbe4d3;
            border-radius: 10px;
            padding: 12px 14px;
            background: #fbfdf9;
            min-height: 92px;
        }

        .signature-title {
            font-size: 10px;
            font-weight: bold;
            color: {{ $secondaryColor }};
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .signature-name {
            font-size: 12px;
            font-weight: bold;
            color: #111827;
        }

        .signature-meta,
        .signature-line {
            font-size: 9px;
            color: #6b7280;
        }

        .signature-line {
            border-top: 1px solid #4b5563;
            margin-top: 18px;
            padding-top: 6px;
        }

        .stamp-circle {
            width: 95px;
            height: 95px;
            margin: 0 auto 6px auto;
            border: 1px dashed {{ $primaryColor }};
            border-radius: 50%;
            display: table;
        }

        .stamp-text {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            color: {{ $primaryColor }};
            line-height: 1.4;
        }

        .text-center {
            text-align: center;
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
                <td class="footer-left">Printed on {{ $eatNow->format('d M Y, H:i') }} EAT</td>
                <td class="footer-center">Attendance Report</td>
                <td class="footer-right">Created by {{ $generatedBy->name ?? 'System' }}
                    ({{ $generatedByRole ?? 'User' }})</td>
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
            <h1>Attendance Report</h1>
            <p>Reporting Period: {{ $periodLabel }}</p>
        </div>

        <table class="summary-table">
            <tr>
                <td>
                    <div class="summary-box summary-present">
                        <div class="summary-title">Present</div>
                        <div class="summary-value">{{ $present }}</div>
                    </div>
                </td>
                <td>
                    <div class="summary-box summary-late">
                        <div class="summary-title">Late</div>
                        <div class="summary-value">{{ $late }}</div>
                    </div>
                </td>
                <td>
                    <div class="summary-box summary-absent">
                        <div class="summary-title">Absent</div>
                        <div class="summary-value">{{ $absent }}</div>
                    </div>
                </td>
                <td>
                    <div class="summary-box summary-leave">
                        <div class="summary-title">Leave</div>
                        <div class="summary-value">{{ $leave }}</div>
                    </div>
                </td>
                <td>
                    <div class="summary-box summary-hours">
                        <div class="summary-title">Hours Worked</div>
                        <div class="summary-value">{{ number_format($hours, 2) }}</div>
                    </div>
                </td>
                <td>
                    <div class="summary-box summary-overtime">
                        <div class="summary-title">Overtime</div>
                        <div class="summary-value">{{ number_format($overtime, 2) }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <table class="report-table">
            <thead>
                <tr>
                    <th width="10%">Date</th>
                    <th width="19%">Employee</th>
                    <th width="12%">Status</th>
                    <th width="10%">Check In</th>
                    <th width="10%">Check Out</th>
                    <th width="10%">Hours</th>
                    <th width="10%">OT</th>
                    <th width="8%">Late</th>
                    <th width="11%">Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $r)
                    @php
                        $status = $r['status'] ?? '-';
                        $statusClass = 'status-' . str_replace('-', '_', str_replace(' ', '_', $status));
                    @endphp
                    <tr>
                        <td>{{ !empty($r['attendance_date']) ? \Carbon\Carbon::parse($r['attendance_date'])->format('d M Y') : '-' }}
                        </td>
                        <td>{{ $r['employee_name'] ?? '-' }}</td>
                        <td>
                            <span class="status-badge {{ $statusClass }}">
                                {{ strtoupper(str_replace('_', ' ', $status)) }}
                            </span>
                        </td>
                        <td>{{ $r['check_in'] ?? '-' }}</td>
                        <td>{{ $r['check_out'] ?? '-' }}</td>
                        <td>{{ number_format((float) ($r['hours_worked'] ?? 0), 2) }}</td>
                        <td>{{ number_format((float) ($r['overtime_hours'] ?? 0), 2) }}</td>
                        <td>{{ $r['late_minutes'] ?? 0 }}</td>
                        <td>{{ $r['remarks'] ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center">No attendance records found for the selected period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="signatures-wrap">
            <table class="signature-table">
                <tr>
                    <td width="38%">
                        <div class="signature-card">
                            <div class="signature-title">Prepared By</div>
                            <div class="signature-name">{{ $generatedBy->name ?? 'System' }}</div>
                            <div class="signature-meta">{{ $generatedByRole ?? 'User' }}</div>
                            <div class="signature-line">
                                Generated on {{ $eatNow->format('d M Y, H:i') }} EAT
                            </div>
                        </div>
                    </td>

                    <td width="32%">
                        <div class="signature-card">
                            <div class="signature-title">Report Reference</div>
                            <div class="signature-name">{{ $farmName }}</div>
                            <div class="signature-meta">Attendance performance report</div>
                            <div class="signature-line">
                                Period: {{ $periodLabel }}
                            </div>
                        </div>
                    </td>

                    <td width="30%" class="text-center">
                        <div class="stamp-circle">
                            <div class="stamp-text">OFFICIAL<br>REPORT</div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </main>
</body>

</html>
