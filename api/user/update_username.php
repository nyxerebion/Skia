<?php
require_once __DIR__ . '/../../core/bootstrap.php';

header('Content-Type: application/json');

if (!checkLogin()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$csrf_token = $input['csrf_token'] ?? '';
$new_username = trim($input['username'] ?? ''); // ✅ Use 'username'

validateCSRFToken($csrf_token);

// ✅ Get old username from database
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$old_username = $stmt->fetchColumn();

$errors = [];

$stmt = $pdo->prepare("
    SELECT username_updated_at 
    FROM name_history 
    WHERE user_id = ? 
    AND change_type IN ('username', 'both')
    AND username_updated_at IS NOT NULL
    ORDER BY username_updated_at DESC 
    LIMIT 1
");
$stmt->execute([$_SESSION['user_id']]);
$last_change = $stmt->fetchColumn();

if ($last_change && strtotime($last_change) > strtotime('-48 hours')) {
    $remaining = ceil((strtotime($last_change) + (48 * 3600) - time()) / 3600);
    echo json_encode(['success' => false, 'errors' => ["You can only change your username once every 48 hours. Please wait $remaining hour(s)."]]);
    exit;
}

// Validate
if (strlen($new_username) < 4) {
    $errors[] = "Username must be at least 4 characters";
}
if (strlen($new_username) > 12) {
    $errors[] = "Username must be at most 12 characters";
}
if (preg_match('/[^a-zA-Z0-9_]/', $new_username)) {
    $errors[] = "Username can only contain letters, numbers, and underscores";
}
if (preg_match_all('/[a-zA-Z]/', $new_username) < 2) {
    $errors[] = "Username must contain at least 2 letters";
}
if (preg_match_all('/[0-9]/', $new_username) < 1) {
    $errors[] = "Username must contain at least 1 number";
}
if (substr_count($new_username, '_') > 1) {
    $errors[] = "Username can contain at most 1 underscore";
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// Check if username exists (excluding current user)
$stmt = $pdo->prepare("SELECT id FROM users WHERE LOWER(username) = LOWER(?) AND id != ?");
$stmt->execute([$new_username, $_SESSION['user_id']]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'errors' => 'Username already taken']);
    exit;
}

// ✅ Update username
$stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
$stmt->execute([$new_username, $_SESSION['user_id']]);
$_SESSION['username'] = $new_username;

// ✅ Log to name_history
$stmt = $pdo->prepare("
    INSERT INTO name_history (
        user_id,
        previous_username,
        updated_username,
        change_type,
        username_updated_at,
        changed_by
    ) VALUES (?, ?, ?, 'username', NOW(), ?)
");
$stmt->execute([
    $_SESSION['user_id'],
    $old_username,
    $new_username,
    $_SESSION['user_id']
]);

addNotification(
    $_SESSION['user_id'],
    "success",
    "Username changed successfully!",
    "Your username has been updated from " . htmlspecialchars($old_username) . " to " . htmlspecialchars($new_username) . ".",
    SITE_URL . "/pages/profile.php",
    "system",
    null
);

// ✅ Log action
logAction('Changed username from \'' . $old_username . '\' to \'' . $new_username . '\'');

echo json_encode(['success' => true]);
