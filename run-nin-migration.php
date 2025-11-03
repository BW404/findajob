<?php
/**
 * Run NIN Verification Migration
 * Simple script to execute the database migration
 */

echo "🚀 Starting NIN Verification Migration...\n\n";

require_once 'config/database.php';

// Read the SQL file
$sqlFile = __DIR__ . '/database/add-nin-verification.sql';
if (!file_exists($sqlFile)) {
    die("❌ Error: SQL file not found at $sqlFile\n");
}

$sql = file_get_contents($sqlFile);
echo "📄 SQL file loaded: " . strlen($sql) . " bytes\n\n";

// Split into statements (handling multi-line statements)
$statements = [];
$currentStatement = '';
$lines = explode("\n", $sql);

foreach ($lines as $line) {
    $line = trim($line);
    
    // Skip comments and empty lines
    if (empty($line) || strpos($line, '--') === 0) {
        continue;
    }
    
    $currentStatement .= ' ' . $line;
    
    // Check if statement is complete
    if (substr($line, -1) === ';') {
        $statements[] = trim($currentStatement);
        $currentStatement = '';
    }
}

echo "📊 Found " . count($statements) . " SQL statements to execute\n\n";

// Execute statements
$success = 0;
$skipped = 0;
$failed = 0;

foreach ($statements as $index => $statement) {
    if (empty(trim($statement))) {
        continue;
    }
    
    // Show preview of statement
    $preview = substr(str_replace(["\n", "\r"], ' ', $statement), 0, 80);
    echo "[" . ($index + 1) . "] " . $preview . "...\n";
    
    try {
        $pdo->exec($statement);
        echo "    ✅ Success\n";
        $success++;
    } catch (PDOException $e) {
        $errorMsg = $e->getMessage();
        
        // Check if it's a "already exists" or "duplicate" error (not a real error)
        if (stripos($errorMsg, 'Duplicate column') !== false || 
            stripos($errorMsg, 'Duplicate key') !== false ||
            stripos($errorMsg, 'already exists') !== false) {
            echo "    ⚠️  Already exists (skipped)\n";
            $skipped++;
        } else {
            echo "    ❌ Error: " . $errorMsg . "\n";
            $failed++;
        }
    }
    echo "\n";
}

// Summary
echo str_repeat("=", 60) . "\n";
echo "📊 Migration Summary:\n";
echo "   ✅ Successful: $success\n";
echo "   ⚠️  Skipped: $skipped\n";
echo "   ❌ Failed: $failed\n";
echo str_repeat("=", 60) . "\n\n";

if ($failed === 0) {
    echo "🎉 Migration completed successfully!\n\n";
    echo "Next steps:\n";
    echo "1. Visit: http://localhost/findajob/test-nin-verification.php\n";
    echo "2. Configure Dojah API credentials in config/constants.php\n";
    echo "3. Test the verification flow\n";
} else {
    echo "⚠️  Migration completed with $failed errors.\n";
    echo "Please check the errors above and fix them.\n";
}
