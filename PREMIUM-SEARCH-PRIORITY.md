# Premium Job Seeker Priority in Search Results

## Overview
This feature gives premium job seekers priority placement in employer search results, making them more visible to potential employers. Premium users are identified by a "Boosted" badge and always appear at the top of search results.

## Implementation Date
December 19, 2025

## What is a Premium Job Seeker?
A job seeker is considered "premium" if they meet ANY of the following criteria:

1. **Active Pro Subscription**: 
   - `users.subscription_status = 'active'`
   - `users.subscription_plan = 'pro'`
   - `users.subscription_end` is NULL or in the future

2. **Profile Boost Active**:
   - `job_seeker_profiles.profile_boosted = 1`
   - `job_seeker_profiles.profile_boost_until` is NULL or in the future

## Features Implemented

### 1. Priority Placement in CV Search (`pages/company/search-cvs.php`)
- **Premium users always appear first** regardless of sort option
- Sort order is: `Premium First → User's Selected Sort (Newest/Experience/etc.)`
- Visual "Boosted" badge appears next to premium users' names
- Badge has animated pulse effect to draw attention

### 2. Priority in Job Seeker Search API (`api/search.php`)
- Autocomplete search for job seekers prioritizes premium users
- Premium status included in API response (`is_premium` field)
- Used in features like sending private job offers

### 3. Visual Indicators
- **Boosted Badge**: Orange gradient badge with rocket icon (⚡)
- **Badge Styling**: 
  - Background: Linear gradient from `#f59e0b` to `#d97706`
  - Animated pulse effect (2s cycle)
  - Uppercase text with letter spacing
  - Box shadow for depth

## Files Modified

### 1. `pages/company/search-cvs.php`
**Query Changes:**
```sql
-- Added subscription and boost fields to SELECT
u.subscription_status,
u.subscription_plan,
u.subscription_end,
jsp.profile_boosted,
jsp.profile_boost_until,

-- Added is_premium calculation
CASE 
  WHEN u.subscription_status = 'active' 
    AND u.subscription_plan = 'pro' 
    AND (u.subscription_end IS NULL OR u.subscription_end > NOW()) THEN 1
  WHEN jsp.profile_boosted = 1 
    AND (jsp.profile_boost_until IS NULL OR jsp.profile_boost_until > NOW()) THEN 1
  ELSE 0
END as is_premium
```

**Sort Changes:**
```sql
-- All sort options now prioritize premium users first
ORDER BY is_premium DESC, cv.created_at DESC  -- Newest
ORDER BY is_premium DESC, cv.created_at ASC   -- Oldest
ORDER BY is_premium DESC, jsp.years_of_experience DESC  -- Experience High
ORDER BY is_premium DESC, jsp.years_of_experience ASC   -- Experience Low
```

**UI Changes:**
- Added `.boosted-badge` CSS class with animation
- Badge appears next to user's name in search results
- Badge includes rocket icon (🚀) and "BOOSTED" text

### 2. `api/search.php`
**Function: `searchJobSeekers()`**
- Added subscription and boost fields to query
- Added `is_premium` calculated field
- Modified ORDER BY to prioritize premium users: `ORDER BY is_premium DESC, ...`
- Premium status now included in API response

### 3. `pages/company/send-private-offer.php`
**JavaScript Changes:**
- Updated autocomplete results to display "⚡ BOOSTED" badge for premium users
- Badge appears inline next to name in dropdown results
- Maintains compact design for autocomplete

## Database Schema Requirements

### Required Columns in `users` table:
```sql
subscription_status ENUM('free', 'active', 'expired', 'cancelled') DEFAULT 'free'
subscription_plan ENUM('basic', 'pro') DEFAULT 'basic'
subscription_type ENUM('monthly', 'yearly') DEFAULT NULL
subscription_start TIMESTAMP NULL
subscription_end TIMESTAMP NULL
```

### Required Columns in `job_seeker_profiles` table:
```sql
profile_boosted BOOLEAN DEFAULT 0
profile_boost_until TIMESTAMP NULL
verification_boosted BOOLEAN DEFAULT 0  -- Not currently used for priority
verification_boost_date TIMESTAMP NULL  -- Not currently used for priority
```

### Required Indexes:
```sql
CREATE INDEX idx_subscription_status ON users(subscription_status);
CREATE INDEX idx_subscription_end ON users(subscription_end);
CREATE INDEX idx_profile_boosted ON job_seeker_profiles(profile_boosted, profile_boost_until);
```

## Testing

### Test Script: `test-premium-search.php`
Run this script to:
1. Verify database schema is correct
2. Create test premium users (2 premium, 1 free)
3. Test the search query with premium priority
4. Verify premium users appear first

**To run:**
```
http://localhost/findajob/test-premium-search.php
```

