<!-- Employer Dashboard Header -->
<header class="site-header">
    <div class="container">
        <nav class="site-nav" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: nowrap;">
            <a href="/findajob" class="site-logo" style="flex-shrink: 0;">
                <img src="/findajob/assets/images/logo_full.png" alt="FindAJob Nigeria" class="site-logo-img">
            </a>
            <div class="nav-links" style="display: flex; align-items: center; gap: 1rem; flex-wrap: nowrap; overflow: visible;">
                <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" style="text-decoration: none; font-weight: <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? '600' : '500'; ?>; white-space: nowrap; color: <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'var(--primary)' : 'var(--text-primary)'; ?>;">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                
                <!-- Jobs Dropdown -->
                <div class="nav-dropdown" style="position: relative; display: inline-block;">
                    <button class="nav-link dropdown-toggle" style="background: none; border: none; cursor: pointer; font-size: inherit; font-weight: 500; white-space: nowrap; color: var(--text-primary); padding: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-briefcase"></i> Jobs <i class="fas fa-chevron-down" style="font-size: 0.7rem;"></i>
                    </button>
                    <div class="dropdown-menu" style="display: none; position: absolute; top: calc(100% + 5px); left: 0; background: white; box-shadow: 0 4px 12px rgba(0,0,0,0.15); border-radius: 8px; min-width: 220px; z-index: 9999;">
                        <a href="post-job.php" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1.25rem; text-decoration: none; color: var(--text-primary); transition: background 0.2s;">
                            <i class="fas fa-plus-circle" style="color: var(--primary); width: 20px;"></i> Post New Job
                        </a>
                        <a href="active-jobs.php" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1.25rem; text-decoration: none; color: var(--text-primary); transition: background 0.2s;">
                            <i class="fas fa-list" style="color: var(--primary); width: 20px;"></i> Active Jobs
                        </a>
                        <a href="<?php echo isset($isPro) && $isPro ? 'private-offers.php' : '../payment/plans.php'; ?>" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1.25rem; text-decoration: none; color: var(--text-primary); transition: background 0.2s; position: relative;">
                            <i class="fas fa-lock" style="color: var(--primary); width: 20px;"></i> 
                            <span>Private Offers</span>
                            <?php if (isset($isPro) && !$isPro): ?>
                            <span style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; font-size: 0.6rem; padding: 2px 5px; border-radius: 8px; font-weight: 700; margin-left: auto;">PRO</span>
                            <?php endif; ?>
                        </a>
                        <div style="height: 1px; background: #e5e7eb; margin: 0.5rem 0;"></div>
                        <a href="internship-management.php" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1.25rem; text-decoration: none; color: var(--text-primary); transition: background 0.2s;">
                            <i class="fas fa-graduation-cap" style="color: var(--primary); width: 20px;"></i> Internship Management
                        </a>
                    </div>
                </div>
                
                <!-- Candidates Dropdown -->
                <div class="nav-dropdown" style="position: relative; display: inline-block;">
                    <button class="nav-link dropdown-toggle" style="background: none; border: none; cursor: pointer; font-size: inherit; font-weight: 500; white-space: nowrap; color: var(--text-primary); padding: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-users"></i> Candidates <i class="fas fa-chevron-down" style="font-size: 0.7rem;"></i>
                    </button>
                    <div class="dropdown-menu" style="display: none; position: absolute; top: calc(100% + 5px); left: 0; background: white; box-shadow: 0 4px 12px rgba(0,0,0,0.15); border-radius: 8px; min-width: 200px; z-index: 9999;">
                        <a href="all-applications.php" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1.25rem; text-decoration: none; color: var(--text-primary); transition: background 0.2s;">
                            <i class="fas fa-inbox" style="color: var(--primary); width: 20px;"></i> All Applications
                        </a>
                        <a href="applicants.php" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1.25rem; text-decoration: none; color: var(--text-primary); transition: background 0.2s;">
                            <i class="fas fa-user-tie" style="color: var(--primary); width: 20px;"></i> Browse Applicants
                        </a>
                    </div>
                </div>
                
                <a href="analytics.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : ''; ?>" style="text-decoration: none; font-weight: <?php echo basename($_SERVER['PHP_SELF']) == 'analytics.php' ? '600' : '500'; ?>; white-space: nowrap; color: <?php echo basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'var(--primary)' : 'var(--text-primary)'; ?>;">
                    <i class="fas fa-chart-line"></i> Analytics
                </a>
                
                <a href="mini-jobsite-settings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'mini-jobsite-settings.php' ? 'active' : ''; ?>" style="text-decoration: none; font-weight: <?php echo basename($_SERVER['PHP_SELF']) == 'mini-jobsite-settings.php' ? '600' : '500'; ?>; white-space: nowrap; color: <?php echo basename($_SERVER['PHP_SELF']) == 'mini-jobsite-settings.php' ? 'var(--primary)' : 'var(--text-primary)'; ?>;">
                    <i class="fas fa-globe"></i> Mini Jobsite
                </a>
                
                <!-- Account Dropdown -->
                <div class="nav-dropdown" style="position: relative; margin-left: auto; display: inline-block;">
                    <button class="nav-link dropdown-toggle" style="background: none; border: none; cursor: pointer; font-size: inherit; font-weight: 500; white-space: nowrap; color: var(--text-primary); padding: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user['provider_first_name'] ?? $user['first_name'] ?? 'User'); ?> <i class="fas fa-chevron-down" style="font-size: 0.7rem;"></i>
                    </button>
                    <div class="dropdown-menu" style="display: none; position: absolute; top: calc(100% + 5px); right: 0; background: white; box-shadow: 0 4px 12px rgba(0,0,0,0.15); border-radius: 8px; min-width: 180px; z-index: 9999;">
                        <a href="profile.php" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1.25rem; text-decoration: none; color: var(--text-primary); transition: background 0.2s;">
                            <i class="fas fa-user" style="color: var(--primary); width: 20px;"></i> My Profile
                        </a>
                        <div style="height: 1px; background: #e5e7eb; margin: 0.5rem 0;"></div>
                        <a href="../auth/logout.php" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1.25rem; text-decoration: none; color: #dc2626; transition: background 0.2s;">
                            <i class="fas fa-sign-out-alt" style="width: 20px;"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </nav>
    </div>
