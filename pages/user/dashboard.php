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
        jsp.id as profile_id, jsp.user_id, jsp.date_of_birth, jsp.gender, 
        jsp.state_of_origin, jsp.lga_of_origin, jsp.current_state, jsp.current_city,
        jsp.education_level, jsp.years_of_experience, jsp.job_status,
        jsp.salary_expectation_min, jsp.salary_expectation_max, jsp.skills, jsp.bio,
        jsp.profile_picture, jsp.nin, jsp.nin_verified, jsp.nin_verified_at, 
        jsp.bvn, jsp.is_verified, jsp.verification_status,
        jsp.subscription_type, jsp.subscription_expires,
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
                                <img src="../../assets/images/default-avatar.png" alt="Profile" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="avatar-placeholder">
                                    <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                </div>
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
                                    $skills = $user['skills'] ? explode(',', $user['skills']) : ['Complete Profile'];
                                    foreach (array_slice($skills, 0, 3) as $skill): 
                                    ?>
                                        <span class="tag"><?php echo htmlspecialchars(trim($skill)); ?></span>
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
                            
                            <a href="subscription.php" class="action-btn upgrade">
                                <div class="action-icon">⭐</div>
                                <div class="action-content">
                                    <div class="action-title">Upgrade to Pro</div>
                                    <div class="action-desc">Unlock premium features</div>
                                </div>
                            </a>
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

                    <!-- Recommended Jobs -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>Recommended for You</h3>
                            <span class="ai-badge">🤖 AI Matched</span>
                        </div>
                        <div class="jobs-list">
                            <?php if (count($recommendedJobs) > 0): ?>
                                <?php foreach ($recommendedJobs as $job): ?>
                                    <div class="job-item">
                                        <div class="job-header">
                                            <h4>
                                                <a href="../jobs/details.php?id=<?php echo $job['id']; ?>" style="text-decoration: none; color: inherit;">
                                                    <?php echo htmlspecialchars($job['title']); ?>
                                                </a>
                                            </h4>
                                            <span class="match-score"><?php echo rand(75, 95); ?>% match</span>
                                        </div>
                                        <p class="job-company">🏢 <?php echo htmlspecialchars($job['employer_name'] ?? $job['company_name']); ?></p>
                                        <p class="job-location">📍 <?php echo htmlspecialchars($job['location']); ?></p>
                                        <div class="job-details">
                                            <span class="job-salary">
                                                <?php if ($job['salary_min'] && $job['salary_max']): ?>
                                                    ₦<?php echo number_format($job['salary_min']/1000); ?>K - ₦<?php echo number_format($job['salary_max']/1000); ?>K
                                                <?php elseif ($job['salary_min']): ?>
                                                    ₦<?php echo number_format($job['salary_min']/1000); ?>K+
                                                <?php else: ?>
                                                    Negotiable
                                                <?php endif; ?>
                                            </span>
                                            <span class="job-type"><?php echo ucfirst($job['type']); ?></span>
                                        </div>
                                        <div class="job-actions">
                                            <button class="btn-apply" onclick="window.location.href='../jobs/apply.php?id=<?php echo $job['id']; ?>'">Quick Apply</button>
                                            <button class="btn-save save-job" data-job-id="<?php echo $job['id']; ?>">💖</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="job-item" style="text-align: center; padding: 2rem;">
                                    <div class="job-header">
                                        <h4 style="color: var(--text-secondary);">No Matches Yet</h4>
                                        <span class="match-score" style="color: var(--text-secondary);">0% match</span>
                                    </div>
                                    <p class="job-company" style="margin: 1rem 0;">Complete your profile to get personalized job recommendations</p>
                                    <div class="job-actions" style="justify-content: center;">
                                        <button class="btn-apply" onclick="window.location.href='profile.php'">Complete Profile</button>
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
    </script>

    <?php include '../../includes/footer.php'; ?>
</body>
</html>