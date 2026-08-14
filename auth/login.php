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

<style>
/* 一键填充按钮样式 */
.demo-accounts {
    margin-top: 16px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.demo-account-btn {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 10px 14px;
    background-color: #f1f5f9;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 13px;
    color: #334155;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: left;
}
.demo-account-btn:hover {
    background-color: #e2e8f0;
    border-color: #94a3b8;
    color: #0f172a;
}
.demo-account-btn strong {
    color: #0f172a;
}
.demo-account-btn .fill-badge {
    font-size: 11px;
    background-color: #e0f2fe;
    color: #0369a1;
    padding: 2px 8px;
    border-radius: 12px;
    font-weight: 600;
}
</style>

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
                <input type="email" id="login_email" name="email" value="<?= e(old('email')) ?>" required>
            </label>
            <label>
                Password
                <input type="password" id="login_password" name="password" required>
            </label>
            <button class="primary-button" type="submit">Login</button>
        </form>

        <!-- 一键填充按钮区域 -->
        <div class="demo-accounts">
            <button type="button" class="demo-account-btn" onclick="fillCredentials('admin@example.com', 'admin123')">
                <span><strong>Admin:</strong> admin@example.com</span>
                <span class="fill-badge">Click to fill</span>
            </button>
            <button type="button" class="demo-account-btn" onclick="fillCredentials('rider@example.com', 'rider123')">
                <span><strong>Rider:</strong> rider@example.com</span>
                <span class="fill-badge">Click to fill</span>
            </button>
        </div>
    </div>
</div>

<script>
// 自动填充账号和密码函数
function fillCredentials(email, password) {
    const emailInput = document.getElementById('login_email');
    const passwordInput = document.getElementById('login_password');

    if (emailInput && passwordInput) {
        emailInput.value = email;
        passwordInput.value = password;
        
        // 自动将焦点移到 Login 按钮上，体验更好
        emailInput.focus();
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>