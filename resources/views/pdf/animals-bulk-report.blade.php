@php
    ini_set('max_execution_time', 300);
    ini_set('memory_limit', '1024M');
    if (!function_exists('animalAgeDisplay')) {
        function animalAgeDisplay($animal): string
        {
            if (blank($animal->date_of_birth)) {
                return '-';
            }

            $dob = \Carbon\Carbon::parse($animal->date_of_birth);

            if ($dob->isFuture()) {
                return 'Invalid DOB';
            }

            $age = $dob->diffForHumans(now(), [
                'parts' => 2,
                'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE,
            ]);

            return (bool) $animal->date_of_birth_is_estimated ? 'Approx. ' . $age : $age;
        }
    }

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

    $farmName = setting('farm.name', 'Penzi Farm Limited');
    $farmTagline = setting('farm.tagline', 'Nurturing Quality, Inspiring Global Standards');
    $farmPhone = setting('farm.phone', '+254 757 046 726');
    $farmEmail = setting('farm.email', 'jambo@penzifarm.com');
    $farmCounty = setting('farm.county', 'Molo - Nakuru County');

    $primaryColor = trim(setting('theme.primary', '#014a12'));
    $secondaryColor = trim(setting('theme.secondary', '#14532d'));
    $accentColor = trim(setting('theme.accent', '#f59e0b'));
    $dangerColor = trim(setting('theme.danger', '#dc2626'));
    $successColor = trim(setting('theme.success', '#16a34a'));

    $logoBase64 = pdfImageBase64(setting('branding.logo_light'));

    $generatedByName = $generatedBy->name ?? 'System';
    $generatedByRole = $generatedByRole ?? 'User';
    $totalAnimals = $animals->count();
    $activeCount = $animals->where('status', 'Active')->count();
    $soldCount = $animals->where('status', 'Sold')->count();
    $deadCount = $animals->where('status', 'Dead')->count();
    $culledCount = $animals->where('status', 'Culled')->count();
    $breederCount = $animals->where('is_breeder', true)->count();
    $saleReadyCount = $animals->where('sale_ready', true)->count();
    $purchasedCount = $animals->where('source', 'Purchased')->count();
    $totalValuation = $animals->sum(fn($animal) => (float) ($animal->valuation_price ?? 0));

    $purityTrackedAnimals = $animals->filter(
        fn ($animal) => $animal->breed_purity_percent !== null
    );

    $purityPendingAnimals = $animals->filter(
        fn ($animal) => $animal->breed_purity_percent === null
    );

    $foundationPurityCount = $animals
        ->where('purity_status', 'foundation')
        ->count();

    $calculatedPurityCount = $animals
        ->where('purity_status', 'calculated')
        ->count();

    $verifiedPurityCount = $animals
        ->filter(fn ($animal) => in_array(
            $animal->purity_status,
            ['dna_verified', 'manual_verified'],
            true
        ))
        ->count();

    $averagePurity = $purityTrackedAnimals->isNotEmpty()
        ? (float) $purityTrackedAnimals->avg(
            fn ($animal) => (float) $animal->breed_purity_percent
        )
        : null;

    $purityCoverage = $totalAnimals > 0
        ? (($purityTrackedAnimals->count() / $totalAnimals) * 100)
        : 0;

    $highPurityAnimals = $purityTrackedAnimals
        ->filter(fn ($animal) => (float) $animal->breed_purity_percent >= 87.5)
        ->sortByDesc('breed_purity_percent')
        ->values();

    $purityAttentionAnimals = $animals
        ->filter(fn ($animal) =>
            $animal->breed_purity_percent === null
            || (
                ! $animal->is_foundation_animal
                && $animal->purity_status === 'pending'
            )
        )
        ->values();

    $purityBreedSummary = $purityTrackedAnimals
        ->groupBy(fn ($animal) => $animal->breed?->breed_name ?? 'Unclassified')
        ->map(function ($breedAnimals, $breedName) {
            return [
                'breed' => $breedName,
                'count' => $breedAnimals->count(),
                'average' => (float) $breedAnimals->avg(
                    fn ($animal) => (float) $animal->breed_purity_percent
                ),
            ];
        })
        ->sortByDesc('average')
        ->values();
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Animal Bulk Report</title>
    <style>
        /*
         * Do not load ChopinScript in this bulk report.
         * A stale DomPDF font-metric cache can make bulk PDF generation fail.
         * The dedicated profile and certificate PDF can use its signature font separately.
         */

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
            opacity: 0.05;
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

        .header-table,
        .footer-table,
        .cards-table,
        .mini-cards-table,
        .signature-table,
        table.report {
            width: 100%;
            border-collapse: collapse;
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
            margin-top: 2px;
            margin-bottom: 7px;
        }

        .report-title h1 {
            font-size: 15px;
            margin: 0 0 2px 0;
            color: #111827;
        }

        .report-title p {
            margin: 0;
            color: {{ $primaryColor }};
            font-size: 8.5px;
        }

        .section-label {
            font-size: 10px;
            font-weight: bold;
            color: {{ $secondaryColor }};
            margin: 11px 0 5px 0;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .summary-card {
            border: 1px solid #dbe4d3;
            border-radius: 6px;
            padding: 7px 9px;
            background: #fbfdf9;
            min-height: 48px;
        }

        .summary-card-green {
            border: 1px solid #cfe3bf;
            background: #f8fff2;
        }

        .summary-card-gold {
            border: 1px solid #f6dfb3;
            background: #fffaf0;
        }

        .summary-card-red {
            border: 1px solid #f2c4c4;
            background: #fff5f5;
        }

        .summary-card-title {
            font-size: 8px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.25px;
            margin-bottom: 3px;
            font-weight: bold;
            line-height: 1.05;
        }

        .summary-card-value {
            font-size: 15px;
            font-weight: bold;
            color: #111827;
            line-height: 1.05;
        }

        .summary-card-value.currency {
            font-size: 11px;
            letter-spacing: -0.15px;
        }

        .summary-card-sub {
            font-size: 7.5px;
            color: #6b7280;
            margin-top: 3px;
            line-height: 1.15;
        }

        table.report {
            margin-top: 10px;
        }

        table.report thead th {
            background: {{ $primaryColor }};
            border: 1px solid {{ $primaryColor }};
            color: #fff;
            padding: 9px 7px;
            font-size: 9.5px;
            text-align: left;
        }

        table.report tbody td {
            border: 1px solid #e5e7eb;
            padding: 7px;
            vertical-align: top;
            font-size: 9.5px;
        }

        table.report tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .pill {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 999px;
            font-size: 8px;
            font-weight: bold;
            color: #fff;
        }

        .pill-active {
            background: {{ $successColor }};
        }

        .pill-sold {
            background: {{ $accentColor }};
        }

        .pill-dead {
            background: {{ $dangerColor }};
        }

        .pill-culled {
            background: #6b7280;
        }

        .pill-source {
            background: {{ $secondaryColor }};
        }

        .signature-card {
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
            color: #356e05;
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

        .signature-meta {
            font-size: 9px;
            color: #6b7280;
            margin-bottom: 12px;
        }

        .signature-handwritten {
            font-family: "Times-Roman", serif;
            font-size: 18px;
            font-style: italic;
            font-weight: bold;
            color: {{ $successColor }};
            letter-spacing: 0.5px;
        }

        .purity-badge {
            display: inline-block;
            padding: 3px 7px;
            border-radius: 999px;
            font-size: 8px;
            font-weight: bold;
            color: #fff;
            white-space: nowrap;
        }

        .purity-foundation {
            background: {{ $successColor }};
        }

        .purity-calculated {
            background: #2563eb;
        }

        .purity-verified {
            background: #7c3aed;
        }

        .purity-pending {
            background: #6b7280;
        }

        .signature-line {
            border-top: 1px solid #4b5563;
            margin-top: 18px;
            padding-top: 6px;
        }

        .signature-footer {
            font-size: 9px;
            color: #6b7280;
            margin-top: 4px;
        }

        .stamp-circle {
            width: 110px;
            height: 110px;
            margin: 0 auto 8px auto;
            border: 1px dashed {{ $primaryColor }};
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

        .stamp-caption {
            font-size: 9px;
            color: #6b7280;
            text-align: center;
        }

        .qr-box {
            text-align: center;
        }

        .qr-image-wrap {
            width: 104px;
            height: 104px;
            margin: 4px auto 8px auto;
            border: 2px solid {{ $primaryColor }};
            background: #fff;
            border-radius: 8px;
            padding-top: 6px;
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

        .qr-caption {
            font-size: 8px;
            color: #6b7280;
            margin-top: 6px;
            line-height: 1.4;
        }

        .small-muted {
            color: #6b7280;
            font-size: 9px;
        }

        .weight-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 8px;
            font-weight: bold;
            color: #fff;
        }

        .weight-gaining {
            background: #16a34a;
        }

        .weight-losing {
            background: #dc2626;
        }

        .weight-stable {
            background: #f59e0b;
        }

        .weight-first {
            background: #2563eb;
        }

        .weight-none {
            background: #6b7280;
        }

        .age-badge {
            display: inline-block;
            padding: 3px 7px;
            border-radius: 999px;
            font-size: 8px;
            font-weight: bold;
            color: #fff;
            background: #16a34a;
        }

        .age-estimated {
            background: #f59e0b;
        }

        .age-missing {
            background: #6b7280;
        }

        .age-invalid {
            background: #dc2626;
        }
    </style>
</head>

<body>
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
                <td style="text-align:left;">
                    Printed on {{ $eatNow->format('d M Y, H:i') }} EAT
                </td>
                <td style="text-align:center;">
                    Animal Bulk Report
                </td>
                <td style="text-align:right;">
                    Created by {{ $generatedByName }} ({{ $generatedByRole }})
                </td>
            </tr>
            <tr>
                <td colspan="3" style="text-align:center;" class="small-muted">
                    {{ $farmName }} • {{ $farmCounty }} • {{ $farmPhone }} • {{ $farmEmail }}
                </td>
            </tr>
        </table>
    </footer>
    @php
        $estimatedDobCount = $animals->where('date_of_birth_is_estimated', true)->count();
        $missingDobCount = $animals->filter(fn($animal) => blank($animal->date_of_birth))->count();
    @endphp
    <main>
        <div class="report-title">
            <h1>Animal Bulk Report</h1>
            <p>Total selected animals: {{ $totalAnimals }}</p>
        </div>

        <div class="section-label">Summary Overview</div>

        <table class="cards-table">
            <tr>
                <td style="width:25%; padding-right:5px; padding-bottom:5px;">
                    <div class="summary-card summary-card-green">
                        <div class="summary-card-title">Total Animals</div>
                        <div class="summary-card-value">{{ number_format($totalAnimals) }}</div>
                        <div class="summary-card-sub">Selected records</div>
                    </div>
                </td>
                <td style="width:25%; padding-right:5px; padding-bottom:5px;">
                    <div class="summary-card">
                        <div class="summary-card-title">Active Animals</div>
                        <div class="summary-card-value">{{ number_format($activeCount) }}</div>
                        <div class="summary-card-sub">Currently active</div>
                    </div>
                </td>
                <td style="width:25%; padding-right:5px; padding-bottom:5px;">
                    <div class="summary-card summary-card-gold">
                        <div class="summary-card-title">Sold Animals</div>
                        <div class="summary-card-value">{{ number_format($soldCount) }}</div>
                        <div class="summary-card-sub">Completed sales</div>
                    </div>
                </td>
                <td style="width:25%; padding-bottom:5px;">
                    <div class="summary-card summary-card-red">
                        <div class="summary-card-title">Dead + Culled</div>
                        <div class="summary-card-value">{{ number_format($deadCount + $culledCount) }}</div>
                        <div class="summary-card-sub">Lifecycle closed</div>
                    </div>
                </td>
            </tr>
        </table>


        <table class="mini-cards-table">
            <tr>
                <td style="width:25%; padding-right:5px; padding-bottom:5px;">
                    <div class="summary-card">
                        <div class="summary-card-title">Breeders</div>
                        <div class="summary-card-value">{{ number_format($breederCount) }}</div>
                        <div class="summary-card-sub">Breeding pool</div>
                    </div>
                </td>
                <td style="width:25%; padding-right:5px; padding-bottom:5px;">
                    <div class="summary-card">
                        <div class="summary-card-title">Sale Ready</div>
                        <div class="summary-card-value">{{ number_format($saleReadyCount) }}</div>
                        <div class="summary-card-sub">Ready for sale</div>
                    </div>
                </td>
                <td style="width:25%; padding-right:5px; padding-bottom:5px;">
                    <div class="summary-card">
                        <div class="summary-card-title">Purchased</div>
                        <div class="summary-card-value">{{ number_format($purchasedCount) }}</div>
                        <div class="summary-card-sub">External stock</div>
                    </div>
                </td>
                <td style="width:25%; padding-bottom:5px;">
                    <div class="summary-card">
                        <div class="summary-card-title">Total Valuation</div>
                        <div class="summary-card-value currency">KES {{ number_format($totalValuation, 2) }}</div>
                        <div class="summary-card-sub">Recorded value</div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="section-label">Breed Purity Intelligence</div>

        <table class="mini-cards-table">
            <tr>
                <td style="width:25%; padding-right:5px; padding-bottom:5px;">
                    <div class="summary-card summary-card-green">
                        <div class="summary-card-title">Purity Coverage</div>
                        <div class="summary-card-value">{{ number_format($purityCoverage, 1) }}%</div>
                        <div class="summary-card-sub">
                            {{ number_format($purityTrackedAnimals->count()) }}/{{ number_format($totalAnimals) }} purity complete
                        </div>
                    </div>
                </td>
                <td style="width:25%; padding-right:5px; padding-bottom:5px;">
                    <div class="summary-card">
                        <div class="summary-card-title">Average Purity</div>
                        <div class="summary-card-value">
                            {{ $averagePurity !== null ? number_format($averagePurity, 2) . '%' : 'Pending' }}
                        </div>
                        <div class="summary-card-sub">With purity data</div>
                    </div>
                </td>
                <td style="width:25%; padding-right:5px; padding-bottom:5px;">
                    <div class="summary-card">
                        <div class="summary-card-title">Foundation Stock</div>
                        <div class="summary-card-value">{{ number_format($foundationPurityCount) }}</div>
                        <div class="summary-card-sub">Approved 100% records</div>
                    </div>
                </td>
                <td style="width:25%; padding-bottom:5px;">
                    <div class="summary-card summary-card-gold">
                        <div class="summary-card-title">Pending Purity</div>
                        <div class="summary-card-value">{{ number_format($purityPendingAnimals->count()) }}</div>
                        <div class="summary-card-sub">Needs parentage review</div>
                    </div>
                </td>
            </tr>
        </table>

        <div
            style="
                margin-bottom: 14px;
                border: 1px solid #dbe4d3;
                background: #fbfdf9;
                padding: 12px 14px;
                border-radius: 10px;
                line-height: 1.7;
                font-size: 10px;
            "
        >
            <strong style="color: {{ $primaryColor }};">Breed Intelligence:</strong>

            The report contains
            <strong>{{ number_format($foundationPurityCount) }}</strong> foundation-stock record(s),
            <strong>{{ number_format($calculatedPurityCount) }}</strong> automatically calculated record(s),
            and
            <strong>{{ number_format($verifiedPurityCount) }}</strong> DNA/manual verified record(s).

            @if ($highPurityAnimals->isNotEmpty())
                <br>
                <strong style="color:#16a34a;">High-purity breeding pipeline:</strong>
                {{ $highPurityAnimals->take(8)->map(
                    fn ($animal) => $animal->tag_number . ' (' . number_format((float) $animal->breed_purity_percent, 2) . '%)'
                )->join(', ') }}
                @if ($highPurityAnimals->count() > 8)
                    and {{ $highPurityAnimals->count() - 8 }} more.
                @endif
            @endif

            @if ($purityAttentionAnimals->isNotEmpty())
                <br>
                <strong style="color:#a16207;">Pedigree action required:</strong>
                {{ $purityAttentionAnimals->take(8)->pluck('tag_number')->join(', ') }}
                @if ($purityAttentionAnimals->count() > 8)
                    and {{ $purityAttentionAnimals->count() - 8 }} more.
                @endif
                need parent linkage, foundation approval, or verified purity evidence before they can contribute reliable purity data to offspring.
            @else
                <br>
                <strong style="color:#16a34a;">Pedigree status:</strong>
                all selected animals have usable purity information.
            @endif

            @if ($purityBreedSummary->isNotEmpty())
                <br>
                <strong style="color: {{ $primaryColor }};">Breed profile:</strong>
                {{ $purityBreedSummary->take(5)->map(
                    fn ($row) => $row['breed'] . ': ' . number_format($row['average'], 2) . '% avg across ' . $row['count']
                )->join(' • ') }}
            @endif
        </div>

        @php
            $losingAnimals = $animals->filter(function ($animal) {
                $latestWeight = $animal->latestWeight ?? null;

                return $latestWeight?->trend === 'losing';
            });

            $gainingAnimals = $animals->filter(function ($animal) {
                $latestWeight = $animal->latestWeight ?? null;

                return $latestWeight?->trend === 'gaining';
            });

            /*$stableAnimals = $animals->filter(function ($animal) {
                $latestWeight = $animal
                    ->weights()
                    ->whereNull('deleted_at')
                    ->latest('recorded_at')
                    ->latest('id')
                    ->first();

                return $latestWeight?->trend === 'stable';
            });

            $animalsWithoutWeights = $animals->filter(function ($animal) {
                return !$animal->weights()->whereNull('deleted_at')->exists();
            });*/
            $stableAnimals = $animals->filter(function ($animal) {
    $latestWeight = $animal->latestWeight ?? null;

    return $latestWeight?->trend === 'stable';
});

$animalsWithoutWeights = $animals->filter(function ($animal) {
    return blank($animal->latestWeight);
});
        @endphp

        <div
            style="
    margin-bottom: 14px;
    border: 1px solid #dbe4d3;
    background: #fbfdf9;
    padding: 12px 14px;
    border-radius: 10px;
    line-height: 1.7;
    font-size: 10px;
">
            <strong style="color: {{ $primaryColor }}">
                Weight Intelligence:
            </strong>

            Out of
            <strong>{{ number_format($totalAnimals) }}</strong>
            animals analysed,

            <strong style="color:#16a34a;">
                {{ number_format($gainingAnimals->count()) }}
            </strong>
            are currently showing positive weight progression,

            <strong style="color:#f59e0b;">
                {{ number_format($stableAnimals->count()) }}
            </strong>
            remain relatively stable,

            while
            <strong style="color:#dc2626;">
                {{ number_format($losingAnimals->count()) }}
            </strong>
            animals are experiencing weight decline based on their latest recorded trends.

            @if ($losingAnimals->count())
                Immediate attention is recommended for:

                <strong style="color:#dc2626;">
                    {{ $losingAnimals->take(8)->pluck('tag_number')->join(', ') }}
                </strong>

                @if ($losingAnimals->count() > 8)
                    and {{ $losingAnimals->count() - 8 }} more.
                @endif

                These animals may require nutritional review, health inspection,
                parasite management, treatment follow-up, or closer production monitoring.
            @else
                No animals are currently flagged under weight-loss monitoring.
            @endif

            <br>

            Additionally,

            <strong style="color:#2563eb;">
                {{ number_format($animalsWithoutWeights->count()) }}
            </strong>

            animals currently do not have any recorded weight entries in the system.

            @if ($animalsWithoutWeights->count())
                Missing weight records include:

                <strong style="color:#2563eb;">
                    {{ $animalsWithoutWeights->take(8)->pluck('tag_number')->join(', ') }}
                </strong>

                @if ($animalsWithoutWeights->count() > 8)
                    and {{ $animalsWithoutWeights->count() - 8 }} more.
                @endif

                Recording baseline weights is highly recommended for performance monitoring,
                treatment evaluation, growth analysis, sale preparation, and breed intelligence reporting.
            @endif
            <br>
            <strong style="color: {{ $primaryColor }}">
                Age Intelligence:
            </strong> <strong>{{ number_format($estimatedDobCount) }}</strong>
            animals have estimated dates of birth, while
            <strong style="color:#dc2626;">{{ number_format($missingDobCount) }}</strong>
            animals do not have date of birth records.
        </div>

        <div class="section-label">Animal Details</div>

        <table class="report">
            <thead>
                <tr>
                    <th width="3%">#</th>
                    <th width="8%">Tag</th>
                    <th width="8%">Breed</th>
                    <th width="9%">Purity</th>
                    <th width="6%">Species</th>
                    <th width="5%">Sex</th>
                    <th width="8%">Age</th>
                    <th width="7%">Status</th>
                    <th width="7%">Purpose</th>
                    <th width="7%">Source</th>
                    <th width="8%">Location</th>
                    <th width="8%">Valuation</th>
                    <th width="8%">Weight Trend</th>
                    <th width="6%">Breeder</th>
                    <th width="7%">Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($animals as $index => $animal)
                    @php
                      /*  $latestWeight = $animal
                            ->weights()
                            ->whereNull('deleted_at')
                            ->latest('recorded_at')
                            ->latest('id')
                            ->first();*/
                        $latestWeight = $animal->latestWeight ?? null;

                        $trend = $latestWeight?->trend ?? 'none';

                        $trendLabel = match ($trend) {
                            'gaining' => 'Gaining',
                            'losing' => 'Losing',
                            'stable' => 'Stable',
                            'first' => 'First Entry',
                            default => 'No Weight',
                        };

                        $trendClass = match ($trend) {
                            'gaining' => 'weight-gaining',
                            'losing' => 'weight-losing',
                            'stable' => 'weight-stable',
                            'first' => 'weight-first',
                            default => 'weight-none',
                        };
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td><strong>{{ $animal->tag_number }}</strong></td>
                        <td>{{ $animal->breed?->breed_name ?? '-' }}</td>
                        <td>
                            @php
                                $purityStatus = $animal->purity_status ?? 'pending';

                                $purityClass = match ($purityStatus) {
                                    'foundation' => 'purity-foundation',
                                    'calculated' => 'purity-calculated',
                                    'dna_verified', 'manual_verified' => 'purity-verified',
                                    default => 'purity-pending',
                                };

                                $purityLabel = $animal->breed_purity_percent !== null
                                    ? number_format((float) $animal->breed_purity_percent, 2) . '%'
                                    : 'Pending';

                                $purityStatusLabel = match ($purityStatus) {
                                    'foundation' => 'Foundation',
                                    'calculated' => 'Calculated',
                                    'dna_verified' => 'DNA Verified',
                                    'manual_verified' => 'Manual Verified',
                                    default => 'Parentage Pending',
                                };
                            @endphp

                            <span class="purity-badge {{ $purityClass }}">
                                {{ $purityLabel }}
                            </span>
                            <div style="margin-top:4px;font-size:8px;color:#6b7280;">
                                {{ $purityStatusLabel }}
                            </div>
                        </td>
                        <td>{{ $animal->species ?? '-' }}</td>
                        <td>{{ $animal->sex ?? '-' }}</td>

                        <td>
                            @php
                                $ageText = animalAgeDisplay($animal);

                                $ageClass = match (true) {
                                    $ageText === '-' => 'age-missing',
                                    $ageText === 'Invalid DOB' => 'age-invalid',
                                    (bool) $animal->date_of_birth_is_estimated => 'age-estimated',
                                    default => 'age-badge',
                                };
                            @endphp

                            <span class="age-badge {{ $ageClass }}">
                                {{ $ageText }}
                            </span>
                        </td>

                        <td>
                            @php
                                $statusClass = match ($animal->status) {
                                    'Active' => 'pill-active',
                                    'Sold' => 'pill-sold',
                                    'Dead' => 'pill-dead',
                                    'Culled' => 'pill-culled',
                                    default => 'pill-culled',
                                };
                            @endphp
                            <span class="pill {{ $statusClass }}">{{ $animal->status }}</span>
                        </td>
                        <td>{{ $animal->purpose ?? '-' }}</td>
                        <td>
                            <span class="pill pill-source">{{ $animal->source ?? '-' }}</span>
                        </td>
                        <td>{{ $animal->location?->name ?? '-' }}</td>
                        <td>
                            {{ $animal->valuation_price ? 'KES ' . number_format((float) $animal->valuation_price, 2) : '-' }}
                        </td>
                        <td>
                            <span class="weight-badge {{ $trendClass }}">
                                {{ $trendLabel }}
                            </span>

                            @if ($latestWeight)
                                <div style="margin-top:4px; font-size:8px; color:#6b7280;">
                                    {{ number_format((float) $latestWeight->weight_kg, 2) }} KG
                                </div>
                            @endif
                        </td>

                        <td>{{ $animal->is_breeder ? 'Yes' : 'No' }}</td>

                        <td>{{ $animal->notes ?: '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div style="margin-top: 20px;" class="section-label">Approval & Verification</div>

        <table class="signature-table">
            <tr>
                <td style="width: 26%; padding-top: 16px; padding-right: 8px;">
                    <div class="signature-card">
                        <div class="signature-card-title">Prepared By</div>
                        <div class="signature-name">{{ $generatedByName }}</div>
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
                            Digitally Approved
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
                    <div class="stamp-circle">
                        <div class="stamp-text">OFFICIAL<br>STAMP</div>
                    </div>
                    <div class="stamp-caption">{{ $farmName }} Stamp</div>
                </td>

                <td style="width: 24%; padding-top: 16px;">
                    <div class="qr-box">
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
