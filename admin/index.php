<?php
require_once '../config/database.php';
require_once '../config/session.php';

// Redirect if not admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: login.php');
    exit;
}

// Get dashboard statistics
try {
    // Total users count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE status != 'deleted'");
    $totalUsers = $stmt->fetchColumn();
    
    // Job seekers count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'job_seeker' AND status != 'deleted'");
    $jobSeekers = $stmt->fetchColumn();
    
    // Employers count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'employer' AND status != 'deleted'");
    $employers = $stmt->fetchColumn();
    
    // Total jobs count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM jobs WHERE STATUS != 'deleted'");
    $totalJobs = $stmt->fetchColumn();
    
    // Active jobs count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM jobs WHERE STATUS = 'active'");
    $activeJobs = $stmt->fetchColumn();
    
    // Job applications count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM job_applications");
    $totalApplications = $stmt->fetchColumn();
    
    // Recent users (last 7 days)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $recentUsers = $stmt->fetchColumn();
    
    // Recent jobs (last 7 days)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM jobs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $recentJobs = $stmt->fetchColumn();
    
    // Get recent activities for activity feed
    $stmt = $pdo->prepare("
        SELECT 'user_registration' as type, u.first_name, u.last_name, u.role, u.created_at as activity_time 
        FROM users u 
        WHERE u.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) 
        UNION ALL
        SELECT 'job_posted' as type, j.title as first_name, c.company_name as last_name, 'job' as role, j.created_at as activity_time 
        FROM jobs j 
        LEFT JOIN users c ON j.employer_id = c.id 
        WHERE j.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY activity_time DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $recentActivities = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Admin dashboard error: " . $e->getMessage());
    $totalUsers = $jobSeekers = $employers = $totalJobs = $activeJobs = $totalApplications = $recentUsers = $recentJobs = 0;
    $recentActivities = [];
}

