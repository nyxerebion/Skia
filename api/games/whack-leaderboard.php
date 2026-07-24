<?php
require_once __DIR__ . '/../../core/bootstrap.php';

header('Content-Type: application/json');

if (!checkLogin()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT
        MAX(score) AS high_score,
        SUM(points) AS points,
        SUM(total_points) AS total_points,
        SUM(time_played) AS total_time_played
    FROM whack_scores
    WHERE user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user_data = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT u.username, ws.score, ws.points, ws.total_points, ws.time_played
    FROM whack_scores ws
    JOIN users u ON ws.user_id = u.id
    ORDER BY ws.score DESC
    LIMIT 10
");
$stmt->execute();
$leaderboard = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'high_score' => (int)($user_data['high_score'] ?? 0),
    'points' => (int)($user_data['points'] ?? 0),
    'total_points' => (int)($user_data['total_points'] ?? 0),
    'total_time_played' => (int)($user_data['total_time_played'] ?? 0),
    'leaderboard' => $leaderboard
]);