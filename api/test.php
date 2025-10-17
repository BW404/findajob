<?php
require_once '../config/database.php';

// Test the job search API
$testKeywords = 'developer';
$testLocation = 'lagos';

echo "<h1>Job Search API Test</h1>";

// Test basic search
echo "<h2>Testing API with keywords: '$testKeywords' and location: '$testLocation'</h2>";

$params = http_build_query([
    'keywords' => $testKeywords,
    'location' => $testLocation,
    'limit' => 5
]);

$apiUrl = "http://localhost/findajob/api/jobs.php?$params";
echo "<p>API URL: <a href='$apiUrl' target='_blank'>$apiUrl</a></p>";

// Make API call
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h3>Response (HTTP $httpCode):</h3>";
echo "<pre>";
echo htmlspecialchars($response);
echo "</pre>";

// Test database connection and sample data
echo "<h2>Database Test</h2>";

try {
    // Check if jobs table exists and has data
    $stmt = $pdo->query("SELECT COUNT(*) as job_count FROM jobs");
    $result = $stmt->fetch();
    echo "<p>Total jobs in database: " . $result['job_count'] . "</p>";
    
    if ($result['job_count'] == 0) {
        echo "<p style='color: orange;'>⚠️ No jobs found. Inserting sample data...</p>";
        
        // Insert sample job categories if they don't exist
        $stmt = $pdo->query("SELECT COUNT(*) as cat_count FROM job_categories");
        $catResult = $stmt->fetch();
        
        if ($catResult['cat_count'] == 0) {
            echo "<p>Inserting job categories...</p>";
            $pdo->exec("
                INSERT INTO job_categories (name, slug, description, icon, is_active) VALUES 
                ('Technology', 'technology', 'Software development, IT, cybersecurity, and tech roles', '💻', TRUE),
                ('Banking & Finance', 'banking-finance', 'Banking, accounting, financial services, and investment', '🏦', TRUE),
                ('Oil & Gas', 'oil-gas', 'Petroleum, energy, and oil industry positions', '⛽', TRUE),
                ('Healthcare', 'healthcare', 'Medical, nursing, pharmaceutical, and health services', '🏥', TRUE),
                ('Education', 'education', 'Teaching, training, academic, and educational roles', '🎓', TRUE)
            ");
        }
        
        // Insert sample employer
        $pdo->exec("
            INSERT IGNORE INTO users (id, user_type, email, password_hash, first_name, last_name, phone, email_verified, is_active) VALUES 
            (100, 'employer', 'hr@techcorp.ng', '\$2y\$10\$example_hash', 'Tech', 'Corp', '+234-800-TECH', TRUE, TRUE)
        ");
        
        $pdo->exec("
            INSERT IGNORE INTO employer_profiles (id, user_id, company_name, industry, company_size, website, description, address, state, city, is_verified, verification_status, subscription_type) VALUES 
            (100, 100, 'TechCorp Nigeria', 'Technology', '201-500', 'https://techcorp.ng', 'Leading software development company in Nigeria', 'Plot 15, Admiralty Way, Lekki Phase 1', 'Lagos', 'Lagos', TRUE, 'verified', 'pro')
        ");
        
        // Insert sample jobs
        $pdo->exec("
            INSERT INTO jobs (
                employer_id, title, slug, category_id, job_type, employment_type,
                description, requirements, salary_min, salary_max, salary_currency, salary_period,
                location_type, state, city, experience_level, education_level, 
                application_email, company_name, is_featured, status, created_at
            ) VALUES 
            (100, 'Senior Software Developer', 'senior-software-developer-1697132800', 1, 'permanent', 'full_time',
             'We are seeking an experienced Software Developer to join our growing team in Lagos. You will work on cutting-edge applications using modern technologies.',
             'Bachelor degree in Computer Science, 5+ years experience in software development, Strong knowledge of PHP, JavaScript, React',
             500000, 800000, 'NGN', 'monthly',
             'onsite', 'Lagos', 'Lagos', 'senior', 'bsc',
             'careers@techcorp.ng', 'TechCorp Nigeria', TRUE, 'active', NOW()),
            
            (100, 'Frontend Developer', 'frontend-developer-1697132900', 1, 'permanent', 'full_time',
             'Join our frontend development team to build innovative web applications for Nigerian businesses.',
             'Experience with React, Vue.js or Angular, Strong CSS and JavaScript skills, Portfolio of web projects',
             300000, 500000, 'NGN', 'monthly',
             'hybrid', 'Lagos', 'Lagos', 'mid', 'bsc',
             'careers@techcorp.ng', 'TechCorp Nigeria', FALSE, 'active', NOW()),
             
            (100, 'Junior Developer (Graduate)', 'junior-developer-graduate-1697133000', 1, 'permanent', 'full_time',
             'Perfect opportunity for recent graduates to start their tech career with comprehensive training and mentorship.',
             'Recent graduate in Computer Science or related field, Basic programming knowledge, Willingness to learn',
             200000, 350000, 'NGN', 'monthly',
             'onsite', 'Lagos', 'Lagos', 'entry', 'bsc',
             'careers@techcorp.ng', 'TechCorp Nigeria', FALSE, 'active', NOW())
        ");
        
        echo "<p style='color: green;'>✅ Sample data inserted successfully!</p>";
    }
    
    // Show sample jobs
    echo "<h3>Sample Jobs in Database:</h3>";
    $stmt = $pdo->query("
        SELECT j.title, j.company_name, j.state, j.city, j.job_type, j.salary_min, j.salary_max, j.created_at 
        FROM jobs j 
        WHERE j.status = 'active' 
        ORDER BY j.created_at DESC 
        LIMIT 10
    ");
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Title</th><th>Company</th><th>Location</th><th>Type</th><th>Salary</th><th>Created</th></tr>";
    
    while ($job = $stmt->fetch()) {
        $salary = '';
        if ($job['salary_min'] && $job['salary_max']) {
            $salary = "₦" . number_format($job['salary_min']) . " - ₦" . number_format($job['salary_max']);
        }
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($job['title']) . "</td>";
        echo "<td>" . htmlspecialchars($job['company_name']) . "</td>";
        echo "<td>" . htmlspecialchars($job['city'] . ', ' . $job['state']) . "</td>";
        echo "<td>" . htmlspecialchars($job['job_type']) . "</td>";
        echo "<td>" . $salary . "</td>";
        echo "<td>" . $job['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='browse.php'>→ Test Job Browse Page</a></p>";
echo "<p><a href='../../'>→ Back to Home</a></p>";
?>