<?php

function getShopItems() {
    return [
        [
            'id' => 1,
            'name' => 'Damage +1',
            'description' => '+1 attack damage',
            'coin_cost' => 0,
            'click_cost' => 10,
            'type' => 'damage',
            'stat' => 'damage',
            'increment' => 1,
            'value' => 1,
            'max' => 20,
            'need' => null
        ],
        [
            'id' => 2,
            'name' => 'Damage +2',
            'description' => '+2 attack damage',
            'coin_cost' => 10,
            'click_cost' => 50,
            'type' => 'damage',
            'stat' => 'damage',
            'increment' => 2,
            'value' => 2,
            'max' => 20,
            'need' => ['id' => 1, 'amount' => 10]
        ],
        [
            'id' => 3,
            'name' => 'Max Health +20',
            'description' => '+20 max health',
            'coin_cost' => 100,
            'click_cost' => 50,
            'type' => 'health',
            'stat' => 'max_health',
            'increment' => 20,
            'value' => 20,
            'max' => 30,
            'need' => null
        ],
        [
            'id' => 4,
            'name' => 'Click Power +5',
            'description' => '+5 per tap',
            'coin_cost' => 75,
            'click_cost' => 200,
            'type' => 'click',
            'stat' => 'click_power',
            'increment' => 5,
            'value' => 5,
            'max' => 10,
            'need' => null
        ],
        [
            'id' => 5,
            'name' => ' Heal +50%',
            'description' => 'Restore 50% your max health',
            'coin_cost' => 55,
            'click_cost' => 55,
            'type' => 'heal',
            'stat' => 'health',
            'increment' => 0,
            'value' => 0.5,
            'max' => 100,
            'need' => ['id' => 1, 'amount' => 5]
        ],
        [
            'id' => 6,
            'name' => 'Super Damage +5',
            'description' => 'Big damage boost',
            'coin_cost' => 200,
            'click_cost' => 300,
            'type' => 'damage',
            'stat' => 'damage',
            'increment' => 5,
            'value' => 5,
            'max' => 20,
            'need' => ['id' => 2, 'amount' => 5]
        ],
        [
            'id' => 7,
            'name' => 'Vampire',
            'description' => 'Heal 10% of damage',
            'coin_cost' => 5000,
            'click_cost' => 8000,
            'type' => 'vampire',
            'stat' => 'health',
            'increment' => 0,
            'value' => 0.10,
            'max' => 1,
            'need' => ['id' => 3, 'amount' => 3]
        ],
        [
            'id' => 8,
            'name' => 'Max Health +50',
            'description' => '+50 max health',
            'coin_cost' => 550,
            'click_cost' => 400,
            'type' => 'health',
            'stat' => 'max_health',
            'increment' => 50,
            'value' => 50,
            'max' => 20,
            'need' => ['id' => 3, 'amount' => 5]
        ],
        [
            'id' => 9,
            'name' => 'Ultra Damage +10',
            'description' => '+10 attack damage',
            'coin_cost' => 300,
            'click_cost' => 800,
            'type' => 'damage',
            'stat' => 'damage',
            'increment' => 10,
            'value' => 10,
            'max' => 15,
            'need' => ['id' => 6, 'amount' => 10]
        ],
        [
            'id' => 10,
            'name' => 'Mega Damage +20',
            'description' => '+20 attack damage',
            'coin_cost' => 600,
            'click_cost' => 1900,
            'type' => 'damage',
            'stat' => 'damage',
            'increment' => 20,
            'value' => 20,
            'max' => 10,
            'need' => ['id' => 9, 'amount' => 10]
        ],
        [
            'id' => 11,
            'name' => 'Click Power +10',
            'description' => '+10 per tap',
            'coin_cost' => 500,
            'click_cost' => 1000,
            'type' => 'click',
            'stat' => 'click_power',
            'increment' => 10,
            'value' => 10,
            'max' => 10,
            'need' => ['id' => 4, 'amount' => 10]
        ],
        [
            'id' => 12,
            'name' => 'Max Health +200',
            'description' => '+200 max health',
            'coin_cost' => 1500,
            'click_cost' => 1200,
            'type' => 'health',
            'stat' => 'max_health',
            'increment' => 200,
            'value' => 200,
            'max' => 10,
            'need' => ['id' => 8, 'amount' => 10]
        ],
        [
            'id' => 13,
            'name' => 'Ultra Mega Damage +50',
            'description' => '+50 damage',
            'coin_cost' => 2500,
            'click_cost' => 4000,
            'type' => 'damage',
            'stat' => 'damage',
            'increment' => 50,
            'value' => 50,
            'max' => 10,
            'need' => ['id' => 10, 'amount' => 5]
        ],
        [
            'id' => 14,
            'name' => 'Crit Chance +5%',
            'description' => 'Increase critical hit chance by 5%',
            'coin_cost' => 300,
            'click_cost' => 500,
            'type' => 'critChance',
            'stat' => 'critical_chance',
            'increment' => 5,
            'value' => 5,
            'max' => 10,
            'need' => ['id' => 1, 'amount' => 5]
        ],
        [
            'id' => 15,
            'name' => 'Crit Damage +0.5x',
            'description' => 'Increase critical damage multiplier',
            'coin_cost' => 500,
            'click_cost' => 1000,
            'type' => 'critMultiplier',
            'stat' => 'critical_multiplier',
            'increment' => 0.5,
            'value' => 0.5,
            'max' => 5,
            'need' => ['id' => 14, 'amount' => 3]
        ],
        [
            'id' => 16,
            'name' => 'Super Crit',
            'description' => 'Critical hits can happen on taps too!',
            'coin_cost' => 1000,
            'click_cost' => 2000,
            'type' => 'critTap',
            'stat' => 'damage',
            'increment' => 0,
            'value' => 1,
            'max' => 1,
            'need' => ['id' => 15, 'amount' => 2]
        ],
        [
            'id' => 17,
            'name' => 'XP Boost +25%',
            'description' => 'Gain 25% more XP from enemies',
            'coin_cost' => 6000,
            'click_cost' => 8000,
            'type' => 'xpBoost',
            'stat' => 'damage',
            'increment' => 0,
            'value' => 0.25,
            'max' => 3,
            'need' => ['id' => 7, 'amount' => 1]
        ],
        [
            'id' => 18,
            'name' => 'Double Level Up',
            'description' => 'Gain 2 levels instead of 1',
            'coin_cost' => 50000,
            'click_cost' => 80000,
            'type' => 'doubleLevel',
            'stat' => 'damage',
            'increment' => 0,
            'value' => 1,
            'max' => 1,
            'need' => ['id' => 17, 'amount' => 2]
        ],
        [
            'id' => 19,
            'name' => 'Leather Armor',
            'description' => '+5 Defense',
            'coin_cost' => 300,
            'click_cost' => 500,
            'type' => 'defense',
            'stat' => 'defense',
            'increment' => 5,
            'value' => 5,
            'max' => 20,
            'need' => ['id' => 3, 'amount' => 2]
        ],
        [
            'id' => 20,
            'name' => 'Iron Shield',
            'description' => '+10 Defense',
            'coin_cost' => 800,
            'click_cost' => 1200,
            'type' => 'defense',
            'stat' => 'defense',
            'increment' => 10,
            'value' => 10,
            'max' => 15,
            'need' => ['id' => 19, 'amount' => 5]
        ],
        [
            'id' => 21,
            'name' => 'Dragon Scale',
            'description' => '+25 Defense',
            'coin_cost' => 2500,
            'click_cost' => 4000,
            'type' => 'defense',
            'stat' => 'defense',
            'increment' => 25,
            'value' => 25,
            'max' => 10,
            'need' => ['id' => 20, 'amount' => 5]
        ],
        [
            'id' => 22,
            'name' => 'Defensive Stance',
            'description' => 'Double defense effectiveness',
            'coin_cost' => 5000,
            'click_cost' => 8000,
            'type' => 'defenseMultiplier',
            'stat' => 'defense',
            'increment' => 0,
            'value' => 2,
            'max' => 1,
            'need' => ['id' => 21, 'amount' => 3]
        ],
        [
            'id' => 23,
            'name' => 'Hardened Armor',
            'description' => 'Reduce enemy armor penetration by 10%',
            'coin_cost' => 3000,
            'click_cost' => 5000,
            'type' => 'penetrationResist',
            'stat' => 'defense',
            'increment' => 0,
            'value' => 0.1,
            'max' => 3,
            'need' => ['id' => 21, 'amount' => 2]
        ]
    ];
}

