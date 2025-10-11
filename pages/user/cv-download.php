<?php
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/constants.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access Denied');
}

// Get CV ID from URL
$cv_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : 'download';

if (!$cv_id) {
    header('HTTP/1.0 404 Not Found');
    exit('CV not found');
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Get CV information
$stmt = $pdo->prepare("
    SELECT cv.*, CONCAT(u.first_name, ' ', u.last_name) as owner_name 
    FROM cvs cv
    JOIN users u ON cv.user_id = u.id
    WHERE cv.id = ?
");
$stmt->execute([$cv_id]);
$cv = $stmt->fetch();

if (!$cv) {
    header('HTTP/1.0 404 Not Found');
    exit('CV not found');
}

// Check permissions
$can_access = false;

if ($user_type === 'job_seeker' && $cv['user_id'] == $user_id) {
    // CV owner can always access their own CVs
    $can_access = true;
} elseif ($user_type === 'employer') {
    // Employers can access CVs if:
    // 1. They have a job posting and the user applied
    // 2. They have a premium subscription (for CV search)
    // For now, we'll implement basic access - you can enhance this later
    
    $stmt = $pdo->prepare("
        SELECT 1 FROM job_applications ja
        JOIN jobs j ON ja.job_id = j.id
        WHERE j.company_id = (SELECT company_id FROM users WHERE id = ?)
        AND ja.user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$user_id, $cv['user_id']]);
    
    if ($stmt->fetch()) {
        $can_access = true;
    }
} elseif ($user_type === 'admin') {
    // Admins can access all CVs
    $can_access = true;
}

if (!$can_access) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access Denied');
}

// Get file path
$file_path = '../../uploads/cvs/' . $cv['file_path'];

if (!file_exists($file_path)) {
    header('HTTP/1.0 404 Not Found');
    exit('File not found');
}

// Set appropriate headers based on action
if ($action === 'preview') {
    // For preview, set headers to display in browser
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $cv['original_filename'] . '"');
} else {
    // For download, force download
    $mime_type = mime_content_type($file_path);
    header('Content-Type: ' . $mime_type);
    header('Content-Disposition: attachment; filename="' . $cv['original_filename'] . '"');
}

header('Content-Length: ' . filesize($file_path));
header('Cache-Control: private');
header('Pragma: private');

// Output the file
readfile($file_path);

// Log the access for audit purposes
if (isDevelopmentMode()) {
    $log_message = sprintf(
        "[%s] CV Access - User: %d (%s), CV: %d (%s), Action: %s, Owner: %s\n",
        date('Y-m-d H:i:s'),
        $user_id,
        $user_type,
        $cv_id,
        $cv['title'],
        $action,
        $cv['owner_name']
    );
    
    error_log($log_message, 3, '../../logs/cv_access.log');
}

exit();
?>