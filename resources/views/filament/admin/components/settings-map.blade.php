<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<div
    style="margin-top: 12px; border: 2px solid #16a34a; border-radius: 12px; padding: 12px; background: #fff;"
    wire:ignore
>
    <div style="font-weight: 700; margin-bottom: 10px; color: #16a34a;">
        Map Picker
    </div>

    <div id="farm-settings-map" style="height: 380px; border-radius: 10px;"></div>

    <div style="margin-top: 8px; font-size: 12px; color: #6b7280;">
        Click on the map or drag the marker to set farm coordinates.
    </div>
</div>

<script>
    let farmMap;
    let farmMarker;

    function bootFarmMap() {
        const defaultLat = @js($this->data['latitude'] ?? -0.0236);
        const defaultLng = @js($this->data['longitude'] ?? 37.9062);

        if (farmMap) {
            farmMap.remove();
        }

        farmMap = L.map('farm-settings-map').setView([defaultLat, defaultLng], 6);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(farmMap);

        farmMarker = L.marker([defaultLat, defaultLng], {
            draggable: true
        }).addTo(farmMap);

        farmMarker.on('dragend', function () {
            const pos = farmMarker.getLatLng();
            updateFarmLocation(pos.lat, pos.lng);
        });

        farmMap.on('click', function (e) {
            farmMarker.setLatLng(e.latlng);
            updateFarmLocation(e.latlng.lat, e.latlng.lng);
        });

        setTimeout(() => {
            farmMap.invalidateSize();
        }, 300);
    }

    function updateFarmLocation(lat, lng) {
        Livewire.dispatch('setLocation', {
            lat: String(lat),
            lng: String(lng)
        });
    }

    window.addEventListener('getLocationFromBrowser', () => {
        if (!navigator.geolocation) {
            alert('Geolocation is not supported by this browser.');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            function (position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;

                if (farmMarker && farmMap) {
                    farmMarker.setLatLng([lat, lng]);
                    farmMap.setView([lat, lng], 13);
                }

                updateFarmLocation(lat, lng);
            },
            function (error) {
                alert('Unable to get your location. Please allow location access.');
                console.error(error);
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    });

    document.addEventListener('livewire:init', () => {
        setTimeout(() => {
            bootFarmMap();
        }, 300);
    });
</script>
