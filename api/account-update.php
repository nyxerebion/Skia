<?php
require_once __DIR__ . '/../core/bootstrap.php';

header('Content-Type: application/json');

if (!checkLogin()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRFToken($_POST['csrf_token'] ?? '');

    $username = sanitizeUsername($_POST['username'] ?? '');
    $name = sanitizeString($_POST['name'] ?? '');
    $bio = sanitizeTextarea($_POST['bio'] ?? '');

    $name = preg_replace('/\s+/', ' ', $name);

    if (!preg_match('/^[a-zA-Z0-9. ]+$/', $name)) {
        setFlashMessage('Invalid characters in name', 'error');
        header('Location: settings.php');
        exit;
    }

    if (substr_count($name, ' ') > 1) {
        setFlashMessage('Name can only have one space', 'error');
        header('Location: settings.php');
        exit;
    }

    if (strlen($name) > 20) {
        setFlashMessage('Name too long (max 20 characters)', 'error');
        header('Location: settings.php');
        exit;
    }

    $stmt = $pdo->prepare("UPDATE users SET name = ?, bio = ? WHERE id = ?");
    $stmt->execute([$name, $bio, $_SESSION['user_id']]);

    $_SESSION['name'] = $name;
    setFlashMessage('Profile updated successfully!', 'success');
    header('Location: settings.php');
    exit;
}