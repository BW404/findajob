<?php
// Test job posting system repair
session_start();
include 'config/database.php';
include 'config/session.php';

echo "🔧 Testing Job Posting System Repair...\n\n";

// Set up session for employer test2@gmail.com
$_SESSION['user_id'] = 2;
$_SESSION['user_type'] = 'employer';

echo "✅ Session setup: Employer ID 2 (test2@gmail.com)\n\n";

// Test database connection
try {
    $stmt = $pdo->query("SELECT 1");
    echo "✅ Database connection: Working\n";
} catch (Exception $e) {
    echo "❌ Database connection: Failed - " . $e->getMessage() . "\n";
    exit;
}

// Test jobs table structure
try {
    $stmt = $pdo->query("DESCRIBE jobs");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "✅ Jobs table exists with " . count($columns) . " columns\n";
    
    $required_columns = ['title', 'description', 'requirements', 'responsibilities', 'benefits', 'employer_id', 'STATUS'];
    $missing_columns = [];
    
    foreach ($required_columns as $col) {
        if (!in_array($col, $columns)) {
            $missing_columns[] = $col;
        }
    }
    
    if (empty($missing_columns)) {
        echo "✅ All required columns present\n";
    } else {
        echo "❌ Missing columns: " . implode(', ', $missing_columns) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Table check failed: " . $e->getMessage() . "\n";
}

echo "\n";

// Test job insertion with sample data
try {
    echo "🧪 Testing job insertion...\n";
    
    $test_data = [
        'job_title' => 'Test Developer Position - Automated Test',
        'description' => 'This is a test job description with more than 50 characters to pass validation checks. We are looking for a skilled developer.',
        'requirements' => 'Bachelor degree in Computer Science, 2+ years experience with PHP, MySQL knowledge',
        'responsibilities' => 'Develop web applications, maintain existing code, collaborate with team members',
        'benefits' => 'Health insurance, flexible working hours, professional development opportunities',
        'job_type' => 'permanent',
        'employment_type' => 'full_time',
        'location' => 'lagos',
        'location_type' => 'onsite',
        'salary_min' => '150000',
        'salary_max' => '300000',
        'salary_period' => 'monthly',
        'experience_level' => 'mid',
        'education_level' => 'bsc',
        'application_email' => 'jobs@test.com',
        'skills' => 'PHP, MySQL, JavaScript, HTML, CSS'
    ];
    
    // Simulate POST data
    $_POST = $test_data;
    $_POST['submit_job'] = 'true';
    
    // Generate slug
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $test_data['job_title']));
    $slug = trim($slug, '-');
    
    // Get employer info
    $company_stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
    $company_stmt->execute([2]);
    $company_info = $company_stmt->fetch();
    $company_name = ($company_info['first_name'] ?? '') . ' ' . ($company_info['last_name'] ?? 'Company');
    
    // Prepare insertion query
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
    
    $job_data = [
        2,                                          // employer_id
        trim($test_data['job_title']),             // title
        $slug,                                     // slug
        $test_data['job_type'],                    // job_type
        $test_data['employment_type'],             // employment_type
        trim($test_data['description']),           // description
        trim($test_data['requirements']),          // requirements
        trim($test_data['responsibilities']),      // responsibilities
        trim($test_data['benefits']),              // benefits
        (int)$test_data['salary_min'],             // salary_min
        (int)$test_data['salary_max'],             // salary_max
        'NGN',                                     // salary_currency
        $test_data['salary_period'],               // salary_period
        $test_data['location_type'],               // location_type
        trim($test_data['location']),              // state
        trim($test_data['location']),              // city
        '',                                        // address
        $test_data['experience_level'],            // experience_level
        $test_data['education_level'],             // education_level
        null,                                      // application_deadline
        $test_data['application_email'],           // application_email
        trim($company_name),                       // company_name
        'active'                                   // STATUS
    ];
    
    $result = $stmt->execute($job_data);
    
    if ($result) {
        $job_id = $pdo->lastInsertId();
        echo "✅ Job inserted successfully! Job ID: #$job_id\n";
        
        // Verify the insertion
        $verify_stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ?");
        $verify_stmt->execute([$job_id]);
        $job = $verify_stmt->fetch();
        
        if ($job) {
            echo "✅ Job verification successful\n";
            echo "   📋 Title: " . $job['title'] . "\n";
            echo "   🏢 Company: " . $job['company_name'] . "\n";
            echo "   📍 Location: " . $job['state'] . "\n";
            echo "   💼 Type: " . $job['job_type'] . "\n";
            echo "   📊 Status: " . $job['STATUS'] . "\n";
            echo "   📝 Requirements: " . (!empty($job['requirements']) ? 'Present' : 'Missing') . "\n";
            echo "   🎯 Responsibilities: " . (!empty($job['responsibilities']) ? 'Present' : 'Missing') . "\n";
            echo "   🎁 Benefits: " . (!empty($job['benefits']) ? 'Present' : 'Missing') . "\n";
        } else {
            echo "❌ Job verification failed - job not found\n";
        }
        
    } else {
        echo "❌ Job insertion failed\n";
        print_r($stmt->errorInfo());
    }
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    echo "📋 Error Info: " . print_r($e->errorInfo ?? [], true) . "\n";
} catch (Exception $e) {
    echo "❌ General error: " . $e->getMessage() . "\n";
}

echo "\n🎉 Job posting system test completed!\n";
echo "💡 If all tests passed, the job posting system should now work correctly.\n";
echo "🌐 Test the form at: http://localhost/findajob/pages/company/post-job.php\n";
?>