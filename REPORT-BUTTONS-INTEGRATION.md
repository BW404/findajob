# Report Button Integration - Complete

## Overview
Added "Report to Admin" buttons across all major pages where users interact with content they may want to report.

## Integration Locations

### 1. Job Browse Page (`pages/jobs/browse.php`)
**Location**: Job cards in browse listings  
**Implementation**: 
- Added report button to JavaScript-rendered job cards in `assets/js/job-search.js`
- Button appears next to the save/heart button in job actions
- Reports entire job posting
- Included report modal in browse.php

**Code Pattern**:
```javascript
<button class="report-job-btn" 
        data-job-id="${job.id}" 
        title="Report this job" 
        onclick="event.stopPropagation(); openReportModal('job', ${job.id}, '${this.escapeHtml(job.title)}');">
    <span class="report-icon">⚠️</span>
</button>
```

**CSS Added**:
```css
.report-job-btn {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 1.2rem;
    padding: 0.5rem;
    transition: all 0.2s ease;
    line-height: 1;
    opacity: 0.6;
}

.report-job-btn:hover {
    opacity: 1;
    transform: scale(1.15);
}
```

**Files Modified**:
- `assets/js/job-search.js` - Added report button to renderJobCard()
- `pages/jobs/browse.php` - Included report modal, added CSS

---

### 2. Job Details Page (`pages/jobs/details.php`)
**Location**: Job detail view  
**Implementation**: 
- Report button in prominent location on job details page
- Already implemented previously
- Reports the specific job being viewed

**Code Pattern**:
```php
<button class="btn btn-outline" 
        onclick="openReportModal('job', <?php echo $jobId; ?>, '<?php echo htmlspecialchars(addslashes($job['title'])); ?>')">
    <i class="fas fa-flag"></i> Report Job
</button>
```

---

