<x-filament-panels::page>

@include('filament.pages.accounting.partials.premium-report-styles')

    @php
        $d = $this->dashboard;
        $monthlyMax = max(1, collect($d['monthly'])->flatMap(fn ($m) => [abs($m['income']), abs($m['expense']), abs($m['profit'])])->max() ?: 1);
        $mixMax = max(1, collect($d['account_mix'])->max('amount') ?: 1);
        $netProfit = collect($d['cards'])->firstWhere('label', 'Net Profit')['value'] ?? 0;
        $income = collect($d['cards'])->firstWhere('label', 'Income')['value'] ?? 0;
        $expenses = collect($d['cards'])->firstWhere('label', 'Expenses')['value'] ?? 0;
    @endphp

    <div class="lw-accounting">
        <section class="lw-hero">
            <div class="lw-hero-inner">
                <div>
                    <span class="lw-eyebrow"><x-heroicon-o-presentation-chart-line class="h-4 w-4" /> Financial Brain</span>
                    <h2>Accounting Command Centre</h2>
                    <p>
                        Executive finance view for income, farm costs, profit trend, balance checks, project funds,
                        account mix, and recent posted journals.
                    </p>
                </div>
                <div class="lw-hero-metrics">
                    <div class="lw-hero-metric"><small><x-heroicon-o-calendar-days class="h-4 w-4" /> From</small><strong>{{ \Carbon\Carbon::parse($from)->format('d M Y') }}</strong></div>
                    <div class="lw-hero-metric"><small><x-heroicon-o-calendar class="h-4 w-4" /> To</small><strong>{{ \Carbon\Carbon::parse($to)->format('d M Y') }}</strong></div>
                </div>
            </div>
        </section>

        <section class="lw-panel lw-panel-pad lw-insight">
            <div class="lw-insight-grid">
                <div class="lw-insight-card"><h3><x-heroicon-o-user-group class="h-4 w-4" /> Director view</h3><p>{{ $netProfit >= 0 ? 'The selected period currently shows a positive net result. Review cost pressure and project fund usage before approving expansion spend.' : 'The selected period shows a negative result or no income. Review revenue postings, feed costs, payroll, project expenses and overheads.' }}</p></div>
                <div class="lw-insight-card"><h3><x-heroicon-o-banknotes class="h-4 w-4" /> Cash discipline</h3><p>Use the dashboard together with Cashbook, M-Pesa and Bank accounts to track whether farm spending is aligned with approved funding.</p></div>
                <div class="lw-insight-card"><h3><x-heroicon-o-light-bulb class="h-4 w-4" /> Decision action</h3><p>Use these numbers for board updates: income performance, cost control, profitability, project utilization and posted journal activity.</p></div>
            </div>
        </section>

        <section class="lw-panel lw-panel-pad">
            <div class="lw-toolbar-main">
                <label class="lw-field"><span>From</span><input class="lw-input" type="date" wire:model.live="from"></label>
                <label class="lw-field"><span>To</span><input class="lw-input" type="date" wire:model.live="to"></label>
                <div class="lw-field"><span>Quick View</span><div class="lw-segment-row"><button type="button" class="lw-segment" wire:click="$set('from', '{{ now()->startOfMonth()->toDateString() }}')">This Month</button><button type="button" class="lw-segment" wire:click="$set('from', '{{ now()->startOfYear()->toDateString() }}')">Year</button></div></div>
                <div class="lw-field"><span>Status</span><div class="lw-segment is-active"><x-heroicon-o-bolt class="h-4 w-4" /> Live</div></div>
            </div>
        </section>

        <section class="lw-kpis">
            @foreach ($d['cards'] as $card)
                @php
                    $tone = match($card['tone']) {
                        'rose' => '#e11d48',
                        'sky' => '#0284c7',
                        'amber' => '#b45309',
                        default => '#047857',
                    };
                    $icon = match($card['label']) {
                        'Income' => '+',
                        'Expenses' => '−',
                        'Net Profit' => 'NP',
                        default => 'A',
                    };
                @endphp
                <div class="lw-kpi" style="--tone: {{ $tone }}">
                    <div class="lw-kpi-top"><div class="lw-kpi-label">{{ $card['label'] }}</div><div class="lw-kpi-icon">{{ $icon }}</div></div>
                    <div class="lw-kpi-value">KES {{ number_format($card['value'], 2) }}</div>
                    <div class="lw-kpi-hint">{{ $card['hint'] }}</div>
                </div>
            @endforeach
        </section>

        <section class="lw-grid-2">
            <div class="lw-panel lw-panel-pad">
                <div class="lw-card-title"><div><h3>Monthly Financial Trend</h3><div class="lw-muted text-sm">Income, expenses and profit across the selected period.</div></div><span class="lw-badge">Trend</span></div>
                <div class="lw-mini-chart">
                    @forelse ($d['monthly'] as $m)
                        <div class="grid gap-2 rounded-xl border border-[var(--lw-line)] p-3">
                            <strong>{{ $m['label'] }}</strong>
                            <div class="lw-chart-row"><span>Income</span><div class="lw-bar" style="--tone:#047857"><span style="--w: {{ min(100, abs($m['income']) / $monthlyMax * 100) }}%"></span></div><span class="lw-right">{{ number_format($m['income'], 0) }}</span></div>
                            <div class="lw-chart-row"><span>Expense</span><div class="lw-bar" style="--tone:#e11d48"><span style="--w: {{ min(100, abs($m['expense']) / $monthlyMax * 100) }}%"></span></div><span class="lw-right">{{ number_format($m['expense'], 0) }}</span></div>
                            <div class="lw-chart-row"><span>Profit</span><div class="lw-bar" style="--tone:{{ $m['profit'] >= 0 ? '#047857' : '#e11d48' }}"><span style="--w: {{ min(100, abs($m['profit']) / $monthlyMax * 100) }}%"></span></div><span class="lw-right {{ $m['profit'] >= 0 ? 'lw-money-positive' : 'lw-money-negative' }}">{{ number_format($m['profit'], 0) }}</span></div>
                        </div>
                    @empty
                        <div class="lw-empty">No monthly accounting trend yet. Post journals first.</div>
                    @endforelse
                </div>
            </div>

            <div class="lw-panel lw-panel-pad">
                <div class="lw-card-title"><div><h3>Account Mix</h3><div class="lw-muted text-sm">Balance distribution across account classes.</div></div><span class="lw-badge">Control</span></div>
                <div class="lw-mini-chart">
                    @foreach ($d['account_mix'] as $mix)
                        <div class="lw-chart-row"><strong>{{ $mix['label'] }}</strong><div class="lw-bar" style="--tone:#0284c7"><span style="--w: {{ min(100, abs($mix['amount']) / $mixMax * 100) }}%"></span></div><span class="lw-right">KES {{ number_format($mix['amount'], 0) }}</span></div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="lw-grid-2">
            <div class="lw-panel lw-panel-pad">
                <div class="lw-card-title"><div><h3>Project Fund Snapshot</h3><div class="lw-muted text-sm">Budget, receipts, spent amount and balance for active farm projects.</div></div><span class="lw-badge lw-badge-gold">Projects</span></div>
                <div class="grid gap-2">
                    @forelse ($d['project_funds'] as $fund)
                        <div class="rounded-xl border border-[var(--lw-line)] p-3"><div class="flex justify-between gap-3"><div><strong>{{ $fund['name'] }}</strong><div class="lw-muted text-xs">{{ $fund['code'] }} • {{ str($fund['type'])->headline() }}</div></div><span class="lw-badge">{{ str($fund['status'])->headline() }}</span></div><div class="mt-3 lw-chart-row"><span>Utilized</span><div class="lw-bar" style="--tone:#b45309"><span style="--w: {{ min(100, $fund['utilization']) }}%"></span></div><span class="lw-right">{{ number_format($fund['utilization'], 1) }}%</span></div><div class="mt-2 text-xs lw-muted">Budget KES {{ number_format($fund['budget'], 2) }} • Received KES {{ number_format($fund['received'], 2) }} • Spent KES {{ number_format($fund['spent'], 2) }} • Balance KES {{ number_format($fund['balance'], 2) }}</div></div>
                    @empty
                        <div class="lw-empty">No project funds found yet.</div>
                    @endforelse
                </div>
            </div>

            <div class="lw-panel lw-panel-pad">
                <div class="lw-card-title"><div><h3>Recent Posted Journals</h3><div class="lw-muted text-sm">Latest accounting movements available for management review.</div></div><span class="lw-badge lw-badge-blue">Audit Trail</span></div>
                <div class="grid gap-2">
                    @forelse ($d['recent_journals'] as $journal)
                        <div class="lw-activity-item"><div class="lw-activity-icon">JE</div><div><div class="flex flex-wrap items-center gap-2"><strong>{{ $journal->journal_number }}</strong><span class="lw-badge">{{ str($journal->status)->headline() }}</span></div><div class="lw-muted text-sm mt-1">{{ $journal->narration ?: 'No narration' }}</div><div class="lw-muted text-xs mt-1">{{ $journal->transaction_date }} • {{ $journal->reference ?: 'No reference' }} • {{ $journal->source_type ?: 'Manual/General' }}</div></div><div class="lw-right"><strong>KES {{ number_format($journal->total_debit, 2) }}</strong></div></div>
                    @empty
                        <div class="lw-empty">No posted journals yet.</div>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
</x-filament-panels::page>
