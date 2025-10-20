# Fixed: Undefined Array Key "status" Warning

## Problem
```
Warning: Undefined array key "status" in active-jobs.php on line 216
Warning: Undefined array key "status" in active-jobs.php on line 227
```

## Root Cause

The database column is named `STATUS` (uppercase), but the code was trying to access it as `$job['status']` (lowercase).

In MySQL:
- Column names are case-insensitive in queries
- But PDO returns results using the **actual column name** from the database

**Database Schema:**
```sql
STATUS ENUM('draft','active','paused','closed','expired')
```

**Code was trying:**
```php
$job['status']  // ❌ Doesn't exist (case mismatch)
```

## Solution Implemented

### 1. Added Explicit Alias in Query

```php
$query = "SELECT j.*, 
          j.STATUS as status,  // ✅ Explicitly alias to lowercase
          COUNT(DISTINCT ja.id) as application_count,
          ...
```

This ensures `$job['status']` will always be available.

### 2. Added Fallback Safety Check

```php
// Handle both lowercase and uppercase, default to 'draft'
$job_status = $job['status'] ?? $job['STATUS'] ?? 'draft';
```

This provides triple-layer protection:
1. Uses `status` if aliased
2. Falls back to `STATUS` if not aliased
3. Defaults to `'draft'` if neither exists

### 3. Added All Status Values

Updated status colors to include all possible ENUM values:

```php
$statusColors = [
    'active' => ['bg' => '#059669', 'text' => 'white'],    // Green
    'inactive' => ['bg' => '#f59e0b', 'text' => 'white'],  // Orange
    'paused' => ['bg' => '#f59e0b', 'text' => 'white'],    // Orange
    'closed' => ['bg' => '#ef4444', 'text' => 'white'],    // Red
    'expired' => ['bg' => '#6b7280', 'text' => 'white'],   // Gray
    'draft' => ['bg' => '#6b7280', 'text' => 'white']      // Gray
];
```

### 4. Updated Stats Query

Also updated the statistics query for consistency:

```php
COUNT(DISTINCT CASE WHEN j.STATUS = 'active' THEN j.id END) as active_jobs
```

## Files Modified

✅ `pages/company/active-jobs.php`
- Line 16: Added `j.STATUS as status` alias
- Line 213: Added fallback logic `$job['status'] ?? $job['STATUS'] ?? 'draft'`
- Line 215-220: Added all status types (paused, closed, expired)
- Lines 62-64: Updated stats query to use STATUS

## Testing

✅ PHP syntax: PASS
✅ Alias correctly maps STATUS → status
✅ Fallback handles missing values
✅ All status types have colors

## Status Display Colors

| Status   | Color  | Hex Code |
|----------|--------|----------|
| active   | Green  | #059669  |
| inactive | Orange | #f59e0b  |
| paused   | Orange | #f59e0b  |
| closed   | Red    | #ef4444  |
| expired  | Gray   | #6b7280  |
| draft    | Gray   | #6b7280  |

## Result

✅ No more "Undefined array key" warnings
✅ Status badges display correctly
✅ All job statuses supported
✅ Graceful fallback if status missing

---

*Fixed: October 21, 2025*
*Issue: Case mismatch between database column (STATUS) and array key (status)*
*Solution: Explicit alias + fallback safety check*
