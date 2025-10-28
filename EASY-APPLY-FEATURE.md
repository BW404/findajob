# Easy Apply and Manual Apply Feature

## Overview

The application system now supports two distinct application methods that employers can choose when posting jobs:

### 1. **Easy Apply** (Recommended)
- **What it is**: One-click application through the platform
- **How it works**: Job seekers apply instantly with their profile, CV, and a personalized message
- **Benefits**:
  - Fastest application method for job seekers
  - All applications collected in one dashboard
  - Full applicant information captured
  - No external email management needed

### 2. **Manual Apply**
- **What it is**: Traditional application via email or company website
- **How it works**: Employers provide email address, website URL, or custom application link
- **Benefits**:
  - Use existing recruitment processes
  - Redirect to ATS systems
  - Custom application workflows

### 3. **Both Methods**
- Employers can enable both Easy Apply AND Manual Apply simultaneously
- Job seekers choose their preferred method
- Maximum flexibility

---

## Database Changes

### Jobs Table (New Fields)
```sql
- application_type ENUM('easy', 'manual', 'both') DEFAULT 'easy'
- application_instructions TEXT
```

### Job Applications Table (Enhanced Fields)
```sql
- applicant_name VARCHAR(255) -- Full name from Easy Apply form
- applicant_email VARCHAR(255) -- Email from Easy Apply form
- applicant_phone VARCHAR(20) -- Phone number
- application_message TEXT -- Message/cover letter from applicant
- resume_file_path VARCHAR(500) -- Path to CV file
```

**Migration Files:**
- `database/add-application-type.sql` - Adds application type to jobs table
- `database/enhance-applications-table.sql` - Adds Easy Apply fields to applications table
- `database/run-application-migration.php` - Run this file to apply migrations

---

## Updated Files

### 1. Job Posting Form (`pages/company/post-job.php`)

**New Section: Application Settings**
- Radio buttons to select: Easy Apply, Manual Apply, or Both
- Conditional fields for Manual Apply:
  - Application Email (optional)
  - Application URL/Website (optional)
  - Application Instructions (optional)
- JavaScript to show/hide fields based on selection
- Visual indicators and helpful descriptions

**Key Changes:**
- Added `application_type` field to form data
- Added `application_email`, `application_url`, `application_instructions` to SQL INSERT
- Added JavaScript `updateApplicationFields()` function
- Added CSS styling for `.application-type-option` radio cards

### 2. Job Details Page (`pages/jobs/details.php`)

**Dynamic Application Display:**
- Checks `$job['application_type']` to determine display
- **Easy Apply**: Shows "✨ Easy Apply" button linking to apply.php
- **Manual Apply**: Shows instruction box with email/website links
- **Both**: Shows Easy Apply button + "or" separator + manual options
- "Easy Apply" badge on easy apply applications
- Color-coded application instructions box

### 3. Easy Apply Page (`pages/jobs/apply.php`)

**Complete Application Form:**
- Full name (pre-filled from profile)
- Email address (pre-filled from profile)
- Phone number (pre-filled if available)
- Cover letter / Message (minimum 20 characters)
- CV attachment (automatically includes primary CV)
- Warning if no CV uploaded
- Submit directly to employer dashboard

**Features:**
- Form validation with error messages
- Auto-population from user profile
- CV detection and display
- Responsive design
- Cancel button returns to job details

### 4. Applicants Dashboard (`pages/company/applicants.php`)

**Enhanced Display:**
- Shows "Easy Apply" badge for Easy Apply submissions
- Displays applicant name (from form if Easy Apply, else from profile)
- Shows applicant email (from form if Easy Apply, else from profile)
- Shows applicant phone (from form if Easy Apply, else from profile)
- Displays application message (from Easy Apply) or cover letter
- Lists CV title if attached
- All existing features maintained (status updates, filtering, etc.)

**Visual Indicators:**
- Green "Easy Apply" badge next to applicant name
- "Application Message" label for Easy Apply vs "Cover Letter" for others

---

## User Flows

### For Employers (Posting Jobs)

1. **Go to Post Job page**
2. **Fill in job details** (Step 1: Basic Info)
3. **In Step 2 (Requirements)**, scroll to "Application Settings"
4. **Choose application method:**
   - ✨ **Easy Apply**: No additional setup needed
   - 📧 **Manual Apply**: Provide email and/or website URL
   - 🔄 **Both**: Provide manual options (Easy Apply enabled by default)
5. **Publish job**

### For Job Seekers (Applying)

#### Easy Apply Flow:
1. **Click job listing** → View Details
2. **Click "✨ Easy Apply" button**
3. **Review pre-filled information** (name, email, phone)
4. **Write cover letter/message** (minimum 20 characters)
5. **Click "Submit Application"**
6. **Confirmation**: Redirected to job details with success message

#### Manual Apply Flow:
1. **Click job listing** → View Details
2. **See "How to Apply" box** with:
   - Email address (clickable mailto: link)
   - Company website link (opens in new tab)
   - Custom instructions
3. **Apply using provided method**

---

## Technical Implementation

