CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email_verified TINYINT(1) DEFAULT 0,
    verification_token VARCHAR(255) NULL,
    verification_token_expires TIMESTAMP NULL,
    password_reset_token VARCHAR(255) NULL,
    password_reset_expires TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create indexes for better performance
CREATE INDEX idx_admins_email ON admins(email);
CREATE INDEX idx_admins_verification_token ON admins(verification_token);
CREATE INDEX idx_admins_password_reset_token ON admins(password_reset_token);

-- Migration: add role and created_by columns to admins table
ALTER TABLE admins
    ADD COLUMN `role` VARCHAR(50) NOT NULL DEFAULT 'admin',
    ADD COLUMN `created_by` INT NULL;

-- Index for created_by for convenience
CREATE INDEX idx_admins_created_by ON admins(created_by);

-- Optional: add foreign key if desired (commented out by default)
ALTER TABLE admins
  ADD CONSTRAINT fk_admins_created_by FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL;