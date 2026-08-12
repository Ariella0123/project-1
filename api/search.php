<?php
require_once __DIR__ . '/_init.php';
require_login();
require_role('admin');

$type = $_GET['type'] ?? '';
$query = trim((string) ($_GET['query'] ?? ''));
$results = [];

if ($type === 'riders') {
    $stmt = db()->prepare('SELECT r.id, u.name, u.email, r.current_status, r.latitude, r.longitude, r.last_location_at FROM riders r INNER JOIN users u ON u.id = r.user_id WHERE u.name LIKE ? OR u.email LIKE ? OR r.phone LIKE ? ORDER BY u.name ASC');
    $like = '%' . $query . '%';
    $stmt->execute([$like, $like, $like]);
    $results = $stmt->fetchAll();
} elseif ($type === 'parcels') {
    $stmt = db()->prepare('SELECT p.id, p.tracking_number, p.customer_name, p.customer_phone, p.status, p.assigned_rider_id, p.created_at FROM parcels p WHERE p.tracking_number LIKE ? OR p.customer_name LIKE ? OR p.customer_phone LIKE ? ORDER BY p.id DESC');
    $like = '%' . $query . '%';
    $stmt->execute([$like, $like, $like]);
    $results = $stmt->fetchAll();
}

json_response(['ok' => true, 'results' => $results]);
