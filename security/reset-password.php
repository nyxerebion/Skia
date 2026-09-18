<?php
require_once __DIR__ . '/../core/bootstrap.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    setFlashMessage('Invalid reset link. Please request a new one.', 'error');
    header('Location: forgot-password.php');
    exit;
}

// Check token
$stmt = $pdo->prepare("
    SELECT id, username FROM users 
    WHERE reset_token = ? AND reset_token_expires > NOW() 
");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    setFlashMessage('Invalid reset link. Please request a new one.', 'error');
    header('Location: forgot-password.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $old_pass = $stmt->fetchColumn();

        if (password_verify($password, $old_pass)) {
            $error = 'New password cannot be the same as your current password.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
            UPDATE users 
            SET password = ?, reset_token = NULL, reset_token_expires = NULL 
            WHERE id = ?
            ");
            $stmt->execute([$hashed, $user['id']]);

            addNotification(
                $user['id'],
                'success',
                'Password Updated',
                'You successfully updated your password.',
                SITE_URL,
                'system',
                null
            );

            setFlashMessage('Password reset successfully! Please login.', 'success');
            header('Location: login.php');
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <?php require_once __DIR__ . '/../core/head.php'; ?>
    <title>Reset Password</title>

    <link rel="stylesheet" href="../css/general.css?v=<?= filemtime(__DIR__ . '/../css/general.css') ?>">

    <style>
        body {
            grid-template-columns: 1fr;
            grid-template-rows: auto 1fr auto;
            grid-template-areas:
                "header"
                "main"
                "footer";
        }

        main {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .reset-container {
            background: var(--bg-surface);
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            width: 100%;
            max-width: 400px;
            padding: 32px 24px;
        }

        .reset-container h1 {
            font-size: 1.6rem;
            margin: 0 0 4px 0;
        }

        .reset-container p {
            color: var(--text-secondary);
            font-size: 0.95rem;
            margin: 0 0 10px 0;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 16px;
            border-top: 1px solid var(--border);
            padding-top: 10px;
        }

        label {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: -8px;
        }

        form input[type="text"],
        form input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 1rem;
            background: var(--bg-body);
            transition: border 0.2s;
            color: var(--text-primary);
        }

        form input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        .password-wrapper {
            display: flex;
            gap: 8px;
            width: 100%;
        }

        .password-wrapper input {
            flex: 1;
        }

        .toggle-btn {
            background: var(--bg-body);
            color: var(--text-secondary);
            padding: 0 16px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-weight: 500;
            font-size: 0.85rem;
            white-space: nowrap;
            box-shadow: none;
            transform: none;
            cursor: pointer;
        }

        .toggle-btn:hover {
            background: var(--border);
            transform: none;
            box-shadow: none;
        }

        form button[type="submit"] {
            width: 100%;
            padding: 12px;
            font-size: 1rem;
            border-radius: var(--radius-sm);
            margin-top: 4px;
            color: white;
        }

        footer {
            grid-area: footer;
            background: var(--bg-surface);
            border-top: 1px solid var(--border);
            padding: 16px 24px;
            text-align: center;
        }

        footer p {
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 400;
            margin: 0;
        }

        /* Error message styling */
        .error-message {
            background: #fef2f2;
            color: #b91c1c;
            padding: 12px 16px;
            border-radius: var(--radius-sm);
            border-left: 4px solid #ef4444;
            text-align: center;
            font-weight: 500;
            margin: 8px 0;
            font-size: 0.9rem;
            width: 100%;
            max-width: 400px;
        }
    </style>
</head>

<body>
    <header>
        <h1>Reset Password | Skia</h1>
        <div class="header-right">
            <a href="login.php">← Back to Login</a>
        </div>
    </header>

    <main>
        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="reset-container">
            <h1>Reset Password</h1>
            <p>Hi <?= htmlspecialchars($user['username'] ?? 'User') ?>, enter your new password.</p>

            <form method="POST">
                <label for="passwordField">New Password:</label>
                <div class="password-wrapper">
                    <input type="password" id="passwordField" name="password" placeholder="New password" required minlength="6">
                    <button type="button" class="toggle-btn" onclick="togglePasswordVisibility('passwordField', this)">Show</button>
                </div>

                <label for="confirmPasswordField">Confirm Password:</label>
                <div class="password-wrapper">
                    <input type="password" id="confirmPasswordField" name="confirm_password" placeholder="Confirm password" required>
                    <button type="button" class="toggle-btn" onclick="togglePasswordVisibility('confirmPasswordField', this)">Show</button>
                </div>

                <button type="submit">Reset Password</button>
            </form>
        </div>
    </main>

    <footer>Made with ❤️ by Axel | <?= date('Y') ?></footer>

    <script>
        function togglePasswordVisibility(fieldId, btn) {
            const field = document.getElementById(fieldId);
            field.type = field.type === 'password' ? 'text' : 'password';
            btn.textContent = field.type === 'password' ? 'Show' : 'Hide';
        }
    </script>

    <script src="../js/general.js?v=<?= filemtime(__DIR__ . '/../js/general.js') ?>"></script>

</body>

</html>