<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (($_POST['action'] ?? '') === 'delete') {
        $parcelId = int_or_null($_POST['parcel_id'] ?? null);
        if ($parcelId) {
            $stmt = db()->prepare('DELETE FROM parcels WHERE id = ?');
            $stmt->execute([$parcelId]);
            flash('success', 'Parcel deleted.');
        }
        redirect('admin/parcels.php');
    }
}

$q = trim((string) ($_GET['q'] ?? ''));
$params = [];
$sql = 'SELECT p.id, p.tracking_number, p.customer_name, p.customer_phone, p.status, p.assigned_rider_id, p.created_at, u.name AS rider_name FROM parcels p LEFT JOIN riders r ON r.id = p.assigned_rider_id LEFT JOIN users u ON u.id = r.user_id';
if ($q !== '') {
    $sql .= ' WHERE p.tracking_number LIKE ? OR p.customer_name LIKE ? OR p.customer_phone LIKE ?';
    $like = '%' . $q . '%';
    $params = [$like, $like, $like];
}
$sql .= ' ORDER BY p.id DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$parcels = $stmt->fetchAll();
$riders = db()->query('SELECT r.id, u.name FROM riders r INNER JOIN users u ON u.id = r.user_id ORDER BY u.name ASC')->fetchAll();

$pageTitle = 'Parcels';
include __DIR__ . '/../includes/header.php';
?>
<div class="panel">
    <div class="toolbar">
        <form method="get">
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search parcels">
            <button class="secondary-button" type="submit">Search</button>
        </form>
        <a class="primary-button" href="<?= url('admin/parcel_form.php') ?>">Create Parcel</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Tracking</th><th>Customer</th><th>Status</th><th>Rider</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($parcels as $parcel): ?>
                <tr data-search-row>
                    <td><a href="<?= url('admin/parcel_view.php?id=' . (int) $parcel['id']) ?>"><?= e($parcel['tracking_number']) ?></a></td>
                    <td><?= e($parcel['customer_name']) ?><br><span class="small-note"><?= e($parcel['customer_phone'] ?? '-') ?></span></td>
                    <td><span class="status-pill status-<?= strtolower(str_replace(' ', '-', $parcel['status'])) ?>"><?= e($parcel['status']) ?></span></td>
                    <td><?= e($parcel['rider_name'] ?? 'Unassigned') ?></td>
                    <td><?= e($parcel['created_at']) ?></td>
                    <td>
                        <div class="inline-actions">
                            <a class="secondary-button" href="<?= url('admin/parcel_form.php?id=' . (int) $parcel['id']) ?>">Edit</a>
                            <a class="secondary-button" href="<?= url('admin/parcel_view.php?id=' . (int) $parcel['id']) ?>">View</a>
                            <form method="post" onsubmit="return confirm('Delete this parcel?');">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="parcel_id" value="<?= (int) $parcel['id'] ?>">
                                <button class="danger-button" type="submit">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
