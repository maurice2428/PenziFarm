@php
    $breedName = $animal->breed?->breed_name ?? 'Unknown breed';
    $purityPercent = $animal->breed_purity_percent !== null
        ? number_format((float) $animal->breed_purity_percent, 2) . '%'
        : 'Pending';
    $purityLabel = match ($animal->purity_status) {
        'foundation' => 'Foundation stock',
        'calculated' => 'Calculated pedigree',
        'dna_verified' => 'DNA verified',
        'manual_verified' => 'Manual verified',
        default => 'Pending parentage',
    };
    $weight = $animal->latestWeight;
    $age = $animal->date_of_birth
        ? $animal->date_of_birth->diffForHumans(now(), ['parts' => 2, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE])
        : 'Not recorded';
    if ($animal->date_of_birth && $animal->date_of_birth_is_estimated) {
        $age = 'Approx. ' . $age;
    }
    $node = function ($record, string $fallback = 'Not recorded') {
        return [
            'tag' => $record?->tag_number ?? $fallback,
            'meta' => $record ? (($record->breed?->breed_name ?? 'Breed pending') . ' · ' . ($record->sex ?? '')) : 'Parentage pending',
        ];
    };
    $sire = $animal->sire;
    $dam = $animal->dam;
    $ss = $sire?->sire;
    $sd = $sire?->dam;
    $ds = $dam?->sire;
    $dd = $dam?->dam;
@endphp

