-- Database Migration: Add Referral System Support
-- This script should be run on the existing 'funding4x' database

-- Step 1: Add referral system columns
ALTER TABLE waitlist_users 
ADD COLUMN referral_code VARCHAR(20) UNIQUE AFTER password;

ALTER TABLE waitlist_users 
ADD COLUMN parent_user_id INT NULL AFTER referral_code;

ALTER TABLE waitlist_users 
ADD COLUMN credits INT DEFAULT 0 AFTER parent_user_id;

-- Step 2: Create indexes for performance
CREATE INDEX idx_referral_code ON waitlist_users(referral_code);
CREATE INDEX idx_parent_user ON waitlist_users(parent_user_id);

-- Step 3: Generate referral codes for existing users (optional)
-- This will generate unique referral codes for users who don't have one yet
UPDATE waitlist_users 
SET referral_code = CONCAT('REF', UPPER(SUBSTR(MD5(CONCAT(id, name, email)), 1, 6)))
WHERE referral_code IS NULL OR referral_code = '';

-- Note: This update generates referral codes based on existing data
-- Users can manually generate new codes through the application if needed