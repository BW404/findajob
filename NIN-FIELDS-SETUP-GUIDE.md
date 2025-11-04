# NIN Verification - Complete Implementation Guide

## ✅ What's Implemented

The system now automatically replaces user profile data with verified NIN information for these fields:
- **Name** (First & Last)
- **Date of Birth**
- **State of Origin**
- **LGA of Origin**
- **City/LGA of Birth**
- **Religion**

## 🚀 Setup Instructions

### Step 1: Run Database Migration

This will add all required fields to your `job_seeker_profiles` table if they don't exist.

**PowerShell Command:**
```powershell
cd e:\XAMPP\htdocs\findajob\database
php run-migration.php add-nin-profile-fields.sql
```

**Expected Output:**
```
=================================================
Migration Runner
=================================================
File: add-nin-profile-fields.sql
=================================================
Connected to database: findajob_ng

Executing migration...
--------------------------------------------------
Statement 1 executed successfully.
Statement 2 executed successfully.
Statement 3 executed successfully.
Statement 4 executed successfully.
Statement 5 executed successfully.
--------------------------------------------------
✓ Migration completed successfully!
Total statements executed: 5
=================================================
```

---

### Step 2: Backfill Existing Verified Users (Optional but Recommended)

If you already have users who completed NIN verification before this update, you should backfill their data.

**Access the Admin Backfill Page:**
```
http://localhost/findajob/admin/backfill-nin-data.php
```

**Requirements:**
- You must be logged in as an **Admin**
- The page will automatically process all verified users with stored NIN data

**What It Does:**
- Finds all NIN-verified job seekers
- Applies their stored NIN data to profile fields
- Shows real-time progress
- Displays statistics and any errors

---

## 📋 Fields Mapping

| NIN Field | Database Table | Database Column | Fallback |
|-----------|---------------|-----------------|----------|
| `first_name` | `users` | `first_name` | - |
| `last_name` | `users` | `last_name` | - |
| `date_of_birth` | `job_seeker_profiles` | `date_of_birth` | - |
| `origin_state` | `job_seeker_profiles` | `state_of_origin` | `residence_state` |
| `origin_lga` | `job_seeker_profiles` | `lga_of_origin` | `residence_lga` |
| `birth_lga` / `origin_lga` / `place_of_birth` | `job_seeker_profiles` | `city_of_birth` | Tries all three |
| `religion` | `job_seeker_profiles` | `religion` | - |

---

## 🔄 How It Works

### For New Verifications
When a user completes NIN verification:
1. System calls Dojah API
2. Stores complete NIN response in `nin_verification_data` (JSON)
3. **Automatically applies** the fields listed above to their profile
4. Saves NIN photo as profile picture
5. Locks profile picture from changes

### For Existing Verified Users
Use one of these methods:

#### Method 1: Admin Batch Backfill (Recommended)
- Access `admin/backfill-nin-data.php`
- Processes all verified users automatically
- Shows progress and statistics

#### Method 2: Individual User Script
- User logs in and visits `apply-nin-data.php`
- Applies their own stored NIN data
- Useful for testing or single-user updates

---

## 🛡️ Safety Features

### Column Existence Checks
The system checks if each column exists before updating:
- If `state_of_origin` missing → skips state update
- If `lga_of_origin` missing → skips LGA update
- If `city_of_birth` missing → skips city update
- If `religion` missing → skips religion update

### Transaction Safety
- All updates wrapped in database transactions
- Automatic rollback on errors
- Detailed error logging

### Data Validation
- Checks for empty/null values before updating
- Uses fallback fields when primary field is empty
- Preserves existing data if NIN data is unavailable

---

## 📊 Migration File Details

**File:** `database/add-nin-profile-fields.sql`

**Columns Added (if missing):**
```sql
state_of_origin VARCHAR(100) NULL COMMENT "State of origin from NIN"
lga_of_origin VARCHAR(100) NULL COMMENT "LGA of origin from NIN"
city_of_birth VARCHAR(100) NULL COMMENT "City/LGA of birth from NIN"
religion VARCHAR(64) NULL COMMENT "Religion from NIN verification"
```

**Safe Features:**
- ✅ Checks `INFORMATION_SCHEMA` before adding columns
- ✅ Safe to run multiple times
- ✅ Won't duplicate columns or fail if they exist
- ✅ Executes in single transaction

---

## 🧪 Testing

### Test New Verification Flow
1. Register as a new job seeker
2. Complete NIN verification
3. Check profile to see:
   - Name updated from NIN
   - Date of birth populated
   - State/LGA of origin filled
   - City of birth added
   - Religion recorded
   - Profile picture from NIN

### Test Backfill
1. Run migration
2. Access `admin/backfill-nin-data.php` as admin
3. Verify statistics match expected counts
4. Check sample user profiles to confirm updates

---

## 📝 Files Modified/Created

### New Files
- `database/add-nin-profile-fields.sql` - Comprehensive migration for all NIN fields
- `database/run-migration.php` - CLI migration runner
- `admin/backfill-nin-data.php` - Admin batch backfill interface
- `apply-nin-data.php` - Single-user apply script

### Modified Files
- `api/verify-nin.php` - Added `applyNINDataToProfile()` method with column checks
- Updated to include `city_of_birth` mapping

---

## ❓ Troubleshooting

### Migration Fails
```powershell
# Check if MySQL is running
php -r "new mysqli('localhost', 'root', '', 'findajob_ng');"

# Run migration with verbose output
php database/run-migration.php add-nin-profile-fields.sql 2>&1
```

### Columns Still Missing After Migration
```sql
-- Check current schema
SHOW COLUMNS FROM job_seeker_profiles;

-- Manually add missing column (example)
ALTER TABLE job_seeker_profiles 
ADD COLUMN religion VARCHAR(64) NULL COMMENT "Religion from NIN";
```

### Backfill Shows Zero Users
- Verify users have `nin_verified = 1`
- Check `nin_verification_data` is not empty
- Ensure you're logged in as admin

### Fields Not Updating
- Check error logs: `logs/error.log`
- Verify NIN response contains expected fields
- Ensure columns exist in database schema

---

## 🎯 Next Steps

1. ✅ Run the migration now
2. ✅ Test with a new verification
3. ✅ Backfill existing users
4. ✅ Monitor error logs for any issues
5. ✅ Update user documentation about verified data

---

## 🔐 Security Notes

- NIN data is considered **authoritative** - it overwrites existing profile data
- Profile picture is **locked** after NIN verification
- All updates are logged in `verification_audit_log`
- Transaction records stored in `verification_transactions`
- Full NIN response preserved in `nin_verification_data` (JSON)

---

**Ready to proceed?** Run the migration command above! 🚀
