// ----- ELEMENTS -----
const board = document.getElementById("board");
const timerEl = document.getElementById("timer");
const scoreEl = document.getElementById("score");
const pointsEl = document.getElementById("points");
const startBtn = document.getElementById("startBtn");
const highScoreDisplay = document.getElementById("highScoreDisplay");
const pointsDisplay = document.getElementById("pointsDisplay");
const totalPointsDisplay = document.getElementById("totalPointsDisplay");
const leaderboardBody = document.getElementById("leaderboardBody");

// ----- GAME STATE -----
let currGold = null,
  currBomb = null;
let score,
  points,
  player_points,
  player_total_points,
  timeLeft = 30,
  gameActive = false,
  gameOver = false,
  isSaving = false,
  timeBuffer = 0;
let goldTimer, bombTimer, countdown;

const API_URL = window.location.pathname.includes("/skia")
  ? "/skia/api"
  : "/api";

window.addEventListener("focus", () => {
  loadStats();
});

document.addEventListener("DOMContentLoaded", InitializeWhackGame);

function InitializeWhackGame() {
  score = 0;
  points = 0;
  total_points = 0;
  loadStats();
  loadLeaderboard();
}

function updateStatsDisplay(stats) {
  if (!stats || typeof stats !== "object") {
    console.error("Invalid stats data:", stats);
    return;
  }

  player_score = stats.score ?? 0;
  player_points = stats.points ?? 0;
  player_total_points = stats.total_points ?? 0;

  if (highScoreDisplay) highScoreDisplay.textContent = player_score;
  if (pointsDisplay) pointsDisplay.textContent = player_points;
  if (totalPointsDisplay) totalPointsDisplay.textContent = player_total_points;
}

function loadStats(retryCount = 0) {
  const csrfToken =
    document.querySelector('meta[name="csrf-token"]')?.content || "";

  fetch(API_URL + "/games/whack-load-stats.php?_=" + Date.now(), {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ csrf_token: csrfToken }),
  })
    .then((res) => {
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      return res.json();
    })
    .then((data) => {
      if (data.success && data.stats) {
        updateStatsDisplay(data.stats);
        toastMessage("Game stats loaded!", "win");
      } else {
        console.error("Failed to load stats:", data.error || "Unknown error");
        toastMessage("Failed to load game stats.", "error");
      }
    })
    .catch((err) => {
      console.error("Error loading stats:", err);
      if (retryCount < 3) {
        setTimeout(() => loadStats(retryCount + 1), 1000 * (retryCount + 1));
      } else {
        toastMessage(
          "Failed to load stats after 3 attempts. Please refresh.",
          "error",
        );
      }
    });
}

// ----- BUILD BOARD -----
for (let i = 0; i < 9; i++) {
  let tile = document.createElement("div");
  tile.className = "tile";
  tile.id = i;
  tile.onclick = function () {
    clickTile(this);
  };
  board.appendChild(tile);
}

// ----- LOAD LEADERBOARD -----
function loadLeaderboard() {
  const csrfToken =
    document.querySelector('meta[name="csrf-token"]')?.content || "";

  fetch(API_URL + "/games/whack-leaderboard.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ csrf_token: csrfToken }),
  })
    .then((res) => res.json())
    .then((data) => {
      if (!data.success) return;

      if (data.stats) {
        updateStatsDisplay(data.stats);
      }

      if (data.leaderboard?.length) {
        leaderboardBody.innerHTML = data.leaderboard
          .map((entry, index) => {
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
          })
          .join("");
      } else {
        leaderboardBody.innerHTML = `<tr><td colspan="6" style="text-align:center;color:#94a3b8;">No scores yet</td></tr>`;
      }
    })
    .catch(() => {
      leaderboardBody.innerHTML = `<tr><td colspan="6" style="text-align:center;color:#94a3b8;">Failed to load</td></tr>`;
    });
}

function escapeHtml(text) {
  if (!text) return "";
  const div = document.createElement("div");
  div.textContent = text;
  return div.innerHTML;
}

// ----- SAVE TIME -----
function saveTime() {
  timeBuffer++;

  if (timeBuffer < 10) return;
  if (isSaving) return;
  isSaving = true;

  const csrfToken =
    document.querySelector('meta[name="csrf-token"]')?.content || "";

  fetch(API_URL + "/games/whack-save-time.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      time_increment: timeBuffer,
      csrf_token: csrfToken,
    }),
  })
    .catch(() => console.error("Failed to save time"))
    .finally(() => {
      isSaving = false;
      timeBuffer = 0;
    });
}

// ----- SAVE SCORE -----
function saveScore(finalScore, finalPoints) {
  const csrfToken =
    document.querySelector('meta[name="csrf-token"]')?.content || "";

  fetch(API_URL + "/games/whack-save-score.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      score: finalScore,
      points_gained: finalPoints,
      csrf_token: csrfToken,
    }),
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        highScoreDisplay.textContent = data.high_score || finalScore;
        pointsDisplay.textContent = data.points || 0;
        totalPointsDisplay.textContent = data.total_points || 0;
        loadStats();
      }
    })
    .catch(() => console.error("Failed to save score"));
}

// ----- GAME FUNCTIONS -----
function randTile() {
  return Math.floor(Math.random() * 9);
}

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
  toastMessage(`⏱️ Time up! Score: ${score} | Points: ${points}`, "info");
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
    toastMessage(`💥 BOOM! Score: ${score} | Points: ${points}`, "lost");
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

  document
    .querySelectorAll(".tile")
    .forEach((t) => t.classList.remove("gold", "bomb"));
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
setInterval(saveTime, 1000);
startBtn.onclick = startGame;
