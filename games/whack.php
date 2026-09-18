<?php
require_once __DIR__ . '/../core/bootstrap.php';

if (!checkLogin()) {
    setFlashMessage('Unauthorized access', 'error');
    header('Location: ../index.php');
    exit;
}

logAction("Played Whack Game");

if (!isset($_SESSION['page_load_time'])) {
    $_SESSION['page_load_time'] = time();
}
$pageLoadTime = $_SESSION['page_load_time'];
?>

<!DOCTYPE html>
<html>

<head>
    <?php require_once __DIR__ . '/../core/head.php'; ?>
    <title>Whack A Gold | Skia</title>

    <link rel="stylesheet" href="css/whack.css?v=<?= filemtime(__DIR__ . '/css/whack.css') ?>">
</head>

<body>
    <?php $flash = getFlashMessage();
    if ($flash): ?>
        <div class="flash-message <?= $flash['type'] ?>">
            <?= htmlspecialchars($flash['message']) ?>
        </div>
    <?php endif; ?>

    <header>
        <h1>WHACK A GOLD</h1>
        <a href="index.php">← Back</a>
    </header>

    <main>
        <div class="personal-stats">
            <span class="high-score">🏅 High Score: <span id="highScoreDisplay">0</span></span>
            <span class="points">💰 Current Points: <span id="pointsDisplay">0</span></span>
            <span class="points">💰 Total Points Earned: <span id="totalPointsDisplay">0</span></span>
        </div>

        <div class="game-content">
            <div class="game-stats">
                <span>⏱ <span id="timer">30</span>s</span>
                <span>🏆 <span id="score">0</span></span>
                <span>⭐ <span id="points">0</span></span>
            </div>
            <div id="board"></div>
            <button id="startBtn">Start</button>
        </div>

        <div id="leaderboard">
            <h3>🏆 Leaderboard (High Score based)</h3>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Player</th>
                            <th>High Score</th>
                            <th>Total Points</th>
                            <th>Current Points</th>
                            <th>Time (s)</th>
                        </tr>
                    </thead>
                    <tbody id="leaderboardBody">
                        <tr>
                            <td colspan="6" style="text-align:center;color:#94a3b8;">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="dev-notice">
            <span class="dev-icon">🚧</span>
            <span class="dev-text">Under Development</span>
            <span class="dev-sub">Whack A Gold is currently in progress. Check back soon!</span>
        </div>
    </main>

    <script src="js/whack.js?v=<?= filemtime(__DIR__ . '/js/whack.js') ?>"></script>
    <script src="js/game-helper.js?v=<?= filemtime(__DIR__ . '/js/game-helper.js') ?>"></script>
</body>

</html>