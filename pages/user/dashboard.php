<?php
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/constants.php';
require_once '../../includes/functions.php';



requireJobSeeker();

$userId = getCurrentUserId();

// Get user profile data with explicit column selection to avoid conflicts
$stmt = $pdo->prepare("
    SELECT 
        u.id, u.user_type, u.email, u.first_name, u.last_name, u.phone, 
        u.email_verified, u.is_active, u.created_at as user_created_at, u.updated_at as user_updated_at,
        u.subscription_status, u.subscription_plan, u.subscription_type, u.subscription_start, u.subscription_end,
        jsp.id as profile_id, jsp.user_id, jsp.date_of_birth, jsp.gender, 
        jsp.state_of_origin, jsp.lga_of_origin, jsp.current_state, jsp.current_city,
        jsp.education_level, jsp.years_of_experience, jsp.job_status,
        jsp.salary_expectation_min, jsp.salary_expectation_max, jsp.skills, jsp.bio,
        jsp.profile_picture, jsp.nin, jsp.nin_verified, jsp.nin_verified_at, 
        jsp.bvn, jsp.verification_status,
        jsp.verification_boosted, jsp.verification_boost_date, jsp.profile_boosted, jsp.profile_boost_until,
        jsp.created_at as profile_created_at, jsp.updated_at as profile_updated_at
    FROM users u 
    LEFT JOIN job_seeker_profiles jsp ON u.id = jsp.user_id 
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Check if job_seeker_profiles record exists, create if not
$profileCheckStmt = $pdo->prepare("SELECT COUNT(*) FROM job_seeker_profiles WHERE user_id = ?");
$profileCheckStmt->execute([$userId]);
$profileExists = $profileCheckStmt->fetchColumn();

if (!$profileExists) {
    try {
        $createProfileStmt = $pdo->prepare("
            INSERT IGNORE INTO job_seeker_profiles (user_id) VALUES (?)
        ");
        $createProfileStmt->execute([$userId]);
        
        // Fetch user data again with the new profile
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error creating job seeker profile: " . $e->getMessage());
    }
}



// Get real statistics
$stats = [
    'applications_count' => 0,
    'saved_jobs_count' => 0,
    'profile_views' => 0,
    'job_matches' => 0,
    'profile_completeness' => 0
];

try {
    // Get applications count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM job_applications WHERE job_seeker_id = ?");
    $stmt->execute([$userId]);
    $stats['applications_count'] = $stmt->fetchColumn();
    
    // Get saved jobs count
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM saved_jobs WHERE user_id = ?");
        $stmt->execute([$userId]);
        $stats['saved_jobs_count'] = $stmt->fetchColumn();
    } catch (PDOException $e) {
        // Table might not exist yet
        $stats['saved_jobs_count'] = 0;
    }
    
    // Get profile views (if tracking exists)
    $stmt = $pdo->prepare("SELECT profile_views FROM job_seeker_profiles WHERE user_id = ?");
    $stmt->execute([$userId]);
    $profileViews = $stmt->fetchColumn();
    $stats['profile_views'] = $profileViews ? $profileViews : rand(5, 50); // Fallback to random for demo
    
    // Get job matches (jobs matching user's skills/location)
    $userLocation = $user['current_state'] ?? null;
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM jobs j 
        WHERE j.status = 'active' 
        AND (j.location LIKE CONCAT('%', ?, '%') OR ? IS NULL)
        AND j.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $stmt->execute([$userLocation, $userLocation]);
    $stats['job_matches'] = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    // If tables don't exist, use defaults
    error_log("Dashboard stats error: " . $e->getMessage());
}

// Calculate profile completeness outside database operations (separate try-catch)
try {
    $stats['profile_completeness'] = calculateProfileCompletion($user);
} catch (Exception $e) {
    error_log("Profile completion calculation error: " . $e->getMessage());
    $stats['profile_completeness'] = 0; // Fallback to 0 if calculation fails
}

// Get recent applications with real data
$recentApplications = [];
try {
    $stmt = $pdo->prepare("
        SELECT ja.*, j.title, j.company_name, ja.applied_at, ja.application_status as status
        FROM job_applications ja 
        JOIN jobs j ON ja.job_id = j.id 
        WHERE ja.job_seeker_id = ? 
        ORDER BY ja.applied_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $recentApplications = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Recent applications error: " . $e->getMessage());
}

// Get recent saved jobs
$savedJobs = [];
try {
    $stmt = $pdo->prepare("
        SELECT j.*, sj.saved_at
        FROM saved_jobs sj 
        JOIN jobs j ON sj.job_id = j.id 
        WHERE sj.user_id = ? 
        ORDER BY sj.saved_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $savedJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug logging
    error_log("Dashboard - User ID: " . $userId);
    error_log("Dashboard - Saved jobs count: " . count($savedJobs));
    if (count($savedJobs) > 0) {
        error_log("Dashboard - First saved job: " . print_r($savedJobs[0], true));
    }
} catch (PDOException $e) {
    error_log("Saved jobs error: " . $e->getMessage());
    error_log("Saved jobs SQL error: " . $e->getTraceAsString());
}

// Get recommended jobs with real data
$recommendedJobs = [];
try {
    $userLocation = $user['current_state'] ?? null;
    $stmt = $pdo->prepare("
        SELECT j.*, c.company_name as employer_name
        FROM jobs j 
        LEFT JOIN companies c ON j.company_id = c.id
        WHERE j.status = 'active' 
        AND (j.location LIKE CONCAT('%', ?, '%') OR ? IS NULL)
        AND j.id NOT IN (SELECT job_id FROM job_applications WHERE job_seeker_id = ?)
        ORDER BY j.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$userLocation, $userLocation, $userId]);
    $recommendedJobs = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Recommended jobs error: " . $e->getMessage());
}

// Get recent activities
$recentActivities = [];
try {
    // Get recent applications as activities
    $stmt = $pdo->prepare("
        SELECT 'application' as type, j.title, j.company_name, ja.applied_at as activity_time
        FROM job_applications ja 
        JOIN jobs j ON ja.job_id = j.id 
        WHERE ja.job_seeker_id = ? 
        ORDER BY ja.applied_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$userId]);
    $applications = $stmt->fetchAll();
    
    foreach ($applications as $app) {
        $recentActivities[] = [
            'type' => 'application',
            'title' => $app['title'],
            'company' => $app['company_name'],
            'time' => $app['activity_time']
        ];
    }
    
    // Add profile update activity if profile was recently updated
    if (!empty($user['profile_updated_at']) && strtotime($user['profile_updated_at']) > strtotime('-7 days')) {
        $recentActivities[] = [
            'type' => 'profile_update',
            'title' => 'Profile Updated',
            'company' => null,
            'time' => $user['profile_updated_at']
        ];
    }
    
    // Sort by time
    usort($recentActivities, function($a, $b) {
        return strtotime($b['time']) - strtotime($a['time']);
    });
    
    $recentActivities = array_slice($recentActivities, 0, 5);
    
} catch (PDOException $e) {
    error_log("Recent activities error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .header-actions {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }
        
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        a.stat-card:hover {
            box-shadow: 0 8px 25px rgba(220, 38, 38, 0.2);
        }
        
        .dashboard-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .main-content, .sidebar {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert-info {
            background: var(--blue-light, #dbeafe);
            border: 1px solid var(--blue, #2563eb);
            color: var(--blue-dark, #1d4ed8);
        }
        
        /* Verified Badge Checkmark (like Facebook/LinkedIn) */
        .verified-checkmark {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            background: #1877f2; /* Facebook blue */
            border-radius: 50%;
            color: white;
            font-size: 12px;
            font-weight: bold;
            margin-left: 6px;
            vertical-align: middle;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            position: relative;
            top: -1px;
        }
        
        .profile-details h4 {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* AI Recommendations Styling */
        .ai-recommended {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .ai-recommended::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
        }

        .ai-recommended:hover::before {
            transform: scaleX(1);
        }

        .ai-recommended:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 28px rgba(102, 126, 234, 0.2);
        }

        .job-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
            gap: 1rem;
        }

        .job-header h4 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.4;
            flex: 1;
        }

        .job-header h4 a {
            color: inherit;
            text-decoration: none;
            transition: color 0.2s;
        }

        .job-header h4 a:hover {
            color: var(--primary);
        }

        .match-score {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            white-space: nowrap;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .match-score::before {
            content: '⭐';
            font-size: 1rem;
        }

        .urgent-badge {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            padding: 0.25rem 0.6rem;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 700;
            margin-left: 0.5rem;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        .job-company {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0.5rem 0;
            font-size: 1rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .job-location {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0.5rem 0;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .match-reasons {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border-left: 3px solid #3b82f6;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin: 0.75rem 0;
            font-size: 0.85rem;
            color: #1e40af;
        }

        .match-reasons span:first-child {
            font-weight: 700;
            color: #1e3a8a;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            margin-right: 0.25rem;
        }

        .match-reasons span:first-child::before {
            content: '✓';
            display: inline-block;
            background: #3b82f6;
            color: white;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            font-size: 0.7rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .job-details {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin: 0.75rem 0;
            font-size: 0.85rem;
        }

        .job-salary {
            color: #059669;
            font-weight: 700;
            background: #d1fae5;
            padding: 0.35rem 0.75rem;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .job-salary::before {
            content: '💰';
        }

        .job-type {
            background: #e0e7ff;
            color: #4f46e5;
            padding: 0.35rem 0.75rem;
            border-radius: 6px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .job-type::before {
            content: '💼';
        }

        .job-time {
            color: var(--text-muted);
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .job-time::before {
            content: '🕐';
        }

        .job-deadline {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            font-weight: 600;
        }

        .job-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .btn-apply {
            flex: 1;
            background: linear-gradient(135deg, var(--primary) 0%, #b91c1c 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-apply:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.4);
            background: linear-gradient(135deg, #b91c1c 0%, #991b1b 100%);
        }

        .btn-apply:active {
            transform: translateY(0);
        }

        .btn-save {
            background: white;
            border: 2px solid #e5e7eb;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1.25rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-save:hover {
            border-color: var(--primary);
            background: #fef2f2;
            transform: scale(1.1);
        }

        .loading-state {
            text-align: center;
            padding: 2rem;
        }

        .spinner {
            border: 4px solid #f3f4f6;
            border-top: 4px solid var(--primary);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1.5rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .ai-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.35rem 0.85rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .ai-badge::before {
            content: '✨';
        }

        /* Card hover effects */
        .dashboard-card {
            transition: all 0.3s ease;
        }

        .dashboard-card:hover {
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }

        /* Scrollbar styling for recommendations container */
        .jobs-list {
            max-height: 600px;
            overflow-y: auto;
            padding-right: 0.5rem;
        }

        .jobs-list::-webkit-scrollbar {
            width: 6px;
        }

        .jobs-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .jobs-list::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
        }

        .jobs-list::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, #764ba2 0%, #667eea 100%);
        }


        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .header-actions {
                justify-content: center;
            }
            
            .dashboard-content {
                grid-template-columns: 1fr;
            }
            
            .dashboard-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>

    <div class="dashboard-container">
        <!-- Header -->
        <div class="dashboard-header">
            <div>
                <h1 style="margin: 0 0 0.5rem 0; color: var(--text-primary);">🏠 Job Seeker Dashboard</h1>
                <p style="margin: 0; color: var(--text-secondary);">Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>! Track your job search progress and manage your applications</p>
            </div>
            <div class="header-actions">
                <a href="ai-recommendations.php" class="btn btn-primary" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                    🤖 AI Jobs
                </a>
                <?php if (isDevelopmentMode()): ?>
                    <a href="/findajob/temp_mail.php" target="_blank" class="btn btn-blue btn-sm">📧 Dev Emails</a>
                <?php endif; ?>
                <a href="../auth/logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </div>
        
        <?php if (!$user['email_verified']): ?>
                <div class="alert alert-info">
                    <strong>Please verify your email address.</strong>
                    Your account is not fully activated until you verify your email.
                    <button onclick="resendVerification('<?php echo $user['email']; ?>')" class="btn btn-secondary mt-2">
                        Resend Verification Email
                    </button>
                </div>
            <?php endif; ?>

        <!-- Subscription Status Banner -->
        <?php 
        $subscriptionStatus = $user['subscription_status'] ?? 'free';
        $subscriptionPlan = $user['subscription_plan'] ?? 'basic';
        $subscriptionEnd = $user['subscription_end'] ?? null;
        $isPro = $subscriptionPlan === 'pro' && $subscriptionStatus === 'active';
        $isExpiringSoon = false;
        $daysUntilExpiry = 0;
        
        if ($subscriptionEnd) {
            $now = new DateTime();
            $expiryDate = new DateTime($subscriptionEnd);
            $daysUntilExpiry = $now->diff($expiryDate)->days;
            $isExpiringSoon = $daysUntilExpiry <= 7 && $expiryDate > $now;
        }
        
        $profileBoosted = $user['profile_boosted'] ?? 0;
        $profileBoostUntil = $user['profile_boost_until'] ?? null;
        $profileBoostActive = false;
        if ($profileBoostUntil) {
            $boostDate = new DateTime($profileBoostUntil);
            $profileBoostActive = $boostDate > new DateTime();
        }
        ?>
        
        <?php if (!$isPro): ?>
        <!-- Upgrade to Pro Banner -->
        <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-left: 4px solid #f59e0b; padding: 1.25rem; border-radius: 8px; margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 250px;">
                    <h3 style="margin: 0 0 0.5rem 0; font-size: 1.125rem; color: #92400e; display: flex; align-items: center; gap: 0.5rem;">
                        <span style="font-size: 1.5rem;">👑</span>
                        Upgrade to Pro Plan
                    </h3>
                    <p style="margin: 0; color: #78350f; font-size: 0.875rem;">
                        Get priority job alerts, featured profile, unlimited CV downloads, and AI-powered recommendations. Starting from ₦6,000/month.
                    </p>
                </div>
                <div>
                    <a href="../payment/plans.php" class="btn btn-primary" style="white-space: nowrap; background: #f59e0b; border-color: #f59e0b;">
                        🚀 View Plans
                    </a>
                </div>
            </div>
        </div>
        <?php elseif ($isExpiringSoon): ?>
        <!-- Expiring Soon Warning -->
        <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-left: 4px solid #dc2626; padding: 1.25rem; border-radius: 8px; margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 250px;">
                    <h3 style="margin: 0 0 0.5rem 0; font-size: 1.125rem; color: #92400e; display: flex; align-items: center; gap: 0.5rem;">
                        <span style="font-size: 1.5rem;">⚠️</span>
                        Your Pro Plan Expires in <?php echo $daysUntilExpiry; ?> Days
                    </h3>
                    <p style="margin: 0; color: #78350f; font-size: 0.875rem;">
                        Renew now to continue enjoying priority job alerts, featured profile, and unlimited CV downloads.
                    </p>
                </div>
                <div>
                    <a href="../payment/plans.php" class="btn btn-primary" style="white-space: nowrap; background: #dc2626; border-color: #dc2626;">
                        🔄 Renew Now
                    </a>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Active Pro Status -->
        <div style="background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); border-left: 4px solid #059669; padding: 1.25rem; border-radius: 8px; margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 250px;">
                    <h3 style="margin: 0 0 0.5rem 0; font-size: 1.125rem; color: #065f46; display: flex; align-items: center; gap: 0.5rem;">
                        <span style="font-size: 1.5rem;">👑</span>
                        Pro Plan Active <?php if ($profileBoostActive): ?><span style="margin-left: 0.5rem;">🚀 Profile Boosted</span><?php endif; ?>
                    </h3>
                    <p style="margin: 0; color: #047857; font-size: 0.875rem;">
                        Your subscription <?php echo $subscriptionEnd ? 'expires on ' . date('M d, Y', strtotime($subscriptionEnd)) : 'is active'; ?>.
                        <?php if ($profileBoostActive): ?>
                        Profile boost active until <?php echo date('M d, Y', strtotime($profileBoostUntil)); ?>.
                        <?php endif; ?>
                    </p>
                </div>
                <?php if (!$profileBoostActive): ?>
                <div>
                    <a href="../payment/plans.php#boosters" class="btn btn-primary" style="white-space: nowrap; background: #7c3aed; border-color: #7c3aed;">
                        🚀 Boost Profile (₦500)
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Job Status Banner -->
        <?php
        $jobStatus = $user['job_status'] ?? 'looking';
        $statusConfig = [
            'looking' => [
                'icon' => '🔍',
                'title' => 'Looking for work',
                'message' => 'Your profile is active. You\'ll receive job alerts and employers can view your CV.',
                'style' => 'background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); border-left: 4px solid #059669;'
            ],
            'employed_but_looking' => [
                'icon' => '💼',
                'title' => 'Employed but still looking',
                'message' => 'Your profile is active. You\'ll receive job alerts and employers can view your CV. Your search is confidential.',
                'style' => 'background: linear-gradient(135deg, #dbeafe 0%, #93c5fd 100%); border-left: 4px solid #1e40af;'
            ],
            'not_looking' => [
                'icon' => '🚫',
                'title' => 'Not looking for work',
                'message' => 'Your profile is paused. Job notifications are disabled and your CV is hidden from employers.',
                'style' => 'background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); border-left: 4px solid #dc2626;'
            ]
        ];
        
        $currentStatus = $statusConfig[$jobStatus];
        ?>
        <div style="padding: 1.25rem; border-radius: 8px; margin-bottom: 1.5rem; <?php echo $currentStatus['style']; ?>">
            <div style="display: flex; align-items: start; justify-content: space-between; gap: 1rem;">
                <div style="flex: 1;">
                    <h3 style="margin: 0 0 0.5rem 0; font-size: 1.125rem; color: #1f2937; display: flex; align-items: center; gap: 0.5rem;">
                        <span style="font-size: 1.5rem;"><?php echo $currentStatus['icon']; ?></span>
                        <?php echo $currentStatus['title']; ?>
                    </h3>
                    <p style="margin: 0; color: #4b5563; font-size: 0.875rem;">
                        <?php echo $currentStatus['message']; ?>
                    </p>
                </div>
                <a href="profile.php#job-status" style="padding: 0.5rem 1rem; background: white; color: #1f2937; border: 2px solid #d1d5db; border-radius: 6px; font-weight: 600; text-decoration: none; font-size: 0.875rem; white-space: nowrap; transition: all 0.2s; display: inline-block;">
                    Change Status
                </a>
            </div>
        </div>

        <!-- Dashboard Stats Cards -->
        <div class="dashboard-stats">
                <a href="applications.php" class="stat-card" style="text-decoration: none; color: inherit; cursor: pointer;">
                    <div class="stat-icon">📋</div>
                    <div class="stat-content">
                        <h3>Applications</h3>
                        <div class="stat-number"><?php echo $stats['applications_count']; ?></div>
                        <div class="stat-change <?php echo $stats['applications_count'] > 0 ? 'positive' : 'neutral'; ?>">
                            <?php echo $stats['applications_count'] > 0 ? 'Keep applying!' : 'Start applying'; ?>
                        </div>
                    </div>
                </a>
                
                <a href="saved-jobs.php" class="stat-card" style="text-decoration: none; color: inherit; cursor: pointer;">
                    <div class="stat-icon">❤️</div>
                    <div class="stat-content">
                        <h3>Saved Jobs</h3>
                        <div class="stat-number"><?php echo $stats['saved_jobs_count']; ?></div>
                        <div class="stat-change <?php echo $stats['saved_jobs_count'] > 0 ? 'positive' : 'neutral'; ?>">
                            <?php echo $stats['saved_jobs_count'] > 0 ? 'View saved' : 'Save jobs'; ?>
                        </div>
                    </div>
                </a>
                
                <div class="stat-card">
                    <div class="stat-icon">👁️</div>
                    <div class="stat-content">
                        <h3>Profile Views</h3>
                        <div class="stat-number"><?php echo $stats['profile_views']; ?></div>
                        <div class="stat-change <?php echo $stats['profile_views'] > 10 ? 'positive' : 'neutral'; ?>">
                            <?php echo $stats['profile_views'] > 10 ? 'Great visibility!' : 'Improve profile'; ?>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">💼</div>
                    <div class="stat-content">
                        <h3>Job Matches</h3>
                        <div class="stat-number"><?php echo $stats['job_matches']; ?></div>
                        <div class="stat-change <?php echo $stats['job_matches'] > 0 ? 'positive' : 'neutral'; ?>">
                            <?php echo $stats['job_matches'] > 0 ? 'New matches' : 'Update preferences'; ?>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">⭐</div>
                    <div class="stat-content">
                        <h3>Profile Score</h3>
                        <div class="stat-number"><?php echo $stats['profile_completeness']; ?>%</div>
                        <div class="stat-change <?php echo $stats['profile_completeness'] >= 80 ? 'positive' : ($stats['profile_completeness'] >= 50 ? 'neutral' : 'negative'); ?>">
                            <?php 
                            if ($stats['profile_completeness'] >= 80) echo 'Excellent!';
                            elseif ($stats['profile_completeness'] >= 50) echo 'Good progress';
                            else echo 'Complete profile';
                            ?>
                        </div>
                    </div>
                </div>
        </div>

        <!-- Main Dashboard Content -->
        <div class="dashboard-content">
                <!-- Left Column -->
                <div class="dashboard-left">
                    <!-- Profile Summary Card -->
                    <div class="dashboard-card profile-summary">
                        <div class="card-header">
                            <!-- add profile picture -->
                            <h3>Profile Summary</h3>
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <?php if ($user['nin_verified']): ?>
                                    <span class="status-badge verified" title="NIN Verified">
                                        🛡️ NIN Verified
                                    </span>
                                <?php endif; ?>
                                <?php if ($user['email_verified']): ?>
                                    <span class="status-badge verified">✓ Email Verified</span>
                                <?php else: ?>
                                    <span class="status-badge unverified">⚠ Email Unverified</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="profile-info">
                            <div class="profile-avatar">
                                <?php if (!empty($user['profile_picture'])): ?>
                                    <?php
                                        // Normalize stored profile_picture (e.g., "uploads/profile_pictures/...")
                                        $pp = $user['profile_picture'];
                                        if (strpos($pp, '/') === 0 || preg_match('#^https?://#i', $pp)) {
                                            $pp_url = $pp;
                                        } else {
                                            $pp_url = '/findajob/' . ltrim($pp, '/');
                                        }
                                    ?>
                                    <img src="<?php echo htmlspecialchars($pp_url); ?>" alt="Profile">
                                <?php else: ?>
                                    <img src="/findajob/assets/images/default-avatar.png" alt="Profile" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="avatar-placeholder">
                                        <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="profile-details">
                                <h4>
                                    <span><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
                                    <?php if ($user['nin_verified']): ?>
                                        <span class="verified-checkmark" title="NIN Verified">✓</span>
                                    <?php endif; ?>
                                </h4>
                                <p class="profile-title"><?php echo htmlspecialchars($user['job_title'] ?? 'Job Seeker'); ?></p>
                                <p class="profile-location">📍 <?php echo htmlspecialchars(($user['current_city'] ?? '') . ($user['current_state'] ? ', ' . $user['current_state'] : '') ?: 'Nigeria'); ?></p>
                                <div class="profile-tags">
                                    <?php
                                        // Normalize skills from stored string and display up to 4 tags
                                        if (!empty($user['skills'])) {
                                            $skills = array_filter(array_map('trim', explode(',', $user['skills'])), function($v){ return $v !== ''; });
                                        } else {
                                            $skills = [];
                                        }
                                        if (empty($skills)) {
                                            $skills = ['Complete Profile'];
                                        }
                                        foreach (array_slice($skills, 0, 4) as $skill):
                                    ?>
                                        <span class="tag"><?php echo htmlspecialchars($skill); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div class="profile-progress">
                            <div class="progress-header">
                                <span>Profile Completion</span>
                                <span><?php echo $stats['profile_completeness']; ?>%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $stats['profile_completeness']; ?>%"></div>
                            </div>
                            <p class="progress-tip">
                                <?php if ($stats['profile_completeness'] < 50): ?>
                                    Complete your basic information to improve visibility
                                <?php elseif ($stats['profile_completeness'] < 80): ?>
                                    Add skills and experience to reach 100%
                                <?php else: ?>
                                    Great! Your profile is well optimized
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>Quick Actions</h3>
                        </div>
                        <div class="quick-actions">
                            <a href="../jobs/browse.php" class="action-btn primary">
                                <div class="action-icon">🔍</div>
                                <div class="action-content">
                                    <div class="action-title">Browse Jobs</div>
                                    <div class="action-desc">Find your next opportunity</div>
                                </div>
                            </a>
                            
                            <a href="profile.php" class="action-btn">
                                <div class="action-icon">👤</div>
                                <div class="action-content">
                                    <div class="action-title">Update Profile</div>
                                    <div class="action-desc">Keep your info current</div>
                                </div>
                            </a>
                            
                            <a href="cv-manager.php" class="action-btn">
                                <div class="action-icon">📄</div>
                                <div class="action-content">
                                    <div class="action-title">Manage CVs</div>
                                    <div class="action-desc">Upload and organize resumes</div>
                                </div>
                            </a>
                            
                            <a href="../payment/plans.php" class="action-btn upgrade">
                                <div class="action-icon">⭐</div>
                                <div class="action-content">
                                    <div class="action-title">Upgrade to Pro</div>
                                    <div class="action-desc">Unlock premium features</div>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- AI-Powered Recommended Jobs -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3 class="ai-recommendations-header" style="display: flex; align-items: center; gap: 0.5rem; margin: 0;">
                                <span style="font-size: 1.5rem;">🤖</span>
                                <span>AI Recommendations</span>
                            </h3>
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <span class="ai-badge" style="
                                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                    color: white;
                                    padding: 0.35rem 0.85rem;
                                    border-radius: 16px;
                                    font-size: 0.75rem;
                                    font-weight: 700;
                                    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
                                    display: flex;
                                    align-items: center;
                                    gap: 0.3rem;
                                ">
                                    ✨ Powered by AI
                                </span>
                                <a href="ai-recommendations.php" class="view-all" style="
                                    color: #667eea;
                                    font-weight: 600;
                                    transition: all 0.2s ease;
                                ">View All →</a>
                            </div>
                        </div>
                        <div id="ai-recommendations-container" class="jobs-list">
                            <!-- Loading state -->
                            <div class="loading-state" style="text-align: center; padding: 3rem 1.5rem;">
                                <div class="spinner" style="
                                    border: 4px solid rgba(102, 126, 234, 0.1);
                                    border-top: 4px solid #667eea;
                                    border-radius: 50%;
                                    width: 50px;
                                    height: 50px;
                                    animation: spin 1s linear infinite;
                                    margin: 0 auto 1.5rem;
                                "></div>
                                <div style="
                                    font-size: 2rem;
                                    margin-bottom: 1rem;
                                    animation: pulse 2s ease-in-out infinite;
                                ">🧠</div>
                                <p style="
                                    color: var(--text-primary);
                                    font-weight: 600;
                                    font-size: 1.1rem;
                                    margin-bottom: 0.5rem;
                                ">AI is analyzing your profile...</p>
                                <p style="
                                    font-size: 0.9rem;
                                    color: var(--text-secondary);
                                    margin-top: 0.5rem;
                                    line-height: 1.6;
                                ">Finding the best job matches tailored just for you ✨</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="dashboard-right">
                    <!-- Recent Applications -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>Recent Applications</h3>
                            <a href="applications.php" class="view-all">View All</a>
                        </div>
                        <div class="applications-list">
                            <?php if (count($recentApplications) > 0): ?>
                                <?php foreach ($recentApplications as $application): ?>
                                    <div class="application-item">
                                        <div class="application-info">
                                            <h4><?php echo htmlspecialchars($application['title']); ?></h4>
                                            <p class="company"><?php echo htmlspecialchars($application['company_name']); ?></p>
                                            <span class="application-date">Applied <?php echo timeAgo($application['applied_at']); ?></span>
                                        </div>
                                        <div class="application-status">
                                            <span class="status-badge <?php echo strtolower($application['status']); ?>">
                                                <?php echo ucfirst($application['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="application-item" style="text-align: center; padding: 2rem;">
                                    <div class="application-info">
                                        <h4 style="color: var(--text-secondary);">No Applications Yet</h4>
                                        <p class="company" style="margin: 0.5rem 0;">Start applying to jobs that match your skills</p>
                                        <a href="../jobs/browse.php" class="btn btn-primary btn-sm" style="margin-top: 0.5rem;">Browse Jobs</a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Saved Jobs -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>❤️ Saved Jobs</h3>
                            <a href="saved-jobs.php" class="view-all">View All</a>
                        </div>
                        <div class="applications-list">
                            <?php if (count($savedJobs) > 0): ?>
                                <?php foreach ($savedJobs as $job): ?>
                                    <div class="application-item">
                                        <div class="application-info">
                                            <h4>
                                                <a href="../jobs/details.php?id=<?php echo $job['id']; ?>" style="text-decoration: none; color: inherit;">
                                                    <?php echo htmlspecialchars($job['title']); ?>
                                                </a>
                                            </h4>
                                            <p class="company"><?php echo htmlspecialchars($job['company_name']); ?></p>
                                            <span class="application-date">Saved <?php echo timeAgo($job['saved_at']); ?></span>
                                        </div>
                                        <div class="application-status">
                                            <span class="status-badge <?php echo strtolower($job['STATUS'] ?? 'active'); ?>">
                                                <?php echo ucfirst($job['STATUS'] ?? 'Active'); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="application-item" style="text-align: center; padding: 2rem;">
                                    <div class="application-info">
                                        <h4 style="color: var(--text-secondary);">No Saved Jobs Yet</h4>
                                        <p class="company" style="margin: 0.5rem 0;">Save jobs you're interested in by clicking the heart ❤️ icon</p>
                                        <a href="../jobs/browse.php" class="btn btn-primary btn-sm" style="margin-top: 0.5rem;">Browse Jobs</a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>Recent Activity</h3>
                        </div>
                        <div class="activity-list">
                            <?php if (count($recentActivities) > 0): ?>
                                <?php foreach ($recentActivities as $activity): ?>
                                    <div class="activity-item">
                                        <?php if ($activity['type'] === 'application'): ?>
                                            <div class="activity-icon applied">�</div>
                                            <div class="activity-content">
                                                <p>You applied to <strong><?php echo htmlspecialchars($activity['title']); ?></strong></p>
                                                <span class="activity-time"><?php echo timeAgo($activity['time']); ?></span>
                                            </div>
                                        <?php elseif ($activity['type'] === 'profile_update'): ?>
                                            <div class="activity-icon viewed">✏️</div>
                                            <div class="activity-content">
                                                <p><strong>Profile updated</strong> - Improved visibility to employers</p>
                                                <span class="activity-time"><?php echo timeAgo($activity['time']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="activity-item" style="text-align: center; padding: 2rem;">
                                    <div class="activity-content">
                                        <p style="color: var(--text-secondary);">No recent activity</p>
                                        <span class="activity-time">Start by applying to jobs or updating your profile</span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Dashboard Tips Section -->
        <div style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-top: 2rem;">
            <h3 style="margin: 0 0 1rem 0; color: var(--text-primary);">💡 Job Search Tips</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                <div>
                    <h4 style="color: var(--success); margin: 0 0 0.5rem 0;">📝 Optimize Your Profile</h4>
                    <p style="margin: 0; color: var(--text-secondary); font-size: 0.875rem;">
                        Complete your profile with skills, experience, and education to increase visibility 
                        to employers and improve job matching.
                    </p>
                </div>
                <div>
                    <h4 style="color: var(--orange); margin: 0 0 0.5rem 0;">🎯 Apply Strategically</h4>
                    <p style="margin: 0; color: var(--text-secondary); font-size: 0.875rem;">
                        Tailor your CV for each application and write compelling cover letters. 
                        Quality applications get better response rates.
                    </p>
                </div>
                <div>
                    <h4 style="color: var(--purple); margin: 0 0 0.5rem 0;">🔄 Stay Active</h4>
                    <p style="margin: 0; color: var(--text-secondary); font-size: 0.875rem;">
                        Log in regularly, update your profile, and respond quickly to employer messages 
                        to maintain high visibility in search results.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Navigation for Mobile -->
    <nav class="app-bottom-nav">
        <a href="../../index.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">🏠</div>
            <div class="app-bottom-nav-label">Home</div>
        </a>
        <a href="../jobs/browse.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">🔍</div>
            <div class="app-bottom-nav-label">Jobs</div>
        </a>
        <a href="saved-jobs.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">❤️</div>
            <div class="app-bottom-nav-label">Saved</div>
        </a>
        <a href="applications.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">�</div>
            <div class="app-bottom-nav-label">Applications</div>
        </a>
        <a href="dashboard.php" class="app-bottom-nav-item active">
            <div class="app-bottom-nav-icon">👤</div>
            <div class="app-bottom-nav-label">Profile</div>
        </a>
    </nav>

    <script src="../../assets/js/auth.js"></script>
    <script src="../../assets/js/pwa.js"></script>
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

        // Dashboard interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Quick apply functionality
            const applyButtons = document.querySelectorAll('.btn-apply');
            applyButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const jobTitle = this.closest('.job-item').querySelector('h4').textContent;
                    if (confirm(`Apply to ${jobTitle}?`)) {
                        // Add loading state
                        this.textContent = 'Applying...';
                        this.disabled = true;
                        
                        // Simulate application process
                        setTimeout(() => {
                            this.textContent = 'Applied ✓';
                            this.style.background = '#059669';
                            
                            // Show success message
                            showNotification('Application submitted successfully!', 'success');
                        }, 1500);
                    }
                });
            });

            // Save job functionality
            const saveButtons = document.querySelectorAll('.btn-save');
            saveButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const isSaved = this.textContent === '❤️';
                    
                    if (isSaved) {
                        this.textContent = '💖';
                        this.style.background = '#fecaca';
                        showNotification('Job removed from saved', 'info');
                    } else {
                        this.textContent = '❤️';
                        this.style.background = '#dcfce7';
                        showNotification('Job saved successfully!', 'success');
                    }
                });
            });

            // Animate stats on load
            const statNumbers = document.querySelectorAll('.stat-number');
            statNumbers.forEach((stat, index) => {
                const finalValue = parseInt(stat.textContent);
                stat.textContent = '0';
                
                setTimeout(() => {
                    animateNumber(stat, finalValue);
                }, index * 200);
            });
        });

        function animateNumber(element, target) {
            const duration = 1000;
            const start = 0;
            const increment = target / (duration / 16);
            let current = start;

            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    element.textContent = target + (element.textContent.includes('%') ? '%' : '');
                    clearInterval(timer);
                } else {
                    element.textContent = Math.floor(current) + (element.textContent.includes('%') ? '%' : '');
                }
            }, 16);
        }

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <div class="notification-content">
                    <span>${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()">×</button>
                </div>
            `;
            
            // Add notification styles if not exist
            if (!document.querySelector('#notification-styles')) {
                const styles = document.createElement('style');
                styles.id = 'notification-styles';
                styles.textContent = `
                    .notification {
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        background: white;
                        border-radius: 8px;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                        border-left: 4px solid var(--primary);
                        z-index: 1000;
                        animation: slideIn 0.3s ease;
                    }
                    .notification-success { border-left-color: var(--success); }
                    .notification-info { border-left-color: var(--primary); }
                    .notification-content {
                        padding: 1rem;
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        gap: 1rem;
                    }
                    .notification button {
                        background: none;
                        border: none;
                        font-size: 1.5rem;
                        cursor: pointer;
                        color: var(--text-secondary);
                    }
                    @keyframes slideIn {
                        from { transform: translateX(100%); opacity: 0; }
                        to { transform: translateX(0); opacity: 1; }
                    }
                `;
                document.head.appendChild(styles);
            }
            
            document.body.appendChild(notification);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 3000);
        }

        // Add body class for bottom nav
        document.body.classList.add('has-bottom-nav');

        // Load AI Recommendations
        loadAIRecommendations();

        async function loadAIRecommendations() {
            const container = document.getElementById('ai-recommendations-container');
            
            try {
                const response = await fetch('/findajob/api/ai-job-recommendations.php');
                
                // Log response for debugging
                console.log('AI Recommendations Response Status:', response.status);
                
                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('API Error Response:', errorText);
                    throw new Error(`HTTP ${response.status}: ${errorText}`);
                }
                
                const data = await response.json();
                console.log('AI Recommendations Data:', data);
                
                if (data.error) {
                    console.error('API returned error:', data.error);
                    renderErrorState(data.error);
                    return;
                }
                
                if (data.success && data.recommendations.length > 0) {
                    renderRecommendations(data.recommendations);
                } else if (data.success) {
                    // No recommendations found
                    renderEmptyState(data.profile_completeness || 0, data.has_sufficient_profile || false);
                } else {
                    renderErrorState('Unexpected response format');
                }
            } catch (error) {
                console.error('Failed to load AI recommendations:', error);
                renderErrorState(error.message || 'Network error');
            }
        }

        function renderRecommendations(recommendations) {
            const container = document.getElementById('ai-recommendations-container');
            container.innerHTML = '';
            
            // Show top 5 recommendations in sidebar layout
            const topRecommendations = recommendations.slice(0, 5);
            
            topRecommendations.forEach(job => {
                const jobCard = createJobCard(job);
                container.appendChild(jobCard);
            });
        }

        function createJobCard(job) {
            const card = document.createElement('div');
            card.className = 'job-item ai-recommended';
            
            const matchColor = getMatchColor(job.match_level);
            
            card.innerHTML = `
                <div class="job-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 0.75rem;">
                    <h4 style="margin: 0; flex: 1;">
                        <a href="${job.job_url}" style="text-decoration: none; color: var(--text-primary); font-size: 1.1rem; font-weight: 600; display: block; line-height: 1.4;">
                            ${escapeHtml(job.title)}
                            ${job.is_urgent ? '<span class="urgent-badge">🔥 URGENT</span>' : ''}
                        </a>
                    </h4>
                    <span class="match-score" style="
                        background: linear-gradient(135deg, ${matchColor}, ${adjustBrightness(matchColor, -20)});
                        color: white;
                        padding: 0.4rem 0.9rem;
                        border-radius: 20px;
                        font-size: 0.85rem;
                        font-weight: 700;
                        white-space: nowrap;
                        box-shadow: 0 2px 8px ${matchColor}40;
                        display: flex;
                        align-items: center;
                        gap: 0.3rem;
                    ">
                        ⭐ ${job.match_score}%
                    </span>
                </div>
                
                <div class="job-company" style="display: flex; align-items: center; gap: 0.5rem; margin: 0.5rem 0; font-size: 0.95rem; color: var(--text-primary); font-weight: 500;">
                    <span style="font-size: 1.2rem;">🏢</span>
                    <span>${escapeHtml(job.company_name)}</span>
                </div>
                
                <div class="job-location" style="display: flex; align-items: center; gap: 0.5rem; margin: 0.5rem 0; color: var(--text-secondary); font-size: 0.9rem;">
                    <span style="font-size: 1.1rem;">📍</span>
                    <span>${escapeHtml(job.location)}</span>
                    ${job.remote_friendly ? '<span style="background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 0.2rem 0.6rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600; margin-left: 0.5rem;">🌐 Remote OK</span>' : ''}
                </div>
                
                ${job.match_reasons ? `
                    <div class="match-reasons" style="
                        background: linear-gradient(135deg, #eff6ff, #dbeafe);
                        padding: 0.75rem;
                        border-radius: 8px;
                        margin: 1rem 0;
                        font-size: 0.875rem;
                        color: #1e40af;
                        border-left: 3px solid #3b82f6;
                    ">
                        <div style="font-weight: 700; margin-bottom: 0.3rem; display: flex; align-items: center; gap: 0.4rem;">
                            <span style="font-size: 1.1rem;">✨</span>
                            <span>Why this matches:</span>
                        </div>
                        <div style="color: #1e3a8a;">${escapeHtml(job.match_reasons)}</div>
                    </div>
                ` : ''}
                
                <div class="job-details" style="display: flex; gap: 0.75rem; flex-wrap: wrap; margin: 1rem 0; font-size: 0.875rem;">
                    <span class="job-salary" style="
                        background: linear-gradient(135deg, #d1fae5, #a7f3d0);
                        color: #065f46;
                        font-weight: 700;
                        padding: 0.35rem 0.8rem;
                        border-radius: 6px;
                        display: flex;
                        align-items: center;
                        gap: 0.3rem;
                    ">
                        💰 ${job.formatted_salary}
                    </span>
                    <span class="job-type" style="
                        background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
                        color: #4338ca;
                        padding: 0.35rem 0.8rem;
                        border-radius: 6px;
                        font-weight: 600;
                        display: flex;
                        align-items: center;
                        gap: 0.3rem;
                    ">
                        💼 ${capitalizeFirst(job.employment_type)}
                    </span>
                    <span class="job-time" style="color: var(--text-muted); display: flex; align-items: center; gap: 0.3rem;">
                        <span style="font-size: 1rem;">🕐</span>
                        ${job.time_ago}
                    </span>
                    ${job.days_left !== null ? `
                        <span class="job-deadline" style="
                            color: ${job.days_left <= 7 ? '#dc2626' : '#6b7280'};
                            font-weight: ${job.days_left <= 7 ? '700' : '500'};
                            display: flex;
                            align-items: center;
                            gap: 0.3rem;
                            ${job.days_left <= 7 ? 'background: #fee2e2; padding: 0.35rem 0.8rem; border-radius: 6px;' : ''}
                        ">
                            ⏰ ${job.days_left} day${job.days_left !== 1 ? 's' : ''} left
                        </span>
                    ` : ''}
                </div>
                
                <div class="job-actions" style="display: flex; gap: 0.75rem; margin-top: 1.25rem;">
                    <button class="btn-view-details" onclick="viewJobDetails(${job.id})" style="
                        flex: 1;
                        background: linear-gradient(135deg, #dc2626, #991b1b);
                        color: white;
                        border: none;
                        padding: 0.85rem 1.5rem;
                        border-radius: 8px;
                        cursor: pointer;
                        transition: all 0.3s ease;
                        font-size: 0.95rem;
                        font-weight: 600;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 0.5rem;
                        box-shadow: 0 2px 8px rgba(220, 38, 38, 0.3);
                    " title="View Details">
                        <span>👁️</span> View Details
                    </button>
                    <button class="btn-save save-job ai-save-btn" data-job-id="${job.id}" onclick="saveJob(${job.id}, this)" style="
                        background: white;
                        border: 2px solid #e5e7eb;
                        padding: 0.75rem 1rem;
                        border-radius: 8px;
                        cursor: pointer;
                        transition: all 0.3s ease;
                        font-size: 1.3rem;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    " title="Save job">
                        ❤️
                    </button>
                </div>
            `;
            
            return card;
        }
        
        function adjustBrightness(color, percent) {
            const num = parseInt(color.replace("#",""), 16);
            const amt = Math.round(2.55 * percent);
            const R = (num >> 16) + amt;
            const G = (num >> 8 & 0x00FF) + amt;
            const B = (num & 0x0000FF) + amt;
            return "#" + (0x1000000 + (R<255?R<1?0:R:255)*0x10000 +
                (G<255?G<1?0:G:255)*0x100 + (B<255?B<1?0:B:255))
                .toString(16).slice(1);
        }

        function renderEmptyState(profileCompleteness = 0, hasSufficientProfile = false) {
            const container = document.getElementById('ai-recommendations-container');
            
            if (hasSufficientProfile || profileCompleteness >= 40) {
                // Profile is complete but no matching jobs found
                container.innerHTML = `
                    <div class="job-item empty-state" style="text-align: center; padding: 2.5rem 1.5rem; background: linear-gradient(135deg, #f9fafb, #f3f4f6); border: 2px dashed #d1d5db; border-radius: 12px;">
                        <div class="empty-state-icon" style="font-size: 4rem; margin-bottom: 1.5rem;">🔍</div>
                        <h4 style="color: var(--text-primary); margin-bottom: 1rem; font-size: 1.25rem; font-weight: 700;">
                            No Matching Jobs Right Now
                        </h4>
                        <p class="job-company" style="margin: 1rem auto; max-width: 400px; color: var(--text-secondary); line-height: 1.6;">
                            Our AI couldn't find any new jobs matching your profile at the moment. Check back later or browse all available jobs.
                        </p>
                        <div class="job-actions" style="justify-content: center; margin-top: 2rem; gap: 1rem; flex-wrap: wrap;">
                            <button class="btn-apply" onclick="window.location.href='../jobs/browse.php'" style="
                                background: linear-gradient(135deg, #dc2626, #991b1b);
                                color: white;
                                border: none;
                                padding: 0.85rem 2rem;
                                border-radius: 10px;
                                font-weight: 700;
                                cursor: pointer;
                                font-size: 1rem;
                                box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
                                transition: all 0.3s ease;
                            ">
                                🌐 Browse All Jobs
                            </button>
                            <button class="btn-apply" onclick="window.location.href='ai-recommendations.php'" style="
                                background: white;
                                border: 2px solid #667eea;
                                color: #667eea;
                                padding: 0.85rem 2rem;
                                border-radius: 10px;
                                font-weight: 700;
                                cursor: pointer;
                                font-size: 1rem;
                                box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
                                transition: all 0.3s ease;
                            ">
                                🤖 View Full AI Page
                            </button>
                        </div>
                    </div>
                `;
            } else {
                // Profile incomplete
                const progressColor = profileCompleteness < 30 ? '#ef4444' : profileCompleteness < 60 ? '#f59e0b' : '#10b981';
                
                container.innerHTML = `
                    <div class="job-item empty-state" style="text-align: center; padding: 2.5rem 1.5rem; background: linear-gradient(135deg, #fef3c7, #fde68a); border: 2px dashed #fbbf24; border-radius: 12px;">
                        <div class="empty-state-icon" style="font-size: 4rem; margin-bottom: 1.5rem;">🤖</div>
                        <h4 style="color: var(--text-primary); margin-bottom: 1rem; font-size: 1.25rem; font-weight: 700;">
                            AI Needs More Information
                        </h4>
                        <p class="job-company" style="margin: 1rem auto; max-width: 400px; color: var(--text-secondary); line-height: 1.6;">
                            Complete your profile with skills, experience, and preferences to get personalized AI-powered job recommendations
                        </p>
                        <div style="
                            background: white;
                            padding: 1.5rem;
                            border-radius: 12px;
                            margin: 1.5rem auto;
                            max-width: 350px;
                            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                        ">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                <div style="font-size: 0.9rem; color: var(--text-secondary); font-weight: 600;">
                                    Profile Completeness
                                </div>
                                <div style="font-size: 1.75rem; font-weight: 800; color: ${progressColor};">
                                    ${profileCompleteness}%
                                </div>
                            </div>
                            <div style="background: #e5e7eb; height: 12px; border-radius: 12px; overflow: hidden; position: relative;">
                                <div style="
                                    background: linear-gradient(90deg, ${progressColor}, ${adjustBrightness(progressColor, 20)});
                                    height: 100%;
                                    width: ${profileCompleteness}%;
                                    border-radius: 12px;
                                    transition: width 1s ease;
                                    box-shadow: 0 0 10px ${progressColor}80;
                                    position: relative;
                                    overflow: hidden;
                                ">
                                    <div style="
                                        position: absolute;
                                        top: 0;
                                        left: 0;
                                        bottom: 0;
                                        right: 0;
                                        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
                                        animation: shimmer 2s infinite;
                                    "></div>
                                </div>
                            </div>
                            <div style="margin-top: 0.75rem; font-size: 0.8rem; color: var(--text-muted);">
                                ${profileCompleteness < 40 ? '⚠️ Minimum 40% required for AI recommendations' : '✅ Looking good! Add more for better matches'}
                            </div>
                        </div>
                        <div class="job-actions" style="justify-content: center; margin-top: 2rem;">
                            <button class="btn-apply" onclick="window.location.href='profile.php'" style="
                                background: linear-gradient(135deg, #dc2626, #991b1b);
                                color: white;
                                border: none;
                                padding: 0.85rem 2rem;
                                border-radius: 10px;
                                font-weight: 700;
                                cursor: pointer;
                                font-size: 1rem;
                                box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
                                transition: all 0.3s ease;
                            ">
                                ✏️ Complete Profile Now
                            </button>
                        </div>
                    </div>
                `;
            }
        }

        function renderErrorState(errorMessage = '') {
            const container = document.getElementById('ai-recommendations-container');
            container.innerHTML = `
                <div class="job-item empty-state" style="
                    text-align: center;
                    padding: 2.5rem 1.5rem;
                    background: linear-gradient(135deg, #fee2e2, #fecaca);
                    border: 2px dashed #f87171;
                    border-radius: 12px;
                ">
                    <div class="empty-state-icon" style="font-size: 4rem; margin-bottom: 1.5rem;">⚠️</div>
                    <h4 style="color: #991b1b; margin-bottom: 1rem; font-size: 1.25rem; font-weight: 700;">
                        Could not load recommendations
                    </h4>
                    <p style="margin: 1rem auto; color: #7f1d1d; max-width: 400px; line-height: 1.6;">
                        We encountered a problem loading your AI recommendations. Please try refreshing the page.
                    </p>
                    ${errorMessage ? `
                        <div style="
                            background: white;
                            padding: 1rem;
                            border-radius: 8px;
                            margin: 1.5rem auto;
                            max-width: 500px;
                            border-left: 4px solid #ef4444;
                        ">
                            <div style="font-size: 0.75rem; color: #7f1d1d; font-weight: 600; margin-bottom: 0.5rem; text-align: left;">
                                🔍 Technical Details:
                            </div>
                            <p style="
                                font-size: 0.8rem;
                                color: #991b1b;
                                font-family: 'Courier New', monospace;
                                text-align: left;
                                word-break: break-word;
                                margin: 0;
                                line-height: 1.5;
                            ">${escapeHtml(errorMessage)}</p>
                        </div>
                    ` : ''}
                    <button onclick="loadAIRecommendations()" class="btn-apply" style="
                        background: linear-gradient(135deg, #dc2626, #991b1b);
                        color: white;
                        border: none;
                        padding: 0.85rem 2rem;
                        border-radius: 10px;
                        font-weight: 700;
                        cursor: pointer;
                        font-size: 1rem;
                        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
                        transition: all 0.3s ease;
                        margin-top: 1rem;
                    ">
                        🔄 Retry
                    </button>
                </div>
            `;
        }

        function getMatchColor(level) {
            const colors = {
                'excellent': '#10b981',
                'good': '#3b82f6',
                'fair': '#f59e0b',
                'basic': '#6b7280'
            };
            return colors[level] || colors['basic'];
        }

        function capitalizeFirst(str) {
            if (!str) return '';
            return str.charAt(0).toUpperCase() + str.slice(1).replace(/_/g, ' ');
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function adjustBrightness(color, percent) {
            // Handle both hex colors and already calculated values
            if (!color || !color.startsWith('#')) return color || '#000000';
            
            const num = parseInt(color.replace("#",""), 16);
            const amt = Math.round(2.55 * percent);
            const R = (num >> 16) + amt;
            const G = (num >> 8 & 0x00FF) + amt;
            const B = (num & 0x0000FF) + amt;
            return "#" + (0x1000000 + (R<255?R<1?0:R:255)*0x10000 +
                (G<255?G<1?0:G:255)*0x100 + (B<255?B<1?0:B:255))
                .toString(16).slice(1);
        }
        
        function applyToJob(jobId) {
            window.location.href = '/findajob/pages/jobs/apply.php?id=' + jobId;
        }

        function viewJobDetails(jobId) {
            window.location.href = '/findajob/pages/jobs/details.php?id=' + jobId;
        }

        async function saveJob(jobId, button) {
            try {
                const response = await fetch('/findajob/api/save-job.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ job_id: jobId })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    button.innerHTML = '💚';
                    button.style.borderColor = 'var(--success)';
                    showNotification('Job saved successfully!', 'success');
                } else {
                    showNotification(data.message || 'Failed to save job', 'error');
                }
            } catch (error) {
                showNotification('Failed to save job', 'error');
            }
        }
    </script>

    <style>
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.8;
                transform: scale(1.05);
            }
        }
        
        @keyframes shimmer {
            0% {
                background-position: -1000px 0;
            }
            100% {
                background-position: 1000px 0;
            }
        }
        
        .ai-recommended {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            animation: fadeInUp 0.5s ease;
            border-left: 4px solid transparent;
            position: relative;
            overflow: hidden;
        }
        
        .ai-recommended::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, 
                transparent 0%, 
                rgba(102, 126, 234, 0.05) 50%, 
                transparent 100%);
            background-size: 1000px 100%;
            animation: shimmer 3s infinite;
            pointer-events: none;
        }
        
        .ai-recommended:hover {
            transform: translateY(-4px) scale(1.01);
            box-shadow: 0 12px 28px rgba(102, 126, 234, 0.15), 
                        0 6px 12px rgba(0, 0, 0, 0.1);
            border-left-color: #667eea;
        }
        
        .urgent-badge {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            padding: 0.25rem 0.7rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
            margin-left: 0.5rem;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            animation: pulse 2s ease-in-out infinite;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
        }
        
        .match-score {
            animation: fadeInUp 0.6s ease 0.2s backwards;
        }
        
        .ai-apply-btn:hover {
            background: linear-gradient(135deg, #b91c1c, #7f1d1d) !important;
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.4) !important;
        }
        
        .ai-apply-btn:active {
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(220, 38, 38, 0.3) !important;
        }
        
        .btn-view-details:hover {
            background: linear-gradient(135deg, #667eea, #764ba2) !important;
            color: white !important;
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4) !important;
        }
        
        .btn-view-details:active {
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(102, 126, 234, 0.3) !important;
        }
        
        .ai-save-btn {
            position: relative;
        }
        
        .ai-save-btn:hover {
            border-color: #dc2626 !important;
            background: linear-gradient(135deg, #fef2f2, #fee2e2) !important;
            transform: scale(1.1);
        }
        
        .ai-save-btn:active {
            transform: scale(0.95);
        }
        
        .ai-save-btn.saved {
            background: linear-gradient(135deg, #dcfce7, #bbf7d0) !important;
            border-color: #10b981 !important;
        }
        
        .job-header h4 a {
            transition: color 0.2s ease;
        }
        
        .job-header h4 a:hover {
            color: #667eea !important;
        }
        
        .match-reasons {
            animation: fadeInUp 0.7s ease 0.3s backwards;
        }
        
        .job-details > span {
            animation: fadeInUp 0.8s ease 0.4s backwards;
        }
        
        .job-actions {
            animation: fadeInUp 0.9s ease 0.5s backwards;
        }
        
        /* Loading spinner enhancement */
        .spinner {
            border: 4px solid rgba(102, 126, 234, 0.1);
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 2rem auto;
        }
        
        /* Scrollbar enhancement */
        .jobs-list::-webkit-scrollbar {
            width: 8px;
        }
        
        .jobs-list::-webkit-scrollbar-track {
            background: #f3f4f6;
            border-radius: 10px;
        }
        
        .jobs-list::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #667eea, #764ba2);
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .jobs-list::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, #5568d3, #6a4193);
            box-shadow: 0 0 10px rgba(102, 126, 234, 0.5);
        }
        
        /* AI Recommendations header enhancement */
        .ai-recommendations-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 800;
        }
        
        /* Tooltip enhancement */
        [title]:hover::after {
            content: attr(title);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: #1f2937;
            color: white;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            white-space: nowrap;
            z-index: 1000;
            margin-bottom: 0.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        /* Responsive enhancements */
        @media (max-width: 768px) {
            .ai-recommended {
                margin-bottom: 1rem;
            }
            
            .job-header {
                flex-direction: column;
                gap: 0.75rem !important;
            }
            
            .match-score {
                align-self: flex-start;
            }
            
            .job-details {
                flex-direction: column;
                gap: 0.5rem !important;
            }
            
            .job-actions {
                flex-direction: column;
            }
            
            .ai-apply-btn {
                width: 100%;
            }
        }
        
        /* Empty state enhancement */
        .empty-state-icon {
            font-size: 4rem;
            animation: pulse 2s ease-in-out infinite;
        }
        
        /* Button ripple effect */
        @keyframes ripple {
            0% {
                transform: scale(0);
                opacity: 1;
            }
            100% {
                transform: scale(4);
                opacity: 0;
            }
        }
        
        .btn-apply, .btn-save {
            position: relative;
            overflow: hidden;
        }
        
        .btn-apply::after, .btn-save::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .btn-apply:active::after, .btn-save:active::after {
            width: 300px;
            height: 300px;
        }
    </style>

    <?php include '../../includes/footer.php'; ?>
</body>
</html>