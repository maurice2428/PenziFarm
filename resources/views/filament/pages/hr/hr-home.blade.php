<x-filament-panels::page>
    <style>
        .hr-page-shell {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .hr-section {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .hr-section-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .hr-section-title {
            font-size: 15px;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: rgb(17 24 39);
        }

        .dark .hr-section-title {
            color: rgb(255 255 255);
        }

        .hr-section-subtitle {
            margin-top: 0.25rem;
            font-size: 10px;
            line-height: 1.5;
            color: rgb(107 114 128);
        }

        .dark .hr-section-subtitle {
            color: rgb(156 163 175);
        }

        .hr-cards-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 1rem;
        }

        @media (min-width: 640px) {
            .hr-cards-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1024px) {
            .hr-cards-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (min-width: 1536px) {
            .hr-cards-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        .hr-stat-card {
            position: relative;
            overflow: hidden;
            min-height: 80px;
            border-radius: 1.5rem;
            border: 1px solid rgba(229, 231, 235, 1);
            background:
                linear-gradient(180deg, rgba(255,255,255,0.94), rgba(249,250,251,0.96));
            padding: 1.25rem;
            cursor: pointer;
            transition:
                transform 260ms ease,
                box-shadow 260ms ease,
                border-color 260ms ease,
                background 260ms ease;
            box-shadow: 0 10px 30px rgba(2, 6, 23, 0.04);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            isolation: isolate;
        }

        .dark .hr-stat-card {
            border-color: rgba(255,255,255,0.08);
            background:
                linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.025));
            box-shadow: 0 12px 34px rgba(0, 0, 0, 0.22);
        }

        .hr-stat-card::before {
            content: "";
            position: absolute;
            inset: 0;
            opacity: 0;
            transition: opacity 280ms ease;
            z-index: -1;
        }

        .hr-stat-card::after {
            content: "";
            position: absolute;
            right: -2rem;
            top: -2rem;
            width: 7rem;
            height: 7rem;
            border-radius: 9999px;
            opacity: 0.10;
            filter: blur(8px);
            transition: transform 300ms ease, opacity 300ms ease;
            z-index: -1;
        }

        .hr-stat-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 45px rgba(2, 6, 23, 0.10);
        }

        .dark .hr-stat-card:hover {
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.34);
        }

        .hr-stat-card:hover::before {
            opacity: 1;
        }

        .hr-stat-card:hover::after {
            transform: scale(1.08);
            opacity: 0.16;
        }

        .hr-stat-card__top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
        }

        .hr-stat-card__icon-wrap {
            width: 3.5rem;
            height: 3.5rem;
            min-width: 3.5rem;
            border-radius: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: transform 260ms ease, box-shadow 260ms ease;
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.25);
        }

        .hr-stat-card:hover .hr-stat-card__icon-wrap {
            transform: translateY(-2px) scale(1.04);
        }

        .hr-stat-card__portal {
            padding: 0.45rem 0.7rem;
            border-radius: 9999px;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            white-space: nowrap;
            align-self: flex-start;
            border: 1px solid transparent;
            backdrop-filter: blur(10px);
        }

        .hr-stat-card__body {
            margin-top: 1.2rem;
        }

        .hr-stat-card__label {
            font-size: 0.9rem;
            line-height: 1.45;
            font-weight: 600;
            color: rgb(107 114 128);
        }

        .dark .hr-stat-card__label {
            color: rgb(156 163 175);
        }

        .hr-stat-card__value {
            margin-top: 0.45rem;
            font-size: 20px;
            line-height: 1.1;
            font-weight: 800;
            letter-spacing: -0.03em;
            color: rgb(17 24 39);
            word-break: break-word;
        }

        .dark .hr-stat-card__value {
            color: rgb(255 255 255);
        }

        .hr-stat-card__footer {
            margin-top: 1.25rem;
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            opacity: 0.9;
            transition: transform 220ms ease, opacity 220ms ease;
        }

        .hr-stat-card:hover .hr-stat-card__footer {
            transform: translateX(2px);
            opacity: 1;
        }

        .hr-stat-card__arrow {
            width: 0.9rem;
            height: 0.9rem;
        }

        /* Color themes */
        .hr-theme-blue::before { background: linear-gradient(135deg, rgba(59,130,246,0.10), rgba(37,99,235,0.04)); }
        .hr-theme-blue::after { background: rgba(59,130,246,0.22); }
        .hr-theme-blue:hover { border-color: rgba(59,130,246,0.28); }
        .hr-theme-blue .hr-stat-card__icon-wrap { background: linear-gradient(135deg, rgba(219,234,254,1), rgba(191,219,254,0.85)); color: rgb(37 99 235); }
        .dark .hr-theme-blue .hr-stat-card__icon-wrap { background: linear-gradient(135deg, rgba(59,130,246,0.18), rgba(37,99,235,0.16)); color: rgb(96 165 250); }
        .hr-theme-blue .hr-stat-card__portal { background: rgba(239,246,255,0.95); color: rgb(37 99 235); border-color: rgba(59,130,246,0.14); }
        .dark .hr-theme-blue .hr-stat-card__portal { background: rgba(59,130,246,0.12); color: rgb(147 197 253); border-color: rgba(96,165,250,0.12); }
        .hr-theme-blue .hr-stat-card__footer { color: rgb(37 99 235); }

        .hr-theme-emerald::before { background: linear-gradient(135deg, rgba(16,185,129,0.11), rgba(5,150,105,0.04)); }
        .hr-theme-emerald::after { background: rgba(16,185,129,0.22); }
        .hr-theme-emerald:hover { border-color: rgba(16,185,129,0.28); }
        .hr-theme-emerald .hr-stat-card__icon-wrap { background: linear-gradient(135deg, rgba(209,250,229,1), rgba(167,243,208,0.88)); color: rgb(5 150 105); }
        .dark .hr-theme-emerald .hr-stat-card__icon-wrap { background: linear-gradient(135deg, rgba(16,185,129,0.18), rgba(5,150,105,0.16)); color: rgb(52 211 153); }
        .hr-theme-emerald .hr-stat-card__portal { background: rgba(236,253,245,0.95); color: rgb(5 150 105); border-color: rgba(16,185,129,0.14); }
        .dark .hr-theme-emerald .hr-stat-card__portal { background: rgba(16,185,129,0.12); color: rgb(110 231 183); border-color: rgba(52,211,153,0.12); }
        .hr-theme-emerald .hr-stat-card__footer { color: rgb(5 150 105); }

        .hr-theme-amber::before { background: linear-gradient(135deg, rgba(245,158,11,0.12), rgba(217,119,6,0.04)); }
        .hr-theme-amber::after { background: rgba(245,158,11,0.24); }
        .hr-theme-amber:hover { border-color: rgba(245,158,11,0.28); }
        .hr-theme-amber .hr-stat-card__icon-wrap { background: linear-gradient(135deg, rgba(254,243,199,1), rgba(253,230,138,0.88)); color: rgb(217 119 6); }
        .dark .hr-theme-amber .hr-stat-card__icon-wrap { background: linear-gradient(135deg, rgba(245,158,11,0.18), rgba(217,119,6,0.16)); color: rgb(251 191 36); }
        .hr-theme-amber .hr-stat-card__portal { background: rgba(255,251,235,0.95); color: rgb(217 119 6); border-color: rgba(245,158,11,0.14); }
        .dark .hr-theme-amber .hr-stat-card__portal { background: rgba(245,158,11,0.12); color: rgb(252 211 77); border-color: rgba(251,191,36,0.12); }
        .hr-theme-amber .hr-stat-card__footer { color: rgb(217 119 6); }

        .hr-theme-purple::before { background: linear-gradient(135deg, rgba(168,85,247,0.11), rgba(147,51,234,0.04)); }
        .hr-theme-purple::after { background: rgba(168,85,247,0.24); }
        .hr-theme-purple:hover { border-color: rgba(168,85,247,0.28); }
        .hr-theme-purple .hr-stat-card__icon-wrap { background: linear-gradient(135deg, rgba(243,232,255,1), rgba(233,213,255,0.88)); color: rgb(147 51 234); }
        .dark .hr-theme-purple .hr-stat-card__icon-wrap { background: linear-gradient(135deg, rgba(168,85,247,0.18), rgba(147,51,234,0.16)); color: rgb(196 181 253); }
        .hr-theme-purple .hr-stat-card__portal { background: rgba(250,245,255,0.95); color: rgb(147 51 234); border-color: rgba(168,85,247,0.14); }
        .dark .hr-theme-purple .hr-stat-card__portal { background: rgba(168,85,247,0.12); color: rgb(216 180 254); border-color: rgba(196,181,253,0.12); }
        .hr-theme-purple .hr-stat-card__footer { color: rgb(147 51 234); }

        .hr-theme-rose::before { background: linear-gradient(135deg, rgba(244,63,94,0.11), rgba(225,29,72,0.04)); }
        .hr-theme-rose::after { background: rgba(244,63,94,0.22); }
        .hr-theme-rose:hover { border-color: rgba(244,63,94,0.28); }
        .hr-theme-rose .hr-stat-card__icon-wrap { background: linear-gradient(135deg, rgba(255,228,230,1), rgba(254,205,211,0.88)); color: rgb(225 29 72); }
        .dark .hr-theme-rose .hr-stat-card__icon-wrap { background: linear-gradient(135deg, rgba(244,63,94,0.18), rgba(225,29,72,0.16)); color: rgb(251 113 133); }
        .hr-theme-rose .hr-stat-card__portal { background: rgba(255,241,242,0.95); color: rgb(225 29 72); border-color: rgba(244,63,94,0.14); }
        .dark .hr-theme-rose .hr-stat-card__portal { background: rgba(244,63,94,0.12); color: rgb(253 164 175); border-color: rgba(251,113,133,0.12); }
        .hr-theme-rose .hr-stat-card__footer { color: rgb(225 29 72); }

        .hr-theme-orange::before { background: linear-gradient(135deg, rgba(249,115,22,0.11), rgba(234,88,12,0.04)); }
        .hr-theme-orange::after { background: rgba(249,115,22,0.22); }
        .hr-theme-orange:hover { border-color: rgba(249,115,22,0.28); }
        .hr-theme-orange .hr-stat-card__icon-wrap { background: linear-gradient(135deg, rgba(255,237,213,1), rgba(254,215,170,0.88)); color: rgb(234 88 12); }
        .dark .hr-theme-orange .hr-stat-card__icon-wrap { background: linear-gradient(135deg, rgba(249,115,22,0.18), rgba(234,88,12,0.16)); color: rgb(251 146 60); }
        .hr-theme-orange .hr-stat-card__portal { background: rgba(255,247,237,0.95); color: rgb(234 88 12); border-color: rgba(249,115,22,0.14); }
        .dark .hr-theme-orange .hr-stat-card__portal { background: rgba(249,115,22,0.12); color: rgb(253 186 116); border-color: rgba(251,146,60,0.12); }
        .hr-theme-orange .hr-stat-card__footer { color: rgb(234 88 12); }

        .hr-theme-cyan::before { background: linear-gradient(135deg, rgba(6,182,212,0.12), rgba(8,145,178,0.04)); }
        .hr-theme-cyan::after { background: rgba(6,182,212,0.22); }
        .hr-theme-cyan:hover { border-color: rgba(6,182,212,0.28); }
        .hr-theme-cyan .hr-stat-card__icon-wrap { background: linear-gradient(135deg, rgba(207,250,254,1), rgba(165,243,252,0.88)); color: rgb(8 145 178); }
        .dark .hr-theme-cyan .hr-stat-card__icon-wrap { background: linear-gradient(135deg, rgba(6,182,212,0.18), rgba(8,145,178,0.16)); color: rgb(103 232 249); }
        .hr-theme-cyan .hr-stat-card__portal { background: rgba(236,254,255,0.95); color: rgb(8 145 178); border-color: rgba(6,182,212,0.14); }
        .dark .hr-theme-cyan .hr-stat-card__portal { background: rgba(6,182,212,0.12); color: rgb(165 243 252); border-color: rgba(103,232,249,0.12); }
        .hr-theme-cyan .hr-stat-card__footer { color: rgb(8 145 178); }

        .hr-list-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        @media (min-width: 1280px) {
            .hr-list-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .hr-list-card {
            border-radius: 1.5rem;
            border: 1px solid rgba(229, 231, 235, 1);
            background: linear-gradient(180deg, rgba(255,255,255,0.96), rgba(249,250,251,0.96));
            box-shadow: 0 10px 28px rgba(2, 6, 23, 0.04);
            overflow: hidden;
        }

        .dark .hr-list-card {
            border-color: rgba(255,255,255,0.08);
            background: linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.02));
            box-shadow: 0 12px 34px rgba(0, 0, 0, 0.22);
        }

        .hr-list-card__head {
            padding: 1.35rem 1.35rem 1rem;
            border-bottom: 1px solid rgba(229, 231, 235, 0.8);
        }

        .dark .hr-list-card__head {
            border-bottom-color: rgba(255,255,255,0.06);
        }

        .hr-list-card__title {
            font-size: 1.02rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: rgb(17 24 39);
        }

        .dark .hr-list-card__title {
            color: rgb(255 255 255);
        }

        .hr-list-card__subtitle {
            margin-top: 0.25rem;
            font-size: 0.84rem;
            color: rgb(107 114 128);
        }

        .dark .hr-list-card__subtitle {
            color: rgb(156 163 175);
        }

        .hr-list-card__body {
            padding: 1.1rem 1.35rem 1.35rem;
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
        }

        .hr-approval-item {
            display: block;
            border-radius: 1.15rem;
            border: 1px solid rgba(229, 231, 235, 0.92);
            background: rgba(249, 250, 251, 0.72);
            padding: 1rem;
            transition:
                transform 220ms ease,
                box-shadow 220ms ease,
                border-color 220ms ease,
                background 220ms ease;
        }

        .dark .hr-approval-item {
            border-color: rgba(255,255,255,0.08);
            background: rgba(255,255,255,0.025);
        }

        .hr-approval-item:hover {
            transform: translateY(-3px);
            background: rgba(255,255,255,1);
            box-shadow: 0 14px 28px rgba(2, 6, 23, 0.07);
            border-color: rgba(16, 185, 129, 0.22);
        }

        .dark .hr-approval-item:hover {
            background: rgba(255,255,255,0.045);
            box-shadow: 0 16px 30px rgba(0, 0, 0, 0.25);
            border-color: rgba(16, 185, 129, 0.18);
        }

        .hr-approval-item__row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .hr-approval-item__name {
            font-size: 0.96rem;
            font-weight: 700;
            color: rgb(17 24 39);
        }

        .dark .hr-approval-item__name {
            color: rgb(255 255 255);
        }

        .hr-approval-item__meta {
            margin-top: 0.2rem;
            font-size: 0.84rem;
            line-height: 1.5;
            color: rgb(107 114 128);
        }

        .dark .hr-approval-item__meta {
            color: rgb(156 163 175);
        }

        .hr-approval-item__date {
            margin-top: 0.55rem;
            font-size: 0.76rem;
            font-weight: 600;
            color: rgb(107 114 128);
        }

        .dark .hr-approval-item__date {
            color: rgb(156 163 175);
        }

        .hr-approval-item__icon {
            width: 1.1rem;
            height: 1.1rem;
            color: rgb(156 163 175);
            transition: transform 220ms ease, color 220ms ease;
            margin-top: 0.1rem;
        }

        .hr-approval-item:hover .hr-approval-item__icon {
            transform: translateX(3px);
            color: rgb(16 185 129);
        }

        .hr-empty {
            padding: 2.5rem 1rem;
            text-align: center;
            color: rgb(107 114 128);
        }

        .dark .hr-empty {
            color: rgb(156 163 175);
        }

        .hr-empty svg {
            width: 2rem;
            height: 2rem;
            margin: 0 auto;
            opacity: 0.75;
        }

        .hr-empty p {
            margin-top: 0.7rem;
            font-size: 0.92rem;
        }

