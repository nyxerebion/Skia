<?php
require_once '../core/bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;

$stmt = $pdo->prepare("
    SELECT 
        u.id, 
        u.username, 
        u.avatar, 
        u.role, 
        u.last_activity,
        (SELECT COUNT(*) FROM follows WHERE following_id = u.id) AS followers
    FROM users u
    WHERE u.last_activity >= (NOW() - INTERVAL 1 MINUTE)
    ORDER BY u.last_activity DESC
");
$stmt->execute();
$onlineUsers = $stmt->fetchAll();

// Prefetch follow relationships in one query
$followingIds = [];
if ($user_id && !empty($onlineUsers)) {
    $ids = array_column($onlineUsers, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("
        SELECT following_id 
        FROM follows 
        WHERE follower_id = ? AND following_id IN ($placeholders)
    ");
    $stmt->execute(array_merge([$user_id], $ids));
    $followingIds = array_flip(array_column($stmt->fetchAll(), 'following_id'));
}

foreach ($onlineUsers as &$user) {
    $user['avatar_url'] = !empty($user['avatar'])
        ? SITE_URL . '/uploads/avatars/' . $user['avatar']
        : null;
    $user['hashed_id'] = $hashids->encode($user['id']);
    $user['followers'] = (int)($user['followers'] ?? 0);
    $user['is_following'] = isset($followingIds[$user['id']]);
    $user['is_self'] = ($user_id && (int)$user['id'] === (int)$user_id);
}
unset($user);

echo json_encode([
    'success' => true,
    'online_users' => $onlineUsers,
    'online_users_count' => count($onlineUsers)
]);