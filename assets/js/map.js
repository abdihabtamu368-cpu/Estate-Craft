/**
 * Leaflet Interactive Map Module - Ethiopia Satellite & Geo-Search
 * Configured specifically for Ethiopia with High-Resolution Satellite & Street Imagery
 */

// Ethiopia Coordinates Constants
const ETHIOPIA_CENTER = [9.010793, 38.761252]; // Addis Ababa

function getEthiopiaBounds() {
    if (typeof L !== 'undefined' && L.latLngBounds) {
        return L.latLngBounds([3.2, 32.8], [15.1, 48.2]);
    }
    return null;
}

let catalogMapInstance = null;
let catalogMarkersLayer = null;
let catalogSearchMarker = null;

/**
 * Creates satellite and street tile layers with robust fallbacks
 */
function createMapLayers() {
    // Standard OpenStreetMap (Fast, Global, Always Available)
    const streetLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    });

    // Esri World Imagery (High-Resolution Satellite)
    const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 19,
        attribution: 'Tiles &copy; Esri &mdash; Earthstar Geographics'
    });

    // Road & Place Labels Overlay for Satellite
    const labelsOverlay = L.tileLayer('https://services.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 19,
        attribution: ''
    });

    const hybridLayer = L.layerGroup([satelliteLayer, labelsOverlay]);

    return {
        streets: streetLayer,
        satellite: hybridLayer
    };
}

/**
 * Initializes the full catalog map with interactive property markers (Ethiopia)
 */
function initCatalogMap(containerId, apiUrl, queryParams = {}) {
    const mapEl = document.getElementById(containerId);
    if (!mapEl) return;

    // Guard against Leaflet not being loaded yet (retries gracefully)
    if (typeof L === 'undefined') {
        setTimeout(function() {
            initCatalogMap(containerId, apiUrl, queryParams);
        }, 150);
        return;
    }

    window.catalogApiUrl = apiUrl;
    window.catalogQueryParams = queryParams;

    // Reset if previously bound on a refreshed container
    if (mapEl._leaflet_id && catalogMapInstance) {
        try {
            catalogMapInstance.remove();
        } catch (e) {}
        catalogMapInstance = null;
    }

    if (!catalogMapInstance) {
        const layers = createMapLayers();
        const bounds = getEthiopiaBounds();

        const mapOptions = {
            center: ETHIOPIA_CENTER,
            zoom: 12,
            minZoom: 5,
            maxZoom: 19,
            layers: [layers.streets] // Default to fast Street map, Satellite toggleable
        };

        if (bounds) {
            mapOptions.maxBounds = bounds;
            mapOptions.maxBoundsViscosity = 0.6;
        }

        catalogMapInstance = L.map(containerId, mapOptions);

        // Layer Switcher Control (Street vs Satellite)
        const baseMaps = {
            "🗺️ Street Map": layers.streets,
            "🛰️ Satellite (Ethiopia)": layers.satellite
        };
        L.control.layers(baseMaps, null, { position: 'topright' }).addTo(catalogMapInstance);

        catalogMarkersLayer = L.layerGroup().addTo(catalogMapInstance);
        window.homeMap = catalogMapInstance;

        // Force container reflow to prevent gray/unrendered tiles
        setTimeout(function() {
            if (catalogMapInstance) {
                catalogMapInstance.invalidateSize();
            }
        }, 300);
    }

    loadPropertiesOnMap(apiUrl, queryParams);
}

/**
 * Fetches JSON properties and renders markers on the catalog map
 */
