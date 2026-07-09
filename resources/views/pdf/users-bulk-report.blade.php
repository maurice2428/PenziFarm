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
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Users Report</title>
    <style>
        @font-face {
            font-family: "ChopinScript";
            src: url("{{ public_path('fonts/ChopinScript.ttf') }}") format("truetype");
            font-weight: normal;
            font-style: normal;
        }

        .signature-handwritten {
            font-family: "ChopinScript" !important;
            font-size: 25px;
            color: {{ $successColor }};
            letter-spacing: 1px;
        }

        @page {
            margin: 120px 35px 95px 35px;
        }



        body {
            font-family: Courier, sans-serif;
            font-size: 11px;
            color: #222;
            position: relative;
        }

        .watermark {
            position: fixed;
            top: 30%;
            left: 12%;
            width: 75%;
            opacity: 0.06;
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

        .header-table,
        .footer-table,
        .report {
            width: 100%;
            border-collapse: collapse;
        }

        .section-block {
            margin-top: 30px;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }

        .signature-table td {
            vertical-align: top;
        }

        .header-left {
            text-align: left;
            vertical-align: middle;
        }

        .header-center {
            text-align: center;
            vertical-align: middle;
        }

        .header-right {
            text-align: right;
            vertical-align: middle;
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
            margin-bottom: 2px;
            text-align: center;
        }

        .tagline {
            font-size: 11px;
            color: #4b5563;
            font-style: italic;
            text-align: center;
        }

        .report-title {
            margin-top: 5px;
            margin-bottom: 14px;
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

        table.report {
            margin-top: 10px;
        }

        table.report thead th {
            background: {{ $primaryColor }};
            border: 1px solid {{ $primaryColor }};
            color: #fff;
            padding: 10px 8px;
            font-size: 10px;
            text-align: left;

        }

        table.report tbody td {
            border: 1px solid #e5e7eb;
            padding: 8px;
            vertical-align: top;
        }

        table.report tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .role-badge {
            display: inline-block;
            padding: 3px 7px;
            border-radius: 999px;
            font-size: 9px;
            font-weight: bold;
            color: #fff;
        }

        .role-admin {
            background: {{ $dangerColor }};
        }

        .role-manager {
            background: {{ $successColor }};
        }

        .role-finance {
            background: {{ $accentColor }};
        }

        .role-vet {
            background: {{ $secondaryColor }};
        }

        .role-other {
            background: #6b7280;
        }

        .avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid #d1d5db;
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

        .section-block {
            margin-top: 30px;
        }

        .signature-card,
        .info-card {
            border: 1px solid #dbe4d3;
            border-radius: 10px;
            padding: 12px 14px;
            background: #fbfdf9;
            min-height: 120px;
        }

        .signature-card-authorized {
            border: 1px solid #cfe3bf;
            background: #f8fff2;
        }



        .signature-card-title {
            font-size: 11px;
            font-weight: bold;
            color: {{ $secondaryColor }};
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .signature-name {
            font-size: 13px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 4px;
        }


        .stamp-caption {
            font-size: 9px;
            color: #6b7280;
            text-align: center;

        }

        .qr-caption {
            font-size: 8px;
            color: #6b7280;
            margin-top: 6px;
            line-height: 1.4;
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid #4b5563;
            margin-top: 18px;
            padding-top: 6px;
        }

        .stamp-circle {
            width: 110px;
            height: 110px;
            margin: 0 auto 8px auto;
            border: 1px dashed #014a12;
            border-radius: 50%;
            display: table;
        }

        .stamp-text {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            color: {{ $primaryColor }};
            line-height: 1.4;
        }

        .qr-image-wrap {
            width: 104px;
            height: 104px;
            margin: 4px auto 8px auto;
            padding: 4px auto 8px auto;
            background: #fff;
            border-radius: 8px;
        }

        .qr-image {
            width: 90px;
            height: 90px;
            display: block;
            margin: 0 auto;
        }

        .qr-fallback {
            width: 104px;
            height: 104px;
            margin: 4px auto 8px auto;
            border: 1px solid #999;
            border-radius: 8px;
            font-size: 9px;
            display: table;
            background: #fff;
        }

        .qr-fallback span {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
        }
    </style>
</head>

<body>
    @php
        $eatNow = now('Africa/Nairobi');

        $farmName = setting('farm.name', 'Lelekwe Farms');
        $farmTagline = setting('farm.tagline', 'Nurturing Nature, Feeding The Future');
        $farmPhone = setting('farm.phone', '+254 743 487 186');
        $farmEmail = setting('farm.email', 'jambo@lelekwefarms.co.ke');
        $farmCounty = setting('farm.county', 'Ravine, Kambi Moto');

        $primaryColor = setting('theme.primary', '#014a12');
        $secondaryColor = setting('theme.secondary', '#14532d');
        $accentColor = setting('theme.accent', '#f59e0b');
        $dangerColor = setting('theme.danger', '#dc2626');
        $successColor = setting('theme.success', '#16a34a');

        $logoPath = setting('branding.logo_light');
        $logoFullPath = $logoPath ? public_path('storage/' . ltrim($logoPath, '/')) : null;
    @endphp

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
                    Users Bulk Report
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
            <h1>Users Bulk Report</h1>
            <p>Total selected users: {{ $users->count() }}</p>
        </div>

        <table class="report">
            <thead>
                <tr>
                    <th width="6%">#</th>
                    <th width="10%">Avatar</th>
                    <th width="20%">Name</th>
                    <th width="22%">Email</th>
                    <th width="14%">Phone</th>
                    <th width="16%">Role</th>
                    <th width="12%">Created At</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $index => $userRecord)
                    @php
                        $role = $userRecord->getRoleNames()?->first() ?? 'Other';
                        $roleClass = match ($role) {
                            'Admin' => 'role-admin',
                            'Manager' => 'role-manager',
                            'Finance' => 'role-finance',
                            'Vet' => 'role-vet',
                            default => 'role-other',
                        };
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            @php
    $userAvatarBase64 = pdfImageBase64($userRecord->avatar ?? null);
@endphp

@if ($userAvatarBase64)
    <img src="{{ $userAvatarBase64 }}" class="avatar" alt="Avatar">
@else
    -
@endif
                        </td>
                        <td>{{ $userRecord->name }}</td>
                        <td>{{ $userRecord->email }}</td>
                        <td>{{ $userRecord->phone ?: '-' }}</td>
                        <td>
                            <span class="role-badge {{ $roleClass }}">{{ $role }}</span>
                        </td>
                        <td>{{ optional($userRecord->created_at)->format('d M Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="section-block">
            <table class="signature-table">
                <tr>
                    <td style="width: 26%; padding-top: 16px; padding-right: 8px;">
                        <div class="signature-card">
                            <div class="signature-card-title">Prepared By</div>
                            <div class="signature-name">{{ $generatedBy->name }}</div>
                            <div class="signature-meta">{{ $generatedByRole }}</div>
                            <div class="signature-line"></div>
                            <div class="signature-footer">
                                Generated on {{ $eatNow->format('d M Y, H:i') }} EAT
                            </div>
                        </div>
                    </td>

                    <td style="width: 28%; padding-top: 16px; padding-right: 8px;">
                        <div class="signature-card signature-card-authorized">
                            <div class="signature-card-title">Authorized Signature</div>

                            <div class="signature-handwritten">
                                Digitally.Approved!
                            </div>

                            <div class="signature-meta">
                                Signature Date: {{ $eatNow->format('d M Y, H:i') }} EAT
                            </div>

                            <div class="signature-line"></div>

                            <div class="signature-footer">
                                {{ $farmName }} Management Approval
                            </div>
                        </div>
                    </td>

                    <td style="width: 22%; padding-top: 16px; padding-right: 8px;">
                        <div class="stamp-wrap">
                            <!--<div class="info-card-title">Official Stamp</div>-->

                            <div class="stamp-circle">
                                <div class="stamp-text">OFFICIAL<br>STAMP</div>
                            </div>

                            <div class="stamp-caption">{{ $farmName }} Stamp</div>
                        </div>
                    </td>

                    <td style="width: 24%; padding-top: 16px;">
                        <div class=" qr-box">
                            <!--<div class="info-card-title">Verification QR</div>-->

                            @if (!empty($qrImage))
                                <div class="qr-image-wrap">
                                    <img src="{{ $qrImage }}" alt="QR Code" class="qr-image">
                                </div>
                            @else
                                <div class="qr-fallback">
                                    <span>QR not available</span>
                                </div>
                            @endif

                            <div class="qr-caption">
                                Scan to verify report metadata
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
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
