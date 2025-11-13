CREATE TABLE waitlist_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE waitlist_users
ADD COLUMN country VARCHAR(100) NULL DEFAULT NULL
AFTER email;
ALTER TABLE waitlist_users ADD COLUMN password VARCHAR(255) NULL AFTER country;

-- Add referral system columns
ALTER TABLE waitlist_users
ADD COLUMN referral_code VARCHAR(20) UNIQUE AFTER password;
ALTER TABLE waitlist_users
ADD COLUMN parent_user_id INT NULL AFTER referral_code;
ALTER TABLE waitlist_users
ADD COLUMN credits INT DEFAULT 0 AFTER parent_user_id;

-- Create index for performance
CREATE INDEX idx_referral_code ON waitlist_users(referral_code);
CREATE INDEX idx_parent_user ON waitlist_users(parent_user_id);