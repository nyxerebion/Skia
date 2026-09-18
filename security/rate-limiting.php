<?php
// security/rate-limiting.php

/**
 * Check if rate limit is exceeded
 */
function checkRateLimit($pdo, $ip, $action, $maxAttempts = 5, $windowMinutes = 15)
{
    // Delete old records (expired)
    $stmt = $pdo->prepare("DELETE FROM rate_limits WHERE last_attempt < DATE_SUB(NOW(), INTERVAL ? MINUTE) AND archived = FALSE");
    $stmt->execute([$windowMinutes]);

    // Get current attempts (ignore archived)
    $stmt = $pdo->prepare("SELECT attempts, first_attempt FROM rate_limits WHERE ip_address = ? AND action = ? AND archived = FALSE");
    $stmt->execute([$ip, $action]);
    $record = $stmt->fetch();

    if ($record && $record['attempts'] >= $maxAttempts) {
        $remaining = $windowMinutes - ((time() - strtotime($record['first_attempt'])) / 60);
        if ($remaining > 0) {
            http_response_code(429);
            header('Content-Type: application/json');
            http_response_code(429);
            echo json_encode([
                'success' => false,
                'error' => 'Too many attempts. Try again in ' . ceil($remaining) . ' minutes.'
            ]);
            exit;
        }
        // Window expired, reset
        $stmt = $pdo->prepare("DELETE FROM rate_limits WHERE ip_address = ? AND action = ? AND archived = FALSE");
        $stmt->execute([$ip, $action]);
        return true;
    }
    return true;
}

/**
 * Record a failed attempt
 */
function recordRateLimitAttempt($pdo, $ip, $action)
{
    // Check if active record exists
    $stmt = $pdo->prepare("SELECT id, attempts FROM rate_limits WHERE ip_address = ? AND action = ? AND archived = FALSE");
    $stmt->execute([$ip, $action]);
    $active = $stmt->fetch();

    if ($active) {
        // Increment active record
        $stmt = $pdo->prepare("UPDATE rate_limits SET attempts = attempts + 1, last_attempt = NOW() WHERE id = ?");
        $stmt->execute([$active['id']]);
        return;
    }

    // Check if archived record exists
    $stmt = $pdo->prepare("SELECT id FROM rate_limits WHERE ip_address = ? AND action = ? AND archived = TRUE ORDER BY id DESC LIMIT 1");
    $stmt->execute([$ip, $action]);
    $archived = $stmt->fetch();

    if ($archived) {
        // Reactivate archived record with attempts = 1
        $stmt = $pdo->prepare("UPDATE rate_limits SET archived = FALSE, attempts = 1, first_attempt = NOW(), last_attempt = NOW() WHERE id = ?");
        $stmt->execute([$archived['id']]);
    } else {
        // Insert new record
        $stmt = $pdo->prepare("
            INSERT INTO rate_limits (ip_address, action, attempts, first_attempt, last_attempt)
            VALUES (?, ?, 1, NOW(), NOW())
        ");
        $stmt->execute([$ip, $action]);
    }
}

/**
 * Clear rate limit on success (archive instead of delete)
 */
function clearRateLimit($pdo, $ip, $action)
{
    $stmt = $pdo->prepare("UPDATE rate_limits SET archived = TRUE, last_attempt = NOW() WHERE ip_address = ? AND action = ? AND archived = FALSE");
    $stmt->execute([$ip, $action]);
}

/**
 * Get rate limit history for admin
 */
function getRateLimitHistory($pdo, $ip = null, $action = null, $limit = 50)
{
    $sql = "SELECT * FROM rate_limits WHERE archived = TRUE";
    $params = [];

    if ($ip) {
        $sql .= " AND ip_address = ?";
        $params[] = $ip;
    }
    if ($action) {
        $sql .= " AND action = ?";
        $params[] = $action;
    }

    $sql .= " ORDER BY last_attempt DESC LIMIT ?";
    $params[] = $limit;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}
