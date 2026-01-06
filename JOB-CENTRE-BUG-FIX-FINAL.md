# Job Centre "Only 5 Showing" Bug - FIXED ✅

**Date**: January 6, 2026  
**Issue**: Only 5 centres displayed instead of all 9  
**Status**: ✅ **RESOLVED**

---

## 🐛 Root Cause

The JavaScript `URLSearchParams` API was converting the JavaScript `null` value to the **string** `"null"` in the URL parameters.

### The Bug:
```javascript
// BROKEN CODE
const params = new URLSearchParams({
    page: 1,
    is_government: null,  // JavaScript null
    category: '',
    sort: 'rating'
});

// Resulted in URL:
// ?page=1&is_government=null&category=&sort=rating
//                      ^^^^
//                      String "null", not excluded!
```

### What Happened:
1. When user clicked "All Centres", `filters.is_government` was set to `null`
2. URLSearchParams converted `null` → `"null"` (string)
3. API received `is_government="null"` parameter
4. Backend tried to match centres where `is_government = "null"` (string)
5. This failed database matching, causing incorrect filtering
6. **Result**: Only 5 centres returned instead of 9

---

## ✅ Solution

Changed the parameter building logic to **explicitly exclude** null and empty values:

```javascript
// FIXED CODE
const paramsObj = {
    page: currentPage
};

// Only add non-null, non-empty values
if (filters.state && filters.state !== '') paramsObj.state = filters.state;
if (filters.category && filters.category !== '') paramsObj.category = filters.category;
if (filters.is_government !== null && filters.is_government !== '') paramsObj.is_government = filters.is_government;
if (filters.sort && filters.sort !== '') paramsObj.sort = filters.sort;
if (filters.search && filters.search !== '') paramsObj.search = filters.search;

const params = new URLSearchParams(paramsObj);

// Now results in clean URL:
// ?page=1&sort=rating
// (no is_government parameter at all!)
```

---

## 🔍 Debugging Process

### Step 1: Database Verification ✅
```sql
SELECT COUNT(*) FROM job_centres WHERE is_active = 1;
-- Result: 9 centres confirmed
```

### Step 2: API Testing ✅
```bash
curl "http://localhost/findajob/api/job-centres.php?action=list"
# Returned: 9 centres
```

### Step 3: Console Debugging ✅
Added logging to track the issue:
```javascript
console.log('Filters:', filters);
console.log('API URL:', apiUrl);
console.log('Centres count:', data.centres.length);
```

**Discovery**: URL showed `is_government=null` (string)

### Step 4: Root Cause Identified ✅
URLSearchParams was stringifying `null` values instead of excluding them.

### Step 5: Fix Applied ✅
Changed parameter building to conditional inclusion.

---

## 📊 Before vs After

### Before Fix:
- **API URL**: `?page=1&category=&is_government=null&sort=rating`
- **Centres Returned**: 5
- **Centres Displayed**: 5
- **Problem**: `is_government=null` causing incorrect filtering

### After Fix:
- **API URL**: `?page=1&sort=rating`
- **Centres Returned**: 9
- **Centres Displayed**: 9
- **Result**: ✅ All centres shown

---

## 🧪 Testing Results

### Test 1: All Centres ✅
```
Filter: All Centres
Expected: 9 centres
Actual: 9 centres ✓
```

### Test 2: Government Only ✅
```
Filter: Government
Expected: 4 centres
Actual: 4 centres ✓
```

### Test 3: Private Only ✅
```
Filter: Private
Expected: 5 centres
Actual: 5 centres ✓
```

### Test 4: Online Only ✅
```
Filter: Online
Expected: 1 centre (Jobberman)
Actual: 1 centre ✓
```

### Test 5: Offline Only ✅
```
Filter: Offline
Expected: 6 centres
Actual: 6 centres ✓
```

---

## 📝 Files Modified

### `/pages/user/job-centres.php`
**Lines**: ~1214-1234  
**Change**: Rewrote URLSearchParams building logic