.fi-wi-stats-overview-stat-value {
    font-size: 20px !important;
    font-weight: 700;
}
.fi-wi-stats-overview-stat-label {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 9999px;
    font-size: 12px !important;
    font-weight: 600;

    background: #dcfce7; /* light green */
    color: #166534;       /* dark green text */

    border: 1px solid #bbf7d0;
}
.dark .fi-wi-stats-overview-stat-label {
    background: rgba(34, 197, 94, 0.12);
    color: #86efac;
    border: 1px solid rgba(34, 197, 94, 0.25);
}
.fi-wi-stats-overview-stat:nth-child(3) .fi-wi-stats-overview-stat-label {
    display: inline-flex;
    align-items: center;
    gap: 6px;

    padding: 5px 12px;
    border-radius: 9999px;

    background: linear-gradient(135deg, #dcfce7, #bbf7d0);
    color: #14532d;

    font-size: 12px !important;
    font-weight: 600;

    box-shadow: 0 2px 6px rgba(34, 197, 94, 0.15);
}

    </style>

    <div
        x-data="{
            externalModalOpen: false,
            externalLink: '',
            externalLabel: '',
            openExternal(url, label) {
                this.externalLink = url
                this.externalLabel = label
                this.externalModalOpen = true
            },
            confirmExternal() {
                window.open(this.externalLink, '_blank', 'noopener,noreferrer')
                this.externalModalOpen = false
            }
        }"
        class="hr-page-shell"
    >
        <div class="hr-section">
            <div class="hr-section-head">
                <div>
                    <h2 class="hr-section-title">Statutory Payables</h2>
                    <p class="hr-section-subtitle">
                        Current payroll totals · click a card to visit the official portal
                    </p>
                </div>

                @if($currentPayroll)
                    <x-filament::badge color="info">
                        {{ \Carbon\Carbon::createFromDate($currentPayroll->year, $currentPayroll->month, 1)->format('F Y') }}
                    </x-filament::badge>
                @endif
            </div>

            @php
                $statutoryCards = [
                    [
                        'label' => 'Salary Payable',
                        'value' => $salaryPayable,
                        'icon' => 'heroicon-o-banknotes',
                        'theme' => 'blue',
                        'url' => '#',
                        'portal' => '',
                    ],
                    [
                        'label' => 'Gross Payroll',
                        'value' => $grossPay,
                        'icon' => 'heroicon-o-currency-dollar',
                        'theme' => 'emerald',
                        'url' => '#',
                        'portal' => 'Pay Staff',
                    ],
                    [
                        'label' => 'PAYE',
                        'value' => $payeTotal,
                        'icon' => 'heroicon-o-document-text',
                        'theme' => 'amber',
                        'url' => 'https://itax.kra.go.ke/KRA-Portal/',
                        'portal' => 'KRA iTax',
                    ],
                    [
                        'label' => 'NSSF',
                        'value' => $nssfTotal,
                        'icon' => 'heroicon-o-shield-check',
                        'theme' => 'purple',
                        'url' => 'https://eservice.nssfkenya.co.ke/',
                        'portal' => 'NSSF',
                    ],
                    [
                        'label' => 'SHA',
                        'value' => $shaTotal,
                        'icon' => 'heroicon-o-heart',
                        'theme' => 'rose',
                        'url' => 'https://employers.sha.go.ke/members',
                        'portal' => 'SHA',
                    ],
                    [
                        'label' => 'Housing Levy',
                        'value' => $housingLevyTotal,
                        'icon' => 'heroicon-o-home',
                        'theme' => 'orange',
                        'url' => 'https://itax.kra.go.ke/KRA-Portal/',
                        'portal' => 'Boma Yangu',
                    ],
                    [
                        'label' => 'NITA',
                        'value' => $nitaTotal,
                        'icon' => 'heroicon-o-academic-cap',
                        'theme' => 'cyan',
                        'url' => 'https://itax.kra.go.ke/KRA-Portal/',
                        'portal' => 'NITA',
                    ],
                ];
            @endphp

            <div class="hr-cards-grid">
                @foreach($statutoryCards as $card)
                    <button
                        type="button"
                        @click="openExternal('{{ $card['url'] }}', '{{ $card['portal'] }} – {{ $card['label'] }}')"
                        class="hr-stat-card hr-theme-{{ $card['theme'] }}"
                    >
                        <div>
                            <div class="hr-stat-card__top">
                                <div class="hr-stat-card__icon-wrap">
                                    <x-dynamic-component
                                        :component="$card['icon']"
                                        class="h-6 w-6"
                                    />
                                </div>

                                <span class="hr-stat-card__portal">
                                    {{ $card['portal'] }}
                                </span>
                            </div>

                            <div class="hr-stat-card__body">
                                <p class="hr-stat-card__label">{{ $card['label'] }}</p>
                                <p class="hr-stat-card__value">
                                    KES {{ number_format((float) $card['value'], 2) }}
                                </p>
                            </div>
                        </div>

                        <div class="hr-stat-card__footer">
                            <span>Visit portal</span>
                            <x-heroicon-o-arrow-right class="hr-stat-card__arrow" />
                        </div>
                    </button>
                @endforeach
            </div>
        </div>

        <div class="hr-list-grid">
            <section class="hr-list-card">
                <div class="hr-list-card__head">
                    <h3 class="hr-list-card__title">Pending Leave Applications</h3>
                    <p class="hr-list-card__subtitle">Awaiting approval</p>
                </div>

                <div class="hr-list-card__body">
                    @forelse ($pendingLeaves as $leave)
                        <a href="{{ \App\Filament\Resources\HR\LeaveApplicationResource::getUrl('edit', ['record' => $leave]) }}" class="hr-approval-item">
                            <div class="hr-approval-item__row">
                                <div>
                                    <p class="hr-approval-item__name">
                                        {{ $leave->employee->full_name ?? 'Unknown Employee' }}
                                    </p>
                                    <p class="hr-approval-item__meta">
                                        {{ $leave->leaveType->name ?? 'Leave' }} · {{ number_format((float) $leave->days_requested, 2) }} day(s)
                                    </p>
                                    <p class="hr-approval-item__date">
                                        {{ \Carbon\Carbon::parse($leave->start_date)->format('d M Y') }} – {{ \Carbon\Carbon::parse($leave->end_date)->format('d M Y') }}
                                    </p>
                                </div>

                                <x-heroicon-o-chevron-right class="hr-approval-item__icon" />
                            </div>
                        </a>
                    @empty
                        <div class="hr-empty">
                            <x-heroicon-o-check-circle />
                            <p>All caught up! No pending leave requests.</p>
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="hr-list-card">
                <div class="hr-list-card__head">
                    <h3 class="hr-list-card__title">Pending Salary Advances</h3>
                    <p class="hr-list-card__subtitle">Awaiting approval</p>
                </div>

                <div class="hr-list-card__body">
                    @forelse ($pendingAdvances as $advance)
                        <a href="{{ \App\Filament\Resources\HR\SalaryAdvanceResource::getUrl('edit', ['record' => $advance]) }}" class="hr-approval-item">
                            <div class="hr-approval-item__row">
                                <div>
                                    <p class="hr-approval-item__name">
                                        {{ $advance->employee->full_name ?? 'Unknown Employee' }}
                                    </p>
                                    <p class="hr-approval-item__meta">
                                        Requested: KES {{ number_format((float) $advance->amount_requested, 2) }}
                                    </p>
                                    <p class="hr-approval-item__date">
                                        {{ optional($advance->request_date)->format('d M Y') ?? '—' }}
                                    </p>
                                </div>

                                <x-heroicon-o-chevron-right class="hr-approval-item__icon" />
                            </div>
                        </a>
                    @empty
                        <div class="hr-empty">
                            <x-heroicon-o-check-circle />
                            <p>No pending salary advances.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>

        <div
            x-show="externalModalOpen"
            x-cloak
            x-transition.opacity.duration.300ms
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
        >
            <div
                x-show="externalModalOpen"
                x-transition.scale.origin.center.duration.300ms
                class="w-full max-w-md rounded-3xl bg-white dark:bg-gray-900 shadow-2xl border border-gray-200 dark:border-white/10 p-6"
            >
                <div class="flex items-start gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-100 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300">
                        <x-heroicon-o-exclamation-triangle class="h-6 w-6" />
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Open External Website</h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                            You are about to leave the system and open:
                            <span class="font-medium" x-text="externalLabel"></span>
                        </p>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Please confirm before continuing.
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <x-filament::button color="gray" x-on:click="externalModalOpen = false">
                        Cancel
                    </x-filament::button>

                    <x-filament::button color="warning" x-on:click="confirmExternal()">
                        Continue
                    </x-filament::button>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
