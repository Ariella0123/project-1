<?php
require_once __DIR__ . '/_init.php';
require_login();
require_role('rider');
verify_csrf();

$rider = current_rider();
if (!$rider) {
    json_response(['ok' => false, 'message' => 'Rider profile not found.'], 404);
}

$status = $_POST['status'] ?? '';
if (!in_array($status, ['online', 'offline'], true)) {
    json_response(['ok' => false, 'message' => 'Invalid rider status.'], 422);
}

$stmt = db()->prepare('UPDATE riders SET current_status = ? WHERE id = ?');
$stmt->execute([$status, $rider['id']]);
add_activity((int) current_user()['id'], 'rider_status_changed', 'Rider set status to ' . $status);

json_response(['ok' => true, 'status' => $status]);
