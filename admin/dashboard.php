<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$pageTitle = 'Admin Dashboard';
$stats = [
    'total_riders' => count_value('SELECT COUNT(*) FROM riders'),
    'online_riders' => count_value("SELECT COUNT(*) FROM riders WHERE current_status = 'online'"),
    'offline_riders' => count_value("SELECT COUNT(*) FROM riders WHERE current_status = 'offline'"),
    'total_parcels' => count_value('SELECT COUNT(*) FROM parcels'),
    'delivered_parcels' => count_value("SELECT COUNT(*) FROM parcels WHERE status = 'Delivered'"),
    'pending_parcels' => count_value("SELECT COUNT(*) FROM parcels WHERE status = 'Pending'"),
];

$recentParcels = db()->query('SELECT p.id, p.tracking_number, p.customer_name, p.status, p.created_at, u.name AS rider_name FROM parcels p LEFT JOIN riders r ON r.id = p.assigned_rider_id LEFT JOIN users u ON u.id = r.user_id ORDER BY p.id DESC LIMIT 6')->fetchAll();
$recentRiders = db()->query('SELECT r.id, u.name, u.email, r.current_status, r.latitude, r.longitude, r.last_location_at FROM riders r INNER JOIN users u ON u.id = r.user_id ORDER BY r.updated_at DESC LIMIT 6')->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<div class="card-grid">
    <div class="card stat-card"><span>Total Riders</span><strong><?= (int) $stats['total_riders'] ?></strong></div>
    <div class="card stat-card"><span>Online Riders</span><strong><?= (int) $stats['online_riders'] ?></strong></div>
    <div class="card stat-card"><span>Total Parcels</span><strong><?= (int) $stats['total_parcels'] ?></strong></div>
    <div class="card stat-card"><span>Delivered Parcels</span><strong><?= (int) $stats['delivered_parcels'] ?></strong></div>
</div>

<div class="layout-grid">
    <section class="panel">
        <div class="toolbar">
            <div>
                <h2>Live Rider Map</h2>
                <p class="muted">Active riders update from the browser geolocation feed.</p>
            </div>
            <a class="secondary-button" href="<?= url('admin/tracking.php') ?>">Open Full Map</a>
        </div>
        <div id="adminMap" class="map-box"></div>
    </section>

    <section class="panel">
        <h2>Quick Status</h2>
        <div class="route-list">
            <div class="route-item"><strong><?= (int) $stats['online_riders'] ?></strong><div class="small-note">Riders online now</div></div>
            <div class="route-item"><strong><?= (int) $stats['offline_riders'] ?></strong><div class="small-note">Riders offline</div></div>
            <div class="route-item"><strong><?= (int) $stats['pending_parcels'] ?></strong><div class="small-note">Parcels still pending</div></div>
        </div>
    </section>
</div>

<div class="layout-grid">
    <section class="panel">
        <div class="toolbar">
            <h2>Recent Parcels</h2>
            <a class="secondary-button" href="<?= url('admin/parcels.php') ?>">Manage Parcels</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Tracking</th><th>Customer</th><th>Status</th><th>Rider</th><th>Created</th></tr></thead>
                <tbody>
                <?php foreach ($recentParcels as $parcel): ?>
                    <tr>
                        <td><a href="<?= url('admin/parcel_view.php?id=' . (int) $parcel['id']) ?>"><?= e($parcel['tracking_number']) ?></a></td>
                        <td><?= e($parcel['customer_name']) ?></td>
                        <td><span class="status-pill status-<?= strtolower(str_replace(' ', '-', $parcel['status'])) ?>"><?= e($parcel['status']) ?></span></td>
                        <td><?= e($parcel['rider_name'] ?? 'Unassigned') ?></td>
                        <td><?= e($parcel['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel">
        <div class="toolbar">
            <h2>Recent Riders</h2>
            <a class="secondary-button" href="<?= url('admin/riders.php') ?>">View Riders</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Name</th><th>Status</th><th>Last Update</th></tr></thead>
                <tbody>
                <?php foreach ($recentRiders as $rider): ?>
                    <tr>
                        <td><?= e($rider['name']) ?></td>
                        <td><span class="status-pill status-<?= $rider['current_status'] === 'online' ? 'online' : 'offline' ?>"><?= e(ucfirst($rider['current_status'])) ?></span></td>
                        <td><?= e($rider['last_location_at'] ?? 'No GPS data') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(async () => {
    const map = L.map('adminMap').setView([3.1390, 101.6869], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap contributors' }).addTo(map);

    const response = await fetch('<?= url('api/map_data.php') ?>', { credentials: 'same-origin' });
    const data = await response.json();
    const bounds = [];
    (data.riders || []).forEach((rider) => {
        if (!rider.latitude || !rider.longitude) {
            return;
        }
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
        bounds.push([rider.latitude, rider.longitude]);
    });
    if (bounds.length > 0) {
        map.fitBounds(bounds, { padding: [25, 25] });
    }
})();
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
