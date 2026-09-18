<?php
require_once '../core/bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$csrf_token = $input['csrf_token'] ?? '';
$content = trim($input['content'] ?? '');

if (empty($csrf_token)) {
    echo json_encode(['success' => false, 'error' => 'CSRF token is required']);
    exit;
}
validateCSRFToken($csrf_token);

if (empty($content)) {
    echo json_encode(['success' => false, 'error' => 'Content cannot be empty']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO posts (user_id, content, created_at) VALUES (?, ?, ?)");
$stmt->execute([$_SESSION['user_id'], $content, date('Y-m-d H:i:s')]);

$id = $pdo->lastInsertId();
$hashed_id = encodeID($id);

logAction("User created a post: " . substr($content, 0, 50));

echo json_encode(['success' => true, 'message' => 'Post created!', 'hashed_id' => $hashed_id]);
exit;
