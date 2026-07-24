<?php
require_once __DIR__ . '/../core/bootstrap.php';

if (!checkLogin()) {
    setFlashMessage('Unauthorized access', 'error');
    http_response_code(403);
    header('Location: ../index.php');
    exit;
}

logAction("Viewed Posts Page");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_post'])) {
    validateCSRFToken($_POST['csrf_token'] ?? '');
    $content = trim($_POST['content'] ?? '');
    if (!empty($content)) {
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, created_at) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $content, date('Y-m-d H:i:s')]);
        setFlashMessage('Post created!', 'success');
        header('Location: index.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    validateCSRFToken($_POST['csrf_token'] ?? '');
    $post_id = (int)$_POST['post_id'];
    $content = trim($_POST['content'] ?? '');
    if (!empty($content)) {
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content, created_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([$post_id, $_SESSION['user_id'], $content, date('Y-m-d H:i:s')]);

        $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
        $stmt->execute([$post_id]);
        $post = $stmt->fetch();
        if ($post && $post['user_id'] !== $_SESSION['user_id']) {
            $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $commenter = $stmt->fetch();
            addNotification(
                $post['user_id'],
                'info',
                '💬 New Comment',
                $commenter['username'] . ' commented on your post: "' . substr($content, 0, 50) . '"',
                SITE_URL . '/posts/index.php?scroll_to=' . $post_id
            );
        }
        setFlashMessage('Comment added!', 'success');
        header('Location: index.php');
        exit;
    }
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$scroll_to_id = isset($_GET['scroll_to']) ? (int)$_GET['scroll_to'] : 0;

// Scroll to post
if ($scroll_to_id > 0) {
    $stmt = $pdo->prepare("SELECT created_at FROM posts WHERE id = ? AND archived = 0");
    $stmt->execute([$scroll_to_id]);
    $post = $stmt->fetch();
    if (!$post) {
        header('Location: ?page=' . $page);
        exit;
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE archived = 0 AND created_at > ?");
    $stmt->execute([$post['created_at']]);
    $newer = $stmt->fetchColumn();
    $correct_page = ceil(($newer + 1) / $limit);
    if ($page != $correct_page) {
        header('Location: ?page=' . $correct_page . '&scroll_to=' . $scroll_to_id);
        exit;
    }
}

$total_stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE archived = 0");
$total_stmt->execute();
$total_posts = $total_stmt->fetchColumn();
$total_pages = ceil($total_posts / $limit);

if ($page > $total_pages && $total_pages > 0) {
    header('Location: ?page=' . $total_pages . ($scroll_to_id ? '&scroll_to=' . $scroll_to_id : ''));
    exit;
}

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
    LIMIT ? OFFSET ?
");
$stmt->execute([$limit, $offset]);
$posts = $stmt->fetchAll();

if (count($posts) === 0 && $total_posts > 0) {
    header('Location: ?page=' . $total_pages . ($scroll_to_id ? '&scroll_to=' . $scroll_to_id : ''));
    exit;
}
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
                    <a href="../security/logout.php" id="responsiveBtn"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-log-out-icon lucide-log-out">
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

        <!-- Create Post -->
        <section class="create-post">
            <h3>Create Post</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                <textarea name="content" placeholder="What's on your mind?" rows="3" required></textarea>
                <button type="submit" name="create_post">Post</button>
            </form>
        </section>

        <!-- Posts Feed -->
        <section class="posts-section">
            <div class="posts-header">
                <h2>Feed</h2>
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
                                <a href="../pages/view-profile.php?id=<?= $post['user_id'] ?>" class="post-author-link">
                                    <span class="avatar-container avatar-sm"><?= getUserAvatar($post['user_id']) ?></span>
                                    <strong><?= htmlspecialchars($post['username']) ?></strong>
                                </a>
                                <span class="dot">·</span>
                                <span class="span-date post-date"><?= timeAgo($post['created_at']) ?></span>
                            </div>
                            <?php if ($post['user_id'] == $_SESSION['user_id'] || isAdmin()): ?>
                                <div class="post-controls">
                                    <a href="edit.php?id=<?= $post['id'] ?>" class="btn-edit">✏️ Edit</a>
                                    <a href="delete.php?id=<?= $post['id'] ?>" class="btn-delete" onclick="return confirm('Delete this post?')">🗑️ Delete</a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="post-content">
                            <?= nl2br(htmlspecialchars($post['content'])) ?>
                        </div>

                        <div class="post-actions">
                            <button onclick="toggleLike(<?= $post['id'] ?>, 'post')" class="like-btn <?= $user_liked ? 'liked' : '' ?>">
                                <?= $user_liked ? '❤️' : '🤍' ?> <?= $post['like_count'] ?>
                            </button>
                            <button class="comment-toggle" onclick="toggleComments(<?= $post['id'] ?>)">
                                💬 <?= $post['comment_count'] ?>
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

                        <div class="comments-container" id="comments-<?= $post['id'] ?>" style="display: none;">
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

                            <div class="top-title">
                                <h2>Comments</h2>
                                <span>(<?= count($comments) ?>)</span>
                            </div>

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
                                    <div class="comment-item">
                                        <div class="comment-header">
                                            <a href="../pages/view-profile.php?id=<?= $comment['user_id'] ?>" class="comment-author">
                                                <span class="avatar-container avatar-sm"><?= getUserAvatar($comment['user_id']) ?></span>
                                                <strong><?= htmlspecialchars($comment['username']) ?></strong>
                                            </a>
                                            <span class="dot">·</span>
                                            <span class="comment-date"><?= timeAgo($comment['created_at']) ?></span>
                                        </div>
                                        <p><?= nl2br(htmlspecialchars($comment['content'])) ?></p>
                                    </div>

                                    <div class="wrapper">
                                        <div class="comment-actions">
                                            <button onclick="toggleLike(<?= $comment['id'] ?>, 'comment')" class="comment-like-btn <?= $user_liked_comment ? 'liked' : '' ?>">
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
                                    <span class="avatar-container avatar-sm">
                                        <?= getUserAvatar($_SESSION['user_id']) ?>
                                    </span>
                                    <textarea name="content" placeholder="Write a comment..." required></textarea>
                                    <button type="submit">Post</button>
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

    <?php if ($scroll_to_id): ?>
        <script>
            window.addEventListener('load', function() {
                const el = document.getElementById('post-<?= $scroll_to_id ?>');
                if (el) {
                    setTimeout(() => {
                        el.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });

                        el.classList.add('post-highlight');

                        setTimeout(() => {
                            el.classList.remove('post-highlight');
                        }, 2000);

                    }, 400);
                }
            });
        </script>
    <?php endif; ?>
</body>

</html>