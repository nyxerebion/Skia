<?php
// bootstrap.php

// 1. Session settings
ini_set('session.cache_limiter', 'nocache');
ini_set('session.cache_expire', '0');
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', 1);

// 2. Start output buffering to prevent header issues
ob_start();

// 3. Load connection (starts session)
require_once __DIR__ . '/../database/connection.php';

// 4. Send headers immediately after session start, before any output
require_once __DIR__ . '/../security/headers.php';

// 5. Rest of includes
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

// 6. Optional: flush headers but keep buffer for page output
// ob_end_flush() called at end of each page script
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