<?php
require_once __DIR__ . '/../../core/bootstrap.php';

header('Content-Type: application/json');

if (!checkLogin()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$csrf_token = $input['csrf_token'] ?? '';
$new_bio = trim($input['bio'] ?? '');

validateCSRFToken($csrf_token);

// ✅ Get old bio from database
$stmt = $pdo->prepare("SELECT bio FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$old_bio = $stmt->fetchColumn();

$errors = [];

if (strlen($new_bio) > 1000) {
    $errors[] = "Bio must be at most 1000 characters";
} elseif (strlen($new_bio) < 5) {
    $errors[] = "Bio must be at least 5 characters";
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// Update bio
$stmt = $pdo->prepare("UPDATE users SET bio = ? WHERE id = ?");
$stmt->execute([$new_bio, $_SESSION['user_id']]);

// Log to bio_history
$stmt = $pdo->prepare("
    INSERT INTO bio_history (
        user_id,
        previous_bio,
        updated_bio,
        bio_updated_at,
        changed_by
    ) VALUES (?, ?, ?, NOW(), ?)
");
$stmt->execute([
    $_SESSION['user_id'],
    $old_bio,
    $new_bio,
    $_SESSION['user_id']
]);

// No need to notify the user for bio updates.

logAction('Changed bio from \'' . $old_bio . '\' to \'' . $new_bio . '\'');

echo json_encode(['success' => true]);