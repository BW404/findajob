-- Enhanced Advertisement System
-- Adds Google AdSense support and custom ad code
-- Created: 2026-01-06

-- Add custom_code column for Google AdSense or custom HTML/JS ads
ALTER TABLE advertisements 
ADD COLUMN custom_code TEXT COMMENT 'Custom HTML/JS code for Google AdSense or other ad networks' AFTER image_path,
ADD COLUMN ad_position VARCHAR(50) DEFAULT 'center' COMMENT 'Position: top, center, bottom, left, right' AFTER placement,
ADD COLUMN priority INT DEFAULT 0 COMMENT 'Higher priority ads shown first' AFTER custom_code;

-- Update ad_type to include more options
ALTER TABLE advertisements 
MODIFY ad_type ENUM('banner', 'sidebar', 'inline', 'popup', 'google_adsense', 'custom_code', 'video') NOT NULL DEFAULT 'banner';

-- Update placement to include more locations
ALTER TABLE advertisements
MODIFY placement ENUM('homepage', 'jobs_page', 'job_details', 'dashboard', 'cv_page', 'company_page', 'search_results', 'profile_page', 'all_pages') NOT NULL DEFAULT 'homepage';

-- Add index for priority-based sorting
ALTER TABLE advertisements ADD INDEX idx_priority (priority DESC);

-- Insert Google AdSense settings into site_settings
INSERT INTO site_settings (setting_key, setting_value, setting_type, description) VALUES
('google_adsense_enabled', '0', 'boolean', 'Enable Google AdSense ads'),
('google_adsense_client_id', '', 'string', 'Google AdSense Client ID (ca-pub-XXXXXXXX)'),
('ads_enabled', '1', 'boolean', 'Enable advertisement system'),
('max_ads_per_page', '3', 'integer', 'Maximum number of ads to show per page')
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- Sample Google AdSense ad
INSERT INTO advertisements (
    title, 
    description, 
    ad_type, 
    placement, 
    custom_code,
    ad_position,
    priority,
    start_date, 
    end_date, 
    is_active, 
    created_by
) VALUES (
    'Google AdSense - Homepage Top',
    'Google AdSense banner ad for homepage',
    'google_adsense',
    'homepage',
    '<!-- Google AdSense Code Here -->
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
<ins class="adsbygoogle"
     style="display:block"
     data-ad-client="ca-pub-XXXXXXXX"
     data-ad-slot="XXXXXXXXXX"
     data-ad-format="auto"
     data-full-width-responsive="true"></ins>
<script>
     (adsbygoogle = window.adsbygoogle || []).push({});
</script>',
    'top',
    10,
    CURDATE(),
    DATE_ADD(CURDATE(), INTERVAL 365 DAY),
    0,
    1
);
