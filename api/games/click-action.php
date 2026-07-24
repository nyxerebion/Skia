<?php
require_once __DIR__ . '/../../core/bootstrap.php';

header('Content-Type: application/json');

if (!checkLogin()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$csrf_token = $input['csrf_token'] ?? '';
validateCSRFToken($csrf_token);

$user_id = $_SESSION['user_id'];
$player = getPlayer($user_id);
$clickPower = $player['click_power'] ?? 1;

// Update clicks
$newClicks = $player['clicks'] + $clickPower;
$newTotalClicks = $player['total_clicks'] + $clickPower;

$stmt = $pdo->prepare("
    UPDATE click_data 
    SET clicks = ?, total_clicks = ?, last_played = NOW() 
    WHERE user_id = ?
");
$stmt->execute([$newClicks, $newTotalClicks, $user_id]);

// ✅ Get updated stats
$stmt = $pdo->prepare("SELECT * FROM click_data WHERE user_id = ?");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

// ✅ Always return stats
echo json_encode([
    'success' => true,
    'stats' => $stats, // ✅ Must include this
    'click_gain' => $clickPower
]);