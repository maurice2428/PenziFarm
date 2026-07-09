@php
    $formatDate = static function ($value, string $format = 'd M Y'): string {
        if (blank($value)) {
            return 'Not recorded';
        }

        return \Carbon\Carbon::parse($value)->format($format);
    };

    $ageDisplay = 'Not recorded';

    if (filled($animal->date_of_birth)) {
        $dob = \Carbon\Carbon::parse($animal->date_of_birth);

        $ageDisplay = $dob->isFuture()
            ? 'Invalid date of birth'
            : ($animal->date_of_birth_is_estimated ? 'Approx. ' : '')
                . $dob->diffForHumans(now(), [
                    'parts' => 2,
                    'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE,
                ]);
    }

    $purityPercent = $animal->breed_purity_percent !== null
        ? (float) $animal->breed_purity_percent
        : null;

    $purityStatus = $animal->purity_status ?: 'pending';

    $purityLabel = match ($purityStatus) {
        'foundation' => 'Foundation Stock',
        'calculated' => 'Pedigree Calculated',
        'dna_verified' => 'DNA Verified',
        'manual_verified' => 'Manual Verified',
        default => 'Pending Parentage',
    };

    $purityTone = match ($purityStatus) {
        'foundation' => 'green',
        'calculated' => 'blue',
        'dna_verified' => 'purple',
        'manual_verified' => 'gold',
        default => 'gray',
    };

    $weight = $animal->latestWeight;
    $weightTrend = $weight?->trend ?? 'none';
    $weightLabel = match ($weightTrend) {
        'gaining' => 'Gaining',
        'losing' => 'Losing',
        'stable' => 'Stable',
        'first' => 'Baseline',
        default => 'No weight recorded',
    };

    $statusTone = match ($animal->status) {
        'Active' => 'green',
        'Sold' => 'gold',
        'Dead' => 'red',
        'Culled' => 'gray',
        default => 'gray',
    };

    $healthAdministrations = $animal->healthAdministrations;
    $clinicalCases = $animal->clinicalCases;
    $treatments = $animal->treatmentRecords;
    $labRequests = $animal->labRequests;

    $openClinicalCases = $clinicalCases->filter(
        fn ($case) => ! in_array($case->status, ['Resolved', 'Closed'], true)
    )->count();

    $vaccinationCount = $healthAdministrations->filter(
        fn ($administration) => in_array(
            strtolower((string) ($administration->product?->type ?? '')),
            ['vaccine', 'vaccination'],
            true
        )
    )->count();

    $dewormingCount = $healthAdministrations->filter(
        fn ($administration) => in_array(
            strtolower((string) ($administration->product?->type ?? '')),
            ['dewormer', 'deworming'],
            true
        )
    )->count();
@endphp

