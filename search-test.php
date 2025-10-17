<?php
// Simple test page to verify search functionality
require_once 'config/database.php';

echo "<h2>Search Test</h2>";

// Test database connection
echo "<p>Database connection: ";
try {
    $pdo->query("SELECT 1");
    echo "✅ Connected</p>";
} catch (Exception $e) {
    echo "❌ Failed - " . $e->getMessage() . "</p>";
}

// Test jobs table
echo "<p>Jobs table: ";
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM jobs");
    $count = $stmt->fetchColumn();
    echo "✅ Found $count jobs</p>";
} catch (Exception $e) {
    echo "❌ Failed - " . $e->getMessage() . "</p>";
}

// Test categories table
echo "<p>Categories table: ";
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM job_categories");
    $count = $stmt->fetchColumn();
    echo "✅ Found $count categories</p>";
} catch (Exception $e) {
    echo "❌ Failed - " . $e->getMessage() . "</p>";
}

// Test search API
echo "<p>Search API test: ";
$testKeywords = "software";
$apiUrl = "http://localhost/findajob/api/jobs.php?keywords=" . urlencode($testKeywords);

$context = stream_context_create([
    'http' => [
        'timeout' => 5,
        'method' => 'GET'
    ]
]);

$result = @file_get_contents($apiUrl, false, $context);
if ($result !== false) {
    $data = json_decode($result, true);
    if ($data && isset($data['success']) && $data['success']) {
        echo "✅ API working - Found " . count($data['jobs'] ?? []) . " results for '$testKeywords'</p>";
    } else {
        echo "❌ API error - " . ($data['error'] ?? 'Unknown error') . "</p>";
    }
} else {
    echo "❌ API unreachable</p>";
}

// Direct form test
echo "<h3>Direct Search Test</h3>";
echo "<form action='pages/jobs/browse.php' method='GET'>";
echo "<input type='text' name='keywords' placeholder='Enter keywords...' value='software'>";
echo "<input type='text' name='location' placeholder='Enter location...' value='lagos'>";
echo "<button type='submit'>Test Search</button>";
echo "</form>";

echo "<h3>Homepage Search Link Test</h3>";
echo "<a href='pages/jobs/browse.php?keywords=developer'>Test Developer Search</a><br>";
echo "<a href='pages/jobs/browse.php?keywords=marketing&location=abuja'>Test Marketing in Abuja</a><br>";
?>