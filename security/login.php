<?php
ob_start();
require_once '../core/bootstrap.php';

$is_local = ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_ADDR'] === '127.0.0.1');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $ip = getRealIP();
    checkRateLimit($pdo, $ip, 'login', 5, 15);

    $login = trim($_POST['login']);
    $password = $_POST['password'];

    try {
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $sql = "SELECT * FROM users WHERE email = ?";
        } else {
            $sql = "SELECT * FROM users WHERE username = ?";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            secure_session_regenerate();
            clearRateLimit($pdo, $ip, 'login');

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'] ?? 'user';

            if (isset($_POST['remember'])) {
                $token = bin2hex(random_bytes(32));
                $hashed_token = password_hash($token, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                $stmt->execute([$hashed_token, $user['id']]);
                $secure = !$is_local;
                setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', $secure, true);
                setcookie('user_id', $user['id'], time() + (30 * 24 * 60 * 60), '/', '', $secure, true);
            }

            $stmt = $pdo->prepare("
                UPDATE users
                SET last_ip = ?, last_activity = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$ip, $user['id']]);

            logAction("User logged in: " . $user['username']);
            header("Location: ../index.php");
            exit();
        } elseif (!$user) {
            $is_email = filter_var($login, FILTER_VALIDATE_EMAIL);
            $field = $is_email ? 'email' : 'username';
            setFlashMessage(htmlspecialchars($login) . ' doesn\'t exist yet. Redirected to registration', 'warning');
            $_SESSION['register_prefill'] = ['value' => $login, 'is_email' => $is_email];
            header('Location: register.php');
            exit;
        } else {
            $error = 'Invalid username/email or password!';
            recordRateLimitAttempt($pdo, $ip, 'login');
        }
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());

        if ($is_local) {
            $error = "Database error: " . $e->getMessage();
        } else {
            $error = "Unable to process login. Please try again later.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once __DIR__ . '/../core/head.php'; ?>

    <title>Login | Skia</title>

    <link rel="stylesheet" href="../css/general.css?v=<?= filemtime(__DIR__ . '/../css/general.css') ?>">
    <link rel="stylesheet" href="../css/login.css?v=<?= filemtime(__DIR__ . '/../css/login.css') ?>">
</head>

<body>
    <header>
        <h1>Login | Skia</h1>
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

        <section class="form-section">

            <?php if ($error): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <label for="login">Username or Email:</label>
                <input type="text" id="login" name="login"
                    placeholder="Username or Email" required
                    maxlength="100"
                    title="Enter your username or email">

                <label for="passwordField">Password:</label>
                <div class="password-wrapper">
                    <input type="password" id="passwordField" name="password" placeholder="Password" required>
                    <button type="button" class="toggle-btn" onclick="togglePasswordVisibility('passwordField', this)">Show</button>
                </div>

                <div class="optional-controls">
                    <label class="remember-checkbox">
                        <input type="checkbox" name="remember"> Remember me
                    </label>
                    <a href="forgot-password.php">Forgot password</a>
                </div>

                <button type="submit">Login</button>
            </form>

            <p>No account? <a href="register.php">Register here</a></p>
        </section>
    </main>

    <footer>Made with ❤️ by Axel | <?= date('Y') ?></footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            handleMessage();
            document.querySelector('input[name="remember"]').checked = true;
        });

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
            field.type = field.type === 'password' ? 'text' : 'password';
            btn.textContent = field.type === 'password' ? 'Show' : 'Hide';
        }
    </script>
    <script src="../js/general.js?v=<?= filemtime(__DIR__ . '/../js/general.js') ?>"></script>
</body>

</html>
<?php ob_end_flush(); ?>