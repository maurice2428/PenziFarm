<x-filament-panels::page>

<style>

<style>
    /*
    |--------------------------------------------------------------------------
    | PDF Header Visibility Fix
    |--------------------------------------------------------------------------
    | DomPDF can fail to render some modern gradients/color-mix styles exactly
    | like the browser. These overrides keep the report title, subtitle,
    | total records, and reporting period visible in generated PDFs.
    */

    .accounting-pdf-hero,
    .accounting-report-hero,
    .lw-pdf-hero,
    .lw-report-hero,
    .report-hero,
    .pdf-hero,
    .report-title,
    .pdf-report-title,
    .report-summary,
    .pdf-summary,
    .report-meta-band,
    .pdf-meta-band {
        color: #111827 !important;
        background: #f8fafc !important;
        border: 1px solid #d1d5db !important;
    }

    .accounting-pdf-hero h1,
    .accounting-report-hero h1,
    .lw-pdf-hero h1,
    .lw-report-hero h1,
    .report-hero h1,
    .pdf-hero h1,
    .report-title h1,
    .pdf-report-title h1,
    h1.report-title,
    h1.pdf-title {
        color: #064e3b !important;
        font-weight: 800 !important;
    }

    .accounting-pdf-hero p,
    .accounting-report-hero p,
    .lw-pdf-hero p,
    .lw-report-hero p,
    .report-hero p,
    .pdf-hero p,
    .report-title p,
    .pdf-report-title p,
    .report-description,
    .pdf-description,
    .report-subtitle,
    .pdf-subtitle {
        color: #374151 !important;
    }

    .report-kpi,
    .pdf-kpi,
    .report-stat,
    .pdf-stat,
    .report-period,
    .pdf-period,
    .report-total-records,
    .pdf-total-records,
    .summary-card,
    .period-card,
    .meta-card {
        color: #111827 !important;
        background: #ffffff !important;
        border: 1px solid #d1d5db !important;
    }

    .report-kpi-label,
    .pdf-kpi-label,
    .report-period-label,
    .pdf-period-label,
    .meta-label,
    .summary-label {
        color: #064e3b !important;
        font-weight: 800 !important;
        letter-spacing: .04em !important;
        text-transform: uppercase !important;
    }

    .report-kpi-value,
    .pdf-kpi-value,
    .report-period-value,
    .pdf-period-value,
    .meta-value,
    .summary-value {
        color: #111827 !important;
        font-weight: 800 !important;
    }

    /*
     * If the V3 template uses white utility classes inside the PDF header,
     * force them back to readable colors.
     */
    .text-white,
    .text-gray-50,
    .text-slate-50 {
        color: #111827 !important;
    }

    .pdf-dark-title,
    .report-dark-title {
        color: #064e3b !important;
    }

    .pdf-muted,
    .report-muted {
        color: #4b5563 !important;
    }
