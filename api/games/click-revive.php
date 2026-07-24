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

// Reset enemy to level 1
$enemy = getEnemyByLevel(1);

$stmt = $pdo->prepare("
    UPDATE click_data 
    SET health = max_health,
        current_enemy = ?,
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
    $enemy['name'],
    $enemy['health'],
    $enemy['health'],
    $enemy['damage'],
    $enemy['reward'],
    $enemy['critChance'] ?? 0,
    $enemy['critMultiplier'] ?? 1.5,
    $enemy['xp'] ?? 10,
    $user_id
]);

$stmt = $pdo->prepare("SELECT * FROM click_data WHERE user_id = ?");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();
$stats['max_enemy_level'] = getMaxEnemyLevel();
$stats['max_player_level'] = getMaxPlayerLevel();
$stats['experience'] = $stats['experience'] ?? 0;
$stats['level'] = $stats['level'] ?? 1;
$stats['xp_required'] = getLevelXpRequired(min($stats['level'] + 1, getMaxPlayerLevel()));

echo json_encode([
    'success' => true,
    'stats' => $stats,
    'message' => 'Revived successfully!'
]);