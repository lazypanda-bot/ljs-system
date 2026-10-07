document.addEventListener('DOMContentLoaded', function () {
    const mapEl = document.getElementById('propertyMap');
    if (!mapEl || typeof L === 'undefined') {
        return;
    }

    const map = L.map('propertyMap', {
        zoomControl: false,
        maxZoom: 22,
    }).setView([10.3157, 123.8854], 11);

    L.control.zoom({ position: 'bottomright' }).addTo(map);

    const searchInput = document.getElementById('mapSearchInput');
    const searchButton = document.getElementById('mapSearchButton');
    const mapData = Array.isArray(window.propertyMapData) ? window.propertyMapData : [];

    const statusColors = {
        'Available': '#2bbf62',
        'For Sale': '#2bbf62',
        'For Rent': '#1f6cff',
        'Rented': '#1f6cff',
        'Sold': '#ef4444',
        'Reserved': '#f59e0b',
        'Unavailable': '#64748b',
        'Pending': '#f59e0b',
    };

    const getPropertySearchText = (property) => `${property.name || ''} ${property.location || ''}`.toLowerCase();

    const fitMapToProperties = (items) => {
        if (!items.length) {
            map.setView([10.3157, 123.8854], 11);
            return;
        }

        const bounds = [];
        items.forEach((property) => {
            const lat = Number(property.lat);
            const lng = Number(property.lng);
            if (Number.isFinite(lat) && Number.isFinite(lng) && Math.abs(lat) <= 90 && Math.abs(lng) <= 180) {
                bounds.push([lat, lng]);
            }
        });

        if (bounds.length) {
            map.fitBounds(bounds, { padding: [50, 50], maxZoom: 15 });
            return;
        }

        map.setView([10.3157, 123.8854], 11);
    };

    const addMarkers = (items) => {
        map.eachLayer((layer) => {
            if (layer instanceof L.Marker || layer instanceof L.CircleMarker) {
                map.removeLayer(layer);
            }
        });

        const markerGroups = new Map();
        items.forEach((property) => {
            const lat = parseFloat(property.lat);
            const lng = parseFloat(property.lng);
            if (!Number.isFinite(lat) || !Number.isFinite(lng) || Math.abs(lat) > 90 || Math.abs(lng) > 180) {
                return;
            }

            const coordinateKey = `${lat.toFixed(6)},${lng.toFixed(6)}`;
            markerGroups.set(coordinateKey, [...(markerGroups.get(coordinateKey) || []), property]);
        });

        markerGroups.forEach((properties) => {
            const property = properties[0];
            const lat = Number(property.lat);
            const lng = Number(property.lng);
            const color = statusColors[property.status] || '#1f6cff';
            const customIcon = L.divIcon({
                className: 'map-bubble-marker',
                html: `
                    <div class="map-bubble-badge">
                        <span class="bubble-dot" style="background: ${color};">
                            <svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round" style="color: #ffffff;"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        </span>
                        ${properties.length > 1 ? `<span class="bubble-count" aria-label="${properties.length} properties">${properties.length}</span>` : ''}
                    </div>
                `,
                iconSize: [34, 34],
                iconAnchor: [17, 34],
                popupAnchor: [0, -30],
            });

            const marker = L.marker([lat, lng], { icon: customIcon }).addTo(map);
            const popup = document.createElement('div');
            popup.style.minWidth = '180px';

            properties.forEach((item, index) => {
                if (index > 0) {
                    const divider = document.createElement('hr');
                    divider.style.cssText = 'margin:10px 0;border:0;border-top:1px solid #e2e8f0';
                    popup.append(divider);
                }

                const name = document.createElement('strong');
                name.textContent = item.name || 'Property';
                popup.append(name, document.createElement('br'));

                const type = document.createElement('span');
                type.textContent = item.type || '';
                popup.append(type, document.createElement('br'));

                const location = document.createElement('small');
                location.textContent = item.location || '';
                popup.append(location, document.createElement('br'));

                const price = document.createElement('strong');
                price.textContent = `PHP ${Number(item.price || 0).toLocaleString()}`;
                popup.append(price, document.createElement('br'));

                if (item.approximate) {
                    const note = document.createElement('small');
                    note.textContent = 'Approximate map location while address lookup completes.';
                    note.style.cssText = 'display:block;color:#92400e;margin-top:4px';
                    popup.append(note);
                }

                const detailsLink = document.createElement('a');
                detailsLink.href = `/properties/${encodeURIComponent(item.id)}`;
                detailsLink.textContent = 'View Details';
                detailsLink.style.cssText = 'color:#1f6cff;text-decoration:underline;margin-top:5px;display:inline-block';
                popup.append(detailsLink);
            });

            marker.bindPopup(popup);
        });
    };

    const searchMap = () => {
        const query = (searchInput?.value || '').trim().toLowerCase();
        if (!query) {
            addMarkers(mapData);
            fitMapToProperties(mapData);
            return;
        }

        const filtered = mapData.filter((property) => getPropertySearchText(property).includes(query));
        if (filtered.length) {
            addMarkers(filtered);
            fitMapToProperties(filtered);
            return;
        }

        fetch(`https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${encodeURIComponent(query)}`, { headers: { Accept: 'application/json' } })
            .then((response) => response.json())
            .then((results) => {
                if (results[0]) {
                    map.setView([Number(results[0].lat), Number(results[0].lon)], 14);
                    return;
                }
                alert('Location not found.');
            })
            .catch(() => alert('Unable to search this location right now.'));
    };

    searchButton?.addEventListener('click', searchMap);
    searchInput?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            searchMap();
        }
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 22,
        maxNativeZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(map);

    addMarkers(mapData);
    fitMapToProperties(mapData);

    const navbar = document.querySelector('.landing-nav.transparent-nav');

    if (navbar) {
        function handleScroll() {
            if (window.scrollY > 20) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        }

        window.addEventListener('scroll', handleScroll);
        handleScroll();
    }
});