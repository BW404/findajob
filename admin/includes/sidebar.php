<?php
// Get current page for active menu item
$current_page = basename($_SERVER['PHP_SELF']);

// Ensure session and permission functions are available
if (!function_exists('getCurrentUserId')) {
    require_once __DIR__ . '/../../config/session.php';
}
if (!function_exists('hasPermission')) {
    require_once __DIR__ . '/../../config/permissions.php';
}

// Get current admin's role
$current_user_id = getCurrentUserId();
$admin_role_name = 'Admin';
try {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT ar.role_name 
        FROM users u 
        LEFT JOIN admin_roles ar ON u.admin_role_id = ar.id 
        WHERE u.id = ?
    ");
    $stmt->execute([$current_user_id]);
    $role_data = $stmt->fetch();
    if ($role_data && $role_data['role_name']) {
        $admin_role_name = $role_data['role_name'];
    }
} catch (Exception $e) {
    error_log("Sidebar role fetch error: " . $e->getMessage());
}
?>
<div class="admin-sidebar">
    <div class="sidebar-header">
        <h1><i class="fas fa-shield-alt"></i> FindAJob Admin</h1>
        <p><?= htmlspecialchars($admin_role_name) ?> Panel</p>
    </div>
    
    <?php if (defined('MAINTENANCE_MODE_ACTIVE') && MAINTENANCE_MODE_ACTIVE): ?>
    <div style="background: #dc2626; color: white; padding: 12px 20px; margin: 0; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1);">
        <i class="fas fa-tools"></i> <strong>MAINTENANCE MODE</strong>
        <div style="font-size: 11px; margin-top: 4px; opacity: 0.9;">Site is under maintenance</div>
    </div>
    <?php endif; ?>
    
    <nav class="sidebar-nav">
        <!-- Dashboard -->
        <div class="nav-section">
            <div class="nav-section-title">Main</div>
            <a href="dashboard.php" class="nav-link <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </div>
        
        <!-- User Management -->
        <div class="nav-section">
            <div class="nav-section-title">User Management</div>
            <?php if (isSuperAdmin(getCurrentUserId())): ?>
            <a href="admin-users.php" class="nav-link <?= $current_page == 'admin-users.php' ? 'active' : '' ?>">
                <i class="fas fa-user-shield"></i>
                <span>Admin Users</span>
            </a>
            <a href="roles.php" class="nav-link <?= $current_page == 'roles.php' ? 'active' : '' ?>">
                <i class="fas fa-user-tag"></i>
                <span>Roles & Permissions</span>
            </a>
            <?php endif; ?>
            <?php if (hasPermission(getCurrentUserId(), 'view_job_seekers')): ?>
            <a href="job-seekers.php" class="nav-link <?= $current_page == 'job-seekers.php' ? 'active' : '' ?>">
                <i class="fas fa-users"></i>
                <span>Job Seekers</span>
            </a>
            <?php endif; ?>
            <?php if (hasPermission(getCurrentUserId(), 'view_employers')): ?>
            <a href="employers.php" class="nav-link <?= $current_page == 'employers.php' ? 'active' : '' ?>">
                <i class="fas fa-building"></i>
                <span>Employers</span>
            </a>
            <?php endif; ?>
        </div>
        
        <!-- Content -->
        <div class="nav-section">
            <div class="nav-section-title">Content</div>
            <?php if (hasPermission(getCurrentUserId(), 'view_jobs')): ?>
            <a href="jobs.php" class="nav-link <?= $current_page == 'jobs.php' ? 'active' : '' ?>">
                <i class="fas fa-briefcase"></i>
                <span>Jobs Manager</span>
            </a>
            <?php endif; ?>
            <?php if (hasPermission(getCurrentUserId(), 'view_cvs')): ?>
            <a href="cvs.php" class="nav-link <?= $current_page == 'cvs.php' ? 'active' : '' ?>">
                <i class="fas fa-file-alt"></i>
                <span>CV Manager</span>
            </a>
            <?php endif; ?>
            <a href="premium-cv-manager.php" class="nav-link <?= $current_page == 'premium-cv-manager.php' ? 'active' : '' ?>">
                <i class="fas fa-crown"></i>
                <span>Premium CV Requests</span>
            </a>
            <?php if (hasPermission(getCurrentUserId(), 'view_ads')): ?>
            <a href="ads.php" class="nav-link <?= $current_page == 'ads.php' ? 'active' : '' ?>">
                <i class="fas fa-ad"></i>
                <span>AD Manager</span>
            </a>
            <?php endif; ?>
        </div>
        
        <!-- Finance -->
        <?php if (hasPermission(getCurrentUserId(), 'view_transactions')): ?>
        <div class="nav-section">
            <div class="nav-section-title">Finance</div>
            <a href="transactions.php" class="nav-link <?= $current_page == 'transactions.php' ? 'active' : '' ?>">
                <i class="fas fa-money-bill-wave"></i>
                <span>Transactions</span>
            </a>
        </div>
        <?php endif; ?>
        
        <!-- Tools -->
        <?php if (hasPermission(getCurrentUserId(), 'manage_api') || isSuperAdmin(getCurrentUserId())): ?>
        <div class="nav-section">
            <div class="nav-section-title">Tools</div>
            <a href="scraper.php" class="nav-link <?= $current_page == 'scraper.php' ? 'active' : '' ?>">
                <i class="fas fa-spider"></i>
                <span>Data Scraper</span>
            </a>
            <a href="social-media.php" class="nav-link <?= $current_page == 'social-media.php' ? 'active' : '' ?>">
                <i class="fas fa-share-alt"></i>
                <span>Social Media</span>
            </a>
            <a href="api-manager.php" class="nav-link <?= $current_page == 'api-manager.php' ? 'active' : '' ?>">
                <i class="fas fa-code"></i>
                <span>API Manager</span>
            </a>
        </div>
        <?php endif; ?>
        
        <!-- Analytics -->
        <?php if (hasPermission(getCurrentUserId(), 'view_reports')): ?>
        <div class="nav-section">
            <div class="nav-section-title">Analytics</div>
            <a href="reports.php" class="nav-link <?= $current_page == 'reports.php' ? 'active' : '' ?>">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </a>
        </div>
        <?php endif; ?>
        
        <!-- Settings -->
        <div class="nav-section">
            <div class="nav-section-title">System</div>
            <?php if (hasAnyPermission(getCurrentUserId(), ['view_settings', 'edit_settings'])): ?>
            <a href="settings.php" class="nav-link <?= $current_page == 'settings.php' ? 'active' : '' ?>">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
            <?php endif; ?>
            <a href="logout.php" class="nav-link">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </nav>
</div>
