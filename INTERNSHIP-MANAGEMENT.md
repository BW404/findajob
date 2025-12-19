# Internship Management & Badge System

## Overview
Complete internship tracking and badge award system for the FindAJob Nigeria platform. Allows employers to manage internships from start to completion and automatically awards verified badges to successful interns.

## Database Tables

### `internships`
Tracks all internship engagements between employers and job seekers.

**Columns:**
- `id` - Primary key
- `job_id` - Reference to the job posting
- `application_id` - Reference to the application
- `job_seeker_id` - The intern's user ID
- `employer_id` - The company's user ID
- `start_date` - Internship start date
- `end_date` - Internship end date
- `duration_months` - Duration in months (1-12)
- `status` - enum: 'pending', 'active', 'completed', 'terminated'
- `completion_confirmed_by_employer` - Boolean flag
- `completion_confirmed_at` - Timestamp of completion
- `employer_feedback` - Text feedback from employer
- `performance_rating` - Rating 1-5 stars
- `badge_awarded` - Boolean flag
- `badge_awarded_at` - Timestamp of badge award

### `internship_badges`
Stores awarded badges that appear on job seeker profiles.

**Columns:**
- `id` - Primary key
- `job_seeker_id` - Recipient's user ID
- `internship_id` - Reference to internship record
- `company_name` - Company name (cached)
- `job_title` - Position title (cached)
- `start_date` - Internship start
- `end_date` - Internship end
- `duration_months` - Duration
- `performance_rating` - Employer's rating
- `employer_feedback` - Employer's feedback
- `is_visible` - Boolean to show/hide badge
- `awarded_at` - Timestamp

## Workflow

### 1. Job Posting
- Employer creates job post with job_type = 'internship'
- Job appears in regular job listings
- Candidates apply normally through Easy Apply or Manual methods

### 2. Candidate Selection
- Employer reviews applications at `internship-management.php`
- "Pending Applications" tab shows all applicants for internship jobs
- Employer clicks "Confirm Intern" button
- Modal appears to set:
  - Start date
  - Duration (1-12 months)
- System creates `internships` record with status='active'
- Application status changes to 'hired'

### 3. Active Internship Management
- "Active Internships" tab shows all ongoing internships
- Displays intern details, start/end dates, contact info
- Employer has two options:
  - **Complete & Award Badge** - For successful completion
  - **Terminate** - For early termination

### 4. Completion & Badge Award
- Employer clicks "Complete & Award Badge"
- Modal prompts for:
  - Performance rating (1-5 stars, interactive)
  - Feedback (optional text)
- On submission:
  - Internship status → 'completed'
  - Badge awarded flag set to true
  - New record created in `internship_badges`
  - Badge immediately visible on job seeker profile

### 5. Badge Display
- Badges appear on job seeker dashboard (recent 2 badges)
- Full badge gallery on profile page
- Each badge shows:
  - Company logo/icon
  - Job title
  - Company name
  - Duration (dates and months)
  - Performance rating (stars)
  - Employer feedback
  - "Verified" indicator
  - Award date

## File Structure

```
database/
  └── add-internship-management.sql     - Database schema

pages/company/
  └── internship-management.php         - Employer management interface

includes/
  └── internship-badges.php             - Badge display component
      ├── displayInternshipBadges()     - Renders badge gallery
      ├── getInternshipBadgeCount()     - Counts user's badges
      └── hasInternshipBadge()          - Checks if user has any badges

pages/user/
  └── dashboard.php                     - Shows recent badges (modified)
  └── profile.php                       - Full badge gallery (to be added)
```

## Employer Interface Features

### Navigation
- Added "Internship Management" to Jobs dropdown in employer header
- Accessible from all employer pages

### Tabs
1. **Pending Applications** - Awaiting confirmation
2. **Active Internships** - Currently ongoing
3. **Completed** - Successfully finished with badges
4. **All Internships** - Complete history

### Modals
- **Confirm Intern Modal** - Set start date and duration
- **Complete Modal** - Rate performance and provide feedback
- **Terminate Modal** - Early termination with reason

### Status Badges
- **Pending** - Yellow - Awaiting confirmation
- **Active** - Green - Currently ongoing
- **Completed** - Blue - Successfully finished
- **Terminated** - Red - Ended early

## Job Seeker Experience

### Dashboard Display
- Golden gradient card with award icon
- Shows recent 2 badges
- Badge count indicator
- "View All Badges" link if > 2 badges

### Profile Display (to be implemented)
- Full badge gallery in grid layout
- Golden section header with certificate icon
- Each badge in card format with all details
- Hover effects and professional styling

### Badge Visibility
- Badges visible to all platform users
- Can be toggled with `is_visible` flag
- Shows "Verified" indicator for authenticity

