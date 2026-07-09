<x-filament-panels::page>
    @php
        $primaryColor = trim(setting('theme.primary', '#008f00'));
        $secondaryColor = trim(setting('theme.secondary', '#111827'));
        $accentColor = trim(setting('theme.accent', '#f59e0b'));

        $normalizeHex = function (?string $color, string $fallback): string {
            $color = trim((string) $color);

            if (! str_starts_with($color, '#')) {
                $color = '#' . $color;
            }

            return preg_match('/^#[0-9A-Fa-f]{6}$/', $color) ? $color : $fallback;
        };

        $hexToRgb = function (string $hex): array {
            $hex = ltrim($hex, '#');

            return [
                hexdec(substr($hex, 0, 2)),
                hexdec(substr($hex, 2, 2)),
                hexdec(substr($hex, 4, 2)),
            ];
        };

        $readableText = function (string $hex) use ($hexToRgb): string {
            [$r, $g, $b] = $hexToRgb($hex);
            $brightness = ($r * 299 + $g * 587 + $b * 114) / 1000;

            return $brightness > 150 ? '#0f172a' : '#ffffff';
        };

        $primaryColor = $normalizeHex($primaryColor, '#008f00');
        $secondaryColor = $normalizeHex($secondaryColor, '#111827');
        $accentColor = $normalizeHex($accentColor, '#f59e0b');

        $primaryRgb = implode(',', $hexToRgb($primaryColor));
        $secondaryRgb = implode(',', $hexToRgb($secondaryColor));
        $accentRgb = implode(',', $hexToRgb($accentColor));
        $primaryTextColor = $readableText($primaryColor);

        $activeSeasons = $stats['activeSeasons'] ?? 0;
        $dueSoon = $stats['dueSoon'] ?? 0;
        $nurseryReady = $stats['nurseryReady'] ?? 0;
        $pendingTasks = $stats['pendingTasks'] ?? 0;

        $today = now('Africa/Nairobi')->format('l, d M Y');
    @endphp

    <style>
        .crop-dashboard-wrapper {
            --crop-primary: {{ $primaryColor }};
            --crop-primary-rgb: {{ $primaryRgb }};
            --crop-secondary: {{ $secondaryColor }};
            --crop-secondary-rgb: {{ $secondaryRgb }};
            --crop-accent: {{ $accentColor }};
            --crop-accent-rgb: {{ $accentRgb }};
            --crop-primary-text: {{ $primaryTextColor }};

            --crop-page: #f8fafc;
            --crop-card: #ffffff;
            --crop-card-2: #f8fafc;
            --crop-card-3: #f1f5f9;
            --crop-border: #dbe3ea;
            --crop-text: #0f172a;
            --crop-muted: #475569;
            --crop-soft: #64748b;
            --crop-shadow: 0 16px 45px rgba(15, 23, 42, .08);

            color: var(--crop-text);
            padding: 1.15rem;
        }

        .dark .crop-dashboard-wrapper {
            --crop-page: #020617;
            --crop-card: #0f172a;
            --crop-card-2: #111827;
            --crop-card-3: #1e293b;
            --crop-border: #334155;
            --crop-text: #f8fafc;
            --crop-muted: #cbd5e1;
            --crop-soft: #94a3b8;
            --crop-shadow: 0 18px 55px rgba(0, 0, 0, .36);
        }

        @media (max-width: 640px) {
            .crop-dashboard-wrapper {
                padding: .75rem;
            }
        }

        .crop-hero {
            position: relative;
            overflow: hidden;
            border-radius: 1.5rem;
            background:
                radial-gradient(circle at top right, rgba(var(--crop-accent-rgb), .28), transparent 30%),
                radial-gradient(circle at bottom left, rgba(255, 255, 255, .12), transparent 26%),
                linear-gradient(135deg, var(--crop-primary), var(--crop-secondary));
            color: #ffffff;
            padding: 1.25rem;
            margin-bottom: 1rem;
            box-shadow: var(--crop-shadow);
            border: 1px solid rgba(255, 255, 255, .16);
        }

        .crop-hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                repeating-linear-gradient(
                    135deg,
                    rgba(255, 255, 255, .055) 0,
                    rgba(255, 255, 255, .055) 1px,
                    transparent 1px,
                    transparent 14px
                );
            pointer-events: none;
        }

        .crop-hero-inner {
            position: relative;
            z-index: 1;
        }

        .hero-badges {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            margin-bottom: .95rem;
        }

        .smart-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .45rem;
            min-height: 2.05rem;
            padding: .52rem .86rem;
            border-radius: 999px;
            font-size: .66rem;
            font-weight: 950;
            line-height: 1;
            letter-spacing: .07em;
            text-transform: uppercase;
            white-space: nowrap;
            border: 1px solid transparent;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .08);
        }

        .smart-badge-hero {
            color: #ffffff;
            background: linear-gradient(135deg, rgba(255,255,255,.22), rgba(255,255,255,.10));
            border-color: rgba(255, 255, 255, .26);
            backdrop-filter: blur(14px);
        }

        .smart-badge-primary {
            color: var(--crop-primary);
            background:
                linear-gradient(135deg, rgba(var(--crop-primary-rgb), .18), rgba(var(--crop-primary-rgb), .08));
            border-color: rgba(var(--crop-primary-rgb), .28);
        }

        .dark .smart-badge-primary {
            color: #bbf7d0;
        }

        .smart-badge-accent {
            color: #92400e;
            background:
                linear-gradient(135deg, rgba(var(--crop-accent-rgb), .20), rgba(var(--crop-accent-rgb), .08));
            border-color: rgba(var(--crop-accent-rgb), .30);
        }

        .dark .smart-badge-accent {
            color: #fde68a;
        }

        .smart-badge-purple {
            color: #4f46e5;
            background: linear-gradient(135deg, rgba(99,102,241,.18), rgba(99,102,241,.08));
            border-color: rgba(99,102,241,.30);
        }

        .dark .smart-badge-purple {
            color: #c4b5fd;
        }

        .smart-badge-danger {
            color: #dc2626;
            background: linear-gradient(135deg, rgba(239,68,68,.18), rgba(239,68,68,.08));
            border-color: rgba(239,68,68,.30);
        }

        .dark .smart-badge-danger {
            color: #fca5a5;
        }

        .smart-badge-success {
            color: #15803d;
            background: linear-gradient(135deg, rgba(34,197,94,.18), rgba(34,197,94,.08));
            border-color: rgba(34,197,94,.30);
        }

        .dark .smart-badge-success {
            color: #86efac;
        }

        .hero-title {
            font-size: clamp(1.45rem, 3vw, 2.25rem);
            font-weight: 950;
            line-height: 1.08;
            letter-spacing: -.04em;
            color: #ffffff;
            margin-bottom: .6rem;
        }

        .hero-subtitle {
            max-width: 780px;
            color: rgba(255, 255, 255, .88);
            font-size: .88rem;
            line-height: 1.65;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .85rem;
            margin-bottom: 1rem;
        }

        @media (max-width: 1100px) {
            .stats-row {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .stat-card,
        .section-card,
        .item-card,
        .mini-stat,
        .next-action-box,
        .task-instruction-box {
            background: var(--crop-card);
            color: var(--crop-text);
            border: 1px solid var(--crop-border);
        }

        .stat-card {
            border-radius: 1.15rem;
            padding: 1rem;
            box-shadow: var(--crop-shadow);
            transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
        }

        @media (hover: hover) {
            .stat-card:hover,
            .item-card:hover {
                transform: translateY(-3px);
                border-color: rgba(var(--crop-primary-rgb), .34);
                box-shadow: 0 20px 55px rgba(15, 23, 42, .13);
            }

            .dark .stat-card:hover,
            .dark .item-card:hover {
                box-shadow: 0 22px 65px rgba(0, 0, 0, .45);
            }
        }

        .stat-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .65rem;
            margin-bottom: .75rem;
        }

        .stat-icon-wrapper {
            width: 2.45rem;
            height: 2.45rem;
            border-radius: .9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            border: 1px solid transparent;
        }

        .stat-icon-primary {
            background: rgba(var(--crop-primary-rgb), .13);
            color: var(--crop-primary);
            border-color: rgba(var(--crop-primary-rgb), .22);
        }

        .dark .stat-icon-primary {
            color: #bbf7d0;
        }

        .stat-icon-accent {
            background: rgba(var(--crop-accent-rgb), .14);
            color: var(--crop-accent);
            border-color: rgba(var(--crop-accent-rgb), .24);
        }

        .stat-icon-purple {
            background: rgba(99, 102, 241, .14);
            color: #6366f1;
            border-color: rgba(99, 102, 241, .24);
        }

        .dark .stat-icon-purple {
            color: #c4b5fd;
        }

        .stat-icon-red {
            background: rgba(239, 68, 68, .14);
            color: #ef4444;
            border-color: rgba(239, 68, 68, .24);
        }

        .dark .stat-icon-red {
            color: #fca5a5;
        }

        .stat-value {
            font-size: 1.55rem;
            font-weight: 950;
            line-height: 1;
            color: var(--crop-text);
        }

        .stat-label {
            margin-top: .25rem;
            font-size: .68rem;
            font-weight: 850;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: var(--crop-soft);
        }

        .content-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(360px, .9fr);
            gap: 1rem;
        }

        @media (max-width: 1280px) {
            .content-layout {
                grid-template-columns: 1fr;
            }
        }

        .section-card {
            border-radius: 1.15rem;
            overflow: hidden;
            box-shadow: var(--crop-shadow);
        }

        .section-header {
            padding: 1rem 1.15rem;
            border-bottom: 1px solid var(--crop-border);
            background:
                linear-gradient(135deg, rgba(var(--crop-primary-rgb), .08), rgba(var(--crop-accent-rgb), .045)),
                var(--crop-card-2);
        }

        .section-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: .85rem;
        }

        .section-heading-left {
            display: flex;
            align-items: center;
            gap: .75rem;
            min-width: 0;
        }

        .section-body {
            padding: 1rem 1.15rem;
            background: var(--crop-card);
        }

        .section-title {
            font-size: 1rem;
            font-weight: 950;
            letter-spacing: -.015em;
            color: var(--crop-text);
        }

        .section-subtitle {
            font-size: .73rem;
            color: var(--crop-soft);
            margin-top: .14rem;
            line-height: 1.45;
        }

        .item-list {
            display: flex;
            flex-direction: column;
            gap: .75rem;
        }

        .item-card {
            border-radius: .95rem;
            padding: .8rem;
            background:
                linear-gradient(145deg, rgba(var(--crop-primary-rgb), .04), transparent 42%),
                var(--crop-card-2);
            transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease;
        }

        .stack-column {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .nursery-item {
            display: flex;
            gap: .9rem;
        }

        .nursery-thumb {
            width: 5rem;
            height: 5rem;
            border-radius: .9rem;
            overflow: hidden;
            flex-shrink: 0;
            background: var(--crop-card-3);
            border: 1px solid var(--crop-border);
        }

        .nursery-title {
            font-weight: 900;
            font-size: .86rem;
            color: var(--crop-text);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .nursery-crop,
        .task-source {
            font-size: .73rem;
            color: var(--crop-soft);
            font-weight: 750;
            margin-top: .15rem;
        }

        .nursery-mini-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .5rem;
            margin-top: .7rem;
        }

        @media (max-width: 640px) {
            .nursery-mini-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .mini-stat {
            text-align: center;
            padding: .55rem .45rem;
            background: var(--crop-card);
            border-radius: .65rem;
        }

        .mini-stat-value {
            font-size: .86rem;
            font-weight: 950;
            color: var(--crop-text);
        }

        .mini-stat-label {
            margin-top: .12rem;
            font-size: .58rem;
            color: var(--crop-soft);
            text-transform: uppercase;
            font-weight: 850;
            letter-spacing: .06em;
        }

        .next-action-box,
        .task-instruction-box {
            margin-top: .6rem;
            padding: .65rem;
            background: var(--crop-card);
            border-radius: .7rem;
            font-size: .74rem;
            line-height: 1.55;
            color: var(--crop-muted);
        }

        .task-row {
            display: flex;
            align-items: flex-start;
            gap: .75rem;
        }

        .task-icon {
            width: 2.35rem;
            height: 2.35rem;
            border-radius: .85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: #ffffff;
            box-shadow: 0 12px 28px rgba(15, 23, 42, .12);
        }

        .task-title {
            font-weight: 900;
            font-size: .86rem;
            color: var(--crop-text);
            line-height: 1.35;
        }

        .empty-state {
            text-align: center;
            padding: 2rem 1rem;
        }

        .empty-icon {
            width: 3.25rem;
            height: 3.25rem;
            border-radius: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: .85rem;
        }

        .empty-title {
            font-size: .95rem;
            font-weight: 900;
            margin-bottom: .35rem;
            color: var(--crop-text);
        }

        .empty-text {
            font-size: .82rem;
            color: var(--crop-soft);
            max-width: 400px;
            margin: 0 auto;
            line-height: 1.55;
        }

        @media (max-width: 640px) {
            .crop-hero {
                padding: 1rem;
                border-radius: 1.15rem;
            }

            .smart-badge {
                min-height: 1.75rem;
                padding: .4rem .58rem;
                font-size: .54rem;
                letter-spacing: .04em;
            }

            .hero-title {
                font-size: 1.35rem;
            }

            .hero-subtitle {
                font-size: .8rem;
                line-height: 1.55;
            }

            .stat-card {
                padding: .78rem;
                border-radius: .9rem;
            }

            .stat-icon-wrapper {
                width: 2.05rem;
                height: 2.05rem;
                border-radius: .72rem;
            }

            .stat-value {
                font-size: 1.25rem;
            }

            .stat-label {
                font-size: .55rem;
            }

            .section-header,
            .section-body {
                padding: .85rem;
            }

            .section-title {
                font-size: .92rem;
            }

            .section-subtitle {
                font-size: .68rem;
            }

            .nursery-item {
                flex-direction: column;
            }

            .nursery-thumb {
                width: 100%;
                height: 9rem;
            }
        }
    </style>

    <div class="crop-dashboard-wrapper">
        <div class="crop-hero">
            <div class="crop-hero-inner">
                <div class="hero-badges">
                    <div class="smart-badge smart-badge-hero">
                        <x-heroicon-o-cpu-chip style="width: .9rem; height: .9rem;" />
                        Crop Intelligence
                    </div>

                    <div class="smart-badge smart-badge-hero">
                        <x-heroicon-o-calendar-days style="width: .9rem; height: .9rem;" />
                        {{ $today }}
                    </div>

                    <div class="smart-badge smart-badge-hero">
                        <x-heroicon-o-sparkles style="width: .9rem; height: .9rem;" />
                        Smart Crop Advice
                    </div>
                </div>

                <h1 class="hero-title">Smart Crop Management Dashboard</h1>

                <p class="hero-subtitle">
                    Monitor your entire crop ecosystem with real-time insights on growth stages,
                    nursery health, and harvest forecasting from one premium command center.
                </p>
            </div>
        </div>

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon-wrapper stat-icon-primary">
                        <x-heroicon-o-sun style="width: 1.15rem; height: 1.15rem;" />
                    </div>
                    <span class="smart-badge smart-badge-primary">Active</span>
                </div>
                <div class="stat-value">{{ number_format($activeSeasons) }}</div>
                <div class="stat-label">Active Seasons</div>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon-wrapper stat-icon-accent">
                        <x-heroicon-o-gift-top style="width: 1.15rem; height: 1.15rem;" />
                    </div>
                    <span class="smart-badge smart-badge-accent">Soon</span>
                </div>
                <div class="stat-value">{{ number_format($dueSoon) }}</div>
                <div class="stat-label">Harvest Due Soon</div>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon-wrapper stat-icon-purple">
                        <x-heroicon-o-beaker style="width: 1.15rem; height: 1.15rem;" />
                    </div>
                    <span class="smart-badge smart-badge-purple">Nursery</span>
                </div>
                <div class="stat-value">{{ number_format($nurseryReady) }}</div>
                <div class="stat-label">Nursery Ready</div>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon-wrapper stat-icon-red">
                        <x-heroicon-o-clipboard-document-check style="width: 1.15rem; height: 1.15rem;" />
                    </div>
                    <span class="smart-badge smart-badge-danger">Tasks</span>
                </div>
                <div class="stat-value">{{ number_format($pendingTasks) }}</div>
                <div class="stat-label">Pending Tasks</div>
            </div>
        </div>

        <div class="content-layout">
            <div class="section-card">
                <div class="section-header">
                    <div class="section-header-row">
                        <div class="section-heading-left">
                            <div class="stat-icon-wrapper stat-icon-primary">
                                <x-heroicon-o-sun style="width: 1.15rem; height: 1.15rem;" />
                            </div>

                            <div>
                                <h2 class="section-title">Active Crop Seasons</h2>
                                <p class="section-subtitle">Stage visuals, watering advice & harvest progress</p>
                            </div>
                        </div>

                        <span class="smart-badge smart-badge-primary">
                            <x-heroicon-o-arrow-path-rounded-square style="width: .85rem; height: .85rem;" />
                            {{ number_format($activeSeasons) }} Running
                        </span>
                    </div>
                </div>

                <div class="section-body">
                    <div class="item-list">
                        @forelse ($seasons as $season)
                            <div class="item-card">
                                @include('filament.crops.crop-season-intelligence-card', [
                                    'record' => $season,
                                    'compact' => true,
                                ])
                            </div>
                        @empty
                            <div class="empty-state">
                                <div class="empty-icon stat-icon-primary">
                                    <x-heroicon-o-sun style="width: 1.6rem; height: 1.6rem;" />
                                </div>

                                <div class="empty-title">No Active Seasons Found</div>

                                <p class="empty-text">
                                    Create a crop season to start tracking germination, growth, inputs, and harvest forecasts.
                                </p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="stack-column">
                <div class="section-card">
                    <div class="section-header">
                        <div class="section-header-row">
                            <div class="section-heading-left">
                                <div class="stat-icon-wrapper stat-icon-purple">
                                    <x-heroicon-o-beaker style="width: 1.15rem; height: 1.15rem;" />
                                </div>

                                <div>
                                    <h2 class="section-title">Nursery Intelligence</h2>
                                    <p class="section-subtitle">Seedling health, germination & readiness</p>
                                </div>
                            </div>

                            <span class="smart-badge smart-badge-purple">
                                <x-heroicon-o-squares-2x2 style="width: .85rem; height: .85rem;" />
                                {{ number_format($nurseryBatches->count()) }} Batches
                            </span>
                        </div>
                    </div>

                    <div class="section-body">
                        <div class="item-list">
                            @forelse ($nurseryBatches as $batch)
                                <div class="item-card">
                                    <div class="nursery-item">
                                        <div class="nursery-thumb">
                                            @if ($batch->stage_image_url)
                                                <img
                                                    src="{{ $batch->stage_image_url }}"
                                                    alt="{{ $batch->name }}"
                                                    style="width: 100%; height: 100%; object-fit: cover;"
                                                    loading="lazy"
                                                >
                                            @else
                                                <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: var(--crop-soft);">
                                                    <x-heroicon-o-photo style="width: 2rem; height: 2rem;" />
                                                </div>
                                            @endif
                                        </div>

                                        <div style="flex: 1; min-width: 0;">
                                            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: .65rem;">
                                                <div style="min-width: 0;">
                                                    <h3 class="nursery-title">{{ $batch->name }}</h3>
                                                    <div class="nursery-crop">{{ $batch->crop_name }}</div>
                                                </div>

                                                <span class="smart-badge smart-badge-primary">
                                                    {{ number_format($batch->available_seedlings) }} Available
                                                </span>
                                            </div>

                                            <div class="nursery-mini-stats">
                                                <div class="mini-stat">
                                                    <div class="mini-stat-value">{{ number_format($batch->initial_seedlings) }}</div>
                                                    <div class="mini-stat-label">Initial</div>
                                                </div>

                                                <div class="mini-stat">
                                                    <div class="mini-stat-value" style="color: var(--crop-primary);">
                                                        {{ number_format($batch->healthy_seedlings) }}
                                                    </div>
                                                    <div class="mini-stat-label">Healthy</div>
                                                </div>

                                                <div class="mini-stat">
                                                    <div class="mini-stat-value" style="color: var(--crop-accent);">
                                                        {{ number_format($batch->weak_seedlings) }}
                                                    </div>
                                                    <div class="mini-stat-label">Weak</div>
                                                </div>

                                                <div class="mini-stat">
                                                    <div class="mini-stat-value" style="color: #ef4444;">
                                                        {{ number_format($batch->dead_seedlings) }}
                                                    </div>
                                                    <div class="mini-stat-label">Dead</div>
                                                </div>
                                            </div>

                                            @if ($batch->next_action_advice)
                                                <div class="next-action-box">
                                                    <span style="font-weight: 900; color: var(--crop-text);">Next Action:</span>
                                                    {{ $batch->next_action_advice }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="empty-state">
                                    <div class="empty-icon stat-icon-purple">
                                        <x-heroicon-o-beaker style="width: 1.6rem; height: 1.6rem;" />
                                    </div>

                                    <div class="empty-title">No Nursery Batches</div>

                                    <p class="empty-text">
                                        Create nursery batches to track seedling health and transplant readiness.
                                    </p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="section-card">
                    <div class="section-header">
                        <div class="section-header-row">
                            <div class="section-heading-left">
                                <div class="stat-icon-wrapper stat-icon-red">
                                    <x-heroicon-o-clipboard-document-check style="width: 1.15rem; height: 1.15rem;" />
                                </div>

                                <div>
                                    <h2 class="section-title">Due Care Tasks</h2>
                                    <p class="section-subtitle">Tasks due today or within next 7 days</p>
                                </div>
                            </div>

                            <span class="smart-badge smart-badge-danger">
                                <x-heroicon-o-bell-alert style="width: .85rem; height: .85rem;" />
                                {{ number_format($dueTasks->count()) }} Due
                            </span>
                        </div>
                    </div>

                    <div class="section-body">
                        <div class="item-list">
                            @forelse ($dueTasks as $task)
                                @php
                                    $isOverdue = $task->due_date
                                        && $task->due_date->isPast()
                                        && ! $task->due_date->isToday();
                                @endphp

                                <div class="item-card">
                                    <div class="task-row">
                                        <div
                                            class="task-icon"
                                            style="background: {{ $isOverdue ? '#ef4444' : 'var(--crop-accent)' }};"
                                        >
                                            <x-heroicon-o-bell-alert style="width: 1.1rem; height: 1.1rem;" />
                                        </div>

                                        <div style="flex: 1; min-width: 0;">
                                            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: .65rem;">
                                                <h3 class="task-title">{{ $task->title }}</h3>

                                                <span class="smart-badge {{ $isOverdue ? 'smart-badge-danger' : 'smart-badge-accent' }}">
                                                    {{ $task->due_date?->format('d M') }}
                                                </span>
                                            </div>

                                            <div class="task-source">
                                                {{ $task->cropSeason?->name ?? ($task->nurseryBatch?->name ?? 'Crop Task') }}
                                            </div>

                                            @if ($task->instructions)
                                                <p class="task-instruction-box">
                                                    {{ $task->instructions }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="empty-state">
                                    <div class="empty-icon stat-icon-primary">
                                        <x-heroicon-o-check-circle style="width: 1.6rem; height: 1.6rem;" />
                                    </div>

                                    <div class="empty-title">No Urgent Tasks</div>

                                    <p class="empty-text">
                                        Your crop care schedule is clear for now. All tasks are up to date.
                                    </p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
