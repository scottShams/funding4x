-- ============================
-- Table: waitlist_users
-- ============================

CREATE TABLE waitlist_users (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Basic user info
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    country VARCHAR(100) DEFAULT NULL,
    password VARCHAR(255) DEFAULT NULL,

    -- Meta
    user_ip VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Referral system
    referral_code VARCHAR(20) UNIQUE DEFAULT NULL,
    parent_user_id INT DEFAULT NULL,
    credits INT DEFAULT 0,

    -- Email verification
    email_verified TINYINT(1) DEFAULT 0,
    verification_token VARCHAR(255) DEFAULT NULL,
    verification_token_expires TIMESTAMP NULL,
    referral_dashboard_mail_sent TINYINT(1) DEFAULT 0,

    -- User status
    status ENUM('active', 'inactive') DEFAULT 'active',

    -- Test & quiz data
    quiz_result JSON DEFAULT NULL,
    knowledge_test_result JSON DEFAULT NULL,

    -- Credit system
    user_credit INT DEFAULT 0,
    manual_credit_update TINYINT(1) DEFAULT 0,
    credit_updated_at TIMESTAMP NULL,

    -- Payment flags
    paid_user TINYINT(1) DEFAULT 0,
    discount_taken TINYINT(1) DEFAULT 0
);

-- ============================
-- Indexes
-- ============================

CREATE INDEX idx_referral_code ON waitlist_users(referral_code);
CREATE INDEX idx_parent_user ON waitlist_users(parent_user_id);
CREATE INDEX idx_verification_token ON waitlist_users(verification_token);
