<?php

function getClickPlayer($user_id)
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM click_data WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $player = $stmt->fetch();

    if (!$player) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO click_data (
                    user_id, health, max_health, damage, click_power,
                    defense, critical_chance, critical_multiplier,
                    clicks, coins, total_clicks, total_coins,
                    level, experience,
                    current_enemy, enemy_health, enemy_damage, enemy_reward, enemy_max_health,
                    enemy_crit_chance, enemy_crit_multiplier, enemy_xp,
                    time_played, shop_upgrades, deaths, last_played
                ) VALUES (
                    ?, 100, 100, 2, 10,
                    0, 0, 1.5,
                    0, 0, 0, 0,
                    1, 0,
                    'Rowan', 100, 2, 50, 100,
                    0, 1.5, 10,
                    0, '{}', 0, NOW()
                )
            ");
            $stmt->execute([$user_id]);
        } catch (PDOException $e) {
            error_log("getClickPlayer INSERT failed for user $user_id: " . $e->getMessage());
            throw $e;
        }

        $stmt = $pdo->prepare("SELECT * FROM click_data WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $player = $stmt->fetch();
    }

    // Safety net: if a prior crash left health at 0, respawn via canonical helper.
    if ((int) $player['health'] <= 0) {
        respawnPlayer($pdo, (int) $user_id);

        $stmt = $pdo->prepare("SELECT * FROM click_data WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $player = $stmt->fetch();
    }

    // Apply any missing level-up bonuses.
    $level = (int) ($player['level'] ?? 1);
    $bonusApplied = (int) ($player['level_bonus_applied'] ?? 0);

    if ($level > $bonusApplied) {
        $totalDamageBonus = 0;
        $totalHealthBonus = 0;

        for ($i = $bonusApplied + 1; $i <= $level; $i++) {
            $levelData = getLevelData()[$i] ?? null;
            if ($levelData) {
                $totalDamageBonus += (int) ($levelData['damageBonus'] ?? 0);
                $totalHealthBonus += (int) ($levelData['healthBonus'] ?? 0);
            }
        }

        if ($totalDamageBonus > 0 || $totalHealthBonus > 0) {
            $newMaxHealth = (int) $player['max_health'] + $totalHealthBonus;
            $newHealth    = min((int) $player['health'] + $totalHealthBonus, $newMaxHealth);
            $newDamage    = (int) $player['damage'] + $totalDamageBonus;

            $stmt = $pdo->prepare("
                UPDATE click_data
                SET damage = ?,
                    max_health = ?,
                    health = ?,
                    level_bonus_applied = ?
                WHERE user_id = ?
            ");
            $stmt->execute([
                $newDamage,
                $newMaxHealth,
                $newHealth,
                $level,
                $user_id
            ]);
        }
    }

    // Clamp level if it exceeds max.
    $maxPlayerLevel = getMaxPlayerLevel();
    if ((int) $player['level'] > $maxPlayerLevel) {
        $stmt = $pdo->prepare("UPDATE click_data SET level = ? WHERE user_id = ?");
        $stmt->execute([$maxPlayerLevel, $user_id]);
    }

    $stmt = $pdo->prepare("SELECT * FROM click_data WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $player = $stmt->fetch();

    return $player;
}

function enrichClickStats($stats)
{
    if (!is_array($stats)) {
        return null;
    }

    $stats['max_enemy_level'] = getMaxEnemyLevel();
    $stats['max_player_level'] = getMaxPlayerLevel();
    $stats['experience'] = $stats['experience'] ?? 0;
    $stats['level'] = $stats['level'] ?? 1;
    $stats['xp_required'] = getLevelXpRequired(min($stats['level'] + 1, getMaxPlayerLevel()));
    $stats['time_played'] = $stats['time_played'] ?? 0;

    return $stats;
}

function respawnPlayer(PDO $pdo, int $user_id): void
{
    $e = getEnemyByLevel(1);

    $stmt = $pdo->prepare("
        UPDATE click_data
        SET health = max_health,
            current_enemy = ?,
            current_enemy_level = 1,
            enemy_health = ?,
            enemy_max_health = ?,
            enemy_damage = ?,
            enemy_defense = ?,
            enemy_reward = ?,
            enemy_crit_chance = ?,
            enemy_crit_multiplier = ?,
            enemy_xp = ?,
            deaths = deaths + 1,
            last_played = NOW()
        WHERE user_id = ?
    ");
    $stmt->execute([
        $e['name'],
        (int) $e['health'],
        (int) $e['health'],
        (int) $e['damage'],
        (int) ($e['defense'] ?? 0),
        (int) $e['reward'],
        (int) ($e['critChance'] ?? 0),
        (float) ($e['critMultiplier'] ?? 1.5),
        (int) ($e['xp'] ?? 10),
        $user_id
    ]);
}