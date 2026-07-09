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
    $dangerColor = setting('theme.danger', '#dc2626');
    $accentColor = setting('theme.accent', '#f59e0b');

    $logoBase64 = pdfImageBase64(setting('branding.logo_light'));

    $status =
        is_object($advance->approval_status) && isset($advance->approval_status->value)
            ? $advance->approval_status->value
            : (string) $advance->approval_status;
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Salary Advance</title>
    <style>
        @page {
            margin: 110px 30px 85px 30px;
        }

        body {
            font-family: Courier, sans-serif;
            font-size: 11px;
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
            width: 380px;
        }

        header {
            position: fixed;
            top: -90px;
            left: 0;
            right: 0;
            height: 80px;
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
        .detail-table {
            width: 100%;
            border-collapse: collapse;
        }

        .logo {
            width: 150px;
        }

        .title {
            font-size: 10px;
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
            padding: 5px 6px;
            vertical-align: top;
        }

        .label {
            font-weight: bold;
            width: 190px;
            color: #374151;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 9px;
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
    <div class="watermark">
    @if ($logoBase64)
        <img src="{{ $logoBase64 }}" alt="Watermark">
    @endif
</div>

    <header>
        <table class="header-table">
            <tr>
                <td width="160">
                    @if ($logoBase64)
                        <img src="{{ $logoBase64 }}" class="logo" alt="Logo">
                    @endif
                </td>
                <td>
                    <div class="title">Salary Advance Request / Approval Summary</div>
                    <div class="tagline">{{ $farmName }} — {{ $farmTagline }}</div>
                </td>
                <td width="220" style="text-align:right; font-size:10px;">
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
                <td style="text-align:center;">Salary Advance Record</td>
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
                    <td class="label">Request Date</td>
                    <td>{{ optional($advance->request_date)->format('d M Y') }}</td>
                    <td class="label">Approval Status</td>
                    <td>
                        <span class="status-badge {{ $status }}">
                            {{ ucfirst($status) }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td class="label">Amount Requested</td>
                    <td>KSh {{ number_format((float) $advance->amount_requested, 2) }}</td>
                    <td class="label">Amount Approved</td>
                    <td>KSh {{ number_format((float) $advance->amount_approved, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Repayment Mode</td>
                    <td>{{ $advance->repayment_mode === 'one_off' ? 'One Off' : 'Installments' }}</td>
                    <td class="label">Repayment Months</td>
                    <td>{{ $advance->repayment_months ?? 1 }}</td>
                </tr>
                <tr>
                    <td class="label">Monthly Deduction</td>
                    <td>KSh {{ number_format((float) $advance->monthly_deduction, 2) }}</td>
                    <td class="label">Outstanding Balance</td>
                    <td>KSh {{ number_format((float) $advance->balance, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Approved By</td>
                    <td>{{ $advance->approver->name ?? '-' }}</td>
                    <td class="label">Approved At</td>
                    <td>{{ optional($advance->approved_at)->format('d M Y, H:i') ?? '-' }}</td>
                </tr>
            </table>
        </div>

        <div class="notes-box">
            <div class="notes-title">Reason</div>
            {{ $advance->reason ?: '-' }}
        </div>

        <div class="notes-box">
            <div class="notes-title">Internal Notes</div>
            {{ $advance->notes ?: '-' }}
        </div>
    </main>
</body>

</html>
