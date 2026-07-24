<?php
require_once __DIR__ . '/../core/bootstrap.php';

if (!isCreator()) {
    setFlashMessage('Unauthorized access', 'error');
    http_response_code(403);
    header("Location: ../index.php");
    exit;
}

// Get pending requests
$stmt = $pdo->prepare("
    SELECT pa.*, u.username AS admin_name 
    FROM pending_actions pa
    JOIN users u ON pa.admin_id = u.id
    WHERE pa.status = 'pending'
    ORDER BY pa.created_at DESC
");
$stmt->execute();
$pending = $stmt->fetchAll();

// Approve
if (isset($_POST['approve'])) {
    validateCSRFToken($_POST['csrf_token'] ?? '');

    $id = (int)$_POST['id'];
    $stmt = $pdo->prepare("SELECT * FROM pending_actions WHERE id = ? AND status = 'pending'");
    $stmt->execute([$id]);
    $action = $stmt->fetch();

    if ($action) {
        $data = json_decode($action['data'], true);

        if ($action['action_type'] === 'delete_post') {
            $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
            $stmt->execute([$data['post_id']]);

            addNotification(
                $action['admin_id'],
                'approved',
                '✅ Post Deleted',
                "Your request to delete post #" . $data['post_id'] . " was approved.",
                '../posts/index.php'
            );
        } elseif ($action['action_type'] === 'promote_user') {
            // Double-check user is not already admin
            $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$data['user_id']]);
            $user = $stmt->fetch();

            if ($user && $user['role'] === 'admin') {
                addNotification(
                    $action['admin_id'],
                    'warning',
                    '⚠️ Already Admin',
                    "User is already an admin. Request ignored.",
                    'pending-actions.php'
                );
            } else {
                $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                $stmt->execute([$data['new_role'], $data['user_id']]);

                addNotification(
                    $data['user_id'],
                    'approved',
                    '🎉 Role Updated',
                    "You have been promoted to " . ucfirst($data['new_role']) . ".",
                    '../pages/profile.php'
                );

                addNotification(
                    $action['admin_id'],
                    'approved',
                    '✅ Promotion Approved',
                    "Your request to promote user to " . ucfirst($data['new_role']) . " was approved.",
                    'pending-actions.php'
                );
            }
        } elseif ($action['action_type'] === 'edit_post') {
            $stmt = $pdo->prepare("UPDATE posts SET content = ?, updated_at = ? WHERE id = ?");
            $stmt->execute([$data['new_content'], date('Y-m-d H:i:s'), $data['post_id']]);

            addNotification(
                $action['admin_id'],
                'approved',
                '✅ Edit Approved',
                "Your edit request for post #" . $data['post_id'] . " was approved.",
                '../posts/index.php'
            );
        }

        $stmt = $pdo->prepare("UPDATE pending_actions SET status = 'approved', reviewed_at = NOW(), reviewed_by = ? WHERE id = ?");
        $stmt->execute([$_SESSION['user_id'], $id]);

        setFlashMessage('Action approved.', 'success');
    }
    header('Location: creator.php');
    exit;
}

// Reject
if (isset($_POST['reject'])) {
    validateCSRFToken($_POST['csrf_token'] ?? '');

    $id = (int)$_POST['id'];

    $stmt = $pdo->prepare("SELECT * FROM pending_actions WHERE id = ? AND status = 'pending'");
    $stmt->execute([$id]);
    $action = $stmt->fetch();

    if ($action) {
        $stmt = $pdo->prepare("UPDATE pending_actions SET status = 'rejected', reviewed_at = NOW(), reviewed_by = ? WHERE id = ?");
        $stmt->execute([$_SESSION['user_id'], $id]);

        addNotification(
            $action['admin_id'],
            'rejected',
            '❌ Request Rejected',
            "Your request to " . $action['action_type'] . " " . $action['target_type'] . " #" . $action['target_id'] . " was rejected.",
            'pending-actions.php'
        );

        setFlashMessage('Action rejected.', 'info');
    } else {
        setFlashMessage('Action not found.', 'error');
    }

    header('Location: creator.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once __DIR__ . '/../core/head.php'; ?>

    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, private">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Creator Dashboard | Skia</title>
    <link rel="stylesheet" href="../css/general.css?v=<?= filemtime(__DIR__ . '/../css/general.css') ?>">
    <link rel="stylesheet" href="../css/admin.css?v=<?= filemtime(__DIR__ . '/../css/admin.css') ?>">
</head>

<body>
    <header>
        <h1>Creator Dashboard</h1>
        <div class="header-right">
            <a href="../index.php" class="back-link">← Back</a>
        </div>
    </header>

    <main>
        <?php $flash = getFlashMessage();
        if ($flash): ?>
            <div class="flash-wrapper">
                <span class="flash-message <?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></span>
            </div>
        <?php endif; ?>

        <section>
            <h2>Pending Requests</h2>
            <?php if (empty($pending)): ?>
                <p class="empty-state">No pending requests.</p>
            <?php else: ?>
                <?php foreach ($pending as $action): ?>
                    <div class="pending-item">
                        <p>
                            <strong><?= htmlspecialchars($action['admin_name']) ?></strong>
                            requested to <?= $action['action_type'] ?>
                            <?= $action['target_type'] ?> #<?= $action['target_id'] ?>
                            <span class="date"><?= timeAgo($action['created_at']) ?></span>
                        </p>
                        <div>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                                <input type="hidden" name="id" value="<?= $action['id'] ?>">
                                <button type="submit" name="approve" class="btn-approve">✅ Approve</button>
                            </form>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                                <input type="hidden" name="id" value="<?= $action['id'] ?>">
                                <button type="submit" name="reject" class="btn-reject">❌ Reject</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>
</body>

</html>