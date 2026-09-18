<?php
// games/whack-load-stats.php
require_once __DIR__ . '/../../core/bootstrap.php';

header('Content-Type: application/json');

if (!checkLogin()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$player = getWhackPlayer($user_id);

$defaultStats = [
    'score' => 0,
    'points' => 0,
    'total_points' => 0,
];

$stats = array_merge($defaultStats, $player ?: []);

echo json_encode([
    'success' => true,
    'stats' => $stats
]);