<?php
require_once 'config/database.php';
require_once 'config/session.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h2>Job Application Flow Test</h2>\n\n";

// Check if we have any jobs
$stmt = $pdo->query("SELECT id, title, company_name, employer_id FROM jobs WHERE status = 'active' LIMIT 1");
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    echo "❌ No active jobs found. Please create a job first.\n";
    exit;
}

echo "✓ Found active job: {$job['title']} at {$job['company_name']} (ID: {$job['id']})\n\n";

// Check if we have a job seeker user
$stmt = $pdo->query("SELECT id, email, first_name FROM users WHERE user_type = 'job_seeker' LIMIT 1");
$jobseeker = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$jobseeker) {
    echo "❌ No job seeker found. Please register a job seeker account first.\n";
    exit;
}

echo "✓ Found job seeker: {$jobseeker['first_name']} ({$jobseeker['email']}, ID: {$jobseeker['id']})\n\n";

// Simulate login
$_SESSION['user_id'] = $jobseeker['id'];
$_SESSION['user_type'] = 'job_seeker';
$_SESSION['logged_in'] = true;

// Try to apply for the job
try {
    // Check if already applied
    $check = $pdo->prepare("SELECT id FROM job_applications WHERE job_id = ? AND job_seeker_id = ?");
    $check->execute([$job['id'], $jobseeker['id']]);
    $existing = $check->fetch();
    
    if ($existing) {
        echo "ℹ Job seeker has already applied for this job (Application ID: {$existing['id']})\n\n";
        
        // Show application details
        $stmt = $pdo->prepare("SELECT * FROM job_applications WHERE id = ?");
        $stmt->execute([$existing['id']]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "Application Details:\n";
        echo "  - Status: {$app['application_status']}\n";
        echo "  - Applied At: {$app['applied_at']}\n";
        echo "  - Created At: {$app['created_at']}\n";
        
    } else {
        // Create new application
        $insert = $pdo->prepare("INSERT INTO job_applications (job_id, job_seeker_id, application_status) VALUES (?, ?, 'applied')");
        $insert->execute([$job['id'], $jobseeker['id']]);
        $appId = $pdo->lastInsertId();
        
        // Update applications count
        $pdo->prepare("UPDATE jobs SET applications_count = COALESCE(applications_count, 0) + 1 WHERE id = ?")->execute([$job['id']]);
        
        echo "✅ Application submitted successfully! (Application ID: {$appId})\n\n";
        
        // Show application details
        $stmt = $pdo->prepare("SELECT * FROM job_applications WHERE id = ?");
        $stmt->execute([$appId]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "Application Details:\n";
        echo "  - Job ID: {$app['job_id']}\n";
        echo "  - Job Seeker ID: {$app['job_seeker_id']}\n";
        echo "  - Status: {$app['application_status']}\n";
        echo "  - Applied At: {$app['applied_at']}\n";
        echo "  - Created At: {$app['created_at']}\n\n";
    }
    
    // Check if it appears in job seeker dashboard query
    echo "Checking Job Seeker Dashboard Query:\n";
    $stmt = $pdo->prepare("
        SELECT ja.*, j.title, j.company_name, ja.applied_at, ja.application_status as status
        FROM job_applications ja 
        JOIN jobs j ON ja.job_id = j.id 
        WHERE ja.job_seeker_id = ? 
        ORDER BY ja.applied_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$jobseeker['id']]);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($applications) {
        echo "✓ Found {" . count($applications) . "} application(s) in job seeker dashboard\n";
        foreach ($applications as $app) {
            echo "  - {$app['title']} at {$app['company_name']} ({$app['status']})\n";
        }
    } else {
        echo "❌ No applications found in job seeker dashboard query\n";
    }
    
    echo "\n";
    
    // Check if it appears in employer dashboard query
    echo "Checking Employer Dashboard Query:\n";
    $stmt = $pdo->prepare("
        SELECT ja.*, 
               j.title as job_title,
               u.first_name, u.last_name, u.email, u.phone,
               jsp.years_of_experience, jsp.job_status, jsp.education_level,
               ja.application_status as status
        FROM job_applications ja
        JOIN jobs j ON ja.job_id = j.id
        JOIN users u ON ja.job_seeker_id = u.id
        LEFT JOIN job_seeker_profiles jsp ON u.id = jsp.user_id
        WHERE j.employer_id = ?
        ORDER BY ja.applied_at DESC
    ");
    $stmt->execute([$job['employer_id']]);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($applications) {
        echo "✓ Found {" . count($applications) . "} application(s) in employer dashboard\n";
        foreach ($applications as $app) {
            echo "  - {$app['first_name']} {$app['last_name']} applied for {$app['job_title']} ({$app['status']})\n";
        }
    } else {
        echo "❌ No applications found in employer dashboard query\n";
    }
    
    echo "\n✅ All tests completed!\n\n";
    echo "Next steps:\n";
    echo "1. Visit: http://localhost/findajob/pages/jobs/details.php?id={$job['id']}\n";
    echo "2. Visit job seeker dashboard: http://localhost/findajob/pages/user/dashboard.php\n";
    echo "3. Visit employer dashboard: http://localhost/findajob/pages/company/dashboard.php\n";
    echo "4. Visit applicants page: http://localhost/findajob/pages/company/applicants.php\n";
    
} catch (Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n";
    echo "Stack trace:\n{$e->getTraceAsString()}\n";
}
