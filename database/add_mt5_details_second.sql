CREATE TABLE mt5_details_second (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Link to user (for easy lookup)
    user_id INT NOT NULL,

    -- One-to-one relation with mt5_details
    mt5_details_id INT NOT NULL UNIQUE,

    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    server VARCHAR(255) NOT NULL,
    instrument VARCHAR(255) NULL,

    status ENUM('pending', 'under_review', 'pass', 'running', 'fail')
        DEFAULT 'pending',

    fail_reason TEXT NULL,
    pass_reason TEXT NULL,

    -- Test Type
    test_type ENUM('50:50 F4x', '20:80 F4x', '10:90 F4x', '80:20 F4x', '70:30 F4x', '60:40 F4x')
        DEFAULT '50:50 F4x',

    status_updated_at TIMESTAMP NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES waitlist_users(id) ON DELETE CASCADE,
    FOREIGN KEY (mt5_details_id) REFERENCES mt5_details(id) ON DELETE CASCADE
);
