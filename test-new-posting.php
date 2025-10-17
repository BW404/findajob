<?php
// Test new job posting system
session_start();
include 'config/database.php';
include 'config/session.php';

echo "🚀 Testing New Job Posting System...\n\n";

// Set up session for employer test2@gmail.com
$_SESSION['user_id'] = 2;
$_SESSION['user_type'] = 'employer';

echo "✅ Session setup: Employer ID 2 (test2@gmail.com)\n\n";

// Simulate form submission with comprehensive data
$_POST = [
    'submit_job' => 'true',
    'job_title' => 'Full Stack Developer - New System Test',
    'description' => 'We are looking for an experienced Full Stack Developer to join our dynamic team. You will be responsible for developing both front-end and back-end applications, working with modern technologies, and contributing to our growing platform. This is an exciting opportunity to work on cutting-edge projects in a collaborative environment.',
    'requirements' => 'Bachelor degree in Computer Science or related field, 3+ years experience with PHP, JavaScript, MySQL, experience with modern frameworks like React or Vue.js, strong problem-solving skills',
    'responsibilities' => 'Develop and maintain web applications, collaborate with design team, write clean and maintainable code, participate in code reviews, troubleshoot and debug applications',
    'benefits' => 'Competitive salary, health insurance, flexible working hours, remote work options, professional development budget, annual leave',
    'job_type' => 'permanent',
    'employment_type' => 'full_time',
    'location' => 'Lagos',
    'location_type' => 'hybrid',
    'salary_min' => '250000',
    'salary_max' => '450000',
    'salary_period' => 'monthly',
    'experience_level' => 'mid',
    'education_level' => 'bsc',
    'application_email' => 'careers@testcompany.ng',
    'application_deadline' => date('Y-m-d', strtotime('+30 days')),
    'remote_friendly' => '1',
    'is_urgent' => '1'
];

$_SERVER['REQUEST_METHOD'] = 'POST';

echo "🧪 Testing job posting with comprehensive data...\n";

// Include the new job posting logic (extract the PHP logic)
$userId = 2;

