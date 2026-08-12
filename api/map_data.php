<?php
require_once __DIR__ . '/_init.php';
require_login();
require_role('admin');

$stmt = db()->query('SELECT r.id, u.name, r.current_status, r.latitude, r.longitude, r.last_location_at FROM riders r INNER JOIN users u ON u.id = r.user_id WHERE r.latitude IS NOT NULL AND r.longitude IS NOT NULL ORDER BY r.last_location_at DESC');
json_response(['ok' => true, 'riders' => $stmt->fetchAll()]);
