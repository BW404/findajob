<?php
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../includes/pro-features.php';

// Check if GET request (show application form) or POST (process application)
$isFormSubmission = $_SERVER['REQUEST_METHOD'] === 'POST';

// Get job ID from GET or POST
$jobId = 0;
if ($isFormSubmission) {
    $jobId = isset($_POST['job_id']) ? (int)$_POST['job_id'] : 0;
} else {
    $jobId = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
}

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
$errors = [];
$success = false;

// Check Pro feature limits (daily applications)
$subscription = getUserSubscription($pdo, $userId);
$isPro = $subscription['is_pro'];
$limits = getFeatureLimits($isPro);

if (!$isPro && $isFormSubmission) {
    // Check daily application limit for Basic users
    $today_start = date('Y-m-d 00:00:00');
    $today_end = date('Y-m-d 23:59:59');
    $todayStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM job_applications 
        WHERE job_seeker_id = ? 
        AND applied_at BETWEEN ? AND ?
    ");
    $todayStmt->execute([$userId, $today_start, $today_end]);
    $applications_today = $todayStmt->fetchColumn();
    
    if ($applications_today >= $limits['applications_per_day']) {
        // Redirect to applications page with limit error
        header('Location: /findajob/pages/user/applications.php?error=daily_limit');
        exit;
    }
}

