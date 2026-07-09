<x-filament-panels::page>
    @php
        $animal = $this->selectedAnimal;
        $tree = $this->tree;
        $metrics = $this->metrics;
        $latestReview = $this->latestReview;
        $topSires = $this->topSires;
        $topDams = $this->topDams;

        $farmName = setting('farm.name', 'Penzi Farm');
        $farmTagline = setting('farm.tagline', 'Nurturing Nature, Feeding The Future');

        $primary = trim(setting('theme.primary', '#14532d'));
        $secondary = trim(setting('theme.secondary', '#166534'));
        $accent = trim(setting('theme.accent', '#f59e0b'));
        $danger = trim(setting('theme.danger', '#dc2626'));
        $success = trim(setting('theme.success', '#16a34a'));

        $recommendation = $metrics['recommendation'] ?? 'insufficient_data';
        $recommendationLabel = str($recommendation)
            ->replace('_', ' ')
            ->title();

        $recommendationColor = match ($recommendation) {
            'retain' => $success,
            'monitor' => $accent,
            'sell' => '#ea580c',
            'cull' => $danger,
            default => '#64748b',
        };

        $treeModeLabel = $mode === 'ancestors'
            ? 'Ancestral lineage'
            : 'Progeny lineage';
    @endphp

    <style>
        .progeny-page {
            --progeny-primary: {{ $primary }};
            --progeny-secondary: {{ $secondary }};
            --progeny-accent: {{ $accent }};
            --progeny-danger: {{ $danger }};
            --progeny-success: {{ $success }};
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .progeny-shell {
            border: 1px solid color-mix(in srgb, var(--progeny-primary) 14%, #e5e7eb);
            background:
                radial-gradient(circle at top right, color-mix(in srgb, var(--progeny-primary) 7%, transparent), transparent 30%),
                linear-gradient(180deg, rgba(255, 255, 255, .99), rgba(249, 250, 251, .95));
            box-shadow: 0 15px 40px rgba(2, 6, 23, .055);
        }

        .dark .progeny-shell {
            background:
                radial-gradient(circle at top right, color-mix(in srgb, var(--progeny-primary) 17%, transparent), transparent 32%),
                linear-gradient(180deg, rgba(17, 24, 39, .97), rgba(15, 23, 42, .95));
            border-color: rgba(148, 163, 184, .14);
        }

        .progeny-command {
            overflow: hidden;
        }

        .progeny-command-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: .9rem 1rem;
            color: #fff;
            background:
                radial-gradient(circle at top right, rgba(255, 255, 255, .18), transparent 28%),
                linear-gradient(135deg, var(--progeny-primary), var(--progeny-secondary), #052e16);
        }

        .progeny-command-kicker {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            font-size: .64rem;
            font-weight: 950;
            letter-spacing: .07em;
            text-transform: uppercase;
            opacity: .9;
        }

        .progeny-command-title {
            margin-top: .22rem;
            font-size: clamp(1rem, 2vw, 1.35rem);
            line-height: 1.2;
            font-weight: 950;
            letter-spacing: -.025em;
        }

        .progeny-command-subtitle {
            margin-top: .22rem;
            max-width: 800px;
            color: rgba(255, 255, 255, .8);
            font-size: .68rem;
            line-height: 1.45;
        }

        .progeny-command-status {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            gap: .45rem;
            padding: .48rem .68rem;
            border: 1px solid rgba(255, 255, 255, .2);
            background: rgba(255, 255, 255, .12);
            font-size: .6rem;
            font-weight: 950;
            white-space: nowrap;
        }

        .progeny-command-body {
            padding: .85rem;
        }

        .progeny-control-grid {
            display: grid;
            grid-template-columns: minmax(0, 2fr) minmax(190px, .85fr) minmax(170px, .72fr);
            gap: .65rem;
        }

        .progeny-control-card {
            min-width: 0;
            padding: .65rem;
            border: 1px solid #e5e7eb;
            border-left: 3px solid var(--control-color);
            background: rgba(255, 255, 255, .97);
            box-shadow: 0 8px 22px rgba(2, 6, 23, .04);
        }

        .dark .progeny-control-card {
            background: rgba(31, 41, 55, .93);
            border-color: rgba(148, 163, 184, .14);
        }

        .progeny-control-label {
            display: flex;
            align-items: center;
            gap: .35rem;
            margin-bottom: .42rem;
            color: #374151;
            font-size: .62rem;
            font-weight: 950;
            letter-spacing: .055em;
            text-transform: uppercase;
        }

        .progeny-control-label svg {
            color: var(--control-color);
        }

        .dark .progeny-control-label {
            color: #e5e7eb;
        }

        .progeny-select-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }

        .progeny-select-leading {
            position: absolute;
            left: .62rem;
            z-index: 2;
            width: 1rem;
            height: 1rem;
            color: var(--control-color);
            pointer-events: none;
        }

        .progeny-select-chevron {
            position: absolute;
            right: .62rem;
            z-index: 2;
            width: .92rem;
            height: .92rem;
            color: #64748b;
            pointer-events: none;
        }

        .progeny-select {
            appearance: none !important;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            width: 100%;
            min-height: 42px;
            border: 1px solid #d1d5db;
            border-radius: 0;
            outline: none;
            background-color: #fff !important;
            background-image: none !important;
            color: #111827;
            padding: .55rem 2rem .55rem 2.15rem;
            font-size: .73rem;
            line-height: 1.25;
            font-weight: 800;
            transition: border-color .18s ease, box-shadow .18s ease;
        }

        .progeny-select::-ms-expand {
            display: none;
        }

        .progeny-select:focus {
            border-color: var(--control-color);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--control-color) 13%, transparent);
        }

        .dark .progeny-select {
            background-color: #111827 !important;
            color: #f9fafb;
            border-color: rgba(148, 163, 184, .24);
        }

        .progeny-control-help {
            margin-top: .35rem;
            color: #9ca3af;
            font-size: .52rem;
            line-height: 1.35;
            font-weight: 700;
        }

        .progeny-command-summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .65rem;
            flex-wrap: wrap;
            margin-top: .65rem;
            padding: .58rem .65rem;
            border: 1px solid color-mix(in srgb, var(--progeny-primary) 14%, #e5e7eb);
            background: color-mix(in srgb, var(--progeny-primary) 5%, white);
            color: #475569;
            font-size: .6rem;
            line-height: 1.4;
            font-weight: 800;
        }

        .dark .progeny-command-summary {
            background: color-mix(in srgb, var(--progeny-primary) 11%, #111827);
            border-color: rgba(148, 163, 184, .14);
            color: #cbd5e1;
        }

        .progeny-command-summary strong {
            color: var(--progeny-primary);
        }

        .progeny-hero {
            position: relative;
            overflow: hidden;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 1rem;
            align-items: stretch;
            padding: 1rem;
            color: #fff;
            background:
                radial-gradient(circle at top right, rgba(255, 255, 255, .20), transparent 30%),
                radial-gradient(circle at bottom left, color-mix(in srgb, var(--progeny-accent) 28%, transparent), transparent 28%),
                linear-gradient(135deg, var(--progeny-primary), var(--progeny-secondary), #052e16);
            box-shadow: 0 20px 50px rgba(2, 6, 23, .15);
        }

        .progeny-hero::after {
            content: "";
            position: absolute;
            top: -65px;
            right: -55px;
            width: 190px;
            height: 190px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .08);
            pointer-events: none;
        }

        .progeny-hero-main,
        .progeny-score-panel {
            position: relative;
            z-index: 2;
        }

        .progeny-hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            font-size: .61rem;
            font-weight: 950;
            letter-spacing: .06em;
            text-transform: uppercase;
            opacity: .84;
        }

        .progeny-hero-tag {
            margin-top: .3rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: clamp(1.55rem, 3vw, 2.45rem);
            line-height: 1;
            font-weight: 950;
            letter-spacing: .02em;
        }

        .progeny-hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
            margin-top: .58rem;
        }

        .progeny-hero-pill {
            display: inline-flex;
            align-items: center;
            gap: .28rem;
            padding: .34rem .5rem;
            border: 1px solid rgba(255, 255, 255, .2);
            background: rgba(255, 255, 255, .12);
            font-size: .58rem;
            font-weight: 900;
        }

        .progeny-parent-strip {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .48rem;
            margin-top: .7rem;
            max-width: 700px;
        }

        .progeny-parent-card {
            padding: .52rem .58rem;
            border: 1px solid rgba(255, 255, 255, .18);
            background: rgba(255, 255, 255, .09);
        }

        .progeny-parent-label {
            font-size: .5rem;
            font-weight: 950;
            letter-spacing: .05em;
            text-transform: uppercase;
            opacity: .7;
        }

        .progeny-parent-value {
            margin-top: .16rem;
            font-size: .66rem;
            font-weight: 950;
        }

        .progeny-score-panel {
            min-width: 160px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: .8rem;
            border: 1px solid rgba(255, 255, 255, .2);
            background: rgba(255, 255, 255, .12);
            backdrop-filter: blur(12px);
            text-align: center;
        }

        .progeny-score-value {
            font-size: 2rem;
            line-height: .95;
            font-weight: 950;
        }

        .progeny-score-label {
            margin-top: .28rem;
            font-size: .54rem;
            font-weight: 950;
            letter-spacing: .055em;
            text-transform: uppercase;
        }

        .progeny-score-recommendation {
            margin-top: .5rem;
            padding: .28rem .42rem;
            border: 1px solid rgba(255, 255, 255, .22);
            background: rgba(255, 255, 255, .12);
            font-size: .52rem;
            font-weight: 950;
            text-transform: uppercase;
        }

        .progeny-section,
        .progeny-ranking-wrap {
            padding: .9rem;
        }

        .progeny-section-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .85rem;
            flex-wrap: wrap;
            margin-bottom: .75rem;
        }

        .progeny-section-kicker {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            color: var(--progeny-primary);
            font-size: .6rem;
            font-weight: 950;
            letter-spacing: .055em;
            text-transform: uppercase;
        }

        .progeny-section-title {
            margin-top: .18rem;
            color: #111827;
            font-size: .98rem;
            font-weight: 950;
            letter-spacing: -.02em;
        }

        .dark .progeny-section-title {
            color: #f9fafb;
        }

        .progeny-section-note {
            margin-top: .2rem;
            max-width: 820px;
            color: #6b7280;
            font-size: .67rem;
            line-height: 1.5;
        }

        .dark .progeny-section-note {
            color: #9ca3af;
        }

        .progeny-section-badge {
            display: inline-flex;
            align-items: center;
            gap: .32rem;
            padding: .4rem .6rem;
            color: var(--progeny-primary);
            background: color-mix(in srgb, var(--progeny-primary) 8%, white);
            border: 1px solid color-mix(in srgb, var(--progeny-primary) 16%, white);
            font-size: .58rem;
            font-weight: 950;
            white-space: nowrap;
        }

        .dark .progeny-section-badge {
            background: color-mix(in srgb, var(--progeny-primary) 14%, #111827);
            border-color: color-mix(in srgb, var(--progeny-primary) 24%, #374151);
        }

        .progeny-metrics {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .55rem;
        }

        @media (min-width: 900px) {
            .progeny-metrics {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        .progeny-metric {
            position: relative;
            min-height: 86px;
            padding: .66rem;
            border: 1px solid #e5e7eb;
            border-left: 3px solid var(--metric-color);
            background: rgba(255, 255, 255, .97);
            box-shadow: 0 8px 22px rgba(2, 6, 23, .04);
            overflow: hidden;
        }

        .progeny-metric::after {
            content: "";
            position: absolute;
            top: -30px;
            right: -30px;
            width: 78px;
            height: 78px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--metric-color) 9%, transparent);
        }

        .dark .progeny-metric {
            background: rgba(31, 41, 55, .93);
            border-color: rgba(148, 163, 184, .14);
        }

        .progeny-metric-label,
        .progeny-metric-value {
            position: relative;
            z-index: 2;
        }

        .progeny-metric-label {
            color: #6b7280;
            font-size: .54rem;
            font-weight: 950;
            letter-spacing: .05em;
            text-transform: uppercase;
        }

        .progeny-metric-value {
            margin-top: .38rem;
            color: #111827;
            font-size: 1.28rem;
            line-height: 1;
            font-weight: 950;
        }

        .dark .progeny-metric-value {
            color: #f9fafb;
        }

        .progeny-review {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, .8fr);
            gap: .65rem;
            margin-top: .7rem;
        }

        .progeny-review-card {
            padding: .7rem;
            border: 1px solid color-mix(in srgb, var(--review-color) 26%, #e5e7eb);
            border-left: 4px solid var(--review-color);
            background: color-mix(in srgb, var(--review-color) 6%, white);
        }

        .dark .progeny-review-card {
            background: color-mix(in srgb, var(--review-color) 12%, #111827);
        }

        .progeny-review-title {
            color: var(--review-color);
            font-size: .66rem;
            font-weight: 950;
            text-transform: uppercase;
        }

        .progeny-review-text {
            margin-top: .26rem;
            color: #4b5563;
            font-size: .66rem;
            line-height: 1.5;
        }

        .dark .progeny-review-text {
            color: #d1d5db;
        }

        .progeny-tree-toolbar {
            display: flex;
            align-items: center;
            gap: .45rem;
            flex-wrap: wrap;
        }

        .progeny-tree-legend {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .3rem .42rem;
            border: 1px solid #e5e7eb;
            background: #fff;
            color: #64748b;
            font-size: .52rem;
            font-weight: 900;
        }

        .dark .progeny-tree-legend {
            background: #111827;
            border-color: rgba(148, 163, 184, .17);
        }

        .progeny-tree-dot {
            width: .42rem;
            height: .42rem;
            border-radius: 999px;
            background: var(--legend-color);
        }

        .progeny-tree-scroll {
            max-height: 770px;
            overflow: auto;
            padding: .35rem .35rem 1rem;
            scrollbar-width: thin;
            scrollbar-color: color-mix(in srgb, var(--progeny-primary) 35%, #cbd5e1) transparent;
        }

        .progeny-tree-node {
            min-width: 245px;
        }

        .progeny-node-card {
            position: relative;
            max-width: 295px;
            padding: .63rem;
            border: 1px solid color-mix(in srgb, var(--node-color) 25%, #e5e7eb);
            border-left: 4px solid var(--node-color);
            background: #fff;
            box-shadow: 0 8px 20px rgba(2, 6, 23, .045);
        }

        .dark .progeny-node-card {
            background: rgba(31, 41, 55, .94);
            border-color: color-mix(in srgb, var(--node-color) 28%, #374151);
        }

        .progeny-node-card.is-circular {
            opacity: .72;
        }

        .progeny-node-top {
            display: flex;
            align-items: flex-start;
            gap: .5rem;
        }

        .progeny-node-icon {
            width: 31px;
            height: 31px;
            flex: 0 0 31px;
            display: grid;
            place-items: center;
            color: var(--node-color);
            background: color-mix(in srgb, var(--node-color) 10%, white);
            border: 1px solid color-mix(in srgb, var(--node-color) 18%, white);
        }

        .progeny-node-main {
            min-width: 0;
            flex: 1;
        }

        .progeny-node-tag {
            color: #111827;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: .72rem;
            font-weight: 950;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dark .progeny-node-tag {
            color: #f9fafb;
        }

        .progeny-node-breed {
            margin-top: .12rem;
            color: #6b7280;
            font-size: .55rem;
            font-weight: 800;
        }

        .progeny-node-generation {
            color: var(--node-color);
            font-size: .55rem;
            font-weight: 950;
        }

        .progeny-node-meta {
            display: flex;
            flex-wrap: wrap;
            gap: .25rem;
            margin-top: .5rem;
        }

        .progeny-node-meta span,
        .progeny-node-badge,
        .progeny-node-warning {
            padding: .2rem .32rem;
            border: 1px solid #e5e7eb;
            background: #f8fafc;
            color: #64748b;
            font-size: .47rem;
            font-weight: 850;
        }

        .dark .progeny-node-meta span,
        .dark .progeny-node-badge,
        .dark .progeny-node-warning {
            background: #111827;
            border-color: rgba(148, 163, 184, .17);
        }

        .progeny-node-badge {
            margin-top: .38rem;
            color: var(--progeny-success);
        }

        .progeny-node-warning {
            margin-top: .38rem;
            color: var(--progeny-danger);
        }

        .progeny-children {
            margin-left: 1rem;
            padding-left: 1rem;
            border-left: 2px solid color-mix(in srgb, var(--progeny-primary) 23%, #d1d5db);
        }

        .progeny-child-branch {
            position: relative;
            padding-top: .72rem;
        }

        .progeny-child-branch::before {
            content: "";
            position: absolute;
            left: -1rem;
            top: 1.58rem;
            width: 1rem;
            border-top: 2px solid color-mix(in srgb, var(--progeny-primary) 23%, #d1d5db);
        }

        .progeny-ranking-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: .75rem;
        }

        @media (min-width: 1024px) {
            .progeny-ranking-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .progeny-ranking-block {
            padding: .68rem;
            border: 1px solid #e5e7eb;
            background: rgba(255, 255, 255, .96);
        }

        .dark .progeny-ranking-block {
            background: rgba(31, 41, 55, .92);
            border-color: rgba(148, 163, 184, .14);
        }

        .progeny-ranking-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .45rem;
            margin-top: .5rem;
        }

        .progeny-ranking-card {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: .55rem;
            align-items: center;
            padding: .58rem;
            border: 1px solid #e5e7eb;
            border-left: 3px solid var(--rank-color);
            background: #fff;
            text-decoration: none;
            color: inherit;
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .progeny-ranking-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(2, 6, 23, .08);
        }

        .dark .progeny-ranking-card {
            background: #111827;
            border-color: rgba(148, 163, 184, .16);
        }

        .progeny-ranking-tag {
            color: #111827;
            font-size: .66rem;
            font-weight: 950;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dark .progeny-ranking-tag {
            color: #f9fafb;
        }

        .progeny-ranking-meta {
            margin-top: .14rem;
            color: #6b7280;
            font-size: .5rem;
            line-height: 1.35;
        }

        .progeny-ranking-score {
            color: var(--rank-color);
            font-size: 1.1rem;
            font-weight: 950;
        }

        .progeny-empty {
            display: grid;
            place-items: center;
            min-height: 230px;
            padding: 1rem;
            text-align: center;
        }

        .progeny-empty-icon {
            width: 52px;
            height: 52px;
            display: grid;
            place-items: center;
            margin: 0 auto;
            color: var(--progeny-primary);
            background: color-mix(in srgb, var(--progeny-primary) 10%, white);
            border: 1px solid color-mix(in srgb, var(--progeny-primary) 18%, white);
        }

        @media (max-width: 960px) {
            .progeny-control-grid {
                grid-template-columns: 1fr 1fr;
            }

            .progeny-control-card:first-child {
                grid-column: 1 / -1;
            }
        }

        @media (max-width: 760px) {
            .progeny-command-head,
            .progeny-hero {
                grid-template-columns: 1fr;
            }

            .progeny-command-head {
                align-items: flex-start;
            }

            .progeny-command-status {
                width: 100%;
                justify-content: center;
            }

            .progeny-control-grid,
            .progeny-review,
            .progeny-parent-strip {
                grid-template-columns: 1fr;
            }

            .progeny-control-card:first-child {
                grid-column: auto;
            }

            .progeny-score-panel {
                width: 100%;
                min-width: 0;
            }

            .progeny-tree-scroll {
                max-height: 620px;
            }

            .progeny-ranking-list {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="progeny-page">
        <section class="progeny-command progeny-shell">
            <div class="progeny-command-head">
                <div>
                    <div class="progeny-command-kicker">
                        <x-heroicon-o-sparkles class="h-4 w-4" />
                        {{ $farmName }} breeding intelligence
                    </div>
                    <div class="progeny-command-title">
                        Progeny, Heredity & Breeding Performance
                    </div>
                    <div class="progeny-command-subtitle">
                        {{ $farmTagline }}. Explore registered descendants or ancestors, compare breeding performance, and prepare auditable heredity reports.
                    </div>
                </div>

                <div class="progeny-command-status">
                    <x-heroicon-o-shield-check class="h-4 w-4" />
                    Decision-support analytics
                </div>
            </div>

            <div class="progeny-command-body">
                <div class="progeny-control-grid">
                    <label class="progeny-control-card" style="--control-color: #2563eb;">
                        <span class="progeny-control-label">
                            <x-heroicon-o-identification class="h-4 w-4" />
                            Breeding Animal
                        </span>

                        <span class="progeny-select-wrap">
                            <x-heroicon-o-tag class="progeny-select-leading" />
                            <select wire:model.live="animalId" class="progeny-select">
                                <option value="">Select an animal</option>
                                @foreach ($this->animalOptions as $id => $label)
                                    <option value="{{ $id }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-heroicon-m-chevron-down class="progeny-select-chevron" />
                        </span>

                        <span class="progeny-control-help">
                            Select a sire, dam, offspring, or any animal with recorded parentage.
                        </span>
                    </label>

                    <label class="progeny-control-card" style="--control-color: #7c3aed;">
                        <span class="progeny-control-label">
                            <x-heroicon-o-arrows-right-left class="h-4 w-4" />
                            Tree Direction
                        </span>

                        <span class="progeny-select-wrap">
                            <x-heroicon-o-share class="progeny-select-leading" />
                            <select wire:model.live="mode" class="progeny-select">
                                <option value="descendants">Progeny / Descendants</option>
                                <option value="ancestors">Parents / Ancestors</option>
                            </select>
                            <x-heroicon-m-chevron-down class="progeny-select-chevron" />
                        </span>

                        <span class="progeny-control-help">
                            Move down through offspring or upward through parent lineage.
                        </span>
                    </label>

                    <label class="progeny-control-card" style="--control-color: #059669;">
                        <span class="progeny-control-label">
                            <x-heroicon-o-queue-list class="h-4 w-4" />
                            Generations
                        </span>

                        <span class="progeny-select-wrap">
                            <x-heroicon-o-numbered-list class="progeny-select-leading" />
                            <select wire:model.live="generations" class="progeny-select">
                                @foreach (range(1, 5) as $generation)
                                    <option value="{{ $generation }}">
                                        {{ $generation }} generation{{ $generation === 1 ? '' : 's' }}
                                    </option>
                                @endforeach
                            </select>
                            <x-heroicon-m-chevron-down class="progeny-select-chevron" />
                        </span>

                        <span class="progeny-control-help">
                            Choose how far the screen tree and PDF should expand.
                        </span>
                    </label>
                </div>

                <div class="progeny-command-summary" wire:loading.class="opacity-60">
                    <span>
                        <strong>{{ $treeModeLabel }}</strong>
                        - {{ $generations }} generation{{ $generations === 1 ? '' : 's' }} selected
                    </span>
                    <span>
                        Connector lines represent registered sire, dam, and offspring relationships.
                    </span>
                </div>
            </div>
        </section>

        @if ($animal)
            <section class="progeny-hero">
                <div class="progeny-hero-main">
                    <div class="progeny-hero-eyebrow">
                        <x-heroicon-o-identification class="h-4 w-4" />
                        Selected breeding record
                    </div>

                    <div class="progeny-hero-tag">{{ $animal->tag_number }}</div>

                    <div class="progeny-hero-meta">
                        <span class="progeny-hero-pill">
                            <x-heroicon-o-user class="h-3 w-3" />
                            {{ $animal->sex }}
                        </span>
                        <span class="progeny-hero-pill">
                            <x-heroicon-o-tag class="h-3 w-3" />
                            {{ $animal->breed?->breed_name ?? 'Unknown breed' }}
                        </span>
                        <span class="progeny-hero-pill">
                            <x-heroicon-o-check-badge class="h-3 w-3" />
                            {{ $animal->status }}
                        </span>
                        <span class="progeny-hero-pill">
                            <x-heroicon-o-map-pin class="h-3 w-3" />
                            {{ $animal->location?->name ?? 'No location' }}
                        </span>
                    </div>

                    <div class="progeny-parent-strip">
                        <div class="progeny-parent-card">
                            <div class="progeny-parent-label">Registered Sire</div>
                            <div class="progeny-parent-value">
                                {{ $animal->sire?->tag_number ?? 'Not recorded' }}
                                @if ($animal->sire?->breed)
                                    - {{ $animal->sire->breed->breed_name }}
                                @endif
                            </div>
                        </div>

                        <div class="progeny-parent-card">
                            <div class="progeny-parent-label">Registered Dam</div>
                            <div class="progeny-parent-value">
                                {{ $animal->dam?->tag_number ?? 'Not recorded' }}
                                @if ($animal->dam?->breed)
                                    - {{ $animal->dam->breed->breed_name }}
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="progeny-score-panel">
                    <div class="progeny-score-value">
                        {{ number_format((float) ($metrics['score'] ?? 0), 1) }}
                    </div>
                    <div class="progeny-score-label">Breeding score / 100</div>
                    <div class="progeny-score-recommendation">
                        {{ $recommendationLabel }}
                    </div>
                </div>
            </section>

            <section class="progeny-section progeny-shell">
                <div class="progeny-section-head">
                    <div>
                        <div class="progeny-section-kicker">
                            <x-heroicon-o-chart-bar-square class="h-4 w-4" />
                            Performance evidence
                        </div>
                        <div class="progeny-section-title">
                            {{ $animal->sex === 'Female' ? 'Maternal Performance' : 'Sire Progeny Performance' }}
                        </div>
                        <div class="progeny-section-note">
                            Performance is calculated from registered parent links and breeding outcomes. Any sale or cull recommendation must still be confirmed by authorised management or veterinary staff.
                        </div>
                    </div>

                    <div class="progeny-section-badge">
                        <x-heroicon-o-bolt class="h-4 w-4" />
                        Live analytics
                    </div>
                </div>

                <div class="progeny-metrics">
                    @if (($metrics['role'] ?? null) === 'dam')
                        @foreach ([
                            ['Services', $metrics['services'] ?? 0, '#2563eb'],
                            ['Deliveries', $metrics['deliveries'] ?? 0, '#16a34a'],
                            ['Abortions', $metrics['abortions'] ?? 0, '#dc2626'],
                            ['Live Births', $metrics['live_births'] ?? 0, '#059669'],
                            ['Weaned', $metrics['weaned'] ?? 0, '#7c3aed'],
                            ['Conception', number_format((float) ($metrics['conception_rate'] ?? 0), 1) . '%', '#0891b2'],
                            ['Survival', number_format((float) ($metrics['live_birth_survival_rate'] ?? 0), 1) . '%', '#0f766e'],
                            ['Mothering', number_format((float) ($metrics['mothering_score'] ?? 0), 2) . '/5', '#db2777'],
                        ] as [$label, $value, $color])
                            <div class="progeny-metric" style="--metric-color: {{ $color }};">
                                <div class="progeny-metric-label">{{ $label }}</div>
                                <div class="progeny-metric-value">{{ $value }}</div>
                            </div>
                        @endforeach
                    @else
                        @foreach ([
                            ['Direct Offspring', $metrics['direct_offspring'] ?? 0, '#2563eb'],
                            ['All Descendants', $metrics['all_descendants'] ?? 0, '#7c3aed'],
                            ['Active Offspring', $metrics['active_offspring'] ?? 0, '#16a34a'],
                            ['Breeder Offspring', $metrics['breeder_offspring'] ?? 0, '#059669'],
                            ['Male Offspring', $metrics['male_offspring'] ?? 0, '#0891b2'],
                            ['Female Offspring', $metrics['female_offspring'] ?? 0, '#db2777'],
                            ['Survival', number_format((float) ($metrics['survival_rate'] ?? 0), 1) . '%', '#0f766e'],
                            ['Avg. Purity', number_format((float) ($metrics['average_offspring_purity'] ?? 0), 1) . '%', '#f59e0b'],
                        ] as [$label, $value, $color])
                            <div class="progeny-metric" style="--metric-color: {{ $color }};">
                                <div class="progeny-metric-label">{{ $label }}</div>
                                <div class="progeny-metric-value">{{ $value }}</div>
                            </div>
                        @endforeach
                    @endif
                </div>

                <div class="progeny-review">
                    <div class="progeny-review-card" style="--review-color: {{ $recommendationColor }};">
                        <div class="progeny-review-title">
                            System indication: {{ $recommendationLabel }}
                        </div>
                        <div class="progeny-review-text">
                            {{ $metrics['reason'] ?? 'No performance explanation is available.' }}
                        </div>
                    </div>

                    <div class="progeny-review-card" style="--review-color: {{ $latestReview ? $success : '#64748b' }};">
                        <div class="progeny-review-title">Latest authorised decision</div>
                        <div class="progeny-review-text">
                            @if ($latestReview)
                                <strong>{{ $latestReview->recommendation_label }}</strong><br>
                                {{ $latestReview->reason }}<br>
                                Reviewed {{ $latestReview->reviewed_at?->format('d M Y H:i') }}.
                            @else
                                No authorised breeding decision has been recorded for this animal.
                            @endif
                        </div>
                    </div>
                </div>
            </section>

            <section class="progeny-section progeny-shell">
                <div class="progeny-section-head">
                    <div>
                        <div class="progeny-section-kicker">
                            <x-heroicon-o-share class="h-4 w-4" />
                            Connected heredity map
                        </div>
                        <div class="progeny-section-title">
                            {{ $mode === 'ancestors' ? 'Ancestral Heredity Tree' : 'Progeny and Descendant Tree' }}
                        </div>
                        <div class="progeny-section-note">
                            Showing up to {{ $generations }} generation{{ $generations === 1 ? '' : 's' }}. Each connector line represents a registered relationship in the animal record.
                        </div>
                    </div>

                    <div class="progeny-tree-toolbar">
                        <div class="progeny-tree-legend">
                            <span class="progeny-tree-dot" style="--legend-color: #2563eb;"></span>
                            Male
                        </div>
                        <div class="progeny-tree-legend">
                            <span class="progeny-tree-dot" style="--legend-color: #db2777;"></span>
                            Female
                        </div>
                        <div class="progeny-section-badge">
                            <x-heroicon-o-queue-list class="h-4 w-4" />
                            {{ $generations }} generation{{ $generations === 1 ? '' : 's' }}
                        </div>
                    </div>
                </div>

                <div class="progeny-tree-scroll">
                    @if ($tree)
                        @include('filament.pages.partials.progeny-tree-node', ['node' => $tree])
                    @else
                        <div class="progeny-section-note">No lineage data is available for the selected animal.</div>
                    @endif
                </div>
            </section>
        @else
            <section class="progeny-empty progeny-shell">
                <div>
                    <div class="progeny-empty-icon">
                        <x-heroicon-o-share class="h-6 w-6" />
                    </div>
                    <div class="progeny-section-title" style="margin-top: .7rem;">
                        Select a breeding animal
                    </div>
                    <div class="progeny-section-note">
                        Choose an animal above to display progeny, ancestors, breeding performance, and management recommendations.
                    </div>
                </div>
            </section>
        @endif

        <section class="progeny-ranking-wrap progeny-shell">
            <div class="progeny-section-head">
                <div>
                    <div class="progeny-section-kicker">
                        <x-heroicon-o-trophy class="h-4 w-4" />
                        Farm breeding leaders
                    </div>
                    <div class="progeny-section-title">Most Productive Breeding Animals</div>
                    <div class="progeny-section-note">
                        A compact decision-support view of the strongest currently recorded sires and dams.
                    </div>
                </div>
            </div>

            <div class="progeny-ranking-grid">
                <div class="progeny-ranking-block">
                    <div class="progeny-control-label" style="--control-color: #2563eb;">
                        <x-heroicon-o-shield-check class="h-4 w-4" />
                        Top Sires
                    </div>
                    <div class="progeny-ranking-list">
                        @forelse ($topSires as $entry)
                            <a
                                href="{{ \App\Filament\Pages\ProgenyExplorer::getUrl(['animal' => $entry['animal']->id]) }}"
                                class="progeny-ranking-card"
                                style="--rank-color: #2563eb;"
                            >
                                <div>
                                    <div class="progeny-ranking-tag">{{ $entry['animal']->tag_number }}</div>
                                    <div class="progeny-ranking-meta">
                                        {{ $entry['animal']->breed?->breed_name ?? 'Unknown breed' }} -
                                        {{ $entry['metrics']['direct_offspring'] }} offspring -
                                        {{ number_format($entry['metrics']['survival_rate'], 1) }}% survival
                                    </div>
                                </div>
                                <div class="progeny-ranking-score">
                                    {{ number_format($entry['metrics']['score'], 1) }}
                                </div>
                            </a>
                        @empty
                            <div class="progeny-section-note">No sire progeny data has been recorded.</div>
                        @endforelse
                    </div>
                </div>

                <div class="progeny-ranking-block">
                    <div class="progeny-control-label" style="--control-color: #db2777;">
                        <x-heroicon-o-heart class="h-4 w-4" />
                        Top Dams
                    </div>
                    <div class="progeny-ranking-list">
                        @forelse ($topDams as $entry)
                            <a
                                href="{{ \App\Filament\Pages\ProgenyExplorer::getUrl(['animal' => $entry['animal']->id]) }}"
                                class="progeny-ranking-card"
                                style="--rank-color: #db2777;"
                            >
                                <div>
                                    <div class="progeny-ranking-tag">{{ $entry['animal']->tag_number }}</div>
                                    <div class="progeny-ranking-meta">
                                        {{ $entry['animal']->breed?->breed_name ?? 'Unknown breed' }} -
                                        {{ $entry['metrics']['deliveries'] }} deliveries -
                                        {{ number_format($entry['metrics']['mothering_score'], 1) }}/5 mothering
                                    </div>
                                </div>
                                <div class="progeny-ranking-score">
                                    {{ number_format($entry['metrics']['score'], 1) }}
                                </div>
                            </a>
                        @empty
                            <div class="progeny-section-note">No dam outcome evaluations have been recorded.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>
    </div>
</x-filament-panels::page>
