<?php
require_once __DIR__ . '/../core/bootstrap.php';

if (!isAdmin() || isCreator()) {
    header('Location: ../index.php');
    exit;
}

validateCSRFToken($_POST['csrf_token'] ?? '');

$user_id = (int)$_POST['user_id'];
$new_role = $_POST['role'] ?? '';

if ($user_id && in_array($new_role, ['admin'])) {
    // Check if user is already an admin
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if ($user && $user['role'] === 'admin') {
        setFlashMessage('User is already an admin.', 'error');
        header('Location: panel.php');
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM pending_actions
        WHERE target_id = ?
        AND target_type = 'user'
        AND action_type = 'promote_user'
        AND status = 'pending'
    ");
    $stmt->execute([$user_id]);
    $pending = $stmt->fetchColumn();

    if ($pending > 0) {
        setFlashMessage('Promotion request already pending for this user.', 'warning');
        header('Location: panel.php');
        exit;
    }

    $data = json_encode(['user_id' => $user_id, 'new_role' => $new_role]);

    $stmt = $pdo->prepare("
        INSERT INTO pending_actions (admin_id, action_type, target_type, target_id, data)
        VALUES (?, 'promote_user', 'user', ?, ?)
    ");
    $stmt->execute([$_SESSION['user_id'], $user_id, $data]);

    setFlashMessage('Promotion request sent to creator.', 'info');
} else {
    setFlashMessage('Invalid request.', 'error');
}

header('Location: panel.php');
exit;
