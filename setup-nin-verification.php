<?php
/**
 * Quick Setup Script for NIN Verification
 * Run this script to automatically set up the NIN verification system
 */

require_once 'config/database.php';
require_once 'config/constants.php';

// Only accessible in development mode
if (!defined('DEV_MODE') || !DEV_MODE) {
    die('This script can only be run in development mode. Set DEV_MODE to true in config/constants.php');
}

$results = [];
$hasErrors = false;

echo "<!DOCTYPE html>
<html>
<head>
    <title>NIN Verification Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #dc2626; margin-bottom: 10px; }
        .subtitle { color: #666; margin-bottom: 30px; }
        .step { margin: 20px 0; padding: 15px; border-left: 4px solid #ddd; background: #f9f9f9; }
        .step.success { border-color: #10b981; background: #f0fdf4; }
        .step.error { border-color: #ef4444; background: #fef2f2; }
        .step.warning { border-color: #f59e0b; background: #fffbeb; }
        .step-title { font-weight: bold; margin-bottom: 5px; }
        .step-message { color: #666; font-size: 14px; }
        .btn { display: inline-block; padding: 12px 24px; background: #dc2626; color: white; text-decoration: none; border-radius: 6px; margin-top: 20px; font-weight: 600; }
        .btn:hover { background: #991b1b; }
        code { background: #1f2937; color: #10b981; padding: 2px 6px; border-radius: 3px; font-size: 13px; }
        pre { background: #1f2937; color: #e5e7eb; padding: 15px; border-radius: 6px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🛡️ NIN Verification Setup</h1>
        <p class='subtitle'>Automatically setting up the NIN verification system...</p>
";

// Step 1: Read SQL file
echo "<div class='step'><div class='step-title'>📄 Step 1: Reading migration file...</div>";
$sqlFile = __DIR__ . '/database/add-nin-verification.sql';
if (!file_exists($sqlFile)) {
    echo "<div class='step-message'>❌ Error: Migration file not found at: $sqlFile</div></div>";
    $hasErrors = true;
} else {
    echo "<div class='step-message'>✅ Migration file found</div></div>";
    $sqlContent = file_get_contents($sqlFile);
}

// Step 2: Execute SQL statements
if (!$hasErrors) {
    echo "<div class='step'><div class='step-title'>⚙️ Step 2: Executing database migration...</div>";
    
    try {
        // Split SQL into individual statements
        $statements = array_filter(
            array_map('trim', explode(';', $sqlContent)),
            function($stmt) { return !empty($stmt) && !preg_match('/^--/', $stmt); }
        );
        
        $executed = 0;
        $errors = 0;
        
        foreach ($statements as $statement) {
            if (empty(trim($statement))) continue;
            
            try {
                $pdo->exec($statement);
                $executed++;
            } catch (PDOException $e) {
                // Check if error is about existing column/table - that's OK
                if (strpos($e->getMessage(), 'Duplicate column') !== false || 
                    strpos($e->getMessage(), 'already exists') !== false) {
                    // Column/table already exists - not an error
                    $executed++;
                } else {
                    $errors++;
                    echo "<div class='step-message'>⚠️ Warning: " . htmlspecialchars($e->getMessage()) . "</div>";
                }
            }
        }
        
        if ($errors === 0) {
            echo "<div class='step-message'>✅ Successfully executed $executed SQL statements</div></div>";
        } else {
            echo "<div class='step-message'>⚠️ Executed $executed statements with $errors warnings</div></div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='step-message'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div></div>";
        $hasErrors = true;
    }
}

// Step 3: Verify tables created
if (!$hasErrors) {
    echo "<div class='step'><div class='step-title'>✔️ Step 3: Verifying database tables...</div>";
    
    $tables = [
        'job_seeker_profiles' => ['nin', 'nin_verified', 'nin_verified_at', 'nin_verification_data'],
        'verification_transactions' => true,
        'verification_audit_log' => true
    ];
    
    $allGood = true;
    foreach ($tables as $table => $columns) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<div class='step-message'>✅ Table <code>$table</code> exists</div>";
            
            if (is_array($columns)) {
                // Check specific columns
                foreach ($columns as $column) {
                    $stmt = $pdo->query("SHOW COLUMNS FROM $table LIKE '$column'");
                    if ($stmt->rowCount() > 0) {
                        echo "<div class='step-message'>  ✅ Column <code>$column</code> exists</div>";
                    } else {
                        echo "<div class='step-message'>  ❌ Column <code>$column</code> missing!</div>";
                        $allGood = false;
                    }
                }
            }
        } else {
            echo "<div class='step-message'>❌ Table <code>$table</code> missing!</div>";
            $allGood = false;
        }
    }
    
    echo "</div>";
    
    if (!$allGood) {
        $hasErrors = true;
    }
}

// Step 4: Check configuration
echo "<div class='step'><div class='step-title'>🔧 Step 4: Checking API configuration...</div>";

$configIssues = [];

if (!defined('DOJAH_APP_ID') || DOJAH_APP_ID === 'your_app_id_here') {
    $configIssues[] = 'DOJAH_APP_ID needs to be configured';
}

if (!defined('DOJAH_API_KEY') || DOJAH_API_KEY === 'your_api_key_here') {
    $configIssues[] = 'DOJAH_API_KEY needs to be configured';
}

if (!defined('DOJAH_API_BASE_URL')) {
    $configIssues[] = 'DOJAH_API_BASE_URL not defined';
}

if (!defined('NIN_VERIFICATION_FEE')) {
    $configIssues[] = 'NIN_VERIFICATION_FEE not defined';
}

if (empty($configIssues)) {
    echo "<div class='step-message'>✅ All API configuration constants are defined</div>";
    echo "<div class='step-message'>📋 DOJAH_APP_ID: " . htmlspecialchars(substr(DOJAH_APP_ID, 0, 8)) . "...</div>";
    echo "<div class='step-message'>📋 API URL: " . htmlspecialchars(DOJAH_API_BASE_URL) . "</div>";
    echo "<div class='step-message'>💰 Verification Fee: ₦" . number_format(NIN_VERIFICATION_FEE, 2) . "</div>";
} else {
    echo "<div class='step-message'>⚠️ Configuration needed:</div>";
    foreach ($configIssues as $issue) {
        echo "<div class='step-message'>  • $issue</div>";
    }
}

echo "</div>";

// Final Status
if (!$hasErrors && empty($configIssues)) {
    echo "
    <div class='step success'>
        <div class='step-title'>🎉 Setup Complete!</div>
        <div class='step-message'>The NIN verification system is ready to use.</div>
    </div>
    
    <div class='step'>
        <div class='step-title'>📋 Next Steps:</div>
        <div class='step-message'>
            1. <strong>Update Dojah API credentials</strong> in <code>config/constants.php</code><br>
            2. <strong>Test the system:</strong> <a href='test-nin-verification.php'>Run System Tests</a><br>
            3. <strong>Try verification:</strong> Login as job seeker and visit your profile<br>
            4. <strong>Use test NIN:</strong> 70123456789 (for sandbox testing)
        </div>
    </div>
    ";
} elseif (!$hasErrors) {
    echo "
    <div class='step warning'>
        <div class='step-title'>⚠️ Setup Partially Complete</div>
        <div class='step-message'>Database setup successful, but configuration is needed.</div>
    </div>
    
    <div class='step'>
        <div class='step-title'>📋 Action Required:</div>
        <div class='step-message'>
            Update <code>config/constants.php</code> with your Dojah API credentials:<br><br>
            <pre>define('DOJAH_APP_ID', 'your_app_id_here');
define('DOJAH_API_KEY', 'your_api_key_here');</pre>
            <br>
            Get credentials from: <a href='https://dojah.io' target='_blank'>https://dojah.io</a>
        </div>
    </div>
    ";
} else {
    echo "
    <div class='step error'>
        <div class='step-title'>❌ Setup Incomplete</div>
        <div class='step-message'>Some errors occurred during setup. Please check the messages above.</div>
    </div>
    ";
}

echo "
        <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;'>
            <a href='test-nin-verification.php' class='btn'>🧪 Run System Tests</a>
            <a href='pages/user/profile.php' class='btn' style='background: #6b7280;'>👤 Go to Profile</a>
            <a href='index.php' class='btn' style='background: #6b7280;'>🏠 Home</a>
        </div>
    </div>
</body>
</html>
";
