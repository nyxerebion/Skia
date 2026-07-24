<?php
require_once '../core/bootstrap.php';

logAction("User logged out: " . $_SESSION['username']);

session_regenerate_id(true);

$_SESSION = array();

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

setcookie('remember_token', '', time() - 3600, '/', '', true, true);
setcookie('user_id', '', time() - 3600, '/', '', true, true);

header('Location: ../guest-page.php');
exit();
