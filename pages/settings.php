<?php
require_once __DIR__ . '/../core/bootstrap.php';

if (!checkLogin()) {
    setFlashMessage('Unauthorized access', 'error');
    header('Location: ../index.php');
    exit;
}

logAction('Accessed settings page');

$active_tab = $_GET['tab'] ?? 'account';

// Handle settings updates here...

$edit_mode = isset($_GET['edit']);

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$current_user = $stmt->fetch();

// Latest username update
$stmt = $pdo->prepare("
    SELECT username_updated_at 
    FROM name_history 
    WHERE user_id = ? AND username_updated_at IS NOT NULL
    ORDER BY username_updated_at DESC 
    LIMIT 1
");
$stmt->execute([$current_user['id']]);
$username_updated_at = $stmt->fetchColumn();

// Latest name update
$stmt = $pdo->prepare("
    SELECT name_updated_at 
    FROM name_history 
    WHERE user_id = ? AND name_updated_at IS NOT NULL
    ORDER BY name_updated_at DESC 
    LIMIT 1
");
$stmt->execute([$current_user['id']]);
$name_updated_at = $stmt->fetchColumn();

// Latest bio update
$stmt = $pdo->prepare("
    SELECT bio_updated_at 
    FROM bio_history 
    WHERE user_id = ? AND bio_updated_at IS NOT NULL
    ORDER BY bio_updated_at DESC 
    LIMIT 1
");
$stmt->execute([$current_user['id']]);
$bio_updated_at = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once __DIR__ . '/../core/head.php'; ?>
    <title>Settings | Skia</title>
    <link rel="stylesheet" href="../css/general.css?v=<?= filemtime(__DIR__ . '/../css/general.css') ?>">
    <link rel="stylesheet" href="../css/settings.css?v=<?= filemtime(__DIR__ . '/../css/settings.css') ?>">

    <!-- Cropper.js CSS -->
    <link rel="stylesheet" href="../css/vendor/cropper.min.css?v=<?= filemtime(__DIR__ . '/../css/vendor/cropper.min.css') ?>">
    <!-- Cropper.js JS -->
    <script src="../js/vendor/cropper.min.js?v=<?= filemtime(__DIR__ . '/../js/vendor/cropper.min.js') ?>"></script>
</head>

<body>
    <header>
        <h1>Settings | Skia</h1>
        <div class="header-right">
            <a href="<?= SITE_URL ?>/index.php">← Back</a>
        </div>
    </header>

    <main>
        <?php $flash = getFlashMessage();
        if ($flash): ?>
            <div class="flash-wrapper">
                <div class="flash-message <?= $flash['type'] ?>">
                    <?= htmlspecialchars($flash['message'] ?? '') ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="settings-container">
            <!-- Sidebar -->
            <nav class="settings-nav">
                <a href="?tab=account" class="<?= $active_tab === 'account' ? 'active' : '' ?>">
                    👤 Account
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

                        <div class="section">
                            <h3>Profile details</h3>

                            <div class="info-wrapper">
                                <div class="info-item" id="avatarItem">
                                    <span class="info-name">Avatar</span>
                                    <span class="info-value info-value-avatar">
                                        <?= getUserAvatar($current_user['id']) ?>
                                    </span>
                                    <span class="icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-right-icon lucide-chevron-right">
                                            <path d="m9 18 6-6-6-6" />
                                        </svg>
                                    </span>
                                </div>

                                <div class="info-item" id="usernameItem">
                                    <span class="info-name">Username</span>
                                    <span class="info-value" id="usernameInfo">
                                        <?= htmlspecialchars($current_user['username']) ?>
                                    </span>
                                    <span class="icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-right-icon lucide-chevron-right">
                                            <path d="m9 18 6-6-6-6" />
                                        </svg>
                                    </span>
                                </div>

                                <div class="info-item" id="nameItem">
                                    <span class="info-name">Display Name</span>
                                    <span class="info-value" id="nameInfo">
                                        <?= htmlspecialchars(!empty($current_user['name']) ? $current_user['name'] : 'name not set') ?>
                                    </span>
                                    <span class="icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-right-icon lucide-chevron-right">
                                            <path d="m9 18 6-6-6-6" />
                                        </svg>
                                    </span>
                                </div>

                                <div class="info-item" id="bioItem">
                                    <span class="info-name">Bio</span>
                                    <span class="info-value" id="bioInfo">
                                        <?= htmlspecialchars(!empty($current_user['bio']) ? $current_user['bio'] : 'no bio yet...')  ?>
                                    </span>
                                    <span class="icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-right-icon lucide-chevron-right">
                                            <path d="m9 18 6-6-6-6" />
                                        </svg>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="section">
                            <h3>Personal Details</h3>

                            <div class="info-wrapper">
                                <div class="info-item solo">
                                    <span class="info-name">Contact info</span>
                                    <span class="info-value">
                                        <?= htmlspecialchars($current_user['email']) ?>
                                    </span>
                                    <span class="icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-right-icon lucide-chevron-right">
                                            <path d="m9 18 6-6-6-6" />
                                        </svg>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="section">
                            <h3>Password and security</h3>

                            <div class="info-wrapper">
                                <div class="info-item solo" id="passItem">
                                    <span class="info-action">
                                        Change password
                                    </span>
                                    <span class="icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-right-icon lucide-chevron-right">
                                            <path d="m9 18 6-6-6-6" />
                                        </svg>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php elseif ($active_tab === 'appearance'): ?>
                    <div class="content-wrapper">
                        <div class="content-header">
                            <h2>🎨 Appearance</h2>
                        </div>

                        <div class="section">
                            <h3>Theme</h3>

                            <div class="setting-item">
                                <span class="setting-name">Dark Mode</span>
                                <span class="setting-value">
                                    <label class="switch">
                                        <input type="checkbox" id="darkMode" onchange="toggleTheme()">
                                        <span class="slider"></span>
                                    </label>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <div class="popup-overlay" id="usernamePopup">
        <div class="popup-card">
            <button class="close-btn" onclick="closePopup(this.parentElement.parentElement)">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 6 6 18" />
                    <path d="m6 6 12 12" />
                </svg>
            </button>

            <div class="content-wrapper">
                <div class="username-section">
                    <h2>Username</h2>
                    <p class="cooldown-notice">⏳ Changing your username can only be done once every 48 hours.</p>

                    <form method="POST" id="usernameForm">
                        <div class="input-group">
                            <input type="text" placeholder=" " id="usernameInput"
                                value="<?= $current_user['username'] ?>"
                                oninput="checkUsername(this.value)"
                                onfocus="checkUsername(this.value)"
                                maxlength="12" required>
                            <label for="username">Username</label>
                            <button type="button" class="clear-btn" onclick="clearInput(this.parentElement)"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M18 6 6 18" />
                                    <path d="m6 6 12 12" />
                                </svg></button>
                        </div>
                        <small id="usernameValidationMessage" class="validationMessage"></small>
                        <input type="submit" id="submitUsernameBtn" value="Confirm Changes">
                    </form>

                    <p class="last_update">
                        <?= $username_updated_at
                            ? "Last update on " . date("M d, Y", strtotime($username_updated_at))
                            : 'Not updated yet' ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="popup-overlay" id="namePopup">
        <div class="popup-card">
            <button class="close-btn" onclick="closePopup(this.parentElement.parentElement)">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 6 6 18" />
                    <path d="m6 6 12 12" />
                </svg>
            </button>

            <div class="content-wrapper">
                <div class="name-section">
                    <h2>Display Name</h2>
                    <p class="cooldown-notice">⏳ Changing your display name can only be done once every 24 hours.</p>

                    <form method="POST" id="nameForm">
                        <div class="input-group">
                            <input type="text" id="nameInput" placeholder=" "
                                value="<?= $current_user['name'] ?? '' ?>"
                                oninput="checkName(this.value)"
                                onfocus="checkName(this.value)"
                                maxlength="100">
                            <label for="name">Display Name</label>
                            <button type="button" class="clear-btn" onclick="clearInput(this.parentElement)"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M18 6 6 18" />
                                    <path d="m6 6 12 12" />
                                </svg></button>
                        </div>
                        <small id="nameValidationMessage" class="validationMessage"></small>
                        <input type="submit" id="submitNameBtn" value="Confirm Changes">
                    </form>

                    <p class="last_update">
                        <?= $name_updated_at
                            ? "Last update on " . date("M d, Y", strtotime($name_updated_at))
                            : 'Not updated yet' ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="popup-overlay" id="bioPopup">
        <div class="popup-card">
            <button class="close-btn" onclick="closePopup(this.parentElement.parentElement)">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 6 6 18" />
                    <path d="m6 6 12 12" />
                </svg>
            </button>

            <div class="content-wrapper">
                <div class="bio-section">
                    <h2>Bio</h2>
                    <p class="cooldown-notice">⏳ Changing your bio is available anytime.</p>

                    <form method="POST" id="bioForm">
                        <div class="input-group input-group-textarea">
                            <textarea id="bioInput"
                                placeholder=" "
                                oninput="checkBio(this.value); updateCharCount(); autoResize(this);"
                                onfocus="checkBio(this.value); updateCharCount(); autoResize(this);"
                                maxlength="1000" minlength="5"><?= htmlspecialchars($current_user['bio'] ?? '') ?></textarea>
                            <label for="bio">Bio</label>
                        </div>
                        <div class="form-bottom-wrapper">
                            <button type="button" class="clear-btn-special" onclick="clearTextarea(this.parentElement.parentElement)"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M18 6 6 18" />
                                    <path d="m6 6 12 12" />
                                </svg> Clear</button>
                            <p class="char-count" id="bioCharCount"></p>
                        </div>
                        <small id="bioValidationMessage" class="validationMessage"></small>
                        <input type="submit" id="submitBioBtn" value="Confirm Changes">
                    </form>

                    <p class="last_update">
                        <?= $bio_updated_at
                            ? "Last update on " . date("M d, Y", strtotime($bio_updated_at))
                            : 'Not updated yet' ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="popup-overlay" id="passPopup">
        <div class="popup-card">
            <button class="close-btn" onclick="closePopup(this.parentElement.parentElement)">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 6 6 18" />
                    <path d="m6 6 12 12" />
                </svg>
            </button>

            <div class="content-wrapper">
                <div class="password-section">
                    <h2>Password</h2>
                    <p class="cooldown-notice">⏳ Changing your password is available anytime.</p>

                    <form method="POST" id="passForm">
                        <div class="input-group">
                            <input type="password" id="currentPassInput" placeholder=" "
                                oninput="validatePasswordForm()"
                                maxlength="100" required>
                            <label for="currentPassInput">Current Password</label>
                            <button type="button" class="toggle-pass" onclick="togglePasswordVisibility(this.parentElement)">Show</button>
                        </div>

                        <div class="input-group">
                            <input type="password" id="newPassInput" placeholder=" "
                                oninput="validatePasswordForm()"
                                maxlength="100" minlength="6" required>
                            <label for="newPassInput">New Password</label>
                            <button type="button" class="toggle-pass" onclick="togglePasswordVisibility(this.parentElement)">Show</button>
                        </div>

                        <div class="input-group">
                            <input type="password" id="confirmPassInput" placeholder=" "
                                oninput="validatePasswordForm()"
                                maxlength="100" required>
                            <label for="confirmPassInput">Confirm New Password</label>
                            <button type="button" class="toggle-pass" onclick="togglePasswordVisibility(this.parentElement)">Show</button>
                        </div>

                        <small id="passwordValidationMessage" class="validationMessage"></small>
                        <input type="submit" id="submitPassBtn" value="Confirm Changes">
                    </form>
                    <p class="last_update">
                        <?= $current_user['password_updated_at']
                            ? "Last update on " . date("M d, Y", strtotime($current_user['password_updated_at']))
                            : 'Not updated yet' ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="popup-overlay" id="avatarPopup">
        <div class="popup-card">
            <button class="close-btn" onclick="closePopup(this.parentElement.parentElement)">✕</button>
            <div class="content-wrapper">
                <h2>Profile Picture</h2>
                <p class="cooldown-notice">⏳ You can change your avatar anytime.</p>

                <div class="avatar-preview" id="avatarPreview">
                    <?= getUserAvatar($current_user['id']) ?>
                </div>

                <?php if (!empty($current_user['avatar'])): ?>
                    <button type="button" id="removeAvatarBtn" class="btn-remove">Remove Avatar</button>
                <?php endif; ?>

                <form id="avatarForm" method="POST" enctype="multipart/form-data" action="../api/user/update_avatar.php">
                    <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                    <input type="hidden" name="upload_avatar" value="1">
                    <input type="hidden" name="crop_data" id="cropData">

                    <div class="avatar-upload">
                        <div id="uploadControls">
                            <label for="avatarInput">Update Avatar</label>
                            <input type="file" id="avatarInput" name="avatar" accept="image/*" required>
                        </div>
                        <button type="button" id="cropBtn" class="btn-save" style="display:none;">Crop & Upload</button>
                        <small>Max 5MB. JPG, PNG, GIF, WEBP only.</small>
                    </div>
                </form>

                <!-- Crop modal -->
                <div id="cropModal" style="display: none;">
                    <div class="crop-modal-content">
                        <h3>Crop Image</h3>
                        <img id="cropImage" src="" alt="Crop">
                        <div class="crop-actions">
                            <button id="cancelCrop" class="btn-cancel">Cancel</button>
                            <button id="confirmCrop" class="btn-save">✅ Apply</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/general.js?v=<?= filemtime(__DIR__ . '/../js/general.js') ?>"></script>
    <script src="../js/settings.js?v=<?= filemtime(__DIR__ . '/../js/settings.js') ?>"></script>
    <script src="../js/cropping.js?v=<?= filemtime(__DIR__ . '/../js/cropping.js') ?>"></script>
</body>

</html>