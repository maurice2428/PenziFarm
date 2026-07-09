@php
    $initial = $field->getState();

    if (! is_array($initial)) {
        $initial = [];
    }

    $isLocationResourcePage = request()->routeIs(
        'filament.admin.animals.resources.locations.*'
    );
@endphp

@once
<style>
    /*
     * Normal Locations create/edit page map.
     */
    .location-map-canvas {
        display: block !important;
        width: 100% !important;
        height: 330px !important;
        min-height: 330px !important;
        max-height: 330px !important;
        position: relative !important;
        isolation: isolate !important;
        overflow: hidden !important;
        z-index: 0 !important;
    }

    /*
     * Desktop quick-create drawer: compact map.
     */
    .lelekwe-location-quick-create .location-map-canvas {
        height: 185px !important;
        min-height: 185px !important;
        max-height: 185px !important;
    }

    .location-map-canvas .leaflet-pane {
        z-index: 1 !important;
    }

    .location-map-canvas .leaflet-top,
    .location-map-canvas .leaflet-bottom {
        z-index: 2 !important;
    }

    /*
     * Phone layout:
     * Full-width drawer, still entering from the right.
     * The drawer itself becomes vertically scrollable.
     */
    @media (max-width: 639px) {
        .fi-modal-slide-over-window.lelekwe-location-quick-create,
        .fi-modal-window.lelekwe-location-quick-create {
            width: 100vw !important;
            max-width: 100vw !important;
            height: 100dvh !important;
            max-height: 100dvh !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            overscroll-behavior-y: contain !important;
            -webkit-overflow-scrolling: touch !important;
        }

        .fi-modal-slide-over-window.lelekwe-location-quick-create .fi-modal-content,
        .fi-modal-window.lelekwe-location-quick-create .fi-modal-content {
            min-height: min-content !important;
            overflow: visible !important;
            padding-bottom: calc(1rem + env(safe-area-inset-bottom)) !important;
        }

        /*
         * Keep Wizard navigation visible on every step.
         */
        .lelekwe-location-quick-create .fi-fo-wizard-footer {
            position: sticky !important;
            bottom: 0 !important;
            z-index: 40 !important;
            display: flex !important;
            gap: 0.5rem !important;
            padding: 0.75rem 0 !important;
            background: rgb(255 255 255) !important;
            border-top: 1px solid rgb(229 231 235) !important;
        }

        .dark .lelekwe-location-quick-create .fi-fo-wizard-footer {
            background: rgb(17 24 39) !important;
            border-top-color: rgba(255, 255, 255, .12) !important;
        }

        .lelekwe-location-quick-create .fi-fo-wizard-footer .fi-btn {
            flex: 1 1 0 !important;
            justify-content: center !important;
            min-height: 44px !important;
        }

        .lelekwe-location-quick-create .location-map-canvas {
            height: 155px !important;
            min-height: 155px !important;
            max-height: 155px !important;
        }

        .lelekwe-location-quick-create .fi-fo-wizard-header {
            overflow-x: auto !important;
            scrollbar-width: none !important;
        }

        .lelekwe-location-quick-create .fi-fo-wizard-header::-webkit-scrollbar {
            display: none !important;
        }

        .lelekwe-location-quick-create .fi-fo-wizard-header-step-description {
            display: none !important;
        }

        .lelekwe-location-quick-create .fi-fo-wizard-header-step-label {
            font-size: 0.72rem !important;
            white-space: nowrap !important;
        }
    }
</style>
@endonce


<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="locationMapPicker({
        state: $wire.entangle('{{ $getStatePath() }}').live,
        initial: @js($initial),
    })" x-init="boot()" data-location-map-context="{{ $isLocationResourcePage ? 'resource' : 'quick-create' }}"
        @class([
            'location-map-picker',
            'location-map-picker--resource-page' => $isLocationResourcePage,
            'rounded-2xl border border-gray-200 bg-white p-3 shadow-sm dark:border-white/10 dark:bg-gray-900',
        ])>
        <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-gray-900 dark:text-white">Location Map Selector</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Click a point or drag the marker. Coordinates and
                    address details are captured automatically.</p>
            </div>
            <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
                <button type="button" x-on:click="useCurrentPosition()"
                    class="inline-flex w-full items-center justify-center gap-1 sm:w-auto rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 shadow-sm dark:border-white/10 dark:bg-gray-800 dark:text-gray-200">
                    <x-filament::icon icon="heroicon-m-map-pin" class="h-4 w-4" />
                    Use My Position
                </button>
                <button type="button" x-on:click="reverseCurrentPoint()"
                    class="inline-flex w-full items-center justify-center gap-1 sm:w-auto rounded-lg bg-primary-600 px-3 py-2 text-xs font-semibold text-white shadow-sm">
                    <x-filament::icon icon="heroicon-m-arrow-path" class="h-4 w-4" />
                    Refresh Details
                </button>
            </div>
        </div>
