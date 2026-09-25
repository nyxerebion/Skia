<?php
ob_start();
require_once '../core/bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$csrf_token = $input['csrf_token'] ?? '';
$username = trim($input['username'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$confirm_password = $input['confirm_password'] ?? '';

if (empty($csrf_token)) {
    echo json_encode(['success' => false, 'error' => 'CSRF token is required']);
    exit;
}

validateCSRFToken($csrf_token);

$ip = getRealIP();
checkRateLimit($pdo, $ip, 'register', 5, 15);

// Server-side validation
$errors = [];

if ($username === '') {
    $errors[] = 'Username is required';
} else {
    if (strlen($username) < 4 || strlen($username) > 12) {
        $errors[] = 'Username must be 4-12 characters';
    }
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Username can only contain letters, numbers, and underscores';
    }
    if (substr_count($username, '_') > 1) {
        $errors[] = 'Username can have at most one underscore';
    }
    if (preg_match_all('/[a-zA-Z]/', $username) < 2) {
        $errors[] = 'Username must contain at least 2 letters';
    }
    if (preg_match_all('/[0-9]/', $username) < 1) {
        $errors[] = 'Username must contain at least 1 number';
    }
}

if ($email === '') {
    $errors[] = 'Email is required';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format';
} elseif (strlen($email) > 100) {
    $errors[] = 'Email must be at most 100 characters';
}

if ($password === '') {
    $errors[] = 'Password is required';
} elseif (strlen($password) < 6) {
    $errors[] = 'Password must be at least 6 characters';
} elseif (strlen($password) > 100) {
    $errors[] = 'Password must be at most 100 characters';
}

if ($password !== $confirm_password) {
    $errors[] = 'Passwords do not match';
}

if (!empty($errors)) {
    recordRateLimitAttempt($pdo, $ip, 'register');
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);

    if ($stmt->rowCount() > 0) {
        recordRateLimitAttempt($pdo, $ip, 'register');
        echo json_encode(['success' => false, 'error' => 'Username or email already exists']);
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$username, $email, $hashed_password]);
    $user_id = $pdo->lastInsertId();

    // Auto-login
    $token = bin2hex(random_bytes(32));
    $hashed_token = password_hash($token, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
    $stmt->execute([$hashed_token, $user_id]);

    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['SERVER_PORT'] ?? '') === '443';
    setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', $secure, true);
    setcookie('user_id', $user_id, time() + (30 * 24 * 60 * 60), '/', '', $secure, true);

    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['role'] = 'user';
    secure_session_regenerate();

    addNotification(
        $user_id,
        'success',
        '🎉 Welcome to Skia!',
        'Your account has been successfully created. Start exploring and connecting with the community!',
        SITE_URL . '/index.php',
        'system',
        null
    );

    logAction("New user registered: $username");
    clearRateLimit($pdo, $ip, 'register');

    echo json_encode(['success' => true, 'message' => 'Registration successful! Welcome to Skia!']);
} catch (PDOException $e) {
    recordRateLimitAttempt($pdo, $ip, 'register');
    error_log("Registration error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Registration failed. Please try again later.']);
}