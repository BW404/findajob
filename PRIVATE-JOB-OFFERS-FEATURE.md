# Private Job Offers Feature - Complete Implementation

## Overview
Implemented a comprehensive private job offer system that allows employers to send exclusive job offers directly to specific job seekers. This feature enables targeted recruitment without public job postings.

## Database Structure

### Tables Created
1. **private_job_offers** - Stores all private job offer data
2. **private_offer_notifications** - Tracks notifications for both employers and job seekers

### Migration File
- `database/add-private-job-offers.sql` - Complete schema with indexes and foreign keys

## API Endpoint

### File: `api/private-job-offers.php`

**Actions:**
- `create_offer` - Employer creates a new private job offer
- `get_offers` - Retrieve offers (filtered by user type and status)
- `get_offer_details` - Get detailed information about a specific offer
- `respond_to_offer` - Job seeker accepts or rejects an offer
- `withdraw_offer` - Employer withdraws a pending/viewed offer
- `get_unread_count` - Get notification count for badge display

## Employer Features

### 1. Send Private Offer Page
**File:** `pages/company/send-private-offer.php`

**Features:**
- Pre-fill candidate information if coming from applicants page
- Comprehensive form with 6 sections:
  - Job Details (title, type, category, description)
  - Location (state, city, location type)
  - Compensation (salary range, period)
  - Requirements (experience, education, skills)
  - Personal Message (offer message, benefits)
  - Timeline (start date, response deadline)
- Form validation
- Async submission with success/error handling

**Usage:**
```
send-private-offer.php?job_seeker_id=123
```

### 2. Manage Private Offers Page
**File:** `pages/company/private-offers.php`

**Features:**
- Filter offers by status (All, Pending, Viewed, Accepted, Rejected)
- Real-time counts for each status
- Display candidate information with avatar
- Show offer details (job type, salary, location, days left)
- View response messages from candidates
- Withdraw offers that are pending/viewed
- Auto-refresh every 30 seconds

### 3. Integration Points

**Applicants Page** (`pages/company/applicants.php`):
- Added "Send Private Offer" button for each applicant
- Links directly to send-private-offer.php with pre-filled job seeker ID

**Dashboard** (`pages/company/dashboard.php`):
- Added "Private Offers" link to navigation
- Notification badge for new responses

**Header** (`includes/header.php`):
- Added "Private Offers" menu item for employers
- Notification badge support

## Job Seeker Features

### 1. View Private Offers Page
**File:** `pages/user/private-offers.php`

**Features:**
- Filter offers by status (All, New, Viewed, Accepted, Rejected)
- Unread offer highlighting (red left border)
- "NEW" badge for pending offers
- Display company information with logo
- Show offer details (job type, salary, location, deadline)
- Personal message from employer
- Quick accept/decline buttons
- Expiry warnings (⚠️ for offers expiring in 3 days or less)
- Auto-refresh every 30 seconds

### 2. Integration Points

**Header** (`includes/header.php`):
- Added "Private Offers" menu item for job seekers
- Notification badge for new offers

## Key Features

### Offer Lifecycle
1. **Created** → Employer sends offer → Status: `pending`
2. **Viewed** → Job seeker opens offer → Status: `viewed`, notification sent to employer
3. **Accepted** → Job seeker accepts → Status: `accepted`, notification sent to employer
4. **Rejected** → Job seeker declines → Status: `rejected`, notification sent to employer
5. **Expired** → Deadline passes → Status: `expired`
6. **Withdrawn** → Employer cancels → Status: `withdrawn`

### Notifications
- Job seeker notified when new offer is received
- Employer notified when offer is viewed
- Employer notified when offer is accepted/rejected
- Badge counters in navigation

### Data Security
- Authentication required for all endpoints
- User type validation (employers can only create, job seekers can only respond)
- Ownership verification (users can only access their own offers)
- SQL injection prevention (prepared statements)

### UX Enhancements
- Time ago display (e.g., "2 days ago")
- Deadline countdown with warnings
- Status badges with color coding
- Empty states with helpful messages
- Real-time updates without page refresh
- Responsive design

