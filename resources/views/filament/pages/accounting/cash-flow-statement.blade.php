<x-filament-panels::page>
    @php
        $primary = setting('theme.primary', '#14532d');
        $secondary = setting('theme.secondary', '#166534');
        $accent = setting('theme.accent', '#b7791f');
        $farmName = setting('farm.name', setting('company.name', config('app.name')));
        $farmPhone = setting('farm.phone', '');
        $farmEmail = setting('farm.email', '');
        $report = $this->report;
    @endphp

    <style>
        .acf{--p:{{ $primary }};--s:{{ $secondary }};--a:{{ $accent }};display:grid;gap:1rem;color:#0f172a}.dark .acf{color:#f8fafc}.acf-hero{padding:1.2rem;border-radius:1rem;color:#fff;background:linear-gradient(125deg,var(--p),var(--s));box-shadow:0 14px 32px color-mix(in srgb,var(--p) 18%,transparent)}.acf-hero-grid{display:grid;grid-template-columns:1fr auto;gap:1rem;align-items:center}.acf-eyebrow{font-size:.7rem;font-weight:900;text-transform:uppercase;letter-spacing:.08em;color:rgba(255,255,255,.78)}.acf-hero h2{font-size:clamp(1.35rem,2vw,2rem);font-weight:950}.acf-hero p{margin-top:.35rem;max-width:760px;color:rgba(255,255,255,.82);font-size:.78rem;line-height:1.5}.acf-actions{display:flex;gap:.55rem;flex-wrap:wrap;justify-content:flex-end}.acf-btn{display:inline-flex;align-items:center;gap:.35rem;border-radius:.6rem;padding:.65rem .85rem;font-size:.72rem;font-weight:900;text-decoration:none}.acf-btn-light{background:#fff;color:var(--p)}.acf-btn-soft{background:rgba(255,255,255,.14);color:#fff;border:1px solid rgba(255,255,255,.28)}.acf-filter,.acf-card,.acf-table,.acf-footer{border:1px solid #dbe4df;border-radius:.9rem;background:#fff}.dark .acf-filter,.dark .acf-card,.dark .acf-table,.dark .acf-footer{border-color:#334155;background:#0f172a}.acf-filter{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.7rem;padding:.85rem}.acf-filter label{font-size:.65rem;font-weight:900}.acf-filter input{display:block;width:100%;margin-top:.25rem;border:1px solid #cbd5e1;border-radius:.55rem;padding:.55rem;background:transparent}.dark .acf-filter input{border-color:#475569}.acf-kpis{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.7rem}.acf-card{position:relative;overflow:hidden;padding:.9rem;border-left:4px solid var(--tone,var(--p))}.acf-card small{color:#64748b;font-weight:900;text-transform:uppercase}.dark .acf-card small{color:#94a3b8}.acf-card strong{display:block;margin-top:.35rem;font-size:1.35rem;font-weight:950}.acf-wrap{overflow-x:auto}.acf-table table{width:100%;min-width:900px;border-collapse:collapse}.acf-table th,.acf-table td{padding:.7rem;border-bottom:1px solid #e2e8f0;font-size:.72rem;text-align:left}.dark .acf-table th,.dark .acf-table td{border-color:#334155}.acf-table th{background:#f8fafc;text-transform:uppercase;font-size:.62rem}.dark .acf-table th{background:#111827}.acf-footer{display:flex;justify-content:space-between;gap:1rem;align-items:center;padding:.75rem .9rem;color:#64748b;font-size:.68rem}@media(min-width:1100px){.acf-kpis{grid-template-columns:repeat(4,minmax(0,1fr))}.acf-card{padding:1rem}}@media(max-width:900px){.acf-hero-grid{grid-template-columns:1fr}.acf-actions{justify-content:flex-start}.acf-filter{grid-template-columns:1fr 1fr}}@media(max-width:560px){.acf{gap:.7rem}.acf-hero{padding:.95rem}.acf-filter{grid-template-columns:1fr}.acf-kpis{grid-template-columns:1fr}.acf-card strong{font-size:1.1rem}.acf-btn{width:100%;justify-content:center}.acf-footer{align-items:flex-start;flex-direction:column}}
    </style>

    <div class="acf">
        <section class="acf-hero">
            <div class="acf-hero-grid">
                <div>
                    <div class="acf-eyebrow">{{ $farmName }} - Accounting Reports</div>
                    <h2>Cash Flow Statement</h2>
                    <p>Track cash, bank and mobile-money inflows, payments, liquidity pressure and the net movement in configured cash-equivalent ledger accounts.</p>
                </div>
                <div class="acf-actions">
                    @if (Route::has('accounting.reports.cash-flow.print'))
                        <a class="acf-btn acf-btn-soft" target="_blank" href="{{ route('accounting.reports.cash-flow.print', ['from' => $this->from, 'to' => $this->to, 'search' => $this->search]) }}">
                            <x-heroicon-o-printer class="h-4 w-4" /> Print View
                        </a>
                    @endif
                    @if (Route::has('accounting.reports.cash-flow.pdf'))
                        <a class="acf-btn acf-btn-light" target="_blank" href="{{ route('accounting.reports.cash-flow.pdf', ['from' => $this->from, 'to' => $this->to, 'search' => $this->search]) }}">
                            <x-heroicon-o-arrow-down-tray class="h-4 w-4" /> Download PDF
                        </a>
                    @endif
                </div>
            </div>
        </section>

        <section class="acf-filter">
            <label>From<input type="date" wire:model.live="from"></label>
            <label>To<input type="date" wire:model.live="to"></label>
            <label>Search<input type="search" wire:model.live.debounce.400ms="search" placeholder="Journal, account, reference..."></label>
        </section>

        <section class="acf-kpis">
            <div class="acf-card" style="--tone:#15803d"><small>Cash Inflows</small><strong>KES {{ number_format($report['inflows'],2) }}</strong></div>
            <div class="acf-card" style="--tone:#dc2626"><small>Cash Outflows</small><strong>KES {{ number_format($report['outflows'],2) }}</strong></div>
            <div class="acf-card" style="--tone:{{ $report['net_cash_flow'] >= 0 ? '#15803d' : '#dc2626' }}"><small>Net Cash Flow</small><strong>KES {{ number_format($report['net_cash_flow'],2) }}</strong></div>
            <div class="acf-card" style="--tone:var(--a)"><small>Cash Movements</small><strong>{{ number_format($report['movements']) }}</strong></div>
        </section>

        <section class="acf-table">
            <div class="acf-wrap">
                <table>
                    <thead><tr><th>Date</th><th>Journal</th><th>Reference</th><th>Cash Account</th><th>Description</th><th>Inflow</th><th>Outflow</th></tr></thead>
                    <tbody>
                        @forelse($report['lines'] as $line)
                            <tr>
                                <td>{{ $line->journalEntry?->transaction_date?->format('d M Y') }}</td>
                                <td>{{ $line->journalEntry?->journal_number }}</td>
                                <td>{{ $line->journalEntry?->reference ?: '-' }}</td>
                                <td>{{ $line->account?->code }} - {{ $line->account?->name }}</td>
                                <td>{{ $line->description ?: $line->journalEntry?->narration }}</td>
                                <td>KES {{ number_format((float)$line->debit,2) }}</td>
                                <td>KES {{ number_format((float)$line->credit,2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7">No cash movements for the selected period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <footer class="acf-footer">
            <span>{{ $farmName }}{{ $farmPhone ? ' - ' . $farmPhone : '' }}{{ $farmEmail ? ' - ' . $farmEmail : '' }}</span>
            <span>PDF output includes the dynamic header, footer, signature and official stamp.</span>
        </footer>
    </div>
</x-filament-panels::page>
