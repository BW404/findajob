<?php
session_start();
include 'config/database.php';
include 'config/session.php';

echo "🔍 Debugging Job Dashboard Visibility...\n\n";

// Check current session
echo "📋 Current Session State:\n";
echo "   User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "\n";
echo "   User Type: " . ($_SESSION['user_type'] ?? 'Not set') . "\n";
echo "   Logged In: " . (isLoggedIn() ? 'Yes' : 'No') . "\n";
echo "   Is Employer: " . (isEmployer() ? 'Yes' : 'No') . "\n\n";

// Get current user ID
$currentUserId = getCurrentUserId();
echo "📊 Current User ID from function: " . ($currentUserId ?: 'None') . "\n\n";

if ($currentUserId) {
    // Check user type in database
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, user_type FROM users WHERE id = ?");
    $stmt->execute([$currentUserId]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "👤 User Database Record:\n";
        echo "   ID: " . $user['id'] . "\n";
        echo "   Name: " . $user['first_name'] . ' ' . $user['last_name'] . "\n";
        echo "   Email: " . $user['email'] . "\n";
        echo "   Type: " . $user['user_type'] . "\n\n";
        
        // Get all jobs for this employer
        $stmt = $pdo->prepare("
            SELECT id, title, STATUS, created_at, employer_id 
            FROM jobs 
            WHERE employer_id = ? 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        $stmt->execute([$currentUserId]);
        $allJobs = $stmt->fetchAll();
        
        echo "📋 ALL Jobs by This Employer:\n";
        if (empty($allJobs)) {
            echo "   ❌ No jobs found for employer ID: " . $currentUserId . "\n";
        } else {
            foreach ($allJobs as $job) {
                echo "   Job #" . $job['id'] . ": " . $job['title'] . " (Status: " . $job['STATUS'] . ") - " . date('Y-m-d H:i', strtotime($job['created_at'])) . "\n";
            }
        }
        
        echo "\n";
        
        // Get dashboard query (same as used in dashboard.php)
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
        $stmt->execute([$currentUserId]);
        $dashboardJobs = $stmt->fetchAll();
        
        echo "📊 Dashboard Query Results:\n";
        if (empty($dashboardJobs)) {
            echo "   ❌ No jobs returned by dashboard query\n";
        } else {
            foreach ($dashboardJobs as $job) {
                echo "   Job #" . $job['id'] . ": " . $job['title'] . " (Status: " . $job['status'] . ") - Apps: " . $job['application_count'] . "\n";
            }
        }
        
        // Check job statistics query
        echo "\n📈 Job Statistics:\n";
        $stats_stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_jobs,
                COUNT(CASE WHEN STATUS = 'active' THEN 1 END) as active_jobs,
                COUNT(CASE WHEN STATUS = 'paused' THEN 1 END) as paused_jobs,
                COUNT(CASE WHEN STATUS = 'draft' THEN 1 END) as draft_jobs
            FROM jobs 
            WHERE employer_id = ?
        ");
        $stats_stmt->execute([$currentUserId]);
        $stats = $stats_stmt->fetch();
        
        echo "   Total: " . $stats['total_jobs'] . "\n";
        echo "   Active: " . $stats['active_jobs'] . "\n";
        echo "   Paused: " . $stats['paused_jobs'] . "\n";
        echo "   Draft: " . $stats['draft_jobs'] . "\n";
        
    } else {
        echo "❌ User not found in database for ID: " . $currentUserId . "\n";
    }
} else {
    echo "❌ No current user ID available\n";
}

// Also check most recent jobs in database regardless of employer
echo "\n🔍 Most Recent Jobs in Database (All Employers):\n";
$stmt = $pdo->prepare("
    SELECT j.id, j.title, j.STATUS, j.employer_id, j.created_at, u.email 
    FROM jobs j 
    LEFT JOIN users u ON j.employer_id = u.id 
    ORDER BY j.created_at DESC 
    LIMIT 5
");
$stmt->execute();
$recentJobs = $stmt->fetchAll();

foreach ($recentJobs as $job) {
    echo "   Job #" . $job['id'] . ": " . $job['title'] . " by " . ($job['email'] ?? 'Unknown') . " (ID: " . $job['employer_id'] . ") - Status: " . $job['STATUS'] . " - " . date('Y-m-d H:i', strtotime($job['created_at'])) . "\n";
}

echo "\n💡 Dashboard Access:\n";
echo "   Dashboard URL: http://localhost/findajob/pages/company/dashboard.php\n";
echo "   Post Job URL: http://localhost/findajob/pages/company/post-job.php\n";
?>