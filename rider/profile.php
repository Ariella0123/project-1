<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('rider');

$user = current_user();
$rider = current_rider();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $vehicle = trim((string) ($_POST['vehicle_info'] ?? ''));
    $plate = trim((string) ($_POST['plate_number'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($name === '' || $email === '') {
        $errors[] = 'Name and email are required.';
    }

    if (!$errors) {
        $stmt = db()->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
        $stmt->execute([$name, $email, $user['id']]);
        $stmt = db()->prepare('UPDATE riders SET phone = ?, vehicle_info = ?, plate_number = ? WHERE user_id = ?');
        $stmt->execute([$phone, $vehicle, $plate, $user['id']]);
        if ($password !== '') {
            $stmt = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        }
        flash('success', 'Profile updated.');
        redirect('rider/profile.php');
    }
}

$stmt = db()->prepare('SELECT u.name, u.email, r.phone, r.vehicle_info, r.plate_number FROM users u INNER JOIN riders r ON r.user_id = u.id WHERE u.id = ? LIMIT 1');
$stmt->execute([$user['id']]);
$profile = $stmt->fetch();
$pageTitle = 'Profile Management';
include __DIR__ . '/../includes/header.php';
?>
<div class="panel">
    <?php if ($errors): ?><div class="alert error"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
    <form method="post" class="stack-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>Full Name<input type="text" name="name" value="<?= e(old('name', $profile['name'] ?? '')) ?>" required></label>
        <label>Email<input type="email" name="email" value="<?= e(old('email', $profile['email'] ?? '')) ?>" required></label>
        <label>Phone<input type="text" name="phone" value="<?= e(old('phone', $profile['phone'] ?? '')) ?>"></label>
        <label>Vehicle Info<input type="text" name="vehicle_info" value="<?= e(old('vehicle_info', $profile['vehicle_info'] ?? '')) ?>"></label>
        <label>Plate Number<input type="text" name="plate_number" value="<?= e(old('plate_number', $profile['plate_number'] ?? '')) ?>"></label>
        <label>New Password<input type="password" name="password" placeholder="Leave blank to keep current password"></label>
        <button class="primary-button" type="submit">Save Profile</button>
    </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
