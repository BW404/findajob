# Advertisement System - Complete Implementation Guide

## Overview
FindAJob Nigeria now has a comprehensive advertisement management system supporting multiple ad formats including banner ads, sidebar ads, inline ads, Google AdSense, and custom HTML/JavaScript code.

## Features
✅ **Multiple Ad Types**:
- Banner ads (large, medium, small)
- Sidebar ads
- Inline content ads
- Google AdSense integration
- Custom HTML/JavaScript ads
- Video ads (prepared)
- Popup ads (prepared)

✅ **Strategic Placements**:
- Homepage
- Job listings page
- Job details
- User dashboard
- CV pages
- Company pages
- Search results
- All pages (global)

✅ **Admin Panel**:
- Full CRUD operations
- Image upload support
- Custom code editor
- Click & impression tracking
- Priority-based display
- Position control (top, center, bottom, left, right)
- Date-based scheduling
- Active/inactive toggle

✅ **Tracking & Analytics**:
- Impression counting
- Click tracking
- Real-time statistics
- Performance metrics

## Database Setup

### Step 1: Run the Enhancement SQL
```bash
# Access MySQL via XAMPP
cd D:\code\php\XAMPP\mysql\bin
.\mysql.exe -u root

USE findajob_ng;
SOURCE D:/code/php/XAMPP/htdocs/findajob/database/enhance-advertisements-system.sql;
```

### Step 2: Verify Tables
```sql
DESCRIBE advertisements;
SELECT * FROM site_settings WHERE setting_key LIKE '%ads%';
```

Expected `advertisements` columns:
- `id`, `title`, `description`
- `ad_type` (banner, sidebar, inline, popup, google_adsense, custom_code, video)
- `placement` (homepage, jobs_page, job_details, dashboard, cv_page, company_page, search_results, profile_page, all_pages)
- `image_path`, `custom_code`, `ad_position`, `priority`
- `target_url`, `start_date`, `end_date`
- `is_active`, `click_count`, `impression_count`
- `created_by`, `created_at`, `updated_at`

## Admin Panel Usage

### Access Ad Manager
1. Navigate to: `http://localhost/findajob/admin/ads.php`
2. Login with admin credentials
3. Click "AD Manager" in sidebar

### Creating Advertisements

#### Option 1: Banner Ad with Image
1. Click "Create Advertisement"
2. Fill in:
   - **Title**: "Premium Employer Spotlight"
   - **Description**: "Discover top companies hiring"
   - **Type**: Banner Ad
   - **Placement**: Homepage
   - **Position**: Top
   - **Priority**: 10 (higher = more important)
   - **Upload Image**: Select banner image (recommended: 1200x250px)
   - **Target URL**: https://findajob.ng/employers
   - **Start Date**: Today
   - **End Date**: 30 days from now
   - **Active**: ✓ Checked
3. Click "Save Advertisement"

#### Option 2: Google AdSense
1. Click "Create Advertisement"
2. Fill in:
   - **Title**: "Google AdSense - Jobs Page"
   - **Type**: Google AdSense
   - **Placement**: Jobs Page
   - **Position**: Center
   - **Custom Code**: Paste your AdSense code:
   ```html
   <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-XXXXXXXX"
        crossorigin="anonymous"></script>
   <ins class="adsbygoogle"
        style="display:block"
        data-ad-client="ca-pub-XXXXXXXX"
        data-ad-slot="1234567890"
        data-ad-format="auto"
        data-full-width-responsive="true"></ins>
   <script>
        (adsbygoogle = window.adsbygoogle || []).push({});
   </script>
   ```
   - **Start Date**: Today
   - **Active**: ✓ Checked
3. Click "Save Advertisement"

#### Option 3: Custom Promotional Ad
1. Click "Create Advertisement"
2. Fill in:
   - **Title**: "CV Writing Service Promo"
   - **Description**: "Get your professional CV today"
   - **Type**: Sidebar Ad
   - **Placement**: Dashboard
   - **Upload Image**: 300x400px promo image
   - **Target URL**: https://findajob.ng/services/cv-generator
   - **Priority**: 5
3. Click "Save Advertisement"

## Integration Points

### Already Integrated Pages
✅ **Homepage** (`index.php`):
- Banner ad after hero section
- Inline ad between features and CTA
- Ad tracking script included

✅ **Job Browse Page** (`pages/jobs/browse.php`):
- 2 sidebar ads in right column
- Ad tracking script included

### How to Add Ads to Other Pages

#### Example: Add to Dashboard
```php
<?php
// At top of file
require_once '../../includes/ad-display.php';

// In sidebar or content area
<?php displayAds('dashboard', 'sidebar', 1); ?>

// At bottom before </body>
<?php includeAdTrackingScript(); ?>
```

#### Example: Add Banner to Job Details
```php
<?php
// After job description
$banner_ads = getActiveAds('job_details', 'banner', 1);
if (!empty($banner_ads)) {
    displayBannerAd($banner_ads[0], 'medium');
}
?>
```

## Ad Display Functions

### Function Reference
```php
// Get active ads for placement
getActiveAds($placement, $ad_type = null, $limit = 5)

// Display specific ad types
displayBannerAd($ad, $size = 'large') // Sizes: large, medium, small
displaySidebarAd($ad)
displayInlineAd($ad)
displayGoogleAd($slot = '', $format = 'auto')

// Display multiple ads automatically
displayAds($placement, $ad_type = null, $count = 1)

// Track interactions
recordAdImpression($ad_id)
recordAdClick($ad_id)

// Include tracking script
includeAdTrackingScript()
```

