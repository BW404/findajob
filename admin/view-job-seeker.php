<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/permissions.php';

// Check admin authentication
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = getCurrentUserId();
$stmt = $pdo->prepare("SELECT user_type FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user || $user['user_type'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$seeker_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$seeker_id) {
    header('Location: job-seekers.php');
    exit();
}

$success = '';
$error = '';

// Handle form submissions (Super Admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isSuperAdmin($user_id)) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_subscription') {
        try {
            $plan = $_POST['plan'] ?? 'basic';
            $type = $_POST['type'] ?? 'monthly';
            $duration_days = (int)($_POST['duration_days'] ?? 30);
            
            $status = ($plan === 'basic') ? 'free' : 'active';
            $start_date = date('Y-m-d H:i:s');
            $end_date = date('Y-m-d H:i:s', strtotime("+$duration_days days"));
            
            $stmt = $pdo->prepare("
                UPDATE users 
                SET subscription_plan = ?, 
                    subscription_status = ?,
                    subscription_type = ?,
                    subscription_start = ?,
                    subscription_end = ?
                WHERE id = ?
            ");
            $stmt->execute([$plan, $status, $type, $start_date, $end_date, $seeker_id]);
            
            $success = 'Subscription plan updated successfully!';
        } catch (Exception $e) {
            $error = 'Error updating subscription: ' . $e->getMessage();
        }
    } elseif ($action === 'update_boost') {
        try {
            $boost_type = $_POST['boost_type'] ?? 'profile';
            $duration_days = (int)($_POST['boost_duration'] ?? 30);
            $boost_until = date('Y-m-d H:i:s', strtotime("+$duration_days days"));
            
            if ($boost_type === 'profile') {
                $stmt = $pdo->prepare("
                    UPDATE job_seeker_profiles 
                    SET profile_boosted = 1, profile_boost_until = ?
                    WHERE user_id = ?
                ");
                $stmt->execute([$boost_until, $seeker_id]);
                $success = 'Profile boost activated successfully!';
            } elseif ($boost_type === 'verification') {
                $stmt = $pdo->prepare("
                    UPDATE job_seeker_profiles 
                    SET verification_boosted = 1, verification_boost_date = ?
                    WHERE user_id = ?
                ");
                $stmt->execute([date('Y-m-d H:i:s'), $seeker_id]);
                $success = 'Verification boost activated successfully!';
            }
        } catch (Exception $e) {
            $error = 'Error activating boost: ' . $e->getMessage();
        }
    } elseif ($action === 'remove_boost') {
        try {
            $stmt = $pdo->prepare("
                UPDATE job_seeker_profiles 
                SET profile_boosted = 0, profile_boost_until = NULL
                WHERE user_id = ?
            ");
            $stmt->execute([$seeker_id]);
            $success = 'Profile boost removed successfully!';
        } catch (Exception $e) {
            $error = 'Error removing boost: ' . $e->getMessage();
        }
    }
    
    // Reload page to show updated data
    if ($success) {
        header("Location: view-job-seeker.php?id=$seeker_id&success=" . urlencode($success));
        exit();
    }
}

// Get success message from URL
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}

// Get job seeker profile data
$stmt = $pdo->prepare("
    SELECT 
        u.id, u.first_name, u.last_name, u.email, u.phone,
        u.email_verified, u.phone_verified, u.is_active,
        u.subscription_plan, u.subscription_status, u.subscription_type, 
        u.subscription_start, u.subscription_end,
        u.created_at as user_created_at,
        jsp.date_of_birth, jsp.gender, 
        jsp.state_of_origin, jsp.lga_of_origin, 
        jsp.current_state, jsp.current_city,
        jsp.education_level, jsp.years_of_experience, jsp.job_status,
        jsp.salary_expectation_min, jsp.salary_expectation_max, 
        jsp.skills, jsp.bio, jsp.profile_picture,
        jsp.nin_verified, jsp.verification_status,
        jsp.profile_boosted, jsp.profile_boost_until
    FROM users u
    LEFT JOIN job_seeker_profiles jsp ON u.id = jsp.user_id
    WHERE u.id = ? AND u.user_type = 'job_seeker'
");
$stmt->execute([$seeker_id]);
$seeker = $stmt->fetch();

if (!$seeker) {
    header('Location: job-seekers.php?error=not_found');
    exit();
}

// Get application count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM job_applications WHERE job_seeker_id = ?");
$stmt->execute([$seeker_id]);
$application_count = $stmt->fetchColumn();

// Get CV count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM cvs WHERE user_id = ?");
$stmt->execute([$seeker_id]);
$cv_count = $stmt->fetchColumn();

// Get saved jobs count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM saved_jobs WHERE user_id = ?");
$stmt->execute([$seeker_id]);
$saved_jobs_count = $stmt->fetchColumn();

$pageTitle = 'View Job Seeker - ' . $seeker['first_name'] . ' ' . $seeker['last_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - FindAJob Admin</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f7fa; }
        .admin-layout { display: flex; min-height: 100vh; }
        
        .admin-sidebar {
            width: 260px;
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        .sidebar-header { padding: 24px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header h1 { font-size: 20px; font-weight: 700; color: #fff; margin-bottom: 4px; }
        .sidebar-header p { font-size: 13px; color: rgba(255,255,255,0.6); }
        .sidebar-nav { padding: 20px 0; }
        .nav-section { margin-bottom: 24px; }
        .nav-section-title { padding: 0 20px 8px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: rgba(255,255,255,0.5); }
        .nav-link { display: flex; align-items: center; padding: 12px 20px; color: rgba(255,255,255,0.8); text-decoration: none; transition: all 0.2s; }
        .nav-link:hover { background: rgba(255,255,255,0.1); color: white; }
        .nav-link.active { background: rgba(220, 38, 38, 0.2); color: white; border-left: 3px solid #dc2626; }
        .nav-link i { width: 20px; margin-right: 12px; font-size: 16px; }
        
        .admin-main { margin-left: 260px; flex: 1; padding: 24px; width: calc(100% - 260px); }
        
        .page-header {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            margin-bottom: 24px;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #dc2626;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 16px;
            padding: 8px 12px;
            border-radius: 6px;
            transition: background 0.2s;
        }
        
        .back-link:hover { background: #fee2e2; }
        
        .profile-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .profile-header {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: white;
            padding: 32px 24px;
            text-align: center;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: white;
            color: #dc2626;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 16px;
            border: 4px solid rgba(255,255,255,0.3);
        }
        
        .profile-name { font-size: 28px; font-weight: 700; margin-bottom: 8px; }
        .profile-email { opacity: 0.9; font-size: 16px; }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            padding: 24px;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .stat-box {
            background: white;
            padding: 16px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-value { font-size: 32px; font-weight: 700; color: #dc2626; }
        .stat-label { font-size: 14px; color: #6b7280; margin-top: 4px; }
        
        .profile-content { padding: 24px; }
        
        .info-section {
            margin-bottom: 32px;
        }
        
        .info-section h3 {
            font-size: 18px;
            color: #1f2937;
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 2px solid #dc2626;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
        }
        
        .info-item {
            padding: 12px;
            background: #f9fafb;
            border-radius: 6px;
        }
        
        .info-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .info-value {
            font-size: 14px;
            color: #1f2937;
            font-weight: 500;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        
        .skills-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px;
        }
        
        .skill-tag {
            background: #dc2626;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="admin-main">
            <a href="job-seekers.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Job Seekers
            </a>
            
            <div class="profile-container">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?= strtoupper(substr($seeker['first_name'], 0, 1) . substr($seeker['last_name'], 0, 1)) ?>
                    </div>
                    <div class="profile-name"><?= htmlspecialchars($seeker['first_name'] . ' ' . $seeker['last_name']) ?></div>
                    <div class="profile-email"><?= htmlspecialchars($seeker['email']) ?></div>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-box">
                        <div class="stat-value"><?= $application_count ?></div>
                        <div class="stat-label">Applications</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value"><?= $cv_count ?></div>
                        <div class="stat-label">CVs</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value"><?= $saved_jobs_count ?></div>
                        <div class="stat-label">Saved Jobs</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value"><?= $seeker['years_of_experience'] ?? 0 ?></div>
                        <div class="stat-label">Years Experience</div>
                    </div>
                </div>
                
                <div class="profile-content">
                    <!-- Account Information -->
                    <div class="info-section">
                        <h3><i class="fas fa-user-circle"></i> Account Information</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">User ID</div>
                                <div class="info-value">#<?= $seeker['id'] ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Phone</div>
                                <div class="info-value"><?= htmlspecialchars($seeker['phone'] ?? 'Not provided') ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Email Verified</div>
                                <div class="info-value">
                                    <?php if ($seeker['email_verified']): ?>
                                        <span class="badge badge-success">✓ Verified</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">✗ Not Verified</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Account Status</div>
                                <div class="info-value">
                                    <?php if ($seeker['is_active']): ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Suspended</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Member Since</div>
                                <div class="info-value"><?= date('M d, Y', strtotime($seeker['user_created_at'])) ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Subscription Information -->
                    <div class="info-section">
                        <h3><i class="fas fa-crown"></i> Subscription & Boosts</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Current Plan</div>
                                <div class="info-value">
                                    <?php 
                                    $is_pro = strpos($seeker['subscription_plan'], 'pro') !== false && $seeker['subscription_status'] === 'active';
                                    if ($is_pro): 
                                    ?>
                                        <span class="badge badge-success">👑 Pro <?= ucfirst($seeker['subscription_type']) ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-info">Basic Plan</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($is_pro && $seeker['subscription_end']): 
                                $sub_end_date = new DateTime($seeker['subscription_end']);
                                $now = new DateTime();
                                $is_expired = $sub_end_date <= $now;
                                $days_diff = $now->diff($sub_end_date)->days;
                            ?>
                            <div class="info-item">
                                <div class="info-label">Subscription Expires</div>
                                <div class="info-value">
                                    <?php if ($is_expired): ?>
                                        <span class="badge badge-danger">⚠️ Expired on <?= date('M d, Y', strtotime($seeker['subscription_end'])) ?></span>
                                    <?php else: ?>
                                        <?= date('M d, Y', strtotime($seeker['subscription_end'])) ?>
                                        <small style="color: #059669; display: block; margin-top: 4px;">
                                            (<?= $days_diff ?> days remaining)
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            <div class="info-item">
                                <div class="info-label">Profile Boost</div>
                                <div class="info-value">
                                    <?php 
                                    $boost_active = false;
                                    if ($seeker['profile_boost_until']) {
                                        $boost_date = new DateTime($seeker['profile_boost_until']);
                                        $boost_active = $boost_date > new DateTime();
                                    }
                                    if ($boost_active): 
                                        $boost_days = (new DateTime())->diff($boost_date)->days;
                                    ?>
                                        <span class="badge badge-info" style="background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); color: white;">
                                            🚀 Active until <?= date('M d, Y', strtotime($seeker['profile_boost_until'])) ?>
                                        </span>
                                        <small style="color: #7c3aed; display: block; margin-top: 4px;">
                                            (<?= $boost_days ?> days remaining)
                                        </small>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Not Active</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">NIN Verified</div>
                                <div class="info-value">
                                    <?php if ($seeker['nin_verified']): ?>
                                        <span class="badge badge-success">✓ Verified</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Not Verified</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (isSuperAdmin($user_id)): ?>
                        <!-- Admin Actions -->
                        <div style="margin-top: 24px; padding-top: 24px; border-top: 2px solid #e5e7eb;">
                            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                                <button onclick="showSubscriptionModal()" class="btn btn-primary" style="background: #059669; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; color: white; font-weight: 500;">
                                    <i class="fas fa-crown"></i> Update Subscription
                                </button>
                                <button onclick="showBoostModal()" class="btn btn-primary" style="background: #7c3aed; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; color: white; font-weight: 500;">
                                    <i class="fas fa-rocket"></i> Manage Boosts
                                </button>
                                <?php if ($boost_active): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Remove profile boost?');">
                                    <input type="hidden" name="action" value="remove_boost">
                                    <button type="submit" class="btn btn-danger" style="background: #dc2626; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; color: white; font-weight: 500;">
                                        <i class="fas fa-times"></i> Remove Boost
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Personal Information -->
                    <div class="info-section">
                        <h3><i class="fas fa-info-circle"></i> Personal Information</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Date of Birth</div>
                                <div class="info-value"><?= $seeker['date_of_birth'] ? date('M d, Y', strtotime($seeker['date_of_birth'])) : 'Not provided' ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Gender</div>
                                <div class="info-value"><?= ucfirst($seeker['gender'] ?? 'Not specified') ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">State of Origin</div>
                                <div class="info-value"><?= htmlspecialchars($seeker['state_of_origin'] ?? 'Not provided') ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">LGA of Origin</div>
                                <div class="info-value"><?= htmlspecialchars($seeker['lga_of_origin'] ?? 'Not provided') ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Current Location -->
                    <div class="info-section">
                        <h3><i class="fas fa-map-marker-alt"></i> Current Location</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">State</div>
                                <div class="info-value"><?= htmlspecialchars($seeker['current_state'] ?? 'Not provided') ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">City</div>
                                <div class="info-value"><?= htmlspecialchars($seeker['current_city'] ?? 'Not provided') ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Professional Information -->
                    <div class="info-section">
                        <h3><i class="fas fa-briefcase"></i> Professional Information</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Job Status</div>
                                <div class="info-value">
                                    <?php
                                    $status_badges = [
                                        'looking' => ['class' => 'success', 'text' => '🔍 Looking for Work'],
                                        'not_looking' => ['class' => 'danger', 'text' => '🚫 Not Looking'],
                                        'employed_but_looking' => ['class' => 'info', 'text' => '💼 Employed but Looking']
                                    ];
                                    $status = $status_badges[$seeker['job_status']] ?? ['class' => 'warning', 'text' => 'Unknown'];
                                    ?>
                                    <span class="badge badge-<?= $status['class'] ?>"><?= $status['text'] ?></span>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Education Level</div>
                                <div class="info-value"><?= strtoupper($seeker['education_level'] ?? 'Not specified') ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Years of Experience</div>
                                <div class="info-value"><?= $seeker['years_of_experience'] ?? 0 ?> years</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Salary Expectation</div>
                                <div class="info-value">
                                    <?php if ($seeker['salary_expectation_min'] && $seeker['salary_expectation_max']): ?>
                                        ₦<?= number_format($seeker['salary_expectation_min']) ?> - ₦<?= number_format($seeker['salary_expectation_max']) ?>
                                    <?php else: ?>
                                        Not specified
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Skills -->
                    <?php if ($seeker['skills']): ?>
                    <div class="info-section">
                        <h3><i class="fas fa-star"></i> Skills</h3>
                        <div class="skills-list">
                            <?php
                            $skills = json_decode($seeker['skills'], true);
                            if (is_array($skills)) {
                                foreach ($skills as $skill) {
                                    echo '<span class="skill-tag">' . htmlspecialchars($skill) . '</span>';
                                }
                            } else {
                                echo '<span class="info-value">No skills listed</span>';
                            }
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Bio -->
                    <?php if ($seeker['bio']): ?>
                    <div class="info-section">
                        <h3><i class="fas fa-align-left"></i> Bio</h3>
                        <div class="info-item">
                            <div class="info-value"><?= nl2br(htmlspecialchars($seeker['bio'])) ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Subscription Modal -->
    <div id="subscriptionModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; padding: 32px; max-width: 500px; width: 90%; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
            <h3 style="margin-bottom: 24px; color: #1f2937; font-size: 24px;">
                <i class="fas fa-crown" style="color: #059669;"></i> Update Subscription Plan
            </h3>
            <form method="POST">
                <input type="hidden" name="action" value="update_subscription">
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Plan Type</label>
                    <select name="plan" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
                        <option value="basic">Basic (Free)</option>
                        <option value="job_seeker_pro_monthly">Pro Monthly</option>
                        <option value="job_seeker_pro_yearly">Pro Yearly</option>
                    </select>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Subscription Type</label>
                    <select name="type" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
                        <option value="monthly">Monthly</option>
                        <option value="yearly">Yearly</option>
                    </select>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Duration (Days)</label>
                    <input type="number" name="duration_days" value="30" min="1" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
                    <small style="color: #6b7280; display: block; margin-top: 4px;">Number of days from today</small>
                </div>
                
                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" onclick="closeSubscriptionModal()" style="padding: 10px 20px; background: #e5e7eb; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">
                        Cancel
                    </button>
                    <button type="submit" style="padding: 10px 20px; background: #059669; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">
                        <i class="fas fa-save"></i> Update Plan
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Boost Modal -->
    <div id="boostModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; padding: 32px; max-width: 500px; width: 90%; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
            <h3 style="margin-bottom: 24px; color: #1f2937; font-size: 24px;">
                <i class="fas fa-rocket" style="color: #7c3aed;"></i> Activate Boost
            </h3>
            <form method="POST">
                <input type="hidden" name="action" value="update_boost">
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Boost Type</label>
                    <select name="boost_type" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
                        <option value="profile">Profile Boost (Top of searches)</option>
                        <option value="verification">Verification Badge</option>
                    </select>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Duration (Days)</label>
                    <input type="number" name="boost_duration" value="30" min="1" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
                    <small style="color: #6b7280; display: block; margin-top: 4px;">Number of days from today</small>
                </div>
                
                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" onclick="closeBoostModal()" style="padding: 10px 20px; background: #e5e7eb; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">
                        Cancel
                    </button>
                    <button type="submit" style="padding: 10px 20px; background: #7c3aed; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">
                        <i class="fas fa-rocket"></i> Activate Boost
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showSubscriptionModal() {
            document.getElementById('subscriptionModal').style.display = 'flex';
        }
        
        function closeSubscriptionModal() {
            document.getElementById('subscriptionModal').style.display = 'none';
        }
        
        function showBoostModal() {
            document.getElementById('boostModal').style.display = 'flex';
        }
        
        function closeBoostModal() {
            document.getElementById('boostModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        document.getElementById('subscriptionModal')?.addEventListener('click', function(e) {
            if (e.target === this) closeSubscriptionModal();
        });
        
        document.getElementById('boostModal')?.addEventListener('click', function(e) {
            if (e.target === this) closeBoostModal();
        });
        
        // Show success/error messages
        <?php if ($success): ?>
        alert('<?= addslashes($success) ?>');
        <?php endif; ?>
        
        <?php if ($error): ?>
        alert('Error: <?= addslashes($error) ?>');
        <?php endif; ?>
    </script>
</body>
</html>
