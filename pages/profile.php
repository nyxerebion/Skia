<?php
require_once __DIR__ . '/../core/bootstrap.php';

if (!checkLogin()) {
    setFlashMessage('Unauthorized access', 'error');
    header('Location: ../index.php');
    exit;
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    validateCSRFToken($_POST['csrf_token'] ?? '');

    $name = sanitizeString($_POST['name'] ?? '');
    $bio = sanitizeTextarea($_POST['bio'] ?? '');

    $name = preg_replace('/\s+/', ' ', $name);

    if (!preg_match('/^[a-zA-Z0-9. ]+$/', $name)) {
        setFlashMessage('Invalid characters in name', 'error');
        header('Location: profile.php');
        exit;
    }

    if (substr_count($name, ' ') > 1) {
        setFlashMessage('Name can only have one space', 'error');
        header('Location: profile.php');
        exit;
    }

    if (strlen($name) > 20) {
        setFlashMessage('Name too long (max 20 characters)', 'error');
        header('Location: profile.php');
        exit;
    }

    $stmt = $pdo->prepare("UPDATE users SET name = ?, bio = ? WHERE id = ?");
    $stmt->execute([$name, $bio, $_SESSION['user_id']]);

    $_SESSION['name'] = $name;
    setFlashMessage('Profile updated successfully!', 'success');
    header('Location: profile.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$current_user = $stmt->fetch();

$add_count = isset($_GET['add-count']) ? (int)$_GET['add-count'] : 0;
$item_count = 5 + $add_count;

$stmt = $pdo->prepare("
    SELECT 
        p.*,
        (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS like_count
    FROM posts p
    WHERE p.user_id = ? AND p.archived = 0
    ORDER BY p.created_at DESC
    LIMIT ?
");
$stmt->execute([$current_user['id'], $item_count]);
$user_posts = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT COUNT(user_id) FROM posts WHERE user_id = ? AND archived = 0");
$stmt->execute([$current_user['id']]);
$total_posts = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(l.id) 
    FROM likes l 
    JOIN posts p ON l.post_id = p.id
    WHERE p.archived = 0 AND p.user_id = ?
");
$stmt->execute([$current_user['id']]);
$user_likes = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(following_id) FROM follows WHERE following_id = ?");
$stmt->execute([$current_user['id']]);
$user_followers = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(follower_id) FROM follows WHERE follower_id = ?");
$stmt->execute([$current_user['id']]);
$user_following = $stmt->fetchColumn();

$edit_mode = isset($_GET['edit']);

$stmt = $pdo->prepare("SELECT score, points, total_points, time_played FROM whack_scores WHERE user_id = ?");
$stmt->execute([$current_user['id']]);
$user_whack_data = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT l.*, p.content AS post_content, p.id AS post_id, u.username
    FROM likes l
    JOIN posts p ON l.post_id = p.id
    JOIN users u ON p.user_id = u.id
    WHERE l.user_id = ? AND p.archived = 0
    ORDER BY l.created_at DESC
    LIMIT 10
");
$stmt->execute([$current_user['id']]);
$liked_posts = $stmt->fetchAll();

// Add this to profile.php right after the other POST handlers

// Handle avatar upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_avatar'])) {
    validateCSRFToken($_POST['csrf_token'] ?? '');

    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        setFlashMessage('No file uploaded or upload error.', 'error');
        header('Location: profile.php');
        exit;
    }

    $file = $_FILES['avatar'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    if ($file['size'] > $maxSize) {
        setFlashMessage('File too large. Maximum 5MB.', 'error');
        header('Location: profile.php');
        exit;
    }

    $uploadDir = __DIR__ . '/../uploads/avatars/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($extension, $allowed)) {
        setFlashMessage('Invalid file type. Only JPG, PNG, GIF, and WEBP allowed.', 'error');
        header('Location: profile.php');
        exit;
    }

    $uploaded = false;
    
    if ($extension === 'webp') {
        $image = @imagecreatefromwebp($file['tmp_name']);
        if ($image === false) {
            setFlashMessage('Invalid WebP image', 'error');
            header('Location: profile.php');
            exit;
        }
        $filename = 'avatar_' . $_SESSION['user_id'] . '_' . time() . '.jpg';
        $filepath = $uploadDir . $filename;
        $uploaded = imagejpeg($image, $filepath, 90);
        $image = null;
    } else {
        $filename = 'avatar_' . $_SESSION['user_id'] . '_' . time() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        $uploaded = move_uploaded_file($file['tmp_name'], $filepath);
    }

    if (!$uploaded) {
        setFlashMessage('Failed to upload image.', 'error');
        header('Location: profile.php');
        exit;
    }

    // Delete old avatar
    $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $oldAvatar = $stmt->fetchColumn();
    if ($oldAvatar && file_exists($uploadDir . $oldAvatar)) {
        unlink($uploadDir . $oldAvatar);
    }

    // Update database
    $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
    $stmt->execute([$filename, $_SESSION['user_id']]);

    logAction('avatar_updated');
    setFlashMessage('Avatar updated successfully!', 'success');
    header('Location: profile.php');
    exit;
}
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
                    <?= getUserAvatar($_SESSION['user_id']) ?>
                </div>

                <div class="profile-info-container">
                    <div class="wrapper">
                        <span class="user-name">
                            <span class="username">
                                <?= htmlspecialchars($current_user['username']) ?>
                            </span>
                            <span class="name">
                                (<?= htmlspecialchars(!empty($current_user['name']) ? $current_user['name'] : 'name not set') ?>)
                            </span>
                        </span>
                        <span class="user-role"><?= htmlspecialchars($current_user['role']) ?></span>
                    </div>

                    <span class="user-email">
                        <?= htmlspecialchars($current_user['email']) ?>
                    </span>

                    <span class="user-stats">
                        <span><?= htmlspecialchars($user_likes) ?></span> Likes
                        <span><?= htmlspecialchars($user_followers) ?></span> Followers
                        <span><?= htmlspecialchars($user_following) ?></span> Following
                    </span>

                    <span class="user-bio"><?= htmlspecialchars(!empty($current_user['bio']) ? $current_user['bio'] : 'no bio yet...') ?></span>
                </div>

                <div class="profile-controls">
                    <a href="?edit=1" class="link edit-link">✏️ Edit Profile</a>
                </div>
            </section>

            <?php if ($edit_mode): ?>
                <section class="edit-form-section">
                    <h2>✏️ Edit Profile</h2>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                        <input type="hidden" name="update_profile" value="1">

                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" value="<?= htmlspecialchars($current_user['username']) ?>" disabled>
                            <small>Username cannot be changed</small>
                        </div>

                        <div class="form-group">
                            <label>Email</label>
                            <input type="text" value="<?= htmlspecialchars($current_user['email']) ?>" disabled>
                            <small>Email cannot be changed</small>
                        </div>

                        <div class="form-group">
                            <label>Display Name</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($current_user['name'] ?? '') ?>" placeholder="Your display name" maxlength="20" pattern="[a-zA-Z0-9. ]+" title="Letters, numbers, dots, and 1 space only">
                        </div>

                        <div class="form-group">
                            <label>Bio</label>
                            <textarea name="bio" rows="3" placeholder="Tell us about yourself"><?= htmlspecialchars($current_user['bio'] ?? '') ?></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-save">💾 Save Changes</button>
                            <a href="profile.php" class="btn-cancel">Cancel</a>
                        </div>
                    </form>
                </section>
            <?php endif; ?>

            <!-- Avatar Upload with Crop -->
            <section class="avatar-section">
                <h3>Profile Picture</h3>

                <!-- Current avatar preview -->
                <div class="avatar-preview">
                    <?= getUserAvatar($current_user['id']) ?>
                </div>

                <!-- Upload form -->
                <form id="avatarForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                    <input type="hidden" name="upload_avatar" value="1">
                    <input type="hidden" name="crop_data" id="cropData">

                    <div class="avatar-upload">
                        <div id="uploadControls">
                            <label for="avatarInput">Choose Image</label>
                            <input type="file" id="avatarInput" name="avatar" accept="image/*" required>
                        </div>
                        <button type="button" id="cropBtn" class="btn-save" style="display:none;">Crop & Upload</button>
                        <small>Max 5MB. JPG, PNG, GIF, WEBP only.</small>
                    </div>
                </form>

                <!-- Crop modal -->
                <div id="cropModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.8);z-index:9999;display:none;align-items:center;justify-content:center;flex-direction:column;">
                    <div style="background:var(--bg-surface);border-radius:12px;padding:24px;max-width:600px;width:90%;max-height:90vh;overflow:auto;">
                        <h3 style="margin-bottom:12px;">Crop Image</h3>
                        <div style="max-height:400px;overflow:hidden;">
                            <img id="cropImage" src="" alt="Crop" style="max-width:100%;">
                        </div>
                        <div style="display:flex;gap:12px;margin-top:16px;justify-content:flex-end;">
                            <button id="cancelCrop" class="btn-cancel">Cancel</button>
                            <button id="confirmCrop" class="btn-save">✅ Apply</button>
                        </div>
                    </div>
                </div>
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
                            <?= htmlspecialchars($user_whack_data['points'] ?? 0) ?>
                        </div>
                        <div class="stat-label">
                            Current Points
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-number">
                            <?= htmlspecialchars(formatTime($user_whack_data['time_played'] ?? 0)) ?>
                        </div>
                        <div class="stat-label">
                            Time Played
                        </div>
                    </div>
                </div>
            </section>

            <?php if (!empty($user_posts)): ?>
                <div class="posts-section">
                    <h3>Your Posts</h3>
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
                                    <a href="../posts/edit.php?id=<?= $post['id'] ?>" class="edit-link">Edit Post →</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (count($user_posts) < $total_posts): ?>
                        <a href="?add-count=<?= $add_count + 5 ?>" class="load-link">Load More</a>
                    <?php else: ?>
                        <span class="all-loaded">🎉 You've seen all your posts</span>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    You don't have any posts yet.
                </div>
            <?php endif; ?>

            <section class="likes-section">
                <h3>Posts You Liked</h3>

                <?php if (empty($liked_posts)): ?>
                    <p class="empty-state">You haven't liked any posts yet.</p>
                <?php else: ?>
                    <?php foreach ($liked_posts as $like): ?>
                        <div class="liked-post-card">
                            <div class="liked-post-header">
                                <strong><?= htmlspecialchars($like['username']) ?></strong>
                                <span class="liked-date"><?= timeAgo($like['created_at']) ?></span>
                            </div>
                            <p><?= nl2br(htmlspecialchars(substr($like['post_content'], 0, 150))) ?></p>
                            <a href="../posts/index.php?scroll_to=<?= $like['post_id'] ?>" class="view-link">View Post →</a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <script src="../js/general.js?v=<?= filemtime(__DIR__ . '/../js/general.js') ?>"></script>
    <script src="../js/cropping.js?v=<?= filemtime(__DIR__ . '/../js/cropping.js') ?>"></script>
</body>

</html>