### Frontend (JavaScript)
```javascript
// In post-job.php
function updateApplicationFields() {
    const selectedType = document.querySelector('input[name="application_type"]:checked').value;
    const manualFieldsDiv = document.getElementById('manual-apply-fields');
    
    // Show manual fields if 'manual' or 'both' selected
    if (selectedType === 'manual' || selectedType === 'both') {
        manualFieldsDiv.style.display = 'block';
    } else {
        manualFieldsDiv.style.display = 'none';
    }
    
    // Update visual styles for selected option
    // ... (border color, background)
}
```

### Backend (PHP)
```php
// In apply.php - Process Easy Apply
$insert = $pdo->prepare("
    INSERT INTO job_applications (
        job_id, job_seeker_id, cv_id,
        applicant_name, applicant_email, applicant_phone,
        application_message, application_status,
        applied_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'applied', NOW())
");

$insert->execute([
    $jobId, $userId, $cvId,
    $applicantName, $applicantEmail, $applicantPhone,
    $applicationMessage
]);
```

### Display Logic (details.php)
```php
$appType = $job['application_type'] ?? 'easy';

if ($appType === 'easy') {
    // Show Easy Apply button
} elseif ($appType === 'manual') {
    // Show manual application instructions
} elseif ($appType === 'both') {
    // Show both options
}
```

---

## Testing Checklist

### Database Migration
- [ ] Run `run-application-migration.php` successfully
- [ ] Verify `application_type` column exists in `jobs` table
- [ ] Verify `applicant_name`, `applicant_email`, `applicant_phone`, `application_message` columns exist in `job_applications` table

### Post Job (Employer)
- [ ] Select "Easy Apply" → Manual fields hidden
- [ ] Select "Manual Apply" → Manual fields shown
- [ ] Select "Both" → Manual fields shown
- [ ] Submit job with Easy Apply → Saves correctly
- [ ] Submit job with Manual Apply + email → Saves correctly
- [ ] Submit job with Manual Apply + URL → Saves correctly
- [ ] Submit job with Both options → Saves correctly

### Job Details (Job Seeker)
- [ ] Easy Apply job → Shows "Easy Apply" button
- [ ] Manual Apply job with email → Shows email link
- [ ] Manual Apply job with URL → Shows website button
- [ ] Manual Apply job with instructions → Shows instruction text
- [ ] Both options job → Shows Easy Apply + manual options
- [ ] Already applied → Shows "Already Applied" (disabled)

### Easy Apply Form
- [ ] Click Easy Apply → Redirects to apply.php
- [ ] Form pre-fills name, email, phone from profile
- [ ] CV detected and displayed (if available)
- [ ] No CV warning shown (if no CV)
- [ ] Submit with empty message → Shows error
- [ ] Submit with short message (<20 chars) → Shows error
- [ ] Submit valid form → Success, redirects to details page
- [ ] Cancel button → Returns to job details

### Applicants Dashboard (Employer)
- [ ] Easy Apply applications show "Easy Apply" badge
- [ ] Shows applicant name from Easy Apply form
- [ ] Shows applicant email from Easy Apply form
- [ ] Shows applicant phone from Easy Apply form
- [ ] Displays application message correctly
- [ ] Shows CV title if attached
- [ ] "Read more" works for long messages
- [ ] View CV button works
- [ ] Status updates work correctly
- [ ] Filter by job works

### Edge Cases
- [ ] Job with no application_type → Defaults to 'easy'
- [ ] User not logged in → Redirects to login
- [ ] Already applied → Cannot apply again
- [ ] Expired job → Cannot apply
- [ ] Job with NULL email and URL (manual) → Still shows instructions if provided

---

## Benefits

### For Employers
✅ **Flexibility**: Choose the application method that fits your workflow
✅ **Centralized**: All Easy Apply submissions in one dashboard
✅ **Complete Data**: Full applicant information captured automatically
✅ **Time-Saving**: No need to check multiple email accounts
✅ **Professional**: Modern, streamlined hiring process

### For Job Seekers
✅ **Speed**: Apply to jobs in under 30 seconds with Easy Apply
✅ **Convenience**: Profile and CV automatically attached
✅ **Choice**: Select manual apply if preferred
✅ **Transparency**: Clear application instructions
✅ **Track Applications**: View status in dashboard

---

## Future Enhancements (Optional)

1. **Email Notifications**
   - Notify employer immediately on Easy Apply submission
   - Send confirmation email to job seeker

2. **Application Analytics**
   - Track Easy Apply vs Manual Apply conversion rates
   - Show which method gets more applications

3. **Bulk Actions**
   - Accept/reject multiple Easy Apply applications at once
   - Export Easy Apply applications to CSV

4. **Application Responses**
   - Employer can message applicants directly
   - In-platform communication

5. **Smart Matching**
   - Auto-score Easy Apply applications based on requirements
   - Highlight top candidates

---

## Support

If you encounter any issues:

1. **Check database migration**: Ensure both SQL files were executed successfully
2. **Clear browser cache**: Force refresh (Ctrl+F5) on updated pages
3. **Check error logs**: Look in PHP error logs for any database errors
4. **Verify file permissions**: Ensure `apply.php` is readable and executable

## Version
- **Feature Added**: October 23, 2025
- **Version**: 1.0
- **Status**: Production Ready ✅
