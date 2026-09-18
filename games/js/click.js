// click.js - FULL VERSION
let clicks,
  totalClicks,
  clickBuffer,
  timeBuffer,
  clickPower,
  damage,
  health,
  maxHealth,
  coins,
  totalCoins,
  level;
let enemyHealth,
  enemyMaxHealth,
  enemyDamage,
  enemyReward,
  enemyXP,
  defense,
  critChance,
  critMultiplier;
let enemyCritChance, enemyCritMultiplier;
let lastUpdate = 0;

const clickButton = document.getElementById("clickButton");
const attackButton = document.getElementById("attackButton");

const API_URL = window.location.pathname.includes("/skia")
  ? "/skia/api"
  : "/api";

// Broadcast channel for tab sync
const channel = new BroadcastChannel("click_adventure");

channel.onmessage = (event) => {
  if (event.data.type === "stats_updated") {
    console.log("Stats changed in another tab – reloading...");
    loadStats();
  }
};

document.addEventListener("DOMContentLoaded", InitializeClickGame);

function InitializeClickGame() {
  clicks = 0;
  totalClicks = 0;
  clickBuffer = 0;
  timeBuffer = 0;
  clickPower = 1;
  damage = 2;
  health = 100;
  maxHealth = 100;
  coins = 0;
  totalCoins = 0;
  level = 1;
  enemyName = "Rowan";
  enemyHealth = 100;
  enemyMaxHealth = 100;
  enemyDamage = 2;
  enemyDefense = 0;
  enemyReward = 0;
  enemyXP = 10;
  defense = 0;
  critChance = 0;
  critMultiplier = 1.5;
  enemyCritChance = 5;
  enemyCritMultiplier = 1.5;

  loadStats();
  healthCheck();

  if (clickButton) {
    clickButton.addEventListener("click", handleClick);
  }

  if (attackButton) {
    attackButton.addEventListener("click", handleAttack);
  }
}

window.addEventListener("beforeunload", () => {
  const data = {
    clicks: clicks,
    total_clicks: totalClicks,
    coins: coins,
    total_coins: totalCoins,
    health: health,
    enemy_health: enemyHealth,
  };

  const csrfToken =
    document.querySelector('meta[name="csrf-token"]')?.content || "";
  navigator.sendBeacon(
    API_URL + "/games/click-save-beforeunload.php",
    JSON.stringify({ ...data, csrf_token: csrfToken }),
  );
});

window.addEventListener("focus", () => {
  loadStats();
  healthCheck();
});

// ============================================
// UPDATE FUNCTIONS
// ============================================

function updateXpBar(stats) {
  const xpBar = document.getElementById("xpBar");
  const xpText = document.getElementById("xpText");
  if (!xpBar || !xpText) return;

  const currentXp = stats?.experience ?? 0;
  const xpRequired = stats?.xp_required ?? 0;
  const maxLevel = stats?.max_player_level ?? 14;
  const currentLevel = stats?.level ?? 1;

  if (currentLevel >= maxLevel) {
    xpBar.style.width = "100%";
    xpText.textContent = "MAX LEVEL";
  } else if (xpRequired > 0) {
    const xpPercent = Math.min(100, (currentXp / xpRequired) * 100);
    xpBar.style.width = xpPercent + "%";
    xpText.textContent = `${currentXp} / ${xpRequired} XP`;
  } else {
    xpBar.style.width = "0%";
    xpText.textContent = "0 / 0 XP";
  }
}

function updateStatsPanels(stats) {
  if (!stats || typeof stats !== "object") {
    console.error("Invalid stats data:", stats);
    return;
  }

  // 📊 STATS PANEL
  const statElements = {
    statDamage:
      formatNumber(stats.damage ?? 0, "damage", true) +
      " | " +
      (stats.damage ?? 0),
    statClickPower:
      formatNumber(stats.click_power ?? 0, "clicks", true) +
      " | " +
      (stats.click_power ?? 0),
    statMaxHealth:
      formatNumber(stats.max_health ?? 100, "health", true) +
      " | " +
      (stats.max_health ?? 0),
    statDefense:
      formatNumber(stats.defense ?? 0, "defense", true) +
      " | " +
      (stats.defense ?? 0),
    statCritChance: (stats.critical_chance ?? 0) + "%",
    statCritMultiplier: (stats.critical_multiplier ?? 1.5) + "x",
    statVampire: (stats.vampire ?? 0) + "%",
    statSuperCrit: stats.super_crit_chance
      ? stats.super_crit_chance + "%"
      : "—",
  };

  Object.keys(statElements).forEach((id) => {
    const el = document.getElementById(id);
    if (el) el.textContent = statElements[id];
  });

  // 📈 HISTORY PANEL
  const historyElements = {
    currentCoins:
      formatNumber(stats.coins ?? 0, "coins", true) +
      " | " +
      (stats.coins ?? 0),
    totalCoinsEarned:
      formatNumber(stats.total_coins ?? 0, "coins", true) +
      " | " +
      (stats.total_coins ?? 0),
    totalCoinsSpent:
      formatNumber(
        (stats.total_coins ?? 0) - (stats.coins ?? 0),
        "coins",
        true,
      ) +
      " | " +
      ((stats.total_coins ?? 0) - (stats.coins ?? 0)),
    currentClicks:
      formatNumber(stats.clicks ?? 0, "clicks", true) +
      " | " +
      (stats.clicks ?? 0),
    totalClicksEarned:
      formatNumber(stats.total_clicks ?? 0, "clicks", true) +
      " | " +
      (stats.total_clicks ?? 0),
    totalClicksSpent:
      formatNumber(
        (stats.total_clicks ?? 0) - (stats.clicks ?? 0),
        "clicks",
        true,
      ) +
      " | " +
      ((stats.total_clicks ?? 0) - (stats.clicks ?? 0)),
  };

  Object.keys(historyElements).forEach((id) => {
    const el = document.getElementById(id);
    if (el) el.textContent = historyElements[id];
  });

  // 🏆 ACHIEVES PANEL
  const achieveElements = {
    highestDamage:
      formatNumber(stats.highest_damage ?? stats.damage ?? 0, "damage", true) +
      " | " +
      (stats.highest_damage ?? stats.damage ?? 0),
    highestCrit: (stats.highest_crit ?? stats.critical_chance ?? 0) + "%",
    enemiesDefeated: stats.kills ?? 0,
    playTime: stats.time_played ? formatTime(stats.time_played) : "0s",
  };

  Object.keys(achieveElements).forEach((id) => {
    const el = document.getElementById(id);
    if (el) el.textContent = achieveElements[id];
  });

  // ⭐ LEVEL PANEL
  const levelElements = {
    playerLevel: stats.level ?? 1,
    playerXP: stats.experience ?? 0,
    playerXPMax: stats.xp_required ?? 100,
    totalLevels: stats.max_player_level ?? 14,
    reqXP: stats.xp_required ?? 100,
  };

  Object.keys(levelElements).forEach((id) => {
    const el = document.getElementById(id);
    if (el) el.textContent = levelElements[id];
  });

  // XP Bar
  updateXpBar(stats);

  // Next level bonus
  const bonusEl = document.getElementById("nextLevelBonus");
  if (bonusEl) {
    const nextDmg = (stats.damage ?? 0) + 2;
    const nextHP = (stats.max_health ?? 100) + 10;
    bonusEl.textContent = `+${nextDmg} dmg, +${nextHP} HP`;
  }
}

