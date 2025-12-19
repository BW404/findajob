-- Mini Jobsite Feature for Employers
-- Creates a branded mini-website for each employer to display their jobs

-- Add mini jobsite settings to employer_profiles table
ALTER TABLE employer_profiles
ADD COLUMN mini_jobsite_enabled TINYINT(1) DEFAULT 1 COMMENT 'Whether mini jobsite is active',
ADD COLUMN mini_jobsite_slug VARCHAR(100) UNIQUE COMMENT 'URL-friendly identifier for mini jobsite',
ADD COLUMN mini_jobsite_theme VARCHAR(20) DEFAULT 'default' COMMENT 'Theme/color scheme for mini jobsite',
ADD COLUMN mini_jobsite_custom_message TEXT COMMENT 'Welcome message on mini jobsite',
ADD COLUMN mini_jobsite_show_contact TINYINT(1) DEFAULT 1 COMMENT 'Show contact information',
ADD COLUMN mini_jobsite_show_social TINYINT(1) DEFAULT 1 COMMENT 'Show social media links',
ADD COLUMN mini_jobsite_views INT DEFAULT 0 COMMENT 'Total views of mini jobsite',
ADD COLUMN mini_jobsite_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN mini_jobsite_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Add social media fields to employer_profiles
ALTER TABLE employer_profiles
ADD COLUMN social_linkedin VARCHAR(255) COMMENT 'LinkedIn profile URL',
ADD COLUMN social_twitter VARCHAR(255) COMMENT 'Twitter profile URL',
ADD COLUMN social_facebook VARCHAR(255) COMMENT 'Facebook page URL',
ADD COLUMN social_instagram VARCHAR(255) COMMENT 'Instagram profile URL';

-- Create index on slug for fast lookups
CREATE INDEX idx_mini_jobsite_slug ON employer_profiles(mini_jobsite_slug);

-- Auto-generate slugs for existing employers
UPDATE employer_profiles 
SET mini_jobsite_slug = CONCAT(
    LOWER(REPLACE(REPLACE(REPLACE(company_name, ' ', '-'), '&', 'and'), '.', '')),
    '-',
    user_id
)
WHERE mini_jobsite_slug IS NULL AND company_name IS NOT NULL;
