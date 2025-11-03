-- Add NIN verification fields to job_seeker_profiles table
-- For integration with Dojah NIN verification API

USE `findajob_ng`;

-- Add NIN and verification columns (with error handling for existing columns)
-- Add nin_verified column
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'findajob_ng' 
     AND TABLE_NAME = 'job_seeker_profiles' 
     AND COLUMN_NAME = 'nin_verified') > 0,
    'SELECT 1',
    'ALTER TABLE `job_seeker_profiles` ADD COLUMN `nin_verified` TINYINT(1) DEFAULT 0 COMMENT ''Whether NIN has been verified'''
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add nin_verified_at column
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'findajob_ng' 
     AND TABLE_NAME = 'job_seeker_profiles' 
     AND COLUMN_NAME = 'nin_verified_at') > 0,
    'SELECT 1',
    'ALTER TABLE `job_seeker_profiles` ADD COLUMN `nin_verified_at` TIMESTAMP NULL DEFAULT NULL COMMENT ''When NIN was verified'''
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add nin_verification_data column
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'findajob_ng' 
     AND TABLE_NAME = 'job_seeker_profiles' 
     AND COLUMN_NAME = 'nin_verification_data') > 0,
    'SELECT 1',
    'ALTER TABLE `job_seeker_profiles` ADD COLUMN `nin_verification_data` JSON DEFAULT NULL COMMENT ''JSON data from Dojah API verification'''
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index on nin (if not exists)
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = 'findajob_ng' 
     AND TABLE_NAME = 'job_seeker_profiles' 
     AND INDEX_NAME = 'idx_nin') > 0,
    'SELECT 1',
    'ALTER TABLE `job_seeker_profiles` ADD INDEX `idx_nin` (`nin`)'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index on nin_verified (if not exists)
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = 'findajob_ng' 
     AND TABLE_NAME = 'job_seeker_profiles' 
     AND INDEX_NAME = 'idx_nin_verified') > 0,
    'SELECT 1',
    'ALTER TABLE `job_seeker_profiles` ADD INDEX `idx_nin_verified` (`nin_verified`)'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update existing verification_status to support NIN verification
ALTER TABLE `job_seeker_profiles`
MODIFY COLUMN `verification_status` ENUM('pending','nin_verified','fully_verified','rejected') DEFAULT 'pending' COMMENT 'User verification status';

-- Create verification transactions table for tracking NIN verification payments
CREATE TABLE IF NOT EXISTS `verification_transactions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `transaction_type` ENUM('nin_verification','bvn_verification','profile_verification') NOT NULL DEFAULT 'nin_verification',
  `amount` DECIMAL(10,2) NOT NULL,
  `currency` VARCHAR(3) DEFAULT 'NGN',
  `status` ENUM('pending','completed','failed','refunded') DEFAULT 'pending',
  `reference` VARCHAR(100) NOT NULL COMMENT 'Unique transaction reference',
  `payment_method` VARCHAR(50) DEFAULT NULL,
  `metadata` JSON DEFAULT NULL COMMENT 'Additional transaction data',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference` (`reference`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `transaction_type` (`transaction_type`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create verification audit log for tracking all verification attempts
CREATE TABLE IF NOT EXISTS `verification_audit_log` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `verification_type` ENUM('nin','bvn','other') NOT NULL,
  `nin_number` VARCHAR(11) DEFAULT NULL,
  `status` ENUM('initiated','success','failed','error') NOT NULL,
  `api_provider` VARCHAR(50) DEFAULT 'dojah' COMMENT 'Verification service provider',
  `api_response` JSON DEFAULT NULL COMMENT 'Full API response',
  `error_message` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `verification_type` (`verification_type`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add comment to table
ALTER TABLE `job_seeker_profiles` 
COMMENT = 'Job seeker profile data with NIN verification support';
