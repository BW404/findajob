<?php
/**
 * Run migration to add profile_picture column to users table
 */

require_once 'config/database.php';

try {
    // Read the SQL file
    $sql = file_get_contents('database/add-profile-pictures.sql');
    
    // Execute the migration
    $pdo->exec($sql);
    
    echo "✅ Migration completed successfully!\n";
    echo "- Added profile_picture column to users table\n";
    echo "- Synced existing pictures from job_seeker_profiles\n";
    echo "- Synced existing logos from employer_profiles\n";
    
} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    
    // Check if column already exists
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Note: The profile_picture column already exists. No action needed.\n";
    }
}