function getShopItemById($id) {
    $items = getShopItems();
    foreach ($items as $item) {
        if ($item['id'] === $id) {
            return $item;
        }
    }
    return null;
}

function getItemCost($item, $owned) {
    $multiplier = 1 + ($owned * 0.5);
    return [
        'coins' => floor($item['coin_cost'] * $multiplier),
        'clicks' => floor($item['click_cost'] * $multiplier)
    ];
}

function getItemIcon($type) {
    $icons = [
        'damage' => '⚔️',
        'health' => '❤️',
        'click' => '👆',
        'defense' => '🛡️',
        'defenseMultiplier' => '🛡️',
        'critChance' => '🎯',
        'critMultiplier' => '💥',
        'critTap' => '🎯',
        'heal' => '💚',
        'vampire' => '🧛',
        'xpBoost' => '📈',
        'doubleLevel' => '⬆️',
        'penetrationResist' => '🔰',
        'trueDamageBlock' => '🛡️'
    ];
    return $icons[$type] ?? '📦';
}

function getShopSections()
{
    return [
        'ATTACK' => ['damage'],
        'CLICKS' => ['click'],
        'HEALTH' => ['health', 'heal', 'vampire'],
        'CRITICAL' => ['critChance', 'critMultiplier', 'critTap'],
        'DEFENSE' => ['defense', 'defenseMultiplier', 'penetrationResist'],
        'BOOSTS' => ['xpBoost', 'doubleLevel']
    ];
}