<?php
require_once __DIR__ . '/../core/bootstrap.php';

header('Content-Type: application/json');

if (!checkLogin()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$csrf_token = $input['csrf_token'] ?? '';

validateCSRFToken($csrf_token);

$stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
$result = $stmt->execute([$_SESSION['user_id']]);

echo json_encode(['success' => $result]);