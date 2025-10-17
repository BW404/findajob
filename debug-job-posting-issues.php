<?php
session_start();
include 'config/database.php';

echo "🔍 Debugging Main Job Posting Form Issues...\n\n";

// Set up session for employer test2@gmail.com
$_SESSION['user_id'] = 2;
$_SESSION['user_type'] = 'employer';

try {
    // Test database connection
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE employer_id = 2");
    $stmt->execute();
    $current_jobs = $stmt->fetchColumn();
    echo "✅ Database connection: OK\n";
    echo "✅ Current jobs: $current_jobs\n";
    
    // Test categories
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM job_categories WHERE is_active = 1");
    $stmt->execute();
    $cat_count = $stmt->fetchColumn();
    echo "✅ Categories available: $cat_count\n";
    
    // Test user verification
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = 2");
    $stmt->execute();
    $user = $stmt->fetch();
    echo "✅ User found: " . $user['email'] . "\n";
    
    // Simulate form data that might be causing issues
    $test_cases = [
        [
            'name' => 'Basic Job Post',
            'data' => [
                'job_title' => 'Test Developer Position',
                'category' => 1,
                'job_type' => 'full-time',
                'location' => 'Lagos',
                'description' => 'This is a test job description for debugging purposes. We are looking for a skilled developer to join our team.',
                'requirements' => 'Bachelor degree in Computer Science. Experience with PHP and JavaScript.',
                'salary_min' => 100000,
                'salary_max' => 200000
            ]
        ],
        [
            'name' => 'Empty Field Test',
            'data' => [
                'job_title' => '',
                'category' => 1,
                'job_type' => 'full-time',
                'location' => 'Lagos',
                'description' => 'Test description',
                'requirements' => 'Test requirements'
            ]
        ],
        [
            'name' => 'Invalid Category Test',
            'data' => [
                'job_title' => 'Test Job',
                'category' => 999,
                'job_type' => 'full-time',
                'location' => 'Lagos',
                'description' => 'Test description for invalid category testing.',
                'requirements' => 'Test requirements'
            ]
        ]
    ];
    
    foreach ($test_cases as $test) {
        echo "\n🧪 Testing: " . $test['name'] . "\n";
        
        // Basic validation
        $required_fields = ['job_title', 'category', 'job_type', 'location', 'description', 'requirements'];
        $errors = [];
        
        foreach ($required_fields as $field) {
            if (empty(trim($test['data'][$field] ?? ''))) {
                $errors[] = $field;
            }
        }
        
        if (!empty($errors)) {
            echo "❌ Missing required fields: " . implode(', ', $errors) . "\n";
            continue;
        }
        
        // Check category exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM job_categories WHERE id = ? AND is_active = 1");
        $stmt->execute([$test['data']['category']]);
        if ($stmt->fetchColumn() == 0) {
            echo "❌ Invalid category ID: " . $test['data']['category'] . "\n";
            continue;
        }
        
        echo "✅ Validation passed\n";
    }
    
    echo "\n🎯 Possible Issues in Main Form:\n";
    echo "1. JavaScript validation preventing submission\n";
    echo "2. Form field name mismatches\n";
    echo "3. Session timeout or authentication issues\n";
    echo "4. Hidden form fields with invalid values\n";
    echo "5. Client-side validation blocking submission\n";
    echo "6. Form action attribute pointing to wrong location\n";
    
    echo "\n🌐 Test URLs:\n";
    echo "📝 Simple Test Form: http://localhost/findajob/pages/company/simple-job-test.php\n";
    echo "🐛 Main Form Debug: http://localhost/findajob/pages/company/post-job.php?debug=1\n";
    echo "📊 Dashboard: http://localhost/findajob/pages/company/dashboard.php\n";
    
    echo "\n💡 Debugging Steps:\n";
    echo "1. Use the simple test form to verify basic functionality\n";
    echo "2. Check browser console for JavaScript errors\n";
    echo "3. Compare working simple form vs main form\n";
    echo "4. Check network tab to see if form is actually submitting\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🎉 Simple test form created for debugging!\n";
?>