<?php
require_once __DIR__ . '/../../core/bootstrap.php';

header('Content-Type: application/json');

if (!checkLogin()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$csrf_token = $input['csrf_token'] ?? '';
$name = trim($input['name'] ?? '');

validateCSRFToken($csrf_token);

$errors = [];

// ✅ Validate format only (no uniqueness check)
if (strlen($name) < 2) {
    $errors[] = "Name must be at least 2 characters";
}
if (strlen($name) > 100) {
    $errors[] = "Name must be at most 100 characters";
}
if (preg_match('/^\s|\s$/', $name)) {
    $errors[] = "Name cannot start or end with spaces";
}
if (preg_match('/\s{2,}/', $name)) {
    $errors[] = "Name cannot contain multiple spaces in a row";
}

// Check each word has at least one letter
$words = explode(' ', $name);
foreach ($words as $word) {
    if (!preg_match('/[a-zA-Z]/', $word)) {
        $errors[] = "Each part of the name must contain at least one letter";
        break;
    }
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

echo json_encode([
    'success' => true,
    'valid' => true,
    'same' => false
]);