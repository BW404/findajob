<?php
require_once '../config/database.php';
require_once '../config/session.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

try {
    $userId = getCurrentUserId();
    $userType = $_SESSION['user_type'];
    // Prevent profile picture changes if job seeker has NIN verified
    if ($userType === 'job_seeker') {
        $chk = $pdo->prepare("SELECT nin_verified FROM job_seeker_profiles WHERE user_id = ?");
        $chk->execute([$userId]);
        $r = $chk->fetch();
        if (!empty($r['nin_verified'])) {
            echo json_encode(['success' => false, 'error' => 'Profile picture cannot be changed after NIN verification.']);
            exit;
        }
    }
    
    // Check if file was uploaded
    if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error occurred');
    }
    
    $file = $_FILES['profile_picture'];
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $fileType = mime_content_type($file['tmp_name']);
    
    if (!in_array($fileType, $allowedTypes)) {
        throw new Exception('Invalid file type. Only JPG, PNG, GIF, and WebP images are allowed.');
    }
    
    // Validate file size (max 5MB)
    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize) {
        throw new Exception('File too large. Maximum size is 5MB.');
    }
    
    // Create upload directory if it doesn't exist
    $uploadDir = '../uploads/profile-pictures/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . $userId . '_' . time() . '.' . $extension;
    $filePath = $uploadDir . $filename;
    
    // Delete old profile picture if exists
    $oldPicture = null;
    if ($userType === 'job_seeker') {
        $stmt = $pdo->prepare("SELECT profile_picture FROM job_seeker_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        $oldPicture = $result['profile_picture'] ?? null;
    } else if ($userType === 'employer') {
        $stmt = $pdo->prepare("SELECT logo FROM employer_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        $oldPicture = $result['logo'] ?? null;
    }
    
    if ($oldPicture && file_exists($uploadDir . $oldPicture)) {
        unlink($uploadDir . $oldPicture);
    }
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception('Failed to save uploaded file');
    }
    
    // Update database
    if ($userType === 'job_seeker') {
        // Update job_seeker_profiles
        $stmt = $pdo->prepare("UPDATE job_seeker_profiles SET profile_picture = ? WHERE user_id = ?");
        $stmt->execute([$filename, $userId]);
        
        // Check if users table has profile_picture column
        try {
            $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
            $stmt->execute([$filename, $userId]);
        } catch (PDOException $e) {
            // Column doesn't exist yet, that's okay
            error_log("Users table doesn't have profile_picture column yet: " . $e->getMessage());
        }
        
    } else if ($userType === 'employer') {
        // Update employer_profiles (logo field)
        $stmt = $pdo->prepare("UPDATE employer_profiles SET logo = ? WHERE user_id = ?");
        $stmt->execute([$filename, $userId]);
        
        // Check if users table has profile_picture column
        try {
            $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
            $stmt->execute([$filename, $userId]);
        } catch (PDOException $e) {
            // Column doesn't exist yet, that's okay
            error_log("Users table doesn't have profile_picture column yet: " . $e->getMessage());
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Profile picture uploaded successfully',
        'filename' => $filename,
        'url' => '/findajob/uploads/profile-pictures/' . $filename
    ]);
    
} catch (Exception $e) {
    error_log("Profile picture upload error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
