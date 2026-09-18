<?php
// encode_notifications.php - Run this once to encode all existing notification links

require_once __DIR__ . '/core/bootstrap.php';

// Set your password here
define('ACCESS_PASSWORD', '201013');

// Check password
if (!isset($_GET['key']) || $_GET['key'] !== ACCESS_PASSWORD) {
    http_response_code(403);
    die('Access denied. Provide ?key=your_password');
}

require_once __DIR__ . '/core/bootstrap.php';

echo "Starting notification link encoding...\n";

// Get all notifications
$stmt = $pdo->prepare("SELECT id, link FROM notifications");
$stmt->execute();
$notifications = $stmt->fetchAll();

$updated = 0;

foreach ($notifications as $notif) {
    $link = $notif['link'];
    $newLink = $link;
    $changed = false;

    // Replace scroll_to=123 with scroll_to=encodedHash
    if (preg_match('/scroll_to=(\d+)/', $link, $matches)) {
        $rawId = (int)$matches[1];
        $encoded = encodeID($rawId);
        $newLink = str_replace('scroll_to=' . $rawId, 'scroll_to=' . $encoded, $newLink);
        $changed = true;
    }

    // Replace id=123 with id=encodedHash
    if (preg_match('/[?&]id=(\d+)/', $link, $matches)) {
        $rawId = (int)$matches[1];
        $encoded = encodeID($rawId);
        $newLink = str_replace('id=' . $rawId, 'id=' . $encoded, $newLink);
        $changed = true;
    }

    // Replace user_id=123 with user_id=encodedHash
    if (preg_match('/user_id=(\d+)/', $link, $matches)) {
        $rawId = (int)$matches[1];
        $encoded = encodeID($rawId);
        $newLink = str_replace('user_id=' . $rawId, 'user_id=' . $encoded, $newLink);
        $changed = true;
    }

    // Replace post_id=123 with post_id=encodedHash
    if (preg_match('/post_id=(\d+)/', $link, $matches)) {
        $rawId = (int)$matches[1];
        $encoded = encodeID($rawId);
        $newLink = str_replace('post_id=' . $rawId, 'post_id=' . $encoded, $newLink);
        $changed = true;
    }

    // Replace profile.php?id=123 with profile.php?id=encodedHash
    if (preg_match('/profile\.php\?id=(\d+)/', $link, $matches)) {
        $rawId = (int)$matches[1];
        $encoded = encodeID($rawId);
        $newLink = str_replace('id=' . $rawId, 'id=' . $encoded, $newLink);
        $changed = true;
    }

    if ($changed && $newLink !== $link) {
        $stmt = $pdo->prepare("UPDATE notifications SET link = ? WHERE id = ?");
        $stmt->execute([$newLink, $notif['id']]);
        $updated++;
        echo "Updated #{$notif['id']}: $link → $newLink\n";
    }
}

echo "\nDone! Updated $updated notifications.\n";
