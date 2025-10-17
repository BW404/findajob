<?php
// Diagnostic: Check job posting issues
include 'config/database.php';

echo "🔍 Job Posting System Diagnostic\n\n";

// Check recent jobs
$stmt = $pdo->query("
    SELECT j.*, u.first_name, u.last_name 
    FROM jobs j 
    LEFT JOIN users u ON j.employer_id = u.id 
    ORDER BY j.created_at DESC 
    LIMIT 5
");
$jobs = $stmt->fetchAll();

echo "📋 Recent Jobs Posted:\n";
foreach ($jobs as $job) {
    echo sprintf("  #%d: %s (by %s %s) - Status: %s\n", 
        $job['id'], 
        $job['title'], 
        $job['first_name'] ?? 'Unknown', 
        $job['last_name'] ?? 'User',
        $job['STATUS']
    );
    
    // Check for empty required fields
    $issues = [];
    if (empty($job['description'])) $issues[] = 'Missing description';
    if (empty($job['requirements'])) $issues[] = 'Missing requirements';
    if (empty($job['job_type'])) $issues[] = 'Missing job_type';
    if (empty($job['state'])) $issues[] = 'Missing location';
    
    if (!empty($issues)) {
        echo "    ⚠️  Issues: " . implode(', ', $issues) . "\n";
    } else {
        echo "    ✅ All required fields present\n";
    }
}

echo "\n📊 Database Statistics:\n";
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total_jobs,
        COUNT(CASE WHEN STATUS = 'active' THEN 1 END) as active_jobs,
        COUNT(CASE WHEN requirements IS NULL OR requirements = '' THEN 1 END) as missing_requirements,
        COUNT(CASE WHEN responsibilities IS NULL OR responsibilities = '' THEN 1 END) as missing_responsibilities,
        COUNT(CASE WHEN benefits IS NULL OR benefits = '' THEN 1 END) as missing_benefits
    FROM jobs
")->fetch();

echo sprintf("  Total Jobs: %d\n", $stats['total_jobs']);
echo sprintf("  Active Jobs: %d\n", $stats['active_jobs']);
echo sprintf("  Jobs missing requirements: %d\n", $stats['missing_requirements']);
echo sprintf("  Jobs missing responsibilities: %d\n", $stats['missing_responsibilities']);
echo sprintf("  Jobs missing benefits: %d\n", $stats['missing_benefits']);

echo "\n✅ Diagnostic complete!\n";
?>