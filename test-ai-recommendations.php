<?php
// Test script to debug AI recommendations API
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>AI Recommendations API Debug</h2>";
echo "<pre>";

require_once '../config/database.php';
require_once '../config/session.php';

echo "Session check:\n";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "User Type: " . ($_SESSION['user_type'] ?? 'NOT SET') . "\n";
echo "\n";

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'job_seeker') {
    echo "❌ Authentication failed - Not a job seeker or not logged in\n";
    echo "Please login as a job seeker first.\n";
    exit;
}

$userId = $_SESSION['user_id'];
echo "✅ Authenticated as user ID: $userId\n\n";

try {
    // Test 1: Get user profile
    echo "Test 1: Fetching user profile...\n";
    $stmt = $pdo->prepare("
        SELECT 
            u.first_name, u.last_name,
            jsp.skills, jsp.years_of_experience, jsp.education_level,
            jsp.current_state, jsp.current_city, jsp.job_status,
            jsp.salary_expectation_min, jsp.salary_expectation_max, jsp.bio
        FROM users u
        LEFT JOIN job_seeker_profiles jsp ON u.id = jsp.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$userId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$profile) {
        echo "❌ Profile not found\n";
        exit;
    }
    
    echo "✅ Profile found:\n";
    print_r($profile);
    echo "\n";
    
    // Test 2: Check profile completeness
    echo "Test 2: Calculating profile completeness...\n";
    $totalFields = 9;
    $filledFields = 0;
    
    if (!empty($profile['skills'])) { $filledFields++; echo "  ✓ Skills\n"; } else { echo "  ✗ Skills\n"; }
    if (!empty($profile['years_of_experience']) && $profile['years_of_experience'] != '0') { $filledFields++; echo "  ✓ Years of experience\n"; } else { echo "  ✗ Years of experience\n"; }
    if (!empty($profile['education_level'])) { $filledFields++; echo "  ✓ Education level\n"; } else { echo "  ✗ Education level\n"; }
    if (!empty($profile['current_state'])) { $filledFields++; echo "  ✓ Current state\n"; } else { echo "  ✗ Current state\n"; }
    if (!empty($profile['current_city'])) { $filledFields++; echo "  ✓ Current city\n"; } else { echo "  ✗ Current city\n"; }
    if (!empty($profile['job_status'])) { $filledFields++; echo "  ✓ Job status\n"; } else { echo "  ✗ Job status\n"; }
    if (!empty($profile['salary_expectation_min']) && $profile['salary_expectation_min'] > 0) { $filledFields++; echo "  ✓ Salary min\n"; } else { echo "  ✗ Salary min\n"; }
    if (!empty($profile['salary_expectation_max']) && $profile['salary_expectation_max'] > 0) { $filledFields++; echo "  ✓ Salary max\n"; } else { echo "  ✗ Salary max\n"; }
    if (!empty($profile['bio'])) { $filledFields++; echo "  ✓ Bio\n"; } else { echo "  ✗ Bio\n"; }
    
    $completeness = round(($filledFields / $totalFields) * 100);
    echo "\nProfile completeness: $completeness% ($filledFields/$totalFields fields)\n\n";
    
    // Test 3: Count active jobs
    echo "Test 3: Counting active jobs...\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'active'");
    $activeJobsCount = $stmt->fetchColumn();
    echo "Active jobs in database: $activeJobsCount\n\n";
    
    // Test 4: Check jobs table structure
    echo "Test 4: Checking jobs table structure...\n";
    $stmt = $pdo->query("DESCRIBE jobs");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredColumns = ['id', 'title', 'slug', 'description', 'requirements', 'skills', 
                        'category', 'employment_type', 'location_type', 'salary_min', 
                        'salary_max', 'salary_period', 'experience_level', 'education_level',
                        'state', 'city', 'is_urgent', 'remote_friendly', 'employer_id',
                        'created_at', 'application_deadline', 'views_count', 'applications_count', 'status'];
    
    $missingColumns = [];
    foreach ($requiredColumns as $col) {
        if (!in_array($col, $columns)) {
            $missingColumns[] = $col;
        }
    }
    
    if (empty($missingColumns)) {
        echo "✅ All required columns exist\n";
    } else {
        echo "❌ Missing columns: " . implode(', ', $missingColumns) . "\n";
    }
    echo "\n";
    
    // Test 5: Sample query from recommendations algorithm
    echo "Test 5: Testing sample recommendation query...\n";
    $stmt = $pdo->prepare("
        SELECT j.id, j.title, j.status, j.application_deadline
        FROM jobs j
        WHERE j.status = 'active'
        AND j.application_deadline >= CURDATE()
        LIMIT 5
    ");
    $stmt->execute();
    $sampleJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($sampleJobs) . " active jobs with future deadlines:\n";
    foreach ($sampleJobs as $job) {
        echo "  - ID {$job['id']}: {$job['title']} (deadline: {$job['application_deadline']})\n";
    }
    echo "\n";
    
    // Test 6: Check for applied/saved jobs
    echo "Test 6: Checking applied and saved jobs...\n";
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM job_applications WHERE job_seeker_id = ?");
    $stmt->execute([$userId]);
    $appliedCount = $stmt->fetchColumn();
    echo "Jobs applied to: $appliedCount\n";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM saved_jobs WHERE user_id = ?");
    $stmt->execute([$userId]);
    $savedCount = $stmt->fetchColumn();
    echo "Jobs saved: $savedCount\n";
    echo "\n";
    
    echo "=== DEBUG COMPLETE ===\n";
    echo "\nIf you see active jobs above but still get errors,\n";
    echo "the issue might be in the complex recommendation logic.\n";
    echo "\nTry accessing the API directly:\n";
    echo "<a href='/findajob/api/ai-job-recommendations.php' target='_blank'>Open API Response</a>\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>
