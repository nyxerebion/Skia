<?php
// database/connection.php

// Detect environment
$is_local = (
    (isset($_SERVER['SERVER_NAME']) && in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1'])) ||
    (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] === '127.0.0.1') ||
    php_sapi_name() === 'cli'
);

// Load .env ONLY if NOT local
if (!$is_local && file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

// Session settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_lifetime', 0);
    ini_set('expose_php', 0);
    session_start();
}

// Error reporting
if ($is_local) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}

// Database - local uses hardcoded, production uses .env
if ($is_local) {
    $host = 'localhost';
    $dbname = 'skiadb';
    $username = 'root';
    $password = '';
} else {
    $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';
    $dbname = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'skiadb';
    $username = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root';
    $password = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '';
}

try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ];
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, $options);
} catch (PDOException $e) {
    if ($is_local) {
        die("Database connection failed: " . $e->getMessage());
    } else {
        error_log("Database connection failed: " . $e->getMessage());
        die("Database connection failed. Please try again later.");
    }
}
?>