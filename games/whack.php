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

    <div id="stats">⏱ <span id="timer">30</span>s 🏆 <span id="score">0</span> ⭐ <span id="points">0</span></div>
    <div id="high-score">🏅 High Score: <span id="highScoreDisplay">0</span></div>
    <div id="total-points">💰 Total Points Earned: <span id="totalPointsDisplay">0</span></div>
    <div id="board"></div>
    <button id="startBtn">Start</button>

    <div id="leaderboard">
        <h3>🏆 Leaderboard (High Score based)</h3>
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

    <script src="../js/general.js?v=<?= filemtime(__DIR__ . '/../js/general.js') ?>"></script>
    <script src="js/whack.js?v=<?= filemtime(__DIR__ . '/js/whack.js') ?>"></script>
</body>

</html>