try {
    // Ensure job exists and is active
    $stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ? AND STATUS = 'active'");
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
    
    // Get user profile info
    $userStmt = $pdo->prepare("SELECT first_name, last_name, email, phone FROM users WHERE id = ?");
    $userStmt->execute([$userId]);
    $userProfile = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get ALL user's CVs (not just primary)
    $allCVs = [];
    $primaryCV = null;
    try {
        $cvStmt = $pdo->prepare("SELECT id, title, file_path, is_primary, created_at FROM cvs WHERE user_id = ? ORDER BY is_primary DESC, created_at DESC");
        $cvStmt->execute([$userId]);
        $allCVs = $cvStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Find primary CV
        foreach ($allCVs as $cv) {
            if ($cv['is_primary']) {
                $primaryCV = $cv;
                break;
            }
        }
        
        // If no primary, use most recent
        if (!$primaryCV && !empty($allCVs)) {
            $primaryCV = $allCVs[0];
        }
    } catch (PDOException $e) {
        // CVs table might not exist
        error_log('CV fetch error: ' . $e->getMessage());
    }

    // Process form submission
    if ($isFormSubmission) {
        // Validate inputs
        $applicantName = trim($_POST['applicant_name'] ?? '');
        $applicantEmail = trim($_POST['applicant_email'] ?? '');
        $applicantPhone = trim($_POST['applicant_phone'] ?? '');
        $applicationMessage = trim($_POST['application_message'] ?? '');
        $cvId = !empty($_POST['cv_id']) ? (int)$_POST['cv_id'] : null;
        
        if (empty($applicantName)) {
            $errors[] = 'Full name is required';
        }
        
        if (empty($applicantEmail) || !filter_var($applicantEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid email address is required';
        }
        
        if (empty($applicationMessage) || strlen($applicationMessage) < 20) {
            $errors[] = 'Please provide a message (at least 20 characters)';
        }
        
        // Validate CV selection if CVs are available
        if (!empty($allCVs) && empty($cvId)) {
            $errors[] = 'Please select a CV to submit with your application';
        }
        
        // Verify the selected CV belongs to the user (security check)
        if ($cvId) {
            $cvCheck = $pdo->prepare("SELECT id FROM cvs WHERE id = ? AND user_id = ?");
            $cvCheck->execute([$cvId, $userId]);
            if (!$cvCheck->fetch()) {
                $errors[] = 'Invalid CV selected';
                $cvId = null;
            }
        }
        
        // If no errors, create application
        if (empty($errors)) {
            try {
                $insert = $pdo->prepare("
                    INSERT INTO job_applications (
                        job_id, job_seeker_id, cv_id,
                        applicant_name, applicant_email, applicant_phone,
                        application_message, application_status,
                        applied_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'applied', NOW())
                ");
                
                $insert->execute([
                    $jobId,
                    $userId,
                    $cvId,
                    $applicantName,
                    $applicantEmail,
                    $applicantPhone,
                    $applicationMessage
                ]);
                
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
                $errors[] = 'Failed to submit application. Please try again.';
            }
        }
    }

} catch (PDOException $e) {
    error_log('Apply handler error: ' . $e->getMessage());
    header('Location: /findajob/pages/jobs/details.php?id=' . $jobId . '&applied=error&msg=' . urlencode('An error occurred'));
    exit;
}

// If we're here, show the application form
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Easy Apply - <?php echo htmlspecialchars($job['title']); ?></title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <style>
        .apply-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .apply-card {
            background: var(--surface);
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        .apply-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid var(--border);
        }
        .apply-header h1 {
            font-size: 1.75rem;
            margin: 0 0 0.5rem;
            color: var(--text-primary);
        }
        .apply-header .job-title {
            font-size: 1.1rem;
            color: var(--text-secondary);
            font-weight: normal;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        .form-group label .required {
            color: var(--primary);
        }
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        .form-group .helper-text {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-top: 0.5rem;
        }
        .error-list {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        .error-list ul {
            margin: 0.5rem 0 0;
            padding-left: 1.5rem;
            color: #991b1b;
        }
        .cv-info {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 0.5rem;
        }
        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        .btn {
            flex: 1;
            padding: 0.85rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: var(--primary);
            color: white;
            border: none;
        }
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border);
        }
        .btn-secondary:hover {
            background: var(--surface-hover);
        }
        .cv-selector {
            position: relative;
        }
        .cv-selector select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 12px;
            padding-right: 2.5rem;
        }
        .cv-selector select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
        }
        @media (max-width: 640px) {
            .apply-card {
                padding: 1.5rem;
            }
            .btn-group {
                flex-direction: column-reverse;
            }
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="apply-container">
        <div class="apply-card">
            <div class="apply-header">
                <h1>✨ Easy Apply</h1>
                <div class="job-title">
                    <?php echo htmlspecialchars($job['title']); ?> at 
                    <?php echo htmlspecialchars($job['company_name']); ?>
                </div>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="error-list">
                    <strong style="color: #991b1b;">⚠️ Please fix the following errors:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="job_id" value="<?php echo (int)$jobId; ?>">
                
                <div class="form-group">
                    <label for="applicant_name">
                        Full Name <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="applicant_name" 
                        name="applicant_name" 
                        value="<?php echo htmlspecialchars($_POST['applicant_name'] ?? ($userProfile['first_name'] . ' ' . $userProfile['last_name'])); ?>"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="applicant_email">
                        Email Address <span class="required">*</span>
                    </label>
                    <input 
                        type="email" 
                        id="applicant_email" 
                        name="applicant_email" 
                        value="<?php echo htmlspecialchars($_POST['applicant_email'] ?? $userProfile['email']); ?>"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="applicant_phone">
                        Phone Number
                    </label>
                    <input 
                        type="tel" 
                        id="applicant_phone" 
                        name="applicant_phone" 
                        value="<?php echo htmlspecialchars($_POST['applicant_phone'] ?? $userProfile['phone'] ?? ''); ?>"
                        placeholder="+234 800 000 0000"
                    >
                </div>
                
                <div class="form-group">
                    <label for="application_message">
                        Cover Letter / Message <span class="required">*</span>
                    </label>
                    <textarea 
                        id="application_message" 
                        name="application_message" 
                        required
                        placeholder="Tell the employer why you're a great fit for this role..."
                    ><?php echo htmlspecialchars($_POST['application_message'] ?? ''); ?></textarea>
                    <div class="helper-text">
                        Minimum 20 characters. Introduce yourself and explain your interest in the position.
                    </div>
                </div>
                
                <!-- CV Selection -->
                <div class="form-group cv-selector">
                    <label for="cv_id">
                        Select Your CV/Resume <span class="required">*</span>
                    </label>
                    
                    <?php if (!empty($allCVs)): ?>
                        <select id="cv_id" name="cv_id" required>
                            <?php foreach ($allCVs as $cv): ?>
                                <option value="<?php echo (int)$cv['id']; ?>" 
                                        <?php echo ($primaryCV && $cv['id'] == $primaryCV['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cv['title']); ?>
                                    <?php if ($cv['is_primary']): ?>
                                        (Primary)
                                    <?php endif; ?>
                                    - Updated <?php echo date('M j, Y', strtotime($cv['created_at'])); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <div class="helper-text" style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.5rem;">
                            <span style="color: #059669;">
                                ✓ <?php echo count($allCVs); ?> CV<?php echo count($allCVs) > 1 ? 's' : ''; ?> available
                            </span>
                            <a href="/findajob/pages/user/cv-manager.php" target="_blank" style="color: var(--primary); font-size: 0.9rem; text-decoration: none;">
                                + Upload New CV
                            </a>
                        </div>
                    <?php else: ?>
                        <div style="background: #fef3c7; border: 1px solid #fcd34d; border-radius: 8px; padding: 1rem;">
                            <strong style="color: #92400e;">📄 No CV Found</strong>
                            <p style="margin: 0.5rem 0 0; color: #92400e; font-size: 0.9rem;">
                                You need to <a href="/findajob/pages/user/cv-manager.php" style="color: #92400e; text-decoration: underline; font-weight: 600;">upload a CV</a> before applying.
                            </p>
                            <div style="margin-top: 1rem;">
                                <a href="/findajob/pages/user/cv-manager.php" class="btn btn-primary" style="display: inline-block; padding: 0.75rem 1.5rem;">
                                    Upload Your CV Now
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="btn-group">
                    <a href="/findajob/pages/jobs/details.php?id=<?php echo (int)$jobId; ?>" class="btn btn-secondary">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary" <?php echo empty($allCVs) ? 'disabled' : ''; ?>>
                        <?php echo empty($allCVs) ? 'Upload CV Required' : 'Submit Application'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>