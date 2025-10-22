# 🐛 Save Button Debug Guide

## Issue: Save job button not working

## What I Fixed:

1. **Added `onclick="event.stopPropagation()"` to button HTML**
   - Prevents the job card's onclick from triggering when you click the save button

2. **Added `e.preventDefault()` in event handler**
   - Ensures the button click doesn't cause any default behavior

3. **Added console logging**
   - Now you can see exactly what's happening in the browser console

## How to Test:

### Step 1: Open Browser Console
1. Open http://localhost/findajob/pages/jobs/browse.php
2. Press **F12** to open Developer Tools
3. Click on **Console** tab

### Step 2: Click a Save Button
Click the heart icon (🤍) on any job card

### Step 3: Check Console Output
You should see:
```
Binding events to X save buttons
Save button clicked for job ID: 123
Toggle save job: 123 Action: save
Making API call to: ../../api/jobs.php
API response status: 200
API response data: {success: true, message: "Job saved successfully", action: "saved"}
```

## What to Look For:

### If you see "Binding events to 0 save buttons":
**Problem:** Buttons not rendered or wrong class name
**Solution:** Check if jobs are loading, refresh page

### If you don't see "Save button clicked":
**Problem:** Event listener not attached or event bubbling issue
**Solution:** Check if page fully loaded, try hard refresh (Ctrl+F5)

### If you see "API response status: 401":
**Problem:** Not logged in
**Solution:** Log in as a job seeker first

### If you see "API response status: 403":
**Problem:** Logged in as employer, not job seeker
**Solution:** Log out and log in as a job seeker

### If you see "Failed to fetch" or network error:
**Problem:** API endpoint not found or CORS issue
**Solution:** Check if API file exists at `api/jobs.php`

### If you see "Table 'saved_jobs' doesn't exist":
**Problem:** Database table not created (shouldn't happen, we just created it!)
**Solution:** Re-run the SQL migration

## Quick Fixes:

### Fix 1: Hard Refresh
Press **Ctrl+F5** to force reload JavaScript files

### Fix 2: Clear Browser Cache
1. Press F12
2. Right-click on the refresh button
3. Click "Empty Cache and Hard Reload"

### Fix 3: Check Login Status
Make sure you're logged in as a **job seeker**, not an employer.
- Job seekers can save jobs
- Employers cannot save jobs (they post jobs)

### Fix 4: Verify API Works
Test the API directly by running this in browser console:
```javascript
fetch('../../api/jobs.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=save&job_id=1'
})
.then(r => r.json())
.then(d => console.log(d));
```

Expected output:
```
{success: true, message: "Job saved successfully", action: "saved"}
```

Or if you're not logged in:
```
{success: false, message: "You must be logged in to save jobs"}
```

## Common Issues & Solutions:

| Issue | Cause | Solution |
|-------|-------|----------|
| Button does nothing | Event not bound | Hard refresh (Ctrl+F5) |
| Redirects to job details | Event bubbling | Fixed with stopPropagation |
| "Not logged in" error | No active session | Log in as job seeker |
| "Employer can't save" | Wrong user type | Use job seeker account |
| Heart doesn't change | CSS not loaded | Check main.css loading |
| No buttons visible | JS error on page | Check console for errors |

## Test Account Setup:

If you don't have a job seeker account:
1. Go to http://localhost/findajob/pages/auth/register-jobseeker.php
2. Register a new job seeker account
3. Verify email (or skip if email verification disabled)
4. Browse jobs and try saving

## Success Indicators:

When working correctly:
- ✅ Console shows "Binding events to X save buttons" (X > 0)
- ✅ Clicking heart shows console logs
- ✅ Heart changes from 🤍 to ❤️
- ✅ No errors in console
- ✅ Job appears in saved jobs page
- ✅ Clicking again unsaves (❤️ to 🤍)

## Need More Help?

Run this diagnostic in console to check everything:
```javascript
// Check if buttons exist
console.log('Save buttons:', document.querySelectorAll('.save-job-btn').length);

// Check if event listeners attached
console.log('Job cards:', document.querySelectorAll('.job-card').length);

// Check login status
fetch('../../api/auth.php?action=status')
    .then(r => r.json())
    .then(d => console.log('Login status:', d));

// Check if saved_jobs table exists
fetch('../../api/jobs.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=save&job_id=999999'
})
.then(r => r.json())
.then(d => console.log('API test:', d));
```

---

**Updated:** October 23, 2025  
**Status:** Debugging mode enabled with console logs
