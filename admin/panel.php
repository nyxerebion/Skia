<?php
require_once __DIR__ . '/../core/bootstrap.php';

if (!isAdmin()) {
    setFlashMessage('Unauthorized access', 'error');
    http_response_code(403);
    header('Location: ../index.php');
    exit;
}

logAction("Viewed Admin Panel");

if (isCreator() && isset($_POST['promote_user'])) {
    validateCSRFToken($_POST['csrf_token'] ?? '');

    $user_id = (int)$_POST['user_id'];
    $new_role = $_POST['role'] ?? '';

    if ($user_id && in_array($new_role, ['user', 'admin', 'creator'])) {
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $current_user = $stmt->fetch();

        if ($current_user && $current_user['role'] === $new_role) {
            setFlashMessage("User already has this role.", 'warning');
            header('Location: panel.php');
            exit;
        }

        $is_promotion = ($current_user['role'] === 'user' && $new_role === 'admin') ||
            ($current_user['role'] === 'user' && $new_role === 'creator') ||
            ($current_user['role'] === 'admin' && $new_role === 'creator');
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$new_role, $user_id]);

        if ($is_promotion) {
            setFlashMessage("user promoted to $new_role.", 'success');
            addNotification(
                $user_id,
                'approved',
                '🎉 Role Updated',
                "You have been promoted to " . ucfirst($new_role),
                SITE_URL . '/pages/profile.php'
            );
        } else {
            setFlashMessage("User demoted to $new_role.", 'info');
            addNotification(
                $user_id,
                'approved',
                'Role Updated',
                'You have been demoted to ' . ucfirst($new_role) . ".",
                SITE_URL . '/pages/profile.php'
            );
        }
    } else {
        setFlashMessage("Invalid role", 'error');
    }

    header('Location: panel.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$_SESSION['username']]);
$current_user = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM users ORDER BY id DESC");
$stmt->execute();
$users = $stmt->fetchAll();

// Pagination
$page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
$limit = 200;
$offset = ($page - 1) * $limit;

// Get total count for pagination
$stmt = $pdo->prepare("SELECT COUNT(*) FROM activity_log");
$stmt->execute();
$total_activities = $stmt->fetchColumn();
$total_pages = ceil($total_activities / $limit);

$stmt = $pdo->prepare("
    SELECT a.*, u.username
     FROM activity_log a 
     JOIN users u ON a.user_id = u.id 
     ORDER BY a.id DESC
     LIMIT ? OFFSET ?");
$stmt->execute([$limit, $offset]);
$activities = $stmt->fetchAll();

// Get pending actions count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM pending_actions WHERE status = 'pending'");
$stmt->execute();
$pending_count = $stmt->fetchColumn();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once __DIR__ . '/../core/head.php'; ?>

    <title>Admin Panel</title>

    <link rel="stylesheet" href="../css/general.css?v=<?= filemtime(__DIR__ . '/../css/general.css') ?>">
    <link rel="stylesheet" href="../css/admin.css?v=<?= filemtime(__DIR__ . '/../css/admin.css') ?>">
    <link rel="stylesheet" href="../css/updates.css?v=<?= filemtime(__DIR__ . '/../css/admin.css') ?>">
</head>

<body>
    <header>
        <h1>Admin Panel | Skia</h1>
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

            <?php if (isAdmin() && !isCreator() && $pending_count > 0): ?>
                <a href="pending-actions.php" class="nav-link">
                    <span>⏳</span>
                    <span class="name">Pending (<?= $pending_count ?>)</span>
                </a>
            <?php endif; ?>

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

        <section class="stats">
            <div class="stat-card">
                <div class="stat-number">
                    <?= htmlspecialchars(count($users)) ?>
                </div>
                <div class="stat-label">
                    Total Users
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-number">
                    <?= htmlspecialchars($total_activities) ?>
                </div>
                <div class="stat-label">
                    Total Activities
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-number">
                    <?= htmlspecialchars($pending_count) ?>
                </div>
                <div class="stat-label">
                    Pending Actions
                </div>
            </div>
        </section>

        <section class="user-management">
            <div class="section-header">
                <h3>👥 User Management</h3>
                <span class="count">(<?= count($users) ?>)</span>
            </div>

            <div class="table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Avatar</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Name</th>
                            <th>Bio</th>
                            <th>Role</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>#<?= $user['id'] ?></td>
                                <td><span class="player-td"><span class="avatar-container avatar-sm"><?= getUserAvatar($user['id']) ?></td>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= htmlspecialchars($user['name'] ?? '—') ?></td>
                                <td>
                                    <span class="user-bio" onclick="showBioModal(this)"><?= htmlspecialchars($user['bio'] ?? '—') ?></span>
                                </td>
                                <td><span class="role-badge <?= $user['role'] ?>"><?= $user['role'] ?></span></td>
                                <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                <td>
                                    <?php if (isAdmin() && !isCreator()): ?>
                                        <form method="POST" action="request-promote.php">
                                            <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <select name="role" class="role-select">
                                                <!-- To add, demote to user -->
                                                <option value="admin">Admin</option>
                                            </select>
                                            <button type="submit" name="request_promote" class="btn-request">Request</button>
                                        </form>
                                    <?php elseif (isCreator()): ?>
                                        <form method="POST">
                                            <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <input type="hidden" name="promote_user" value="1">
                                            <!-- To add, demote system -->
                                            <select name="role" class="role-select">
                                                <option value="user" <?= $user['role'] === "user" ? 'selected' : '' ?>>User</option>
                                                <option value="admin" <?= $user['role'] === "admin" ? 'selected' : '' ?>>Admin</option>
                                                <option value="creator" <?= $user['role'] === "creator" ? 'selected' : '' ?>>Creator</option>
                                            </select>
                                            <button type="submit" class="btn-edit">Save</button>
                                        </form>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="activity-management">
            <div class="section-header">
                <h3>👥 Activity Management</h3>
                <span class="count">(<?= $total_activities ?>)</span>
            </div>

            <div class="table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User_id</th>
                            <th>Username</th>
                            <th>Action</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activities as $activity): ?>
                            <tr>
                                <td>#<?= $activity['id'] ?></td>
                                <td><?= htmlspecialchars($activity['user_id']) ?></td>
                                <td><?= htmlspecialchars($activity['username']) ?></td>
                                <td><?= htmlspecialchars($activity['action']) ?></td>
                                <?php if (strpos($activity['timestamp'], '2026-01-01') === 0): ?>
                                    <td>2026 (Unknown)</td>
                                <?php else: ?>
                                    <td><?= date('M d, Y h:i A', strtotime($activity['timestamp'])) ?></td>
                                <?php endif; ?>

                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page_num=<?= $page - 1 ?>">&laquo; Previous</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page_num=<?= $i ?>" class="<?= $i === $page ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page_num=<?= $page + 1 ?>">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <script src="../js/general.js?v=<?= filemtime(__DIR__ . '/../js/general.js') ?>"></script>
    <script src="../js/contents.js?v=<?= filemtime(__DIR__ . '/../js/contents.js') ?>"></script>
</body>

</html>