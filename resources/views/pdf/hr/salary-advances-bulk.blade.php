@php
if (! function_exists('pdfImageBase64')) {
    function pdfImageBase64(?string $path): ?string
    {
        if (! $path) {
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
    $accentColor = setting('theme.accent', '#f59e0b');
    $dangerColor = setting('theme.danger', '#dc2626');
    $successColor = setting('theme.success', '#16a34a');

   $logoBase64 = pdfImageBase64(setting('branding.logo_light'));

    $totalRequested = $advances->sum(fn($a) => (float) $a->amount_requested);
    $totalApproved = $advances->sum(fn($a) => (float) $a->amount_approved);
    $totalBalance = $advances->sum(fn($a) => (float) $a->balance);
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Salary Advances Bulk Report</title>
    <style>
        @page {
            margin: 115px 24px 90px 24px;
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

        .header-right {
            text-align: right;
            font-size: 10px;
            line-height: 1.5;
            color: #374151;
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

        .pill {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 999px;
            font-size: 8px;
            font-weight: bold;
            color: #fff;
        }

        .pending {
            background: {{ $accentColor }};
        }

        .approved {
            background: {{ $successColor }};
        }

        .rejected {
            background: {{ $dangerColor }};
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
                <td width="110">
                    @if ($logoBase64)
                        <img src="{{ $logoBase64 }}" class="logo" alt="Logo">
                    @endif
                </td>
                <td>
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
                <td class="footer-left">Generated on {{ $eatNow->format('d M Y, H:i') }} EAT</td>
                <td class="footer-center">Salary Advances Bulk Report</td>
                <td class="footer-right">Prepared by {{ $generatedBy->name ?? 'System' }}</td>
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
            <h1>Salary Advances Bulk Report</h1>
            <p>Detailed register of selected salary advances</p>
        </div>

        <div class="summary-wrap">
            <table class="summary-table">
                <tr>
                    <td><span class="summary-label">Total Records:</span> {{ $advances->count() }}</td>
                    <td><span class="summary-label">Total Requested:</span> KSh {{ number_format($totalRequested, 2) }}
                    </td>
                    <td><span class="summary-label">Total Approved:</span> KSh {{ number_format($totalApproved, 2) }}
                    </td>
                    <td><span class="summary-label">Total Balance:</span> KSh {{ number_format($totalBalance, 2) }}
                    </td>
                </tr>
            </table>
        </div>

        <table class="report">
            <thead>
                <tr>
                    <th width="3%">#</th>
                    <th width="11%">Employee</th>
                    <th width="8%">Employee No.</th>
                    <th width="8%">Department</th>
                    <th width="7%">Request Date</th>
                    <th width="8%">Requested</th>
                    <th width="8%">Approved</th>
                    <th width="8%">Repayment</th>
                    <th width="6%">Months</th>
                    <th width="8%">Monthly Deduction</th>
                    <th width="8%">Balance</th>
                    <th width="6%">Status</th>
                    <th width="11%">Reason</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($advances as $index => $advance)
                    @php
                        $status =
                            is_object($advance->approval_status) && isset($advance->approval_status->value)
                                ? $advance->approval_status->value
                                : (string) $advance->approval_status;
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $advance->employee->full_name ?? '-' }}</td>
                        <td>{{ $advance->employee->employee_number ?? '-' }}</td>
                        <td>{{ $advance->employee->department->name ?? '-' }}</td>
                        <td>{{ optional($advance->request_date)->format('d M Y') ?? '-' }}</td>
                        <td class="right">{{ number_format((float) $advance->amount_requested, 2) }}</td>
                        <td class="right">{{ number_format((float) $advance->amount_approved, 2) }}</td>
                        <td>{{ $advance->repayment_mode === 'one_off' ? 'One Off' : 'Installments' }}</td>
                        <td class="right">{{ $advance->repayment_months ?? 1 }}</td>
                        <td class="right">{{ number_format((float) $advance->monthly_deduction, 2) }}</td>
                        <td class="right">{{ number_format((float) $advance->balance, 2) }}</td>
                        <td>
                            <span class="pill {{ $status }}">
                                {{ ucfirst($status) }}
                            </span>
                        </td>
                        <td>{{ \Illuminate\Support\Str::limit($advance->reason, 80) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </main>


<style>
    /*
    |--------------------------------------------------------------------------
    | LELEKWE_ACCOUNTING_PDF_VISIBILITY_FINAL_FIX
    |--------------------------------------------------------------------------
    | Generated accounting reports only.
    | Forces report text to black and Courier.
    | Keeps signature handwritten text in the farm primary color.
    */

    html,
    body,
    main,
    section,
    article {
        font-family: Courier, "Courier New", monospace !important;
        color: #000000 !important;
        background: #ffffff !important;
    }

    body * {
        font-family: Courier, "Courier New", monospace !important;
        color: #000000 !important;
    }

    /*
     * Fix the white/invisible report intro/header text.
     */
    .report-title,
    .report-title *,
    .pdf-report-title,
    .pdf-report-title *,
    .accounting-report-title,
    .accounting-report-title *,
    .accounting-pdf-title,
    .accounting-pdf-title *,
    .report-hero,
    .report-hero *,
    .pdf-hero,
    .pdf-hero *,
    .accounting-report-hero,
    .accounting-report-hero *,
    .accounting-pdf-hero,
    .accounting-pdf-hero *,
    .report-summary,
    .report-summary *,
    .pdf-summary,
    .pdf-summary *,
    .report-meta,
    .report-meta *,
    .pdf-meta,
    .pdf-meta *,
    .report-period,
    .report-period *,
    .pdf-period,
    .pdf-period *,
    .summary-card,
    .summary-card *,
    .period-card,
    .period-card *,
    .meta-card,
    .meta-card *,
    .control-card,
    .control-card *,
    .director-note,
    .director-note *,
    .executive-note,
    .executive-note *,
    .report-description,
    .pdf-description,
    .report-subtitle,
    .pdf-subtitle,
    .report-caption,
    .pdf-caption,
    .report-kicker,
    .pdf-kicker,
    .meta-label,
    .summary-label,
    .period-label,
    .meta-value,
    .summary-value,
    .period-value {
        color: #000000 !important;
        font-family: Courier, "Courier New", monospace !important;
    }

    .report-title,
    .pdf-report-title,
    .accounting-report-title,
    .accounting-pdf-title,
    .report-hero,
    .pdf-hero,
    .accounting-report-hero,
    .accounting-pdf-hero,
    .report-summary,
    .pdf-summary,
    .report-meta,
    .pdf-meta,
    .report-period,
    .pdf-period,
    .summary-card,
    .period-card,
    .meta-card,
    .control-card,
    .director-note,
    .executive-note {
        background: #ffffff !important;
        border-color: #000000 !important;
    }

    /*
     * Catch Tailwind/utility classes that may be making PDF text white.
     */
    .text-white,
    .text-gray-50,
    .text-slate-50,
    .text-zinc-50,
    .text-neutral-50,
    .text-stone-50,
    .text-gray-100,
    .text-slate-100,
    .text-emerald-50,
    .text-green-50,
    .text-lime-50 {
        color: #000000 !important;
    }

    /*
     * Headings and labels.
     */
    h1,
    h2,
    h3,
    h4,
    h5,
    h6,
    p,
    span,
    small,
    strong,
    b,
    div {
        color: #000000 !important;
    }

    .report-title h1,
    .pdf-report-title h1,
    .accounting-report-title h1,
    .accounting-pdf-title h1,
    .report-hero h1,
    .pdf-hero h1 {
        color: #000000 !important;
        font-weight: 800 !important;
    }

    .meta-label,
    .summary-label,
    .period-label,
    .report-period-label,
    .pdf-period-label,
    .report-kpi-label,
    .pdf-kpi-label {
        color: #000000 !important;
        font-weight: 800 !important;
        text-transform: uppercase !important;
        letter-spacing: .04em !important;
    }

    .meta-value,
    .summary-value,
    .period-value,
    .report-period-value,
    .pdf-period-value,
    .report-kpi-value,
    .pdf-kpi-value {
        color: #000000 !important;
        font-weight: 800 !important;
    }

    /*
     * Table content like 000 — Assets, Total, etc.
     */
    table,
    thead,
    tbody,
    tfoot,
    tr,
    th,
    td,
    table *,
    .report-table,
    .report-table *,
    .accounting-table,
    .accounting-table *,
    table.report,
    table.report * {
        color: #000000 !important;
        font-family: Courier, "Courier New", monospace !important;
    }

    table thead th,
    table.report thead th,
    .report-table thead th,
    .accounting-table thead th {
        color: #000000 !important;
        background: #e5e7eb !important;
        border-color: #000000 !important;
        font-weight: 800 !important;
    }

    .group-row,
    .account-group-row,
    .section-row,
    .total-row,
    .subtotal-row,
    .grand-total-row,
    tr.group-row td,
    tr.account-group-row td,
    tr.section-row td,
    tr.total-row td,
    tr.subtotal-row td,
    tr.grand-total-row td {
        color: #000000 !important;
        background: #f3f4f6 !important;
        border-color: #000000 !important;
        font-weight: 800 !important;
    }

    /*
     * Signature should NOT be black. Use the dynamic farm primary color.
     */
    .signature-handwritten,
    .signature-handwritten *,
    .handwritten,
    .handwritten *,
    .signature-script,
    .signature-script * {
        font-family: "ChopinScript", "ChopinScriptDashboard", cursive !important;
        color: {{ $primaryColor ?? setting('theme.primary', '#14532d') }} !important;
    }
</style>

</body>

</html>
