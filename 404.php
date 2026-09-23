<?php
require_once __DIR__ . '/core/bootstrap.php';

http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once __DIR__ . '/core/head.php'; ?>
    <title>Page Not Found | Skia</title>
    <link rel="stylesheet" href="css/404.css?v=<?= filemtime(__DIR__ . '/css/404.css') ?>">
</head>

<body>
    <div class="notfound-container">
        <div class="notfound-code">404</div>
        <h1>Page not found</h1>
        <p>The page you're looking for doesn't exist, was moved, or the link is broken.</p>

        <div class="notfound-actions">
            <a href="<?= defined('SITE_URL') ? SITE_URL : '/' ?>" class="btn-primary">← Back to home</a>
            <a href="javascript:history.back()" class="btn-secondary">Go back</a>
        </div>
    </div>
</body>

</html>