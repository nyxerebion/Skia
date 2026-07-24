<?php
require_once __DIR__ . '/../../core/bootstrap.php';

if (!checkLogin()) {
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$clicks = (int)($input['clicks'] ?? 0);
$total_clicks = (int)($input['total_clicks'] ?? 0);
$coins = (int)($input['coins'] ?? 0);
$total_coins = (int)($input['total_coins'] ?? 0);
$health = (int)($input['health'] ?? 100);
$enemy_health = (int)($input['enemy_health'] ?? 100);

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    UPDATE click_data 
    SET clicks = ?,
        total_clicks = ?,
        coins = ?,
        total_coins = ?,
        health = ?,
        enemy_health = ?,
        last_played = NOW()
    WHERE user_id = ?
");
$stmt->execute([$clicks, $total_clicks, $coins, $total_coins, $health, $enemy_health, $user_id]);

exit;