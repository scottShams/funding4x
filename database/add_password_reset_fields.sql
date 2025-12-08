-- Add password reset fields to waitlist_users table
ALTER TABLE waitlist_users
ADD COLUMN password_reset_token VARCHAR(255) NULL,
ADD COLUMN password_reset_expires DATETIME NULL;

-- Add index for faster lookups
CREATE INDEX idx_password_reset_token ON waitlist_users(password_reset_token);