</style>

    </style>


    @include('filament.pages.accounting.partials.premium-report-styles')
    @php
        $r = $this->report;
        $equityPlusProfit = ($r['equity'] ?? 0) + ($r['current_year_profit'] ?? 0);
        $positionMax = max(1, abs($r['assets'] ?? 0), abs($r['liabilities'] ?? 0), abs($equityPlusProfit));
        $isBalanced = abs($r['difference'] ?? 0) < 0.01;
    @endphp

    <div class="lw-accounting">
        @include('filament.pages.accounting.partials.report-actions', [
            'report' => 'balance-sheet',
            'params' => [
                'as_at' => $this->asAt,
                'search' => $this->search,
            ],
        ])

        
        <section class="lw-hero"><div class="lw-hero-inner"><div><span class="lw-eyebrow"><x-heroicon-o-building-library class="h-4 w-4" /> Financial position</span><h2>Balance Sheet</h2><p>Classic position statement showing what the farm owns, what it owes, and the directors’/owners’ claim at the selected date.</p></div><div class="lw-hero-metrics"><div class="lw-hero-metric"><small><x-heroicon-o-calendar-days class="h-4 w-4" /> As At</small><strong>{{ \Carbon\Carbon::parse($asAt)->format('d M Y') }}</strong></div><div class="lw-hero-metric"><small><x-heroicon-o-check-circle class="h-4 w-4" /> Balance Check</small><strong>KES {{ number_format($r['difference'] ?? 0, 2) }}</strong></div></div></div></section>

        <section class="lw-panel lw-panel-pad lw-insight"><div class="lw-insight-grid"><div class="lw-insight-card"><h3><x-heroicon-o-user-group class="h-4 w-4" /> Director lens</h3><p>The Balance Sheet gives directors the farm’s financial strength: assets available, liabilities due, and equity position.</p></div><div class="lw-insight-card"><h3><x-heroicon-o-shield-check class="h-4 w-4" /> Balance status</h3><p>{{ $isBalanced ? 'The statement currently balances. It is suitable for management review after confirming all period postings are complete.' : 'The statement is not balancing. Review mapping, opening balances, journals and retained earnings before issuing it.' }}</p></div><div class="lw-insight-card"><h3><x-heroicon-o-banknotes class="h-4 w-4" /> Funding context</h3><p>Use this with Director Loans, Director Capital and Project Funds to see how much of farm growth is internally funded or still owed.</p></div></div></section>

        <section class="lw-panel lw-panel-pad"><div class="lw-toolbar-main"><label class="lw-field"><span>As At</span><input class="lw-input" type="date" wire:model.live="asAt"></label><label class="lw-field"><span>Search</span><input class="lw-input" type="search" placeholder="Code, account or class" wire:model.live.debounce.400ms="search"></label><div class="lw-field"><span>Rows</span><div class="lw-segment-row">@foreach ([15,25,50,100] as $size)<button type="button" wire:click="setPerPage({{ $size }})" class="lw-segment {{ $perPage === $size ? 'is-active' : '' }}">{{ $size }}</button>@endforeach</div></div><div class="lw-field"><span>State</span><div class="lw-segment {{ $isBalanced ? 'is-active' : '' }}">{{ $isBalanced ? 'Balanced' : 'Needs Review' }}</div></div></div></section>

        <section class="lw-kpis"><div class="lw-kpi" style="--tone:#0284c7"><div class="lw-kpi-top"><span class="lw-kpi-label">Assets</span><span class="lw-kpi-icon">A</span></div><div class="lw-kpi-value">KES {{ number_format($r['assets'] ?? 0, 2) }}</div><div class="lw-kpi-hint">Farm resources controlled.</div></div><div class="lw-kpi" style="--tone:#b45309"><div class="lw-kpi-top"><span class="lw-kpi-label">Liabilities</span><span class="lw-kpi-icon">L</span></div><div class="lw-kpi-value">KES {{ number_format($r['liabilities'] ?? 0, 2) }}</div><div class="lw-kpi-hint">External and internal obligations.</div></div><div class="lw-kpi" style="--tone:#7c3aed"><div class="lw-kpi-top"><span class="lw-kpi-label">Equity + Profit</span><span class="lw-kpi-icon">E</span></div><div class="lw-kpi-value">KES {{ number_format($equityPlusProfit, 2) }}</div><div class="lw-kpi-hint">Owners’ claim and current result.</div></div><div class="lw-kpi" style="--tone:{{ $isBalanced ? '#047857' : '#e11d48' }}"><div class="lw-kpi-top"><span class="lw-kpi-label">Difference</span><span class="lw-kpi-icon">=</span></div><div class="lw-kpi-value {{ $isBalanced ? 'lw-money-positive' : 'lw-money-negative' }}">KES {{ number_format($r['difference'] ?? 0, 2) }}</div><div class="lw-kpi-hint">Assets minus liabilities & equity.</div></div></section>

        <section class="lw-panel lw-panel-pad"><div class="lw-card-title"><div><h3>Position Visual</h3><div class="lw-muted text-sm">Assets compared with liabilities and equity claims.</div></div><span class="lw-badge">Balance Check</span></div><div class="lw-mini-chart"><div class="lw-chart-row"><strong>Assets</strong><div class="lw-bar" style="--tone:#0284c7"><span style="--w: {{ min(100, abs($r['assets'] ?? 0) / $positionMax * 100) }}%"></span></div><span class="lw-right">KES {{ number_format($r['assets'] ?? 0, 0) }}</span></div><div class="lw-chart-row"><strong>Liabilities</strong><div class="lw-bar" style="--tone:#b45309"><span style="--w: {{ min(100, abs($r['liabilities'] ?? 0) / $positionMax * 100) }}%"></span></div><span class="lw-right">KES {{ number_format($r['liabilities'] ?? 0, 0) }}</span></div><div class="lw-chart-row"><strong>Equity + Profit</strong><div class="lw-bar" style="--tone:#7c3aed"><span style="--w: {{ min(100, abs($equityPlusProfit) / $positionMax * 100) }}%"></span></div><span class="lw-right">KES {{ number_format($equityPlusProfit, 0) }}</span></div></div></section>

        <section class="lw-panel"><div class="lw-table-wrap"><table class="lw-table"><thead><tr><th>Code</th><th>Account</th><th>Class</th><th>Normal</th><th class="lw-right">Debit</th><th class="lw-right">Credit</th><th class="lw-right">Balance</th></tr></thead><tbody>@forelse($this->pagedRows as $row)<tr><td class="font-black">{{ $row['code'] }}</td><td>{{ $row['name'] }}</td><td><span class="lw-badge">{{ str($row['type'])->replace('_',' ')->headline() }}</span></td><td>{{ ucfirst($row['normal_balance']) }}</td><td class="lw-right">{{ number_format($row['debits'], 2) }}</td><td class="lw-right">{{ number_format($row['credits'], 2) }}</td><td class="lw-right font-black">{{ number_format($row['balance'], 2) }}</td></tr>@empty<tr><td colspan="7" class="lw-empty">No Balance Sheet lines found.</td></tr>@endforelse</tbody><tfoot><tr><td colspan="6" class="lw-right font-black">Difference</td><td class="lw-right font-black">KES {{ number_format($r['difference'] ?? 0, 2) }}</td></tr></tfoot></table></div><div class="lw-pagination"><span class="lw-muted text-sm">Page {{ $this->page }} of {{ $this->totalPages }} • {{ $this->rows->count() }} rows</span><div class="flex gap-2"><button class="lw-btn" wire:click="previousPage" type="button">← Previous</button><button class="lw-btn" wire:click="nextPage" type="button">Next →</button></div></div></section>
    </div>
</x-filament-panels::page>
