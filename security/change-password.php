<?php
require_once __DIR__ . '/../core/bootstrap.php';


?>

<!DOCTYPE html>
<html>

<head>
    <?php require_once __DIR__ . '/../core/head.php'; ?>
    <title>Forgot Password</title>
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

        .forgot-section {
            background: var(--bg-surface);
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            width: 100%;
            max-width: 400px;
            padding: 32px 24px;
            display: flex;
            flex-direction: column;
        }

        .top-message h1 {
            font-size: 1.4rem;
        }

        .top-message p {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 16px;
            width: 100%;
            border-top: 1px solid var(--border);
            padding-top: 10px;
            margin-top: 10px;
        }

        label {
            display: block;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: -10px;
        }

        form input[type="email"] {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 1rem;
            background: var(--bg-body);
            transition: border 0.2s;
            color: var(--text-primary);
        }

        form button[type="submit"] {
            width: 100%;
            padding: 12px;
            font-size: 1rem;
            border-radius: var(--radius-sm);
            margin-top: 4px;
            color: white;
        }

        .back-message {
            display: flex;
            gap: 8px;
            margin-top: 10px;
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .back-message a {
            background: transparent;
            color: var(--accent);
            transform: none;
            box-shadow: none;
            text-decoration: underline;
        }

        .back-message a:hover {
            background: transparent;
            color: var(--accent-hover);
            transform: none;
            box-shadow: none;
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
        <h1>Forgot Password | Skia</h1>
        <div class="header-right">
            <a href="../guest-page.php">← Back</a>
        </div>
    </header>

    <main>
        <?php $flash = getFlashMessage();
        if ($flash): ?>
            <div class="flash-wrapper">
                <span class="flash-message <?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <section class="forgot-section">
            <div class="top-message">
                <h1>Forgot Password</h1>
                <p>Enter your email and we'll send you a reset link.</p>
            </div>

            <form method="POST">
                <label for="email">Email:</label>
                <input type="email" name="email" placeholder="Your email" value="<?= htmlspecialchars($email) ?>" title="Enter your email" required>
                <button type="submit">Set New Password</button>
            </form>
        </section>

        <p class="back-message">Changed your mind? <a href="login.php">Go back to login</a></p>
    </main>

    <footer>Made with ❤️ by Axel | <?= date('Y') ?></footer>


    <script src="../js/general.js?v=<?= filemtime(__DIR__ . '/../js/general.js') ?>"></script>
</body>

</html>