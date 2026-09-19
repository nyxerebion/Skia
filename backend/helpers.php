<?php
function timeAgo($datetime)
{
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;

    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . 'm ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . 'h ago';
    } elseif ($diff < 172800) {
        return 'Yesterday';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . 'd ago';
    } elseif ($diff < 2592000) {
        $weeks = floor($diff / 604800);
        return $weeks . 'w ago';
    } elseif ($diff < 31536000) {
        $months = floor($diff / 2592000);
        return $months . 'mo ago';
    } else {
        return date('M d, Y', $time);
    }
}

function addNotification($user_id, $type, $title, $message, $link = null, $sender_type = 'system', $sender_id = null)
{
    global $pdo;

    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, type, title, message, link, sender_type, sender_id, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$user_id, $type, $title, $message, $link, $sender_type, $sender_id]);
    return $pdo->lastInsertId();
}

function getNotifications($user_id, $limit = 10)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT * FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT ?
    ");
    $stmt->execute([$user_id, $limit]);
    return $stmt->fetchAll();
}

function getUnreadCount($user_id)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM notifications 
        WHERE user_id = ? AND is_read = 0
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}

function markNotificationRead($id, $user_id)
{
    global $pdo;

    $stmt = $pdo->prepare("
        UPDATE notifications 
        SET is_read = 1 
        WHERE id = ? AND user_id = ?
    ");
    return $stmt->execute([$id, $user_id]);
}

function markAllNotificationsRead($user_id)
{
    global $pdo;

    $stmt = $pdo->prepare("
        UPDATE notifications 
        SET is_read = 1 
        WHERE user_id = ? AND is_read = 0
    ");
    return $stmt->execute([$user_id]);
}

/**
 * Get user avatar HTML
 */
/**
 * Get user avatar HTML
 */
// In /backend/helpers.php or similar
function getUserAvatar($userId)
{
    global $pdo;
    static $avatarCache = [];

    if (isset($avatarCache[$userId])) {
        return $avatarCache[$userId];
    }

    $stmt = $pdo->prepare("SELECT avatar, username FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        return '<div class="avatar-placeholder">?</div>';
    }

    $avatar = $user['avatar'] ?? '';
    $username = $user['username'] ?? '';

    // Resolve the uploaded avatar from the project’s uploads directory.
    $uploadDir = __DIR__ . '/../uploads/avatars/';
    $server_path = $uploadDir . $avatar;
    $avatar_exists = !empty($avatar) && file_exists($server_path) && is_file($server_path) && filesize($server_path) > 0;

    if ($avatar_exists) {
        $html = '<img src="' . SITE_URL . '/uploads/avatars/' . rawurlencode($avatar) . '?v=' . filemtime($server_path) . '" 
                     alt="' . htmlspecialchars($username) . '" 
                     class="avatar-img" 
                     loading="lazy"
                     onerror="this.style.display=\'none\';this.parentElement.querySelector(\'.avatar-fallback\').style.display=\'flex\'">
                <div class="avatar-initials avatar-fallback" style="display:none;">' . strtoupper(substr($username, 0, 1)) . '</div>';
    } else {
        $initial = strtoupper(substr($username, 0, 1));
        $html = '<div class="avatar-initials">' . $initial . '</div>';
    }

    $avatarCache[$userId] = $html;
    return $html;
}

function formatTime($seconds)
{
    $seconds = (int)$seconds;

    if ($seconds < 60) {
        return $seconds . 's';
    }

    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;

    if ($hours > 0) {
        return $hours . 'h ' . $minutes . 'm';
    } else {
        return $minutes . 'm ' . $secs . 's';
    }
}

function getDisplayName($name)
{
    if (empty($name)) {
        return $_SESSION['username'] ?? 'Unknown'; // ✅ Use array syntax
    }

    $name = trim(preg_replace('/\s+/', ' ', ucwords(strtolower($name))));
    $parts = explode(' ', $name);

    $first = $parts[0];
    $firstLen = strlen($first);

    if ($firstLen >= 5 && $firstLen <= 8) {
        return $first;
    }

    if ($firstLen < 5 && isset($parts[1])) {
        return $first . ' ' . $parts[1];
    }

    return $first;
}

function getUserGameStatus($last_played)
{
    $diff = time() - strtotime($last_played);

    if ($diff < 300) {
        return 'online';
    } elseif ($diff < 3600) {
        return 'idle';
    } else {
        return 'offline';
    }
}

function getOnlinePlayersByGame($game_name)
{
    global $pdo;

    if ($game_name === "click") {
        $tableToSelect = "click_data";
    } else {
        $tableToSelect = 'whack_scores';
    }

    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS online_count 
        FROM $tableToSelect
        WHERE last_played > NOW() - INTERVAL 1 MINUTE"
    );
    $stmt->execute();
    $result = $stmt->fetch();

    return $result['online_count'] ?? 0;
}


function getOnlineUsers() {
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS online_count 
        FROM users 
        WHERE last_activity > NOW() - INTERVAL 1 MINUTE"
    );
    $stmt->execute();
    $result = $stmt->fetch();

    return $result['online_count'] ?? 0;
}
function formatNumber($amount, $type = 'coins', $use_prefix = true)
{
    $prefixes = [
        'coins' => '💰',
        'clicks' => '👆',
        'damage' => '⚔️',
        'health' => '❤️',
        'defense' => '🛡️',
        'xp' => '⭐',
        'score' => '⭐',
        'points' => '⭐',
        'enemy' => '👾'
    ];

    $prefix = $prefixes[$type] ?? '';
    $formatted = '';

    if ($amount >= 1000000000) {
        $formatted = number_format($amount / 1000000000, 1) . 'B';
    } elseif ($amount >= 1000000) {
        $formatted = number_format($amount / 1000000, 1) . 'M';
    } elseif ($amount >= 1000) {
        $formatted = number_format($amount / 1000, 1) . 'K';
    } else {
        $formatted = (string)$amount;
    }

    return ($use_prefix ? $prefix . ' ' : '') . $formatted;
}