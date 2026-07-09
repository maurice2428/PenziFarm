<x-filament-panels::page>
    @php
        $snapshot = $this->getSnapshot();
        $pdfUrl = $this->getPdfUrl();
        $primary = setting('theme.primary', '#14532d');
        $secondary = setting('theme.secondary', '#166534');
        $accent = setting('theme.accent', '#f59e0b');
        $total = max((int) ($snapshot['total'] ?? 0), 1);
        $maxBreed = max(1, collect($snapshot['breed_rows'] ?? [])->max('total') ?? 1);
        $maxTrend = max(1, collect($snapshot['addition_trend'] ?? [])->max('total') ?? 1);
    @endphp

    <style>
        .fae-shell {
            --fae-primary: {{ $primary }};
            --fae-secondary: {{ $secondary }};
            --fae-accent: {{ $accent }};
            --fae-card: rgba(255,255,255,.86);
            --fae-border: rgba(15,23,42,.10);
            --fae-muted: #64748b;
            --fae-text: #0f172a;
            display: grid;
            gap: 1rem;
        }
        .dark .fae-shell {
            --fae-card: rgba(15,23,42,.78);
            --fae-border: rgba(148,163,184,.18);
            --fae-muted: #94a3b8;
            --fae-text: #e5e7eb;
        }
        .fae-hero {
            position: relative;
            overflow: hidden;
            border-radius: 1.6rem;
            padding: clamp(1rem, 2vw, 1.45rem);
            color: #fff;
            background:
                radial-gradient(circle at top left, rgba(255,255,255,.25), transparent 28%),
                radial-gradient(circle at 85% 20%, rgba(245,158,11,.22), transparent 32%),
                linear-gradient(135deg, var(--fae-primary), var(--fae-secondary));
            box-shadow: 0 24px 60px rgba(20,83,45,.22);
        }
        .fae-hero-grid { display:grid; grid-template-columns: minmax(0,1fr) auto; gap:1rem; align-items:start; }
        .fae-eyebrow { font-size:.72rem; font-weight:900; text-transform:uppercase; letter-spacing:.12em; opacity:.85; }
        .fae-title { margin:.25rem 0 .35rem; font-size:clamp(1.35rem, 3vw, 2.2rem); font-weight:950; line-height:1.05; }
        .fae-subtitle { max-width: 780px; font-size:.88rem; line-height:1.55; opacity:.9; }
        .fae-actions { display:flex; gap:.55rem; flex-wrap:wrap; justify-content:flex-end; }
        .fae-btn {
            display:inline-flex; align-items:center; justify-content:center; gap:.45rem;
            min-height:2.45rem; padding:.7rem .95rem; border-radius:999px;
            text-decoration:none; font-size:.78rem; font-weight:950; white-space:nowrap;
            transition: transform .18s ease, box-shadow .18s ease;
        }
        .fae-btn:hover { transform: translateY(-1px); }
        .fae-btn-primary { color:var(--fae-primary); background:#fff; box-shadow:0 14px 30px rgba(0,0,0,.16); }
        .fae-btn-soft { color:#fff; background:rgba(255,255,255,.14); border:1px solid rgba(255,255,255,.25); }
        .fae-filter-card, .fae-card {
            border:1px solid var(--fae-border); border-radius:1.35rem; background:var(--fae-card);
            box-shadow:0 18px 45px rgba(15,23,42,.06); backdrop-filter: blur(14px);
        }
        .fae-filter-card { padding:1rem; display:grid; grid-template-columns: repeat(2, minmax(160px, 220px)) minmax(0,1fr); gap:.85rem; align-items:end; }
        .fae-field label { display:block; margin-bottom:.35rem; color:var(--fae-muted); font-size:.72rem; font-weight:900; text-transform:uppercase; letter-spacing:.08em; }
        .fae-field input { width:100%; border-radius:.9rem; border:1px solid var(--fae-border); background:rgba(255,255,255,.8); padding:.68rem .8rem; font-weight:800; color:#0f172a; }
        .dark .fae-field input { background:rgba(2,6,23,.55); color:#e5e7eb; }
        .fae-filter-note { color:var(--fae-muted); font-size:.82rem; line-height:1.45; }
        .fae-kpis { display:grid; grid-template-columns: repeat(6, minmax(0,1fr)); gap:.85rem; }
        .fae-kpi { position:relative; overflow:hidden; padding:1rem; min-height:132px; border-radius:1.25rem; border:1px solid var(--fae-border); background:var(--fae-card); box-shadow:0 16px 38px rgba(15,23,42,.055); }
        .fae-kpi:before { content:""; position:absolute; inset:auto -30px -40px auto; width:110px; height:110px; border-radius:999px; background:color-mix(in srgb, var(--fae-primary) 14%, transparent); }
        .fae-kpi-label { color:var(--fae-muted); font-size:.72rem; font-weight:900; text-transform:uppercase; letter-spacing:.07em; }
        .fae-kpi-value { margin:.35rem 0; color:var(--fae-text); font-size:clamp(1.4rem, 2vw, 2.1rem); font-weight:950; line-height:1; }
        .fae-kpi-note { color:var(--fae-muted); font-size:.76rem; line-height:1.35; }
        .fae-grid-2 { display:grid; grid-template-columns: minmax(0,1.2fr) minmax(340px,.8fr); gap:1rem; }
        .fae-grid-3 { display:grid; grid-template-columns: repeat(3,minmax(0,1fr)); gap:1rem; }
        .fae-card { padding:1rem; min-width:0; }
        .fae-card-head { display:flex; justify-content:space-between; gap:1rem; align-items:flex-start; margin-bottom:.85rem; }
        .fae-card-title { color:var(--fae-text); font-size:1rem; font-weight:950; }
        .fae-card-subtitle { color:var(--fae-muted); font-size:.78rem; line-height:1.4; margin-top:.2rem; }
        .fae-pill { display:inline-flex; align-items:center; border-radius:999px; padding:.32rem .55rem; font-size:.7rem; font-weight:900; color:var(--fae-primary); background:color-mix(in srgb, var(--fae-primary) 10%, white); }
        .dark .fae-pill { background:color-mix(in srgb, var(--fae-primary) 22%, #020617); color:#dcfce7; }
        .fae-status-list, .fae-bar-list { display:grid; gap:.72rem; }
        .fae-status-row { display:grid; grid-template-columns: 160px 1fr 70px; gap:.7rem; align-items:center; }
        .fae-label { min-width:0; color:var(--fae-text); font-size:.82rem; font-weight:900; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .fae-count { color:var(--fae-text); font-size:.8rem; font-weight:950; text-align:right; }
        .fae-track { height:.75rem; border-radius:999px; background:rgba(148,163,184,.22); overflow:hidden; }
        .fae-fill { height:100%; border-radius:999px; background:linear-gradient(90deg,var(--fae-primary),var(--fae-accent)); min-width:4px; }
        .fae-mini-chart { display:flex; align-items:flex-end; gap:.35rem; height:150px; padding:.85rem; border-radius:1rem; background:linear-gradient(180deg, rgba(20,83,45,.055), rgba(20,83,45,.015)); border:1px dashed var(--fae-border); overflow-x:auto; }
        .fae-mini-bar { min-width:24px; flex:1; max-width:46px; display:flex; flex-direction:column; justify-content:flex-end; align-items:center; gap:.3rem; }
        .fae-mini-bar span:first-child { width:100%; border-radius:.7rem .7rem .2rem .2rem; background:linear-gradient(180deg,var(--fae-accent),var(--fae-primary)); min-height:4px; }
        .fae-mini-bar span:last-child { color:var(--fae-muted); font-size:.62rem; font-weight:900; writing-mode:vertical-rl; transform:rotate(180deg); max-height:54px; }
        .fae-insights { display:grid; gap:.75rem; }
        .fae-insight { display:grid; grid-template-columns:32px 1fr; gap:.7rem; padding:.82rem; border-radius:1rem; background:color-mix(in srgb, var(--fae-primary) 6%, white); border:1px solid color-mix(in srgb, var(--fae-primary) 12%, white); color:var(--fae-text); }
        .dark .fae-insight { background:color-mix(in srgb, var(--fae-primary) 13%, #020617); border-color:rgba(148,163,184,.18); }
        .fae-insight-number { width:32px; height:32px; border-radius:.8rem; display:flex; align-items:center; justify-content:center; background:var(--fae-primary); color:#fff; font-weight:950; font-size:.75rem; }
        .fae-insight-text { font-size:.82rem; line-height:1.45; color:var(--fae-text); }
        .fae-table { width:100%; border-collapse:separate; border-spacing:0 .45rem; }
        .fae-table th { color:var(--fae-muted); font-size:.7rem; text-transform:uppercase; letter-spacing:.08em; text-align:left; padding:.25rem .6rem; }
        .fae-table td { color:var(--fae-text); font-size:.8rem; font-weight:800; padding:.7rem .6rem; background:rgba(148,163,184,.08); }
        .fae-table td:first-child { border-radius:.8rem 0 0 .8rem; }
        .fae-table td:last-child { border-radius:0 .8rem .8rem 0; text-align:right; }
        @media (max-width: 1280px) { .fae-kpis { grid-template-columns: repeat(3,minmax(0,1fr)); } .fae-grid-3 { grid-template-columns: 1fr 1fr; } }
        @media (max-width: 900px) { .fae-hero-grid, .fae-filter-card, .fae-grid-2, .fae-grid-3 { grid-template-columns:1fr; } .fae-actions { justify-content:flex-start; } .fae-kpis { grid-template-columns: repeat(2,minmax(0,1fr)); } .fae-status-row { grid-template-columns: 120px 1fr 54px; } }
        @media (max-width: 560px) { .fae-kpis { grid-template-columns:1fr; } .fae-status-row { grid-template-columns:1fr; gap:.35rem; } .fae-count { text-align:left; } .fae-btn { width:100%; } }
    </style>

    <div class="fae-shell">
        <section class="fae-hero">
            <div class="fae-hero-grid">
                <div>
                    <div class="fae-eyebrow">Farm Intelligence • Livestock Snapshot</div>
                    <h1 class="fae-title">Farm Activity Explorer</h1>
                    <div class="fae-subtitle">
                        Director-level livestock intelligence showing animal numbers as at a selected date, status distribution, breed concentration, period additions and operational event movement. The page focuses on decisions, not long animal lists.
                    </div>
                </div>
                <div class="fae-actions">
                    <a href="{{ $pdfUrl }}" class="fae-btn fae-btn-primary">
                        <x-heroicon-o-arrow-down-tray class="h-4 w-4" />
                        Download Livestock PDF
                    </a>
                    <a href="{{ url('/admin') }}" class="fae-btn fae-btn-soft">
                        <x-heroicon-o-home class="h-4 w-4" />
                        Dashboard
                    </a>
                </div>
            </div>
        </section>

        <section class="fae-filter-card">
            <div class="fae-field">
                <label>From</label>
                <input type="date" wire:model.live="from">
            </div>
            <div class="fae-field">
                <label>To / As At</label>
                <input type="date" wire:model.live="to">
            </div>
            <div class="fae-filter-note">
                Showing <strong>{{ number_format($snapshot['total'] ?? 0) }}</strong> animal record(s) as at <strong>{{ $snapshot['as_at_label'] ?? '-' }}</strong>. Period activity is calculated from <strong>{{ $snapshot['period_label'] ?? '-' }}</strong>.
            </div>
        </section>

        <section class="fae-kpis">
            @foreach (($snapshot['kpis'] ?? []) as $kpi)
                <article class="fae-kpi">
                    <div class="fae-kpi-label">{{ $kpi['label'] }}</div>
                    <div class="fae-kpi-value">{{ number_format($kpi['value']) }}</div>
                    <div class="fae-kpi-note">{{ $kpi['note'] }}</div>
                </article>
            @endforeach
        </section>

        <section class="fae-grid-2">
            <article class="fae-card">
                <div class="fae-card-head">
                    <div>
                        <div class="fae-card-title">Status Distribution</div>
                        <div class="fae-card-subtitle">Active, dead, culled, sold, archived and non-standard statuses as at the selected date.</div>
                    </div>
                    <span class="fae-pill">{{ number_format($snapshot['active_rate'] ?? 0, 1) }}% active</span>
                </div>
                <div class="fae-status-list">
                    @forelse (($snapshot['status_rows'] ?? []) as $row)
                        @php $percent = (($row['total'] ?? 0) / $total) * 100; @endphp
                        <div class="fae-status-row">
                            <div class="fae-label">{{ $row['label'] }}</div>
                            <div class="fae-track"><div class="fae-fill" style="width: {{ max(2, $percent) }}%"></div></div>
                            <div class="fae-count">{{ number_format($row['total']) }}</div>
                        </div>
                    @empty
                        <div class="fae-card-subtitle">No status column was found on the animals table.</div>
                    @endforelse
                </div>
            </article>

            <article class="fae-card">
                <div class="fae-card-head">
                    <div>
                        <div class="fae-card-title">Executive Insights</div>
                        <div class="fae-card-subtitle">Explanation of what the numbers mean operationally.</div>
                    </div>
                    <span class="fae-pill">{{ count($snapshot['insights'] ?? []) }} notes</span>
                </div>
                <div class="fae-insights">
                    @foreach (($snapshot['insights'] ?? []) as $index => $insight)
                        <div class="fae-insight">
                            <div class="fae-insight-number">{{ $index + 1 }}</div>
                            <div class="fae-insight-text">{{ $insight }}</div>
                        </div>
                    @endforeach
                </div>
            </article>
        </section>

        <section class="fae-grid-3">
            <article class="fae-card">
                <div class="fae-card-head">
                    <div>
                        <div class="fae-card-title">Breed Composition</div>
                        <div class="fae-card-subtitle">Top breed/category concentration.</div>
                    </div>
                </div>
                <div class="fae-bar-list">
                    @forelse (($snapshot['breed_rows'] ?? []) as $row)
                        @php $percent = (($row['total'] ?? 0) / $maxBreed) * 100; @endphp
                        <div>
                            <div style="display:flex; justify-content:space-between; gap:.7rem; margin-bottom:.3rem;">
                                <div class="fae-label">{{ $row['breed_name'] }}</div>
                                <div class="fae-count">{{ number_format($row['total']) }}</div>
                            </div>
                            <div class="fae-track"><div class="fae-fill" style="width: {{ max(3, $percent) }}%"></div></div>
                        </div>
                    @empty
                        <div class="fae-card-subtitle">Breed linkage is not available for this snapshot.</div>
                    @endforelse
                </div>
            </article>

            <article class="fae-card">
                <div class="fae-card-head">
                    <div>
                        <div class="fae-card-title">Period Additions Trend</div>
                        <div class="fae-card-subtitle">Daily animal records added in the selected period.</div>
                    </div>
                    <span class="fae-pill">{{ number_format($snapshot['additions'] ?? 0) }} added</span>
                </div>
                <div class="fae-mini-chart">
                    @forelse (($snapshot['addition_trend'] ?? []) as $row)
                        @php $height = (($row['total'] ?? 0) / $maxTrend) * 100; @endphp
                        <div class="fae-mini-bar" title="{{ $row['date'] }}: {{ $row['total'] }}">
                            <span style="height: {{ max(4, $height) }}%"></span>
                            <span>{{ $row['date'] }}</span>
                        </div>
                    @empty
                        <div class="fae-card-subtitle">No additions were recorded in this period.</div>
                    @endforelse
                </div>
            </article>

            <article class="fae-card">
                <div class="fae-card-head">
                    <div>
                        <div class="fae-card-title">Animal Events</div>
                        <div class="fae-card-subtitle">Operational activity captured in the period.</div>
                    </div>
                    <span class="fae-pill">{{ number_format($snapshot['events']['total_events'] ?? 0) }} events</span>
                </div>
                <table class="fae-table">
                    <thead><tr><th>Event</th><th>Total</th></tr></thead>
                    <tbody>
                        @forelse (($snapshot['events']['rows'] ?? []) as $row)
                            <tr><td>{{ $row['label'] }}</td><td>{{ number_format($row['total']) }}</td></tr>
                        @empty
                            <tr><td colspan="2">No animal events were found for this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </article>
        </section>

        <section class="fae-grid-3">
            @foreach (['gender_rows' => 'Sex / Gender Snapshot', 'stage_rows' => 'Stage Snapshot', 'location_rows' => 'Location / Unit Snapshot'] as $key => $title)
                <article class="fae-card">
                    <div class="fae-card-head">
                        <div>
                            <div class="fae-card-title">{{ $title }}</div>
                            <div class="fae-card-subtitle">Grouped from {{ $snapshot[$key]['column'] ?? 'available fields' }}.</div>
                        </div>
                    </div>
                    <table class="fae-table">
                        <thead><tr><th>Label</th><th>Total</th></tr></thead>
                        <tbody>
                            @forelse (($snapshot[$key]['rows'] ?? []) as $row)
                                <tr><td>{{ $row['label'] }}</td><td>{{ number_format($row['total']) }}</td></tr>
                            @empty
                                <tr><td colspan="2">No matching column/data available.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </article>
            @endforeach
        </section>
    </div>
</x-filament-panels::page>
