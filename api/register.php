<?php
ob_start();
require_once '../core/bootstrap.php';

// Only handle JSON requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$csrf_token = $input['csrf_token'] ?? '';
$username = $input['username'] ?? '';
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';
$confirm_password = $input['confirm_password'] ?? '';

if (empty($csrf_token)) {
    echo json_encode(['success' => false, 'error' => 'CSRF token is required']);
    exit;
}

validateCSRFToken($csrf_token);

$ip = getRealIP();
checkRateLimit($pdo, $ip, 'register', 5, 15);

// Server-side validation (fallback)
$errors = [];
if (empty($username)) $errors[] = 'Username is required';
if (empty($email)) $errors[] = 'Email is required';
if (empty($password)) $errors[] = 'Password is required';
if ($password !== $confirm_password) $errors[] = 'Passwords do not match';

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

    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] === 443;
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
