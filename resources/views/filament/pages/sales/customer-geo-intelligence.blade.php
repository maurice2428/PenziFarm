<div class="geo-page" wire:key="customer-geo-intelligence-root">
    @php
        $primaryColor = trim(setting('theme.primary', '#008f00'));
        $secondaryColor = trim(setting('theme.secondary', '#111827'));
        $accentColor = trim(setting('theme.accent', '#f59e0b'));
    @endphp

    <style>
        .geo-page {
            --geo-primary: {{ $primaryColor }};
            --geo-secondary: {{ $secondaryColor }};
            --geo-accent: {{ $accentColor }};
            --geo-border: rgba(15, 23, 42, .14);
            display: block;
        }

        .geo-hero {
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, .20);
            border-radius: 30px;
            padding: 28px;
            color: #ffffff;
            background:
                radial-gradient(circle at 85% 12%, rgba(255,255,255,.22), transparent 28%),
                radial-gradient(circle at 12% 88%, rgba(0,0,0,.24), transparent 34%),
                linear-gradient(135deg, var(--geo-primary), var(--geo-secondary));
            box-shadow: 0 24px 60px rgba(15, 23, 42, .22);
            isolation: isolate;
        }

        .geo-hero::before {
            content: "";
            position: absolute;
            right: -80px;
            top: -80px;
            width: 280px;
            height: 280px;
            border-radius: 999px;
            background: rgba(255,255,255,.09);
            z-index: -1;
        }

        .geo-hero::after {
            content: "";
            position: absolute;
            left: -90px;
            bottom: -100px;
            width: 300px;
            height: 300px;
            border-radius: 999px;
            background: rgba(0,0,0,.13);
            z-index: -1;
        }

        .geo-hero-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 340px;
            gap: 26px;
            align-items: center;
        }

        .geo-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid rgba(255,255,255,.24);
            border-radius: 999px;
            background: rgba(255,255,255,.15);
            padding: 7px 13px;
            font-size: 10px;
            font-weight: 950;
            letter-spacing: .18em;
            text-transform: uppercase;
            backdrop-filter: blur(14px);
        }

        .geo-title {
            margin-top: 16px;
            font-size: clamp(28px, 4vw, 46px);
            font-weight: 950;
            line-height: 1.02;
            letter-spacing: -.055em;
        }

        .geo-copy {
            margin-top: 13px;
            max-width: 850px;
            color: rgba(255,255,255,.86);
            font-size: 14px;
            line-height: 1.75;
        }

        .geo-hero-mini-grid {
            display: grid;
            gap: 10px;
        }

        .geo-hero-mini {
            border: 1px solid rgba(255,255,255,.18);
            border-radius: 18px;
            background: rgba(255,255,255,.13);
            padding: 13px;
            backdrop-filter: blur(16px);
        }

        .geo-hero-mini-label {
            color: rgba(255,255,255,.72);
            font-size: 9px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .12em;
        }

        .geo-hero-mini-value {
            margin-top: 4px;
            color: #ffffff;
            font-size: 18px;
            font-weight: 950;
        }

        .geo-panel,
        .geo-card,
        .geo-map-shell {
            border: 1px solid rgba(15, 23, 42, .12);
            background: #ffffff;
            box-shadow: 0 12px 30px rgba(15, 23, 42, .07);
        }

        .geo-panel {
            margin-top: 18px;
            border-radius: 18px;
            padding: 16px;
        }

        .geo-filter-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 12px;
            align-items: end;
        }

        .geo-label {
            display: block;
            margin-bottom: 6px;
            color: #475569;
            font-size: 10px;
            font-weight: 950;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .geo-input,
        .geo-select {
            width: 100%;
            min-height: 42px;
            border: 1px solid rgba(15, 23, 42, .16);
            border-radius: 10px;
            background: #ffffff;
            padding: 9px 11px;
            color: #0f172a;
            font-size: 13px;
            font-weight: 700;
            outline: none;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.7);
        }

        .geo-select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: none !important;
            padding-right: 11px;
        }

        .geo-select::-ms-expand {
            display: none;
        }

        .geo-input:focus,
        .geo-select:focus {
            border-color: var(--geo-primary);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--geo-primary) 18%, transparent);
        }

        .geo-check {
            display: flex;
            min-height: 42px;
            align-items: center;
            gap: 9px;
            border: 1px solid rgba(15, 23, 42, .14);
            border-radius: 10px;
            padding: 9px 11px;
            color: #334155;
            font-size: 12px;
            font-weight: 850;
            background: linear-gradient(135deg, #ffffff, #f8fafc);
        }

        .geo-kpis {
            margin-top: 16px;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        .geo-card {
            position: relative;
            overflow: hidden;
            border-radius: 16px;
            padding: 16px;
        }

        .geo-card::after {
            content: "";
            position: absolute;
            right: -26px;
            top: -30px;
            width: 96px;
            height: 96px;
            border-radius: 999px;
            background: var(--geo-primary);
            opacity: .08;
        }

        .geo-card-icon {
            display: flex;
            width: 42px;
            height: 42px;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--geo-primary), var(--geo-secondary));
            color: #ffffff;
        }

        .geo-card-label {
            margin-top: 12px;
            color: #64748b;
            font-size: 10px;
            font-weight: 950;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .geo-card-value {
            margin-top: 5px;
            color: #0f172a;
            font-size: 22px;
            font-weight: 950;
            line-height: 1.12;
            word-break: break-word;
        }

        .geo-card-foot {
            margin-top: 6px;
            color: #64748b;
            font-size: 11px;
            line-height: 1.45;
        }

        .geo-layout {
            margin-top: 16px;
            display: grid;
            grid-template-columns: minmax(0, 1fr) 360px;
            gap: 16px;
            align-items: start;
        }

        .geo-map-shell {
            overflow: hidden;
            border-radius: 18px;
        }

        .geo-map-shell:fullscreen {
            border-radius: 0;
            background: #ffffff;
            padding: 12px;
        }

        .geo-map-shell:fullscreen #customer-geo-map {
            height: calc(100vh - 92px);
        }

        .geo-map-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            border-bottom: 1px solid rgba(15, 23, 42, .10);
            padding: 14px 16px;
            background:
                linear-gradient(135deg, color-mix(in srgb, var(--geo-primary) 8%, #ffffff), #ffffff);
        }

        .geo-map-title {
            color: #0f172a;
            font-size: 14px;
            font-weight: 950;
        }

        .geo-map-subtitle {
            margin-top: 3px;
            color: #64748b;
            font-size: 11px;
            line-height: 1.45;
        }

        .geo-map-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
        }

        .geo-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(15, 23, 42, .12);
            border-radius: 10px;
            background: #ffffff;
            color: #0f172a;
            padding: 9px 11px;
            font-size: 11px;
            font-weight: 950;
            line-height: 1;
            cursor: pointer;
        }

        .geo-btn-primary {
            border-color: transparent;
            background: linear-gradient(135deg, var(--geo-primary), var(--geo-secondary));
            color: #ffffff;
        }

        #customer-geo-map {
            width: 100%;
            height: 680px;
            background: #e5e7eb;
            z-index: 1;
        }

        .geo-side {
            display: grid;
            gap: 12px;
        }

        .geo-section-title {
            color: #0f172a;
            font-size: 13px;
            font-weight: 950;
            letter-spacing: -.01em;
        }

        .geo-section-subtitle {
            margin-top: 3px;
            color: #64748b;
            font-size: 11px;
            line-height: 1.5;
        }

        .geo-location-row,
        .geo-smart-row {
            margin-top: 10px;
            display: flex;
            justify-content: space-between;
            gap: 12px;
            border: 1px solid rgba(15, 23, 42, .08);
            border-radius: 12px;
            padding: 10px;
            background: linear-gradient(135deg, #ffffff, #f8fafc);
        }

        .geo-location-name {
            color: #334155;
            font-size: 12px;
            font-weight: 900;
        }

        .geo-location-meta {
            margin-top: 3px;
            color: #64748b;
            font-size: 10.5px;
            line-height: 1.45;
        }

        .geo-pill {
            border-radius: 999px;
            background: color-mix(in srgb, var(--geo-primary) 12%, #ffffff);
            color: var(--geo-primary);
            padding: 5px 8px;
            font-size: 11px;
            font-weight: 950;
            white-space: nowrap;
            height: fit-content;
        }

        .geo-insight-grid {
            margin-top: 16px;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        .geo-insight-card {
            border: 1px solid rgba(15, 23, 42, .12);
            border-radius: 16px;
            background: #ffffff;
            padding: 14px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, .06);
        }

        .geo-insight-title {
            color: #64748b;
            font-size: 10px;
            font-weight: 950;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .geo-insight-value {
            margin-top: 6px;
            color: #0f172a;
            font-size: 14px;
            font-weight: 950;
            line-height: 1.35;
        }

        .geo-insight-body {
            margin-top: 6px;
            color: #64748b;
            font-size: 11px;
            line-height: 1.5;
        }

        .customer-popup {
            min-width: 245px;
            font-family: Arial, sans-serif;
        }

        .customer-popup-title {
            color: #0f172a;
            font-size: 14px;
            font-weight: 900;
        }

        .customer-popup-meta {
            margin-top: 4px;
            color: #64748b;
            font-size: 12px;
            line-height: 1.45;
        }

        .customer-popup-tags {
            margin-top: 8px;
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }

        .customer-popup-tag {
            border-radius: 999px;
            background: #ecfdf5;
            color: #047857;
            padding: 3px 6px;
            font-size: 10px;
            font-weight: 800;
        }

        @media (max-width: 1180px) {
            .geo-hero-grid,
            .geo-layout {
                grid-template-columns: 1fr;
            }

            .geo-hero-mini-grid,
            .geo-side {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .geo-filter-grid,
            .geo-kpis,
            .geo-insight-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 700px) {
            .geo-hero {
                border-radius: 22px;
                padding: 20px 15px;
            }

            .geo-filter-grid,
            .geo-kpis,
            .geo-insight-grid,
            .geo-side,
            .geo-hero-mini-grid {
                grid-template-columns: 1fr;
            }

            .geo-map-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .geo-map-actions {
                width: 100%;
                justify-content: stretch;
            }

            .geo-btn {
                flex: 1;
            }

            #customer-geo-map {
                height: 540px;
            }
        }

        .dark .geo-panel,
        .dark .geo-card,
        .dark .geo-map-shell,
        .dark .geo-insight-card {
            border-color: rgba(148, 163, 184, .22);
            background: #111827;
        }

        .dark .geo-card-value,
        .dark .geo-map-title,
        .dark .geo-section-title,
        .dark .geo-insight-value {
            color: #f8fafc;
        }

        .dark .geo-card-label,
        .dark .geo-card-foot,
        .dark .geo-map-subtitle,
        .dark .geo-location-meta,
        .dark .geo-label,
        .dark .geo-section-subtitle,
        .dark .geo-insight-title,
        .dark .geo-insight-body {
            color: #cbd5e1;
        }

        .dark .geo-input,
        .dark .geo-select,
        .dark .geo-check,
        .dark .geo-location-row,
        .dark .geo-smart-row,
        .dark .geo-btn {
            border-color: rgba(148, 163, 184, .28);
            background: #020617;
            color: #f8fafc;
        }

        .dark .geo-location-name {
            color: #e5e7eb;
        }

        .dark .geo-map-header {
            border-color: rgba(148, 163, 184, .18);
            background:
                linear-gradient(135deg, color-mix(in srgb, var(--geo-primary) 18%, #111827), #020617);
        }
    </style>

    <div class="geo-hero">
        <div class="geo-hero-grid">
            <div>
                <div class="geo-eyebrow">
                    <x-heroicon-o-map class="h-4 w-4" />
                    Africa Sales Heatmap
                </div>

                <div class="geo-title">
                    Customer Geo Intelligence
                </div>

                <div class="geo-copy">
                    Visualize customer sales movement across Africa, detect buyer hotspots, inspect animal tags sold,
                    and identify where stronger marketing, delivery, and relationship follow-up should be focused.
                </div>
            </div>

            <div class="geo-hero-mini-grid">
                <div class="geo-hero-mini">
                    <div class="geo-hero-mini-label">Map Coverage</div>
                    <div class="geo-hero-mini-value">{{ $mapCoverage }}%</div>
                </div>

                <div class="geo-hero-mini">
                    <div class="geo-hero-mini-label">Unmapped Customers</div>
                    <div class="geo-hero-mini-value">{{ number_format($unmappedCustomers) }}</div>
                </div>

                <div class="geo-hero-mini">
                    <div class="geo-hero-mini-label">Avg Animals / Customer</div>
                    <div class="geo-hero-mini-value">{{ $averageAnimalsPerCustomer }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="geo-panel">
        <div class="geo-filter-grid">
            <div>
                <label class="geo-label">From Date</label>
                <input type="date" class="geo-input" wire:model.live.debounce.500ms="fromDate">
            </div>

            <div>
                <label class="geo-label">To Date</label>
                <input type="date" class="geo-input" wire:model.live.debounce.500ms="toDate">
            </div>

            <div>
                <label class="geo-label">Country</label>
                <select class="geo-select" wire:model.live="country">
                    <option value="">All Countries</option>
                    @foreach ($countries as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="geo-label">Map Scope</label>
                <label class="geo-check">
                    <input type="checkbox" wire:model.live="africaOnly">
                    Focus on Africa
                </label>
            </div>

            <div>
                <label class="geo-label">Mapped Points</label>
                <div class="geo-check">
                    {{ number_format(count($points)) }} customer location(s)
                </div>
            </div>
        </div>
    </div>

    <div class="geo-kpis">
        <div class="geo-card">
            <div class="geo-card-icon">
                <x-heroicon-o-users class="h-5 w-5" />
            </div>
            <div class="geo-card-label">Mapped Customers</div>
            <div class="geo-card-value">{{ number_format($totalCustomers) }}</div>
            <div class="geo-card-foot">{{ number_format($mappedCustomerDatabaseCount) }} customer profiles have coordinates.</div>
        </div>

        <div class="geo-card">
            <div class="geo-card-icon">
                <x-heroicon-o-cube class="h-5 w-5" />
            </div>
            <div class="geo-card-label">Animals Bought</div>
            <div class="geo-card-value">{{ number_format($totalAnimals) }}</div>
            <div class="geo-card-foot">Average {{ $averageAnimalsPerCustomer }} animal(s) per mapped buyer.</div>
        </div>

        <div class="geo-card">
            <div class="geo-card-icon">
                <x-heroicon-o-document-text class="h-5 w-5" />
            </div>
            <div class="geo-card-label">Invoices</div>
            <div class="geo-card-value">{{ number_format($totalInvoices) }}</div>
            <div class="geo-card-foot">Filtered between {{ $fromDate }} and {{ $toDate }}.</div>
        </div>

        <div class="geo-card">
            <div class="geo-card-icon">
                <x-heroicon-o-banknotes class="h-5 w-5" />
            </div>
            <div class="geo-card-label">Revenue</div>
            <div class="geo-card-value">{{ $totalRevenueFormatted }}</div>
            <div class="geo-card-foot">Avg {{ $averageRevenuePerCustomerFormatted }} per mapped customer.</div>
        </div>
    </div>

    <div class="geo-insight-grid">
        @foreach ($insights as $insight)
            <div class="geo-insight-card">
                <div class="geo-insight-title">{{ $insight['title'] }}</div>
                <div class="geo-insight-value">{{ $insight['value'] }}</div>
                <div class="geo-insight-body">{{ $insight['body'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="geo-layout">
        <div class="geo-map-shell" id="customer-geo-map-shell">
            <div class="geo-map-header">
                <div>
                    <div class="geo-map-title">Customer Heatmap & Sale Points</div>
                    <div class="geo-map-subtitle">
                        Points show buyers. Heat intensity increases where more animals were bought from the same location.
                    </div>
                </div>

                <div class="geo-map-actions">
                    <button type="button" class="geo-btn" onclick="window.customerGeoResetMap && window.customerGeoResetMap()">
                        Reset Map
                    </button>

                    <button type="button" class="geo-btn geo-btn-primary" onclick="window.customerGeoFullscreen && window.customerGeoFullscreen()">
                        Full Screen
                    </button>
                </div>
            </div>

            <div wire:ignore>
                <div id="customer-geo-map"></div>
            </div>
        </div>

        <div class="geo-side">
            <div class="geo-panel" style="margin-top: 0;">
                <div class="geo-section-title">Top Buyer Locations</div>
                <div class="geo-section-subtitle">Ranked by animal movement from mapped customers.</div>

                @forelse ($topLocations as $location)
                    <div class="geo-location-row">
                        <div>
                            <div class="geo-location-name">{{ $location['location'] }}</div>
                            <div class="geo-location-meta">
                                {{ number_format($location['customers']) }} customer(s) ·
                                {{ number_format($location['animals']) }} animal(s) ·
                                {{ $location['revenue_formatted'] }}
                            </div>
                        </div>

                        <div class="geo-pill">
                            {{ number_format($location['animals']) }}
                        </div>
                    </div>
                @empty
                    <div class="mt-3 text-sm text-gray-500 dark:text-gray-300">
                        No mapped customer sales yet. Add customer coordinates first.
                    </div>
                @endforelse
            </div>

            <div class="geo-panel" style="margin-top: 0;">
                <div class="geo-section-title">Top Countries</div>
                <div class="geo-section-subtitle">Useful for regional demand planning and customer origin analysis.</div>

                @forelse ($topCountries as $country)
                    <div class="geo-location-row">
                        <div>
                            <div class="geo-location-name">{{ $country['country'] }}</div>
                            <div class="geo-location-meta">
                                {{ number_format($country['customers']) }} customer(s) ·
                                {{ number_format($country['animals']) }} animal(s) ·
                                {{ $country['revenue_formatted'] }}
                            </div>
                        </div>

                        <div class="geo-pill">
                            {{ number_format($country['animals']) }}
                        </div>
                    </div>
                @empty
                    <div class="mt-3 text-sm text-gray-500 dark:text-gray-300">
                        No country intelligence yet.
                    </div>
                @endforelse
            </div>

            <div class="geo-panel" style="margin-top: 0;">
                <div class="geo-section-title">Top Customers</div>
                <div class="geo-section-subtitle">Shows the buyers with the strongest animal purchase movement.</div>

                @forelse ($topCustomers as $customer)
                    <div class="geo-location-row">
                        <div>
                            <div class="geo-location-name">{{ $customer['customer'] }}</div>
                            <div class="geo-location-meta">
                                {{ $customer['location'] }} ·
                                {{ number_format($customer['animals']) }} animal(s) ·
                                {{ $customer['revenue_formatted'] }}
                            </div>
                        </div>

                        <div class="geo-pill">
                            {{ number_format($customer['invoices']) }} inv.
                        </div>
                    </div>
                @empty
                    <div class="mt-3 text-sm text-gray-500 dark:text-gray-300">
                        No top customer intelligence yet.
                    </div>
                @endforelse
            </div>

            <div class="geo-panel" style="margin-top: 0;">
                <div class="geo-section-title">Smart Map Notes</div>
                <div class="geo-section-subtitle">
                    {{ number_format($unmappedCustomers) }} customer(s) are not appearing because they do not have latitude and longitude.
                    Improve customer mapping to increase the accuracy of heatmaps and buyer origin intelligence.
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const initialPoints = @json($points);
            const primaryColor = @json($primaryColor);
            const accentColor = @json($accentColor);

            function loadCssOnce(id, href) {
                if (document.getElementById(id)) return;

                const link = document.createElement('link');
                link.id = id;
                link.rel = 'stylesheet';
                link.href = href;
                document.head.appendChild(link);
            }

            function loadScriptOnce(id, src) {
                return new Promise(function (resolve, reject) {
                    const existing = document.getElementById(id);

                    if (existing) {
                        if (existing.dataset.loaded === 'true' || window.L) {
                            resolve();
                            return;
                        }

                        existing.addEventListener('load', resolve);
                        return;
                    }

                    const script = document.createElement('script');
                    script.id = id;
                    script.src = src;
                    script.async = true;

                    script.onload = function () {
                        script.dataset.loaded = 'true';
                        resolve();
                    };

                    script.onerror = reject;

                    document.body.appendChild(script);
                });
            }

            function text(value) {
                return String(value ?? '');
            }

            function makeEl(tagName, className, content) {
                const element = document.createElement(tagName);

                if (className) {
                    element.className = className;
                }

                if (content !== undefined && content !== null) {
                    element.textContent = String(content);
                }

                return element;
            }

            function addLine(container, label, value) {
                const strong = document.createElement('strong');
                strong.textContent = label + ': ';

                container.appendChild(strong);
                container.appendChild(document.createTextNode(text(value)));
                container.appendChild(document.createElement('br'));
            }

            function makePopup(point) {
                const wrapper = makeEl('div', 'customer-popup');

                wrapper.appendChild(makeEl('div', 'customer-popup-title', point.customer));

                const meta = makeEl('div', 'customer-popup-meta');

                addLine(meta, 'Location', point.location);
                addLine(meta, 'Phone', point.phone || '-');
                addLine(meta, 'Animals', point.animals);
                addLine(meta, 'Invoices', (point.invoice_numbers || []).join(', '));
                addLine(meta, 'Revenue', point.revenue_formatted);

                wrapper.appendChild(meta);

                const tagsWrap = makeEl('div', 'customer-popup-tags');

                (point.tags || []).slice(0, 18).forEach(function (tag) {
                    tagsWrap.appendChild(makeEl('span', 'customer-popup-tag', tag));
                });

                wrapper.appendChild(tagsWrap);

                if ((point.tags || []).length > 18) {
                    wrapper.appendChild(
                        makeEl(
                            'div',
                            'customer-popup-meta',
                            '+ ' + ((point.tags || []).length - 18) + ' more tag(s)'
                        )
                    );
                }

                return wrapper;
            }

            function renderCustomerGeoMap(points) {
                if (! window.L) return;

                const mapElement = document.getElementById('customer-geo-map');

                if (! mapElement) return;

                if (! window.customerGeoMapInstance) {
                    window.customerGeoMapInstance = L.map(mapElement, {
                        zoomControl: true,
                        scrollWheelZoom: true,
                    }).setView([1.5, 20], 3);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: 'OpenStreetMap contributors',
                    }).addTo(window.customerGeoMapInstance);
                }

                const map = window.customerGeoMapInstance;

                if (window.customerGeoMarkers) {
                    window.customerGeoMarkers.remove();
                }

                if (window.customerGeoHeat) {
                    window.customerGeoHeat.remove();
                }

                window.customerGeoMarkers = L.layerGroup().addTo(map);

                const bounds = [];

                points.forEach(function (point) {
                    const lat = Number(point.lat);
                    const lng = Number(point.lng);

                    if (! Number.isFinite(lat) || ! Number.isFinite(lng)) return;

                    bounds.push([lat, lng]);

                    L.circleMarker([lat, lng], {
                        radius: Math.min(20, 7 + Number(point.animals || 1)),
                        color: primaryColor,
                        weight: 2,
                        fillColor: accentColor,
                        fillOpacity: 0.76,
                    })
                        .bindPopup(makePopup(point))
                        .addTo(window.customerGeoMarkers);
                });

                if (window.L.heatLayer) {
                    const heatPoints = points
                        .map(function (point) {
                            return [
                                Number(point.lat),
                                Number(point.lng),
                                Number(point.heat_weight || 0.45),
                            ];
                        })
                        .filter(function (point) {
                            return Number.isFinite(point[0]) && Number.isFinite(point[1]);
                        });

                    window.customerGeoHeat = L.heatLayer(heatPoints, {
                        radius: 30,
                        blur: 24,
                        maxZoom: 8,
                    }).addTo(map);
                }

                if (bounds.length > 0) {
                    map.fitBounds(bounds, {
                        padding: [40, 40],
                        maxZoom: 7,
                    });
                } else {
                    map.setView([1.5, 20], 3);
                }

                setTimeout(function () {
                    map.invalidateSize();
                }, 300);
            }

            function bootMap(points) {
                loadCssOnce('leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');

                loadScriptOnce('leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js')
                    .then(function () {
                        return loadScriptOnce('leaflet-heat-js', 'https://unpkg.com/leaflet.heat/dist/leaflet-heat.js');
                    })
                    .then(function () {
                        setTimeout(function () {
                            renderCustomerGeoMap(points || []);
                        }, 250);
                    });
            }

            window.customerGeoResetMap = function () {
                bootMap(initialPoints);
            };

            window.customerGeoFullscreen = function () {
                const shell = document.getElementById('customer-geo-map-shell');

                if (! shell) return;

                if (shell.requestFullscreen) {
                    shell.requestFullscreen();
                }

                setTimeout(function () {
                    if (window.customerGeoMapInstance) {
                        window.customerGeoMapInstance.invalidateSize();
                    }
                }, 400);
            };

            document.addEventListener('fullscreenchange', function () {
                setTimeout(function () {
                    if (window.customerGeoMapInstance) {
                        window.customerGeoMapInstance.invalidateSize();
                    }
                }, 350);
            });

            bootMap(initialPoints);

            document.addEventListener('livewire:init', function () {
                Livewire.on('customer-geo-map-updated', function (event) {
                    const payload = Array.isArray(event) ? event[0] : event;

                    setTimeout(function () {
                        bootMap(payload.points || []);
                    }, 300);
                });
            });
        })();
    </script>
</div>
