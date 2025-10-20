# Fixed: Undefined Array Key "cv_file" Warning

## Problem
```
Warning: Undefined array key "cv_file" in applicants.php on line 281
```

## Root Cause

The SQL query wasn't selecting CV information, and the code was trying to access `$application['cv_file']` which didn't exist in the result set.

**Database Schema:**
```sql
job_applications table:
- cv_id INT(11)         -- Foreign key to cvs table

cvs table:
- id INT(11)
- file_path VARCHAR     -- Actual path to CV file
```

The CV file path is in a separate `cvs` table, not directly in `job_applications`.

## Solution Implemented

### 1. Updated SQL Query to JOIN cvs Table

**Before:**
```php
SELECT ja.*, 
       j.title as job_title,
       u.first_name, u.last_name, u.email, u.phone,
       ...
FROM job_applications ja
JOIN jobs j ON ja.job_id = j.id
JOIN users u ON ja.job_seeker_id = u.id
LEFT JOIN job_seeker_profiles jsp ON u.id = jsp.user_id
```

**After:**
```php
SELECT ja.*, 
       j.title as job_title,
       u.first_name, u.last_name, u.email, u.phone,
       ...,
       cv.file_path as cv_file              -- ✅ Added CV file path
FROM job_applications ja
JOIN jobs j ON ja.job_id = j.id
JOIN users u ON ja.job_seeker_id = u.id
LEFT JOIN job_seeker_profiles jsp ON u.id = jsp.user_id
LEFT JOIN cvs cv ON ja.cv_id = cv.id       -- ✅ Added JOIN with cvs table
```

### 2. Updated CV Display Logic with Safety Checks

**Before:**
```php
<?php if ($application['cv_file']): ?>
    <a href="../../uploads/cvs/<?php echo htmlspecialchars($application['cv_file']); ?>" 
       target="_blank" class="btn btn-outline btn-sm">
        <i class="fas fa-file-pdf"></i> View CV
    </a>
<?php endif; ?>
```

**After:**
```php
<?php if (!empty($application['cv_file'])): ?>
    <!-- If cv_file path exists, use it directly -->
    <a href="../../<?php echo htmlspecialchars($application['cv_file']); ?>" 
       target="_blank" class="btn btn-outline btn-sm">
        <i class="fas fa-file-pdf"></i> View CV
    </a>
<?php elseif (!empty($application['cv_id'])): ?>
    <!-- Fallback: if only cv_id exists, use download script -->
    <a href="../user/cv-download.php?id=<?php echo $application['cv_id']; ?>" 
       target="_blank" class="btn btn-outline btn-sm">
        <i class="fas fa-file-pdf"></i> Download CV
    </a>
<?php endif; ?>
```

## Changes Made

### Query Changes (Line ~33)
- ✅ Added `cv.file_path as cv_file` to SELECT
- ✅ Added `LEFT JOIN cvs cv ON ja.cv_id = cv.id`

### Display Changes (Line ~281)
- ✅ Changed `if ($application['cv_file'])` to `if (!empty($application['cv_file']))`
- ✅ Added fallback for `cv_id` if `cv_file` is empty
- ✅ Fixed file path (removed hardcoded `uploads/cvs/`, now uses full path from DB)

## How It Works Now

### With CV File Path
Application has `cv_file` = "uploads/cvs/resume.pdf"
→ Shows "View CV" button linking to file

### With CV ID Only
Application has `cv_id` = 123, but no `cv_file`
→ Shows "Download CV" button using cv-download.php script

### No CV
Application has neither
→ No CV button shown

## Files Modified

✅ `pages/company/applicants.php`
- Line 33-48: Updated SQL query
- Line 281-291: Updated CV display logic

## Testing

✅ PHP syntax: PASS
✅ Query includes CV data: YES
✅ Safety checks added: YES
✅ Fallback logic: YES

## Result

✅ No more "Undefined array key" warnings
✅ CV button shows when CV exists
✅ Graceful handling when CV missing
✅ Works with both file paths and CV IDs

---

*Fixed: October 21, 2025*
*Issue: Missing cv_file in query result*
*Solution: Added LEFT JOIN with cvs table and safety checks*
