<?php
// bootstrap.php

use Dotenv\Dotenv;
use Hashids\Hashids;

$project_root = dirname(__DIR__);
$autoload_path = $project_root . '/vendor/autoload.php';

if (file_exists($autoload_path)) {
    require_once $autoload_path;
}

if (file_exists($project_root . '/.env') && class_exists(Dotenv::class)) {
    $dotenv = Dotenv::createImmutable($project_root);
    $dotenv->safeLoad();
}

// 1. Session settings
ini_set('session.cache_limiter', 'nocache');
ini_set('session.cache_expire', '0');
ini_set('session.cookie_lifetime', 0);
ini_set('expose_php', 0);

// 2. START SESSION HERE
if (session_status() === PHP_SESSION_NONE) {
    // Detect if we are on HTTPS (important for the 'secure' flag)
    $is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $is_https, // Only send cookie over HTTPS
        'httponly' => true,    // Prevent JavaScript access (XSS protection)
        'samesite' => 'Lax',   // CSRF protection
    ]);

    // Prevent session fixation
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_start();
}

// 3. Start output buffering to prevent header issues
ob_start();

// 4. Load connection (database only, no session)
require_once __DIR__ . '/../database/connection.php';

// 5. Send headers immediately after session start, before any output
require_once __DIR__ . '/../security/headers.php';

// 6. Rest of includes
global $pdo;
require_once __DIR__ . '/../backend/auth-helpers.php';
require_once __DIR__ . '/../backend/helpers.php';
require_once __DIR__ . '/../backend/logging.php';
require_once __DIR__ . '/../security/rate-limiting.php';
require_once __DIR__ . '/../security/functions.php';
require_once __DIR__ . '/../security/input-sanitization.php';
require_once __DIR__ . '/../security/input-validation.php';

require_once __DIR__ . '/../backend/config.php';
$GLOBALS['icons'] = $icons;
$GLOBALS['badge_labels'] = $badge_labels;

date_default_timezone_set('Asia/Manila');

// Detect environment
$is_local = ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_ADDR'] === '127.0.0.1');

if ($is_local) {
    define('SITE_URL', 'http://localhost/skia');
} else {
    define('SITE_URL', 'https://skia.unaux.com');
}

require_once __DIR__ . '/../backend/config/click-functions.php';
require_once __DIR__ . '/../backend/config/enemies.php';
require_once __DIR__ . '/../backend/config/levels.php';

require_once __DIR__ . '/../backend/config/whack-functions.php';

$hashids = new Hashids($_ENV['HASHIDS_SALT'] ?? '');

if (isset($_SESSION['user_id'])) {
    updateUserActivity($_SESSION['user_id']);
}