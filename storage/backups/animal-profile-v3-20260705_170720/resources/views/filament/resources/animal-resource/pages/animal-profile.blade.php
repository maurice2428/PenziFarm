@php
    $breedName = $animal->breed?->breed_name ?? 'Unknown Breed';
    $purityBreedName = $animal->purityBreed?->breed_name ?? $breedName;
    $purityPercent = $animal->breed_purity_percent !== null
        ? number_format((float) $animal->breed_purity_percent, 2) . '%'
        : 'Pending';

    $purityLabel = match ($animal->purity_status) {
        'foundation' => 'Foundation stock',
        'calculated' => 'Calculated pedigree',
        'dna_verified' => 'DNA verified',
        'manual_verified' => 'Manual verified',
        default => 'Pending verification',
    };

    $latestWeight = $animal->latestWeight;
    $weightTrend = $latestWeight?->trend ?? 'none';
    $weightTrendLabel = match ($weightTrend) {
        'gaining' => 'Gaining',
        'losing' => 'Losing',
        'stable' => 'Stable',
        'first' => 'First record',
        default => 'No weight record',
    };

    $age = '-';
    if ($animal->date_of_birth) {
        $age = $animal->date_of_birth->diffForHumans(now(), [
            'parts' => 2,
            'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE,
        ]);
        if ($animal->date_of_birth_is_estimated) {
            $age = 'Approx. ' . $age;
        }
    }

    $sire = $animal->sire;
    $dam = $animal->dam;
    $sireSire = $sire?->sire;
    $sireDam = $sire?->dam;
    $damSire = $dam?->sire;
    $damDam = $dam?->dam;

    $node = function ($item, string $fallback = 'Not recorded'): array {
        if (! $item) {
            return [
                'tag' => $fallback,
                'meta' => 'Parentage pending',
                'sex' => '',
            ];
        }

        return [
            'tag' => $item->tag_number,
            'meta' => trim(
                ($item->breed?->breed_name ?? 'Breed not recorded')
                . ' · '
                . ($item->sex ?? 'Sex not recorded')
            ),
            'sex' => $item->sex ?? '',
        ];
    };

    $healthRows = $animal->healthAdministrations->take(8);
    $caseRows = $animal->clinicalCases->take(5);
    $treatmentRows = $animal->treatmentRecords->take(5);
    $labRows = $animal->labRequests->take(5);

    $statusClass = match ($animal->status) {
        'Active' => 'good',
        'Sold' => 'warn',
        'Dead', 'Culled' => 'bad',
        default => 'neutral',
    };
@endphp

