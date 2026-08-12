<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('rider');

$rider = current_rider();
$stmt = db()->prepare('SELECT h.status, h.remarks, h.created_at, p.tracking_number FROM parcel_status_history h INNER JOIN parcels p ON p.id = h.parcel_id WHERE h.rider_id = ? ORDER BY h.created_at DESC LIMIT 100');
$stmt->execute([$rider['id']]);
$historyRows = $stmt->fetchAll();
$pageTitle = 'Delivery History';
include __DIR__ . '/../includes/header.php';
?>
<div class="panel">
    <div class="route-list">
        <?php foreach ($historyRows as $row): ?>
            <div class="route-item">
                <strong><?= e($row['tracking_number']) ?> · <?= e($row['status']) ?></strong>
                <div class="small-note"><?= e($row['created_at']) ?></div>
                <p><?= e($row['remarks'] ?? '-') ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
