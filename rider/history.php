<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('rider');

$rider = current_rider();
$allowedStatuses = ['Pending', 'Out for Delivery', 'Failed Delivery', 'Delivered'];
$selectedStatus = trim((string) ($_GET['h_status'] ?? ''));
if ($selectedStatus !== '' && !in_array($selectedStatus, $allowedStatuses, true)) {
    $selectedStatus = '';
}

$sql = 'SELECT h.status, h.remarks, h.created_at, p.tracking_number FROM parcel_status_history h INNER JOIN parcels p ON p.id = h.parcel_id WHERE h.rider_id = ?';
$params = [$rider['id']];
if ($selectedStatus !== '') {
    $sql .= ' AND h.status = ?';
    $params[] = $selectedStatus;
}
$sql .= ' ORDER BY h.status ASC, h.created_at DESC LIMIT 100';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$historyRows = $stmt->fetchAll();
$pageTitle = 'Delivery History';
include __DIR__ . '/../includes/header.php';
?>
<div class="panel">
    <div class="toolbar" style="margin-bottom: 18px; flex-wrap: wrap; align-items: center;">
        <div>
            <h2>Delivery History</h2>
            <p class="muted">Filter records by h_status tag. This view is read-only.</p>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a class="status-pill <?= $selectedStatus === '' ? 'status-out' : 'secondary-button' ?>" href="<?= url('rider/history.php') ?>">All</a>
            <?php foreach ($allowedStatuses as $status): ?>
                <a class="status-pill status-<?= strtolower(str_replace(' ', '-', $status)) ?>" href="<?= url('rider/history.php?h_status=' . urlencode($status)) ?>" style="<?= $selectedStatus === $status ? 'box-shadow: 0 0 0 3px rgba(15, 94, 138, 0.14);' : '' ?>"><?= e($status) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="route-list">
        <?php if (!$historyRows): ?>
            <p class="muted">No delivery history found for this filter.</p>
        <?php endif; ?>
        <?php foreach ($historyRows as $row): ?>
            <div class="route-item">
                <strong><?= e($row['tracking_number']) ?></strong>
                <div><span class="status-pill status-<?= strtolower(str_replace(' ', '-', $row['status'])) ?>"><?= e($row['status']) ?></span></div>
                <div class="small-note"><?= e($row['created_at']) ?></div>
                <p><?= e($row['remarks'] ?? '-') ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
