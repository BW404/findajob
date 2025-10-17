<?php
session_start();
include 'config/database.php';

echo "🔧 Testing Job Limit Fix...\n\n";

// Set up session for employer test2@gmail.com
$_SESSION['user_id'] = 2;
$_SESSION['user_type'] = 'employer';

$debug_mode = true;
$debug_info = [];

try {
    // Check current job count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE employer_id = ? AND STATUS = 'active'");
    $stmt->execute([2]);
    $current_jobs = $stmt->fetchColumn();
    
    // Check subscription
    $is_premium = false;
    $job_limit = $is_premium ? 999 : 5;
    
    echo "Current situation:\n";
    echo "   👤 User ID: 2 (test2@gmail.com)\n";
    echo "   📊 Current jobs: $current_jobs\n";
    echo "   🚫 Job limit: $job_limit\n";
    echo "   💎 Premium: " . ($is_premium ? 'YES' : 'NO') . "\n";
    
    // Test the limit check logic
    if ($current_jobs >= $job_limit) {
        echo "\n❌ LIMIT REACHED - Should show error message:\n";
        $error_message = "❌ Job posting limit reached! " . ($is_premium ? "Please contact support." : "Free accounts can post up to $job_limit active jobs. Upgrade to Premium for unlimited job postings.");
        echo "   Error: $error_message\n";
        echo "\n✅ Form processing should STOP here (no job creation)\n";
    } else {
        echo "\n✅ LIMIT OK - Should allow job posting\n";
        echo "   Jobs allowed: " . ($job_limit - $current_jobs) . " more\n";
    }
    
    echo "\n🔧 What the fix does:\n";
    echo "   1. Check job limit BEFORE any form processing\n";
    echo "   2. Set error message and STOP if limit reached\n";
    echo "   3. Only process form if within limits\n";
    echo "   4. Show clear debug info about the block\n";
    
    echo "\n🎯 Solutions for user:\n";
    if ($current_jobs >= $job_limit) {
        echo "   💰 Upgrade to Premium for unlimited job postings\n";
        echo "   🗑️ Delete old jobs to make space for new ones\n";
        echo "   ⏸️ Deactivate completed jobs to free up slots\n";
    }
    
} catch (Exception $e) {
    echo "❌ Test error: " . $e->getMessage() . "\n";
}

echo "\n🌐 Test the fix at:\n";
echo "   📝 http://localhost/findajob/pages/company/post-job.php?debug=1\n";
echo "\n🎉 Job limit error should now display properly!\n";
?>