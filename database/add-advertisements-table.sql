-- Advertisement Management Table
-- Created: 2025-11-27
-- Purpose: Store and manage platform advertisements

CREATE TABLE IF NOT EXISTS advertisements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    ad_type ENUM('banner', 'sidebar', 'inline', 'popup') NOT NULL DEFAULT 'banner',
    placement ENUM('homepage', 'jobs_page', 'job_details', 'dashboard', 'cv_page', 'company_page') NOT NULL DEFAULT 'homepage',
    image_path VARCHAR(500),
    target_url VARCHAR(500),
    start_date DATE NOT NULL,
    end_date DATE,
    is_active BOOLEAN DEFAULT 1,
    click_count INT DEFAULT 0,
    impression_count INT DEFAULT 0,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_placement (placement),
    INDEX idx_dates (start_date, end_date),
    INDEX idx_active (is_active),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample advertisements for testing
INSERT INTO advertisements (title, description, ad_type, placement, target_url, start_date, end_date, is_active, created_by) VALUES
('Featured Employer Spotlight', 'Discover top companies hiring in Nigeria', 'banner', 'homepage', 'https://findajob.ng/employers', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 1, 1),
('CV Writing Services', 'Professional CV writing services available', 'sidebar', 'dashboard', 'https://findajob.ng/services/cv-generator', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 60 DAY), 1, 1),
('Skill Development Courses', 'Upgrade your skills with online courses', 'inline', 'jobs_page', 'https://findajob.ng/courses', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 90 DAY), 1, 1);
