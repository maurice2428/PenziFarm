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
    $weightTrend = match ($latestWeight?->trend ?? 'none') {
        'gaining' => 'Gaining',
        'losing' => 'Losing',
        'stable' => 'Stable',
        'first' => 'First record',
        default => 'No weight record',
    };

    $age = 'Not recorded';
    if ($animal->date_of_birth) {
        $age = $animal->date_of_birth->diffForHumans($generatedAt, [
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

    $pedigreeNode = function ($item, string $fallback = 'Not recorded'): array {
        if (! $item) {
            return [
                'tag' => $fallback,
                'meta' => 'Parentage pending',
            ];
        }

        return [
            'tag' => $item->tag_number,
            'meta' => trim(
                ($item->breed?->breed_name ?? 'Breed not recorded')
                . ' · '
                . ($item->sex ?? 'Sex not recorded')
            ),
        ];
    };

    $healthType = function ($entry): string {
        return str($entry->product?->type ?? 'Health')
            ->replace('_', ' ')
            ->title()
            ->toString();
    };

    $statusColor = match ($animal->status) {
        'Active' => '#15803d',
        'Sold' => '#b7791f',
        'Dead', 'Culled' => '#b91c1c',
        default => '#64748b',
    };
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ strtoupper($animal->tag_number) }} Pedigree Profile</title>
    <style>
        @page {
            margin: 24px 26px 22px 26px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #152235;
            font-family: Courier, monospace;
            font-size: 9px;
            line-height: 1.35;
        }

        .page {
            position: relative;
            min-height: 1048px;
        }

        .page-break {
            page-break-before: always;
        }

        .top-rule {
            height: 5px;
            background: {{ $primaryColor }};
            margin-bottom: 10px;
        }

        .header-table,
        .data-grid,
        .health-table,
        .matrix-table,
        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .logo-cell {
            width: 74px;
            vertical-align: middle;
        }

        .logo {
            max-width: 62px;
            max-height: 62px;
        }

        .header-centre {
            text-align: center;
            vertical-align: middle;
        }

        .header-right {
            width: 215px;
            vertical-align: middle;
            text-align: right;
            font-size: 8px;
            color: #526174;
            line-height: 1.55;
        }

        .farm-name {
            color: {{ $primaryColor }};
            font-size: 16px;
            font-weight: 900;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .farm-tagline {
            margin-top: 4px;
            color: #536478;
            font-size: 9px;
        }

        .certificate-bar {
            margin-top: 10px;
            padding: 9px 10px;
            text-align: center;
            color: #ffffff;
            background: {{ $primaryColor }};
            font-size: 13px;
            font-weight: 900;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .certificate-sub {
            margin: 6px 0 11px;
            text-align: center;
            color: #607087;
            font-size: 8.5px;
        }

        .section-title {
            margin: 11px 0 6px;
            padding-bottom: 4px;
            color: {{ $secondaryColor }};
            border-bottom: 1px solid #cbd7cb;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .data-grid td {
            width: 33.333%;
            padding: 5px 7px;
            border: 1px solid #c9d3c6;
            vertical-align: top;
        }

        .detail-key {
            display: block;
            color: #708095;
            font-size: 7px;
            font-weight: 900;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .detail-value {
            display: block;
            margin-top: 3px;
            color: #152235;
            font-size: 9px;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .chip {
            display: inline-block;
            padding: 2px 5px;
            color: #ffffff;
            background: {{ $primaryColor }};
            font-size: 7px;
            font-weight: 900;
        }

        .pedigree-canvas {
            position: relative;
            height: 360px;
            overflow: hidden;
            border: 1px solid #c8d4c7;
            background:
                linear-gradient(90deg, rgba(20,83,45,.035) 1px, transparent 1px),
                linear-gradient(rgba(20,83,45,.035) 1px, transparent 1px),
                #fcfffb;
            background-size: 18px 18px;
        }

        .node {
            position: absolute;
            width: 20%;
            min-height: 61px;
            padding: 7px;
            border: 1px solid #7f907f;
            border-left: 4px solid {{ $primaryColor }};
            background: #ffffff;
            z-index: 2;
        }

        .node.target {
            width: 23%;
            border-left-color: {{ $accentColor }};
            background: #fffdf5;
        }

        .node-label {
            color: #718096;
            font-size: 6.7px;
            font-weight: 900;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .node-tag {
            margin-top: 4px;
            color: #101827;
            font-size: 9px;
            font-weight: 900;
            overflow-wrap: anywhere;
        }

        .node-meta {
            margin-top: 3px;
            color: #64748b;
            font-size: 7px;
            line-height: 1.25;
        }

        .line-h,
        .line-v {
            position: absolute;
            background: #647768;
            z-index: 1;
        }

        .line-h { height: 1px; }
        .line-v { width: 1px; }

        .legend {
            margin: 5px 0 0;
            color: #607087;
            font-size: 7px;
            text-align: right;
        }

        .insight {
            margin-top: 8px;
            padding: 7px 9px;
            border-left: 4px solid {{ $accentColor }};
            background: #fffdf5;
            color: #364152;
            font-size: 8px;
        }

        .health-table th,
        .matrix-table th {
            padding: 5px;
            color: #ffffff;
            background: {{ $primaryColor }};
            border: 1px solid {{ $primaryColor }};
            text-align: left;
            font-size: 7px;
            letter-spacing: .03em;
        }

        .health-table td,
        .matrix-table td {
            padding: 5px;
            border: 1px solid #d7e0d5;
            vertical-align: top;
            font-size: 7.3px;
            line-height: 1.3;
        }

        .health-table tr:nth-child(even) td,
        .matrix-table tr:nth-child(even) td {
            background: #fbfdf9;
        }

        .empty {
            color: #718096;
            text-align: center;
            padding: 9px !important;
        }

        .mini-card {
            padding: 7px;
            border: 1px solid #d3ded1;
            background: #fbfdf9;
        }

        .mini-card-title {
            color: {{ $secondaryColor }};
            font-size: 8px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .mini-card-value {
            margin-top: 5px;
            color: #101827;
            font-size: 13px;
            font-weight: 900;
        }

        .mini-card-note {
            margin-top: 3px;
            color: #6b7a8d;
            font-size: 7px;
        }

        .signature-box {
            margin-top: 12px;
            padding: 8px;
            border: 1px solid #c9d3c6;
            background: #fbfdf9;
        }

        .signature-line {
            width: 68%;
            margin-top: 23px;
            padding-top: 4px;
            border-top: 1px solid #506051;
            color: #536478;
            font-size: 7px;
        }

        .footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            color: #68778a;
            font-size: 7px;
        }

        .footer-table td {
            padding-top: 7px;
            border-top: 1px solid #cbd7cb;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="top-rule"></div>

        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    @if ($logoBase64)
                        <img src="{{ $logoBase64 }}" class="logo" alt="Penzi Farm">
                    @endif
                </td>
                <td class="header-centre">
                    <div class="farm-name">{{ $farmName }}</div>
                    <div class="farm-tagline">{{ $farmTagline }}</div>
                </td>
                <td class="header-right">
                    {{ $farmCounty }}<br>
                    {{ $farmPhone }}<br>
                    {{ $farmEmail }}
                </td>
            </tr>
        </table>

        <div class="certificate-bar">
            {{ strtoupper($breedName) }} · Pedigree & Breeding Profile
        </div>

        <div class="certificate-sub">
            Farm-bred livestock record · generated from live Penzi Farm ERP data ·
            profile reference {{ strtoupper($animal->tag_number) }}
        </div>

        <div class="section-title">Animal identity & breeding record</div>

        <table class="data-grid">
            <tr>
                <td><span class="detail-key">Animal tag</span><span class="detail-value">{{ $animal->tag_number }}</span></td>
                <td><span class="detail-key">Breed / Species</span><span class="detail-value">{{ $breedName }} · {{ $animal->species }}</span></td>
                <td><span class="detail-key">Sex / Current status</span><span class="detail-value">{{ $animal->sex }} · <span class="chip" style="background:{{ $statusColor }};">{{ $animal->status }}</span></span></td>
            </tr>
            <tr>
                <td><span class="detail-key">Date of birth</span><span class="detail-value">{{ $animal->date_of_birth?->format('d M Y') ?? 'Not recorded' }}</span></td>
                <td><span class="detail-key">Age</span><span class="detail-value">{{ $age }}</span></td>
                <td><span class="detail-key">Location</span><span class="detail-value">{{ $animal->location?->display_name ?? $animal->location?->name ?? 'Not recorded' }}</span></td>
            </tr>
            <tr>
                <td><span class="detail-key">Purity target breed</span><span class="detail-value">{{ $purityBreedName }}</span></td>
                <td><span class="detail-key">Purity</span><span class="detail-value">{{ $purityPercent }} · {{ $purityLabel }}</span></td>
                <td><span class="detail-key">Latest weight</span><span class="detail-value">{{ $latestWeight ? number_format((float) $latestWeight->weight_kg, 2) . ' KG · ' . $weightTrend : 'No weight record' }}</span></td>
            </tr>
            <tr>
                <td><span class="detail-key">Source</span><span class="detail-value">{{ $animal->source ?? 'Not recorded' }}</span></td>
                <td><span class="detail-key">Purpose</span><span class="detail-value">{{ $animal->purpose ?? 'Not recorded' }}</span></td>
                <td><span class="detail-key">Valuation</span><span class="detail-value">{{ $animal->valuation_price !== null ? 'KES ' . number_format((float) $animal->valuation_price, 2) : 'Not recorded' }}</span></td>
            </tr>
        </table>

        <div class="section-title">Four-grandparent heredity chart</div>

        <div class="pedigree-canvas">
            <div class="node target" style="left:3%; top:149px;">
                <div class="node-label">Selected animal</div>
                <div class="node-tag">{{ $animal->tag_number }}</div>
                <div class="node-meta">{{ $breedName }} · {{ $animal->sex }}<br>{{ $animal->date_of_birth?->format('d M Y') ?? 'DOB not recorded' }}</div>
            </div>

            <div class="node" style="left:34%; top:70px;">
                <div class="node-label">Sire</div>
                <div class="node-tag">{{ $pedigreeNode($sire)['tag'] }}</div>
                <div class="node-meta">{{ $pedigreeNode($sire)['meta'] }}</div>
            </div>

            <div class="node" style="left:34%; top:234px;">
                <div class="node-label">Dam</div>
                <div class="node-tag">{{ $pedigreeNode($dam)['tag'] }}</div>
                <div class="node-meta">{{ $pedigreeNode($dam)['meta'] }}</div>
            </div>

            <div class="node" style="left:70%; top:15px;">
                <div class="node-label">Paternal grandsire</div>
                <div class="node-tag">{{ $pedigreeNode($sireSire)['tag'] }}</div>
                <div class="node-meta">{{ $pedigreeNode($sireSire)['meta'] }}</div>
            </div>

            <div class="node" style="left:70%; top:98px;">
                <div class="node-label">Paternal granddam</div>
                <div class="node-tag">{{ $pedigreeNode($sireDam)['tag'] }}</div>
                <div class="node-meta">{{ $pedigreeNode($sireDam)['meta'] }}</div>
            </div>

            <div class="node" style="left:70%; top:196px;">
                <div class="node-label">Maternal grandsire</div>
                <div class="node-tag">{{ $pedigreeNode($damSire)['tag'] }}</div>
                <div class="node-meta">{{ $pedigreeNode($damSire)['meta'] }}</div>
            </div>

            <div class="node" style="left:70%; top:279px;">
                <div class="node-label">Maternal granddam</div>
                <div class="node-tag">{{ $pedigreeNode($damDam)['tag'] }}</div>
                <div class="node-meta">{{ $pedigreeNode($damDam)['meta'] }}</div>
            </div>

            <div class="line-h" style="left:26%; top:180px; width:8%;"></div>
            <div class="line-v" style="left:34%; top:100px; height:165px;"></div>
            <div class="line-h" style="left:34%; top:100px; width:3%;"></div>
            <div class="line-h" style="left:34%; top:265px; width:3%;"></div>

            <div class="line-h" style="left:54%; top:100px; width:10%;"></div>
            <div class="line-v" style="left:64%; top:47px; height:105px;"></div>
            <div class="line-h" style="left:64%; top:47px; width:6%;"></div>
            <div class="line-h" style="left:64%; top:130px; width:6%;"></div>

            <div class="line-h" style="left:54%; top:265px; width:10%;"></div>
            <div class="line-v" style="left:64%; top:227px; height:105px;"></div>
            <div class="line-h" style="left:64%; top:227px; width:6%;"></div>
            <div class="line-h" style="left:64%; top:310px; width:6%;"></div>
        </div>

        <div class="legend">
            Relationship lines show direct sire and dam lineage. Blank parent nodes indicate data not yet recorded.
        </div>

        <div class="insight">
            <strong>Breeding intelligence:</strong>
            {{ $animal->is_foundation_animal ? 'This animal is an approved foundation record at 100.00% purity.' : (
                $animal->breed_purity_percent !== null
                    ? 'Recorded purity is ' . number_format((float) $animal->breed_purity_percent, 2) . '% for ' . $purityBreedName . '.'
                    : 'Purity awaits verified parentage or an approved verification record.'
            ) }}
            {{ $animal->is_breeder ? 'It is currently retained in the breeding pool.' : ($animal->sale_ready ? 'It is marked sale ready.' : '') }}
        </div>

        <div class="signature-box">
            <strong>Prepared for farm management and breeding decisions</strong><br>
            Generated by {{ $generatedBy?->name ?? 'System' }} ({{ $generatedByRole }}) on {{ $generatedAt->format('d M Y, H:i') }} EAT.
            <div class="signature-line">
                {{ $farmLegalName }} · authorised farm record
            </div>
        </div>

        <div class="footer">
            <table class="footer-table">
                <tr>
                    <td style="text-align:left;">{{ $farmName }} · {{ $farmCounty }}</td>
                    <td style="text-align:center;">{{ strtoupper($animal->tag_number) }} Pedigree Profile</td>
                    <td style="text-align:right;">Page 1 of 2</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="page page-break">
        <div class="top-rule"></div>

        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    @if ($logoBase64)
                        <img src="{{ $logoBase64 }}" class="logo" alt="Penzi Farm">
                    @endif
                </td>
                <td class="header-centre">
                    <div class="farm-name">{{ $farmName }}</div>
                    <div class="farm-tagline">{{ strtoupper($animal->tag_number) }} · Health, treatment & laboratory record</div>
                </td>
                <td class="header-right">
                    {{ $farmPhone }}<br>
                    {{ $farmEmail }}<br>
                    Generated {{ $generatedAt->format('d M Y H:i') }} EAT
                </td>
            </tr>
        </table>

        <div class="section-title">Vaccination, deworming & health administration history · latest {{ min(10, $healthAdministrationCount) }} of {{ $healthAdministrationCount }}</div>

        <table class="health-table">
            <thead>
                <tr>
                    <th style="width:12%;">Date</th>
                    <th style="width:13%;">Type</th>
                    <th style="width:25%;">Product</th>
                    <th style="width:16%;">Dosage</th>
                    <th style="width:14%;">Next due</th>
                    <th style="width:20%;">Notes / attendant</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($healthAdministrations as $entry)
                    <tr>
                        <td>{{ $entry->administered_at?->format('d M Y') ?? '—' }}</td>
                        <td>{{ $healthType($entry) }}</td>
                        <td>{{ $entry->product?->name ?? 'Product not recorded' }}</td>
                        <td>
                            {{ $entry->dosage_per_animal !== null ? $entry->dosage_per_animal . ' / animal' : '—' }}
                            @if ($entry->total_quantity_used !== null)
                                <br><span style="color:#6b7a8d;">Total {{ $entry->total_quantity_used }}</span>
                            @endif
                        </td>
                        <td>{{ $entry->next_due_date?->format('d M Y') ?? '—' }}</td>
                        <td>{{ $entry->administered_by ?? '—' }}<br><span style="color:#6b7a8d;">{{ \Illuminate\Support\Str::limit($entry->notes ?? '', 60) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty">No health-administration records are linked to this animal.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="section-title">Clinical cases, treatment & laboratory workflow</div>

        <table class="matrix-table">
            <thead>
                <tr>
                    <th style="width:31%;">Clinical cases · latest {{ min(5, $clinicalCaseCount) }} of {{ $clinicalCaseCount }}</th>
                    <th style="width:31%;">Treatments · latest {{ min(5, $treatmentCount) }} of {{ $treatmentCount }}</th>
                    <th style="width:38%;">Laboratory requests · latest {{ min(5, $labRequestCount) }} of {{ $labRequestCount }}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        @forelse ($clinicalCases as $case)
                            <strong>{{ $case->case_number }}</strong><br>
                            {{ $case->case_date?->format('d M Y') }} · {{ $case->severity }} · {{ $case->status }}<br>
                            <span style="color:#627185;">{{ \Illuminate\Support\Str::limit($case->diagnosis ?: $case->clinical_signs ?: 'No diagnosis recorded', 105) }}</span>
                            <br><br>
                        @empty
                            <span style="color:#718096;">No sick-case records.</span>
                        @endforelse
                    </td>
                    <td>
                        @forelse ($treatments as $treatment)
                            <strong>{{ $treatment->medicine_name ?? 'Medicine not recorded' }}</strong><br>
                            {{ $treatment->given_at?->format('d M Y') }} · {{ $treatment->status }}<br>
                            <span style="color:#627185;">{{ \Illuminate\Support\Str::limit($treatment->dosage ?: $treatment->notes ?: 'No dosage / notes recorded', 105) }}</span>
                            <br><br>
                        @empty
                            <span style="color:#718096;">No treatment records.</span>
                        @endforelse
                    </td>
                    <td>
                        @forelse ($labRequests as $lab)
                            <strong>{{ $lab->request_number }}</strong><br>
                            {{ $lab->clinic_display_name }} · {{ $lab->status }}<br>
                            <span style="color:#627185;">
                                Requested {{ $lab->requested_at?->format('d M Y') ?? '—' }} ·
                                {{ \Illuminate\Support\Str::limit($lab->requested_tests_text ?: $lab->results ?: 'No test/result recorded', 100) }}
                            </span>
                            <br><br>
                        @empty
                            <span style="color:#718096;">No laboratory requests.</span>
                        @endforelse
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="section-title">Director-level care summary</div>
        <table class="data-grid">
            <tr>
                <td>
                    <span class="detail-key">Open clinical cases</span>
                    <span class="detail-value">{{ $animal->clinicalCases->whereIn('status', ['Open', 'Under Treatment', 'Referred'])->count() }}</span>
                </td>
                <td>
                    <span class="detail-key">Laboratory items in progress</span>
                    <span class="detail-value">{{ $animal->labRequests->whereIn('status', ['Requested', 'Sample Collected', 'Dispatched', 'In Progress'])->count() }}</span>
                </td>
                <td>
                    <span class="detail-key">Profile verification</span>
                    <span class="detail-value">{{ $purityPercent }} {{ $purityBreedName }} · {{ $purityLabel }}</span>
                </td>
            </tr>
        </table>

        <div class="insight">
            <strong>Management note:</strong>
            The certificate displays the latest operational records to remain within two compact pages. The live Animal Profile contains the full history, attachments and linked records.
        </div>

        <table style="width:100%; margin-top:14px; border-collapse:collapse;">
            <tr>
                <td style="width:67%; vertical-align:top;">
                    <div class="signature-box">
                        <strong>Authorised farm record</strong><br>
                        {{ $farmLegalName }} · prepared by {{ $generatedBy?->name ?? 'System' }} ({{ $generatedByRole }})
                        <div class="signature-line">Signature / authorisation</div>
                    </div>
                </td>
                <td style="width:33%; vertical-align:top; text-align:center;">
                    @if ($qrImage)
                        <img src="{{ $qrImage }}" style="width:82px; height:82px; border:1px solid #c9d3c6; padding:3px;" alt="Profile QR">
                        <div style="margin-top:3px; color:#6b7a8d; font-size:7px;">Scan for live profile</div>
                    @else
                        <div style="padding:25px 8px; border:1px solid #c9d3c6; color:#718096;">QR unavailable</div>
                    @endif
                </td>
            </tr>
        </table>

        <div class="footer">
            <table class="footer-table">
                <tr>
                    <td style="text-align:left;">{{ $farmName }} · {{ $farmCounty }}</td>
                    <td style="text-align:center;">{{ strtoupper($animal->tag_number) }} Pedigree Profile</td>
                    <td style="text-align:right;">Page 2 of 2</td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
