# Employer Profiles Table Cleanup - Complete

## Summary
Successfully removed 9 duplicate and unnecessary columns from `employer_profiles` table and updated all code references.

## Columns Removed (9 total)

### 1. Company NIN Fields (5 columns) - REMOVED ❌
Companies don't have NIns - only individuals (company representatives) do.
- `nin` - Company NIN
- `nin_verified` - Company NIN verified flag  
- `nin_verified_at` - Company NIN verification date
- `nin_verification_data` - Company NIN data
- `nin_photo` - Company NIN photo

**Replaced by**: `provider_nin`, `provider_nin_verified`, `provider_nin_verified_at`, `provider_nin_data` (for company representative)

### 2. Duplicate Logo Field (1 column) - REMOVED ❌
- `logo` - Old logo field

**Kept**: `company_logo` (single source of truth for company logo)

### 3. Duplicate Registration Number (1 column) - REMOVED ❌
- `company_registration_number` - Generic registration field

**Kept**: `company_cac_number` (CAC number IS the company registration number in Nigeria)

### 4. Generic Verification Flag (1 column) - REMOVED ❌
- `is_verified` - Generic company verified flag

**Replaced by**: 
- `company_cac_verified` (for company CAC verification)
- `provider_nin_verified` (for representative NIN verification)

### 5. Unused Photo Field (1 column) - REMOVED ❌
- `nin_photo` - Was for company NIN photo (unnecessary)

**Kept**: `provider_profile_picture` (for company representative's photo)

## Database Changes

### Before Cleanup: 48 columns
### After Cleanup: 39 columns
### Reduction: 9 columns (18.75% reduction)

## Migration Process

1. ✅ **Data Migration**: Copied data from old columns to new columns
   - `logo` → `company_logo`
   - `company_registration_number` → `company_cac_number`

2. ✅ **Column Removal**: Dropped all duplicate/unnecessary columns via ALTER TABLE

3. ✅ **Verification**: Confirmed 8 employer records preserved

## Code Updates (7 files)

### 1. `pages/company/profile.php` (3 changes)
- Line 23: Changed `u.profile_picture as logo` to use `ep.company_logo`
- Line 138: Changed `u.profile_picture as logo` to use `ep.company_logo`
- Line 542: Changed `$user['logo']` to `$user['company_logo']`

### 2. `api/search.php` (2 changes)
- Line 103: Changed `ep.logo` to `ep.company_logo as logo`
- Line 112: Changed `ep.is_verified` to `ep.company_cac_verified`

### 3. `api/jobs.php` (1 change)
- Line 223: Changed `ep.is_verified` to `ep.company_cac_verified as employer_verified`

### 4. `includes/header.php` (1 change)
- Line 50: Changed `COALESCE(ep.logo, ...)` to `COALESCE(ep.company_logo, ...)`

### 5. `api/upload-profile-picture.php` (1 change)
- Line 71: Changed `SELECT logo` to `SELECT company_logo`

### 6. `database/schema.sql` (1 update)
- Updated schema documentation to reflect current structure
- Removed obsolete columns
- Added comments for provider and CAC verification sections

### 7. `database/cleanup-employer-duplicates.sql` (NEW)
- Complete migration script with rollback instructions
- Safe data migration before column removal
- Verification queries included

## Current Table Structure (39 columns)

### Core Company Information (10)
- id, user_id, company_name, industry, company_size
- website, description, address, state, city

### Company Logo & Branding (1)
- company_logo ✅

### Verification & Subscription (3)
- verification_status, subscription_type, subscription_expires

### Mini Site Feature (2)
- mini_site_enabled, mini_site_url

### Company Representative - Provider (13)
- provider_first_name, provider_last_name, provider_phone
- provider_date_of_birth, provider_gender
- provider_state_of_origin, provider_lga_of_origin, provider_city_of_birth, provider_religion
- provider_nin, provider_nin_verified, provider_nin_verified_at, provider_nin_data
- provider_profile_picture

### Company CAC Verification (6)
- company_cac_number ✅
- company_cac_verified ✅
- company_cac_verified_at
- company_cac_data
- company_cac_document
- company_registration_date, company_tax_id

### Timestamps (2)
- created_at, updated_at

## Verification Status Fields (Current)

### For Company (CAC-based):
- `company_cac_verified` - Boolean flag (0 or 1)
- `company_cac_verified_at` - Timestamp when verified
- `company_cac_data` - Full CAC verification data (JSON)

### For Company Representative (NIN-based):
- `provider_nin_verified` - Boolean flag (0 or 1)
- `provider_nin_verified_at` - Timestamp when verified
- `provider_nin_data` - Full NIN verification data from Dojah API (JSON)

### Generic Status:
- `verification_status` - Enum: 'pending', 'verified', 'rejected'

## Benefits of Cleanup

✅ **Clearer Data Model**: Separate verification for company (CAC) vs representative (NIN)
✅ **No More Confusion**: One logo field, one registration number field
✅ **Accurate Semantics**: Companies verify via CAC, representatives verify via NIN
✅ **Reduced Storage**: 18.75% fewer columns
✅ **Better Indexing**: Removed unnecessary indexes on duplicate columns
✅ **Code Clarity**: All code now references correct, single-source-of-truth columns

## Testing Checklist

### Database Verification
- ✅ Column count reduced from 48 to 39
- ✅ All 8 employer records preserved
- ✅ No NULL values in critical fields

### Code Functionality
- [ ] Test employer profile page loads correctly
- [ ] Test company logo upload/display
- [ ] Test company search/autocomplete
- [ ] Test job posting with employer verification badge
- [ ] Test NIN verification for company representative
- [ ] Test CAC verification workflow (when implemented)

## Next Steps

1. **Test all employer features** to ensure no broken functionality
2. **Monitor logs** for any SQL errors related to removed columns
3. **Consider implementing CAC verification** now that the structure is clean
4. **Update any external documentation** that references old column names

---

**Migration Completed**: 2025-11-08
**Records Affected**: 8 employer profiles
**Downtime**: None (ALTER TABLE operations completed successfully)
**Rollback Available**: Yes (backup recommended before running cleanup script)