function updateStatsDisplay(stats) {
  if (!stats || typeof stats !== "object") {
    console.error("Invalid stats data:", stats);
    return;
  }

  if (stats.updated_at && stats.updated_at <= lastUpdate) {
    console.log("Skipping stale data");
    return;
  }

  if (stats.updated_at) {
    lastUpdate = stats.updated_at;
  }

  health = stats.health ?? 100;
  maxHealth = stats.max_health ?? 100;
  defense = stats.defense ?? 0;
  damage = stats.damage ?? 2;
  clickPower = stats.click_power ?? 1;
  critChance = stats.critical_chance ?? 0;
  critMultiplier = stats.critical_multiplier ?? 1.5;
  level = stats.level ?? 1;
  clicks = stats.clicks ?? 0;
  totalClicks = stats.total_clicks ?? 0;
  coins = stats.coins ?? 0;
  totalCoins = stats.total_coins ?? 0;
  enemyName = stats.current_enemy ?? "Rowan";
  enemyHealth = stats.enemy_health ?? 100;
  enemyMaxHealth = stats.enemy_max_health ?? 100;
  enemyDamage = stats.enemy_damage ?? 2;
  enemyDefense = stats.enemy_defense ?? 0;
  enemyReward = stats.enemy_reward ?? 0;
  enemyXP = stats.enemy_xp ?? 10;
  enemyCritChance = stats.enemy_crit_chance ?? 0;
  enemyCritMultiplier = stats.enemy_crit_multiplier ?? 1.5;

  const el = (id) => document.getElementById(id);

  if (el("playerHealth")) el("playerHealth").textContent = health;
  if (el("playerMaxHealth")) el("playerMaxHealth").textContent = maxHealth;
  if (el("playerDefense")) el("playerDefense").textContent = formatNumber(defense, 'defense');
  if (el("playerDamage")) el("playerDamage").textContent = formatNumber(damage, 'damage');
  if (el("clickPower")) el("clickPower").textContent = clickPower;
  if (el("playerCritChance")) el("playerCritChance").textContent = critChance;
  if (el("playerCritMultiplier"))
    el("playerCritMultiplier").textContent = critMultiplier;
  if (el("playerLevel")) el("playerLevel").textContent = level;
  if (el("clicksCount")) el("clicksCount").textContent = formatNumber(clicks, 'clicks', false);
  if (el("coinsCount")) el("coinsCount").textContent = formatNumber(coins, 'coins', false);
  if (el("enemyHealth")) el("enemyHealth").textContent = enemyHealth;
  if (el("enemyMaxHealth")) el("enemyMaxHealth").textContent = enemyMaxHealth;
  if (el("enemyDamage")) el("enemyDamage").textContent = formatNumber(enemyDamage, 'enemy', true);
  if (el("enemyDefense")) el("enemyDefense").textContent = formatNumber(enemyDefense, 'defense', true);
  if (el("enemyCritChance"))
    el("enemyCritChance").textContent = enemyCritChance;
  if (el("enemyCritMultiplier"))
    el("enemyCritMultiplier").textContent = enemyCritMultiplier;
  if (el("enemyReward")) el("enemyReward").textContent = enemyReward;
  if (el("enemyXP")) el("enemyXP").textContent = enemyXP;
  if (el("enemyName")) el("enemyName").textContent = enemyName || "Rowan";
  if (el("enemyLevel"))
    el("enemyLevel").textContent =
      (stats.current_enemy_level || 1) + "/" + (stats.max_enemy_level || 9);

  if (stats.shop_upgrades) {
    const upgrades =
      typeof stats.shop_upgrades === "string"
        ? JSON.parse(stats.shop_upgrades)
        : stats.shop_upgrades;
    const totalUpgrades = Object.values(upgrades).reduce((a, b) => a + b, 0);
    if (el("totalUpgrades")) el("totalUpgrades").textContent = totalUpgrades;
  }

  const healthPercent = (health / maxHealth) * 100;
  if (el("playerHealthBar"))
    el("playerHealthBar").style.width = healthPercent + "%";

  const enemyHealthPercent = (enemyHealth / enemyMaxHealth) * 100;
  if (el("enemyHealthBar"))
    el("enemyHealthBar").style.width = enemyHealthPercent + "%";

  // Update panels (this already calls updateXpBar)
  updateStatsPanels(stats);
}

// ============================================
// LOAD STATS
// ============================================

