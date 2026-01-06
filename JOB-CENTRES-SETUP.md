# Job Centres Admin Panel - Quick Setup Guide (XAMPP)

## ✅ Setup Complete!

The Job Centres management feature has been successfully added to your admin panel.

## What's Been Added

### 1. Admin Panel Page
- **Location**: `admin/job-centres.php`
- **Features**:
  - View all job centres with pagination
  - Add new job centres manually
  - Edit existing job centres
  - Bulk upload via CSV
  - Advanced filtering (state, category, verified status)
  - Toggle verification and active status
  - Delete job centres
  - Real-time statistics dashboard

### 2. API Endpoint
- **Location**: `admin/api/job-centres-admin.php`
- **Actions**:
  - `add` - Create new job centre
  - `edit` - Update existing job centre
  - `get` - Retrieve job centre data
  - `bulk_upload` - Import multiple job centres from CSV

### 3. Documentation
- **Admin Guide**: `JOB-CENTRES-ADMIN-GUIDE.md` - Complete user manual
- **SQL Script**: `database/verify-job-centres-tables.sql` - Table verification

### 4. Navigation
- Added to admin sidebar under **Content → Job Centres**

## Installation Steps for XAMPP

### Step 1: Verify Database Tables

Open phpMyAdmin or MySQL command line:

```bash
# Windows XAMPP
cd C:\xampp\mysql\bin
mysql -u root -p

# Or use phpMyAdmin at http://localhost/phpmyadmin
```

Then run:

```sql
USE findajob_ng;
SOURCE d:/code/php/XAMPP/htdocs/findajob/database/verify-job-centres-tables.sql
```

**Or** simply copy and paste the SQL from `database/verify-job-centres-tables.sql` into phpMyAdmin SQL tab.

### Step 2: Verify Tables Created

Check if tables exist:

```sql
USE findajob_ng;
SHOW TABLES LIKE 'job_centres%';

-- Should show:
-- job_centres
-- job_centre_reviews
-- job_centre_bookmarks
```

### Step 3: Access the Feature

1. Start XAMPP (Apache + MySQL)
2. Navigate to: `http://localhost/findajob/admin/login.php`
3. Log in with admin credentials
4. Click **"Job Centres"** in the sidebar under Content section

## Testing the Feature

### Test 1: Add a Job Centre Manually

1. Click **"Add Job Centre"** button
2. Fill in the form:
   - Name: `Test Job Centre`
   - Category: `Offline`
   - State: `Lagos`
   - City: `Ikeja`
   - Check "Active Status"
3. Click **"Save Job Centre"**
4. Verify it appears in the table

### Test 2: Bulk Upload

1. Click **"Download CSV Template"** button (or "Bulk Upload" → template link)
2. Save the downloaded CSV file
3. Open it in Excel or a text editor
4. Verify the sample data
5. Click **"Bulk Upload"**
6. Select the CSV file
7. Check "Skip duplicate entries"
8. Click **"Upload CSV"**
9. Review the import results

### Test 3: Edit and Filter

1. Click the edit icon (blue pencil) on any job centre
2. Modify some fields
3. Save changes
4. Use the filter bar to search by state or category
5. Try toggling verification status

## CSV Upload Format

Your CSV file should look like this:

```csv
name,category,description,address,state,city,contact_number,email,website,services,operating_hours,is_verified,is_government,is_active
NDE Abuja,offline,"Government employment agency","Plot 5, Garki",Federal Capital Territory,Abuja,08011111111,info@nde.gov.ng,https://nde.gov.ng,"Job Placement; Training",Mon-Fri: 8AM-4PM,1,1,1
Career Hub,both,"Private recruitment firm","12 Allen Avenue",Lagos,Ikeja,08022222222,info@careerhub.ng,https://careerhub.ng,"Recruitment; CV Writing",Mon-Fri: 9AM-5PM,1,0,1
```

**Important**: 
- Use semicolons (;) to separate services in CSV
- Required columns: name, category, state, city
- Valid categories: online, offline, both
- Boolean fields: 1 or 0

## Troubleshooting

### Issue: "Table doesn't exist" error
**Solution**: Run the SQL script from Step 1 above

### Issue: Can't see "Job Centres" in sidebar
**Solution**: 
1. Clear browser cache (Ctrl+Shift+Del)
2. Ensure you're logged in as admin
3. Check `admin/includes/sidebar.php` was updated

### Issue: CSV upload fails
**Solution**:
1. Verify CSV format matches template
2. Check file size (max 5MB)
3. Ensure all required columns exist
4. Review error messages for specific issues

### Issue: 403 Access Denied
**Solution**: 
1. Ensure you're logged in with admin account
2. Check user_type in database: `SELECT user_type FROM users WHERE id = YOUR_ID`

## Features Available

### Statistics Dashboard
- Total job centres count
- Verified centres count
- Government centres count
- Active centres count

### Filtering Options
- Search by name, city, state
- Filter by state
- Filter by category (online/offline/both)
- Filter by verification status
- Filter by type (government/private)

### Bulk Actions
- Toggle verification status
- Toggle active status
- Delete multiple centres (one at a time)

### CSV Import
- Upload up to 5MB
- Skip duplicates option
- Validation and error reporting
- Import statistics

## Next Steps

1. **Add Real Job Centres**: 
   - Prepare a CSV with Nigerian job centres
   - Government: NDE offices, Labour Ministry offices, ITF centres
   - Private: Recruitment agencies, career centres

2. **Verify Data Quality**:
   - Check for duplicate entries
   - Verify contact information
   - Update operating hours

3. **Enable Frontend Display**:
   - Job seekers can view centres at `pages/user/job-centres.php`
   - Browse and filter by location
   - Read reviews and ratings

4. **Monitor Usage**:
   - Track view counts
   - Monitor ratings
   - Review user feedback

## Security Notes

- Only admin users can access this feature
- All database operations use prepared statements
- CSRF protection on all forms
- Input validation and sanitization
- File upload security checks

## Support

For issues or questions:
- Check `JOB-CENTRES-ADMIN-GUIDE.md` for detailed documentation
- Review error logs in `logs/` directory
- Contact: admin@findajob.ng

---

**Setup Date**: January 6, 2026  
**Status**: ✅ Ready to Use  
**Version**: 1.0.0
