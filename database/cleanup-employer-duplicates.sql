-- =====================================================
-- Cleanup Employer Profiles Table - Remove Duplicates
-- =====================================================
-- Date: 2025-11-08
-- Purpose: Remove duplicate and unnecessary columns from employer_profiles
-- 
-- DUPLICATES TO REMOVE:
-- 1. Company NIN fields (companies don't have NIns - only individuals)
-- 2. Duplicate logo field
-- 3. Duplicate registration number field
-- 4. Generic is_verified field (replaced by specific verification fields)
-- =====================================================

USE findajob_ng;

-- =====================================================
-- STEP 1: Migrate data before deletion
-- =====================================================

-- Migrate logo data: Use company_logo if exists, otherwise use logo
UPDATE employer_profiles 
SET company_logo = logo 
WHERE company_logo IS NULL AND logo IS NOT NULL;

-- Migrate CAC number: Use company_cac_number if exists, otherwise use company_registration_number
UPDATE employer_profiles 
SET company_cac_number = company_registration_number 
WHERE company_cac_number IS NULL AND company_registration_number IS NOT NULL;

-- =====================================================
-- STEP 2: Drop unnecessary columns
-- =====================================================

-- Drop company NIN fields (companies don't have NIns)
ALTER TABLE employer_profiles 
DROP COLUMN IF EXISTS nin,
DROP COLUMN IF EXISTS nin_verified,
DROP COLUMN IF EXISTS nin_verified_at,
DROP COLUMN IF EXISTS nin_verification_data,
DROP COLUMN IF EXISTS nin_photo;

-- Drop duplicate logo field (keep company_logo)
ALTER TABLE employer_profiles 
DROP COLUMN IF EXISTS logo;

-- Drop duplicate registration number field (keep company_cac_number)
ALTER TABLE employer_profiles 
DROP COLUMN IF EXISTS company_registration_number;

-- Drop generic is_verified field (use company_cac_verified and provider_nin_verified instead)
ALTER TABLE employer_profiles 
DROP COLUMN IF EXISTS is_verified;

-- =====================================================
-- VERIFICATION
-- =====================================================

-- Show remaining columns
DESCRIBE employer_profiles;

-- Show count of records (should be unchanged)
SELECT COUNT(*) as total_records FROM employer_profiles;

-- =====================================================
-- ROLLBACK INSTRUCTIONS (if needed)
-- =====================================================
-- To restore these columns, you would need to:
-- 1. ALTER TABLE employer_profiles ADD COLUMN [column_name] [type];
-- 2. Restore data from backup
-- 
-- Make sure you have a backup before running this script!
-- =====================================================
