<?php
ob_clean();
require_once __DIR__ . '/../../core/bootstrap.php';
require_once __DIR__ . '/../../backend/config/click-shop.php';

header('Content-Type: application/json');

// ✅ Define getItemIcon FIRST
try {
    if (!checkLogin()) {
        echo json_encode(['success' => false, 'error' => 'Not logged in']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $csrf_token = $input['csrf_token'] ?? '';
    validateCSRFToken($csrf_token);

    $user_id = $_SESSION['user_id'];
    $player = getClickPlayer($user_id);

    // Load shop upgrades from JSON
    $upgrades = json_decode($player['shop_upgrades'] ?? '{}', true);

    if ($action === 'get_shop') {
        $items = getShopItems();
        $response = [];

        foreach ($items as $item) {
            $id = $item['id'];
            $current_level = $upgrades[$id] ?? 0;
            $cost = getItemCost($item, $current_level);

            $response[] = [
                'id' => $id,
                'name' => $item['name'],
                'description' => $item['description'],
                'icon' => getItemIcon($item['type']),
                'level' => $current_level,
                'max_level' => $item['max'],
                'cost' => $cost,
                'maxed' => $current_level >= $item['max'],
                'type' => $item['type'],
                'value' => $item['value'],
                'need' => $item['need'],
                'stat' => $item['stat'],
                'increment' => $item['increment']
            ];
        }

        echo json_encode([
            'success' => true,
            'shop' => $response,
            'sections' => getShopSections(),
            'coins' => $player['coins'],
            'clicks' => $player['clicks']
        ]);
        exit;
    }

    if ($action === 'buy') {
        $pdo->beginTransaction();

        try {
            $item_id = (int)($input['item_id'] ?? 0);
            $item = getShopItemById($item_id);

            if (!$item) {
                throw new Exception('Item not found');
            }

            // Re-fetch fresh player data inside transaction
            $stmt = $pdo->prepare("SELECT * FROM click_data WHERE user_id = ? FOR UPDATE");
            $stmt->execute([$user_id]);
            $player = $stmt->fetch();

            if (!$player) {
                throw new Exception('Player not found');
            }

            $upgrades = json_decode($player['shop_upgrades'] ?? '{}', true);
            $current_level = $upgrades[$item_id] ?? 0;

            if ($current_level >= $item['max']) {
                throw new Exception('Already max level');
            }

            // Check requirements
            if ($item['need']) {
                $needItem = getShopItemById($item['need']['id']);
                if (!$needItem) {
                    throw new Exception('Required item not found');
                }
                $needOwned = $upgrades[$needItem['id']] ?? 0;
                if ($needOwned < $item['need']['amount']) {
                    throw new Exception("Need {$needItem['name']} x{$item['need']['amount']}");
                }
            }

            $cost = getItemCost($item, $current_level);

            if ($player['coins'] < $cost['coins']) {
                throw new Exception("Not enough coins (need {$cost['coins']})");
            }
            if ($player['clicks'] < $cost['clicks']) {
                throw new Exception("Not enough clicks (need {$cost['clicks']})");
            }

            // Apply upgrade
            $new_level = $current_level + 1;
            $upgrades[$item_id] = $new_level;

            $newCoins = $player['coins'] - $cost['coins'];
            $newClicks = $player['clicks'] - $cost['clicks'];

            $stat = $item['stat'];
            $increment = $item['increment'];

            if ($item['type'] === 'heal') {
                $healAmount = floor($player['max_health'] * $item['value']);
                $stmt = $pdo->prepare("
                    UPDATE click_data
                    SET health = LEAST(max_health, health + ?),
                        shop_upgrades = ?,
                        coins = ?,
                        clicks = ?,
                        last_played = NOW()
                    WHERE user_id = ?
                ");
                $stmt->execute([$healAmount, json_encode($upgrades), $newCoins, $newClicks, $user_id]);
            } elseif ($item['type'] === 'health') {
                $stmt = $pdo->prepare("
                    UPDATE click_data 
                    SET $stat = $stat + ?,
                        health = max_health + ?,
                        shop_upgrades = ?,
                        coins = ?,
                        clicks = ?,
                        last_played = NOW()
                    WHERE user_id = ?
                ");
                $stmt->execute([$increment, $increment, json_encode($upgrades), $newCoins, $newClicks, $user_id]);
            } else {
                $stmt = $pdo->prepare("
            UPDATE click_data 
            SET $stat = $stat + ?,
                shop_upgrades = ?,
                coins = ?,
                clicks = ?,
                last_played = NOW()
            WHERE user_id = ?
        ");
                $stmt->execute([$increment, json_encode($upgrades), $newCoins, $newClicks, $user_id]);
            }

            $pdo->commit();

            // Fetch fresh stats for response
            $stmt = $pdo->prepare("SELECT * FROM click_data WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $stats = $stmt->fetch();
            $stats = enrichClickStats($stats);

            echo json_encode([
                'success' => true,
                'stats' => $stats,
                'message' => "Upgraded {$item['name']} to level {$new_level}!",
                'new_level' => $new_level
            ]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Invalid action']);
} catch (Exception $e) {
    error_log("Shop error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
