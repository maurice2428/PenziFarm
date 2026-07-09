<x-filament-panels::page>

@php
    $animalSnapshotPdfUrl = url('/farm-activity-explorer/pdf?' . http_build_query([
        'from' => $this->from ?? request('from'),
        'to' => $this->to ?? request('to'),
    ]));
@endphp

<div style="display:flex; justify-content:flex-end; gap:.6rem; flex-wrap:wrap; margin-bottom:1rem;">
    <a
        href="{{ $animalSnapshotPdfUrl }}"
        style="
            display:inline-flex;
            align-items:center;
            gap:.45rem;
            padding:.68rem .95rem;
            border-radius:14px;
            color:#fff;
            font-size:.8rem;
            font-weight:900;
            text-decoration:none;
            background:linear-gradient(135deg, {{ setting('theme.primary', '#14532d') }}, {{ setting('theme.secondary', '#166534') }});
            box-shadow:0 14px 30px rgba(20,83,45,.22);
        "
    >
        🖨 Download Animal PDF
    </a>
</div>


@include('filament.pages.accounting.partials.premium-report-styles')
    @php
        $modules = [
            'all' => 'All',
            'animals' => 'Animals',
            'sales' => 'Sales',
            'accounting' => 'Accounting',
            'projects' => 'Projects',
            'inventory' => 'Inventory',
        ];
        $moduleCounts = $this->activities->groupBy('module')->map->count();
    @endphp

    <div class="lw-accounting">
        <section class="lw-hero"><div class="lw-hero-inner"><div><span class="lw-eyebrow"><x-heroicon-o-clock class="h-4 w-4" /> Farm-wide event trail</span><h2>Farm Activity Explorer</h2><p>View all important activities from a date range: animals, sales, accounting journals, project funds and inventory movements. It safely skips modules that are not yet installed.</p></div><div class="lw-hero-metrics"><div class="lw-hero-metric"><small><x-heroicon-o-bolt class="h-4 w-4" /> Activities Found</small><strong>{{ number_format($this->activities->count()) }}</strong></div><div class="lw-hero-metric"><small><x-heroicon-o-squares-2x2 class="h-4 w-4" /> Selected Module</small><strong>{{ $modules[$module] ?? 'All' }}</strong></div></div></div></section>

        <section class="lw-panel lw-panel-pad lw-insight"><div class="lw-insight-grid"><div class="lw-insight-card"><h3><x-heroicon-o-user-group class="h-4 w-4" /> Director lens</h3><p>Use this page as a daily farm command trail to see what changed, when it changed and which operational area was affected.</p></div><div class="lw-insight-card"><h3><x-heroicon-o-document-magnifying-glass class="h-4 w-4" /> Investigation use</h3><p>Search by tag, invoice, journal, project or reference when reconciling farm activity against payments, livestock movement or management reports.</p></div><div class="lw-insight-card"><h3><x-heroicon-o-shield-check class="h-4 w-4" /> Control action</h3><p>When an activity looks unusual, open the source module and confirm the record before approving related accounting or operational reports.</p></div></div></section>

        <section class="lw-panel lw-panel-pad"><div class="lw-toolbar"><div class="lw-toolbar-main"><label class="lw-field"><span>From</span><input class="lw-input" type="date" wire:model.live="from"></label><label class="lw-field"><span>To</span><input class="lw-input" type="date" wire:model.live="to"></label><label class="lw-field"><span>Search</span><input class="lw-input" type="search" placeholder="Tag, invoice, journal, project" wire:model.live.debounce.400ms="search"></label><div class="lw-field"><span>Rows</span><div class="lw-segment-row">@foreach ([15,25,50,100] as $size)<button type="button" wire:click="setPerPage({{ $size }})" class="lw-segment {{ $perPage === $size ? 'is-active' : '' }}">{{ $size }}</button>@endforeach</div></div></div><div><span class="lw-section-label">Module</span><div class="lw-segment-row">@foreach($modules as $key => $label)<button type="button" class="lw-segment {{ $module === $key ? 'is-active' : '' }}" wire:click="setModule('{{ $key }}')">{{ $label }}</button>@endforeach</div></div></div></section>

        <section class="lw-kpis">@foreach(['Animals','Sales','Accounting','Project Funds'] as $label)<div class="lw-kpi" style="--tone:{{ $label === 'Sales' ? '#0284c7' : ($label === 'Accounting' ? '#7c3aed' : ($label === 'Project Funds' ? '#b45309' : '#047857')) }}"><div class="lw-kpi-top"><span class="lw-kpi-label">{{ $label }}</span><span class="lw-kpi-icon">{{ substr($label,0,1) }}</span></div><div class="lw-kpi-value">{{ number_format($moduleCounts[$label] ?? 0) }}</div><div class="lw-kpi-hint">Events in selected period.</div></div>@endforeach</section>

        <section class="lw-panel lw-panel-pad"><div class="lw-card-title"><div><h3>Activity Timeline</h3><div class="lw-muted text-sm">Latest farm events first for quick operational review.</div></div><span class="lw-badge">{{ $this->activities->count() }} events</span></div><div class="grid gap-2">@forelse($this->pagedActivities as $activity)<div class="lw-activity-item"><div class="lw-activity-icon">{{ $activity['icon'] }}</div><div><div class="flex flex-wrap items-center gap-2"><strong>{{ $activity['title'] }}</strong><span class="lw-badge {{ $activity['module'] === 'Sales' ? 'lw-badge-blue' : ($activity['module'] === 'Project Funds' ? 'lw-badge-gold' : '') }}">{{ $activity['module'] }}</span>@if($activity['badge'])<span class="lw-badge">{{ str($activity['badge'])->headline() }}</span>@endif</div><div class="mt-1 lw-muted text-sm">{{ $activity['description'] ?: 'No description' }}</div><div class="mt-1 text-xs lw-muted">{{ $activity['date'] }} • {{ $activity['time'] }} @if($activity['reference']) • Ref: {{ $activity['reference'] }} @endif</div></div><div class="lw-right">@if($activity['amount'] !== null)<div class="font-black">KES {{ number_format($activity['amount'], 2) }}</div>@else<span class="lw-muted text-sm">—</span>@endif</div></div>@empty<div class="lw-empty">No activity found for this filter.</div>@endforelse</div></section>
        <section class="lw-panel"><div class="lw-pagination"><span class="lw-muted text-sm">Page {{ $this->page }} of {{ $this->totalPages }} • {{ $this->activities->count() }} activities</span><div class="flex gap-2"><button class="lw-btn" wire:click="previousPage" type="button">← Previous</button><button class="lw-btn" wire:click="nextPage" type="button">Next →</button></div></div></section>
    </div>
</x-filament-panels::page>
