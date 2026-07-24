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
$post_id = (int)($input['id'] ?? 0);
$csrf_token = $input['csrf_token'] ?? '';

validateCSRFToken($csrf_token);

$user_id = $_SESSION['user_id'];

// Check if already liked
$stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ? AND user_id = ?");
$stmt->execute([$post_id, $user_id]);
$exists = $stmt->fetchColumn();

if ($exists) {
    $stmt = $pdo->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
    $result = $stmt->execute([$post_id, $user_id]);
    $action = 'unliked';
} else {
    $stmt = $pdo->prepare("INSERT INTO likes (post_id, user_id, created_at) VALUES (?, ?, ?)");
    $result = $stmt->execute([$post_id, $user_id, date('Y-m-d H:i:s')]);
    $action = 'liked';

    // Get post owner
    $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();

    if ($post && $post['user_id'] != $user_id) {
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $liker = $stmt->fetch();

        addNotification(
            $post['user_id'],
            'info',
            '❤️ New Like',
            $liker['username'] . ' liked your post.',
            SITE_URL . '/posts/index.php?scroll_to=' . $post_id
        );
    }
}

// Get updated count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
$stmt->execute([$post_id]);
$count = $stmt->fetchColumn();

echo json_encode([
    'success' => true,
    'action' => $action,
    'count' => (int)$count
]);
