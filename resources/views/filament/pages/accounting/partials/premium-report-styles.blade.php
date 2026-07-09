@php
    $primaryColor = trim(function_exists('setting') ? setting('theme.primary', '#14532d') : '#14532d');
    $secondaryColor = trim(function_exists('setting') ? setting('theme.secondary', '#166534') : '#166534');
    $accentColor = trim(function_exists('setting') ? setting('theme.accent', '#b7791f') : '#b7791f');
    $successColor = trim(function_exists('setting') ? setting('theme.success', '#16a34a') : '#16a34a');
    $dangerColor = trim(function_exists('setting') ? setting('theme.danger', '#dc2626') : '#dc2626');
@endphp

@once
<style>
    :root {
        --lw-primary: {{ $primaryColor }};
        --lw-secondary: {{ $secondaryColor }};
        --lw-accent: {{ $accentColor }};
        --lw-success: {{ $successColor }};
        --lw-danger: {{ $dangerColor }};
        --lw-ink: #0f172a;
        --lw-muted: #64748b;
        --lw-line: rgba(15, 23, 42, .10);
        --lw-card: rgba(255, 255, 255, .95);
        --lw-card-strong: #ffffff;
        --lw-soft: rgba(248, 250, 252, .92);
        --lw-table-head: rgba(248, 250, 252, .98);
    }

    .dark {
        --lw-ink: #f8fafc;
        --lw-muted: #94a3b8;
        --lw-line: rgba(255, 255, 255, .11);
        --lw-card: rgba(15, 23, 42, .78);
        --lw-card-strong: rgba(17, 24, 39, .96);
        --lw-soft: rgba(2, 6, 23, .38);
        --lw-table-head: rgba(15, 23, 42, .98);
    }

    .fi-main .fi-header-heading {
        letter-spacing: -.045em;
        font-weight: 900;
    }

    .fi-main .fi-header-subheading {
        max-width: 58rem;
        line-height: 1.55;
    }

    .lw-accounting {
        display: grid;
        gap: .9rem;
        color: var(--lw-ink);
    }

    .lw-hero {
        position: relative;
        overflow: hidden;
        border-radius: .95rem;
        padding: 1.15rem;
        color: #fff;
        background:
            radial-gradient(circle at top right, rgba(255,255,255,.22), transparent 28%),
            radial-gradient(circle at bottom left, color-mix(in srgb, var(--lw-accent) 36%, transparent), transparent 25%),
            linear-gradient(135deg, var(--lw-primary) 0%, var(--lw-secondary) 56%, #052e16 100%);
        box-shadow: 0 22px 62px rgba(2, 6, 23, .16);
        isolation: isolate;
    }

    .lw-hero::after {
        content: "";
        position: absolute;
        right: -5.5rem;
        top: -5.8rem;
        width: 17rem;
        height: 17rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, .11);
        pointer-events: none;
        z-index: 0;
    }

    .lw-hero-inner {
        position: relative;
        z-index: 1;
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: .95rem;
        align-items: stretch;
    }

    .lw-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: .42rem;
        border-radius: 999px;
        padding: .28rem .62rem;
        font-size: .68rem;
        font-weight: 900;
        letter-spacing: .06em;
        text-transform: uppercase;
        background: rgba(255,255,255,.13);
        border: 1px solid rgba(255,255,255,.19);
        color: rgba(255,255,255,.90);
        backdrop-filter: blur(12px);
    }

    .lw-hero h2 {
        margin: .55rem 0 0;
        font-size: clamp(1.55rem, 2.4vw, 2.4rem);
        font-weight: 950;
        letter-spacing: -.052em;
        line-height: 1.02;
    }

    .lw-hero p {
        margin: .42rem 0 0;
        max-width: 59rem;
        color: rgba(255,255,255,.83);
        line-height: 1.55;
        font-size: .93rem;
    }

    .lw-hero-metrics {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .65rem;
    }

    .lw-hero-metric {
        min-height: 4.6rem;
        border-radius: .82rem;
        padding: .72rem .82rem;
        background: rgba(255, 255, 255, .12);
        border: 1px solid rgba(255, 255, 255, .20);
        backdrop-filter: blur(14px);
    }

    .lw-hero-metric small {
        display: flex;
        align-items: center;
        gap: .35rem;
        color: rgba(255,255,255,.74);
        font-size: .7rem;
        font-weight: 750;
    }

    .lw-hero-metric strong {
        display: block;
        margin-top: .2rem;
        font-size: clamp(.96rem, 1.2vw, 1.18rem);
        font-weight: 920;
        letter-spacing: -.035em;
        line-height: 1.2;
    }

    .lw-panel {
        border: 1px solid var(--lw-line);
        background: var(--lw-card);
        border-radius: .95rem;
        box-shadow: 0 12px 32px rgba(15, 23, 42, .055);
        backdrop-filter: blur(18px);
    }

    .lw-panel-pad { padding: .92rem; }

    .lw-insight {
        border-left: 4px solid var(--lw-primary);
        background:
            radial-gradient(circle at top right, color-mix(in srgb, var(--lw-primary) 7%, transparent), transparent 31%),
            var(--lw-card);
    }

    .lw-insight-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: .75rem;
    }

    .lw-insight-card {
        border: 1px solid var(--lw-line);
        background: var(--lw-soft);
        padding: .78rem;
        border-radius: .82rem;
    }

    .lw-insight-card h3 {
        display: flex;
        align-items: center;
        gap: .45rem;
        margin: 0;
        font-size: .86rem;
        font-weight: 950;
        letter-spacing: -.025em;
    }

    .lw-insight-card p {
        margin: .28rem 0 0;
        color: var(--lw-muted);
        font-size: .78rem;
        line-height: 1.45;
    }

    .lw-toolbar {
        display: grid;
        gap: .85rem;
    }

    .lw-toolbar-main {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: .72rem;
        align-items: end;
    }

    .lw-field span,
    .lw-section-label {
        display: block;
        margin-bottom: .34rem;
        font-size: .66rem;
        font-weight: 900;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--lw-muted);
    }

    .lw-input {
        width: 100%;
        min-height: 2.48rem;
        border-radius: .78rem;
        border: 1px solid rgba(148, 163, 184, .38);
        background: rgba(255, 255, 255, .94);
        color: #0f172a;
        padding: .62rem .78rem;
        font-size: .88rem;
        outline: none;
        transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
    }

    .dark .lw-input {
        background: rgba(2, 6, 23, .42);
        color: #f8fafc;
        border-color: rgba(255,255,255,.12);
    }

    .lw-input:focus {
        border-color: var(--lw-primary);
        box-shadow: 0 0 0 4px color-mix(in srgb, var(--lw-primary) 14%, transparent);
    }

    .lw-segment-row {
        display: flex;
        flex-wrap: wrap;
        gap: .42rem;
    }

    .lw-segment {
        min-height: 2.22rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .35rem;
        border-radius: .72rem;
        padding: .48rem .72rem;
        border: 1px solid var(--lw-line);
        background: var(--lw-soft);
        color: var(--lw-ink);
        font-size: .81rem;
        font-weight: 850;
        transition: transform .16s ease, background .16s ease, border-color .16s ease;
    }

    .lw-segment:hover {
        transform: translateY(-1px);
        border-color: color-mix(in srgb, var(--lw-primary) 30%, var(--lw-line));
        background: color-mix(in srgb, var(--lw-primary) 8%, transparent);
    }

    .lw-segment.is-active {
        background: linear-gradient(135deg, var(--lw-primary), var(--lw-secondary));
        color: #fff;
        border-color: color-mix(in srgb, var(--lw-primary) 80%, white);
        box-shadow: 0 10px 22px color-mix(in srgb, var(--lw-primary) 21%, transparent);
    }

    .lw-kpis {
        display: grid;
        grid-template-columns: repeat(1, minmax(0, 1fr));
        gap: .72rem;
    }

    .lw-kpi {
        position: relative;
        overflow: hidden;
        min-height: 6rem;
        padding: .86rem .92rem .82rem;
        border-radius: .9rem;
        border: 1px solid var(--lw-line);
        background:
            radial-gradient(circle at 96% 16%, color-mix(in srgb, var(--tone, var(--lw-primary)) 16%, transparent), transparent 36%),
            var(--lw-card);
        box-shadow: 0 12px 30px rgba(15, 23, 42, .052);
    }

    .lw-kpi::before {
        content: "";
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: .26rem;
        background: var(--tone, var(--lw-primary));
    }

    .lw-kpi-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .7rem;
    }

    .lw-kpi-icon {
        width: 2.15rem;
        height: 2.15rem;
        display: grid;
        place-items: center;
        border-radius: .78rem;
        background: color-mix(in srgb, var(--tone, var(--lw-primary)) 12%, transparent);
        color: var(--tone, var(--lw-primary));
        font-weight: 950;
    }

    .lw-kpi-label { color: var(--lw-muted); font-size: .76rem; font-weight: 850; }

    .lw-kpi-value {
        margin-top: .32rem;
        font-size: clamp(1.06rem, 1.75vw, 1.58rem);
        line-height: 1.1;
        font-weight: 950;
        letter-spacing: -.046em;
    }

    .lw-kpi-hint { margin-top: .25rem; color: var(--lw-muted); font-size: .74rem; line-height: 1.35; }

    .lw-grid-2,
    .lw-grid-3 { display: grid; grid-template-columns: minmax(0, 1fr); gap: .82rem; }

    .lw-card-title {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        padding-bottom: .72rem;
        border-bottom: 1px solid var(--lw-line);
        margin-bottom: .72rem;
    }

    .lw-card-title h3 { margin: 0; font-size: 1rem; font-weight: 950; letter-spacing: -.026em; }
    .lw-muted { color: var(--lw-muted); }

    .lw-table-wrap { overflow: auto; max-height: 65vh; border-radius: .92rem; }
    .lw-table { width: 100%; min-width: 760px; border-collapse: separate; border-spacing: 0; }

    .lw-table th {
        position: sticky;
        top: 0;
        z-index: 2;
        text-align: left;
        padding: .72rem .82rem;
        background: var(--lw-table-head);
        color: #475569;
        font-size: .67rem;
        font-weight: 950;
        text-transform: uppercase;
        letter-spacing: .08em;
        border-bottom: 1px solid rgba(148, 163, 184, .25);
    }

    .dark .lw-table th { color: #cbd5e1; border-color: rgba(255, 255, 255, .10); }

    .lw-table td {
        padding: .76rem .82rem;
        border-bottom: 1px solid rgba(148, 163, 184, .16);
        vertical-align: top;
        font-size: .85rem;
    }

    .dark .lw-table td { border-color: rgba(255, 255, 255, .07); }
    .lw-table tbody tr:hover { background: color-mix(in srgb, var(--lw-primary) 5%, transparent); }
    .lw-right { text-align: right !important; font-variant-numeric: tabular-nums; }
    .lw-money-positive { color: #059669; }
    .lw-money-negative { color: #dc2626; }
    .dark .lw-money-positive { color: #34d399; }
    .dark .lw-money-negative { color: #fb7185; }

    .lw-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .28rem;
        border-radius: 999px;
        padding: .24rem .56rem;
        font-size: .68rem;
        font-weight: 900;
        background: color-mix(in srgb, var(--lw-primary) 10%, transparent);
        color: var(--lw-primary);
        white-space: nowrap;
    }

    .dark .lw-badge { background: rgba(52, 211, 153, .12); color: #6ee7b7; }
    .lw-badge-blue { background: rgba(37, 99, 235, .11); color: #1d4ed8; }
    .lw-badge-gold { background: rgba(180, 83, 9, .12); color: #92400e; }
    .lw-badge-rose { background: rgba(225, 29, 72, .10); color: #be123c; }
    .dark .lw-badge-blue { color: #93c5fd; }
    .dark .lw-badge-gold { color: #fcd34d; }
    .dark .lw-badge-rose { color: #fda4af; }

    .lw-bar { height: .52rem; border-radius: 999px; overflow: hidden; background: rgba(148, 163, 184, .18); }
    .lw-bar > span { display: block; height: 100%; width: var(--w, 0%); border-radius: inherit; background: linear-gradient(90deg, var(--tone, var(--lw-primary)), color-mix(in srgb, var(--tone, var(--lw-primary)) 52%, var(--lw-accent))); }

    .lw-mini-chart { display: grid; gap: .82rem; }
    .lw-chart-row { display: grid; grid-template-columns: 7rem minmax(0, 1fr) 7rem; gap: .65rem; align-items: center; font-size: .82rem; }
    .lw-chart-row strong { font-weight: 900; }

    .lw-account-picker { max-height: 16rem; overflow: auto; display: grid; gap: .45rem; padding-right: .25rem; }
    .lw-account-pick { width: 100%; text-align: left; border-radius: .82rem; border: 1px solid var(--lw-line); background: var(--lw-soft); padding: .66rem .76rem; transition: .16s ease; }
    .lw-account-pick:hover { background: color-mix(in srgb, var(--lw-primary) 8%, transparent); border-color: color-mix(in srgb, var(--lw-primary) 26%, var(--lw-line)); }
    .lw-account-pick.is-active { background: linear-gradient(135deg, color-mix(in srgb, var(--lw-primary) 14%, transparent), color-mix(in srgb, var(--lw-secondary) 8%, transparent)); border-color: color-mix(in srgb, var(--lw-primary) 48%, var(--lw-line)); box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--lw-primary) 10%, transparent); }

    .lw-pagination { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .8rem; border-top: 1px solid var(--lw-line); padding: .82rem .92rem; }
    .lw-btn { display: inline-flex; align-items: center; justify-content: center; gap: .4rem; min-height: 2.25rem; border-radius: .72rem; padding: .48rem .76rem; font-size: .81rem; font-weight: 900; border: 1px solid var(--lw-line); background: var(--lw-soft); color: var(--lw-ink); transition: .16s ease; }
    .lw-btn:hover { transform: translateY(-1px); background: color-mix(in srgb, var(--lw-primary) 9%, transparent); }

    .lw-activity-item { display: grid; grid-template-columns: 2.35rem minmax(0, 1fr) auto; gap: .78rem; align-items: start; padding: .8rem; border-radius: .82rem; border: 1px solid var(--lw-line); background: var(--lw-soft); }
    .lw-activity-icon { width: 2.35rem; height: 2.35rem; border-radius: .78rem; display: grid; place-items: center; background: color-mix(in srgb, var(--lw-primary) 10%, transparent); font-size: 1.05rem; }
    .lw-empty { padding: 2rem 1rem; text-align: center; color: var(--lw-muted); }

    @media (min-width: 768px) {
        .lw-hero-inner { grid-template-columns: 1.35fr .65fr; }
        .lw-toolbar-main { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .lw-kpis { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .lw-grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .lw-grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .lw-insight-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }

    @media (max-width: 767px) {
        .lw-hero { padding: .95rem; }
        .lw-hero-metrics { grid-template-columns: 1fr; }
        .lw-chart-row { grid-template-columns: 1fr; gap: .25rem; }
        .lw-activity-item { grid-template-columns: 2.35rem minmax(0, 1fr); }
        .lw-activity-item > .lw-right { grid-column: 1 / -1; text-align: left !important; }
        .lw-table { min-width: 680px; }
    }

    @media print {
        .fi-sidebar, .fi-topbar, .fi-header, .fi-breadcrumbs, .lw-toolbar, .lw-pagination, .lw-no-print { display: none !important; }
        .lw-accounting { display: block !important; }
        .lw-panel, .lw-hero, .lw-kpi { box-shadow: none !important; border: 1px solid #d1d5db !important; background: white !important; color: black !important; }
        .lw-hero::after { display: none !important; }
        .lw-table-wrap { max-height: none !important; overflow: visible !important; }
        .lw-table th { position: static !important; background: #f3f4f6 !important; color: #111827 !important; }
    }
</style>
@endonce

<style>
    .lw-report-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: .65rem;
        flex-wrap: wrap;
        margin: -0.25rem 0 .9rem;
        position: relative;
        z-index: 30;
    }

    .lw-report-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .45rem;
        min-height: 2.35rem;
        padding: .62rem .9rem;
        border-radius: .9rem;
        font-size: .78rem;
        font-weight: 900;
        text-decoration: none;
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        white-space: nowrap;
    }

    .lw-report-action:hover {
        transform: translateY(-1px);
    }

    .lw-report-action-primary {
        color: #fff;
        background:
            radial-gradient(circle at top left, rgba(255,255,255,.22), transparent 34%),
            linear-gradient(135deg, var(--lw-primary), var(--lw-secondary));
        border: 1px solid color-mix(in srgb, var(--lw-primary) 70%, white);
        box-shadow: 0 14px 30px color-mix(in srgb, var(--lw-primary) 22%, transparent);
    }

    .lw-report-action-soft {
        color: var(--lw-primary);
        background: color-mix(in srgb, var(--lw-primary) 8%, white);
        border: 1px solid color-mix(in srgb, var(--lw-primary) 18%, white);
        box-shadow: 0 10px 24px rgba(15, 23, 42, .055);
    }

    .dark .lw-report-action-soft {
        color: #d1fae5;
        background: color-mix(in srgb, var(--lw-primary) 18%, #111827);
        border-color: color-mix(in srgb, var(--lw-primary) 26%, #374151);
    }

    @media (max-width: 640px) {
        .lw-report-actions {
            justify-content: stretch;
        }

        .lw-report-action {
            flex: 1;
            width: 100%;
        }
    }
</style>
