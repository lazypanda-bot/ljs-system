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

    // Satellite and Label layers
    L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 22,
        maxNativeZoom: 19,
        attribution: 'Tiles &copy; Esri'
    }).addTo(map);

    L.tileLayer('https://services.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 22,
        maxNativeZoom: 19,
        pane: 'overlayPane' 
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

    const mapData = window.propertyMapData || [];

    if (mapData.length > 0) {
        const bounds = [];

        mapData.forEach((property) => {
            const lat = parseFloat(property.lat);
            const lng = parseFloat(property.lng);

            if (!isNaN(lat) && !isNaN(lng)) {
                const marker = L.marker([lat, lng]).addTo(map);
                const color = statusColors[property.status] || '#1f6cff';
                const statusLabel = property.status || 'Available';

                marker.bindPopup(`
                    <div style="min-width:180px;">
                        <strong>${property.name}</strong><br>
                        <span>${property.type}</span><br>
                        <small>${property.location}</small><br>
                        <strong>₱ ${Number(property.price || 0).toLocaleString()}</strong>
                    </div>
                `);

                // Teardrop bubble pin with exact matching white home SVG inside solid circle
                marker.setIcon(L.divIcon({
                    className: 'map-bubble-marker',
                    html: `
                        <div class="map-bubble-badge">
                            <span class="bubble-dot" style="background: ${color};">
                                <svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round" style="color: #ffffff;"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                            </span>
                            <span class="bubble-text" style="color: ${color};">${statusLabel}</span>
                        </div>
                    `,
                    iconSize: [130, 40],
                    iconAnchor: [65, 40],
                    popupAnchor: [0, -42]
                }));

                bounds.push([lat, lng]);
            }
        });

        if (bounds.length > 0) {
            map.fitBounds(bounds, { padding: [50, 50], maxZoom: 15 });
        }
    }
});