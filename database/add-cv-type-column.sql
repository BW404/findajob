-- Add cv_type column to cvs table if it doesn't exist
-- This column tracks whether a CV was uploaded or generated

ALTER TABLE `cvs` 
ADD COLUMN IF NOT EXISTS `cv_type` ENUM('uploaded', 'generated') DEFAULT 'uploaded' AFTER `file_path`;

-- Update existing records (assume all existing CVs are uploaded)
UPDATE `cvs` SET `cv_type` = 'uploaded' WHERE `cv_type` IS NULL;

SELECT 'cv_type column added successfully!' as message;
