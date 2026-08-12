<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$q = trim((string) ($_GET['q'] ?? ''));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_role('admin');
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $riderId = int_or_null($_POST['rider_id'] ?? null);

    if ($action === 'toggle' && $riderId) {
        $status = ($_POST['current_status'] ?? 'offline') === 'online' ? 'offline' : 'online';
        $stmt = db()->prepare('UPDATE riders SET current_status = ? WHERE id = ?');
        $stmt->execute([$status, $riderId]);
        flash('success', 'Rider status updated.');
        redirect('admin/riders.php');
    }
}

$params = [];
$sql = 'SELECT r.id, u.name, u.email, r.phone, r.vehicle_info, r.plate_number, r.current_status, r.latitude, r.longitude, r.last_location_at FROM riders r INNER JOIN users u ON u.id = r.user_id';
if ($q !== '') {
    $sql .= ' WHERE u.name LIKE ? OR u.email LIKE ? OR r.phone LIKE ? OR r.plate_number LIKE ?';
    $like = '%' . $q . '%';
    $params = [$like, $like, $like, $like];
}
$sql .= ' ORDER BY r.updated_at DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$riders = $stmt->fetchAll();

$pageTitle = 'Riders';
include __DIR__ . '/../includes/header.php';
?>
<div class="panel">
    <div class="toolbar">
        <form method="get">
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search riders">
            <button class="secondary-button" type="submit">Search</button>
        </form>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Vehicle</th><th>Status</th><th>Location</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($riders as $rider): ?>
                <tr data-search-row>
                    <td><?= e($rider['name']) ?></td>
                    <td><?= e($rider['email']) ?></td>
                    <td><?= e($rider['phone'] ?? '-') ?></td>
                    <td><?= e(trim(($rider['vehicle_info'] ?? '') . ' ' . ($rider['plate_number'] ?? ''))) ?></td>
                    <td><span class="status-pill status-<?= $rider['current_status'] === 'online' ? 'online' : 'offline' ?>"><?= e(ucfirst($rider['current_status'])) ?></span></td>
                    <td><?= e(($rider['latitude'] && $rider['longitude']) ? $rider['latitude'] . ', ' . $rider['longitude'] : 'No GPS data') ?></td>
                    <td>
                        <form method="post" class="inline-actions">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="rider_id" value="<?= (int) $rider['id'] ?>">
                            <input type="hidden" name="current_status" value="<?= e($rider['current_status']) ?>">
                            <button class="secondary-button" type="submit"><?= $rider['current_status'] === 'online' ? 'Set Offline' : 'Set Online' ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
