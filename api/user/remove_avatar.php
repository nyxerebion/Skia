<?php
// /api/user/remove_avatar.php
require_once __DIR__ . '/../../core/bootstrap.php';

header('Content-Type: application/json');

if (!checkLogin()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$csrf_token = $input['csrf_token'] ?? '';

validateCSRFToken($csrf_token);

$user_id = $_SESSION['user_id'];

$uploadDir = __DIR__ . '/../../uploads/avatars/';
$archiveDir = __DIR__ . '/../../uploads/avatars_archive/';

if (!is_dir($archiveDir)) {
    mkdir($archiveDir, 0755, true);
}

// Get current avatar filename
$stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current_avatar = $stmt->fetchColumn();

// Archive file
if ($current_avatar && file_exists($uploadDir . $current_avatar)) {
    rename($uploadDir . $current_avatar, $archiveDir . $current_avatar);
}

// Clear DB column
$stmt = $pdo->prepare("UPDATE users SET avatar = NULL WHERE id = ?");
$stmt->execute([$user_id]);

// Log to avatar_history
$stmt = $pdo->prepare("
    INSERT INTO avatar_history (
        user_id, previous_avatar, updated_avatar,
        change_type, avatar_updated_at, changed_by
    ) VALUES (?, ?, NULL, 'remove', NOW(), ?)
");
$stmt->execute([
    $user_id,
    $current_avatar,
    $user_id
]);


logAction('avatar_removed');

addNotification(
    $user_id,
    'success',
    'Avatar Removed',
    'Your profile picture has been removed. Your initials will be shown instead.',
    SITE_URL . '/pages/settings.php?tab=account',
    'system',
    null
);

echo json_encode(['success' => true]);