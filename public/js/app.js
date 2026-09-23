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

    // Hook up functionality to your existing HTML button
    const locateBtn = document.getElementById('locateMeBtn');
    if (locateBtn) {
        locateBtn.addEventListener('click', function (e) {
            e.preventDefault();
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function (position) {
                    map.setView([position.coords.latitude, position.coords.longitude], 14);
                }, function () {
                    alert('Unable to retrieve your location.');
                });
            }
        });
    }

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
                const statusLabel = property.status || 'Available';

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

                marker.bindPopup(`
                    <div style="min-width:180px;">
                        <strong>${property.name}</strong><br>
                        <span>${property.type}</span><br>
                        <small>${property.location}</small><br>
                        <strong>₱ ${Number(property.price || 0).toLocaleString()}</strong><br>
                        <a href="/properties/${property.id}" style="color: #1f6cff; text-decoration: underline; margin-top: 5px; display: inline-block;">View Details</a>
                    </div>
                `);

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