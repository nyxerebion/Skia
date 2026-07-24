<?php
require_once __DIR__ . '/../core/bootstrap.php';

if (!isAdmin()) {
    setFlashMessage('Unauthorized access', 'error');
    http_response_code(403);
    header('Location: ../index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT updates.*, users.username FROM updates JOIN users ON updates.user_id = users.id ORDER BY created_at DESC");
$stmt->execute();
$updates = $stmt->fetchAll();

if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM updates WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_update = $stmt->fetch();

    if (!$edit_update) {
        setFlashMessage('Update not found', 'error');
        header('Location: updates.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_update'])) {
    validateCSRFToken($_POST['csrf_token'] ?? '');

    $id = (int)$_POST['id'];
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $type = $_POST['type'] ?? 'improvement';

    if (!empty($title) && !empty($content)) {
        $stmt = $pdo->prepare("UPDATE updates SET title = ?, content = ?, type = ? WHERE id = ?");
        $stmt->execute([$title, $content, $type, $id]);
        header('Location: updates.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <?php require_once __DIR__ . '/../core/head.php'; ?>

    <title>Updates</title>
    <link rel="stylesheet" href="../css/general.css?v=<?= filemtime(__DIR__ . '/../css/general.css') ?>">
    <link rel="stylesheet" href="../css/updates.css?v=<?= filemtime(__DIR__ . '/../css/updates.css') ?>">
</head>

<body>
    <?php
    // Show flash message if exists
    $flash = getFlashMessage();
    if ($flash):
    ?>
        <div class="flash-message <?php echo $flash['type']; ?>">
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
    <?php endif; ?>

    <header>
        <h1>Admin | Skia</h1>
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
                    <label>
                        Soon..
                    </label>
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
                <span class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-users-icon lucide-users">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                        <path d="M16 3.128a4 4 0 0 1 0 7.744" />
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                        <circle cx="9" cy="7" r="4" />
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
        <?php if (isset($edit_update)): ?>
            <section class="edit-section">
                <h2>✏️ Edit Update</h2>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                    <input type="hidden" name="id" value="<?= $edit_update['id'] ?>">
                    <input type="hidden" name="edit_update" value="1">

                    <div class="form-group">
                        <input type="text" name="title" value="<?= htmlspecialchars($edit_update['title']) ?>" required>
                    </div>
                    <div class="form-group">
                        <textarea name="content" rows="5" required><?= htmlspecialchars($edit_update['content']) ?></textarea>
                    </div>
                    <div class="form-group">
                        <select name="type">
                            <option value="feature" <?= $edit_update['type'] === 'feature' ? 'selected' : '' ?>>✨ Feature</option>
                            <option value="improvement" <?= $edit_update['type'] === 'improvement' ? 'selected' : '' ?>>📈 Improvement</option>
                            <option value="fix" <?= $edit_update['type'] === 'fix' ? 'selected' : '' ?>>🔧 Fix</option>
                            <option value="security" <?= $edit_update['type'] === 'security' ? 'selected' : '' ?>>🛡️ Security</option>
                            <option value="patch" <?= $edit_update['type'] === 'patch' ? 'selected' : '' ?>>📦 Patch</option>
                            <option value="tweak" <?= $edit_update['type'] === 'tweak' ? 'selected' : '' ?>>🎨 Tweak</option>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-save">💾 Save Changes</button>
                        <a href="updates.php" class="btn-cancel">Cancel</a>
                    </div>
                </form>
            </section>
            <hr>
        <?php endif; ?>

        <section class="updates-container">
            <div class="section-header">
                <h2>📢 Latest Updates</h2>
                <span class="count">(<?= count($updates) ?>)</span>
                <a href="add-update.php" class="new-link">➕ New</a>
            </div>

            <?php if (empty($updates)): ?>
                <p class="empty-state">No updates yet.</p>
            <?php else: ?>
                <?php foreach ($updates as $update):
                    $is_new = (time() - strtotime($update['created_at'])) < 86400;
                    $icon = $icons[$update['type']] ?? '📌';
                    $badge_class = $update['type'] ?? 'patch';
                    $badge_label = $badge_labels[$update['type']] ?? ucfirst($update['type']);
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
                            <span class="update-date">📅 <?= date('h:i A M: d, Y', strtotime($update['created_at'])) ?></span>
                            <span class="dot">·</span>
                            <a href="updates.php?edit=<?= $update['id'] ?>" class="edit-link">✏️ Edit</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>

    <script src="../js/general.js?v=<?= filemtime(__DIR__ . '/../js/general.js') ?>"></script>
</body>

</html>