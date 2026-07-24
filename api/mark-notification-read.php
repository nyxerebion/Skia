<?php
require_once __DIR__ . '/../core/bootstrap.php';

error_log('=== mark-notification-read.php called ===');
error_log('Raw input: ' . file_get_contents('php://input'));

header('Content-Type: application/json');

if (!checkLogin()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id = (int)($input['id'] ?? 0);
$csrf_token = $input['csrf_token'] ?? '';

validateCSRFToken($csrf_token);

if ($id > 0) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $result = $stmt->execute([$id, $_SESSION['user_id']]);
    echo json_encode(['success' => $result]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid ID']);
}