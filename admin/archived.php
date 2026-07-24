<?php
require_once __DIR__ . '/../core/bootstrap.php';

if (!isAdmin()) {
    setFlashMessage('Unauthorized access', 'error');
    header('Location: ../index.php');
    exit;
}

// Restore post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_post'])) {
    validateCSRFToken($_POST['csrf_token'] ?? '');

    $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
    $stmt->execute([$id]);
    $post = $stmt->fetch();

    $id = (int)$_POST['id'];
    $stmt = $pdo->prepare("
        UPDATE posts 
        SET archived = 0, 
            archived_at = NULL, 
            archived_by = NULL 
        WHERE id = ?
    ");
    $stmt->execute([$id]);

    // Notify the post owner
    if ($post && $post['user_id'] != $_SESSION['user_id']) {
        addNotification(
            $post['user_id'],
            'info',
            '📄 Post Restored',
            'Your post has been restored from archives.',
            SITE_URL . '/posts/index.php'
        );
    }

    setFlashMessage('Post restored.', 'success');
    header('Location: archived.php');
    exit;
}

// Permanent delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['permanent_delete'])) {
    validateCSRFToken($_POST['csrf_token'] ?? '');

    $id = (int)$_POST['id'];
    $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->execute([$id]);

    setFlashMessage('Post permanently deleted.', 'error');
    header('Location: archived.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT p.*, u.username
    FROM posts p 
    JOIN users u ON p.user_id = u.id
    WHERE p.archived = 1 
    ORDER BY p.archived_at DESC
");
$stmt->execute();
$archived_posts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once __DIR__ . '/../core/head.php' ?>
    <title>Archives</title>
    <link rel="stylesheet" href="../css/general.css?v=<?= filemtime(__DIR__ . '/../css/general.css') ?>">
    <link rel="stylesheet" href="../css/admin.css?v=<?= filemtime(__DIR__ . '/../css/admin.css') ?>">
    <style>
        .archived-card {
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 16px 20px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .archived-card .content {
            flex: 1;
            color: var(--text-secondary);
        }

        .archived-card .meta {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .btn-restore {
            background: #22c55e;
            color: white;
            border: none;
            padding: 6px 16px;
            border-radius: 6px;
            cursor: pointer;
        }

        .btn-restore:hover {
            background: #16a34a;
        }

        .btn-delete {
            background: #ef4444;
            color: white;
            border: none;
            padding: 6px 16px;
            border-radius: 6px;
            cursor: pointer;
        }

        .btn-delete:hover {
            background: #dc2626;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-muted);
        }
    </style>
</head>

<body>
    <header>
        <h1>Archives | Skia</h1>
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
                    <a href="../security/logout.php" class="logout-btn" id="responsiveBtn"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-log-out-icon lucide-log-out">
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

            <a href="updates.php" class="nav-link">
                <span><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-scroll-icon lucide-scroll">
                        <path d="M19 17V5a2 2 0 0 0-2-2H4" />
                        <path d="M8 21h12a2 2 0 0 0 2-2v-1a1 1 0 0 0-1-1H11a1 1 0 0 0-1 1v1a2 2 0 1 1-4 0V5a2 2 0 1 0-4 0v2a1 1 0 0 0 1 1h3" />
                    </svg></span>
                <span class="name">Updates</span>
            </a>

            <a href="add-update.php" class="nav-link">
                <span><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-square-plus-icon lucide-square-plus">
                        <rect width="18" height="18" x="3" y="3" rx="2" />
                        <path d="M8 12h8" />
                        <path d="M12 8v8" />
                    </svg></span>
                <span class="name">Add Update</span>
            </a>

            <?php if (isCreator()): ?>
                <a href="creator.php" class="nav-link">
                    <span><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-square-plus-icon lucide-square-plus">
                            <rect width="18" height="18" x="3" y="3" rx="2" />
                            <path d="M8 12h8" />
                            <path d="M12 8v8" />
                        </svg></span>
                    <span class="name">Creator</span>
                </a>
            <?php endif; ?>
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

        <h2>Archived Posts</h2>

        <?php if (empty($archived_posts)): ?>
            <div class="empty-state">No archived posts.</div>
        <?php else: ?>
            <?php foreach ($archived_posts as $a_post): ?>
                <div class="archived-card">
                    <span class="meta">#<?= htmlspecialchars($a_post['id']) ?></span>
                    <span class="meta"><?= htmlspecialchars($a_post['username']) ?></span>
                    <span class="content"><?= nl2br(htmlspecialchars(substr($a_post['content'], 0, 100))) ?></span>
                    <span class="meta">Deleted: <?= date('M d, Y h:i A', strtotime($a_post['archived_at'])) ?></span>

                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                        <input type="hidden" name="id" value="<?= $a_post['id'] ?>">
                        <button type="submit" name="restore_post" class="btn-restore">↩️ Restore</button>
                    </form>

                    <form method="POST" style="display:inline;" onsubmit="return confirm('Permanently delete this post?')">
                        <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                        <input type="hidden" name="id" value="<?= $a_post['id'] ?>">
                        <button type="submit" name="permanent_delete" class="btn-delete">🗑️ Delete</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <script src="../js/general.js?v=<?= filemtime(__DIR__ . '/../js/general.js') ?>"></script>
</body>

</html>