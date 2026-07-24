<?php
require_once __DIR__ . '/../../core/bootstrap.php';

header('Content-Type: application/json');

if (!checkLogin()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$player = getPlayer($user_id);

// ✅ Check if player is dead
if ($player['health'] <= 0) {
    // Reset everything
    $enemy = getEnemyByLevel(1);

    $stmt = $pdo->prepare("
        UPDATE click_data 
        SET health = max_health,
            current_enemy = 'Rowan',
            current_enemy_level = 1,
            enemy_health = ?,
            enemy_max_health = ?,
            enemy_damage = ?,
            enemy_reward = ?,
            enemy_crit_chance = ?,
            enemy_crit_multiplier = ?,
            enemy_xp = ?,
            deaths = deaths + 1,
            last_played = NOW()
        WHERE user_id = ?
    ");
    $stmt->execute([
        $enemy['health'],
        $enemy['health'],
        $enemy['damage'],
        $enemy['reward'],
        $enemy['critChance'] ?? 0,
        $enemy['critMultiplier'] ?? 1.5,
        $enemy['xp'] ?? 10,
        $user_id
    ]);
}

// ✅ Check if enemy is dead
if ($player['enemy_health'] <= 0) {
    $enemy_level = $player['current_enemy_level'] ?? 1;
    $nextLevel = min($enemy_level + 1, getMaxEnemyLevel());
    $nextEnemy = getEnemyByLevel($nextLevel);

    $stmt = $pdo->prepare("
        UPDATE click_data 
        SET enemy_health = ?,
            enemy_max_health = ?,
            current_enemy = ?,
            enemy_damage = ?,
            enemy_reward = ?,
            enemy_crit_chance = ?,
            enemy_crit_multiplier = ?,
            enemy_xp = ?,
            current_enemy_level = ?,
            last_played = NOW()
        WHERE user_id = ?
    ");
    $stmt->execute([
        $nextEnemy['health'],
        $nextEnemy['health'],
        $nextEnemy['name'],
        $nextEnemy['damage'],
        $nextEnemy['reward'],
        $nextEnemy['critChance'] ?? 0,
        $nextEnemy['critMultiplier'] ?? 1.5,
        $nextEnemy['xp'] ?? 10,
        $nextLevel,
        $user_id
    ]);
}

// Get fresh stats
$stmt = $pdo->prepare("SELECT * FROM click_data WHERE user_id = ?");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();
$stats['max_enemy_level'] = getMaxEnemyLevel();
$stats['xp_required'] = getLevelXpRequired(min($stats['level'] + 1, getMaxPlayerLevel()));
$stats['max_player_level'] = getMaxPlayerLevel();

echo json_encode([
    'success' => true,
    'stats' => $stats,
    'message' => 'Health check completed'
]);