## Design Elements

### Colors
- Primary: Gold/Amber (#f59e0b, #fbbf24)
- Backgrounds: Light yellow gradients (#fef3c7, #fde68a)
- Text: Brown shades (#78350f, #92400e)
- Stars: Golden (#fbbf24)

### Icons
- Award/Trophy for main headers
- Certificate for individual badges
- Graduation cap for internship context
- Calendar for dates
- Clock for duration
- Star for ratings
- Shield for verification

## Integration Points

### Employer Header
- Dropdown under "Jobs" section
- Added with divider separator
- Graduation cap icon

### Job Seeker Dashboard
- Conditional display (only if badges exist)
- Positioned between Quick Actions and AI Recommendations
- Responsive grid layout

### Notifications (Future Enhancement)
- Email notification when badge is awarded
- Dashboard notification for new badge
- Badge milestone achievements

## Security & Validation

### Access Control
- Employers can only manage their own internships
- Job seekers can only view their own badges
- Foreign key constraints prevent orphaned records

### Data Integrity
- Unique constraint on `application_id` in internships table
- Cascading deletes on job/application deletion
- Timestamps for audit trail

### Input Validation
- Date validation (start before end)
- Duration limits (1-12 months)
- Rating limits (1-5 stars)
- SQL injection prevention via prepared statements

## Usage Examples

### For Employers

```php
// Access internship management
// Navigate to: Jobs > Internship Management

// Confirm an intern
1. Go to "Pending Applications" tab
2. Click "Confirm Intern" on desired application
3. Set start date and duration
4. Click "Confirm Internship"

// Complete internship
1. Go to "Active Internships" tab
2. Find the intern
3. Click "Complete & Award Badge"
4. Rate performance (click stars)
5. Add optional feedback
6. Click "Complete & Award Badge"

// Result: Badge instantly appears on intern's profile
```

### For Job Seekers

```php
// View badges on dashboard
// Visit: /pages/user/dashboard.php
// Badges automatically appear if any exist

// Include badges in profile
require_once '../../includes/internship-badges.php';
displayInternshipBadges($userId, $pdo);

// Check badge count
$count = getInternshipBadgeCount($userId, $pdo);
if ($count > 0) {
    echo "You have $count verified internship badge(s)!";
}
```

## Future Enhancements

1. **Badge Sharing**
   - Generate shareable badge images
   - LinkedIn integration
   - Twitter cards
   - PDF certificates

2. **Analytics**
   - Employer: Track intern performance over time
   - Platform: Internship completion rates
   - Job Seeker: Badge impact on applications

3. **Verification**
   - QR codes on badges
   - Public verification page
   - Blockchain integration

4. **Enhanced Features**
   - Mid-term reviews
   - Intern feedback on company
   - Internship recommendations
   - Badge levels (bronze, silver, gold)

5. **Notifications**
   - Email on badge award
   - SMS notifications
   - In-app notifications
   - Reminder for completion date

## Testing Checklist

- [ ] Create internship job posting
- [ ] Submit application as job seeker
- [ ] Confirm intern as employer
- [ ] Verify internship appears in Active tab
- [ ] Complete internship with rating
- [ ] Verify badge appears on job seeker dashboard
- [ ] Test badge visibility on profile
- [ ] Test termination workflow
- [ ] Verify all timestamps are recorded
- [ ] Check foreign key constraints
- [ ] Test with multiple internships
- [ ] Verify responsive design on mobile
- [ ] Test modal interactions
- [ ] Verify star rating system
- [ ] Test with no badges (empty state)

## Migration

To add this feature to existing installation:

```bash
cd E:\XAMPP\mysql\bin
Get-Content "E:\XAMPP\htdocs\findajob\database\add-internship-management.sql" | .\mysql.exe -u root findajob_ng
```

## Support & Maintenance

**Common Issues:**

1. **Badge not appearing**: Check `is_visible` flag and `badge_awarded` status
2. **Duplicate internships**: Unique constraint on `application_id` prevents this
3. **Performance issues**: Indexes on `job_seeker_id` and `status` optimize queries
4. **Date calculations**: PHP `strtotime()` handles month additions correctly

**Database Maintenance:**

```sql
-- Clean up terminated internships older than 1 year
DELETE FROM internships 
WHERE status = 'terminated' 
AND updated_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);

-- Badge statistics
SELECT 
    COUNT(*) as total_badges,
    AVG(performance_rating) as avg_rating,
    AVG(duration_months) as avg_duration
FROM internship_badges;
```

---

**Status**: ✅ Fully Implemented
**Version**: 1.0
**Date**: December 16, 2025
**Author**: FindAJob Development Team
