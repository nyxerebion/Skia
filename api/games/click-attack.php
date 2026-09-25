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

$pdo->beginTransaction();

try {
    // Lock the row so concurrent attacks serialize
    $stmt = $pdo->prepare("SELECT * FROM click_data WHERE user_id = ? FOR UPDATE");
    $stmt->execute([$user_id]);
    $player = $stmt->fetch();

    if (!$player) {
        throw new Exception('Player data not found');
    }

    $upgrades = json_decode($player['shop_upgrades'] ?? '{}', true);

    $enemy_level = $player['current_enemy_level'] ?? 1;
    $enemy = getEnemyByLevel($enemy_level);

    // ---------- Dead player branch ----------
    if ((int) $player['health'] <= 0) {
        respawnPlayer($pdo, $user_id);

        $stmt = $pdo->prepare("SELECT * FROM click_data WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $stats = enrichClickStats($stmt->fetch());

        $pdo->commit();

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
            'vampire_heal' => 0,
            'defense_multiplier_active' => ($upgrades[22] ?? 0) > 0,
            'stats' => $stats,
            'message' => 'You died! Respawned at level 1.'
        ]);
        exit;
    }

    // ---------- Player damage ----------
    $baseDamage = (int) $player['damage'];
    $isCrit = rand(1, 100) <= (int) $player['critical_chance'];
    $initialDamage = $isCrit
        ? (int) floor($baseDamage * (float) $player['critical_multiplier'])
        : $baseDamage;

    $enemyDefense = (int) ($enemy['defense'] ?? 0);
    $enemyDefenseBlocked = min($enemyDefense, max(0, $initialDamage - 1));
    $finalDamage = max(1, $initialDamage - $enemyDefense);

    $defenseMultiplier = ($upgrades[22] ?? 0) > 0 ? 2 : 1;
    $effectiveDefense = (int) $player['defense'] * $defenseMultiplier;

    // ---------- Enemy counter-attack ----------
    $enemyIsCrit = rand(1, 100) <= (int) $enemy['critChance'];
    $enemyBaseDamage = (int) $enemy['damage'];
    $enemyFinalDamage = $enemyIsCrit
        ? (int) floor($enemyBaseDamage * (float) $enemy['critMultiplier'])
        : $enemyBaseDamage;
    $damageDealt = max(0, $enemyFinalDamage - $effectiveDefense);

    // ---------- Vampire ----------
    $vampireHeal = 0;
    if (($upgrades[7] ?? 0) > 0) {
        $vampireHeal = (int) floor($finalDamage * 0.10);
    }

    $newEnemyHealth = (int) $player['enemy_health'] - $finalDamage;

    // ---------- Enemy defeated branch ----------
    if ($newEnemyHealth <= 0) {
        $xpBoostLevel = (int) ($upgrades[17] ?? 0);
        $xpMultiplier = 1 + ($xpBoostLevel * 0.25);
        $xpGain = (int) floor(((int) ($enemy['xp'] ?? 10)) * $xpMultiplier);

        $newExp = (int) $player['experience'] + $xpGain;
        $newLevel = getLevelByXp($newExp);
        $maxPlayerLevel = getMaxPlayerLevel();

        $doubleLevelActive = ($upgrades[18] ?? 0) > 0;
        $doubleLevelTriggered = $doubleLevelActive && $newLevel > (int) $player['level'];

        if ($doubleLevelTriggered) {
            $newLevel = min($newLevel + 1, $maxPlayerLevel);
        }

        $newMaxHealth = (int) $player['max_health'];
        $newHealth    = (int) $player['health'];
        $newDamage    = (int) $player['damage'];

        if ($newLevel > (int) $player['level']) {
            $levelData = getLevelData()[$newLevel] ?? null;
            if ($levelData) {
                $damageBonus = (int) ($levelData['damageBonus'] ?? 0);
                $healthBonus = (int) ($levelData['healthBonus'] ?? 0);

                $newDamage += $damageBonus;
                $newMaxHealth += $healthBonus;
                $newHealth = min($newHealth + $healthBonus, $newMaxHealth);
            }
        }

        $coins = (int) $player['coins'] + (int) $enemy['reward'];
        $total_coins = (int) $player['total_coins'] + (int) $enemy['reward'];

        $nextLevel = min($enemy_level + 1, getMaxEnemyLevel());
        $nextEnemy = getEnemyByLevel($nextLevel);

        if ($enemy_level >= getMaxEnemyLevel()) {
            $nextLevel = getMaxEnemyLevel();
            $nextEnemy = getEnemyByLevel($nextLevel);
        }

        $nextEnemyHealth = (int) $nextEnemy['health'];

        $stmt = $pdo->prepare("
            UPDATE click_data
            SET damage = ?,
                max_health = ?,
                health = ?,
                enemy_health = ?,
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
            $newDamage,
            $newMaxHealth,
            $newHealth,
            $nextEnemyHealth,
            (int) $nextEnemy['health'],
            $nextEnemy['name'],
            (int) $nextEnemy['damage'],
            (int) ($nextEnemy['defense'] ?? 0),
            (int) $nextEnemy['reward'],
            (int) ($nextEnemy['critChance'] ?? 0),
            (float) ($nextEnemy['critMultiplier'] ?? 1.5),
            (int) ($nextEnemy['xp'] ?? 10),
            $nextLevel,
            $coins,
            $total_coins,
            $newExp,
            $newLevel,
            $user_id
        ]);

        $stmt = $pdo->prepare("SELECT * FROM click_data WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $stats = enrichClickStats($stmt->fetch());

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'defeated' => true,
            'damage' => $finalDamage,
            'crit' => $isCrit,
            'enemy_crit' => $enemyIsCrit,
            'enemy_damage_dealt' => $damageDealt,
            'enemy_base_damage' => $enemyFinalDamage,
            'player_defense' => (int) $player['defense'],
            'enemy_defense_blocked' => $enemyDefenseBlocked,
            'vampire_heal' => $vampireHeal,
            'xp_gain' => $xpGain,
            'xp_boost_active' => $xpBoostLevel > 0,
            'double_level_active' => $doubleLevelActive,
            'double_level_triggered' => $doubleLevelTriggered,
            'defense_multiplier_active' => ($upgrades[22] ?? 0) > 0,
            'stats' => $stats
        ]);
        exit;
    }

    // ---------- Normal attack ----------
    $newPlayerHealth = (int) $player['health'] - $damageDealt + $vampireHeal;
    $newPlayerHealth = max(0, min($newPlayerHealth, (int) $player['max_health']));

    if ($newPlayerHealth <= 0) {
        respawnPlayer($pdo, $user_id);

        $stmt = $pdo->prepare("SELECT * FROM click_data WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $stats = enrichClickStats($stmt->fetch());

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'defeated' => false,
            'damage' => $finalDamage,
            'crit' => $isCrit,
            'enemy_crit' => $enemyIsCrit,
            'enemy_damage_dealt' => $damageDealt,
            'enemy_base_damage' => $enemyFinalDamage,
            'player_defense' => (int) $player['defense'],
            'enemy_defense_blocked' => $enemyDefenseBlocked,
            'vampire_heal' => $vampireHeal,
            'defense_multiplier_active' => ($upgrades[22] ?? 0) > 0,
            'stats' => $stats,
            'message' => 'You died! Respawned at level 1.'
        ]);
        exit;
    }

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
        (int) $enemy['health'],
        $newPlayerHealth,
        $user_id
    ]);

    $stmt = $pdo->prepare("SELECT * FROM click_data WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $stats = enrichClickStats($stmt->fetch());

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'defeated' => false,
        'damage' => $finalDamage,
        'crit' => $isCrit,
        'enemy_crit' => $enemyIsCrit,
        'enemy_damage_dealt' => $damageDealt,
        'enemy_base_damage' => $enemyFinalDamage,
        'player_defense' => (int) $player['defense'],
        'enemy_defense_blocked' => $enemyDefenseBlocked,
        'vampire_heal' => $vampireHeal,
        'defense_multiplier_active' => ($upgrades[22] ?? 0) > 0,
        'stats' => $stats
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("click-attack error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}