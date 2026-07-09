<div x-data="customerLocationMap({
    statePath: @js($getStatePath()),
    lat: @entangle('data.latitude'),
    lng: @entangle('data.longitude'),
    address: @entangle('data.address'),
    placeLabel: @entangle('data.place_label'),
    county: @entangle('data.county'),
    country: @entangle('data.country'),
})" x-init="init()" class="space-y-4">
    <div class="rounded-2xl border border-gray-200 bg-white p-3 shadow-sm dark:border-white/10 dark:bg-gray-900 sm:p-4">
        <label class="mb-2 flex items-center gap-2 text-sm font-semibold text-gray-950 dark:text-white">
            <x-heroicon-o-map-pin class="h-5 w-5 text-primary-600 dark:text-primary-400" />
            Search Customer Location
        </label>

        <div class="relative">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <x-heroicon-o-magnifying-glass class="h-5 w-5 text-gray-400" />
            </div>

            <input type="text" x-model="query" x-on:input.debounce.300ms="searchLocation"
                x-on:keydown.escape="suggestions = []" placeholder="Type location, e.g. Baringo, Nakuru"
                class="fi-input block w-full rounded-xl border-gray-300 bg-white py-2.5 pl-10 pr-10 text-sm text-gray-950 shadow-sm outline-none transition duration-75 placeholder:text-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white sm:text-sm">

            <button type="button" x-show="query.length" x-on:click="query = ''; suggestions = []"
                class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                <x-heroicon-o-x-mark class="h-5 w-5" />
            </button>

            <div x-show="suggestions.length" x-transition
                class="absolute z-50 mt-2 max-h-72 w-full overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-xl dark:border-white/10 dark:bg-gray-900">
                <template x-for="item in suggestions" :key="item.place_id">
                    <button type="button" x-on:click="selectSuggestion(item)"
                        class="flex w-full gap-3 border-b border-gray-100 px-3 py-3 text-left text-sm transition hover:bg-gray-50 dark:border-white/10 dark:hover:bg-white/5 sm:px-4">
                        <div
                            class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-300">
                            <x-heroicon-o-map-pin class="h-4 w-4" />
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="line-clamp-2 font-semibold text-gray-900 dark:text-white"
                                x-text="item.display_name"></div>
                            <div class="mt-1 flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                                <x-heroicon-o-globe-alt class="h-3.5 w-3.5" />
                                <span x-text="locationMeta(item) || 'Location suggestion'"></span>
                            </div>
                        </div>
                    </button>
                </template>
            </div>
        </div>
    </div>

    <div
        class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
        <div
            class="flex flex-col gap-2 border-b border-gray-200 px-3 py-3 dark:border-white/10 sm:flex-row sm:items-center sm:justify-between sm:px-4">
            <div class="flex items-center gap-2 text-sm font-semibold text-gray-950 dark:text-white">
                <x-heroicon-o-map class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                Map Picker
            </div>

            <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                <x-heroicon-o-cursor-arrow-rays class="h-4 w-4" />
                Click map or drag pin
            </div>
        </div>

        <div x-ref="map" class="h-[300px] w-full sm:h-[360px] md:h-[420px]"></div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        <div
            class="rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-900 dark:border-green-900 dark:bg-green-950 dark:text-green-100">
            <div class="mb-1 flex items-center gap-2 font-semibold">
                <x-heroicon-o-check-circle class="h-5 w-5" />
                Selected Location
            </div>

            <div class="break-words text-xs leading-5 sm:text-sm" x-text="placeLabel || 'No location selected yet.'">
            </div>
        </div>

        <div
            class="rounded-xl border border-gray-200 bg-gray-50 p-3 text-sm text-gray-900 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">
            <div class="mb-1 flex items-center gap-2 font-semibold">
                <x-heroicon-o-map-pin class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                Coordinates
            </div>

            <template x-if="lat && lng">
                <div class="space-y-1 text-xs sm:text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-gray-500 dark:text-gray-400">Latitude</span>
                        <strong class="break-all" x-text="lat"></strong>
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <span class="text-gray-500 dark:text-gray-400">Longitude</span>
                        <strong class="break-all" x-text="lng"></strong>
                    </div>
                </div>
            </template>

            <template x-if="!lat || !lng">
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    No coordinates selected.
                </div>
            </template>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('customerLocationMap', (config) => ({
            query: '',
            suggestions: [],
            map: null,
            marker: null,

            searchCache: {},
            abortController: null,
            lastQuery: '',

            lat: config.lat,
            lng: config.lng,
            address: config.address,
            placeLabel: config.placeLabel,
            county: config.county,
            country: config.country,

            init() {
                if (typeof L === 'undefined') {
                    console.error('Leaflet is not loaded. Check AdminPanelProvider renderHook.');
                    return;
                }

                const defaultLat = this.lat || -0.3744766;
                const defaultLng = this.lng || 35.9442615;

                this.map = L.map(this.$refs.map, {
                    scrollWheelZoom: false,
                }).setView([defaultLat, defaultLng], 12);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors',
                }).addTo(this.map);

                this.marker = L.marker([defaultLat, defaultLng], {
                    draggable: true,
                }).addTo(this.map);

                this.map.on('click', (e) => {
                    this.setCoordinates(e.latlng.lat, e.latlng.lng);
                    this.reverseLookup(e.latlng.lat, e.latlng.lng);
                });

                this.marker.on('dragend', () => {
                    const pos = this.marker.getLatLng();
                    this.setCoordinates(pos.lat, pos.lng);
                    this.reverseLookup(pos.lat, pos.lng);
                });

                setTimeout(() => this.map.invalidateSize(), 500);
                setTimeout(() => this.map.invalidateSize(), 1200);

                window.addEventListener('resize', () => {
                    if (this.map) {
                        setTimeout(() => this.map.invalidateSize(), 250);
                    }
                });
            },

            async searchLocation() {
                const q = this.query.trim();

                if (q.length < 2) {
                    this.suggestions = [];
                    return;
                }

                if (q === this.lastQuery) {
                    return;
                }

                this.lastQuery = q;

                if (this.searchCache[q]) {
                    this.suggestions = this.searchCache[q];
                    return;
                }

                if (this.abortController) {
                    this.abortController.abort();
                }

                this.abortController = new AbortController();

                const url =
                    `https://nominatim.openstreetmap.org/search?format=jsonv2&addressdetails=1&limit=8&countrycodes=ke&q=${encodeURIComponent(q)}`;

                try {
                    const response = await fetch(url, {
                        signal: this.abortController.signal,
                        headers: {
                            Accept: 'application/json',
                        },
                    });

                    if (!response.ok) {
                        return;
                    }

                    const data = await response.json();

                    this.searchCache[q] = data;
                    this.suggestions = data;
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        console.error(error);
                    }
                }
            },

            selectSuggestion(item) {
                const lat = parseFloat(item.lat);
                const lng = parseFloat(item.lon);

                this.setCoordinates(lat, lng);

                this.placeLabel = item.display_name || '';
                this.address = item.display_name || '';

                const addr = item.address || {};

                this.county = addr.county || addr.state_district || addr.region || addr.state || '';
                this.country = addr.country || '';

                this.query = item.display_name || '';
                this.suggestions = [];

                if (this.marker) {
                    this.marker.bindPopup(this.placeLabel).openPopup();
                }

                if (this.map) {
                    this.map.setView([lat, lng], 15);
                    setTimeout(() => this.map.invalidateSize(), 250);
                }
            },

            async reverseLookup(lat, lng) {
                const url =
                    `https://nominatim.openstreetmap.org/reverse?format=jsonv2&addressdetails=1&lat=${lat}&lon=${lng}`;

                try {
                    const response = await fetch(url, {
                        headers: {
                            Accept: 'application/json',
                        },
                    });

                    if (!response.ok) {
                        return;
                    }

                    const item = await response.json();
                    const addr = item.address || {};

                    this.placeLabel = item.display_name || `${lat}, ${lng}`;
                    this.address = item.display_name || '';
                    this.county = addr.county || addr.state_district || addr.region || addr
                        .state || '';
                    this.country = addr.country || '';

                    if (this.marker) {
                        this.marker.bindPopup(this.placeLabel).openPopup();
                    }
                } catch (error) {
                    console.error(error);
                }
            },

            setCoordinates(lat, lng) {
                this.lat = Number(lat).toFixed(7);
                this.lng = Number(lng).toFixed(7);

                if (this.marker) {
                    this.marker.setLatLng([this.lat, this.lng]);
                }
            },

            locationMeta(item) {
                const addr = item.address || {};

                return [
                    addr.county || addr.state_district || addr.region || addr.state || '',
                    addr.country || '',
                ].filter(Boolean).join(' • ');
            },
        }));
    });
</script>
