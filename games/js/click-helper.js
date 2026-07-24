function toastMessage(text, type = "info") {
  let container = document.querySelector(".toast-container");
  if (!container) {
    container = document.createElement("div");
    container.className = "toast-container";
    document.body.appendChild(container);
  }

  const msg = document.createElement("div");
  msg.textContent = text;
  msg.className = `message ${type}`;

  let timeOut = type === "win" || type === "lost" ? 3500 : 2000;
  msg.style.animation = `showThenHide ${timeOut / 1000}s ease`;

  container.appendChild(msg);
  setTimeout(() => msg.remove(), timeOut);
}

function addActionMessage(text, type = "info") {
  const log = document.getElementById("actionMessages");
  if (!log) return;

  const msg = document.createElement("div");
  msg.textContent = text;
  msg.className = `log-entry ${type}`;
  log.insertBefore(msg, log.firstChild);

  while (log.children.length > 10) log.removeChild(log.lastChild);
}

function addCombatEffect(target, type, message) {
  const container = document.getElementById(
    target === "player" ? "playerCombatEffect" : "enemyCombatEffect",
  );
  if (!container) return;

  // ✅ Limit based on type
  const maxItems = type === "crit" ? 6 : 9;
  while (container.children.length >= maxItems) {
    container.removeChild(container.children[0]);
  }

  const emojis = {
    click: "👆",
    damage: "⚔️",
    crit: "💥",
    heal: "💚",
    defense: "🛡️",
    block: "🔰",
    enemy: "👾",
    vampire: "🧛",
    xp: "📈",
    levelup: "⬆️",
    stance: "🛡️",
  };

  const el = document.createElement("div");
  el.className = `combat-effect ${type}`;
  el.textContent = `${emojis[type] || "•"} ${message}`;

  container.appendChild(el);

  setTimeout(() => {
    if (el.parentNode) el.remove();
  }, 2500);
}

// ============================================
// REVIVE MODAL
// ============================================
function showReviveModal(stats) {
  const modal = document.getElementById("reviveModal");
  if (!modal) return;

  document.getElementById("reviveCoins").textContent = stats.coins || 0;
  document.getElementById("reviveLevel").textContent = stats.level || 1;
  document.getElementById("reviveEnemy").textContent =
    stats.current_enemy || "Rowan";
  document.getElementById("reviveEnemyLevel").textContent =
    stats.current_enemy_level || 1;

  modal.classList.add("show");
  modal.style.display = "flex";
}

function hideReviveModal() {
  const modal = document.getElementById("reviveModal");
  if (!modal) return;
  modal.classList.remove("show");
  modal.style.display = "none";
}

function revivePlayer() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || "";

    fetch(API_URL + "/games/click-revive.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ csrf_token: csrfToken }),
    })
    .then((res) => res.json())
    .then((data) => {
        if (data.success) {
            updateStatsDisplay(data.stats);
            updateXpBar(data.stats); // ✅ Force XP bar update
            hideReviveModal();
            toastMessage("💚 You have been revived!", "win");
            healthCheck();
        } else {
            toastMessage(data.error || "Revive failed", "error");
        }
    })
    .catch((err) => {
        console.error("Revive error:", err);
        toastMessage("Revive failed. Retrying...", "error");
    });
}

// Event listeners for modal buttons
document.addEventListener("DOMContentLoaded", function () {
  const reviveBtn = document.getElementById("reviveBtn");

  if (reviveBtn) {
    reviveBtn.addEventListener("click", revivePlayer);
  }
});

// Shop toggle
document.addEventListener("DOMContentLoaded", function () {
  const shopToggleBtn = document.getElementById("shopToggleBtn");
  const shopContainer = document.getElementById("shopContainer");

  if (shopToggleBtn && shopContainer) {
    shopToggleBtn.addEventListener("click", function () {
      if (shopContainer.style.display === "none") {
        shopContainer.style.display = "block";
        loadShop();
        shopToggleBtn.textContent = "❌ Close Shop";
      } else {
        shopContainer.style.display = "none";
        shopToggleBtn.textContent = "🏪 Shop";
      }
    });
  }
});
