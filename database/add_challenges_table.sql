-- Create 'challenges' table to track user challenges (each challenge has Phase 1 & Phase 2)
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