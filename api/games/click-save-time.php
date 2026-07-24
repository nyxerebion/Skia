<?php
// click-save-time.php
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
$increment = (int)($input['time_increment'] ?? 0);
$csrf_token = $input['csrf_token'] ?? '';

validateCSRFToken($csrf_token);

if ($increment <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid increment']);
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT user_id, time_played FROM click_data WHERE user_id = ?");
$stmt->execute([$user_id]);
$existing = $stmt->fetch();

if ($existing) {
    $new_time = $existing['time_played'] + $increment;
    $stmt = $pdo->prepare("UPDATE click_data SET time_played = ? WHERE user_id = ?");
    $stmt->execute([$new_time, $existing['user_id']]);
} else {
    $stmt = $pdo->prepare("INSERT INTO click_data (user_id, time_played) VALUES (?, ?)");
    $stmt->execute([$user_id, $increment]);
}

echo json_encode(['success' => true, 'time_played' => $new_time ?? $increment]);