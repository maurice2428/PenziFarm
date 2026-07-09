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
        $selected = $this->selectedAccount;
        $hasMovement = $this->rows->count() > 0;
    @endphp

    <div class="lw-accounting">
        @include('filament.pages.accounting.partials.report-actions', [
            'report' => 'general-ledger',
            'params' => [
                'account_id' => $this->accountId,
                'from' => $this->from,
                'to' => $this->to,
                'search' => $this->search,
            ],
        ])

        
        <section class="lw-hero">
            <div class="lw-hero-inner">
                <div>
                    <span class="lw-eyebrow"><x-heroicon-o-book-open class="h-4 w-4" /> Account movement trail</span>
                    <h2>General Ledger</h2>
                    <p>
                        Review the full debit and credit trail for a selected account, including running balance,
                        source journal, reference, project fund and cost centre context.
                    </p>
                </div>
                <div class="lw-hero-metrics">
                    <div class="lw-hero-metric"><small><x-heroicon-o-rectangle-stack class="h-4 w-4" /> Selected Account</small><strong>{{ $selected?->code ?? '-' }} {{ $selected?->name ?? '' }}</strong></div>
                    <div class="lw-hero-metric"><small><x-heroicon-o-calculator class="h-4 w-4" /> Closing Balance</small><strong>KES {{ number_format($this->closingBalance, 2) }}</strong></div>
                </div>
            </div>
        </section>

        <section class="lw-panel lw-panel-pad lw-insight">
            <div class="lw-insight-grid">
                <div class="lw-insight-card"><h3><x-heroicon-o-magnifying-glass-circle class="h-4 w-4" /> Director lens</h3><p>Use this to trace exactly why cash, bank, M-Pesa, receivables, payables or director funding balances changed.</p></div>
                <div class="lw-insight-card"><h3><x-heroicon-o-clipboard-document-check class="h-4 w-4" /> Control action</h3><p>{{ $hasMovement ? 'Movements are available for this account. Review unusual narration, references, or project allocations before approval.' : 'No movement found for this selected account and period. Confirm the account and date filters before reporting zero activity.' }}</p></div>
                <div class="lw-insight-card"><h3><x-heroicon-o-arrow-path-rounded-square class="h-4 w-4" /> Audit trail</h3><p>The ledger should reconcile with Journal Entries and Trial Balance. Any unexplained item should be corrected through a posted adjustment, not manual deletion.</p></div>
            </div>
        </section>

        <section class="lw-grid-2">
            <div class="lw-panel lw-panel-pad">
                <div class="lw-card-title"><div><h3>Choose Account</h3><div class="lw-muted text-sm">Search and click the account. This avoids the large dropdown problem.</div></div><span class="lw-badge">{{ $this->accounts->count() }} shown</span></div>
                <label class="lw-field"><span>Find Account</span><input type="search" class="lw-input" placeholder="Search code or name" wire:model.live.debounce.350ms="accountSearch"></label>
                <div class="mt-3 lw-account-picker">
                    @foreach ($this->accounts as $account)
                        <button type="button" wire:click="selectAccount({{ $account->id }})" class="lw-account-pick {{ (int) $accountId === (int) $account->id ? 'is-active' : '' }}"><div class="flex items-center justify-between gap-3"><div><strong>{{ $account->code }}</strong> — {{ $account->name }}<div class="lw-muted text-xs">{{ str($account->type)->replace('_',' ')->headline() }}</div></div><span class="lw-badge">Open</span></div></button>
                    @endforeach
                </div>
            </div>
            <div class="lw-panel lw-panel-pad">
                <div class="lw-toolbar-main">
                    <label class="lw-field"><span>From</span><input class="lw-input" type="date" wire:model.live="from"></label>
                    <label class="lw-field"><span>To</span><input class="lw-input" type="date" wire:model.live="to"></label>
                    <label class="lw-field"><span>Search Movement</span><input class="lw-input" type="search" placeholder="Ref, journal, project" wire:model.live.debounce.400ms="search"></label>
                    <div class="lw-field"><span>Rows</span><div class="lw-segment-row">@foreach ([15,25,50,100] as $size)<button type="button" wire:click="setPerPage({{ $size }})" class="lw-segment {{ $perPage === $size ? 'is-active' : '' }}">{{ $size }}</button>@endforeach</div></div>
                </div>
            </div>
        </section>

        <section class="lw-kpis">
            <div class="lw-kpi" style="--tone:#047857"><div class="lw-kpi-top"><span class="lw-kpi-label">Total Debits</span><span class="lw-kpi-icon">Dr</span></div><div class="lw-kpi-value">KES {{ number_format($this->totalDebits, 2) }}</div><div class="lw-kpi-hint">Debit movement for selected account.</div></div>
            <div class="lw-kpi" style="--tone:#1d4ed8"><div class="lw-kpi-top"><span class="lw-kpi-label">Total Credits</span><span class="lw-kpi-icon">Cr</span></div><div class="lw-kpi-value">KES {{ number_format($this->totalCredits, 2) }}</div><div class="lw-kpi-hint">Credit movement for selected account.</div></div>
            <div class="lw-kpi" style="--tone:#b45309"><div class="lw-kpi-top"><span class="lw-kpi-label">Closing Balance</span><span class="lw-kpi-icon">Σ</span></div><div class="lw-kpi-value">KES {{ number_format($this->closingBalance, 2) }}</div><div class="lw-kpi-hint">Running balance after current filters.</div></div>
            <div class="lw-kpi" style="--tone:#7c3aed"><div class="lw-kpi-top"><span class="lw-kpi-label">Rows</span><span class="lw-kpi-icon">#</span></div><div class="lw-kpi-value">{{ $this->rows->count() }}</div><div class="lw-kpi-hint">Visible movements.</div></div>
        </section>

        <section class="lw-panel">
            <div class="lw-table-wrap"><table class="lw-table"><thead><tr><th>Date</th><th>Journal</th><th>Reference</th><th>Description</th><th>Project / Cost Centre</th><th class="lw-right">Debit</th><th class="lw-right">Credit</th><th class="lw-right">Balance</th></tr></thead><tbody>@forelse($this->pagedRows as $row)<tr><td>{{ $row['date'] }}</td><td><span class="lw-badge lw-badge-blue">{{ $row['journal_number'] }}</span></td><td>{{ $row['reference'] ?: '-' }}</td><td>{{ $row['description'] ?: '-' }}</td><td><div>{{ $row['project'] ?: '-' }}</div><div class="lw-muted text-xs">{{ $row['cost_center'] ?: '' }}</div></td><td class="lw-right">{{ number_format($row['debit'], 2) }}</td><td class="lw-right">{{ number_format($row['credit'], 2) }}</td><td class="lw-right font-black">{{ number_format($row['balance'], 2) }}</td></tr>@empty<tr><td colspan="8" class="lw-empty">No ledger movements found for this account and period.</td></tr>@endforelse</tbody></table></div>
            <div class="lw-pagination"><span class="lw-muted text-sm">Page {{ $this->page }} of {{ $this->totalPages }} • {{ $this->rows->count() }} rows</span><div class="flex gap-2"><button class="lw-btn" wire:click="previousPage" type="button">← Previous</button><button class="lw-btn" wire:click="nextPage" type="button">Next →</button></div></div>
        </section>
    </div>
</x-filament-panels::page>
