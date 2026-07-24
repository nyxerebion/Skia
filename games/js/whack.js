// ----- ELEMENTS -----
const board = document.getElementById("board");
const scoreEl = document.getElementById("score");
const pointsEl = document.getElementById("points");
const timerEl = document.getElementById("timer");
const startBtn = document.getElementById("startBtn");
const highScoreDisplay = document.getElementById("highScoreDisplay");
const totalPointsDisplay = document.getElementById("totalPointsDisplay");
const leaderboardBody = document.getElementById("leaderboardBody");

// ----- GAME STATE -----
let currGold = null,
    currBomb = null;
let score = 0,
    points = 0,
    timeLeft = 30,
    gameActive = false,
    gameOver = false,
    isSaving = false,
    timeBuffer = 0;
let goldTimer, bombTimer, countdown;

// ----- BUILD BOARD -----
for (let i = 0; i < 9; i++) {
    let tile = document.createElement("div");
    tile.className = "tile";
    tile.id = i;
    tile.onclick = function() { clickTile(this); };
    board.appendChild(tile);
}

// ----- LOAD LEADERBOARD -----
function loadData() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || "";

    fetch(API_URL + "/games/whack-leaderboard.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ csrf_token: csrfToken })
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) return;

        highScoreDisplay.textContent = data.high_score || 0;
        totalPointsDisplay.textContent = data.total_points || 0;

        if (data.leaderboard?.length) {
            leaderboardBody.innerHTML = data.leaderboard.map((entry, index) => {
                const medals = ["🥇", "🥈", "🥉"];
                const rankDisplay = medals[index] || `🔹${index + 1}`;
                const rankClass = index < 3 ? `rank-${index + 1}` : "";
                return `
                    <tr>
                        <td class="${rankClass}">${rankDisplay}</td>
                        <td>${escapeHtml(entry.username)}</td>
                        <td>${entry.score}</td>
                        <td>${entry.total_points || 0}</td>
                        <td>${entry.points || 0}</td>
                        <td>${formatTime(entry.time_played || 0)}</td>
                    </tr>
                `;
            }).join("");
        } else {
            leaderboardBody.innerHTML = `<tr><td colspan="6" style="text-align:center;color:#94a3b8;">No scores yet</td></tr>`;
        }
    })
    .catch(() => {
        leaderboardBody.innerHTML = `<tr><td colspan="6" style="text-align:center;color:#94a3b8;">Failed to load</td></tr>`;
    });
}

function escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
}

// ----- SAVE TIME (adds +1 each call) -----
function saveTime() {
  timeBuffer++;

  if (timeBuffer < 10) return;

  if (isSaving) return;
  isSaving = true;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || "";

    fetch(API_URL + "/games/whack-save-time.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            time_increment: timeBuffer,
            csrf_token: csrfToken
        })
    })
    .catch(() => console.error("Failed to save time"))
    .finally(() => { 
      isSaving = false;
      timeBuffer = 0;
     });
}

// ----- SAVE SCORE -----
function saveScore(finalScore, finalPoints) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || "";

    fetch(API_URL + "/games/whack-save-score.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            score: finalScore,
            points: finalPoints,
            csrf_token: csrfToken
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            highScoreDisplay.textContent = data.high_score || finalScore;
            totalPointsDisplay.textContent = data.total_points || 0;
            loadData();
        }
    })
    .catch(() => console.error("Failed to save score"));
}

// ----- GAME FUNCTIONS -----
function randTile() { return Math.floor(Math.random() * 9); }

function clearTile(tile) {
    if (tile) tile.classList.remove("gold", "bomb");
}

function spawnGold() {
    if (!gameActive || gameOver) return;
    clearTile(currGold);

    let num = randTile();
    if (currBomb && currBomb.id == num) {
        num = randTile();
        if (currBomb && currBomb.id == num) return;
    }

    currGold = document.getElementById(num);
    currGold.classList.add("gold");

    setTimeout(() => {
        if (currGold && currGold.id == num && !gameOver) {
            currGold.classList.remove("gold");
            currGold = null;
        }
    }, 700);
}

function spawnBomb() {
    if (!gameActive || gameOver) return;
    clearTile(currBomb);

    let num = randTile();
    if (currGold && currGold.id == num) {
        num = randTile();
        if (currGold && currGold.id == num) return;
    }

    currBomb = document.getElementById(num);
    currBomb.classList.add("bomb");

    setTimeout(() => {
        if (currBomb && currBomb.id == num && !gameOver) {
            currBomb.classList.remove("bomb");
            currBomb = null;
        }
    }, 1000);
}

function endGame() {
    gameActive = false;
    gameOver = true;
    clearInterval(goldTimer);
    clearInterval(bombTimer);
    clearInterval(countdown);
    clearTile(currGold);
    clearTile(currBomb);
    currGold = null;
    currBomb = null;
    startBtn.disabled = false;

    saveScore(score, points);
    alert(`⏱️ Time up! Score: ${score} | Points: ${points}`);
}

function clickTile(tile) {
    if (!gameActive || gameOver) return;

    if (tile.classList.contains("gold")) {
        score += 10;
        points += 1;
        scoreEl.textContent = score;
        pointsEl.textContent = points;
        tile.classList.remove("gold");
        currGold = null;
    } else if (tile.classList.contains("bomb")) {
        gameOver = true;
        gameActive = false;
        clearInterval(goldTimer);
        clearInterval(bombTimer);
        clearInterval(countdown);
        startBtn.disabled = false;

        saveScore(score, points);
        alert(`💥 BOOM! Score: ${score} | Points: ${points}`);
    }
}

function startGame() {
    gameActive = true;
    gameOver = false;
    score = 0;
    points = 0;
    timeLeft = 30;
    scoreEl.textContent = "0";
    pointsEl.textContent = "0";
    timerEl.textContent = "30";
    startBtn.disabled = true;

    document.querySelectorAll(".tile").forEach(t => t.classList.remove("gold", "bomb"));
    currGold = null;
    currBomb = null;

    goldTimer = setInterval(spawnGold, 900);
    bombTimer = setInterval(spawnBomb, 500);
    countdown = setInterval(() => {
        timeLeft--;
        timerEl.textContent = timeLeft;
        if (timeLeft <= 0) endGame();
    }, 1000);
}

// ----- INIT -----
// Start time counter on page load
setInterval(saveTime, 1000);

startBtn.onclick = startGame;
loadData();