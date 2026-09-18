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
$player = getClickPlayer($user_id);

// Load shop upgrades for special items
$upgrades = json_decode($player['shop_upgrades'] ?? '{}', true);

// Get enemy
$enemy_level = $player['current_enemy_level'] ?? 1;
$enemy = getEnemyByLevel($enemy_level);

// Player damage
$baseDamage = $player['damage'];
$isCrit = rand(1, 100) <= $player['critical_chance'];
$initialDamage = $isCrit ? floor($baseDamage * $player['critical_multiplier']) : $baseDamage;

// Enemy defense
$enemyDefense = $enemy['defense'] ?? 0;
$enemyDefenseBlocked = min($enemyDefense, max(0, $initialDamage - 1));
$finalDamage = max(1, $initialDamage - $enemyDefense);

// Defensive Stance (id 22) - Double defense effectiveness
$defenseMultiplier = ($upgrades[22] ?? 0) > 0 ? 2 : 1;
$effectiveDefense = $player['defense'] * $defenseMultiplier;

// Enemy counter-attack
$enemyIsCrit = rand(1, 100) <= $enemy['critChance'];
$enemyBaseDamage = $enemy['damage'];
$enemyFinalDamage = $enemyIsCrit ? floor($enemyBaseDamage * $enemy['critMultiplier']) : $enemyBaseDamage;
$damageDealt = max(0, $enemyFinalDamage - $effectiveDefense);

// Vampire (id 7) - Heal 10% of damage dealt
$vampireHeal = 0;
if (($upgrades[7] ?? 0) > 0) {
    $vampireHeal = floor($finalDamage * 0.10);
}

// Update enemy health
$newEnemyHealth = $player['enemy_health'] - $finalDamage;

