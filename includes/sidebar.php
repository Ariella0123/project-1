<aside class="sidebar">
    <div class="brand-block">
        <div class="brand-mark">PD</div>
        <div>
            <strong>Parcel Delivery</strong>
            <span>Management System</span>
        </div>
    </div>
    <nav class="sidebar-nav">
        <?php if ($user && $user['role'] === 'admin'): ?>
            <a href="<?= url('admin/dashboard.php') ?>">Dashboard</a>
            <a href="<?= url('admin/riders.php') ?>">Riders</a>
            <a href="<?= url('admin/parcels.php') ?>">Parcels</a>
            <a href="<?= url('admin/tracking.php') ?>">Live Tracking</a>
            <a href="<?= url('admin/reports.php') ?>">Reports</a>
            <a href="<?= url('admin/logs.php') ?>">Activity Logs</a>
        <?php elseif ($user && $user['role'] === 'rider'): ?>
            <a href="<?= url('rider/dashboard.php') ?>">Dashboard</a>
            <a href="<?= url('rider/parcels.php') ?>">Assigned Parcels</a>
            <a href="<?= url('rider/history.php') ?>">Delivery History</a>
            <a href="<?= url('rider/profile.php') ?>">Profile</a>
        <?php endif; ?>
    </nav>
</aside>