<x-filament-panels::page>
    <style>
        .penzi-profile {
            font-family: Courier, monospace;
            color: #172033;
        }

        .penzi-profile * {
            box-sizing: border-box;
        }

        .penzi-profile .profile-shell {
            border: 1px solid #d9e2d5;
            background: #ffffff;
            box-shadow: 0 18px 44px rgba(15, 61, 34, .08);
        }

        .penzi-profile .topbar {
            padding: 18px 20px;
            color: #ffffff;
            background: linear-gradient(135deg, {{ $primaryColor }}, {{ $secondaryColor }});
        }

        .penzi-profile .top-grid {
            display: grid;
            grid-template-columns: 80px 1fr auto;
            gap: 16px;
            align-items: center;
        }

        .penzi-profile .logo-wrap {
            width: 64px;
            height: 64px;
            background: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 2px solid rgba(255,255,255,.55);
        }

        .penzi-profile .logo-wrap img {
            max-width: 58px;
            max-height: 58px;
        }

        .penzi-profile .brand-title {
            margin: 0;
            font-size: 18px;
            letter-spacing: .08em;
            font-weight: 900;
        }

        .penzi-profile .brand-subtitle {
            margin-top: 5px;
            font-size: 11px;
            opacity: .88;
        }

        .penzi-profile .contact {
            text-align: right;
            font-size: 10px;
            line-height: 1.65;
            opacity: .92;
        }

        .penzi-profile .profile-title {
            padding: 14px 20px;
            background: #f7faf6;
            border-bottom: 1px solid #d9e2d5;
        }

        .penzi-profile .profile-title h2 {
            margin: 0;
            color: {{ $primaryColor }};
            font-size: 20px;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .penzi-profile .profile-title p {
            margin: 4px 0 0;
            color: #637083;
            font-size: 11px;
        }

        .penzi-profile .profile-body {
            padding: 16px;
        }

        .penzi-profile .metric-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 8px;
            margin-bottom: 14px;
        }

        .penzi-profile .metric {
            min-height: 78px;
            padding: 10px;
            border: 1px solid #d9e2d5;
            background: #fbfdf9;
        }

        .penzi-profile .metric-label {
            color: #718096;
            font-size: 9px;
            letter-spacing: .06em;
            text-transform: uppercase;
            font-weight: 800;
        }

        .penzi-profile .metric-value {
            margin-top: 7px;
            color: #172033;
            font-size: 16px;
            line-height: 1.05;
            font-weight: 900;
        }

        .penzi-profile .metric-note {
            margin-top: 5px;
            color: #64748b;
            font-size: 9px;
            line-height: 1.35;
        }

        .penzi-profile .section-title {
            margin: 17px 0 8px;
            color: {{ $secondaryColor }};
            font-size: 12px;
            letter-spacing: .08em;
            text-transform: uppercase;
            font-weight: 900;
        }

        .penzi-profile .identity-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 8px;
        }

        .penzi-profile .detail-card {
            border: 1px solid #dfe7dc;
            background: #ffffff;
            padding: 10px;
        }

        .penzi-profile .detail-key {
            color: #728091;
            font-size: 9px;
            letter-spacing: .05em;
            text-transform: uppercase;
            font-weight: 800;
        }

        .penzi-profile .detail-value {
            margin-top: 4px;
            color: #172033;
            font-size: 11px;
            line-height: 1.45;
            font-weight: 700;
        }

        .penzi-profile .status-pill {
            display: inline-block;
            padding: 3px 8px;
            font-size: 9px;
            font-weight: 900;
            color: #ffffff;
            border-radius: 999px;
        }

        .penzi-profile .good { background: #15803d; }
        .penzi-profile .warn { background: #b7791f; }
        .penzi-profile .bad { background: #b91c1c; }
        .penzi-profile .neutral { background: #64748b; }

        .penzi-profile .pedigree {
            position: relative;
            min-height: 390px;
            overflow: hidden;
            border: 1px solid #d7e1d5;
            background:
                linear-gradient(90deg, rgba(20,83,45,.03) 1px, transparent 1px),
                linear-gradient(rgba(20,83,45,.03) 1px, transparent 1px),
                #fcfffb;
            background-size: 22px 22px;
        }

        .penzi-profile .tree-node {
            position: absolute;
            width: 21%;
            min-height: 66px;
            padding: 9px;
            border: 1px solid #aebbad;
            border-left: 5px solid {{ $primaryColor }};
            background: #ffffff;
            box-shadow: 0 4px 12px rgba(15, 61, 34, .07);
            z-index: 2;
        }

        .penzi-profile .tree-node.target {
            width: 23%;
            border-left-color: {{ $accentColor }};
            background: #fffdf7;
        }

        .penzi-profile .tree-label {
            color: #788697;
            font-size: 8px;
            letter-spacing: .07em;
            text-transform: uppercase;
            font-weight: 900;
        }

        .penzi-profile .tree-tag {
            margin-top: 6px;
            color: #172033;
            font-size: 11px;
            font-weight: 900;
            overflow-wrap: anywhere;
        }

        .penzi-profile .tree-meta {
            margin-top: 4px;
            color: #657489;
            font-size: 8px;
            line-height: 1.35;
        }

        .penzi-profile .line-h,
        .penzi-profile .line-v {
            position: absolute;
            background: #7d8e80;
            z-index: 1;
        }

        .penzi-profile .line-h { height: 1px; }
        .penzi-profile .line-v { width: 1px; }

        .penzi-profile .profile-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        .penzi-profile .profile-table th {
            background: {{ $primaryColor }};
            color: #ffffff;
            padding: 7px;
            text-align: left;
            border: 1px solid {{ $primaryColor }};
            font-size: 9px;
        }

        .penzi-profile .profile-table td {
            padding: 7px;
            vertical-align: top;
            border: 1px solid #dfe7dc;
            color: #243447;
        }

        .penzi-profile .profile-table tr:nth-child(even) td {
            background: #fbfdf9;
        }

        .penzi-profile .empty-row {
            color: #7a8795;
            text-align: center;
            padding: 13px !important;
        }

        .penzi-profile .footer-note {
            margin-top: 16px;
            padding-top: 10px;
            border-top: 1px solid #d9e2d5;
            display: flex;
            justify-content: space-between;
            color: #718096;
            font-size: 9px;
        }

        @media (max-width: 1100px) {
            .penzi-profile .metric-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .penzi-profile .identity-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .penzi-profile .top-grid { grid-template-columns: 70px 1fr; }
            .penzi-profile .contact { display: none; }
            .penzi-profile .pedigree { overflow-x: auto; min-width: 850px; }
        }
    </style>

    <div class="penzi-profile">
        <div class="profile-shell">
            <div class="topbar">
                <div class="top-grid">
                    <div class="logo-wrap">
                        @if ($logoUrl)
                            <img src="{{ $logoUrl }}" alt="Farm logo">
                        @else
                            <span style="color:{{ $primaryColor }};font-weight:900;">PF</span>
                        @endif
                    </div>

                    <div>
                        <h1 class="brand-title">{{ $farmName }}</h1>
                        <div class="brand-subtitle">{{ $farmTagline }}</div>
                    </div>

                    <div class="contact">
                        {{ $farmCounty }}<br>
                        {{ $farmPhone }}<br>
                        {{ $farmEmail }}
                    </div>
                </div>
            </div>

            <div class="profile-title">
                <h2>{{ strtoupper($breedName) }} · PEDIGREE & ANIMAL PROFILE</h2>
                <p>
                    Registered animal profile for <strong>{{ $animal->tag_number }}</strong>
                    · live pedigree, performance and animal-health record
                </p>
            </div>

            <div class="profile-body">
                <div class="metric-grid">
                    <div class="metric">
                        <div class="metric-label">Animal tag</div>
                        <div class="metric-value">{{ $animal->tag_number }}</div>
                        <div class="metric-note">{{ $animal->sex }} · {{ $animal->species }}</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">Breed purity</div>
                        <div class="metric-value">{{ $purityPercent }}</div>
                        <div class="metric-note">{{ $purityBreedName }} · {{ $purityLabel }}</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">Life status</div>
                        <div class="metric-value">
                            <span class="status-pill {{ $statusClass }}">{{ $animal->status }}</span>
                        </div>
                        <div class="metric-note">{{ $animal->purpose ?: 'Purpose not recorded' }}</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">Latest weight</div>
                        <div class="metric-value">
                            {{ $latestWeight ? number_format((float) $latestWeight->weight_kg, 2) . ' KG' : '—' }}
                        </div>
                        <div class="metric-note">{{ $weightTrendLabel }}</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">Health activity</div>
                        <div class="metric-value">{{ $animal->healthAdministrations->count() }}</div>
                        <div class="metric-note">Recorded administrations</div>
                    </div>
                </div>

                <div class="section-title">Identity, management & status</div>

                <div class="identity-grid">
                    <div class="detail-card"><div class="detail-key">Date of birth</div><div class="detail-value">{{ $animal->date_of_birth?->format('d M Y') ?? 'Not recorded' }}</div></div>
                    <div class="detail-card"><div class="detail-key">Age</div><div class="detail-value">{{ $age }}</div></div>
                    <div class="detail-card"><div class="detail-key">Current location</div><div class="detail-value">{{ $animal->location?->display_name ?? $animal->location?->name ?? 'Not recorded' }}</div></div>
                    <div class="detail-card"><div class="detail-key">Source</div><div class="detail-value">{{ $animal->source ?? 'Not recorded' }}</div></div>
                    <div class="detail-card"><div class="detail-key">Breeding status</div><div class="detail-value">{{ $animal->is_breeder ? 'Retained for breeding' : ($animal->sale_ready ? 'Sale ready' : 'General herd') }}</div></div>
                    <div class="detail-card"><div class="detail-key">Current valuation</div><div class="detail-value">{{ $animal->valuation_price !== null ? 'KES ' . number_format((float) $animal->valuation_price, 2) : 'Not recorded' }}</div></div>
                </div>

                <div class="section-title">Heredity chart · four-grandparent pedigree</div>

                <div class="pedigree">
                    {{-- Target animal --}}
                    <div class="tree-node target" style="left:3%; top:154px;">
                        <div class="tree-label">Selected animal</div>
                        <div class="tree-tag">{{ $animal->tag_number }}</div>
                        <div class="tree-meta">{{ $breedName }} · {{ $animal->sex }}<br>{{ $animal->date_of_birth?->format('d M Y') ?? 'DOB not recorded' }}</div>
                    </div>

                    {{-- Sire and dam --}}
                    <div class="tree-node" style="left:34%; top:74px;">
                        <div class="tree-label">Sire</div>
                        <div class="tree-tag">{{ $node($sire)['tag'] }}</div>
                        <div class="tree-meta">{{ $node($sire)['meta'] }}</div>
                    </div>
                    <div class="tree-node" style="left:34%; top:236px;">
                        <div class="tree-label">Dam</div>
                        <div class="tree-tag">{{ $node($dam)['tag'] }}</div>
                        <div class="tree-meta">{{ $node($dam)['meta'] }}</div>
                    </div>

                    {{-- Grandparents --}}
                    <div class="tree-node" style="left:70%; top:18px;">
                        <div class="tree-label">Paternal grandsire</div>
                        <div class="tree-tag">{{ $node($sireSire)['tag'] }}</div>
                        <div class="tree-meta">{{ $node($sireSire)['meta'] }}</div>
                    </div>
                    <div class="tree-node" style="left:70%; top:102px;">
                        <div class="tree-label">Paternal granddam</div>
                        <div class="tree-tag">{{ $node($sireDam)['tag'] }}</div>
                        <div class="tree-meta">{{ $node($sireDam)['meta'] }}</div>
                    </div>
                    <div class="tree-node" style="left:70%; top:198px;">
                        <div class="tree-label">Maternal grandsire</div>
                        <div class="tree-tag">{{ $node($damSire)['tag'] }}</div>
                        <div class="tree-meta">{{ $node($damSire)['meta'] }}</div>
                    </div>
                    <div class="tree-node" style="left:70%; top:282px;">
                        <div class="tree-label">Maternal granddam</div>
                        <div class="tree-tag">{{ $node($damDam)['tag'] }}</div>
                        <div class="tree-meta">{{ $node($damDam)['meta'] }}</div>
                    </div>

                    {{-- Target to parents --}}
                    <div class="line-h" style="left:26%; top:187px; width:8%;"></div>
                    <div class="line-v" style="left:34%; top:107px; height:162px;"></div>
                    <div class="line-h" style="left:34%; top:107px; width:3%;"></div>
                    <div class="line-h" style="left:34%; top:269px; width:3%;"></div>

                    {{-- Sire to paternal grandparents --}}
                    <div class="line-h" style="left:55%; top:107px; width:9%;"></div>
                    <div class="line-v" style="left:64%; top:51px; height:107px;"></div>
                    <div class="line-h" style="left:64%; top:51px; width:6%;"></div>
                    <div class="line-h" style="left:64%; top:135px; width:6%;"></div>

                    {{-- Dam to maternal grandparents --}}
                    <div class="line-h" style="left:55%; top:269px; width:9%;"></div>
                    <div class="line-v" style="left:64%; top:231px; height:107px;"></div>
                    <div class="line-h" style="left:64%; top:231px; width:6%;"></div>
                    <div class="line-h" style="left:64%; top:315px; width:6%;"></div>
                </div>

                <div class="section-title">Health administrations · latest {{ min(8, $animal->healthAdministrations->count()) }} records</div>
                <table class="profile-table">
                    <thead><tr><th>Date</th><th>Type</th><th>Product</th><th>Dosage / quantity</th><th>Next due</th><th>Administered by</th></tr></thead>
                    <tbody>
                        @forelse ($healthRows as $entry)
                            <tr>
                                <td>{{ $entry->administered_at?->format('d M Y') ?? '—' }}</td>
                                <td>{{ str($entry->product?->type ?? 'Health')->replace('_', ' ')->title() }}</td>
                                <td>{{ $entry->product?->name ?? 'Product not recorded' }}</td>
                                <td>{{ $entry->dosage_per_animal !== null ? $entry->dosage_per_animal . ' per animal' : '—' }}</td>
                                <td>{{ $entry->next_due_date?->format('d M Y') ?? '—' }}</td>
                                <td>{{ $entry->administered_by ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="empty-row">No health-administration records are linked to this animal yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="section-title">Clinical care, treatment & laboratory history</div>
                <table class="profile-table">
                    <thead><tr><th style="width:18%">Clinical case</th><th style="width:27%">Treatment</th><th style="width:27%">Laboratory request</th><th style="width:28%">Decision point</th></tr></thead>
                    <tbody>
                        <tr>
                            <td>
                                @forelse ($caseRows as $case)
                                    <strong>{{ $case->case_number }}</strong><br>
                                    {{ $case->case_date?->format('d M Y') }} · {{ $case->severity }}<br>
                                    <span style="color:#64748b">{{ \Illuminate\Support\Str::limit($case->diagnosis ?: $case->clinical_signs ?: 'No diagnosis recorded', 85) }}</span><br><br>
                                @empty
                                    <span style="color:#7a8795">No sick-case records.</span>
                                @endforelse
                            </td>
                            <td>
                                @forelse ($treatmentRows as $treatment)
                                    <strong>{{ $treatment->medicine_name ?? 'Medicine not recorded' }}</strong><br>
                                    {{ $treatment->given_at?->format('d M Y') }} · {{ $treatment->status }}<br>
                                    <span style="color:#64748b">{{ \Illuminate\Support\Str::limit($treatment->dosage ?: $treatment->notes ?: 'No dosage/notes', 85) }}</span><br><br>
                                @empty
                                    <span style="color:#7a8795">No treatment records.</span>
                                @endforelse
                            </td>
                            <td>
                                @forelse ($labRows as $lab)
                                    <strong>{{ $lab->request_number }}</strong><br>
                                    {{ $lab->clinic_display_name }}<br>
                                    <span style="color:#64748b">{{ $lab->status }} · {{ $lab->requested_at?->format('d M Y') }}</span><br><br>
                                @empty
                                    <span style="color:#7a8795">No laboratory requests.</span>
                                @endforelse
                            </td>
                            <td>
                                <strong>Current health view</strong><br>
                                {{ $animal->clinicalCases->whereIn('status', ['Open', 'Under Treatment', 'Referred'])->count() }} active/review cases<br>
                                {{ $animal->labRequests->whereIn('status', ['Requested', 'Sample Collected', 'Dispatched', 'In Progress'])->count() }} laboratory items in progress<br><br>
                                <span style="color:#64748b">Use the complete health modules for all underlying records and attachments.</span>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="footer-note">
                    <span>Profile data is live from the Penzi Farm ERP.</span>
                    <span>Profile ID: {{ $animal->id }} · {{ now('Africa/Nairobi')->format('d M Y H:i') }} EAT</span>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
