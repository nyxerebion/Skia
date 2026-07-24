<?php
require_once __DIR__ . '/../core/bootstrap.php';

if (!checkLogin()) {
    setFlashMessage('Unauthorized access', 'error');
    header('Location: ../index.php');
    exit;
}
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$user_id) {
    setFlashMessage('User not specified.', 'error');
    header('Location: ' . SITE_URL);
    exit;
}

if ($_SESSION['user_id'] === $user_id) {
    header('Location: profile.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$profile_user = $stmt->fetch();

logAction($_SESSION['username'] . ' Viewed ' . $profile_user['username'] . '\'s profile.');

if (!$profile_user) {
    setFlashMessage('User not found.', 'error');
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT 
        p.*,
        (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS like_count
    FROM posts p
    WHERE p.user_id = ? AND p.archived = 0
    ORDER BY p.created_at DESC
");
$stmt->execute([$profile_user['id']]);
$user_posts = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT COUNT(l.id) 
    FROM likes l 
    JOIN posts p ON l.post_id = p.id
    WHERE p.archived = 0 AND p.user_id = ?
");
$stmt->execute([$profile_user['id']]);
$user_likes = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT score, points, total_points, time_played FROM whack_scores WHERE user_id = ?");
$stmt->execute([$profile_user['id']]);
$user_whack_data = $stmt->fetch();

$stmt = $pdo->prepare("SELECT COUNT(following_id) FROM follows WHERE following_id = ?");
$stmt->execute([$profile_user['id']]);
$user_followers = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(follower_id) FROM follows WHERE follower_id = ?");
$stmt->execute([$profile_user['id']]);
$user_following = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once __DIR__ . '/../core/head.php'; ?>
    <title>Profile | Skia</title>
    <link rel="stylesheet" href="../css/general.css?v=<?= filemtime(__DIR__ . '/../css/general.css') ?>">
    <link rel="stylesheet" href="../css/profile.css?v=<?= filemtime(__DIR__ . '/../css/profile.css') ?>">

    <!-- Cropper.js CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
    <!-- Cropper.js JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
</head>

<body>
    <header>
        <h1>Profile | Skia</h1>
        <div class="header-right">
            <a href="../security/logout.php" class="logout-btn"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-log-out-icon lucide-log-out">
                    <path d="m16 17 5-5-5-5" />
                    <path d="M21 12H9" />
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                </svg> Logout</a>

            <div class="notification-wrapper">
                <button class="notification-bell" onclick="toggleNotifications()" id="notificationBell">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
                    <label>
                        <input type="checkbox" id="darkMode" onclick="toggleTheme()"> Dark Mode
                    </label>
                </div>
                <div class="other">
                    <h4>Other</h4>
                    <a href="../security/logout.php" class="logout-btn" id="responsiveBtn">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <aside>
        <span class="sidebar-title"></span>
        <nav class="sidebar-nav">
            <a href="../index.php" class="nav-link">
                <span class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8" />
                        <path d="M3 10a2 2 0 0 1 .709-1.528l7-6a2 2 0 0 1 2.582 0l7 6A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    </svg></span>
                <span class="name">Home</span>
            </a>
            <a href="../pages/contents.php" class="nav-link">
                <span class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect width="7" height="18" x="3" y="3" rx="1" />
                        <rect width="7" height="7" x="14" y="3" rx="1" />
                        <rect width="7" height="7" x="14" y="14" rx="1" />
                    </svg></span>
                <span class="name">Contents</span>
            </a>
            <?php if (isAdmin()): ?>
                <a href="../admin/panel.php" class="nav-link">
                    <span class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16.051 12.616a1 1 0 0 1 1.909.024l.737 1.452a1 1 0 0 0 .737.535l1.634.256a1 1 0 0 1 .588 1.806l-1.172 1.168a1 1 0 0 0-.282.866l.259 1.613a1 1 0 0 1-1.541 1.134l-1.465-.75a1 1 0 0 0-.912 0l-1.465.75a1 1 0 0 1-1.539-1.133l.258-1.613a1 1 0 0 0-.282-.866l-1.156-1.153a1 1 0 0 1 .572-1.822l1.633-.256a1 1 0 0 0 .737-.535z" />
                            <path d="M8 15H7a4 4 0 0 0-4 4v2" />
                            <circle cx="10" cy="7" r="4" />
                        </svg></span>
                    <span class="name">Admin</span>
                </a>
            <?php endif; ?>
            <a href="../pages/notes.php" class="nav-link">
                <span><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M15 12h-5" />
                        <path d="M15 8h-5" />
                        <path d="M19 17V5a2 2 0 0 0-2-2H4" />
                        <path d="M8 21h12a2 2 0 0 0 2-2v-1a1 1 0 0 0-1-1H11a1 1 0 0 0-1 1v1a2 2 0 1 1-4 0V5a2 2 0 1 0-4 0v2a1 1 0 0 0 1 1h3" />
                    </svg></span>
                <span class="name">Notes</span>
            </a>
            <a href="../posts/index.php" class="nav-link">
                <span><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M7 2h10" />
                        <path d="M5 6h14" />
                        <rect width="18" height="12" x="3" y="10" rx="2" />
                    </svg></span>
                <span class="name">Posts</span>
            </a>
        </nav>

        <div class="sidebar-profile">
            <a href="../pages/profile.php" class="profile-link">
                <div class="left-content">
                    <div class="avatar-container avatar-sm">
                        <?= getUserAvatar($_SESSION['user_id']) ?>
                    </div>
                    <div class="profile-info">
                        <span class="profile-name"><?= htmlspecialchars(getDisplayName($_SESSION['name'] ?? '')) ?></span>
                        <span class="profile-role"><?= htmlspecialchars($_SESSION['role'] ?? 'unknown') ?></span>
                    </div>
                </div>
                <span class="more-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="1" />
                        <circle cx="12" cy="5" r="1" />
                        <circle cx="12" cy="19" r="1" />
                    </svg></span>
            </a>
        </div>
    </aside>

    <main>
        <div class="main-container">
            <?php $flash = getFlashMessage();
            if ($flash): ?>
                <div class="flash-wrapper">
                    <div class="flash-message <?= $flash['type'] ?>">
                        <?= htmlspecialchars($flash['message']) ?>
                    </div>
                </div>
            <?php endif; ?>

            <section class="profile-section">
                <div class="profile-avatar">
                    <?= getUserAvatar($profile_user['id']) ?>
                </div>

                <div class="profile-info-container">
                    <div class="wrapper">
                        <span class="user-name">
                            <span class="username">
                                <?= htmlspecialchars($profile_user['username']) ?>
                            </span>
                            <span class="name">
                                (<?= htmlspecialchars(!empty($profile_user['name']) ? $profile_user['name'] : 'name not set') ?>)
                            </span>
                        </span>
                        <span class="user-role"><?= htmlspecialchars($profile_user['role']) ?></span>
                    </div>

                    <span class="user-email">
                        <?= htmlspecialchars($profile_user['email']) ?>
                    </span>

                    <span class="user-stats">
                        <span><?= htmlspecialchars($user_likes) ?></span> Likes
                        <span><?= htmlspecialchars($user_followers) ?></span> Followers
                        <span><?= htmlspecialchars($user_following) ?></span> Following
                    </span>

                    <span class="user-bio"><?= htmlspecialchars(!empty($profile_user['bio']) ? $profile_user['bio'] : 'no bio yet...') ?></span>
                </div>

                <?php
                // Check if current user is following this profile user
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ? AND following_id = ?");
                $stmt->execute([$_SESSION['user_id'], $profile_user['id']]);
                $is_following = $stmt->fetchColumn() > 0;
                ?>

                <button onclick="toggleFollow(<?= $profile_user['id'] ?>)"
                    data-user="<?= $profile_user['id'] ?>"
                    class="btn-follow <?= $is_following ? 'following' : '' ?>">
                    <?= $is_following ? '✔️ Following' : '➕ Follow' ?>
                </button>
            </section>

            <section class="whack-data-section">
                <h3>Whack Game Stats</h3>

                <div class="stats">
                    <div class="stat-card">
                        <div class="stat-number">
                            <?= htmlspecialchars($user_whack_data['score'] ?? 0) ?>
                        </div>
                        <div class="stat-label">
                            High Score
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-number">
                            <?= htmlspecialchars($user_whack_data['total_points'] ?? 0) ?>
                        </div>
                        <div class="stat-label">
                            Total Points
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-number">
                            <?= htmlspecialchars(formatTime($user_whack_data['points']) ?? 0) ?>
                        </div>
                        <div class="stat-label">
                            Current Points
                        </div>
                    </div>
                </div>
            </section>

            <?php if (!empty($user_posts)): ?>
                <div class="posts-section">
                    <h3>Their Posts</h3>
                    <?php foreach ($user_posts as $post): ?>
                        <div class="user-post-card">
                            <p><?= nl2br(htmlspecialchars($post['content'])) ?></p>
                            <div class="user-post-meta">
                                <div class="wrapper">
                                    <span class="post-date"><?= timeAgo($post['created_at']) ?></span>
                                    <?php if ($post['updated_at'] && strtotime($post['updated_at']) > strtotime($post['created_at'])): ?>
                                        <span class="post-edited">(edited)</span>
                                    <?php endif; ?>
                                    <span class="post-likes">❤️ <?= $post['like_count'] ?></span>
                                </div>

                                <div class="link">
                                    <a href="../posts/index.php?scroll_to=<?= $post['id'] ?>" class="view-link">View Post →</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    No posts yet.
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="../js/general.js?v=<?= filemtime(__DIR__ . '/../js/general.js') ?>"></script>
    <script src="../js/profile.js?v=<?= filemtime(__DIR__ . '/../js/profile.js') ?>"></script>
</body>

</html>