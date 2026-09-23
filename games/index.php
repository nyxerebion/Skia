<?php
require_once __DIR__ . '/../core/bootstrap.php';

if (!checkLogin()) {
    setFlashMessage('Unauthorized access', 'error');
    header('Location: ../index.php');
    exit;
}

$gameStatusOff = false; // Set to true to disable the game center for maintenance

if ($gameStatusOff) {
    setFlashMessage('The game is currently under maintenance. Please try again later.', 'error');
    header('Location: ../pages/maintenance.php');
    exit;
}

logAction('Visited Game Center');

$clickOnlineUsers = getOnlinePlayersByGame('click');
$whackOnlineUsers = getOnlinePlayersByGame('whack');

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once __DIR__ . '/../core/head.php'; ?>
    <title>Games Center</title>
    <link rel="stylesheet" href="../css/general.css?v=<?= filemtime(__DIR__ . '/../css/general.css') ?>">
    <link rel="stylesheet" href="css/games.css?v=<?= filemtime(__DIR__ . '/css/games.css') ?>">
</head>

<body>
    <header>
        <h1>Games | Skia</h1>
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
                        </svg> Settings</a> <a href="../security/logout.php" class="logout-btn" id="responsiveBtn">Logout</a>
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
                <div class="flash-message <?= $flash['type'] ?>">
                    <?= htmlspecialchars($flash['message'] ?? '') ?>
                </div>
            </div>
        <?php endif; ?>

        <section class="hero-section">
            <h1>Welcome back, <span><?= htmlspecialchars(getDisplayName($_SESSION['name'] ?? '')) ?></span> 👋</h1>
            <p class="hero-subtitle">Ready to play, compete, and dominate?</p>
        </section>

        <section class="game-grid">
            <div class="game-card">
                <div class="game-header">
                    <div class="game-icon">🪙</div>
                    <h2>Whack A Gold</h2>
                    <p>Click the gold, avoid the bombs. Test your reflexes!</p>
                    <div class="game-meta">
                        <span class="players">👥 <span id="whack-players"><?= $whackOnlineUsers ?></span> online</span>
                        <span class="game-status online">🟢 Live</span>
                        <a href="whack.php" class="btn-play">Play Now →</a>
                    </div>
                </div>
            </div>

            <div class="game-card coming-soon">
                <div class="game-icon">🐍</div>
                <h2>Snake</h2>
                <p>Classic snake game. Comming soon!</p>
                <div class="game-meta">
                    <span class="game-status offline">🔴 Coming Soon</span>
                    <button class="btn-play disabled" disabled>Coming Soon</button>
                </div>
            </div>

            <div class="game-card coming-soon">
                <div class="game-icon">🧩</div>
                <h2>Tile Match</h2>
                <p>Test your memory. Comming soon!</p>
                <div class="game-meta">
                    <span class="game-status offline">🔴 Coming Soon</span>
                    <button class="btn-play disabled" disabled>Coming Soon</button>
                </div>
            </div>

            <div class="game-card">
                <div class="game-icon">👆</div>
                <h2>Click Adventure</h2>
                <p>Click almighty. Fully Developed!</p>
                <div class="game-meta">
                    <span class="players">👥 <span id="click-players"><?= $clickOnlineUsers ?></span> online</span>
                    <span class="game-status online">🟢 Live</span>
                    <a href="click-adventure.php" class="btn-play">Play Now →</a>
                </div>
            </div>
        </section>
    </main>

    <script src="../js/general.js?v=<?= filemtime(__DIR__ . '/../js/general.js') ?>"></script>
    <script src="js/games.js?v=<?= filemtime(__DIR__ . '/js/games.js') ?>"></script>
</body>

</html>