CREATE TABLE mt5_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    server VARCHAR(255) NOT NULL,
    status ENUM('pending', 'pass', 'running', 'fail') DEFAULT 'pending',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES waitlist_users(id)
);

ALTER TABLE mt5_details
ADD COLUMN instrument VARCHAR(255) NULL AFTER server;

ALTER TABLE mt5_details
ADD COLUMN fail_reason TEXT NULL AFTER status;

ALTER TABLE mt5_details
ADD COLUMN pass_reason TEXT NULL AFTER fail_reason;

ALTER TABLE mt5_details
ADD COLUMN status_updated_at TIMESTAMP NULL AFTER pass_reason;