-- Migration to enhance existing CVs table for file upload support
-- Run this if your cvs table doesn't have these columns

USE findajob_ng;

-- Add columns if they don't exist
SET @sql = CONCAT('ALTER TABLE cvs 
    ADD COLUMN IF NOT EXISTS description TEXT AFTER title,
    ADD COLUMN IF NOT EXISTS original_filename VARCHAR(255) AFTER file_name,
    ADD COLUMN IF NOT EXISTS file_type VARCHAR(50) AFTER file_size,
    ADD INDEX IF NOT EXISTS idx_file_type (file_type)
');

-- Execute the migration
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update existing records to have proper file names if needed
UPDATE cvs SET original_filename = file_name WHERE original_filename IS NULL AND file_name IS NOT NULL;

SELECT 'CVs table migration completed successfully!' as status;