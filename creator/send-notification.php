<?php
require_once __DIR__ . '/../core/bootstrap.php';

if (!isCreator()) {
    http_response_code(403);
    die('Access denied.');
}

validateCSRFToken($_POST['csrf_token' ?? '']);

$type = $_POST['type'] ?? 'info';
$title = trim($_POST['title'] ?? '');
$message = trim($_POST['message'] ?? '');
$link = trim($_POST['link'] ?? '');
$recipient_type = $_POST['recipient_type'] ?? 'all';
$user_ids = $_POST['user_ids'] ?? [];

if (empty($title) || empty($message)) {
    setFlashMessage('Title and message are required.', 'danger');
    header('Location: notifications.php');
    exit;
}

$query = "SELECT id FROM users WHERE 1=1";
$params = [];

if ($recipient_type === 'admin') {
    $query .= " AND role = 'admin'";
} elseif ($recipient_type === 'creator') {
    $query .= " AND role = 'creator'";
} elseif ($recipient_type === 'select' && !empty($user_ids)) {
    $placeholders = implode(',', array_fill(0, count($user_ids), '?'));
    $query .= " AND id IN ($placeholders)";
    $params = $user_ids;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

$sent = 0;
foreach ($users as $user) {
    addNotification(
        $user['id'],
        $type,
        $title,
        $message,
        $link,
        'creator',
        $_SESSION['user_id']
    );
    $sent++;
}

logAction("Creator sent notification: '$title' to $sent users");
setFlashMessage("✅ Notification sent to $sent users!", 'success');
header('Location: ' . SITE_URL . '/creator/notifications.php');
exit;