</header>

<style>
.site-header {
    position: relative;
    z-index: 100;
}
.site-nav {
    position: relative;
}
.nav-links {
    position: relative;
}
.nav-dropdown {
    position: relative !important;
}
.dropdown-menu {
    position: absolute !important;
    z-index: 9999 !important;
}
.dropdown-menu a:hover {
    background: #f9fafb;
}
.dropdown-toggle:hover {
    color: var(--primary) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle dropdown toggles
    const dropdowns = document.querySelectorAll('.nav-dropdown');
    
    dropdowns.forEach(dropdown => {
        const toggle = dropdown.querySelector('.dropdown-toggle');
        const menu = dropdown.querySelector('.dropdown-menu');
        
        if (toggle && menu) {
            // Toggle on click
            toggle.addEventListener('click', function(e) {
                e.stopPropagation();
                
                // Close all other dropdowns
                dropdowns.forEach(other => {
                    if (other !== dropdown) {
                        const otherMenu = other.querySelector('.dropdown-menu');
                        if (otherMenu) {
                            otherMenu.style.display = 'none';
                        }
                    }
                });
                
                // Toggle current dropdown
                if (menu.style.display === 'block') {
                    menu.style.display = 'none';
                } else {
                    menu.style.display = 'block';
                }
            });
            
            // Show on hover
            dropdown.addEventListener('mouseenter', function() {
                menu.style.display = 'block';
            });
            
            dropdown.addEventListener('mouseleave', function() {
                menu.style.display = 'none';
            });
        }
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function() {
        dropdowns.forEach(dropdown => {
            const menu = dropdown.querySelector('.dropdown-menu');
            if (menu) {
                menu.style.display = 'none';
            }
        });
    });
});
</script>
