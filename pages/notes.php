<?php
require_once __DIR__ . '/../core/bootstrap.php';

if (!checkLogin()) {
    setFlashMessage('Unauthorized access', 'error');
    http_response_code(403);
    header("Location: ../guest-page.php");
    exit();
}

getCSRFToken();

// Handle POST (save/delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRFToken($_POST['csrf_token'] ?? '');

    if (isset($_POST['delete'])) {
        $id = sanitizeInt($_POST['id'] ?? null);
        if ($id) {
            $stmt = $pdo->prepare("
                UPDATE notes
                SET archived = 1,
                    archived_at = ?,
                    archived_by = ?
                WHERE id = ?
            ");
            $stmt->execute([date('Y-m-d H:i:s'), $_SESSION['user_id'], $id]);
            setFlashMessage('Note deleted', 'success');
        }
        header('Location: notes.php');
        exit;
    }

    // Save/update
    $id = sanitizeInt($_POST['id'] ?? null);
    $title = sanitizeString($_POST['title'] ?? '');
    $content = sanitizeTextarea($_POST['content'] ?? '');

    if ($title && $content) {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE notes SET note_title = ?, note_content = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$title, $content, $id, $_SESSION['user_id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO notes (user_id, note_title, note_content, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$_SESSION['user_id'], $title, $content]);
        }
        setFlashMessage('Note saved', 'success');
    } else {
        setFlashMessage('Title and content required', 'error');
    }
    header('Location: notes.php');
    exit;
}

// Get notes
$stmt = $pdo->prepare("SELECT * FROM notes WHERE user_id = ? AND archived = 0 ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$notes = $stmt->fetchAll();

// Get single note for editing
$edit_note = null;
if (isset($_GET['edit'])) {
    $id = sanitizeInt($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM notes WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $edit_note = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html>

<head>
    <?php require_once __DIR__ . '/../core/head.php'; ?>

    <meta name="csrf-token" content="<?= htmlspecialchars(getCSRFToken()) ?>">
    <title>Notes | Skia</title>
    <link rel="stylesheet" href="../css/notes.css?v=<?= filemtime(__DIR__ . '/../css/notes.css') ?>">
</head>

<body>
    <header>
        <h1>Notes | Skia</h1>
        <div class="header-right">
            <a href="javascript:history.back()">← Back</a>
        </div>
    </header>

    <main>
        <?php $flash = getFlashMessage();
        if ($flash): ?>
            <div class="flash-wrapper">
                <span class="flash-message <?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></span>
            </div>
        <?php endif; ?>

        <div class="notes-container">
            <!-- Form -->
            <div class="note-editor">
                <h2><?= $edit_note ? 'Edit Note' : 'New Note' ?></h2>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                    <?php if ($edit_note): ?>
                        <input type="hidden" name="id" value="<?= $edit_note['id'] ?>">
                    <?php endif; ?>

                    <input type="text" name="title" placeholder="Title" required
                        value="<?= $edit_note ? htmlspecialchars($edit_note['note_title']) : '' ?>">

                    <textarea name="content" placeholder="Content" rows="10" required><?= $edit_note ? htmlspecialchars($edit_note['note_content']) : '' ?></textarea>

                    <div class="form-actions">
                        <button type="submit"><?= $edit_note ? 'Update' : 'Create' ?></button>
                        <?php if ($edit_note): ?>
                            <a href="notes.php" class="btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- List -->
            <div class="notes-list">
                <h3>Your Notes (<?= count($notes) ?>)</h3>
                <?php if (empty($notes)): ?>
                    <p class="empty">No notes yet. Create one above.</p>
                <?php else: ?>
                    <?php foreach ($notes as $note): ?>
                        <div class="note-item">
                            <div class="note-header">
                                <h4><?= htmlspecialchars($note['note_title']) ?></h4>
                                <span class="note-date"><?= date('M d, Y', strtotime($note['created_at'])) ?></span>
                            </div>
                            <p><?= nl2br(htmlspecialchars(substr($note['note_content'], 0, 150))) ?>...</p>
                            <div class="note-actions">
                                <a href="?edit=<?= $note['id'] ?>" class="btn-edit">Edit</a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this note?')">
                                    <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                                    <input type="hidden" name="id" value="<?= $note['id'] ?>">
                                    <input type="hidden" name="delete" value="1">
                                    <button type="submit" class="btn-delete">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>

</html>