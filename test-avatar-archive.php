<?php
require_once __DIR__ . '/core/bootstrap.php';

if (!checkLogin()) {
    die('Login required');
}

$user_id = $_SESSION['user_id'];

echo "<h1>Avatar Archive Test</h1>";
echo "<p>User ID: " . htmlspecialchars($user_id) . "</p>";

// 1. Current avatar
$stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current = $stmt->fetchColumn();

echo "<h2>Current Avatar</h2>";
if ($current) {
    $path = __DIR__ . '/uploads/avatars/' . $current;
    if (file_exists($path)) {
        $url = SITE_URL . '/uploads/avatars/' . rawurlencode($current) . '?v=' . filemtime($path);
        echo "<img src='$url' width='120' style='border-radius:50%;'><br>";
        echo "<code>" . htmlspecialchars($current) . "</code><br>";
        echo "Size: " . filesize($path) . " bytes<br>";
    } else {
        echo "<p style='color:red;'>DB says: <code>$current</code> — FILE MISSING</p>";
    }
} else {
    echo "<p>No avatar set (using initials)</p>";
}

// 2. Archive history
echo "<h2>Avatar History</h2>";
$stmt = $pdo->prepare("
    SELECT id, previous_avatar, updated_avatar, change_type, avatar_updated_at
    FROM avatar_history
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$user_id]);
$history = $stmt->fetchAll();

if (empty($history)) {
    echo "<p>No history yet</p>";
} else {
    echo "<table border='1' cellpadding='8'>";
    echo "<tr><th>ID</th><th>Type</th><th>Previous</th><th>Updated</th><th>Date</th><th>Archived File</th></tr>";
    foreach ($history as $row) {
        $archivedFile = $row['previous_avatar'];
        $archivedPath = __DIR__ . '/uploads/avatars_archive/' . $archivedFile;
        $exists = $archivedFile && file_exists($archivedPath);

        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['change_type']) . "</td>";
        echo "<td><code>" . htmlspecialchars($row['previous_avatar'] ?? '—') . "</code></td>";
        echo "<td><code>" . htmlspecialchars($row['updated_avatar'] ?? '—') . "</code></td>";
        echo "<td>" . htmlspecialchars($row['avatar_updated_at'] ?? '—') . "</td>";

        if ($exists) {
            $url = SITE_URL . '/uploads/avatars_archive/' . rawurlencode($archivedFile) . '?v=' . filemtime($archivedPath);
            echo "<td><img src='$url' width='60' style='border-radius:50%;'> ✅</td>";
        } elseif ($archivedFile) {
            echo "<td style='color:red;'>❌ Missing: <code>$archivedFile</code></td>";
        } else {
            echo "<td>—</td>";
        }

        echo "</tr>";
    }
    echo "</table>";
}

// 3. Folder counts
echo "<h2>Folder Stats</h2>";
$activeCount = count(glob(__DIR__ . '/uploads/avatars/*'));
$archiveCount = count(glob(__DIR__ . '/uploads/avatars_archive/*'));

echo "<p>Active avatars: <strong>$activeCount</strong></p>";
echo "<p>Archived avatars: <strong>$archiveCount</strong></p>";