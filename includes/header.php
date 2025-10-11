<?php
/**
 * FindAJob Nigeria - Common Header
 * Shared header component for all pages
 */

// Include required dependencies
$header_base_path = dirname(__DIR__) . '/';
if (file_exists($header_base_path . 'config/session.php')) {
    require_once $header_base_path . 'config/session.php';
}
if (file_exists($header_base_path . 'includes/functions.php')) {
    require_once $header_base_path . 'includes/functions.php';
}

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current page info
$current_page = basename($_SERVER['PHP_SELF']);
$is_auth_page = strpos($_SERVER['REQUEST_URI'], '/auth/') !== false;
?>
<header class="main-header">
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <a href="<?php echo $is_auth_page ? '../../index.php' : '/findajob/index.php'; ?>" class="brand-link">
                    <img src="<?php echo $is_auth_page ? '../../assets/images/logo_full.png' : '/findajob/assets/images/logo_full.png'; ?>" alt="FindAJob Nigeria" class="brand-logo">
                </a>
            </div>
            
            <div class="nav-menu" id="navMenu">
                <ul class="nav-links">
                    <?php if (isLoggedIn()): ?>
                        <?php if (isJobSeeker()): ?>
                            <li><a href="<?php echo $is_auth_page ? '../user/dashboard.php' : '/findajob/pages/user/dashboard.php'; ?>" class="nav-link">Dashboard</a></li>
                            <li><a href="<?php echo $is_auth_page ? '../jobs/browse.php' : '/findajob/pages/jobs/browse.php'; ?>" class="nav-link">Browse Jobs</a></li>
                            <li><a href="<?php echo $is_auth_page ? '../user/applications.php' : '/findajob/pages/user/applications.php'; ?>" class="nav-link">My Applications</a></li>
                            <li><a href="<?php echo $is_auth_page ? '../user/profile.php' : '/findajob/pages/user/profile.php'; ?>" class="nav-link">Profile</a></li>
                        <?php else: ?>
                            <li><a href="<?php echo $is_auth_page ? '../company/dashboard.php' : '/findajob/pages/company/dashboard.php'; ?>" class="nav-link">Dashboard</a></li>
                            <li><a href="<?php echo $is_auth_page ? '../company/post-job.php' : '/findajob/pages/company/post-job.php'; ?>" class="nav-link">Post Job</a></li>
                            <li><a href="<?php echo $is_auth_page ? '../company/applicants.php' : '/findajob/pages/company/applicants.php'; ?>" class="nav-link">Applicants</a></li>
                            <li><a href="<?php echo $is_auth_page ? '../company/profile.php' : '/findajob/pages/company/profile.php'; ?>" class="nav-link">Company</a></li>
                        <?php endif; ?>
                        
                        <li class="nav-dropdown">
                            <a href="#" class="nav-link dropdown-toggle">
                                <img src="<?php echo $is_auth_page ? '../../assets/images/default-avatar.png' : '/findajob/assets/images/default-avatar.png'; ?>" alt="Profile" class="nav-avatar">
                                <span><?php echo htmlspecialchars($_SESSION['first_name']); ?></span>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a href="<?php echo $is_auth_page ? '../user/profile.php' : '/findajob/pages/user/profile.php'; ?>">Settings</a></li>
                                <li><a href="<?php echo $is_auth_page ? '../user/subscription.php' : '/findajob/pages/user/subscription.php'; ?>">Subscription</a></li>
                                <li class="dropdown-divider"></li>
                                <li><a href="<?php echo $is_auth_page ? '../../api/auth.php?action=logout' : '/findajob/api/auth.php?action=logout'; ?>">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li><a href="<?php echo $is_auth_page ? '../jobs/browse.php' : '/findajob/pages/jobs/browse.php'; ?>" class="nav-link">Browse Jobs</a></li>
                        <li><a href="<?php echo $is_auth_page ? '../services/cv-creator.php' : '/findajob/pages/services/cv-creator.php'; ?>" class="nav-link">CV Builder</a></li>
                        <li><a href="<?php echo $is_auth_page ? 'login.php' : '/findajob/pages/auth/login.php'; ?>" class="nav-link">Sign In</a></li>
                        <li><a href="<?php echo $is_auth_page ? 'register-jobseeker.php' : '/findajob/pages/auth/register-jobseeker.php'; ?>" class="btn btn-register nav-cta">Get Started</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <div class="nav-toggle" id="navToggle">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </div>
        </div>
    </nav>
</header>

<style>
.main-header {
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.navbar {
    padding: 1rem 0;
}

.nav-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.nav-brand {
    display: flex;
    align-items: center;
}

.brand-link {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
    color: var(--text-primary);
}

.brand-logo {
    height: 40px;
    width: auto;
    max-width: 180px;
}

.brand-text {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary);
}

.nav-menu {
    display: flex;
    align-items: center;
}

.nav-links {
    display: flex;
    align-items: center;
    gap: 2rem;
    list-style: none;
    margin: 0;
    padding: 0;
}

.nav-link {
    color: var(--text-primary);
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s;
}

.nav-link:hover {
    color: var(--primary);
}

.nav-cta {
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-size: 0.9rem;
}

.nav-dropdown {
    position: relative;
}

.dropdown-toggle {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.nav-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    border-radius: 8px;
    padding: 0.5rem 0;
    min-width: 180px;
    list-style: none;
    margin: 0;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s;
}

.nav-dropdown:hover .dropdown-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown-menu li {
    margin: 0;
}

.dropdown-menu a {
    display: block;
    padding: 0.5rem 1rem;
    color: var(--text-primary);
    text-decoration: none;
    font-size: 0.9rem;
    transition: background-color 0.3s;
}

.dropdown-menu a:hover {
    background: var(--background);
    color: var(--primary);
}

.dropdown-divider {
    height: 1px;
    background: #e2e8f0;
    margin: 0.5rem 0;
}

.nav-toggle {
    display: none;
    flex-direction: column;
    cursor: pointer;
    gap: 4px;
}

.hamburger-line {
    width: 24px;
    height: 2px;
    background: var(--text-primary);
    transition: all 0.3s;
}

@media (max-width: 768px) {
    .nav-toggle {
        display: flex;
    }
    
    .nav-menu {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        padding: 1rem;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.3s;
    }
    
    .nav-menu.active {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }
    
    .nav-links {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .nav-link {
        padding: 0.5rem 0;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .dropdown-menu {
        position: static;
        opacity: 1;
        visibility: visible;
        transform: none;
        box-shadow: none;
        background: var(--background);
        margin-top: 0.5rem;
    }
}
</style>

<script>
// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');
    
    if (navToggle && navMenu) {
        navToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
        });
    }
});
</script>