$adminName = $_SESSION['admin_name'] ?? 'Admin';
$adminRole = $_SESSION['admin_role'] ?? 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FindAJob Nigeria</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #dc2626;
            --primary-light: #fecaca;
            --primary-dark: #991b1b;
            --secondary: #64748b;
            --accent: #059669;
            --warning: #d97706;
            --info: #2563eb;
            --background: #f8fafc;
            --surface: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #9ca3af;
            --border-color: #e2e8f0;
            --sidebar-width: 280px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--background);
            color: var(--text-primary);
            overflow-x: hidden;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(180deg, var(--surface) 0%, #fafafa 100%);
            border-right: 2px solid var(--border-color);
            z-index: 100;
            overflow-y: auto;
            transition: transform 0.3s ease;
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 2px solid var(--border-color);
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 1.3rem;
            font-weight: 800;
        }

        .sidebar-logo i {
            font-size: 2rem;
        }

        .sidebar-nav {
            padding: 1.5rem 0;
        }

        .nav-section {
            margin-bottom: 2rem;
        }

        .nav-section-title {
            padding: 0 1.5rem 0.75rem;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .nav-item {
            display: block;
            padding: 1rem 1.5rem;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .nav-item:hover {
            background: rgba(220, 38, 38, 0.05);
            color: var(--primary);
            border-left-color: var(--primary);
        }

        .nav-item.active {
            background: rgba(220, 38, 38, 0.1);
            color: var(--primary);
            border-left-color: var(--primary);
            font-weight: 600;
        }

        .nav-item i {
            width: 20px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }

        .header {
            background: var(--surface);
            padding: 1.5rem 2rem;
            border-bottom: 2px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }

        .header-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem 1rem;
            background: var(--background);
            border-radius: 12px;
            border: 2px solid var(--border-color);
        }

        .admin-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .logout-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .logout-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        /* Dashboard Content */
        .dashboard-content {
            padding: 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--surface);
            border-radius: 16px;
            padding: 2rem;
            border: 2px solid var(--border-color);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.1);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--text-primary);
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .stat-trend {
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .trend-up {
            color: var(--accent);
        }

        .trend-down {
            color: var(--primary);
        }

        /* Activity Feed */
        .activity-section {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        .section-card {
            background: var(--surface);
            border-radius: 16px;
            padding: 2rem;
            border: 2px solid var(--border-color);
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 0.75rem;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .activity-item:hover {
            background: var(--background);
            border-color: var(--primary);
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.9rem;
        }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .activity-time {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .quick-action {
            background: var(--surface);
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            text-decoration: none;
            color: var(--text-primary);
            transition: all 0.3s ease;
        }

        .quick-action:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .quick-action i {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--primary);
        }

        .quick-action h4 {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .quick-action p {
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
        }

        @media (max-width: 768px) {
            .dashboard-content {
                padding: 1rem;
            }
            
            .header {
                padding: 1rem;
            }
            
            .activity-section {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <i class="fas fa-shield-alt"></i>
                <div>
                    <div>FindAJob</div>
                    <div style="font-size: 0.8rem; opacity: 0.9;">Admin Portal</div>
                </div>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Overview</div>
                <a href="index.php" class="nav-item active">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
                <a href="analytics.php" class="nav-item">
                    <i class="fas fa-chart-line"></i>
                    Analytics
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">User Management</div>
                <a href="users.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    All Users
                </a>
                <a href="job-seekers.php" class="nav-item">
                    <i class="fas fa-user-tie"></i>
                    Job Seekers
                </a>
                <a href="employers.php" class="nav-item">
                    <i class="fas fa-building"></i>
                    Employers
                </a>
                <a href="verification.php" class="nav-item">
                    <i class="fas fa-check-circle"></i>
                    Verification
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Content Management</div>
                <a href="jobs.php" class="nav-item">
                    <i class="fas fa-briefcase"></i>
                    Jobs
                </a>
                <a href="applications.php" class="nav-item">
                    <i class="fas fa-file-alt"></i>
                    Applications
                </a>
                <a href="categories.php" class="nav-item">
                    <i class="fas fa-tags"></i>
                    Categories
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Financial</div>
                <a href="payments.php" class="nav-item">
                    <i class="fas fa-credit-card"></i>
                    Payments
                </a>
                <a href="subscriptions.php" class="nav-item">
                    <i class="fas fa-crown"></i>
                    Subscriptions
                </a>
                <a href="transactions.php" class="nav-item">
                    <i class="fas fa-receipt"></i>
                    Transactions
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">System</div>
                <a href="settings.php" class="nav-item">
                    <i class="fas fa-cogs"></i>
                    Settings
                </a>
                <a href="logs.php" class="nav-item">
                    <i class="fas fa-list-alt"></i>
                    Activity Logs
                </a>
                <a href="backup.php" class="nav-item">
                    <i class="fas fa-database"></i>
                    Backup
                </a>
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <header class="header">
            <h1 class="header-title">Dashboard Overview</h1>
            <div class="header-actions">
                <div class="admin-info">
                    <div class="admin-avatar">
                        <?php echo strtoupper(substr($adminName, 0, 1)); ?>
                    </div>
                    <div>
                        <div style="font-weight: 600;"><?php echo htmlspecialchars($adminName); ?></div>
                        <div style="font-size: 0.85rem; color: var(--text-muted);"><?php echo ucwords(str_replace('_', ' ', $adminRole)); ?></div>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </header>

        <div class="dashboard-content">
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value"><?php echo number_format($totalUsers); ?></div>
                            <div class="stat-label">Total Users</div>
                        </div>
                        <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-arrow-up"></i>
                        +<?php echo $recentUsers; ?> this week
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value"><?php echo number_format($jobSeekers); ?></div>
                            <div class="stat-label">Job Seekers</div>
                        </div>
                        <div class="stat-icon" style="background: linear-gradient(135deg, #059669 0%, #047857 100%);">
                            <i class="fas fa-user-tie"></i>
                        </div>
                    </div>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-percentage"></i>
                        <?php echo $totalUsers > 0 ? round(($jobSeekers / $totalUsers) * 100, 1) : 0; ?>% of users
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value"><?php echo number_format($employers); ?></div>
                            <div class="stat-label">Employers</div>
                        </div>
                        <div class="stat-icon" style="background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);">
                            <i class="fas fa-building"></i>
                        </div>
                    </div>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-percentage"></i>
                        <?php echo $totalUsers > 0 ? round(($employers / $totalUsers) * 100, 1) : 0; ?>% of users
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value"><?php echo number_format($totalJobs); ?></div>
                            <div class="stat-label">Total Jobs</div>
                        </div>
                        <div class="stat-icon" style="background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%);">
                            <i class="fas fa-briefcase"></i>
                        </div>
                    </div>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-arrow-up"></i>
                        +<?php echo $recentJobs; ?> this week
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value"><?php echo number_format($activeJobs); ?></div>
                            <div class="stat-label">Active Jobs</div>
                        </div>
                        <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                            <i class="fas fa-fire"></i>
                        </div>
                    </div>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-percentage"></i>
                        <?php echo $totalJobs > 0 ? round(($activeJobs / $totalJobs) * 100, 1) : 0; ?>% active
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value"><?php echo number_format($totalApplications); ?></div>
                            <div class="stat-label">Applications</div>
                        </div>
                        <div class="stat-icon" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);">
                            <i class="fas fa-file-alt"></i>
                        </div>
                    </div>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-calculator"></i>
                        <?php echo $activeJobs > 0 ? round($totalApplications / $activeJobs, 1) : 0; ?> avg per job
                    </div>
                </div>
            </div>

            <!-- Activity and Quick Actions -->
            <div class="activity-section">
                <div class="section-card">
                    <h3 class="section-title">
                        <i class="fas fa-clock"></i>
                        Recent Activity
                    </h3>
                    
                    <?php if (empty($recentActivities)): ?>
                        <div style="text-align: center; padding: 2rem; color: var(--text-muted);">
                            <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                            <p>No recent activity in the last 24 hours.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentActivities as $activity): ?>
                            <div class="activity-item">
                                <?php if ($activity['type'] === 'user_registration'): ?>
                                    <div class="activity-icon" style="background: var(--accent);">
                                        <i class="fas fa-user-plus"></i>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title">
                                            New <?php echo ucfirst($activity['role']); ?> Registration
                                        </div>
                                        <div class="activity-time">
                                            <?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?> • 
                                            <?php echo date('M j, Y g:i A', strtotime($activity['activity_time'])); ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="activity-icon" style="background: var(--info);">
                                        <i class="fas fa-briefcase"></i>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title">New Job Posted</div>
                                        <div class="activity-time">
                                            <?php echo htmlspecialchars($activity['first_name']); ?> by <?php echo htmlspecialchars($activity['last_name']); ?> • 
                                            <?php echo date('M j, Y g:i A', strtotime($activity['activity_time'])); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="section-card">
                    <h3 class="section-title">
                        <i class="fas fa-bolt"></i>
                        Quick Actions
                    </h3>
                    
                    <div class="quick-actions">
                        <a href="users.php" class="quick-action">
                            <i class="fas fa-users"></i>
                            <h4>Manage Users</h4>
                            <p>View and manage all platform users</p>
                        </a>
                        
                        <a href="jobs.php" class="quick-action">
                            <i class="fas fa-briefcase"></i>
                            <h4>Review Jobs</h4>
                            <p>Moderate job postings</p>
                        </a>
                        
                        <a href="verification.php" class="quick-action">
                            <i class="fas fa-check-circle"></i>
                            <h4>Verifications</h4>
                            <p>Handle ID verifications</p>
                        </a>
                        
                        <a href="analytics.php" class="quick-action">
                            <i class="fas fa-chart-bar"></i>
                            <h4>View Analytics</h4>
                            <p>Platform performance metrics</p>
                        </a>
                        
                        <a href="settings.php" class="quick-action">
                            <i class="fas fa-cogs"></i>
                            <h4>System Settings</h4>
                            <p>Configure platform settings</p>
                        </a>
                        
                        <a href="backup.php" class="quick-action">
                            <i class="fas fa-download"></i>
                            <h4>Backup Data</h4>
                            <p>Download system backups</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Mobile sidebar toggle
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }
        
        // Auto-refresh dashboard stats every 5 minutes
        setInterval(function() {
            location.reload();
        }, 300000);
        
        // Add loading animations
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.stat-card, .activity-item');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>