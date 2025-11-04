-- Migration: Add all NIN verification fields to job_seeker_profiles
-- Safely adds: state_of_origin, lga_of_origin, city_of_birth, religion
-- Safe to run multiple times - checks for existing columns before adding

SET @schema_name = DATABASE();
SET @table_name = 'job_seeker_profiles';

-- Add state_of_origin if it doesn't exist
SELECT COUNT(*) INTO @col_exists FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = @table_name AND COLUMN_NAME = 'state_of_origin';

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `job_seeker_profiles` ADD COLUMN `state_of_origin` VARCHAR(100) NULL DEFAULT NULL COMMENT "State of origin from NIN";',
    'SELECT "state_of_origin already exists" as status;'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add lga_of_origin if it doesn't exist
SELECT COUNT(*) INTO @col_exists FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = @table_name AND COLUMN_NAME = 'lga_of_origin';

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `job_seeker_profiles` ADD COLUMN `lga_of_origin` VARCHAR(100) NULL DEFAULT NULL COMMENT "LGA of origin from NIN";',
    'SELECT "lga_of_origin already exists" as status;'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add city_of_birth if it doesn't exist
SELECT COUNT(*) INTO @col_exists FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = @table_name AND COLUMN_NAME = 'city_of_birth';

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `job_seeker_profiles` ADD COLUMN `city_of_birth` VARCHAR(100) NULL DEFAULT NULL COMMENT "City/LGA of birth from NIN";',
    'SELECT "city_of_birth already exists" as status;'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add religion if it doesn't exist
SELECT COUNT(*) INTO @col_exists FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = @table_name AND COLUMN_NAME = 'religion';

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `job_seeker_profiles` ADD COLUMN `religion` VARCHAR(64) NULL DEFAULT NULL COMMENT "Religion from NIN verification";',
    'SELECT "religion already exists" as status;'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Done
SELECT 'NIN fields migration completed successfully!' as result;
