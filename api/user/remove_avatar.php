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

// Get current avatar filename
$stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current_avatar = $stmt->fetchColumn();

// Delete file if exists
if ($current_avatar) {
    $filepath = __DIR__ . '/../../uploads/avatars/' . $current_avatar;
    if (file_exists($filepath)) {
        unlink($filepath);
    }
}

// Clear DB column
$stmt = $pdo->prepare("UPDATE users SET avatar = NULL WHERE id = ?");
$stmt->execute([$user_id]);

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