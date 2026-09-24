<?php
// save-clicks.php
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

$csrf_token = $input['csrf_token'] ?? '';
validateCSRFToken($csrf_token);

$clicks = (int)($input['clicks'] ?? 0);
$total_clicks = (int)($input['total_clicks'] ?? 0);

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    UPDATE click_data
    SET clicks = ?, total_clicks = ?, last_played = NOW()
    WHERE user_id = ?
");
$stmt->execute([$clicks, $total_clicks, $user_id]);

$stmt = $pdo->prepare("SELECT clicks, total_clicks FROM click_data WHERE user_id = ?");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

echo json_encode(['success' => true, 'stats' => $stats]);
