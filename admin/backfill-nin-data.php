<?php
/**
 * Admin Script: Backfill NIN Data for Verified Users
 * Applies stored nin_verification_data to user profiles for all verified accounts
 * This is useful after adding new fields or updating the apply logic
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

// Admin authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    die("Access denied. Admin privileges required.");
}

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if religion column exists
$religionExists = false;
$checkCol = $conn->query("
    SELECT COUNT(*) as col_count 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = '" . DB_NAME . "' 
    AND TABLE_NAME = 'job_seeker_profiles' 
    AND COLUMN_NAME = 'religion'
");
if ($checkCol && $row = $checkCol->fetch_assoc()) {
    $religionExists = ($row['col_count'] > 0);
}

// Check if city_of_birth column exists
$cityOfBirthExists = false;
$checkCol2 = $conn->query("
    SELECT COUNT(*) as col_count 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = '" . DB_NAME . "' 
    AND TABLE_NAME = 'job_seeker_profiles' 
    AND COLUMN_NAME = 'city_of_birth'
");
if ($checkCol2 && $row2 = $checkCol2->fetch_assoc()) {
    $cityOfBirthExists = ($row2['col_count'] > 0);
}

// Check if state_of_origin column exists
$stateOfOriginExists = false;
$checkCol3 = $conn->query("
    SELECT COUNT(*) as col_count 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = '" . DB_NAME . "' 
    AND TABLE_NAME = 'job_seeker_profiles' 
    AND COLUMN_NAME = 'state_of_origin'
");
if ($checkCol3 && $row3 = $checkCol3->fetch_assoc()) {
    $stateOfOriginExists = ($row3['col_count'] > 0);
}

// Check if lga_of_origin column exists
$lgaOfOriginExists = false;
$checkCol4 = $conn->query("
    SELECT COUNT(*) as col_count 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = '" . DB_NAME . "' 
    AND TABLE_NAME = 'job_seeker_profiles' 
    AND COLUMN_NAME = 'lga_of_origin'
");
if ($checkCol4 && $row4 = $checkCol4->fetch_assoc()) {
    $lgaOfOriginExists = ($row4['col_count'] > 0);
}

// Fetch all verified job seekers with stored NIN data
$query = "
    SELECT 
        u.id as user_id,
        u.first_name,
        u.last_name,
        jsp.id as profile_id,
        jsp.nin_verification_data
    FROM users u
    INNER JOIN job_seeker_profiles jsp ON u.id = jsp.user_id
    WHERE u.user_type = 'job_seeker'
    AND jsp.nin_verified = 1
    AND jsp.nin_verification_data IS NOT NULL
    AND jsp.nin_verification_data != ''
    ORDER BY jsp.nin_verified_at DESC
";

$result = $conn->query($query);

if (!$result) {
    die("Query failed: " . $conn->error);
}

$totalUsers = $result->num_rows;
$processed = 0;
$updated = 0;
$skipped = 0;
$errors = [];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backfill NIN Data - Admin</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 1200px;
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
        .warning-box {
            background: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 15px;
            margin: 20px 0;
        }
        .success-box {
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 15px;
            margin: 20px 0;
        }
        .error-box {
            background: #ffebee;
            border-left: 4px solid #f44336;
            padding: 15px;
            margin: 20px 0;
        }
        .user-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .user-item:last-child {
            border-bottom: none;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success {
            background: #4caf50;
            color: white;
        }
        .badge-warning {
            background: #ff9800;
            color: white;
        }
        .badge-error {
            background: #f44336;
            color: white;
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
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat-card {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 4px;
            text-align: center;
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #2196F3;
        }
        .stat-label {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Backfill NIN Data for Verified Users</h1>
        
        <div class="info-box">
            <strong>ℹ️ Information</strong><br>
            This script will apply stored NIN verification data to user profiles for all verified job seekers.<br>
            Fields updated: First Name, Last Name, Date of Birth, State of Origin, LGA of Origin, City of Birth<?php echo $religionExists ? ', Religion' : ''; ?>.
        </div>

        <?php if (!$religionExists || !$cityOfBirthExists || !$stateOfOriginExists || !$lgaOfOriginExists): ?>
        <div class="warning-box">
            <strong>⚠️ Warning - Missing Columns</strong><br>
            Some columns are missing from the database:<br>
            <?php if (!$stateOfOriginExists): ?><code>state_of_origin</code> not found<br><?php endif; ?>
            <?php if (!$lgaOfOriginExists): ?><code>lga_of_origin</code> not found<br><?php endif; ?>
            <?php if (!$cityOfBirthExists): ?><code>city_of_birth</code> not found<br><?php endif; ?>
            <?php if (!$religionExists): ?><code>religion</code> not found<br><?php endif; ?>
            <br>
            <strong>Run the migration to add these columns:</strong><br>
            <code>php database/run-migration.php add-nin-profile-fields.sql</code>
        </div>
        <?php endif; ?>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalUsers; ?></div>
                <div class="stat-label">Verified Users Found</div>
            </div>
        </div>

        <?php if ($totalUsers === 0): ?>
            <div class="info-box">
                No verified users with NIN data found. Nothing to process.
            </div>
        <?php else: ?>
            <h3>Processing Users...</h3>
            <div style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                <?php
                // Process each user
                while ($user = $result->fetch_assoc()) {
                    $processed++;
                    
                    // Decode NIN data
                    $ninData = json_decode($user['nin_verification_data'], true);
                    
                    if (!$ninData || !is_array($ninData)) {
                        echo '<div class="user-item">';
                        echo '<span>User ID: ' . $user['user_id'] . ' - ' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . '</span>';
                        echo '<span class="badge badge-warning">SKIPPED - Invalid JSON</span>';
                        echo '</div>';
                        $skipped++;
                        $errors[] = "User ID {$user['user_id']}: Invalid NIN data JSON";
                        continue;
                    }
                    
                    $conn->begin_transaction();
                    
                    try {
                        $updateSuccess = false;
                        
                        // Update users table (name)
                        if (!empty($ninData['first_name']) || !empty($ninData['last_name'])) {
                            $updateFields = [];
                            $types = '';
                            $params = [];
                            
                            if (!empty($ninData['first_name'])) {
                                $updateFields[] = "first_name = ?";
                                $types .= 's';
                                $params[] = $ninData['first_name'];
                            }
                            if (!empty($ninData['last_name'])) {
                                $updateFields[] = "last_name = ?";
                                $types .= 's';
                                $params[] = $ninData['last_name'];
                            }
                            
                            $types .= 'i';
                            $params[] = $user['user_id'];
                            
                            $stmt = $conn->prepare("UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?");
                            $stmt->bind_param($types, ...$params);
                            $stmt->execute();
                            $stmt->close();
                            $updateSuccess = true;
                        }
                        
                        // Update job_seeker_profiles table
                        $profileUpdates = [];
                        $profileTypes = '';
                        $profileParams = [];
                        
                        if (!empty($ninData['date_of_birth'])) {
                            $profileUpdates[] = "date_of_birth = ?";
                            $profileTypes .= 's';
                            $profileParams[] = $ninData['date_of_birth'];
                        }
                        
                        // State of origin
                        $stateOfOrigin = $ninData['origin_state'] ?? $ninData['residence_state'] ?? null;
                        if ($stateOfOrigin && $stateOfOriginExists) {
                            $profileUpdates[] = "state_of_origin = ?";
                            $profileTypes .= 's';
                            $profileParams[] = $stateOfOrigin;
                        }
                        
                        // LGA of origin
                        $lgaOfOrigin = $ninData['origin_lga'] ?? $ninData['residence_lga'] ?? null;
                        if ($lgaOfOrigin && $lgaOfOriginExists) {
                            $profileUpdates[] = "lga_of_origin = ?";
                            $profileTypes .= 's';
                            $profileParams[] = $lgaOfOrigin;
                        }

                        // City of birth - can come from birth_lga, birthlga, origin_lga, or place_of_birth
                        $cityOfBirth = $ninData['birth_lga'] ?? $ninData['birthlga'] ?? $ninData['origin_lga'] ?? $ninData['place_of_birth'] ?? null;
                        if ($cityOfBirth && $cityOfBirthExists) {
                            $profileUpdates[] = "city_of_birth = ?";
                            $profileTypes .= 's';
                            $profileParams[] = $cityOfBirth;
                        }
                        
                        // Religion (if column exists) - check multiple field names
                        $religion = $ninData['religion'] ?? $ninData['Religion'] ?? null;
                        if ($religionExists && !empty($religion)) {
                            $profileUpdates[] = "religion = ?";
                            $profileTypes .= 's';
                            $profileParams[] = $religion;
                        }
                        
                        if (!empty($profileUpdates)) {
                            $profileTypes .= 'i';
                            $profileParams[] = $user['user_id'];
                            
                            $stmt = $conn->prepare("UPDATE job_seeker_profiles SET " . implode(', ', $profileUpdates) . " WHERE user_id = ?");
                            $stmt->bind_param($profileTypes, ...$profileParams);
                            $stmt->execute();
                            $stmt->close();
                            $updateSuccess = true;
                        }
                        
                        $conn->commit();
                        
                        echo '<div class="user-item">';
                        echo '<span>User ID: ' . $user['user_id'] . ' - ' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . '</span>';
                        echo '<span class="badge badge-success">✓ UPDATED</span>';
                        echo '</div>';
                        $updated++;
                        
                    } catch (Exception $e) {
                        $conn->rollback();
                        echo '<div class="user-item">';
                        echo '<span>User ID: ' . $user['user_id'] . ' - ' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . '</span>';
                        echo '<span class="badge badge-error">✗ ERROR</span>';
                        echo '</div>';
                        $skipped++;
                        $errors[] = "User ID {$user['user_id']}: " . $e->getMessage();
                    }
                    
                    // Flush output buffer to show progress in real-time
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }
                ?>
            </div>

            <div class="success-box" style="margin-top: 20px;">
                <strong>✓ Backfill Complete!</strong>
            </div>

            <div class="stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $processed; ?></div>
                    <div class="stat-label">Total Processed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="color: #4caf50;"><?php echo $updated; ?></div>
                    <div class="stat-label">Successfully Updated</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="color: #ff9800;"><?php echo $skipped; ?></div>
                    <div class="stat-label">Skipped/Errors</div>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="error-box">
                    <strong>Errors Encountered:</strong>
                    <ul style="margin: 10px 0 0 20px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

        <?php endif; ?>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee;">
            <a href="index.php" style="text-decoration: none; color: #2196F3;">← Back to Admin Dashboard</a>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>
