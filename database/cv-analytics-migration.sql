-- Add analytics columns to CVs table
ALTER TABLE cvs 
ADD COLUMN IF NOT EXISTS view_count INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS download_count INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS last_viewed_at DATETIME NULL,
ADD COLUMN IF NOT EXISTS last_downloaded_at DATETIME NULL;

-- Create CV analytics tracking table
CREATE TABLE IF NOT EXISTS cv_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cv_id INT NOT NULL,
    action_type ENUM('view', 'download', 'share', 'application') NOT NULL,
    user_id INT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cv_id) REFERENCES cvs(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_cv_id (cv_id),
    INDEX idx_user_id (user_id),
    INDEX idx_action_type (action_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_cv_view_count ON cvs(view_count);
CREATE INDEX IF NOT EXISTS idx_cv_download_count ON cvs(download_count);
CREATE INDEX IF NOT EXISTS idx_cv_last_viewed ON cvs(last_viewed_at);
