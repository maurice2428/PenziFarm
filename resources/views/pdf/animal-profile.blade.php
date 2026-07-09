@php
    /*
    |--------------------------------------------------------------------------
    | Penzi Farm — World-Class Animal Profile PDF
    |--------------------------------------------------------------------------
    | Courier-only, DomPDF-safe, fixed two-page breeder certificate.
    | Header/footer values are resolved from the controller first and then
    | from the live Farm / Branding / Theme settings.
    */
    if (! function_exists('animalProfilePdfImage')) {
        function animalProfilePdfImage(?string $path): ?string
        {
            if (blank($path)) {
                return null;
            }

            $path = preg_replace('#^/?storage/#', '', trim((string) $path));

            foreach ([
                storage_path('app/public/' . $path),
                public_path('storage/' . $path),
                public_path($path),
            ] as $file) {
                if (! is_file($file)) {
                    continue;
                }

                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                $mime = match ($extension) {
                    'jpg', 'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    'webp' => 'image/webp',
                    'svg' => 'image/svg+xml',
                    default => 'image/png',
                };

                return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($file));
            }

            return null;
        }
    }

    $generatedAt = $generatedAt ?? now('Africa/Nairobi');

    $farmName = $farmName ?? setting('farm.name', 'Penzi Farm');
    $farmLegalName = $farmLegalName ?? setting('farm.legal_name', $farmName);
    $farmTagline = $farmTagline ?? setting('farm.tagline', 'Nurturing Quality, Inspiring Global Standards');
    $farmPhone = $farmPhone ?? setting('farm.phone', 'Not recorded');
    $farmEmail = $farmEmail ?? setting('farm.email', 'Not recorded');
    $farmCounty = $farmCounty ?? setting('farm.county', 'Kenya');
    $farmAddress = setting('farm.address', $farmCounty);
    $primaryColor = $primaryColor ?? setting('theme.primary', '#14532d');
    $secondaryColor = $secondaryColor ?? setting('theme.secondary', '#1f3d2b');
    $accentColor = $accentColor ?? setting('theme.accent', '#b8860b');
    $logoPath = $logoPath ?? setting('branding.logo_light');

    $logo = animalProfilePdfImage($logoPath);

    $breed = $animal->breed?->breed_name ?? 'Unknown Breed';
    $purityBreed = $animal->purityBreed?->breed_name ?? $breed;

    $purity = $animal->breed_purity_percent !== null
        ? number_format((float) $animal->breed_purity_percent, 2) . '%'
        : 'Pending';

    $purityStatus = match ($animal->purity_status) {
        'foundation' => 'Foundation Stock',
        'calculated' => 'Calculated Pedigree',
        'dna_verified' => 'DNA Verified',
        'manual_verified' => 'Manual Verified',
        default => 'Pending Parentage',
    };

    $age = $animal->date_of_birth
        ? $animal->date_of_birth->diffForHumans($generatedAt, [
            'parts' => 2,
            'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE,
        ])
        : 'Not recorded';

    if ($animal->date_of_birth && $animal->date_of_birth_is_estimated) {
        $age = 'Approx. ' . $age;
    }

    $weight = $animal->latestWeight;

    $sire = $animal->sire;
    $dam = $animal->dam;
    $sireSire = $sire?->sire;
    $sireDam = $sire?->dam;
    $damSire = $dam?->sire;
    $damDam = $dam?->dam;

    $pedigreeNode = static function ($animalNode): array {
        if (! $animalNode) {
            return [
                'tag' => 'NOT RECORDED',
                'meta' => 'Parentage pending',
                'status' => 'Pending',
            ];
        }

        return [
            'tag' => $animalNode->tag_number ?? 'NOT RECORDED',
            'meta' => trim(($animalNode->breed?->breed_name ?? 'Breed pending') . ' · ' . ($animalNode->sex ?? 'Sex pending')),
            'status' => $animalNode->breed_purity_percent !== null
                ? number_format((float) $animalNode->breed_purity_percent, 2) . '%'
                : 'Pending',
        ];
    };

    $healthType = static fn ($entry): string => str($entry->product?->type ?? 'Health')
        ->replace('_', ' ')
        ->title()
        ->toString();

    $value = static fn ($input, string $fallback = 'Not recorded'): string => filled($input) ? (string) $input : $fallback;

    $healthRecords = $animal->healthAdministrations->take(8);
    $clinicalCases = $animal->clinicalCases->take(4);
    $treatments = $animal->treatmentRecords->take(4);
    $labs = $animal->labRequests->take(4);

    $openCases = $animal->clinicalCases
        ->filter(fn ($case) => ! in_array($case->status, ['Resolved', 'Closed'], true))
        ->count();

    $pendingLabs = $animal->labRequests
        ->filter(fn ($lab) => ! in_array($lab->status, ['Completed', 'Cancelled'], true))
        ->count();

    $generatedByName = $generatedBy?->name ?? 'System';
    $generatedByRole = $generatedByRole ?? 'User';

    $verificationText = implode(' | ', [
        $farmLegalName,
        'Animal Profile',
        $animal->tag_number,
        'Generated ' . $generatedAt->format('Y-m-d H:i:s') . ' EAT',
        'By ' . $generatedByName . ' (' . $generatedByRole . ')',
    ]);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ strtoupper($animal->tag_number) }} — Pedigree & Animal Profile</title>
    <style>
        @page { margin: 16px 22px 18px 22px; }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Courier, "Courier New", monospace;
            font-size: 7.45px;
            line-height: 1.25;
            color: #111827;
            background: #ffffff;
        }

        .page {
            min-height: 1064px;
            position: relative;
            page-break-inside: avoid;
        }

        .page-break { page-break-before: always; }

        .header-table,
        .contact-table,
        .meta-grid,
        .history-table,
        .footer-table,
        .split-table {
            width: 100%;
            border-collapse: collapse;
        }

        /* Dynamic certificate header */
        .header-shell {
            border-top: 5px solid {{ $primaryColor }};
            border-bottom: 1px solid #9db19c;
            padding: 6px 0 7px;
        }

        .logo-cell {
            width: 82px;
            vertical-align: middle;
            text-align: left;
        }

        .logo {
            max-width: 66px;
            max-height: 56px;
            object-fit: contain;
        }

        .header-center {
            vertical-align: middle;
            text-align: center;
            padding: 0 8px;
        }

        .header-right {
            width: 224px;
            vertical-align: middle;
            text-align: right;
        }

        .farm-name {
            color: {{ $primaryColor }};
            font-size: 14px;
            font-weight: 900;
            letter-spacing: .09em;
            line-height: 1.04;
            text-transform: uppercase;
        }

        .farm-legal-name {
            margin-top: 2px;
            color: #435466;
            font-size: 6.7px;
            font-weight: 900;
            letter-spacing: .045em;
            text-transform: uppercase;
        }

        .farm-tagline {
            margin-top: 3px;
            color: #64748b;
            font-size: 7.2px;
            font-style: italic;
        }

        .contact-table td {
            padding: 1px 0;
            vertical-align: top;
        }

        .contact-label {
            width: 64px;
            color: #6b7a8a;
            font-size: 6px;
            font-weight: 900;
            letter-spacing: .04em;
            text-align: right;
            text-transform: uppercase;
        }

        .contact-value {
            color: #1f2937;
            font-size: 6.65px;
            font-weight: 800;
            text-align: right;
        }

        .title-ribbon {
            margin-top: 7px;
            border: 1px solid {{ $primaryColor }};
            background: {{ $primaryColor }};
            color: #ffffff;
            padding: 7px 9px 6px;
            text-align: center;
            font-size: 11.1px;
            font-weight: 900;
            letter-spacing: .08em;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .document-reference {
            margin: 4px 0 7px;
            color: #64748b;
            font-size: 6.65px;
            text-align: center;
        }

        .section-title {
            margin-top: 7px;
            border-bottom: 1px solid {{ $accentColor }};
            color: {{ $secondaryColor }};
            padding: 0 0 3px;
            font-size: 8.25px;
            font-weight: 900;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        /* Joined registration record: no slash-combined values. */
        .meta-grid {
            margin-top: 5px;
            table-layout: fixed;
            border: 1px solid #99ad98;
        }

        .meta-grid td {
            width: 33.333%;
            border: 1px solid #bdcbbb;
            padding: 4px 5px;
            vertical-align: top;
        }

        .meta-key {
            display: block;
            color: #627388;
            font-size: 6.05px;
            font-weight: 900;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .meta-value {
            display: block;
            margin-top: 2px;
            color: #111827;
            font-size: 7.75px;
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        /* Pedigree chart */
        .pedigree-stage {
            position: relative;
            height: 314px;
            margin-top: 6px;
            overflow: hidden;
            border: 1px solid #98aa97;
            background:
                linear-gradient(90deg, rgba(20, 83, 45, .032) 1px, transparent 1px),
                linear-gradient(rgba(20, 83, 45, .032) 1px, transparent 1px),
                #fbfdf9;
            background-size: 15px 15px;
        }

        .pedigree-stage::before {
            content: "GRANDPARENTS";
            position: absolute;
            left: 2%;
            top: 4px;
            color: #708074;
            font-size: 5.85px;
            font-weight: 900;
            letter-spacing: .08em;
        }

        .pedigree-stage::after {
            content: "PARENTS                                      SELECTED ANIMAL";
            position: absolute;
            left: 39%;
            top: 4px;
            width: 58%;
            color: #708074;
            font-size: 5.85px;
            font-weight: 900;
            letter-spacing: .065em;
            white-space: pre;
        }

        .pedigree-connectors {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 314px;
            z-index: 1;
        }

        .pedigree-connector {
            position: absolute;
            display: block;
            font-size: 0;
            line-height: 0;
        }

        .pedigree-connector-h { height: 0; border-top: 2px solid {{ $primaryColor }}; }
        .pedigree-connector-v { width: 0; border-left: 2px solid {{ $primaryColor }}; }

        /* Grandparents → Sire */
        .line-ss-h { left: 21%; top: 57px; width: 9%; }
        .line-ss-v { left: 30%; top: 57px; height: 42px; }
        .line-sd-h { left: 21%; top: 127px; width: 9%; }
        .line-sd-v { left: 30%; top: 99px; height: 28px; }
        .line-sire-in { left: 30%; top: 99px; width: 9%; }

        /* Grandparents → Dam */
        .line-ds-h { left: 21%; top: 213px; width: 9%; }
        .line-ds-v { left: 30%; top: 213px; height: 26px; }
        .line-dd-h { left: 21%; top: 283px; width: 9%; }
        .line-dd-v { left: 30%; top: 239px; height: 44px; }
        .line-dam-in { left: 30%; top: 239px; width: 9%; }

        /* Sire + Dam → selected animal */
        .line-sire-out { left: 61%; top: 99px; width: 8%; }
        .line-sire-down { left: 69%; top: 99px; height: 70px; }
        .line-dam-out { left: 61%; top: 239px; width: 8%; }
        .line-dam-up { left: 69%; top: 169px; height: 70px; }
        .line-subject-in { left: 69%; top: 169px; width: 8%; }

        .pedigree-node {
            position: absolute;
            z-index: 2;
            width: 19%;
            min-height: 51px;
            border: 1px solid #8da28c;
            background: #ffffff;
            padding: 5px 5px 4px;
        }

        .pedigree-node-parent {
            width: 22%;
            min-height: 64px;
            border: 2px solid #789576;
            background: #f7fbf6;
        }

        .pedigree-node-subject {
            width: 21%;
            min-height: 70px;
            border: 2px solid {{ $primaryColor }};
            background: #eff8ef;
        }

        .node-role {
            color: #5c6e81;
            font-size: 5.85px;
            font-weight: 900;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .node-tag {
            margin-top: 3px;
            color: #111827;
            font-size: 7.95px;
            font-weight: 900;
            overflow-wrap: anywhere;
        }

        .node-meta {
            margin-top: 2px;
            color: #66758a;
            font-size: 5.95px;
            overflow-wrap: anywhere;
        }

        .node-status {
            margin-top: 3px;
            color: {{ $primaryColor }};
            font-size: 5.9px;
            font-weight: 900;
        }

        .gss { left: 2%; top: 31px; }
        .gsd { left: 2%; top: 101px; }
        .gds { left: 2%; top: 187px; }
        .gdd { left: 2%; top: 257px; }
        .sire { left: 39%; top: 67px; }
        .dam { left: 39%; top: 207px; }
        .subject { left: 77%; top: 134px; }

        .breeder-note {
            margin-top: 6px;
            border: 1px solid #b7c7b4;
            border-left: 4px solid {{ $primaryColor }};
            background: #f7fbf6;
            padding: 5px 7px;
            color: #344155;
            font-size: 6.75px;
            line-height: 1.42;
        }

        .breeder-note strong { color: {{ $primaryColor }}; }

        /* Health page */
        .history-table {
            margin-top: 5px;
            table-layout: fixed;
            border: 1px solid #a8b7a6;
        }

        .history-table th {
            border: 1px solid #8fa28c;
            background: {{ $primaryColor }};
            color: #ffffff;
            padding: 4px 3px;
            font-size: 6.1px;
            text-align: left;
            vertical-align: middle;
        }

        .history-table td {
            border: 1px solid #c6d1c4;
            padding: 3.5px 3px;
            vertical-align: top;
            font-size: 6.35px;
            overflow-wrap: anywhere;
        }

        .history-table tbody tr:nth-child(even) { background: #fbfcfa; }

        .split-table {
            margin-top: 7px;
            table-layout: fixed;
        }

        .split-table > tbody > tr > td {
            width: 50%;
            vertical-align: top;
        }

        .split-table > tbody > tr > td:first-child { padding-right: 3px; }
        .split-table > tbody > tr > td:last-child { padding-left: 3px; }

        .summary-box,
        .verification-box {
            min-height: 77px;
            border: 1px solid #aab9a8;
            background: #fbfdf9;
            padding: 5px 6px;
        }

        .summary-box {
            border-left: 4px solid {{ $primaryColor }};
            color: #38485b;
            font-size: 6.55px;
            line-height: 1.42;
        }

        .summary-box strong { color: {{ $primaryColor }}; }

        .verification-box { background: #ffffff; }

        .verification-qr {
            float: left;
            width: 62px;
            height: 62px;
            margin-right: 7px;
            border: 1px solid {{ $primaryColor }};
            padding: 2px;
            background: #ffffff;
        }

        .verification-title {
            color: {{ $primaryColor }};
            font-size: 6.7px;
            font-weight: 900;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .verification-copy {
            margin-top: 4px;
            color: #627286;
            font-size: 6.05px;
            line-height: 1.42;
        }

        /* ------------------------------------------------------------------ */
        /* Premium certificate footer                                        */
        /* Layout: logo far left · farm identity centre · reference far right */
        /* ------------------------------------------------------------------ */
        .footer-wrap {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
        }

        .footer-rule-primary {
            height: 2px;
            background: {{ $primaryColor }};
        }

        .footer-rule-accent {
            height: 1px;
            margin-top: 1px;
            background: {{ $accentColor }};
        }

        .footer-table {
            margin-top: 6px;
        }

        .footer-table td {
            vertical-align: middle;
            padding: 0;
        }

        .footer-brand-cell {
            width: 30%;
            text-align: left;
        }

        .footer-logo {
            display: inline-block;
            width: 20px;
            height: 20px;
            margin-right: 5px;
            vertical-align: middle;
            object-fit: contain;
        }

        .footer-brand-text {
            display: inline-block;
            vertical-align: middle;
        }

        .footer-brand-name {
            color: {{ $primaryColor }};
            font-size: 6.9px;
            font-weight: 900;
            letter-spacing: .05em;
            text-transform: uppercase;
            line-height: 1.15;
        }

        .footer-brand-legal {
            color: #7a8796;
            font-size: 5.55px;
            font-weight: 700;
            letter-spacing: .03em;
            text-transform: uppercase;
        }

        .footer-center-cell {
            width: 40%;
            text-align: center;
        }

        .footer-doc-label {
            color: {{ $secondaryColor }};
            font-size: 6.15px;
            font-weight: 900;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .footer-doc-sub {
            margin-top: 1px;
            color: #8492a3;
            font-size: 5.5px;
            letter-spacing: .03em;
        }

        .footer-right-cell {
            width: 30%;
            text-align: right;
        }

        .footer-tag {
            color: {{ $primaryColor }};
            font-size: 6.6px;
            font-weight: 900;
            letter-spacing: .04em;
        }

        .footer-page {
            margin-top: 1px;
            color: #7a8796;
            font-size: 5.65px;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
        }

        .footer-meta-row td {
            padding-top: 4px;
            border-top: 1px solid #e3e9e1;
        }

        .footer-meta {
            text-align: center;
            color: #93a0ae;
            font-size: 5.45px;
            letter-spacing: .015em;
        }

        .watermark {
            position: absolute;
            z-index: 0;
            top: 39%;
            left: 36%;
            width: 205px;
            opacity: .03;
        }

        .muted { color: #66758a; }
    </style>
</head>
<body>
    {{-- PAGE 1: Breeder pedigree certificate --}}
    <div class="page">
        @if ($logo)
            <img src="{{ $logo }}" class="watermark" alt="">
        @endif

        <div class="header-shell">
            <table class="header-table">
                <tr>
                    <td class="logo-cell">
                        @if ($logo)
                            <img src="{{ $logo }}" class="logo" alt="{{ $farmName }} logo">
                        @endif
                    </td>
                    <td class="header-center">
                        <div class="farm-name">{{ $farmName }}</div>
                        <div class="farm-legal-name">{{ $farmLegalName }}</div>
                        <div class="farm-tagline">{{ $farmTagline }}</div>
                    </td>
                    <td class="header-right">
                        <table class="contact-table">
                            <tr><td class="contact-label">Location</td><td class="contact-value">{{ $farmCounty }}</td></tr>
                            <tr><td class="contact-label">Telephone</td><td class="contact-value">{{ $farmPhone }}</td></tr>
                            <tr><td class="contact-label">Email</td><td class="contact-value">{{ $farmEmail }}</td></tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        <div class="title-ribbon">{{ strtoupper($breed) }} · Pedigree & Animal Profile</div>
        <div class="document-reference">Breeding record and management profile · {{ strtoupper($animal->tag_number) }} · Issued {{ $generatedAt->format('d M Y, H:i') }} EAT</div>

        <div class="section-title">Animal Registration & Breeding Summary</div>

        <table class="meta-grid">
            <tr>
                <td><span class="meta-key">Farm Tag</span><span class="meta-value">{{ $animal->tag_number }}</span></td>
                <td><span class="meta-key">Breed</span><span class="meta-value">{{ $breed }}</span></td>
                <td><span class="meta-key">Species</span><span class="meta-value">{{ $value($animal->species) }}</span></td>
            </tr>
            <tr>
                <td><span class="meta-key">Sex</span><span class="meta-value">{{ $value($animal->sex) }}</span></td>
                <td><span class="meta-key">Current Lifecycle</span><span class="meta-value">{{ $value($animal->status) }}</span></td>
                <td><span class="meta-key">Date of Birth</span><span class="meta-value">{{ $animal->date_of_birth?->format('d M Y') ?? 'Not recorded' }}</span></td>
            </tr>
            <tr>
                <td><span class="meta-key">Age</span><span class="meta-value">{{ $age }}</span></td>
                <td><span class="meta-key">Breed Purity</span><span class="meta-value">{{ $purity }}</span></td>
                <td><span class="meta-key">Purity Evidence</span><span class="meta-value">{{ $purityStatus }}</span></td>
            </tr>
            <tr>
                <td><span class="meta-key">Purity Breed</span><span class="meta-value">{{ $purityBreed }}</span></td>
                <td><span class="meta-key">Current Location</span><span class="meta-value">{{ $animal->location?->display_name ?? $animal->location?->name ?? 'Not recorded' }}</span></td>
                <td><span class="meta-key">Latest Weight</span><span class="meta-value">{{ $weight ? number_format((float) $weight->weight_kg, 2) . ' KG · ' . ucfirst($weight->trend ?? 'first') : 'No weight recorded' }}</span></td>
            </tr>
            <tr>
                <td><span class="meta-key">Source</span><span class="meta-value">{{ $value($animal->source) }}</span></td>
                <td><span class="meta-key">Management Purpose</span><span class="meta-value">{{ $value($animal->purpose) }}</span></td>
                <td><span class="meta-key">Valuation</span><span class="meta-value">{{ $animal->valuation_price !== null ? 'KES ' . number_format((float) $animal->valuation_price, 2) : 'Not recorded' }}</span></td>
            </tr>
            <tr>
                <td><span class="meta-key">Breeding Position</span><span class="meta-value">{{ $animal->is_breeder ? 'Retained for breeding' : 'Not retained for breeding' }}</span></td>
                <td><span class="meta-key">Sale Position</span><span class="meta-value">{{ $animal->sale_ready ? 'Ready for sale' : 'Not marked sale ready' }}</span></td>
                <td><span class="meta-key">Owner / Breeder Record</span><span class="meta-value">{{ $farmLegalName }}</span></td>
            </tr>
        </table>

        <div class="section-title">Pedigree Chart · Four Grandparents to Selected Animal</div>

        <div class="pedigree-stage">
            <div class="pedigree-connectors" aria-hidden="true">
                {{-- Sire lineage --}}
                <span class="pedigree-connector pedigree-connector-h line-ss-h"></span>
                <span class="pedigree-connector pedigree-connector-v line-ss-v"></span>
                <span class="pedigree-connector pedigree-connector-h line-sd-h"></span>
                <span class="pedigree-connector pedigree-connector-v line-sd-v"></span>
                <span class="pedigree-connector pedigree-connector-h line-sire-in"></span>

                {{-- Dam lineage --}}
                <span class="pedigree-connector pedigree-connector-h line-ds-h"></span>
                <span class="pedigree-connector pedigree-connector-v line-ds-v"></span>
                <span class="pedigree-connector pedigree-connector-h line-dd-h"></span>
                <span class="pedigree-connector pedigree-connector-v line-dd-v"></span>
                <span class="pedigree-connector pedigree-connector-h line-dam-in"></span>

                {{-- Parent pair to selected animal --}}
                <span class="pedigree-connector pedigree-connector-h line-sire-out"></span>
                <span class="pedigree-connector pedigree-connector-v line-sire-down"></span>
                <span class="pedigree-connector pedigree-connector-h line-dam-out"></span>
                <span class="pedigree-connector pedigree-connector-v line-dam-up"></span>
                <span class="pedigree-connector pedigree-connector-h line-subject-in"></span>
            </div>

            @php($node = $pedigreeNode($sireSire))
            <div class="pedigree-node gss"><div class="node-role">Sire's Sire</div><div class="node-tag">{{ $node['tag'] }}</div><div class="node-meta">{{ $node['meta'] }}</div><div class="node-status">{{ $node['status'] }}</div></div>

            @php($node = $pedigreeNode($sireDam))
            <div class="pedigree-node gsd"><div class="node-role">Sire's Dam</div><div class="node-tag">{{ $node['tag'] }}</div><div class="node-meta">{{ $node['meta'] }}</div><div class="node-status">{{ $node['status'] }}</div></div>

            @php($node = $pedigreeNode($damSire))
            <div class="pedigree-node gds"><div class="node-role">Dam's Sire</div><div class="node-tag">{{ $node['tag'] }}</div><div class="node-meta">{{ $node['meta'] }}</div><div class="node-status">{{ $node['status'] }}</div></div>

            @php($node = $pedigreeNode($damDam))
            <div class="pedigree-node gdd"><div class="node-role">Dam's Dam</div><div class="node-tag">{{ $node['tag'] }}</div><div class="node-meta">{{ $node['meta'] }}</div><div class="node-status">{{ $node['status'] }}</div></div>

            @php($node = $pedigreeNode($sire))
            <div class="pedigree-node pedigree-node-parent sire"><div class="node-role">Sire</div><div class="node-tag">{{ $node['tag'] }}</div><div class="node-meta">{{ $node['meta'] }}</div><div class="node-status">{{ $node['status'] }}</div></div>

            @php($node = $pedigreeNode($dam))
            <div class="pedigree-node pedigree-node-parent dam"><div class="node-role">Dam</div><div class="node-tag">{{ $node['tag'] }}</div><div class="node-meta">{{ $node['meta'] }}</div><div class="node-status">{{ $node['status'] }}</div></div>

            <div class="pedigree-node pedigree-node-subject subject"><div class="node-role">Selected Animal</div><div class="node-tag">{{ $animal->tag_number }}</div><div class="node-meta">{{ $breed }} · {{ $value($animal->sex) }} · {{ $value($animal->status) }}</div><div class="node-status">Purity: {{ $purity }}</div></div>
        </div>

        <div class="breeder-note">
            <strong>Breeding intelligence:</strong>
            @if ($animal->is_foundation_animal)
                Approved foundation stock; purity is recorded as 100.00%.
            @elseif ($animal->breed_purity_percent !== null)
                Purity is available from the recorded pedigree or verified pathway.
            @else
                Purity remains pending until parentage or verified purity evidence is recorded.
            @endif
            {{ $animal->is_breeder ? ' The animal is retained in the breeding programme.' : ' The animal is not currently retained in the breeding programme.' }}
            @if ($weight?->trend === 'losing')
                Latest recorded weight trend is declining; review nutrition and health before movement, breeding, sale, or valuation.
            @endif
        </div>

        <div class="footer-wrap">
            <div class="footer-rule-primary"></div>
            <div class="footer-rule-accent"></div>
            <table class="footer-table">
                <tr>
                    <td class="footer-brand-cell">
                        @if ($logo)
                            <img src="{{ $logo }}" class="footer-logo" alt="{{ $farmName }} logo">
                        @endif
                        <span class="footer-brand-text">
                            <span class="footer-brand-name">{{ $farmName }}</span><br>
                            <span class="footer-brand-legal">{{ $farmLegalName }}</span>
                        </span>
                    </td>
                    <td class="footer-center-cell">
                        <div class="footer-doc-label">Pedigree &amp; Animal Profile</div>
                        <div class="footer-doc-sub">Issued {{ $generatedAt->format('d M Y, H:i') }} EAT</div>
                    </td>
                    <td class="footer-right-cell">
                        <div class="footer-tag">{{ strtoupper($animal->tag_number) }}</div>
                        <div class="footer-page">Page 1 of 2</div>
                    </td>
                </tr>
                <tr class="footer-meta-row">
                    <td colspan="3" class="footer-meta">{{ $farmAddress }} · {{ $farmPhone }} · {{ $farmEmail }} · Prepared by {{ $generatedByName }} ({{ $generatedByRole }})</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- PAGE 2: Health, clinical, treatment and laboratory history --}}
    <div class="page page-break">
        <div class="header-shell">
            <table class="header-table">
                <tr>
                    <td class="logo-cell">
                        @if ($logo)
                            <img src="{{ $logo }}" class="logo" alt="{{ $farmName }} logo">
                        @endif
                    </td>
                    <td class="header-center">
                        <div class="farm-name">{{ $farmName }}</div>
                        <div class="farm-legal-name">{{ strtoupper($animal->tag_number) }} · Health, Clinical & Laboratory History</div>
                        <div class="farm-tagline">{{ $farmTagline }}</div>
                    </td>
                    <td class="header-right">
                        <table class="contact-table">
                            <tr><td class="contact-label">Location</td><td class="contact-value">{{ $farmCounty }}</td></tr>
                            <tr><td class="contact-label">Telephone</td><td class="contact-value">{{ $farmPhone }}</td></tr>
                            <tr><td class="contact-label">Email</td><td class="contact-value">{{ $farmEmail }}</td></tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        <div class="title-ribbon">{{ strtoupper($breed) }} · Animal Health & Performance Record</div>
        <div class="document-reference">Linked health, clinical and laboratory records as at {{ $generatedAt->format('d M Y, H:i') }} EAT · Reference {{ strtoupper($animal->tag_number) }}</div>

        <div class="section-title">Vaccination, Deworming & Health Administration History</div>
        <table class="history-table">
            <thead>
                <tr>
                    <th width="12%">Type</th><th width="29%">Product</th><th width="14%">Administered</th><th width="14%">Next Due</th><th width="15%">Dosage</th><th width="16%">Officer</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($healthRecords as $entry)
                    <tr>
                        <td>{{ $healthType($entry) }}</td>
                        <td>{{ $entry->product?->name ?? 'Not recorded' }}</td>
                        <td>{{ $entry->administered_at?->format('d M Y') ?? '—' }}</td>
                        <td>{{ $entry->next_due_date?->format('d M Y') ?? '—' }}</td>
                        <td>{{ $entry->dosage_per_animal ?? '—' }} {{ $entry->product?->dosage_unit ?? '' }}</td>
                        <td>{{ $entry->administered_by ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">No linked health administration records have been recorded for this animal.</td></tr>
                @endforelse
            </tbody>
        </table>

        <table class="split-table"><tr>
            <td>
                <div class="section-title">Clinical Cases</div>
                <table class="history-table"><thead><tr><th width="27%">Case</th><th width="23%">Status</th><th width="18%">Date</th><th width="32%">Clinical Signs</th></tr></thead><tbody>
                    @forelse ($clinicalCases as $case)
                        <tr><td>{{ $case->case_number }}</td><td>{{ $case->status }}<br><span class="muted">{{ $case->severity }}</span></td><td>{{ $case->case_date?->format('d M Y') ?? '—' }}</td><td>{{ $case->clinical_signs ?? '—' }}</td></tr>
                    @empty
                        <tr><td colspan="4">No clinical cases recorded.</td></tr>
                    @endforelse
                </tbody></table>
            </td>
            <td>
                <div class="section-title">Treatment Records</div>
                <table class="history-table"><thead><tr><th width="40%">Medicine / Dosage</th><th width="18%">Given</th><th width="21%">Status</th><th width="21%">Follow-up</th></tr></thead><tbody>
                    @forelse ($treatments as $treatment)
                        <tr><td>{{ $treatment->medicine_name ?? '—' }}<br><span class="muted">{{ $treatment->dosage ?? '' }}</span></td><td>{{ $treatment->given_at?->format('d M Y') ?? '—' }}</td><td>{{ $treatment->status ?? '—' }}</td><td>{{ $treatment->follow_up_date?->format('d M Y') ?? '—' }}</td></tr>
                    @empty
                        <tr><td colspan="4">No treatment records recorded.</td></tr>
                    @endforelse
                </tbody></table>
            </td>
        </tr></table>

        <div class="section-title">Laboratory Requests & Results</div>
        <table class="history-table">
            <thead><tr><th width="18%">Request</th><th width="14%">Status</th><th width="18%">Clinic</th><th width="13%">Sample</th><th width="13%">Testing</th><th width="24%">Results / Recommendation</th></tr></thead>
            <tbody>
                @forelse ($labs as $lab)
                    <tr>
                        <td>{{ $lab->request_number }}</td><td>{{ $lab->status }}</td><td>{{ $lab->clinic_display_name }}</td><td>{{ $lab->sample_collected_at?->format('d M Y') ?? '—' }}</td><td>{{ $lab->testing_date?->format('d M Y') ?? '—' }}</td>
                        <td>{{ $lab->results ?? 'Pending' }}@if ($lab->recommended_medication)<br><strong>Rx:</strong> {{ $lab->recommended_medication }}@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="6">No laboratory requests recorded.</td></tr>
                @endforelse
            </tbody>
        </table>

        <table class="split-table"><tr>
            <td>
                <div class="section-title">Management Intelligence</div>
                <div class="summary-box">
                    <strong>Health record position:</strong> {{ $animal->healthAdministrations->count() }} administration record(s), {{ $animal->clinicalCases->count() }} clinical case(s), {{ $animal->treatmentRecords->count() }} treatment record(s), and {{ $animal->labRequests->count() }} laboratory request(s).
                    <br><br>
                    <strong>Attention points:</strong> {{ $openCases }} unresolved clinical case(s) and {{ $pendingLabs }} pending laboratory request(s). Review due dates, unresolved cases, and pending results before breeding, sale, movement, or valuation decisions.
                </div>
            </td>
            <td>
                <div class="section-title">Profile Verification</div>
                <div class="verification-box">
                    @if (! empty($qrImage))
                        <img src="{{ $qrImage }}" class="verification-qr" alt="QR verification">
                    @endif
                    <div class="verification-title">System Verification</div>
                    <div class="verification-copy">
                        Profile: {{ $animal->tag_number }}<br>
                        {{ $verificationText }}<br>
                        Issued by {{ $generatedByName }} ({{ $generatedByRole }})<br>
                        {{ $generatedAt->format('d M Y, H:i') }} EAT
                    </div>
                </div>
            </td>
        </tr></table>

        <div class="footer-wrap">
            <div class="footer-rule-primary"></div>
            <div class="footer-rule-accent"></div>
            <table class="footer-table">
                <tr>
                    <td class="footer-brand-cell">
                        @if ($logo)
                            <img src="{{ $logo }}" class="footer-logo" alt="{{ $farmName }} logo">
                        @endif
                        <span class="footer-brand-text">
                            <span class="footer-brand-name">{{ $farmName }}</span><br>
                            <span class="footer-brand-legal">{{ $farmLegalName }}</span>
                        </span>
                    </td>
                    <td class="footer-center-cell">
                        <div class="footer-doc-label">Health &amp; Performance Record</div>
                        <div class="footer-doc-sub">Issued {{ $generatedAt->format('d M Y, H:i') }} EAT</div>
                    </td>
                    <td class="footer-right-cell">
                        <div class="footer-tag">{{ strtoupper($animal->tag_number) }}</div>
                        <div class="footer-page">Page 2 of 2</div>
                    </td>
                </tr>
                <tr class="footer-meta-row">
                    <td colspan="3" class="footer-meta">{{ $farmAddress }} · {{ $farmPhone }} · {{ $farmEmail }} · Dynamic farm details from Settings</td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
