-- Add subscription and booster columns to users and profiles tables
-- Created: 2025-11-28

-- Add subscription fields to users table
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS subscription_status ENUM('free', 'active', 'expired', 'cancelled') DEFAULT 'free' AFTER email_verified,
ADD COLUMN IF NOT EXISTS subscription_plan ENUM('basic', 'pro') DEFAULT 'basic' AFTER subscription_status,
ADD COLUMN IF NOT EXISTS subscription_type ENUM('monthly', 'yearly') DEFAULT NULL AFTER subscription_plan,
ADD COLUMN IF NOT EXISTS subscription_start TIMESTAMP NULL AFTER subscription_type,
ADD COLUMN IF NOT EXISTS subscription_end TIMESTAMP NULL AFTER subscription_start;

-- Add booster fields to job_seeker_profiles table
ALTER TABLE job_seeker_profiles 
ADD COLUMN IF NOT EXISTS verification_boosted BOOLEAN DEFAULT 0 AFTER verified_at,
ADD COLUMN IF NOT EXISTS verification_boost_date TIMESTAMP NULL AFTER verification_boosted,
ADD COLUMN IF NOT EXISTS profile_boosted BOOLEAN DEFAULT 0 AFTER verification_boost_date,
ADD COLUMN IF NOT EXISTS profile_boost_until TIMESTAMP NULL AFTER profile_boosted;

-- Add booster fields to employer_profiles table
ALTER TABLE employer_profiles 
ADD COLUMN IF NOT EXISTS verification_boosted BOOLEAN DEFAULT 0 AFTER verified_at,
ADD COLUMN IF NOT EXISTS verification_boost_date TIMESTAMP NULL AFTER verification_boosted,
ADD COLUMN IF NOT EXISTS job_boost_credits INT DEFAULT 0 AFTER verification_boost_date;

-- Add booster fields to jobs table
ALTER TABLE jobs 
ADD COLUMN IF NOT EXISTS is_boosted BOOLEAN DEFAULT 0 AFTER is_featured,
ADD COLUMN IF NOT EXISTS boosted_until TIMESTAMP NULL AFTER is_boosted;

-- Add indexes for performance
CREATE INDEX IF NOT EXISTS idx_subscription_status ON users(subscription_status);
CREATE INDEX IF NOT EXISTS idx_subscription_end ON users(subscription_end);
CREATE INDEX IF NOT EXISTS idx_profile_boosted ON job_seeker_profiles(profile_boosted, profile_boost_until);
CREATE INDEX IF NOT EXISTS idx_job_boosted ON jobs(is_boosted, boosted_until);

-- Verify the changes
SELECT 'Subscription and booster fields added successfully!' as status;

-- Show updated structure
DESCRIBE users;
