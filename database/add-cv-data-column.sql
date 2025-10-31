-- Add cv_data column to cvs table for storing structured CV data
-- Date: October 29, 2025

-- Add cv_data column (TEXT type to store JSON data)
ALTER TABLE cvs 
ADD COLUMN cv_data TEXT NULL AFTER content;

-- Update existing records to copy content to cv_data if needed
UPDATE cvs SET cv_data = content WHERE content IS NOT NULL AND cv_data IS NULL;

-- Optionally add index for better performance
-- ALTER TABLE cvs ADD INDEX idx_cv_data ((CAST(cv_data AS CHAR(255))));
