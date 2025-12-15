-- ============================
-- Table: mt5_details
-- ============================

CREATE TABLE mt5_details (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Relation
    user_id INT NOT NULL,

    -- MT5 account details
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    server VARCHAR(255) NOT NULL,
    instrument VARCHAR(255) DEFAULT NULL,

    -- Review & status
    status ENUM('pending', 'under_review', 'pass', 'running', 'fail')
        DEFAULT 'pending',
    fail_reason TEXT DEFAULT NULL,
    pass_reason TEXT DEFAULT NULL,

    -- Timestamps
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status_updated_at TIMESTAMP NULL,

    -- Foreign key
    FOREIGN KEY (user_id) REFERENCES waitlist_users(id)
);
