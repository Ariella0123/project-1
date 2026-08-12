<?php
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    $user = current_user();
    redirect($user && $user['role'] === 'admin' ? 'admin/dashboard.php' : 'rider/dashboard.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $stmt = db()->prepare('SELECT id, name, email, password_hash, role, status FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && $user['status'] === 'active' && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = (int) $user['id'];
        session_regenerate_id(true);
        add_activity((int) $user['id'], 'login', 'User logged in successfully');
        redirect($user['role'] === 'admin' ? 'admin/dashboard.php' : 'rider/dashboard.php');
    }

    $error = 'Invalid login details or inactive account.';
    set_old(['email' => $email]);
}

$pageTitle = 'Login';
include __DIR__ . '/../includes/header.php';
?>
<div class="auth-grid">
    <div class="auth-panel hero-panel">
        <p class="eyebrow">Courier operations</p>
        <h2>Track parcels, manage riders, and keep deliveries moving in real time.</h2>
        <p>Native PHP, MySQL, AJAX, and browser geolocation combined in a clean operational dashboard.</p>
        <ul class="feature-list">
            <li>Real-time rider location tracking</li>
            <li>Parcel assignment and status workflow</li>
            <li>Mobile camera proof upload</li>
        </ul>
    </div>
    <div class="auth-panel form-panel">
        <h2>Sign in</h2>
        <p class="muted">Use your assigned role account to continue.</p>
        <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
        <form method="post" class="stack-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label>
                Email
                <input type="email" name="email" value="<?= e(old('email')) ?>" required>
            </label>
            <label>
                Password
                <input type="password" name="password" required>
            </label>
            <button class="primary-button" type="submit">Login</button>
        </form>
        <div class="seed-note">
            <p><strong>Admin:</strong> admin@example.com / admin123</p>
            <p><strong>Rider:</strong> rider@example.com / rider123</p>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
