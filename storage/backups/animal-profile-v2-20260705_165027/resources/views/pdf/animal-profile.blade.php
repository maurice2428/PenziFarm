@php
    $formatDate = static function ($value, string $format = 'd M Y'): string {
        return blank($value)
            ? 'Not recorded'
            : \Carbon\Carbon::parse($value)->format($format);
    };

    $ageDisplay = 'Not recorded';
    if (filled($animal->date_of_birth)) {
        $dob = \Carbon\Carbon::parse($animal->date_of_birth);
        $ageDisplay = $dob->isFuture()
            ? 'Invalid date of birth'
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
    $weightTrend = match ($weight?->trend) {
        'gaining' => 'Gaining',
        'losing' => 'Losing',
        'stable' => 'Stable',
        'first' => 'Baseline',
        default => 'No weight record',
    };

    $statusColor = match ($animal->status) {
        'Active' => $successColor,
        'Sold' => $accentColor,
        'Dead' => $dangerColor,
        default => '#6b7280',
    };
@endphp

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $animal->tag_number }} Pedigree and Animal Profile</title>
    <style>
        @page { margin: 20px 24px 22px 24px; }
        * { box-sizing: border-box; }
        body { font-family: Courier, monospace; color: #1f2937; font-size: 9px; margin: 0; }
        .page { min-height: 1040px; position: relative; }
        .page-break { page-break-after: always; }
        .header { border-top: 5px solid {{ $primaryColor }}; border-bottom: 1px solid #cfd9d0; padding: 10px 0 9px; }
        .header-table, .identity-table, .pedigree-table, .health-table, .mini-table, .footer-table { width: 100%; border-collapse: collapse; }
        .header-logo { width: 72px; vertical-align: middle; }
        .logo { max-width: 66px; max-height: 48px; object-fit: contain; }
        .header-center { text-align: center; vertical-align: middle; }
        .header-right { width: 205px; text-align: right; vertical-align: middle; color: #5f6d62; font-size: 8px; line-height: 1.55; }
        .farm-name { color: {{ $primaryColor }}; font-size: 15px; font-weight: bold; letter-spacing: .03em; }
        .farm-tagline { color: #6b7280; font-size: 8px; margin-top: 3px; }
        .breed-title { margin-top: 18px; text-align: center; color: #111827; font-size: 20px; font-weight: bold; letter-spacing: .08em; }
        .certificate-title { margin-top: 4px; text-align: center; color: {{ $primaryColor }}; font-size: 10px; font-weight: bold; letter-spacing: .11em; }
        .certificate-subtitle { margin-top: 4px; text-align: center; color: #6b7280; font-size: 8px; }
        .section-title { margin: 15px 0 7px; padding: 7px 9px; color: #fff; background: {{ $primaryColor }}; font-size: 9px; font-weight: bold; letter-spacing: .08em; text-transform: uppercase; }
        .subsection-title { margin: 13px 0 6px; color: {{ $secondaryColor }}; font-size: 9px; font-weight: bold; letter-spacing: .06em; text-transform: uppercase; }
        .identity-table td { border: 1px solid #cfd9d0; padding: 7px 8px; vertical-align: top; }
        .identity-label { width: 28%; color: #536258; background: #f7faf7; font-size: 8px; font-weight: bold; text-transform: uppercase; }
        .identity-value { color: #1f2937; font-size: 9px; font-weight: bold; }
        .quick-panel { border: 1px solid #cfd9d0; padding: 10px; min-height: 276px; }
        .quick-title { color: {{ $secondaryColor }}; font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: .07em; }
        .qr-wrap { margin: 12px auto 7px; width: 104px; padding: 5px; text-align: center; border: 1px solid {{ $primaryColor }}; }
        .qr-wrap img { width: 90px; height: 90px; }
        .qr-caption { text-align: center; color: #6b7280; font-size: 7px; }
        .purity-value { margin-top: 15px; color: {{ $primaryColor }}; text-align: center; font-size: 24px; font-weight: bold; }
        .purity-label { margin-top: 2px; text-align: center; color: #536258; font-size: 8px; }
        .progress { margin-top: 7px; height: 8px; background: #dfe9df; }
        .progress-fill { height: 8px; background: {{ $successColor }}; }
        .quick-stat { margin-top: 10px; padding-top: 8px; border-top: 1px solid #edf1ed; font-size: 8px; line-height: 1.55; }
        .quick-stat b { color: #26342a; }
        .pedigree-table th { border: 1px solid #cfd9d0; padding: 6px; background: #f1f6f1; color: #425246; text-align: left; font-size: 7.5px; text-transform: uppercase; }
        .pedigree-table td { border: 1px solid #d9e1da; padding: 7px; vertical-align: top; font-size: 8px; }
        .pedigree-role { color: {{ $primaryColor }}; font-weight: bold; }
        .pedigree-main { font-size: 9px; font-weight: bold; color: #1f2937; }
        .pedigree-meta { margin-top: 3px; color: #6b7280; font-size: 7.5px; }
        .note { margin-top: 9px; padding: 8px 10px; border-left: 4px solid {{ $accentColor }}; background: #fffaf0; color: #6a5630; line-height: 1.55; font-size: 8px; }
        .health-table th, .mini-table th { border: 1px solid #cfd9d0; padding: 6px 5px; background: #f1f6f1; color: #425246; text-align: left; font-size: 7px; text-transform: uppercase; }
        .health-table td, .mini-table td { border: 1px solid #d9e1da; padding: 6px 5px; vertical-align: top; font-size: 7.5px; line-height: 1.35; }
        .health-table tr:nth-child(even) td, .mini-table tr:nth-child(even) td { background: #fbfdfb; }
        .pill { display: inline-block; padding: 2px 5px; color: #fff; font-size: 7px; font-weight: bold; background: {{ $secondaryColor }}; }
        .pill-status { background: {{ $statusColor }}; }
        .two-col { width: 100%; border-collapse: collapse; }
        .two-col > tbody > tr > td { width: 50%; vertical-align: top; }
        .two-col > tbody > tr > td:first-child { padding-right: 5px; }
        .two-col > tbody > tr > td:last-child { padding-left: 5px; }
        .record-card { border: 1px solid #d9e1da; padding: 7px; margin-bottom: 6px; min-height: 57px; }
        .record-card:last-child { margin-bottom: 0; }
        .record-title { color: #1f2937; font-size: 8px; font-weight: bold; }
        .record-meta { margin-top: 3px; color: #68766b; font-size: 7px; }
        .record-note { margin-top: 3px; color: #4b5a4e; font-size: 7px; line-height: 1.35; }
        .empty { border: 1px dashed #cfd9d0; padding: 12px; text-align: center; color: #748078; font-size: 8px; }
        .footer { position: absolute; left: 0; right: 0; bottom: 0; border-top: 1px solid #cfd9d0; padding-top: 7px; color: #6b7280; font-size: 7px; }
        .footer-table td { vertical-align: top; }
        .footer-center { text-align: center; }
        .footer-right { text-align: right; }
        .signature-line { border-top: 1px solid #7b8580; margin: 15px 0 4px; width: 180px; }
        .signature-title { color: {{ $primaryColor }}; font-size: 8px; font-weight: bold; }
        .summary-strip { margin-top: 10px; border: 1px solid #cfd9d0; }
        .summary-strip td { width: 25%; padding: 8px; border-right: 1px solid #cfd9d0; text-align: center; }
        .summary-strip td:last-child { border-right: 0; }
        .summary-label { color: #69766d; font-size: 7px; text-transform: uppercase; font-weight: bold; }
        .summary-value { margin-top: 4px; color: {{ $primaryColor }}; font-size: 13px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="page page-break">
        <div class="header">
            <table class="header-table">
                <tr>
                    <td class="header-logo">
                        @if ($logoBase64)
                            <img class="logo" src="{{ $logoBase64 }}" alt="{{ $farmName }}">
                        @else
                            <strong style="color: {{ $primaryColor }}; font-size: 12px;">PF</strong>
                        @endif
                    </td>
                    <td class="header-center">
                        <div class="farm-name">{{ $farmName }}</div>
                        <div class="farm-tagline">{{ $farmTagline }}</div>
                    </td>
                    <td class="header-right">
                        {{ $farmCounty }}<br>
                        {{ $farmEmail }} | {{ $farmPhone }}
                    </td>
                </tr>
            </table>
        </div>

        <div class="breed-title">{{ strtoupper($animal->breed?->breed_name ?? $animal->species ?? 'ANIMAL') }}</div>
        <div class="certificate-title">PEDIGREE & ANIMAL PROFILE CERTIFICATE</div>
        <div class="certificate-subtitle">Penzi Farm internal animal record and lineage intelligence document</div>

        <table style="width:100%; border-collapse:collapse; margin-top:15px;">
            <tr>
                <td style="width:62%; vertical-align:top; padding-right:7px;">
                    <table class="identity-table">
                        <tr><td class="identity-label">Animal Identification Tag</td><td class="identity-value">{{ $animal->tag_number }}</td></tr>
                        <tr><td class="identity-label">Breed / Category</td><td class="identity-value">{{ $animal->breed?->breed_name ?? 'Not recorded' }} / {{ $animal->species ?? 'Not recorded' }}</td></tr>
                        <tr><td class="identity-label">Sex / Purpose</td><td class="identity-value">{{ $animal->sex ?? 'Not recorded' }} / {{ $animal->purpose ?? 'Not recorded' }}</td></tr>
                        <tr><td class="identity-label">Birth Date / Age</td><td class="identity-value">{{ $formatDate($animal->date_of_birth) }}{{ $animal->date_of_birth_is_estimated ? ' (estimated)' : '' }} / {{ $ageDisplay }}</td></tr>
                        <tr><td class="identity-label">Owner / Location</td><td class="identity-value">{{ $farmLegalName }} / {{ $animal->location?->display_name ?? $animal->location?->name ?? 'Not recorded' }}</td></tr>
                        <tr><td class="identity-label">Source / Purchase Detail</td><td class="identity-value">{{ $animal->source ?? 'Not recorded' }}{{ $animal->source === 'Purchased' && $animal->bought_from ? ' - ' . $animal->bought_from : '' }}</td></tr>
                        <tr><td class="identity-label">Lifecycle Status</td><td class="identity-value"><span class="pill pill-status">{{ strtoupper($animal->status) }}</span></td></tr>
                    </table>
                </td>
                <td style="width:38%; vertical-align:top; padding-left:7px;">
                    <div class="quick-panel">
                        <div class="quick-title">Quick Access & Genetic Status</div>
                        @if ($qrImage)
                            <div class="qr-wrap"><img src="{{ $qrImage }}" alt="Profile QR"></div>
                            <div class="qr-caption">Scan to open the authenticated animal profile</div>
                        @else
                            <div class="qr-wrap" style="padding:35px 5px; color:#6b7280;">QR unavailable</div>
                        @endif
                        <div class="purity-value">{{ $purityPercent !== null ? number_format($purityPercent, 2) . '%' : 'PENDING' }}</div>
                        <div class="purity-label">{{ $purityLabel }} - {{ $animal->purityBreed?->breed_name ?? $animal->breed?->breed_name ?? 'Not recorded' }}</div>
                        <div class="progress"><div class="progress-fill" style="width: {{ $purityPercent !== null ? min(100, max(0, $purityPercent)) : 0 }}%;"></div></div>
                        <div class="quick-stat"><b>Latest weight:</b> {{ $weight ? number_format((float) $weight->weight_kg, 2) . ' KG' : 'Not recorded' }}<br><b>Trend:</b> {{ $weightTrend }}<br><b>Recorded:</b> {{ $weight?->recorded_at ? $formatDate($weight->recorded_at, 'd M Y') : 'Not recorded' }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <table class="summary-strip">
            <tr>
                <td><div class="summary-label">Foundation Status</div><div class="summary-value">{{ $animal->is_foundation_animal ? 'YES' : 'NO' }}</div></td>
                <td><div class="summary-label">Breeder</div><div class="summary-value">{{ $animal->is_breeder ? 'YES' : 'NO' }}</div></td>
                <td><div class="summary-label">Sale Ready</div><div class="summary-value">{{ $animal->sale_ready ? 'YES' : 'NO' }}</div></td>
                <td><div class="summary-label">Valuation</div><div class="summary-value">{{ $animal->valuation_price !== null ? 'KES ' . number_format((float) $animal->valuation_price, 0) : '-' }}</div></td>
            </tr>
        </table>

        <div class="section-title">Recorded Pedigree & Lineage</div>
        <table class="pedigree-table">
            <thead>
                <tr>
                    <th width="17%">Relationship</th>
                    <th width="25%">Animal Tag</th>
                    <th width="19%">Breed</th>
                    <th width="19%">Sire Line</th>
                    <th width="20%">Dam Line</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="pedigree-role">Animal</td>
                    <td><div class="pedigree-main">{{ $animal->tag_number }}</div><div class="pedigree-meta">{{ $animal->sex }} - {{ $animal->status }}</div></td>
                    <td>{{ $animal->breed?->breed_name ?? 'Not recorded' }}</td>
                    <td>{{ $animal->sire?->tag_number ?? 'Not recorded' }}</td>
                    <td>{{ $animal->dam?->tag_number ?? 'Not recorded' }}</td>
                </tr>
                <tr>
                    <td class="pedigree-role">Sire / Father</td>
                    <td><div class="pedigree-main">{{ $animal->sire?->tag_number ?? 'Not recorded' }}</div></td>
                    <td>{{ $animal->sire?->breed?->breed_name ?? 'Not recorded' }}</td>
                    <td>{{ $animal->sire?->sire?->tag_number ?? 'Not recorded' }}</td>
                    <td>{{ $animal->sire?->dam?->tag_number ?? 'Not recorded' }}</td>
                </tr>
                <tr>
                    <td class="pedigree-role">Dam / Mother</td>
                    <td><div class="pedigree-main">{{ $animal->dam?->tag_number ?? 'Not recorded' }}</div></td>
                    <td>{{ $animal->dam?->breed?->breed_name ?? 'Not recorded' }}</td>
                    <td>{{ $animal->dam?->sire?->tag_number ?? 'Not recorded' }}</td>
                    <td>{{ $animal->dam?->dam?->tag_number ?? 'Not recorded' }}</td>
                </tr>
            </tbody>
        </table>

        <div class="note">
            <b>Purity interpretation:</b> {{ $purityLabel }}. This result is derived from the current Penzi Farm animal record, recorded parentage, approved foundation status and any verified purity evidence. It is an internal farm management record and not an external stud-book registration certificate.
        </div>

        <div style="margin-top:18px; width:48%;">
            <div class="signature-line"></div>
            <div class="signature-title">{{ $farmLegalName }} Management</div>
            <div style="color:#6b7280;font-size:7px;margin-top:3px;">Authorised animal profile verification</div>
        </div>

        <div class="footer">
            <table class="footer-table"><tr>
                <td>Generated {{ $generatedAt->format('d M Y, H:i') }} EAT</td>
                <td class="footer-center">{{ $animal->tag_number }} - Pedigree & Animal Profile</td>
                <td class="footer-right">Page 1 of 2</td>
            </tr></table>
        </div>
    </div>

    <div class="page">
        <div class="header">
            <table class="header-table"><tr>
                <td class="header-logo">
                    @if ($logoBase64)<img class="logo" src="{{ $logoBase64 }}" alt="{{ $farmName }}">@else <strong style="color:{{ $primaryColor }};font-size:12px;">PF</strong> @endif
                </td>
                <td class="header-center"><div class="farm-name">{{ $farmName }}</div><div class="farm-tagline">{{ strtoupper($animal->breed?->breed_name ?? $animal->species ?? 'Animal') }} - {{ $animal->tag_number }} Health & Operational Record</div></td>
                <td class="header-right">{{ $farmEmail }}<br>{{ $farmPhone }}</td>
            </tr></table>
        </div>

        <div class="section-title">Vaccination, Deworming & Health Administration History</div>
        @if ($healthAdministrations->isEmpty())
            <div class="empty">No linked Health Administration record exists for this animal.</div>
        @else
            <table class="health-table">
                <thead><tr><th width="13%">Type</th><th width="26%">Product</th><th width="13%">Date Given</th><th width="14%">Next Due</th><th width="16%">Dosage / Quantity</th><th width="18%">Notes</th></tr></thead>
                <tbody>
                    @foreach ($healthAdministrations as $administration)
                        @php $productType = str($administration->product?->type ?? 'Health')->replace('_', ' ')->title()->toString(); @endphp
                        <tr>
                            <td><span class="pill">{{ $productType }}</span></td>
                            <td><b>{{ $administration->product?->name ?? 'Product not recorded' }}</b><br><span style="color:#6b7280;">{{ $administration->administered_by ?: 'Officer not recorded' }}</span></td>
                            <td>{{ $formatDate($administration->administered_at) }}</td>
                            <td>{{ $administration->next_due_date ? $formatDate($administration->next_due_date) : 'Not scheduled' }}</td>
                            <td>{{ $administration->dosage_per_animal !== null ? rtrim(rtrim(number_format((float) $administration->dosage_per_animal, 2), '0'), '.') . ' per animal' : '-' }}{{ $administration->total_quantity_used !== null ? ' / Total: ' . rtrim(rtrim(number_format((float) $administration->total_quantity_used, 2), '0'), '.') : '' }}</td>
                            <td>{{ $administration->notes ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($healthAdministrationCount > $healthAdministrations->count())
                <div style="margin-top:5px;color:#6b7280;font-size:7px;">Showing the most recent {{ $healthAdministrations->count() }} of {{ $healthAdministrationCount }} linked health administration records. The live animal profile contains the full history.</div>
            @endif
        @endif

        <table class="two-col" style="margin-top:12px;"><tr>
            <td>
                <div class="subsection-title">Clinical Cases ({{ $clinicalCaseCount }})</div>
                @forelse ($clinicalCases as $case)
                    <div class="record-card">
                        <div class="record-title">{{ $case->case_number }} - {{ $case->status }}</div>
                        <div class="record-meta">{{ $formatDate($case->case_date, 'd M Y, H:i') }} | {{ $case->severity }} severity | {{ $case->attending_officer ?: 'Officer not recorded' }}</div>
                        <div class="record-note">{{ $case->clinical_signs ?: $case->diagnosis ?: 'No clinical signs or diagnosis recorded.' }}</div>
                    </div>
                @empty
                    <div class="empty">No clinical cases recorded.</div>
                @endforelse
            </td>
            <td>
                <div class="subsection-title">Treatment Records ({{ $treatmentCount }})</div>
                @forelse ($treatments as $treatment)
                    <div class="record-card">
                        <div class="record-title">{{ $treatment->medicine_name ?: 'Medicine not recorded' }} - {{ $treatment->status }}</div>
                        <div class="record-meta">{{ $formatDate($treatment->given_at, 'd M Y, H:i') }} | {{ $treatment->dosage ?: 'Dosage not recorded' }} | {{ $treatment->method ?: 'Method not recorded' }}</div>
                        <div class="record-note">{{ $treatment->notes ?: ($treatment->clinicalCase?->case_number ? 'Linked case: ' . $treatment->clinicalCase->case_number : 'No treatment notes recorded.') }}</div>
                    </div>
                @empty
                    <div class="empty">No treatment records recorded.</div>
                @endforelse
            </td>
        </tr></table>

        <div class="subsection-title">Laboratory Requests & Results ({{ $labRequestCount }})</div>
        @if ($labRequests->isEmpty())
            <div class="empty">No laboratory requests recorded.</div>
        @else
            <table class="mini-table">
                <thead><tr><th width="17%">Request</th><th width="16%">Clinic</th><th width="14%">Dates</th><th width="20%">Requested Tests</th><th width="15%">Results</th><th width="18%">Recommended Action</th></tr></thead>
                <tbody>
                    @foreach ($labRequests as $labRequest)
                        <tr>
                            <td><b>{{ $labRequest->request_number }}</b><br><span class="pill">{{ $labRequest->status }}</span></td>
                            <td>{{ $labRequest->clinic_display_name }}</td>
                            <td>Requested: {{ $formatDate($labRequest->requested_at) }}<br>Sample: {{ $labRequest->sample_collected_at ? $formatDate($labRequest->sample_collected_at) : '-' }}<br>Result: {{ $labRequest->resulted_at ? $formatDate($labRequest->resulted_at) : '-' }}</td>
                            <td>{{ $labRequest->requested_tests_text ?: '-' }}</td>
                            <td>{{ $labRequest->results ?: 'Pending / not recorded' }}</td>
                            <td>{{ $labRequest->recommended_medication ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($labRequestCount > $labRequests->count())
                <div style="margin-top:5px;color:#6b7280;font-size:7px;">Showing the most recent {{ $labRequests->count() }} of {{ $labRequestCount }} laboratory requests.</div>
            @endif
        @endif

        <div class="note"><b>Verification:</b> This profile was generated from the live Penzi Farm ERP record. Scan the QR code on page 1 while authenticated to view the current animal profile. Generated by {{ $generatedBy?->name ?? 'System' }} ({{ $generatedByRole }}).</div>

        <div class="footer">
            <table class="footer-table"><tr>
                <td>Generated {{ $generatedAt->format('d M Y, H:i') }} EAT</td>
                <td class="footer-center">{{ $farmName }} - {{ $animal->tag_number }}</td>
                <td class="footer-right">Page 2 of 2</td>
            </tr></table>
        </div>
    </div>
</body>
</html>