<x-filament-panels::page>
    <style>
        .animal-profile-shell {
            --profile-primary: {{ $primaryColor }};
            --profile-secondary: {{ $secondaryColor }};
            --profile-accent: {{ $accentColor }};
            --profile-danger: {{ $dangerColor }};
            color: #18221c;
        }

        .animal-profile-shell * { box-sizing: border-box; }

        .profile-hero {
            position: relative;
            overflow: hidden;
            border: 1px solid color-mix(in srgb, var(--profile-primary) 34%, #dfe8de);
            border-radius: 18px;
            padding: 24px;
            background:
                radial-gradient(circle at 88% 5%, color-mix(in srgb, var(--profile-primary) 17%, transparent), transparent 34%),
                linear-gradient(135deg, #f7fcf7 0%, #ffffff 58%, #edf7ee 100%);
            box-shadow: 0 18px 42px rgba(20, 83, 45, .10);
        }

        .profile-brand-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(20, 83, 45, .16);
        }

        .profile-farm-brand { display: flex; align-items: center; gap: 12px; }
        .profile-logo-wrap {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            background: #ffffff;
            border: 1px solid rgba(20, 83, 45, .18);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .profile-logo-wrap img { max-width: 46px; max-height: 46px; object-fit: contain; }
        .profile-logo-fallback { font-weight: 900; color: var(--profile-primary); font-size: 15px; }
        .profile-farm-name { font-size: 15px; font-weight: 900; color: #153c24; letter-spacing: .01em; }
        .profile-farm-sub { font-size: 11px; color: #6b7280; margin-top: 2px; }
        .profile-certificate-caption { text-align: right; }
        .profile-certificate-caption strong { display: block; font-size: 12px; color: var(--profile-primary); letter-spacing: .11em; text-transform: uppercase; }
        .profile-certificate-caption span { display: block; margin-top: 3px; color: #6b7280; font-size: 10px; }

        .profile-identity-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 18px;
            align-items: end;
            padding-top: 23px;
        }

        .profile-breed-kicker { color: var(--profile-primary); font-size: 11px; font-weight: 900; letter-spacing: .14em; text-transform: uppercase; }
        .profile-animal-title { margin: 5px 0 4px; font-size: clamp(24px, 4vw, 36px); line-height: 1.05; font-weight: 950; letter-spacing: -.035em; color: #152017; }
        .profile-animal-subtitle { margin: 0; color: #57635b; font-size: 13px; }
        .profile-tag-chip { min-width: 185px; padding: 15px 18px; border-radius: 14px; border: 1px solid rgba(20, 83, 45, .20); background: #fff; text-align: right; }
        .profile-tag-chip span { display: block; color: #708075; font-size: 9px; font-weight: 900; letter-spacing: .12em; text-transform: uppercase; }
        .profile-tag-chip strong { display: block; margin-top: 5px; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; color: var(--profile-primary); font-size: 18px; letter-spacing: .04em; }

        .profile-metrics {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 10px;
            margin-top: 14px;
        }
        .profile-metric { min-height: 92px; padding: 14px; border: 1px solid #e4ebe4; border-radius: 14px; background: #fff; }
        .profile-metric-label { color: #748077; font-size: 9px; letter-spacing: .10em; font-weight: 900; text-transform: uppercase; }
        .profile-metric-value { margin-top: 9px; color: #17221a; font-size: 18px; font-weight: 900; line-height: 1.05; }
        .profile-metric-note { margin-top: 6px; color: #758075; font-size: 10px; line-height: 1.35; }

        .tone-green { color: #15803d !important; }
        .tone-blue { color: #2563eb !important; }
        .tone-purple { color: #7c3aed !important; }
        .tone-gold { color: #b45309 !important; }
        .tone-red { color: #b91c1c !important; }
        .tone-gray { color: #6b7280 !important; }

        .profile-section { margin-top: 16px; border: 1px solid #e5e7eb; border-radius: 16px; background: #fff; overflow: hidden; }
        .profile-section-heading { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 14px 18px; background: linear-gradient(90deg, #f8fbf8, #ffffff); border-bottom: 1px solid #e5e7eb; }
        .profile-section-heading h2 { margin: 0; color: #173a25; font-size: 13px; font-weight: 950; letter-spacing: .035em; text-transform: uppercase; }
        .profile-section-heading span { color: #6b7280; font-size: 10px; }
        .profile-section-body { padding: 16px 18px; }

        .profile-grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .profile-grid-3 { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
        .profile-grid-4 { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; }

        .profile-data-table { width: 100%; border-collapse: collapse; }
        .profile-data-table td { padding: 10px 0; vertical-align: top; border-bottom: 1px solid #edf0ed; }
        .profile-data-table tr:last-child td { border-bottom: 0; }
        .profile-data-table td:first-child { width: 43%; color: #718075; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .055em; }
        .profile-data-table td:last-child { color: #1f2937; font-size: 12px; font-weight: 750; }

        .profile-purity-panel { border: 1px solid #d6ead8; border-radius: 14px; padding: 15px; background: linear-gradient(135deg, #f4fff5, #fff); }
        .profile-purity-top { display: flex; justify-content: space-between; gap: 12px; align-items: baseline; }
        .profile-purity-percent { font-size: 30px; font-weight: 950; color: var(--profile-primary); letter-spacing: -.05em; }
        .profile-purity-label { font-size: 11px; font-weight: 900; color: #46624e; text-transform: uppercase; letter-spacing: .08em; text-align: right; }
        .profile-progress { height: 9px; margin-top: 13px; overflow: hidden; border-radius: 999px; background: #dceadd; }
        .profile-progress > div { height: 100%; border-radius: 999px; background: linear-gradient(90deg, var(--profile-primary), #22c55e); }
        .profile-purity-meta { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 9px; margin-top: 14px; }
        .profile-purity-meta div { padding: 9px; border: 1px solid #e5eee5; border-radius: 9px; background: #fff; }
        .profile-purity-meta span { display: block; color: #77837a; font-size: 9px; text-transform: uppercase; font-weight: 900; letter-spacing: .07em; }
        .profile-purity-meta strong { display: block; margin-top: 4px; font-size: 11px; color: #203124; }

        .pedigree-layout { display: grid; grid-template-columns: minmax(0, 1fr) minmax(260px, .9fr); gap: 16px; }
        .pedigree-tree { border: 1px solid #e6ece6; border-radius: 14px; overflow: hidden; }
        .pedigree-tree-header { padding: 12px 14px; background: #fbfdfb; border-bottom: 1px solid #e6ece6; color: #385143; font-size: 10px; font-weight: 900; letter-spacing: .09em; text-transform: uppercase; }
        .pedigree-parent-row { display: grid; grid-template-columns: 110px minmax(0, 1fr); gap: 12px; padding: 13px 14px; border-bottom: 1px solid #eef1ee; }
        .pedigree-parent-row:last-child { border-bottom: 0; }
        .pedigree-parent-role { color: #66806e; font-size: 10px; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; }
        .pedigree-name { font-size: 13px; color: #1f2937; font-weight: 900; }
        .pedigree-meta { margin-top: 4px; color: #6b7280; font-size: 10px; }
        .pedigree-ancestor-list { margin-top: 8px; display: grid; gap: 4px; }
        .pedigree-ancestor-list span { color: #617166; font-size: 10px; }
        .pedigree-ancestor-list b { color: #334155; }
        .profile-note-card { padding: 15px; border: 1px solid #f0dfbb; border-radius: 14px; background: #fffaf0; }
        .profile-note-card h3 { margin: 0; color: #9a5b00; font-size: 11px; text-transform: uppercase; letter-spacing: .08em; }
        .profile-note-card p { margin: 9px 0 0; color: #6b5a32; font-size: 11px; line-height: 1.65; }

        .profile-table-wrap { overflow-x: auto; }
        .profile-table { width: 100%; border-collapse: collapse; min-width: 760px; }
        .profile-table th { padding: 10px; background: #f6f9f6; border-bottom: 1px solid #dfe7df; color: #58675c; text-align: left; font-size: 9px; font-weight: 900; letter-spacing: .07em; text-transform: uppercase; }
        .profile-table td { padding: 11px 10px; border-bottom: 1px solid #edf0ed; color: #364238; font-size: 11px; vertical-align: top; }
        .profile-table tr:last-child td { border-bottom: 0; }
        .profile-table td strong { color: #1f2937; }
        .profile-empty { padding: 23px; border: 1px dashed #ced7cf; border-radius: 12px; color: #718075; text-align: center; font-size: 12px; background: #fbfdfb; }
        .profile-status-pill { display: inline-block; padding: 4px 8px; border-radius: 999px; font-size: 9px; font-weight: 900; letter-spacing: .04em; }
        .profile-status-pill.green { color: #166534; background: #dcfce7; }
        .profile-status-pill.gold { color: #92400e; background: #fef3c7; }
        .profile-status-pill.red { color: #991b1b; background: #fee2e2; }
        .profile-status-pill.blue { color: #1d4ed8; background: #dbeafe; }
        .profile-status-pill.gray { color: #4b5563; background: #e5e7eb; }
        .profile-record-count { display: inline-flex; align-items: center; min-width: 28px; height: 22px; justify-content: center; border-radius: 999px; background: #edf5ed; color: var(--profile-primary); font-size: 10px; font-weight: 950; }

        @media (max-width: 1000px) {
            .profile-metrics { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .pedigree-layout { grid-template-columns: 1fr; }
        }
        @media (max-width: 700px) {
            .profile-brand-row, .profile-identity-row { display: block; }
            .profile-certificate-caption, .profile-tag-chip { margin-top: 14px; text-align: left; }
            .profile-metrics, .profile-grid-2, .profile-grid-3, .profile-grid-4 { grid-template-columns: 1fr; }
            .profile-section-body { padding: 14px; }
            .pedigree-parent-row { grid-template-columns: 1fr; gap: 6px; }
        }
    </style>

    <div class="animal-profile-shell">
        <section class="profile-hero">
            <div class="profile-brand-row">
                <div class="profile-farm-brand">
                    <div class="profile-logo-wrap">
                        @if ($logoUrl)
                            <img src="{{ $logoUrl }}" alt="{{ $farmName }} logo">
                        @else
                            <span class="profile-logo-fallback">PF</span>
                        @endif
                    </div>
                    <div>
                        <div class="profile-farm-name">{{ $farmName }}</div>
                        <div class="profile-farm-sub">{{ $farmTagline }} - {{ $farmCounty }}</div>
                    </div>
                </div>
                <div class="profile-certificate-caption">
                    <strong>Pedigree & Animal Profile</strong>
                    <span>Live record, health intelligence and lineage summary</span>
                </div>
            </div>

            <div class="profile-identity-row">
                <div>
                    <div class="profile-breed-kicker">{{ $animal->breed?->breed_name ?? 'Unclassified breed' }}</div>
                    <h1 class="profile-animal-title">{{ $animal->tag_number }}</h1>
                    <p class="profile-animal-subtitle">
                        {{ $animal->species }} - {{ $animal->sex }} - {{ $animal->purpose }} purpose
                        @if ($animal->location)
                            - {{ $animal->location->name }}
                        @endif
                    </p>
                </div>
                <div class="profile-tag-chip">
                    <span>Official Penzi Tag</span>
                    <strong>{{ $animal->tag_number }}</strong>
                </div>
            </div>

            <div class="profile-metrics">
                <div class="profile-metric">
                    <div class="profile-metric-label">Lifecycle Status</div>
                    <div class="profile-metric-value tone-{{ $statusTone }}">{{ $animal->status }}</div>
                    <div class="profile-metric-note">{{ $animal->sale_ready ? 'Available for sale workflow' : ($animal->is_breeder ? 'Retained for breeding' : 'Current farm record') }}</div>
                </div>
                <div class="profile-metric">
                    <div class="profile-metric-label">Breed Purity</div>
                    <div class="profile-metric-value tone-{{ $purityTone }}">{{ $purityPercent !== null ? number_format($purityPercent, 2) . '%' : 'Pending' }}</div>
                    <div class="profile-metric-note">{{ $purityLabel }}</div>
                </div>
                <div class="profile-metric">
                    <div class="profile-metric-label">Latest Weight</div>
                    <div class="profile-metric-value">{{ $weight ? number_format((float) $weight->weight_kg, 2) . ' KG' : '-' }}</div>
                    <div class="profile-metric-note tone-{{ $weightTrend === 'losing' ? 'red' : ($weightTrend === 'gaining' ? 'green' : 'gray') }}">{{ $weightLabel }}{{ $weight?->recorded_at ? ' - ' . $formatDate($weight->recorded_at) : '' }}</div>
                </div>
                <div class="profile-metric">
                    <div class="profile-metric-label">Health Administrations</div>
                    <div class="profile-metric-value">{{ $healthAdministrations->count() }}</div>
                    <div class="profile-metric-note">{{ $vaccinationCount }} vaccine / {{ $dewormingCount }} deworming entries</div>
                </div>
                <div class="profile-metric">
                    <div class="profile-metric-label">Clinical Attention</div>
                    <div class="profile-metric-value tone-{{ $openClinicalCases ? 'red' : 'green' }}">{{ $openClinicalCases }}</div>
                    <div class="profile-metric-note">Open or active sick-case record(s)</div>
                </div>
            </div>
        </section>

        <section class="profile-section">
            <div class="profile-section-heading">
                <h2>Identity & Registration Details</h2>
                <span>Core animal record</span>
            </div>
            <div class="profile-section-body profile-grid-2">
                <table class="profile-data-table">
                    <tr><td>Tag Number</td><td>{{ $animal->tag_number }}</td></tr>
                    <tr><td>Breed</td><td>{{ $animal->breed?->breed_name ?? 'Not recorded' }}</td></tr>
                    <tr><td>Species / Category</td><td>{{ $animal->species ?? 'Not recorded' }}</td></tr>
                    <tr><td>Sex</td><td>{{ $animal->sex ?? 'Not recorded' }}</td></tr>
                    <tr><td>Date of Birth</td><td>{{ $formatDate($animal->date_of_birth) }}{{ $animal->date_of_birth_is_estimated ? ' (estimated)' : '' }}</td></tr>
                    <tr><td>Current Age</td><td>{{ $ageDisplay }}</td></tr>
                    <tr><td>Source</td><td>{{ $animal->source ?? 'Not recorded' }}</td></tr>
                </table>
                <table class="profile-data-table">
                    <tr><td>Purpose</td><td>{{ $animal->purpose ?? 'Not recorded' }}</td></tr>
                    <tr><td>Current Location</td><td>{{ $animal->location?->display_name ?? $animal->location?->name ?? 'Not recorded' }}</td></tr>
                    <tr><td>Breeding Retention</td><td>{{ $animal->is_breeder ? 'Yes - retained as breeder' : 'No' }}</td></tr>
                    <tr><td>Sale Readiness</td><td>{{ $animal->sale_ready ? 'Yes' : 'No' }}</td></tr>
                    <tr><td>Valuation</td><td>{{ $animal->valuation_price !== null ? 'KES ' . number_format((float) $animal->valuation_price, 2) : 'Not recorded' }}</td></tr>
                    <tr><td>Purchase Date</td><td>{{ $animal->source === 'Purchased' ? $formatDate($animal->bought_on) : 'Born on farm' }}</td></tr>
                    <tr><td>Recorded By</td><td>{{ $animal->created_at?->format('d M Y, H:i') ?? 'Not recorded' }}</td></tr>
                </table>
            </div>
        </section>

        <section class="profile-section">
            <div class="profile-section-heading">
                <h2>Breed Purity & Pedigree Intelligence</h2>
                <span>Lineage-derived breeding confidence</span>
            </div>
            <div class="profile-section-body pedigree-layout">
                <div class="pedigree-tree">
                    <div class="pedigree-tree-header">Recorded Parentage</div>
                    <div class="pedigree-parent-row">
                        <div class="pedigree-parent-role">Sire / Father</div>
                        <div>
                            <div class="pedigree-name">{{ $animal->sire?->tag_number ?? 'Not recorded' }}</div>
                            <div class="pedigree-meta">{{ $animal->sire?->breed?->breed_name ?? 'No sire breed recorded' }}</div>
                            <div class="pedigree-ancestor-list">
                                <span><b>Paternal grandsire:</b> {{ $animal->sire?->sire?->tag_number ?? 'Not recorded' }}</span>
                                <span><b>Paternal granddam:</b> {{ $animal->sire?->dam?->tag_number ?? 'Not recorded' }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="pedigree-parent-row">
                        <div class="pedigree-parent-role">Dam / Mother</div>
                        <div>
                            <div class="pedigree-name">{{ $animal->dam?->tag_number ?? 'Not recorded' }}</div>
                            <div class="pedigree-meta">{{ $animal->dam?->breed?->breed_name ?? 'No dam breed recorded' }}</div>
                            <div class="pedigree-ancestor-list">
                                <span><b>Maternal grandsire:</b> {{ $animal->dam?->sire?->tag_number ?? 'Not recorded' }}</span>
                                <span><b>Maternal granddam:</b> {{ $animal->dam?->dam?->tag_number ?? 'Not recorded' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="profile-purity-panel">
                    <div class="profile-purity-top">
                        <div class="profile-purity-percent">{{ $purityPercent !== null ? number_format($purityPercent, 2) . '%' : '—' }}</div>
                        <div class="profile-purity-label">{{ $purityLabel }}</div>
                    </div>
                    <div class="profile-progress"><div style="width: {{ $purityPercent !== null ? min(100, max(0, $purityPercent)) : 0 }}%;"></div></div>
                    <div class="profile-purity-meta">
                        <div><span>Target Breed</span><strong>{{ $animal->purityBreed?->breed_name ?? $animal->breed?->breed_name ?? 'Not recorded' }}</strong></div>
                        <div><span>Verification Date</span><strong>{{ $animal->purity_verified_at ? $formatDate($animal->purity_verified_at) : 'Not recorded' }}</strong></div>
                        <div><span>Foundation Flag</span><strong>{{ $animal->is_foundation_animal ? 'Approved foundation' : 'Not foundation' }}</strong></div>
                        <div><span>Evidence</span><strong>{{ $animal->purity_notes ?: 'System pedigree logic' }}</strong></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="profile-section">
            <div class="profile-section-heading">
                <h2>Vaccination, Deworming & Health History</h2>
                <span>{{ $healthAdministrations->count() }} linked administration record(s)</span>
            </div>
            <div class="profile-section-body">
                @if ($healthAdministrations->isEmpty())
                    <div class="profile-empty">No Health Administrations are linked to this animal yet. Save the selected animal tags on the Health Administration record to populate this history.</div>
                @else
                    <div class="profile-table-wrap">
                        <table class="profile-table">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Product</th>
                                    <th>Date Given</th>
                                    <th>Next Due</th>
                                    <th>Dosage / Quantity</th>
                                    <th>Administered By</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($healthAdministrations as $administration)
                                    @php
                                        $productType = str($administration->product?->type ?? 'Health')->replace('_', ' ')->title()->toString();
                                    @endphp
                                    <tr>
                                        <td><span class="profile-status-pill blue">{{ $productType }}</span></td>
                                        <td><strong>{{ $administration->product?->name ?? 'Product not recorded' }}</strong></td>
                                        <td>{{ $formatDate($administration->administered_at) }}</td>
                                        <td>{{ $administration->next_due_date ? $formatDate($administration->next_due_date) : 'Not scheduled' }}</td>
                                        <td>{{ $administration->dosage_per_animal !== null ? rtrim(rtrim(number_format((float) $administration->dosage_per_animal, 2), '0'), '.') . ' per animal' : '-' }}{{ $administration->total_quantity_used !== null ? ' / total ' . rtrim(rtrim(number_format((float) $administration->total_quantity_used, 2), '0'), '.') : '' }}</td>
                                        <td>{{ $administration->administered_by ?: '-' }}</td>
                                        <td>{{ $administration->notes ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>

        <section class="profile-section">
            <div class="profile-section-heading">
                <h2>Clinical Cases, Treatments & Laboratory Requests</h2>
                <span>{{ $clinicalCases->count() }} case(s) - {{ $treatments->count() }} treatment(s) - {{ $labRequests->count() }} lab request(s)</span>
            </div>
            <div class="profile-section-body profile-grid-3">
                <div>
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:9px;"><strong style="font-size:12px;color:#213328;">Sick Cases</strong><span class="profile-record-count">{{ $clinicalCases->count() }}</span></div>
                    @forelse ($clinicalCases->take(5) as $case)
                        <div style="padding:10px 0;border-bottom:1px solid #edf0ed;">
                            <div style="display:flex;justify-content:space-between;gap:8px;"><strong style="font-size:11px;">{{ $case->case_number }}</strong><span class="profile-status-pill {{ in_array($case->status, ['Resolved', 'Closed'], true) ? 'green' : 'red' }}">{{ $case->status }}</span></div>
                            <div style="margin-top:5px;font-size:10px;color:#667267;">{{ $formatDate($case->case_date) }} - {{ $case->severity }} severity</div>
                            <div style="margin-top:4px;font-size:10px;color:#48534b;">{{ $case->clinical_signs ?: $case->diagnosis ?: 'No clinical description recorded.' }}</div>
                        </div>
                    @empty
                        <div class="profile-empty">No sick cases recorded.</div>
                    @endforelse
                </div>

                <div>
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:9px;"><strong style="font-size:12px;color:#213328;">Treatments</strong><span class="profile-record-count">{{ $treatments->count() }}</span></div>
                    @forelse ($treatments->take(5) as $treatment)
                        <div style="padding:10px 0;border-bottom:1px solid #edf0ed;">
                            <div style="display:flex;justify-content:space-between;gap:8px;"><strong style="font-size:11px;">{{ $treatment->medicine_name ?: 'Medicine not recorded' }}</strong><span class="profile-status-pill {{ $treatment->status === 'Completed' ? 'green' : 'gold' }}">{{ $treatment->status }}</span></div>
                            <div style="margin-top:5px;font-size:10px;color:#667267;">{{ $formatDate($treatment->given_at, 'd M Y, H:i') }} - {{ $treatment->dosage ?: 'No dosage' }}</div>
                            <div style="margin-top:4px;font-size:10px;color:#48534b;">{{ $treatment->method ?: 'Method not recorded' }}{{ $treatment->administered_by ? ' - ' . $treatment->administered_by : '' }}</div>
                        </div>
                    @empty
                        <div class="profile-empty">No treatment records recorded.</div>
                    @endforelse
                </div>

                <div>
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:9px;"><strong style="font-size:12px;color:#213328;">Laboratory Requests</strong><span class="profile-record-count">{{ $labRequests->count() }}</span></div>
                    @forelse ($labRequests->take(5) as $labRequest)
                        <div style="padding:10px 0;border-bottom:1px solid #edf0ed;">
                            <div style="display:flex;justify-content:space-between;gap:8px;"><strong style="font-size:11px;">{{ $labRequest->request_number }}</strong><span class="profile-status-pill {{ $labRequest->status === 'Completed' ? 'green' : 'blue' }}">{{ $labRequest->status }}</span></div>
                            <div style="margin-top:5px;font-size:10px;color:#667267;">{{ $labRequest->clinic_display_name }} - {{ $formatDate($labRequest->requested_at, 'd M Y') }}</div>
                            <div style="margin-top:4px;font-size:10px;color:#48534b;">{{ $labRequest->requested_tests_text ?: 'No requested tests recorded' }}</div>
                        </div>
                    @empty
                        <div class="profile-empty">No laboratory requests recorded.</div>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
</x-filament-panels::page>