// ✅ Check if player is dead
if ($player['health'] <= 0) {
    $stmt = $pdo->prepare("
        UPDATE click_data 
        SET health = max_health,
            current_enemy = 'Rowan',
            current_enemy_level = 1,
            enemy_health = 100,
            enemy_max_health = 100,
            enemy_damage = 2,
            enemy_reward = 50,
            enemy_crit_chance = 0,
            enemy_crit_multiplier = 1.5,
            enemy_xp = 10,
            deaths = deaths + 1,
            last_played = NOW()
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);

    $stmt = $pdo->prepare("SELECT * FROM click_data WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $stats = $stmt->fetch();
    $stats = enrichClickStats($stats);

    echo json_encode([
        'success' => true,
        'defeated' => false,
        'damage' => 0,
        'crit' => false,
        'enemy_crit' => false,
        'enemy_damage_dealt' => 0,
        'enemy_base_damage' => 0,
        'player_defense' => 0,
        'enemy_defense_blocked' => 0,
        'vampire_heal' => $vampireHeal ?? 0,
        'defense_multiplier_active' => ($upgrades[22] ?? 0) > 0,
        'stats' => $stats,
        'message' => 'You died! Respawned at level 1.'
    ]);
    exit;
}

if ($newEnemyHealth <= 0) {
    // XP Boost (id 17) - Gain 25% more XP per level
    $xpBoostLevel = $upgrades[17] ?? 0;
    $xpMultiplier = 1 + ($xpBoostLevel * 0.25);
    $xpGain = floor(($enemy['xp'] ?? 10) * $xpMultiplier);

    $newExp = $player['experience'] + $xpGain;
    $newLevel = getLevelByXp($newExp);
    $maxPlayerLevel = getMaxPlayerLevel();

    // Double Level Up (id 18)
    $doubleLevelActive = ($upgrades[18] ?? 0) > 0;
    $doubleLevelTriggered = $doubleLevelActive && $newLevel > $player['level'];

    if ($doubleLevelTriggered) {
        $newLevel = min($newLevel + 1, $maxPlayerLevel);
    }

    // Apply level-up stat bonuses
    if ($newLevel > $player['level']) {
        $levelData = getLevelData()[$newLevel] ?? null;
        if ($levelData) {
            $damageBonus = $levelData['damageBonus'] ?? 0;
            $healthBonus = $levelData['healthBonus'] ?? 0;

            $stmt = $pdo->prepare("
                UPDATE click_data 
                SET damage = damage + ?,
                    max_health = max_health + ?,
                    health = health + ?
                WHERE user_id = ?
            ");
            $stmt->execute([$damageBonus, $healthBonus, $healthBonus, $user_id]);
        }
    }

    $coins = $player['coins'] + $enemy['reward'];
    $total_coins = $player['total_coins'] + $enemy['reward'];

    $nextLevel = min($enemy_level + 1, getMaxEnemyLevel());
    $nextEnemy = getEnemyByLevel($nextLevel);

    if ($enemy_level >= getMaxEnemyLevel()) {
        $nextLevel = getMaxEnemyLevel();
        $nextEnemy = getEnemyByLevel($nextLevel);
        $nextEnemyHealth = $nextEnemy['health'];
    } else {
        $nextEnemyHealth = $nextEnemy['health'];
    }

    $stmt = $pdo->prepare("
        UPDATE click_data 
        SET enemy_health = ?,
            enemy_max_health = ?,
            current_enemy = ?,
            enemy_damage = ?,
            enemy_defense = ?,
            enemy_reward = ?,
            enemy_crit_chance = ?,
            enemy_crit_multiplier = ?,
            enemy_xp = ?,
            current_enemy_level = ?,
            coins = ?,
            total_coins = ?,
            experience = ?,
            level = ?,
            kills = kills + 1,
            last_played = NOW()
        WHERE user_id = ?
    ");
    $stmt->execute([
        $nextEnemyHealth,
        $nextEnemy['health'],
        $nextEnemy['name'],
        $nextEnemy['damage'],
        $nextEnemy['defense'] ?? 0,
        $nextEnemy['reward'],
        $nextEnemy['critChance'] ?? 0,
        $nextEnemy['critMultiplier'] ?? 1.5,
        $nextEnemy['xp'] ?? 10,
        $nextLevel,
        $coins,
        $total_coins,
        $newExp,
        $newLevel,
        $user_id
    ]);

    $stmt = $pdo->prepare("SELECT current_enemy_level, enemy_health FROM click_data WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $verify = $stmt->fetch();

    if ($verify['enemy_health'] <= 0) {
        $stmt = $pdo->prepare("UPDATE click_data SET enemy_health = 100 WHERE user_id = ?");
        $stmt->execute([$user_id]);
    }

    $stmt = $pdo->prepare("SELECT * FROM click_data WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $stats = $stmt->fetch();
    $stats = enrichClickStats($stats);

    echo json_encode([
        'success' => true,
        'defeated' => true,
        'damage' => $finalDamage,
        'crit' => $isCrit,
        'enemy_crit' => $enemyIsCrit,
        'enemy_damage_dealt' => $damageDealt,
        'enemy_base_damage' => $enemyFinalDamage,
        'player_defense' => $player['defense'],
        'enemy_defense_blocked' => $enemyDefenseBlocked,
        'vampire_heal' => $vampireHeal ?? 0,
        'xp_gain' => $xpGain ?? 0,
        'xp_boost_active' => ($upgrades[17] ?? 0) > 0,
        'double_level_active' => $doubleLevelActive,
        'double_level_triggered' => $doubleLevelTriggered,
        'defense_multiplier_active' => ($upgrades[22] ?? 0) > 0,
        'stats' => $stats
    ]);
    exit;
}

// ✅ Normal attack with vampire heal
$newPlayerHealth = $player['health'] - $damageDealt + $vampireHeal;
$newPlayerHealth = min($newPlayerHealth, $player['max_health']);

$stmt = $pdo->prepare("
    UPDATE click_data 
    SET current_enemy = ?,
        enemy_health = ?,
        enemy_max_health = ?,
        health = ?,
        last_played = NOW()
    WHERE user_id = ?
");
$stmt->execute([
    $enemy['name'],
    $newEnemyHealth,
    $enemy['health'],
    $newPlayerHealth,
    $user_id
]);

$stmt = $pdo->prepare("SELECT * FROM click_data WHERE user_id = ?");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();
$stats = enrichClickStats($stats);

echo json_encode([
    'success' => true,
    'defeated' => false,
    'damage' => $finalDamage,
    'crit' => $isCrit,
    'enemy_crit' => $enemyIsCrit,
    'enemy_damage_dealt' => $damageDealt,
    'enemy_base_damage' => $enemyFinalDamage,
    'player_defense' => $player['defense'],
    'enemy_defense_blocked' => $enemyDefenseBlocked,
    'vampire_heal' => $vampireHeal ?? 0,
    'defense_multiplier_active' => ($upgrades[22] ?? 0) > 0,
    'stats' => $stats
]);
