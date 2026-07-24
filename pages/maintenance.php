<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, private">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

    <title>Under Maintenance | Skia</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: #f1f5f9;
            color: #0f172a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .maintenance-container {
            text-align: center;
            max-width: 600px;
            padding: 40px;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
        }

        .maintenance-icon {
            font-size: 4rem;
            margin-bottom: 16px;
        }

        h1 {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 8px;
        }

        p {
            color: #475569;
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .countdown {
            display: flex;
            justify-content: center;
            gap: 16px;
            margin: 24px 0;
        }

        .countdown-item {
            background: #f1f5f9;
            padding: 12px 16px;
            border-radius: 12px;
            min-width: 70px;
        }

        .countdown-item .number {
            font-size: 2rem;
            font-weight: 800;
            color: #0f172a;
            display: block;
        }

        .countdown-item .label {
            font-size: 0.7rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .progress-bar {
            width: 100%;
            height: 6px;
            background: #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            margin: 16px 0 24px 0;
        }

        .progress-bar .progress {
            height: 100%;
            background: #3b82f6;
            border-radius: 10px;
            width: 0%;
            transition: width 0.3s;
        }

        .back-link {
            display: inline-block;
            color: #3b82f6;
            text-decoration: none;
            font-weight: 600;
            padding: 8px 20px;
            border: 1px solid #e2e8f0;
            border-radius: 40px;
            transition: all 0.2s;
        }

        .back-link:hover {
            background: #f1f5f9;
        }

        .status-badge {
            display: inline-block;
            background: #fee2e2;
            color: #dc2626;
            padding: 4px 16px;
            border-radius: 40px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 12px;
        }

        @media (max-width: 480px) {
            .maintenance-container {
                padding: 24px;
            }

            h1 {
                font-size: 1.5rem;
            }

            .countdown-item {
                min-width: 55px;
                padding: 8px 12px;
            }

            .countdown-item .number {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="maintenance-container">
        <div class="maintenance-icon">🔧</div>
        <span class="status-badge">🚧 Under Maintenance</span>
        <h1>Be Right Back</h1>
        <p>
            I'm currently performing scheduled maintenance to improve your experience.
            <br>This shouldn't take long.
        </p>

        <div class="countdown">
            <div class="countdown-item">
                <span class="number" id="hours">00</span>
                <span class="label">Hours</span>
            </div>
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
            <div class="progress" id="progress"></div>
        </div>

        <p style="font-size:0.85rem; color:#94a3b8; margin-top:-8px;">
            ⏳ Estimated time remaining
        </p>

        <a href="javascript:history.back()" class="back-link">← Go Back</a>
    </div>

    <script>
        // Set maintenance duration (in seconds)
        const totalDuration = 7200; // 2 hours
        let timeLeft;

        // Force reset if ?reset=1
        if (window.location.search.includes('reset=1')) {
            localStorage.removeItem('maintenanceTimeLeft');
            window.location.href = window.location.pathname;
        }

        // Check if timer exists in localStorage
        if (localStorage.getItem('maintenanceTimeLeft')) {
            timeLeft = parseInt(localStorage.getItem('maintenanceTimeLeft'));
            // If timer expired or invalid, reset
            if (timeLeft <= 0 || isNaN(timeLeft)) {
                localStorage.removeItem('maintenanceTimeLeft');
                timeLeft = totalDuration;
                localStorage.setItem('maintenanceTimeLeft', timeLeft);
            }
        } else {
            timeLeft = totalDuration;
            localStorage.setItem('maintenanceTimeLeft', timeLeft);
        }

        const hoursEl = document.getElementById('hours');
        const minutesEl = document.getElementById('minutes');
        const secondsEl = document.getElementById('seconds');
        const progressEl = document.getElementById('progress');

        function updateCountdown() {
            if (timeLeft <= 0) {
                localStorage.removeItem('maintenanceTimeLeft');
                window.location.href = 'index.php';
                return;
            }

            const hours = Math.floor(timeLeft / 3600);
            const minutes = Math.floor((timeLeft % 3600) / 60);
            const seconds = Math.floor(timeLeft % 60);

            hoursEl.textContent = String(hours).padStart(2, '0');
            minutesEl.textContent = String(minutes).padStart(2, '0');
            secondsEl.textContent = String(seconds).padStart(2, '0');

            const progress = ((totalDuration - timeLeft) / totalDuration) * 100;
            progressEl.style.width = Math.min(progress, 100) + '%';

            timeLeft--;
            localStorage.setItem('maintenanceTimeLeft', timeLeft);
        }

        setInterval(updateCountdown, 1000);
        updateCountdown();
    </script>
</body>

</html>