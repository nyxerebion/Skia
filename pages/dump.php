<?php
require_once __DIR__ . '/../core/bootstrap.php';

if (!checkLogin()) {
    setFlashMessage('Unauthorized access', 'error');
    header('Location: ../index.php');
    exit;
}

$active_tab = $_GET['tab'] ?? 'account';

// Handle settings updates here...

$edit_mode = isset($_GET['edit']);

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$current_user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once __DIR__ . '/../core/head.php'; ?>
    <title>Settings | Skia</title>
    <link rel="stylesheet" href="../css/general.css?v=<?= filemtime(__DIR__ . '/../css/general.css') ?>">
    <link rel="stylesheet" href="../css/settings.css?v=<?= filemtime(__DIR__ . '/../css/settings.css') ?>">
</head>

<body>
    <header>
        <h1>Settings | Skia</h1>
        <div class="header-right">
            <a href="../index.php">← Back</a>
        </div>
    </header>

    <main>
        <?php $flash = getFlashMessage();
        if ($flash): ?>
            <div class="flash-wrapper">
                <div class="flash-message <?= $flash['type'] ?>">
                    <?= htmlspecialchars($flash['message']) ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="settings-container">
            <!-- Sidebar -->
            <nav class="settings-nav">
                <a href="?tab=account" class="<?= $active_tab === 'account' ? 'active' : '' ?>">
                    👤 Account
                </a>

                <a href="?tab=security" class="<?= $active_tab === 'security' ? 'active' : '' ?>">
                    🔒 Security
                </a>

                <a href="?tab=appearance" class="<?= $active_tab === 'appearance' ? 'active' : '' ?>">
                    🎨 Appearance
                </a>
            </nav>

            <!-- Content -->
            <section class="settings-content">
                <?php if ($active_tab === 'account'): ?>
                    <div class="content-wrapper">
                        <div class="content-header">
                            <h2>👤 Account Settings</h2>
                        </div>

                        <?php if ($edit_mode): ?>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" id="csrfToken" value="<?php getCSRFToken() ?>">
                                <div class="account-info">
                                    <div class="info-item">
                                        <span class="info-name">Username</span>
                                        <span class="info-value">
                                            <input type="text" name="username" id="usernameField"
                                                placeholder="<?= $current_user['username'] ?> (click to type)"
                                                oninput="checkUsername(this.value)">
                                                
                                        </span>
                                    </div>
                                    <small id="validationMessage">hello</small>
                                    <small>Your previous username: <em><?= $current_user['username'] ?></em></small>
                                    <small>⏳ You can change your <strong>username</strong> once every 48 hours.</small>

                                    <div class="info-item">
                                        <span class="info-name">Display Name</span>
                                        <span class="info-value">
                                            <input type="text" name="name" id="nameField" placeholder="<?= $current_user['name'] ?> (click to type)">
                                        </span>
                                    </div>
                                    <small>Your previous name: <em><?= $current_user['name'] ?></em></small>
                                    <small>⏳ You can change your <strong>display name</strong> once every 24 hours.</small>

                                    <div class="info-item">
                                        <span class="info-name">Bio</span>
                                        <span class="info-value">
                                            <textarea name="bio" id="bioField" placeholder="<?= $current_user['name'] ?> (click to type)"></textarea>
                                        </span>
                                    </div>
                                    <small>⏳ You can change your <strong>bio</strong> anytime.</small>
                                </div>

                                <div class="controls">
                                    <input type="submit" value="Save Changes">
                                    <a href="?tab=account" class="btn">Cancel Edit</a>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="account-info">
                                <div class="info-item">
                                    <span class="info-name">Username</span>
                                    <span class="info-value">
                                        <?= htmlspecialchars($current_user['username']) ?>
                                    </span>
                                </div>

                                <div class="info-item">
                                    <span class="info-name">Email</span>
                                    <span class="info-value">
                                        <?= htmlspecialchars($current_user['email']) ?>
                                    </span>
                                </div>

                                <div class="info-item">
                                    <span class="info-name">Display Name</span>
                                    <span class="info-value">
                                        <?= htmlspecialchars(!empty($current_user['name']) ? $current_user['name'] : 'name not set') ?>
                                    </span>

                                </div>

                                <div class="info-item">
                                    <span class="info-name">Role</span>
                                    <span class="info-value">
                                        <?= htmlspecialchars($current_user['role']) ?>
                                    </span>
                                </div>

                                <div class="info-item">
                                    <span class="info-name">Bio</span>
                                    <span class="info-value">
                                        <?= htmlspecialchars(!empty($current_user['bio']) ? $current_user['bio'] : 'no bio yet...')  ?>
                                    </span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!$edit_mode): ?>
                            <a href="?tab=account&edit=1" class="btn">✏️ Edit Profile</a>
                        <?php endif; ?>
                    </div>
                <?php elseif ($active_tab === 'security'): ?>
                    <div class="content-wrapper">
                        <div class="content-header">
                            <h2>🔒 Security</h2>
                        </div>
                        <p>Change your password and security settings.</p>
                        <a href="#" class="btn">Change Password</a>

                        <div class="under-dev-section">
                            <div class="under-dev-content">
                                <span class="dev-icon">🚧</span>
                                <h2>Settings Page Under Development</h2>
                                <p>This page is currently being built.</p>

                                <p class="dev-note">
                                    <i>Recent updates: Tabs, Account details.</i>
                                </p>

                                <p class="dev-note">
                                    <i>Account credentials update coming soon.</i>
                                </p>

                                <p class="dev-note">
                                    <i>Password Change update coming soon.</i>
                                </p>

                                <p class="dev-note">
                                    <i>Apperance update coming soon.</i>
                                </p>

                                <div class="dev-progress">
                                    <span>Progress:</span>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: 80%;"></div>
                                    </div>
                                    <span>70%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php elseif ($active_tab === 'appearance'): ?>
                    <div class="content-warpper">
                        <div class="content-header">
                            <h2>🎨 Appearance</h2>
                        </div>
                        <div class="setting-item">
                            <label>
                                <input type="checkbox" id="darkMode" onchange="toggleTheme()">
                                Dark Mode
                            </label>
                        </div>

                        <div class="under-dev-section">
                            <div class="under-dev-content">
                                <span class="dev-icon">🚧</span>
                                <h2>Settings Page Under Development</h2>
                                <p>This page is currently being built.</p>

                                <p class="dev-note">
                                    <i>Recent updates: Tabs, Account details.</i>
                                </p>

                                <p class="dev-note">
                                    <i>Account credentials update coming soon.</i>
                                </p>

                                <p class="dev-note">
                                    <i>Password Change update coming soon.</i>
                                </p>

                                <p class="dev-note">
                                    <i>Apperance update coming soon.</i>
                                </p>

                                <div class="dev-progress">
                                    <span>Progress:</span>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: 80%;"></div>
                                    </div>
                                    <span>70%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>



    <script src="../js/general.js?v=<?= filemtime(__DIR__ . '/../js/general.js') ?>"></script>
    <script src="../js/settings.js?v=<?= filemtime(__DIR__ . '/../js/settings.js') ?>"></script>

</body>

</html>