<?php
// Quick API test
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Quick API Test</h1>";

// Test database connection first
try {
    $pdo = new PDO("mysql:host=localhost;dbname=findajob_ng;charset=utf8mb4", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p>✅ Database connection successful</p>";
    
    // Test if jobs table exists and has data
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM jobs WHERE status = 'active'");
    $jobCount = $stmt->fetchColumn();
    echo "<p>Active jobs in database: $jobCount</p>";
    
    if ($jobCount == 0) {
        echo "<p style='color: orange;'>⚠️ No jobs found. <a href='setup-database.php'>Run setup</a> to add sample data.</p>";
    }
    
    // Test a simple query
    echo "<h3>Testing Simple Query:</h3>";
    $stmt = $pdo->prepare("
        SELECT j.id, j.title, j.company_name, jc.name as category
        FROM jobs j 
        LEFT JOIN job_categories jc ON j.category_id = jc.id 
        WHERE j.status = 'active' 
        LIMIT 3
    ");
    $stmt->execute();
    $results = $stmt->fetchAll();
    
    if (count($results) > 0) {
        echo "<p>✅ Query successful! Sample jobs:</p>";
        echo "<ul>";
        foreach ($results as $job) {
            echo "<li><strong>{$job['title']}</strong> at {$job['company_name']} ({$job['category']})</li>";
        }
        echo "</ul>";
        
        echo "<p><a href='api/jobs.php?limit=5'>Test Jobs API</a></p>";
    } else {
        echo "<p style='color: red;'>❌ No results from query</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
    
    if (strpos($e->getMessage(), '1049') !== false) {
        echo "<p><strong>Database 'findajob_ng' doesn't exist.</strong></p>";
        echo "<p>Please create it in phpMyAdmin and import basic-schema.sql</p>";
    } elseif (strpos($e->getMessage(), '1146') !== false) {
        echo "<p><strong>Jobs table doesn't exist.</strong></p>";
        echo "<p>Please import database/basic-schema.sql in phpMyAdmin</p>";
    }
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
p { margin: 10px 0; }
ul { margin: 10px 0 10px 20px; }
</style>