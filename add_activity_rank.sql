-- Add rank column to activities table
ALTER TABLE activities ADD COLUMN `rank` INT DEFAULT 0;

-- Example UPDATE query to set ranks (you'll need to update these values as needed)
UPDATE activities SET `rank` = 1 WHERE id = 1;
UPDATE activities SET `rank` = 2 WHERE id = 2;
UPDATE activities SET `rank` = 3 WHERE id = 3;
UPDATE activities SET `rank` = 4 WHERE id = 4;
UPDATE activities SET `rank` = 5 WHERE id = 5;
UPDATE activities SET `rank` = 6 WHERE id = 6;
UPDATE activities SET `rank` = 7 WHERE id = 7; 