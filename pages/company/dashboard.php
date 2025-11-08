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
$recentJobs = [];

try {
    $stmt = $pdo->prepare("SELECT 
        COUNT(*) as job_count,
        COALESCE(SUM(applications_count), 0) as applications_count,
        COALESCE(SUM(views_count), 0) as views_count
        FROM jobs WHERE employer_id = ? AND STATUS != 'deleted'");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    if ($result) {
        $jobStats = $result;
    }
    
    // Get recent jobs
    $stmt = $pdo->prepare("
        SELECT j.*, 
               j.STATUS as status,
               COALESCE(app_count.count, 0) as application_count,
               j.state as state_name, 
               j.city as lga_name
        FROM jobs j 
        LEFT JOIN (
            SELECT job_id, COUNT(*) as count 
            FROM job_applications 
            GROUP BY job_id
        ) app_count ON j.id = app_count.job_id
        WHERE j.employer_id = ? AND j.STATUS != 'deleted'
        ORDER BY j.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $recentJobs = $stmt->fetchAll();
    
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="has-bottom-nav">
    <header class="site-header">
        <div class="container">
            <nav class="site-nav">
                <a href="/findajob" class="site-logo">
                    <img src="/findajob/assets/images/logo_full.png" alt="FindAJob Nigeria" class="site-logo-img">
                </a>
                <div class="nav-links" style="display: flex; align-items: center; gap: 1.5rem;">
                    <a href="dashboard.php" class="nav-link" style="text-decoration: none; color: var(--primary); font-weight: 600;">Dashboard</a>
                    <a href="post-job.php" class="nav-link" style="text-decoration: none; color: var(--text-primary); font-weight: 500;">Post Job</a>
                    <a href="active-jobs.php" class="nav-link" style="text-decoration: none; color: var(--text-primary); font-weight: 500;">Active Jobs</a>
                    <a href="all-applications.php" class="nav-link" style="text-decoration: none; color: var(--text-primary); font-weight: 500;">Applications</a>
                    <a href="applicants.php" class="nav-link" style="text-decoration: none; color: var(--text-primary); font-weight: 500;">Applicants</a>
                    <a href="analytics.php" class="nav-link" style="text-decoration: none; color: var(--text-primary); font-weight: 500;">Analytics</a>
                    <a href="profile.php" class="nav-link" style="text-decoration: none; color: var(--text-primary); font-weight: 500;">Profile</a>
                    <span style="margin-left: 1rem;">Welcome, <?php echo htmlspecialchars($user['provider_first_name'] ?? $user['first_name']); ?>!</span>
                    <?php if ($_SERVER['SERVER_NAME'] === 'localhost'): ?>
                        <a href="/findajob/temp_mail.php" target="_blank" class="btn btn-secondary" style="margin-left: 1rem;">📧 Dev Emails</a>
                    <?php endif; ?>
                    <a href="../auth/logout.php" class="btn btn-secondary">Logout</a>
                </div>
            </nav>
        </div>
    </header>

    <main class="container">
        <div style="padding: 2rem 0;">
            <!-- Dashboard Header -->
            <div class="dashboard-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <div>
                    <h1 style="margin: 0; font-size: 2.5rem; font-weight: 700; color: var(--text-primary);">
                        <?php echo htmlspecialchars($user['company_name'] ?? 'Employer'); ?> Dashboard
                    </h1>
                    <p style="margin: 0.5rem 0 0 0; color: var(--text-secondary); font-size: 1.1rem;">
                        Welcome back, <?php echo htmlspecialchars($user['provider_first_name'] ?? $user['first_name']); ?>! 
                        Manage your jobs and find the perfect candidates.
                    </p>
                </div>
                <div class="dashboard-actions">
                    <a href="post-job.php" class="btn btn-primary" style="font-size: 1.1rem; padding: 0.75rem 1.5rem;">
                        <i class="fas fa-plus"></i> Post New Job
                    </a>
                </div>
            </div>
            
            <!-- Alert Messages -->
            <?php if (!$user['email_verified']): ?>
                <div class="alert alert-warning" style="margin-bottom: 2rem;">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 1.5rem; color: var(--warning);"></i>
                        <div style="flex: 1;">
                            <strong>Email Verification Required</strong><br>
                            Please verify your email address to access all employer features and build trust with candidates.
                        </div>
                        <button onclick="resendVerification('<?php echo $user['email']; ?>')" class="btn btn-outline btn-sm">
                            Resend Verification
                        </button>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!$user['provider_nin_verified']): ?>
                <div class="alert alert-info" style="margin-bottom: 2rem; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none;">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <i class="fas fa-id-card" style="font-size: 1.5rem;"></i>
                        <div style="flex: 1;">
                            <strong style="font-size: 1.1rem;">Verify Your Identity (Company Representative)</strong><br>
                            <span style="opacity: 0.95;">Complete NIN verification to build trust and unlock premium features. Verification fee: ₦1,000</span>
                        </div>
                        <a href="nin-verification.php" class="btn btn-sm" style="background: white; color: #2563eb; font-weight: 600; padding: 0.625rem 1.25rem; border-radius: 8px; text-decoration: none; white-space: nowrap;">
                            <i class="fas fa-shield-alt"></i> Verify Now
                        </a>
                    </div>
                </div>
            <?php elseif ($user['provider_nin_verified']): ?>
                <div class="alert alert-success" style="margin-bottom: 2rem; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none;">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <i class="fas fa-check-circle" style="font-size: 1.5rem;"></i>
                        <div style="flex: 1;">
                            <strong style="font-size: 1.1rem;">Identity Verified</strong><br>
                            <span style="opacity: 0.95;">Your NIN verification is complete. Job seekers can trust your company profile.</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem; background: rgba(255,255,255,0.2); padding: 0.5rem 1rem; border-radius: 8px;">
                            <i class="fas fa-shield-check" style="font-size: 1.2rem;"></i>
                            <span style="font-weight: 600;">Verified</span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($jobStats['job_count'] === 0 && isset($_GET['db_error'])): ?>
                <div class="alert alert-error" style="margin-bottom: 2rem;">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <i class="fas fa-exclamation-circle" style="font-size: 1.5rem; color: var(--error);"></i>
                        <div style="flex: 1;">
                            <strong>Database Update Required</strong><br>
                            Some database tables are missing. Please run the update to enable all features.
                        </div>
                        <a href="/findajob/database/update.php" class="btn btn-primary btn-sm" target="_blank">
                            🔧 Update Database
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Dashboard Stats Grid -->
            <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem; margin-bottom: 3rem;">
                <a href="active-jobs.php" style="text-decoration: none; color: inherit;">
                <div class="stat-card" style="
                    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
                    color: white; 
                    padding: 2.5rem 2rem; 
                    border-radius: 16px; 
                    text-align: left;
                    box-shadow: 0 10px 25px rgba(220, 38, 38, 0.15);
                    transition: all 0.3s ease;
                    position: relative;
                    overflow: hidden;
                    cursor: pointer;
                " onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 15px 35px rgba(220, 38, 38, 0.25)';" 
                   onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 10px 25px rgba(220, 38, 38, 0.15)';">
                
                    <div style="position: absolute; top: -20px; right: -20px; opacity: 0.1; font-size: 6rem;">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <div style="position: relative; z-index: 2;">
                        <div style="display: flex; align-items: center; margin-bottom: 1rem;">
                            <div style="
                                width: 48px; height: 48px; 
                                background: rgba(255, 255, 255, 0.2); 
                                border-radius: 12px; 
                                display: flex; 
                                align-items: center; 
                                justify-content: center;
                                margin-right: 1rem;
                            ">
                                <i class="fas fa-briefcase" style="font-size: 1.5rem;"></i>
                            </div>
                            <div>
                                <div style="font-size: 0.9rem; opacity: 0.9; font-weight: 500;">Active Jobs</div>
                                <div style="font-size: 2.5rem; font-weight: 700; line-height: 1;">
                                    <?php echo $jobStats['job_count'] ?? 0; ?>
                                </div>
                            </div>
                        </div>
                        <div style="font-size: 0.85rem; opacity: 0.8; line-height: 1.4;">
                            <?php if (($jobStats['job_count'] ?? 0) > 0): ?>
                                <i class="fas fa-arrow-up" style="margin-right: 0.5rem;"></i>Great! Keep posting to attract more candidates.
                            <?php else: ?>
                                <i class="fas fa-plus" style="margin-right: 0.5rem;"></i>Start by posting your first job!
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                </a>

                <a href="all-applications.php" style="text-decoration: none; color: inherit;">
                <div class="stat-card" style="
                    background: linear-gradient(135deg, #059669 0%, #047857 100%);
                    color: white; 
                    padding: 2.5rem 2rem; 
                    border-radius: 16px; 
                    text-align: left;
                    box-shadow: 0 10px 25px rgba(5, 150, 105, 0.15);
                    transition: all 0.3s ease;
                    position: relative;
                    overflow: hidden;
                    cursor: pointer;
                " onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 15px 35px rgba(5, 150, 105, 0.25)';" 
                   onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 10px 25px rgba(5, 150, 105, 0.15)';">
                
                    <div style="position: absolute; top: -20px; right: -20px; opacity: 0.1; font-size: 6rem;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div style="position: relative; z-index: 2;">
                        <div style="display: flex; align-items: center; margin-bottom: 1rem;">
                            <div style="
                                width: 48px; height: 48px; 
                                background: rgba(255, 255, 255, 0.2); 
                                border-radius: 12px; 
                                display: flex; 
                                align-items: center; 
                                justify-content: center;
                                margin-right: 1rem;
                            ">
                                <i class="fas fa-users" style="font-size: 1.3rem;"></i>
                            </div>
                            <div>
                                <div style="font-size: 0.9rem; opacity: 0.9; font-weight: 500;">Applications</div>
                                <div style="font-size: 2.5rem; font-weight: 700; line-height: 1;">
                                    <?php echo $jobStats['applications_count'] ?? 0; ?>
                                </div>
                            </div>
                        </div>
                        <div style="font-size: 0.85rem; opacity: 0.8; line-height: 1.4;">
                            <i class="fas fa-envelope" style="margin-right: 0.5rem;"></i>Applications received across all jobs
                        </div>
                    </div>
                </div>
                </a>

                <a href="analytics.php" style="text-decoration: none; color: inherit;">
                <div class="stat-card" style="
                    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
                    color: white; 
                    padding: 2.5rem 2rem; 
                    border-radius: 16px; 
                    text-align: left;
                    box-shadow: 0 10px 25px rgba(99, 102, 241, 0.15);
                    transition: all 0.3s ease;
                    position: relative;
                    overflow: hidden;
                    cursor: pointer;
                " onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 15px 35px rgba(99, 102, 241, 0.25)';" 
                   onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 10px 25px rgba(99, 102, 241, 0.15)';">
                
                    <div style="position: absolute; top: -20px; right: -20px; opacity: 0.1; font-size: 6rem;">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div style="position: relative; z-index: 2;">
                        <div style="display: flex; align-items: center; margin-bottom: 1rem;">
                            <div style="
                                width: 48px; height: 48px; 
                                background: rgba(255, 255, 255, 0.2); 
                                border-radius: 12px; 
                                display: flex; 
                                align-items: center; 
                                justify-content: center;
                                margin-right: 1rem;
                            ">
                                <i class="fas fa-eye" style="font-size: 1.5rem;"></i>
                            </div>
                            <div>
                                <div style="font-size: 0.9rem; opacity: 0.9; font-weight: 500;">Total Views</div>
                                <div style="font-size: 2.5rem; font-weight: 700; line-height: 1;">
                                    <?php echo $jobStats['views_count'] ?? 0; ?>
                                </div>
                            </div>
                        </div>
                        <div style="font-size: 0.85rem; opacity: 0.8; line-height: 1.4;">
                            <i class="fas fa-chart-line" style="margin-right: 0.5rem;"></i>Views across all your job postings
                        </div>
                    </div>
                </div>
                </a>

                <a href="profile.php#subscription" style="text-decoration: none; color: inherit;">
                <div class="stat-card" style="
                    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
                    color: white; 
                    padding: 2.5rem 2rem; 
                    border-radius: 16px; 
                    text-align: left;
                    box-shadow: 0 10px 25px rgba(245, 158, 11, 0.15);
                    transition: all 0.3s ease;
                    position: relative;
                    overflow: hidden;
                    cursor: pointer;
                " onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 15px 35px rgba(245, 158, 11, 0.25)';" 
                   onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 10px 25px rgba(245, 158, 11, 0.15)';">
                
                    <div style="position: absolute; top: -20px; right: -20px; opacity: 0.1; font-size: 6rem;">
                        <i class="fas fa-crown"></i>
                    </div>
                    <div style="position: relative; z-index: 2;">
                        <div style="display: flex; align-items: center; margin-bottom: 1rem;">
                            <div style="
                                width: 48px; height: 48px; 
                                background: rgba(255, 255, 255, 0.2); 
                                border-radius: 12px; 
                                display: flex; 
                                align-items: center; 
                                justify-content: center;
                                margin-right: 1rem;
                            ">
                                <i class="fas fa-crown" style="font-size: 1.3rem;"></i>
                            </div>
                            <div>
                                <div style="font-size: 0.9rem; opacity: 0.9; font-weight: 500;">Subscription</div>
                                <div style="font-size: 1.8rem; font-weight: 700; line-height: 1;">
                                    <?php echo ucfirst($user['subscription_type'] ?? 'Free'); ?>
                                </div>
                            </div>
                        </div>
                        <div style="font-size: 0.85rem; opacity: 0.8; line-height: 1.4;">
                            <?php if (($user['subscription_type'] ?? 'free') === 'free'): ?>
                                <i class="fas fa-arrow-up" style="margin-right: 0.5rem;"></i>
                                Upgrade to unlock premium features
                            <?php else: ?>
                                <i class="fas fa-check" style="margin-right: 0.5rem;"></i>Premium features active
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                </a>
            </div>

            <!-- Dashboard Content Grid -->
            <div class="dashboard-grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
                <!-- Main Content -->
                <div class="main-content">
                    <!-- Recent Jobs Section -->
                    <div class="dashboard-card" style="
                        background: var(--surface); 
                        border-radius: 16px; 
                        padding: 2.5rem; 
                        box-shadow: 0 8px 25px rgba(0,0,0,0.08); 
                        margin-bottom: 2rem;
                        border: 1px solid rgba(0,0,0,0.05);
                    ">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                            <div>
                                <h3 style="margin: 0; font-size: 1.6rem; font-weight: 700; color: var(--text-primary); display: flex; align-items: center;">
                                    <i class="fas fa-briefcase" style="margin-right: 0.75rem; color: var(--primary);"></i>
                                    Recent Jobs
                                </h3>
                                <p style="margin: 0.5rem 0 0 2.5rem; color: var(--text-secondary); font-size: 0.95rem;">
                                    Your latest job postings and their performance
                                </p>
                            </div>
                            <a href="manage-jobs.php" class="btn btn-outline" style="
                                padding: 0.75rem 1.5rem;
                                border-radius: 10px;
                                font-weight: 600;
                                transition: all 0.3s ease;
                                border: 2px solid var(--primary);
                            ">
                                <i class="fas fa-list"></i> View All Jobs
                            </a>
                        </div>
                        
                        <?php if (empty($recentJobs)): ?>
                            <div style="text-align: center; padding: 3rem; color: var(--text-secondary);">
                                <i class="fas fa-briefcase" style="font-size: 3rem; margin-bottom: 1rem; color: var(--text-secondary);"></i>
                                <h4>No jobs posted yet</h4>
                                <p>Start by posting your first job to attract qualified candidates.</p>
                                <a href="post-job.php" class="btn btn-primary">Post Your First Job</a>
                            </div>
                        <?php else: ?>
                            <!-- Recent Job Listings -->
                            <div class="recent-jobs">
                                <?php foreach ($recentJobs as $job): ?>
                                    <div style="padding: 1.5rem; border: 1px solid var(--border-color); border-radius: 8px; margin-bottom: 1rem; transition: all 0.2s ease;">
                                        <div style="display: flex; justify-content: space-between; align-items: start;">
                                            <div style="flex: 1;">
                                                <h4 style="margin: 0 0 0.5rem 0; font-size: 1.2rem; font-weight: 600;">
                                                    <a href="../jobs/details.php?id=<?php echo $job['id']; ?>" style="color: var(--text-primary); text-decoration: none;">
                                                        <?php echo htmlspecialchars($job['title']); ?>
                                                    </a>
                                                </h4>
                                                <div style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0.5rem;">
                                                    <i class="fas fa-map-marker-alt"></i> 
                                                    <?php echo htmlspecialchars(($job['lga_name'] ?? '') . ', ' . ($job['state_name'] ?? '')); ?>
                                                    <?php if ($job['job_type']): ?>
                                                        • <i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($job['job_type']); ?>
                                                    <?php endif; ?>
                                                </div>
                                                <div style="color: var(--text-secondary); font-size: 0.85rem;">
                                                    Posted <?php echo date('M j, Y', strtotime($job['created_at'])); ?>
                                                </div>
                                            </div>
                                            <div style="text-align: right; margin-left: 1rem;">
                                                <?php
                                                $statusColors = [
                                                    'active' => 'var(--accent)',
                                                    'inactive' => 'var(--warning)',
                                                    'draft' => 'var(--text-secondary)'
                                                ];
                                                $statusColor = $statusColors[$job['status']] ?? 'var(--text-secondary)';
                                                ?>
                                                <div style="margin-bottom: 0.5rem;">
                                                    <span style="background: <?php echo $statusColor; ?>; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 500;">
                                                        <?php echo ucfirst($job['status']); ?>
                                                    </span>
                                                </div>
                                                <div style="font-size: 0.9rem; color: var(--text-secondary);">
                                                    <strong style="color: var(--primary);"><?php echo $job['application_count']; ?></strong> applications
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Analytics Preview -->
                    <div class="dashboard-card" style="
                        background: var(--surface); 
                        border-radius: 16px; 
                        padding: 2.5rem; 
                        box-shadow: 0 8px 25px rgba(0,0,0,0.08);
                        border: 1px solid rgba(0,0,0,0.05);
                        position: relative;
                        overflow: hidden;
                    ">
                        <!-- Background Pattern -->
                        <div style="
                            position: absolute; 
                            top: -50px; 
                            right: -50px; 
                            width: 200px; 
                            height: 200px; 
                            background: radial-gradient(circle, rgba(220, 38, 38, 0.05) 0%, transparent 70%);
                        "></div>
                        
                        <div style="position: relative; z-index: 2;">
                            <h3 style="margin: 0 0 2rem 0; font-size: 1.6rem; font-weight: 700; color: var(--text-primary); display: flex; align-items: center;">
                                <i class="fas fa-chart-line" style="margin-right: 0.75rem; color: var(--primary);"></i>
                                Performance Analytics
                            </h3>
                            
                            <!-- Preview Charts Grid -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                                <div style="
                                    background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(99, 102, 241, 0.05) 100%); 
                                    padding: 1.5rem; 
                                    border-radius: 12px; 
                                    text-align: center;
                                    border: 1px solid rgba(99, 102, 241, 0.1);
                                ">
                                    <div style="font-size: 2rem; font-weight: 700; color: var(--primary); margin-bottom: 0.5rem;">
                                        <?php echo $jobStats['views_count'] ?? '0'; ?>
                                    </div>
                                    <div style="font-size: 0.85rem; color: var(--text-secondary);">Total Views</div>
                                </div>
                                <div style="
                                    background: linear-gradient(135deg, rgba(5, 150, 105, 0.1) 0%, rgba(5, 150, 105, 0.05) 100%); 
                                    padding: 1.5rem; 
                                    border-radius: 12px; 
                                    text-align: center;
                                    border: 1px solid rgba(5, 150, 105, 0.1);
                                ">
                                    <div style="font-size: 2rem; font-weight: 700; color: var(--accent); margin-bottom: 0.5rem;">
                                        <?php echo $jobStats['applications_count'] ?? '0'; ?>
                                    </div>
                                    <div style="font-size: 0.85rem; color: var(--text-secondary);">Applications</div>
                                </div>
                            </div>
                            
                            <!-- Coming Soon Message -->
                            <div style="
                                text-align: center; 
                                padding: 2rem; 
                                background: linear-gradient(135deg, rgba(220, 38, 38, 0.05) 0%, rgba(220, 38, 38, 0.02) 100%);
                                border-radius: 12px;
                                border: 2px dashed rgba(220, 38, 38, 0.1);
                            ">
                                <i class="fas fa-chart-bar" style="font-size: 2.5rem; margin-bottom: 1rem; color: var(--primary); opacity: 0.7;"></i>
                                <h4 style="margin: 0 0 0.5rem; color: var(--text-primary); font-weight: 600;">Advanced Analytics Coming Soon!</h4>
                                <p style="margin: 0; color: var(--text-secondary); font-size: 0.95rem; line-height: 1.5;">
                                    Track application rates, candidate quality, hiring funnels, and performance metrics across all your job postings.
                                </p>
                                <div style="margin-top: 1.5rem; display: flex; justify-content: center; gap: 2rem; font-size: 0.8rem; color: var(--text-muted);">
                                    <span><i class="fas fa-check" style="color: var(--accent); margin-right: 0.25rem;"></i>Application Tracking</span>
                                    <span><i class="fas fa-check" style="color: var(--accent); margin-right: 0.25rem;"></i>Performance Metrics</span>
                                    <span><i class="fas fa-check" style="color: var(--accent); margin-right: 0.25rem;"></i>Hiring Analytics</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="sidebar">
                    <!-- Profile Card -->
                    <div class="dashboard-card" style="
                        background: var(--surface); 
                        border-radius: 16px; 
                        padding: 2.5rem; 
                        box-shadow: 0 8px 25px rgba(0,0,0,0.08); 
                        margin-bottom: 2rem;
                        border: 1px solid rgba(0,0,0,0.05);
                        position: relative;
                        overflow: hidden;
                    ">
                        <!-- Background Pattern -->
                        <div style="
                            position: absolute; 
                            top: 0; 
                            right: 0; 
                            width: 100px; 
                            height: 100px; 
                            background: linear-gradient(45deg, var(--primary), transparent); 
                            opacity: 0.05;
                            border-radius: 0 0 0 100px;
                        "></div>
                        
                        <div style="position: relative; z-index: 2;">
                            <h3 style="margin: 0 0 2rem 0; font-size: 1.4rem; font-weight: 700; color: var(--text-primary); display: flex; align-items: center;">
                                <i class="fas fa-building" style="margin-right: 0.75rem; color: var(--primary);"></i>
                                Company Profile
                            </h3>
                            
                            <div style="text-align: center; margin-bottom: 2rem;">
                                <div style="
                                    width: 90px; 
                                    height: 90px; 
                                    border-radius: 20px; 
                                    background: linear-gradient(135deg, var(--primary), var(--primary-dark)); 
                                    color: white; 
                                    display: flex; 
                                    align-items: center; 
                                    justify-content: center; 
                                    margin: 0 auto 1.5rem; 
                                    font-size: 2.2rem; 
                                    font-weight: bold;
                                    box-shadow: 0 8px 20px rgba(220, 38, 38, 0.3);
                                    position: relative;
                                ">
                                    <?php echo strtoupper(substr($user['company_name'] ?? $user['first_name'] ?? 'C', 0, 1)); ?>
                                    <?php if ($user['email_verified']): ?>
                                        <div style="
                                            position: absolute; 
                                            bottom: -3px; 
                                            right: -3px; 
                                            width: 24px; 
                                            height: 24px; 
                                            background: var(--accent); 
                                            border-radius: 50%; 
                                            display: flex; 
                                            align-items: center; 
                                            justify-content: center;
                                            border: 3px solid white;
                                        ">
                                            <i class="fas fa-check" style="font-size: 0.7rem; color: white;"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <h4 style="margin: 0; color: var(--text-primary); font-size: 1.2rem; font-weight: 600;">
                                    <?php echo htmlspecialchars($user['company_name'] ?? 'Company Name'); ?>
                                </h4>
                                <p style="margin: 0.5rem 0 0; color: var(--text-secondary); font-size: 0.9rem;">
                                    <?php echo htmlspecialchars($user['email']); ?>
                                </p>
                            </div>
                        </div>
                        
                        <div style="border-top: 1px solid var(--border-color); padding-top: 1.5rem;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                                <span style="color: var(--text-secondary);">Status:</span>
                                <?php if ($user['email_verified']): ?>
                                    <span style="color: var(--accent); font-weight: 500;"><i class="fas fa-check-circle"></i> Verified</span>
                                <?php else: ?>
                                    <span style="color: var(--warning); font-weight: 500;"><i class="fas fa-exclamation-circle"></i> Unverified</span>
                                <?php endif; ?>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                                <span style="color: var(--text-secondary);">Plan:</span>
                                <span style="color: var(--text-primary); font-weight: 500;"><?php echo ucfirst($user['subscription_type'] ?? 'Free'); ?></span>
                            </div>
                        </div>
                        
                        <a href="profile.php" class="btn btn-outline btn-sm" style="width: 100%; margin-top: 1rem;">
                            <i class="fas fa-edit"></i> Edit Profile
                        </a>
                    </div>

                    <!-- Quick Actions -->
                    <div class="dashboard-card" style="
                        background: var(--surface); 
                        border-radius: 16px; 
                        padding: 2.5rem; 
                        box-shadow: 0 8px 25px rgba(0,0,0,0.08); 
                        margin-bottom: 2rem;
                        border: 1px solid rgba(0,0,0,0.05);
                    ">
                        <h3 style="margin: 0 0 2rem 0; font-size: 1.4rem; font-weight: 700; color: var(--text-primary); display: flex; align-items: center;">
                            <i class="fas fa-bolt" style="margin-right: 0.75rem; color: var(--primary);"></i>
                            Quick Actions
                        </h3>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <a href="post-job.php" class="btn btn-primary" style="
                                text-decoration: none; 
                                padding: 1rem 1.5rem; 
                                border-radius: 12px; 
                                font-weight: 600; 
                                box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
                                transition: all 0.3s ease;
                                display: flex; 
                                align-items: center;
                                justify-content: center;
                                gap: 0.75rem;
                            ">
                                <i class="fas fa-plus"></i> Post New Job
                            </a>
                            <a href="manage-jobs.php" class="action-btn" style="
                                text-decoration: none; 
                                padding: 0.875rem 1.5rem; 
                                border-radius: 12px; 
                                font-weight: 500; 
                                border: 2px solid var(--border-color);
                                color: var(--text-primary);
                                background: var(--background);
                                transition: all 0.3s ease;
                                display: flex; 
                                align-items: center;
                                gap: 0.75rem;
                            ">
                                <i class="fas fa-list" style="color: var(--primary);"></i> Manage Jobs
                            </a>
                            <a href="applicants.php" class="action-btn" style="
                                text-decoration: none; 
                                padding: 0.875rem 1.5rem; 
                                border-radius: 12px; 
                                font-weight: 500; 
                                border: 2px solid var(--border-color);
                                color: var(--text-primary);
                                background: var(--background);
                                transition: all 0.3s ease;
                                display: flex; 
                                align-items: center;
                                gap: 0.75rem;
                            ">
                                <i class="fas fa-users" style="color: var(--primary);"></i> View Applicants
                            </a>
                            <a href="profile.php" style="
                                display: flex; 
                                align-items: center;
                                gap: 0.75rem;
                            ">
                                <i class="fas fa-building" style="color: var(--primary);"></i> Company Profile
                            </a>
                            <a href="search-cvs.php" style="
                                display: flex; 
                                align-items: center;
                                gap: 0.75rem;
                            ">
                                <i class="fas fa-search" style="color: var(--primary);"></i> Search CVs
                            </a>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="dashboard-card" style="
                        background: var(--surface); 
                        border-radius: 16px; 
                        padding: 2.5rem; 
                        box-shadow: 0 8px 25px rgba(0,0,0,0.08);
                        border: 1px solid rgba(0,0,0,0.05);
                    ">
                        <h3 style="margin: 0 0 2rem 0; font-size: 1.4rem; font-weight: 700; color: var(--text-primary); display: flex; align-items: center;">
                            <i class="fas fa-history" style="margin-right: 0.75rem; color: var(--primary);"></i>
                            Recent Activity
                        </h3>
                        
                        <!-- Sample Activities for Demo -->
                        <div style="display: flex; flex-direction: column; gap: 1.25rem; margin-bottom: 2rem;">
                            <div class="activity-item" style="
                                padding: 1.25rem; 
                                background: linear-gradient(135deg, rgba(220, 38, 38, 0.05) 0%, rgba(220, 38, 38, 0.02) 100%); 
                                border-radius: 12px; 
                                border-left: 4px solid var(--primary);
                                position: relative;
                                overflow: hidden;
                            ">
                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                    <div style="flex: 1;">
                                        <div style="font-weight: 600; margin-bottom: 0.5rem; color: var(--text-primary); display: flex; align-items: center; font-size: 0.95rem;">
                                            <i class="fas fa-plus-circle" style="margin-right: 0.5rem; color: var(--primary); font-size: 0.875rem;"></i>
                                            Job Posted
                                        </div>
                                        <div style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Senior PHP Developer position created</div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);">Start posting jobs to see real activity</div>
                                    </div>
                                    <div style="background: rgba(220, 38, 38, 0.1); color: var(--primary); padding: 0.25rem 0.5rem; border-radius: 6px; font-size: 0.7rem; font-weight: 600;">
                                        Demo
                                    </div>
                                </div>
                            </div>
                            
                            <div class="activity-item" style="
                                padding: 1.25rem; 
                                background: linear-gradient(135deg, rgba(5, 150, 105, 0.05) 0%, rgba(5, 150, 105, 0.02) 100%); 
                                border-radius: 12px; 
                                border-left: 4px solid var(--accent);
                            ">
                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                    <div style="flex: 1;">
                                        <div style="font-weight: 600; margin-bottom: 0.5rem; color: var(--text-primary); display: flex; align-items: center; font-size: 0.95rem;">
                                            <i class="fas fa-eye" style="margin-right: 0.5rem; color: var(--accent); font-size: 0.875rem;"></i>
                                            Job Views
                                        </div>
                                        <div style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Your jobs were viewed 25+ times</div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);">Track engagement when you post jobs</div>
                                    </div>
                                    <div style="background: rgba(5, 150, 105, 0.1); color: var(--accent); padding: 0.25rem 0.5rem; border-radius: 6px; font-size: 0.7rem; font-weight: 600;">
                                        Demo
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div style="text-align: center; padding: 1.5rem; background: rgba(0,0,0,0.02); border-radius: 12px; border: 2px dashed rgba(0,0,0,0.1);">
                            <i class="fas fa-rocket" style="font-size: 2rem; margin-bottom: 1rem; color: var(--primary); opacity: 0.7;"></i>
                            <p style="margin: 0 0 0.5rem; color: var(--text-primary); font-weight: 600;">Ready to Get Started?</p>
                            <p style="margin: 0; font-size: 0.9rem; color: var(--text-secondary);">Post your first job to see real activity here!</p>
                            <a href="post-job.php" class="btn btn-primary" style="
                                margin-top: 1rem; 
                                padding: 0.75rem 1.5rem; 
                                font-size: 0.9rem; 
                                text-decoration: none;
                                display: inline-flex;
                                align-items: center;
                                gap: 0.5rem;
                            ">
                                <i class="fas fa-plus"></i> Post Your First Job
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../../assets/js/auth.js"></script>
    
    <style>
        /* Dashboard Enhancement Styles */
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15) !important;
        }
        
        .dashboard-card:hover {
            box-shadow: 0 12px 35px rgba(0,0,0,0.12) !important;
        }
        
        .action-btn:hover {
            background: var(--primary) !important;
            color: white !important;
            border-color: var(--primary) !important;
            transform: translateX(5px);
        }
        
        .action-btn:hover i {
            color: white !important;
        }
        
        .job-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
        }
        
        .activity-item:hover {
            transform: translateX(5px);
        }
        
        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr !important;
            }
            
            .stats-grid {
                grid-template-columns: 1fr !important;
            }
            
            .stat-card {
                padding: 2rem 1.5rem !important;
            }
            
            .dashboard-card {
                padding: 1.5rem !important;
            }
            
            .main-content, .sidebar {
                margin-bottom: 1rem;
            }
        }
        
        @media (max-width: 480px) {
            .dashboard-card h3 {
                font-size: 1.2rem !important;
            }
            
            .stat-card .stat-value {
                font-size: 2rem !important;
            }
            
            .btn {
                padding: 0.75rem 1rem !important;
                font-size: 0.9rem !important;
            }
        }
        
        /* Loading Animation */
        .dashboard-card {
            animation: fadeInUp 0.6s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }
    </style>
    
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
        
        // Dashboard Enhancement Scripts
        document.addEventListener('DOMContentLoaded', function() {
            // Add smooth hover effects
            const cards = document.querySelectorAll('.dashboard-card, .stat-card');
            cards.forEach(card => {
                card.style.transition = 'all 0.3s ease';
            });
            
            // Add loading animation stagger
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });
        });
    </script>
    
    <!-- Bottom Navigation for PWA -->
    <nav class="app-bottom-nav">
        <a href="../../index.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">🏠</div>
            <div class="app-bottom-nav-label">Home</div>
        </a>
        <a href="post-job.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">📝</div>
            <div class="app-bottom-nav-label">Post Job</div>
        </a>
        <a href="dashboard.php" class="app-bottom-nav-item active">
            <div class="app-bottom-nav-icon">📊</div>
            <div class="app-bottom-nav-label">Dashboard</div>
        </a>
        <a href="profile.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">🏢</div>
            <div class="app-bottom-nav-label">Company</div>
        </a>
    </nav>
</body>
</html>