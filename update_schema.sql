ALTER TABLE user_logins
ADD COLUMN latitude DECIMAL(10, 8) NULL AFTER client_version,
ADD COLUMN longitude DECIMAL(11, 8) NULL AFTER latitude;
