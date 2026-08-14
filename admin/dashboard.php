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
                <h2>Live Rider & Delivery Map</h2>
                <p class="muted">Real-time rider movement and assigned delivery destinations.</p>
            </div>
            <a class="secondary-button" href="<?= url('admin/tracking.php') ?>">Open Full Map</a>
        </div>
        <div id="adminMap" class="map-box" style="height: 400px; width: 100%;"></div>
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

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const defaultCenter = [3.1390, 101.6869];
    const map = L.map('adminMap').setView(defaultCenter, 16);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { 
        maxZoom: 19, 
        attribution: '&copy; OpenStreetMap contributors' 
    }).addTo(map);

    setTimeout(() => map.invalidateSize(), 200);

    // 图标定义 (与 Rider 端一致)
    const riderIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
    });

    const destIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
    });

    let riderMarkers = {}; 
    let destMarkers = {};
    let routePolylines = {}; 
    let isFirstLoad = true;

    function isValidLatLng(lat, lng) {
        return lat && lng && !isNaN(lat) && !isNaN(lng) && Math.abs(lat) <= 90 && Math.abs(lng) <= 180 && (lat !== 0 || lng !== 0);
    }

    // 🛣️ 获取真实道路导航路线 (OSRM 免费服务)
    async function fetchOSRMRoute(riderLatLng, destLatLng) {
        try {
            // OSRM 格式为: lng,lat;lng,lat
            const url = `https://router.project-osrm.org/route/v1/driving/${riderLatLng[1]},${riderLatLng[0]};${destLatLng[1]},${destLatLng[0]}?overview=full&geometries=geojson`;
            const res = await fetch(url);
            const data = await res.json();
            
            if (data.routes && data.routes[0]) {
                // 将 GeoJSON 的 [lng, lat] 转换为 Leaflet 的 [lat, lng]
                return data.routes[0].geometry.coordinates.map(coord => [coord[1], coord[0]]);
            }
        } catch (err) {
            console.error('Failed to fetch OSRM route:', err);
        }
        // 如果网络请求失败，退回到两点直线
        return [riderLatLng, destLatLng];
    }

    // 🔄 获取数据并刷新地图
    async function updateMapData() {
        try {
            const response = await fetch('<?= url('api/map_data.php') ?>', { credentials: 'same-origin' });
            const data = await response.json();

            let activeBounds = [];

            for (const item of (data.riders || [])) {
                const riderId = item.rider_id;
                const rLat = parseFloat(item.rider_lat);
                const rLng = parseFloat(item.rider_lng);
                const dLat = parseFloat(item.dest_lat);
                const dLng = parseFloat(item.dest_lng);

                let riderLatLng = null;
                let destLatLng = null;

                // 1. 🛵 骑手 Marker
                if (isValidLatLng(rLat, rLng)) {
                    riderLatLng = [rLat, rLng];
                    activeBounds.push(riderLatLng);

                    const riderPopup = `<b>🛵 Rider: ${item.rider_name}</b><br>Status: ${item.current_status}<br>Updated: ${item.last_location_at || 'n/a'}`;

                    if (riderMarkers[riderId]) {
                        riderMarkers[riderId].setLatLng(riderLatLng).getPopup().setContent(riderPopup);
                    } else {
                        riderMarkers[riderId] = L.marker(riderLatLng, { icon: riderIcon }).addTo(map).bindPopup(riderPopup);
                    }
                }

                // 2. 📍 目的地 Marker
                if (isValidLatLng(dLat, dLng)) {
                    destLatLng = [dLat, dLng];
                    activeBounds.push(destLatLng);

                    const destPopup = `<b>📍 Destination</b><br>Customer: ${item.customer_name}<br>Tracking: ${item.tracking_number}<br>Address: ${item.customer_address}`;

                    if (destMarkers[riderId]) {
                        destMarkers[riderId].setLatLng(destLatLng).getPopup().setContent(destPopup);
                    } else {
                        destMarkers[riderId] = L.marker(destLatLng, { icon: destIcon }).addTo(map).bindPopup(destPopup);
                    }
                }

                // 3. 🛣️ 获取并绘制与 Rider 端完全一样的“实际街道路线”
                if (riderLatLng && destLatLng) {
                    let routePoints = [];

                    // 如果后端数据直接提供了 route_path 就用后端的，否则调用 OSRM 实时计算道路路线
                    if (item.route_path && Array.isArray(item.route_path) && item.route_path.length > 0) {
                        routePoints = item.route_path;
                    } else {
                        routePoints = await fetchOSRMRoute(riderLatLng, destLatLng);
                    }

                    // 把路线上的所有道路弯道点也加入到 Bounds，确保整体视野能完整放下整条路线
                    routePoints.forEach(pt => activeBounds.push(pt));

                    if (routePolylines[riderId]) {
                        routePolylines[riderId].setLatLngs(routePoints);
                    } else {
                        routePolylines[riderId] = L.polyline(routePoints, {
                            color: '#e63946', // 与 Rider 端一致的经典红色
                            weight: 4,
                            opacity: 0.8,
                            lineCap: 'round',
                            lineJoin: 'round'
                        }).addTo(map);
                    }
                }
            }

            // 🎯 首次加载：精准适配“骑手 + 路线 + 目的地”的全局视野
            if (isFirstLoad && activeBounds.length > 0) {
                map.fitBounds(activeBounds, { 
                    padding: [30, 30], 
                    maxZoom: 17, 
                    animate: false 
                });
                isFirstLoad = false;
            }

        } catch (err) {
            console.error('Error fetching live map data:', err);
        }
    }

    // 🚀 首次运行
    updateMapData();

    // ⏱️ 每 3 秒自动轮询更新，确保骑手移动时路线同步变化
    setInterval(updateMapData, 3000);
});
</script>