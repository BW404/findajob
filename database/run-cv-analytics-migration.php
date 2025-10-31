<?php
require_once '../config/database.php';

try {
    echo "Running CV Analytics Migration...\n\n";
    
    // Add analytics columns to CVs table
    echo "1. Adding analytics columns to CVs table...\n";
    $pdo->exec("
        ALTER TABLE cvs 
        ADD COLUMN IF NOT EXISTS view_count INT DEFAULT 0,
        ADD COLUMN IF NOT EXISTS download_count INT DEFAULT 0,
        ADD COLUMN IF NOT EXISTS last_viewed_at DATETIME NULL,
        ADD COLUMN IF NOT EXISTS last_downloaded_at DATETIME NULL
    ");
    echo "   ✓ Analytics columns added\n\n";
    
    // Create CV analytics tracking table
    echo "2. Creating CV analytics tracking table...\n";
    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ✓ CV analytics table created\n\n";
    
    // Add indexes for better performance
    echo "3. Adding performance indexes...\n";
    try {
        $pdo->exec("CREATE INDEX idx_cv_view_count ON cvs(view_count)");
        echo "   ✓ View count index added\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "   ⚠ View count index already exists\n";
        } else {
            throw $e;
        }
    }
    
    try {
        $pdo->exec("CREATE INDEX idx_cv_download_count ON cvs(download_count)");
        echo "   ✓ Download count index added\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "   ⚠ Download count index already exists\n";
        } else {
            throw $e;
        }
    }
    
    try {
        $pdo->exec("CREATE INDEX idx_cv_last_viewed ON cvs(last_viewed_at)");
        echo "   ✓ Last viewed index added\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "   ⚠ Last viewed index already exists\n";
        } else {
            throw $e;
        }
    }
    
    echo "\n✅ Migration completed successfully!\n";
    echo "\nNew features available:\n";
    echo "- CV preview in browser\n";
    echo "- View and download tracking\n";
    echo "- CV analytics dashboard\n";
    
} catch (PDOException $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