function loadPropertiesOnMap(apiUrl, queryParams = {}, autoOpenFirst = false) {
    if (!catalogMapInstance || !catalogMarkersLayer) return;

    catalogMarkersLayer.clearLayers();

    const queryString = new URLSearchParams(queryParams).toString();
    const fetchUrl = apiUrl + (queryString ? '?' + queryString : '');

    fetch(fetchUrl)
        .then(res => res.json())
        .then(data => {
            const statusEl = document.getElementById('catMapSearchStatus') || document.getElementById('homeMapSearchStatus');

            if (!data.properties || data.properties.length === 0) {
                if (statusEl && queryParams.location) {
                    statusEl.innerHTML = `📍 Map centered on <strong>${escapeHtml(queryParams.location)}</strong>. <em>No active listings in this exact area yet.</em>`;
                }
                return;
            }

            const bounds = [];
            let firstMarker = null;

            data.properties.forEach((item, idx) => {
                const lat = parseFloat(item.latitude);
                const lng = parseFloat(item.longitude);

                if (isNaN(lat) || isNaN(lng)) return;

                bounds.push([lat, lng]);

                // Custom popup content
                const popupContent = `
                    <div style="min-width: 220px; font-family: 'Plus Jakarta Sans', sans-serif;">
                        <img src="${item.image_url}" style="width: 100%; height: 120px; object-fit: cover; border-radius: 8px; margin-bottom: 8px;">
                        <div style="font-weight: 800; color: #2563eb; font-size: 15px;">
                            ${item.currency || 'ETB'} ${Number(item.price).toLocaleString()} <span style="font-size: 11px; color: #64748b; font-weight: normal;">${item.price_frequency === 'monthly' ? '/mo' : ''}</span>
                        </div>
                        <div style="font-weight: 700; color: #0f172a; font-size: 13px; line-height: 1.3; margin: 4px 0;">${escapeHtml(item.title)}</div>
                        <div style="font-size: 12px; color: #64748b; margin-bottom: 8px;">📍 ${escapeHtml(item.city)}, Ethiopia</div>
                        ${item.has_broker == 1 ? `<div style="font-size: 11px; color: #b45309; background: #fef3c7; padding: 2px 6px; border-radius: 4px; margin-bottom: 8px;">${escapeHtml(item.broker_fee_display || 'Broker Fee Disclosed')}</div>` : ''}
                        <a href="${item.details_url}" style="display: block; text-align: center; background: #2563eb; color: #fff; font-size: 12px; font-weight: 600; padding: 6px 12px; border-radius: 6px; text-decoration: none;">View Property Details</a>
                    </div>
                `;

                const marker = L.marker([lat, lng]).bindPopup(popupContent);
                catalogMarkersLayer.addLayer(marker);

                if (idx === 0) firstMarker = marker;
            });

            if (statusEl && queryParams.location) {
                statusEl.innerHTML = `📍 Found <strong>${data.properties.length} property</strong> in <strong>${escapeHtml(queryParams.location)}</strong>!`;
            }

            if (bounds.length === 1) {
                catalogMapInstance.setView(bounds[0], 15);
                if (autoOpenFirst && firstMarker) {
                    setTimeout(() => firstMarker.openPopup(), 400);
                }
            } else if (bounds.length > 1) {
                catalogMapInstance.fitBounds(bounds, { padding: [40, 40], maxZoom: 15 });
                if (autoOpenFirst && firstMarker) {
                    setTimeout(() => firstMarker.openPopup(), 400);
                }
            }
        })
        .catch(err => {
            console.error("Map data fetch error:", err);
        });
}

/**
 * Initializes a single property detail map view (Ethiopia Satellite)
 */
function initPropertyDetailMap(containerId, lat, lng, title, priceText, addressText) {
    const mapEl = document.getElementById(containerId);
    if (!mapEl) return;

    const layers = createMapLayers();

    const map = L.map(containerId, {
        center: [lat, lng],
        zoom: 15,
        minZoom: 6,
        maxZoom: 19,
        maxBounds: ETHIOPIA_BOUNDS,
        maxBoundsViscosity: 1.0,
        layers: [layers.satellite] // Default to Satellite
    });

    const baseMaps = {
        "🛰️ Satellite": layers.satellite,
        "🗺️ Street Map": layers.streets
    };
    L.control.layers(baseMaps, null, { position: 'topright' }).addTo(map);

    const marker = L.marker([lat, lng]).addTo(map);
    marker.bindPopup(`
        <div style="font-family: 'Plus Jakarta Sans', sans-serif;">
            <strong style="color: #2563eb; font-size: 15px;">${priceText}</strong><br>
            <span style="font-weight: 600; font-size: 13px; color: #0f172a;">${escapeHtml(title)}</span><br>
            <small style="color: #64748b;">📍 ${escapeHtml(addressText)}</small>
        </div>
    `).openPopup();
}

