<?php
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

// Ensure POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /findajob/pages/jobs/browse.php');
    exit;
}

$jobId = isset($_POST['job_id']) ? (int)$_POST['job_id'] : 0;
if ($jobId <= 0) {
    header('Location: /findajob/pages/jobs/browse.php');
    exit;
}

// Require job seeker to apply
if (!isLoggedIn() || !isJobSeeker()) {
    // Redirect to login with return URL
    $return = '/findajob/pages/jobs/details.php?id=' . $jobId;
    header('Location: /findajob/pages/auth/login.php?return=' . urlencode($return));
    exit;
}

$userId = getCurrentUserId();

try {
    // Ensure job exists and is active
    $stmt = $pdo->prepare("SELECT id, title FROM jobs WHERE id = ? AND STATUS = 'active'");
    $stmt->execute([$jobId]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$job) {
        header('Location: /findajob/pages/jobs/browse.php?error=not_found');
        exit;
    }

    // Create application record if table exists
    try {
        $insert = $pdo->prepare("INSERT INTO job_applications (job_id, user_id, created_at) VALUES (?, ?, NOW())");
        $insert->execute([$jobId, $userId]);
        // Increment applications_count on jobs if column exists
        try {
            $pdo->prepare("UPDATE jobs SET applications_count = COALESCE(applications_count,0) + 1 WHERE id = ?")->execute([$jobId]);
        } catch (Exception $e) {
            // ignore
        }

        header('Location: /findajob/pages/jobs/details.php?id=' . $jobId . '&applied=1');
        exit;
    } catch (PDOException $e) {
        // Table might not exist - fallback: redirect back with info
        error_log('Job application error: ' . $e->getMessage());
        header('Location: /findajob/pages/jobs/details.php?id=' . $jobId . '&applied=0');
        exit;
    }

} catch (PDOException $e) {
    error_log('Apply handler error: ' . $e->getMessage());
    header('Location: /findajob/pages/jobs/details.php?id=' . $jobId . '&applied=0');
    exit;
}
