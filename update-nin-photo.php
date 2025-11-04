<?php
/**
 * Update Profile Picture from Existing NIN Data
 * This script extracts the photo from existing NIN verification data
 * and sets it as the profile picture
 */

require_once 'config/database.php';
require_once 'config/session.php';

// Check if user is logged in
if (!isLoggedIn()) {
    die("Please log in first");
}

$userId = getCurrentUserId();
$message = '';
$error = '';

// Get NIN verification data
$stmt = $pdo->prepare("
    SELECT nin_verification_data, profile_picture 
    FROM job_seeker_profiles 
    WHERE user_id = ?
");
$stmt->execute([$userId]);
$profile = $stmt->fetch();

// Process photo if requested
if (isset($_POST['update_photo'])) {
    if (!empty($profile['nin_verification_data'])) {
        $ninData = json_decode($profile['nin_verification_data'], true);
        
        if (!empty($ninData['photo'])) {
            try {
                // Create uploads directory if it doesn't exist
                $uploadDir = 'uploads/profile_pictures/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $photoData = $ninData['photo'];
                
                // Remove data URI scheme if present
                if (strpos($photoData, 'data:image') === 0) {
                    $photoData = preg_replace('/^data:image\/\w+;base64,/', '', $photoData);
                }
                
                $imageContent = base64_decode($photoData, true);
                
                if ($imageContent === false) {
                    $error = "Failed to decode base64 photo data";
                } else {
                    // Generate unique filename
                    $filename = 'nin_' . $userId . '_' . time() . '.jpg';
                    $filePath = $uploadDir . $filename;
                    
                    // Save the file
                    if (file_put_contents($filePath, $imageContent)) {
                        // Update database
                        $stmt = $pdo->prepare("UPDATE job_seeker_profiles SET profile_picture = ? WHERE user_id = ?");
                        if ($stmt->execute([$filePath, $userId])) {
                            $message = "Profile picture updated successfully! File saved: $filePath (" . filesize($filePath) . " bytes)";
                            // Refresh profile data
                            $stmt = $pdo->prepare("SELECT nin_verification_data, profile_picture FROM job_seeker_profiles WHERE user_id = ?");
                            $stmt->execute([$userId]);
                            $profile = $stmt->fetch();
                        } else {
                            $error = "Failed to update database";
                        }
                    } else {
                        $error = "Failed to write file to disk";
                    }
                }
            } catch (Exception $e) {
                $error = "Error: " . $e->getMessage();
            }
        } else {
            $error = "No photo found in NIN verification data";
        }
    } else {
        $error = "No NIN verification data found. Please verify your NIN first.";
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Update Profile Picture from NIN</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .content {
            padding: 30px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .section {
            margin: 25px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .section h2 {
            font-size: 20px;
            margin-bottom: 15px;
            color: #333;
        }
        
        .photo-display {
            display: flex;
            gap: 30px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .photo-box {
            text-align: center;
        }
        
        .photo-box h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .photo-box img {
            max-width: 250px;
            border-radius: 8px;
            border: 3px solid #667eea;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .photo-box .placeholder {
            width: 250px;
            height: 250px;
            background: #e9ecef;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 14px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            text-decoration: none;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            box-shadow: 0 8px 20px rgba(108, 117, 125, 0.4);
        }
        
        .info-item {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #495057;
            width: 200px;
            flex-shrink: 0;
        }
        
        .info-value {
            color: #212529;
        }
        
        .actions {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🖼️ Update Profile Picture from NIN</h1>
            <p>Extract and set your NIN photo as your profile picture</p>
        </div>
        
        <div class="content">
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <span>✅</span>
                    <span><?php echo htmlspecialchars($message); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <span>❌</span>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (empty($profile['nin_verification_data'])): ?>
                <div class="alert alert-info">
                    <span>ℹ️</span>
                    <span>You need to verify your NIN first before you can use this feature.</span>
                </div>
                <div class="actions">
                    <a href="pages/user/profile.php" class="btn">Go to Profile to Verify NIN</a>
                </div>
            <?php else: ?>
                <?php $ninData = json_decode($profile['nin_verification_data'], true); ?>
                
                <div class="section">
                    <h2>Current Status</h2>
                    <div class="photo-display">
                        <div class="photo-box">
                            <h3>Current Profile Picture</h3>
                            <?php if (!empty($profile['profile_picture']) && file_exists($profile['profile_picture'])): ?>
                                <img src="<?php echo htmlspecialchars($profile['profile_picture']); ?>" alt="Current Profile">
                            <?php else: ?>
                                <div class="placeholder">No profile picture set</div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($ninData['photo'])): ?>
                            <div class="photo-box">
                                <h3>NIN Photo</h3>
                                <?php
                                $photoData = $ninData['photo'];
                                if (strpos($photoData, 'data:image') !== 0) {
                                    $photoData = 'data:image/jpeg;base64,' . $photoData;
                                }
                                ?>
                                <img src="<?php echo $photoData; ?>" alt="NIN Photo">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="section">
                    <h2>NIN Verification Details</h2>
                    <div class="info-item">
                        <div class="info-label">Name</div>
                        <div class="info-value"><?php echo htmlspecialchars($ninData['first_name'] . ' ' . ($ninData['middle_name'] ?? '') . ' ' . $ninData['last_name']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Gender</div>
                        <div class="info-value"><?php echo htmlspecialchars($ninData['gender'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Date of Birth</div>
                        <div class="info-value"><?php echo htmlspecialchars($ninData['date_of_birth'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Photo Available</div>
                        <div class="info-value"><?php echo !empty($ninData['photo']) ? '✅ Yes' : '❌ No'; ?></div>
                    </div>
                </div>
                
                <form method="POST" class="actions">
                    <?php if (!empty($ninData['photo'])): ?>
                        <button type="submit" name="update_photo" class="btn">
                            🔄 Update Profile Picture from NIN
                        </button>
                    <?php endif; ?>
                    <a href="pages/user/profile.php" class="btn btn-secondary">Back to Profile</a>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