## Usage Example

### Employer Workflow:
1. Browse applicants or search for candidates
2. Click "Send Private Offer" button
3. Fill out offer form with job details
4. Submit offer
5. Monitor responses in "Private Offers" page
6. Receive notification when candidate responds

### Job Seeker Workflow:
1. Receive notification of new private offer
2. Visit "Private Offers" page
3. Review offer details and personal message
4. Accept or decline before deadline
5. Employer is notified of response

## Database Queries

### Get Job Seeker's Pending Offers
```sql
SELECT COUNT(*) 
FROM private_job_offers 
WHERE job_seeker_id = ? AND status = 'pending'
```

### Get Employer's Offer Responses
```sql
SELECT COUNT(*) 
FROM private_offer_notifications 
WHERE user_id = ? AND is_read = 0
```

### Get Offers with Company Info (Job Seeker View)
```sql
SELECT pjo.*, 
       u.first_name, u.last_name, u.email,
       ep.company_name, ep.company_logo, ep.industry, ep.website
FROM private_job_offers pjo
LEFT JOIN users u ON pjo.employer_id = u.id
LEFT JOIN employer_profiles ep ON u.id = ep.user_id
WHERE pjo.job_seeker_id = ?
ORDER BY pjo.created_at DESC
```

## Testing Checklist

### Employer Side:
- [ ] Can access send-private-offer.php
- [ ] Form validates required fields
- [ ] Can send offer to valid job seeker
- [ ] Cannot send to invalid user ID
- [ ] Offers appear in private-offers.php
- [ ] Can filter by status
- [ ] Can withdraw pending/viewed offers
- [ ] Cannot withdraw accepted/rejected offers
- [ ] Receives notifications when offer is viewed
- [ ] Receives notifications when offer is accepted/rejected
- [ ] Notification badge updates correctly

### Job Seeker Side:
- [ ] Can access private-offers.php
- [ ] New offers show "NEW" badge
- [ ] Can view offer details
- [ ] Offer status changes to "viewed" on first view
- [ ] Can accept offer
- [ ] Can decline offer
- [ ] Cannot respond to expired offers
- [ ] Cannot respond twice to same offer
- [ ] Notification badge updates correctly
- [ ] Deadline warnings display correctly

### Security:
- [ ] Unauthenticated users cannot access API
- [ ] Employers cannot respond to offers
- [ ] Job seekers cannot create offers
- [ ] Users cannot access others' offers
- [ ] SQL injection prevented
- [ ] XSS prevented (htmlspecialchars used)

## Files Modified/Created

### Created:
1. `database/add-private-job-offers.sql`
2. `api/private-job-offers.php`
3. `pages/company/send-private-offer.php`
4. `pages/company/private-offers.php`
5. `pages/user/private-offers.php`

### Modified:
1. `pages/company/applicants.php` - Added "Send Private Offer" button
2. `pages/company/dashboard.php` - Added navigation link
3. `includes/header.php` - Added menu items for both user types

## Future Enhancements

### Potential Additions:
1. Email notifications for new offers and responses
2. Offer templates for common positions
3. Bulk offer sending to multiple candidates
4. Counter-offer functionality
5. Interview scheduling integration
6. Offer analytics (acceptance rate, time to respond)
7. Salary negotiation messaging
8. Offer history and archiving
9. SMS notifications for urgent offers
10. Integration with calendar for start dates

## Notes

- Offers expire automatically based on deadline
- Response deadline defaults to 30 days if not specified
- Personal message is optional but recommended
- Salary range is optional (can be marked as "Negotiable")
- All monetary values stored in Naira (NGN)
- Location type can be onsite, remote, or hybrid
- Skills can be listed as comma-separated or line-separated

## Success Metrics

Track these metrics to measure feature success:
1. Number of private offers sent per employer
2. Offer acceptance rate
3. Average time to respond
4. Most common job types for private offers
5. Salary ranges that get highest acceptance
6. Offers by location type (remote vs onsite)
