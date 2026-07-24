<?php
date_default_timezone_set('Asia/Manila');

function logAction($action) {
    global $pdo;
    if (!isset($_SESSION['user_id'])) return false;
    
    $user_id = $_SESSION['user_id'];
    $page = basename($_SERVER['PHP_SELF']);
    $timestamp = date('Y-m-d H:i:s');
    
    // Check last entry for same action/page in last 60 seconds
    $stmt = $pdo->prepare("
        SELECT timestamp FROM activity_log 
        WHERE user_id = ? AND action = ? AND page = ? 
        ORDER BY id DESC LIMIT 1
    ");
    $stmt->execute([$user_id, $action, $page]);
    $last = $stmt->fetch();
    
    if ($last) {
        $last_time = strtotime($last['timestamp']);
        $current_time = time();
        if (($current_time - $last_time) < 60) {
            return true; // Skip if less than 60 seconds
        }
    }
    
    $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, page, timestamp) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$user_id, $action, $page, $timestamp]);
}

function addHistory($user_id, $action) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO profile_updates (user_id, action) VALUES (?, ?)");
        return $stmt->execute([$user_id, $action]);
    } catch (PDOException $e) {
        error_log("Failed to add history: " . $e->getMessage());
        return false;
    }
}