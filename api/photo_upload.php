<?php
require_once __DIR__ . '/_init.php';
require_login();
require_role('rider');
verify_csrf();

$rider = current_rider();
$parcelId = int_or_null($_POST['parcel_id'] ?? null);
$remarks = trim((string) ($_POST['remarks'] ?? ''));

if (!$parcelId || empty($_FILES['photo'])) {
    json_response(['ok' => false, 'message' => 'Parcel and photo are required.'], 422);
}

$upload = upload_delivery_photo($_FILES['photo']);
if (!$upload['ok']) {
    json_response(['ok' => false, 'message' => $upload['error']], 422);
}

$stmt = db()->prepare('INSERT INTO delivery_photos (parcel_id, rider_id, photo_path, remarks, created_at) VALUES (?, ?, ?, ?, NOW())');
$stmt->execute([$parcelId, $rider['id'], $upload['path'], $remarks]);

add_activity((int) current_user()['id'], 'delivery_photo_uploaded', 'Photo proof uploaded for parcel ' . $parcelId);

json_response(['ok' => true, 'path' => $upload['path']]);
