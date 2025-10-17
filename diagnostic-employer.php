<?php
require_once './config/database.php';
require_once './config/session.php';

echo "🔍 COMPREHENSIVE JOB VISIBILITY DIAGNOSTIC\n";
echo "==========================================\n\n";

// Check the specific employer account
$email = 'test2@gmail.com';
echo "🎯 Checking employer: {$email}\n\n";

try {
    // 1. Find the employer account
    echo "1️⃣ EMPLOYER ACCOUNT CHECK:\n";
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, user_type, is_active, created_at FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $employer = $stmt->fetch();
    
    if (!$employer) {
        echo "❌ No user found with email: {$email}\n";
        exit;
    }
    
    echo "   ✅ Found user:\n";
    echo "      ID: {$employer['id']}\n";
    echo "      Name: {$employer['first_name']} {$employer['last_name']}\n";
    echo "      Email: {$employer['email']}\n";
    echo "      Type: {$employer['user_type']}\n";
    echo "      Active: " . ($employer['is_active'] ? 'Yes' : 'No') . "\n";
    echo "      Created: {$employer['created_at']}\n\n";
    
    if ($employer['user_type'] !== 'employer') {
        echo "⚠️  WARNING: User type is '{$employer['user_type']}', not 'employer'!\n\n";
    }
    
    $employerId = $employer['id'];
    
    // 2. Check jobs posted by this employer
    echo "2️⃣ JOBS CHECK:\n";
    $stmt = $pdo->prepare("SELECT id, title, employer_id, STATUS, job_type, created_at, updated_at FROM jobs WHERE employer_id = ? ORDER BY created_at DESC");
    $stmt->execute([$employerId]);
    $jobs = $stmt->fetchAll();
    
    if (empty($jobs)) {
        echo "❌ No jobs found for employer ID: {$employerId}\n";
        
        // Check if any jobs exist with this email in different ways
        echo "\n   🔍 Checking for jobs by email match...\n";
        $stmt = $pdo->prepare("
            SELECT j.id, j.title, j.employer_id, j.STATUS, u.email, u.user_type 
            FROM jobs j 
            JOIN users u ON j.employer_id = u.id 
            WHERE u.email = ?
        ");
        $stmt->execute([$email]);
        $emailJobs = $stmt->fetchAll();
        
        if (!empty($emailJobs)) {
            foreach ($emailJobs as $job) {
                echo "   📋 Job #{$job['id']}: {$job['title']} (Employer: {$job['employer_id']}, Type: {$job['user_type']})\n";
            }
        } else {
            echo "   ❌ No jobs found by email either\n";
        }
        
    } else {
        echo "   ✅ Found " . count($jobs) . " job(s):\n";
        foreach ($jobs as $job) {
            echo "   📋 Job #{$job['id']}: {$job['title']}\n";
            echo "      Status: {$job['STATUS']}\n";
            echo "      Type: {$job['job_type']}\n";
            echo "      Created: {$job['created_at']}\n";
            echo "      Updated: {$job['updated_at']}\n\n";
        }
    }
    
    // 3. Check recent jobs across all employers
    echo "3️⃣ RECENT JOBS ACROSS ALL EMPLOYERS:\n";
    $stmt = $pdo->prepare("
        SELECT j.id, j.title, j.employer_id, j.STATUS, j.created_at, 
               u.first_name, u.last_name, u.email, u.user_type
        FROM jobs j 
        JOIN users u ON j.employer_id = u.id 
        ORDER BY j.created_at DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $allJobs = $stmt->fetchAll();
    
    foreach ($allJobs as $job) {
        $isTarget = ($job['email'] === $email) ? " ⭐ TARGET USER" : "";
        $typeWarning = ($job['user_type'] !== 'employer') ? " ⚠️ " : "";
        echo "   📋 Job #{$job['id']}: {$job['title']}\n";
        echo "      By: {$job['first_name']} {$job['last_name']} ({$job['email']})\n";
        echo "      Employer ID: {$job['employer_id']} | Type: {$job['user_type']}{$typeWarning}\n";
        echo "      Status: {$job['STATUS']} | Created: {$job['created_at']}{$isTarget}\n\n";
    }
    
    // 4. Test dashboard query specifically
    echo "4️⃣ DASHBOARD QUERY TEST:\n";
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as job_count,
            COALESCE(SUM(applications_count), 0) as applications_count,
            COALESCE(SUM(views_count), 0) as views_count
        FROM jobs 
        WHERE employer_id = ? AND STATUS != 'deleted'
    ");
    $stmt->execute([$employerId]);
    $dashStats = $stmt->fetch();
    
    echo "   📊 Dashboard Statistics for Employer ID {$employerId}:\n";
    echo "      Total Jobs: {$dashStats['job_count']}\n";
    echo "      Applications: {$dashStats['applications_count']}\n";
    echo "      Views: {$dashStats['views_count']}\n\n";
    
    // 5. Test recent jobs query for dashboard
    echo "5️⃣ DASHBOARD RECENT JOBS QUERY TEST:\n";
    $stmt = $pdo->prepare("
        SELECT j.id, j.title, j.STATUS, j.created_at,
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
    $stmt->execute([$employerId]);
    $dashboardJobs = $stmt->fetchAll();
    
    if (empty($dashboardJobs)) {
        echo "   ❌ Dashboard query returned no jobs\n";
    } else {
        echo "   ✅ Dashboard query found " . count($dashboardJobs) . " job(s):\n";
        foreach ($dashboardJobs as $job) {
            echo "   📋 #{$job['id']}: {$job['title']} ({$job['STATUS']})\n";
            echo "      Location: {$job['state_name']}, {$job['lga_name']}\n";
            echo "      Applications: {$job['application_count']}\n\n";
        }
    }
    
    // 6. Check session functions
    echo "6️⃣ SESSION FUNCTIONS TEST:\n";
    if (function_exists('getCurrentUserId')) {
        echo "   ✅ getCurrentUserId() function exists\n";
    } else {
        echo "   ❌ getCurrentUserId() function missing\n";
    }
    
    if (function_exists('isEmployer')) {
        echo "   ✅ isEmployer() function exists\n";
    } else {
        echo "   ❌ isEmployer() function missing\n";
    }
    
    if (function_exists('requireEmployer')) {
        echo "   ✅ requireEmployer() function exists\n";
    } else {
        echo "   ❌ requireEmployer() function missing\n";
    }
    
    // 7. Check current session state
    echo "\n7️⃣ CURRENT SESSION STATE:\n";
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    $currentUserId = $_SESSION['user_id'] ?? null;
    $currentUserType = $_SESSION['user_type'] ?? null;
    
    echo "   Session Status: " . (session_status() == PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . "\n";
    echo "   Current User ID: " . ($currentUserId ?? 'Not set') . "\n";
    echo "   Current User Type: " . ($currentUserType ?? 'Not set') . "\n";
    
    if ($currentUserId == $employerId) {
        echo "   ✅ Session matches target employer!\n";
    } else {
        echo "   ⚠️  Session does NOT match target employer\n";
    }
    
    // 8. Recommendations
    echo "\n8️⃣ RECOMMENDATIONS:\n";
    
    if ($employer['user_type'] !== 'employer') {
        echo "   🔧 CRITICAL: Fix user type to 'employer'\n";
    }
    
    if (empty($jobs)) {
        echo "   📝 No jobs found - employer needs to post a job\n";
    }
    
    if ($currentUserId != $employerId) {
        echo "   🔑 Login as the correct employer to see dashboard\n";
        echo "   📧 Login with: {$email}\n";
    }
    
    if (!empty($dashboardJobs)) {
        echo "   ✅ Dashboard query is working - jobs should be visible\n";
    }
    
    echo "\n🌐 Test URLs:\n";
    echo "   Login: http://localhost/findajob/pages/auth/login-employer.php\n";
    echo "   Dashboard: http://localhost/findajob/pages/company/dashboard.php\n";
    echo "   Post Job: http://localhost/findajob/pages/company/post-job.php\n";
    
} catch (Exception $e) {
    echo "❌ Error during diagnostic: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>