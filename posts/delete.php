<?php
require_once __DIR__ . '/../core/bootstrap.php';

if (!checkLogin()) {
    setFlashMessage('Unauthorized access', 'error');
    header('Location: ../index.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get post
$stmt = $pdo->prepare(
    "
    SELECT p.*, u.username 
    FROM posts p 
    JOIN users u ON p.user_id = u.id
    WHERE p.id = ?"
);
$stmt->execute([$id]);
$post = $stmt->fetch();

if (!$post) {
    setFlashMessage('Post not found', 'error');
    header('Location: posts.php');
    exit;
}

$is_owner = ($post['user_id'] === $_SESSION['user_id']);
$is_admin = isAdmin();

// Check if user owns the post or is admin
if (!$is_owner && !$is_admin) {
    setFlashMessage('You do not own this post', 'error');
    header('Location: posts.php');
    exit;
}

if ($is_admin && !$is_owner && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'])) {
    validateCSRFToken($_POST['csrf_token'] ?? '');

    $data = json_encode(['post_id' => $id]);

    $stmt = $pdo->prepare("
        INSERT INTO pending_actions (admin_id, action_type, target_type, target_id, data)
        VALUES (?, 'delete_post', 'post', ?, ?)
    ");
    $stmt->execute([$_SESSION['user_id'], $id, $data]);

    setFlashMessage('Delete request sent to creator for approval.', 'info');
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post']) && $is_owner) {
    validateCSRFToken($_POST['csrf_token'] ?? '');

    // Delete likes first (foreign key cascade will handle if set)
    $stmt = $pdo->prepare("
        UPDATE posts
        SET archived = 1,
            archived_at = ?,
            archived_by = ?
        WHERE id = ?
    ");
    $stmt->execute([date('Y-m-d H:i:s'), $_SESSION['user_id'], $id]);

    setFlashMessage('Post deleted!', 'success');
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once __DIR__ . '/../core/head.php'; ?>

    <title>Delete Post | Skia</title>
    <link rel="stylesheet" href="../css/general.css?v=<?= filemtime(__DIR__ . '/../css/general.css') ?>">
    <link rel="stylesheet" href="../css/admin.css?v=<?= filemtime(__DIR__ . '/../css/admin.css') ?>">
</head>

<body>
    <header>
        <h1>Delete Post</h1>
        <div class="header-right">
            <a href="index.php" class="back-link">← Back</a>
        </div>
    </header>

    <main>
        <section class="delete-confirm">
            <h2>Are you sure?</h2>

            <?php if (!$is_owner && $is_admin): ?>
                <div class="pending-notice">
                    ⚠️ You are about to delete <strong><?= htmlspecialchars($post['username']) ?></strong>'s post.
                    This will be sent to <strong>Creator</strong> for approval.
                </div>
            <?php endif; ?>

            <p>This will permanently delete the post:</p>
            <blockquote><?= nl2br(htmlspecialchars($post['content'])) ?></blockquote>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                <input type="hidden" name="delete_post" value="1">
                <div class="form-actions">
                    <button type="submit" class="btn-danger">
                        <?= $is_owner ? '🗑️ Yes, Delete' : '📤 Request Delete' ?>
                    </button>
                    <a href="index.php" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </section>
    </main>
</body>

</html>