// Get user info
try {
    $stmt = $pdo->prepare("SELECT user_type, first_name, last_name, email FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userInfo = $stmt->fetch();
    
    if (!$userInfo) {
        echo "❌ User not found\n";
        exit;
    }
    
    echo "✅ User verified: " . $userInfo['email'] . " (" . $userInfo['user_type'] . ")\n";
    
    // Validation
    $errors = [];
    
    $required_fields = [
        'job_title' => 'Job Title',
        'description' => 'Job Description', 
        'requirements' => 'Requirements',
        'job_type' => 'Job Type',
        'location' => 'Location'
    ];
    
    foreach ($required_fields as $field => $label) {
        if (empty(trim($_POST[$field] ?? ''))) {
            $errors[] = $label . ' is required';
        }
    }
    
    // Length validations
    if (!empty($_POST['job_title']) && strlen(trim($_POST['job_title'])) < 5) {
        $errors[] = 'Job title must be at least 5 characters';
    }
    
    if (!empty($_POST['description']) && strlen(trim($_POST['description'])) < 50) {
        $errors[] = 'Job description must be at least 50 characters';
    }
    
    if (!empty($_POST['requirements']) && strlen(trim($_POST['requirements'])) < 10) {
        $errors[] = 'Requirements must be at least 10 characters';
    }
    
    if (!empty($errors)) {
        echo "❌ Validation errors:\n";
        foreach ($errors as $error) {
            echo "   • $error\n";
        }
        exit;
    }
    
    echo "✅ All validations passed\n";
    
    // Generate unique slug
    $base_slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', trim($_POST['job_title'])));
    $base_slug = trim($base_slug, '-');
    
    $slug = $base_slug;
    $counter = 1;
    while (true) {
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE slug = ?");
        $check_stmt->execute([$slug]);
        if ($check_stmt->fetchColumn() == 0) {
            break;
        }
        $slug = $base_slug . '-' . $counter;
        $counter++;
    }
    
    echo "✅ Generated unique slug: $slug\n";
    
    // Get company name
    $company_name = trim(($userInfo['first_name'] ?? '') . ' ' . ($userInfo['last_name'] ?? ''));
    if (empty(trim($company_name))) {
        $company_name = 'Employer Company';
    }
    
    // Prepare job data
    $job_data = [
        'employer_id' => $userId,
        'title' => trim($_POST['job_title']),
        'slug' => $slug,
        'job_type' => $_POST['job_type'],
        'employment_type' => $_POST['employment_type'] ?? 'full_time',
        'description' => trim($_POST['description']),
        'requirements' => trim($_POST['requirements']),
        'responsibilities' => trim($_POST['responsibilities'] ?? ''),
        'benefits' => trim($_POST['benefits'] ?? ''),
        'salary_min' => !empty($_POST['salary_min']) ? (int)$_POST['salary_min'] : null,
        'salary_max' => !empty($_POST['salary_max']) ? (int)$_POST['salary_max'] : null,
        'salary_currency' => 'NGN',
        'salary_period' => $_POST['salary_period'] ?? 'monthly',
        'location_type' => $_POST['location_type'] ?? 'onsite',
        'state' => $_POST['location'],
        'city' => $_POST['location'],
        'address' => trim($_POST['job_address'] ?? ''),
        'experience_level' => $_POST['experience_level'] ?? 'entry',
        'education_level' => $_POST['education_level'] ?? 'any',
        'application_deadline' => !empty($_POST['application_deadline']) ? $_POST['application_deadline'] : null,
        'application_email' => !empty($_POST['application_email']) ? $_POST['application_email'] : null,
        'application_url' => trim($_POST['application_url'] ?? ''),
        'company_name' => $company_name,
        'is_featured' => 0,
        'is_urgent' => isset($_POST['is_urgent']) ? 1 : 0,
        'is_remote_friendly' => isset($_POST['remote_friendly']) ? 1 : 0,
        'views_count' => 0,
        'applications_count' => 0,
        'STATUS' => 'active'
    ];
    
    echo "✅ Job data prepared\n";
    
    // Insert job
    $sql = "INSERT INTO jobs (
        employer_id, title, slug, job_type, employment_type,
        description, requirements, responsibilities, benefits,
        salary_min, salary_max, salary_currency, salary_period,
        location_type, state, city, address,
        experience_level, education_level, application_deadline,
        application_email, application_url, company_name,
        is_featured, is_urgent, is_remote_friendly,
        views_count, applications_count, STATUS, created_at, updated_at
    ) VALUES (
        :employer_id, :title, :slug, :job_type, :employment_type,
        :description, :requirements, :responsibilities, :benefits,
        :salary_min, :salary_max, :salary_currency, :salary_period,
        :location_type, :state, :city, :address,
        :experience_level, :education_level, :application_deadline,
        :application_email, :application_url, :company_name,
        :is_featured, :is_urgent, :is_remote_friendly,
        :views_count, :applications_count, :STATUS, NOW(), NOW()
    )";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($job_data);
    
    if ($result) {
        $job_id = $pdo->lastInsertId();
        echo "✅ Job inserted successfully! Job ID: #$job_id\n";
        
        // Verify the job was inserted correctly
        $verify_stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ?");
        $verify_stmt->execute([$job_id]);
        $job = $verify_stmt->fetch();
        
        if ($job) {
            echo "✅ Job verification successful\n";
            echo "   📋 Title: " . $job['title'] . "\n";
            echo "   🏢 Company: " . $job['company_name'] . "\n";
            echo "   📍 Location: " . $job['state'] . " (" . $job['location_type'] . ")\n";
            echo "   💼 Type: " . $job['job_type'] . " / " . $job['employment_type'] . "\n";
            echo "   💰 Salary: ₦" . number_format($job['salary_min']) . " - ₦" . number_format($job['salary_max']) . " " . $job['salary_period'] . "\n";
            echo "   📊 Status: " . $job['STATUS'] . "\n";
            echo "   🎯 Experience: " . $job['experience_level'] . "\n";
            echo "   🎓 Education: " . $job['education_level'] . "\n";
            echo "   📧 Apply to: " . $job['application_email'] . "\n";
            echo "   📅 Deadline: " . $job['application_deadline'] . "\n";
            echo "   🚀 Urgent: " . ($job['is_urgent'] ? 'Yes' : 'No') . "\n";
            echo "   🌐 Remote: " . ($job['is_remote_friendly'] ? 'Yes' : 'No') . "\n";
            
            // Test dashboard query
            echo "\n🔍 Testing dashboard visibility...\n";
            $dashboard_stmt = $pdo->prepare("
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
                LIMIT 1
            ");
            $dashboard_stmt->execute([$userId]);
            $dashboard_job = $dashboard_stmt->fetch();
            
            if ($dashboard_job && $dashboard_job['id'] == $job_id) {
                echo "✅ Job appears correctly in dashboard query\n";
            } else {
                echo "❌ Job NOT appearing in dashboard query\n";
            }
            
        } else {
            echo "❌ Job verification failed - job not found after insertion\n";
        }
        
    } else {
        echo "❌ Job insertion failed\n";
        print_r($stmt->errorInfo());
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🎉 New job posting system test completed!\n";
echo "🌐 Test the new form at: http://localhost/findajob/pages/company/post-job-new.php\n";
echo "📊 Check dashboard at: http://localhost/findajob/pages/company/dashboard.php\n";
?>