// Ethiopian Locations Dictionary for Fast Instant Search & Autocomplete
// Comprehensive Ethiopian Locations & Subcity Dictionary
const ETHIOPIAN_LOCATIONS = {
    'bole': { lat: 8.9954, lng: 38.7884, zoom: 15, name: 'Bole Subcity, Addis Ababa' },
    'bole atlas': { lat: 9.0020, lng: 38.7840, zoom: 16, name: 'Bole Atlas, Addis Ababa' },
    'bole medhanialem': { lat: 8.9972, lng: 38.7865, zoom: 16, name: 'Bole Medhanialem, Addis Ababa' },
    'bole bulbula': { lat: 8.9667, lng: 38.7900, zoom: 15, name: 'Bole Bulbula, Addis Ababa' },
    'bole 24': { lat: 9.0050, lng: 38.7920, zoom: 16, name: 'Bole 24, Addis Ababa' },
    'bole rwanda': { lat: 8.9910, lng: 38.7830, zoom: 16, name: 'Bole Rwanda, Addis Ababa' },
    'kazanchis': { lat: 9.0190, lng: 38.7690, zoom: 16, name: 'Kazanchis, Kirkos, Addis Ababa' },
    'old airport': { lat: 8.9880, lng: 38.7300, zoom: 15, name: 'Old Airport, Nefas Silk, Addis Ababa' },
    'cmc': { lat: 9.0260, lng: 38.8250, zoom: 15, name: 'CMC Michael, Yeka, Addis Ababa' },
    'summit': { lat: 9.0150, lng: 38.8550, zoom: 15, name: 'Summit, Bole, Addis Ababa' },
    'sarbet': { lat: 8.9950, lng: 38.7450, zoom: 15, name: 'Sarbet (African Union), Addis Ababa' },
    'piazza': { lat: 9.0345, lng: 38.7523, zoom: 16, name: 'Piazza / Arada, Addis Ababa' },
    'piassa': { lat: 9.0345, lng: 38.7523, zoom: 16, name: 'Piazza / Arada, Addis Ababa' },
    'megenagna': { lat: 9.0188, lng: 38.8023, zoom: 16, name: 'Megenagna, Yeka, Addis Ababa' },
    'gerji': { lat: 8.9980, lng: 38.8150, zoom: 15, name: 'Gerji, Bole, Addis Ababa' },
    'mexico': { lat: 9.0110, lng: 38.7460, zoom: 16, name: 'Mexico Square, Kirkos, Addis Ababa' },
    'sengatera': { lat: 9.0135, lng: 38.7490, zoom: 16, name: 'Sengatera Financial District, Addis Ababa' },
    'gotera': { lat: 8.9880, lng: 38.7550, zoom: 15, name: 'Gotera, Kirkos, Addis Ababa' },
    'lebu': { lat: 8.9600, lng: 38.7100, zoom: 15, name: 'Lebu, Nefas Silk, Addis Ababa' },
    'jemo': { lat: 8.9500, lng: 38.6900, zoom: 15, name: 'Jemo, Kolfe Keranio, Addis Ababa' },
    'ayat': { lat: 9.0300, lng: 38.8700, zoom: 15, name: 'Ayat, Yeka, Addis Ababa' },
    'kality': { lat: 8.9300, lng: 38.7600, zoom: 14, name: 'Akaki Kality, Addis Ababa' },
    'hayahulet': { lat: 9.0180, lng: 38.7850, zoom: 16, name: '22 Mazoriya / Haya Hulet, Addis Ababa' },
    '22': { lat: 9.0180, lng: 38.7850, zoom: 16, name: '22 Mazoriya, Addis Ababa' },
    'arat kilo': { lat: 9.0320, lng: 38.7620, zoom: 16, name: 'Arat Kilo (4 Kilo), Addis Ababa' },
    '4 kilo': { lat: 9.0320, lng: 38.7620, zoom: 16, name: '4 Kilo, Addis Ababa' },
    'sidist kilo': { lat: 9.0450, lng: 38.7610, zoom: 16, name: 'Sidist Kilo (6 Kilo), Addis Ababa' },
    '6 kilo': { lat: 9.0450, lng: 38.7610, zoom: 16, name: '6 Kilo, Addis Ababa' },
    'torhailoch': { lat: 9.0080, lng: 38.7250, zoom: 15, name: 'Torhailoch, Addis Ababa' },
    'addis ababa': { lat: 9.010793, lng: 38.761252, zoom: 13, name: 'Addis Ababa City Center' },
    'addis': { lat: 9.010793, lng: 38.761252, zoom: 13, name: 'Addis Ababa' },
    'hawassa': { lat: 7.0504, lng: 38.4764, zoom: 14, name: 'Hawassa (Lake Awassa), Sidama' },
    'bahir dar': { lat: 11.5936, lng: 37.3908, zoom: 14, name: 'Bahir Dar, Amhara' },
    'adama': { lat: 8.5400, lng: 39.2700, zoom: 14, name: 'Adama / Nazret, Oromia' },
    'nazret': { lat: 8.5400, lng: 39.2700, zoom: 14, name: 'Adama / Nazret, Oromia' },
    'bishoftu': { lat: 8.7500, lng: 38.9800, zoom: 14, name: 'Bishoftu (Debre Zeyit), Oromia' },
    'dire dawa': { lat: 9.6009, lng: 41.8501, zoom: 14, name: 'Dire Dawa' },
    'gondar': { lat: 12.6000, lng: 37.4667, zoom: 14, name: 'Gondar, Amhara' },
    'mekelle': { lat: 13.4967, lng: 39.4767, zoom: 14, name: 'Mekelle, Tigray' },
    'jimma': { lat: 7.6734, lng: 36.8344, zoom: 14, name: 'Jimma, Oromia' }
};

