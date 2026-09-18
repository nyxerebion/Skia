<?php
// config/levels.php

function getLevelData() {
    return [
        1 => ['xpRequired' => 0, 'damageBonus' => 0, 'healthBonus' => 0],
        2 => ['xpRequired' => 500, 'damageBonus' => 2, 'healthBonus' => 10],
        3 => ['xpRequired' => 1250, 'damageBonus' => 2, 'healthBonus' => 10],
        4 => ['xpRequired' => 4050, 'damageBonus' => 2, 'healthBonus' => 10],
        5 => ['xpRequired' => 7900, 'damageBonus' => 2, 'healthBonus' => 10],
        6 => ['xpRequired' => 10000, 'damageBonus' => 2, 'healthBonus' => 10],
        7 => ['xpRequired' => 13050, 'damageBonus' => 2, 'healthBonus' => 10],
        8 => ['xpRequired' => 17050, 'damageBonus' => 2, 'healthBonus' => 10],
        9 => ['xpRequired' => 29200, 'damageBonus' => 2, 'healthBonus' => 10],
        10 => ['xpRequired' => 45700, 'damageBonus' => 5, 'healthBonus' => 30],
        11 => ['xpRequired' => 70700, 'damageBonus' => 2, 'healthBonus' => 10],
        12 => ['xpRequired' => 129200, 'damageBonus' => 2, 'healthBonus' => 10],
        13 => ['xpRequired' => 425700, 'damageBonus' => 3, 'healthBonus' => 30],
        14 => ['xpRequired' => 550700, 'damageBonus' => 2, 'healthBonus' => 10],
    ];
}

function getLevelByXp($xp) {
    $levels = getLevelData();
    $currentLevel = 1;
    foreach ($levels as $level => $data) {
        if ($xp >= $data['xpRequired']) {
            $currentLevel = $level;
        }
    }
    return min($currentLevel, getMaxPlayerLevel());
}

function getMaxPlayerLevel() {
    return max(array_keys(getLevelData()));
}

function getLevelXpRequired($level) {
    $levels = getLevelData();
    $maxLevel = getMaxPlayerLevel();
    if ($level > $maxLevel) {
        return 0;
    }
    return $levels[$level]['xpRequired'] ?? 0;
}