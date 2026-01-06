# Job Centre Directory - Quick Reference Guide

## Feature Overview
A comprehensive directory of government and private employment centres across Nigeria, helping job seekers find career support services.

---

## Quick Access

### URLs
- **Listing Page**: `/pages/user/job-centres.php`
- **Details Page**: `/pages/user/job-centre-details.php?id={centre_id}`
- **API Endpoint**: `/api/job-centres.php`

### Navigation
- **Header Menu**: Dashboard → Browse Jobs → **Job Centres**
- **Footer**: For Job Seekers section

---

## Database Quick Reference

### Tables
```sql
-- Main centres table
SELECT * FROM job_centres WHERE is_active = 1;

-- Reviews
SELECT * FROM job_centre_reviews WHERE job_centre_id = ?;

-- User bookmarks
SELECT * FROM job_centre_bookmarks WHERE user_id = ?;
```

### Sample Queries

#### Get All Active Centres
```sql
SELECT id, name, category, city, state, rating_avg, rating_count, views_count
FROM job_centres 
WHERE is_active = 1 
ORDER BY rating_avg DESC;
```

#### Get Government Centres by State
```sql
SELECT * FROM job_centres 
WHERE is_government = 1 
AND state = 'Lagos' 
AND is_active = 1;
```

#### Get Centre with Reviews
```sql
SELECT jc.*, 
       COUNT(jcr.id) as review_count,
       AVG(jcr.rating) as avg_rating
FROM job_centres jc
LEFT JOIN job_centre_reviews jcr ON jc.id = jcr.job_centre_id
WHERE jc.id = ?
GROUP BY jc.id;
```

#### Get User's Bookmarked Centres
```sql
SELECT jc.* 
FROM job_centres jc
INNER JOIN job_centre_bookmarks jcb ON jc.id = jcb.job_centre_id
WHERE jcb.user_id = ?
ORDER BY jcb.created_at DESC;
```

---

## API Quick Reference

### Base URL
`/api/job-centres.php`

### Actions

#### 1. List Centres
```javascript
// GET /api/job-centres.php?action=list
fetch('/api/job-centres.php?action=list&state=Lagos&sort=rating')
  .then(res => res.json())
  .then(data => console.log(data.centres));
```

#### 2. Search Centres
```javascript
// GET /api/job-centres.php?action=search&query=NDE
fetch('/api/job-centres.php?action=search&query=training')
  .then(res => res.json())
  .then(data => console.log(data.centres));
```

#### 3. Bookmark Centre
```javascript
// POST /api/job-centres.php
const formData = new FormData();
formData.append('action', 'bookmark');
formData.append('centre_id', 1);

fetch('/api/job-centres.php', {
  method: 'POST',
  body: formData
})
  .then(res => res.json())
  .then(data => console.log(data.success));
```

#### 4. Add Review
```javascript
// POST /api/job-centres.php
const formData = new FormData();
formData.append('action', 'add_review');
formData.append('centre_id', 1);
formData.append('rating', 5);
formData.append('review_text', 'Excellent service!');

fetch('/api/job-centres.php', {
  method: 'POST',
  body: formData
})
  .then(res => res.json())
  .then(data => console.log(data.review));
```

---

## Common Tasks

### Add a New Job Centre

#### Via SQL
```sql
INSERT INTO job_centres (
  name, category, description, address, state, city,
  contact_number, email, website, services,
  is_government, is_verified, is_active
) VALUES (
  'New Centre Name',
  'offline',
  'Centre description',
  '123 Main Street',
  'Lagos',
  'Ikeja',
  '08012345678',
  'info@centre.com',
  'https://centre.com',
  '["Job Placement", "Training"]',
  0,
  1,
  1
);
```

#### Services Field (JSON)
```json
[
  "Job Placement",
  "Vocational Training",
  "Career Counseling",
  "Entrepreneurship Development",
  "Skills Assessment",
  "Resume Services"
]
```

