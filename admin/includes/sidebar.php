<?php
// Get current page for active menu item
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="admin-sidebar">
    <div class="sidebar-header">
        <h1><i class="fas fa-shield-alt"></i> FindAJob Admin</h1>
        <p>Super Admin Panel</p>
    </div>
    
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
            <a href="admin-users.php" class="nav-link <?= $current_page == 'admin-users.php' ? 'active' : '' ?>">
                <i class="fas fa-user-shield"></i>
                <span>Admin Users</span>
            </a>
            <a href="roles.php" class="nav-link <?= $current_page == 'roles.php' ? 'active' : '' ?>">
                <i class="fas fa-user-tag"></i>
                <span>Roles & Permissions</span>
            </a>
            <a href="job-seekers.php" class="nav-link <?= $current_page == 'job-seekers.php' ? 'active' : '' ?>">
                <i class="fas fa-users"></i>
                <span>Job Seekers</span>
            </a>
            <a href="employers.php" class="nav-link <?= $current_page == 'employers.php' ? 'active' : '' ?>">
                <i class="fas fa-building"></i>
                <span>Employers</span>
            </a>
        </div>
        
        <!-- Content Management -->
        <div class="nav-section">
            <div class="nav-section-title">Content</div>
            <a href="jobs.php" class="nav-link <?= $current_page == 'jobs.php' ? 'active' : '' ?>">
                <i class="fas fa-briefcase"></i>
                <span>Jobs Manager</span>
            </a>
            <a href="cvs.php" class="nav-link <?= $current_page == 'cvs.php' ? 'active' : '' ?>">
                <i class="fas fa-file-alt"></i>
                <span>CV Manager</span>
            </a>
            <a href="ads.php" class="nav-link <?= $current_page == 'ads.php' ? 'active' : '' ?>">
                <i class="fas fa-ad"></i>
                <span>AD Manager</span>
            </a>
        </div>
        
        <!-- Finance -->
        <div class="nav-section">
            <div class="nav-section-title">Finance</div>
            <a href="transactions.php" class="nav-link <?= $current_page == 'transactions.php' ? 'active' : '' ?>">
                <i class="fas fa-money-bill-wave"></i>
                <span>Transactions</span>
            </a>
        </div>
        
        <!-- Tools -->
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
        
        <!-- Analytics -->
        <div class="nav-section">
            <div class="nav-section-title">Analytics</div>
            <a href="reports.php" class="nav-link <?= $current_page == 'reports.php' ? 'active' : '' ?>">
                <i class="fas fa-chart-line"></i>
                <span>Reports</span>
            </a>
        </div>
        
        <!-- Settings -->
        <div class="nav-section">
            <div class="nav-section-title">System</div>
            <a href="settings.php" class="nav-link <?= $current_page == 'settings.php' ? 'active' : '' ?>">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
            <a href="logout.php" class="nav-link">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </nav>
</div>
