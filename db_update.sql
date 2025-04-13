-- Add new fields to users table for better identification
ALTER TABLE `users` 
ADD COLUMN `unique_id` VARCHAR(64) NULL AFTER `password`,
ADD COLUMN `email` VARCHAR(100) NULL AFTER `unique_id`,
ADD COLUMN `last_ip` VARCHAR(45) NULL AFTER `email`,
ADD COLUMN `user_agent` TEXT NULL AFTER `last_ip`,
ADD COLUMN `last_login` DATETIME NULL AFTER `user_agent`;

-- Update existing users to have a unique ID
UPDATE `users` SET 
  `unique_id` = MD5(CONCAT(`id`, `baptism_name`, NOW(), RAND())) 
WHERE `unique_id` IS NULL;

-- Add index for faster lookups
ALTER TABLE `users` 
ADD UNIQUE INDEX `unique_id_UNIQUE` (`unique_id` ASC);

-- Add fields to admins table
ALTER TABLE `admins` 
ADD COLUMN `last_ip` VARCHAR(45) NULL AFTER `password`,
ADD COLUMN `last_login` DATETIME NULL AFTER `last_ip`;

-- Add device fingerprint to user_sessions table
ALTER TABLE `user_sessions` 
ADD COLUMN `fingerprint` VARCHAR(128) NULL AFTER `device_info`; 