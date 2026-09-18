<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../backend/config/click-shop.php';

if (!checkLogin()) {
    setFlashMessage('Unauthorized access', 'error');
    header('Location: ../index.php');
    exit;
}

logAction('Played Click Adventure Game');

$player = getClickPlayer($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once __DIR__ . '/../core/head.php'; ?>
    <title>Click Adventure | Skia</title>
    <link rel="stylesheet" href="css/click.css?v=<?= filemtime(__DIR__ . '/css/click.css') ?>">
</head>

<body>
    <header>
        <h1>Click Adventure</h1>
    </header>

    <main>
        <div class="toast-container"></div>
        <section class="game-section">
            <div class="cards-wrapper">
                <div class="game-card enemy-card">
                    <div class="title enemy-title">
                        <span>👾 <span id="enemyName">Rowan</span></span>
                        <span class="enemy-level-badge" id="enemyLevel">1/9</span>
                    </div>

                    <div class="health-bar-container">
                        <div class="health-bar-fill" id="enemyHealthBar"></div>
                    </div>

                    <div class="stats enemy-stats">
                        <span class="health-wrapper">❤️
                            <span id="enemyHealth"><?= $player['enemy_health'] ?? 100 ?></span>/
                            <span id="enemyMaxHealth"><?= $player['enemy_max_health'] ?? 100 ?></span>HP
                        </span>
                        <span> <span id="enemyDamage"><?= $player['enemy_damage'] ?? 2 ?></span> DMG</span>
                        <span> <span id="enemyDefense"><?= $player['enemy_defense'] ?? 0 ?></span> DEF</span>
                        <span>🎯 <span id="enemyCritChance"><?= $player['enemy_crit_chance'] ?? 5 ?></span> CRIT</span>
                        <span>💥 <span id="enemyCritMultiplier"><?= $player['enemy_crit_multiplier'] ?? 1.5 ?></span> MULT</span>
                        <span id="enemyTrueDamage" style="display: none;"></span>
                        <span>📈 <span id="enemyXP">0</span> XP</span>
                        <span>💰 <span id="enemyReward"><?= $player['enemy_reward'] ?? 0 ?></span> REWARD</span>
                    </div>

                    <div class="combat-buttons">
                        <button id="attackButton" class="btn attack-btn">⚔️ ATTACK</button>
                    </div>

                    <div class="combat-effect-wrapper">
                        <div id="enemyCombatEffect" class="combat-effect-container"></div>
                    </div>

                </div>

                <div class="game-card player-card">
                    <div class="title player-title">
                        <span>👤 <?= htmlspecialchars(getDisplayName($_SESSION['name'] ?? '')) ?></span>
                        <span>· LVL <span id="playerLevel">1</span>/<span id="playerMaxLevel">14</span></span>
                    </div>

                    <div class="health-bar-container">
                        <div class="health-bar-fill" id="playerHealthBar"></div>
                    </div>

                    <!-- After player health bar, add XP bar -->
                    <div class="xp-bar-container">
                        <div class="xp-bar-fill" id="xpBar"></div>
                        <span class="xp-text" id="xpText">0 / 500 XP</span>
                    </div>

                    <div class="stats player-stats">
                        <span class="health-wrapper">❤️
                            <span id="playerHealth"><?= $player['health'] ?? 100 ?></span>/
                            <span id="playerMaxHealth"><?= $player['max_health'] ?? 100 ?></span>HP
                        </span>

                        <span> <span id="playerDamage"><?= $player['damage'] ?? 2 ?></span> DMG</span>
                        <span> <span id="playerDefense"><?= $player['defense'] ?? 0 ?></span> DEF</span>
                        <span>👆 <span id="clickPower"><?= $player['click_power'] ?? 0 ?></span> CP</span>
                        <span>🎯 <span id="playerCritChance"><?= $player['crit_chance'] ?? 0 ?></span> CRIT</span>
                        <span>💥 <span id="playerCritMultiplier"><?= $player['crit_multiplier'] ?? 0 ?></span> MULT</span>
                    </div>

                    <div class="combat-buttons">
                        <button id="clickButton" class="btn tap-btn">👆 CLICK</button>
                    </div>

                    <div class="combat-effect-wrapper">
                        <div id="playerCombatEffect" class="combat-effect-container"></div>
                    </div>
                </div>
            </div>

            <div class="action-log">
                <h4>📋 Log</h4>
                <small>last 10 actions</small>
                <div id="actionMessages"></div>
            </div>

            <div class="resources">
                <span>👆 CLICKS: <span id="clicksCount">0</span></span>
                <span>💰 COINS: <span id="coinsCount">0</span></span>
            </div>

            <button id="shopToggleBtn" class="btn">🏪 Shop</button>

            <div id="shopContainer" class="shop-container" style="display: none;">
                <div class="shop-header">
                    <h2>🏪 Shop</h2>
                    <div class="shop-currencies">
                        <span class="shop-coins">💰 Coins: <span id="shopCoins">0</span></span>
                        <span class="shop-clicks">👆 Clicks: <span id="shopClicks">0</span></span>
                    </div>
                </div>

                <div class="shop-tabs" id="shopTabs"></div>

                <div class="shop-items" id="shopItems"></div>
            </div>

            <!--<div class="shop-stats">
                <h4>🏪 Upgrades</h4>
                <div class="upgrade-stats">
                    <span>Total Upgrades: <strong id="totalUpgrades">0</strong></span>
                    
                    <span>⚔️ Damage: <strong id="upgrade-damage">0</strong></span>
                    <span>🛡️ Defense: <strong id="upgrade-defense">0</strong></span>
                    <span>❤️ Health: <strong id="upgrade-health">0</strong></span>
                    <span>👆 Click Power: <strong id="upgrade-click">0</strong></span>
                    <span>🎯 Crit Chance: <strong id="upgrade-critchance">0</strong></span>
                    <span>💥 Crit Multiplier: <strong id="upgrade-critmultiplier">0</strong></span>
                </div>
            </div>-->
        </section>
        <nav>
            <h3>Navigate to:</h3>
            <div class="links">
                <a href="index.php">Game Center</a>
                <a href="../pages/contents.php">Dashboard</a>
                <a href="../index.php">Home Page</a>
            </div>
        </nav>

        <section class="panels-container">
            <div class="stats-panel">
                <h3>📊 STATS</h3>
                <div class="stat-row">
                    <span>⚔️ dmg</span><span id="statDamage">4</span>
                </div>
                <div class="stat-row">
                    <span>👆 click</span><span id="statClickPower">12</span>
                </div>
                <div class="stat-row">
                    <span>❤️ max HP</span><span id="statMaxHealth">100</span>
                </div>
                <div class="stat-row">
                    <span>🛡️ def</span><span id="statDefense">2</span>
                </div>
                <div class="stat-row">
                    <span>🎯 crit</span><span id="statCritChance">5%</span>
                </div>
                <div class="stat-row">
                    <span>💥 crit mult</span><span id="statCritMultiplier">1.5x</span>
                </div>
                <div class="stat-row">
                    <span>🩸 vampire</span><span id="statVampire">—</span>
                </div>
                <div class="stat-row">
                    <span>🔮 super crit</span><span id="statSuperCrit">—</span>
                </div>
            </div>

            <div class="stats-panel">
                <h3>📈 HISTORY</h3>
                <div class="stat-row">
                    <span>💰 coins now</span><span id="currentCoins">0</span>
                </div>
                <div class="stat-row">
                    <span>🪙 coins earned</span><span id="totalCoinsEarned">0</span>
                </div>
                <div class="stat-row">
                    <span>💸 coins spent</span><span id="totalCoinsSpent">0</span>
                </div>
                <div class="stat-row">
                    <span>👆 clicks now</span><span id="currentClicks">0</span>
                </div>
                <div class="stat-row">
                    <span>🖱️ total clicks</span><span id="totalClicksEarned">0</span>
                </div>
                <div class="stat-row">
                    <span>💸 clicks spent</span><span id="totalClicksSpent">0</span>
                </div>
            </div>

            <div class="stats-panel">
                <h3>🏆 ACHIEVES</h3>
                <div class="stat-row">
                    <span>⚔️ top dmg</span><span id="highestDamage">4</span>
                </div>
                <div class="stat-row">
                    <span>🎯 best crit</span><span id="highestCrit">5%</span>
                </div>
                <div class="stat-row">
                    <span>💀 kills</span><span id="enemiesDefeated">0</span>
                </div>
                <div class="stat-row">
                    <span>⏱️ time</span><span id="playTime">0s</span>
                </div>
            </div>

            <!--
            <div class="level-panel">
                <h3>⭐ LEVEL</h3>
                <div class="level-row">
                    <span>level</span><span id="playerLevel">1</span>
                </div>
                <div class="level-row">
                    <span>XP</span>
                    <span>
                        <span id="playerXP">0</span> /
                        <span id="playerXPMax">100</span>
                    </span>
                </div>
                <div class="xp-bar-container">
                    <div class="xp-bar-bg">
                        <div id="xpBar" class="xp-bar-fill" style="width: 0%"></div>
                    </div>
                </div>
                <div class="level-row">
                    <span>next bonus</span><span id="nextLevelBonus">+2 dmg, +10 HP</span>
                </div>
                <div class="level-row">
                    <span>total levels</span><span id="totalLevels">14</span>
                </div>
                <div class="level-row">
                    <span>required XP</span><span id="reqXP">500</span>
                </div>
            </div>-->
        </section>

        <!-- Revive Modal -->
        <div id="reviveModal" class="revive-modal" style="display:none;">
            <div class="revive-modal-content">
                <div class="revive-icon">💀</div>
                <h2>You Died!</h2>
                <p>Your adventure has ended. Revive to continue?</p>
                <h3>You reached:</h3>
                <div class="revive-stats">
                    <span>💰 Coins: <strong id="reviveCoins">0</strong></span>
                    <span>📊 Level: <strong id="reviveLevel">1</strong></span>
                    <span>⚔️ Enemy: <strong id="reviveEnemy">Rowan</strong></span>
                    <span>👾 Enemy Level: <strong id="reviveEnemyLevel">1</strong></span>
                </div>
                <button id="reviveBtn" class="btn revive-btn">🔄 Revive</button>
            </div>
        </div>
    </main>

    <footer>
        <p>
            Made with ❤️ by Axel | 2025-<?php echo date('Y'); ?>
        </p>
    </footer>

    <script src="js/click.js?=<?= filemtime(__DIR__ . '/js/click.js') ?>"></script>
    <script src="js/click-shop.js?=<?= filemtime(__DIR__ . '/js/click-shop.js') ?>"></script>
    <script src="js/game-helper.js?=<?= filemtime(__DIR__ . '/js/click-helper.js') ?>"></script>
    <script src="js/click-helper.js?=<?= filemtime(__DIR__ . '/js/click-helper.js') ?>"></script>
</body>

</html>