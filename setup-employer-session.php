<?php
require_once './config/database.php';
require_once './config/session.php';

echo "🔑 Setting up session for test2@gmail.com...\n\n";

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get the employer details
$stmt = $pdo->prepare("SELECT id, first_name, last_name, email, user_type FROM users WHERE email = 'test2@gmail.com'");
$stmt->execute();
$employer = $stmt->fetch();

if ($employer) {
    // Set up session for this employer
    $_SESSION['user_id'] = $employer['id'];
    $_SESSION['user_type'] = $employer['user_type'];
    $_SESSION['email'] = $employer['email'];
    $_SESSION['first_name'] = $employer['first_name'];
    $_SESSION['last_name'] = $employer['last_name'];
    $_SESSION['login_time'] = time();
    
    echo "✅ Session created for:\n";
    echo "   ID: {$employer['id']}\n";
    echo "   Name: {$employer['first_name']} {$employer['last_name']}\n";
    echo "   Email: {$employer['email']}\n";
    echo "   Type: {$employer['user_type']}\n\n";
    
    // Verify session functions
    echo "🔍 Testing session functions:\n";
    echo "   isLoggedIn(): " . (isLoggedIn() ? 'Yes' : 'No') . "\n";
    echo "   isEmployer(): " . (isEmployer() ? 'Yes' : 'No') . "\n";
    echo "   getCurrentUserId(): " . getCurrentUserId() . "\n\n";
    
    // Test dashboard query with current session
    $userId = getCurrentUserId();
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as job_count,
            COALESCE(SUM(applications_count), 0) as applications_count,
            COALESCE(SUM(views_count), 0) as views_count
        FROM jobs 
        WHERE employer_id = ? AND STATUS != 'deleted'
    ");
    $stmt->execute([$userId]);
    $stats = $stmt->fetch();
    
    echo "📊 Dashboard will show:\n";
    echo "   Active Jobs: {$stats['job_count']}\n";
    echo "   Applications: {$stats['applications_count']}\n";
    echo "   Views: {$stats['views_count']}\n\n";
    
    echo "🎉 SUCCESS! Now visit the dashboard:\n";
    echo "   Dashboard: http://localhost/findajob/pages/company/dashboard.php\n";
    echo "   Manage Jobs: http://localhost/findajob/pages/company/manage-jobs.php\n\n";
    
    echo "💡 Your job should now be visible on the dashboard!\n";
    
} else {
    echo "❌ Employer not found with email: test2@gmail.com\n";
}
?>