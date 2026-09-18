<?php

function getClickPlayer($user_id)
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM click_data WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $player = $stmt->fetch();

    if (!$player) {
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

        if (!$stmt->execute([$user_id])) {
            error_log("getPlayer INSERT failed for user $user_id: " . print_r($stmt->errorInfo(), true));
            // Return default player structure or exit
        }

        // Re-fetch
        $stmt = $pdo->prepare("SELECT * FROM click_data WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $player = $stmt->fetch();
    }

    // ✅ If player is dead, reset enemy only (preserve player stats)
    if ($player['health'] <= 0) {
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

        // Refresh player data
        $stmt = $pdo->prepare("SELECT * FROM click_data WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $player = $stmt->fetch();
    }

    // ✅ Apply missing level bonuses
    $level = $player['level'] ?? 1;
    $upgrades = json_decode($player['shop_upgrades'] ?? '{}', true);
    $bonusApplied = $upgrades['_level_bonus'] ?? 0;

    if ($level > $bonusApplied) {
        $totalDamageBonus = 0;
        $totalHealthBonus = 0;

        for ($i = $bonusApplied + 1; $i <= $level; $i++) {
            $levelData = getLevelData()[$i] ?? null;
            if ($levelData) {
                $totalDamageBonus += $levelData['damageBonus'] ?? 0;
                $totalHealthBonus += $levelData['healthBonus'] ?? 0;
            }
        }

        if ($totalDamageBonus > 0 || $totalHealthBonus > 0) {
            $stmt = $pdo->prepare("
                UPDATE click_data 
                SET damage = damage + ?,
                    max_health = max_health + ?,
                    health = health + ?
                WHERE user_id = ?
            ");
            $stmt->execute([$totalDamageBonus, $totalHealthBonus, $totalHealthBonus, $user_id]);

            // Mark as applied
            $upgrades['_level_bonus'] = $level;
            $stmt = $pdo->prepare("UPDATE click_data SET shop_upgrades = ? WHERE user_id = ?");
            $stmt->execute([json_encode($upgrades), $user_id]);
        }
    }

    $maxPlayerLevel = getMaxPlayerLevel();
    if (($player['level'] ?? 1) > $maxPlayerLevel) {
        $stmt = $pdo->prepare("UPDATE click_data SET level = ? WHERE user_id = ?");
        $stmt->execute([$maxPlayerLevel, $user_id]);
    }

    // Re-fetch updated player and return
    $stmt = $pdo->prepare("SELECT * FROM click_data WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $player = $stmt->fetch();

    return $player;
}

function enrichClickStats($stats)
{
    $stats['max_enemy_level'] = getMaxEnemyLevel();
    $stats['max_player_level'] = getMaxPlayerLevel();
    $stats['experience'] = $stats['experience'] ?? 0;
    $stats['level'] = $stats['level'] ?? 1;
    $stats['xp_required'] = getLevelXpRequired(min($stats['level'] + 1, getMaxPlayerLevel()));
    $stats['time_played'] = $stats['time_played'] ?? 0;

    return $stats;
}
