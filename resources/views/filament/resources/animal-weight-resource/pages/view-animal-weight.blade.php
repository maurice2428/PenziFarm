<x-filament-panels::page>
    @php
        $latestTrend = $summary['latest_trend'] ?? 'none';

        $trendMeta = match ($latestTrend) {
            'gaining' => [
                'label' => 'Gaining',
                'icon' => '↗',
                'class' => 'trend-gaining',
                'description' => 'The latest reading increased.',
            ],
            'losing' => [
                'label' => 'Losing',
                'icon' => '↘',
                'class' => 'trend-losing',
                'description' => 'The latest reading decreased.',
            ],
            'stable' => [
                'label' => 'Stable',
                'icon' => '→',
                'class' => 'trend-stable',
                'description' => 'No change from the previous reading.',
            ],
            'first' => [
                'label' => 'First Reading',
                'icon' => '●',
                'class' => 'trend-first',
                'description' => 'Only the baseline reading is available.',
            ],
            default => [
                'label' => 'No Data',
                'icon' => '—',
                'class' => 'trend-none',
                'description' => 'No active weight readings are available.',
            ],
        };

        $formatWeight = static fn ($value): string =>
            $value === null
                ? '—'
                : number_format((float) $value, 2) . ' KG';

        $formatSignedWeight = static fn ($value): string =>
            $value === null
                ? '—'
                : (((float) $value > 0 ? '+' : '')
                    . number_format((float) $value, 2)
                    . ' KG');

        $totalChangeClass = match (true) {
            ($summary['total_change'] ?? null) === null => 'metric-neutral',
            (float) $summary['total_change'] > 0 => 'metric-success',
            (float) $summary['total_change'] < 0 => 'metric-danger',
            default => 'metric-warning',
        };
    @endphp

    <style>
        .weight-intelligence {
            --wi-primary: {{ $primaryColor }};
            --wi-secondary: {{ $secondaryColor }};
            --wi-accent: {{ $accentColor }};
            --wi-success: {{ $successColor }};
            --wi-danger: {{ $dangerColor }};

            --wi-page-text: #1e293b;
            --wi-heading: #0f172a;
            --wi-muted: #475569;
            --wi-border: #dbe3ec;
            --wi-surface: #ffffff;
            --wi-soft: #f8fafc;
            --wi-soft-strong: #f1f5f9;
            --wi-table-head: #0f172a;
            --wi-table-head-text: #ffffff;
            --wi-row: #ffffff;
            --wi-row-alt: #f8fafc;
            --wi-row-hover: #eef6f1;

            display: grid;
            gap: 1.15rem;
            color: var(--wi-page-text);
        }

        .dark .weight-intelligence {
            --wi-page-text: #e2e8f0;
            --wi-heading: #f8fafc;
            --wi-muted: #cbd5e1;
            --wi-border: #334155;
            --wi-surface: #0f172a;
            --wi-soft: #111827;
            --wi-soft-strong: #1e293b;
            --wi-table-head: #020617;
            --wi-table-head-text: #f8fafc;
            --wi-row: #0f172a;
            --wi-row-alt: #111827;
            --wi-row-hover: #1e293b;
        }

        .wi-hero {
            position: relative;
            overflow: hidden;
            border: 1px solid var(--wi-border);
            border-top: 5px solid var(--wi-primary);
            border-radius: 1.1rem;
            padding: 1.35rem;
            color: var(--wi-page-text);
            background: var(--wi-surface);
            box-shadow: 0 10px 28px rgba(15, 23, 42, .07);
        }

        .dark .wi-hero {
            box-shadow: 0 12px 30px rgba(0, 0, 0, .28);
        }

        .wi-hero-content {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .wi-eyebrow {
            color: var(--wi-primary);
            font-size: .69rem;
            font-weight: 950;
            letter-spacing: .13em;
            text-transform: uppercase;
        }

        .wi-title {
            margin-top: .35rem;
            color: var(--wi-heading);
            font-size: clamp(1rem, 1.9vw, 1.5rem);
            font-weight: 950;
            letter-spacing: -.035em;
        }

        .wi-subtitle {
            max-width: 760px;
            margin-top: .45rem;
            color: var(--wi-muted);
            font-size: .84rem;
            line-height: 1.55;
        }

        .wi-hero-badges {
            display: flex;
            flex-wrap: wrap;
            gap: .42rem;
            margin-top: .85rem;
        }

        .wi-hero-badge {
            display: inline-flex;
            align-items: center;
            gap: .32rem;
            padding: .4rem .62rem;
            border: 1px solid var(--wi-border);
            border-radius: 999px;
            color: var(--wi-heading);
            background: var(--wi-soft);
            font-size: .68rem;
            font-weight: 850;
        }

        .wi-latest {
            min-width: 190px;
            padding: .95rem 1rem;
            border: 1px solid var(--wi-primary);
            border-radius: .95rem;
            color: var(--wi-heading);
            background: var(--wi-soft);
            text-align: right;
        }

        .wi-latest-label {
            color: var(--wi-primary);
            font-size: .64rem;
            font-weight: 900;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .wi-latest-value {
            margin-top: .26rem;
            color: var(--wi-heading);
            font-size: 1.75rem;
            font-weight: 950;
        }

        .wi-latest-date {
            margin-top: .22rem;
            color: var(--wi-muted);
            font-size: .69rem;
        }

        .wi-kpis {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .75rem;
        }

        .wi-card {
            position: relative;
            overflow: hidden;
            min-width: 0;
            border: 1px solid var(--wi-border);
            border-radius: 1rem;
            background: var(--wi-surface);
            box-shadow: 0 8px 22px rgba(15, 23, 42, .055);
        }

        .dark .wi-card {
            box-shadow: 0 8px 24px rgba(0, 0, 0, .22);
        }

        .wi-kpi {
            padding: .92rem 1rem;
        }

        .wi-kpi::before {
            content: "";
            position: absolute;
            inset: 0 auto 0 0;
            width: 4px;
            background: var(--wi-primary);
        }

        .wi-kpi-label {
            color: var(--wi-muted);
            font-size: .63rem;
            font-weight: 900;
            letter-spacing: .07em;
            text-transform: uppercase;
        }

        .wi-kpi-value {
            margin-top: .37rem;
            color: var(--wi-heading);
            font-size: 1.25rem;
            font-weight: 950;
            letter-spacing: -.025em;
        }

        .wi-kpi-note {
            margin-top: .28rem;
            color: var(--wi-muted);
            font-size: .68rem;
            line-height: 1.35;
        }

        .metric-success .wi-kpi-value { color: #15803d; }
        .metric-danger .wi-kpi-value { color: #b91c1c; }
        .metric-warning .wi-kpi-value { color: #a16207; }

        .dark .metric-success .wi-kpi-value { color: #86efac; }
        .dark .metric-danger .wi-kpi-value { color: #fca5a5; }
        .dark .metric-warning .wi-kpi-value { color: #fde68a; }

        .wi-section {
            padding: 1rem;
        }

        .wi-section-heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: .8rem;
            flex-wrap: wrap;
        }

        .wi-section-title {
            color: var(--wi-heading);
            font-size: .95rem;
            font-weight: 950;
        }

        .wi-section-description {
            margin-top: .2rem;
            color: var(--wi-muted);
            font-size: .72rem;
            line-height: 1.45;
        }

        .wi-trend-pill {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .4rem .62rem;
            border-radius: 999px;
            font-size: .68rem;
            font-weight: 900;
            white-space: nowrap;
        }

        .trend-gaining {
            color: #166534;
            background: #dcfce7;
            border: 1px solid #86efac;
        }

        .trend-losing {
            color: #991b1b;
            background: #fee2e2;
            border: 1px solid #fca5a5;
        }

        .trend-stable {
            color: #854d0e;
            background: #fef3c7;
            border: 1px solid #fcd34d;
        }

        .trend-first,
        .trend-none {
            color: #334155;
            background: #e2e8f0;
            border: 1px solid #cbd5e1;
        }

        .dark .trend-gaining {
            color: #bbf7d0;
            background: #14532d;
            border-color: #22c55e;
        }

        .dark .trend-losing {
            color: #fecaca;
            background: #7f1d1d;
            border-color: #ef4444;
        }

        .dark .trend-stable {
            color: #fef3c7;
            background: #713f12;
            border-color: #f59e0b;
        }

        .dark .trend-first,
        .dark .trend-none {
            color: #e2e8f0;
            background: #334155;
            border-color: #64748b;
        }

        .wi-chart-shell {
            overflow-x: auto;
            border: 1px solid var(--wi-border);
            border-radius: .9rem;
            background: var(--wi-soft);
        }

        .wi-chart {
            display: block;
            width: 100%;
            min-width: 720px;
            height: auto;
        }

        .wi-grid-line {
            stroke: #cbd5e1;
            stroke-width: 1;
            stroke-dasharray: 5 7;
        }

        .dark .wi-grid-line {
            stroke: #475569;
        }

        .wi-axis-label {
            fill: var(--wi-muted);
            font-size: 12px;
            font-weight: 750;
        }

        .wi-area {
            fill: url(#wiAreaGradient);
        }

        .wi-line {
            fill: none;
            stroke: var(--wi-primary);
            stroke-width: 5;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .wi-point {
            fill: var(--wi-surface);
            stroke: var(--wi-primary);
            stroke-width: 4;
        }

        .wi-point.latest {
            fill: var(--wi-accent);
            stroke: var(--wi-surface);
            stroke-width: 4;
        }

        .wi-empty {
            padding: 3rem 1rem;
            color: var(--wi-muted);
            background: var(--wi-soft);
            text-align: center;
        }

        .wi-table-wrap {
            overflow-x: auto;
            border: 1px solid var(--wi-border);
            border-top: 4px solid var(--wi-primary);
            border-radius: .9rem;
            background: var(--wi-row);
        }

        .wi-table {
            width: 100%;
            min-width: 940px;
            border-collapse: separate;
            border-spacing: 0;
            background: var(--wi-row);
        }

        .wi-table th {
            position: sticky;
            top: 0;
            z-index: 2;
            padding: .78rem .82rem;
            border-right: 1px solid #334155;
            border-bottom: 1px solid #334155;
            color: var(--wi-table-head-text);
            background: var(--wi-table-head);
            font-size: .66rem;
            font-weight: 950;
            letter-spacing: .065em;
            text-align: left;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .wi-table th:last-child {
            border-right: 0;
        }

        .wi-table td {
            padding: .78rem .82rem;
            border-right: 1px solid var(--wi-border);
            border-bottom: 1px solid var(--wi-border);
            color: var(--wi-page-text);
            background: var(--wi-row);
            font-size: .74rem;
            line-height: 1.45;
            vertical-align: middle;
        }

        .wi-table td:last-child {
            border-right: 0;
        }

        .wi-table tbody tr:nth-child(even) td {
            background: var(--wi-row-alt);
        }

        .wi-table tbody tr:hover td {
            background: var(--wi-row-hover);
        }

        .wi-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .wi-table tbody td:first-child {
            color: var(--wi-muted);
            font-weight: 900;
            text-align: center;
        }

        .wi-weight-value {
            color: var(--wi-heading);
            font-size: .84rem;
            font-weight: 950;
            white-space: nowrap;
        }

        .wi-diff-positive {
            display: inline-block;
            color: #166534;
            font-weight: 950;
            white-space: nowrap;
        }

        .wi-diff-negative {
            display: inline-block;
            color: #b91c1c;
            font-weight: 950;
            white-space: nowrap;
        }

        .wi-diff-neutral {
            display: inline-block;
            color: #475569;
            font-weight: 900;
            white-space: nowrap;
        }

        .dark .wi-diff-positive { color: #86efac; }
        .dark .wi-diff-negative { color: #fca5a5; }
        .dark .wi-diff-neutral { color: #cbd5e1; }

        .wi-remark {
            max-width: 340px;
            color: var(--wi-muted);
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .wi-date-main,
        .wi-recorder-main {
            color: var(--wi-heading);
            font-weight: 900;
        }

        .wi-date-time,
        .wi-recorder-meta {
            margin-top: .15rem;
            color: var(--wi-muted);
            font-size: .67rem;
        }

        @media (min-width: 760px) {
            .wi-kpis {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        @media (min-width: 1280px) {
            .wi-kpis {
                grid-template-columns: repeat(6, minmax(0, 1fr));
            }
        }

        @media (max-width: 639px) {
            .wi-hero {
                padding: 1rem;
                border-radius: 1rem;
            }

            .wi-latest {
                width: 100%;
                text-align: left;
            }

            .wi-section {
                padding: .82rem;
            }

            .wi-table {
                min-width: 900px;
            }
        }
    </style>

    <div class="weight-intelligence">
        <section class="wi-hero">
            <div class="wi-hero-content">
                <div>
                    <div class="wi-eyebrow">
                        {{ $farmName }} · Weight Intelligence
                    </div>

                    <div class="wi-title">
                        {{ $animal?->tag_number ?? 'Animal' }}
                        Growth & Weight History
                    </div>

                    <div class="wi-subtitle">
                        Complete chronological weight performance for this
                        animal, including every active entry, change between
                        readings, long-term growth, and the latest direction.
                    </div>

                    <div class="wi-hero-badges">
                        <span class="wi-hero-badge">
                            {{ $animal?->breed?->breed_name ?? 'Breed not recorded' }}
                        </span>

                        <span class="wi-hero-badge">
                            {{ $animal?->species ?? 'Species not recorded' }}
                        </span>

                        <span class="wi-hero-badge">
                            {{ $animal?->sex ?? 'Sex not recorded' }}
                        </span>

                        <span class="wi-hero-badge">
                            {{ $animal?->location?->name ?? 'Location not recorded' }}
                        </span>

                        <span class="wi-hero-badge">
                            {{ number_format($summary['record_count']) }}
                            reading{{ $summary['record_count'] === 1 ? '' : 's' }}
                        </span>
                    </div>
                </div>

                <div class="wi-latest">
                    <div class="wi-latest-label">Latest Weight</div>
                    <div class="wi-latest-value">
                        {{ $formatWeight($summary['latest_weight']) }}
                    </div>
                    <div class="wi-latest-date">
                        {{ $chartHistory->last()?->recorded_at?->format('d M Y, H:i') ?? 'No reading date' }}
                    </div>
                </div>
            </div>
        </section>

        <section class="wi-kpis">
            <article class="wi-card wi-kpi">
                <div class="wi-kpi-label">First Weight</div>
                <div class="wi-kpi-value">
                    {{ $formatWeight($summary['first_weight']) }}
                </div>
                <div class="wi-kpi-note">Baseline reading</div>
            </article>

            <article class="wi-card wi-kpi {{ $totalChangeClass }}">
                <div class="wi-kpi-label">Total Change</div>
                <div class="wi-kpi-value">
                    {{ $formatSignedWeight($summary['total_change']) }}
                </div>
                <div class="wi-kpi-note">
                    First to latest reading
                </div>
            </article>

            <article class="wi-card wi-kpi">
                <div class="wi-kpi-label">Average Weight</div>
                <div class="wi-kpi-value">
                    {{ $formatWeight($summary['average_weight']) }}
                </div>
                <div class="wi-kpi-note">Across all entries</div>
            </article>

            <article class="wi-card wi-kpi">
                <div class="wi-kpi-label">Weight Range</div>
                <div class="wi-kpi-value">
                    {{ $formatWeight($summary['minimum_weight']) }}
                </div>
                <div class="wi-kpi-note">
                    Maximum:
                    {{ $formatWeight($summary['maximum_weight']) }}
                </div>
            </article>

            <article class="wi-card wi-kpi">
                <div class="wi-kpi-label">Average Daily Gain</div>
                <div class="wi-kpi-value">
                    @if ($summary['average_daily_gain'] !== null)
                        {{ number_format(
                            (float) $summary['average_daily_gain'],
                            3
                        ) }} KG/day
                    @else
                        —
                    @endif
                </div>
                <div class="wi-kpi-note">
                    Across {{ number_format($summary['days_covered']) }} day(s)
                </div>
            </article>

            <article class="wi-card wi-kpi">
                <div class="wi-kpi-label">Latest Direction</div>
                <div class="wi-kpi-value">
                    {{ $trendMeta['icon'] }}
                    {{ $trendMeta['label'] }}
                </div>
                <div class="wi-kpi-note">
                    {{ $formatSignedWeight($summary['latest_difference']) }}
                    from previous
                </div>
            </article>
        </section>

        <section class="wi-card wi-section">
            <div class="wi-section-heading">
                <div>
                    <div class="wi-section-title">
                        Complete Growth Trend
                    </div>
                    <div class="wi-section-description">
                        Readings are plotted from the oldest to the newest.
                        Hover over a point to see its date and exact weight.
                    </div>
                </div>

                <span class="wi-trend-pill {{ $trendMeta['class'] }}">
                    <span>{{ $trendMeta['icon'] }}</span>
                    <span>
                        {{ $trendMeta['label'] }}
                    </span>
                </span>
            </div>

            @if ($chartHistory->isEmpty())
                <div class="wi-empty">
                    No active weight records are available for this animal.
                </div>
            @else
                <div class="wi-chart-shell">
                    <svg
                        class="wi-chart"
                        viewBox="0 0 {{ $chart['width'] }} {{ $chart['height'] }}"
                        preserveAspectRatio="xMidYMid meet"
                        role="img"
                        aria-label="Weight trend chart for {{ $animal?->tag_number }}"
                    >
                        <defs>
                            <linearGradient
                                id="wiLineGradient"
                                x1="0%"
                                y1="0%"
                                x2="100%"
                                y2="0%"
                            >
                                <stop
                                    offset="0%"
                                    stop-color="{{ $primaryColor }}"
                                />
                                <stop
                                    offset="55%"
                                    stop-color="{{ $secondaryColor }}"
                                />
                                <stop
                                    offset="100%"
                                    stop-color="{{ $accentColor }}"
                                />
                            </linearGradient>

                            <linearGradient
                                id="wiAreaGradient"
                                x1="0%"
                                y1="0%"
                                x2="0%"
                                y2="100%"
                            >
                                <stop
                                    offset="0%"
                                    stop-color="{{ $primaryColor }}"
                                    stop-opacity=".28"
                                />
                                <stop
                                    offset="100%"
                                    stop-color="{{ $primaryColor }}"
                                    stop-opacity=".015"
                                />
                            </linearGradient>
                        </defs>

                        @foreach ($chart['y_ticks'] as $tick)
                            <line
                                class="wi-grid-line"
                                x1="{{ $chart['plot']['left'] }}"
                                y1="{{ $tick['y'] }}"
                                x2="{{ $chart['plot']['right'] }}"
                                y2="{{ $tick['y'] }}"
                            />

                            <text
                                class="wi-axis-label"
                                x="{{ $chart['plot']['left'] - 13 }}"
                                y="{{ $tick['y'] + 4 }}"
                                text-anchor="end"
                            >
                                {{ number_format($tick['value'], 1) }}
                            </text>
                        @endforeach

                        @foreach ($chart['x_labels'] as $label)
                            <text
                                class="wi-axis-label"
                                x="{{ $label['x'] }}"
                                y="{{ $chart['plot']['bottom'] + 33 }}"
                                text-anchor="middle"
                            >
                                {{ $label['label'] }}
                            </text>
                        @endforeach

                        @if ($chart['area'] !== '')
                            <polygon
                                class="wi-area"
                                points="{{ $chart['area'] }}"
                            />
                        @endif

                        @if ($chart['polyline'] !== '')
                            <polyline
                                class="wi-line"
                                points="{{ $chart['polyline'] }}"
                            />
                        @endif

                        @foreach ($chart['points'] as $index => $point)
                            <g>
                                <title>
                                    {{ $point['date'] }}
                                    ·
                                    {{ number_format($point['weight'], 2) }}
                                    KG
                                </title>

                                <circle
                                    class="wi-point {{ $loop->last ? 'latest' : '' }}"
                                    cx="{{ $point['x'] }}"
                                    cy="{{ $point['y'] }}"
                                    r="{{ $loop->last ? 8 : 6 }}"
                                />
                            </g>
                        @endforeach

                        <text
                            class="wi-axis-label"
                            x="18"
                            y="{{ $chart['height'] / 2 }}"
                            text-anchor="middle"
                            transform="rotate(-90 18 {{ $chart['height'] / 2 }})"
                        >
                            Weight (KG)
                        </text>
                    </svg>
                </div>
            @endif
        </section>

        <section class="wi-card wi-section">
            <div class="wi-section-heading">
                <div>
                    <div class="wi-section-title">
                        All Weight Entries
                    </div>
                    <div class="wi-section-description">
                        Every non-deleted reading recorded for
                        {{ $animal?->tag_number ?? 'this animal' }},
                        newest first.
                    </div>
                </div>
            </div>

            @if ($weightHistory->isEmpty())
                <div class="wi-empty">
                    No active weight history has been recorded.
                </div>
            @else
                <div class="wi-table-wrap">
                    <table class="wi-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Recorded At</th>
                                <th>Weight</th>
                                <th>Previous</th>
                                <th>Change</th>
                                <th>Trend</th>
                                <th>Recorded By</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($weightHistory as $entry)
                                @php
                                    $difference =
                                        $entry->calculated_difference;

                                    $differenceClass = match (true) {
                                        $difference === null =>
                                            'wi-diff-neutral',
                                        (float) $difference > 0 =>
                                            'wi-diff-positive',
                                        (float) $difference < 0 =>
                                            'wi-diff-negative',
                                        default =>
                                            'wi-diff-neutral',
                                    };

                                    $entryTrend = match (
                                        $entry->calculated_trend
                                    ) {
                                        'gaining' => [
                                            'label' => 'Gaining',
                                            'icon' => '↗',
                                            'class' => 'trend-gaining',
                                        ],
                                        'losing' => [
                                            'label' => 'Losing',
                                            'icon' => '↘',
                                            'class' => 'trend-losing',
                                        ],
                                        'stable' => [
                                            'label' => 'Stable',
                                            'icon' => '→',
                                            'class' => 'trend-stable',
                                        ],
                                        default => [
                                            'label' => 'First Entry',
                                            'icon' => '●',
                                            'class' => 'trend-first',
                                        ],
                                    };
                                @endphp

                                <tr>
                                    <td>{{ $loop->iteration }}</td>

                                    <td>
                                        <div class="wi-date-main">
                                            {{ $entry->recorded_at?->format(
                                                'd M Y'
                                            ) ?? 'Date not recorded' }}
                                        </div>
                                        <div class="wi-date-time">
                                            {{ $entry->recorded_at?->format(
                                                'h:i A'
                                            ) ?? 'Time not recorded' }}
                                        </div>
                                    </td>

                                    <td>
                                        <span class="wi-weight-value">
                                            {{ number_format(
                                                (float) $entry->weight_kg,
                                                2
                                            ) }}
                                            KG
                                        </span>
                                    </td>

                                    <td>
                                        {{ $entry->calculated_previous_weight
                                            === null
                                                ? '—'
                                                : number_format(
                                                    (float) $entry
                                                        ->calculated_previous_weight,
                                                    2
                                                ) . ' KG' }}
                                    </td>

                                    <td>
                                        <span class="{{ $differenceClass }}">
                                            {{ $formatSignedWeight(
                                                $difference
                                            ) }}
                                        </span>
                                    </td>

                                    <td>
                                        <span
                                            class="wi-trend-pill {{ $entryTrend['class'] }}"
                                        >
                                            {{ $entryTrend['icon'] }}
                                            {{ $entryTrend['label'] }}
                                        </span>
                                    </td>

                                    <td>
                                        <div class="wi-recorder-main">
                                            {{ $entry->recorder?->name
                                                ?? 'System / Not recorded' }}
                                        </div>
                                        <div class="wi-recorder-meta">
                                            Weight entry recorder
                                        </div>
                                    </td>

                                    <td>
                                        <div class="wi-remark">
                                            {{ filled($entry->remarks)
                                                ? $entry->remarks
                                                : 'No remarks' }}
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</x-filament-panels::page>
