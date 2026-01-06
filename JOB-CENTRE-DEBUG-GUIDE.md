# Job Centre Display Issue - Debugging Guide

**Date**: January 6, 2026  
**Issue**: Only 5 centres showing when "All Centres" is selected (should show 9)  
**Status**: 🔍 DEBUGGING IN PROGRESS

---

## Problem Statement

User reported that when selecting "All Centres", only 5 job centres are displayed instead of the expected 9 centres that exist in the database.

---

## Verified Facts

### Database Status ✅
- **Total Active Centres**: 9
- **All centres are active** (`is_active = 1`)
- **Centre IDs**: 1-9 (sequential)
- **Verified via**: Direct MySQL query

```sql
SELECT COUNT(*) as total FROM job_centres WHERE is_active = 1;
-- Result: 9
```

### API Backend ✅
- SQL query looks correct
- LIMIT/OFFSET bug was fixed
- Per page setting: 12 (should show all 9 on page 1)

---

## Debugging Steps Added

### 1. Console Logging
Added debug logging to track data flow:

**In `loadCentres()` function:**
```javascript
console.log('API Response:', data);
console.log('Centres count:', data.centres ? data.centres.length : 0);
console.log('Pagination:', data.pagination);
```

**In `displayCentres()` function:**
```javascript
console.log('Displaying centres:', centres.length, centres);
```

### 2. Error Handling
Added try-catch in `createCentreCard()` to prevent silent failures:

```javascript
try {
    // Card creation logic
    return cardHTML;
} catch (error) {
    console.error('Error creating card for centre:', centre, error);
    return ''; // Don't break the whole page
}
```

### 3. Services Array Fix
Fixed potential issue with services field:

```javascript
// Before (could fail if services is string)
const services = centre.services.slice(0, 3);

// After (handles both string and array)
const servicesArray = Array.isArray(centre.services) ? centre.services : 
                     (typeof centre.services === 'string' ? JSON.parse(centre.services) : []);
const services = servicesArray.slice(0, 3);
```

---

## How to Debug

### Step 1: Open Browser Console
1. Navigate to: `http://localhost/findajob/pages/user/job-centres.php`
2. Press `F12` to open Developer Tools
3. Click on the "Console" tab
4. Refresh the page

### Step 2: Check Console Output
Look for these messages:

```
API Response: {success: true, centres: Array(9), pagination: {...}}
Centres count: 9
Pagination: {page: 1, per_page: 12, total: 9, total_pages: 1}
Displaying centres: 9 [...]
```

**Expected Results:**
- ✅ API should return 9 centres
- ✅ `centres.length` should be 9
- ✅ `displayCentres` should receive 9 centres
- ✅ No errors in createCentreCard

**If you see fewer centres:**
- Check for errors in the console
- Look for failed centre card creation
- Check if any centres have malformed data

### Step 3: Test API Directly
Open the test page:
```
http://localhost/findajob/test-job-centres-api.php
```

This will show:
1. Database count (should be 9)
2. API response structure
3. SQL query results
4. JavaScript fetch test

### Step 4: Network Tab
1. Open DevTools → Network tab
2. Filter by "XHR" or "Fetch"
3. Refresh the page
4. Click on the `job-centres.php?action=list` request
5. Check the "Response" tab

**Look for:**
- Status: 200 OK
- Response JSON with 9 centres
- Pagination: `{total: 9, total_pages: 1}`

---

## Possible Causes

### 1. JavaScript Error (Most Likely)
If `createCentreCard()` throws an error for some centres, the `.map()` might fail silently or return empty strings.

**Symptoms:**
- Some centres render, others don't
- No error in console (before our fixes)
- Inconsistent count

**Solution:** ✅ Added try-catch with logging

### 2. Services Field Format
If some centres have `services` as a string instead of array, `.slice()` would fail.

**Symptoms:**
- Cards fail to render
- TypeError in console
- Only centres with proper arrays show

**Solution:** ✅ Added type checking and parsing

### 3. CSS Display Issue
Grid layout hiding some cards due to overflow or height limits.

**Symptoms:**
- All 9 cards exist in DOM
- Only 5 visible on screen
- Can see others by scrolling or inspecting

**Check:** Inspect page, count `.centre-card` elements in DOM

### 4. API Returning Limited Data
Backend limiting results despite LIMIT 12.

**Symptoms:**
- API response shows < 9 centres
- Network tab confirms fewer centres
- Database has 9 but API returns 5

**Check:** Test API page, Network tab

