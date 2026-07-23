@php
    $primaryColor = function_exists('setting')
        ? trim((string) setting('theme.primary', '#14532d'))
        : '#14532d';
    $secondaryColor = function_exists('setting')
        ? trim((string) setting('theme.secondary', '#166534'))
        : '#166534';
    $accentColor = function_exists('setting')
        ? trim((string) setting('theme.accent', '#f59e0b'))
        : '#f59e0b';

    $payroll = $dashboard['payroll'];
    $workforce = $dashboard['workforce'];
    $portals = $dashboard['external_portals'];
    $money = fn ($amount): string => 'KES ' . number_format((float) $amount, 2);

    $workforceCards = [
        ['label' => 'Present Today', 'value' => $workforce['present_today'], 'subtitle' => 'Attendance recorded', 'icon' => 'heroicon-o-check-circle', 'color' => '#16a34a'],
        ['label' => 'Absent Today', 'value' => $workforce['absent_today'], 'subtitle' => 'Marked absent', 'icon' => 'heroicon-o-x-circle', 'color' => '#dc2626'],
        ['label' => 'On Leave', 'value' => $workforce['on_leave_today'], 'subtitle' => 'Approved leave today', 'icon' => 'heroicon-o-calendar-days', 'color' => '#2563eb'],
        ['label' => 'Pending Leave', 'value' => $workforce['pending_leave'], 'subtitle' => 'Awaiting approval', 'icon' => 'heroicon-o-clock', 'color' => '#f59e0b'],
        ['label' => 'Pending Advances', 'value' => $workforce['pending_advances'], 'subtitle' => 'Awaiting review', 'icon' => 'heroicon-o-banknotes', 'color' => '#9333ea'],
        ['label' => 'Active Staff', 'value' => $workforce['active_staff'], 'subtitle' => 'Currently employed', 'icon' => 'heroicon-o-user-group', 'color' => '#059669'],
        ['label' => 'Exited Staff', 'value' => $workforce['exited_staff'], 'subtitle' => 'Inactive or exited', 'icon' => 'heroicon-o-arrow-right-on-rectangle', 'color' => '#64748b'],
        ['label' => 'Total Employees', 'value' => $workforce['total_employees'], 'subtitle' => 'All HR records', 'icon' => 'heroicon-o-identification', 'color' => $primaryColor],
    ];

    $payrollCards = [
        ['key' => 'net_pay', 'label' => 'Salary Payable', 'value' => $payroll['net_pay'], 'subtitle' => 'Net payroll after deductions', 'icon' => 'heroicon-o-wallet', 'color' => '#2563eb', 'portal' => null],
        ['key' => 'gross_pay', 'label' => 'Gross Payroll', 'value' => $payroll['gross_pay'], 'subtitle' => 'Gross earnings from payroll items', 'icon' => 'heroicon-o-banknotes', 'color' => '#16a34a', 'portal' => null],
        ['key' => 'basic_salary', 'label' => 'Basic Salaries', 'value' => $payroll['basic_salary'], 'subtitle' => 'Basic salary items', 'icon' => 'heroicon-o-currency-dollar', 'color' => '#0f766e', 'portal' => null],
        ['key' => 'allowances', 'label' => 'Allowances', 'value' => $payroll['allowances'], 'subtitle' => 'Allowance and benefit items', 'icon' => 'heroicon-o-plus-circle', 'color' => '#0284c7', 'portal' => null],
        ['key' => 'paye', 'label' => 'PAYE', 'value' => $payroll['paye'], 'subtitle' => 'Income tax payable', 'icon' => 'heroicon-o-receipt-percent', 'color' => '#f59e0b', 'portal' => $portals['paye']],
        ['key' => 'nssf', 'label' => 'NSSF', 'value' => $payroll['nssf'], 'subtitle' => 'NSSF payable', 'icon' => 'heroicon-o-building-library', 'color' => '#7c3aed', 'portal' => $portals['nssf']],
        ['key' => 'sha', 'label' => 'SHA', 'value' => $payroll['sha'], 'subtitle' => 'Health contribution payable', 'icon' => 'heroicon-o-heart', 'color' => '#e11d48', 'portal' => $portals['sha']],
        ['key' => 'housing_levy', 'label' => 'Housing Levy', 'value' => $payroll['housing_levy'], 'subtitle' => 'Affordable housing levy', 'icon' => 'heroicon-o-home-modern', 'color' => '#ea580c', 'portal' => $portals['housing_levy']],
        [
            'key' => 'nita',
            'label' => 'NITA Levy',
            'value' => $payroll['nita'],
            'subtitle' => number_format((int) ($payroll['nita_active_employee_count'] ?? 0))
                . ' active employee(s) × KES '
                . number_format((float) ($payroll['nita_rate_per_active_employee'] ?? 50), 2),
            'icon' => 'heroicon-o-academic-cap',
            'color' => '#0891b2',
            'portal' => $portals['nita'],
        ],
    ];

    $leaveResource = class_exists(\App\Filament\Resources\HR\LeaveApplicationResource::class)
        ? \App\Filament\Resources\HR\LeaveApplicationResource::class
        : null;
    $advanceResource = class_exists(\App\Filament\Resources\HR\SalaryAdvanceResource::class)
        ? \App\Filament\Resources\HR\SalaryAdvanceResource::class
        : null;