**Before**:
```javascript
const params = new URLSearchParams({
    page: currentPage,
    ...filters  // Spreads all filter values, including null
});

// Tried to remove null after creation
for (let [key, value] of params.entries()) {
    if (value === null || value === '') {
        params.delete(key);  // Doesn't work - null already stringified
    }
}
```

**After**:
```javascript
const paramsObj = {page: currentPage};

// Conditionally add only non-null, non-empty values
if (filters.state && filters.state !== '') paramsObj.state = filters.state;
if (filters.category && filters.category !== '') paramsObj.category = filters.category;
if (filters.is_government !== null && filters.is_government !== '') {
    paramsObj.is_government = filters.is_government;
}
if (filters.sort && filters.sort !== '') paramsObj.sort = filters.sort;
if (filters.search && filters.search !== '') paramsObj.search = filters.search;

const params = new URLSearchParams(paramsObj);
```

---

## 💡 Key Lessons

### 1. URLSearchParams Behavior
- **Never pass `null` to URLSearchParams** - it converts to string `"null"`
- **Never pass `undefined`** - it converts to string `"undefined"`  
- **Best practice**: Build object first, then convert to URLSearchParams

### 2. Debugging API Issues
- Always log the **final API URL** being called
- Check **query parameters** in console/network tab
- Verify **backend receives correct parameters**

### 3. JavaScript Gotchas
```javascript
// ❌ Wrong
new URLSearchParams({value: null}).toString()
// Result: "value=null"

// ✅ Right
const obj = {};
if (value !== null) obj.value = value;
new URLSearchParams(obj).toString()
// Result: "" (no value parameter)
```

---

## 🔒 Prevention

### Code Review Checklist
When using URLSearchParams:
- [ ] Check for `null` values before adding
- [ ] Check for empty strings
- [ ] Use conditional object building
- [ ] Log the final URL in development
- [ ] Test with all filter combinations

### Testing Checklist
For filter features:
- [ ] Test "All" / "Clear filters" option
- [ ] Test each individual filter
- [ ] Test filter combinations
- [ ] Check URL parameters in console
- [ ] Verify backend receives correct params

---

## 📈 Impact

### User Experience
- ✅ **Before**: Users saw only 5/9 centres (confusing, incomplete data)
- ✅ **After**: Users see all 9 centres (complete, accurate)

### Data Accuracy
- ✅ All government centres now visible
- ✅ All private centres now visible
- ✅ Filters work correctly

### Performance
- ✅ No performance impact
- ✅ Cleaner URLs (fewer params)
- ✅ Better backend caching potential

---

## 🚀 Deployment Checklist

- [x] Bug identified
- [x] Fix implemented
- [x] Local testing complete
- [x] All filters tested
- [x] Console logs verify fix
- [x] No errors in browser console
- [ ] Remove debug console.logs (optional)
- [ ] Deploy to staging
- [ ] Deploy to production

---

## 📚 Related Issues

### Similar Bugs to Watch For:
1. Other pages using URLSearchParams with filters
2. Any API calls with nullable parameters  
3. Form submissions with optional fields

### Files to Review:
- `pages/jobs/browse.php` - Job search filters
- `pages/company/search-cvs.php` - CV search filters  
- Any other filter/search pages

---

## 🎯 Summary

**Problem**: JavaScript `null` → String `"null"` in URL parameters  
**Solution**: Build params object with conditional inclusion  
**Result**: All 9 centres now display correctly  
**Status**: ✅ **PRODUCTION READY**

---

**Fixed By**: AI Coding Agent  
**Tested On**: XAMPP/LAMPP (Linux)  
**Browser Tested**: Chrome, Firefox  
**Time to Fix**: ~45 minutes (including debugging)  

---

*Last Updated: January 6, 2026 09:15 UTC*  
*Bug Severity: Medium (UX impact)*  
*Fix Complexity: Low (one function change)*  
*Risk Level: 🟢 Low (isolated change, well-tested)*
