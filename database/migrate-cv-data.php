<?php
/**
 * Add cv_data column to cvs table
 * Run this script once to update the database schema
 */

require_once '../config/database.php';

try {
    echo "Starting migration: Add cv_data column to cvs table...\n\n";
    
    // Check if column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM cvs LIKE 'cv_data'");
    $columnExists = $stmt->fetch();
    
    if ($columnExists) {
        echo "✓ Column 'cv_data' already exists in cvs table.\n";
    } else {
        // Add the cv_data column
        echo "Adding cv_data column...\n";
        $pdo->exec("ALTER TABLE cvs ADD COLUMN cv_data TEXT NULL AFTER content");
        echo "✓ Column 'cv_data' added successfully.\n\n";
        
        // Copy existing data from content to cv_data
        echo "Copying existing data from 'content' to 'cv_data'...\n";
        $pdo->exec("UPDATE cvs SET cv_data = content WHERE content IS NOT NULL AND cv_data IS NULL");
        echo "✓ Data copied successfully.\n";
    }
    
    echo "\n✅ Migration completed successfully!\n";
    
} catch (PDOException $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
