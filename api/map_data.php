<?php
// api/map_data.php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

// 查询骑手信息，同时 LEFT JOIN 该骑手未完成的包裹信息
$sql = "SELECT 
            r.id AS rider_id,
            u.name AS rider_name,
            r.current_status,
            r.latitude AS rider_lat,
            r.longitude AS rider_lng,
            r.last_location_at,
            p.id AS parcel_id,
            p.tracking_number,
            p.customer_name,
            p.customer_address,
            p.customer_latitude AS dest_lat,
            p.customer_longitude AS dest_lng,
            p.status AS parcel_status
        FROM riders r
        INNER JOIN users u ON u.id = r.user_id
        LEFT JOIN parcels p ON p.assigned_rider_id = r.id AND p.status IN ('Out for Delivery', 'Pending')
        WHERE r.current_status = 'online'";

$stmt = db()->query($sql);
$data = $stmt->fetchAll();

echo json_encode(['riders' => $data]);