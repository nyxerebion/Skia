<?php
// logout.php
require_once '../core/bootstrap.php';

logAction("User logged out: " . $_SESSION['username']);

// Regenerate before destroy (prevents fixation on the logout request itself)
secure_session_regenerate();

// Clear session data
$_SESSION = array();

// Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

// Delete remember-me cookies
$is_local = ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_ADDR'] === '127.0.0.1');
$secure = !$is_local;

setcookie('remember_token', '', time() - 3600, '/', '', $secure, true);
setcookie('user_id', '', time() - 3600, '/', '', $secure, true);

header('Location: ../guest-page.php');
exit();