-- Migration: add challenges table and link mt5 tables to challenges
-- Run this migration once against your DB (example using mysql client)

CREATE TABLE IF NOT EXISTS challenges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    challenge_number INT NOT NULL,
    challenge_name VARCHAR(255) DEFAULT NULL,
    status ENUM('pending','active','completed') NOT NULL DEFAULT 'pending',
    metadata JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES waitlist_users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_user_challenge_number (user_id, challenge_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Make mt5_details columns nullable and add challenge_id

ALTER TABLE mt5_details
    ADD COLUMN challenge_id INT NULL AFTER status_updated_at;

ALTER TABLE mt5_details
    MODIFY COLUMN username VARCHAR(255) DEFAULT NULL,
    MODIFY COLUMN password VARCHAR(255) DEFAULT NULL,
    MODIFY COLUMN server VARCHAR(255) DEFAULT NULL;

-- Add FK and unique index for challenge_id
ALTER TABLE mt5_details
    ADD CONSTRAINT fk_mt5_challenge
    FOREIGN KEY (challenge_id)
    REFERENCES challenges(id)
    ON DELETE CASCADE;

CREATE INDEX idx_mt5_challenge ON mt5_details (challenge_id);

-- Make mt5_details_second columns nullable and add challenge_id

ALTER TABLE mt5_details_second
    ADD COLUMN challenge_id INT NULL AFTER submitted_at;

ALTER TABLE mt5_details_second
    MODIFY COLUMN username VARCHAR(255) DEFAULT NULL,
    MODIFY COLUMN password VARCHAR(255) DEFAULT NULL,
    MODIFY COLUMN server VARCHAR(255) DEFAULT NULL;

ALTER TABLE mt5_details_second
    ADD CONSTRAINT fk_mt5_second_challenge
    FOREIGN KEY (challenge_id)
    REFERENCES challenges(id)
    ON DELETE CASCADE;

CREATE INDEX idx_mt5_second_challenge ON mt5_details_second (challenge_id);

-- Note: Some MySQL versions do not support IF NOT EXISTS for ADD COLUMN or ADD CONSTRAINT. Adjust accordingly before running.
