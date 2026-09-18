<?php
require_once __DIR__ . '/../../core/bootstrap.php';

header('Content-Type: application/json');

if (!checkLogin()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$csrf_token = $input['csrf_token'] ?? '';
$username = $input['username'] ?? '';

validateCSRFToken($csrf_token);

// ✅ Validate username format
$errors = [];

if (strlen($username) < 4) {
    $errors[] = "Username must be at least 4 characters";
}
if (strlen($username) > 12) {
    $errors[] = "Username must be at most 12 characters";
}
if (preg_match('/[^a-zA-Z0-9_]/', $username)) {
    $errors[] = "Username can only contain letters, numbers, and underscores";
}
if (preg_match_all('/[a-zA-Z]/', $username) < 2) {
    $errors[] = "Username must contain at least 2 letters";
}
if (preg_match_all('/[0-9]/', $username) < 1) {
    $errors[] = "Username must contain at least 1 number";
}
if (substr_count($username, '_') > 1) {
    $errors[] = "Username can contain at most 1 underscore";
}

if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'errors' => $errors
    ]);
    exit;
}

// Get current user's username
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$current_username = $stmt->fetchColumn();

// If username is same as current
if (strtolower($username) === strtolower($current_username)) {
    echo json_encode([
        'success' => true,
        'exist' => false,
        'same' => true,
        'message' => 'This is your current username'
    ]);
    exit;
}

// Check if username exists (excluding current user)
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM users 
    WHERE LOWER(username) = LOWER(?) AND id != ?
");
$stmt->execute([$username, $_SESSION['user_id']]);
$exist = $stmt->fetchColumn() > 0;

echo json_encode([
    'success' => true,
    'exist' => $exist,
    'same' => false
]);