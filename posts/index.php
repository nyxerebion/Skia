<?php
require_once __DIR__ . '/../core/bootstrap.php';

if (!checkLogin()) {
    setFlashMessage('Unauthorized access', 'error');
    http_response_code(403);
    header('Location: ../index.php');
    exit;
}

logAction("Viewed Posts Page");

// post handle moved to api

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    validateCSRFToken($_POST['csrf_token'] ?? '');
    $post_id = (int)$_POST['post_id'];
    $content = trim($_POST['content'] ?? '');

    if (empty($content)) {
        setFlashMessage('Comment cannot be empty.', 'danger');
        header('Location: index.php');
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
    $result = $stmt->execute([$post_id, $_SESSION['user_id'], $content]); // ← Only once

    if ($result) {
        $comment_id = $pdo->lastInsertId();
    } else {
        setFlashMessage('Comment insert failed!', 'error');
        $comment_id = 0;
    }

    $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();

    if ($post && $post['user_id'] !== $_SESSION['user_id']) {
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $commenter = $stmt->fetch();

        if ($commenter) {
            addNotification(
                $post['user_id'],
                'info',
                '💬 New Comment',
                $commenter['username'] . ' commented on your post: "' . substr($content, 0, 50) . '"',
                SITE_URL . '/posts/index.php?scroll_to=' . encodeID($post_id) . '&comment=' . encodeID($comment_id),
                'user',
                $_SESSION['user_id']
            );
        }
    }

    setFlashMessage('Comment added!', 'success');
    header('Location: index.php?scroll_to=' . encodeID($post_id) . '&comment=' . encodeID($comment_id));
    exit;
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

$scroll_to_hash = $_GET['scroll_to'] ?? null;
$scroll_to_id = decodeID($scroll_to_hash ?? '');

$comment_hash = $_GET['comment'] ?? null;
$comment_id = decodeID($comment_hash ?? '');

$page_query = '?page=' . $page;
if ($scroll_to_id) {
    $page_query .= '&scroll_to=' . encodeID($scroll_to_id);
}
if ($comment_id) {
    $page_query .= '&comment=' . encodeID($comment_id);
}

$limit = 10;
$offset = ($page - 1) * $limit;

// Scroll to post
if ($scroll_to_id > 0) {
    $stmt = $pdo->prepare("SELECT created_at FROM posts WHERE id = ? AND archived = 0");
    $stmt->execute([$scroll_to_id]);
    $post = $stmt->fetch();
    if (!$post) {
        header('Location: ' . $page_query);
        exit;
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE archived = 0 AND created_at > ?");
    $stmt->execute([$post['created_at']]);
    $newer = $stmt->fetchColumn();
    $correct_page = ceil(($newer + 1) / $limit);
    if ($page != $correct_page) {
        header('Location: ?page=' . $correct_page . '&scroll_to=' . encodeID($scroll_to_id) . ($comment_id ? '&comment=' . encodeID($comment_id) : ''));
        exit;
    }
}

$total_stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE archived = 0");
$total_stmt->execute();
$total_posts = $total_stmt->fetchColumn();
$total_pages = ceil($total_posts / $limit);

if ($page > $total_pages && $total_pages > 0) {
    header('Location: ' . $page_query);
    exit;
}

$stmt = $pdo->prepare("
    SELECT
        p.*,
        u.username, u.role,
        (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS like_count,
        (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comment_count
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE archived = 0
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$limit, $offset]);
$posts = $stmt->fetchAll();

if (count($posts) === 0 && $total_posts > 0) {
    header('Location: ' . $page_query);
    exit;
}

$online_users = getOnlineUsers();

/*
To do:
- Add js process with api for creating a post
- JS validation and confirmation that the post has no harm or has followed the rules
- Add rules for creating post
- Create post status system for post when good, warning (holded), deleted (archived -> to recover in the future and audit)
- Add version system for post when user edit it, for audit and history (types = fresh (default), edited and deleted) + version number for edited with saving all of the version
*/
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once __DIR__ . '/../core/head.php'; ?>
    <title>Posts | Skia</title>
    <link rel="stylesheet" href="../css/general.css?v=<?= filemtime(__DIR__ . '/../css/general.css') ?>">
    <link rel="stylesheet" href="../css/posts.css?v=<?= filemtime(__DIR__ . '/../css/posts.css') ?>">
</head>

<body>
    <header>
        <h1>Posts | Skia</h1>
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
                        </svg> Settings</a> <a href="../security/logout.php" id="responsiveBtn"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-log-out-icon lucide-log-out">
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
                        <div class="status status-offline" data-user="<?= $_SESSION['user_id'] ?>"></div>

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
                <span class="flash-message <?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></span>
            </div>
        <?php endif; ?>

        <!-- Create Post -->
        <section class="create-post">
            <h3>Create Post</h3>
            <form method="POST" id="createPostForm">
                <input type="hidden" name="csrf_token" id="csrfToken" value="<?= getCSRFToken() ?>">
                <textarea name="content" id="content" placeholder="What's on your mind?" rows="3" required></textarea>
                <div class="bottom-wrapper">
                    <div class="char-counter">
                        <span id="charCount">0</span> / 5000
                    </div>
                    <button type="submit" name="create_post">Post</button>
                </div>
            </form>

            <div class="post-rules-overlay" id="rulesContent" style="display: none;">
                <div class="post-rules-modal">
                    <div class="post-rules-header">
                        <span class="rules-icon">📋</span>
                        <h2>Post Guidelines</h2>
                    </div>

                    <div class="post-rules-body">
                        <p>✅ Be respectful and kind to others</p>
                        <p>✅ No hate speech, harassment, or bullying</p>
                        <p>✅ No spam or excessive links</p>
                        <p>✅ Keep it clean – no inappropriate language</p>
                        <p>✅ Posts are monitored and may be removed</p>
                    </div>

                    <hr class="hr-divider">

                    <div class="post-rules-footer">
                        <h3>Does this post follow the guidelines?</h3>
                        <small>⚠️ Note: It can be deleted once flagged</small>

                        <div class="actions">
                            <button onclick="cancelPost()" class="cancel-btn">✏️ Cancel and edit</button>
                            <button onclick="proceedPost()" class="proceed-btn">✅ Proceed Post</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Posts Feed -->
        <section class="posts-section">
            <div class="section-header">
                <h2>Feed</h2>
                <span class="divider">|</span>
                <span class="online-wrapper">
                    🟢 <span class="online-users posts-online-users"><?= $online_users ?></span> Online
                </span>
            </div>

            <?php if (count($posts) > 0): ?>
                <?php foreach ($posts as $post): ?>
                    <?php
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ? AND user_id = ?");
                    $stmt->execute([$post['id'], $_SESSION['user_id']]);
                    $user_liked = $stmt->fetchColumn() > 0;

                    $stmt = $pdo->prepare("
                        SELECT u.username
                        FROM likes l
                        JOIN users u ON l.user_id = u.id
                        WHERE l.post_id = ?
                        LIMIT 3
                    ");
                    $stmt->execute([$post['id']]);
                    $liked_users = $stmt->fetchAll();
                    $liked_names = array_column($liked_users, 'username');
                    ?>

                    <div class="post-card" id="post-<?= $post['id'] ?>">
                        <div class="post-header">
                            <div class="post-header-right">
                                <a href="../pages/view-profile.php?id=<?= encodeID($post['user_id']) ?>" class="post-author-link">
                                    <span class="avatar-container avatar-sm">
                                        <?= getUserAvatar($post['user_id']) ?>
                                        <div class="status status-offline" data-user="<?= $post['user_id'] ?>"></div>
                                    </span>
                                    <div class="name-date-wrapper">
                                        <span>
                                            <span class="username"><?= htmlspecialchars($post['username']) ?></span>

                                            <?php
                                            $role = $post['role'] ?? 'user';
                                            $class = match ($role) {
                                                'creator' => 'creator-badge',
                                                'admin' => 'admin-badge',
                                                default => 'user-badge',
                                            };
                                            ?>
                                            <span class="role <?= $class ?>">
                                                <?= htmlspecialchars($role) ?>
                                            </span>
                                        </span>
                                        <span class="span-date post-date"><?= timeAgo($post['created_at']) ?></span>
                                    </div>
                                </a>
                            </div>
                            <?php if ($post['user_id'] == $_SESSION['user_id'] || isAdmin()): ?>
                                <div class="post-controls">
                                    <a href="edit.php?id=<?= encodeID($post['id']) ?>" class="btn-edit">✏️ Edit</a>
                                    <a href="delete.php?id=<?= encodeID($post['id']) ?>" class="btn-delete">🗑️ Delete</a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="post-content">
                            <?= nl2br(htmlspecialchars($post['content'])) ?>
                        </div>

                        <div class="post-actions">
                            <button onclick="toggleLike('<?= encodeID($post['id']) ?>', 'post')" class="like-btn <?= $user_liked ? 'liked' : '' ?>">
                                <?= $user_liked ? '❤️ Liked' : '🤍 Like' ?> (<?= $post['like_count'] ?>)
                            </button>
                            <button class="comment-toggle" onclick="toggleComments(<?= $post['id'] ?>)">
                                💬 Comment (<?= $post['comment_count'] ?>)
                            </button>
                        </div>

                        <?php if ($post['like_count'] > 0): ?>
                            <div class="post-likes">
                                <?php if (count($liked_names) > 0): ?>
                                    Liked by <?= implode(', ', array_map('htmlspecialchars', $liked_names)) ?>
                                    <?php if ($post['like_count'] > 3): ?>
                                        and <?= $post['like_count'] - 3 ?> others
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="comments-container" id="comments-<?= $post['id'] ?>" style="<?= ($comment_id > 0 && $post['id'] == $scroll_to_id) ? '' : 'display: none;' ?>">
                            <?php
                            $stmt = $pdo->prepare("
                                SELECT c.*, u.username
                                FROM comments c
                                JOIN users u ON c.user_id = u.id
                                WHERE c.post_id = ?
                                ORDER BY c.created_at ASC
                            ");
                            $stmt->execute([$post['id']]);
                            $comments = $stmt->fetchAll();
                            ?>

                            <?php if (count($comments) > 0): ?>
                                <?php foreach ($comments as $comment):
                                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM comment_likes WHERE comment_id = ? AND user_id = ?");
                                    $stmt->execute([$comment['id'], $_SESSION['user_id']]);
                                    $user_liked_comment = $stmt->fetchColumn() > 0;

                                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM comment_likes WHERE comment_id = ?");
                                    $stmt->execute([$comment['id']]);
                                    $comment_like_count = $stmt->fetchColumn();

                                    $stmt = $pdo->prepare("
                                        SELECT u.username
                                        FROM comment_likes cl
                                        JOIN users u ON cl.user_id = u.id
                                        WHERE cl.comment_id = ?
                                        LIMIT 3
                                    ");
                                    $stmt->execute([$comment['id']]);
                                    $comment_liked_users = $stmt->fetchAll();
                                    $comment_liked_names = array_column($comment_liked_users, 'username');
                                ?>
                                    <div class="comment-item" id="comment-<?= $comment['id'] ?>">
                                        <div class="content-left">
                                            <a href="../pages/view-profile.php?id=<?= $comment['user_id'] ?>" class="comment-author-link">
                                                <span class="avatar-container avatar-sm"><?= getUserAvatar($comment['user_id']) ?></span>
                                            </a>
                                        </div>

                                        <div class="content-right">
                                            <span class="username"><?= htmlspecialchars($comment['username']) ?></span>
                                            <!--
                                                    <?php
                                                    $role = $comment['role'] ?? 'user';
                                                    $class = match ($role) {
                                                        'creator' => 'creator-badge',
                                                        'admin' => 'admin-badge',
                                                        default => 'user-badge',
                                                    };
                                                    ?>
                                                    <span class="role <?= $class ?>">
                                                        <?= htmlspecialchars($role) ?>
                                                    </span>
                                                    -->
                                            <p class="comment-text"><?= nl2br(htmlspecialchars($comment['content'])) ?></p>
                                        </div>
                                    </div>

                                    <div class="content-bottom">
                                        <span class="comment-date"><?= timeAgo($post['created_at']) ?></span>

                                        <div class="comment-actions">
                                            <button onclick="toggleLike('<?= encodeID($comment['id']) ?>', 'comment')" class="comment-like-btn <?= $user_liked_comment ? 'liked' : '' ?>">
                                                <?= $user_liked_comment ? '❤️' : '🤍' ?> <?= $comment_like_count ?>
                                            </button>
                                        </div>


                                        <?php if ($comment_like_count > 0): ?>
                                            <div class="comment-likes">
                                                <?php if (count($comment_liked_names) > 0): ?>
                                                    Liked by <?= implode(', ', array_map('htmlspecialchars', $comment_liked_names)) ?>
                                                    <?php if ($comment_like_count > 3): ?>
                                                        and <?= $comment_like_count - 3 ?> others
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="no-comments">No comments yet.</p>
                            <?php endif; ?>

                            <form method="POST" class="comment-form">
                                <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                <input type="hidden" name="add_comment" value="1">
                                <div class="visible">
                                    <div class="item-left">
                                        <span class="avatar-container avatar-sm">
                                            <?= getUserAvatar($_SESSION['user_id']) ?>
                                            <div class="status" data-user="<?= $_SESSION['user_id'] ?>"></div>


                                        </span>
                                    </div>
                                    <textarea name="content" placeholder="Write a comment..." required></textarea>
                                    <button type="submit">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-send-icon lucide-send">
                                            <path d="M14.536 21.686a.5.5 0 0 0 .937-.024l6.5-19a.496.496 0 0 0-.635-.635l-19 6.5a.5.5 0 0 0-.024.937l7.93 3.18a2 2 0 0 1 1.112 1.11z" />
                                            <path d="m21.854 2.147-10.94 10.939" />
                                        </svg>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>">« Previous</a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?= $i ?>" class="<?= $i === $page ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?= $page + 1 ?>">Next »</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="empty-state">No posts yet. Be the first!</div>
            <?php endif; ?>
        </section>
    </main>

    <script src="../js/general.js?v=<?= filemtime(__DIR__ . '/../js/general.js') ?>"></script>
    <script src="../js/posts.js?v=<?= filemtime(__DIR__ . '/../js/posts.js') ?>"></script>

    <?php if ($scroll_to_id || $comment_id): ?>
        <script>
            window.addEventListener('load', () => {
                const targetPostId = <?= (int)$scroll_to_id ?>;
                const targetCommentId = <?= (int)$comment_id ?>;

                const postEl = document.getElementById(`post-${targetPostId}`);
                if (!postEl) return;

                // Only open comments if there's a comment ID
                if (targetCommentId > 0) {
                    const commentsContainer = document.getElementById(`comments-${targetPostId}`);
                    if (commentsContainer) {
                        commentsContainer.style.display = 'block';
                    }
                }

                // Find the comment element
                let targetEl = null;
                if (targetCommentId > 0) {
                    targetEl = document.getElementById(`comment-${targetCommentId}`);
                }

                // If comment not found, fallback to post
                if (!targetEl) {
                    targetEl = postEl;
                }

                if (targetEl) {
                    setTimeout(() => {
                        targetEl.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });

                        // Only add highlight if it's the comment
                        if (targetCommentId > 0 && targetEl.id.startsWith('comment-')) {
                            targetEl.classList.add('comment-highlight');
                            setTimeout(() => {
                                targetEl.classList.remove('comment-highlight');
                            }, 2000);
                        } else {
                            targetEl.classList.add('post-highlight');
                            setTimeout(() => {
                                targetEl.classList.remove('post-highlight');
                            }, 2000);
                        }
                    }, 400);
                }
            });
        </script>
    <?php endif; ?>
</body>

</html>