### Update Centre Information
```sql
UPDATE job_centres 
SET 
  operating_hours = 'Monday-Friday: 9:00 AM - 5:00 PM',
  contact_number = '08098765432',
  is_verified = 1
WHERE id = 1;
```

### Moderate Reviews
```sql
-- Get all reviews for moderation
SELECT jcr.*, u.first_name, u.last_name, jc.name as centre_name
FROM job_centre_reviews jcr
JOIN users u ON jcr.user_id = u.id
JOIN job_centres jc ON jcr.job_centre_id = jc.id
ORDER BY jcr.created_at DESC;

-- Delete inappropriate review
DELETE FROM job_centre_reviews WHERE id = ?;

-- Note: Triggers will auto-update centre ratings
```

### Get Statistics
```sql
-- Total centres by type
SELECT 
  CASE WHEN is_government = 1 THEN 'Government' ELSE 'Private' END as type,
  COUNT(*) as total
FROM job_centres
WHERE is_active = 1
GROUP BY is_government;

-- Most reviewed centres
SELECT jc.name, COUNT(jcr.id) as review_count
FROM job_centres jc
LEFT JOIN job_centre_reviews jcr ON jc.id = jcr.job_centre_id
GROUP BY jc.id
ORDER BY review_count DESC
LIMIT 10;

-- Most bookmarked centres
SELECT jc.name, COUNT(jcb.id) as bookmark_count
FROM job_centres jc
LEFT JOIN job_centre_bookmarks jcb ON jc.id = jcb.job_centre_id
GROUP BY jc.id
ORDER BY bookmark_count DESC
LIMIT 10;
```

---

## Troubleshooting

### Issue: Ratings Not Updating
**Solution**: Check if triggers are active
```sql
SHOW TRIGGERS LIKE 'job_centre_reviews';
```

### Issue: Centres Not Showing
**Check**:
1. `is_active = 1`
2. User is logged in (job seeker)
3. API returning data: `/api/job-centres.php?action=list`

### Issue: Can't Submit Review
**Check**:
1. User is logged in
2. User hasn't already reviewed this centre
3. Rating is between 1-5

### Issue: Bookmark Not Working
**Check**:
1. User is logged in as job seeker
2. Unique constraint allows only one bookmark per user per centre

---

## Maintenance Commands

### Backup Job Centres Data
```bash
cd /opt/lampp
./bin/mysqldump -u root findajob_ng job_centres job_centre_reviews job_centre_bookmarks > job_centres_backup.sql
```

### Restore Job Centres Data
```bash
cd /opt/lampp
./bin/mysql -u root findajob_ng < job_centres_backup.sql
```

### Reset All Ratings (Recalculate)
```sql
UPDATE job_centres jc
SET 
  rating_avg = COALESCE((
    SELECT AVG(rating) 
    FROM job_centre_reviews 
    WHERE job_centre_id = jc.id
  ), 0),
  rating_count = (
    SELECT COUNT(*) 
    FROM job_centre_reviews 
    WHERE job_centre_id = jc.id
  );
```

### Clear Old View Counts
```sql
UPDATE job_centres SET views_count = 0;
```

---

## Feature Flags

### Current Settings
- ✅ Authentication required for bookmarks and reviews
- ✅ Job seekers only (employers redirected)
- ✅ One review per user per centre
- ✅ Auto-update ratings via triggers
- ✅ View tracking enabled

### To Disable Authentication (Testing Only)
Edit `pages/user/job-centres.php`:
```php
// Comment out these lines for testing
// if (!isLoggedIn()) {
//     header('Location: ../auth/login.php');
//     exit;
// }
```

**⚠️ WARNING**: Always re-enable authentication in production!

---

## Performance Tips

### Index Usage
```sql
-- Verify indexes are being used
EXPLAIN SELECT * FROM job_centres WHERE state = 'Lagos';
EXPLAIN SELECT * FROM job_centre_reviews WHERE job_centre_id = 1;
```

