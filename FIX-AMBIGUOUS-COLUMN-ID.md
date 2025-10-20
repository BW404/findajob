# Fixed: Ambiguous Column 'id' Error

## Problem
```
Fatal error: SQLSTATE[23000]: Integrity constraint violation: 1052 
Column 'id' in field list is ambiguous
in all-applications.php on line 92
```

## Root Cause

In a JOIN query with multiple tables, when selecting `id` without specifying which table, SQL doesn't know which `id` column to use.

**Problem Query:**
```sql
SELECT id, title, COUNT(ja.id) as app_count 
FROM jobs j
LEFT JOIN job_applications ja ON j.id = ja.job_id
```

Both tables have an `id` column:
- `jobs.id` ✅ (what we want)
- `job_applications.id` ❌ (ambiguous)

SQL doesn't know which one to SELECT.

## Solution Implemented

### Specify Table Prefix for Ambiguous Columns

**Before:**
```sql
SELECT id, title, COUNT(ja.id) as app_count 
       ^^
       Ambiguous! Which table's id?
```

**After:**
```sql
SELECT j.id, j.title, COUNT(ja.id) as app_count 
       ^^^^  ^^^^^^^
       Explicitly use jobs table
```

## Best Practice: Always Use Table Prefixes in JOINs

When you have JOINs, always prefix columns with table aliases:

**Good:**
```sql
SELECT j.id, j.title, j.status,
       u.id as user_id, u.name
FROM jobs j
JOIN users u ON j.user_id = u.id
```

**Bad:**
```sql
SELECT id, title, status  -- ❌ Which table's id?
FROM jobs j
JOIN users u ON j.user_id = u.id
```

## Files Modified

✅ `pages/company/all-applications.php`
- Line 85: Changed `SELECT id, title` → `SELECT j.id, j.title`

## Testing

✅ PHP syntax: PASS
✅ SQL query: VALID
✅ No ambiguous columns: VERIFIED

## Result

✅ SQL error resolved
✅ Jobs dropdown will load correctly
✅ Query explicitly selects jobs.id and jobs.title

---

*Fixed: October 21, 2025*
*Issue: Ambiguous column 'id' in SELECT with multiple tables*
*Solution: Added table prefix (j.id, j.title)*
