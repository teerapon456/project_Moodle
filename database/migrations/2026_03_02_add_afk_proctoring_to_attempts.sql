-- Migration to add AFK proctoring columns
ALTER TABLE iga_user_test_attempts 
ADD COLUMN afk_count INT DEFAULT 0,
ADD COLUMN submission_status VARCHAR(50) DEFAULT 'normal',
ADD COLUMN proctoring_notes TEXT;
