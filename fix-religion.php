<?php
/**
 * Fix Religion Field - Check and Apply from NIN Data
 * This script checks the stored NIN data for religion field and applies it
 */

require_once 'config/database.php';
require_once 'config/session.php';

if (!isLoggedIn()) {
    die('Please log in as a job seeker.');
}

$userId = getCurrentUserId();

// Get current profile
$stmt = $pdo->prepare("
    SELECT 
        nin_verification_data,
        religion,
        nin_verified
    FROM job_seeker_profiles 
    WHERE user_id = ?
");
$stmt->execute([$userId]);
$profile = $stmt->fetch();

if (!$profile || !$profile['nin_verified']) {
    die('No NIN verification data found or not verified.');
}

$ninData = json_decode($profile['nin_verification_data'], true);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Religion Field</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
        }
        .success-box {
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 15px;
            margin: 20px 0;
        }
        .warning-box {
            background: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 15px;
            margin: 20px 0;
        }
        .error-box {
            background: #ffebee;
            border-left: 4px solid #f44336;
            padding: 15px;
            margin: 20px 0;
        }
        .field-check {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin: 15px 0;
        }
        .field-row {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .field-label {
            font-weight: 600;
        }
        .field-value {
            color: #333;
        }
        .field-value.empty {
            color: #999;
            font-style: italic;
        }
        .field-value.found {
            color: #4caf50;
            font-weight: 600;
        }
        button {
            background: #2196F3;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
        }
        button:hover {
            background: #1976D2;
        }
        .json-view {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            max-height: 400px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Fix Religion Field</h1>
        
        <?php
        // Check all possible religion field variations
        $religionChecks = [
            'religion' => $ninData['religion'] ?? null,
            'Religion' => $ninData['Religion'] ?? null,
            'RELIGION' => $ninData['RELIGION'] ?? null,
        ];
        
        $foundReligion = null;
        $foundKey = null;
        
        foreach ($religionChecks as $key => $value) {
            if (!empty($value)) {
                $foundReligion = $value;
                $foundKey = $key;
                break;
            }
        }
        ?>
        
        <div class="info-box">
            <strong>Current Status:</strong><br>
            Religion in database: <strong><?php echo htmlspecialchars($profile['religion'] ?? 'Not set'); ?></strong>
        </div>
        
        <div class="field-check">
            <h3>🔍 Checking NIN Data for Religion Field:</h3>
            <?php foreach ($religionChecks as $key => $value): ?>
                <div class="field-row">
                    <div class="field-label">ninData['<?php echo $key; ?>']:</div>
                    <div class="field-value <?php echo !empty($value) ? 'found' : 'empty'; ?>">
                        <?php echo !empty($value) ? htmlspecialchars($value) : 'Not found'; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($foundReligion): ?>
            <div class="success-box">
                <strong>✓ Religion Found!</strong><br>
                Field: <code><?php echo $foundKey; ?></code><br>
                Value: <strong><?php echo htmlspecialchars($foundReligion); ?></strong>
            </div>
            
            <?php if (isset($_POST['apply_religion'])): ?>
                <?php
                try {
                    $stmt = $pdo->prepare("UPDATE job_seeker_profiles SET religion = ? WHERE user_id = ?");
                    $stmt->execute([$foundReligion, $userId]);
                    echo '<div class="success-box"><strong>✓ Success!</strong><br>Religion has been updated to: <strong>' . htmlspecialchars($foundReligion) . '</strong></div>';
                } catch (Exception $e) {
                    echo '<div class="error-box"><strong>✗ Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
                ?>
            <?php else: ?>
                <form method="POST">
                    <button type="submit" name="apply_religion">Apply Religion to Profile</button>
                </form>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="warning-box">
                <strong>⚠️ Religion Not Found</strong><br>
                The religion field is not present in your NIN verification data.<br>
                This might mean:
                <ul>
                    <li>Dojah API doesn't provide religion for your NIN</li>
                    <li>The field uses a different name</li>
                    <li>The field was empty in the NIN database</li>
                </ul>
            </div>
            
            <div class="field-check">
                <h3>📦 All Available Fields in NIN Data:</h3>
                <div class="json-view">
                    <pre><?php 
                    $allKeys = array_keys($ninData);
                    echo "Available fields (" . count($allKeys) . "):\n\n";
                    foreach ($allKeys as $key) {
                        $value = $ninData[$key];
                        if (is_string($value) && strlen($value) < 100) {
                            echo "$key: " . $value . "\n";
                        } else {
                            echo "$key: [" . gettype($value) . "]\n";
                        }
                    }
                    ?></pre>
                </div>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee;">
            <a href="pages/user/profile.php" style="text-decoration: none; color: #2196F3;">← Back to Profile</a> |
            <a href="debug-nin-fields.php" style="text-decoration: none; color: #2196F3;">View Full NIN Data</a>
        </div>
    </div>
</body>
</html>
