-- Add application type fields to jobs table
-- This allows employers to choose between Easy Apply and Manual Apply options

ALTER TABLE `jobs` 
ADD COLUMN `application_type` ENUM('easy', 'manual', 'both') DEFAULT 'easy' AFTER `application_deadline`,
ADD COLUMN `application_instructions` TEXT AFTER `application_url`;

-- Update existing jobs to use Easy Apply by default
UPDATE `jobs` SET `application_type` = 'easy' WHERE `application_type` IS NULL;

-- Add comments for clarity
ALTER TABLE `jobs` 
MODIFY COLUMN `application_type` ENUM('easy', 'manual', 'both') DEFAULT 'easy' 
COMMENT 'Easy Apply: Applications via platform | Manual Apply: External email/website | Both: Allow both methods',

MODIFY COLUMN `application_email` VARCHAR(255) 
COMMENT 'Email address for manual applications (optional)',

MODIFY COLUMN `application_url` VARCHAR(500) 
COMMENT 'External application URL for manual applications (optional)',

MODIFY COLUMN `application_instructions` TEXT 
COMMENT 'Additional instructions for manual applications (optional)';
