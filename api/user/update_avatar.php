<?php
// /api/user/update_avatar.php
require_once __DIR__ . '/../../core/bootstrap.php';

if (!checkLogin()) {
    setFlashMessage('Unauthorized access', 'error');
    header('Location: ../../pages/settings.php?tab=account');
    exit;
}

// Handle avatar upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_avatar'])) {
    validateCSRFToken($_POST['csrf_token'] ?? '');

    $redirect = '../../pages/settings.php?tab=account&popup=avatar';

    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        setFlashMessage('No file uploaded or upload error.', 'error');
        header('Location: ' . $redirect);
        exit;
    }

    $file = $_FILES['avatar'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    if ($file['size'] > $maxSize) {
        setFlashMessage('File too large. Maximum 5MB.', 'error');
        header('Location: ' . $redirect);
        exit;
    }

    $uploadDir = __DIR__ . '/../../uploads/avatars/';
    $archiveDir = __DIR__ . '/../../uploads/avatars_archive/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (!is_dir($archiveDir)) {
        mkdir($archiveDir, 0755, true);
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($extension, $allowed)) {
        setFlashMessage('Invalid file type. Only JPG, PNG, GIF, and WEBP allowed.', 'error');
        header('Location: ' . $redirect);
        exit;
    }

    $uploaded = false;

    if ($extension === 'webp') {
        $image = @imagecreatefromwebp($file['tmp_name']);
        if ($image === false) {
            setFlashMessage('Invalid WebP image', 'error');
            header('Location: ' . $redirect);
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
        header('Location: ' . $redirect);
        exit;
    }

    // Archive old avatar with unique name (instead of deleting)
    $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $oldAvatar = $stmt->fetchColumn();

    if ($oldAvatar && file_exists($uploadDir . $oldAvatar)) {
        rename($uploadDir . $oldAvatar, $archiveDir . $oldAvatar);
    }

    // Update database
    $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
    $stmt->execute([$filename, $_SESSION['user_id']]);

    // Log to avatar_history
    $stmt = $pdo->prepare("
        INSERT INTO avatar_history (
            user_id, previous_avatar, updated_avatar,
            change_type, avatar_updated_at, changed_by
        ) VALUES (?, ?, ?, 'upload', NOW(), ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $oldAvatar,
        $filename,
        $_SESSION['user_id']
    ]);

    logAction('avatar_updated');

    addNotification(
        $_SESSION['user_id'],
        'success',
        'Avatar Updated',
        'Your profile picture has been updated.',
        SITE_URL . '/pages/settings.php?tab=account',
        'system',
        null
    );

    setFlashMessage('Avatar updated successfully!', 'success');
    header('Location: ../../pages/settings.php?tab=account');
    exit;
}