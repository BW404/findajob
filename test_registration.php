<?php
/**
 * Registration Debug Test Page
 * Test registration process and show detailed errors
 */

require_once 'config/constants.php';
require_once 'includes/functions.php';

// Only accessible in development mode
if (!isDevelopmentMode()) {
    die('This page is only available in development mode.');
}

$test_result = null;
$test_data = [
    'user_type' => 'job_seeker',
    'first_name' => 'Test',
    'last_name' => 'User',
    'email' => 'test_' . time() . '@example.com',
    'password' => 'testpass123',
    'confirm_password' => 'testpass123'
];

if (isset($_POST['test_registration'])) {
    // Test registration with sample data
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://' . $_SERVER['HTTP_HOST'] . '/findajob/api/auth.php');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array_merge($test_data, ['action' => 'register'])));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $test_result = [
        'http_code' => $http_code,
        'raw_response' => $response,
        'parsed_response' => json_decode($response, true)
    ];
}

if (isset($_POST['test_custom'])) {
    // Test with custom data
    $custom_data = [
        'action' => 'register',
        'user_type' => $_POST['user_type'],
        'first_name' => $_POST['first_name'],
        'last_name' => $_POST['last_name'],
        'email' => $_POST['email'],
        'password' => $_POST['password'],
        'confirm_password' => $_POST['confirm_password']
    ];
    
    if ($_POST['user_type'] === 'employer') {
        $custom_data['company_name'] = $_POST['company_name'] ?? '';
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://' . $_SERVER['HTTP_HOST'] . '/findajob/api/auth.php');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($custom_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $test_result = [
        'http_code' => $http_code,
        'raw_response' => $response,
        'parsed_response' => json_decode($response, true)
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Debug - FindAJob Nigeria</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="assets/images/icons/icon-192x192.svg">
    <link rel="alternate icon" href="assets/images/icons/icon-192x192.svg">
    <link rel="shortcut icon" href="assets/images/icons/icon-192x192.svg">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 2rem;
            background: #f8fafc;
            color: #1e293b;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 1rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            background: #dc2626;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            margin-right: 1rem;
            margin-bottom: 1rem;
        }
        
        .btn:hover {
            background: #991b1b;
        }
        
        .btn-secondary {
            background: #64748b;
        }
        
        .btn-secondary:hover {
            background: #475569;
        }
        
        .result {
            margin-top: 2rem;
            padding: 1.5rem;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        
        .success {
            border-color: #16a34a;
            background: #f0fdf4;
        }
        
        .error {
            border-color: #dc2626;
            background: #fef2f2;
        }
        
        pre {
            background: #1e293b;
            color: #f8fafc;
            padding: 1rem;
            border-radius: 6px;
            overflow-x: auto;
            font-size: 0.875rem;
        }
        
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🐛 Registration Debug Tool</h1>
            <p>Test and debug registration issues in development mode</p>
        </div>
        
        <div class="grid">
            <!-- Quick Test -->
            <div>
                <h3>🚀 Quick Test</h3>
                <p>Test registration with pre-filled sample data:</p>
                <form method="POST">
                    <div style="background: #f8fafc; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                        <strong>Test Data:</strong><br>
                        Type: Job Seeker<br>
                        Name: Test User<br>
                        Email: test_<?php echo time(); ?>@example.com<br>
                        Password: testpass123
                    </div>
                    <button type="submit" name="test_registration" class="btn">Run Quick Test</button>
                </form>
            </div>
            
            <!-- Custom Test -->
            <div>
                <h3>⚙️ Custom Test</h3>
                <form method="POST">
                    <div class="form-group">
                        <label for="user_type">User Type:</label>
                        <select name="user_type" id="user_type" onchange="toggleCompanyField()">
                            <option value="job_seeker">Job Seeker</option>
                            <option value="employer">Employer</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="first_name">First Name:</label>
                        <input type="text" name="first_name" id="first_name" value="John" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Last Name:</label>
                        <input type="text" name="last_name" id="last_name" value="Doe" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" name="email" id="email" value="john.doe.<?php echo time(); ?>@example.com" required>
                    </div>
                    
                    <div class="form-group" id="company_field" style="display: none;">
                        <label for="company_name">Company Name:</label>
                        <input type="text" name="company_name" id="company_name" value="Test Company Ltd">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" name="password" id="password" value="password123" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password:</label>
                        <input type="password" name="confirm_password" id="confirm_password" value="password123" required>
                    </div>
                    
                    <button type="submit" name="test_custom" class="btn">Test Custom Data</button>
                </form>
            </div>
        </div>
        
        <?php if ($test_result): ?>
        <div class="result <?php echo $test_result['parsed_response']['success'] ?? false ? 'success' : 'error'; ?>">
            <h3>📊 Test Results</h3>
            
            <p><strong>HTTP Status:</strong> <?php echo $test_result['http_code']; ?></p>
            
            <?php if ($test_result['parsed_response']): ?>
                <p><strong>Success:</strong> <?php echo $test_result['parsed_response']['success'] ? 'Yes' : 'No'; ?></p>
                
                <?php if (isset($test_result['parsed_response']['message'])): ?>
                    <p><strong>Message:</strong> <?php echo htmlspecialchars($test_result['parsed_response']['message']); ?></p>
                <?php endif; ?>
                
                <?php if (isset($test_result['parsed_response']['errors'])): ?>
                    <div>
                        <strong>Errors:</strong>
                        <pre><?php echo htmlspecialchars(json_encode($test_result['parsed_response']['errors'], JSON_PRETTY_PRINT)); ?></pre>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($test_result['parsed_response']['user_id'])): ?>
                    <p><strong>User ID:</strong> <?php echo $test_result['parsed_response']['user_id']; ?></p>
                <?php endif; ?>
            <?php endif; ?>
            
            <details>
                <summary style="cursor: pointer; margin-top: 1rem; font-weight: 600;">View Raw Response</summary>
                <pre><?php echo htmlspecialchars($test_result['raw_response']); ?></pre>
            </details>
        </div>
        <?php endif; ?>
        
        <div style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #e2e8f0;">
            <div class="alert alert-success">
                <strong>🔧 Recent Fix Applied:</strong><br>
                Fixed missing <code>confirm_password</code> field in registration forms. Both job seeker and employer registration should now work properly!
            </div>
            
            <h3>🔧 Troubleshooting Tips</h3>
            <ul>
                <li><strong>Database Issues:</strong> Check if all tables exist in the database</li>
                <li><strong>Email Issues:</strong> Check if development email capture is working</li>
                <li><strong>Validation Issues:</strong> Review error messages for specific field problems</li>
                <li><strong>Server Issues:</strong> Check Apache error logs for PHP errors</li>
                <li><strong>Form Issues:</strong> Ensure all required fields are being sent to the API</li>
            </ul>
            
            <div style="margin-top: 1rem;">
                <a href="dev_status.php" class="btn btn-secondary">Development Status</a>
                <a href="temp_mail.php" class="btn btn-secondary">Email Inbox</a>
                <a href="index.php" class="btn btn-secondary">Main Site</a>
            </div>
        </div>
    </div>
    
    <script>
        function toggleCompanyField() {
            const userType = document.getElementById('user_type').value;
            const companyField = document.getElementById('company_field');
            
            if (userType === 'employer') {
                companyField.style.display = 'block';
                document.getElementById('company_name').required = true;
            } else {
                companyField.style.display = 'none';
                document.getElementById('company_name').required = false;
            }
        }
    </script>
</body>
</html>