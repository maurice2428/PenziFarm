@php
    if (!function_exists('animalLabPdfImageBase64')) {
        function animalLabPdfImageBase64(?string $path): ?string
        {
            if (blank($path)) {
                return null;
            }

            $cleanPath = ltrim(trim($path), '/');
            $cleanPath = preg_replace('#^storage/#', '', $cleanPath);

            $possiblePaths = [
                storage_path('app/public/' . $cleanPath),
                public_path('storage/' . $cleanPath),
                public_path($cleanPath),
            ];

            foreach ($possiblePaths as $fullPath) {
                if (!is_file($fullPath)) {
                    continue;
                }

                $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

                $mime = match ($extension) {
                    'jpg', 'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    'webp' => 'image/webp',
                    default => 'image/png',
                };

                return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullPath));
            }

            return null;
        }
    }

    $farmName = setting('farm.name', 'Penzi Farm Limited');

    $farmTagline = setting('farm.tagline', setting('farm.slogan', 'Nurturing Quality, Inspiring Global Standards'));

    $farmPhone = setting('farm.phone', '+254 757 046 726');
    $farmEmail = setting('farm.email', 'jambo@penzifarm.com');
    $farmCounty = setting('farm.county', 'Molo - Nakuru County');
    $farmAddress = setting('farm.address', 'Penzi Farm');

    $primaryColor = setting('theme.primary', '#14532d');
    $secondaryColor = setting('theme.secondary', '#b8892d');
    $accentColor = setting('theme.accent', '#f59e0b');
    $successColor = setting('theme.success', '#16a34a');
    $dangerColor = setting('theme.danger', '#dc2626');

    $logoBase64 =
        animalLabPdfImageBase64(setting('branding.logo_light')) ?:
        animalLabPdfImageBase64(setting('branding.logo_dark')) ?:
        animalLabPdfImageBase64('images/logo.png');

    $authorizedCompany = setting('farm.legal_name', $farmName);

    $authorizedDepartment = setting('pdf.authorized_signatory_department', 'Mgt');

    $clinic = $clinic ?? null;

    $clinicName = $clinicName ?? ($clinic?->display_name ?? ($labRequest->clinic_name ?? 'Not recorded'));

    $latestWeight = $animal?->latestWeight;

    $healthAdministrations = $healthAdministrations ?? collect();

    //$fontPath = 'file://' . public_path('fonts/ChopinScript.ttf');
    $fontPath = 'file://' . storage_path('fonts/ChopinScript.ttf');

    $statusColor = match ($labRequest->status) {
        'Completed' => $successColor,
        'Cancelled' => $dangerColor,
        'In Progress' => $accentColor,
        'Dispatched' => '#2563eb',
        default => '#6b7280',
    };
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <title>
        {{ $labRequest->request_number }} - Laboratory Request
    </title>

    <style>
        @font-face {
            font-family: "ChopinScript";
            src: url("{{ $fontPath }}") format("truetype");

        }

        @page {
            margin: 112px 34px 92px 34px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Courier, "Courier New", monospace;
            color: #111827;
            font-size: 10px;
            line-height: 1.42;
        }

        header {
            position: fixed;
            top: -92px;
            left: 0;
            right: 0;
            height: 82px;
            border-bottom: 2px solid {{ $primaryColor }};
        }

        footer {
            position: fixed;
            bottom: -72px;
            left: 0;
            right: 0;
            height: 60px;
            border-top: 1px solid #d1d5db;
            color: #4b5563;
            font-size: 8px;
        }

        .header-table,
        .footer-table,
        .detail-table,
        .history-table,
        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td,
        .footer-table td,
        .signature-table td {
            vertical-align: middle;
        }

        .logo {
            width: 135px;
            max-height: 84px;
            object-fit: contain;
        }

        .company-name {
            color: {{ $primaryColor }};
            font-size: 19px;
            font-weight: bold;
            text-align: center;
        }

        .company-tagline {
            color: #4b5563;
            font-size: 9px;
            font-style: italic;
            text-align: center;
            margin-top: 3px;
        }

        .company-contact {
            color: #4b5563;
            font-size: 8px;
            line-height: 1.55;
            text-align: right;
        }

        .watermark {
            position: fixed;
            top: 290px;
            left: 165px;
            opacity: 0.055;
            z-index: -1;
        }

        .watermark img {
            width: 270px;
            height: auto;
        }

        .document-banner {
            border: 1px solid {{ $primaryColor }};
            border-left: 7px solid {{ $secondaryColor }};
            background: #f8fffa;
            padding: 13px 15px;
            margin-bottom: 16px;
        }

        .document-title {
            color: {{ $primaryColor }};
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .document-subtitle {
            color: #4b5563;
            font-size: 9px;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 9px;
            color: #ffffff;
            background: {{ $statusColor }};
            font-size: 8px;
            font-weight: bold;
            border-radius: 999px;
        }

        .section-title {
            color: {{ $primaryColor }};
            font-size: 11px;
            font-weight: bold;
            margin: 17px 0 6px;
            padding-bottom: 4px;
            border-bottom: 1px solid {{ $secondaryColor }};
        }

        .detail-table td,
        .history-table td,
        .history-table th {
            border: 1px solid #1f2937;
            padding: 6px;
            vertical-align: top;
        }

        .detail-table .label {
            width: 23%;
            font-weight: bold;
            background: #f8fafc;
        }

        .history-table th {
            background: {{ $primaryColor }};
            color: #ffffff;
            font-size: 8px;
            text-align: left;
        }

        .history-table tbody tr:nth-child(even) {
            background: #f9fafb;
        }

        .muted {
            color: #6b7280;
        }

        .verification-box {
            min-height: 134px;
            padding: 10px;
            border: 1px solid {{ $secondaryColor }};
            background: #fffdf7;
            text-align: center;
        }

        .qr-image {
            width: 92px;
            height: 92px;
            padding: 3px;
            border: 2px solid {{ $primaryColor }};
            background: #ffffff;
        }

        .qr-caption {
            color: #6b7280;
            font-size: 7px;
            line-height: 1.35;
            margin-top: 5px;
        }

        .signature-card {
            min-height: 120px;
            padding: 11px;
            border: 1px solid #d1d5db;
            background: #ffffff;
        }

        .signature-card.highlight {
            border-color: {{ $secondaryColor }};
            background: #fffdf7;
        }

        .signature-heading {
            color: {{ $primaryColor }};
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .signature-name {
            color: #111827;
            font-size: 11px;
            font-weight: bold;
        }

        .signature-meta {
            color: #6b7280;
            font-size: 8px;
            margin-top: 4px;
        }

        .signature-script-company {
            font-family: "ChopinScript", cursive !important;
            color: {{ $primaryColor }} !important;
            font-size: 18px;
            line-height: 1;
            margin: 5px 0 3px;
        }

        .signature-script-department {
            font-family: "ChopinScript", cursive !important;
            color: {{ $primaryColor }} !important;
            font-size: 22px;
            line-height: 1;
            margin: 0 0 8px;
        }

        .signature-line {
            border-top: 1px solid #374151;
            margin-top: 18px;
            padding-top: 5px;
            color: #6b7280;
            font-size: 8px;
        }

        .footer-left {
            text-align: left;
        }

        .footer-center {
            text-align: center;
        }

        .footer-right {
            text-align: right;
        }

        .page-break-avoid {
            page-break-inside: avoid;
        }
    </style>
</head>

<body>
    @if ($logoBase64)
        <div class="watermark">
            <img src="{{ $logoBase64 }}" alt="Watermark">
        </div>
    @endif

    <header>
        <table class="header-table">
            <tr>
                <td width="26%">
                    @if ($logoBase64)
                        <img src="{{ $logoBase64 }}" class="logo" alt="Farm Logo">
                    @endif
                </td>

                <td width="44%">
                    <div class="company-name">{{ $farmName }}</div>

                    <div class="company-tagline">
                        {{ $farmTagline }}
                    </div>
                </td>

                <td width="30%" class="company-contact">
                    <strong>Phone:</strong> {{ $farmPhone }}<br>
                    <strong>Email:</strong> {{ $farmEmail }}<br>
                    <strong>Location:</strong> {{ $farmCounty }}
                </td>
            </tr>
        </table>
    </header>

    <footer>
        <table class="footer-table" style="margin-top: 8px;">
            <tr>
                <td class="footer-left" width="33%">
                    Generated:
                    {{ $generatedAt->format('d M Y, H:i') }} EAT
                </td>

                <td class="footer-center" width="34%">
                    {{ $farmName }} · Laboratory Request
                </td>

                <td class="footer-right" width="33%">
                    Prepared by {{ $generatedBy?->name ?? 'System' }}
                    ({{ $generatedByRole }})
                </td>
            </tr>

            <tr>
                <td colspan="3" class="footer-center muted">
                    {{ $farmName }} · {{ $farmAddress }} ·
                    {{ $farmCounty }} · {{ $farmPhone }} ·
                    {{ $farmEmail }}
                </td>
            </tr>
        </table>
    </footer>

    <main>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 16px;">
            <tr>
                <td width="72%">
                    <div class="document-banner">
                        <div class="document-title">
                            ANIMAL LABORATORY REQUEST FORM
                        </div>

                        <div class="document-subtitle">
                            Request No:
                            <strong>{{ $labRequest->request_number }}</strong>

                            · Animal:
                            <strong>
                                {{ $animal?->tag_number ?? 'Not recorded' }}
                            </strong>

                            · Requested:
                            {{ optional($labRequest->requested_at)->format('d M Y H:i') ?? 'Not recorded' }}
                        </div>
                    </div>
                </td>

                <td width="28%" style="padding-left: 10px; text-align: right;">
                    <span class="status-badge">
                        {{ $labRequest->status }}
                    </span>
                </td>
            </tr>
        </table>

        <div class="section-title">Farm & Laboratory Details</div>

        <table class="detail-table">
            <tr>
                <td class="label">Farm / Client</td>
                <td>{{ $farmName }}</td>

                <td class="label">Veterinary Clinic / Laboratory</td>
                <td>{{ $clinicName }}</td>
            </tr>

            <tr>
                <td class="label">Farm Contact</td>
                <td>{{ $farmPhone }} · {{ $farmEmail }}</td>

                <td class="label">Clinic Contact</td>
                <td>
                    {{ $clinic?->contact_person ?: 'Not recorded' }}

                    @if ($clinic?->phone)
                        · {{ $clinic->phone }}
                    @endif
                </td>
            </tr>

            <tr>
                <td class="label">Farm Location</td>
                <td>{{ $farmCounty }}</td>

                <td class="label">Clinic Address</td>
                <td>
                    {{ $clinic?->address ?: 'Not recorded' }}

                    @if ($clinic?->county)
                        · {{ $clinic->county }}
                    @endif
                </td>
            </tr>
        </table>

        <div class="section-title">Affected Animal & Clinical Context</div>

        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td width="72%" style="vertical-align: top;">
                    <table class="detail-table">
                        <tr>
                            <td class="label">Animal Tag</td>
                            <td>{{ $animal?->tag_number ?? 'Not recorded' }}</td>

                            <td class="label">Breed</td>
                            <td>
                                {{ $animal?->breed?->breed_name ?? 'Not recorded' }}
                            </td>
                        </tr>

                        <tr>
                            <td class="label">Sex</td>
                            <td>{{ $animal?->sex ?? 'Not recorded' }}</td>

                            <td class="label">Age</td>
                            <td>{{ $age }}</td>
                        </tr>

                        <tr>
                            <td class="label">Current Location</td>
                            <td>
                                {{ $animal?->location?->display_name ?? 'Not recorded' }}
                            </td>

                            <td class="label">Latest Weight</td>
                            <td>
                                {{ $latestWeight ? number_format((float) $latestWeight->weight_kg, 2) . ' KG' : 'Not recorded' }}
                            </td>
                        </tr>

                        <tr>
                            <td class="label">Sick Case</td>
                            <td colspan="3">
                                {{ $labRequest->clinicalCase?->case_number ?? 'Standalone laboratory request' }}
                            </td>
                        </tr>

                        <tr>
                            <td class="label">Signs & Symptoms</td>
                            <td colspan="3">
                                {{ $labRequest->clinicalCase?->clinical_signs ?: ($labRequest->clinical_signs ?: 'Not recorded') }}
                            </td>
                        </tr>

                        <tr>
                            <td class="label">Length of Illness</td>
                            <td>
                                {{ $labRequest->length_of_illness ?: 'Not recorded' }}
                            </td>

                            <td class="label">Temperature</td>
                            <td>
                                {{ $labRequest->temperature_c !== null ? $labRequest->temperature_c . ' °C' : 'Not recorded' }}
                            </td>
                        </tr>

                        <tr>
                            <td class="label">Animal Source</td>
                            <td>
                                {{ $labRequest->animal_source ?: 'Not recorded' }}
                            </td>

                            <td class="label">Attending Officer</td>
                            <td>
                                {{ $labRequest->attending_officer ?: 'Not recorded' }}
                            </td>
                        </tr>
                    </table>
                </td>

                <td width="28%" style="vertical-align: top; padding-left: 10px;">
                    <div class="verification-box">
                        @if ($qrPng)
                            <img src="data:image/png;base64,{{ $qrPng }}" class="qr-image"
                                alt="Verification QR">
                        @endif

                        <div class="qr-caption">
                            Scan to verify request reference.<br>
                            {{ $labRequest->request_number }}
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="section-title">Sample & Requested Tests</div>

        <table class="detail-table">
            <tr>
                <td class="label">Specimen Collected</td>
                <td>
                    {{ $labRequest->specimens_text ?: 'Not recorded' }}
                </td>

                <td class="label">Testing Purpose</td>
                <td>
                    {{ $labRequest->testing_purpose ?: 'Not recorded' }}
                </td>
            </tr>

            <tr>
                <td class="label">Tests Requested</td>
                <td colspan="3">
                    {{ $labRequest->requested_tests_text ?: 'Not recorded' }}
                </td>
            </tr>

            <tr>
                <td class="label">Sample Collected At</td>
                <td>
                    {{ optional($labRequest->sample_collected_at)->format('d M Y H:i') ?? 'Not recorded' }}
                </td>

                <td class="label">Dispatched At</td>
                <td>
                    {{ optional($labRequest->dispatched_at)->format('d M Y H:i') ?? 'Not recorded' }}
                </td>
            </tr>

            <tr>
                <td class="label">Testing Date</td>
                <td>
                    {{ optional($labRequest->testing_date)->format('d M Y H:i') ?? 'Not recorded' }}
                </td>

                <td class="label">Results Received</td>
                <td>
                    {{ optional($labRequest->resulted_at)->format('d M Y H:i') ?? 'Pending' }}
                </td>
            </tr>
        </table>

        <div class="section-title">
            Vaccination, Deworming & Health History
        </div>

        <table class="history-table">
            <thead>
                <tr>
                    <th width="14%">Type</th>
                    <th width="26%">Product</th>
                    <th width="14%">Date Given</th>
                    <th width="14%">Next Due</th>
                    <th width="16%">Dosage</th>
                    <th width="16%">Notes</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($healthAdministrations as $administration)
                    @php
                        $productType = ucwords(
                            str_replace('_', ' ', $administration->product_type ?? 'Health Product'),
                        );

                        $dosageUnit = $administration->dosage_unit ?? '';
                    @endphp

                    <tr>
                        <td>{{ $productType }}</td>

                        <td>
                            {{ $administration->product_name ?? 'Product not recorded' }}
                        </td>

                        <td>
                            {{ $administration->administered_at ? $administration->administered_at->format('d M Y') : 'Not recorded' }}
                        </td>

                        <td>
                            {{ $administration->next_due_date ? $administration->next_due_date->format('d M Y') : 'N/A' }}
                        </td>

                        <td>
                            {{ number_format((float) $administration->dosage_per_animal, 2) }}
                            {{ $dosageUnit }}

                            <br>

                            <span class="muted">
                                Total:
                                {{ number_format((float) $administration->total_quantity_used, 2) }}
                                {{ $dosageUnit }}
                            </span>
                        </td>

                        <td>
                            {{ $administration->notes ?: '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="muted">
                            No Health Administration records have been recorded
                            for this animal.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="section-title">
            Laboratory Results & Recommendations
        </div>

        <table class="detail-table">
            <tr>
                <td class="label">Results</td>
                <td>
                    {{ $labRequest->results ?: 'Pending laboratory results.' }}
                </td>
            </tr>

            <tr>
                <td class="label">Recommended Medication / Action</td>
                <td>
                    {{ $labRequest->recommended_medication ?: 'Pending recommendation.' }}
                </td>
            </tr>

            <tr>
                <td class="label">Additional Notes</td>
                <td>
                    {{ $labRequest->notes ?: 'No additional notes recorded.' }}
                </td>
            </tr>
        </table>

        <div class="section-title">Verification & Signatures</div>

        <table class="signature-table page-break-avoid">
            <tr>
                <td width="34%" style="padding-right: 8px;">
                    <div class="signature-card">
                        <div class="signature-heading">Prepared By</div>

                        <div class="signature-name">
                            {{ $generatedBy?->name ?? 'System User' }}
                        </div>

                        <div class="signature-meta">
                            {{ $generatedByRole }}<br>

                            Generated
                            {{ $generatedAt->format('d M Y, H:i') }} EAT
                        </div>

                        <div class="signature-line">
                            Prepared by signature
                        </div>
                    </div>
                </td>

                <td width="40%" style="padding-right: 8px;">
                    <div class="signature-card highlight">
                        <div class="signature-heading">
                            AUTHORISED SIGNATORY
                        </div>

                        <div class="signature-script-company">
                            {{ $authorizedCompany }}
                        </div>

                      <!-- <div class="signature-script-department">
                            //$authorizedDepartment }}
                        </div>-->

                        <div class="signature-meta">
                            Digitally generated on
                            {{ $generatedAt->format('d M Y, H:i') }} EAT
                        </div>

                        <div class="signature-line">
                            {{ $authorizedCompany }}
                            {{ $authorizedDepartment }}
                        </div>
                    </div>
                </td>

                <td width="26%">
                    <div class="signature-card" style="text-align: center;">
                        <div class="signature-heading">
                            Verification Reference
                        </div>

                        <strong style="color: {{ $primaryColor }};">
                            {{ $labRequest->request_number }}
                        </strong>

                        <div class="signature-meta" style="margin-top: 12px;">
                            Generated from the {{ $farmName }}
                            Animal Health system.
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </main>

    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->getFont('Courier', 'normal');

            $pdf->page_text(
                470,
                805,
                'Page {PAGE_NUM} of {PAGE_COUNT}',
                $font,
                8,
                [0.35, 0.38, 0.42]
            );
        }
    </script>
</body>

</html>
