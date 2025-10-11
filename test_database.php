<?php
/**
 * Database Test Tool
 * Test database connection and tables
 */

require_once 'config/constants.php';
require_once 'includes/functions.php';

// Only accessible in development mode
if (!isDevelopmentMode()) {
    die('This page is only available in development mode.');
}

$tests = [];

// Test 1: Database Connection
try {
    require_once 'config/database.php';
    $tests['connection'] = [
        'status' => 'success',
        'message' => 'Database connection successful'
    ];
} catch (Exception $e) {
    $tests['connection'] = [
        'status' => 'error',
        'message' => 'Database connection failed: ' . $e->getMessage()
    ];
}

// Test 2: Required Tables
$required_tables = ['users', 'job_seeker_profiles', 'employer_profiles', 'login_attempts'];
foreach ($required_tables as $table) {
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        $tests["table_$table"] = [
            'status' => 'success',
            'message' => "Table '$table' exists"
        ];
    } catch (Exception $e) {
        $tests["table_$table"] = [
            'status' => 'error',
            'message' => "Table '$table' missing: " . $e->getMessage()
        ];
    }
}

// Test 3: Sample Insert Test
try {
    $pdo->beginTransaction();
    
    // Test user insert
    $stmt = $pdo->prepare("
        INSERT INTO users (user_type, email, password_hash, first_name, last_name, 
                          email_verification_token, email_verification_expires) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $test_email = 'db_test_' . time() . '@example.com';
    $stmt->execute([
        'job_seeker',
        $test_email,
        password_hash('test123', PASSWORD_DEFAULT),
        'Test',
        'User',
        bin2hex(random_bytes(32)),
        date('Y-m-d H:i:s', time() + 3600)
    ]);
    
    $user_id = $pdo->lastInsertId();
    
    // Test profile insert
    $stmt = $pdo->prepare("INSERT INTO job_seeker_profiles (user_id) VALUES (?)");
    $stmt->execute([$user_id]);
    
    $pdo->rollback(); // Don't actually save the test data
    
    $tests['insert_test'] = [
        'status' => 'success',
        'message' => 'Database insert test successful'
    ];
    
} catch (Exception $e) {
    $pdo->rollback();
    $tests['insert_test'] = [
        'status' => 'error',
        'message' => 'Database insert test failed: ' . $e->getMessage()
    ];
}

// Test 4: Check for existing test users
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE email LIKE 'test_%@example.com'");
    $result = $stmt->fetch();
    $tests['test_users'] = [
        'status' => 'info',
        'message' => "Found {$result['count']} test users in database"
    ];
} catch (Exception $e) {
    $tests['test_users'] = [
        'status' => 'error',
        'message' => 'Could not check test users: ' . $e->getMessage()
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Test - FindAJob Nigeria</title>
    
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
            max-width: 800px;
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
        
        .test-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-radius: 8px;
            border-left: 4px solid #cbd5e1;
        }
        
        .test-item.success {
            background: #f0fdf4;
            border-left-color: #16a34a;
        }
        
        .test-item.error {
            background: #fef2f2;
            border-left-color: #dc2626;
        }
        
        .test-item.info {
            background: #eff6ff;
            border-left-color: #2563eb;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .status-success {
            background: #16a34a;
            color: white;
        }
        
        .status-error {
            background: #dc2626;
            color: white;
        }
        
        .status-info {
            background: #2563eb;
            color: white;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            background: #dc2626;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin-right: 1rem;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🗄️ Database Test Tool</h1>
            <p>Test database connection and required tables</p>
        </div>
        
        <div>
            <?php foreach ($tests as $test_name => $test): ?>
            <div class="test-item <?php echo $test['status']; ?>">
                <div>
                    <strong><?php echo ucwords(str_replace('_', ' ', $test_name)); ?>:</strong><br>
                    <span><?php echo htmlspecialchars($test['message']); ?></span>
                </div>
                <div class="status-badge status-<?php echo $test['status']; ?>">
                    <?php echo strtoupper($test['status']); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #e2e8f0;">
            <h3>🛠️ Quick Actions</h3>
            <a href="test_registration.php" class="btn">Test Registration</a>
            <a href="temp_mail.php" class="btn btn-secondary">Email Inbox</a>
            <a href="dev_status.php" class="btn btn-secondary">Dev Status</a>
            <a href="#" onclick="location.reload();" class="btn btn-secondary">Refresh Tests</a>
        </div>
        
        <div style="margin-top: 2rem; padding: 1rem; background: #f8fafc; border-radius: 6px;">
            <h4>📋 Database Configuration</h4>
            <ul>
                <li><strong>Host:</strong> <?php echo DB_HOST; ?></li>
                <li><strong>Database:</strong> <?php echo DB_NAME; ?></li>
                <li><strong>User:</strong> <?php echo DB_USER; ?></li>
            </ul>
        </div>
    </div>
</body>
</html>