<?php
require_once __DIR__ . '/../core/bootstrap.php';

$email = $_GET['email'] ?? '';

// If no email param, redirect to forgot-password
if (empty($email)) {
    setFlashMessage('Invalid method.', 'error');
    header('Location: forgot-password.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT reset_token, reset_token_expires 
    FROM users 
    WHERE email = ?
");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    setFlashMessage('Email not found.', 'error');
    header('Location: forgot-password.php');
    exit;
}

if ($user['reset_token'] === null) {
    setFlashMessage('Password already updated. Please login.', 'info');
    header('Location: login.php');
    exit;
}

if ($user['reset_token_expires'] === null || strtotime($user['reset_token_expires']) < time()) {
    setFlashMessage('Reset link expired. Please request a new one.', 'error');
    header('Location: forgot-password.php');
    exit;
}
?>

<!DOCTYPE html>
<html>

<head>
    <?php require_once __DIR__ . '/../core/head.php'; ?>
    <title>Reset Link Sent</title>

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
        }

        .reset-sent-container {
            background: var(--bg-surface);
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            width: 100%;
            max-width: 400px;
            padding: 32px 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            position: relative;
            text-align: center;
        }

        .icon {
            position: absolute;
            top: -18px;
            left: -35px;
            font-size: 3.2rem;
            rotate: -30deg;
        }

        .reset-sent-container h1 {
            margin: 0;
            font-size: 1.6rem;
        }

        .reset-sent-container p {
            color: var(--text-secondary);
            margin: 4px 0;
        }

        .reset-sent-container strong {
            color: var(--text-primary);
        }

        .actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: 100%;
            margin-top: 12px;
        }

        .actions .btn {
            text-align: center;
            padding: 10px 20px;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            background: var(--accent);
            color: #fff;
            transition: background 0.2s;
        }

        .actions .btn:hover {
            background: var(--accent-hover);
        }

        .actions .btn-secondary {
            text-align: center;
            padding: 10px 20px;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border);
            transition: background 0.2s;
        }

        .actions .btn-secondary:hover {
            background: var(--bg-body);
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
    </style>
</head>

<body>
    <header>
        <h1>Reset Link Sent | Skia</h1>
        <div class="header-right">
            <a href="../guest-page.php">← Back</a>
        </div>
    </header>

    <main>
        <div class="reset-sent-container">
            <div class="icon">📧</div>
            <h1>Check Your Email</h1>
            <p>We've sent a password reset link to:</p>
            <p><strong><?= htmlspecialchars($email) ?></strong></p>
            <p>Click the link in the email to reset your password.</p>
            <p><small>Didn't receive it? Check your spam folder.</small></p>
            <div class="actions">
                <a href="forgot-password.php" class="btn">Resend Email</a>
                <a href="login.php" class="btn-secondary">Back to Login</a>
            </div>
        </div>
    </main>

    <footer>Made with ❤️ by Axel | <?= date('Y') ?></footer>

    <script src="../js/general.js?v=<?= filemtime(__DIR__ . '/../js/general.js') ?>"></script>

</body>

</html>