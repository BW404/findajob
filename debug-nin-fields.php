<?php
/**
 * Debug script to view stored NIN verification data
 * Shows all available fields from Dojah response
 */

require_once 'config/database.php';
require_once 'config/session.php';

if (!isLoggedIn()) {
    die('Please log in as a job seeker.');
}

$userId = getCurrentUserId();

// Fetch NIN verification data
$stmt = $pdo->prepare("
    SELECT 
        nin, 
        nin_verified, 
        nin_verified_at, 
        nin_verification_data,
        city_of_birth,
        lga_of_origin,
        state_of_origin,
        religion
    FROM job_seeker_profiles 
    WHERE user_id = ?
");
$stmt->execute([$userId]);
$profile = $stmt->fetch();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NIN Data Debug - FindAJob Nigeria</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 1000px;
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
        h1 {
            color: #333;
            margin-top: 0;
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
        .data-section {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin: 15px 0;
        }
        .data-section h3 {
            margin-top: 0;
            color: #2196F3;
        }
        .field-row {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .field-row:last-child {
            border-bottom: none;
        }
        .field-label {
            font-weight: 600;
            color: #555;
        }
        .field-value {
            color: #333;
            word-break: break-word;
        }
        .field-value.empty {
            color: #999;
            font-style: italic;
        }
        .json-view {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.6;
        }
        .highlight {
            background: #ffeb3b;
            padding: 2px 4px;
            border-radius: 2px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 NIN Verification Data Debug</h1>
        
        <?php if (!$profile): ?>
            <div class="warning-box">
                <strong>⚠️ No Profile Found</strong><br>
                Job seeker profile not found for this user.
            </div>
        <?php elseif (!$profile['nin_verified']): ?>
            <div class="info-box">
                <strong>ℹ️ Not Verified</strong><br>
                NIN verification has not been completed yet.
            </div>
        <?php else: ?>
            <div class="success-box">
                <strong>✓ NIN Verified</strong><br>
                Verified on: <?php echo date('F j, Y g:i A', strtotime($profile['nin_verified_at'])); ?>
            </div>

            <!-- Current Profile Fields -->
            <div class="data-section">
                <h3>📝 Current Profile Fields</h3>
                <div class="field-row">
                    <div class="field-label">State of Origin:</div>
                    <div class="field-value <?php echo empty($profile['state_of_origin']) ? 'empty' : ''; ?>">
                        <?php echo htmlspecialchars($profile['state_of_origin'] ?? 'Not set'); ?>
                    </div>
                </div>
                <div class="field-row">
                    <div class="field-label">LGA of Origin:</div>
                    <div class="field-value <?php echo empty($profile['lga_of_origin']) ? 'empty' : ''; ?>">
                        <?php echo htmlspecialchars($profile['lga_of_origin'] ?? 'Not set'); ?>
                    </div>
                </div>
                <div class="field-row">
                    <div class="field-label">City/LGA of Birth:</div>
                    <div class="field-value <?php echo empty($profile['city_of_birth']) ? 'empty' : ''; ?>">
                        <?php echo htmlspecialchars($profile['city_of_birth'] ?? 'Not set'); ?>
                    </div>
                </div>
                <div class="field-row">
                    <div class="field-label">Religion:</div>
                    <div class="field-value <?php echo empty($profile['religion']) ? 'empty' : ''; ?>">
                        <?php echo htmlspecialchars($profile['religion'] ?? 'Not set'); ?>
                    </div>
                </div>
            </div>

            <!-- Raw NIN Data -->
            <?php if (!empty($profile['nin_verification_data'])): ?>
                <?php 
                    $ninData = json_decode($profile['nin_verification_data'], true);
                    $birthFields = [];
                    
                    // Check for various birth-related fields
                    $fieldsToCheck = [
                        'birth_lga', 'birthlga', 'birth_state', 'birthstate',
                        'place_of_birth', 'birthplace', 'origin_lga', 'origin_state',
                        'residence_lga', 'residence_state', 'religion', 'Religion'
                    ];
                    
                    foreach ($fieldsToCheck as $field) {
                        if (isset($ninData[$field]) && !empty($ninData[$field])) {
                            $birthFields[$field] = $ninData[$field];
                        }
                    }
                ?>
                
                <div class="data-section">
                    <h3>🎯 Birth/Origin Related Fields Found</h3>
                    <?php if (!empty($birthFields)): ?>
                        <?php foreach ($birthFields as $field => $value): ?>
                            <div class="field-row">
                                <div class="field-label"><?php echo htmlspecialchars($field); ?>:</div>
                                <div class="field-value">
                                    <span class="highlight"><?php echo htmlspecialchars($value); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="field-value empty">No birth/origin fields found in NIN data</p>
                    <?php endif; ?>
                </div>

                <div class="data-section">
                    <h3>📦 Complete Stored NIN Data (JSON)</h3>
                    <div class="json-view">
                        <pre><?php echo json_encode($ninData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?></pre>
                    </div>
                </div>

                <div class="data-section">
                    <h3>🔧 Field Mapping Logic</h3>
                    <p><strong>City/LGA of Birth</strong> is populated using this priority:</p>
                    <ol>
                        <li><code>birth_lga</code> (Dojah field)</li>
                        <li><code>birthlga</code> (Alternative spelling)</li>
                        <li><code>origin_lga</code> (Fallback 1)</li>
                        <li><code>place_of_birth</code> (Fallback 2)</li>
                    </ol>
                    
                    <?php
                        $detectedSource = null;
                        if (!empty($ninData['birth_lga'])) {
                            $detectedSource = "birth_lga: " . $ninData['birth_lga'];
                        } elseif (!empty($ninData['birthlga'])) {
                            $detectedSource = "birthlga: " . $ninData['birthlga'];
                        } elseif (!empty($ninData['origin_lga'])) {
                            $detectedSource = "origin_lga: " . $ninData['origin_lga'];
                        } elseif (!empty($ninData['place_of_birth'])) {
                            $detectedSource = "place_of_birth: " . $ninData['place_of_birth'];
                        }
                    ?>
                    
                    <?php if ($detectedSource): ?>
                        <p style="background: #e8f5e9; padding: 10px; border-radius: 4px;">
                            ✓ <strong>Detected:</strong> <code><?php echo htmlspecialchars($detectedSource); ?></code>
                        </p>
                    <?php else: ?>
                        <p style="background: #ffebee; padding: 10px; border-radius: 4px;">
                            ✗ <strong>Not Found:</strong> None of the expected fields are present in the NIN data
                        </p>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <div class="warning-box">
                    <strong>⚠️ No Stored Data</strong><br>
                    NIN verification data is empty or missing.
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee;">
            <a href="pages/user/profile.php" style="text-decoration: none; color: #2196F3;">← Back to Profile</a>
        </div>
    </div>
</body>
</html>
