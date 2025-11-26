ALTER TABLE waitlist_users
ADD COLUMN knowledge_test_result JSON NULL AFTER quiz_result;