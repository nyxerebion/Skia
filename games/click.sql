CREATE TABLE click_data (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    
    -- Player Stats
    clicks INT UNSIGNED DEFAULT 0,
    total_clicks INT UNSIGNED DEFAULT 0,
    damage INT UNSIGNED DEFAULT 2,
    click_power INT UNSIGNED DEFAULT 10,
    defense INT UNSIGNED DEFAULT 0,
    health INT UNSIGNED DEFAULT 100,
    max_health INT UNSIGNED DEFAULT 100,
    critical_chance INT UNSIGNED DEFAULT 0,
    critical_multiplier DECIMAL(3,1) DEFAULT 1.5,
    coins INT UNSIGNED DEFAULT 0,
    total_coins INT UNSIGNED DEFAULT 0,
    items_json JSON DEFAULT NULL,
    shop_upgrades JSON DEFAULT NULL,
    level INT UNSIGNED DEFAULT 1,
    experience INT UNSIGNED DEFAULT 0,
    kills INT UNSIGNED DEFAULT 0,
    deaths INT UNSIGNED DEFAULT 0,
    
    -- Enemy Stats
    current_enemy VARCHAR(50) DEFAULT 'Rowan',
    current_enemy_level INT UNSIGNED DEFAULT 1,
    enemy_health INT UNSIGNED DEFAULT 100,
    enemy_max_health INT UNSIGNED DEFAULT 100,
    enemy_damage INT UNSIGNED DEFAULT 2,
    enemy_defense INT UNSIGNED DEFAULT 0,
    enemy_reward INT UNSIGNED DEFAULT 0,
    enemy_crit_chance INT UNSIGNED DEFAULT 0,
    enemy_crit_multiplier DECIMAL(3,1) DEFAULT 1.5,
    enemy_xp INT UNSIGNED DEFAULT 10,
    
    -- Timestamps
    time_played INT DEFAULT 0,
    last_played TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    
    -- Constraints
    UNIQUE KEY unique_user (user_id),
    INDEX idx_total_clicks (total_clicks),
    INDEX idx_total_coins (total_coins),
    INDEX idx_level (level),
    INDEX idx_last_played (last_played),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ALTER TABLE click_data ADD COLUMN enemy_defense INT UNSIGNED DEFAULT 0 AFTER enemy_damage;

-- ALTER TABLE click_data ADD time_played INT DEFAULT 0 AFTER enemy_xp;

-- ALTER TABLE click_data ADD COLUMN shop_upgrades JSON DEFAULT NULL AFTER items_json;

ALTER TABLE click_data ADD COLUMN highest_damage INT UNSIGNED DEFAULT 0 AFTER deaths;
ALTER TABLE click_data ADD COLUMN highest_crit INT UNSIGNED DEFAULT 0 AFTER highest_damage;