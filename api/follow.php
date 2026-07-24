<?php
require_once __DIR__ . '/../core/bootstrap.php';

header('Content-Type: application/json');

if (!checkLogin()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$follow_id = (int)($input['user_id'] ?? 0);
$ip = $_SERVER['REMOTE_ADDR'];

if (!$follow_id) {
    recordRateLimitAttempt($pdo, $ip, 'follow_invalid');
    echo json_encode(['success' => false, 'error' => 'Invalid user']);
    exit;
}

if ($follow_id === $_SESSION['user_id']) {
    recordRateLimitAttempt($pdo, $ip, 'follow_self');
    echo json_encode(['success' => false, 'error' => 'Cannot follow yourself']);
    exit;
}

checkRateLimit($pdo, $ip, 'follow_global', 20, 60);

$action = 'follow_' . $follow_id;
checkRateLimit($pdo, $ip, $action, 5, 15);

// Check if already following
$stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ? AND following_id = ?");
$stmt->execute([$_SESSION['user_id'], $follow_id]);
$is_following = $stmt->fetchColumn() > 0;

if ($is_following) {
    // Unfollow
    $stmt = $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
    $stmt->execute([$_SESSION['user_id'], $follow_id]);
    clearRateLimit($pdo, $ip, $action);
    clearRateLimit($pdo, $ip, 'follow_global');
    echo json_encode(['success' => true, 'following' => false]);
} else {
    // Follow
    $stmt = $pdo->prepare("INSERT INTO follows (follower_id, following_id) VALUES (?, ?)");
    $stmt->execute([$_SESSION['user_id'], $follow_id]);
    clearRateLimit($pdo, $ip, $action);
    clearRateLimit($pdo, $ip, 'follow_global');
    echo json_encode(['success' => true, 'following' => true]);

    addNotification(
        $follow_id,
        'info',
        '🥳 New Follower',
        $_SESSION['username'] . ' started following you.',
        SITE_URL . '/pages/profile.php'
    );
}
