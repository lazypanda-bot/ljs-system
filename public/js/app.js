document.addEventListener('DOMContentLoaded', function () {
    const mapEl = document.getElementById('propertyMap');
    if (!mapEl || typeof L === 'undefined') {
        return;
    }

    // Initialize map with zoom control moved to the bottom-right to match your reference
    const map = L.map('propertyMap', {
        zoomControl: false,
        maxZoom: 22
    }).setView([10.3157, 123.8854], 11);

    L.control.zoom({ position: 'bottomright' }).addTo(map);

        const searchInput = document.getElementById('mapSearchInput');
        const searchButton = document.getElementById('mapSearchButton');
        const mapData = window.propertyMapData || [];

        function searchMap() {
            const query = (searchInput?.value || '').trim().toLowerCase();
            if (!query) return;

            const match = mapData.find((property) => `${property.name} ${property.location}`.toLowerCase().includes(query));
            if (match) {
                map.setView([Number(match.lat), Number(match.lng)], 15);
                return;
            }

            fetch(`https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${encodeURIComponent(query)}`, { headers: { Accept: 'application/json' } })
                .then((response) => response.json())
                .then((results) => {
                    if (results[0]) map.setView([Number(results[0].lat), Number(results[0].lon)], 14);
                    else alert('Location not found.');
                })
                .catch(() => alert('Unable to search this location right now.'));
        }

        searchButton?.addEventListener('click', searchMap);
        searchInput?.addEventListener('keydown', (event) => { if (event.key === 'Enter') { event.preventDefault(); searchMap(); } });

    // STREET VIEW AND LABELS
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 22,
        maxNativeZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    const statusColors = {
        'Available': '#2bbf62',
        'For Sale': '#2bbf62',
        'For Rent': '#1f6cff',
        'Rented': '#1f6cff',
        'Sold': '#ef4444',
        'Reserved': '#f59e0b',
        'Unavailable': '#64748b',
        'Pending': '#f59e0b'
    };

    // Plot the properties already supplied by the Blade page.
    if (mapData.length > 0) {
        const bounds = [];

        mapData.forEach((property) => {
            const lat = parseFloat(property.lat);
            const lng = parseFloat(property.lng);

            if (!isNaN(lat) && !isNaN(lng)) {
                const color = statusColors[property.status] || '#1f6cff';
                // Create the custom divIcon directly
                const customIcon = L.divIcon({
                    className: 'map-bubble-marker',
                    html: `
                        <div class="map-bubble-badge">
                            <span class="bubble-dot" style="background: ${color};">
                                <svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round" style="color: #ffffff;"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                            </span>
                        </div>
                    `,
                    iconSize: [34, 34],
                    iconAnchor: [17, 34],
                    popupAnchor: [0, -30]
                });

                const marker = L.marker([lat, lng], { icon: customIcon }).addTo(map);

                const popup = document.createElement('div');
                popup.style.minWidth = '180px';

                const name = document.createElement('strong');
                name.textContent = property.name || 'Property';
                popup.append(name, document.createElement('br'));

                const type = document.createElement('span');
                type.textContent = property.type || '';
                popup.append(type, document.createElement('br'));

                const location = document.createElement('small');
                location.textContent = property.location || '';
                popup.append(location, document.createElement('br'));

                const price = document.createElement('strong');
                price.textContent = `PHP ${Number(property.price || 0).toLocaleString()}`;
                popup.append(price, document.createElement('br'));

                const detailsLink = document.createElement('a');
                detailsLink.href = `/properties/${encodeURIComponent(property.id)}`;
                detailsLink.textContent = 'View Details';
                detailsLink.style.cssText = 'color:#1f6cff;text-decoration:underline;margin-top:5px;display:inline-block';
                popup.append(detailsLink);

                marker.bindPopup(popup);

                bounds.push([lat, lng]);
            }
        });

        if (bounds.length > 0) {
            map.fitBounds(bounds, { padding: [50, 50], maxZoom: 15 });
        }
    }

    // SCOLL
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