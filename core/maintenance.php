<?php
require_once __DIR__ . '/../core/bootstrap.php';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once __DIR__ . '/../core/head.php'; ?>
    <title>Under Maintenance | Skia</title>
    <link rel="stylesheet" href="../css/maintenance.css?v=<?= filemtime(__DIR__ . '/../css/maintenance.css') ?>">
</head>

<body>
    <div class="maintenance-container">
        <div class="maintenance-icon">🔧</div>
        <span class="status-badge">🚧 Under Maintenance</span>
        <h1>Be Right Back</h1>
        <p>I'm currently performing scheduled maintenance. This shouldn't take long.</p>

        <div class="countdown">
            <div class="countdown-item">
                <span class="number" id="minutes">00</span>
                <span class="label">Minutes</span>
            </div>
            <div class="countdown-item">
                <span class="number" id="seconds">00</span>
                <span class="label">Seconds</span>
            </div>
        </div>

        <div class="progress-bar">
            <div class="progress" id="progress" style="width:0%"></div>
        </div>

        <p style="font-size:0.85rem; color:#94a3b8;">⏳ Estimated time remaining</p>
        <a href="javascript:history.back()" class="back-link">← Go Back</a>

        <p style="font-size:0.85rem; color:#94a3b8; margin-top: 10px;">
            🔄 Still here? Give it a refresh (Ctrl+Shift+R) – updates might already be applied.
        </p>
    </div>

    <script>
        const totalSeconds = <?= $maintenanceDuration ?>;
        let timeLeft = totalSeconds;

        const minutesEl = document.getElementById('minutes');
        const secondsEl = document.getElementById('seconds');
        const progressEl = document.getElementById('progress');

        function updateDisplay() {
            const mins = Math.floor(timeLeft / 60);
            const secs = Math.floor(timeLeft % 60);

            minutesEl.textContent = String(mins).padStart(2, '0');
            secondsEl.textContent = String(secs).padStart(2, '0');

            const progress = ((totalSeconds - timeLeft) / totalSeconds) * 100;
            progressEl.style.width = Math.min(progress, 100) + '%';
        }

        function countdown() {
            if (timeLeft <= 0) {
                window.location.reload();
                return;
            }
            timeLeft--;
            updateDisplay();
        }

        updateDisplay();
        setInterval(countdown, 1000);
    </script>
</body>

</html>