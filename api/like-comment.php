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
$comment_id = decodeID($input['id'] ?? '');
$csrf_token = $input['csrf_token'] ?? '';

validateCSRFToken($csrf_token);

$user_id = $_SESSION['user_id'];

// Get comment owner and post_id in one query
$stmt = $pdo->prepare("SELECT user_id, post_id FROM comments WHERE id = ?");
$stmt->execute([$comment_id]);
$comment = $stmt->fetch();

if (!$comment) {
    echo json_encode(['success' => false, 'error' => 'Comment not found']);
    exit;
}

// Check if already liked
$stmt = $pdo->prepare("SELECT COUNT(*) FROM comment_likes WHERE comment_id = ? AND user_id = ?");
$stmt->execute([$comment_id, $user_id]);
$exists = $stmt->fetchColumn();

if ($exists) {
    $stmt = $pdo->prepare("DELETE FROM comment_likes WHERE comment_id = ? AND user_id = ?");
    $stmt->execute([$comment_id, $user_id]);
    $action = 'unliked';
} else {
    $stmt = $pdo->prepare("INSERT INTO comment_likes (comment_id, user_id, created_at) VALUES (?, ?, ?)");
    $stmt->execute([$comment_id, $user_id, date('Y-m-d H:i:s')]);
    $action = 'liked';

    if ($comment['user_id'] != $user_id) {
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $liker = $stmt->fetch();

        addNotification(
            $comment['user_id'],
            'info',
            '❤️ New Like',
            $liker['username'] . ' liked your comment.',
            SITE_URL . '/posts/index.php?scroll_to=' . encodeID($comment['post_id']) . '&comment=' . encodeID($comment_id),
            'user',
            $_SESSION['user_id']
        );
    }
}

// Get updated count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM comment_likes WHERE comment_id = ?");
$stmt->execute([$comment_id]);
$count = $stmt->fetchColumn();

echo json_encode([
    'success' => true,
    'action' => $action,
    'count' => (int)$count
]);
