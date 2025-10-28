<?php
/**
 * Run Application Type Migration
 * Adds Easy Apply and Manual Apply support to jobs table
 */

require_once __DIR__ . '/../config/database.php';

echo "=== Running Application Type Migration ===\n\n";

try {
    // Read and execute add-application-type.sql
    $sql1 = file_get_contents(__DIR__ . '/add-application-type.sql');
    $pdo->exec($sql1);
    echo "✓ Successfully added application_type fields to jobs table\n";
    
    // Read and execute enhance-applications-table.sql
    $sql2 = file_get_contents(__DIR__ . '/enhance-applications-table.sql');
    $pdo->exec($sql2);
    echo "✓ Successfully enhanced job_applications table\n";
    
    echo "\n=== Migration Completed Successfully ===\n";
    echo "The database now supports:\n";
    echo "- Easy Apply (applications through platform)\n";
    echo "- Manual Apply (applications via email/website)\n";
    echo "- Both options simultaneously\n";
    
} catch (PDOException $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    echo "\nError Details:\n";
    echo "Error Code: " . $e->getCode() . "\n";
    echo "SQL State: " . $e->errorInfo[0] . "\n";
    exit(1);
}