@endphp

<style>
    .hrx-dashboard {
        --hrx-primary: {{ $primaryColor }};
        --hrx-secondary: {{ $secondaryColor }};
        --hrx-accent: {{ $accentColor }};
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .hrx-shell,
    .hrx-card,
    .hrx-list {
        border: 1px solid rgba(229, 231, 235, 1);
        background: linear-gradient(180deg, rgba(255,255,255,.98), rgba(249,250,251,.96));
        box-shadow: 0 14px 36px rgba(2, 6, 23, .055);
    }

    .dark .hrx-shell,
    .dark .hrx-card,
    .dark .hrx-list {
        border-color: rgba(148, 163, 184, .15);
        background: linear-gradient(180deg, rgba(31,41,55,.95), rgba(17,24,39,.96));
    }

    .hrx-hero {
        position: relative;
        overflow: hidden;
        padding: 1rem;
        color: #fff;
        background:
            radial-gradient(circle at top right, rgba(255,255,255,.22), transparent 30%),
            radial-gradient(circle at bottom left, color-mix(in srgb, var(--hrx-accent) 35%, transparent), transparent 28%),
            linear-gradient(135deg, var(--hrx-primary), var(--hrx-secondary) 62%, #052e16);
        box-shadow: 0 22px 60px rgba(2,6,23,.16);
    }

    .hrx-hero-grid {
        position: relative;
        z-index: 2;
        display: grid;
        gap: .9rem;
        grid-template-columns: minmax(0, 1fr);
    }

    @media (min-width: 980px) {
        .hrx-hero-grid { grid-template-columns: minmax(0, 1fr) minmax(300px, 420px); }
    }

    .hrx-kicker,
    .hrx-section-kicker {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        font-size: .68rem;
        font-weight: 950;
        letter-spacing: .07em;
    }

    .hrx-title {
        margin-top: .3rem;
        font-size: clamp(1.2rem, 2.2vw, 1.75rem);
        font-weight: 950;
        letter-spacing: -.035em;
    }

    .hrx-subtitle {
        margin-top: .35rem;
        max-width: 760px;
        color: rgba(255,255,255,.82);
        font-size: .76rem;
        line-height: 1.55;
    }

    .hrx-period-panel {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .55rem;
        padding: .65rem;
        border: 1px solid rgba(255,255,255,.18);
        background: rgba(255,255,255,.11);
        backdrop-filter: blur(16px);
    }

    .hrx-period-item {
        padding: .6rem;
        border: 1px solid rgba(255,255,255,.13);
        background: rgba(255,255,255,.08);
    }

    .hrx-period-label {
        font-size: .56rem;
        font-weight: 900;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: rgba(255,255,255,.7);
    }

    .hrx-period-value {
        margin-top: .2rem;
        font-size: .77rem;
        line-height: 1.25;
        font-weight: 950;
        overflow-wrap: anywhere;
    }

    .hrx-section {
        padding: .85rem;
    }

    .hrx-section-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: .75rem;
        margin-bottom: .75rem;
    }

    .hrx-section-kicker { color: var(--hrx-primary); }

    .hrx-section-title {
        margin-top: .18rem;
        color: #111827;
        font-size: .98rem;
        font-weight: 950;
        letter-spacing: -.025em;
    }

    .dark .hrx-section-title { color: #f9fafb; }

    .hrx-section-subtitle {
        margin-top: .15rem;
        color: #6b7280;
        font-size: .7rem;
        line-height: 1.45;
    }

    .dark .hrx-section-subtitle { color: #9ca3af; }

    .hrx-badge {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .42rem .62rem;
        border: 1px solid color-mix(in srgb, var(--hrx-primary) 18%, white);
        background: color-mix(in srgb, var(--hrx-primary) 8%, white);
        color: var(--hrx-primary);
        font-size: .62rem;
        font-weight: 950;
    }

    .hrx-workforce-grid,
    .hrx-payroll-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .6rem;
    }

    @media (min-width: 800px) {
        .hrx-workforce-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .hrx-payroll-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }

    @media (min-width: 1450px) {
        .hrx-payroll-grid { grid-template-columns: repeat(5, minmax(0, 1fr)); }
    }

    .hrx-card {
        position: relative;
        min-width: 0;
        min-height: 105px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: .55rem;
        padding: .72rem;
        overflow: hidden;
        color: inherit;
        text-align: left;
        text-decoration: none;
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }

    .hrx-card::before {
        content: '';
        position: absolute;
        inset: 0;
        border-left: 3px solid var(--hrx-card-color);
        pointer-events: none;
    }

    .hrx-card::after {
        content: '';
        position: absolute;
        right: -34px;
        top: -40px;
        width: 96px;
        height: 96px;
        border-radius: 999px;
        background: color-mix(in srgb, var(--hrx-card-color) 10%, transparent);
        pointer-events: none;
    }

    button.hrx-card { width: 100%; cursor: pointer; }

    .hrx-card:hover {
        transform: translateY(-2px);
        border-color: color-mix(in srgb, var(--hrx-card-color) 32%, #e5e7eb);
        box-shadow: 0 16px 38px rgba(2,6,23,.09);
    }

    .hrx-card-top,
    .hrx-card-bottom {
        position: relative;
        z-index: 2;
        display: flex;
        justify-content: space-between;
        gap: .55rem;
    }

    .hrx-card-top { align-items: flex-start; }
    .hrx-card-bottom { align-items: flex-end; }

    .hrx-card-label {
        color: #374151;
        font-size: .68rem;
        line-height: 1.2;
        font-weight: 950;
    }

    .dark .hrx-card-label { color: #e5e7eb; }

    .hrx-card-subtitle {
        margin-top: .18rem;
        color: #9ca3af;
        font-size: .55rem;
        line-height: 1.3;
        font-weight: 750;
    }

    .hrx-card-icon {
        flex: 0 0 31px;
        width: 31px;
        height: 31px;
        display: grid;
        place-items: center;
        color: var(--hrx-card-color);
        background: color-mix(in srgb, var(--hrx-card-color) 12%, white);
        border: 1px solid color-mix(in srgb, var(--hrx-card-color) 20%, white);
    }

    .hrx-card-value {
        min-width: 0;
        color: #111827;
        font-size: clamp(1.08rem, 1.5vw, 1.48rem);
        line-height: 1;
        font-weight: 950;
        letter-spacing: -.04em;
        overflow-wrap: anywhere;
    }

    .hrx-card-value.hrx-money { font-size: clamp(.88rem, 1.15vw, 1.15rem); }
    .dark .hrx-card-value { color: #f9fafb; }

    .hrx-card-pill {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        padding: .28rem .42rem;
        border: 1px solid color-mix(in srgb, var(--hrx-card-color) 16%, white);
        background: color-mix(in srgb, var(--hrx-card-color) 9%, white);
        color: var(--hrx-card-color);
        font-size: .52rem;
        line-height: 1;
        font-weight: 950;
        white-space: nowrap;
    }

    .hrx-zero { opacity: .72; }

    .hrx-payroll-foot {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .6rem;
        margin-top: .7rem;
    }

    @media (min-width: 900px) {
        .hrx-payroll-foot { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    }

    .hrx-mini {
        padding: .65rem;
        border: 1px solid rgba(229,231,235,1);
        background: rgba(255,255,255,.72);
    }

    .dark .hrx-mini {
        border-color: rgba(148,163,184,.14);
        background: rgba(15,23,42,.52);
    }

    .hrx-mini-label {
        color: #6b7280;
        font-size: .56rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .hrx-mini-value {
        margin-top: .2rem;
        color: #111827;
        font-size: .78rem;
        font-weight: 950;
        overflow-wrap: anywhere;
    }

    .dark .hrx-mini-value { color: #f9fafb; }

    .hrx-list-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: .8rem;
    }

    @media (min-width: 1100px) {
        .hrx-list-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    .hrx-list { overflow: hidden; }

    .hrx-list-head {
        padding: .8rem;
        border-bottom: 1px solid rgba(229,231,235,.8);
    }

    .dark .hrx-list-head { border-bottom-color: rgba(148,163,184,.12); }

    .hrx-list-title { color: #111827; font-size: .84rem; font-weight: 950; }
    .dark .hrx-list-title { color: #f9fafb; }
    .hrx-list-subtitle { margin-top: .12rem; color: #6b7280; font-size: .64rem; }

    .hrx-list-body { padding: .45rem; }

    .hrx-item {
        display: block;
        padding: .66rem;
        color: inherit;
        text-decoration: none;
        border: 1px solid transparent;
    }

    .hrx-item + .hrx-item { border-top-color: rgba(229,231,235,.8); }
    .hrx-item:hover { background: color-mix(in srgb, var(--hrx-primary) 5%, white); }
    .dark .hrx-item:hover { background: rgba(255,255,255,.04); }
    .hrx-item-name { color: #111827; font-size: .72rem; font-weight: 900; }
    .dark .hrx-item-name { color: #f9fafb; }
    .hrx-item-meta { margin-top: .14rem; color: #6b7280; font-size: .6rem; line-height: 1.4; }
    .hrx-empty { padding: 1rem; color: #6b7280; font-size: .72rem; text-align: center; }

    .hrx-external-modal[style*='display: none'] { display: none !important; }
</style>

<div
    class="hrx-dashboard"
    x-data="{
        externalModalOpen: false,
        externalLink: '',
        externalLabel: '',
        openExternal(url, label) {
            this.externalLink = url;
            this.externalLabel = label;
            this.externalModalOpen = true;
        },
        confirmExternal() {
            window.open(this.externalLink, '_blank', 'noopener,noreferrer');
            this.externalModalOpen = false;
        }
    }"
>
    <section class="hrx-hero">
        <div class="hrx-hero-grid">
            <div>
                <div class="hrx-kicker">
                    <x-heroicon-o-users class="h-4 w-4" />
                    Human Resource command center
                </div>
                <div class="hrx-title">Workforce, Payroll & Statutory Control</div>
                <div class="hrx-subtitle">
                    Live workforce activity and payroll figures calculated from the selected payroll run and its payroll items.
                    Employee salary profiles are not used as a substitute for an ungenerated payroll.
                </div>
            </div>

            <div class="hrx-period-panel">
                <div class="hrx-period-item">
                    <div class="hrx-period-label">Payroll period</div>
                    <div class="hrx-period-value">{{ $payroll['period_label'] }}</div>
                </div>
                <div class="hrx-period-item">
                    <div class="hrx-period-label">Payroll status</div>
                    <div class="hrx-period-value">{{ str($payroll['status'])->replace('_', ' ')->title() }}</div>
                </div>
                <div class="hrx-period-item">
                    <div class="hrx-period-label">Staff processed</div>
                    <div class="hrx-period-value">{{ number_format($payroll['employee_count']) }}</div>
                </div>
                <div class="hrx-period-item">
                    <div class="hrx-period-label">Data source</div>
                    <div class="hrx-period-value">{{ str($payroll['source'])->replace('_', ' ')->title() }}</div>
                </div>
            </div>
        </div>
    </section>

    <section class="hrx-section hrx-shell">
        <div class="hrx-section-head">
            <div>
                <div class="hrx-section-kicker">
                    <x-heroicon-o-calendar-days class="h-4 w-4" />
                    Daily workforce control
                </div>
                <div class="hrx-section-title">Attendance & Staff Movement</div>
                <div class="hrx-section-subtitle">Attendance, leave, approvals, advances, and employment position.</div>
            </div>
            <div class="hrx-badge">
                <x-heroicon-o-bolt class="h-4 w-4" />
                {{ now('Africa/Nairobi')->format('d M Y') }}
            </div>
        </div>

        <div class="hrx-workforce-grid">
            @foreach ($workforceCards as $card)
                <div class="hrx-card {{ (int) $card['value'] === 0 ? 'hrx-zero' : '' }}" style="--hrx-card-color: {{ $card['color'] }};">
                    <div class="hrx-card-top">
                        <div>
                            <div class="hrx-card-label">{{ $card['label'] }}</div>
                            <div class="hrx-card-subtitle">{{ $card['subtitle'] }}</div>
                        </div>
                        <div class="hrx-card-icon">
                            <x-dynamic-component :component="$card['icon']" class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="hrx-card-bottom">
                        <div class="hrx-card-value">{{ number_format((int) $card['value']) }}</div>
                        <div class="hrx-card-pill">Live</div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="hrx-section hrx-shell">
        <div class="hrx-section-head">
            <div>
                <div class="hrx-section-kicker">
                    <x-heroicon-o-banknotes class="h-4 w-4" />
                    Payroll & statutory position
                </div>
                <div class="hrx-section-title">Monthly Payroll Control</div>
                <div class="hrx-section-subtitle">
                    Values come from payroll items belonging to {{ $payroll['period_label'] }}. Click a statutory card to open its official portal.
                </div>
            </div>
            <div class="hrx-badge">
                <x-heroicon-o-calendar class="h-4 w-4" />
                {{ $payroll['exists'] ? $payroll['period_label'] : 'No payroll generated' }}
            </div>
        </div>

        <div class="hrx-payroll-grid">
            @foreach ($payrollCards as $card)
                @if ($card['portal'])
                    <button
                        type="button"
                        class="hrx-card {{ (float) $card['value'] === 0.0 ? 'hrx-zero' : '' }}"
                        style="--hrx-card-color: {{ $card['color'] }};"
                        x-on:click="openExternal(@js($card['portal']['url']), @js($card['portal']['label'] . ' – ' . $card['label']))"
                    >
                @else
                    <div class="hrx-card {{ (float) $card['value'] === 0.0 ? 'hrx-zero' : '' }}" style="--hrx-card-color: {{ $card['color'] }};">
                @endif

                    <div class="hrx-card-top">
                        <div>
                            <div class="hrx-card-label">{{ $card['label'] }}</div>
                            <div class="hrx-card-subtitle">{{ $card['subtitle'] }}</div>
                        </div>
                        <div class="hrx-card-icon">
                            <x-dynamic-component :component="$card['icon']" class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="hrx-card-bottom">
                        <div class="hrx-card-value hrx-money">{{ $money($card['value']) }}</div>
                        <div class="hrx-card-pill">
                            @if ($card['portal'])
                                {{ $card['portal']['label'] }}
                                <x-heroicon-o-arrow-top-right-on-square class="h-3 w-3" />
                            @else
                                Payroll
                            @endif
                        </div>
                    </div>

                @if ($card['portal'])
                    </button>
                @else
                    </div>
                @endif
            @endforeach
        </div>

        <div class="hrx-payroll-foot">
            <div class="hrx-mini">
                <div class="hrx-mini-label">Total deductions</div>
                <div class="hrx-mini-value">{{ $money($payroll['total_deductions']) }}</div>
            </div>
            <div class="hrx-mini">
                <div class="hrx-mini-label">Salary advances</div>
                <div class="hrx-mini-value">{{ $money($payroll['salary_advances']) }}</div>
            </div>
            <div class="hrx-mini">
                <div class="hrx-mini-label">Other deductions</div>
                <div class="hrx-mini-value">{{ $money($payroll['other_deductions']) }}</div>
            </div>
            <div class="hrx-mini">
                <div class="hrx-mini-label">Period position</div>
                <div class="hrx-mini-value">{{ $payroll['is_current_period'] ? 'Current month' : 'Latest available payroll' }}</div>
            </div>
        </div>
    </section>

    @if ($mode === 'full')
        <div class="hrx-list-grid">
            <section class="hrx-list">
                <div class="hrx-list-head">
                    <div class="hrx-list-title">Pending Leave Applications</div>
                    <div class="hrx-list-subtitle">Latest requests awaiting approval</div>
                </div>
                <div class="hrx-list-body">
                    @forelse ($dashboard['pending_leaves'] as $leave)
                        @php
                            $leaveUrl = $leaveResource
                                ? $leaveResource::getUrl('edit', ['record' => $leave])
                                : null;
                            $employeeName = data_get($leave, 'employee.full_name')
                                ?? data_get($leave, 'employee.name')
                                ?? 'Unknown Employee';
                            $leaveName = data_get($leave, 'leaveType.name') ?? 'Leave';
                            $days = data_get($leave, 'days_requested') ?? data_get($leave, 'number_of_days') ?? 0;
                        @endphp
                        @if ($leaveUrl)
                            <a href="{{ $leaveUrl }}" class="hrx-item">
                        @else
                            <div class="hrx-item">
                        @endif
                            <div class="hrx-item-name">{{ $employeeName }}</div>
                            <div class="hrx-item-meta">
                                {{ $leaveName }} · {{ number_format((float) $days, 2) }} day(s)
                                @if (data_get($leave, 'start_date'))
                                    · {{ \Carbon\Carbon::parse(data_get($leave, 'start_date'))->format('d M Y') }}
                                @endif
                            </div>
                        @if ($leaveUrl)
                            </a>
                        @else
                            </div>
                        @endif
                    @empty
                        <div class="hrx-empty">All caught up. No pending leave applications.</div>
                    @endforelse
                </div>
            </section>

            <section class="hrx-list">
                <div class="hrx-list-head">
                    <div class="hrx-list-title">Pending Salary Advances</div>
                    <div class="hrx-list-subtitle">Latest requests awaiting approval</div>
                </div>
                <div class="hrx-list-body">
                    @forelse ($dashboard['pending_advances'] as $advance)
                        @php
                            $advanceUrl = $advanceResource
                                ? $advanceResource::getUrl('edit', ['record' => $advance])
                                : null;
                            $employeeName = data_get($advance, 'employee.full_name')
                                ?? data_get($advance, 'employee.name')
                                ?? 'Unknown Employee';
                            $amount = data_get($advance, 'amount_requested')
                                ?? data_get($advance, 'amount')
                                ?? 0;
                        @endphp
                        @if ($advanceUrl)
                            <a href="{{ $advanceUrl }}" class="hrx-item">
                        @else
                            <div class="hrx-item">
                        @endif
                            <div class="hrx-item-name">{{ $employeeName }}</div>
                            <div class="hrx-item-meta">
                                Requested: {{ $money($amount) }}
                                @if (data_get($advance, 'request_date'))
                                    · {{ \Carbon\Carbon::parse(data_get($advance, 'request_date'))->format('d M Y') }}
                                @endif
                            </div>
                        @if ($advanceUrl)
                            </a>
                        @else
                            </div>
                        @endif
                    @empty
                        <div class="hrx-empty">No pending salary advances.</div>
                    @endforelse
                </div>
            </section>
        </div>
    @endif

    <div
        x-show="externalModalOpen"
        x-cloak
        x-transition.opacity.duration.250ms
        class="hrx-external-modal fixed inset-0 z-50 flex items-center justify-center bg-black/55 p-4 backdrop-blur-sm"
    >
        <div
            x-show="externalModalOpen"
            x-transition.scale.origin.center.duration.250ms
            class="w-full max-w-md border border-gray-200 bg-white p-6 shadow-2xl dark:border-white/10 dark:bg-gray-900"
        >
            <div class="flex items-start gap-3">
                <div class="flex h-12 w-12 items-center justify-center bg-amber-100 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300">
                    <x-heroicon-o-arrow-top-right-on-square class="h-6 w-6" />
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Open External Website</h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                        You are about to leave the ERP and open
                        <span class="font-semibold" x-text="externalLabel"></span>.
                    </p>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Confirm before continuing.</p>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <x-filament::button color="gray" x-on:click="externalModalOpen = false">Cancel</x-filament::button>
                <x-filament::button color="warning" x-on:click="confirmExternal()">Continue</x-filament::button>
            </div>
        </div>
    </div>
</div>
