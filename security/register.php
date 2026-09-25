<?php
// Register.php
require_once '../core/bootstrap.php';
?>

<!DOCTYPE html>
<html>

<head>
    <?php require_once __DIR__ . '/../core/head.php'; ?>

    <title>Skia | Register</title>

    <link rel="stylesheet" href="../css/general.css?v=<?= filemtime(__DIR__ . '/../css/general.css') ?>">
    <link rel="stylesheet" href="../css/register.css?v=<?= filemtime(__DIR__ . '/../css/register.css') ?>">

</head>

<body>
    <div id="pageLoader"></div>
    <header>
        <h1>Register | Skia</h1>
        <div class="header-right">
            <a href="../guest-page.php" class="">← Back</a>
        </div>
    </header>

    <main>
        <?php $flash = getFlashMessage();
        if ($flash): ?>
            <div class="flash-wrapper">
                <span class="flash-message <?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></span>
            </div>
        <?php endif; ?>

        <section class="form-section">
            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <?php if (is_array($error)): ?>
                        <ul>
                            <?php foreach ($error as $msg): ?>
                                <li><?= htmlspecialchars($msg ?? '') ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <?= htmlspecialchars($error) ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <label for="username">Username:</label>
                <input type="text" id="usernameField" name="username"
                    placeholder="Username" required
                    minlength="3" maxlength="20"
                    pattern="[a-zA-Z0-9_]+"
                    title="Only letters, numbers, underscores. 3-20 characters."
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">

                <label for="email">Email:</label>
                <input type="email" id="emailField" name="email"
                    placeholder="Email" required
                    maxlength="100"
                    title="Enter a valid email address"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

                <label for="passwordField">Create Password:</label>
                <div class="password-wrapper">
                    <input type="password" id="passwordField" name="password"
                        placeholder="Password" required
                        minlength="6" maxlength="255"
                        title="At least 6 characters">
                    <button type="button" class="toggle-btn" onclick="togglePasswordVisibility('passwordField', this)">Show</button>
                </div>

                <label for="confirmField">Confirm Password:</label>
                <div class="password-wrapper">
                    <input type="password" id="confirmField" name="confirm_password"
                        placeholder="Confirm Password" required
                        minlength="6" maxlength="255"
                        title="Passwords must match">
                    <button type="button" class="toggle-btn" onclick="togglePasswordVisibility('confirmField', this)">Show</button>
                </div>

                <button type="submit">Register</button>

                <div class="notes">
                    <small>• Username must contain at least 1 number, and can have at most one underscore.</small>
                    <small>• Password must have at least 6 characters.</small>
                    <small>• Email will be used for account recovery.</small>
                </div>
            </form>

            <p>
                Already have an account? <a href='login.php'>Login here</a>
            </p>
        </section>
    </main>

    <footer>Made with ❤️ by Axel | <?= date('Y') ?></footer>

    <script src="../js/register.js?v=<?= filemtime(__DIR__ . '/../js/register.js') ?>"></script>
    <script src="../js/general.js?v=<?= filemtime(__DIR__ . '/../js/general.js') ?>"></script>
</body>

</html>
<?php ob_end_flush(); ?>