<?php
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/constants.php';

requireEmployer();

$userId = getCurrentUserId();

// Get employer profile data
$stmt = $pdo->prepare("
    SELECT u.*, ep.* 
    FROM users u 
    LEFT JOIN employer_profiles ep ON u.id = ep.user_id 
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Get job posting count (with error handling)
$jobStats = ['job_count' => 0, 'applications_count' => 0, 'views_count' => 0];
try {
    $stmt = $pdo->prepare("SELECT 
        COUNT(*) as job_count,
        COALESCE(SUM(applications_count), 0) as applications_count,
        COALESCE(SUM(views_count), 0) as views_count
        FROM jobs WHERE employer_id = ? AND status != 'draft'");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    if ($result) {
        $jobStats = $result;
    }
} catch (PDOException $e) {
    // Table might not exist yet, use defaults
    error_log("Jobs table not found: " . $e->getMessage());
    
    // Add error parameter to URL if not already present
    if (!isset($_GET['db_error']) && strpos($e->getMessage(), "doesn't exist") !== false) {
        $currentUrl = $_SERVER['REQUEST_URI'];
        $separator = strpos($currentUrl, '?') !== false ? '&' : '?';
        header("Location: {$currentUrl}{$separator}db_error=1");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employer Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/main.css">
</head>
<body>
    <header class="site-header">
        <div class="container">
            <nav class="site-nav">
                <a href="/findajob" class="site-logo">
                    <img src="/findajob/assets/images/logo_full.png" alt="FindAJob Nigeria" class="site-logo-img">
                </a>
                <div>
                    <span>Welcome, <?php echo htmlspecialchars($user['company_name'] ?? $user['first_name']); ?>!</span>
                    <?php if ($_SERVER['SERVER_NAME'] === 'localhost'): ?>
                        <a href="/findajob/temp_mail.php" target="_blank" class="btn btn-secondary" style="margin-right: 1rem;">📧 Dev Emails</a>
                    <?php endif; ?>
                    <a href="../auth/logout.php" class="btn btn-secondary">Logout</a>
                </div>
            </nav>
        </div>
    </header>

    <main class="container">
        <div style="padding: 2rem 0;">
            <h1>Employer Dashboard</h1>
            
            <?php if (!$user['email_verified']): ?>
                <div class="alert alert-info">
                    <strong>Please verify your email address.</strong>
                    Your account is not fully activated until you verify your email.
                    <button onclick="resendVerification('<?php echo $user['email']; ?>')" class="btn btn-secondary mt-2">
                        Resend Verification Email
                    </button>
                </div>
            <?php endif; ?>
            
            <?php if ($jobStats['job_count'] === 0 && isset($_GET['db_error'])): ?>
                <div class="alert alert-error">
                    <strong>Database Update Required</strong><br>
                    Some tables are missing from your database. Please run the database update to enable all features.
                    <br><br>
                    <a href="/findajob/database/update.php" class="btn btn-primary mt-2" target="_blank">
                        🔧 Update Database
                    </a>
                </div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-top: 2rem;">
                <div class="auth-card">
                    <h3>Company Profile</h3>
                    <p><strong>Company:</strong> <?php echo htmlspecialchars($user['company_name'] ?? 'Not set'); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><strong>Status:</strong> 
                        <?php if ($user['email_verified']): ?>
                            <span style="color: var(--success);">✓ Verified</span>
                        <?php else: ?>
                            <span style="color: var(--warning);">⚠ Unverified</span>
                        <?php endif; ?>
                    </p>
                    <p><strong>Subscription:</strong> <?php echo ucfirst($user['subscription_type'] ?? 'free'); ?></p>
                </div>

                <div class="auth-card">
                    <h3>Job Statistics</h3>
                    <p><strong>Active Jobs:</strong> <?php echo $jobStats['job_count'] ?? 0; ?></p>
                    <p><strong>Applications:</strong> <?php echo $jobStats['applications_count'] ?? 0; ?></p>
                    <p><strong>Views:</strong> <?php echo $jobStats['views_count'] ?? 0; ?></p>
                </div>

                <div class="auth-card">
                    <h3>Quick Actions</h3>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <a href="post-job.php" class="btn btn-primary">Post New Job</a>
                        <a href="dashboard.php" class="btn btn-outline">View Posted Jobs</a>
                        <a href="applicants.php" class="btn btn-outline">Manage Applicants</a>
                    </div>
                </div>

                <div class="auth-card">
                    <h3>Recent Activity</h3>
                    <p>No recent activity yet. Start posting jobs to attract candidates!</p>
                </div>
            </div>
        </div>
    </main>

    <script src="../../assets/js/auth.js"></script>
    <script>
        async function resendVerification(email) {
            try {
                const formData = new FormData();
                formData.append('action', 'resend_verification');
                formData.append('email', email);

                const response = await fetch('/findajob/api/auth.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                alert(result.message);
            } catch (error) {
                alert('Failed to resend verification email.');
            }
        }
    </script>
</body>
</html>