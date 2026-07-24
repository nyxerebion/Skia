<?php
require_once __DIR__ . '/../../core/bootstrap.php';

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
$score = (int)($input['score'] ?? 0);
$points = (int)($input['points'] ?? 0);
$csrf_token = $input['csrf_token'] ?? '';

validateCSRFToken($csrf_token);

if ($score < 0 || $points < 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid score']);
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT id, score, points, total_points FROM whack_scores WHERE user_id = ?");
$stmt->execute([$user_id]);
$existing = $stmt->fetch();

if ($existing) {
    $new_points = $existing['points'] + $points;
    $new_total_points = $existing['total_points'] + $points;

    if ($score > $existing['score']) {
        $stmt = $pdo->prepare("UPDATE whack_scores SET score = ?, points = ?, total_points = ? WHERE id = ?");
        $stmt->execute([$score, $new_points, $new_total_points, $existing['id']]);
        $updated_score = true;
    } else {
        $stmt = $pdo->prepare("UPDATE whack_scores SET points = ?, total_points = ? WHERE id = ?");
        $stmt->execute([$new_points, $new_total_points, $existing['id']]);
        $updated_score = false;
    }
} else {
    $stmt = $pdo->prepare("INSERT INTO whack_scores (user_id, score, points, total_points) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $score, $points, $points]);
    $updated_score = true;
}

$stmt = $pdo->prepare("SELECT MAX(score) AS high_score, SUM(points) AS points, SUM(total_points) AS total_points FROM whack_scores WHERE user_id = ?");
$stmt->execute([$user_id]);
$result = $stmt->fetch();

echo json_encode([
    'success' => true,
    'updated' => $updated_score,
    'high_score' => (int)($result['high_score'] ?? 0),
    'points' => (int)($result['points'] ?? 0),
    'total_points' => (int)($result['total_points'] ?? 0)
]);