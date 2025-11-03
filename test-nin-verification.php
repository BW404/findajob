<?php
/**
 * Test NIN Verification System
 * This script tests the complete NIN verification flow
 */

require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'config/session.php';
require_once 'includes/functions.php';

// Only accessible in development mode
if (!isDevelopmentMode()) {
    die('This page is only available in development mode.');
}

$pageTitle = "NIN Verification Test";
$results = [];

// Test 1: Check database tables exist
$results['database'] = [];
try {
    $tables = ['job_seeker_profiles', 'verification_transactions', 'verification_audit_log'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $results['database'][$table] = '✅ Table exists';
        } else {
            $results['database'][$table] = '❌ Table missing - Run migration: database/add-nin-verification.sql';
        }
    }
} catch (Exception $e) {
    $results['database']['error'] = '❌ Database error: ' . $e->getMessage();
}

// Test 2: Check NIN columns exist
$results['columns'] = [];
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM job_seeker_profiles LIKE 'nin%'");
    $ninColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $expectedColumns = ['nin', 'nin_verified', 'nin_verified_at', 'nin_verification_data'];
    foreach ($expectedColumns as $col) {
        if (in_array($col, $ninColumns)) {
            $results['columns'][$col] = '✅ Column exists';
        } else {
            $results['columns'][$col] = '❌ Column missing - Run migration!';
        }
    }
} catch (Exception $e) {
    $results['columns']['error'] = '❌ Error checking columns: ' . $e->getMessage();
}

// Test 3: Check Dojah API configuration
$results['config'] = [];
if (defined('DOJAH_APP_ID') && DOJAH_APP_ID !== 'your_app_id_here') {
    $results['config']['app_id'] = '✅ DOJAH_APP_ID configured';
} else {
    $results['config']['app_id'] = '⚠️ DOJAH_APP_ID needs to be configured in constants.php';
}

if (defined('DOJAH_API_KEY') && DOJAH_API_KEY !== 'your_api_key_here') {
    $results['config']['api_key'] = '✅ DOJAH_API_KEY configured';
} else {
    $results['config']['api_key'] = '⚠️ DOJAH_API_KEY needs to be configured in constants.php';
}

if (defined('DOJAH_API_BASE_URL')) {
    $results['config']['base_url'] = '✅ DOJAH_API_BASE_URL: ' . DOJAH_API_BASE_URL;
} else {
    $results['config']['base_url'] = '❌ DOJAH_API_BASE_URL not defined';
}

if (defined('NIN_VERIFICATION_FEE')) {
    $results['config']['fee'] = '✅ NIN_VERIFICATION_FEE: ₦' . number_format(NIN_VERIFICATION_FEE, 2);
} else {
    $results['config']['fee'] = '❌ NIN_VERIFICATION_FEE not defined';
}

// Test 4: Check API endpoint exists
$results['api'] = [];
$apiFile = __DIR__ . '/api/verify-nin.php';
if (file_exists($apiFile)) {
    $results['api']['file'] = '✅ API endpoint exists at: api/verify-nin.php';
} else {
    $results['api']['file'] = '❌ API endpoint missing: api/verify-nin.php';
}

// Test 5: Test API connectivity (if user is logged in)
$results['api_test'] = [];
if (isLoggedIn() && isJobSeeker()) {
    try {
        // Test getting verification status
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, SITE_URL . '/api/verify-nin.php?action=status');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            if ($data && isset($data['success'])) {
                $results['api_test']['status'] = '✅ API Status Check: Working';
                $results['api_test']['verified'] = $data['verified'] ? '✅ NIN Already Verified' : '⚠️ NIN Not Verified';
            } else {
                $results['api_test']['status'] = '⚠️ API returned unexpected response';
            }
        } else {
            $results['api_test']['status'] = '❌ API Status Check Failed (HTTP ' . $httpCode . ')';
        }
    } catch (Exception $e) {
        $results['api_test']['error'] = '❌ API test error: ' . $e->getMessage();
    }
} else {
    $results['api_test']['note'] = '⚠️ Please log in as a job seeker to test API';
}

