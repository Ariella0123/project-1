<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $server = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    bootstrap_database($server);

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function bootstrap_database(PDO $server): void
{
    $server->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

    $check = $server->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?');
    $check->execute([DB_NAME, 'users']);
    if ((int) $check->fetchColumn() > 0) {
        return;
    }

    $schemaFile = __DIR__ . '/../sql/schema.sql';
    if (!is_file($schemaFile)) {
        throw new RuntimeException('Database schema file not found.');
    }

    $schema = file_get_contents($schemaFile);
    if ($schema === false) {
        throw new RuntimeException('Unable to read database schema file.');
    }

    $lines = preg_split('/\r?\n/', $schema) ?: [];
    $filteredLines = [];
    foreach ($lines as $line) {
        $trimmed = ltrim($line);
        if (str_starts_with($trimmed, 'CREATE DATABASE')) {
            continue;
        }
        if (str_starts_with($trimmed, 'USE ')) {
            continue;
        }
        $filteredLines[] = $line;
    }
    $schema = implode(PHP_EOL, $filteredLines);

    $databasePdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    $statements = array_filter(array_map('trim', explode(';', $schema)));
    foreach ($statements as $statement) {
        if ($statement === '' || str_starts_with(ltrim($statement), '--')) {
            continue;
        }

        $databasePdo->exec($statement);
    }
}
