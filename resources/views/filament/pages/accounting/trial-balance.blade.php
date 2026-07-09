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
        $summaryMax = max(1, collect($this->accountTypeSummary)->flatMap(fn ($s) => [abs($s['debits']), abs($s['credits']), abs($s['balance'])])->max() ?: 1);
        $isBalanced = abs($this->difference) < 0.01;
    @endphp

    <div class="lw-accounting">
        @include('filament.pages.accounting.partials.report-actions', [
            'report' => 'trial-balance',
            'params' => [
                'from' => $this->from,
                'to' => $this->to,
                'search' => $this->search,
            ],
        ])

        
        <section class="lw-hero">
            <div class="lw-hero-inner">
                <div>
                    <span class="lw-eyebrow"><x-heroicon-o-scale class="h-4 w-4" /> Double-entry control</span>
                    <h2>Trial Balance</h2>
                    <p>
                        Confirm whether all posted accounting movements are balancing before producing statutory-style
                        financial statements, director summaries, and audit-ready reports.
                    </p>
                </div>
                <div class="lw-hero-metrics">
                    <div class="lw-hero-metric"><small><x-heroicon-o-arrow-trending-up class="h-4 w-4" /> Total Debits</small><strong>KES {{ number_format($this->totalDebits, 2) }}</strong></div>
                    <div class="lw-hero-metric"><small><x-heroicon-o-arrow-trending-down class="h-4 w-4" /> Total Credits</small><strong>KES {{ number_format($this->totalCredits, 2) }}</strong></div>
                </div>
            </div>
        </section>

        <section class="lw-panel lw-panel-pad lw-insight">
            <div class="lw-insight-grid">
                <div class="lw-insight-card">
                    <h3><x-heroicon-o-check-badge class="h-4 w-4" /> Balance status</h3>
                    <p>{{ $isBalanced ? 'The books are currently balanced for this filter. Directors can proceed to Profit & Loss and Balance Sheet review.' : 'There is a difference between debits and credits. Review unposted, reversed, or incorrectly mapped journals before final reporting.' }}</p>
                </div>
                <div class="lw-insight-card">
                    <h3><x-heroicon-o-user-group class="h-4 w-4" /> Director lens</h3>
                    <p>Use this page to confirm that farm income, project funding, asset purchases, and expense postings are clean before board-level reporting.</p>
                </div>
                <div class="lw-insight-card">
                    <h3><x-heroicon-o-shield-check class="h-4 w-4" /> Control action</h3>
                    <p>Any non-zero variance should be investigated through the General Ledger and Journal Entries before closing an accounting period.</p>
                </div>
            </div>
        </section>

        <section class="lw-panel lw-panel-pad">
            <div class="lw-toolbar-main">
                <label class="lw-field"><span>From</span><input class="lw-input" type="date" wire:model.live="from"></label>
                <label class="lw-field"><span>To</span><input class="lw-input" type="date" wire:model.live="to"></label>
                <label class="lw-field"><span>Search</span><input class="lw-input" type="search" placeholder="Code, account or type" wire:model.live.debounce.400ms="search"></label>
                <div class="lw-field"><span>Rows</span><div class="lw-segment-row">@foreach ([15,25,50,100] as $size)<button type="button" wire:click="setPerPage({{ $size }})" class="lw-segment {{ $perPage === $size ? 'is-active' : '' }}">{{ $size }}</button>@endforeach</div></div>
            </div>
        </section>

        <section class="lw-kpis">
            <div class="lw-kpi" style="--tone:#047857"><div class="lw-kpi-top"><span class="lw-kpi-label">Debits</span><span class="lw-kpi-icon">Dr</span></div><div class="lw-kpi-value">KES {{ number_format($this->totalDebits, 2) }}</div><div class="lw-kpi-hint">Debit-side balances visible after filters.</div></div>
            <div class="lw-kpi" style="--tone:#1d4ed8"><div class="lw-kpi-top"><span class="lw-kpi-label">Credits</span><span class="lw-kpi-icon">Cr</span></div><div class="lw-kpi-value">KES {{ number_format($this->totalCredits, 2) }}</div><div class="lw-kpi-hint">Credit-side balances visible after filters.</div></div>
            <div class="lw-kpi" style="--tone:{{ $isBalanced ? '#047857' : '#e11d48' }}"><div class="lw-kpi-top"><span class="lw-kpi-label">Difference</span><span class="lw-kpi-icon">=</span></div><div class="lw-kpi-value {{ $isBalanced ? 'lw-money-positive' : 'lw-money-negative' }}">KES {{ number_format($this->difference, 2) }}</div><div class="lw-kpi-hint">Target is zero before closing reports.</div></div>
            <div class="lw-kpi" style="--tone:#b45309"><div class="lw-kpi-top"><span class="lw-kpi-label">Accounts</span><span class="lw-kpi-icon">#</span></div><div class="lw-kpi-value">{{ number_format($this->rows->count()) }}</div><div class="lw-kpi-hint">Accounts included in the current view.</div></div>
        </section>

        <section class="lw-panel lw-panel-pad">
            <div class="lw-card-title"><div><h3>Account Class Snapshot</h3><div class="lw-muted text-sm">Debit and credit distribution by account category for fast management review.</div></div><span class="lw-badge">Snapshot</span></div>
            <div class="lw-grid-3">
                @foreach ($this->accountTypeSummary as $type)
                    <div class="rounded-xl border border-[var(--lw-line)] p-4">
                        <div class="flex items-center justify-between gap-3"><strong>{{ $type['label'] }}</strong><span class="lw-badge">KES {{ number_format(abs($type['balance']), 0) }}</span></div>
                        <div class="mt-3 space-y-2 text-xs"><div><div class="mb-1 flex justify-between"><span>Debit</span><span>{{ number_format($type['debits'], 0) }}</span></div><div class="lw-bar" style="--tone:#047857"><span style="--w: {{ min(100, abs($type['debits']) / $summaryMax * 100) }}%"></span></div></div><div><div class="mb-1 flex justify-between"><span>Credit</span><span>{{ number_format($type['credits'], 0) }}</span></div><div class="lw-bar" style="--tone:#1d4ed8"><span style="--w: {{ min(100, abs($type['credits']) / $summaryMax * 100) }}%"></span></div></div></div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="lw-panel">
            <div class="lw-table-wrap">
                <table class="lw-table">
                    <thead><tr><th>Code</th><th>Account</th><th>Class</th><th class="lw-right">Debit</th><th class="lw-right">Credit</th><th class="lw-right">Net Balance</th></tr></thead>
                    <tbody>@forelse ($this->pagedRows as $row)<tr><td class="font-black">{{ $row['code'] }}</td><td><div class="font-bold">{{ $row['name'] }}</div><div class="lw-muted text-xs">Normal: {{ ucfirst($row['normal_balance']) }}</div></td><td><span class="lw-badge">{{ str($row['type'])->replace('_',' ')->headline() }}</span></td><td class="lw-right">{{ number_format($row['debit_balance'], 2) }}</td><td class="lw-right">{{ number_format($row['credit_balance'], 2) }}</td><td class="lw-right font-black">{{ number_format($row['balance'], 2) }}</td></tr>@empty<tr><td colspan="6" class="lw-empty">No accounts found for this filter.</td></tr>@endforelse</tbody>
                    <tfoot><tr><td colspan="3" class="lw-right font-black">Totals</td><td class="lw-right font-black">KES {{ number_format($this->totalDebits, 2) }}</td><td class="lw-right font-black">KES {{ number_format($this->totalCredits, 2) }}</td><td class="lw-right font-black">KES {{ number_format($this->difference, 2) }}</td></tr></tfoot>
                </table>
            </div>
            <div class="lw-pagination"><span class="lw-muted text-sm">Page {{ $this->page }} of {{ $this->totalPages }} • {{ $this->rows->count() }} rows</span><div class="flex gap-2"><button type="button" class="lw-btn" wire:click="previousPage">← Previous</button><button type="button" class="lw-btn" wire:click="nextPage">Next →</button></div></div>
        </section>
    </div>
</x-filament-panels::page>
