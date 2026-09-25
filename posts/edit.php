<?php
require_once __DIR__ . '/../core/bootstrap.php';

if (!checkLogin()) {
    setFlashMessage('Unauthorized access', 'error');
    header('Location: ../index.php');
    exit;
}

$hash = isset($_GET['id']) ? decodeID($_GET['id']) : '';
$id = $hash ?: 0;

// Get post with username
$stmt = $pdo->prepare("
    SELECT p.*, u.username 
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$post = $stmt->fetch();

if (!$post) {
    setFlashMessage('Post not found', 'error');
    header('Location: index.php');
    exit;
}

$is_owner = ($post['user_id'] == $_SESSION['user_id']);
$is_admin = isAdmin();

if (!$is_owner && !$is_admin) {
    setFlashMessage('You do not own this post', 'error');
    header('Location: index.php');
    exit;
}

// Admin editing others → pending
if ($is_admin && !$is_owner && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_post'])) {
    validateCSRFToken($_POST['csrf_token'] ?? '');

    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM pending_actions
        WHERE target_id = ?
        AND target_type = 'post'
        AND action_type = 'edit_post'
        AND status = 'pending'
    ");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        setFlashMessage('An edit request is already pending for this post.', 'warning');
        header('Location: index.php');
        exit;
    }

    $content = trim($_POST['content'] ?? '');
    $original = trim($post['content']);

    if (!empty($content) && $content !== $original) {
        $data = json_encode([
            'post_id' => $id,
            'new_content' => $content,
            'old_content' => $post['content']
        ]);

        $stmt = $pdo->prepare("
            INSERT INTO pending_actions (admin_id, action_type, target_type, target_id, data) 
            VALUES (?, 'edit_post', 'post', ?, ?)
        ");
        $stmt->execute([$_SESSION['user_id'], $id, $data]);

        setFlashMessage('Edit request sent to creator for approval.', 'info');
        header('Location: index.php');
        exit;
    } else {
        setFlashMessage('There is no changes made.', 'error');
        header('Location: index.php');
        exit;
    }
}

// Owner edits directly
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_post']) && $is_owner) {
    validateCSRFToken($_POST['csrf_token'] ?? '');

    $content = trim($_POST['content'] ?? '');
    $original = trim($post['content']);

    if (!empty($content) && $content !== $original) {
        $stmt = $pdo->prepare("UPDATE posts SET content = ?, updated_at = ? WHERE id = ?");
        $stmt->execute([$content, date('Y-m-d H:i:s'), $id]);
        setFlashMessage('Post updated!', 'success');
        header('Location: index.php');
        exit;
    } else {
        setFlashMessage('There is no changes made.', 'error');
        header('Location: index.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once __DIR__ . '/../core/head.php'; ?>

    <title>Edit Post | Skia</title>
    <link rel="stylesheet" href="../css/general.css?v=<?= filemtime(__DIR__ . '/../css/general.css') ?>">

    <style>
        .edit-post {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
        }

        .edit-post h2 {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 16px;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 16px;
            width: 100%;
        }

        textarea {
            resize: vertical;
            width: 100%;
            padding: 12px 14px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-family: inherit;
            font-size: 1rem;
            min-height: 120px;
            background: var(--bg-body);
            color: var(--text-primary);
            transition: border 0.2s;
            line-height: 1.6;
        }

        textarea:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .pending-notice {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 16px;
            color: #92400e;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .pending-notice strong {
            color: #78350f;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 8px;
        }

        .btn-save {
            background: var(--accent);
            color: white;
            border: none;
            padding: 10px 28px;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.2s;
            flex: 1;
            min-width: 120px;
        }

        .btn-save:hover {
            background: var(--accent-hover);
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm);
        }

        .btn-cancel {
            background: var(--bg-body);
            color: var(--text-primary);
            padding: 10px 28px;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            border: 1px solid var(--border);
            transition: all 0.2s;
            text-align: center;
            flex: 1;
            min-width: 120px;
        }

        .btn-cancel:hover {
            background: var(--border);
            transform: translateY(-1px);
        }

        .back-link {
            text-decoration: none;
            color: var(--text-secondary);
            font-weight: 500;
            padding: 6px 14px;
            border-radius: var(--radius-sm);
            transition: all 0.2s;
        }

        .back-link:hover {
            background: var(--bg-body);
            color: var(--text-primary);
        }

        @media (max-width: 500px) {
            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <header>
        <h1>Edit Post</h1>
        <div class="header-right">
            <a href="index.php" class="back-link">← Back</a>
        </div>
    </header>

    <main>
        <section class="edit-post">
            <h2>Edit Post</h2>

            <?php if (!$is_owner && $is_admin): ?>
                <div class="pending-notice">
                    ⚠️ You are editing <strong><?= htmlspecialchars($post['username']) ?></strong>'s post.
                    This will be sent to <strong>Creator</strong> for approval.
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                <input type="hidden" name="edit_post" value="1">

                <textarea name="content" rows="5" required><?= htmlspecialchars($post['content']) ?></textarea>

                <div class="form-actions">
                    <button type="submit" class="btn-save">
                        <?= $is_owner ? '💾 Save Changes' : '📤 Request Edit' ?>
                    </button>
                    <a href="index.php" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </section>
    </main>

    <script src="../js/general.js?v=<?= filemtime(__DIR__ . '/../js/general.js') ?>"></script>
</body>

</html>