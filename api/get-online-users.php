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
        (SELECT COUNT(*) FROM follows WHERE following_id = u.id) AS followers,
        0 AS is_following,
        0 AS is_self
    FROM users u
    WHERE u.last_activity >= (NOW() - INTERVAL 1 MINUTE)
    ORDER BY u.last_activity DESC
");
$stmt->execute();
$onlineUsers = $stmt->fetchAll();

if ($user_id) {
    foreach ($onlineUsers as &$user) {
        $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ? AND following_id = ?");
        $stmt2->execute([$user_id, $user['id']]);
        $is_following = (int)$stmt2->fetchColumn() > 0;

        $user['avatar_url'] = !empty($user['avatar'])
            ? SITE_URL . '/uploads/avatars/' . $user['avatar']
            : null;
        $user['hashed_id'] = $hashids->encode($user['id']);
        $user['followers'] = (int)($user['followers'] ?? 0);
        $user['is_following'] = $is_following;
        $user['is_self'] = (int)($user['id'] == $user_id) ? 1 : 0;
    }
} else {
    foreach ($onlineUsers as &$user) {
        $user['avatar_url'] = !empty($user['avatar'])
            ? SITE_URL . '/uploads/avatars/' . $user['avatar']
            : null;
        $user['hashed_id'] = $hashids->encode($user['id']);
        $user['followers'] = (int)($user['followers'] ?? 0);
        $user['is_following'] = false;
        $user['is_self'] = false;
    }
}

echo json_encode([
    'success' => true,
    'online_users' => $onlineUsers,
    'online_users_count' => count($onlineUsers)
]);
