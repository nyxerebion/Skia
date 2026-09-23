<?php
require_once __DIR__ . '/../core/bootstrap.php';

if (!checkLogin()) {
    setFlashMessage('Unauthorized access', 'error');
    http_response_code(403);
    header('Location: ../index.php');
    exit;
}

logAction("Viewed Contents Panel");

$stmt = $pdo->prepare("SELECT * FROM users");
$stmt->execute();
$users = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT u.*, users.username FROM updates u JOIN users ON u.user_id = users.id ORDER BY created_at DESC");
$stmt->execute();
$updates = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT u.id, u.username, ws.score
    FROM whack_scores ws
    JOIN users u ON ws.user_id = u.id
    ORDER BY ws.score DESC
    LIMIT 10
");
$stmt->execute();
$whack_scores = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT u.id, u.username, ws.total_points
    FROM whack_scores ws
    JOIN users u ON ws.user_id = u.id
    ORDER BY ws.total_points DESC
    LIMIT 10
");
$stmt->execute();
$whack_points = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT u.id, u.username, ws.time_played
    FROM whack_scores ws
    JOIN users u ON ws.user_id = u.id
    ORDER BY ws.time_played DESC
    LIMIT 10
");
$stmt->execute();
$whack_time = $stmt->fetchAll();

// Get top player by score
$stmt = $pdo->prepare("
    SELECT u.username, ws.score
    FROM whack_scores ws
    JOIN users u ON ws.user_id = u.id
    ORDER BY ws.score DESC
    LIMIT 1
");
$stmt->execute();
$whack_top_score = $stmt->fetch();

// Get top player by total points
$stmt = $pdo->prepare("
    SELECT u.username, ws.points, ws.total_points
    FROM whack_scores ws
    JOIN users u ON ws.user_id = u.id
    ORDER BY ws.total_points DESC
    LIMIT 1
");
$stmt->execute();
$whack_top_points = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT u.username, ws.time_played
    FROM whack_scores ws
    JOIN users u ON ws.user_id = u.id
    ORDER BY ws.time_played DESC
    LIMIT 1
");
$stmt->execute();
$whack_top_time = $stmt->fetch();

// Click Adventure
$stmt = $pdo->prepare("
    SELECT u.id, u.username, c.total_clicks
    FROM click_data c
    JOIN users u ON c.user_id = u.id
    ORDER BY c.total_clicks DESC
    LIMIT 10
");
$stmt->execute();
$click_clicks = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT u.id, u.username, c.total_coins
    FROM click_data c
    JOIN users u ON c.user_id = u.id
    ORDER BY c.total_coins DESC
    LIMIT 10
");
$stmt->execute();
$click_coins = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT u.id, u.username, c.kills
    FROM click_data c
    JOIN users u ON c.user_id = u.id
    ORDER BY c.kills DESC
    LIMIT 10
");
$stmt->execute();
$click_kills = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT u.id, u.username, c.level
    FROM click_data c
    JOIN users u ON c.user_id = u.id
    ORDER BY c.level DESC
    LIMIT 10
");
$stmt->execute();
$click_levels = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT u.id, u.username, c.time_played
    FROM click_data c
    JOIN users u ON c.user_id = u.id
    ORDER BY c.time_played DESC
    LIMIT 10
");
$stmt->execute();
$click_time = $stmt->fetchAll();

// Click Adventure - Top players for each category
$stmt = $pdo->prepare("
    SELECT 
        (SELECT username FROM users WHERE id = (SELECT user_id FROM click_data ORDER BY total_clicks DESC LIMIT 1)) AS top_clicks_user,
        (SELECT total_clicks FROM click_data ORDER BY total_clicks DESC LIMIT 1) AS top_clicks,
        (SELECT username FROM users WHERE id = (SELECT user_id FROM click_data ORDER BY total_coins DESC LIMIT 1)) AS top_coins_user,
        (SELECT total_coins FROM click_data ORDER BY total_coins DESC LIMIT 1) AS top_coins,
        (SELECT username FROM users WHERE id = (SELECT user_id FROM click_data ORDER BY time_played DESC LIMIT 1)) AS top_time_user,
        (SELECT time_played FROM click_data ORDER BY time_played DESC LIMIT 1) AS top_time,
        (SELECT username FROM users WHERE id = (SELECT user_id FROM click_data ORDER BY level DESC LIMIT 1)) AS top_level_user,
        (SELECT level FROM click_data ORDER BY level DESC LIMIT 1) AS top_level,
        (SELECT username FROM users WHERE id = (SELECT user_id FROM click_data ORDER BY kills DESC LIMIT 1)) AS top_kills_user,
        (SELECT kills FROM click_data ORDER BY kills DESC LIMIT 1) AS top_kills
");
$stmt->execute();
$click_top = $stmt->fetch() ?: [];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once __DIR__ . '/../core/head.php'; ?>
    <title>All contents</title>
    <link rel="stylesheet" href="../css/general.css?v=<?= filemtime(__DIR__ . '/../css/general.css') ?>">
    <link rel="stylesheet" href="../css/contents.css?v=<?= filemtime(__DIR__ . '/../css/contents.css') ?>">
    <link rel="stylesheet" href="../css/updates.css?v=<?= filemtime(__DIR__ . '/../css/updates.css') ?>">
</head>

<body>
    <header>
        <h1>Contents Panel | Skia</h1>
        <div class="header-right">
            <a href="../security/logout.php" class="logout-btn"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-log-out-icon lucide-log-out">
                    <path d="m16 17 5-5-5-5" />
                    <path d="M21 12H9" />
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                </svg> Logout</a>

            <div class="notification-wrapper">
                <button class="notification-bell" onclick="toggleNotifications()" id="notificationBell">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9" />
                        <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0" />
                    </svg>
                    <?php $unread = (int) getUnreadCount($_SESSION['user_id']);
                    if ($unread > 0): ?>
                        <span class="notification-badge"><?= $unread ?></span>
                    <?php endif; ?>
                </button>

                <div class="notification-dropdown" id="notificationDropdown">
                    <div class="notification-header">
                        <h3>Notification</h3>
                        <?php if ($unread > 0): ?>
                            <button onclick="markAllRead()">Mark all read</button>
                        <?php endif; ?>
                    </div>
                    <?php $notifications = getNotifications($_SESSION['user_id']); ?>
                    <?php if (empty($notifications)): ?>
                        <div class="notification-empty">No notifications</div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notif): ?>
                            <div class="notification-item <?= $notif['is_read'] ? '' : 'unread' ?>"
                                data-id="<?= (int) $notif['id'] ?>"
                                data-link="<?= htmlspecialchars($notif['link'] ?? '', ENT_QUOTES) ?>">
                                <div class="title"><?= htmlspecialchars($notif['title'] ?? '') ?></div>
                                <div class="message"><?= htmlspecialchars($notif['message'] ?? '') ?></div>
                                <div class="meta">
                                    <span class="sender-badge <?= $notif['sender_type'] ?? '' ?>">
                                        <?php
                                        $senderLabels = [
                                            'system' => '🤖 System',
                                            'creator' => '👑 Creator',
                                            'admin' => '🛡️ Admin',
                                            'user' => '👤 User'
                                        ];
                                        echo $senderLabels[$notif['sender_type'] ?? ''] ?? 'System';
                                        ?>
                                    </span>
                                    <span class="type-badge <?= $notif['type'] ?? '' ?>"><?= htmlspecialchars($notif['type'] ?? '') ?></span>
                                    <span class="time"><?= timeAgo($notif['created_at']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <button id="settingsBtn" onclick="toggleSettings()">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9.671 4.136a2.34 2.34 0 0 1 4.659 0 2.34 2.34 0 0 0 3.319 1.915 2.34 2.34 0 0 1 2.33 4.033 2.34 2.34 0 0 0 0 3.831 2.34 2.34 0 0 1-2.33 4.033 2.34 2.34 0 0 0-3.319 1.915 2.34 2.34 0 0 1-4.659 0 2.34 2.34 0 0 0-3.32-1.915 2.34 2.34 0 0 1-2.33-4.033 2.34 2.34 0 0 0 0-3.831A2.34 2.34 0 0 1 6.35 6.051a2.34 2.34 0 0 0 3.319-1.915" />
                    <circle cx="12" cy="12" r="3" />
                </svg>
            </button>
        </div>

        <div id="settingsMenu">
            <div class="menu-header">
                <h3>Settings</h3>
                <button id="closeSettings" onclick="handleClose()">X</button>
            </div>
            <div class="menu-body">
                <div class="theme">
                    <h4>Theme</h4>
                    <div class="theme-item">
                        <span>Dark Mode</span>
                        <label class="switch">
                            <input type="checkbox" id="darkMode" onchange="toggleTheme()">
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
                <div class="other">
                    <h4>Other</h4>
                    <a href="<?= SITE_URL ?>/pages/settings.php"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-settings-icon lucide-settings">
                            <path d="M9.671 4.136a2.34 2.34 0 0 1 4.659 0 2.34 2.34 0 0 0 3.319 1.915 2.34 2.34 0 0 1 2.33 4.033 2.34 2.34 0 0 0 0 3.831 2.34 2.34 0 0 1-2.33 4.033 2.34 2.34 0 0 0-3.319 1.915 2.34 2.34 0 0 1-4.659 0 2.34 2.34 0 0 0-3.32-1.915 2.34 2.34 0 0 1-2.33-4.033 2.34 2.34 0 0 0 0-3.831A2.34 2.34 0 0 1 6.35 6.051a2.34 2.34 0 0 0 3.319-1.915" />
                            <circle cx="12" cy="12" r="3" />
                        </svg> Settings</a> <a href="../security/logout.php" class="logout-btn" id="responsiveBtn"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-log-out-icon lucide-log-out">
                            <path d="m16 17 5-5-5-5" />
                            <path d="M21 12H9" />
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                        </svg> Logout</a>
                </div>
            </div>
        </div>
    </header>

    <aside>
        <span class="sidebar-title"></span>
        <nav class="sidebar-nav">
            <a href="../index.php" class="nav-link">
                <span class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-house-icon lucide-house">
                        <path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8" />
                        <path d="M3 10a2 2 0 0 1 .709-1.528l7-6a2 2 0 0 1 2.582 0l7 6A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    </svg></span>
                <span class="name">Home</span>
            </a>
            <a href="../pages/contents.php" class="nav-link">
                <span class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-layout-panel-left-icon lucide-layout-panel-left">
                        <rect width="7" height="18" x="3" y="3" rx="1" />
                        <rect width="7" height="7" x="14" y="3" rx="1" />
                        <rect width="7" height="7" x="14" y="14" rx="1" />
                    </svg></span>
                <span class="name">Contents</span>
            </a>
            <?php if (isAdmin()): ?>
                <a href="../admin/panel.php" class="nav-link">
                    <span class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user-star-icon lucide-user-star">
                            <path d="M16.051 12.616a1 1 0 0 1 1.909.024l.737 1.452a1 1 0 0 0 .737.535l1.634.256a1 1 0 0 1 .588 1.806l-1.172 1.168a1 1 0 0 0-.282.866l.259 1.613a1 1 0 0 1-1.541 1.134l-1.465-.75a1 1 0 0 0-.912 0l-1.465.75a1 1 0 0 1-1.539-1.133l.258-1.613a1 1 0 0 0-.282-.866l-1.156-1.153a1 1 0 0 1 .572-1.822l1.633-.256a1 1 0 0 0 .737-.535z" />
                            <path d="M8 15H7a4 4 0 0 0-4 4v2" />
                            <circle cx="10" cy="7" r="4" />
                        </svg></span>
                    <span class="name">Admin</span>
                </a>
            <?php endif; ?>

            <a href="../pages/notes.php" class="nav-link">
                <span><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-scroll-text-icon lucide-scroll-text">
                        <path d="M15 12h-5" />
                        <path d="M15 8h-5" />
                        <path d="M19 17V5a2 2 0 0 0-2-2H4" />
                        <path d="M8 21h12a2 2 0 0 0 2-2v-1a1 1 0 0 0-1-1H11a1 1 0 0 0-1 1v1a2 2 0 1 1-4 0V5a2 2 0 1 0-4 0v2a1 1 0 0 0 1 1h3" />
                    </svg></span>
                <span class="name">Notes</span>
            </a>

            <a href="../posts/index.php" class="nav-link">
                <span><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-gallery-vertical-end-icon lucide-gallery-vertical-end">
                        <path d="M7 2h10" />
                        <path d="M5 6h14" />
                        <rect width="18" height="12" x="3" y="10" rx="2" />
                    </svg></span>
                <span class="name">Posts</span>
            </a>

            <a href="../games/index.php" class="nav-link">
                <span><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-dice5-icon lucide-dice-5">
                        <rect width="18" height="18" x="3" y="3" rx="2" ry="2" />
                        <path d="M16 8h.01" />
                        <path d="M8 8h.01" />
                        <path d="M8 16h.01" />
                        <path d="M16 16h.01" />
                        <path d="M12 12h.01" />
                    </svg></span>
                <span class="name">Games</span>
            </a>
        </nav>

        <div class="online-wrapper">
            🟢 <span class="online-users">0</span> Online now
            <span onclick="viewOnlineUsers()" class="view-online-users" title="View Online Users">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-external-link-icon lucide-external-link">
                    <path d="M15 3h6v6" />
                    <path d="M10 14 21 3" />
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
                </svg>
            </span>

            <div class="online-users-view" style="display: none;">
                <div class="section-header">
                    <h3>Online Users</h3>
                    <span id="onlineUsersCount">(0)</span>
                </div>

                <div class="online-users-list" id="onlineUsersList">
                    <!-- Online users will be populated here -->
                </div>

                <div class="bottom-wrapper">
                    <p>Touch outside to close</p>
                    <button onclick="closeOnlineUsers()">close</button>
                </div>
            </div>
        </div>

        <div class="sidebar-profile">
            <a href="../pages/profile.php" class="profile-link">
                <div class="left-content">
                    <div class="avatar-container avatar-sm">
                        <?= getUserAvatar($_SESSION['user_id']) ?>
                        <div class="status status-offline" data-user="<?= (int) $_SESSION['user_id'] ?>"></div>
                    </div>
                    <div class="profile-info">
                        <span class="profile-name"><?= htmlspecialchars(getDisplayName($_SESSION['name'] ?? '')) ?></span>
                        <span class="profile-role"><?= htmlspecialchars($_SESSION['role'] ?? 'unknown') ?></span>
                    </div>
                </div>
            </a>
        </div>
    </aside>

    <main>
        <?php $flash = getFlashMessage();
        if ($flash): ?>
            <div class="flash-wrapper">
                <div class="flash-message <?= $flash['type'] ?? '' ?>">
                    <?= htmlspecialchars($flash['message'] ?? '') ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab-btn active" data-tab="tab1" onclick="switchTab('tab1')">Leaderboard</button>
            <button class="tab-btn" data-tab="tab2" onclick="switchTab('tab2')">Users</button>
            <button class="tab-btn" data-tab="tab3" onclick="switchTab('tab3')">Updates</button>
        </div>

        <div id="tab1" class="tab-content active">
            <section class="leaderboard-section">
                <section class="section-header">
                    <h2>Leaderboard</h2>
                    <div class="sub-tabs">
                        <button class="sub-tab-btn active" data-tab="sub-tab1" onclick="switchSubTab('sub-tab1')">Whack Leaderboard</button>
                        <button class="sub-tab-btn" data-tab="sub-tab2" onclick="switchSubTab('sub-tab2')">Click Leaderboard</button>
                    </div>
                </section>

                <div id="sub-tab1" class="sub-tab-content active">
                    <div class="leaderboard whack-leaderboard">
                        <div class="top-players-grid">
                            <?php if ($whack_top_score): ?>
                                <div class="top-card score-leader">
                                    <span class="medal">🥇</span>
                                    <span class="label">Top Score</span>
                                    <span class="player"><?= htmlspecialchars($whack_top_score['username'] ?? '') ?></span>
                                    <span class="value"><?= formatNumber((int) ($whack_top_score['score'] ?? 0), 'score', false) ?></span>
                                </div>
                            <?php else: ?>
                                <div class="top-card empty">
                                    <span class="label">No scores yet</span>
                                </div>
                            <?php endif; ?>

                            <?php if ($whack_top_points): ?>
                                <div class="top-card points-leader">
                                    <span class="medal">👑</span>
                                    <span class="label">Most Points</span>
                                    <span class="player"><?= htmlspecialchars($whack_top_points['username'] ?? '') ?></span>
                                    <span class="value"><?= formatNumber((int) ($whack_top_points['total_points'] ?? 0), 'points', false) ?></span>
                                </div>
                            <?php else: ?>
                                <div class="top-card empty">
                                    <span class="label">No points yet</span>
                                </div>
                            <?php endif; ?>

                            <?php if ($whack_top_time): ?>
                                <div class="top-card points-leader">
                                    <span class="medal">⏱️</span>
                                    <span class="label">Most Time Played</span>
                                    <span class="player"><?= htmlspecialchars($whack_top_time['username'] ?? '') ?></span>
                                    <span class="value"><?= formatTime((int) ($whack_top_time['time_played'] ?? 0)) ?></span>
                                </div>
                            <?php else: ?>
                                <div class="top-card empty">
                                    <span class="label">No time played yet</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <section class="leaderboard-data">
                            <h3>High Score | <span class="game-name">Whack A Gold</span></h3>

                            <div class="table-wrapper">
                                <?php if (empty($whack_scores)): ?>
                                    <p class="empty-state">No scores yet.</p>
                                <?php else: ?>
                                    <table class="table-data">
                                        <thead>
                                            <tr>
                                                <th>Rank</th>
                                                <th>Player</th>
                                                <th>High Score</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($whack_scores as $index => $user):
                                                $rank_class = $index === 0 ? 'rank-1' : ($index === 1 ? 'rank-2' : ($index === 2 ? 'rank-3' : ''));
                                                $medals = ['🥇', '🥈', '🥉'];
                                                $rank_display = $index < 3 ? $medals[$index] : '#' . ($index + 1);
                                            ?>
                                                <tr>
                                                    <td class="<?= $rank_class ?>"><?= $rank_display ?></td>
                                                    <td><a href="view-profile.php?id=<?= (int) $user['id'] ?>" class="user-link">
                                                            <span class="player-td">
                                                                <span class="avatar-container avatar-sm">
                                                                    <?= getUserAvatar($user['id']) ?>
                                                                </span><?= htmlspecialchars($user['username'] ?? '') ?>
                                                            </span>
                                                        </a>
                                                    </td>
                                                    <td><?= formatNumber((int) ($user['score'] ?? 0), 'score', false) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </section>

                        <section class="leaderboard-data">
                            <h3>Highest Total Points | <span class="game-name">Whack A Gold</span></h3>

                            <div class="table-wrapper">
                                <?php if (empty($whack_points)): ?>
                                    <p class="empty-state">No scores yet.</p>
                                <?php else: ?>
                                    <table class="table-data">
                                        <thead>
                                            <tr>
                                                <th>Rank</th>
                                                <th>Player</th>
                                                <th>Total Points</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($whack_points as $index => $user):
                                                $rank_class = $index === 0 ? 'rank-1' : ($index === 1 ? 'rank-2' : ($index === 2 ? 'rank-3' : ''));
                                                $medals = ['🥇', '🥈', '🥉'];
                                                $rank_display = $index < 3 ? $medals[$index] : '#' . ($index + 1);
                                            ?>
                                                <tr>
                                                    <td class="<?= $rank_class ?>"><?= $rank_display ?></td>
                                                    <td><a href="view-profile.php?id=<?= (int) $user['id'] ?>" class="user-link">
                                                            <span class="player-td">
                                                                <span class="avatar-container avatar-sm">
                                                                    <?= getUserAvatar($user['id']) ?>
                                                                </span><?= htmlspecialchars($user['username'] ?? '') ?>
                                                            </span>
                                                        </a>
                                                    </td>
                                                    <td><?= formatNumber((int) ($user['total_points'] ?? 0), 'points', false) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </section>

                        <section class="leaderboard-data">
                            <h3>Highest Time Played | <span class="game-name">Whack A Gold</span></h3>

                            <div class="table-wrapper">
                                <?php if (empty($whack_time)): ?>
                                    <p class="empty-state">No scores yet.</p>
                                <?php else: ?>
                                    <table class="table-data">
                                        <thead>
                                            <tr>
                                                <th>Rank</th>
                                                <th>Player</th>
                                                <th>Time Played</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($whack_time as $index => $user):
                                                $rank_class = $index === 0 ? 'rank-1' : ($index === 1 ? 'rank-2' : ($index === 2 ? 'rank-3' : ''));
                                                $medals = ['🥇', '🥈', '🥉'];
                                                $rank_display = $index < 3 ? $medals[$index] : '#' . ($index + 1);
                                            ?>
                                                <tr>
                                                    <td class="<?= $rank_class ?>"><?= $rank_display ?></td>
                                                    <td><a href="view-profile.php?id=<?= (int) $user['id'] ?>" class="user-link">
                                                            <span class="player-td">
                                                                <span class="avatar-container avatar-sm">
                                                                    <?= getUserAvatar($user['id']) ?>
                                                                </span><?= htmlspecialchars($user['username'] ?? '') ?>
                                                            </span>
                                                        </a>
                                                    </td>
                                                    <td><?= formatTime((int) ($user['time_played'] ?? 0)) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </section>
                    </div>
                </div>

                <div id="sub-tab2" class="sub-tab-content">
                    <div class="leaderboard">
                        <div class="top-players-grid">
                            <?php if (!empty($click_top['top_clicks_user'])): ?>
                                <div class="top-card clicks-leader">
                                    <span class="medal">👆</span>
                                    <span class="label">Most Clicks</span>
                                    <span class="player"><?= htmlspecialchars($click_top['top_clicks_user'] ?? '') ?></span>
                                    <span class="value"><?= formatNumber((int) ($click_top['top_clicks'] ?? 0), 'click', false) ?></span>
                                </div>

                                <div class="top-card coins-leader">
                                    <span class="medal">💰</span>
                                    <span class="label">Most Coins</span>
                                    <span class="player"><?= htmlspecialchars($click_top['top_coins_user'] ?? '') ?></span>
                                    <span class="value"><?= formatNumber((int) ($click_top['top_coins'] ?? 0), 'coins', false) ?></span>
                                </div>

                                <div class="top-card time-leader">
                                    <span class="medal">⏱️</span>
                                    <span class="label">Most Time Played</span>
                                    <span class="player"><?= htmlspecialchars($click_top['top_time_user'] ?? '') ?></span>
                                    <span class="value"><?= formatTime((int) ($click_top['top_time'] ?? 0)) ?></span>
                                </div>

                                <div class="top-card kills-leader">
                                    <span class="medal">💀</span>
                                    <span class="label">Most Kills</span>
                                    <span class="player"><?= htmlspecialchars($click_top['top_kills_user'] ?? '') ?></span>
                                    <span class="value"><?= number_format((int) ($click_top['top_kills'] ?? 0)) ?></span>
                                </div>

                                <div class="top-card level-leader">
                                    <span class="medal">📊</span>
                                    <span class="label">Highest Level</span>
                                    <span class="player"><?= htmlspecialchars($click_top['top_level_user'] ?? '') ?></span>
                                    <span class="value"><?= number_format((int) ($click_top['top_level'] ?? 0)) ?></span>
                                </div>
                            <?php else: ?>
                                <div class="top-card empty">
                                    <span class="label">No click data yet</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <section class="leaderboard-data">
                            <h3>Highest Total Clicks | <span class="game-name">Click Adventure</span></h3>

                            <div class="table-wrapper">
                                <?php if (empty($click_clicks)): ?>
                                    <p class="empty-state">No scores yet.</p>
                                <?php else: ?>
                                    <table class="table-data">
                                        <thead>
                                            <tr>
                                                <th>Rank</th>
                                                <th>Player</th>
                                                <th>Total Clicks</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($click_clicks as $index => $user):
                                                $rank_class = $index === 0 ? 'rank-1' : ($index === 1 ? 'rank-2' : ($index === 2 ? 'rank-3' : ''));
                                                $medals = ['🥇', '🥈', '🥉'];
                                                $rank_display = $index < 3 ? $medals[$index] : '#' . ($index + 1);
                                            ?>
                                                <tr>
                                                    <td class="<?= $rank_class ?>"><?= $rank_display ?></td>
                                                    <td><a href="view-profile.php?id=<?= (int) $user['id'] ?>" class="user-link">
                                                            <span class="player-td">
                                                                <span class="avatar-container avatar-sm">
                                                                    <?= getUserAvatar($user['id']) ?>
                                                                </span><?= htmlspecialchars($user['username'] ?? '') ?>
                                                            </span>
                                                        </a>
                                                    </td>
                                                    <td><?= formatNumber((int) ($user['total_clicks'] ?? 0), 'clicks', false) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </section>

                        <section class="leaderboard-data">
                            <h3>Highest Total Coins | <span class="game-name">Click Adventure</span></h3>

                            <div class="table-wrapper">
                                <?php if (empty($click_clicks)): ?>
                                    <p class="empty-state">No scores yet.</p>
                                <?php else: ?>
                                    <table class="table-data">
                                        <thead>
                                            <tr>
                                                <th>Rank</th>
                                                <th>Player</th>
                                                <th>Total Coins</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($click_coins as $index => $user):
                                                $rank_class = $index === 0 ? 'rank-1' : ($index === 1 ? 'rank-2' : ($index === 2 ? 'rank-3' : ''));
                                                $medals = ['🥇', '🥈', '🥉'];
                                                $rank_display = $index < 3 ? $medals[$index] : '#' . ($index + 1);
                                            ?>
                                                <tr>
                                                    <td class="<?= $rank_class ?>"><?= $rank_display ?></td>
                                                    <td><a href="view-profile.php?id=<?= (int) $user['id'] ?>" class="user-link">
                                                            <span class="player-td">
                                                                <span class="avatar-container avatar-sm">
                                                                    <?= getUserAvatar($user['id']) ?>
                                                                </span><?= htmlspecialchars($user['username'] ?? '') ?>
                                                            </span>
                                                        </a>
                                                    </td>
                                                    <td><?= formatNumber((int) ($user['total_coins'] ?? 0), 'coins', false) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </section>

                        <section class="leaderboard-data">
                            <h3>Highest Kills | <span class="game-name">Click Adventure</span></h3>

                            <div class="table-wrapper">
                                <?php if (empty($click_clicks)): ?>
                                    <p class="empty-state">No scores yet.</p>
                                <?php else: ?>
                                    <table class="table-data">
                                        <thead>
                                            <tr>
                                                <th>Rank</th>
                                                <th>Player</th>
                                                <th>Kills</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($click_kills as $index => $user):
                                                $rank_class = $index === 0 ? 'rank-1' : ($index === 1 ? 'rank-2' : ($index === 2 ? 'rank-3' : ''));
                                                $medals = ['🥇', '🥈', '🥉'];
                                                $rank_display = $index < 3 ? $medals[$index] : '#' . ($index + 1);
                                            ?>
                                                <tr>
                                                    <td class="<?= $rank_class ?>"><?= $rank_display ?></td>
                                                    <td><a href="view-profile.php?id=<?= (int) $user['id'] ?>" class="user-link">
                                                            <span class="player-td">
                                                                <span class="avatar-container avatar-sm">
                                                                    <?= getUserAvatar($user['id']) ?>
                                                                </span><?= htmlspecialchars($user['username'] ?? '') ?>
                                                            </span>
                                                        </a>
                                                    </td>
                                                    <td><?= number_format((int) ($user['kills'] ?? 0)) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </section>

                        <section class="leaderboard-data">
                            <h3>Highest Level | <span class="game-name">Click Adventure</span></h3>

                            <div class="table-wrapper">
                                <?php if (empty($click_clicks)): ?>
                                    <p class="empty-state">No scores yet.</p>
                                <?php else: ?>
                                    <table class="table-data">
                                        <thead>
                                            <tr>
                                                <th>Rank</th>
                                                <th>Player</th>
                                                <th>Level</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($click_levels as $index => $user):
                                                $rank_class = $index === 0 ? 'rank-1' : ($index === 1 ? 'rank-2' : ($index === 2 ? 'rank-3' : ''));
                                                $medals = ['🥇', '🥈', '🥉'];
                                                $rank_display = $index < 3 ? $medals[$index] : '#' . ($index + 1);
                                            ?>
                                                <tr>
                                                    <td class="<?= $rank_class ?>"><?= $rank_display ?></td>
                                                    <td><a href="view-profile.php?id=<?= (int) $user['id'] ?>" class="user-link">
                                                            <span class="player-td">
                                                                <span class="avatar-container avatar-sm">
                                                                    <?= getUserAvatar($user['id']) ?>
                                                                </span><?= htmlspecialchars($user['username'] ?? '') ?>
                                                            </span>
                                                        </a>
                                                    </td>
                                                    <td><?= number_format((int) ($user['level'] ?? 0)) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </section>

                        <section class="leaderboard-data">
                            <h3>Highest Time Played | <span class="game-name">Click Adventure</span></h3>

                            <div class="table-wrapper">
                                <?php if (empty($click_clicks)): ?>
                                    <p class="empty-state">No scores yet.</p>
                                <?php else: ?>
                                    <table class="table-data">
                                        <thead>
                                            <tr>
                                                <th>Rank</th>
                                                <th>Player</th>
                                                <th>Time Played</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($click_time as $index => $user):
                                                $rank_class = $index === 0 ? 'rank-1' : ($index === 1 ? 'rank-2' : ($index === 2 ? 'rank-3' : ''));
                                                $medals = ['🥇', '🥈', '🥉'];
                                                $rank_display = $index < 3 ? $medals[$index] : '#' . ($index + 1);
                                            ?>
                                                <tr>
                                                    <td class="<?= $rank_class ?>"><?= $rank_display ?></td>
                                                    <td><a href="view-profile.php?id=<?= (int) $user['id'] ?>" class="user-link">
                                                            <span class="player-td">
                                                                <span class="avatar-container avatar-sm">
                                                                    <?= getUserAvatar($user['id']) ?>
                                                                </span><?= htmlspecialchars($user['username'] ?? '') ?>
                                                            </span>
                                                        </a>
                                                    </td>
                                                    <td><?= formatTime((int) ($user['time_played'] ?? 0)) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </section>
                    </div>
                </div>
            </section>
        </div>

        <div id="tab2" class="tab-content">
            <section class="leaderboard-section">
                <div class="leaderboard">
                    <section class="leaderboard-data">
                        <h3>👥 All Users (<span class="count"><?= count($users) ?></span>)</h3>

                        <div class="table-wrapper">
                            <?php if (empty($users)): ?>
                                <div class="empty-state">
                                    <span class="empty-icon">👤</span>
                                    <p>No users yet</p>
                                    <span class="empty-sub">Register your first user to get started</span>
                                </div>
                            <?php else: ?>
                                <table class="table-data">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Username</th>
                                            <th>Name</th>
                                            <th>Bio</th>
                                            <th>Role</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td>#<?= (int) ($user['id'] ?? 0) ?></td>
                                                <td><a href="view-profile.php?id=<?= (int) ($user['id'] ?? 0) ?>" class="user-link">
                                                        <span class="player-td">
                                                            <span class="avatar-container avatar-sm">
                                                                <?= getUserAvatar($user['id']) ?></span>
                                                            <?= htmlspecialchars($user['username'] ?? '') ?>
                                                        </span>
                                                    </a>
                                                </td>
                                                <td><?= !empty($user['name']) ? htmlspecialchars($user['name']) : '—' ?></td>
                                                <td>
                                                    <span class="user-bio" onclick="showBioModal(this)"><?= htmlspecialchars($user['bio'] ?? '') ?></span>
                                                </td>
                                                <?php
                                                $role = $user['role'] ?? 'user';
                                                if ($role === 'creator') {
                                                    $roleClass = 'creator';
                                                } else {
                                                    $roleClass = $role === 'admin' ? 'admin' : 'user';
                                                }
                                                ?>
                                                <td><span class="role-badge <?= $roleClass ?>"><?= htmlspecialchars($role) ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
            </section>
        </div>

        <div id="tab3" class="tab-content">
            <section class="updates-container">
                <div class="section-header">
                    <h2>📢 Latest Updates</h2>
                    <span class="count">(<?= count($updates) ?>)</span>
                </div>

                <?php if (empty($updates)): ?>
                    <p class="empty-state">No updates yet.</p>
                <?php else: ?>
                    <?php foreach ($updates as $update):
                        $is_new = (time() - strtotime($update['created_at'] ?? 'now')) < 86400;
                        $type = $update['type'] ?? 'patch';
                        $icon = $icons[$type] ?? '📌';
                        $badge_class = $type;
                        $badge_label = $badge_labels[$type] ?? ucfirst($type);

                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM update_likes WHERE update_id = ? AND user_id = ?");
                        $stmt->execute([$update['id'], $_SESSION['user_id']]);
                        $user_liked = (int) $stmt->fetchColumn() > 0;

                        $stmt = $pdo->prepare("
                            SELECT u.username
                            FROM update_likes ul
                            JOIN users u ON ul.user_id = u.id
                            WHERE ul.update_id = ?
                            LIMIT 3
                        ");
                        $stmt->execute([$update['id']]);
                        $liked_users = $stmt->fetchAll();
                        $liked_names = array_column($liked_users, 'username');

                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM update_likes WHERE update_id = ?");
                        $stmt->execute([$update['id']]);
                        $like_count = (int) $stmt->fetchColumn();
                    ?>
                        <div class="update-card <?= htmlspecialchars($type) ?>">
                            <div class="update-header">
                                <span><?= $icon ?></span>
                                <span class="update-badge <?= htmlspecialchars($badge_class) ?>"><?= htmlspecialchars($badge_label) ?></span>
                                <?php if ($is_new): ?>
                                    <span class="update-new">New</span>
                                <?php endif; ?>
                            </div>
                            <h3 class="update-title"><?= htmlspecialchars($update['title'] ?? '') ?></h3>
                            <p class="update-content"><?= nl2br(htmlspecialchars($update['content'] ?? '')) ?></p>
                            <div class="update-footer">
                                <span class="update-author">👤 <?= htmlspecialchars($update['username'] ?? '') ?></span>
                                <span class="dot">·</span>
                                <span class="update-date">📅 <?= timeAgo($update['created_at']) ?></span>
                            </div>
                            <div class="update-actions">
                                <button onclick="toggleUpdateLike(<?= (int) $update['id'] ?>)" class="like-btn <?= $user_liked ? 'liked' : '' ?>">
                                    <?= $user_liked ? '❤️' : '🤍' ?> <span id="update-like-count-<?= (int) $update['id'] ?>"><?= $like_count ?></span>
                                </button>
                                <?php if ($like_count > 0): ?>
                                    <div class="update-likes">
                                        <?php if (count($liked_names) > 0): ?>
                                            Liked by <?= implode(', ', array_map(fn($n) => htmlspecialchars($n, ENT_QUOTES), $liked_names)) ?>
                                            <?php if ($like_count > 3): ?>
                                                and <?= $like_count - 3 ?> others
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        </div>

        <section class="footer">
            <p>
                Made with ❤️ by Axel | 2025-<?php echo date('Y'); ?>
            </p>
        </section>
    </main>

    <script src="../js/general.js?v=<?= filemtime(__DIR__ . '/../js/general.js') ?>"></script>
    <script src="../js/contents.js?v=<?= filemtime(__DIR__ . '/../js/contents.js') ?>"></script>
</body>

</html>