window.pickerMapInstance = null;
window.pickerMarkerInstance = null;
window.pickerLatInput = null;
window.pickerLngInput = null;

/**
 * Map location picker for Create & Edit property forms (Ethiopia Satellite)
 * Centered on Addis Ababa / Ethiopia, draggable within Ethiopia
 */
function initPropertyPickerMap(containerId, latInputId, lngInputId, defaultLat = 9.010793, defaultLng = 38.761252) {
    const mapEl = document.getElementById(containerId);
    const latInput = document.getElementById(latInputId);
    const lngInput = document.getElementById(lngInputId);

    if (!mapEl || !latInput || !lngInput) return;

    window.pickerLatInput = latInput;
    window.pickerLngInput = lngInput;

    let initialLat = parseFloat(latInput.value);
    let initialLng = parseFloat(lngInput.value);

    if (isNaN(initialLat) || isNaN(initialLng) || initialLat < 3.2 || initialLat > 15.1) {
        initialLat = defaultLat;
        initialLng = defaultLng;
        latInput.value = initialLat.toFixed(6);
        lngInput.value = initialLng.toFixed(6);
    }

    const layers = createMapLayers();

    const map = L.map(containerId, {
        center: [initialLat, initialLng],
        zoom: 14,
        minZoom: 6,
        maxZoom: 19,
        maxBounds: ETHIOPIA_BOUNDS,
        maxBoundsViscosity: 1.0,
        layers: [layers.satellite] // Default to Satellite
    });

    window.pickerMapInstance = map;

    const baseMaps = {
        "🛰️ Satellite (Ethiopia)": layers.satellite,
        "🗺️ Street Map": layers.streets
    };
    L.control.layers(baseMaps, null, { position: 'topright' }).addTo(map);

    const marker = L.marker([initialLat, initialLng], {
        draggable: true
    }).addTo(map);

    window.pickerMarkerInstance = marker;

    function updateInputs(lat, lng, shouldReverse = true) {
        latInput.value = lat.toFixed(6);
        lngInput.value = lng.toFixed(6);

        const badge = document.getElementById('acceptedCoordsLabel');
        if (badge) {
            badge.textContent = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
        }

        if (shouldReverse) {
            reverseGeocodeCoordinates(lat, lng);
        }
    }

    marker.on('dragend', function (e) {
        const position = marker.getLatLng();
        updateInputs(position.lat, position.lng, true);
    });

    map.on('click', function (e) {
        marker.setLatLng(e.latlng);
        updateInputs(e.latlng.lat, e.latlng.lng, true);
    });

    // Initial label sync
    updateInputs(initialLat, initialLng, false);
}

/**
 * Reverse geocodes coordinates to automatically accept & autofill address / city
 */
