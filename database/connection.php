<?php
// database/connection.php

// Detect environment
$is_local = (
    (isset($_SERVER['SERVER_NAME']) && in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1'])) ||
    (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] === '127.0.0.1') ||
    php_sapi_name() === 'cli'
);

// Database - local uses hardcoded, production uses .env
if ($is_local) {
    $host = 'localhost';
    $dbname = 'skiadb';
    $username = 'root';
    $password = '';
} else {
    $host = $_ENV['DB_HOST'] ?? $_ENV('DB_HOST') ?: 'localhost';
    $dbname = $_ENV['DB_NAME'] ?? $_ENV('DB_NAME') ?: 'skiadb';
    $username = $_ENV['DB_USER'] ?? $_ENV('DB_USER') ?: 'root';
    $password = $_ENV['DB_PASS'] ?? $_ENV('DB_PASS') ?: '';
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