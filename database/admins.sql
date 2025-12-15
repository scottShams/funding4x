-- ============================
-- Table: admins
-- ============================

CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Basic info
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,

    -- Roles & hierarchy
    role VARCHAR(50) NOT NULL DEFAULT 'admin',
    created_by INT DEFAULT NULL,

    -- Email verification
    email_verified TINYINT(1) DEFAULT 0,
    verification_token VARCHAR(255) DEFAULT NULL,
    verification_token_expires TIMESTAMP NULL,

    -- Password reset
    password_reset_token VARCHAR(255) DEFAULT NULL,
    password_reset_expires TIMESTAMP NULL,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Self-reference (creator admin)
    CONSTRAINT fk_admins_created_by
        FOREIGN KEY (created_by)
        REFERENCES admins(id)
        ON DELETE SET NULL
);

-- ============================
-- Indexes
-- ============================

CREATE INDEX idx_admins_email ON admins(email);
CREATE INDEX idx_admins_verification_token ON admins(verification_token);
CREATE INDEX idx_admins_password_reset_token ON admins(password_reset_token);
CREATE INDEX idx_admins_created_by ON admins(created_by);
CREATE INDEX idx_admins_role ON admins(role);