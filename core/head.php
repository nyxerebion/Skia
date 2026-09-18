<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, private">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">

<?php
$notRegisterPage = $_SERVER['PHP_SELF'] !== 'register.php';

if ($notRegisterPage) {
    echo '<meta name="csrf-token" content="' . getCSRFToken() . '">';
}
?>

<meta name="theme-color" content="#6C63FF">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

<!-- Favicons -->
<link rel="apple-touch-icon" sizes="180x180" href="<?= SITE_URL ?>/favicon_io/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="<?= SITE_URL ?>/favicon_io/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="<?= SITE_URL ?>/favicon_io/favicon-16x16.png">
<link rel="manifest" href="<?= SITE_URL ?>/favicon_io/site.webmanifest">
<link rel="shortcut icon" href="<?= SITE_URL ?>/favicon.ico" type="image/x-icon">

<?php

$isMaintenance = false; // Set to true to enable maintenance mode
$isMaintenancePage = basename($_SERVER['PHP_SELF']) === 'maintenance.php';
$maintenanceDuration = 5 * 60;


if ($isMaintenance) {
    if ($isMaintenancePage) {
        // If already on the maintenance page, do nothing
        return;
    }
    
    header("Location: " . SITE_URL . "/core/maintenance.php");
    exit;
} else {
    if ($isMaintenancePage) {
        // If maintenance mode is off but user is on the maintenance page, redirect to home
        setFlashMessage('Maintenance mode has ended. Redirecting to the homepage.', 'success');
        header("Location: " . SITE_URL . "/index.php");
        exit;
    }
}
?>