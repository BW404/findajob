-- Add reports table for user-submitted reports to admin
-- Created: 2025-12-20

CREATE TABLE IF NOT EXISTS reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    reporter_id INT NOT NULL,
    reporter_type ENUM('job_seeker', 'employer') NOT NULL,
    reported_entity_type ENUM('job', 'user', 'company', 'application', 'other') NOT NULL,
    reported_entity_id INT DEFAULT NULL,
    reason ENUM(
        'fake_profile',
        'fake_job',
        'inappropriate_content',
        'harassment',
        'spam',
        'scam',
        'misleading_information',
        'copyright_violation',
        'discrimination',
        'offensive_language',
        'duplicate_posting',
        'privacy_violation',
        'payment_issues',
        'other'
    ) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('pending', 'under_review', 'resolved', 'dismissed') DEFAULT 'pending',
    admin_notes TEXT,
    reviewed_by INT DEFAULT NULL,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_reporter_id (reporter_id),
    INDEX idx_reported_entity (reported_entity_type, reported_entity_id),
    INDEX idx_status (status),
    INDEX idx_reason (reason),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add report_count to track users/entities with multiple reports
ALTER TABLE users
ADD COLUMN IF NOT EXISTS report_count INT DEFAULT 0 AFTER is_active;

ALTER TABLE jobs
ADD COLUMN IF NOT EXISTS report_count INT DEFAULT 0 AFTER saved_count;

-- Create indexes for report counts
CREATE INDEX IF NOT EXISTS idx_user_report_count ON users(report_count);
CREATE INDEX IF NOT EXISTS idx_job_report_count ON jobs(report_count);

-- Verify the changes
SELECT 'Reports table created successfully!' as status;
DESCRIBE reports;
