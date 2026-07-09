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
                . $dob->diffForHumans(now(), [
                    'parts' => 2,
                    'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE,
                ]);
    }

    $purityPercent = $animal->breed_purity_percent !== null
        ? (float) $animal->breed_purity_percent
        : null;

    $purityLabel = match ($animal->purity_status) {
        'foundation' => 'Foundation stock',
        'calculated' => 'Pedigree calculated',
        'dna_verified' => 'DNA verified',
        'manual_verified' => 'Manual verified',
        default => 'Pending parentage',
    };

    $weight = $animal->latestWeight;
    $weightTrend = $weight?->trend ?? 'none';
    $weightTrendLabel = match ($weightTrend) {
        'gaining' => 'Gaining',
        'losing' => 'Losing',
        'stable' => 'Stable',
        'first' => 'Baseline',
        default => 'No weight',
    };

    $healthAdministrations = $animal->healthAdministrations;
    $clinicalCases = $animal->clinicalCases;
    $treatments = $animal->treatmentRecords;
    $labRequests = $animal->labRequests;

    $openCases = $clinicalCases->filter(
        fn ($case) => ! in_array($case->status, ['Resolved', 'Closed'], true)
    )->count();

    $vaccinations = $healthAdministrations->filter(
        fn ($administration) => in_array(
            strtolower((string) ($administration->product?->type ?? '')),
            ['vaccine', 'vaccination'],
            true
        )
    )->count();

    $dewormings = $healthAdministrations->filter(
        fn ($administration) => in_array(
            strtolower((string) ($administration->product?->type ?? '')),
            ['dewormer', 'deworming'],
            true
        )
    )->count();

    $node = static function ($record, string $role) use ($purityPercent): array {
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
@endphp

<x-filament-panels::page>
    <style>
        .ap-shell { --p: {{ $primaryColor }}; --s: {{ $secondaryColor }}; --a: {{ $accentColor }}; --d: {{ $dangerColor }}; color:#1f2937; }
        .ap-shell * { box-sizing:border-box; }
        .ap-card { border:1px solid #e4e7eb; border-radius:14px; background:#fff; overflow:hidden; }
        .ap-hero { padding:18px; border:1px solid #d8eadb; border-radius:16px; background:linear-gradient(135deg,#f4fbf4,#fff 55%,#edf7ee); }
        .ap-brand { display:flex; justify-content:space-between; align-items:center; gap:14px; padding-bottom:12px; border-bottom:1px solid #dce7de; }
        .ap-logo { width:44px; height:44px; border:1px solid #d4e5d6; border-radius:11px; background:#fff; display:flex; align-items:center; justify-content:center; overflow:hidden; }
        .ap-logo img { width:38px; max-height:38px; object-fit:contain; }
        .ap-brand-left { display:flex; gap:10px; align-items:center; }
        .ap-farm { font-size:14px; font-weight:900; color:#143b23; }
        .ap-farm-sub { margin-top:2px; font-size:10px; color:#6b7280; }
        .ap-certificate { text-align:right; color:var(--p); font-size:10px; font-weight:900; letter-spacing:.09em; text-transform:uppercase; }
        .ap-certificate span { display:block; margin-top:3px; color:#6b7280; font-size:9px; font-weight:600; letter-spacing:0; text-transform:none; }
        .ap-main { display:flex; justify-content:space-between; gap:16px; align-items:end; padding-top:16px; }
        .ap-breed { color:var(--p); font-size:10px; font-weight:900; letter-spacing:.13em; text-transform:uppercase; }
        .ap-title { margin:4px 0; color:#17261b; font-size:31px; line-height:1; font-weight:950; letter-spacing:-.035em; }
        .ap-subtitle { color:#69756d; font-size:12px; }
        .ap-tag { min-width:160px; border:1px solid #d9e8db; border-radius:11px; padding:11px 13px; background:#fff; text-align:right; }
        .ap-tag span { display:block; color:#839087; font-size:8px; font-weight:900; letter-spacing:.11em; text-transform:uppercase; }
        .ap-tag strong { display:block; margin-top:4px; color:var(--p); font-size:16px; font-family:ui-monospace,SFMono-Regular,Menlo,monospace; }
        .ap-kpis { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:8px; margin-top:12px; }
        .ap-kpi { border:1px solid #e5ebe6; border-radius:10px; padding:10px; background:#fff; min-height:75px; }
        .ap-kpi small { color:#718073; font-size:8px; font-weight:900; letter-spacing:.09em; text-transform:uppercase; }
        .ap-kpi b { display:block; margin-top:6px; color:#1f2937; font-size:16px; line-height:1; }
        .ap-kpi span { display:block; margin-top:5px; color:#6b7280; font-size:9px; line-height:1.25; }
        .ap-section { margin-top:14px; }
        .ap-section-head { display:flex; justify-content:space-between; gap:12px; align-items:center; padding:10px 13px; border-bottom:1px solid #e5e7eb; background:#fbfdfb; }
        .ap-section-head h2 { margin:0; color:#173a25; font-size:11px; font-weight:950; letter-spacing:.055em; text-transform:uppercase; }
        .ap-section-head span { color:#758075; font-size:9px; }
        .ap-body { padding:13px; }
        .ap-grid-2 { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; }
        .ap-detail-table { width:100%; border-collapse:collapse; }
        .ap-detail-table td { padding:7px 0; border-bottom:1px solid #edf0ed; vertical-align:top; }
        .ap-detail-table tr:last-child td { border-bottom:0; }
        .ap-detail-table td:first-child { width:42%; color:#728075; font-size:9px; font-weight:900; letter-spacing:.05em; text-transform:uppercase; }
        .ap-detail-table td:last-child { color:#253129; font-size:11px; font-weight:700; }
        .ap-purity { border:1px solid #d7ead9; border-radius:11px; padding:11px; background:linear-gradient(135deg,#f4fff5,#fff); }
        .ap-purity-top { display:flex; justify-content:space-between; gap:10px; align-items:baseline; }
        .ap-purity-value { font-size:28px; line-height:1; font-weight:950; color:var(--p); }
        .ap-purity-label { text-align:right; font-size:9px; font-weight:900; color:#4b6652; text-transform:uppercase; letter-spacing:.07em; }
        .ap-progress { height:7px; border-radius:20px; overflow:hidden; background:#dceadd; margin-top:10px; }
        .ap-progress i { display:block; height:100%; border-radius:inherit; background:linear-gradient(90deg,var(--p),#22c55e); }
        .ap-purity-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:7px; margin-top:10px; }
        .ap-purity-grid div { padding:7px; border:1px solid #e5eee5; border-radius:8px; background:#fff; }
        .ap-purity-grid span { display:block; color:#768276; font-size:8px; font-weight:900; text-transform:uppercase; letter-spacing:.07em; }
        .ap-purity-grid b { display:block; margin-top:3px; color:#233127; font-size:10px; line-height:1.25; }
        .ap-pedigree-stage { position:relative; min-height:420px; overflow:hidden; border:1px solid #e5ece5; border-radius:12px; background:linear-gradient(180deg,#fbfefb,#fff); }
        .ap-pedigree-stage svg { position:absolute; inset:0; width:100%; height:100%; pointer-events:none; }
        .ap-pedigree-line { fill:none; stroke:#77a481; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
        .ap-pedigree-node { position:absolute; width:19%; min-height:78px; padding:8px; border:1px solid #d6e4d8; border-radius:9px; background:#fff; box-shadow:0 4px 10px rgba(18,76,39,.05); }
        .ap-pedigree-node .role { color:#718073; font-size:7px; font-weight:900; text-transform:uppercase; letter-spacing:.07em; }
        .ap-pedigree-node .tag { margin-top:4px; color:#183823; font-size:10px; font-weight:950; overflow-wrap:anywhere; }
        .ap-pedigree-node .meta { margin-top:3px; color:#647168; font-size:8px; line-height:1.25; }
        .ap-pedigree-node .purity { margin-top:4px; color:var(--p); font-size:8px; font-weight:900; }
        .ap-gs1 { left:1%; top:6%; } .ap-gs2 { left:25.5%; top:6%; } .ap-gs3 { left:50.5%; top:6%; } .ap-gs4 { left:75.5%; top:6%; }
        .ap-sire { left:15%; top:39%; width:20%; border-color:#99c3a1; } .ap-dam { left:65%; top:39%; width:20%; border-color:#99c3a1; }
        .ap-animal { left:40%; top:72%; width:20%; border:2px solid var(--p); background:#f7fff7; }
        .ap-animal .tag { color:var(--p); font-size:11px; }
        .ap-table-wrap { overflow-x:auto; }
        .ap-table { width:100%; min-width:860px; border-collapse:collapse; }
        .ap-table th { padding:8px; text-align:left; background:var(--p); color:#fff; font-size:8px; letter-spacing:.04em; text-transform:uppercase; }
        .ap-table td { padding:8px; border-bottom:1px solid #edf0ed; color:#344038; font-size:10px; vertical-align:top; }
        .ap-table tr:nth-child(even) td { background:#fbfcfb; }
        .ap-pill { display:inline-block; border-radius:999px; padding:3px 6px; color:#fff; background:var(--p); font-size:8px; font-weight:900; }
        .ap-empty { padding:12px; border:1px dashed #cbd5cb; border-radius:9px; color:#6b7280; font-size:10px; background:#fbfdfb; }
        .ap-record-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; }
        .ap-record { min-height:112px; padding:10px; border:1px solid #e5e9e6; border-radius:10px; background:#fff; }
        .ap-record h3 { margin:0; color:#173a25; font-size:10px; font-weight:950; text-transform:uppercase; letter-spacing:.04em; }
        .ap-record .count { float:right; color:var(--p); font-size:16px; line-height:1; }
        .ap-record-item { margin-top:8px; padding-top:8px; border-top:1px solid #edf0ed; }
        .ap-record-item b { color:#29362d; font-size:9px; }
        .ap-record-item p { margin:3px 0 0; color:#69756d; font-size:9px; line-height:1.35; }
        @media (max-width: 1024px) { .ap-kpis{grid-template-columns:repeat(3,minmax(0,1fr));}.ap-pedigree-stage{min-height:390px;}.ap-pedigree-node{width:22%;}.ap-gs1{left:1%;}.ap-gs2{left:25%;}.ap-gs3{left:51%;}.ap-gs4{left:76%;}.ap-sire{left:14%;}.ap-dam{left:64%;}.ap-animal{left:39%;} }
        @media (max-width: 768px) { .ap-main,.ap-brand{align-items:flex-start;flex-direction:column;}.ap-tag,.ap-certificate{text-align:left;}.ap-kpis,.ap-grid-2,.ap-record-grid{grid-template-columns:1fr;}.ap-pedigree-stage{overflow:auto;min-width:740px;}.ap-table-wrap{overflow-x:auto;} }
    </style>

    <div class="ap-shell">
        <section class="ap-hero">
            <div class="ap-brand">
                <div class="ap-brand-left">
                    <div class="ap-logo">
                        @if($logoUrl)<img src="{{ $logoUrl }}" alt="{{ $farmName }}">@else<span style="font-weight:950;color:var(--p);">PF</span>@endif
                    </div>
                    <div>
                        <div class="ap-farm">{{ $farmName }}</div>
                        <div class="ap-farm-sub">{{ $farmTagline }} · {{ $farmCounty }}</div>
                    </div>
                </div>
                <div class="ap-certificate">{{ strtoupper($animal->breed?->breed_name ?? $animal->species ?? 'Animal') }} pedigree record<span>Live ERP profile · {{ $farmPhone }}</span></div>
            </div>
            <div class="ap-main">
                <div>
                    <div class="ap-breed">{{ $animal->species ?? 'Livestock' }} · {{ $animal->sex ?? 'Sex not recorded' }}</div>
                    <div class="ap-title">{{ strtoupper($animal->breed?->breed_name ?? 'ANIMAL') }}</div>
                    <div class="ap-subtitle">Pedigree, purity, performance and animal health record</div>
                </div>
                <div class="ap-tag"><span>Penzi animal tag</span><strong>{{ $animal->tag_number }}</strong></div>
            </div>
            <div class="ap-kpis">
                <div class="ap-kpi"><small>Lifecycle</small><b>{{ $animal->status ?? '—' }}</b><span>{{ $animal->purpose ?? 'Purpose not recorded' }}</span></div>
                <div class="ap-kpi"><small>Purity</small><b style="color:var(--p);">{{ $purityPercent !== null ? number_format($purityPercent,2).'%' : 'Pending' }}</b><span>{{ $purityLabel }}</span></div>
                <div class="ap-kpi"><small>Latest Weight</small><b>{{ $weight ? number_format((float)$weight->weight_kg,2).' KG' : '—' }}</b><span>{{ $weightTrendLabel }}{{ $weight?->recorded_at ? ' · '.$formatDate($weight->recorded_at) : '' }}</span></div>
                <div class="ap-kpi"><small>Health History</small><b>{{ $healthAdministrations->count() }}</b><span>{{ $vaccinations }} vaccination · {{ $dewormings }} deworming</span></div>
                <div class="ap-kpi"><small>Clinical Watch</small><b style="color:{{ $openCases ? $dangerColor : $primaryColor }};">{{ $openCases }}</b><span>Open clinical case(s)</span></div>
            </div>
        </section>

        <section class="ap-card ap-section">
            <div class="ap-section-head"><h2>Identity & Management Record</h2><span>Current animal master data</span></div>
            <div class="ap-body ap-grid-2">
                <table class="ap-detail-table">
                    <tr><td>Tag Number</td><td>{{ $animal->tag_number }}</td></tr>
                    <tr><td>Breed</td><td>{{ $animal->breed?->breed_name ?? '—' }}</td></tr>
                    <tr><td>Species / Category</td><td>{{ $animal->species ?? '—' }}</td></tr>
                    <tr><td>Sex</td><td>{{ $animal->sex ?? '—' }}</td></tr>
                    <tr><td>Date of Birth</td><td>{{ $formatDate($animal->date_of_birth) }}{{ $animal->date_of_birth_is_estimated ? ' (estimated)' : '' }}</td></tr>
                    <tr><td>Current Age</td><td>{{ $ageDisplay }}</td></tr>
                </table>
                <table class="ap-detail-table">
                    <tr><td>Location</td><td>{{ $animal->location?->display_name ?? $animal->location?->name ?? '—' }}</td></tr>
                    <tr><td>Breeding Retention</td><td>{{ $animal->is_breeder ? 'Retained breeder' : 'No' }}</td></tr>
                    <tr><td>Sale Readiness</td><td>{{ $animal->sale_ready ? 'Ready for sale' : 'Not marked' }}</td></tr>
                    <tr><td>Valuation</td><td>{{ $animal->valuation_price !== null ? 'KES '.number_format((float)$animal->valuation_price,2) : '—' }}</td></tr>
                    <tr><td>Source</td><td>{{ $animal->source ?? '—' }}{{ $animal->source === 'Purchased' && $animal->bought_from ? ' · '.$animal->bought_from : '' }}</td></tr>
                    <tr><td>Notes</td><td>{{ $animal->notes ?: '—' }}</td></tr>
                </table>
            </div>
        </section>

        <section class="ap-card ap-section">
            <div class="ap-section-head"><h2>Heredity & Breed Purity</h2><span>Recorded parents, grandparents and inherited purity context</span></div>
            <div class="ap-body ap-grid-2">
                <div class="ap-purity">
                    <div class="ap-purity-top"><div class="ap-purity-value">{{ $purityPercent !== null ? number_format($purityPercent,2).'%' : '—' }}</div><div class="ap-purity-label">{{ $purityLabel }}</div></div>
                    <div class="ap-progress"><i style="width:{{ $purityPercent !== null ? max(0,min(100,$purityPercent)) : 0 }}%;"></i></div>
                    <div class="ap-purity-grid">
                        <div><span>Purity Breed</span><b>{{ $animal->purityBreed?->breed_name ?? $animal->breed?->breed_name ?? '—' }}</b></div>
                        <div><span>Foundation Flag</span><b>{{ $animal->is_foundation_animal ? 'Approved' : 'Not foundation' }}</b></div>
                        <div><span>Verified On</span><b>{{ $formatDate($animal->purity_verified_at) }}</b></div>
                        <div><span>Evidence</span><b>{{ $animal->purity_notes ?: 'Pedigree calculation' }}</b></div>
                    </div>
                </div>
                <div class="ap-empty"><strong style="color:var(--p);">How purity is used</strong><br>Purity follows the selected Animal Identity breed. Foundation animals are 100%. For offspring, the system computes the target-breed contribution from the recorded sire and dam; incomplete parentage remains Pending.</div>
            </div>
            <div class="ap-body" style="padding-top:0;">
                <div class="ap-pedigree-stage">
                    <svg viewBox="0 0 1000 420" preserveAspectRatio="none" aria-hidden="true">
                        <path class="ap-pedigree-line" d="M105 104 L105 146 L250 146 L250 196"></path>
                        <path class="ap-pedigree-line" d="M350 104 L350 146 L250 146"></path>
                        <path class="ap-pedigree-line" d="M605 104 L605 146 L750 146 L750 196"></path>
                        <path class="ap-pedigree-line" d="M850 104 L850 146 L750 146"></path>
                        <path class="ap-pedigree-line" d="M250 274 L250 318 L500 318 L500 350"></path>
                        <path class="ap-pedigree-line" d="M750 274 L750 318 L500 318"></path>
                    </svg>
                    @foreach ([['c'=>'ap-gs1','n'=>$paternalSire],['c'=>'ap-gs2','n'=>$paternalDam],['c'=>'ap-gs3','n'=>$maternalSire],['c'=>'ap-gs4','n'=>$maternalDam],['c'=>'ap-sire','n'=>$sire],['c'=>'ap-dam','n'=>$dam]] as $item)
                        <div class="ap-pedigree-node {{ $item['c'] }}"><div class="role">{{ $item['n']['role'] }}</div><div class="tag">{{ $item['n']['tag'] }}</div><div class="meta">{{ $item['n']['breed'] }} · {{ $item['n']['sex'] }}</div><div class="purity">{{ $item['n']['purity'] }}</div></div>
                    @endforeach
                    <div class="ap-pedigree-node ap-animal"><div class="role">Subject animal</div><div class="tag">{{ $animal->tag_number }}</div><div class="meta">{{ $animal->breed?->breed_name ?? '—' }} · {{ $animal->sex ?? '—' }}</div><div class="purity">{{ $purityPercent !== null ? number_format($purityPercent,2).'%' : 'Pending' }}</div></div>
                </div>
            </div>
        </section>

        <section class="ap-card ap-section">
            <div class="ap-section-head"><h2>Vaccination, Deworming & Health Administration</h2><span>{{ $healthAdministrations->count() }} linked record(s)</span></div>
            <div class="ap-body">
                @if($healthAdministrations->isEmpty())
                    <div class="ap-empty">No Health Administrations are linked yet. Edit the relevant health record, select this animal tag, and save to populate the profile.</div>
                @else
                    <div class="ap-table-wrap"><table class="ap-table"><thead><tr><th>Type</th><th>Product</th><th>Date Given</th><th>Next Due</th><th>Dosage / Total</th><th>Officer</th><th>Notes</th></tr></thead><tbody>
                        @foreach($healthAdministrations as $administration)
                            @php $type = str($administration->product?->type ?? 'Health')->replace('_',' ')->title()->toString(); @endphp
                            <tr><td><span class="ap-pill">{{ $type }}</span></td><td><strong>{{ $administration->product?->name ?? 'Product not recorded' }}</strong></td><td>{{ $formatDate($administration->administered_at) }}</td><td>{{ $administration->next_due_date ? $formatDate($administration->next_due_date) : 'Not scheduled' }}</td><td>{{ $administration->dosage_per_animal !== null ? rtrim(rtrim(number_format((float)$administration->dosage_per_animal,2),'0'),'.').' / animal' : '—' }}{{ $administration->total_quantity_used !== null ? ' · '.rtrim(rtrim(number_format((float)$administration->total_quantity_used,2),'0'),'.').' total' : '' }}</td><td>{{ $administration->administered_by ?: '—' }}</td><td>{{ $administration->notes ?: '—' }}</td></tr>
                        @endforeach
                    </tbody></table></div>
                @endif
            </div>
        </section>

        <section class="ap-card ap-section">
            <div class="ap-section-head"><h2>Clinical, Treatment & Laboratory Intelligence</h2><span>Recent activity and current operational attention</span></div>
            <div class="ap-body ap-record-grid">
                <div class="ap-record"><span class="count">{{ $clinicalCases->count() }}</span><h3>Clinical Cases</h3>@forelse($clinicalCases->take(3) as $case)<div class="ap-record-item"><b>{{ $case->case_number }} · {{ $case->status }}</b><p>{{ $formatDate($case->case_date,'d M Y, H:i') }} · {{ $case->severity }}<br>{{ $case->clinical_signs ?: $case->diagnosis ?: 'No clinical signs recorded.' }}</p></div>@empty<div class="ap-record-item"><p>No clinical cases recorded.</p></div>@endforelse</div>
                <div class="ap-record"><span class="count">{{ $treatments->count() }}</span><h3>Treatments</h3>@forelse($treatments->take(3) as $treatment)<div class="ap-record-item"><b>{{ $treatment->medicine_name ?: 'Medicine not recorded' }}</b><p>{{ $formatDate($treatment->given_at,'d M Y, H:i') }} · {{ $treatment->status }}<br>{{ $treatment->dosage ?: 'Dosage not recorded' }}{{ $treatment->method ? ' · '.$treatment->method : '' }}</p></div>@empty<div class="ap-record-item"><p>No treatment records recorded.</p></div>@endforelse</div>
                <div class="ap-record"><span class="count">{{ $labRequests->count() }}</span><h3>Laboratory Requests</h3>@forelse($labRequests->take(3) as $lab)<div class="ap-record-item"><b>{{ $lab->request_number }} · {{ $lab->status }}</b><p>{{ $lab->clinic_display_name }}<br>{{ $lab->requested_tests_text ?: 'Tests not recorded' }}<br>{{ $lab->results ?: 'Result pending' }}</p></div>@empty<div class="ap-record-item"><p>No laboratory requests recorded.</p></div>@endforelse</div>
            </div>
        </section>
    </div>
</x-filament-panels::page>