function reverseGeocodeCoordinates(lat, lng) {
    const statusEls = ['mapSearchStatus', 'catMapSearchStatus', 'homeMapSearchStatus'];
    statusEls.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.innerHTML = `⏳ Accepting location from map: <strong>${lat.toFixed(6)}, ${lng.toFixed(6)}</strong>...`;
    });

    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=16&addressdetails=1`)
        .then(res => res.json())
        .then(data => {
            if (data && data.address) {
                const addr = data.address;
                const suburb = addr.suburb || addr.neighbourhood || addr.residential || addr.road || '';
                const city = addr.city || addr.town || addr.county || 'Addis Ababa';
                const state = addr.state || addr.region || '';

                const addressInput = document.getElementById('propAddress');
                const cityInput = document.getElementById('propCity');
                const stateInput = document.getElementById('propState');

                if (addressInput && (!addressInput.value || addressInput.value.trim() === '')) {
                    addressInput.value = suburb ? `${suburb}, ${city}` : city;
                }
                if (cityInput && (!cityInput.value || cityInput.value.trim() === '')) {
                    cityInput.value = city;
                }
                if (stateInput && (!stateInput.value || stateInput.value.trim() === '')) {
                    stateInput.value = state || suburb;
                }

                const resolvedName = [suburb, city].filter(Boolean).join(', ') || city;
                statusEls.forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.innerHTML = `✅ Location accepted from map: <strong>${escapeHtml(resolvedName)}</strong> (${lat.toFixed(6)}, ${lng.toFixed(6)})`;
                });

                if (window.pickerMarkerInstance) {
                    window.pickerMarkerInstance.bindPopup(`
                        <div style="font-family: inherit; font-size: 13px; line-height: 1.4;">
                            <strong style="color: #10b981; font-size: 14px;">✅ Location Accepted from Map</strong><br>
                            <span style="font-size: 12px; color: #1e293b; font-weight: 700;">${escapeHtml(resolvedName)}</span><br>
                            <span style="font-size: 11px; color: #64748b;">Coordinates: ${lat.toFixed(6)}, ${lng.toFixed(6)}</span><br>
                            <span style="font-size: 11px; color: #2563eb; font-weight: 600;">✓ Address fields updated</span>
                        </div>
                    `).openPopup();
                }
            }
        })
        .catch(() => {
            statusEls.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.innerHTML = `✅ Location accepted from map: <strong>${lat.toFixed(6)}, ${lng.toFixed(6)}</strong>`;
            });
        });
}

/**
 * Uses device GPS / Geolocation to detect current position and accept location on map
 */
function useDeviceGpsLocation(mapType = 'picker') {
    if (!navigator.geolocation) {
        alert("Geolocation is not supported by your browser.");
        return;
    }
    const statusEls = ['mapSearchStatus', 'catMapSearchStatus', 'homeMapSearchStatus'];
    statusEls.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.innerHTML = `⏳ Detecting your GPS location...`;
    });

    navigator.geolocation.getCurrentPosition(
        function (pos) {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            applyMapSearchResult(lat, lng, "Current GPS Location", 16, mapType);
            reverseGeocodeCoordinates(lat, lng);
        },
        function (err) {
            statusEls.forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.innerHTML = `⚠️ Could not detect GPS position (${err.message}). You can click anywhere on the map to pin location.`;
                    setTimeout(() => { el.innerHTML = ''; }, 4500);
                }
            });
        },
        { enableHighAccuracy: true, timeout: 8000 }
    );
}

/**
 * Universal Search Location Function for Maps
 * Handles instant local Ethiopian database & online Nominatim geocoding
 */
function searchMapLocation(query, mapType = 'auto') {
    if (!query) return;
    const cleanQuery = query.toLowerCase().trim();

    // Auto-detect target map instance
    if (mapType === 'auto' || (mapType === 'picker' && !window.pickerMapInstance && catalogMapInstance)) {
        mapType = catalogMapInstance ? 'catalog' : (window.pickerMapInstance ? 'picker' : 'catalog');
    }

    // 1. Check local Ethiopian locations first for instant response
    let match = null;
    if (ETHIOPIAN_LOCATIONS[cleanQuery]) {
        match = ETHIOPIAN_LOCATIONS[cleanQuery];
    } else {
        // Partial search
        for (const [key, loc] of Object.entries(ETHIOPIAN_LOCATIONS)) {
            if (cleanQuery.includes(key) || key.includes(cleanQuery)) {
                match = loc;
                break;
            }
        }
    }

    if (match) {
        applyMapSearchResult(match.lat, match.lng, match.name, match.zoom || 15, mapType, cleanQuery);
        return;
    }

    // 2. Fallback to OpenStreetMap Nominatim for exact streets/landmarks in Ethiopia
    const statusEls = ['catMapSearchStatus', 'homeMapSearchStatus', 'mapSearchStatus'];
    statusEls.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.textContent = 'Searching location in Ethiopia...';
    });

    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query + ', Ethiopia')}&countrycodes=et&limit=1`)
        .then(res => res.json())
        .then(results => {
            if (results && results.length > 0) {
                const item = results[0];
                const lat = parseFloat(item.lat);
                const lng = parseFloat(item.lon);
                applyMapSearchResult(lat, lng, item.display_name, 15, mapType, cleanQuery);
            } else {
                statusEls.forEach(id => {
                    const el = document.getElementById(id);
                    if (el) {
                        el.textContent = `Location "${query}" not found. Try Bole, Kazanchis, CMC, or Hawassa.`;
                        setTimeout(() => { el.textContent = ''; }, 4500);
                    }
                });
            }
        })
        .catch(err => {
            console.error("Geocoding error:", err);
            // Default to Bole if network fails
            if (cleanQuery.includes('bole')) {
                const bole = ETHIOPIAN_LOCATIONS['bole'];
                applyMapSearchResult(bole.lat, bole.lng, bole.name, bole.zoom, mapType, cleanQuery);
            }
        });
}

