<?php
ob_start();
require_once '../core/bootstrap.php';

header('Content-Type: application/json');

if (!checkLogin()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$csrf_token = $input['csrf_token'] ?? '';

if (empty($csrf_token)) {
    echo json_encode(['success' => false, 'error' => 'CSRF token is required']);
    exit;
}

validateCSRFToken($csrf_token);

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT
        id,
        username,
        last_activity,
        CASE
            WHEN last_activity >= NOW() - INTERVAL 1 MINUTE THEN 'online'
            WHEN last_activity >= NOW() - INTERVAL 5 MINUTE THEN 'away'
            ELSE 'offline'
        END AS status
    FROM users
    ORDER BY
        CASE
            WHEN last_activity >= NOW() - INTERVAL 1 MINUTE THEN 0
            WHEN last_activity >= NOW() - INTERVAL 5 MINUTE THEN 1
            ELSE 2
        END,
        username ASC
");
$stmt->execute();
$users = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'users' => $users,
    'online_count' => array_reduce($users, function($count, $user) {
        return $count + ($user['status'] === 'online' ? 1 : 0);
    }, 0)
]);
