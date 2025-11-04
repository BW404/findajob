-- Migration: add `religion` column to job_seeker_profiles if it doesn't exist
-- Safe to run multiple times. Uses INFORMATION_SCHEMA to check for existing column.

SET @schema_name = DATABASE();
SET @table_name = 'job_seeker_profiles';
SET @column_name = 'religion';

SELECT
    COUNT(*) INTO @col_exists
FROM
    INFORMATION_SCHEMA.COLUMNS
WHERE
    TABLE_SCHEMA = @schema_name
    AND TABLE_NAME = @table_name
    AND COLUMN_NAME = @column_name;

-- If column does not exist, add it
SET @sql = IF(@col_exists = 0,
    CONCAT('ALTER TABLE `', @table_name, '` ADD COLUMN `', @column_name, '` VARCHAR(64) NULL DEFAULT NULL COMMENT "Religion from NIN verification";'),
    'SELECT "column already exists";'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Done.
