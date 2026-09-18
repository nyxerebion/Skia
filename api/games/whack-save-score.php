<?php
require_once __DIR__ . '/../../core/bootstrap.php';

header('Content-Type: application/json');

if (!checkLogin()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$score = (int)($input['score'] ?? 0);
$points_gained = (int)($input['points_gained'] ?? 0);
$csrf_token = $input['csrf_token'] ?? '';

validateCSRFToken($csrf_token);

$user_id = $_SESSION['user_id'];

// Get current data
$stmt = $pdo->prepare("SELECT score, points, total_points FROM whack_scores WHERE user_id = ?");
$stmt->execute([$user_id]);
$current = $stmt->fetch();

if (!$current) {
    // ✅ Create record if doesn't exist
    $stmt = $pdo->prepare("
        INSERT INTO whack_scores (user_id, score, points, total_points, last_played) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$user_id, $score, $points_gained, $points_gained]);
} else {
    // ✅ Always update points and total_points
    $new_points = $current['points'] + $points_gained;
    $new_total_points = $current['total_points'] + $points_gained;

    // ✅ Update score only if higher
    if ($score > $current['score']) {
        $stmt = $pdo->prepare("
            UPDATE whack_scores 
            SET score = ?, points = ?, total_points = ?, last_played = NOW() 
            WHERE user_id = ?
        ");
        $stmt->execute([$score, $new_points, $new_total_points, $user_id]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE whack_scores 
            SET points = ?, total_points = ?, last_played = NOW() 
            WHERE user_id = ?
        ");
        $stmt->execute([$new_points, $new_total_points, $user_id]);
    }
}

// Get updated data
$stmt = $pdo->prepare("SELECT score, points, total_points FROM whack_scores WHERE user_id = ?");
$stmt->execute([$user_id]);
$updated = $stmt->fetch();

echo json_encode([
    'success' => true,
    'high_score' => (int)($updated['score'] ?? 0),
    'points' => (int)($updated['points'] ?? 0),
    'total_points' => (int)($updated['total_points'] ?? 0),
]);
