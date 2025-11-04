<?php
/**
 * Migration Runner Script
 * Executes SQL migration files safely
 * Usage: php run-migration.php <migration-file.sql>
 */

// Include database config
require_once __DIR__ . '/../config/database.php';

// Get migration file from command line argument or prompt
$migrationFile = $argv[1] ?? null;

if (!$migrationFile) {
    echo "Usage: php run-migration.php <migration-file.sql>\n";
    echo "Example: php run-migration.php add-religion-column.sql\n";
    exit(1);
}

// Check if file exists
$migrationPath = __DIR__ . '/' . basename($migrationFile);
if (!file_exists($migrationPath)) {
    echo "Error: Migration file not found: $migrationPath\n";
    exit(1);
}

echo "=================================================\n";
echo "Migration Runner\n";
echo "=================================================\n";
echo "File: " . basename($migrationFile) . "\n";
echo "Path: $migrationPath\n";
echo "=================================================\n\n";

// Read migration SQL
$sql = file_get_contents($migrationPath);

if (empty($sql)) {
    echo "Error: Migration file is empty\n";
    exit(1);
}

try {
    // Create database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "Connected to database: " . DB_NAME . "\n\n";
    echo "Executing migration...\n";
    echo str_repeat("-", 50) . "\n";
    
    // Execute multi-query (supports multiple statements)
    if ($conn->multi_query($sql)) {
        $resultCount = 0;
        do {
            $resultCount++;
            // Store first result set
            if ($result = $conn->store_result()) {
                // Fetch and display any SELECT results
                while ($row = $result->fetch_assoc()) {
                    foreach ($row as $key => $value) {
                        echo "$key: $value\n";
                    }
                }
                $result->free();
            }
            
            // Check for errors
            if ($conn->errno) {
                throw new Exception("Query error: " . $conn->error);
            }
            
            echo "Statement $resultCount executed successfully.\n";
            
        } while ($conn->more_results() && $conn->next_result());
        
        echo str_repeat("-", 50) . "\n";
        echo "✓ Migration completed successfully!\n";
        echo "Total statements executed: $resultCount\n";
        
    } else {
        throw new Exception("Migration failed: " . $conn->error);
    }
    
    $conn->close();
    echo "\nDatabase connection closed.\n";
    echo "=================================================\n";
    
} catch (Exception $e) {
    echo "\n✗ Migration failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "=================================================\n";
    exit(1);
}

exit(0);
