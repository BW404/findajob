<?php
session_start();
include 'config/database.php';

echo "🔧 Testing Simple Job Test Form...\n\n";

// Set up session for employer test2@gmail.com
$_SESSION['user_id'] = 2;
$_SESSION['user_type'] = 'employer';

try {
    // Test if the simple form can connect to database
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE employer_id = 2");
    $stmt->execute();
    $current_jobs = $stmt->fetchColumn();
    echo "✅ Database connection: OK\n";
    echo "✅ Current jobs for employer 2: $current_jobs\n";
    
    // Test categories loading
    $stmt = $pdo->prepare("SELECT id, name FROM job_categories WHERE is_active = 1 ORDER BY name LIMIT 5");
    $stmt->execute();
    $sample_categories = $stmt->fetchAll();
    
    echo "✅ Sample categories loaded:\n";
    foreach ($sample_categories as $cat) {
        echo "   - ID {$cat['id']}: {$cat['name']}\n";
    }
    
    // Test basic job insertion process
    echo "\n🧪 Testing job insertion process...\n";
    
    $test_job_data = [
        'employer_id' => 2,
        'title' => 'Simple Test Job - ' . date('Y-m-d H:i:s'),
        'slug' => 'simple-test-job-' . time(),
        'category_id' => 1,
        'job_type' => 'permanent',
        'employment_type' => 'full_time',
        'description' => 'This is a test job created to verify the job posting system works correctly.',
        'requirements' => 'Test requirements for debugging purposes.',
        'responsibilities' => 'Test responsibilities',
        'benefits' => 'Test benefits',
        'salary_min' => 50000,
        'salary_max' => 100000,
        'salary_currency' => 'NGN',
        'salary_period' => 'monthly',
        'location_type' => 'onsite',
        'state' => 'Lagos',
        'city' => 'Lagos',
        'address' => '',
        'experience_level' => 'entry',
        'education_level' => 'any',
        'application_deadline' => date('Y-m-d', strtotime('+30 days')),
        'application_email' => 'test@example.com',
        'application_url' => '',
        'company_name' => 'Test Company',
        'is_featured' => 0,
        'is_urgent' => 0,
        'is_remote_friendly' => 0,
        'views_count' => 0,
        'applications_count' => 0,
        'STATUS' => 'active'
    ];
    
    $sql = "INSERT INTO jobs (
        employer_id, title, slug, category_id, job_type, employment_type,
        description, requirements, responsibilities, benefits,
        salary_min, salary_max, salary_currency, salary_period,
        location_type, state, city, address,
        experience_level, education_level, application_deadline,
        application_email, application_url, company_name,
        is_featured, is_urgent, is_remote_friendly,
        views_count, applications_count, STATUS, created_at, updated_at
    ) VALUES (
        :employer_id, :title, :slug, :category_id, :job_type, :employment_type,
        :description, :requirements, :responsibilities, :benefits,
        :salary_min, :salary_max, :salary_currency, :salary_period,
        :location_type, :state, :city, :address,
        :experience_level, :education_level, :application_deadline,
        :application_email, :application_url, :company_name,
        :is_featured, :is_urgent, :is_remote_friendly,
        :views_count, :applications_count, :STATUS, NOW(), NOW()
    )";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($test_job_data);
    
    if ($result) {
        $job_id = $pdo->lastInsertId();
        echo "✅ Test job inserted successfully! Job ID: #$job_id\n";
        
        // Delete the test job immediately
        $delete_stmt = $pdo->prepare("DELETE FROM jobs WHERE id = ?");
        $delete_stmt->execute([$job_id]);
        echo "✅ Test job deleted (cleanup)\n";
        
    } else {
        echo "❌ Test job insertion failed\n";
        echo "Error: " . json_encode($stmt->errorInfo()) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Test error: " . $e->getMessage() . "\n";
}

echo "\n🌐 Simple test form should now work at:\n";
echo "   📝 http://localhost/findajob/pages/company/simple-job-test.php\n";

echo "\n🎯 If the simple form works, the main form issue is likely:\n";
echo "   1. JavaScript validation blocking submission\n";
echo "   2. Form field name mismatches\n";
echo "   3. Multi-step form navigation issues\n";
echo "   4. Client-side validation preventing POST\n";

echo "\n🎉 Simple form path fixed and ready to test!\n";
?>