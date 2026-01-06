-- Job Centres Feature Migration
-- Date: January 6, 2026
-- Purpose: Add Job Centre directory for job seekers

-- Create job_centres table
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

-- Create job_centre_reviews table
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

-- Create job_centre_bookmarks table
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

-- Insert sample job centres (Government)
INSERT INTO job_centres (name, category, description, address, state, city, contact_number, email, website, services, is_verified, is_government) VALUES
('National Directorate of Employment (NDE) Lagos', 'offline', 'The National Directorate of Employment (NDE) provides employment and vocational skills training for Nigerian youths and job seekers.', 'Plot 1, NDE House, Hakeem Balogun Street, Central Business District, Alausa', 'Lagos', 'Ikeja', '08012345678', 'lagos@nde.gov.ng', 'https://www.nde.gov.ng', '["Job Placement", "Vocational Training", "Skills Acquisition", "Youth Empowerment", "Career Counseling", "Entrepreneurship Training"]', 1, 1),

('Federal Ministry of Labour and Employment - Lagos Office', 'offline', 'Government ministry responsible for labour, employment and productivity matters in Nigeria.', 'Western Avenue, Surulere', 'Lagos', 'Surulere', '08023456789', 'info@labour.gov.ng', 'https://www.labour.gov.ng', '["Job Registration", "Employment Advisory", "Labour Market Information", "Skills Certification", "Job Matching"]', 1, 1),

('NDE Abuja Headquarters', 'offline', 'National headquarters of the National Directorate of Employment providing nationwide coordination of employment programs.', 'No. 16 Dunukofia Street, off Ahmadu Bello Way, Area 11, Garki', 'Federal Capital Territory', 'Abuja', '09012345678', 'info@nde.gov.ng', 'https://www.nde.gov.ng', '["Job Placement", "Vocational Training", "Graduate Training", "Rural Employment", "Special Public Works"]', 1, 1),

('Industrial Training Fund (ITF) - Lagos', 'offline', 'Promotes and encourages the acquisition of skills in industry and commerce with a view to generating a pool of skilled personnel.', '15 Idowu Taylor Street, Victoria Island', 'Lagos', 'Victoria Island', '08034567890', 'lagos@itf.gov.ng', 'https://www.itf.gov.ng', '["Skills Training", "Industrial Attachment", "Apprenticeship Programs", "Technical Training", "Entrepreneurship Development"]', 1, 1);

-- Insert sample job centres (Private)
INSERT INTO job_centres (name, category, description, address, state, city, contact_number, email, website, services, is_verified, is_government) VALUES
('Jobberman Nigeria', 'online', 'Nigeria\'s leading online job portal connecting job seekers with employers across various industries.', 'Online Platform', 'Lagos', 'Online', '09087654321', 'support@jobberman.com', 'https://www.jobberman.com', '["Online Job Listings", "CV Writing Services", "Career Advice", "Skills Assessment", "Job Alerts"]', 1, 0),

('Workforce Group', 'both', 'Leading recruitment and HR consulting firm providing comprehensive employment solutions.', '235 Ikorodu Road, Ilupeju', 'Lagos', 'Ilupeju', '08045678901', 'info@workforcegroup.com', 'https://www.workforcegroup.com', '["Executive Search", "Recruitment Services", "HR Consulting", "Career Counseling", "Skills Training"]', 1, 0),

('Dragnet Solutions', 'both', 'Technology-driven recruitment and HR solutions company.', '15A Akin Adesola Street, Victoria Island', 'Lagos', 'Victoria Island', '08056789012', 'contact@dragnetsolutions.com', 'https://www.dragnetsolutions.com', '["Talent Acquisition", "Background Checks", "Psychometric Testing", "HR Technology", "Outsourcing"]', 1, 0),

('Michael Stevens Consulting', 'both', 'Premium recruitment and HR consulting firm specializing in executive placements.', '5th Floor, NIDB House, 18 Muhammadu Buhari Way, Central Business District', 'Federal Capital Territory', 'Abuja', '08067890123', 'abuja@michaelstevens-consulting.com', 'https://www.michaelstevens-consulting.com', '["Executive Search", "Middle Management Recruitment", "Graduate Placement", "Career Advisory"]', 1, 0),

('Career Clinic Nigeria', 'offline', 'Professional career development and job placement centre.', '12 Admiralty Way, Lekki Phase 1', 'Lagos', 'Lekki', '08078901234', 'info@careerclinic.ng', 'https://www.careerclinic.ng', '["Career Counseling", "CV Review", "Interview Preparation", "Job Placement", "Skill Training"]', 1, 0);

-- Update trigger to maintain rating averages
DELIMITER //
CREATE TRIGGER update_job_centre_rating_after_insert
AFTER INSERT ON job_centre_reviews
FOR EACH ROW
BEGIN
    UPDATE job_centres
    SET 
        rating_avg = (SELECT AVG(rating) FROM job_centre_reviews WHERE job_centre_id = NEW.job_centre_id),
        rating_count = (SELECT COUNT(*) FROM job_centre_reviews WHERE job_centre_id = NEW.job_centre_id)
    WHERE id = NEW.job_centre_id;
END//

CREATE TRIGGER update_job_centre_rating_after_update
AFTER UPDATE ON job_centre_reviews
FOR EACH ROW
BEGIN
    UPDATE job_centres
    SET 
        rating_avg = (SELECT AVG(rating) FROM job_centre_reviews WHERE job_centre_id = NEW.job_centre_id),
        rating_count = (SELECT COUNT(*) FROM job_centre_reviews WHERE job_centre_id = NEW.job_centre_id)
    WHERE id = NEW.job_centre_id;
END//

CREATE TRIGGER update_job_centre_rating_after_delete
AFTER DELETE ON job_centre_reviews
FOR EACH ROW
BEGIN
    UPDATE job_centres
    SET 
        rating_avg = COALESCE((SELECT AVG(rating) FROM job_centre_reviews WHERE job_centre_id = OLD.job_centre_id), 0),
        rating_count = (SELECT COUNT(*) FROM job_centre_reviews WHERE job_centre_id = OLD.job_centre_id)
    WHERE id = OLD.job_centre_id;
END//
DELIMITER ;
