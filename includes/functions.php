<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function app_base_url(): string
{
    if (defined('BASE_URL') && BASE_URL !== '') {
        return rtrim(BASE_URL, '/');
    }

    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    if ($scriptName === '') {
        return '';
    }

    $path = str_replace('\\', '/', dirname($scriptName));
    if ($path === '/' || $path === '.' || $path === '\\') {
        return '';
    }

    $segments = array_values(array_filter(explode('/', trim($path, '/')), static fn ($segment) => $segment !== ''));
    if (!$segments) {
        return '';
    }

    return '/' . $segments[0];
}

function url(string $path = ''): string
{
    $base = app_base_url();

    if ($base === '') {
        return '/' . ltrim($path, '/');
    }

    return $base . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    static $cachedUser = null;
    if (is_array($cachedUser) && (int) $cachedUser['id'] === (int) $_SESSION['user_id']) {
        return $cachedUser;
    }

    $stmt = db()->prepare('SELECT id, name, email, role, status FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    $cachedUser = $user ?: null;
    return $cachedUser;
}

function current_rider(): ?array
{
    $user = current_user();
    if (!$user || $user['role'] !== 'rider') {
        return null;
    }

    static $cachedRider = null;
    if (is_array($cachedRider) && (int) $cachedRider['user_id'] === (int) $user['id']) {
        return $cachedRider;
    }

    $stmt = db()->prepare('SELECT * FROM riders WHERE user_id = ? LIMIT 1');
    $stmt->execute([$user['id']]);
    $rider = $stmt->fetch();

    $cachedRider = $rider ?: null;
    return $cachedRider;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect('auth/login.php');
    }
}

function require_role(string $role): void
{
    require_login();
    $user = current_user();
    if (!$user || $user['role'] !== $role) {
        http_response_code(403);
        exit('Access denied');
    }
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }

    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }

    $value = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);
    return $value;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals(csrf_token(), (string) $token)) {
        http_response_code(419);
        exit('Invalid CSRF token');
    }
}

function old(string $key, string $default = ''): string
{
    return isset($_SESSION['old'][$key]) ? (string) $_SESSION['old'][$key] : $default;
}

function set_old(array $values): void
{
    $_SESSION['old'] = $values;
}

function clear_old(): void
{
    unset($_SESSION['old']);
}

function add_activity(int $userId, string $action, string $details = ''): void
{
    $stmt = db()->prepare('INSERT INTO activity_logs (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())');
    $stmt->execute([$userId, $action, $details]);
}

function random_tracking_number(): string
{
    return 'TRK-' . strtoupper(bin2hex(random_bytes(4))) . '-' . date('ymdHis');
}

function upload_delivery_photo(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Photo upload failed.'];
    }

    $maxSize = 5 * 1024 * 1024;
    if (($file['size'] ?? 0) > $maxSize) {
        return ['ok' => false, 'error' => 'Photo must be 5MB or smaller.'];
    }

    $mimeType = mime_content_type($file['tmp_name']);
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mimeType, $allowed, true)) {
        return ['ok' => false, 'error' => 'Only JPG, PNG, or WEBP files are allowed.'];
    }

    if (!is_dir(UPLOAD_PATH)) {
        mkdir(UPLOAD_PATH, 0775, true);
    }

    $extension = match ($mimeType) {
        'image/png' => 'png',
        'image/webp' => 'webp',
        default => 'jpg',
    };

    $filename = date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $destination = rtrim(UPLOAD_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

    if (function_exists('imagecreatetruecolor')) {
        $sourceImage = match ($mimeType) {
            'image/png' => function_exists('imagecreatefrompng') ? imagecreatefrompng($file['tmp_name']) : null,
            'image/webp' => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($file['tmp_name']) : null,
            default => function_exists('imagecreatefromjpeg') ? imagecreatefromjpeg($file['tmp_name']) : null,
        };

        if ($sourceImage) {
            $width = imagesx($sourceImage);
            $height = imagesy($sourceImage);
            $targetWidth = $width > 1600 ? 1600 : $width;
            $targetHeight = (int) round($height * ($targetWidth / $width));
            $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);

            if ($extension === 'png') {
                imagealphablending($targetImage, false);
                imagesavealpha($targetImage, true);
            }

            imagecopyresampled($targetImage, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

            $saved = match ($extension) {
                'png' => function_exists('imagepng') ? imagepng($targetImage, $destination, 6) : false,
                'webp' => function_exists('imagewebp') ? imagewebp($targetImage, $destination, 82) : false,
                default => function_exists('imagejpeg') ? imagejpeg($targetImage, $destination, 82) : false,
            };

            imagedestroy($sourceImage);
            imagedestroy($targetImage);

            if ($saved) {
                return ['ok' => true, 'path' => 'uploads/photos/' . $filename];
            }
        }
    }

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['ok' => false, 'error' => 'Could not save uploaded file.'];
    }

    return ['ok' => true, 'path' => 'uploads/photos/' . $filename];
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

function int_or_null(mixed $value): ?int
{
    if ($value === null || $value === '') {
        return null;
    }

    return (int) $value;
}

function count_value(string $sql, array $params = []): int
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}
