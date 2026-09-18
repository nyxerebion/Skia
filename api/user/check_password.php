<?php
require_once __DIR__ . '/../../core/bootstrap.php';

header('Content-Type: application/json');

if (!checkLogin()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$csrf_token = $input['csrf_token'] ?? '';
$pass_input = $input['current_password'] ?? '';

validateCSRFToken($csrf_token);

$errors = [];

if (empty($pass_input)) {
    $errors[] = 'Current password is required';
}

if (empty($errors)) {
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $hashed_current_pass = $stmt->fetchColumn();

    if (!password_verify($pass_input, $hashed_current_pass)) {
        $errors[] = 'Current password is incorrect';
    }
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

echo json_encode([
    'success' => true
]);
