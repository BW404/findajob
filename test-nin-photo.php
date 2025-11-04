<?php
/**
 * Test NIN Photo Extraction and Save
 */

require_once 'config/database.php';
require_once 'config/session.php';

// Check if user is logged in
if (!isLoggedIn()) {
    die("Please log in first");
}

$userId = getCurrentUserId();

// Get NIN verification data
$stmt = $pdo->prepare("
    SELECT nin_verification_data, profile_picture 
    FROM job_seeker_profiles 
    WHERE user_id = ?
");
$stmt->execute([$userId]);
$profile = $stmt->fetch();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test NIN Photo</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        .success { color: green; }
        .error { color: red; }
        img { max-width: 300px; border: 2px solid #333; }
    </style>
</head>
<body>
    <h1>NIN Photo Test</h1>
    
    <div class="section">
        <h2>Current Profile Picture</h2>
        <?php if (!empty($profile['profile_picture'])): ?>
            <p><strong>Path:</strong> <?php echo htmlspecialchars($profile['profile_picture']); ?></p>
            <img src="<?php echo htmlspecialchars($profile['profile_picture']); ?>" alt="Profile Picture">
        <?php else: ?>
            <p class="error">No profile picture set</p>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2>NIN Verification Data</h2>
        <?php if (!empty($profile['nin_verification_data'])): ?>
            <?php 
            $ninData = json_decode($profile['nin_verification_data'], true);
            ?>
            <p class="success">✅ NIN verification data found</p>
            
            <h3>Available Fields:</h3>
            <ul>
                <?php foreach (array_keys($ninData) as $key): ?>
                    <li><strong><?php echo htmlspecialchars($key); ?>:</strong> 
                        <?php if ($key === 'photo'): ?>
                            <span style="color: blue;">[Base64 Image Data - <?php echo strlen($ninData[$key]); ?> characters]</span>
                        <?php else: ?>
                            <?php echo htmlspecialchars(is_array($ninData[$key]) ? json_encode($ninData[$key]) : $ninData[$key]); ?>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            
            <?php if (!empty($ninData['photo'])): ?>
                <h3>Photo from NIN Data:</h3>
                <p>Attempting to display photo from NIN data...</p>
                <?php
                $photoData = $ninData['photo'];
                // Check if it's already a data URI
                if (strpos($photoData, 'data:image') !== 0) {
                    $photoData = 'data:image/jpeg;base64,' . $photoData;
                }
                ?>
                <img src="<?php echo $photoData; ?>" alt="NIN Photo">
                
                <h3>Manual Photo Save Test:</h3>
                <form method="POST" style="margin-top: 15px;">
                    <button type="submit" name="save_photo" style="padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer;">
                        Save NIN Photo as Profile Picture
                    </button>
                </form>
            <?php else: ?>
                <p class="error">❌ No photo field in NIN data</p>
            <?php endif; ?>
        <?php else: ?>
            <p class="error">❌ No NIN verification data found</p>
            <p>Please verify your NIN first from your profile page.</p>
        <?php endif; ?>
    </div>
    
    <?php
    // Handle manual photo save
    if (isset($_POST['save_photo']) && !empty($profile['nin_verification_data'])) {
        $ninData = json_decode($profile['nin_verification_data'], true);
        
        if (!empty($ninData['photo'])) {
            echo '<div class="section">';
            echo '<h2>Photo Save Result:</h2>';
            
            try {
                // Create uploads directory if it doesn't exist
                $uploadDir = 'uploads/profile_pictures/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                    echo '<p>✅ Created directory: ' . htmlspecialchars($uploadDir) . '</p>';
                }
                
                $photoData = $ninData['photo'];
                
                // Remove data URI scheme if present
                if (strpos($photoData, 'data:image') === 0) {
                    $photoData = preg_replace('/^data:image\/\w+;base64,/', '', $photoData);
                }
                
                echo '<p>Photo data length: ' . strlen($photoData) . ' characters</p>';
                
                $imageContent = base64_decode($photoData, true);
                
                if ($imageContent === false) {
                    echo '<p class="error">❌ Failed to decode base64 data</p>';
                } else {
                    echo '<p>✅ Successfully decoded base64 data (' . strlen($imageContent) . ' bytes)</p>';
                    
                    // Generate unique filename
                    $filename = 'nin_' . $userId . '_' . time() . '.jpg';
                    $filePath = $uploadDir . $filename;
                    
                    // Save the file
                    if (file_put_contents($filePath, $imageContent)) {
                        echo '<p class="success">✅ File saved successfully: ' . htmlspecialchars($filePath) . '</p>';
                        echo '<p>File size: ' . filesize($filePath) . ' bytes</p>';
                        
                        // Update database
                        $stmt = $pdo->prepare("UPDATE job_seeker_profiles SET profile_picture = ? WHERE user_id = ?");
                        if ($stmt->execute([$filePath, $userId])) {
                            echo '<p class="success">✅ Database updated successfully!</p>';
                            echo '<p><a href="">Refresh page to see changes</a></p>';
                        } else {
                            echo '<p class="error">❌ Failed to update database</p>';
                        }
                    } else {
                        echo '<p class="error">❌ Failed to write file to disk</p>';
                    }
                }
            } catch (Exception $e) {
                echo '<p class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
            
            echo '</div>';
        }
    }
    ?>
    
    <div class="section">
        <h2>Error Log Check</h2>
        <p>Check your PHP error log for detailed debugging information:</p>
        <ul>
            <li>XAMPP: <code>E:\XAMPP\php\logs\php_error_log</code></li>
            <li>Or: <code>E:\XAMPP\apache\logs\error.log</code></li>
        </ul>
    </div>
</body>
</html>
