<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('rider');

$parcelId = int_or_null($_GET['id'] ?? null);

if (!$parcelId) {
    redirect('rider/dashboard.php');
}

$stmt = db()->prepare('SELECT * FROM parcels WHERE id = ? LIMIT 1');
$stmt->execute([$parcelId]);
$parcel = $stmt->fetch();

if (!$parcel) {
    redirect('rider/dashboard.php');
}

$pageTitle = 'Navigation - ' . $parcel['tracking_number'];
include __DIR__ . '/../includes/header.php';
?>

<!-- 引入免费开源地图 Leaflet CSS & JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />

<style>
.nav-container {
    display: flex;
    flex-direction: column;
    gap: 16px;
    max-width: 900px;
    margin: 0 auto;
}
.route-info-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}
.route-stats {
    display: flex;
    gap: 20px;
}
.stat-box {
    display: flex;
    flex-direction: column;
}
.stat-label {
    font-size: 12px;
    color: #64748b;
}
.stat-value {
    font-size: 18px;
    font-weight: bold;
    color: #0f172a;
}
#map {
    width: 100%;
    height: 480px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.arrive-btn {
    background-color: #16a34a;
    color: white;
    padding: 12px 24px;
    font-size: 16px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    text-align: center;
    transition: background 0.2s;
}
.arrive-btn:hover {
    background-color: #15803d;
}
</style>

<div class="nav-container">
    <div class="route-info-card">
        <div>
            <h3 style="margin: 0; font-size: 16px;">Delivering to: <?= e($parcel['customer_name']) ?></h3>
            <p style="margin: 4px 0 0 0; color: #64748b; font-size: 13px;"><?= e($parcel['customer_address']) ?></p>
        </div>

        <div class="route-stats">
            <div class="stat-box">
                <span class="stat-label">Distance</span>
                <span class="stat-value" id="route_distance">Calculating...</span>
            </div>
            <div class="stat-box">
                <span class="stat-label">Estimated Time</span>
                <span class="stat-value" id="route_time" style="color: #2563eb;">Calculating...</span>
            </div>
        </div>

        <a href="parcel_view.php?id=<?= (int)$parcel['id'] ?>" class="arrive-btn">
            Arrived & Upload Proof ➔
        </a>
    </div>

    <!-- 地图渲染区域 -->
    <div id="map"></div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // 目的地坐标（从数据库获取，转为浮点数）
    const destLat = parseFloat("<?= $parcel['customer_latitude'] ?? '' ?>") || 5.3758; // 如果为空，默认设为 Perai 附近
    const destLng = parseFloat("<?= $parcel['customer_longitude'] ?? '' ?>") || 100.3985;

    console.log("📍 [Destination Coordinate]:", destLat, destLng);

    // 初始化地图
    const map = L.map('map').setView([destLat, destLng], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    let userLat = null;
    let userLng = null;
    let recenterTimer = null;

    // 自定义 Marker 图标
    const riderIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });

    const destinationIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });

    // 定位配置选项：开启极高精度模式
    const geoOptions = {
        enableHighAccuracy: true, // 强制开启硬件 GPS/高精度定位
        timeout: 10000,           // 10秒超时
        maximumAge: 0             // 拒绝使用缓存定位
    };

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(position => {
            userLat = position.coords.latitude;
            userLng = position.coords.longitude;

            console.log("🛵 [Rider GPS Location]:", userLat, userLng);
            console.log("🎯 [Accuracy Limit]:", position.coords.accuracy, "meters");

            // 绘制路线
            const routingControl = L.Routing.control({
                waypoints: [
                    L.latLng(userLat, userLng), // 起点：骑手位置
                    L.latLng(destLat, destLng)  // 终点：客户地址
                ],
                routeWhileDragging: false,
                addWaypoints: false,
                draggableWaypoints: false,
                show: false,
                createMarker: function(i, wp, nWps) {
                    const isRider = (i === 0);
                    return L.marker(wp.latLng, {
                        draggable: false,
                        icon: isRider ? riderIcon : destinationIcon
                    }).bindPopup(isRider ? '🛵 Rider Location' : '📍 Destination');
                }
            }).addTo(map);

            routingControl.on('routesfound', function(e) {
                const routes = e.routes[0];
                const distanceKm = (routes.summary.totalDistance / 1000).toFixed(1);
                const timeMin = Math.round(routes.summary.totalTime / 60);

                document.getElementById('route_distance').textContent = distanceKm + ' km';
                document.getElementById('route_time').textContent = timeMin + ' mins';
            });

            // 3秒自动回弹
            map.on('dragend', () => {
                if (recenterTimer) clearTimeout(recenterTimer);
                recenterTimer = setTimeout(() => {
                    if (userLat && userLng) {
                        map.panTo([userLat, userLng], { animate: true, duration: 1.0 });
                    }
                }, 3000);
            });

        }, (error) => {
            console.warn("Geolocation Error:", error.message);
            alert('Unable to fetch precise GPS location. Showing destination only.');
            L.marker([destLat, destLng], { draggable: false, icon: destinationIcon })
             .addTo(map)
             .bindPopup('📍 Destination')
             .openPopup();
        }, geoOptions);
    } else {
        alert('Geolocation is not supported by your browser.');
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>