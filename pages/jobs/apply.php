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

    // Check if user has already applied
    try {
        $check = $pdo->prepare("SELECT id FROM job_applications WHERE job_id = ? AND job_seeker_id = ? LIMIT 1");
        $check->execute([$jobId, $userId]);
        if ($check->fetch()) {
            // Already applied
            header('Location: /findajob/pages/jobs/details.php?id=' . $jobId . '&applied=already');
            exit;
        }
    } catch (PDOException $e) {
        // Table might not exist, continue with application
        error_log('Job application check error: ' . $e->getMessage());
    }

    // Create application record
    try {
        $insert = $pdo->prepare("INSERT INTO job_applications (job_id, job_seeker_id, application_status) VALUES (?, ?, 'applied')");
        $insert->execute([$jobId, $userId]);
        
        // Increment applications_count on jobs
        try {
            $pdo->prepare("UPDATE jobs SET applications_count = COALESCE(applications_count,0) + 1 WHERE id = ?")->execute([$jobId]);
        } catch (Exception $e) {
            error_log('Failed to update applications_count: ' . $e->getMessage());
        }

        header('Location: /findajob/pages/jobs/details.php?id=' . $jobId . '&applied=success');
        exit;
    } catch (PDOException $e) {
        // Application failed
        error_log('Job application error: ' . $e->getMessage());
        header('Location: /findajob/pages/jobs/details.php?id=' . $jobId . '&applied=error&msg=' . urlencode('Failed to submit application. Please try again.'));
        exit;
    }

} catch (PDOException $e) {
    error_log('Apply handler error: ' . $e->getMessage());
    header('Location: /findajob/pages/jobs/details.php?id=' . $jobId . '&applied=0');
    exit;
}
