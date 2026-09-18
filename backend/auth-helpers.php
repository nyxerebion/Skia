<?php
function checkLogin()
{
    return isset($_SESSION['user_id']);
}

function setFlashMessage($message, $type = 'info')
{
    $_SESSION['flash'] = [
        'message' => $message,
        'type' => $type
    ];
}

function getFlashMessage()
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function isAdmin()
{
    return isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'creator']);
}

function isCreator() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'creator';
}

function updateUserActivity($user_id)
{
    global $pdo;

    $stmt = $pdo->prepare("
        UPDATE users 
        SET last_activity = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$user_id]);
}