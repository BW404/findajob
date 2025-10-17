<?php
// Database setup and sample data insertion script
// Run this to set up the FindAJob database with sample data

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>FindAJob Database Setup</h1>";
echo "<p>Setting up database with sample data...</p>";

try {
    // Database connection for XAMPP at E:\XAMPP
    $host = 'localhost';
    $dbname = 'findajob_ng';
    $username = 'root';
    $password = '';
    
    // Create database connection
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Connected to MySQL server</p>";
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p>✅ Database '$dbname' created/verified</p>";
    
    // Select the database
    $pdo->exec("USE `$dbname`");
    
    // Check if tables exist
    $stmt = $pdo->query("SHOW TABLES LIKE 'jobs'");
    if ($stmt->rowCount() == 0) {
        echo "<p>⚠️ Tables don't exist. Please run the schema.sql first.</p>";
        echo "<p><strong>Steps to fix:</strong></p>";
        echo "<ol>";
        echo "<li>Open phpMyAdmin (http://localhost/phpmyadmin)</li>";
        echo "<li>Create database 'findajob_ng' if it doesn't exist</li>";
        echo "<li>Import the file: database/schema.sql</li>";
        echo "<li>Then run this script again</li>";
        echo "</ol>";
        exit;
    }
    
    echo "<p>✅ Tables exist</p>";
    
    // Insert job categories
    echo "<h3>Inserting Job Categories...</h3>";
    $pdo->exec("
        INSERT IGNORE INTO job_categories (name, slug, description, icon, is_active) VALUES 
        ('Technology', 'technology', 'Software development, IT, cybersecurity, and tech roles', '💻', TRUE),
        ('Banking & Finance', 'banking-finance', 'Banking, accounting, financial services, and investment', '🏦', TRUE),
        ('Oil & Gas', 'oil-gas', 'Petroleum, energy, and oil industry positions', '⛽', TRUE),
        ('Healthcare', 'healthcare', 'Medical, nursing, pharmaceutical, and health services', '🏥', TRUE),
        ('Education', 'education', 'Teaching, training, academic, and educational roles', '🎓', TRUE),
        ('Engineering', 'engineering', 'Civil, mechanical, electrical, and engineering disciplines', '⚙️', TRUE),
        ('Sales & Marketing', 'sales-marketing', 'Sales, marketing, advertising, and business development', '📈', TRUE),
        ('Government', 'government', 'Public sector, civil service, and government positions', '🏛️', TRUE),
        ('Manufacturing', 'manufacturing', 'Production, quality control, and manufacturing roles', '🏭', TRUE),
        ('Agriculture', 'agriculture', 'Farming, agribusiness, and agricultural development', '🌾', TRUE)
    ");
    echo "<p>✅ Job categories inserted</p>";
    
    // Insert sample employer users
    echo "<h3>Creating Sample Employers...</h3>";
    $pdo->exec("
        INSERT IGNORE INTO users (id, user_type, email, password_hash, first_name, last_name, phone, email_verified, is_active) VALUES 
        (100, 'employer', 'hr@techcorp.ng', '\$2y\$10\$example_hash', 'Tech', 'Corp', '+234-800-TECH', TRUE, TRUE),
        (101, 'employer', 'jobs@bankplus.ng', '\$2y\$10\$example_hash', 'Bank', 'Plus', '+234-800-BANK', TRUE, TRUE),
        (102, 'employer', 'careers@oilfield.ng', '\$2y\$10\$example_hash', 'Oil', 'Field', '+234-800-OIL', TRUE, TRUE),
        (103, 'employer', 'hr@healthcorp.ng', '\$2y\$10\$example_hash', 'Health', 'Corp', '+234-800-HEAL', TRUE, TRUE),
        (104, 'employer', 'talent@startup.ng', '\$2y\$10\$example_hash', 'Start', 'Up', '+234-800-START', TRUE, TRUE)
    ");
    echo "<p>✅ Sample employer users created</p>";
    
    // Insert employer profiles
    echo "<h3>Creating Employer Profiles...</h3>";
    $pdo->exec("
        INSERT IGNORE INTO employer_profiles (id, user_id, company_name, industry, company_size, website, description, address, state, city, is_verified, verification_status, subscription_type) VALUES 
        (100, 100, 'TechCorp Nigeria', 'Technology', '201-500', 'https://techcorp.ng', 'Leading software development company in Nigeria specializing in fintech and e-commerce solutions.', 'Plot 15, Admiralty Way, Lekki Phase 1', 'Lagos', 'Lagos', TRUE, 'verified', 'pro'),
        (101, 101, 'BankPlus Limited', 'Banking & Finance', '500+', 'https://bankplus.ng', 'Premier commercial bank offering comprehensive financial services across Nigeria.', '23 Marina Street, Lagos Island', 'Lagos', 'Lagos', TRUE, 'verified', 'pro'),
        (102, 102, 'OilField Services Ltd', 'Oil & Gas', '201-500', 'https://oilfield.ng', 'Providing drilling and petroleum engineering services to major oil companies in Nigeria.', 'Port Harcourt Industrial Layout', 'Rivers', 'Port Harcourt', TRUE, 'verified', 'free'),
        (103, 103, 'HealthCorp Medical', 'Healthcare', '51-200', 'https://healthcorp.ng', 'Modern healthcare facility providing quality medical services and equipment.', 'Wuse 2, Central Business District', 'Abuja', 'Abuja', TRUE, 'verified', 'pro'),
        (104, 104, 'StartUp Innovations', 'Technology', '11-50', 'https://startup.ng', 'Fast-growing startup focused on mobile app development and digital marketing.', '45 Allen Avenue, Ikeja', 'Lagos', 'Ikeja', FALSE, 'pending', 'free')
    ");
    echo "<p>✅ Employer profiles created</p>";
    
    // Insert sample jobs
    echo "<h3>Creating Sample Jobs...</h3>";
    
    // Technology Jobs
    $pdo->exec("
        INSERT IGNORE INTO jobs (
            employer_id, title, slug, category_id, job_type, employment_type,
            description, requirements, responsibilities, benefits,
            salary_min, salary_max, salary_currency, salary_period,
            location_type, state, city, address,
            experience_level, education_level, application_deadline,
            application_email, company_name,
            is_featured, is_urgent, is_remote_friendly, status, created_at
        ) VALUES 
        (100, 'Senior Full Stack Developer', 'senior-full-stack-developer-" . time() . "', 1, 'permanent', 'full_time',
         'We are seeking an experienced Full Stack Developer to join our growing team. You will work on cutting-edge fintech applications using modern technologies like React, Node.js, and cloud platforms.',
         '• 5+ years experience in full stack development\n• Proficiency in React, Node.js, TypeScript\n• Experience with AWS or Azure cloud platforms\n• Knowledge of database design (PostgreSQL, MongoDB)\n• Experience with CI/CD pipelines\n• Bachelor degree in Computer Science or related field',
         '• Design and develop scalable web applications\n• Collaborate with cross-functional teams\n• Write clean, maintainable code\n• Participate in code reviews and mentoring\n• Troubleshoot and debug applications\n• Stay updated with latest technology trends',
         '• Competitive salary with performance bonuses\n• Health insurance coverage\n• Remote work flexibility\n• Professional development opportunities\n• Modern office environment\n• Annual leave and sick days',
         800000, 1200000, 'NGN', 'monthly',
         'hybrid', 'Lagos', 'Lagos', 'Plot 15, Admiralty Way, Lekki Phase 1',
         'senior', 'bsc', '2024-12-31',
         'careers@techcorp.ng', 'TechCorp Nigeria',
         TRUE, FALSE, TRUE, 'active', NOW())
    ");
    
    $pdo->exec("
        INSERT IGNORE INTO jobs (
            employer_id, title, slug, category_id, job_type, employment_type,
            description, requirements, salary_min, salary_max, salary_currency, salary_period,
            location_type, state, city, experience_level, education_level, 
            application_email, company_name, status, created_at
        ) VALUES 
        (100, 'Frontend Developer', 'frontend-developer-" . time() . "1', 1, 'permanent', 'full_time',
         'Join our frontend development team to build innovative web applications for Nigerian businesses.',
         'Experience with React, Vue.js or Angular, Strong CSS and JavaScript skills, Portfolio of web projects',
         300000, 500000, 'NGN', 'monthly',
         'hybrid', 'Lagos', 'Lagos', 'mid', 'bsc',
         'careers@techcorp.ng', 'TechCorp Nigeria', 'active', NOW()),
         
        (100, 'Junior Developer (Graduate)', 'junior-developer-graduate-" . time() . "2', 1, 'permanent', 'full_time',
         'Perfect opportunity for recent graduates to start their tech career with comprehensive training and mentorship.',
         'Recent graduate in Computer Science or related field, Basic programming knowledge, Willingness to learn',
         200000, 350000, 'NGN', 'monthly',
         'onsite', 'Lagos', 'Lagos', 'entry', 'bsc',
         'careers@techcorp.ng', 'TechCorp Nigeria', 'active', NOW()),
         
        (101, 'Senior Business Analyst', 'senior-business-analyst-" . time() . "3', 2, 'permanent', 'full_time',
         'We are looking for an experienced Business Analyst to drive digital transformation initiatives and improve our banking processes.',
         '5+ years business analysis experience in banking, Strong analytical and problem-solving skills, Experience with process improvement methodologies',
         750000, 1100000, 'NGN', 'monthly',
         'onsite', 'Lagos', 'Lagos', 'senior', 'bsc',
         'jobs@bankplus.ng', 'BankPlus Limited', 'active', NOW()),
         
        (102, 'Drilling Engineer', 'drilling-engineer-" . time() . "4', 3, 'contract', 'full_time',
         'Experienced Drilling Engineer needed for offshore drilling operations. This is a 2-year contract position with rotation schedule.',
         'Bachelor degree in Petroleum Engineering, 5+ years offshore drilling experience, Knowledge of drilling software',
         1200000, 1800000, 'NGN', 'monthly',
         'onsite', 'Rivers', 'Port Harcourt', 'senior', 'bsc',
         'careers@oilfield.ng', 'OilField Services Ltd', 'active', NOW()),
         
        (103, 'Registered Nurse - ICU', 'registered-nurse-icu-" . time() . "5', 4, 'permanent', 'full_time',
         'We are seeking a dedicated ICU Nurse to join our critical care team. You will provide specialized nursing care to critically ill patients.',
         'Bachelor degree in Nursing (BSN), Current RN license in Nigeria, 2+ years ICU experience, BLS and ACLS certification',
         400000, 600000, 'NGN', 'monthly',
         'onsite', 'Abuja', 'Abuja', 'mid', 'bsc',
         'hr@healthcorp.ng', 'HealthCorp Medical', 'active', NOW())
    ");
    
    echo "<p>✅ Sample jobs created</p>";
    
    // Add FULLTEXT index for search functionality
    echo "<h3>Adding Search Indexes...</h3>";
    try {
        $pdo->exec("ALTER TABLE jobs ADD FULLTEXT KEY `search_text` (`title`, `description`)");
        echo "<p>✅ FULLTEXT search index added</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "<p>✅ FULLTEXT search index already exists</p>";
        } else {
            echo "<p>⚠️ Note: FULLTEXT index not added - " . $e->getMessage() . "</p>";
        }
    }
    
    // Update job counts with random values
    $pdo->exec("UPDATE jobs SET views_count = FLOOR(RAND() * 500) + 50, applications_count = FLOOR(RAND() * 25) + 1 WHERE id > 0");
    
    // Set some jobs as featured
    $pdo->exec("UPDATE jobs SET is_featured = TRUE WHERE id IN (SELECT id FROM (SELECT id FROM jobs ORDER BY RAND() LIMIT 3) AS temp)");
    
    echo "<p>✅ Job metadata updated</p>";
    
    // Show statistics
    echo "<h3>Database Statistics:</h3>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM job_categories");
    $categoryCount = $stmt->fetchColumn();
    echo "<p>Job Categories: $categoryCount</p>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'employer'");
    $employerCount = $stmt->fetchColumn();
    echo "<p>Employers: $employerCount</p>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM jobs WHERE status = 'active'");
    $jobCount = $stmt->fetchColumn();
    echo "<p>Active Jobs: $jobCount</p>";
    
    echo "<h3>✅ Setup Complete!</h3>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ul>";
    echo "<li><a href='/findajob/api/test.php'>Test API Functionality</a></li>";
    echo "<li><a href='/findajob/pages/jobs/browse.php'>Test Job Browse Page</a></li>";
    echo "<li><a href='/findajob/'>Go to Homepage</a></li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database Error: " . $e->getMessage() . "</p>";
    echo "<p><strong>Common Solutions:</strong></p>";
    echo "<ul>";
    echo "<li>Make sure XAMPP is running (Apache + MySQL)</li>";
    echo "<li>Check if MySQL is running on port 3306</li>";
    echo "<li>Verify database credentials in config/database.php</li>";
    echo "<li>Import database/schema.sql first if tables don't exist</li>";
    echo "</ul>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>