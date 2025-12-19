<?php
require_once '../config/database.php';
require_once '../config/session.php';

// Check if user is admin
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Check if user has admin role (from users table with user_type)
$user_id = getCurrentUserId();
$stmt = $pdo->prepare("SELECT user_type FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user || $user['user_type'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Get admin info including role
$stmt = $pdo->prepare("
    SELECT u.first_name, u.last_name, u.email, ar.role_name 
    FROM users u 
    LEFT JOIN admin_roles ar ON u.admin_role_id = ar.id 
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$admin = $stmt->fetch();
$admin_role_display = $admin['role_name'] ?? 'Admin';

// Initialize all variables with default values
$totalJobSeekers = $totalEmployers = $totalAdmins = 0;
$activeJobs = $closedJobs = $draftJobs = 0;
$totalApplications = $newApplications = 0;
$completedTransactions = $totalRevenue = 0;
$totalCvs = 0;
$newUsersThisMonth = $newJobsThisMonth = 0;
$verifiedNIN = $verifiedCAC = $verifiedPhones = 0;

// Get dashboard statistics
try {
    // Users Statistics
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'job_seeker'");
    $totalJobSeekers = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'employer'");
    $totalEmployers = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'admin'");
    $totalAdmins = $stmt->fetchColumn();
    
    // Jobs Statistics
    $stmt = $pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'active'");
    $activeJobs = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'closed'");
    $closedJobs = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'draft'");
    $draftJobs = $stmt->fetchColumn();
    
    // Applications Statistics
    $stmt = $pdo->query("SELECT COUNT(*) FROM job_applications");
    $totalApplications = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM job_applications WHERE application_status = 'applied'");
    $newApplications = $stmt->fetchColumn();
    
    // Transactions Statistics
    $stmt = $pdo->query("SELECT COUNT(*) FROM transactions WHERE status = 'successful'");
    $completedTransactions = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE status = 'successful'");
    $totalRevenue = $stmt->fetchColumn();
    
    // CVs Statistics
    $stmt = $pdo->query("SELECT COUNT(*) FROM cvs");
    $totalCvs = $stmt->fetchColumn();
    
    // Recent Activity - Last 30 days
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $newUsersThisMonth = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM jobs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $newJobsThisMonth = $stmt->fetchColumn();
    
    // Verification Statistics
    $stmt = $pdo->query("SELECT COUNT(*) FROM job_seeker_profiles WHERE nin_verified = 1");
    $verifiedNIN = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM employer_profiles WHERE company_cac_verified = 1");
    $verifiedCAC = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE phone_verified = 1");
    $verifiedPhones = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
    // Set all variables to 0 on error
    $totalJobSeekers = $totalEmployers = $totalAdmins = 0;
    $activeJobs = $closedJobs = $draftJobs = 0;
    $totalApplications = $newApplications = 0;
    $completedTransactions = $totalRevenue = 0;
    $totalCvs = 0;
    $newUsersThisMonth = $newJobsThisMonth = 0;
    $verifiedNIN = $verifiedCAC = $verifiedPhones = 0;
}

// Get recent activities (last 50)
try {
    $activities = $pdo->query("
        SELECT 
            'user_registered' as type,
            CONCAT(u.first_name, ' ', u.last_name) as name,
            u.user_type as role,
            u.created_at as activity_time
        FROM users u
        ORDER BY u.created_at DESC
        LIMIT 50
    ")->fetchAll();
} catch (PDOException $e) {
    error_log("Activities fetch error: " . $e->getMessage());
    $activities = [];
}

$pageTitle = 'Admin Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - FindAJob Nigeria</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
        }
        
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
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
        
        .sidebar-header {
            padding: 24px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h1 {
            font-size: 20px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 4px;
        }
        
        .sidebar-header p {
            font-size: 13px;
            color: rgba(255,255,255,0.6);
        }
        
        .sidebar-nav {
            padding: 20px 0;
        }
        
        .nav-section {
            margin-bottom: 24px;
        }
        
        .nav-section-title {
            padding: 0 20px 8px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: rgba(255,255,255,0.5);
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.2s;
            position: relative;
        }
        
        .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .nav-link.active {
            background: rgba(220, 38, 38, 0.2);
            color: white;
            border-left: 3px solid #dc2626;
        }
        
        .nav-link i {
            width: 20px;
            margin-right: 12px;
            font-size: 16px;
        }
        
        .nav-link .badge {
            margin-left: auto;
            background: #dc2626;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        
        /* Main Content */
        .admin-main {
            margin-left: 260px;
            flex: 1;
            padding: 24px;
            width: calc(100% - 260px);
        }
        
        .admin-header {
            background: white;
            padding: 20px 24px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-header h2 {
            font-size: 24px;
            color: #1a1a2e;
        }
        
        .admin-user {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .admin-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #dc2626, #991b1b);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        .admin-info h4 {
            font-size: 14px;
            color: #1a1a2e;
            margin-bottom: 2px;
        }
        
        .admin-info p {
            font-size: 12px;
            color: #6b7280;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .stat-icon.primary { background: linear-gradient(135deg, #dc2626, #991b1b); color: white; }
        .stat-icon.success { background: linear-gradient(135deg, #10b981, #059669); color: white; }
        .stat-icon.warning { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
        .stat-icon.info { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; }
        .stat-icon.purple { background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; }
        .stat-icon.teal { background: linear-gradient(135deg, #14b8a6, #0d9488); color: white; }
        
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 4px;
        }
        
        .stat-label {
            font-size: 13px;
            color: #6b7280;
            font-weight: 500;
        }
        
        .stat-trend {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid #f3f4f6;
        }
        
        .stat-trend.up { color: #10b981; }
        .stat-trend.down { color: #ef4444; }
        
        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
        }
        
        .content-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h3 {
            font-size: 16px;
            color: #1a1a2e;
            font-weight: 600;
        }
        
        .card-body {
            padding: 24px;
        }
        
        /* Activity Feed */
        .activity-list {
            list-style: none;
        }
        
        .activity-item {
            padding: 16px 0;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            gap: 12px;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 14px;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-title {
            font-size: 14px;
            color: #1a1a2e;
            margin-bottom: 2px;
        }
        
        .activity-time {
            font-size: 12px;
            color: #9ca3af;
        }
        
        /* Quick Actions */
        .quick-actions {
            display: grid;
            gap: 12px;
        }
        
        .action-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            text-decoration: none;
            color: #1a1a2e;
            transition: all 0.2s;
        }
        
        .action-btn:hover {
            background: #f3f4f6;
            border-color: #dc2626;
            transform: translateX(4px);
        }
        
        .action-btn i {
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #dc2626;
        }
        
        .action-info h4 {
            font-size: 14px;
            margin-bottom: 2px;
        }
        
        .action-info p {
            font-size: 12px;
            color: #6b7280;
        }
        
        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }
            
            .admin-main {
                margin-left: 0;
                width: 100%;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="admin-main">
            <!-- Access Denied Message -->
            <?php if (isset($_GET['error']) && $_GET['error'] === 'access_denied'): ?>
                <div style="background: #fee2e2; border: 1px solid #ef4444; color: #991b1b; padding: 15px; border-radius: 8px; margin: 20px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 20px;"></i>
                    <div>
                        <strong>Access Denied</strong>
                        <p style="margin: 5px 0 0 0; font-size: 14px;">You don't have permission to access that page. Please contact your administrator if you need access.</p>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Header -->
            <div class="admin-header">
                <h2>Dashboard Overview</h2>
                <div class="admin-user">
                    <div class="admin-avatar">
                        <?= strtoupper(substr($admin['first_name'], 0, 1) . substr($admin['last_name'], 0, 1)) ?>
                    </div>
                    <div class="admin-info">
                        <h4><?= htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']) ?></h4>
                        <p><?= htmlspecialchars($admin_role_display) ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value"><?= number_format($totalJobSeekers) ?></div>
                            <div class="stat-label">Job Seekers</div>
                        </div>
                        <div class="stat-icon primary">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-trend up">
                        <i class="fas fa-arrow-up"></i>
                        <span>+<?= $newUsersThisMonth ?> this month</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value"><?= number_format($totalEmployers) ?></div>
                            <div class="stat-label">Employers</div>
                        </div>
                        <div class="stat-icon success">
                            <i class="fas fa-building"></i>
                        </div>
                    </div>
                    <div class="stat-trend up">
                        <i class="fas fa-arrow-up"></i>
                        <span>Active companies</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value"><?= number_format($activeJobs) ?></div>
                            <div class="stat-label">Active Jobs</div>
                        </div>
                        <div class="stat-icon warning">
                            <i class="fas fa-briefcase"></i>
                        </div>
                    </div>
                    <div class="stat-trend up">
                        <i class="fas fa-arrow-up"></i>
                        <span>+<?= $newJobsThisMonth ?> this month</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value"><?= number_format($totalApplications) ?></div>
                            <div class="stat-label">Applications</div>
                        </div>
                        <div class="stat-icon info">
                            <i class="fas fa-file-alt"></i>
                        </div>
                    </div>
                    <div class="stat-trend">
                        <span><?= $newApplications ?> new applications</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value">₦<?= number_format($totalRevenue) ?></div>
                            <div class="stat-label">Total Revenue</div>
                        </div>
                        <div class="stat-icon purple">
                            <i class="fas fa-naira-sign"></i>
                        </div>
                    </div>
                    <div class="stat-trend up">
                        <span><?= $completedTransactions ?> transactions</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value"><?= number_format($totalCvs) ?></div>
                            <div class="stat-label">Total CVs</div>
                        </div>
                        <div class="stat-icon teal">
                            <i class="fas fa-file-pdf"></i>
                        </div>
                    </div>
                    <div class="stat-trend">
                        <span><?= $verifiedNIN ?> NIN verified</span>
                    </div>
                </div>
            </div>
            
            <!-- Content Grid -->
            <div class="content-grid">
                <!-- Recent Activity -->
                <div class="content-card">
                    <div class="card-header">
                        <h3>Recent Activity</h3>
                        <a href="activities.php" style="color: #dc2626; text-decoration: none; font-size: 14px;">View All</a>
                    </div>
                    <div class="card-body">
                        <ul class="activity-list">
                            <?php if (empty($activities)): ?>
                                <li class="activity-item">
                                    <div class="activity-content">
                                        <div class="activity-title">No recent activity</div>
                                    </div>
                                </li>
                            <?php else: ?>
                                <?php foreach (array_slice($activities, 0, 10) as $activity): ?>
                                    <li class="activity-item">
                                        <div class="activity-icon" style="background: #fef2f2; color: #dc2626;">
                                            <i class="fas fa-user-plus"></i>
                                        </div>
                                        <div class="activity-content">
                                            <div class="activity-title">
                                                <strong><?= htmlspecialchars($activity['name']) ?></strong> 
                                                registered as <?= ucfirst(str_replace('_', ' ', $activity['role'])) ?>
                                            </div>
                                            <div class="activity-time"><?= date('M d, Y H:i', strtotime($activity['activity_time'])) ?></div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="content-card">
                    <div class="card-header">
                        <h3>Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions">
                            <a href="users.php" class="action-btn">
                                <i class="fas fa-users"></i>
                                <div class="action-info">
                                    <h4>Manage Users</h4>
                                    <p>View and manage all users</p>
                                </div>
                            </a>
                            
                            <a href="jobs.php" class="action-btn">
                                <i class="fas fa-briefcase"></i>
                                <div class="action-info">
                                    <h4>Manage Jobs</h4>
                                    <p>Review and moderate jobs</p>
                                </div>
                            </a>
                            
                            <a href="transactions.php" class="action-btn">
                                <i class="fas fa-money-bill-wave"></i>
                                <div class="action-info">
                                    <h4>Transactions</h4>
                                    <p>View payment history</p>
                                </div>
                            </a>
                            
                            <a href="cvs.php" class="action-btn">
                                <i class="fas fa-file-alt"></i>
                                <div class="action-info">
                                    <h4>CV Manager</h4>
                                    <p>Manage uploaded CVs</p>
                                </div>
                            </a>
                            
                            <a href="reports.php" class="action-btn">
                                <i class="fas fa-chart-bar"></i>
                                <div class="action-info">
                                    <h4>View Reports</h4>
                                    <p>Analytics and insights</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Verification Stats -->
            <div class="stats-grid" style="margin-top: 24px;">
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value"><?= number_format($verifiedNIN) ?></div>
                            <div class="stat-label">NIN Verified Users</div>
                        </div>
                        <div class="stat-icon success">
                            <i class="fas fa-id-card"></i>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value"><?= number_format($verifiedCAC) ?></div>
                            <div class="stat-label">CAC Verified Companies</div>
                        </div>
                        <div class="stat-icon info">
                            <i class="fas fa-certificate"></i>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value"><?= number_format($verifiedPhones) ?></div>
                            <div class="stat-label">Phone Verified</div>
                        </div>
                        <div class="stat-icon purple">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
