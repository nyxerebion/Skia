<?php
require_once __DIR__ . '/core/bootstrap.php';

if (checkLogin()) {
    header("Location: index.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM users ORDER BY id DESC LIMIT 5");
$stmt->execute();
$users = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT u.*, users.username FROM updates u JOIN users ON u.user_id = users.id ORDER BY u.created_at DESC LIMIT 3");
$stmt->execute();
$updates = $stmt->fetchAll();

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
    <title>Guest Page | Skia</title>
    <link rel="stylesheet" href="css/updates.css?v=<?= filemtime(__DIR__ . '/css/updates.css') ?>">
    <link rel="stylesheet" href="css/general.css?v=<?= filemtime(__DIR__ . '/css/general.css') ?>">
    <link rel="stylesheet" href="css/guest.css?v=<?= filemtime(__DIR__ . '/css/guest.css') ?>">
</head>

<body>
    <header>
        <h1>Guest | Skia</h1>
        <div class="header-right">
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
            <a href="index.php" class="nav-link">
                <span class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-house-icon lucide-house">
                        <path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8" />
                        <path d="M3 10a2 2 0 0 1 .709-1.528l7-6a2 2 0 0 1 2.582 0l7 6A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    </svg></span>
                <span class="name">Home</span>
            </a>
            <a href="#get-started" class="nav-link">
                <span class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-layout-panel-left-icon lucide-layout-panel-left">
                        <rect width="7" height="18" x="3" y="3" rx="1" />
                        <rect width="7" height="7" x="14" y="3" rx="1" />
                        <rect width="7" height="7" x="14" y="14" rx="1" />
                    </svg></span>
                <span class="name">Contents</span>
            </a>

            <a href="#get-started" class="nav-link">
                <span><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-scroll-text-icon lucide-scroll-text">
                        <path d="M15 12h-5" />
                        <path d="M15 8h-5" />
                        <path d="M19 17V5a2 2 0 0 0-2-2H4" />
                        <path d="M8 21h12a2 2 0 0 0 2-2v-1a1 1 0 0 0-1-1H11a1 1 0 0 0-1 1v1a2 2 0 1 1-4 0V5a2 2 0 1 0-4 0v2a1 1 0 0 0 1 1h3" />
                    </svg></span>
                <span class="name">Notes</span>
            </a>

            <a href="#about-site" class="nav-link">
                <span><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info-icon lucide-info">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M12 16v-4" />
                        <path d="M12 8h.01" />
                    </svg></span>
                <span class="name">About</span>
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
            <div class="get-started-actions">
                <a href="security/login.php" class="btn-secondary">Log in →</a>
            </div>
        </div>
    </aside>

    <main>
        <?php $flash = getFlashMessage();
        if ($flash): ?>
            <div class="flash-wrapper">
                <span class="flash-message <?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></span>
            </div>
        <?php endif; ?>

        <section class="hero-section" id="hero">
            <h1>Skia · Technology Sandbox</h1>
            <p>
                This is a living project where I design, build, and deploy full-stack applications with a focus on backend architecture and security. Every feature here is built from scratch to demonstrate practical engineering skills.
            </p>
            <div class="hero-actions">
                <span class="line"></span>
                <button onclick="window.location.href='#get-started'">Get Started</button>
                <span class="line"></span>
            </div>
            <div class="tech-tags">
                <span>HTML/CSS</span>
                <span>PHP</span>
                <span>MySql</span>
                <span>Security</span>
                <span>JS</span>
            </div>
        </section>

        <section class="users-section">
            <div class="section-header">
                <h3>👥 Recent Users <span class="count">(<?= count($users) ?>)</span></h3>
                <a href="#get-started">View All →</a>
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
                <a href="#get-started" class="btn-secondary">Play Whack Gold →</a>
                <a href="#get-started" class="btn-link">View All Contents</a>
            </div>
        </section>

        <section class="updates-container">
            <div class="section-header">
                <h2>📢 Latest Updates</h2>
                <span class="count">(<?= count($updates) ?>)</span>
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
                            <span class="update-date">📅 <?= timeAgo($update['created_at']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <section class="about-site-section" id="about-site">
            <div class="about-site-container">
                <h2>About This Site</h2>
                <p class="site-etymology">
                    <span class="etymology-word">Skia</span> — from the Greek word for <em>shadow</em>.
                </p>
                <p class="shadow-line">
                    "What you see on the surface is backed by security beneath it — always in the shadow, always present."
                </p>
                <p>
                    A reflection of what's possible when code meets purpose. This is a technology sandbox where I build, test, and showcase full-stack web applications with a focus on security and clean architecture.
                </p>
                <p>
                    Everything you see here is built from scratch. No frameworks or templates. Just code, experimentation, and a commitment to learning in public.
                </p>
                <div class="about-site-highlights">
                    <span class="highlight-item">🔒 Security-first</span>
                    <span class="highlight-item">⚡ Full-stack</span>
                    <span class="highlight-item">🧪 Built for learning</span>
                </div>
            </div>
        </section>

        <section class="about-section" id="about">
            <div class="about-container">
                <div class="about-avatar">
                    <div class="avatar-large">AE</div>
                </div>
                <div class="about-content">
                    <h2>About Me</h2>
                    <p class="about-tagline">Full-stack developer · Security-focused · Lifelong learner</p>
                    <p>
                        I'm Axel, a software engineer passionate about building secure, scalable web applications.
                        I specialize in backend architecture with PHP, database design, and implementing security-first practices.
                    </p>
                    <p>
                        This sandbox is where I experiment, break things, and constantly improve. Every feature you see is built from scratch — from authentication to API design.
                    </p>
                    <div class="about-stats">
                        <div class="stat-item">
                            <span class="stat-number">3+</span>
                            <span class="stat-label">Years coding</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">100%</span>
                            <span class="stat-label">Self-taught</span>
                        </div>
                    </div>
                    <div class="about-skills">
                        <span class="skill-tag">PHP</span>
                        <span class="skill-tag">MySql</span>
                        <span class="skill-tag">Security</span>
                        <span class="skill-tag">JS</span>
                        <span class="skill-tag">HTML/CSS</span>
                    </div>
                    <!--<div class="about-social">
                        <a href="#" class="social-link"><i class="fab fa-github"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-linkedin"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                    </div>-->
                </div>
            </div>
        </section>

        <section class="get-started-section" id="get-started">
            <div class="get-started-container">
                <span class="get-started-badge">⚡ Experience the sandbox</span>
                <h3>See what Skia can do</h3>
                <p>
                    Explore real-world features and full-stack workflows. No demo — just a real app. Create your free account to begin.
                </p>
                <div class="get-started-actions">
                    <a href="security/register.php" class="btn-primary">Create Account</a>
                    <a href="security/login.php" class="btn-secondary">Log in →</a>
                </div>
                <p class="get-started-meta">
                    Login required for full access
                </p>
            </div>
        </section>

        <section class="footer">
            <p>
                Made with ❤️ by Axel | 2025-<?php echo date('Y'); ?>
            </p>
        </section>
    </main>

    <script src="js/general.js?v=<?= filemtime(__DIR__ . '/js/general.js') ?>"></script>
</body>

</html>