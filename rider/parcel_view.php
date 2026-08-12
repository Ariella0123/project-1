<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('rider');

$rider = current_rider();
$parcelId = int_or_null($_GET['id'] ?? null);
$stmt = db()->prepare('SELECT * FROM parcels WHERE id = ? AND assigned_rider_id = ? LIMIT 1');
$stmt->execute([$parcelId, $rider['id']]);
$parcel = $stmt->fetch();
if (!$parcel) {
    flash('error', 'Parcel not assigned to you.');
    redirect('rider/parcels.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'status_update') {
        $status = trim((string) ($_POST['status'] ?? ''));
        $remarks = trim((string) ($_POST['remarks'] ?? ''));

        $allowed = ['Pending', 'Out for Delivery', 'Failed Delivery'];
        if (in_array($status, $allowed, true)) {
            $setFailedReason = $status === 'Failed Delivery' ? ', failed_reason = ?' : ', failed_reason = NULL';
            $sql = 'UPDATE parcels SET status = ?' . $setFailedReason . ' WHERE id = ? AND assigned_rider_id = ?';
            $stmt = db()->prepare($sql);
            $params = [$status];
            if ($status === 'Failed Delivery') {
                $params[] = $remarks !== '' ? $remarks : 'Failed delivery';
            }
            $params[] = $parcelId;
            $params[] = $rider['id'];
            $stmt->execute($params);

            if ($status !== 'Pending') {
                $stmt = db()->prepare('INSERT INTO parcel_status_history (parcel_id, rider_id, status, remarks, created_at) VALUES (?, ?, ?, ?, NOW())');
                $stmt->execute([$parcelId, $rider['id'], $status, $remarks]);
            }
            flash('success', 'Parcel status updated.');
            redirect('rider/parcel_view.php?id=' . $parcelId);
        }
    }

    if ($action === 'photo_upload') {
        if (!empty($_FILES['photo'])) {
            $existingPhoto = db()->prepare('SELECT id FROM delivery_photos WHERE parcel_id = ? LIMIT 1');
            $existingPhoto->execute([$parcelId]);
            if ($existingPhoto->fetch()) {
                flash('error', 'Only one proof photo is allowed for this parcel.');
                redirect('rider/parcel_view.php?id=' . $parcelId);
            }

            $upload = upload_delivery_photo($_FILES['photo']);
            if ($upload['ok']) {
                $stmt = db()->prepare('INSERT INTO delivery_photos (parcel_id, rider_id, photo_path, remarks, created_at) VALUES (?, ?, ?, ?, NOW())');
                $stmt->execute([$parcelId, $rider['id'], $upload['path'], trim((string) ($_POST['remarks'] ?? ''))]);
                flash('success', 'Delivery proof uploaded.');
                redirect('rider/parcel_view.php?id=' . $parcelId);
            }
            flash('error', $upload['error']);
            redirect('rider/parcel_view.php?id=' . $parcelId);
        }
    }
}

$history = db()->prepare('SELECT * FROM parcel_status_history WHERE parcel_id = ? ORDER BY created_at DESC');
$history->execute([$parcelId]);
$historyRows = $history->fetchAll();
$photos = db()->prepare('SELECT * FROM delivery_photos WHERE parcel_id = ? ORDER BY created_at DESC LIMIT 1');
$photos->execute([$parcelId]);
$photoRows = $photos->fetchAll();
$pageTitle = 'Parcel ' . $parcel['tracking_number'];
include __DIR__ . '/../includes/header.php';
?>
<div class="layout-grid">
    <section class="panel">
        <h2><?= e($parcel['tracking_number']) ?></h2>
        <p><strong>Customer:</strong> <?= e($parcel['customer_name']) ?></p>
        <p><strong>Address:</strong><br><?= nl2br(e($parcel['customer_address'])) ?></p>
        <p><strong>Status:</strong> <span class="status-pill status-<?= strtolower(str_replace(' ', '-', $parcel['status'])) ?>"><?= e($parcel['status']) ?></span></p>

        <h3>Update Status</h3>
        <form method="post" class="stack-form" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="status_update">
            <label>Status
                <select name="status">
                    <?php foreach (['Pending', 'Out for Delivery', 'Failed Delivery'] as $status): ?>
                        <option value="<?= e($status) ?>"><?= e($status) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Remarks<textarea name="remarks"></textarea></label>
            <!-- <div class="card-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                <label>Latitude<input type="text" name="latitude" value="<?= e((string) ($rider['latitude'] ?? '')) ?>"></label>
                <label>Longitude<input type="text" name="longitude" value="<?= e((string) ($rider['longitude'] ?? '')) ?>"></label>
            </div> -->
            

        <h3>Upload Proof Photo</h3>
        
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="photo_upload">
            <label>Remarks<input type="text" name="remarks"></label>
            <label>Photo<input type="file" id="delivery_photo" name="photo" accept="image/*" capture="environment" required></label>
            <img id="cameraPreview" class="camera-preview" hidden alt="Photo preview">
            <button class="primary-button" type="submit" ">Upload Proof</button>
        </form>
    </section>

    <section class="panel">
        <h2>Status History</h2>
        <div class="route-list">
            <?php foreach ($historyRows as $row): ?>
                <div class="route-item">
                    <strong><?= e($row['status']) ?></strong>
                    <div class="small-note"><?= e($row['created_at']) ?></div>
                    <p><?= e($row['remarks'] ?? '-') ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <h2>Proof Photos</h2>
        <?php if ($photoRows): ?>
            <?php $photo = $photoRows[0]; ?>
            <div class="route-list">
                <div class="route-item">
                    <img src="<?= url($photo['photo_path']) ?>" alt="Proof photo">
                    <div class="small-note"><?= e($photo['remarks'] ?? '') ?></div>
                </div>
            </div>
        <?php else: ?>
            <p class="muted">No proof photo uploaded yet.</p>
        <?php endif; ?>
    </section>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