/**
 * Applies coordinates and smoothly flies the target map
 */
function applyMapSearchResult(lat, lng, name, zoomLevel = 15, mapType = 'auto', searchQuery = '') {
    // Auto-detect target map instance
    if (mapType === 'auto' || (mapType === 'picker' && !window.pickerMapInstance && catalogMapInstance)) {
        mapType = catalogMapInstance ? 'catalog' : (window.pickerMapInstance ? 'picker' : 'catalog');
    }

    // Update status indicators
    const statusEls = ['catMapSearchStatus', 'homeMapSearchStatus', 'mapSearchStatus'];
    statusEls.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.innerHTML = `📍 Located: <strong>${escapeHtml(name)}</strong>`;
    });

    if (mapType === 'picker' && window.pickerMapInstance && window.pickerMarkerInstance) {
        window.pickerMapInstance.flyTo([lat, lng], zoomLevel, { duration: 1.2 });
        window.pickerMarkerInstance.setLatLng([lat, lng]);
        
        if (window.pickerLatInput && window.pickerLngInput) {
            window.pickerLatInput.value = lat.toFixed(6);
            window.pickerLngInput.value = lng.toFixed(6);
        }

        window.pickerMarkerInstance.bindPopup(`
            <div style="font-family: inherit; font-size: 13px; line-height: 1.4;">
                <strong style="color: #2563eb; font-size: 14px;">📍 ${escapeHtml(name)}</strong><br>
                <span style="font-size: 11px; color: #16a34a; font-weight: 600;">✓ Pin placed at coordinates: ${lat.toFixed(6)}, ${lng.toFixed(6)}</span><br>
                <span style="font-size: 11px; color: #64748b;">Drag pin to adjust the exact house location.</span>
            </div>
        `).openPopup();

        // Auto-fill subcity / city if empty
        const stateInput = document.getElementById('propState');
        const cityInput = document.getElementById('propCity');
        if (stateInput && (!stateInput.value || stateInput.value === '')) {
            stateInput.value = name.split(',')[0].trim();
        }
        if (cityInput && (!cityInput.value || cityInput.value === 'Addis Ababa')) {
            cityInput.value = name.includes('Addis Ababa') ? 'Addis Ababa' : name.split(',').pop().trim();
        }
    } else if (mapType === 'catalog' && catalogMapInstance) {
        catalogMapInstance.flyTo([lat, lng], zoomLevel, { duration: 1.2 });

        // Add prominent pulsing search pin
        if (catalogSearchMarker) {
            catalogMapInstance.removeLayer(catalogSearchMarker);
        }

        const pulseIcon = L.divIcon({
            className: 'catalog-search-pulse-icon',
            html: `<div style="background: #2563eb; color: #ffffff; width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; box-shadow: 0 0 0 4px rgba(37,99,235,0.35), 0 4px 10px rgba(0,0,0,0.3); border: 2px solid #ffffff; cursor: pointer;">📍</div>`,
            iconSize: [34, 34],
            iconAnchor: [17, 17]
        });

        catalogSearchMarker = L.marker([lat, lng], { icon: pulseIcon }).addTo(catalogMapInstance);
        catalogSearchMarker.bindPopup(`
            <div style="font-family: inherit; font-size: 13px; min-width: 190px;">
                <strong style="color: #2563eb; font-size: 14px;">📍 ${escapeHtml(name)}</strong><br>
                <span style="font-size: 12px; color: #475569; display: block; margin-top: 4px;">Centered on searched location. Loading properties in this area...</span>
            </div>
        `).openPopup();

        // Query API to load/filter properties for this location
        if (window.catalogApiUrl) {
            const locTerm = searchQuery || name.split(',')[0].trim();
            loadPropertiesOnMap(window.catalogApiUrl, { ...window.catalogQueryParams, location: locTerm }, true);
        }
    }
}

function escapeHtml(string) {
    if (!string) return '';
    return String(string).replace(/[&<>"']/g, function (m) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[m];
    });
}

