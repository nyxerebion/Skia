<?php
require_once __DIR__ . '/../core/bootstrap.php';

if (!isAdmin()) {
    setFlashMessage('Unauthorized access', 'error');
    http_response_code(403);
    header('Location: ../index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$_SESSION['username']]);
$current_user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $type = $_POST['type'] ?? 'improvement';

    if (!empty($title) && !empty($content)) {
        $timestamp = date('Y-m-d H:i:s');

        $stmt = $pdo->prepare("INSERT INTO updates (user_id, title, content, type, created_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$current_user['id'], $title, $content, $type, $timestamp]);

        header('Location: add-update.php?success=1');
        exit;
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <?php require_once __DIR__ . '/../core/head.php'; ?>

    <title>Add Update</title>
    <link rel="stylesheet" href="../css/general.css?v=<?= filemtime(__DIR__ . '/../css/general.css') ?>">
    <link rel="stylesheet" href="../css/add-update.css?v=<?= filemtime(__DIR__ . '/../css/add-update.css') ?>">
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
                                data-id="<?= $notif['id'] ?>"
                                data-link="<?= htmlspecialchars($notif['link'] ?? '', ENT_QUOTES) ?>">
                                <div class="title"><?= htmlspecialchars($notif['title']) ?></div>
                                <div class="message"><?= htmlspecialchars($notif['message']) ?></div>
                                <div class="meta">
                                    <span class="sender-badge <?= $notif['sender_type'] ?>">
                                        <?php
                                        $senderLabels = [
                                            'system' => '🤖 System',
                                            'creator' => '👑 Creator',
                                            'admin' => '🛡️ Admin',
                                            'user' => '👤 User'
                                        ];
                                        echo $senderLabels[$notif['sender_type']] ?? 'System';
                                        ?>
                                    </span>
                                    <span class="type-badge <?= $notif['type'] ?>"><?= htmlspecialchars($notif['type']) ?></span>
                                    <span class="time"><?= timeAgo($notif['created_at']) ?></span>
                                </div>
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
                        </svg> Settings</a> <label>

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
        <section class="add-update-section">
            <h1>Post Update</h1>

            <?php if (isset($_GET['success'])): ?>
                <p class="success-message">✅ Update posted.</p>
            <?php endif; ?>

            <form method="POST" class="update-form">
                <div class="form-group">
                    <input type="text" name="title" placeholder="Title" required>
                </div>

                <div class="form-group">
                    <textarea name="content" placeholder="Content" rows="4" oninput="autoResize(this)" required></textarea>
                </div>

                <div class="form-group">
                    <select name="type" required>
                        <option value="feature">✨ Feature</option>
                        <option value="improvement">📈 Improvement</option>
                        <option value="fix">🔧 Fix</option>
                        <option value="security">🛡️ Security</option>
                        <option value="patch">📦 Patch</option>
                        <option value="tweak">🎨 Tweak</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="submit-btn">Post</button>
                    <a href="updates.php" class="back-link">View all</a>
                </div>
            </form>
        </section>
    </main>
    <script src="../js/general.js"></script>
</body>

</html>