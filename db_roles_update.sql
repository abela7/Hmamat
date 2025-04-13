-- Add role field to users table
ALTER TABLE `users` 
ADD COLUMN `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user' AFTER `baptism_name`;

-- Add role field to admins table
ALTER TABLE `admins` 
ADD COLUMN `role` ENUM('user', 'admin') NOT NULL DEFAULT 'admin' AFTER `username`;

-- Update existing records
UPDATE `users` SET `role` = 'user';
UPDATE `admins` SET `role` = 'admin';

-- Create index for faster role-based queries
ALTER TABLE `users` 
ADD INDEX `idx_role` (`role`);

ALTER TABLE `admins` 
ADD INDEX `idx_role` (`role`); 