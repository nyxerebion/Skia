<?php

function getEnemies() {
    return [
        1 => [
            'name' => 'Rowan',
            'health' => 100,
            'damage' => 2,
            'defense' => 0,
            'reward' => 50,
            'critChance' => 0,
            'critMultiplier' => 1.5,
            'xp' => 10
        ],
        2 => [
            'name' => 'Ariella',
            'health' => 200,
            'damage' => 5,
            'defense' => 2,
            'reward' => 100,
            'critChance' => 0,
            'critMultiplier' => 1.5,
            'xp' => 25
        ],
        3 => [
            'name' => 'Dan',
            'health' => 550,
            'damage' => 20,
            'defense' => 5,
            'reward' => 200,
            'critChance' => 5,
            'critMultiplier' => 1.5,
            'xp' => 50
        ],
        4 => [
            'name' => 'Nathan',
            'health' => 1000,
            'damage' => 100,
            'defense' => 10,
            'reward' => 500,
            'critChance' => 8,
            'critMultiplier' => 1.8,
            'xp' => 100
        ],
        5 => [
            'name' => 'David',
            'health' => 5000,
            'damage' => 500,
            'defense' => 20,
            'reward' => 2000,
            'critChance' => 12,
            'critMultiplier' => 2.0,
            'xp' => 250
        ],
        6 => [
            'name' => 'Christian',
            'health' => 10000,
            'damage' => 600,
            'defense' => 30,
            'reward' => 5000,
            'critChance' => 15,
            'critMultiplier' => 2.2,
            'xp' => 500
        ],
        7 => [
            'name' => 'Dan 2.0',
            'health' => 25000,
            'damage' => 950,
            'defense' => 40,
            'reward' => 8000,
            'critChance' => 20,
            'critMultiplier' => 2.5,
            'xp' => 1000
        ],
        8 => [
            'name' => 'Rowan 2.0',
            'health' => 50000,
            'damage' => 1200,
            'defense' => 50,
            'reward' => 20000,
            'critChance' => 25,
            'critMultiplier' => 3.0,
            'xp' => 5000
        ],
        9 => [
            'name' => 'Ariella 2.0',
            'health' => 100000,
            'damage' => 2000,
            'defense' => 80,
            'reward' => 50000,
            'critChance' => 30,
            'critMultiplier' => 3.5,
            'xp' => 10000
        ]
    ];
}

function getEnemyByLevel($level) {
    $enemies = getEnemies();
    $level = min($level, count($enemies));
    return $enemies[$level] ?? $enemies[1];
}

function getMaxEnemyLevel() {
    return count(getEnemies());
}