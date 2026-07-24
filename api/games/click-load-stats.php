<?php
require_once __DIR__ . '/../../core/bootstrap.php';

header('Content-Type: application/json');

if (!checkLogin()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$player = getPlayer($user_id);

$defaultStats = [
    'health' => 100,
    'max_health' => 100,
    'defense' => 0,
    'damage' => 2,
    'click_power' => 1,
    'critical_chance' => 0,
    'critical_multiplier' => 1.5,
    'level' => 1,
    'experience' => 0,
    'clicks' => 0,
    'total_clicks' => 0,
    'coins' => 0,
    'total_coins' => 0,
    'current_enemy' => 'Rowan',
    'current_enemy_level' => 1,
    'enemy_health' => 100,
    'enemy_max_health' => 100,
    'enemy_damage' => 2,
    'enemy_reward' => 0,
    'enemy_crit_chance' => 0,
    'enemy_crit_multiplier' => 1.5,
    'enemy_xp' => 10,
    'shop_upgrades' => 'null',
];

$stats = array_merge($defaultStats, $player ?: []);
$stats['experience'] = $player['experience'] ?? 0;
$stats['max_enemy_level'] = getMaxEnemyLevel();
$stats['max_player_level'] = getMaxPlayerLevel();
$nextLevel = min($player['level'] + 1, getMaxPlayerLevel());
$stats['xp_required'] = ($player['level'] >= getMaxPlayerLevel()) ? 0 : getLevelXpRequired($nextLevel);

echo json_encode([
    'success' => true,
    'stats' => $stats
]);