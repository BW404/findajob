-- =====================================================
-- Phone Verification System
-- =====================================================
-- Date: 2025-11-09
-- Purpose: Add phone verification support for job seekers and employers
-- =====================================================

USE findajob_ng;

-- =====================================================
-- Add phone verification fields to users table
-- =====================================================

ALTER TABLE users 
ADD COLUMN IF NOT EXISTS phone_verified TINYINT(1) DEFAULT 0 COMMENT 'Phone number verified status',
ADD COLUMN IF NOT EXISTS phone_verified_at DATETIME NULL COMMENT 'When phone was verified',
ADD INDEX idx_phone_verified (phone_verified);

-- =====================================================
-- Add phone verification fields to job_seeker_profiles
-- =====================================================

ALTER TABLE job_seeker_profiles 
ADD COLUMN IF NOT EXISTS phone_verified TINYINT(1) DEFAULT 0 COMMENT 'Phone number verified status',
ADD COLUMN IF NOT EXISTS phone_verified_at DATETIME NULL COMMENT 'When phone was verified';

-- =====================================================
-- Add phone verification fields to employer_profiles
-- =====================================================

ALTER TABLE employer_profiles 
ADD COLUMN IF NOT EXISTS provider_phone_verified TINYINT(1) DEFAULT 0 COMMENT 'Provider phone number verified status',
ADD COLUMN IF NOT EXISTS provider_phone_verified_at DATETIME NULL COMMENT 'When provider phone was verified',
ADD INDEX idx_provider_phone_verified (provider_phone_verified);

-- =====================================================
-- Create phone verification attempts table
-- =====================================================

CREATE TABLE IF NOT EXISTS phone_verification_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    phone_number VARCHAR(20) NOT NULL COMMENT 'Phone number in international format (234xxx)',
    reference_id VARCHAR(100) NOT NULL COMMENT 'Dojah OTP reference ID',
    verified TINYINT(1) DEFAULT 0 COMMENT 'Whether OTP was successfully verified',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When OTP was sent',
    expires_at TIMESTAMP NULL COMMENT 'When OTP expires',
    verified_at TIMESTAMP NULL COMMENT 'When OTP was verified',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_phone_number (phone_number),
    INDEX idx_reference_id (reference_id),
    INDEX idx_verified (verified),
    UNIQUE KEY unique_user_phone (user_id, phone_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tracks phone verification OTP attempts via Dojah API';

-- =====================================================
-- Verification
-- =====================================================

SELECT 
    'users' as table_name,
    COUNT(*) as total_records,
    SUM(phone_verified) as phone_verified_count
FROM users
UNION ALL
SELECT 
    'job_seeker_profiles',
    COUNT(*),
    SUM(phone_verified)
FROM job_seeker_profiles
UNION ALL
SELECT 
    'employer_profiles',
    COUNT(*),
    SUM(provider_phone_verified)
FROM employer_profiles;

-- Show phone verification attempts table structure
DESCRIBE phone_verification_attempts;

-- =====================================================
-- NOTES
-- =====================================================
-- 1. Phone numbers are stored in international format: 234xxxxxxxxx
-- 2. OTP expiry is configurable in constants.php (default: 10 minutes)
-- 3. Reference IDs are provided by Dojah API for OTP validation
-- 4. Job seekers use phone_verified, employers use provider_phone_verified
-- =====================================================