<x-filament-panels::page>
    <style>
        .penzi-profile { font-family: Courier, monospace; }
        .penzi-profile * { box-sizing: border-box; }
        .penzi-profile .title-strip { border-top: 5px solid {{ $primaryColor }}; border-bottom: 1px solid #cbd5e1; padding: 12px 0; }
        .penzi-profile .breed-title { font-size: 23px; line-height: 1; font-weight: 900; color: {{ $primaryColor }}; letter-spacing: .08em; text-transform: uppercase; }
        .penzi-profile .sub-title { margin-top: 5px; color: #64748b; font-size: 12px; }
        .penzi-profile .meta { color:#64748b; font-size:11px; line-height:1.55; }
        .penzi-profile .grid2 { display:grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top:12px; }
        .penzi-profile .grid4 { display:grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 9px; margin-top:12px; }
        .penzi-profile .card { border:1px solid #d5dfd2; background:#fff; padding:10px; }
        .penzi-profile .card-label { color:#64748b; font-size:10px; font-weight:900; text-transform:uppercase; letter-spacing:.06em; }
        .penzi-profile .card-value { margin-top:5px; color:#182333; font-size:15px; font-weight:900; overflow-wrap:anywhere; }
        .penzi-profile .card-small { margin-top:4px; color:#64748b; font-size:10px; }
        .penzi-profile .section { margin-top:18px; }
        .penzi-profile .section h3 { margin:0 0 7px; padding-bottom:5px; border-bottom:1px solid #cbd5e1; color:{{ $secondaryColor }}; font-size:12px; font-weight:900; letter-spacing:.08em; text-transform:uppercase; }
        .penzi-profile .facts { width:100%; border-collapse:collapse; }
        .penzi-profile .facts td { width:33.33%; border:1px solid #dbe4da; padding:8px; vertical-align:top; }
        .penzi-profile .fact-key { display:block; color:#64748b; font-size:9px; font-weight:900; text-transform:uppercase; }
        .penzi-profile .fact-value { display:block; margin-top:4px; font-size:12px; font-weight:800; color:#172033; }
        .penzi-profile .tree { position:relative; min-height:345px; border:1px solid #cbd8c9; overflow:hidden; background:linear-gradient(90deg, rgba(20,83,45,.035) 1px, transparent 1px),linear-gradient(rgba(20,83,45,.035) 1px, transparent 1px),#fcfffb; background-size:18px 18px; }
        .penzi-profile .tree svg { position:absolute; inset:0; width:100%; height:100%; z-index:1; }
        .penzi-profile .tree line, .penzi-profile .tree path { stroke:{{ $primaryColor }}; stroke-width:2.1; fill:none; }
        .penzi-profile .tree .node { position:absolute; z-index:2; width:20%; min-width:132px; border:1px solid #aebfae; background:#fff; padding:7px; font-size:10px; }
        .penzi-profile .tree .node .role { color:#64748b; font-size:8px; font-weight:900; text-transform:uppercase; letter-spacing:.06em; }
        .penzi-profile .tree .node .tag { margin-top:4px; font-size:11px; font-weight:900; color:#172033; overflow-wrap:anywhere; }
        .penzi-profile .tree .node .nmeta { margin-top:3px; color:#64748b; font-size:9px; }
        .penzi-profile .tree .focus { border:2px solid {{ $primaryColor }}; background:#f5fbf4; }
        .penzi-profile .gss { left:2%; top:7%; } .penzi-profile .gsd { left:2%; top:30%; }
        .penzi-profile .gds { left:2%; top:57%; } .penzi-profile .gdd { left:2%; top:80%; }
        .penzi-profile .sire { left:35%; top:18%; } .penzi-profile .dam { left:35%; top:65%; }
        .penzi-profile .animal { right:2%; top:41%; }
        .penzi-profile .table { width:100%; border-collapse:collapse; font-size:10px; }
        .penzi-profile .table th { background:{{ $primaryColor }}; color:#fff; text-align:left; padding:7px; }
        .penzi-profile .table td { border:1px solid #dbe4da; padding:6px; vertical-align:top; }
        @media (max-width: 900px) { .penzi-profile .grid4 { grid-template-columns:repeat(2,minmax(0,1fr)); } .penzi-profile .tree { overflow-x:auto; } .penzi-profile .tree .node { min-width:115px; } }
    </style>

    <div class="penzi-profile">
        <div class="title-strip">
            <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;">
                <div>
                    <div class="breed-title">{{ $breedName }}</div>
                    <div class="sub-title">PEDIGREE · PERFORMANCE · HEALTH PROFILE</div>
                </div>
                <div class="meta" style="text-align:right;">
                    <strong style="color:{{ $primaryColor }};">{{ $farmName }}</strong><br>
                    {{ $farmCounty }} · {{ $farmPhone }}<br>
                    {{ $farmEmail }}
                </div>
            </div>
        </div>

        <div class="grid4">
            <div class="card"><div class="card-label">Animal Tag</div><div class="card-value">{{ $animal->tag_number }}</div><div class="card-small">{{ $animal->sex }} · {{ $animal->species }}</div></div>
            <div class="card"><div class="card-label">Breed Purity</div><div class="card-value">{{ $purityPercent }}</div><div class="card-small">{{ $purityLabel }}</div></div>
            <div class="card"><div class="card-label">Latest Weight</div><div class="card-value">{{ $weight ? number_format((float) $weight->weight_kg, 2) . ' KG' : '—' }}</div><div class="card-small">{{ $weight?->trend ? ucfirst($weight->trend) : 'No recorded weight' }}</div></div>
            <div class="card"><div class="card-label">Lifecycle Status</div><div class="card-value">{{ $animal->status }}</div><div class="card-small">{{ $animal->location?->display_name ?? 'Location pending' }}</div></div>
        </div>

        <div class="section">
            <h3>Animal Registration & Management</h3>
            <table class="facts"><tr>
                <td><span class="fact-key">Tag / Farm Identifier</span><span class="fact-value">{{ $animal->tag_number }}</span></td>
                <td><span class="fact-key">Breed / Purity Breed</span><span class="fact-value">{{ $breedName }} / {{ $animal->purityBreed?->breed_name ?? $breedName }}</span></td>
                <td><span class="fact-key">Date of Birth / Age</span><span class="fact-value">{{ $animal->date_of_birth?->format('d M Y') ?? 'Not recorded' }} / {{ $age }}</span></td>
            </tr><tr>
                <td><span class="fact-key">Source / Purpose</span><span class="fact-value">{{ $animal->source }} / {{ $animal->purpose }}</span></td>
                <td><span class="fact-key">Breeding / Sale Readiness</span><span class="fact-value">{{ $animal->is_breeder ? 'Breeding retained' : 'Not retained' }} / {{ $animal->sale_ready ? 'Sale ready' : 'Not sale ready' }}</span></td>
                <td><span class="fact-key">Valuation</span><span class="fact-value">{{ $animal->valuation_price ? 'KES ' . number_format((float) $animal->valuation_price, 2) : 'Not recorded' }}</span></td>
            </tr></table>
        </div>

        <div class="section">
            <h3>Four-Generation Pedigree Lineage</h3>
            <div class="tree">
                <svg viewBox="0 0 1000 345" preserveAspectRatio="none" aria-hidden="true">
                    <path d="M220 44 H310 V96 H350" /><path d="M220 123 H310 V96 H350" />
                    <path d="M220 203 H310 V255 H350" /><path d="M220 282 H310 V255 H350" />
                    <path d="M550 96 H670 V170 H790" /><path d="M550 255 H670 V170 H790" />
                </svg>
                @php $n=$node($ss); @endphp <div class="node gss"><div class="role">Sire's Sire</div><div class="tag">{{ $n['tag'] }}</div><div class="nmeta">{{ $n['meta'] }}</div></div>
                @php $n=$node($sd); @endphp <div class="node gsd"><div class="role">Sire's Dam</div><div class="tag">{{ $n['tag'] }}</div><div class="nmeta">{{ $n['meta'] }}</div></div>
                @php $n=$node($ds); @endphp <div class="node gds"><div class="role">Dam's Sire</div><div class="tag">{{ $n['tag'] }}</div><div class="nmeta">{{ $n['meta'] }}</div></div>
                @php $n=$node($dd); @endphp <div class="node gdd"><div class="role">Dam's Dam</div><div class="tag">{{ $n['tag'] }}</div><div class="nmeta">{{ $n['meta'] }}</div></div>
                @php $n=$node($sire); @endphp <div class="node sire"><div class="role">Sire</div><div class="tag">{{ $n['tag'] }}</div><div class="nmeta">{{ $n['meta'] }}</div></div>
                @php $n=$node($dam); @endphp <div class="node dam"><div class="role">Dam</div><div class="tag">{{ $n['tag'] }}</div><div class="nmeta">{{ $n['meta'] }}</div></div>
                <div class="node animal focus"><div class="role">Selected Animal</div><div class="tag">{{ $animal->tag_number }}</div><div class="nmeta">{{ $breedName }} · {{ $animal->sex }} · {{ $purityPercent }}</div></div>
            </div>
        </div>

        <div class="grid2">
            <div class="section" style="margin-top:0;"><h3>Health Snapshot</h3><table class="table"><thead><tr><th>Type</th><th>Product</th><th>Date</th><th>Next Due</th></tr></thead><tbody>@forelse($animal->healthAdministrations->take(6) as $entry)<tr><td>{{ str($entry->product?->type ?? 'Health')->replace('_',' ')->title() }}</td><td>{{ $entry->product?->name ?? 'Not recorded' }}</td><td>{{ $entry->administered_at?->format('d M Y') ?? '—' }}</td><td>{{ $entry->next_due_date?->format('d M Y') ?? '—' }}</td></tr>@empty<tr><td colspan="4">No linked health administrations.</td></tr>@endforelse</tbody></table></div>
            <div class="section" style="margin-top:0;"><h3>Clinical & Laboratory Snapshot</h3><table class="table"><thead><tr><th>Record</th><th>Status</th><th>Date</th><th>Officer / Clinic</th></tr></thead><tbody>@forelse($animal->clinicalCases->take(3) as $case)<tr><td>{{ $case->case_number }}</td><td>{{ $case->status }}</td><td>{{ $case->case_date?->format('d M Y') ?? '—' }}</td><td>{{ $case->attending_officer ?? '—' }}</td></tr>@empty<tr><td colspan="4">No clinical cases recorded.</td></tr>@endforelse @foreach($animal->labRequests->take(3) as $lab)<tr><td>{{ $lab->request_number }}</td><td>{{ $lab->status }}</td><td>{{ $lab->requested_at?->format('d M Y') ?? '—' }}</td><td>{{ $lab->clinic_display_name }}</td></tr>@endforeach</tbody></table></div>
        </div>
    </div>
</x-filament-panels::page>