### 3. CV Search Page (`pages/company/search-cvs.php`)
**Location**: CV card actions (employer's CV search)  
**Implementation**: 
- Report button added after Contact button in CV actions
- Reports the job seeker user account
- Available to employers searching for candidates

**Code Pattern**:
```php
<button class="btn btn-outline" 
        onclick="openReportModal('user', <?php echo $cv['user_id']; ?>, '<?php echo htmlspecialchars(addslashes($cv['full_name'])); ?>')">
    <i class="fas fa-flag"></i> Report
</button>
```

**Files Modified**:
- `pages/company/search-cvs.php` - Added report button, included modal

---

### 4. Applications Page (Job Seeker) (`pages/user/applications.php`)
**Location**: Application card actions  
**Implementation**: 
- Report button in application cards
- Allows job seekers to report problematic job postings from their applications
- Reports the job, not the application itself

**Code Pattern**:
```php
<button class="btn btn-sm btn-outline" 
        onclick="openReportModal('job', <?php echo $application['job_id']; ?>, '<?php echo htmlspecialchars(addslashes($application['job_title'])); ?>')">
    <i class="fas fa-flag"></i> Report Job
</button>
```

**Files Modified**:
- `pages/user/applications.php` - Added report button, included modal

---

### 5. Applicants Page (Employer) (`pages/company/applicants.php`)
**Location**: Applicant action buttons  
**Implementation**: 
- Report button in applicant management interface
- Reports the specific application (suspicious applications, spam, etc.)
- Available to employers reviewing applications

**Code Pattern**:
```php
<button class="btn btn-outline btn-sm" 
        onclick="openReportModal('application', <?php echo $application['id']; ?>, 'Application by <?php echo htmlspecialchars(addslashes($application['applicant_name'])); ?>')">
    <i class="fas fa-flag"></i> Report Application
</button>
```

**Files Modified**:
- `pages/company/applicants.php` - Added report button, included modal

---

## Report Entity Types Used

| Entity Type | Used On Pages | Reports Against |
|------------|--------------|----------------|
| `job` | Job browse, job details, applications (job seeker) | Job postings |
| `user` | CV search | Job seeker accounts |
| `application` | Applicants (employer) | Specific applications |
| `company` | *(Available for future use)* | Employer companies |
| `profile` | *(Available for future use)* | User profiles |

---

## Technical Implementation

### Modal Inclusion Pattern
All pages include the modal before closing body tag:
```php
<!-- Report Modal -->
<?php include '../../includes/report-modal.php'; ?>
```

### JavaScript Function
All buttons call the same global function:
```javascript
openReportModal(entityType, entityId, entityName)
```

### Button Styling
- Uses existing `.btn` classes from main.css
- Icon: Font Awesome `fa-flag` or emoji ⚠️
- Consistent placement near other action buttons
- Prevents event propagation on clickable cards

---

## User Experience Flow

1. **User clicks Report button** on any integrated page
2. **Modal opens** with entity name pre-filled
3. **User selects reason** from 14 predefined options:
   - Spam or misleading content
   - Inappropriate or offensive content
   - Fraudulent job posting
   - Discrimination
   - Salary/compensation issues
   - Fake company or profile
   - Duplicate posting
   - Phishing or scam
   - Harassment
   - Privacy violation
   - Incorrect job information
   - Violation of platform terms
   - Impersonation
   - Other
4. **User writes description** (10-2000 characters, required)
5. **System validates**:
   - Character length
   - Required fields
   - Duplicate prevention (24-hour window)
   - Entity verification
6. **Report submitted** to admin panel
7. **Success animation** and confirmation shown

---

## Admin Integration

Reports appear in admin panel at `admin/reports.php`:
- **Statistics**: Total, pending, under review, resolved, dismissed counts
- **Filtering**: By status, entity type, date range, reporter
- **Actions**: View details, update status, add admin notes, dismiss
- **Workflow**: pending → under_review → resolved/dismissed
- **Reviewer tracking**: Tracks which admin handles each report

---

## Security Features

1. **Authentication Required**: All pages check user login
2. **CSRF Protection**: Forms include CSRF tokens
3. **SQL Injection Prevention**: Prepared statements throughout
4. **XSS Prevention**: All output escaped with htmlspecialchars()
5. **Entity Verification**: API verifies entity exists before accepting report
6. **Rate Limiting**: 24-hour duplicate prevention per user/entity
7. **Input Validation**: Character limits, required fields, enum validation

---

## Files Modified Summary

| File | Changes Made |
|------|-------------|
| `assets/js/job-search.js` | Added report button to job card rendering |
| `pages/jobs/browse.php` | Added CSS for report button, included modal |
| `pages/jobs/details.php` | *(Previously added)* Report button + modal |
| `pages/company/search-cvs.php` | Added report button to CV actions, included modal |
| `pages/user/applications.php` | *(Previously added)* Report button + modal |
| `pages/company/applicants.php` | Added report button to applicant actions, included modal |

---

## Testing Checklist

- [x] Job browse page - report button appears on all job cards
- [x] Job browse page - clicking report opens modal with job info
- [x] Job details page - report button visible and functional
- [x] CV search page - report button appears for each CV
- [x] CV search page - reports user account correctly
- [x] Applications page (job seeker) - can report jobs from applications
- [x] Applicants page (employer) - can report applications
- [x] All modals include entity type and name
- [x] All pages have modal included before </body>
- [x] CSS styling consistent across all implementations
- [x] No JavaScript errors in console
- [x] Report submissions reach admin panel
- [x] Duplicate prevention works (24-hour window)

---

## Future Enhancement Opportunities

1. **Company Profile Reports**: Add report button to company profile pages
2. **User Profile Reports**: Add to public job seeker profile views
3. **Comment/Review Reports**: When comments/reviews are added
4. **Saved Jobs Reports**: Allow reporting from saved jobs list
5. **Notification Reports**: Report inappropriate notifications
6. **Bulk Actions**: Admin ability to handle multiple reports at once
7. **Auto-Actions**: Automatically suspend entities with X reports
8. **Reporter Reputation**: Track false reporting, implement penalties

---

## Notes

- All report buttons use consistent styling and placement
- Entity names are escaped to prevent XSS in modal titles
- Report system is fully functional end-to-end
- Admin panel ready to handle all report types
- System supports 14 predefined report reasons
- 24-hour duplicate prevention prevents spam
- All integrations follow existing code patterns

---

**Status**: ✅ Complete - Report buttons integrated across all major user interaction pages
**Date**: January 2025
**Version**: 1.0
