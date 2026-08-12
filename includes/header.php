<?php
require_once __DIR__ . '/functions.php';
$user = current_user();
$pageTitle = $pageTitle ?? APP_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
    <script defer src="<?= url('assets/js/app.js') ?>"></script>
    <script defer src="<?= url('assets/js/tracking.js') ?>"></script>
</head>
<body data-user-role="<?= e($user['role'] ?? 'guest') ?>" data-page="<?= $user ? 'app' : 'auth' ?>" data-base-url="<?= e(app_base_url()) ?>">
<div class="app-shell">
    <?php if ($user): ?>
        <?php include __DIR__ . '/sidebar.php'; ?>
    <?php endif; ?>
    <main class="app-main">
        <header class="topbar">
            <div>
                <p class="eyebrow"><?= e(APP_NAME) ?></p>
                <h1><?= e($pageTitle) ?></h1>
            </div>
            <div class="topbar-actions">
                <?php if ($user): ?>
                    <span class="user-pill"><?= e($user['name']) ?> · <?= e(ucfirst($user['role'])) ?></span>
                    <a class="link-button" href="<?= url('auth/logout.php') ?>">Logout</a>
                <?php endif; ?>
            </div>
        </header>
        <section class="content-area">
            <?php if ($success = flash('success')): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
            <?php if ($error = flash('error')): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