// Test 6: Check if profile page has NIN verification UI
$results['ui'] = [];
$profileFile = __DIR__ . '/pages/user/profile.php';
if (file_exists($profileFile)) {
    $profileContent = file_get_contents($profileFile);
    if (strpos($profileContent, 'openNINVerificationModal') !== false) {
        $results['ui']['modal'] = '✅ NIN verification modal implemented';
    } else {
        $results['ui']['modal'] = '❌ NIN verification modal missing from profile.php';
    }
    
    if (strpos($profileContent, 'nin_verified') !== false) {
        $results['ui']['status'] = '✅ NIN verification status display implemented';
    } else {
        $results['ui']['status'] = '❌ NIN verification status display missing';
    }
} else {
    $results['ui']['error'] = '❌ Profile page not found';
}

// Test 7: Test with sample NIN (sandbox only)
$results['sandbox_test'] = [];
if (DOJAH_USE_SANDBOX && isset($_GET['test_nin'])) {
    $testNIN = '70123456789'; // Dojah sandbox test NIN
    
    try {
        $url = DOJAH_API_BASE_URL . '/kyc/nin/advance?nin=' . $testNIN;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'AppId: ' . DOJAH_APP_ID,
                'Authorization: ' . DOJAH_API_KEY,
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            if (isset($data['entity'])) {
                $results['sandbox_test']['connection'] = '✅ Dojah API connection successful';
                $results['sandbox_test']['response'] = '✅ Sample data received: ' . $data['entity']['first_name'] . ' ' . $data['entity']['last_name'];
            } else {
                $results['sandbox_test']['connection'] = '⚠️ API connected but unexpected response format';
            }
        } else {
            $results['sandbox_test']['connection'] = '❌ API connection failed (HTTP ' . $httpCode . ')';
            $results['sandbox_test']['response_body'] = $response;
        }
    } catch (Exception $e) {
        $results['sandbox_test']['error'] = '❌ Test failed: ' . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f5f5;
            padding: 2rem;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #dc2626;
            margin-bottom: 0.5rem;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #dc2626;
        }
        
        .test-section {
            margin: 2rem 0;
            padding: 1.5rem;
            background: #f9fafb;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        
        .test-section h2 {
            color: #1f2937;
            margin-bottom: 1rem;
            font-size: 1.25rem;
        }
        
        .test-result {
            padding: 0.75rem 1rem;
            margin: 0.5rem 0;
            border-radius: 6px;
            background: white;
            border-left: 4px solid #6b7280;
        }
        
        .test-result.success {
            border-left-color: #10b981;
            background: #f0fdf4;
        }
        
        .test-result.warning {
            border-left-color: #f59e0b;
            background: #fffbeb;
        }
        
        .test-result.error {
            border-left-color: #ef4444;
            background: #fef2f2;
        }
        
        .test-result strong {
            display: block;
            margin-bottom: 0.25rem;
            color: #1f2937;
        }
        
        .test-result span {
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #dc2626;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin-top: 1rem;
            transition: background 0.2s;
        }
        
        .btn:hover {
            background: #991b1b;
        }
        
        .btn-secondary {
            background: #6b7280;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        .actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e5e7eb;
        }
        
        code {
            background: #1f2937;
            color: #10b981;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        
        pre {
            background: #1f2937;
            color: #e5e7eb;
            padding: 1rem;
            border-radius: 6px;
            overflow-x: auto;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛡️ <?php echo $pageTitle; ?></h1>
        <p class="subtitle">Complete system test for NIN verification using Dojah API</p>
        
        <!-- Database Tests -->
        <div class="test-section">
            <h2>1. Database Tables</h2>
            <?php foreach ($results['database'] as $key => $value): ?>
                <div class="test-result <?php echo strpos($value, '✅') !== false ? 'success' : 'error'; ?>">
                    <strong><?php echo htmlspecialchars($key); ?></strong>
                    <span><?php echo $value; ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Column Tests -->
        <div class="test-section">
            <h2>2. Database Columns</h2>
            <?php foreach ($results['columns'] as $key => $value): ?>
                <div class="test-result <?php echo strpos($value, '✅') !== false ? 'success' : 'error'; ?>">
                    <strong><?php echo htmlspecialchars($key); ?></strong>
                    <span><?php echo $value; ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Configuration Tests -->
        <div class="test-section">
            <h2>3. API Configuration</h2>
            <?php foreach ($results['config'] as $key => $value): ?>
                <div class="test-result <?php echo strpos($value, '✅') !== false ? 'success' : (strpos($value, '⚠️') !== false ? 'warning' : 'error'); ?>">
                    <strong><?php echo htmlspecialchars($key); ?></strong>
                    <span><?php echo $value; ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- API Endpoint Tests -->
        <div class="test-section">
            <h2>4. API Endpoint</h2>
            <?php foreach ($results['api'] as $key => $value): ?>
                <div class="test-result <?php echo strpos($value, '✅') !== false ? 'success' : 'error'; ?>">
                    <strong><?php echo htmlspecialchars($key); ?></strong>
                    <span><?php echo $value; ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- API Connectivity Tests -->
        <div class="test-section">
            <h2>5. API Connectivity</h2>
            <?php foreach ($results['api_test'] as $key => $value): ?>
                <div class="test-result <?php echo strpos($value, '✅') !== false ? 'success' : (strpos($value, '⚠️') !== false ? 'warning' : 'error'); ?>">
                    <strong><?php echo htmlspecialchars($key); ?></strong>
                    <span><?php echo $value; ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- UI Tests -->
        <div class="test-section">
            <h2>6. User Interface</h2>
            <?php foreach ($results['ui'] as $key => $value): ?>
                <div class="test-result <?php echo strpos($value, '✅') !== false ? 'success' : 'error'; ?>">
                    <strong><?php echo htmlspecialchars($key); ?></strong>
                    <span><?php echo $value; ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Sandbox Test -->
        <?php if (DOJAH_USE_SANDBOX): ?>
            <div class="test-section">
                <h2>7. Dojah API Sandbox Test</h2>
                <?php if (!empty($results['sandbox_test'])): ?>
                    <?php foreach ($results['sandbox_test'] as $key => $value): ?>
                        <div class="test-result <?php echo strpos($value, '✅') !== false ? 'success' : (strpos($value, '⚠️') !== false ? 'warning' : 'error'); ?>">
                            <strong><?php echo htmlspecialchars($key); ?></strong>
                            <?php if ($key === 'response_body'): ?>
                                <pre><?php echo htmlspecialchars($value); ?></pre>
                            <?php else: ?>
                                <span><?php echo $value; ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="test-result warning">
                        <strong>Test not run</strong>
                        <span>Add <code>?test_nin=1</code> to URL to test Dojah API connection with sample NIN</span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Actions -->
        <div class="actions">
            <?php if (DOJAH_USE_SANDBOX && !isset($_GET['test_nin'])): ?>
                <a href="?test_nin=1" class="btn">🧪 Test Dojah API Connection</a>
            <?php endif; ?>
            
            <?php if (isLoggedIn() && isJobSeeker()): ?>
                <a href="/findajob/pages/user/profile.php" class="btn-secondary btn">👤 Go to Profile</a>
            <?php else: ?>
                <a href="/findajob/pages/auth/login.php" class="btn-secondary btn">🔐 Login as Job Seeker</a>
            <?php endif; ?>
            
            <a href="/findajob" class="btn-secondary btn">🏠 Home</a>
        </div>
        
        <!-- Instructions -->
        <div class="test-section" style="margin-top: 2rem;">
            <h2>📋 Setup Instructions</h2>
            <div class="test-result">
                <strong>Step 1: Run Database Migration</strong>
                <span>Execute the SQL file: <code>database/add-nin-verification.sql</code></span>
            </div>
            <div class="test-result">
                <strong>Step 2: Configure Dojah API</strong>
                <span>Update <code>config/constants.php</code> with your Dojah API credentials from <a href="https://dojah.io" target="_blank">https://dojah.io</a></span>
            </div>
            <div class="test-result">
                <strong>Step 3: Test the Flow</strong>
                <span>Log in as a job seeker, go to Profile page, and click "Verify My NIN"</span>
            </div>
            <div class="test-result">
                <strong>Step 4: Use Sandbox NIN</strong>
                <span>For testing, use: <code>70123456789</code> (Dojah sandbox test NIN)</span>
            </div>
        </div>
    </div>
</body>
</html>