<div
            x-ref="map"
            wire:ignore
            class="location-map-canvas rounded-xl border border-gray-200 dark:border-white/10"
        ></div>

        <div class="mt-3 grid gap-2 sm:grid-cols-2">
            <div class="rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                <span class="font-semibold">Coordinates:</span> <span x-text="coordinateLabel"></span>
            </div>
            <div class="rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                <span class="font-semibold">Map status:</span> <span x-text="statusText"></span>
            </div>
        </div>

        <p x-show="error" x-text="error" class="mt-3 text-xs font-medium text-danger-600 dark:text-danger-400"></p>
    </div>
</x-dynamic-component>

@script
    <script>
        window.locationMapPicker = window.locationMapPicker || function(config) {
            return {
                state: config.state,
                initial: config.initial || {},
                map: null,
                marker: null,
                resizeObserver: null,
                error: '',
                statusText: 'Ready',
                coordinateLabel: 'Select a point on the map',

                boot() {
                    this.$nextTick(() => {
                        this.prepareQuickCreateScroll();
                        this.installResizeObserver();
                        this.waitForVisibleContainer();

                        setTimeout(() => this.prepareQuickCreateScroll(), 150);
                        setTimeout(() => this.prepareQuickCreateScroll(), 500);
                    });
                },

                prepareQuickCreateScroll() {
                    const drawer = this.$root.closest('.lelekwe-location-quick-create');

                    if (! drawer) {
                        return;
                    }

                    drawer.style.setProperty('overflow-y', 'auto', 'important');
                    drawer.style.setProperty('overflow-x', 'hidden', 'important');
                    drawer.style.setProperty('-webkit-overflow-scrolling', 'touch', 'important');
                    drawer.style.setProperty('overscroll-behavior-y', 'contain', 'important');
                },

                waitForVisibleContainer(attempt = 0) {
                    this.normaliseState();

                    if (!window.L) {
                        this.error = 'Map library is unavailable. Confirm Leaflet is enabled in AdminPanelProvider.';
                        this.statusText = 'Map library unavailable';
                        return;
                    }

                    const mapElement = this.$refs.map;

                    if (!mapElement) {
                        return;
                    }

                    const isVisible = mapElement.offsetParent !== null &&
                        mapElement.clientWidth > 40 &&
                        mapElement.clientHeight > 40;

                    if (!isVisible) {
                        if (attempt < 25) {
                            setTimeout(() => this.waitForVisibleContainer(attempt + 1), 120);
                        }

                        return;
                    }

                    this.createMap();
                },

                createMap() {
                    if (this.map) {
                        this.invalidateMap();
                        return;
                    }

                    const lat = this.numberOr(this.state.latitude, -0.023559);
                    const lng = this.numberOr(this.state.longitude, 37.906193);

                    this.map = window.L.map(this.$refs.map, {
                        scrollWheelZoom: false,
                    }).setView([lat, lng], this.hasCoordinates() ? 15 : 6);

                    window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap contributors',
                    }).addTo(this.map);

                    this.marker = window.L.marker([lat, lng], {
                        draggable: true,
                    }).addTo(this.map);

                    this.marker.on('dragend', (event) => {
                        const point = event.target.getLatLng();
                        this.setPoint(point.lat, point.lng, true);
                    });

                    this.map.on('click', (event) => {
                        this.setPoint(event.latlng.lat, event.latlng.lng, true);
                    });

                    this.updateCoordinateLabel();
                    this.statusText = this.hasCoordinates() ?
                        'Map ready' :
                        'Select a point on the map';

                    this.invalidateMap();

                    setTimeout(() => this.invalidateMap(), 250);
                    setTimeout(() => this.invalidateMap(), 700);
                    setTimeout(() => this.invalidateMap(), 1200);
                },

                invalidateMap() {
                    if (!this.map) {
                        return;
                    }

                    this.map.invalidateSize({
                        pan: false,
                    });
                },

                installResizeObserver() {
                    if (!window.ResizeObserver || !this.$refs.map || this.resizeObserver) {
                        return;
                    }

                    this.resizeObserver = new ResizeObserver(() => {
                        this.invalidateMap();
                    });

                    this.resizeObserver.observe(this.$refs.map);
                },

                normaliseState() {
                    const current = (this.state && typeof this.state === 'object') ? this.state : {};
                    this.state = {
                        latitude: current.latitude ?? this.initial.latitude ?? null,
                        longitude: current.longitude ?? this.initial.longitude ?? null,
                        county: current.county ?? this.initial.county ?? null,
                        sub_county: current.sub_county ?? this.initial.sub_county ?? null,
                        ward: current.ward ?? this.initial.ward ?? null,
                        address: current.address ?? this.initial.address ?? null,
                        place_label: current.place_label ?? this.initial.place_label ?? null,
                    };
                },

                numberOr(value, fallback) {
                    if (value === null || value === undefined || value === '') {
                        return fallback;
                    }

                    const number = Number(value);

                    return Number.isFinite(number) ? number : fallback;
                },

                hasCoordinates() {
                    return this.state.latitude !== null && this.state.latitude !== '' &&
                        this.state.longitude !== null && this.state.longitude !== '';
                },

                updateCoordinateLabel() {
                    this.coordinateLabel = this.hasCoordinates() ?
                        `${Number(this.state.latitude).toFixed(7)}, ${Number(this.state.longitude).toFixed(7)}` :
                        'Select a point on the map';
                },

                setPoint(latitude, longitude, reverseGeocode = true) {
                    const lat = Number(Number(latitude).toFixed(7));
                    const lng = Number(Number(longitude).toFixed(7));

                    this.state = {
                        ...this.state,
                        latitude: lat,
                        longitude: lng
                    };

                    if (this.marker) this.marker.setLatLng([lat, lng]);
                    if (this.map) this.map.panTo([lat, lng]);

                    this.updateCoordinateLabel();
                    this.statusText = 'Map pin updated';

                    if (reverseGeocode) this.reverseGeocode(lat, lng);
                },

                reverseCurrentPoint() {
                    if (!this.hasCoordinates()) {
                        this.error = 'Select a map point first.';
                        return;
                    }

                    this.reverseGeocode(this.state.latitude, this.state.longitude);
                },

                useCurrentPosition() {
                    this.error = '';

                    if (!navigator.geolocation) {
                        this.error = 'This browser does not support location services.';
                        return;
                    }

                    this.statusText = 'Finding your current position…';

                    navigator.geolocation.getCurrentPosition(
                        (position) => this.setPoint(position.coords.latitude, position.coords.longitude, true),
                        () => {
                            this.error = 'Location could not be retrieved. Confirm browser location permission.';
                            this.statusText = 'Location permission unavailable';
                        }, {
                            enableHighAccuracy: true,
                            timeout: 15000,
                            maximumAge: 0
                        }
                    );
                },

                async reverseGeocode(latitude, longitude) {
                    this.error = '';
                    this.statusText = 'Resolving address details…';

                    try {
                        const url = new URL('https://nominatim.openstreetmap.org/reverse');
                        url.searchParams.set('format', 'jsonv2');
                        url.searchParams.set('lat', latitude);
                        url.searchParams.set('lon', longitude);
                        url.searchParams.set('zoom', '18');
                        url.searchParams.set('addressdetails', '1');

                        const response = await fetch(url.toString(), {
                            headers: {
                                'Accept': 'application/json',
                                'Accept-Language': 'en'
                            },
                        });

                        if (!response.ok) throw new Error('Address lookup failed.');

                        const result = await response.json();
                        const address = result.address || {};

                        const county = address.county || address.state_district || address.region || address
                            .state || null;
                        const subCounty = address.city_district || address.municipality || address.suburb || address
                            .town || address.city || null;
                        const ward = address.neighbourhood || address.village || address.hamlet || null;
                        const formattedAddress = [address.road, address.neighbourhood || address.village || address
                                .hamlet, subCounty, county
                            ]
                            .filter(Boolean)
                            .join(', ');

                        this.state = {
                            ...this.state,
                            county,
                            sub_county: subCounty,
                            ward,
                            address: this.state.address || formattedAddress || null,
                            place_label: result.display_name || null,
                        };

                        this.statusText = county ? `Location resolved: ${county}` : 'Coordinates saved';
                    } catch (error) {
                        this.statusText = 'Coordinates saved';
                        this.error =
                            'Coordinates were saved, but the address details could not be fetched. You may complete them later.';
                    }
                },
            };
        };
    </script>
@endscript
