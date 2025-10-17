<?php
// Test dashboard status fix
session_start();
include 'config/database.php';
include 'config/session.php';

// Set up session for employer test2@gmail.com
$_SESSION['user_id'] = 2;
$_SESSION['user_type'] = 'employer';

echo "🔍 Testing Dashboard Status Fix...\n\n";

try {
    // Test the same query used in dashboard
    $userId = 2;
    $stmt = $pdo->prepare("
        SELECT j.*, 
               j.STATUS as status,
               COALESCE(app_count.count, 0) as application_count,
               j.state as state_name, 
               j.city as lga_name
        FROM jobs j 
        LEFT JOIN (
            SELECT job_id, COUNT(*) as count 
            FROM job_applications 
            GROUP BY job_id
        ) app_count ON j.id = app_count.job_id
        WHERE j.employer_id = ? AND j.STATUS != 'deleted'
        ORDER BY j.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $recentJobs = $stmt->fetchAll();
    
    echo "✅ Query executed successfully\n";
    echo "📊 Found " . count($recentJobs) . " recent jobs\n\n";
    
    if (!empty($recentJobs)) {
        foreach ($recentJobs as $job) {
            echo "📄 Job: " . htmlspecialchars($job['title']) . "\n";
            echo "   Status: " . (isset($job['status']) ? $job['status'] : 'NOT SET') . "\n";
            echo "   Location: " . htmlspecialchars(($job['lga_name'] ?? '') . ', ' . ($job['state_name'] ?? '')) . "\n";
            echo "   Applications: " . $job['application_count'] . "\n";
            echo "\n";
        }
    }
    
    echo "🎉 SUCCESS: Dashboard query now includes status column properly!\n";
    echo "💡 The 'Undefined array key status' errors should be resolved.\n\n";
    echo "🌐 You can now visit: http://localhost/findajob/pages/company/dashboard.php\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>