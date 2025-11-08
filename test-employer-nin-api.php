<?php
/**
 * Test Employer NIN Verification API
 */

// Start session first
session_start();

require_once 'config/database.php';
require_once 'config/constants.php';

// Get or create an employer user for testing
$stmt = $pdo->query("SELECT id FROM users WHERE user_type = 'employer' LIMIT 1");
$employer = $stmt->fetch();

if (!$employer) {
    die("No employer found in database. Please register an employer first.\n");
}

// Set session to simulate logged-in employer
$_SESSION['user_id'] = $employer['id'];
$_SESSION['user_type'] = 'employer';
$_SESSION['logged_in'] = true;

echo "Testing Employer NIN Verification API\n";
echo "======================================\n\n";

echo "Employer User ID: " . $employer['id'] . "\n\n";

// Test NIN (11 digits - use a test NIN for sandbox)
$testNIN = '12345678901';

echo "Testing with NIN: $testNIN\n\n";

// Simulate POST request
$_SERVER['REQUEST_METHOD'] = 'POST';

// Simulate JSON input
$jsonInput = json_encode(['nin' => $testNIN]);
file_put_contents('php://memory', $jsonInput);

// Capture output
ob_start();

// Include the API file
include 'api/verify-employer-nin.php';

$output = ob_get_clean();

echo "API Response:\n";
echo $output . "\n";

// Parse JSON response
$response = json_decode($output, true);

if ($response) {
    echo "\nParsed Response:\n";
    echo "Success: " . ($response['success'] ? 'true' : 'false') . "\n";
    
    if (isset($response['error'])) {
        echo "Error: " . $response['error'] . "\n";
    }
    
    if (isset($response['message'])) {
        echo "Message: " . $response['message'] . "\n";
    }
    
    if (isset($response['data'])) {
        echo "Data: " . print_r($response['data'], true) . "\n";
    }
} else {
    echo "\nFailed to parse JSON response\n";
}

echo "\n\nChecking database logs...\n";
echo "========================\n\n";

// Check verification_transactions
$stmt = $pdo->prepare("SELECT * FROM verification_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$employer['id']]);
$transaction = $stmt->fetch();

if ($transaction) {
    echo "Latest Transaction:\n";
    echo "  Reference: " . $transaction['reference'] . "\n";
    echo "  Status: " . $transaction['status'] . "\n";
    echo "  Amount: ₦" . number_format($transaction['amount'], 2) . "\n";
    echo "  Created: " . $transaction['created_at'] . "\n";
} else {
    echo "No transaction found\n";
}

// Check verification_audit_log
$stmt = $pdo->prepare("SELECT * FROM verification_audit_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$employer['id']]);
$log = $stmt->fetch();

if ($log) {
    echo "\nLatest Verification Log:\n";
    echo "  Type: " . $log['verification_type'] . "\n";
    echo "  NIN: " . $log['nin_number'] . "\n";
    echo "  Status: " . $log['status'] . "\n";
    echo "  Provider: " . $log['api_provider'] . "\n";
    if ($log['error_message']) {
        echo "  Error: " . $log['error_message'] . "\n";
    }
    echo "  Created: " . $log['created_at'] . "\n";
} else {
    echo "No verification log found\n";
}

// Check employer profile
$stmt = $pdo->prepare("
    SELECT provider_nin, provider_nin_verified, provider_nin_verified_at 
    FROM employer_profiles 
    WHERE user_id = ?
");
$stmt->execute([$employer['id']]);
$profile = $stmt->fetch();

if ($profile) {
    echo "\nEmployer Profile NIN Status:\n";
    echo "  NIN: " . ($profile['provider_nin'] ?? 'Not set') . "\n";
    echo "  Verified: " . ($profile['provider_nin_verified'] ? 'Yes' : 'No') . "\n";
    echo "  Verified At: " . ($profile['provider_nin_verified_at'] ?? 'N/A') . "\n";
}
