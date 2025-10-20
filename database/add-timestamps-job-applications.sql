-- Add missing timestamp columns to job_applications table
-- These columns help track when applications were created and last updated

ALTER TABLE `job_applications` 
ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `responded_at`,
ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- Update existing records to have proper timestamps (using applied_at as base)
UPDATE `job_applications` 
SET `created_at` = `applied_at`, 
    `updated_at` = COALESCE(`responded_at`, `applied_at`);
