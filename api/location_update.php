<?php
require_once __DIR__ . '/_init.php';
require_login();
require_role('rider');
verify_csrf();

$rider = current_rider();
if (!$rider) {
    json_response(['ok' => false, 'message' => 'Rider profile not found.'], 404);
}

$latitude = filter_input(INPUT_POST, 'latitude', FILTER_VALIDATE_FLOAT);
$longitude = filter_input(INPUT_POST, 'longitude', FILTER_VALIDATE_FLOAT);
$accuracy = filter_input(INPUT_POST, 'accuracy', FILTER_VALIDATE_FLOAT);

if ($latitude === false || $longitude === false) {
    json_response(['ok' => false, 'message' => 'Invalid GPS coordinates.'], 422);
}

$stmt = db()->prepare('INSERT INTO rider_locations (rider_id, latitude, longitude, accuracy, source, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
$stmt->execute([$rider['id'], $latitude, $longitude, $accuracy ?: null, 'browser']);

$stmt = db()->prepare('UPDATE riders SET latitude = ?, longitude = ?, last_location_at = NOW() WHERE id = ?');
$stmt->execute([$latitude, $longitude, $rider['id']]);

json_response(['ok' => true, 'latitude' => $latitude, 'longitude' => $longitude]);
