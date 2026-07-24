<?php
ob_start();
require_once '../core/bootstrap.php';

$is_local = ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_ADDR'] === '127.0.0.1');

// Register.php
$prefill = $_SESSION['register_prefill'] ?? null;
unset($_SESSION['register_prefill']);
$prefill_username = $prefill && !$prefill['is_email'] ? $prefill['value'] : '';
$prefill_email = $prefill && $prefill['is_email'] ? $prefill['value'] : '';

$error = '';

getCSRFToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRFToken($_POST['csrf_token'] ?? '');

    $ip = $_SERVER['REMOTE_ADDR'];
    checkRateLimit($pdo, $ip, 'register', 5, 15);

    // Sanitization
    $username = sanitizeUsername($_POST['username'] ?? '');
    $email = sanitizeEmail($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation

    $errors = [];
    $valid = true;

    if (!validateRequired($username, 'Username', $msg)) {
        $errors[] = $msg;
        $valid = false;
    }
    if (!validateUsername($username, $msg)) {
        $errors[] = $msg;
        $valid = false;
    }
    if (!validateEmail($email, $msg)) {
        $errors[] = $msg;
        $valid = false;
    }
    if (!validatePassword($password, $msg)) {
        $errors[] = $msg;
        $valid = false;
    }
    if (!validatePasswordMatch($password, $confirm_password, $msg)) {
        $errors[] = $msg;
        $valid = false;
    }

    if (!$valid) {
        $error = $errors;
        recordRateLimitAttempt($pdo, $ip, 'register');
    } else {
        try {
            // Uniqueness check
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);

            if ($stmt->rowCount() > 0) {
                $error = 'Username or email already exists';
                recordRateLimitAttempt($pdo, $ip, 'register');
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$username, $email, $hashed_password]);
                $user_id = $pdo->lastInsertId();

                // Auto-login section
                $token = bin2hex(random_bytes(32));
                $hashed_token = password_hash($token, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                $stmt->execute([$hashed_token, $user_id]);

                $secure = !$is_local;
                setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', $secure, true);
                setcookie('user_id', $user_id, time() + (30 * 24 * 60 * 60), '/', '', $secure, true);

                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                session_regenerate_id(true);

                logAction("New user registered: $username");
                setFlashMessage("Registration successful! Welcome to Skia!", "success");

                clearRateLimit($pdo, $ip, 'register');

                header('Location: ../index.php');
                exit();
            }
        } catch (PDOException $e) {
            recordRateLimitAttempt($pdo, $ip, 'register');

            global $is_local;
            error_log("Registration error: " . $e->getMessage());
            $error = $is_local ? "Database error: " . $e->getMessage() : "Registration failed. Try again later.";
        }
    }
}

?>

<!DOCTYPE html>
<html>

<head>
    <?php require_once __DIR__ . '/../core/head.php'; ?>


    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, private">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

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
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <label for="username">Username:</label>
                <input type="text" id="username" name="username"
                    placeholder="Username" required
                    minlength="3" maxlength="20"
                    pattern="[a-zA-Z0-9_]+"
                    title="Only letters, numbers, underscores. 3-20 characters."
                    value="<?= htmlspecialchars($prefill_username ?? $_POST['username']) ?? '' ?>">

                <label for="email">Email:</label>
                <input type="email" id="email" name="email"
                    placeholder="Email" required
                    maxlength="100"
                    title="Enter a valid email address"
                    value="<?= htmlspecialchars($prefill_email ?? $_POST['email']) ?? '' ?>">

                <label for="passwordField">Password:</label>
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

    <script>
        window.onload = () => {
            handleMessage();
        };

        function handleMessage() {
            const flashMessage = document.querySelector(".flash-message");
            const errorMessage = document.querySelector(".error-message");
            const successMessage = document.querySelector(".success-message");

            const updateMessage = errorMessage || successMessage;

            if (!flashMessage && !updateMessage) return;

            setTimeout(() => {
                if (flashMessage) flashMessage.style.display = "none";
                if (updateMessage) updateMessage.style.display = "none";
            }, 10000);
        }

        function togglePasswordVisibility(fieldId, btn) {
            const field = document.getElementById(fieldId);
            if (field.type === 'password') {
                field.type = 'text';
                btn.textContent = 'Hide';
            } else {
                field.type = 'password';
                btn.textContent = 'Show';
            }
        }
    </script>
    <script src="../js/general.js?v=<?= filemtime(__DIR__ . '/../js/general.js') ?>"></script>
</body>

</html>
<?php ob_end_flush(); ?>