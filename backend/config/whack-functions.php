<?php
// config/whack-functions.php
function getWhackPlayer($user_id)
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM whack_scores WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $player = $stmt->fetch();

    if (!$player) {
        $stmt = $pdo->prepare("
            INSERT INTO whack_scores (
                user_id, score, points, total_points,
                time_played, last_played, shop_upgrades
            ) VALUES (
                ?, 0, 0, 0,
                0, NOW(), '{}'
            )
        ");

        if (!$stmt->execute([$user_id])) {
            error_log("getWhackPlayer INSERT failed for user $user_id: " . print_r($stmt->errorInfo(), true));
        }

        $stmt = $pdo->prepare("SELECT * FROM whack_scores WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $player = $stmt->fetch();

        return $player;
    }

    return $player;
}
