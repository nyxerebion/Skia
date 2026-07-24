CREATE TABLE whack_scores (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    score INT DEFAULT 0,
    points INT DEFAULT 0,
    total_points INT DEFAULT 0,
    time_played TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user (user_id),
    INDEX idx_user_score (score),
    INDEX idx_user_total_points (total_points),
    INDEX idx_time_played (time_played),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index for fast leaderboard queries
CREATE INDEX idx_user_score ON whack_scores(user_id, score);
CREATE INDEX idx_user_total_points ON whack_scores(user_id, total_points);
CREATE INDEX idx_time_played ON whack_scores(time_played);

-- ALTER TABLE whack_scores ADD COLUMN points INT DEFAULT 0 AFTER score;

-- ALTER TABLE whack_scores ADD COLUMN total_points INT DEFAULT 0 AFTER points;

-- ALTER TABLE whack_scores ADD COLUMN time_played INT DEFAULT 0 AFTER total_points;

ALTER TABLE whack_scores DROP COLUMN played_at;

ALTER TABLE whack_scores 
ADD COLUMN time_played INT DEFAULT 0 AFTER total_points;