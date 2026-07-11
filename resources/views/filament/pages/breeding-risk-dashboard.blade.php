<x-filament-panels::page>
    @php
        $summary = $dashboard['summary'];
        $lowest = $dashboard['lowest'];

        $recommendationMeta = static function (
            string $recommendation
        ): array {
            return match ($recommendation) {
                'cull' => [
                    'label' => 'Cull',
                    'class' => 'risk-cull',
                    'icon' => 'heroicon-o-x-circle',
                ],
                'sell' => [
                    'label' => 'Sell',
                    'class' => 'risk-sell',
                    'icon' => 'heroicon-o-banknotes',
                ],
                'monitor' => [
                    'label' => 'Monitor',
                    'class' => 'risk-monitor',
                    'icon' => 'heroicon-o-eye',
                ],
                'retain' => [
                    'label' => 'Retain',
                    'class' => 'risk-retain',
                    'icon' => 'heroicon-o-check-badge',
                ],
                default => [
                    'label' => 'Insufficient Evidence',
                    'class' => 'risk-insufficient',
                    'icon' => 'heroicon-o-question-mark-circle',
                ],
            };
        };

        $formatPercent = static fn (mixed $value): string =>
            number_format((float) $value, 1) . '%';

        $formatScore = static fn (mixed $value): string =>
            number_format((float) $value, 1) . '/100';
    @endphp

    <style>
        .breeding-risk-dashboard {
            --br-primary: {{ $primaryColor }};
            --br-secondary: {{ $secondaryColor }};
            --br-accent: {{ $accentColor }};
            --br-success: {{ $successColor }};
            --br-danger: {{ $dangerColor }};

            --br-heading: #0f172a;
            --br-text: #334155;
            --br-muted: #64748b;
            --br-border: #dbe4df;
            --br-surface: #ffffff;
            --br-soft: #f8fafc;
            --br-soft-strong: #f1f5f9;

            display: grid;
            gap: 1rem;
            color: var(--br-text);
        }

        .dark .breeding-risk-dashboard {
            --br-heading: #f8fafc;
            --br-text: #e2e8f0;
            --br-muted: #94a3b8;
            --br-border: #334155;
            --br-surface: #0f172a;
            --br-soft: #111827;
            --br-soft-strong: #1e293b;
        }

        .br-hero {
            position: relative;
            overflow: hidden;
            border: 1px solid color-mix(
                in srgb,
                var(--br-primary) 35%,
                var(--br-border)
            );
            border-radius: 1.05rem;
            padding: 1.2rem;
            color: #fff;
            background:
                radial-gradient(
                    circle at 92% 4%,
                    rgba(255,255,255,.18),
                    transparent 25%
                ),
                radial-gradient(
                    circle at 7% 94%,
                    color-mix(
                        in srgb,
                        var(--br-accent) 32%,
                        transparent
                    ),
                    transparent 32%
                ),
                linear-gradient(
                    125deg,
                    var(--br-primary),
                    var(--br-secondary)
                );
            box-shadow: 0 15px 36px color-mix(
                in srgb,
                var(--br-primary) 18%,
                transparent
            );
        }

        .br-hero::after {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            background-image:
                linear-gradient(
                    rgba(255,255,255,.045) 1px,
                    transparent 1px
                ),
                linear-gradient(
                    90deg,
                    rgba(255,255,255,.045) 1px,
                    transparent 1px
                );
            background-size: 26px 26px;
            mask-image: linear-gradient(to bottom, black, transparent);
        }

        .br-hero-content {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .br-eyebrow {
            display: flex;
            align-items: center;
            gap: .4rem;
            color: rgba(255,255,255,.78);
            font-size: .66rem;
            font-weight: 900;
            letter-spacing: .11em;
            text-transform: uppercase;
        }

        .br-eyebrow svg {
            width: .95rem;
            height: .95rem;
        }

        .br-title {
            margin-top: .35rem;
            font-size: clamp(1.25rem, 2.3vw, 2rem);
            font-weight: 950;
            letter-spacing: -.035em;
        }

        .br-subtitle {
            max-width: 760px;
            margin-top: .42rem;
            color: rgba(255,255,255,.8);
            font-size: .78rem;
            line-height: 1.55;
        }

        .br-hero-pills {
            display: flex;
            flex-wrap: wrap;
            gap: .42rem;
            margin-top: .8rem;
        }

        .br-hero-pill {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .38rem .56rem;
            border: 1px solid rgba(255,255,255,.2);
            border-radius: 999px;
            background: rgba(255,255,255,.11);
            font-size: .62rem;
            font-weight: 850;
            backdrop-filter: blur(8px);
        }

        .br-hero-score {
            min-width: 200px;
            padding: .9rem;
            border: 1px solid rgba(255,255,255,.2);
            border-radius: .9rem;
            background: rgba(255,255,255,.11);
            backdrop-filter: blur(10px);
        }

        .br-hero-score-label {
            color: rgba(255,255,255,.72);
            font-size: .6rem;
            font-weight: 900;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .br-hero-score-value {
            margin-top: .28rem;
            font-size: 1.8rem;
            font-weight: 950;
        }

        .br-hero-score-note {
            margin-top: .2rem;
            color: rgba(255,255,255,.74);
            font-size: .62rem;
        }

        .br-panel {
            overflow: hidden;
            border: 1px solid var(--br-border);
            border-radius: 1rem;
            background: var(--br-surface);
            box-shadow: 0 8px 24px rgba(15,23,42,.045);
        }

        .dark .br-panel {
            box-shadow: 0 10px 28px rgba(0,0,0,.22);
        }

        .br-panel-heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .8rem;
            padding: .9rem 1rem;
            border-bottom: 1px solid var(--br-border);
            background: var(--br-soft);
            flex-wrap: wrap;
        }

        .br-panel-title {
            color: var(--br-heading);
            font-size: .88rem;
            font-weight: 950;
        }

        .br-panel-description {
            margin-top: .22rem;
            color: var(--br-muted);
            font-size: .68rem;
            line-height: 1.45;
        }

        .br-filter-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .7rem;
            padding: .9rem 1rem 1rem;
        }

        .br-filter-field {
            display: grid;
            gap: .3rem;
        }

        .br-filter-label {
            color: var(--br-heading);
            font-size: .63rem;
            font-weight: 850;
        }

        .br-select-wrap {
            position: relative;
            min-width: 0;
        }

        .br-select-wrap::after {
            content: "";
            position: absolute;
            top: 50%;
            right: .82rem;
            width: .46rem;
            height: .46rem;
            border-right: 2px solid var(--br-muted);
            border-bottom: 2px solid var(--br-muted);
            pointer-events: none;
            transform: translateY(-68%) rotate(45deg);
        }

        .br-filter-control {
            display: block;
            width: 100%;
            min-height: 2.35rem;
            border: 1px solid var(--br-border);
            border-radius: .65rem;
            padding: .5rem 2.15rem .5rem .68rem;
            color: var(--br-heading);
            background-color: var(--br-surface);
            background-image: none !important;
            font-size: .7rem;
            outline: none;

            /*
             * Disable the native/Filament background chevrons. The wrapper
             * above renders exactly one arrow for every select.
             */
            appearance: none !important;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
        }

        .br-filter-control::-ms-expand {
            display: none;
        }

        .br-filter-control:focus {
            border-color: var(--br-primary);
            box-shadow: 0 0 0 3px color-mix(
                in srgb,
                var(--br-primary) 12%,
                transparent
            );
        }

        .br-filter-control:disabled {
            cursor: not-allowed;
            opacity: .65;
        }

        .br-summary-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .65rem;
        }

        .br-summary-card {
            --summary-color: var(--br-primary);
            position: relative;
            min-width: 0;
            min-height: 112px;
            overflow: hidden;
            border: 1px solid var(--br-border);
            border-left: 4px solid var(--summary-color);
            border-radius: .85rem;
            padding: .8rem;
            background: var(--br-surface);
        }

        .br-summary-card::after {
            content: "";
            position: absolute;
            top: -30px;
            right: -26px;
            width: 82px;
            height: 82px;
            border-radius: 999px;
            background: color-mix(
                in srgb,
                var(--summary-color) 8%,
                transparent
            );
        }

        .br-summary-card[data-tone="danger"] {
            --summary-color: var(--br-danger);
        }

        .br-summary-card[data-tone="sell"] {
            --summary-color: #ea580c;
        }

        .br-summary-card[data-tone="warning"] {
            --summary-color: var(--br-accent);
        }

        .br-summary-card[data-tone="success"] {
            --summary-color: var(--br-success);
        }

        .br-summary-card[data-tone="info"] {
            --summary-color: #2563eb;
        }

        .br-summary-label {
            position: relative;
            z-index: 1;
            color: var(--br-muted);
            font-size: .59rem;
            font-weight: 900;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .br-summary-value {
            position: relative;
            z-index: 1;
            margin-top: .38rem;
            color: var(--br-heading);
            font-size: 1.5rem;
            font-weight: 950;
            letter-spacing: -.035em;
        }

        .br-summary-note {
            position: relative;
            z-index: 1;
            margin-top: .28rem;
            color: var(--br-muted);
            font-size: .61rem;
            line-height: 1.35;
        }

        .br-risk-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: .72rem;
            padding: .9rem 1rem 1rem;
        }

        .br-risk-card {
            position: relative;
            overflow: hidden;
            border: 1px solid var(--br-border);
            border-radius: .9rem;
            padding: .86rem;
            background: var(--br-surface);
            cursor: pointer;
            transition:
                transform .16s ease,
                box-shadow .16s ease,
                border-color .16s ease;
        }

        .br-risk-card:hover {
            transform: translateY(-2px);
            border-color: color-mix(
                in srgb,
                var(--br-primary) 32%,
                var(--br-border)
            );
            box-shadow: 0 12px 24px rgba(15,23,42,.075);
        }

        .br-risk-card.is-selected {
            border-color: var(--br-primary);
            box-shadow: 0 0 0 3px color-mix(
                in srgb,
                var(--br-primary) 12%,
                transparent
            );
        }

        .br-risk-card-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .7rem;
        }

        .br-animal-tag {
            color: var(--br-heading);
            font-size: .9rem;
            font-weight: 950;
        }

        .br-animal-meta {
            margin-top: .18rem;
            color: var(--br-muted);
            font-size: .61rem;
            line-height: 1.4;
        }

        .br-score {
            flex: 0 0 auto;
            color: var(--br-heading);
            font-size: 1.2rem;
            font-weight: 950;
            text-align: right;
        }

        .br-score small {
            display: block;
            margin-top: .12rem;
            color: var(--br-muted);
            font-size: .52rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .br-risk-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .45rem;
            margin-top: .72rem;
        }

        .br-mini-stat {
            padding: .48rem;
            border: 1px solid var(--br-border);
            border-radius: .6rem;
            background: var(--br-soft);
        }

        .br-mini-label {
            color: var(--br-muted);
            font-size: .5rem;
            font-weight: 850;
            text-transform: uppercase;
        }

        .br-mini-value {
            margin-top: .17rem;
            color: var(--br-heading);
            font-size: .74rem;
            font-weight: 900;
        }

        .br-risk-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .6rem;
            margin-top: .72rem;
            flex-wrap: wrap;
        }

        .br-recommendation {
            display: inline-flex;
            align-items: center;
            gap: .32rem;
            padding: .34rem .5rem;
            border-radius: 999px;
            font-size: .58rem;
            font-weight: 900;
        }

        .br-recommendation svg {
            width: .75rem;
            height: .75rem;
        }

        .risk-cull {
            color: #991b1b;
            border: 1px solid #fca5a5;
            background: #fee2e2;
        }

        .risk-sell {
            color: #9a3412;
            border: 1px solid #fdba74;
            background: #ffedd5;
        }

        .risk-monitor {
            color: #854d0e;
            border: 1px solid #fcd34d;
            background: #fef3c7;
        }

        .risk-retain {
            color: #166534;
            border: 1px solid #86efac;
            background: #dcfce7;
        }

        .risk-insufficient {
            color: #334155;
            border: 1px solid #cbd5e1;
            background: #e2e8f0;
        }

        .dark .risk-cull {
            color: #fecaca;
            border-color: #ef4444;
            background: #7f1d1d;
        }

        .dark .risk-sell {
            color: #fed7aa;
            border-color: #f97316;
            background: #7c2d12;
        }

        .dark .risk-monitor {
            color: #fef3c7;
            border-color: #f59e0b;
            background: #713f12;
        }

        .dark .risk-retain {
            color: #bbf7d0;
            border-color: #22c55e;
            background: #14532d;
        }

        .dark .risk-insufficient {
            color: #e2e8f0;
            border-color: #64748b;
            background: #334155;
        }

        .br-flags {
            display: flex;
            flex-wrap: wrap;
            gap: .3rem;
        }

        .br-flag {
            padding: .25rem .4rem;
            border: 1px solid var(--br-border);
            border-radius: 999px;
            color: var(--br-muted);
            background: var(--br-soft);
            font-size: .52rem;
            font-weight: 750;
        }

        .br-detail {
            display: grid;
            gap: .9rem;
            padding: 1rem;
        }

        .br-animal-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .9rem;
            padding: .9rem;
            border: 1px solid var(--br-border);
            border-left: 5px solid var(--br-primary);
            border-radius: .85rem;
            background: var(--br-soft);
            flex-wrap: wrap;
        }

        .br-animal-title {
            color: var(--br-heading);
            font-size: 1.05rem;
            font-weight: 950;
        }

        .br-animal-subtitle {
            margin-top: .25rem;
            color: var(--br-muted);
            font-size: .68rem;
        }

        .br-detail-score {
            text-align: right;
        }

        .br-detail-score-value {
            color: var(--br-heading);
            font-size: 1.7rem;
            font-weight: 950;
        }

        .br-detail-score-label {
            color: var(--br-muted);
            font-size: .56rem;
            font-weight: 850;
            text-transform: uppercase;
        }

        .br-metric-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .55rem;
        }

        .br-metric-card {
            padding: .68rem;
            border: 1px solid var(--br-border);
            border-radius: .7rem;
            background: var(--br-surface);
        }

        .br-metric-label {
            color: var(--br-muted);
            font-size: .54rem;
            font-weight: 850;
            text-transform: uppercase;
        }

        .br-metric-value {
            margin-top: .22rem;
            color: var(--br-heading);
            font-size: .9rem;
            font-weight: 950;
        }

        .br-history-tree {
            display: grid;
            gap: .85rem;
        }

        .br-history-node {
            position: relative;
            padding-left: 1.65rem;
        }

        .br-history-node::before {
            content: "";
            position: absolute;
            top: 0;
            bottom: -.85rem;
            left: .48rem;
            width: 2px;
            background: color-mix(
                in srgb,
                var(--br-primary) 28%,
                var(--br-border)
            );
        }

        .br-history-node:last-child::before {
            bottom: 50%;
        }

        .br-history-node::after {
            content: "";
            position: absolute;
            top: 1.05rem;
            left: .48rem;
            width: .82rem;
            height: 2px;
            background: color-mix(
                in srgb,
                var(--br-primary) 38%,
                var(--br-border)
            );
        }

        .br-history-dot {
            position: absolute;
            z-index: 2;
            top: .72rem;
            left: .12rem;
            width: .76rem;
            height: .76rem;
            border: 3px solid var(--br-surface);
            border-radius: 999px;
            background: var(--br-primary);
            box-shadow: 0 0 0 2px color-mix(
                in srgb,
                var(--br-primary) 28%,
                var(--br-border)
            );
        }

        .br-batch-card {
            overflow: hidden;
            border: 1px solid var(--br-border);
            border-radius: .85rem;
            background: var(--br-surface);
        }

        .br-batch-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .7rem;
            padding: .72rem .78rem;
            border-bottom: 1px solid var(--br-border);
            background: var(--br-soft);
            flex-wrap: wrap;
        }

        .br-batch-number {
            color: var(--br-heading);
            font-size: .77rem;
            font-weight: 950;
        }

        .br-batch-name {
            margin-top: .16rem;
            color: var(--br-muted);
            font-size: .59rem;
        }

        .br-status-badge {
            padding: .28rem .45rem;
            border: 1px solid var(--br-border);
            border-radius: 999px;
            color: var(--br-heading);
            background: var(--br-surface);
            font-size: .53rem;
            font-weight: 850;
            text-transform: uppercase;
        }

        .br-batch-body {
            display: grid;
            gap: .72rem;
            padding: .78rem;
        }

        .br-event-list {
            display: grid;
            gap: .45rem;
        }

        .br-event {
            display: grid;
            grid-template-columns: 74px minmax(0, 1fr);
            gap: .55rem;
            padding: .48rem 0;
            border-bottom: 1px dashed var(--br-border);
        }

        .br-event:last-child {
            border-bottom: 0;
        }

        .br-event-date {
            color: var(--br-primary);
            font-size: .56rem;
            font-weight: 900;
        }

        .br-event-title {
            color: var(--br-heading);
            font-size: .64rem;
            font-weight: 900;
        }

        .br-event-detail {
            margin-top: .12rem;
            color: var(--br-muted);
            font-size: .57rem;
            line-height: 1.4;
        }

        .br-offspring-tree {
            position: relative;
            display: grid;
            gap: .42rem;
            margin-left: .3rem;
            padding-left: 1rem;
        }

        .br-offspring-tree::before {
            content: "";
            position: absolute;
            top: .15rem;
            bottom: .15rem;
            left: .25rem;
            width: 1px;
            background: var(--br-border);
        }

        .br-offspring-card {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .55rem;
            padding: .52rem .58rem;
            border: 1px solid var(--br-border);
            border-radius: .6rem;
            background: var(--br-soft);
        }

        .br-offspring-card::before {
            content: "";
            position: absolute;
            top: 50%;
            left: -.77rem;
            width: .77rem;
            height: 1px;
            background: var(--br-border);
        }

        .br-offspring-tag {
            color: var(--br-heading);
            font-size: .63rem;
            font-weight: 900;
        }

        .br-offspring-meta {
            margin-top: .12rem;
            color: var(--br-muted);
            font-size: .54rem;
        }

        .br-offspring-status {
            font-size: .53rem;
            font-weight: 850;
            white-space: nowrap;
        }

        .br-empty {
            padding: 2.4rem 1rem;
            color: var(--br-muted);
            background: var(--br-soft);
            text-align: center;
        }

        @media (min-width: 760px) {
            .br-filter-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }

            .br-summary-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .br-risk-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .br-metric-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        @media (min-width: 1280px) {
            .br-summary-grid {
                grid-template-columns: repeat(6, minmax(0, 1fr));
            }

            .br-risk-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .br-metric-grid {
                grid-template-columns: repeat(6, minmax(0, 1fr));
            }
        }

        @media (max-width: 639px) {
            .br-hero {
                padding: .95rem;
            }

            .br-hero-score {
                width: 100%;
            }

            .br-panel-heading,
            .br-filter-grid,
            .br-risk-grid,
            .br-detail {
                padding-right: .78rem;
                padding-left: .78rem;
            }

            .br-event {
                grid-template-columns: 62px minmax(0, 1fr);
            }
        }
    </style>

    <div class="breeding-risk-dashboard">
        <section class="br-hero">
            <div class="br-hero-content">
                <div>
                    <div class="br-eyebrow">
                        <x-filament::icon
                            icon="heroicon-o-shield-exclamation"
                            class="h-4 w-4"
                        />

                        {{ $farmName }} · Breeding intelligence
                    </div>

                    <div class="br-title">
                        Breeding Performance & Risk Dashboard
                    </div>

                    <div class="br-subtitle">
                        Identify the lowest-performing breeding animals,
                        review abortion and conception history, compare
                        offspring survival, and open a complete batch-by-batch
                        family history before retaining, selling, or culling.
                    </div>

                    <div class="br-hero-pills">
                        <span class="br-hero-pill">
                            {{ number_format($summary['evaluated']) }}
                            evaluated
                        </span>

                        <span class="br-hero-pill">
                            {{ number_format($summary['cull']) }}
                            cull alert{{ $summary['cull'] === 1 ? '' : 's' }}
                        </span>

                        <span class="br-hero-pill">
                            {{ number_format($summary['sell']) }}
                            sale alert{{ $summary['sell'] === 1 ? '' : 's' }}
                        </span>

                        <span class="br-hero-pill">
                            {{ number_format($summary['insufficient']) }}
                            awaiting evidence
                        </span>
                    </div>
                </div>

                <div class="br-hero-score">
                    <div class="br-hero-score-label">
                        Average breeding score
                    </div>

                    <div class="br-hero-score-value">
                        {{ number_format(
                            (float) $summary['average_score'],
                            1
                        ) }}
                    </div>

                    <div class="br-hero-score-note">
                        {{ $farmTagline }}
                    </div>
                </div>
            </div>
        </section>

        <section class="br-panel">
            <div class="br-panel-heading">
                <div>
                    <div class="br-panel-title">
                        Analysis Filters
                    </div>

                    <div class="br-panel-description">
                        Adjust the candidate population and evidence threshold.
                        Rankings refresh automatically.
                    </div>
                </div>
            </div>

            <div class="br-filter-grid">
                <label class="br-filter-field">
                    <span class="br-filter-label">Animal Sex</span>
                    <span class="br-select-wrap">
                        <select
                            wire:model.live="sexFilter"
                            class="br-filter-control"
                        >
                            <option value="all">All breeding animals</option>
                            <option value="Female">Dams / Females</option>
                            <option value="Male">Sires / Males</option>
                        </select>
                    </span>
                </label>

                <label class="br-filter-field">
                    <span class="br-filter-label">Recommendation</span>
                    <span class="br-select-wrap">
                        <select
                            wire:model.live="recommendationFilter"
                            class="br-filter-control"
                        >
                            <option value="all">All recommendations</option>
                            <option value="cull">Cull</option>
                            <option value="sell">Sell</option>
                            <option value="monitor">Monitor</option>
                            <option value="retain">Retain</option>
                            <option value="insufficient_data">
                                Insufficient evidence
                            </option>
                        </select>
                    </span>
                </label>

                <label class="br-filter-field">
                    <span class="br-filter-label">
                        Minimum Evidence
                    </span>
                    <span class="br-select-wrap">
                        <select
                            wire:model.live="minimumEvidence"
                            class="br-filter-control"
                        >
                            @foreach ([1, 2, 3, 4, 5] as $evidence)
                                <option value="{{ $evidence }}">
                                    {{ $evidence }}
                                    record{{ $evidence === 1 ? '' : 's' }}
                                </option>
                            @endforeach
                        </select>
                    </span>
                </label>

                <label class="br-filter-field">
                    <span class="br-filter-label">Animals Shown</span>
                    <span class="br-select-wrap">
                        <select
                            wire:model.live="limit"
                            class="br-filter-control"
                        >
                            @foreach ([8, 12, 16, 24, 36] as $count)
                                <option value="{{ $count }}">
                                    {{ $count }} animals
                                </option>
                            @endforeach
                        </select>
                    </span>
                </label>
            </div>
        </section>

        <section class="br-summary-grid">
            <article class="br-summary-card">
                <div class="br-summary-label">Evaluated</div>
                <div class="br-summary-value">
                    {{ number_format($summary['evaluated']) }}
                </div>
                <div class="br-summary-note">
                    Animals with sufficient evidence
                </div>
            </article>

            <article
                class="br-summary-card"
                data-tone="danger"
            >
                <div class="br-summary-label">Cull Alerts</div>
                <div class="br-summary-value">
                    {{ number_format($summary['cull']) }}
                </div>
                <div class="br-summary-note">
                    Critical reproductive or survival risk
                </div>
            </article>

            <article
                class="br-summary-card"
                data-tone="sell"
            >
                <div class="br-summary-label">Sell Alerts</div>
                <div class="br-summary-value">
                    {{ number_format($summary['sell']) }}
                </div>
                <div class="br-summary-note">
                    Low but non-critical breeding value
                </div>
            </article>

            <article
                class="br-summary-card"
                data-tone="warning"
            >
                <div class="br-summary-label">Monitor</div>
                <div class="br-summary-value">
                    {{ number_format($summary['monitor']) }}
                </div>
                <div class="br-summary-note">
                    Requires additional observation
                </div>
            </article>

            <article
                class="br-summary-card"
                data-tone="success"
            >
                <div class="br-summary-label">Retain</div>
                <div class="br-summary-value">
                    {{ number_format($summary['retain']) }}
                </div>
                <div class="br-summary-note">
                    Positive breeding performance
                </div>
            </article>

            <article
                class="br-summary-card"
                data-tone="info"
            >
                <div class="br-summary-label">
                    Insufficient Evidence
                </div>
                <div class="br-summary-value">
                    {{ number_format($summary['insufficient']) }}
                </div>
                <div class="br-summary-note">
                    Not ranked as poor without enough history
                </div>
            </article>
        </section>

        <section class="br-panel">
            <div class="br-panel-heading">
                <div>
                    <div class="br-panel-title">
                        Lowest Breeding Scores
                    </div>

                    <div class="br-panel-description">
                        Ranked from the lowest score upward. Select an animal
                        to inspect every batch, outcome, case, and offspring.
                    </div>
                </div>
            </div>

            @if ($lowest->isEmpty())
                <div class="br-empty">
                    No animals match the current filters.
                </div>
            @else
                <div class="br-risk-grid">
                    @foreach ($lowest as $item)
                        @php
                            $animal = $item['animal'];
                            $metrics = $item['metrics'];
                            $meta = $recommendationMeta(
                                $item['recommendation']
                            );

                            $isSelected =
                                (int) $selectedAnimalId
                                === (int) $animal->id;

                            $primaryRate = $item['role'] === 'dam'
                                ? ($metrics['conception_rate'] ?? 0)
                                : ($metrics['survival_rate'] ?? 0);

                            $secondaryValue = $item['role'] === 'dam'
                                ? ($metrics['abortions'] ?? 0)
                                : ($metrics['direct_offspring'] ?? 0);

                            $secondaryLabel = $item['role'] === 'dam'
                                ? 'Abortions'
                                : 'Offspring';
                        @endphp

                        <article
                            wire:click="selectAnimal({{ $animal->id }})"
                            @class([
                                'br-risk-card',
                                'is-selected' => $isSelected,
                            ])
                        >
                            <div class="br-risk-card-top">
                                <div>
                                    <div class="br-animal-tag">
                                        {{ $animal->tag_number }}
                                    </div>

                                    <div class="br-animal-meta">
                                        {{ $animal->breed?->breed_name
                                            ?? 'Unknown breed' }}
                                        ·
                                        {{ ucfirst($item['role']) }}
                                        ·
                                        {{ $animal->location?->name
                                            ?? 'No location' }}
                                    </div>
                                </div>

                                <div class="br-score">
                                    {{ number_format(
                                        (float) $item['score'],
                                        1
                                    ) }}
                                    <small>Score / 100</small>
                                </div>
                            </div>

                            <div class="br-risk-stats">
                                <div class="br-mini-stat">
                                    <div class="br-mini-label">
                                        Evidence
                                    </div>
                                    <div class="br-mini-value">
                                        {{ number_format(
                                            $item['evidence']
                                        ) }}
                                    </div>
                                </div>

                                <div class="br-mini-stat">
                                    <div class="br-mini-label">
                                        {{ $item['role'] === 'dam'
                                            ? 'Conception'
                                            : 'Survival' }}
                                    </div>
                                    <div class="br-mini-value">
                                        {{ $formatPercent(
                                            $primaryRate
                                        ) }}
                                    </div>
                                </div>

                                <div class="br-mini-stat">
                                    <div class="br-mini-label">
                                        {{ $secondaryLabel }}
                                    </div>
                                    <div class="br-mini-value">
                                        {{ number_format(
                                            (int) $secondaryValue
                                        ) }}
                                    </div>
                                </div>
                            </div>

                            <div class="br-risk-footer">
                                <span
                                    class="br-recommendation {{ $meta['class'] }}"
                                >
                                    <x-filament::icon
                                        :icon="$meta['icon']"
                                        class="h-4 w-4"
                                    />
                                    {{ $meta['label'] }}
                                </span>

                                <div class="br-flags">
                                    @foreach (
                                        array_slice(
                                            $item['risk_flags'],
                                            0,
                                            2
                                        )
                                        as $flag
                                    )
                                        <span class="br-flag">
                                            {{ $flag }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="br-panel">
            <div class="br-panel-heading">
                <div>
                    <div class="br-panel-title">
                        Selected Animal Breeding History
                    </div>

                    <div class="br-panel-description">
                        Batch-by-batch timeline, pregnancy cases, delivery
                        outcomes, and linked offspring survival.
                    </div>
                </div>
            </div>

            @if (! $selectedAnimal || ! $selectedSnapshot)
                <div class="br-empty">
                    Select an animal above to inspect its complete history.
                </div>
            @else
                @php
                    $selectedMetrics =
                        $selectedSnapshot['metrics'];

                    $selectedHistory =
                        $selectedSnapshot['history'];

                    $selectedRecommendation =
                        $recommendationMeta(
                            $selectedMetrics['recommendation']
                                ?? 'insufficient_data'
                        );

                    $selectedIsDam =
                        $selectedAnimal->sex === 'Female';
                @endphp

                <div class="br-detail">
                    <div class="br-animal-header">
                        <div>
                            <div class="br-animal-title">
                                {{ $selectedAnimal->tag_number }}
                                ·
                                {{ $selectedAnimal->breed?->breed_name
                                    ?? 'Unknown breed' }}
                            </div>

                            <div class="br-animal-subtitle">
                                {{ $selectedAnimal->sex }}
                                ·
                                {{ $selectedAnimal->location?->name
                                    ?? 'Location not assigned' }}
                                ·
                                Sire:
                                {{ $selectedAnimal->sire?->tag_number
                                    ?? 'Unknown' }}
                                ·
                                Dam:
                                {{ $selectedAnimal->dam?->tag_number
                                    ?? 'Unknown' }}
                            </div>

                            <div
                                style="margin-top:.55rem"
                            >
                                <span
                                    class="br-recommendation {{ $selectedRecommendation['class'] }}"
                                >
                                    <x-filament::icon
                                        :icon="$selectedRecommendation['icon']"
                                        class="h-4 w-4"
                                    />
                                    {{ $selectedRecommendation['label'] }}
                                </span>
                            </div>
                        </div>

                        <div class="br-detail-score">
                            <div class="br-detail-score-value">
                                {{ number_format(
                                    (float) (
                                        $selectedMetrics['score']
                                        ?? 0
                                    ),
                                    1
                                ) }}
                            </div>
                            <div class="br-detail-score-label">
                                Breeding score / 100
                            </div>
                        </div>
                    </div>

                    <div class="br-metric-grid">
                        @if ($selectedIsDam)
                            <div class="br-metric-card">
                                <div class="br-metric-label">
                                    Services
                                </div>
                                <div class="br-metric-value">
                                    {{ number_format(
                                        (int) (
                                            $selectedMetrics['services']
                                            ?? 0
                                        )
                                    ) }}
                                </div>
                            </div>

                            <div class="br-metric-card">
                                <div class="br-metric-label">
                                    Conception
                                </div>
                                <div class="br-metric-value">
                                    {{ $formatPercent(
                                        $selectedMetrics[
                                            'conception_rate'
                                        ] ?? 0
                                    ) }}
                                </div>
                            </div>

                            <div class="br-metric-card">
                                <div class="br-metric-label">
                                    Deliveries
                                </div>
                                <div class="br-metric-value">
                                    {{ number_format(
                                        (int) (
                                            $selectedMetrics[
                                                'deliveries'
                                            ] ?? 0
                                        )
                                    ) }}
                                </div>
                            </div>

                            <div class="br-metric-card">
                                <div class="br-metric-label">
                                    Abortions
                                </div>
                                <div class="br-metric-value">
                                    {{ number_format(
                                        (int) (
                                            $selectedMetrics[
                                                'abortions'
                                            ] ?? 0
                                        )
                                    ) }}
                                </div>
                            </div>

                            <div class="br-metric-card">
                                <div class="br-metric-label">
                                    Offspring Survival
                                </div>
                                <div class="br-metric-value">
                                    {{ $formatPercent(
                                        $selectedMetrics[
                                            'live_birth_survival_rate'
                                        ] ?? 0
                                    ) }}
                                </div>
                            </div>

                            <div class="br-metric-card">
                                <div class="br-metric-label">
                                    Mothering
                                </div>
                                <div class="br-metric-value">
                                    {{ number_format(
                                        (float) (
                                            $selectedMetrics[
                                                'mothering_score'
                                            ] ?? 0
                                        ),
                                        1
                                    ) }}/5
                                </div>
                            </div>
                        @else
                            <div class="br-metric-card">
                                <div class="br-metric-label">
                                    Direct Offspring
                                </div>
                                <div class="br-metric-value">
                                    {{ number_format(
                                        (int) (
                                            $selectedMetrics[
                                                'direct_offspring'
                                            ] ?? 0
                                        )
                                    ) }}
                                </div>
                            </div>

                            <div class="br-metric-card">
                                <div class="br-metric-label">
                                    Descendants
                                </div>
                                <div class="br-metric-value">
                                    {{ number_format(
                                        (int) (
                                            $selectedMetrics[
                                                'all_descendants'
                                            ] ?? 0
                                        )
                                    ) }}
                                </div>
                            </div>

                            <div class="br-metric-card">
                                <div class="br-metric-label">
                                    Survival
                                </div>
                                <div class="br-metric-value">
                                    {{ $formatPercent(
                                        $selectedMetrics[
                                            'survival_rate'
                                        ] ?? 0
                                    ) }}
                                </div>
                            </div>

                            <div class="br-metric-card">
                                <div class="br-metric-label">
                                    Breeder Conversion
                                </div>
                                <div class="br-metric-value">
                                    {{ $formatPercent(
                                        $selectedMetrics[
                                            'breeder_conversion_rate'
                                        ] ?? 0
                                    ) }}
                                </div>
                            </div>

                            <div class="br-metric-card">
                                <div class="br-metric-label">
                                    Average Purity
                                </div>
                                <div class="br-metric-value">
                                    {{ $formatPercent(
                                        $selectedMetrics[
                                            'average_offspring_purity'
                                        ] ?? 0
                                    ) }}
                                </div>
                            </div>

                            <div class="br-metric-card">
                                <div class="br-metric-label">
                                    Breeder Offspring
                                </div>
                                <div class="br-metric-value">
                                    {{ number_format(
                                        (int) (
                                            $selectedMetrics[
                                                'breeder_offspring'
                                            ] ?? 0
                                        )
                                    ) }}
                                </div>
                            </div>
                        @endif
                    </div>

                    @if ($selectedHistory['records']->isEmpty())
                        <div class="br-empty">
                            No breeding batch history is available for this
                            animal yet.
                        </div>
                    @else
                        <div class="br-history-tree">
                            @foreach (
                                $selectedHistory['records']
                                as $history
                            )
                                @php
                                    $statusLabel = str(
                                        $history['pregnancy_status']
                                    )
                                        ->replace('_', ' ')
                                        ->title();

                                    $statusClass = match (
                                        $history['pregnancy_status']
                                    ) {
                                        'delivered',
                                        'confirmed' => 'risk-retain',
                                        'aborted' => 'risk-cull',
                                        'not_pregnant' => 'risk-sell',
                                        default => 'risk-monitor',
                                    };
                                @endphp

                                <div class="br-history-node">
                                    <span class="br-history-dot"></span>

                                    <article class="br-batch-card">
                                        <div class="br-batch-head">
                                            <div>
                                                <div class="br-batch-number">
                                                    {{ $history['batch_number'] }}
                                                    @if ($history['archived'])
                                                        · Archived
                                                    @endif
                                                </div>

                                                <div class="br-batch-name">
                                                    {{ $history['batch_name'] }}
                                                    · Mated
                                                    {{ $history['mating_date']
                                                        ?? 'date unknown' }}
                                                </div>
                                            </div>

                                            <span
                                                class="br-status-badge {{ $statusClass }}"
                                            >
                                                {{ $statusLabel }}
                                            </span>
                                        </div>

                                        <div class="br-batch-body">
                                            <div class="br-risk-stats">
                                                <div class="br-mini-stat">
                                                    <div class="br-mini-label">
                                                        Pair
                                                    </div>
                                                    <div class="br-mini-value">
                                                        {{ $history['sire'] }}
                                                        ×
                                                        {{ $history['dam'] }}
                                                    </div>
                                                </div>

                                                <div class="br-mini-stat">
                                                    <div class="br-mini-label">
                                                        Live Births
                                                    </div>
                                                    <div class="br-mini-value">
                                                        {{ number_format(
                                                            $history[
                                                                'live_birth_count'
                                                            ]
                                                        ) }}
                                                    </div>
                                                </div>

                                                <div class="br-mini-stat">
                                                    <div class="br-mini-label">
                                                        Stillborn / Neonatal
                                                    </div>
                                                    <div class="br-mini-value">
                                                        {{ number_format(
                                                            $history[
                                                                'stillborn_count'
                                                            ]
                                                        ) }}
                                                        /
                                                        {{ number_format(
                                                            $history[
                                                                'neonatal_death_count'
                                                            ]
                                                        ) }}
                                                    </div>
                                                </div>
                                            </div>

                                            @if (
                                                count(
                                                    $history['risk_flags']
                                                ) > 0
                                            )
                                                <div class="br-flags">
                                                    @foreach (
                                                        $history['risk_flags']
                                                        as $flag
                                                    )
                                                        <span class="br-flag">
                                                            {{ $flag }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif

                                            @if (
                                                count(
                                                    $history['events']
                                                ) > 0
                                            )
                                                <div class="br-event-list">
                                                    @foreach (
                                                        $history['events']
                                                        as $event
                                                    )
                                                        <div class="br-event">
                                                            <div class="br-event-date">
                                                                {{ $event['date'] }}
                                                            </div>

                                                            <div>
                                                                <div class="br-event-title">
                                                                    {{ $event['title'] }}
                                                                </div>

                                                                <div class="br-event-detail">
                                                                    {{ $event['detail'] }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif

                                            @if (
                                                count(
                                                    $history['offspring']
                                                ) > 0
                                            )
                                                <div>
                                                    <div
                                                        class="br-panel-title"
                                                        style="font-size:.68rem;margin-bottom:.45rem"
                                                    >
                                                        Registered Offspring
                                                    </div>

                                                    <div class="br-offspring-tree">
                                                        @foreach (
                                                            $history['offspring']
                                                            as $offspring
                                                        )
                                                            <a
                                                                href="{{ \App\Filament\Resources\AnimalResource::getUrl(
                                                                    'profile',
                                                                    ['record' => $offspring['id']]
                                                                ) }}"
                                                                target="_blank"
                                                                class="br-offspring-card"
                                                            >
                                                                <div>
                                                                    <div class="br-offspring-tag">
                                                                        {{ $offspring['tag_number'] }}
                                                                    </div>

                                                                    <div class="br-offspring-meta">
                                                                        {{ $offspring['sex'] }}
                                                                        ·
                                                                        {{ $offspring['breed']
                                                                            ?? 'Unknown breed' }}
                                                                        · Born
                                                                        {{ $offspring['date_of_birth']
                                                                            ?? 'unknown date' }}
                                                                    </div>
                                                                </div>

                                                                <div
                                                                    class="br-offspring-status"
                                                                    style="color: {{ $offspring['surviving']
                                                                        ? $successColor
                                                                        : $dangerColor }}"
                                                                >
                                                                    {{ $offspring['status'] }}
                                                                    @if (
                                                                        $offspring['is_breeder']
                                                                    )
                                                                        · Breeder
                                                                    @endif
                                                                </div>
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif

                                            @if (
                                                filled(
                                                    $history['delivery_notes']
                                                )
                                                || filled(
                                                    $history['maternal_notes']
                                                )
                                            )
                                                <div class="br-mini-stat">
                                                    <div class="br-mini-label">
                                                        Case Notes
                                                    </div>

                                                    <div
                                                        class="br-mini-value"
                                                        style="font-size:.61rem;line-height:1.5"
                                                    >
                                                        {{ $history['delivery_notes']
                                                            ?: $history['maternal_notes'] }}
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </article>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </section>
    </div>
</x-filament-panels::page>
