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
    
    $status = trim((string) ($_POST['status'] ?? ''));
    $remarks = trim((string) ($_POST['remarks'] ?? ''));
    $allowed = ['Pending', 'Out for Delivery', 'Delivered', 'Failed Delivery'];

    if (in_array($status, $allowed, true)) {
        
        // 🔒 1. 强制拦截：检查是否有选择/上传图片
        if (empty($_FILES['photo']['name']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            flash('error', 'Proof photo is required to update delivery status.');
            redirect('rider/parcel_view.php?id=' . $parcelId);
        }

        // 检查是否已经上传过照片
        $existingPhoto = db()->prepare('SELECT id FROM delivery_photos WHERE parcel_id = ? LIMIT 1');
        $existingPhoto->execute([$parcelId]);
        
        if ($existingPhoto->fetch()) {
            flash('error', 'Only one proof photo is allowed for this parcel.');
            redirect('rider/parcel_view.php?id=' . $parcelId);
        }

        // 处理文件上传
        $upload = upload_delivery_photo($_FILES['photo']);
        if ($upload['ok']) {
            $stmtPhoto = db()->prepare('INSERT INTO delivery_photos (parcel_id, rider_id, photo_path, remarks, created_at) VALUES (?, ?, ?, ?, NOW())');
            $stmtPhoto->execute([$parcelId, $rider['id'], $upload['path'], $remarks]);
        } else {
            flash('error', $upload['error']);
            redirect('rider/parcel_view.php?id=' . $parcelId);
        }

        // 2. 更新包裹状态
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

        // 3. 写入状态变更历史记录
        $stmtHistory = db()->prepare('INSERT INTO parcel_status_history (parcel_id, rider_id, status, remarks, created_at) VALUES (?, ?, ?, ?, NOW())');
        $stmtHistory->execute([$parcelId, $rider['id'], $status, $remarks]);

        flash('success', 'Parcel status and proof photo updated successfully.');
        
        // 🎯 关键修改：提交成功后跳转到 dashboard.php
        redirect('rider/dashboard.php');
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
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h2><?= e($parcel['tracking_number']) ?></h2>
            <a href="<?= url('rider/dashboard.php') ?>" class="secondary-button">&larr; Back to Dashboard</a>
        </div>

        <p><strong>Customer:</strong> <?= e($parcel['customer_name']) ?></p>
        <p><strong>Address:</strong><br><?= nl2br(e($parcel['customer_address'])) ?></p>
        <p><strong>Status:</strong> <span class="status-pill status-<?= strtolower(str_replace(' ', '-', $parcel['status'])) ?>"><?= e($parcel['status']) ?></span></p>

        <hr style="margin: 20px 0; border: none; border-top: 1px solid #eee;">

        <h3>Update Delivery Progress</h3>
        <form method="post" class="stack-form" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            
            <label>Update Status
                <select name="status" id="statusSelect">
                    <?php foreach (['Pending', 'Out for Delivery', 'Delivered', 'Failed Delivery'] as $st): ?>
                        <option value="<?= e($st) ?>" <?= $parcel['status'] === $st ? 'selected' : '' ?>><?= e($st) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>Remarks
                <textarea name="remarks" placeholder="Add optional remarks or reasons..."></textarea>
            </label>

            <label>Upload Proof Photo <span style="color: #ef4444; font-weight: bold;">(Required *)</span>
                <input type="file" id="delivery_photo" name="photo" accept="image/*" capture="environment" required>
            </label>
            
            <img id="cameraPreview" class="camera-preview" alt="Photo preview" style="display: none; max-width: 100%; height: auto; border-radius: 8px; margin-top: 10px; border: 1px solid #cbd5e1;">

            <button class="primary-button" type="submit" style="margin-top: 15px;">Submit Update</button>
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
            <?php if (empty($historyRows)): ?>
                <p class="muted">No history record yet.</p>
            <?php endif; ?>
        </div>

        <h2>Proof Photos</h2>
        <?php if ($photoRows): ?>
            <?php $photo = $photoRows[0]; ?>
            <div class="route-list">
                <div class="route-item">
                    <img src="<?= url($photo['photo_path']) ?>" alt="Proof photo" style="max-width: 100%; height: auto; border-radius: 8px;">
                    <div class="small-note"><?= e($photo['remarks'] ?? '') ?></div>
                </div>
            </div>
        <?php else: ?>
            <p class="muted">No proof photo uploaded yet.</p>
        <?php endif; ?>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const fileInput = document.getElementById('delivery_photo');
    const previewImg = document.getElementById('cameraPreview');

    if (fileInput && previewImg) {
        fileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    previewImg.src = event.target.result;
                    previewImg.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                previewImg.style.display = 'none';
            }
        });
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>