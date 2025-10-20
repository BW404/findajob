# Fixed: Unknown Column 'experience_years' Error

## Problem
```
Fatal error: SQLSTATE[42S22]: Column not found: 1054 
Unknown column 'jsp.experience_years' in 'field list'
in all-applications.php on line 64
```

## Root Cause

Column name mismatch between code and database schema.

**Code was using:**
```php
jsp.experience_years  // ❌ Wrong
```

**Database has:**
```sql
years_of_experience INT(11)  // ✅ Correct
```

## Database Schema Verified

From `job_seeker_profiles` table:
```sql
years_of_experience INT(11) DEFAULT 0
```

**NOT** `experience_years`

## Solution Implemented

### 1. Updated SQL Query (Line 20)

**Before:**
```php
$query = "SELECT ja.*, 
          j.title as job_title, 
          j.id as job_id,
          u.first_name, u.last_name, u.email, u.phone,
          jsp.skills, jsp.experience_years  // ❌ Wrong column name
          FROM job_applications ja
          ...
```

**After:**
```php
$query = "SELECT ja.*, 
          j.title as job_title, 
          j.id as job_id,
          u.first_name, u.last_name, u.email, u.phone,
          jsp.skills, jsp.years_of_experience  // ✅ Correct column name
          FROM job_applications ja
          ...
```

### 2. Updated Display Code (Lines 284-287)

**Before:**
```php
<?php if ($app['experience_years']): ?>  // ❌ Wrong key
    <span>
        <?php echo htmlspecialchars($app['experience_years']); ?> years exp.
    </span>
<?php endif; ?>
```

**After:**
```php
<?php if (!empty($app['years_of_experience'])): ?>  // ✅ Correct key + safety check
    <span>
        <?php echo htmlspecialchars($app['years_of_experience']); ?> years exp.
    </span>
<?php endif; ?>
```

## Files Modified

✅ `pages/company/all-applications.php`
- Line 20: Changed `jsp.experience_years` → `jsp.years_of_experience`
- Line 284: Changed `$app['experience_years']` → `$app['years_of_experience']`
- Line 284: Added `!empty()` check for safety
- Line 286: Changed `$app['experience_years']` → `$app['years_of_experience']`

## Files Verified (Already Correct)

✅ `pages/company/applicants.php` - Already using `years_of_experience` correctly

## Column Name Reference

**Correct column name across the system:**
```
years_of_experience  ✅ Use this
```

**NOT:**
```
experience_years     ❌ Don't use
```

## Testing

✅ PHP syntax: PASS
✅ Column name: CORRECT
✅ Query structure: VALID
✅ Display logic: UPDATED

## Result

✅ SQL error resolved
✅ Experience years display correctly
✅ Consistent column naming
✅ Safety checks added with !empty()

---

*Fixed: October 21, 2025*
*Issue: Wrong column name (experience_years instead of years_of_experience)*
*Solution: Updated query and display code to use correct column name*
