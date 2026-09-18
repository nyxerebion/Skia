<?php
require_once __DIR__ . '/../../core/bootstrap.php';

header('Content-Type: application/json');

if (!checkLogin()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$csrf_token = $input['csrf_token'] ?? '';
$current_password = $input['current_password'] ?? '';
$new_password = $input['new_password'] ?? '';

validateCSRFToken($csrf_token);

$user_id = $_SESSION['user_id'];
$ip = getRealIP();
$action = 'password_change';
$errors = [];

// Check rate limit first
checkRateLimit($pdo, $ip, $action, 5, 15);

// Validate current password
if (empty($current_password)) {
    $errors[] = "Current password is required";
}

// Validate new password
if (empty($new_password)) {
    $errors[] = "New password is required";
} elseif (strlen($new_password) < 6) {
    $errors[] = "New password must be at least 6 characters";
} elseif (strlen($new_password) > 100) {
    $errors[] = "New password must be at most 100 characters";
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// Get current hashed password
$stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$hashed = $stmt->fetchColumn();

if (!$hashed) {
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

// Verifiy current password
if (!password_verify($current_password, $hashed)) {
    // Record failed attempt
    recordRateLimitAttempt($pdo, $ip, $action);

    echo json_encode(['success' => false, 'error' => 'Current password is incorrect']);
    exit;
}

// Prevent same password
if (password_verify($new_password, $hashed)) {
    echo json_encode(['success' => false, 'error' => 'New password cannot be the same as your current password']);
    exit;
}

// Update password
$new_hashed = password_hash($new_password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("
    UPDATE users 
    SET password = ?, password_updated_at = NOW()
    WHERE id = ?
");
$stmt->execute([$new_hashed, $user_id]);

// Clear rate limit on success
clearRateLimit($pdo, $ip, $action);

logAction('Password updated successfully');

addNotification(
    $user_id,
    'success',
    'Password Changed',
    'Your password was changed successfully.',
    SITE_URL . '/pages/settings.php?tab=account',
    'system',
    null
);

echo json_encode(['success' => true]);