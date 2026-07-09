<x-filament-panels::page>
    @php
        $farmName = setting('farm.name', config('app.name', 'Lelekwe Farms'));
        $primaryColor = setting('theme.primary', '#008f00');
        $secondaryColor = setting('theme.secondary', '#111827');

        $purchaseOrdersUrl = \App\Filament\Resources\PurchaseOrderResource::getUrl('index');
        $paymentsUrl = \App\Filament\Resources\PurchaseOrderPaymentResource::getUrl('index');
        $inventoryUrl = \App\Filament\Resources\InventoryItemResource::getUrl('index');
        $suppliersUrl = \App\Filament\Resources\SupplierResource::getUrl('index');
    @endphp

    <style>
        .fi-header-heading {
            display: none !important;
        }

        .fi-header {
            margin-bottom: 0 !important;
        }

        [x-cloak] {
            display: none !important;
        }

        .procurement-hero {
            border-radius: 24px;
            padding: 22px 24px;
            color: white;
            background:
                radial-gradient(circle at top left, rgba(255, 255, 255, .22), transparent 28%),
                linear-gradient(135deg, {{ $primaryColor }}, {{ $secondaryColor }});
            box-shadow: 0 22px 60px rgba(15, 23, 42, .24);
            position: relative;
            overflow: hidden;
        }

        .procurement-hero::after {
            content: "";
            position: absolute;
            width: 260px;
            height: 260px;
            right: -80px;
            top: -80px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .12);
            pointer-events: none;
        }

        .procurement-kicker {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .14em;
            opacity: .82;
            font-weight: 800;
        }

        .procurement-title {
            margin-top: 7px;
            font-size: clamp(22px, 3vw, 32px);
            font-weight: 900;
            line-height: 1.05;
        }

        .procurement-subtitle {
            margin-top: 8px;
            max-width: 680px;
            color: rgba(255, 255, 255, .84);
            font-size: 13px;
            line-height: 1.5;
        }

        .procurement-pills {
            margin-top: 14px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            position: relative;
            z-index: 2;
        }

        .procurement-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid rgba(255, 255, 255, .24);
            background: rgba(255, 255, 255, .13);
            padding: 7px 11px;
            border-radius: 999px;
            font-size: 11.5px;
            font-weight: 800;
            backdrop-filter: blur(10px);
            transition: all .18s ease;
            text-decoration: none;
            color: white;
        }

        .procurement-pill:hover {
            transform: translateY(-1px);
            background: rgba(255, 255, 255, .22);
            box-shadow: 0 14px 30px rgba(15, 23, 42, .22);
        }

        .procurement-filter-card {
            border-radius: 22px;
            padding: 18px;
            background: rgba(255, 255, 255, .78);
            border: 1px solid rgba(148, 163, 184, .22);
            box-shadow: 0 16px 36px rgba(15, 23, 42, .08);
        }

        .dark .procurement-filter-card {
            background: rgba(15, 23, 42, .72);
            border-color: rgba(148, 163, 184, .18);
        }

        .procurement-filter-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 14px;
            align-items: end;
        }

        @media (min-width: 768px) {
            .procurement-filter-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        .procurement-label {
            font-size: 12px;
            font-weight: 800;
            color: rgb(55 65 81);
            margin-bottom: 6px;
        }

        .dark .procurement-label {
            color: rgb(229 231 235);
        }

        .procurement-date-input {
            width: 100%;
            border-radius: 14px;
            border: 1px solid rgb(209 213 219);
            padding: 10px 12px;
            font-size: 14px;
            background: white;
            color: rgb(17 24 39);
        }

        .dark .procurement-date-input {
            background: rgb(17 24 39);
            border-color: rgb(55 65 81);
            color: rgb(243 244 246);
        }

        .procurement-quick-actions {
            margin-top: 14px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .procurement-quick-btn {
            border-radius: 999px;
            padding: 9px 13px;
            font-size: 12px;
            font-weight: 800;
            border: 1px solid rgba(148, 163, 184, .32);
            background: white;
            color: rgb(17 24 39);
            cursor: pointer;
            transition: all .16s ease;
        }

        .procurement-quick-btn:hover,
        .procurement-quick-btn-active {
            transform: translateY(-1px);
            box-shadow: 0 10px 24px rgba(15, 23, 42, .12);
            background: {{ $primaryColor }};
            color: white;
            border-color: {{ $primaryColor }};
        }

        .dark .procurement-quick-btn {
            background: rgb(31 41 55);
            color: white;
            border-color: rgb(75 85 99);
        }

        .dark .procurement-quick-btn-active {
            background: {{ $primaryColor }};
            border-color: {{ $primaryColor }};
        }

        .procurement-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
        }

        @media (min-width: 1280px) {
            .procurement-grid {
                grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            }
        }

        .procurement-charts-row {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
            align-items: stretch;
            width: 100%;
        }

        .procurement-chart-box {
            min-width: 0;
            width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            border-radius: 20px;
            padding-bottom: 4px;
        }

        .procurement-chart-box>div {
            min-width: 580px;
        }

        @media (min-width: 1100px) {
            .procurement-charts-row {
                grid-template-columns: minmax(0, 1.35fr) minmax(0, .95fr);
            }

            .procurement-chart-box>div {
                min-width: 0;
            }
        }

        .procurement-section-title h2 {
            font-size: 16px;
            font-weight: 900;
            color: rgb(17 24 39);
        }

        .dark .procurement-section-title h2 {
            color: rgb(243 244 246);
        }

        .procurement-section-title p {
            margin-top: 3px;
            font-size: 12px;
            color: rgb(107 114 128);
        }

        .dark .procurement-section-title p {
            color: rgb(156 163 175);
        }

        .fi-wi-stats-overview {
            gap: 14px !important;
        }

        .procurement-stat-card {
            border-radius: 20px !important;
            overflow: hidden;
        }

        .procurement-stat-card .fi-wi-stats-overview-stat-label {
            font-size: 11px !important;
            font-weight: 900 !important;
            letter-spacing: .06em !important;
            text-transform: uppercase !important;
            color: rgb(107 114 128) !important;
        }

        .dark .procurement-stat-card .fi-wi-stats-overview-stat-label {
            color: rgb(156 163 175) !important;
        }

        .procurement-stat-card .fi-wi-stats-overview-stat-value {
            font-size: clamp(18px, 2.2vw, 24px) !important;
            line-height: 1.1 !important;
            font-weight: 900 !important;
        }

        .procurement-stat-card .fi-wi-stats-overview-stat-description {
            font-size: 11px !important;
            line-height: 1.25 !important;
            font-weight: 700 !important;
        }

        .procurement-stat-card svg {
            width: 18px;
            height: 18px;
        }

        .procurement-insight-section {
            border-radius: 24px;
            padding: 18px;
            border: 1px solid rgba(148, 163, 184, .22);
            background:
                radial-gradient(circle at top left, rgba(255, 255, 255, .65), transparent 32%),
                linear-gradient(180deg, rgba(255, 255, 255, .92), rgba(249, 250, 251, .86));
            box-shadow: 0 18px 46px rgba(15, 23, 42, .08);
            overflow: hidden;
        }

        .dark .procurement-insight-section {
            background:
                radial-gradient(circle at top left, rgba(255, 255, 255, .08), transparent 32%),
                linear-gradient(180deg, rgba(15, 23, 42, .94), rgba(17, 24, 39, .88));
            border-color: rgba(148, 163, 184, .18);
        }

        .procurement-insight-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 14px;
        }

        .procurement-insight-kicker {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: {{ $primaryColor }};
        }

        .procurement-insight-title {
            margin-top: 4px;
            font-size: 16px;
            font-weight: 900;
            color: rgb(17 24 39);
        }

        .dark .procurement-insight-title {
            color: rgb(243 244 246);
        }

        .procurement-insight-subtitle {
            margin-top: 3px;
            max-width: 780px;
            font-size: 12px;
            line-height: 1.55;
            color: rgb(107 114 128);
        }

        .dark .procurement-insight-subtitle {
            color: rgb(156 163 175);
        }

        /* Section itself does NOT scroll. Cards wrap responsively. */
        .procurement-insight-scroll {
            width: 100%;
            overflow: visible;
            padding-bottom: 0;
        }

        /* This is the key fix. Cards only become 3 columns when there is enough space. */
        .procurement-insight-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 520px), 1fr));
            gap: 18px;
            width: 100%;
            min-width: 0;
        }

        .procurement-insight-card {
            min-width: 0;
            width: 100%;
            border-radius: 24px;
            overflow: hidden;
            border: 1px solid rgba(148, 163, 184, .20);
            background: rgba(255, 255, 255, .94);
            box-shadow: 0 16px 38px rgba(15, 23, 42, .075);
        }

        .dark .procurement-insight-card {
            background: rgba(15, 23, 42, .82);
            border-color: rgba(148, 163, 184, .18);
        }

        .procurement-insight-card-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 14px;
            border-bottom: 1px solid rgba(148, 163, 184, .18);
        }

        .procurement-insight-card-title {
            min-width: 0;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            font-weight: 900;
            color: rgb(17 24 39);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dark .procurement-insight-card-title {
            color: rgb(243 244 246);
        }

        .procurement-expand-btn {
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            padding: 7px 11px;
            font-size: 11px;
            font-weight: 900;
            color: white;
            background: linear-gradient(135deg, {{ $primaryColor }}, {{ $secondaryColor }});
            border: 1px solid rgba(255, 255, 255, .18);
            box-shadow: 0 10px 22px rgba(15, 23, 42, .13);
            cursor: pointer;
            transition: transform .16s ease, box-shadow .16s ease;
            white-space: nowrap;
        }

        .procurement-expand-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 28px rgba(15, 23, 42, .18);
        }

        /* Only the inside of each card can scroll. */
        .procurement-widget-shell {
            width: 100%;
            min-width: 0;
            padding: 12px;
            min-height: 390px;
            overflow-x: auto;
            overflow-y: hidden;
            scrollbar-width: thin;
        }

        .procurement-widget-shell::-webkit-scrollbar {
            height: 7px;
        }

        .procurement-widget-shell::-webkit-scrollbar-track {
            background: rgba(148, 163, 184, .14);
            border-radius: 999px;
        }

        .procurement-widget-shell::-webkit-scrollbar-thumb {
            background: {{ $primaryColor }};
            border-radius: 999px;
        }

        /* Gives chart enough room, but does not force the whole section to scroll. */
        .procurement-widget-shell>div {
            min-width: 500px;
        }

        /* Hide duplicated Filament chart heading/description inside compact cards. */
        .procurement-widget-shell .fi-section-header,
        .procurement-widget-shell header,
        .procurement-widget-shell .fi-wi-chart>div:first-child {
            display: none !important;
        }

        .procurement-widget-shell .fi-wi-widget,
        .procurement-widget-shell .fi-section {
            box-shadow: none !important;
            border-radius: 20px !important;
        }

        .procurement-widget-shell canvas {
            min-height: 310px !important;
            max-height: 330px !important;
        }

        /* Large modal */
        .procurement-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(2, 6, 23, .72);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 22px;
        }

        .procurement-modal-panel {
            width: min(1180px, 96vw);
            max-height: 92vh;
            overflow: auto;
            border-radius: 26px;
            background: white;
            border: 1px solid rgba(148, 163, 184, .24);
            box-shadow: 0 30px 90px rgba(0, 0, 0, .36);
        }

        .dark .procurement-modal-panel {
            background: rgb(15 23 42);
            border-color: rgba(148, 163, 184, .18);
        }

        .procurement-modal-head {
            position: sticky;
            top: 0;
            z-index: 3;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 18px;
            border-bottom: 1px solid rgba(148, 163, 184, .22);
            background: rgba(255, 255, 255, .94);
            backdrop-filter: blur(14px);
        }

        .dark .procurement-modal-head {
            background: rgba(15, 23, 42, .94);
        }

        .procurement-modal-title {
            font-size: 15px;
            font-weight: 900;
            color: rgb(17 24 39);
        }

        .dark .procurement-modal-title {
            color: rgb(243 244 246);
        }

        .procurement-modal-subtitle {
            margin-top: 2px;
            font-size: 12px;
            color: rgb(107 114 128);
        }

        .dark .procurement-modal-subtitle {
            color: rgb(156 163 175);
        }

        .procurement-modal-close {
            flex-shrink: 0;
            border-radius: 999px;
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 900;
            color: white;
            background: rgb(220 38 38);
            cursor: pointer;
        }

        .procurement-modal-body {
            padding: 16px;
            overflow-x: auto;
        }

        .procurement-modal-body>div {
            min-width: 900px;
        }

        .procurement-modal-body .fi-wi-widget,
        .procurement-modal-body .fi-section {
            box-shadow: none !important;
            border-radius: 20px !important;
        }

        .procurement-modal-body canvas {
            min-height: 560px !important;
            max-height: 620px !important;
        }

        @media (max-width: 768px) {
            .procurement-insight-section {
                padding: 14px;
                border-radius: 20px;
            }

            .procurement-insight-head {
                flex-direction: column;
            }

            .procurement-insight-card-top {
                align-items: flex-start;
            }

            .procurement-widget-shell {
                min-height: 380px;
            }

            .procurement-widget-shell>div {
                min-width: 500px;
            }

            .procurement-modal-backdrop {
                padding: 10px;
                align-items: flex-start;
            }

            .procurement-modal-panel {
                margin-top: 12px;
                max-height: 94vh;
            }

            .procurement-modal-body>div {
                min-width: 760px;
            }
        }
    </style>

    <div class="space-y-6">
        <section class="procurement-hero">
            <div class="procurement-kicker">Procurement intelligence center</div>

            <div class="procurement-title">
                {{ $farmName }} Procurement Control
            </div>

            <div class="procurement-subtitle">
                Track supplier invoices, payments, stock pressure, tax exposure, and payables from one executive
                dashboard.
            </div>

            <div class="procurement-pills">
                <a href="{{ $paymentsUrl }}" class="procurement-pill">
                    <x-heroicon-o-banknotes class="h-4 w-4" />
                    Supplier Payments
                </a>

                <a href="{{ $purchaseOrdersUrl }}" class="procurement-pill">
                    <x-heroicon-o-clipboard-document-list class="h-4 w-4" />
                    Purchase Orders
                </a>

                <a href="{{ $inventoryUrl }}" class="procurement-pill">
                    <x-heroicon-o-cube class="h-4 w-4" />
                    Stock Items
                </a>

                <a href="{{ $suppliersUrl }}" class="procurement-pill">
                    <x-heroicon-o-building-storefront class="h-4 w-4" />
                    Suppliers
                </a>
            </div>
        </section>

        <section class="procurement-filter-card">
            <div class="procurement-filter-grid">
                <div>
                    <div class="procurement-label">Date From</div>
                    <input type="date" wire:model.live.debounce.500ms="dateFrom" class="procurement-date-input">
                </div>

                <div>
                    <div class="procurement-label">Date To</div>
                    <input type="date" wire:model.live.debounce.500ms="dateTo" class="procurement-date-input">
                </div>
            </div>

            <div class="procurement-quick-actions">
                @foreach ([
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        'this_week' => 'This Week',
        'this_month' => 'This Month',
        'last_30' => 'Last 30 Days',
        'last_90' => 'Last 90 Days',
        'this_year' => 'This Year',
    ] as $key => $label)
                    <button type="button" wire:click="setRange('{{ $key }}')"
                        class="procurement-quick-btn {{ $activeRange === $key ? 'procurement-quick-btn-active' : '' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </section>

        @livewire(\App\Filament\Widgets\ProcurementStatsOverview::class, ['dateFrom' => $dateFrom, 'dateTo' => $dateTo], key('procurement-stats-' . $dateFrom . '-' . $dateTo))

        <div class="procurement-section-title">
            <h2>Procurement Performance</h2>
            <p> </p>
        </div>

        <div class="procurement-charts-row">
            <div class="procurement-chart-box">
                @livewire(\App\Filament\Widgets\ProcurementSpendingTrendChart::class, ['dateFrom' => $dateFrom, 'dateTo' => $dateTo], key('procurement-spend-chart-' . $dateFrom . '-' . $dateTo))
            </div>

            <div class="procurement-chart-box">
                @livewire(\App\Filament\Widgets\ProcurementPaymentStatusChart::class, ['dateFrom' => $dateFrom, 'dateTo' => $dateTo], key('procurement-payment-chart-' . $dateFrom . '-' . $dateTo))
            </div>
        </div>

        <section class="procurement-insight-section" x-data="{ openProcurementChart: null }">
            <div class="procurement-insight-head">
                <div>
                    <div class="procurement-insight-kicker">
                        <x-heroicon-o-sparkles class="h-4 w-4" />
                        Procurement deep insights
                    </div>

                    <div class="procurement-insight-title">
                        Supplier, Category & Order Intelligence
                    </div>

                    <div class="procurement-insight-subtitle">
                        Compare supplier spend, category exposure, and order flow status in one adaptive executive
                        strip.
                    </div>
                </div>
            </div>

            <div class="procurement-insight-scroll">
                <div class="procurement-insight-grid">
                    <div class="procurement-insight-card">
                        <div class="procurement-insight-card-top">
                            <div class="procurement-insight-card-title">
                                <x-heroicon-o-building-storefront class="h-4 w-4" />
                                Top Suppliers
                            </div>

                            <button type="button" class="procurement-expand-btn"
                                x-on:click="
                                    openProcurementChart = 'topSuppliers';
                                    setTimeout(() => window.dispatchEvent(new Event('resize')), 250);
                                ">
                                <x-heroicon-o-arrows-pointing-out class="h-4 w-4" />

                            </button>
                        </div>

                        <div class="procurement-widget-shell">
                            @livewire(\App\Filament\Widgets\ProcurementTopSuppliersSpendChart::class, ['dateFrom' => $dateFrom, 'dateTo' => $dateTo], key('top-suppliers-card-' . $dateFrom . '-' . $dateTo))
                        </div>
                    </div>

                    <div class="procurement-insight-card">
                        <div class="procurement-insight-card-top">
                            <div class="procurement-insight-card-title">
                                <x-heroicon-o-chart-pie class="h-4 w-4" />
                                Expense Category
                            </div>

                            <button type="button" class="procurement-expand-btn"
                                x-on:click="
                                    openProcurementChart = 'expenseCategory';
                                    setTimeout(() => window.dispatchEvent(new Event('resize')), 250);
                                ">
                                <x-heroicon-o-arrows-pointing-out class="h-4 w-4" />

                            </button>
                        </div>

                        <div class="procurement-widget-shell">
                            @livewire(\App\Filament\Widgets\ProcurementExpenseCategoryChart::class, ['dateFrom' => $dateFrom, 'dateTo' => $dateTo], key('expense-category-card-' . $dateFrom . '-' . $dateTo))
                        </div>
                    </div>

                    <div class="procurement-insight-card">
                        <div class="procurement-insight-card-top">
                            <div class="procurement-insight-card-title">
                                <x-heroicon-o-shield-check class="h-4 w-4" />
                                Order Status
                            </div>

                            <button type="button" class="procurement-expand-btn"
                                x-on:click="
                                    openProcurementChart = 'orderStatus';
                                    setTimeout(() => window.dispatchEvent(new Event('resize')), 250);
                                ">
                                <x-heroicon-o-arrows-pointing-out class="h-4 w-4" />

                            </button>
                        </div>

                        <div class="procurement-widget-shell">
                            @livewire(\App\Filament\Widgets\ProcurementApprovalStatusChart::class, ['dateFrom' => $dateFrom, 'dateTo' => $dateTo], key('order-status-card-' . $dateFrom . '-' . $dateTo))
                        </div>
                    </div>
                </div>
            </div>

            <div x-cloak x-show="openProcurementChart === 'topSuppliers'" x-transition.opacity
                class="procurement-modal-backdrop" x-on:keydown.escape.window="openProcurementChart = null">
                <div class="procurement-modal-panel" x-on:click.outside="openProcurementChart = null">
                    <div class="procurement-modal-head">
                        <div>
                            <div class="procurement-modal-title">Top Suppliers by Spend</div>
                            <div class="procurement-modal-subtitle">
                                Suppliers delivering the highest procurement value in the selected period.
                            </div>
                        </div>

                        <button type="button" class="procurement-modal-close" x-on:click="openProcurementChart = null">
                            Close
                        </button>
                    </div>

                    <div class="procurement-modal-body">
                        @livewire(\App\Filament\Widgets\ProcurementTopSuppliersSpendChart::class, ['dateFrom' => $dateFrom, 'dateTo' => $dateTo], key('top-suppliers-modal-' . $dateFrom . '-' . $dateTo))
                    </div>
                </div>
            </div>

            <div x-cloak x-show="openProcurementChart === 'expenseCategory'" x-transition.opacity
                class="procurement-modal-backdrop" x-on:keydown.escape.window="openProcurementChart = null">
                <div class="procurement-modal-panel" x-on:click.outside="openProcurementChart = null">
                    <div class="procurement-modal-head">
                        <div>
                            <div class="procurement-modal-title">Expense by Category</div>
                            <div class="procurement-modal-subtitle">
                                Spend breakdown by feed, vaccines, dewormers, dips, treatments, and other inputs.
                            </div>
                        </div>

                        <button type="button" class="procurement-modal-close" x-on:click="openProcurementChart = null">
                            Close
                        </button>
                    </div>

                    <div class="procurement-modal-body">
                        @livewire(\App\Filament\Widgets\ProcurementExpenseCategoryChart::class, ['dateFrom' => $dateFrom, 'dateTo' => $dateTo], key('expense-category-modal-' . $dateFrom . '-' . $dateTo))
                    </div>
                </div>
            </div>

            <div x-cloak x-show="openProcurementChart === 'orderStatus'" x-transition.opacity
                class="procurement-modal-backdrop" x-on:keydown.escape.window="openProcurementChart = null">
                <div class="procurement-modal-panel" x-on:click.outside="openProcurementChart = null">
                    <div class="procurement-modal-head">
                        <div>
                            <div class="procurement-modal-title">Purchase Order Status</div>
                            <div class="procurement-modal-subtitle">
                                Current procurement flow status: draft, ordered, partially received, received and
                                cancelled.
                            </div>
                        </div>

                        <button type="button" class="procurement-modal-close"
                            x-on:click="openProcurementChart = null">
                            Close
                        </button>
                    </div>

                    <div class="procurement-modal-body">
                        @livewire(\App\Filament\Widgets\ProcurementApprovalStatusChart::class, ['dateFrom' => $dateFrom, 'dateTo' => $dateTo], key('order-status-modal-' . $dateFrom . '-' . $dateTo))
                    </div>
                </div>
            </div>
        </section>

        @livewire(\App\Filament\Widgets\ProcurementSupplierAgeingWidget::class, ['dateFrom' => $dateFrom, 'dateTo' => $dateTo], key('supplier-ageing-' . $dateFrom . '-' . $dateTo))

        <div class="procurement-grid">
            @livewire(\App\Filament\Widgets\ProcurementOverdueInvoicesWidget::class, ['dateFrom' => $dateFrom, 'dateTo' => $dateTo], key('procurement-overdue-' . $dateFrom . '-' . $dateTo))

            @livewire(\App\Filament\Widgets\ProcurementLowStockWidget::class, ['dateFrom' => $dateFrom, 'dateTo' => $dateTo], key('procurement-low-stock-' . $dateFrom . '-' . $dateTo))
        </div>

        <div class="procurement-grid">
            @livewire(\App\Filament\Widgets\ProcurementGoodsReceivedVarianceWidget::class, ['dateFrom' => $dateFrom, 'dateTo' => $dateTo], key('grn-variance-' . $dateFrom . '-' . $dateTo))

            @livewire(\App\Filament\Widgets\ProcurementReorderSuggestionsWidget::class, key('reorder-suggestions-' . $dateFrom . '-' . $dateTo))
        </div>
    </div>
</x-filament-panels::page>
