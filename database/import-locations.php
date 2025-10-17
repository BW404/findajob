<?php
/**
 * Nigerian Location Data Import Script
 * Imports all 36 states and 774 LGAs into the database
 */

require_once '../config/database.php';

echo "🇳🇬 Nigerian Location Data Import\n";
echo "=================================\n\n";

try {
    // Check if tables exist
    echo "Checking database tables...\n";
    
    $tables = ['nigeria_states', 'nigeria_lgas'];
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->rowCount() === 0) {
            echo "❌ Table '$table' does not exist. Please run schema.sql first.\n";
            exit(1);
        }
    }
    echo "✅ All required tables exist.\n\n";
    
    // Check current data
    echo "Checking current data...\n";
    $statesStmt = $pdo->query("SELECT COUNT(*) FROM nigeria_states");
    $lgasStmt = $pdo->query("SELECT COUNT(*) FROM nigeria_lgas");
    
    $currentStates = $statesStmt->fetchColumn();
    $currentLGAs = $lgasStmt->fetchColumn();
    
    echo "Current states: $currentStates\n";
    echo "Current LGAs: $currentLGAs\n\n";
    
    // Ask for confirmation if data exists
    if ($currentStates > 0 || $currentLGAs > 0) {
        echo "⚠️  Existing location data found!\n";
        echo "This will delete all existing states and LGAs and import fresh data.\n";
        echo "Continue? (y/N): ";
        
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        fclose($handle);
        
        if (trim(strtolower($line)) !== 'y') {
            echo "Import cancelled.\n";
            exit(0);
        }
    }
    
    // Import the location data
    echo "Importing Nigerian location data...\n\n";
    
    $sqlFile = __DIR__ . '/nigeria-locations.sql';
    if (!file_exists($sqlFile)) {
        echo "❌ SQL file not found: $sqlFile\n";
        exit(1);
    }
    
    $sql = file_get_contents($sqlFile);
    if ($sql === false) {
        echo "❌ Failed to read SQL file\n";
        exit(1);
    }
    
    // Execute the SQL
    echo "Executing import queries...\n";
    $pdo->exec($sql);
    
    // Verify import
    echo "\nVerifying import...\n";
    
    $statesStmt = $pdo->query("SELECT COUNT(*) FROM nigeria_states");
    $lgasStmt = $pdo->query("SELECT COUNT(*) FROM nigeria_lgas");
    
    $totalStates = $statesStmt->fetchColumn();
    $totalLGAs = $lgasStmt->fetchColumn();
    
    echo "✅ Import completed!\n";
    echo "📊 Results:\n";
    echo "   - States: $totalStates (expected: 37 including FCT)\n";
    echo "   - LGAs: $totalLGAs (expected: 774)\n\n";
    
    // Show breakdown by region
    echo "📍 Breakdown by Region:\n";
    $regionStmt = $pdo->query("
        SELECT 
            region,
            COUNT(*) as state_count,
            (SELECT COUNT(*) FROM nigeria_lgas l 
             JOIN nigeria_states s2 ON l.state_id = s2.id 
             WHERE s2.region = s.region) as lga_count
        FROM nigeria_states s
        GROUP BY region
        ORDER BY region
    ");
    
    while ($row = $regionStmt->fetch()) {
        $regionName = ucwords(str_replace('_', ' ', $row['region']));
        echo "   - {$regionName}: {$row['state_count']} states, {$row['lga_count']} LGAs\n";
    }
    
    echo "\n🎉 Nigerian location data import successful!\n";
    echo "You can now use location-based job filtering and search.\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>