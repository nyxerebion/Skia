<?php
require_once __DIR__ . '/../../core/bootstrap.php';

header('Content-Type: application/json');

if (!checkLogin()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$csrf_token = $input['csrf_token'] ?? '';
$new_name = trim($input['name'] ?? ''); 

validateCSRFToken($csrf_token);

// ✅ Get old name from database
$stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$stmt->execute([(int) $_SESSION['user_id']]);
$old_name = $stmt->fetchColumn() ?: '';

$errors = [];

$stmt = $pdo->prepare("
    SELECT name_updated_at 
    FROM name_history 
    WHERE user_id = ? 
    AND change_type IN ('name', 'both')
    AND name_updated_at IS NOT NULL
    ORDER BY name_updated_at DESC 
    LIMIT 1
");
$stmt->execute([(int) $_SESSION['user_id']]);
$last_change = $stmt->fetchColumn();

if ($last_change && strtotime($last_change) > strtotime('-24 hours')) {
    $remaining = ceil((strtotime($last_change) + (24 * 3600) - time()) / 3600);
    echo json_encode(['success' => false, 'errors' => ["You can only change your name once every 24 hours. Please wait $remaining hour(s)."]]);
    exit;
}

// Validate
if (strlen($new_name) < 2) {
    $errors[] = "Name must be at least 2 characters";
}
if (strlen($new_name) > 100) {
    $errors[] = "Name must be at most 100 characters";
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

if ($new_name === $old_name) {
    echo json_encode(['success' => false, 'errors' => ["This is already your current display name."]]);
    exit;
}

// ✅ Update name
$stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
$stmt->execute([$new_name, (int) $_SESSION['user_id']]);
$_SESSION['name'] = $new_name;

// ✅ Log to name_history
$stmt = $pdo->prepare("
    INSERT INTO name_history (
        user_id,
        previous_name,
        updated_name,
        change_type,
        name_updated_at,
        changed_by
    ) VALUES (?, ?, ?, 'name', NOW(), ?)
");
$stmt->execute([
    (int) $_SESSION['user_id'],
    $old_name,
    $new_name,
    (int) $_SESSION['user_id']
]);

addNotification(
    (int) $_SESSION['user_id'],
    "success",
    "Name changed successfully!",
    "Your name has been updated from " . $old_name . " to " . $new_name . ".",
    SITE_URL . "/pages/profile.php",
    "system",
    null
);

// ✅ Log action
logAction('Changed name from \'' . $old_name . '\' to \'' . $new_name . '\'');

echo json_encode(['success' => true]);
