<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('rider');

$rider = current_rider();
$pageTitle = 'Rider Dashboard';
$assigned = db()->prepare('SELECT p.id, p.tracking_number, p.customer_name, p.customer_address, p.status, p.created_at FROM parcels p INNER JOIN parcel_status_history h ON p.id = h.parcel_id WHERE p.assigned_rider_id = ? AND p.status = "Pending" ORDER BY p.id DESC LIMIT 6');
$assigned->execute([$rider['id']]);
$parcels = $assigned->fetchAll();
$historyCount = count_value('SELECT COUNT(*) FROM parcel_status_history WHERE rider_id = ?', [$rider['id']]);
include __DIR__ . '/../includes/header.php';
?>
<div class="card-grid">
    <div class="card stat-card"><span>Assigned Parcels</span><strong><?= count($parcels) ?></strong></div>
    <div class="card stat-card"><span>History Records</span><strong><?= (int) $historyCount ?></strong></div>
    <div class="card stat-card"><span>GPS Status</span><strong id="riderStatusLabel"><?= e(ucfirst($rider['current_status'])) ?></strong></div>
    <div class="card stat-card"><span>Last Location</span><strong><?= e($rider['last_location_at'] ?? 'No update') ?></strong></div>
</div>

<div class="layout-grid">
    <section class="panel">
        <div class="toolbar">
            <div>
                <h2>Work Status</h2>
                <p class="muted">Keep the app open while you are on shift to send regular GPS updates.</p>
            </div>
            <div class="switch <?= $rider['current_status'] === 'online' ? 'active' : '' ?>">
                <span class="status-dot"></span>
                <button class="secondary-button" type="button" data-rider-status-toggle data-active="<?= $rider['current_status'] === 'online' ? '1' : '0' ?>">
                    <?= $rider['current_status'] === 'online' ? 'Go Offline' : 'Go Online' ?>
                </button>
            </div>
        </div>
        <p class="small-note">Current status: <span class="status-pill <?= $rider['current_status'] === 'online' ? 'status-online' : 'status-offline' ?>" data-rider-status-pill><?= e(ucfirst($rider['current_status'])) ?></span></p>
        <div class="route-list">
            <div class="route-item">
                <strong>Location tracking</strong>
                <div class="small-note">Latitude and longitude are stored every 10-30 seconds when online.</div>
            </div>
            <div class="route-item">
                <strong>Camera proof</strong>
                <div class="small-note">Use the parcel detail screen to capture and upload proof photos.</div>
            </div>
        </div>
    </section>

    <section class="panel">
        <h2>Assigned Parcels</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Tracking</th><th>Customer</th><th>Status</th><th>Action</th></tr></thead>
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
    </section>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
