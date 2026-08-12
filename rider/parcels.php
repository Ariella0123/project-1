<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('rider');

$rider = current_rider();
$q = trim((string) ($_GET['q'] ?? ''));
$sql = 'SELECT id, tracking_number, customer_name, customer_address, status, created_at FROM parcels WHERE assigned_rider_id = ?';
$params = [$rider['id']];
if ($q !== '') {
    $sql .= ' AND (tracking_number LIKE ? OR customer_name LIKE ? OR customer_address LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like);
}
$sql .= ' ORDER BY id DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$parcels = $stmt->fetchAll();
$pageTitle = 'Assigned Parcels';
include __DIR__ . '/../includes/header.php';
?>
<div class="panel">
    <div class="toolbar">
        <form method="get">
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search assigned parcels">
            <button class="secondary-button" type="submit">Search</button>
        </form>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Tracking</th><th>Customer</th><th>Status</th><th>Details</th></tr></thead>
            <tbody>
            <?php foreach ($parcels as $parcel): ?>
                <tr>
                    <td><?= e($parcel['tracking_number']) ?></td>
                    <td><?= e($parcel['customer_name']) ?></td>
                    <td><?= e($parcel['status']) ?></td>
                    <td><a class="secondary-button" href="<?= url('rider/parcel_view.php?id=' . (int) $parcel['id']) ?>">Open</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
