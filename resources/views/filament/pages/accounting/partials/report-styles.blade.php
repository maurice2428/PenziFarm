<style>
    .acct-wrap { display: grid; gap: 1.25rem; }
    .acct-hero { position: relative; overflow: hidden; border-radius: 1.5rem; padding: 1.35rem; color: white; background: radial-gradient(circle at top left, rgba(255,255,255,.22), transparent 34%), linear-gradient(135deg, #064e3b, #0f766e 45%, #1d4ed8); box-shadow: 0 22px 55px rgba(15, 23, 42, .18); }
    .acct-hero::after { content: ""; position: absolute; inset: auto -7rem -8rem auto; width: 18rem; height: 18rem; border-radius: 999px; background: rgba(255,255,255,.12); }
    .acct-hero-grid { position: relative; z-index: 1; display: grid; gap: 1rem; grid-template-columns: minmax(0, 1fr); }
    .acct-title { font-size: clamp(1.4rem, 2.5vw, 2.35rem); font-weight: 850; letter-spacing: -.04em; }
    .acct-subtitle { margin-top: .35rem; color: rgba(255,255,255,.78); max-width: 54rem; }
    .acct-filter-card, .acct-card, .acct-table-card { border-radius: 1.25rem; background: rgba(255,255,255,.86); border: 1px solid rgba(15,23,42,.08); box-shadow: 0 14px 36px rgba(15,23,42,.07); backdrop-filter: blur(18px); }
    .dark .acct-filter-card, .dark .acct-card, .dark .acct-table-card { background: rgba(15,23,42,.72); border-color: rgba(255,255,255,.1); }
    .acct-filter-card { padding: 1rem; }
    .acct-filter-grid { display: grid; gap: .85rem; grid-template-columns: repeat(1, minmax(0, 1fr)); }
    .acct-label { display: block; margin-bottom: .35rem; font-size: .72rem; font-weight: 750; letter-spacing: .08em; text-transform: uppercase; color: rgb(100,116,139); }
    .dark .acct-label { color: rgb(148,163,184); }
    .acct-input { width: 100%; border-radius: .9rem; border: 1px solid rgba(148,163,184,.35); background: rgba(255,255,255,.9); padding: .68rem .82rem; font-size: .92rem; outline: none; transition: .18s ease; }
    .dark .acct-input { background: rgba(2,6,23,.45); color: white; border-color: rgba(255,255,255,.12); }
    .acct-input:focus { border-color: #14b8a6; box-shadow: 0 0 0 4px rgba(20,184,166,.14); }
    .acct-kpi-grid { display: grid; gap: 1rem; grid-template-columns: repeat(1, minmax(0, 1fr)); }
    .acct-kpi { position: relative; overflow: hidden; padding: 1rem; border-radius: 1.25rem; background: linear-gradient(145deg, rgba(255,255,255,.92), rgba(255,255,255,.7)); border: 1px solid rgba(15,23,42,.08); box-shadow: 0 16px 38px rgba(15,23,42,.07); }
    .dark .acct-kpi { background: linear-gradient(145deg, rgba(15,23,42,.85), rgba(15,23,42,.58)); border-color: rgba(255,255,255,.09); }
    .acct-kpi::before { content: ""; position: absolute; inset: 0 auto 0 0; width: .35rem; background: var(--tone, #14b8a6); }
    .acct-kpi-label { font-size: .78rem; font-weight: 750; color: rgb(100,116,139); }
    .dark .acct-kpi-label { color: rgb(148,163,184); }
    .acct-kpi-value { margin-top: .35rem; font-size: clamp(1.15rem, 2vw, 1.7rem); font-weight: 850; letter-spacing: -.04em; color: rgb(15,23,42); }
    .dark .acct-kpi-value { color: white; }
    .acct-kpi-hint { margin-top: .25rem; font-size: .75rem; color: rgb(100,116,139); }
    .dark .acct-kpi-hint { color: rgb(148,163,184); }
    .acct-table-scroll { overflow: auto; max-height: 64vh; }
    .acct-table { width: 100%; min-width: 760px; border-collapse: separate; border-spacing: 0; }
    .acct-table th { position: sticky; top: 0; z-index: 2; padding: .82rem .95rem; background: rgba(248,250,252,.96); color: rgb(71,85,105); font-size: .72rem; text-transform: uppercase; letter-spacing: .08em; text-align: left; border-bottom: 1px solid rgba(148,163,184,.25); }
    .dark .acct-table th { background: rgba(15,23,42,.96); color: rgb(203,213,225); border-bottom-color: rgba(255,255,255,.1); }
    .acct-table td { padding: .85rem .95rem; border-bottom: 1px solid rgba(148,163,184,.16); font-size: .88rem; vertical-align: top; }
    .dark .acct-table td { border-bottom-color: rgba(255,255,255,.08); }
    .acct-table tbody tr:hover { background: rgba(20,184,166,.06); }
    .acct-right { text-align: right !important; font-variant-numeric: tabular-nums; }
    .acct-badge { display: inline-flex; align-items: center; gap: .35rem; border-radius: 999px; padding: .25rem .6rem; font-size: .72rem; font-weight: 750; background: rgba(20,184,166,.1); color: #0f766e; }
    .dark .acct-badge { background: rgba(45,212,191,.13); color: #5eead4; }
    .acct-bar { height: .55rem; border-radius: 999px; overflow: hidden; background: rgba(148,163,184,.18); }
    .acct-bar > span { display: block; height: 100%; width: var(--w, 0%); background: linear-gradient(90deg, #14b8a6, #22c55e); border-radius: inherit; }
    .acct-pagination { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem; padding: .9rem 1rem; border-top: 1px solid rgba(148,163,184,.18); }
    .acct-btn { display: inline-flex; align-items: center; gap: .4rem; border-radius: .8rem; padding: .55rem .82rem; font-weight: 750; font-size: .82rem; background: rgba(15,23,42,.06); border: 1px solid rgba(15,23,42,.08); }
    .dark .acct-btn { background: rgba(255,255,255,.07); border-color: rgba(255,255,255,.1); color: white; }
    .acct-btn:hover { background: rgba(20,184,166,.12); }
    .acct-muted { color: rgb(100,116,139); }
    .dark .acct-muted { color: rgb(148,163,184); }
    .acct-positive { color: #059669; }
    .acct-negative { color: #dc2626; }
    @media (min-width: 768px) { .acct-filter-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); } .acct-kpi-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); } .acct-hero-grid { grid-template-columns: 1.2fr .8fr; align-items: end; } }
    @media print { .fi-topbar, .fi-sidebar, .fi-header, .fi-breadcrumbs, .acct-filter-card, .acct-pagination { display: none !important; } .acct-hero, .acct-card, .acct-table-card { box-shadow: none !important; border: 1px solid #d1d5db !important; background: white !important; color: black !important; } .acct-table-scroll { max-height: none !important; overflow: visible !important; } .acct-table th { position: static !important; background: #f3f4f6 !important; color: black !important; } }
</style>