### 5. Filter State Issue
Filters not resetting properly when "All Centres" clicked.

**Symptoms:**
- Some filter still active
- API gets filtered query
- Specific centres excluded

**Check:** Console log of `filters` object

---

## Quick Fixes to Try

### Fix 1: Hard Refresh
```
Ctrl + Shift + R (Windows/Linux)
Cmd + Shift + R (Mac)
```
Clears cached JavaScript

### Fix 2: Clear Browser Cache
```javascript
// In console
localStorage.clear();
sessionStorage.clear();
location.reload(true);
```

### Fix 3: Check Filters
```javascript
// In console, check current filters
console.log(filters);

// Should show:
// {state: '', category: '', is_government: null, sort: 'rating'}
```

### Fix 4: Manual API Test
```javascript
// In console
fetch('/findajob/api/job-centres.php?action=list')
    .then(r => r.json())
    .then(d => console.log('Centres:', d.centres.length, d));
```

---

## Expected Console Output (Correct Behavior)

```javascript
// On page load or "All Centres" click
API Response: {
    success: true,
    centres: [
        {id: 1, name: "National Directorate of Employment (NDE) Lagos", ...},
        {id: 2, name: "Federal Ministry of Labour and Employment - Lagos Office", ...},
        {id: 3, name: "NDE Abuja Headquarters", ...},
        {id: 4, name: "Industrial Training Fund (ITF) - Lagos", ...},
        {id: 5, name: "Jobberman Nigeria", ...},
        {id: 6, name: "Workforce Group", ...},
        {id: 7, name: "Dragnet Solutions", ...},
        {id: 8, name: "Michael Stevens Consulting", ...},
        {id: 9, name: "Career Clinic Nigeria", ...}
    ],
    pagination: {
        page: 1,
        per_page: 12,
        total: 9,
        total_pages: 1
    }
}

Centres count: 9
Pagination: {page: 1, per_page: 12, total: 9, total_pages: 1}
Displaying centres: 9 (9) [{...}, {...}, ...]
```

---

## What to Report Back

Please check and report:

1. **Console Output**:
   - How many centres does API return?
   - How many does displayCentres receive?
   - Any JavaScript errors?

2. **DOM Inspection**:
   - Count `.centre-card` elements in DOM
   - Are all 9 in the HTML?

3. **Network Tab**:
   - What does the API response show?
   - Status code?
   - Full JSON response?

4. **Visual**:
   - How many cards do you see on screen?
   - Do you need to scroll to see more?
   - Any cards partially hidden?

---

## Test Commands

### Database Verification
```bash
cd /opt/lampp && ./bin/mysql -u root findajob_ng -e "
SELECT id, name, SUBSTRING(description, 1, 50) as desc_preview 
FROM job_centres 
WHERE is_active = 1 
ORDER BY id;"
```

### API Test
```bash
curl -s "http://localhost/findajob/api/job-centres.php?action=list" | \
grep -o '"id":[0-9]*' | wc -l
# Should output: 9
```

### Check Pagination Calculation
```bash
cd /opt/lampp && ./bin/mysql -u root findajob_ng -e "
SELECT 
    COUNT(*) as total,
    CEILING(COUNT(*) / 12) as total_pages
FROM job_centres 
WHERE is_active = 1;"
# Should show: total=9, total_pages=1
```

---

## Files Modified for Debugging

1. **`/pages/user/job-centres.php`**
   - Added console.log in `loadCentres()`
   - Added console.log in `displayCentres()`
   - Added try-catch in `createCentreCard()`
   - Fixed services array handling

2. **`/test-job-centres-api.php`** (NEW)
   - Comprehensive API test page
   - Shows database vs API comparison
   - JavaScript fetch test
   - SQL query debugging

---

## Next Steps

1. ✅ Open browser console and check logs
2. ✅ Visit test page: `http://localhost/findajob/test-job-centres-api.php`
3. ✅ Report findings (console output, DOM count, network response)
4. 🔄 Analyze results and apply targeted fix

---

## Temporary Workaround

If you need to see all centres immediately while we debug:

**Option 1: Remove pagination limit**
```javascript
// In pages/user/job-centres.php, change per_page
$per_page = 100; // Show all centres on one page
```

**Option 2: Force reload all centres**
```javascript
// In browser console
loadCentres(1);
```

---

**Status**: Awaiting user console output for final diagnosis  
**Priority**: High  
**Impact**: User Experience

---

*Last Updated: January 6, 2026 08:35 UTC*
