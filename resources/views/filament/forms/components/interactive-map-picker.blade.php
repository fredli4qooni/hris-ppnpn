@php
    $finalLat = (float) ($lat ?? ($get('latitude') ?? ($record?->latitude ?? -5.3971400)));
    $finalLon = (float) ($lon ?? ($get('longitude') ?? ($record?->longitude ?? 105.2667900)));
    $finalRadius = (int) ($radius ?? ($get('radius_meters') ?? ($record?->radius_meters ?? 100)));
@endphp

<style>
    .custom-djpb-pin {
        background: transparent !important;
        border: none !important;
    }
</style>

<div 
    x-data="{
        lat: {{ $finalLat }},
        lon: {{ $finalLon }},
        radius: {{ $finalRadius }},
        searchQuery: '',
        isSearching: false,
        searchError: null,
        isSyncing: false,
        mapInstance: null,
        markerInstance: null,
        circleInstance: null,

        initMap() {
            if (typeof L === 'undefined') {
                setTimeout(() => this.initMap(), 150);
                return;
            }
            this.setupLeaflet();
        },

        createPinIcon() {
            return L.divIcon({
                className: 'custom-djpb-pin',
                html: `
                    <div style='width: 32px; height: 42px; position: relative;'>
                        <svg width='32' height='42' viewBox='0 0 32 42' fill='none' xmlns='http://www.w3.org/2000/svg' style='filter: drop-shadow(0 2px 4px rgba(0,0,0,0.35));'>
                            <path d='M16 0C7.16344 0 0 7.16344 0 16C0 27.5 16 42 16 42C16 42 32 27.5 32 16C32 7.16344 24.8366 0 16 0Z' fill='#0058A8'/>
                            <circle cx='16' cy='16' r='6.5' fill='#F8B810'/>
                            <circle cx='16' cy='16' r='3' fill='#FFFFFF'/>
                        </svg>
                    </div>
                `,
                iconSize: [32, 42],
                iconAnchor: [16, 42],
                popupAnchor: [0, -42]
            });
        },

        setupLeaflet() {
            const mapContainer = this.$refs.mapElement;
            if (!mapContainer || this.mapInstance) return;

            // Recalibrate size when cursor enters map area
            mapContainer.addEventListener('mouseenter', () => {
                if (this.mapInstance) {
                    this.mapInstance.invalidateSize();
                }
            });

            // Initialize map instance
            this.mapInstance = L.map(mapContainer, {
                zoomControl: true
            }).setView([this.lat, this.lon], 16);

            // OpenStreetMap Tile Layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(this.mapInstance);

            // Add Draggable Pin with Precision Needle Tip
            this.markerInstance = L.marker([this.lat, this.lon], {
                icon: this.createPinIcon(),
                draggable: true,
                title: 'Geser pin untuk menentukan titik koordinat'
            }).addTo(this.mapInstance);

            // Add Geofence Radius Circle (interactive: false so clicks pass through to map)
            this.circleInstance = L.circle([this.lat, this.lon], {
                radius: this.radius,
                color: '#0058A8',
                fillColor: '#7FB0DB',
                fillOpacity: 0.35,
                weight: 2,
                interactive: false
            }).addTo(this.mapInstance);

            // Drag event on marker: update coordinates without jarring map pan
            this.markerInstance.on('dragend', (e) => {
                const pos = e.target.getLatLng();
                this.updateCoordinates(pos.lat, pos.lng, false);
            });

            // Click event on map: drop pin precisely where clicked without panning map
            this.mapInstance.on('click', (e) => {
                this.updateCoordinates(e.latlng.lat, e.latlng.lng, false);
            });

            // Ensure tile rendering on load
            setTimeout(() => {
                if (this.mapInstance) {
                    this.mapInstance.invalidateSize();
                }
            }, 250);

            // Watch for changes from Filament form inputs
            this.bindFormInputs();
        },

        updateCoordinates(newLat, newLon, shouldPan = false) {
            if (this.isSyncing) return;
            this.isSyncing = true;

            this.lat = parseFloat(newLat);
            this.lon = parseFloat(newLon);

            if (this.markerInstance) {
                this.markerInstance.setLatLng([this.lat, this.lon]);
            }
            if (this.circleInstance) {
                this.circleInstance.setLatLng([this.lat, this.lon]);
            }
            if (shouldPan && this.mapInstance) {
                this.mapInstance.panTo([this.lat, this.lon]);
            }

            // Sync with Filament form inputs: update BOTH values before dispatching events
            const latInputs = document.querySelectorAll('input[id*=\'latitude\']');
            const lonInputs = document.querySelectorAll('input[id*=\'longitude\']');

            latInputs.forEach(input => {
                input.value = this.lat.toFixed(7);
            });
            lonInputs.forEach(input => {
                input.value = this.lon.toFixed(7);
            });

            // Now dispatch events so Livewire receives the synchronized values
            latInputs.forEach(input => {
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
            lonInputs.forEach(input => {
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });

            setTimeout(() => {
                this.isSyncing = false;
            }, 80);
        },

        updateRadius(newRadius) {
            this.radius = parseInt(newRadius) || 100;
            if (this.circleInstance) {
                this.circleInstance.setRadius(this.radius);
            }
        },

        bindFormInputs() {
            // Listen to radius input changes
            const radiusInputs = document.querySelectorAll('input[id*=\'radius_meters\']');
            radiusInputs.forEach(input => {
                input.addEventListener('input', (e) => this.updateRadius(e.target.value));
            });

            // Listen to manual latitude/longitude input edits by user
            const latInputs = document.querySelectorAll('input[id*=\'latitude\']');
            const lonInputs = document.querySelectorAll('input[id*=\'longitude\']');

            const handleManualCoordChange = () => {
                if (this.isSyncing) return; // Prevent race conditions from programmatic dispatches
                const latVal = latInputs[0]?.value;
                const lonVal = lonInputs[0]?.value;
                if (latVal && lonVal) {
                    const parsedLat = parseFloat(latVal);
                    const parsedLon = parseFloat(lonVal);
                    if (!isNaN(parsedLat) && !isNaN(parsedLon)) {
                        this.lat = parsedLat;
                        this.lon = parsedLon;
                        if (this.markerInstance) this.markerInstance.setLatLng([this.lat, this.lon]);
                        if (this.circleInstance) this.circleInstance.setLatLng([this.lat, this.lon]);
                        if (this.mapInstance) this.mapInstance.panTo([this.lat, this.lon]);
                    }
                }
            };

            latInputs.forEach(input => input.addEventListener('change', handleManualCoordChange));
            lonInputs.forEach(input => input.addEventListener('change', handleManualCoordChange));
        },

        searchLocation() {
            const query = this.searchQuery.trim();
            if (!query) return;

            this.isSearching = true;
            this.searchError = null;

            // Geocode using OpenStreetMap Nominatim
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1`)
                .then(res => res.json())
                .then(results => {
                    this.isSearching = false;
                    if (results && results.length > 0) {
                        const first = results[0];
                        const foundLat = parseFloat(first.lat);
                        const foundLon = parseFloat(first.lon);

                        this.updateCoordinates(foundLat, foundLon, true);
                        if (this.mapInstance) {
                            this.mapInstance.setView([foundLat, foundLon], 17);
                        }

                        // Auto-fill address if empty
                        const addressInputs = document.querySelectorAll('textarea[id*=\'address\']');
                        addressInputs.forEach(textarea => {
                            if (!textarea.value.trim()) {
                                textarea.value = first.display_name;
                                textarea.dispatchEvent(new Event('input', { bubbles: true }));
                                textarea.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                        });
                    } else {
                        this.searchError = 'Lokasi tidak ditemukan. Coba gunakan kata kunci nama kantor atau jalan yang lebih spesifik.';
                    }
                })
                .catch(err => {
                    this.isSearching = false;
                    this.searchError = 'Gagal menghubungi layanan pencarian peta. Periksa koneksi internet Anda.';
                });
        },

        useCurrentLocation() {
            if (!navigator.geolocation) {
                alert('Peramban Anda tidak mendukung sensor GPS Geolocation.');
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    this.updateCoordinates(pos.coords.latitude, pos.coords.longitude, true);
                    if (this.mapInstance) {
                        this.mapInstance.setView([pos.coords.latitude, pos.coords.longitude], 17);
                    }
                },
                (err) => {
                    alert('Gagal mengambil lokasi GPS perangkat: ' + err.message);
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        }
    }"
    x-init="initMap()"
    style="display: flex; flex-direction: column; gap: 10px;"
>
    <!-- Search & Control Box -->
    <div style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 12px; display: flex; flex-direction: column; gap: 10px;">
        <!-- Top Row: Search Input + Search Button -->
        <div style="display: flex; gap: 8px; width: 100%;">
            <input 
                type="text" 
                x-model="searchQuery" 
                @keydown.enter.prevent="searchLocation()"
                placeholder="Ketik nama kantor / alamat di peta (contoh: Kanwil DJPb Lampung)..."
                style="flex: 1; height: 38px; padding: 0 12px; font-size: 13px; border-radius: 6px; border: 1px solid #CBD5E1; background: #FFFFFF; color: #1E293B; outline: none;"
            />
            <button 
                type="button" 
                @click="searchLocation()" 
                :disabled="isSearching"
                style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; height: 38px; padding: 0 16px; background-color: #0058A8; color: #FFFFFF; font-size: 12px; font-weight: 600; border-radius: 6px; border: none; cursor: pointer; white-space: nowrap; box-shadow: 0 1px 2px rgba(0,0,0,0.05);"
            >
                <svg style="width: 14px; height: 14px; min-width: 14px; min-height: 14px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <span x-text="isSearching ? 'Mencari...' : 'Cari di Peta'"></span>
            </button>
        </div>

        <!-- Error Alert -->
        <div x-show="searchError" style="background-color: #FDECEA; border: 1px solid #C62828; color: #C62828; padding: 8px 12px; border-radius: 6px; font-size: 12px; display: flex; align-items: center; justify-content: space-between;">
            <span x-text="searchError"></span>
            <button type="button" @click="searchError = null" style="font-weight: 700; background: none; border: none; cursor: pointer; color: #C62828;">&times;</button>
        </div>

        <!-- Bottom Row: GPS Button + Google Maps Link + Coordinates Display -->
        <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 8px; border-top: 1px solid #E2E8F0; padding-top: 8px;">
            <div style="display: flex; gap: 8px;">
                <!-- GPS Button -->
                <button 
                    type="button" 
                    @click="useCurrentLocation()" 
                    style="display: inline-flex; align-items: center; gap: 6px; height: 32px; padding: 0 12px; background: #FFFFFF; border: 1px solid #CBD5E1; color: #334155; font-size: 11px; font-weight: 600; border-radius: 6px; cursor: pointer;"
                    title="Gunakan titik koordinat GPS perangkat saat ini"
                >
                    <svg style="width: 14px; height: 14px; min-width: 14px; min-height: 14px; color: #007A3D; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <span>GPS Saya</span>
                </button>

                <!-- Google Maps Link -->
                <a 
                    :href="'https://www.google.com/maps?q=' + lat + ',' + lon" 
                    target="_blank" 
                    rel="noopener"
                    style="display: inline-flex; align-items: center; gap: 6px; height: 32px; padding: 0 12px; background: #FFFFFF; border: 1px solid #BFDBFE; color: #0058A8; font-size: 11px; font-weight: 600; border-radius: 6px; text-decoration: none;"
                    title="Buka titik koordinat saat ini di Google Maps"
                >
                    <span>Buka Google Maps</span>
                    <svg style="width: 12px; height: 12px; min-width: 12px; min-height: 12px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                    </svg>
                </a>
            </div>

            <!-- Coordinate & Radius Badge -->
            <div style="font-size: 11px; color: #475569; display: flex; align-items: center; gap: 12px;">
                <span><strong>Titik:</strong> <span style="font-family: monospace; color: #0F172A;" x-text="lat.toFixed(7) + ', ' + lon.toFixed(7)"></span></span>
                <span><strong>Radius:</strong> <span style="font-weight: 700; color: #0058A8;" x-text="radius + ' m'"></span></span>
            </div>
        </div>
    </div>

    <!-- Leaflet Map Element -->
    <div style="position: relative; width: 100%; height: 400px; border-radius: 10px; overflow: hidden; border: 1px solid #CBD5E1; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div x-ref="mapElement" style="width: 100%; height: 100%; z-index: 10;"></div>
        <div style="position: absolute; bottom: 8px; left: 8px; z-index: 20; background: rgba(255,255,255,0.92); backdrop-filter: blur(4px); padding: 4px 10px; border-radius: 6px; font-size: 11px; color: #334155; border: 1px solid #CBD5E1; pointer-events: none;">
            💡 <strong>Panduan:</strong> Geser pin atau klik pada peta untuk menentukan titik perimeter kantor.
        </div>
    </div>
</div>
