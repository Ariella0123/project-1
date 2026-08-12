<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');
$pageTitle = 'Live Tracking';
include __DIR__ . '/../includes/header.php';
?>
<div class="panel">
    <div class="toolbar">
        <div>
            <h2>Active Riders</h2>
            <p class="muted">Map view refreshes the latest locations from the GPS feed.</p>
        </div>
        <button class="secondary-button" id="refreshMapButton" type="button">Refresh</button>
    </div>
    <div id="trackingMap" class="map-box"></div>
</div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(async () => {
    const map = L.map('trackingMap').setView([3.1390, 101.6869], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
    let markers = [];

    async function loadRiders() {
        markers.forEach((marker) => marker.remove());
        markers = [];
        const response = await fetch('<?= url('api/map_data.php') ?>', { credentials: 'same-origin' });
        const data = await response.json();
        const bounds = [];
        (data.riders || []).forEach((rider) => {
            if (!rider.latitude || !rider.longitude) return;
            const popup = document.createElement('div');
            const title = document.createElement('strong');
            title.textContent = rider.name;
            popup.appendChild(title);
            popup.appendChild(document.createElement('br'));
            popup.appendChild(document.createTextNode(`Status: ${rider.current_status}`));
            popup.appendChild(document.createElement('br'));
            popup.appendChild(document.createTextNode(`Updated: ${rider.last_location_at || 'n/a'}`));
            const marker = L.marker([rider.latitude, rider.longitude]).addTo(map);
            marker.bindPopup(popup);
            markers.push(marker);
            bounds.push([rider.latitude, rider.longitude]);
        });
        if (bounds.length > 0) {
            map.fitBounds(bounds, { padding: [25, 25] });
        }
    }

    await loadRiders();
    document.getElementById('refreshMapButton').addEventListener('click', loadRiders);
    setInterval(loadRiders, 20000);
})();
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
