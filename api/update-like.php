<?php
require_once __DIR__ . '/../core/bootstrap.php';

header('Content-Type: application/json');

if (!checkLogin()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$update_id = (int)($input['update_id'] ?? 0);
$csrf_token = $input['csrf_token'] ?? '';

validateCSRFToken($csrf_token);

$user_id = $_SESSION['user_id'];

// Check if already liked
$stmt = $pdo->prepare("SELECT COUNT(*) FROM update_likes WHERE update_id = ? AND user_id = ?");
$stmt->execute([$update_id, $user_id]);
$exists = $stmt->fetchColumn();

if ($exists) {
    $stmt = $pdo->prepare("DELETE FROM update_likes WHERE update_id = ? AND user_id = ?");
    $stmt->execute([$update_id, $user_id]);
    $action = 'unliked';
} else {
    $stmt = $pdo->prepare("INSERT INTO update_likes (update_id, user_id, created_at) VALUES (?, ?, ?)");
    $stmt->execute([$update_id, $user_id, date('Y-m-d H:i:s')]);
    $action = 'liked';
}

// Get updated count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM update_likes WHERE update_id = ?");
$stmt->execute([$update_id]);
$count = $stmt->fetchColumn();

echo json_encode([
    'success' => true,
    'action' => $action,
    'count' => (int)$count
]);