## Configuration

### Enable Google AdSense Globally
```sql
UPDATE site_settings 
SET setting_value = '1' 
WHERE setting_key = 'google_adsense_enabled';

UPDATE site_settings 
SET setting_value = 'ca-pub-XXXXXXXX' 
WHERE setting_key = 'google_adsense_client_id';
```

### Set Maximum Ads Per Page
```sql
UPDATE site_settings 
SET setting_value = '3' 
WHERE setting_key = 'max_ads_per_page';
```

## Testing

### Test Ad Display
1. **Create Test Ad**:
   - Title: "Test Banner Ad"
   - Type: Banner
   - Placement: Homepage
   - No image (uses gradient fallback)
   - Active: Yes
   - Start Date: Today

2. **Visit Homepage**:
   - Navigate to: `http://localhost/findajob/`
   - Scroll down after hero section
   - Should see gradient banner with "Test Banner Ad" title

3. **Check Tracking**:
   - Open browser DevTools → Network tab
   - Click the ad
   - Should see POST request to `/findajob/api/track-ad.php`
   - Check database: `SELECT click_count, impression_count FROM advertisements WHERE title = 'Test Banner Ad';`

### Test Sidebar Ads
1. Create sidebar ad for Jobs Page
2. Visit: `http://localhost/findajob/pages/jobs/browse.php`
3. Check right sidebar for ad display

### Test Google AdSense
1. Create Google AdSense ad with test code
2. Visit placement page
3. Check page source for AdSense script tags

## Ad Best Practices

### Image Sizes
- **Banner Ads**: 1200x250px (large), 970x150px (medium), 728x90px (small)
- **Sidebar Ads**: 300x400px or 300x250px
- **Inline Ads**: 600x200px

### Priority System
- 10+ : Critical promotions
- 5-9 : Regular paid ads
- 1-4 : Low priority content
- 0 : Default

### Placement Strategy
- **Homepage**: Use for brand awareness, feature announcements
- **Jobs Page**: Target job seekers, premium employer listings
- **Dashboard**: User-specific promotions (CV services, upgrades)
- **Job Details**: Relevant employer promotions, related services

## Monetization Options

### 1. Direct Ad Sales
- Sell banner placements to employers
- Pricing based on position and duration
- Track ROI via click/impression data

### 2. Google AdSense Revenue
- Enable AdSense on high-traffic pages
- Auto-optimize ad formats
- Collect revenue per click/impression

### 3. Sponsored Content
- Use inline ads for promoted content
- Highlight premium employers
- Feature job categories

## Troubleshooting

### Ads Not Displaying
1. Check if ad is active: `SELECT * FROM advertisements WHERE id = X;`
2. Verify dates: Start date ≤ today, end date ≥ today (or NULL)
3. Check placement matches page
4. Ensure `require_once 'includes/ad-display.php';` at top of page

### Images Not Loading
1. Verify uploads folder exists: `uploads/ads/`
2. Check file permissions (755)
3. Verify image path in database starts with `uploads/ads/`

### Tracking Not Working
1. Check if tracking script is included: `includeAdTrackingScript()`
2. Verify API file exists: `api/track-ad.php`
3. Check browser console for JavaScript errors

### Google AdSense Not Showing
1. Verify client ID in site_settings
2. Check AdSense code format
3. Ensure AdSense is enabled in settings
4. Wait 24-48 hours for AdSense approval/activation

## API Endpoints

### Track Ad (POST)
- **URL**: `/findajob/api/track-ad.php`
- **Body**: `{"action": "click", "ad_id": 1}`
- **Actions**: "impression", "click"

## Security Considerations

✅ **Implemented**:
- Permission-based ad management
- File upload validation
- SQL injection protection (prepared statements)
- XSS protection (htmlspecialchars on output)
- Admin-only access to ad manager

⚠️ **Custom Code Warning**:
- Custom HTML/JS ads can execute scripts
- Only allow trusted administrators to create custom code ads
- Consider implementing code review workflow

## Future Enhancements

🔄 **Planned**:
- A/B testing for ad performance
- Geographic targeting (show ads by state/city)
- User segment targeting (job seekers vs employers)
- Automated ad rotation
- Revenue reporting dashboard
- Ad campaign management
- Video ad support (prepared)
- Popup ad implementation (prepared)

## File Structure
```
findajob/
├── includes/
│   └── ad-display.php          # Ad display functions
├── api/
│   └── track-ad.php            # Click/impression tracking
├── admin/
│   └── ads.php                 # Admin panel for ad management
├── database/
│   ├── add-advertisements-table.sql
│   └── enhance-advertisements-system.sql
├── uploads/
│   └── ads/                    # Ad images storage
└── pages/
    ├── index.php               # Homepage with ads
    └── jobs/
        └── browse.php          # Jobs page with ads
```

## Summary
The advertisement system is now fully operational with:
- ✅ Complete admin panel
- ✅ Multiple ad formats
- ✅ Tracking & analytics
- ✅ Google AdSense support
- ✅ Strategic placement integration
- ✅ Priority-based display
- ✅ Custom code support

**Next Steps**:
1. Run the enhancement SQL script
2. Create test advertisements via admin panel
3. Monitor performance and adjust placements
4. Set up Google AdSense for additional revenue
5. Implement monetization strategy

---
**Last Updated**: January 6, 2026
**Status**: Production Ready ✅
