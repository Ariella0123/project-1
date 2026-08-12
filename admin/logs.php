<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$pageTitle = 'Activity Logs';
$logs = db()->query('SELECT a.action, a.details, a.created_at, u.name FROM activity_logs a INNER JOIN users u ON u.id = a.user_id ORDER BY a.id DESC LIMIT 100')->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<div class="panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>User</th><th>Action</th><th>Details</th><th>Time</th></tr></thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= e($log['name']) ?></td>
                    <td><?= e($log['action']) ?></td>
                    <td><?= e($log['details'] ?? '-') ?></td>
                    <td><?= e($log['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
