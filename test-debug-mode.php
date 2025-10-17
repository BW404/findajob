<?php
session_start();
include '../../config/database.php';
include '../../config/session.php';

echo "🐛 Debug Mode Test - Job Posting System\n\n";

// Set session for testing
$_SESSION['user_id'] = 2;
$_SESSION['user_type'] = 'employer';

echo "✅ Session setup for employer ID 2\n";

// Test the debug mode URL
echo "🔗 Debug URLs to test:\n";
echo "   📝 Post Job (Debug): http://localhost/findajob/pages/company/post-job.php?debug=1\n";
echo "   📝 Post Job (Normal): http://localhost/findajob/pages/company/post-job.php\n";

// Test database connectivity
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE employer_id = 2");
    $stmt->execute();
    $job_count = $stmt->fetchColumn();
    echo "✅ Database connection working - Employer has $job_count active jobs\n";
    
    // Test categories
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM job_categories WHERE is_active = 1");
    $stmt->execute();
    $cat_count = $stmt->fetchColumn();
    echo "✅ Categories available: $cat_count active categories\n";
    
    // Test user subscription
    $stmt = $pdo->prepare("SELECT * FROM user_subscriptions WHERE user_id = 2 AND status = 'active' AND end_date > NOW()");
    $stmt->execute();
    $subscription = $stmt->fetch();
    echo "📊 Premium status: " . ($subscription ? 'PREMIUM' : 'FREE') . "\n";
    
    if ($subscription) {
        echo "   Subscription: " . json_encode($subscription) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

echo "\n🎯 Debug Features Added:\n";
echo "   ✅ Debug mode toggle checkbox\n";
echo "   ✅ Premium plan limit checking\n";
echo "   ✅ Detailed form validation logging\n";
echo "   ✅ Database insertion debugging\n";
echo "   ✅ Error tracking with codes\n";
echo "   ✅ Success/error message improvements\n";
echo "   ✅ Real-time debug panel display\n";

echo "\n🚀 How to use:\n";
echo "   1. Add ?debug=1 to the URL OR check the debug checkbox\n";
echo "   2. Fill out and submit the job posting form\n";
echo "   3. Watch the debug panel for detailed information\n";
echo "   4. Look for ✅ (success) or ❌ (error) indicators\n";

echo "\n🔍 What gets debugged:\n";
echo "   • User authentication and role verification\n";
echo "   • Premium plan status and job limits\n";
echo "   • Form field validation step by step\n";
echo "   • Database query preparation and execution\n";
echo "   • Job data mapping and insertion\n";
echo "   • Success/failure verification\n";

echo "\n🎉 Ready to test! Visit the debug URL above.\n";
?>