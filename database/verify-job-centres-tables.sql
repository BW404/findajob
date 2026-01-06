-- Verify and create job_centres table if not exists
-- Run this script to ensure the job centres feature is ready

-- Check if table exists and create if needed
CREATE TABLE IF NOT EXISTS job_centres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category ENUM('online', 'offline', 'both') NOT NULL DEFAULT 'offline',
    description TEXT,
    address TEXT,
    state VARCHAR(100),
    city VARCHAR(100),
    contact_number VARCHAR(20),
    email VARCHAR(255),
    website VARCHAR(500),
    services TEXT COMMENT 'JSON array of services offered',
    operating_hours VARCHAR(255),
    is_verified TINYINT(1) DEFAULT 0,
    is_government TINYINT(1) DEFAULT 0 COMMENT '1 for government, 0 for private',
    logo VARCHAR(255),
    views_count INT DEFAULT 0,
    rating_avg DECIMAL(3,2) DEFAULT 0.00,
    rating_count INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_state (state),
    INDEX idx_city (city),
    INDEX idx_category (category),
    INDEX idx_is_active (is_active),
    INDEX idx_is_verified (is_verified),
    INDEX idx_rating (rating_avg),
    FULLTEXT idx_search (name, description, services)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create job_centre_reviews table if not exists
CREATE TABLE IF NOT EXISTS job_centre_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_centre_id INT NOT NULL,
    user_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review TEXT,
    helpful_count INT DEFAULT 0,
    is_verified_visit TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (job_centre_id) REFERENCES job_centres(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_review (job_centre_id, user_id),
    INDEX idx_job_centre (job_centre_id),
    INDEX idx_rating (rating),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create job_centre_bookmarks table if not exists
CREATE TABLE IF NOT EXISTS job_centre_bookmarks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    job_centre_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (job_centre_id) REFERENCES job_centres(id) ON DELETE CASCADE,
    UNIQUE KEY unique_bookmark (user_id, job_centre_id),
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample government job centres (only if table is empty)
INSERT IGNORE INTO job_centres (name, category, description, address, state, city, contact_number, email, website, services, is_verified, is_government, is_active) 
SELECT * FROM (SELECT 
    'National Directorate of Employment (NDE) Lagos' as name,
    'offline' as category,
    'The National Directorate of Employment (NDE) provides employment and vocational skills training for Nigerian youths and job seekers.' as description,
    'Plot 1, NDE House, Hakeem Balogun Street, Central Business District, Alausa' as address,
    'Lagos' as state,
    'Ikeja' as city,
    '08012345678' as contact_number,
    'lagos@nde.gov.ng' as email,
    'https://www.nde.gov.ng' as website,
    '["Job Placement", "Vocational Training", "Skills Acquisition", "Youth Empowerment", "Career Counseling", "Entrepreneurship Training"]' as services,
    1 as is_verified,
    1 as is_government,
    1 as is_active
) AS tmp
WHERE NOT EXISTS (
    SELECT 1 FROM job_centres WHERE name = 'National Directorate of Employment (NDE) Lagos'
);

-- Verify table creation
SELECT 
    'job_centres table' as table_name,
    COUNT(*) as record_count,
    SUM(CASE WHEN is_government = 1 THEN 1 ELSE 0 END) as government_count,
    SUM(CASE WHEN is_verified = 1 THEN 1 ELSE 0 END) as verified_count,
    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_count
FROM job_centres;

SELECT '✓ Job Centres tables created/verified successfully!' as status;
