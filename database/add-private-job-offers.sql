-- Private Job Offers Feature
-- Allows employers to send direct job offers to specific job seekers

CREATE TABLE IF NOT EXISTS private_job_offers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employer_id INT NOT NULL,
    job_seeker_id INT NOT NULL,
    
    -- Job Details
    job_title VARCHAR(255) NOT NULL,
    job_description TEXT NOT NULL,
    job_type ENUM('full-time', 'part-time', 'contract', 'internship', 'temporary') NOT NULL,
    category VARCHAR(100),
    
    -- Location
    state VARCHAR(100),
    city VARCHAR(100),
    location_type ENUM('onsite', 'remote', 'hybrid') DEFAULT 'onsite',
    
    -- Compensation
    salary_min DECIMAL(12,2),
    salary_max DECIMAL(12,2),
    salary_currency VARCHAR(10) DEFAULT 'NGN',
    salary_period ENUM('hourly', 'daily', 'weekly', 'monthly', 'yearly') DEFAULT 'monthly',
    
    -- Requirements
    experience_level ENUM('entry', 'intermediate', 'senior', 'expert') DEFAULT 'intermediate',
    education_level VARCHAR(100),
    required_skills TEXT,
    
    -- Offer Details
    offer_message TEXT,
    benefits TEXT,
    start_date DATE,
    deadline DATE,
    
    -- Status
    status ENUM('pending', 'viewed', 'accepted', 'rejected', 'expired', 'withdrawn') DEFAULT 'pending',
    viewed_at DATETIME,
    responded_at DATETIME,
    response_message TEXT,
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at DATETIME,
    
    -- Indexes
    INDEX idx_employer (employer_id),
    INDEX idx_job_seeker (job_seeker_id),
    INDEX idx_status (status),
    INDEX idx_created (created_at),
    
    -- Foreign Keys
    FOREIGN KEY (employer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (job_seeker_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications for private offers
CREATE TABLE IF NOT EXISTS private_offer_notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    offer_id INT NOT NULL,
    user_id INT NOT NULL,
    notification_type ENUM('new_offer', 'offer_viewed', 'offer_accepted', 'offer_rejected', 'offer_expired') NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user (user_id),
    INDEX idx_offer (offer_id),
    INDEX idx_read (is_read),
    
    FOREIGN KEY (offer_id) REFERENCES private_job_offers(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