function loadStats(retryCount = 0) {
  const csrfToken =
    document.querySelector('meta[name="csrf-token"]')?.content || "";

  fetch(API_URL + "/games/click-load-stats.php?_=" + Date.now(), {
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

function healthCheck() {
  const csrfToken =
    document.querySelector('meta[name="csrf-token"]')?.content || "";

  fetch(API_URL + "/games/click-health-check.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ csrf_token: csrfToken }),
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        updateStatsDisplay(data.stats);

        if (data.stats.health <= 0) {
          showReviveModal(data.stats);
        }

        if (data.message) {
          console.log("Health check:", data.message);
        }
      }
    })
    .catch((err) => console.error("Health check error:", err));
}

// ============================================
// ATTACK & CLICK
// ============================================

function handleAttack() {
  if (enemyHealth <= 0) {
    toastMessage("Enemy already dead! Loading next...", "info");
    healthCheck();
    return;
  }

  if (health <= 0) {
    toastMessage("💀 You are dead! Respawning...", "error");
    healthCheck();
    return;
  }

  const attackBtn = document.getElementById("attackButton");
  attackBtn.disabled = true;
  attackBtn.textContent = "⚔️ ATTACKING...";

  const csrfToken =
    document.querySelector('meta[name="csrf-token"]')?.content || "";

  fetch(API_URL + "/games/click-attack.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      action: "attack",
      csrf_token: csrfToken,
    }),
  })
    .then((res) => {
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      return res.json();
    })
    .then((data) => {
      attackBtn.disabled = false;
      attackBtn.textContent = "⚔️ ATTACK";

      if (data.success) {
        updateStatsDisplay(data.stats);

        // Vampire heal
        if (data.vampire_heal && data.vampire_heal > 0) {
          addCombatEffect("player", "vampire", `+${data.vampire_heal} HP`);
          addActionMessage(
            `🧛 Vampire effect healed ${data.vampire_heal} HP`,
            "vampire",
          );
        }

        // Defensive Stance
        if (data.defense_multiplier_active) {
          addCombatEffect("player", "stance", "Defense doubled!");
          addActionMessage(`🛡️ Defensive Stance active`, "stance");
        }

        if (data.stats.health <= 0) {
          showReviveModal(data.stats);
        } else if (data.defeated) {
          const reward = data.stats.enemy_reward || 0;
          addCombatEffect("enemy", "enemy", `Defeated! +${reward} coins`);
          addActionMessage(`Enemy defeated! +${reward} coins`, "reward");

          if (data.xp_gain) {
            const xpText = data.xp_boost_active ? ` (Boosted)` : ``;
            addCombatEffect("player", "xp", `+${data.xp_gain} XP${xpText}`);
            addActionMessage(`📈 +${data.xp_gain} XP${xpText}`, "xp");
          }

          if (data.double_level_triggered) {
            addCombatEffect("player", "levelup", "Double Level!");
            addActionMessage(`⬆️ Double Level Up!`, "levelup");
          }

          updateXpBar(data.stats);

          const enemyCard = document.querySelector(".enemy-card");
          enemyCard.classList.add("flash-win");
          setTimeout(() => enemyCard.classList.remove("flash-win"), 500);
        } else {
          const damageDealt = data.enemy_damage_dealt || 0;
          const enemyBaseDamage = data.enemy_base_damage || 0;
          const playerDefense = data.player_defense || 0;
          const enemyDefenseBlocked = data.enemy_defense_blocked || 0;

          if (data.crit) {
            addCombatEffect("enemy", "crit", `CRIT! -${data.damage} HP`);
          } else {
            addCombatEffect("enemy", "damage", `-${data.damage} HP`);
          }

          if (enemyDefenseBlocked > 0) {
            addCombatEffect(
              "enemy",
              "defense",
              `Blocked ${enemyDefenseBlocked}`,
            );
            addActionMessage(
              `Enemy blocked ${enemyDefenseBlocked} damage`,
              "defense",
            );
          }

          if (data.enemy_crit && damageDealt > 0) {
            addCombatEffect("player", "crit", `Enemy CRIT! -${damageDealt} HP`);
            addActionMessage(`Enemy CRIT! -${damageDealt} HP`, "enemy-crit");
          } else if (damageDealt > 0) {
            addCombatEffect("player", "damage", `-${damageDealt} HP`);

            if (playerDefense > 0 && enemyBaseDamage > damageDealt) {
              addCombatEffect(
                "player",
                "defense",
                `Blocked ${enemyBaseDamage - damageDealt}`,
              );
              addActionMessage(
                `You blocked ${enemyBaseDamage - damageDealt} damage`,
                "defense",
              );
            }
          } else if (damageDealt === 0 && enemyBaseDamage > 0) {
            addCombatEffect(
              "player",
              "defense",
              `Blocked! +${playerDefense} DEF`,
            );
            addActionMessage(`You fully blocked the attack!`, "defense");
          }

          addActionMessage(`⚔️ You Dealt ${data.damage} damage`, "damage");
        }

        healthCheck();
        channel.postMessage({ type: "stats_updated" });
      } else {
        toastMessage(data.error || "Attack failed", "error");
      }
    })
    .catch((err) => {
      console.error("Attack error:", err);
      toastMessage("Attack failed. Retrying...", "error");
      attackBtn.disabled = false;
      attackBtn.textContent = "⚔️ ATTACK";
    });
}

function handleClick() {
  if (health <= 0) {
    toastMessage("💀 You are dead! Respawning...", "error");
    healthCheck();
    return;
  }

  const clickBtn = document.getElementById("clickButton");
  clickBtn.disabled = true;
  clickBtn.textContent = "👆 CLICKING...";

  const csrfToken =
    document.querySelector('meta[name="csrf-token"]')?.content || "";

  fetch(API_URL + "/games/click-action.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      action: "click",
      csrf_token: csrfToken,
    }),
  })
    .then((res) => {
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      return res.json();
    })
    .then((data) => {
      clickBtn.disabled = false;
      clickBtn.textContent = "👆 CLICK";

      if (data.success) {
        updateStatsDisplay(data.stats);

        addCombatEffect("player", "click", `+${data.click_gain} clicks`);
        addActionMessage(`👆 Clicked! +${data.click_gain} clicks`, "click");
        healthCheck();
        channel.postMessage({ type: "stats_updated" });
      } else {
        toastMessage(data.error || "Click failed", "error");
      }
    })
    .catch((err) => {
      console.error("Click error:", err);
      toastMessage("Click failed. Retrying...", "error");
      clickBtn.disabled = false;
      clickBtn.textContent = "👆 CLICK";
    });
}

// ============================================
// TIME SAVING
// ============================================

function saveTime() {
  timeBuffer++;

  if (timeBuffer < 10) return;

  const csrfToken =
    document.querySelector('meta[name="csrf-token"]')?.content || "";

  fetch(API_URL + "/games/click-save-time.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      time_increment: timeBuffer,
      csrf_token: csrfToken,
    }),
  })
    .catch(() => console.error("Failed to save time"))
    .finally(() => {
      timeBuffer = 0;
    });
}

setInterval(saveTime, 1000);
