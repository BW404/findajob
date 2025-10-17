<?php
// Comprehensive Job Posting System Debug
session_start();
include 'config/database.php';
include 'config/session.php';

echo "🔧 Job Posting System Diagnostic\n";
echo "=" . str_repeat("=", 50) . "\n\n";

// 1. Check session
echo "1. SESSION CHECK:\n";
if (isset($_SESSION['user_id'])) {
    echo "   ✅ User ID: " . $_SESSION['user_id'] . "\n";
    echo "   ✅ User Type: " . ($_SESSION['user_type'] ?? 'Not set') . "\n";
} else {
    echo "   ❌ No active session\n";
}
echo "\n";

// 2. Check database connection
echo "2. DATABASE CONNECTION:\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM jobs");
    echo "   ✅ Database connected - Total jobs: " . $stmt->fetchColumn() . "\n";
} catch (Exception $e) {
    echo "   ❌ Database error: " . $e->getMessage() . "\n";
}
echo "\n";

// 3. Check jobs table structure
echo "3. JOBS TABLE STRUCTURE:\n";
try {
    $stmt = $pdo->query("DESCRIBE jobs");
    $columns = $stmt->fetchAll();
    
    $required_columns = ['title', 'description', 'requirements', 'responsibilities', 'benefits', 'job_type', 'STATUS'];
    foreach ($required_columns as $col) {
        $found = false;
        foreach ($columns as $column) {
            if (strtolower($column['Field']) === strtolower($col)) {
                echo "   ✅ Column '$col': " . $column['Type'] . "\n";
                $found = true;
                break;
            }
        }
        if (!$found) {
            echo "   ❌ Missing column: $col\n";
        }
    }
} catch (Exception $e) {
    echo "   ❌ Error checking table: " . $e->getMessage() . "\n";
}
echo "\n";

// 4. Test form submission simulation
echo "4. FORM SUBMISSION TEST:\n";

// Simulate POST data
$test_data = [
    'job_title' => 'Test Debug Job',
    'job_type' => 'permanent',
    'description' => 'This is a test job description for debugging.',
    'requirements' => 'Test requirements for debugging.',
    'responsibilities' => 'Test responsibilities for debugging.',
    'benefits' => 'Test benefits for debugging.',
    'location' => 'Lagos',
    'submit_job' => 'true'
];

echo "   📝 Simulating form data:\n";
foreach ($test_data as $key => $value) {
    echo "      $key: $value\n";
}
echo "\n";

// 5. Test database insertion
echo "5. DATABASE INSERTION TEST:\n";
try {
    $employer_id = $_SESSION['user_id'] ?? 2; // Use session or test with ID 2
    
    $stmt = $pdo->prepare("
        INSERT INTO jobs (
            employer_id, title, slug, job_type, employment_type,
            description, requirements, responsibilities, benefits,
            salary_min, salary_max, salary_currency, salary_period,
            location_type, state, city, address,
            experience_level, education_level, application_deadline,
            application_email, company_name, STATUS, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $slug = 'test-debug-job-' . time();
    $company_name = 'Debug Test Company';
    
    $result = $stmt->execute([
        $employer_id,                          // employer_id
        $test_data['job_title'],              // title
        $slug,                                // slug
        $test_data['job_type'],               // job_type
        'permanent',                          // employment_type
        $test_data['description'],            // description
        $test_data['requirements'],           // requirements
        $test_data['responsibilities'],       // responsibilities
        $test_data['benefits'],               // benefits
        null,                                 // salary_min
        null,                                 // salary_max
        'NGN',                               // salary_currency
        'monthly',                           // salary_period
        'onsite',                            // location_type
        $test_data['location'],              // state
        $test_data['location'],              // city
        null,                                // address
        'entry',                             // experience_level
        'any',                               // education_level
        null,                                // application_deadline
        null,                                // application_email
        $company_name,                       // company_name
        'active'                             // STATUS
    ]);
    
    if ($result) {
        $job_id = $pdo->lastInsertId();
        echo "   ✅ SUCCESS! Job inserted with ID: $job_id\n";
        
        // Verify the insertion
        $verify = $pdo->prepare("SELECT * FROM jobs WHERE id = ?");
        $verify->execute([$job_id]);
        $job = $verify->fetch();
        
        echo "   📊 Verification:\n";
        echo "      Title: " . $job['title'] . "\n";
        echo "      Status: " . $job['STATUS'] . "\n";
        echo "      Employer ID: " . $job['employer_id'] . "\n";
        
    } else {
        echo "   ❌ FAILED to insert job\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Database insertion error: " . $e->getMessage() . "\n";
    echo "   📝 Full error details:\n";
    echo "      Code: " . $e->getCode() . "\n";
    echo "      File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
echo "\n";

// 6. Check recent jobs query (dashboard compatibility)
echo "6. DASHBOARD COMPATIBILITY TEST:\n";
try {
    $employer_id = $_SESSION['user_id'] ?? 2;
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
    $stmt->execute([$employer_id]);
    $jobs = $stmt->fetchAll();
    
    echo "   ✅ Dashboard query successful\n";
    echo "   📊 Found " . count($jobs) . " jobs for employer ID $employer_id\n";
    
    if (count($jobs) > 0) {
        echo "   📝 Latest job:\n";
        $latest = $jobs[0];
        echo "      Title: " . $latest['title'] . "\n";
        echo "      Status: " . $latest['status'] . "\n";
        echo "      Applications: " . $latest['application_count'] . "\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Dashboard query error: " . $e->getMessage() . "\n";
}
echo "\n";

echo "🎯 DIAGNOSIS COMPLETE!\n";
echo "=" . str_repeat("=", 50) . "\n";
echo "\nIf all tests passed, the job posting system should work correctly.\n";
echo "Check the post-job.php form to ensure all required fields are present.\n";
?>