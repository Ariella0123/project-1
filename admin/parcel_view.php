<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$parcelId = int_or_null($_GET['id'] ?? null);
$stmt = db()->prepare('SELECT p.*, u.name AS rider_name FROM parcels p LEFT JOIN riders r ON r.id = p.assigned_rider_id LEFT JOIN users u ON u.id = r.user_id WHERE p.id = ? LIMIT 1');
$stmt->execute([$parcelId]);
$parcel = $stmt->fetch();
if (!$parcel) {
    flash('error', 'Parcel not found.');
    redirect('admin/parcels.php');
}

$history = db()->prepare('SELECT h.*, u.name AS rider_name FROM parcel_status_history h LEFT JOIN riders r ON r.id = h.rider_id LEFT JOIN users u ON u.id = r.user_id WHERE h.parcel_id = ? ORDER BY h.created_at DESC');
$history->execute([$parcelId]);
$historyRows = $history->fetchAll();
$photos = db()->prepare('SELECT p.*, u.name AS rider_name FROM delivery_photos p LEFT JOIN riders r ON r.id = p.rider_id LEFT JOIN users u ON u.id = r.user_id WHERE p.parcel_id = ? ORDER BY p.created_at DESC');
$photos->execute([$parcelId]);
$photoRows = $photos->fetchAll();

$pageTitle = 'Parcel Details';
include __DIR__ . '/../includes/header.php';
?>
<div class="layout-grid">
    <section class="panel">
        <h2><?= e($parcel['tracking_number']) ?></h2>
        <p class="muted"><?= e($parcel['customer_name']) ?> · <?= e($parcel['customer_phone'] ?? '-') ?></p>
        <p><?= nl2br(e($parcel['customer_address'])) ?></p>
        <p><strong>Status:</strong> <span class="status-pill status-<?= strtolower(str_replace(' ', '-', $parcel['status'])) ?>"><?= e($parcel['status']) ?></span></p>
        <p><strong>Assigned rider:</strong> <?= e($parcel['rider_name'] ?? 'Unassigned') ?></p>
        <p><strong>Notes:</strong> <?= e($parcel['notes'] ?? '-') ?></p>
        <div class="inline-actions">
            <a class="secondary-button" href="<?= url('admin/parcel_form.php?id=' . (int) $parcel['id']) ?>">Edit Parcel</a>
            <a class="secondary-button" href="<?= url('admin/parcels.php') ?>">Back to list</a>
        </div>
    </section>

    <section class="panel">
        <h2>Status History</h2>
        <div class="route-list">
            <?php foreach ($historyRows as $row): ?>
                <div class="route-item">
                    <strong><?= e($row['status']) ?></strong>
                    <div class="small-note"><?= e($row['rider_name'] ?? 'System') ?> · <?= e($row['created_at']) ?></div>
                    <p><?= e($row['remarks'] ?? '-') ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<section class="panel">
    <h2>Delivery Proof Photos</h2>
    <div class="card-grid" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
        <?php foreach ($photoRows as $photo): ?>
            <div class="card">
                <img src="<?= url($photo['photo_path']) ?>" alt="Proof photo">
                <p><?= e($photo['remarks'] ?? '') ?></p>
                <div class="small-note"><?= e($photo['rider_name'] ?? 'Unknown rider') ?> · <?= e($photo['created_at']) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
