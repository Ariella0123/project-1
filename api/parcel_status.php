<?php
require_once __DIR__ . '/_init.php';
require_login();
require_role('rider');
verify_csrf();

$rider = current_rider();
$parcelId = int_or_null($_POST['parcel_id'] ?? null);
$status = trim((string) ($_POST['status'] ?? ''));
$remarks = trim((string) ($_POST['remarks'] ?? ''));
$latitude = filter_input(INPUT_POST, 'latitude', FILTER_VALIDATE_FLOAT);
$longitude = filter_input(INPUT_POST, 'longitude', FILTER_VALIDATE_FLOAT);

$allowed = ['Pending', 'Out for Delivery', 'Delivered', 'Failed Delivery'];
if (!$parcelId || !in_array($status, $allowed, true)) {
    json_response(['ok' => false, 'message' => 'Invalid parcel status request.'], 422);
}

$stmt = db()->prepare('SELECT id FROM parcels WHERE id = ? AND assigned_rider_id = ? LIMIT 1');
$stmt->execute([$parcelId, $rider['id']]);
if (!$stmt->fetch()) {
    json_response(['ok' => false, 'message' => 'Parcel not assigned to this rider.'], 403);
}

$setDeliveredAt = $status === 'Delivered' ? ', delivered_at = NOW(), failed_reason = NULL' : '';
$setFailedReason = $status === 'Failed Delivery' ? ', failed_reason = ?' : ', failed_reason = NULL';
$sql = 'UPDATE parcels SET status = ?' . $setDeliveredAt . $setFailedReason . ' WHERE id = ?';
$stmt = db()->prepare($sql);
$params = [$status];
if ($status === 'Failed Delivery') {
    $params[] = $remarks !== '' ? $remarks : 'Failed delivery';
}
$params[] = $parcelId;
$stmt->execute($params);

$stmt = db()->prepare('INSERT INTO parcel_status_history (parcel_id, rider_id, status, remarks, location_latitude, location_longitude, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
$stmt->execute([$parcelId, $rider['id'], $status, $remarks, $latitude ?: null, $longitude ?: null]);

add_activity((int) current_user()['id'], 'parcel_status_updated', 'Parcel ' . $parcelId . ' updated to ' . $status);

json_response(['ok' => true, 'status' => $status]);
