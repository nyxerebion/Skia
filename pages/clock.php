<?php
require_once __DIR__ . '/../core/bootstrap.php';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once __DIR__ . '/../core/head.php'; ?>

    <title>Live Clock | Skia</title>
    <style>
        .live-clock {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 500;
            font-variant-numeric: tabular-nums;
        }
    </style>
</head>

<body>
    <div class="live-clock" id="liveClock"></div>

    <script>
        function updateClock() {
            const now = new Date();
            const options = {
                timeZone: 'Asia/Manila',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            };
            const time = now.toLocaleTimeString('en-PH', options);
            const clock = document.getElementById('liveClock');
            if (clock) clock.textContent = time;
        }

        updateClock();
        setInterval(updateClock, 1000);
    </script>
</body>

</html>