<?php
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    $user = current_user();
    redirect($user && $user['role'] === 'admin' ? 'admin/dashboard.php' : 'rider/dashboard.php');
}

redirect('auth/login.php');
