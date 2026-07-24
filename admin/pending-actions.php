<?php
require_once __DIR__ . '/../core/bootstrap.php';

if (!isAdmin() || isCreator()) {
    header('Location: ../index.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT pa.*, u.username AS admin_name 
    FROM pending_actions pa
    JOIN users u ON pa.admin_id = u.id
    WHERE pa.status = 'pending'
    ORDER BY pa.created_at DESC
");
$stmt->execute();
$pending = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once __DIR__ . '/../core/head.php'; ?>

    <title>Pending Actions | Skia</title>
    <link rel="stylesheet" href="../css/general.css?v=<?= filemtime(__DIR__ . '/../css/general.css') ?>">
</head>

<body>
    <header>
        <h1>Pending Actions</h1>
        <div class="header-right">
            <a href="panel.php" class="back-link">← Back</a>
        </div>
    </header>

    <main>
        <section>
            <h2>Your Pending Requests</h2>
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
                            <span class="status-pending">⏳ Pending</span>
                        </p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>
</body>

</html>