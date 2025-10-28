-- Enhance job_applications table for Easy Apply feature
-- Add fields for full name, email, phone, and application message

ALTER TABLE `job_applications`
ADD COLUMN `applicant_name` VARCHAR(255) AFTER `job_seeker_id`,
ADD COLUMN `applicant_email` VARCHAR(255) AFTER `applicant_name`,
ADD COLUMN `applicant_phone` VARCHAR(20) AFTER `applicant_email`,
ADD COLUMN `application_message` TEXT AFTER `cover_letter`,
ADD COLUMN `resume_file_path` VARCHAR(500) AFTER `cv_id`;

-- Add index for email lookup
ALTER TABLE `job_applications`
ADD INDEX `idx_applicant_email` (`applicant_email`);

-- Add comments for clarity
ALTER TABLE `job_applications`
MODIFY COLUMN `applicant_name` VARCHAR(255) COMMENT 'Full name from user profile or manual entry',
MODIFY COLUMN `applicant_email` VARCHAR(255) COMMENT 'Email from user profile or manual entry',
MODIFY COLUMN `applicant_phone` VARCHAR(20) COMMENT 'Phone number from user profile',
MODIFY COLUMN `application_message` TEXT COMMENT 'Message/cover letter from applicant',
MODIFY COLUMN `resume_file_path` VARCHAR(500) COMMENT 'Path to uploaded CV/resume file';