### Manual Testing Steps:
1. **Login as an employer**
2. **Navigate to CV Search**: `/pages/company/search-cvs.php`
3. **Verify Premium Priority**:
   - Check that premium users appear at the top
   - Look for the orange "Boosted" badge with rocket icon
   - Try different sort options (Newest, Oldest, Experience)
   - Confirm premium users stay on top regardless of sort

4. **Test Private Offer Search**:
   - Go to Send Private Offer page
   - Search for a job seeker by name
   - Verify premium users appear first in autocomplete
   - Check for "⚡ BOOSTED" badge in results

## CSS Classes

### `.boosted-badge`
```css
.boosted-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.4);
    animation: pulse 2s infinite;
}
```

### `.boosted-icon`
```css
.boosted-icon {
    font-size: 0.85rem;
}
```

### Animation
```css
@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.85;
    }
}
```

## User Experience Flow

### For Premium Job Seekers:
1. User purchases Pro subscription or profile boost
2. Their profile automatically gains priority placement
3. Profile is marked with "Boosted" badge in all employer searches
4. Increased visibility leads to more profile views and job offers

### For Employers:
1. Search for candidates via CV Search or autocomplete
2. Premium candidates appear at the top of results
3. "Boosted" badge indicates serious, committed job seekers
4. Can still sort by other criteria, but premium stays on top

## Business Logic

### Priority Calculation
The `is_premium` field is calculated in real-time during each query:
- Checks if active pro subscription is valid (not expired)
- OR checks if profile boost is active (not expired)
- Returns 1 (premium) or 0 (free)

### Subscription Types:
- **Pro Subscription**: Monthly or yearly payment, managed in `users` table
- **Profile Boost**: One-time or limited-time boost, managed in `job_seeker_profiles` table
- Both types receive the same priority treatment

## Integration Points

### Existing Features That Use This:
1. **CV Search** (`pages/company/search-cvs.php`) - Main search page
2. **Job Seeker Search API** (`api/search.php`) - Autocomplete in various places
3. **Private Job Offers** (`pages/company/send-private-offer.php`) - Candidate selection

### Future Integration Opportunities:
1. **Job Applications View**: Show boosted badge in applicant lists
2. **Recommended Candidates**: Prioritize premium users in AI recommendations
3. **Email Notifications**: Highlight premium applicants to employers
4. **Analytics Dashboard**: Track premium vs. free user performance

## Performance Considerations

### Query Optimization:
- `is_premium` is calculated as part of SELECT, not in WHERE clause
- Indexed fields: `subscription_status`, `subscription_end`, `profile_boosted`
- CASE statement is evaluated once per row, minimal overhead
- Results are sorted efficiently using calculated field

### Caching:
- Premium status changes take effect immediately (no caching)
- No additional database calls required beyond main query

## Maintenance Notes

### To Add More Premium Criteria:
Update the CASE statement in both files:
```sql
CASE 
  -- Existing criteria
  WHEN ... THEN 1
  -- Add new criteria here
  WHEN new_premium_field = 1 THEN 1
  ELSE 0
END as is_premium
```

### To Change Badge Appearance:
- Modify `.boosted-badge` CSS in `search-cvs.php`
- Update inline styles in `send-private-offer.php` JavaScript
- Consider creating shared CSS file for consistency

### To Adjust Priority Logic:
- Current: Premium first, then selected sort
- Alternative: Mix premium throughout (every 3rd result)
- Alternative: Premium section at top, then regular results

## Related Features
- **Payment Integration**: How users become premium (see `PAYMENT-INTEGRATION-COMPLETE.md`)
- **Subscription Management**: Managing pro subscriptions (see `JOB-SEEKER-PRO-FEATURES.md`)
- **Profile Boosting**: One-time boosts (see `database/add-subscription-fields.sql`)

## Known Limitations
1. **No Boost Expiration Notifications**: Users are not notified when boost expires
2. **No Boost Purchase UI**: Profile boost feature exists in DB but no UI to purchase
3. **Badge Not in Job Applications**: Badge only shows in search, not in application views

## Future Enhancements
1. Add boost purchase option in user dashboard
2. Show premium badge in job application views
3. Add analytics tracking for premium user performance
4. Email notifications when boost is about to expire
5. A/B testing different badge designs for effectiveness

## Support & Troubleshooting

### Common Issues:

**Issue**: Premium users not appearing first
- **Check**: Verify subscription_end is in the future or NULL
- **Check**: Verify subscription_status = 'active'
- **Check**: Check profile_boost_until is in the future or NULL

**Issue**: Badge not displaying
- **Check**: Font Awesome is loaded (for rocket icon)
- **Check**: CSS is not being overridden
- **Check**: is_premium field is included in query

**Issue**: Search API not returning premium status
- **Check**: Latest version of `api/search.php` is deployed
- **Check**: Clear browser cache
- **Check**: Check browser console for JavaScript errors

## Conclusion
This feature provides a tangible benefit to premium subscribers by giving them increased visibility in employer searches. The implementation is efficient, scalable, and easily maintainable. The visual badge creates clear differentiation while maintaining a professional appearance.
