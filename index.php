<?php
require_once __DIR__ . '/core/bootstrap.php';

if (!checkLogin()) {
    http_response_code(403);
    header("Location: guest-page.php");
    exit();
}

logAction('Viewed Home Page');

$stmt = $pdo->prepare("
    SELECT * FROM users 
    ORDER BY id DESC 
    LIMIT 5");
$stmt->execute();
$users = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT u.*,
     users.username
    FROM updates u
    JOIN users ON u.user_id = users.id 
    ORDER BY u.created_at DESC 
    LIMIT 3");
$stmt->execute();
$updates = $stmt->fetchAll();

// Get latest posts for feed
$stmt = $pdo->prepare("
    SELECT
        p.*,
        u.username,
        (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS like_count,
        (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comment_count
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE archived = 0
    ORDER BY p.created_at DESC
    LIMIT 5
");
$stmt->execute();
$posts = $stmt->fetchAll();

// Get top player by score
$stmt = $pdo->prepare("
    SELECT u.username, ws.score, ws.points, ws.total_points
    FROM whack_scores ws
    JOIN users u ON ws.user_id = u.id
    ORDER BY ws.score DESC
    LIMIT 1
");
$stmt->execute();
$top_score = $stmt->fetch();

// Get top player by total points
$stmt = $pdo->prepare("
    SELECT u.username, ws.score, ws.points, ws.total_points
    FROM whack_scores ws
    JOIN users u ON ws.user_id = u.id
    ORDER BY ws.total_points DESC
    LIMIT 1
");
$stmt->execute();
$top_points = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once __DIR__ . '/core/head.php'; ?>
    <title>Home Page | Skia</title>
    <link rel="stylesheet" href="css/updates.css?v=<?= filemtime(__DIR__ . '/css/updates.css') ?>">
    <link rel="stylesheet" href="css/general.css?v=<?= filemtime(__DIR__ . '/css/general.css') ?>">
    <link rel="stylesheet" href="css/index.css?v=<?= filemtime(__DIR__ . '/css/index.css') ?>">

</head>

<body>
    <header>
        <h1>Home | Skia</h1>
        <div class="header-right">
            <a href="security/logout.php" class="logout-btn"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-log-out-icon lucide-log-out">
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
                    <?php $unread = getUnreadCount($_SESSION['user_id']);
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
                                onclick="markRead(<?= $notif['id'] ?>, '<?= $notif['link'] ?>')">
                                <div class="title"><?= htmlspecialchars($notif['title']) ?></div>
                                <div class="message"><?= htmlspecialchars($notif['message']) ?></div>
                                <div class="time"><?= timeAgo($notif['created_at']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <button id="settingsBtn" onclick="toggleSettings()">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-settings-icon lucide-settings">
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
                    <label>
                        <input type="checkbox" id="darkMode" onclick="toggleTheme()"> Dark Mode
                    </label>
                </div>
                <div class="other">
                    <h4>Other</h4>
                    <a href="security/logout.php" id="responsiveBtn"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-log-out-icon lucide-log-out">
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
            <a href="index.php" class="nav-link">
                <span class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-house-icon lucide-house">
                        <path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8" />
                        <path d="M3 10a2 2 0 0 1 .709-1.528l7-6a2 2 0 0 1 2.582 0l7 6A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    </svg></span>
                <span class="name">Home</span>
            </a>
            <a href="pages/contents.php" class="nav-link">
                <span class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-layout-panel-left-icon lucide-layout-panel-left">
                        <rect width="7" height="18" x="3" y="3" rx="1" />
                        <rect width="7" height="7" x="14" y="3" rx="1" />
                        <rect width="7" height="7" x="14" y="14" rx="1" />
                    </svg></span>
                <span class="name">Contents</span>
            </a>
            <?php if (isAdmin()): ?>
                <a href="admin/panel.php" class="nav-link">
                    <span class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user-star-icon lucide-user-star">
                            <path d="M16.051 12.616a1 1 0 0 1 1.909.024l.737 1.452a1 1 0 0 0 .737.535l1.634.256a1 1 0 0 1 .588 1.806l-1.172 1.168a1 1 0 0 0-.282.866l.259 1.613a1 1 0 0 1-1.541 1.134l-1.465-.75a1 1 0 0 0-.912 0l-1.465.75a1 1 0 0 1-1.539-1.133l.258-1.613a1 1 0 0 0-.282-.866l-1.156-1.153a1 1 0 0 1 .572-1.822l1.633-.256a1 1 0 0 0 .737-.535z" />
                            <path d="M8 15H7a4 4 0 0 0-4 4v2" />
                            <circle cx="10" cy="7" r="4" />
                        </svg></span>
                    <span class="name">Admin</span>
                </a>
            <?php endif; ?>

            <a href="pages/notes.php" class="nav-link">
                <span><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-scroll-text-icon lucide-scroll-text">
                        <path d="M15 12h-5" />
                        <path d="M15 8h-5" />
                        <path d="M19 17V5a2 2 0 0 0-2-2H4" />
                        <path d="M8 21h12a2 2 0 0 0 2-2v-1a1 1 0 0 0-1-1H11a1 1 0 0 0-1 1v1a2 2 0 1 1-4 0V5a2 2 0 1 0-4 0v2a1 1 0 0 0 1 1h3" />
                    </svg></span>
                <span class="name">Notes</span>
            </a>

            <a href="posts/index.php" class="nav-link">
                <span><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-gallery-vertical-end-icon lucide-gallery-vertical-end">
                        <path d="M7 2h10" />
                        <path d="M5 6h14" />
                        <rect width="18" height="12" x="3" y="10" rx="2" />
                    </svg></span>
                <span class="name">Posts</span>
            </a>

            <a href="games/index.php" class="nav-link">
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

        <div class="sidebar-profile">
            <a href="pages/profile.php" class="profile-link">
                <div class="left-content">
                    <div class="avatar-container avatar-sm">
                        <?= getUserAvatar($_SESSION['user_id']) ?>
                    </div>
                    <div class="profile-info">
                        <span class="profile-name"><?= htmlspecialchars(getDisplayName($_SESSION['name'] ?? '')) ?></span>
                        <span class="profile-role"><?= htmlspecialchars($_SESSION['role'] ?? 'unknown') ?></span>
                    </div>
                </div>
                <span class="more-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-ellipsis-vertical-icon lucide-ellipsis-vertical">
                        <circle cx="12" cy="12" r="1" />
                        <circle cx="12" cy="5" r="1" />
                        <circle cx="12" cy="19" r="1" />
                    </svg></span>
            </a>
        </div>
    </aside>

    <main>
        <?php $flash = getFlashMessage();
        if ($flash): ?>
            <div class="flash-wrapper">
                <span class="flash-message <?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></span>
            </div>
        <?php endif; ?>

        <section class="hero-section">
            <h1 class="hero-title">Welcome back, <span><?= htmlspecialchars(getDisplayName($_SESSION['name'] ?? '')) ?></span> 👋</h1>
            <p class="hero-subtitle">Ready to test, explore, and enjoy?</p>
        </section>

        <section class="posts-feed-section">
            <div class="section-header">
                <div>
                    <h3>📝 Latest Posts</h3>
                    <span class="count">(<?= count($posts) ?? 0 ?>)</span>
                </div>
                <a href="posts/index.php">View All →</a>
            </div>

            <?php if (!empty($posts)): ?>
                <?php foreach (array_slice($posts, 0, 5) as $post): ?>
                    <div class="post-preview">
                        <div class="post-preview-header">
                            <a href="pages/view-profile.php?id=<?= $post['user_id'] ?>" class="user-link">
                                <span class="avatar-container avatar-sm"><?= getUserAvatar($post['user_id']) ?></span>
                                <strong><?= htmlspecialchars($post['username']) ?></strong>
                            </a>
                            <span class="span-date post-preview-date"><?= timeAgo($post['created_at']) ?></span>
                        </div>
                        <p class="post-preview-content">
                            <?= nl2br(htmlspecialchars(substr($post['content'], 0, 150))) ?>
                            <?php if (strlen($post['content']) > 150): ?>...<?php endif; ?>
                        </p>
                        <div class="post-preview-stats">
                            <span>❤️ <?= $post['like_count'] ?></span>
                            <span>💬 <?= $post['comment_count'] ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="empty-state">No posts yet. Be the first!</p>
            <?php endif; ?>
        </section>

        <section class="users-section">
            <div class="section-header">
                <h3>👥 Recent Users <span class="count">(<?= count($users) ?>)</span></h3>
                <a href="pages/contents.php">View All →</a>
            </div>
            <div class="user-list">
                <?php if (empty($users)): ?>
                    <div class="empty-state">
                        <span class="empty-icon">👤</span>
                        <p>No users yet</p>
                        <span class="empty-sub">Register your first user to get started</span>
                    </div>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <div class="user-item">
                            <span class="avatar-container avatar-sm">
                                <?= getUserAvatar($user['id']) ?>
                            </span>
                            <div class="user-info">
                                <span class="name"><?= htmlspecialchars($user['username']) ?></span>
                                <span class="role"><?= htmlspecialchars($user['role']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="dashboard-section">
            <div class="section-header">
                <h3>🏆 Top Players</h3>
            </div>

            <div class="top-players-grid">
                <?php if ($top_score): ?>
                    <div class="top-card score-leader">
                        <span class="medal">🥇</span>
                        <span class="label">Top Score</span>
                        <span class="player"><?= htmlspecialchars($top_score['username']) ?></span>
                        <span class="value"><?= number_format($top_score['score']) ?></span>
                    </div>
                <?php else: ?>
                    <div class="top-card empty">
                        <span class="label">No scores yet</span>
                    </div>
                <?php endif; ?>

                <?php if ($top_points): ?>
                    <div class="top-card points-leader">
                        <span class="medal">👑</span>
                        <span class="label">Most Points</span>
                        <span class="player"><?= htmlspecialchars($top_points['username']) ?></span>
                        <span class="value"><?= number_format($top_points['total_points']) ?></span>
                    </div>
                <?php else: ?> 
                    <div class="top-card empty">
                        <span class="label">No points yet</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="dashboard-link">
                <a href="games/whack.php" class="btn-secondary">Play Whack Gold →</a>
                <a href="pages/contents.php" class="btn-link">View All Contents</a>
            </div>
        </section>

        <section class="updates-container">
            <div class="section-header">
                <h2>📢 Latest Updates</h2>
                <span class="count">(<?= count($updates) ?>)</span>
                <a href="pages/contents.php">View All →</a>
            </div>

            <?php if (empty($updates)): ?>
                <p class="empty-state">No updates yet.</p>
            <?php else: ?>
                <?php foreach ($updates as $update):
                    $is_new = (time() - strtotime($update['created_at'])) < 86400;
                    $icon = $icons[$update['type']] ?? '📌';
                    $badge_class = $update['type'] ?? 'patch';
                    $badge_label = $badge_labels[$update['type']] ?? ucfirst($update['type']);

                    // Check if user liked
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM update_likes WHERE update_id = ? AND user_id = ?");
                    $stmt->execute([$update['id'], $_SESSION['user_id']]);
                    $user_liked = $stmt->fetchColumn() > 0;

                    // Get users who liked (first 3)
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

                    // Get total like count
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM update_likes WHERE update_id = ?");
                    $stmt->execute([$update['id']]);
                    $like_count = $stmt->fetchColumn();
                ?>

                    <div class="update-card <?= $update['type'] ?>">
                        <div class="update-header">
                            <span><?= $icon ?></span>
                            <span class="update-badge <?= $badge_class ?>"><?= $badge_label ?></span>

                            <?php if ($is_new): ?>
                                <span class="update-new">New</span>
                            <?php endif; ?>
                        </div>

                        <h3 class="update-title"><?= htmlspecialchars($update['title']) ?></h3>
                        <p class="update-content"><?= nl2br(htmlspecialchars($update['content'])) ?></p>

                        <div class="update-footer">
                            <span class="update-author">👤 <?= htmlspecialchars($update['username']) ?></span>
                            <span class="dot">·</span>
                            <span class="update-date">📅 <?= timeAgo($update['created_at']) ?></span>
                        </div>

                        <div class="update-actions">
                            <button onclick="toggleUpdateLike(<?= $update['id'] ?>)" class="like-btn <?= $user_liked ? 'liked' : '' ?>">
                                <?= $user_liked ? '❤️' : '🤍' ?> <span id="update-like-count-<?= $update['id'] ?>"><?= $like_count ?></span>
                            </button>
                            <?php if ($like_count > 0): ?>
                                <div class="update-likes">
                                    <?php if (count($liked_names) > 0): ?>
                                        Liked by <?= implode(', ', array_map('htmlspecialchars', $liked_names)) ?>
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

        <section class="footer">
            <p>
                Made with ❤️ by Axel | 2025-<?php echo date('Y'); ?>
            </p>
        </section>

        <!--
     NOTE:
     contents = hero, profile, dashboard, games, recent updates, other apps, admin panel
    -->
    </main>

    <script src="js/general.js?v=<?= filemtime(__DIR__ . '/js/general.js') ?>"></script>
    <script src="js/updates.js?v=<?= filemtime(__DIR__ . '/js/updates.js') ?>"></script>
</body>

</html>