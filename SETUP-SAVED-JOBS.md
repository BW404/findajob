# 🚀 Quick Setup Guide - Saved Jobs Feature

## Step 1: Start MySQL Server
Make sure XAMPP MySQL is running:
1. Open XAMPP Control Panel
2. Click "Start" next to MySQL
3. Wait for green "Running" indicator

## Step 2: Create Database Table
Run this command in PowerShell (in the project root):

```powershell
Get-Content database\create-saved-jobs-table.sql | mysql.exe -u root -p findajob_ng
```

**Or** use phpMyAdmin:
1. Open: http://localhost/phpmyadmin
2. Select `findajob_ng` database (left sidebar)
3. Click "SQL" tab
4. Open `database/create-saved-jobs-table.sql` in a text editor
5. Copy all SQL code
6. Paste into phpMyAdmin SQL window
7. Click "Go" button

## Step 3: Verify Table Created
In phpMyAdmin or MySQL command line:

```sql
USE findajob_ng;
SHOW TABLES LIKE 'saved_jobs';
```

You should see:
```
+---------------------------------+
| Tables_in_findajob_ng (saved_jobs) |
+---------------------------------+
| saved_jobs                      |
+---------------------------------+
```

## Step 4: Test the Feature

### Test Saving Jobs:
1. Browse to: http://localhost/findajob/pages/jobs/browse.php
2. Look for heart icon (🤍) in top-right of job cards
3. Click the heart - it should turn red (❤️)
4. Check browser console (F12) - no errors should appear

### Test Saved Jobs Page:
1. Click ❤️ "Saved" in bottom navigation
2. Should redirect to saved jobs page
3. Your saved job should appear
4. Try clicking heart again to unsave
5. Confirm dialog should appear

### Test Bottom Navigation:
1. Visit these pages and verify bottom nav shows:
   - ✅ Browse Jobs: http://localhost/findajob/pages/jobs/browse.php
   - ✅ Dashboard: http://localhost/findajob/pages/user/dashboard.php
   - ✅ Applications: http://localhost/findajob/pages/user/applications.php
   - ✅ Saved Jobs: http://localhost/findajob/pages/user/saved-jobs.php
   - ✅ Profile: http://localhost/findajob/pages/user/profile.php
   - ✅ CV Manager: http://localhost/findajob/pages/user/cv-manager.php

## Troubleshooting

### "Table 'saved_jobs' doesn't exist"
**Problem**: SQL file not run yet
**Solution**: Complete Step 2 above

### "MySQL server not running"
**Problem**: XAMPP MySQL not started
**Solution**: Start MySQL in XAMPP Control Panel

### Heart icon doesn't change
**Problem**: JavaScript error or API issue
**Solution**: 
1. Open browser console (F12)
2. Check for JavaScript errors
3. Check Network tab for failed API calls
4. Verify you're logged in as a job seeker

### Bottom nav not showing
**Problem**: CSS not loaded or JavaScript error
**Solution**:
1. Hard refresh page (Ctrl+F5)
2. Check if `main.css` is loading
3. Verify `document.body.classList.add('has-bottom-nav')` runs

### Can't unsave jobs
**Problem**: Confirmation dialog or API issue
**Solution**:
1. Check browser console for errors
2. Verify you're on the saved-jobs.php page
3. Try hard refresh

## Success Indicators

When everything is working:
- ✅ Heart icons appear on all job cards
- ✅ Clicking heart changes from 🤍 to ❤️
- ✅ Saved jobs page shows saved jobs
- ✅ Bottom nav visible on all pages
- ✅ No console errors
- ✅ Database table has records when you save jobs

## Test Saved Jobs in Database

```sql
SELECT * FROM saved_jobs;
```

Should show:
```
+----+---------+--------+---------------------+
| id | user_id | job_id | saved_at            |
+----+---------+--------+---------------------+
|  1 |       5 |     12 | 2024-01-15 14:30:00 |
+----+---------+--------+---------------------+
```

## Next Steps

Once setup is complete:
1. ✅ Test all features work
2. ✅ Try on mobile viewport (F12 → Toggle Device Toolbar)
3. ✅ Test with different user accounts
4. ✅ Verify data persists after logout/login
5. ✅ Check error handling (try saving non-existent job ID)

---

Need help? Check the detailed documentation in `SAVED-JOBS-FEATURE-ADDED.md`
