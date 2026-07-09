@php
    $formatDate = static function ($value, string $format = 'd M Y'): string {
        return blank($value) ? '—' : \Carbon\Carbon::parse($value)->format($format);
    };

    $ageDisplay = '—';
    if (filled($animal->date_of_birth)) {
        $dob = \Carbon\Carbon::parse($animal->date_of_birth);
        $ageDisplay = $dob->isFuture()
            ? 'Invalid DOB'
            : ($animal->date_of_birth_is_estimated ? 'Approx. ' : '')
                . $dob->diffForHumans($generatedAt, [
                    'parts' => 2,
                    'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE,
                ]);
    }

    $purityPercent = $animal->breed_purity_percent !== null
        ? (float) $animal->breed_purity_percent
        : null;

    $purityLabel = match ($animal->purity_status) {
        'foundation' => 'Foundation Stock',
        'calculated' => 'Pedigree Calculated',
        'dna_verified' => 'DNA Verified',
        'manual_verified' => 'Manual Verified',
        default => 'Pending Parentage',
    };

    $weight = $animal->latestWeight;

    $node = static function ($record, string $role): array {
        return [
            'role' => $role,
            'tag' => $record?->tag_number ?? 'Not recorded',
            'breed' => $record?->breed?->breed_name ?? '—',
            'sex' => $record?->sex ?? '—',
            'purity' => $record && $record->breed_purity_percent !== null
                ? number_format((float) $record->breed_purity_percent, 2) . '%'
                : '—',
        ];
    };

    $paternalSire = $node($animal->sire?->sire, 'Paternal grandsire');
    $paternalDam = $node($animal->sire?->dam, 'Paternal granddam');
    $maternalSire = $node($animal->dam?->sire, 'Maternal grandsire');
    $maternalDam = $node($animal->dam?->dam, 'Maternal granddam');
    $sire = $node($animal->sire, 'Sire / Father');
    $dam = $node($animal->dam, 'Dam / Mother');

    $healthProductTypes = $healthAdministrations
        ->map(fn ($administration) => strtolower((string) ($administration->product?->type ?? 'health')))
        ->countBy();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ strtoupper($animal->tag_number) }} Pedigree Profile</title>
    <style>
        @page { margin: 18px 24px; }
        * { box-sizing: border-box; }
        body { margin:0; font-family: Helvetica, Arial, sans-serif; font-size:8px; color:#202a23; }
        .page { position:relative; min-height:1080px; page-break-after:always; }
        .page:last-child { page-break-after:auto; }
        .header { padding-bottom:8px; border-bottom:2px solid {{ $primaryColor }}; }
        .header-table, .footer-table, .identity-table, .two-col, .health-table, .mini-table { width:100%; border-collapse:collapse; }
        .header-logo { width:95px; vertical-align:middle; }
        .logo { max-width:82px; max-height:45px; object-fit:contain; }
        .header-centre { text-align:center; vertical-align:middle; }
        .farm-name { color:{{ $primaryColor }}; font-size:13px; font-weight:800; }
        .farm-tagline { margin-top:2px; color:#69756d; font-size:7.5px; }
        .header-right { width:165px; text-align:right; color:#5d6a61; font-size:7.5px; line-height:1.45; vertical-align:middle; }
        .certificate-breed { margin:12px 0 2px; color:#111827; font-size:20px; line-height:1; font-weight:900; letter-spacing:.06em; text-align:center; }
        .certificate-title { color:{{ $primaryColor }}; text-align:center; font-size:9px; font-weight:800; letter-spacing:.14em; text-transform:uppercase; }
        .certificate-sub { margin-top:4px; text-align:center; color:#6b7280; font-size:7px; }
        .band-title { margin-top:10px; padding:6px 8px; background:{{ $primaryColor }}; color:#fff; font-size:8px; font-weight:800; letter-spacing:.05em; text-transform:uppercase; }
        .identity-table td { border:1px solid #dfe5df; padding:5px 6px; vertical-align:top; }
        .identity-table .label { width:24%; color:#647168; font-size:6.8px; font-weight:bold; text-transform:uppercase; letter-spacing:.05em; }
        .identity-table .value { font-size:8px; font-weight:bold; color:#253129; }
        .identity-table .profile-cell { width:34%; }
        .purity-panel { padding:7px; border:1px solid #cfe2d0; background:#f6fff6; }
        .purity-top { width:100%; border-collapse:collapse; }
        .purity-value { color:{{ $primaryColor }}; font-size:22px; font-weight:900; line-height:1; }
        .purity-label { text-align:right; color:#45614b; font-size:7px; font-weight:800; text-transform:uppercase; }
        .progress { margin-top:6px; height:6px; background:#dcebdd; }
        .progress div { height:6px; background:{{ $primaryColor }}; }
        .purity-meta { margin-top:6px; color:#546257; font-size:7px; line-height:1.45; }
        .qr-cell { text-align:center; padding-top:8px; }
        .qr-cell img { width:82px; height:82px; border:1px solid #b7c9b8; padding:3px; }
        .qr-caption { margin-top:3px; color:#6b7280; font-size:6.5px; }
        .pedigree-area { position:relative; height:348px; margin-top:8px; border:1px solid #d9e4da; background:#fcfefc; overflow:hidden; }
        .pedigree-svg { position:absolute; inset:0; width:100%; height:100%; }
        .pedigree-line { fill:none; stroke:#7ca987; stroke-width:1.5; stroke-linecap:round; stroke-linejoin:round; }
        .ped-node { position:absolute; width:18%; min-height:56px; padding:5px; border:1px solid #d7e3d8; background:#fff; }
        .ped-node .role { color:#718073; font-size:5.8px; font-weight:bold; text-transform:uppercase; letter-spacing:.05em; }
        .ped-node .tag { margin-top:3px; color:#183823; font-size:7px; font-weight:bold; }
        .ped-node .meta { margin-top:2px; color:#69756d; font-size:6px; line-height:1.2; }
        .ped-node .purity { margin-top:2px; color:{{ $primaryColor }}; font-size:6.4px; font-weight:bold; }
        .n-g1 { left:1%; top:5%; } .n-g2 { left:25.5%; top:5%; } .n-g3 { left:50.5%; top:5%; } .n-g4 { left:75.5%; top:5%; }
        .n-sire { left:15%; top:37%; width:20%; border-color:#a8c8ae; } .n-dam { left:65%; top:37%; width:20%; border-color:#a8c8ae; }
        .n-animal { left:40%; top:70%; width:20%; border:2px solid {{ $primaryColor }}; background:#f6fff6; }
        .n-animal .tag { color:{{ $primaryColor }}; font-size:8px; }
        .insight { margin-top:8px; padding:7px 8px; border-left:4px solid {{ $accentColor }}; background:#fffaf0; color:#5b4a22; font-size:7px; line-height:1.45; }
        .footer { position:absolute; left:0; right:0; bottom:0; border-top:1px solid #d8ded9; padding-top:5px; color:#69756d; font-size:6.5px; }
        .footer-centre { text-align:center; } .footer-right { text-align:right; }
        .health-table th, .mini-table th { background:{{ $primaryColor }}; color:#fff; padding:5px; border:1px solid {{ $primaryColor }}; text-align:left; font-size:6.2px; text-transform:uppercase; }
        .health-table td, .mini-table td { border:1px solid #e1e6e1; padding:5px; vertical-align:top; font-size:6.8px; line-height:1.3; }
        .health-table tr:nth-child(even) td, .mini-table tr:nth-child(even) td { background:#fbfcfb; }
        .pill { display:inline-block; padding:2px 4px; background:{{ $secondaryColor }}; color:#fff; font-size:5.7px; font-weight:bold; }
        .two-col td { width:50%; vertical-align:top; }
        .two-col td:first-child { padding-right:5px; } .two-col td:last-child { padding-left:5px; }
        .record-box { min-height:75px; padding:6px; border:1px solid #e0e6e0; background:#fff; }
        .record-title { color:#173a25; font-size:7px; font-weight:bold; }
        .record-meta { margin-top:3px; color:#647168; font-size:6.2px; }
        .record-note { margin-top:4px; color:#404a43; font-size:6.4px; line-height:1.3; }
        .empty { padding:7px; border:1px dashed #cdd6ce; color:#6b7280; background:#fbfdfb; font-size:6.8px; }
        .page-two-title { margin:9px 0 5px; color:{{ $primaryColor }}; font-size:10px; font-weight:bold; text-transform:uppercase; letter-spacing:.05em; }
        .small-note { margin-top:4px; color:#6b7280; font-size:6px; }
        .verification { margin-top:9px; padding:7px; border:1px solid #d6e4d7; background:#f8fcf8; color:#445347; font-size:6.6px; line-height:1.4; }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <table class="header-table"><tr>
                <td class="header-logo">@if($logoBase64)<img class="logo" src="{{ $logoBase64 }}" alt="{{ $farmName }}">@else<strong style="color:{{ $primaryColor }};font-size:12px;">PF</strong>@endif</td>
                <td class="header-centre"><div class="farm-name">{{ $farmName }}</div><div class="farm-tagline">{{ $farmTagline }} · {{ $farmCounty }}</div></td>
                <td class="header-right">{{ $farmEmail }}<br>{{ $farmPhone }}<br>{{ $farmCounty }}</td>
            </tr></table>
        </div>

        <div class="certificate-breed">{{ strtoupper($animal->breed?->breed_name ?? $animal->species ?? 'ANIMAL') }}</div>
        <div class="certificate-title">Pedigree & Animal Profile Certificate</div>
        <div class="certificate-sub">Live Penzi Farm ERP record · Tag {{ $animal->tag_number }}</div>

        <div class="band-title">Animal Registration & Performance Summary</div>
        <table class="identity-table">
            <tr>
                <td class="label">Name / Farm Tag</td><td class="value">{{ $animal->tag_number }}</td>
                <td class="label">Sex</td><td class="value">{{ $animal->sex ?? '—' }}</td>
                <td class="profile-cell" rowspan="4">
                    <div class="purity-panel">
                        <table class="purity-top"><tr><td class="purity-value">{{ $purityPercent !== null ? number_format($purityPercent,2).'%' : '—' }}</td><td class="purity-label">{{ $purityLabel }}<br>{{ $animal->purityBreed?->breed_name ?? $animal->breed?->breed_name ?? '—' }}</td></tr></table>
                        <div class="progress"><div style="width:{{ $purityPercent !== null ? max(0,min(100,$purityPercent)) : 0 }}%;"></div></div>
                        <div class="purity-meta"><b>Foundation:</b> {{ $animal->is_foundation_animal ? 'Approved' : 'No' }}<br><b>Verified:</b> {{ $formatDate($animal->purity_verified_at) }}<br><b>Evidence:</b> {{ $animal->purity_notes ?: 'System pedigree calculation' }}</div>
                    </div>
                    <div class="qr-cell">@if($qrImage)<img src="{{ $qrImage }}" alt="QR">@endif<div class="qr-caption">Scan while authenticated to open the live profile</div></div>
                </td>
            </tr>
            <tr><td class="label">Breed / Species</td><td class="value">{{ $animal->breed?->breed_name ?? '—' }} · {{ $animal->species ?? '—' }}</td><td class="label">Date of Birth</td><td class="value">{{ $formatDate($animal->date_of_birth) }}{{ $animal->date_of_birth_is_estimated ? ' (estimated)' : '' }}</td></tr>
            <tr><td class="label">Age</td><td class="value">{{ $ageDisplay }}</td><td class="label">Status</td><td class="value">{{ $animal->status ?? '—' }} · {{ $animal->purpose ?? '—' }}</td></tr>
            <tr><td class="label">Location</td><td class="value">{{ $animal->location?->display_name ?? $animal->location?->name ?? '—' }}</td><td class="label">Latest Weight</td><td class="value">{{ $weight ? number_format((float)$weight->weight_kg,2).' KG · '.($weight->trend ?? 'baseline') : 'Not recorded' }}</td></tr>
            <tr><td class="label">Source</td><td class="value">{{ $animal->source ?? '—' }}{{ $animal->source === 'Purchased' && $animal->bought_from ? ' · '.$animal->bought_from : '' }}</td><td class="label">Valuation</td><td class="value">{{ $animal->valuation_price !== null ? 'KES '.number_format((float)$animal->valuation_price,2) : '—' }}</td><td class="profile-cell"><b>Breeder:</b> {{ $animal->is_breeder ? 'Yes' : 'No' }} &nbsp; <b>Sale Ready:</b> {{ $animal->sale_ready ? 'Yes' : 'No' }}</td></tr>
        </table>

        <div class="band-title">Heredity / Pedigree Diagram</div>
        <div class="pedigree-area">
            <svg class="pedigree-svg" viewBox="0 0 1000 348" preserveAspectRatio="none" aria-hidden="true">
                <path class="pedigree-line" d="M100 82 L100 125 L250 125 L250 168"></path><path class="pedigree-line" d="M345 82 L345 125 L250 125"></path>
                <path class="pedigree-line" d="M605 82 L605 125 L750 125 L750 168"></path><path class="pedigree-line" d="M850 82 L850 125 L750 125"></path>
                <path class="pedigree-line" d="M250 224 L250 270 L500 270 L500 305"></path><path class="pedigree-line" d="M750 224 L750 270 L500 270"></path>
            </svg>
            @foreach ([['c'=>'n-g1','n'=>$paternalSire],['c'=>'n-g2','n'=>$paternalDam],['c'=>'n-g3','n'=>$maternalSire],['c'=>'n-g4','n'=>$maternalDam],['c'=>'n-sire','n'=>$sire],['c'=>'n-dam','n'=>$dam]] as $item)
                <div class="ped-node {{ $item['c'] }}"><div class="role">{{ $item['n']['role'] }}</div><div class="tag">{{ $item['n']['tag'] }}</div><div class="meta">{{ $item['n']['breed'] }} · {{ $item['n']['sex'] }}</div><div class="purity">{{ $item['n']['purity'] }}</div></div>
            @endforeach
            <div class="ped-node n-animal"><div class="role">Subject animal</div><div class="tag">{{ $animal->tag_number }}</div><div class="meta">{{ $animal->breed?->breed_name ?? '—' }} · {{ $animal->sex ?? '—' }}</div><div class="purity">{{ $purityPercent !== null ? number_format($purityPercent,2).'%' : 'Pending' }}</div></div>
        </div>

        <div class="insight"><b>Breeding insight:</b> {{ $animal->sire && $animal->dam ? 'Both parents are recorded. Purity and pedigree status are based on the live sire and dam lineage.' : 'One or both parents are not recorded. Add verified sire and dam data to improve pedigree completeness and automatic purity confidence.' }} Current purity status: <b>{{ $purityLabel }}</b>.</div>

        <div class="footer"><table class="footer-table"><tr><td>Generated {{ $generatedAt->format('d M Y, H:i') }} EAT · {{ $generatedBy?->name ?? 'System' }} ({{ $generatedByRole }})</td><td class="footer-centre">{{ $animal->tag_number }} · Pedigree & Animal Profile</td><td class="footer-right">Page 1 of 2</td></tr></table></div>
    </div>

    <div class="page">
        <div class="header"><table class="header-table"><tr><td class="header-logo">@if($logoBase64)<img class="logo" src="{{ $logoBase64 }}" alt="{{ $farmName }}">@else<strong style="color:{{ $primaryColor }};font-size:12px;">PF</strong>@endif</td><td class="header-centre"><div class="farm-name">{{ $farmName }}</div><div class="farm-tagline">{{ strtoupper($animal->breed?->breed_name ?? $animal->species ?? 'Animal') }} · {{ $animal->tag_number }} · Health & Operational Record</div></td><td class="header-right">{{ $farmEmail }}<br>{{ $farmPhone }}</td></tr></table></div>

        <div class="page-two-title">Vaccination, Deworming & Health Administration History</div>
        @if($healthAdministrations->isEmpty())
            <div class="empty">No linked Health Administration records exist for this animal.</div>
        @else
            <table class="health-table"><thead><tr><th width="12%">Type</th><th width="24%">Product</th><th width="12%">Given</th><th width="13%">Next Due</th><th width="18%">Dosage / Total</th><th width="12%">Officer</th><th width="9%">Notes</th></tr></thead><tbody>
                @foreach($healthAdministrations as $administration)
                    @php $type = str($administration->product?->type ?? 'Health')->replace('_',' ')->title()->toString(); @endphp
                    <tr><td><span class="pill">{{ $type }}</span></td><td><b>{{ $administration->product?->name ?? 'Product not recorded' }}</b></td><td>{{ $formatDate($administration->administered_at) }}</td><td>{{ $administration->next_due_date ? $formatDate($administration->next_due_date) : 'Not scheduled' }}</td><td>{{ $administration->dosage_per_animal !== null ? rtrim(rtrim(number_format((float)$administration->dosage_per_animal,2),'0'),'.').' / animal' : '—' }}{{ $administration->total_quantity_used !== null ? ' · '.rtrim(rtrim(number_format((float)$administration->total_quantity_used,2),'0'),'.').' total' : '' }}</td><td>{{ $administration->administered_by ?: '—' }}</td><td>{{ $administration->notes ?: '—' }}</td></tr>
                @endforeach
            </tbody></table>
            @if($healthAdministrationCount > $healthAdministrations->count())<div class="small-note">Showing the latest {{ $healthAdministrations->count() }} of {{ $healthAdministrationCount }} linked health records.</div>@endif
        @endif

        <table class="two-col" style="margin-top:10px;"><tr>
            <td><div class="page-two-title">Clinical Cases ({{ $clinicalCaseCount }})</div>@forelse($clinicalCases as $case)<div class="record-box"><div class="record-title">{{ $case->case_number }} · {{ $case->status }}</div><div class="record-meta">{{ $formatDate($case->case_date,'d M Y, H:i') }} · {{ $case->severity }} severity · {{ $case->attending_officer ?: 'Officer not recorded' }}</div><div class="record-note">{{ $case->clinical_signs ?: $case->diagnosis ?: 'No clinical signs or diagnosis recorded.' }}</div></div>@empty<div class="empty">No clinical cases recorded.</div>@endforelse</td>
            <td><div class="page-two-title">Treatment Records ({{ $treatmentCount }})</div>@forelse($treatments as $treatment)<div class="record-box"><div class="record-title">{{ $treatment->medicine_name ?: 'Medicine not recorded' }} · {{ $treatment->status }}</div><div class="record-meta">{{ $formatDate($treatment->given_at,'d M Y, H:i') }} · {{ $treatment->dosage ?: 'Dosage not recorded' }}{{ $treatment->method ? ' · '.$treatment->method : '' }}</div><div class="record-note">{{ $treatment->notes ?: ($treatment->clinicalCase?->case_number ? 'Linked case: '.$treatment->clinicalCase->case_number : 'No treatment notes recorded.') }}</div></div>@empty<div class="empty">No treatment records recorded.</div>@endforelse</td>
        </tr></table>

        <div class="page-two-title">Laboratory Requests & Results ({{ $labRequestCount }})</div>
        @if($labRequests->isEmpty())
            <div class="empty">No laboratory requests recorded.</div>
        @else
            <table class="mini-table"><thead><tr><th width="17%">Request</th><th width="16%">Clinic</th><th width="15%">Dates</th><th width="20%">Requested Tests</th><th width="15%">Results</th><th width="17%">Recommended Action</th></tr></thead><tbody>
                @foreach($labRequests as $lab)
                    <tr><td><b>{{ $lab->request_number }}</b><br><span class="pill">{{ $lab->status }}</span></td><td>{{ $lab->clinic_display_name }}</td><td>Requested: {{ $formatDate($lab->requested_at) }}<br>Sample: {{ $lab->sample_collected_at ? $formatDate($lab->sample_collected_at) : '—' }}<br>Result: {{ $lab->resulted_at ? $formatDate($lab->resulted_at) : '—' }}</td><td>{{ $lab->requested_tests_text ?: '—' }}</td><td>{{ $lab->results ?: 'Pending / not recorded' }}</td><td>{{ $lab->recommended_medication ?: '—' }}</td></tr>
                @endforeach
            </tbody></table>
            @if($labRequestCount > $labRequests->count())<div class="small-note">Showing the latest {{ $labRequests->count() }} of {{ $labRequestCount }} laboratory requests.</div>@endif
        @endif

        <div class="verification"><b>Verification:</b> This two-page profile is generated from the live Penzi Farm ERP record. The QR code on page 1 opens the current profile for an authenticated user. It is a management report and does not replace an external stud-book registration certificate.</div>
        <div class="footer"><table class="footer-table"><tr><td>Generated {{ $generatedAt->format('d M Y, H:i') }} EAT · {{ $generatedBy?->name ?? 'System' }} ({{ $generatedByRole }})</td><td class="footer-centre">{{ $farmLegalName }} · {{ $animal->tag_number }}</td><td class="footer-right">Page 2 of 2</td></tr></table></div>
    </div>
</body>
</html>
