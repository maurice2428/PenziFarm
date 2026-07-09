<x-filament-panels::page>

@include('filament.pages.accounting.partials.premium-report-styles')
    @php
        $r = $this->report;
        $visualMax = max(1, abs($r['income'] ?? 0), abs($r['cost_of_sales'] ?? 0), abs($r['expenses'] ?? 0), abs($r['net_profit'] ?? 0));
        $netProfit = (float) ($r['net_profit'] ?? 0);
        $isProfitable = $netProfit >= 0;
    @endphp

    <div class="lw-accounting">
        @include('filament.pages.accounting.partials.report-actions', [
            'report' => 'profit-and-loss',
            'params' => [
                'from' => $this->from,
                'to' => $this->to,
                'search' => $this->search,
            ],
        ])

        
        <section class="lw-hero">
            <div class="lw-hero-inner">
                <div>
                    <span class="lw-eyebrow"><x-heroicon-o-chart-bar-square class="h-4 w-4" /> Farm performance</span>
                    <h2>Profit & Loss Statement</h2>
                    <p>
                        Management statement for farm income, livestock/crop production costs, operating expenses and
                        the net result for the selected period.
                    </p>
                </div>
                <div class="lw-hero-metrics">
                    <div class="lw-hero-metric"><small><x-heroicon-o-banknotes class="h-4 w-4" /> Income</small><strong>KES {{ number_format($r['income'] ?? 0, 2) }}</strong></div>
                    <div class="lw-hero-metric"><small><x-heroicon-o-presentation-chart-line class="h-4 w-4" /> Net Result</small><strong>KES {{ number_format($netProfit, 2) }}</strong></div>
                </div>
            </div>
        </section>

        <section class="lw-panel lw-panel-pad lw-insight">
            <div class="lw-insight-grid">
                <div class="lw-insight-card"><h3><x-heroicon-o-user-group class="h-4 w-4" /> Director lens</h3><p>{{ $isProfitable ? 'The selected period is profitable based on posted accounting entries. Directors can review which income streams and cost centres are driving the result.' : 'The selected period is loss-making or has no posted income. Directors should review feed, veterinary, payroll, crop and overhead postings.' }}</p></div>
                <div class="lw-insight-card"><h3><x-heroicon-o-fire class="h-4 w-4" /> Cost pressure</h3><p>Compare cost of sales against income to see whether production costs are consuming too much of farm revenue.</p></div>
                <div class="lw-insight-card"><h3><x-heroicon-o-light-bulb class="h-4 w-4" /> Decision use</h3><p>Use this statement for pricing decisions, project funding requests, cost reduction discussions and monthly management meetings.</p></div>
            </div>
        </section>

        <section class="lw-panel lw-panel-pad"><div class="lw-toolbar-main"><label class="lw-field"><span>From</span><input class="lw-input" type="date" wire:model.live="from"></label><label class="lw-field"><span>To</span><input class="lw-input" type="date" wire:model.live="to"></label><label class="lw-field"><span>Search</span><input class="lw-input" type="search" placeholder="Code, account or class" wire:model.live.debounce.400ms="search"></label><div class="lw-field"><span>Rows</span><div class="lw-segment-row">@foreach ([15,25,50,100] as $size)<button type="button" wire:click="setPerPage({{ $size }})" class="lw-segment {{ $perPage === $size ? 'is-active' : '' }}">{{ $size }}</button>@endforeach</div></div></div></section>

        <section class="lw-kpis"><div class="lw-kpi" style="--tone:#047857"><div class="lw-kpi-top"><span class="lw-kpi-label">Income</span><span class="lw-kpi-icon">+</span></div><div class="lw-kpi-value">KES {{ number_format($r['income'] ?? 0, 2) }}</div><div class="lw-kpi-hint">Posted revenue in period.</div></div><div class="lw-kpi" style="--tone:#b45309"><div class="lw-kpi-top"><span class="lw-kpi-label">Gross Profit</span><span class="lw-kpi-icon">GP</span></div><div class="lw-kpi-value">KES {{ number_format($r['gross_profit'] ?? 0, 2) }}</div><div class="lw-kpi-hint">Income minus cost of sales.</div></div><div class="lw-kpi" style="--tone:#e11d48"><div class="lw-kpi-top"><span class="lw-kpi-label">Expenses</span><span class="lw-kpi-icon">−</span></div><div class="lw-kpi-value">KES {{ number_format($r['expenses'] ?? 0, 2) }}</div><div class="lw-kpi-hint">Operating expenses.</div></div><div class="lw-kpi" style="--tone:{{ $isProfitable ? '#047857' : '#e11d48' }}"><div class="lw-kpi-top"><span class="lw-kpi-label">Net Margin</span><span class="lw-kpi-icon">%</span></div><div class="lw-kpi-value">{{ number_format($this->margin, 2) }}%</div><div class="lw-kpi-hint">Net profit over income.</div></div></section>

        <section class="lw-panel lw-panel-pad"><div class="lw-card-title"><div><h3>Statement Visual</h3><div class="lw-muted text-sm">Income against farm costs, operating expenses and net profit.</div></div><span class="lw-badge">Management Visual</span></div><div class="lw-mini-chart"><div class="lw-chart-row"><strong>Income</strong><div class="lw-bar" style="--tone:#047857"><span style="--w: {{ min(100, abs($r['income'] ?? 0) / $visualMax * 100) }}%"></span></div><span class="lw-right">KES {{ number_format($r['income'] ?? 0, 0) }}</span></div><div class="lw-chart-row"><strong>Cost of Sales</strong><div class="lw-bar" style="--tone:#b45309"><span style="--w: {{ min(100, abs($r['cost_of_sales'] ?? 0) / $visualMax * 100) }}%"></span></div><span class="lw-right">KES {{ number_format($r['cost_of_sales'] ?? 0, 0) }}</span></div><div class="lw-chart-row"><strong>Expenses</strong><div class="lw-bar" style="--tone:#e11d48"><span style="--w: {{ min(100, abs($r['expenses'] ?? 0) / $visualMax * 100) }}%"></span></div><span class="lw-right">KES {{ number_format($r['expenses'] ?? 0, 0) }}</span></div><div class="lw-chart-row"><strong>Net Profit</strong><div class="lw-bar" style="--tone:{{ $isProfitable ? '#047857' : '#e11d48' }}"><span style="--w: {{ min(100, abs($netProfit) / $visualMax * 100) }}%"></span></div><span class="lw-right">KES {{ number_format($netProfit, 0) }}</span></div></div></section>

        <section class="lw-panel"><div class="lw-table-wrap"><table class="lw-table"><thead><tr><th>Code</th><th>Account</th><th>Class</th><th class="lw-right">Debit</th><th class="lw-right">Credit</th><th class="lw-right">Amount</th></tr></thead><tbody>@forelse($this->pagedRows as $row)<tr><td class="font-black">{{ $row['code'] }}</td><td><div class="font-bold">{{ $row['name'] }}</div><div class="lw-muted text-xs">Normal: {{ ucfirst($row['normal_balance']) }}</div></td><td><span class="lw-badge {{ $row['type'] === 'income' ? '' : 'lw-badge-gold' }}">{{ str($row['type'])->replace('_',' ')->headline() }}</span></td><td class="lw-right">{{ number_format($row['debits'], 2) }}</td><td class="lw-right">{{ number_format($row['credits'], 2) }}</td><td class="lw-right font-black">{{ number_format($row['balance'], 2) }}</td></tr>@empty<tr><td colspan="6" class="lw-empty">No Profit & Loss lines found.</td></tr>@endforelse</tbody><tfoot><tr><td colspan="5" class="lw-right font-black">Net Profit</td><td class="lw-right font-black">KES {{ number_format($netProfit, 2) }}</td></tr></tfoot></table></div><div class="lw-pagination"><span class="lw-muted text-sm">Page {{ $this->page }} of {{ $this->totalPages }} • {{ $this->rows->count() }} rows</span><div class="flex gap-2"><button class="lw-btn" wire:click="previousPage" type="button">← Previous</button><button class="lw-btn" wire:click="nextPage" type="button">Next →</button></div></div></section>
    </div>
</x-filament-panels::page>
