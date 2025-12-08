-- Create knowledge test approvals table
CREATE TABLE IF NOT EXISTS knowledge_test_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    approval_status ENUM('pending', 'approved', 'declined') DEFAULT 'pending',
    approved_by INT NULL,
    approved_at DATETIME NULL,
    declined_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign key constraints
    FOREIGN KEY (user_id) REFERENCES waitlist_users(id) ON DELETE CASCADE,

    -- Indexes for performance
    INDEX idx_approval_status (approval_status),
    INDEX idx_approved_by (approved_by)
);

-- Insert default pending status for existing users who have completed knowledge tests
INSERT IGNORE INTO knowledge_test_approvals (user_id, approval_status)
SELECT id, 'pending'
FROM waitlist_users
WHERE knowledge_test_result IS NOT NULL;