### Cache Popular Centres
Consider caching in Redis/Memcached:
- Top 10 rated centres
- Most viewed centres
- Recent centres

### Optimize Queries
```sql
-- Good: Specific columns
SELECT id, name, rating_avg FROM job_centres WHERE is_active = 1;

-- Avoid: SELECT *
SELECT * FROM job_centres; -- Slow with large datasets
```

---

## Integration Examples

### Add to User Dashboard
```php
// Get user's bookmarked centres count
$stmt = $pdo->prepare("
  SELECT COUNT(*) 
  FROM job_centre_bookmarks 
  WHERE user_id = ?
");
$stmt->execute([$userId]);
$bookmarked_count = $stmt->fetchColumn();

echo "You have {$bookmarked_count} bookmarked centres";
```

### Email Notifications (Future)
```php
// When user bookmarks a centre
sendEmail(
  $user_email,
  'Centre Bookmarked',
  "You bookmarked: {$centre_name}"
);
```

### Analytics Tracking
```javascript
// Track centre views in Google Analytics
gtag('event', 'view_centre', {
  'centre_id': centreId,
  'centre_name': centreName,
  'centre_type': centreType
});
```

---

## Sample Nigerian States

Quick reference for state names (from `nigeria_states` table):
- Lagos
- Abuja (FCT)
- Kano
- Rivers
- Oyo
- Delta
- Edo
- Kaduna
- Anambra
- Enugu
- Ogun
- Ondo
- Osun
- Kwara
- Akwa Ibom
- Cross River
- Benue
- Plateau
- Niger
- Bauchi
- Katsina
- Sokoto
- Kebbi
- Zamfara
- Gombe
- Yobe
- Borno
- Adamawa
- Taraba
- Nasarawa
- Kogi
- Imo
- Abia
- Ebonyi
- Ekiti
- Bayelsa
- Jigawa

---

## Testing Scenarios

### Manual Testing Checklist

1. **Browse Centres**
   - [ ] Load listing page
   - [ ] Search for centres
   - [ ] Filter by state
   - [ ] Filter by category
   - [ ] Sort by rating
   - [ ] Click centre card

2. **Centre Details**
   - [ ] View full centre info
   - [ ] Click contact buttons
   - [ ] Read reviews
   - [ ] Submit review
   - [ ] Bookmark centre

3. **User Interactions**
   - [ ] Bookmark/unbookmark works
   - [ ] Review submission works
   - [ ] Can't submit duplicate review
   - [ ] Rating updates after review

4. **Edge Cases**
   - [ ] No search results
   - [ ] No reviews yet
   - [ ] Invalid centre ID
   - [ ] Not logged in
   - [ ] Employer accessing page

---

## Configuration

### Default Settings
```php
// In api/job-centres.php
define('CENTRES_PER_PAGE', 20);
define('REVIEWS_PER_PAGE', 10);
define('MAX_SERVICES_DISPLAY', 3);
```

### Customize Display
Edit `pages/user/job-centres.php`:
```javascript
// Change results per page
const CENTRES_PER_PAGE = 20; // Change to 30, 50, etc.

// Change default sort
filters.sort = 'rating'; // or 'recent', 'reviews', 'views'
```

---

## Security Checklist

- ✅ SQL injection prevention (prepared statements)
- ✅ XSS protection (htmlspecialchars)
- ✅ CSRF protection on forms
- ✅ Authentication required for actions
- ✅ User type validation (job seekers only)
- ✅ Input sanitization
- ✅ Rate limiting (one review per user)

---

## Support

### Log Files
- Error logs: `/opt/lampp/htdocs/findajob/logs/`
- Check for API errors in browser console

### Debug Mode
Enable in `config/constants.php`:
```php
define('DEV_MODE', true);
```

### Common Errors
- **404**: Check file paths
- **403**: Check authentication
- **500**: Check error logs
- **Empty results**: Check database connection

---

**Last Updated**: January 6, 2026  
**Version**: 1.0.0  
**Status**: Production Ready ✅
