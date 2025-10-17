<?php
// Database repair script for FULLTEXT index issue
// Run this if you get FULLTEXT index errors

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>FindAJob Database Repair</h1>";
echo "<p>Fixing FULLTEXT index issues...</p>";

try {
    // Database connection
    $host = 'localhost';
    $dbname = 'findajob_ng';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Connected to database</p>";
    
    // Check if FULLTEXT index exists
    $stmt = $pdo->query("SHOW INDEX FROM jobs WHERE Key_name = 'search_text'");
    $indexExists = $stmt->rowCount() > 0;
    
    if (!$indexExists) {
        echo "<p>⚠️ FULLTEXT index missing. Adding now...</p>";
        
        // Add FULLTEXT index
        $pdo->exec("ALTER TABLE jobs ADD FULLTEXT KEY `search_text` (`title`, `description`)");
        echo "<p>✅ FULLTEXT index added successfully</p>";
    } else {
        echo "<p>✅ FULLTEXT index already exists</p>";
    }
    
    // Test the jobs table
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM jobs WHERE status = 'active'");
    $jobCount = $stmt->fetchColumn();
    echo "<p>Active jobs in database: $jobCount</p>";
    
    if ($jobCount == 0) {
        echo "<p>⚠️ No jobs found. <a href='setup-database.php'>Run database setup</a> to add sample data.</p>";
    }
    
    // Test a simple search query
    echo "<h3>Testing Search Functionality:</h3>";
    $stmt = $pdo->prepare("
        SELECT j.id, j.title, j.company_name, j.salary_min, j.salary_max 
        FROM jobs j 
        WHERE j.status = 'active' 
        AND (j.title LIKE ? OR j.company_name LIKE ? OR j.description LIKE ?)
        LIMIT 5
    ");
    
    $testKeyword = '%developer%';
    $stmt->execute([$testKeyword, $testKeyword, $testKeyword]);
    $results = $stmt->fetchAll();
    
    if (count($results) > 0) {
        echo "<p>✅ Search is working! Found " . count($results) . " jobs matching 'developer':</p>";
        echo "<ul>";
        foreach ($results as $job) {
            $salary = '';
            if ($job['salary_min'] && $job['salary_max']) {
                $salary = ' - ₦' . number_format($job['salary_min']) . ' - ₦' . number_format($job['salary_max']);
            }
            echo "<li><strong>{$job['title']}</strong> at {$job['company_name']}{$salary}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>⚠️ No search results found. This might be normal if there's no sample data.</p>";
    }
    
    echo "<h3>✅ Repair Complete!</h3>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ul>";
    echo "<li><a href='api/jobs.php?keywords=developer'>Test Jobs API</a></li>";
    echo "<li><a href='pages/jobs/browse.php'>Test Job Browse Page</a></li>";
    echo "<li><a href='api/test.php'>Run Full API Tests</a></li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database Error: " . $e->getMessage() . "</p>";
    
    if (strpos($e->getMessage(), "1146") !== false) {
        echo "<p><strong>Table doesn't exist.</strong> Please:</p>";
        echo "<ol>";
        echo "<li>Import database/basic-schema.sql in phpMyAdmin</li>";
        echo "<li>Run <a href='setup-database.php'>database setup</a></li>";
        echo "</ol>";
    } else if (strpos($e->getMessage(), "1049") !== false) {
        echo "<p><strong>Database doesn't exist.</strong> Please:</p>";
        echo "<ol>";
        echo "<li>Create database 'findajob_ng' in phpMyAdmin</li>";
        echo "<li>Import database/basic-schema.sql</li>";
        echo "<li>Run <a href='setup-database.php'>database setup</a></li>";
        echo "</ol>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>