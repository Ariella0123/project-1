<?php
require_once __DIR__ . '/_init.php';
require_login();
require_role('admin');
verify_csrf();

$parcelId = int_or_null($_POST['parcel_id'] ?? null);
$riderId = int_or_null($_POST['rider_id'] ?? null);

if (!$parcelId || !$riderId) {
    json_response(['ok' => false, 'message' => 'Parcel and rider are required.'], 422);
}

$stmt = db()->prepare('SELECT id FROM riders WHERE id = ? LIMIT 1');
$stmt->execute([$riderId]);
if (!$stmt->fetch()) {
    json_response(['ok' => false, 'message' => 'Rider not found.'], 404);
}

$stmt = db()->prepare('UPDATE parcels SET assigned_rider_id = ?, status = CASE WHEN status = "Pending" THEN "Out for Delivery" ELSE status END WHERE id = ?');
$stmt->execute([$riderId, $parcelId]);

$stmt = db()->prepare('INSERT INTO parcel_status_history (parcel_id, rider_id, status, remarks, created_at) VALUES (?, ?, ?, ?, NOW())');
$stmt->execute([$parcelId, $riderId, 'Out for Delivery', 'Assigned by admin']);

add_activity((int) current_user()['id'], 'parcel_assigned', 'Parcel ' . $parcelId . ' assigned to rider ' . $riderId);

json_response(['ok' => true]);
