<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$pageTitle = 'Reports';
$summary = [
    'delivered' => count_value("SELECT COUNT(*) FROM parcels WHERE status = 'Delivered'"),
    'failed' => count_value("SELECT COUNT(*) FROM parcels WHERE status = 'Failed Delivery'"),
    'pending' => count_value("SELECT COUNT(*) FROM parcels WHERE status = 'Pending'"),
];
$rows = db()->query('SELECT p.tracking_number, p.customer_name, p.status, p.created_at, p.delivered_at, u.name AS rider_name FROM parcels p LEFT JOIN riders r ON r.id = p.assigned_rider_id LEFT JOIN users u ON u.id = r.user_id ORDER BY p.created_at DESC LIMIT 25')->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<div class="card-grid">
    <div class="card stat-card"><span>Delivered</span><strong><?= (int) $summary['delivered'] ?></strong></div>
    <div class="card stat-card"><span>Pending</span><strong><?= (int) $summary['pending'] ?></strong></div>
    <div class="card stat-card"><span>Failed</span><strong><?= (int) $summary['failed'] ?></strong></div>
    <div class="card stat-card"><span>Active riders</span><strong><?= count_value("SELECT COUNT(*) FROM riders WHERE current_status = 'online'") ?></strong></div>
</div>

<div class="panel">
    <h2>Parcel Report</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Tracking</th><th>Customer</th><th>Status</th><th>Rider</th><th>Created</th><th>Delivered</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['tracking_number']) ?></td>
                    <td><?= e($row['customer_name']) ?></td>
                    <td><?= e($row['status']) ?></td>
                    <td><?= e($row['rider_name'] ?? '-') ?></td>
                    <td><?= e($row['created_at']) ?></td>
                    <td><?= e($row['delivered_at'] ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
