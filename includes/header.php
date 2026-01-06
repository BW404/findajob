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

// Get user profile picture if logged in
$user_avatar = null;
$user_nin_verified = false;
$user_first_name = $_SESSION['first_name'] ?? 'User';
$upcoming_interviews_count = 0;

if (isLoggedIn()) {
    try {
        $db_path = $header_base_path . 'config/database.php';
        if (file_exists($db_path)) {
            require_once $db_path;
            $userId = getCurrentUserId();
            
            if (isJobSeeker()) {
                // Get profile picture, NIN verification status, and current name from database
                $stmt = $pdo->prepare("
                    SELECT COALESCE(jsp.profile_picture, u.profile_picture) as profile_picture,
                           jsp.nin_verified,
                           u.first_name,
                           u.last_name
                    FROM users u 
                    LEFT JOIN job_seeker_profiles jsp ON u.id = jsp.user_id 
                    WHERE u.id = ?
                ");
                $stmt->execute([$userId]);
                $result = $stmt->fetch();
                
                // Get upcoming interviews count
                $interviewStmt = $pdo->prepare("
                    SELECT COUNT(*) 
                    FROM job_applications 
                    WHERE job_seeker_id = ? 
                    AND interview_date IS NOT NULL 
                    AND interview_date >= NOW()
                ");
                $interviewStmt->execute([$userId]);
                $upcoming_interviews_count = $interviewStmt->fetchColumn();
            } else {
                // Get logo from employer_profiles or users table
                $stmt = $pdo->prepare("
                    SELECT COALESCE(ep.company_logo, u.profile_picture) as profile_picture,
                           0 as nin_verified,
                           u.first_name,
                           u.last_name
                    FROM users u 
                    LEFT JOIN employer_profiles ep ON u.id = ep.user_id 
                    WHERE u.id = ?
                ");
                $stmt->execute([$userId]);
                $result = $stmt->fetch();
            }
            
            if ($result) {
                // Update first name from database (may have been updated by NIN verification)
                $user_first_name = $result['first_name'] ?? $_SESSION['first_name'];
                
                if (!empty($result['profile_picture'])) {
                    $user_avatar = $result['profile_picture'];
                    // Normalize avatar URL: if stored as relative path like "uploads/profile_pictures/..."
                    // prepend the app base path so it resolves correctly in the browser.
                    if (strpos($user_avatar, '/') === 0 || preg_match('#^https?://#i', $user_avatar)) {
                        $user_avatar_url = $user_avatar; // absolute path or full URL
                    } else {
                        $user_avatar_url = '/findajob/' . ltrim($user_avatar, '/');
                    }
                } else {
                    $user_avatar_url = null;
                }
                $user_nin_verified = (bool)($result['nin_verified'] ?? false);
            }
        }
    } catch (Exception $e) {
        // Silently fail - just use default avatar
        error_log("Header avatar fetch error: " . $e->getMessage());
    }
}
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
                            <li><a href="<?php echo $is_auth_page ? '../user/job-centres.php' : '/findajob/pages/user/job-centres.php'; ?>" class="nav-link">Job Centres</a></li>
                            <li><a href="<?php echo $is_auth_page ? '../user/private-offers.php' : '/findajob/pages/user/private-offers.php'; ?>" class="nav-link" style="position: relative;">
                                Private Offers
                                <span id="privateOffersNotificationJobSeeker" style="display: none; position: absolute; top: -5px; right: -10px; background: var(--primary); color: white; font-size: 0.7rem; padding: 2px 6px; border-radius: 10px;"></span>
                            </a></li>
                            <li><a href="<?php echo $is_auth_page ? '../user/interviews.php' : '/findajob/pages/user/interviews.php'; ?>" class="nav-link" style="position: relative;">
                                <i class="fas fa-video"></i> Interviews
                                <?php if ($upcoming_interviews_count > 0): ?>
                                <span style="position: absolute; top: -5px; right: -10px; background: #8b5cf6; color: white; font-size: 0.7rem; padding: 2px 6px; border-radius: 10px; font-weight: 700;">
                                    <?php echo $upcoming_interviews_count; ?>
                                </span>
                                <?php endif; ?>
                            </a></li>
                            <li><a href="<?php echo $is_auth_page ? '../user/applications.php' : '/findajob/pages/user/applications.php'; ?>" class="nav-link">My Applications</a></li>
                            <li><a href="<?php echo $is_auth_page ? '../user/profile.php' : '/findajob/pages/user/profile.php'; ?>" class="nav-link">Profile</a></li>
                        <?php else: ?>
                            <li><a href="<?php echo $is_auth_page ? '../company/dashboard.php' : '/findajob/pages/company/dashboard.php'; ?>" class="nav-link">Dashboard</a></li>
                            <li><a href="<?php echo $is_auth_page ? '../company/post-job.php' : '/findajob/pages/company/post-job.php'; ?>" class="nav-link">Post Job</a></li>
                            <li><a href="<?php echo $is_auth_page ? '../company/private-offers.php' : '/findajob/pages/company/private-offers.php'; ?>" class="nav-link" style="position: relative;">
                                Private Offers
                                <span id="privateOffersNotificationEmployer" style="display: none; position: absolute; top: -5px; right: -10px; background: var(--primary); color: white; font-size: 0.7rem; padding: 2px 6px; border-radius: 10px;"></span>
                            </a></li>
                            <li><a href="<?php echo $is_auth_page ? '../company/applicants.php' : '/findajob/pages/company/applicants.php'; ?>" class="nav-link">Applicants</a></li>
                            <li><a href="<?php echo $is_auth_page ? '../company/profile.php' : '/findajob/pages/company/profile.php'; ?>" class="nav-link">Company</a></li>
                        <?php endif; ?>
                        
                        <li class="nav-dropdown">
                            <a href="#" class="nav-link dropdown-toggle">
                                        <?php if (!empty($user_avatar_url)): ?>
                                            <img src="<?php echo htmlspecialchars($user_avatar_url); ?>" alt="Profile" class="nav-avatar">
                                <?php else: ?>
                                            <span class="nav-avatar-initials"><?php echo strtoupper(substr($user_first_name, 0, 1)); ?></span>
                                <?php endif; ?>
                                <span>
                                    <?php echo htmlspecialchars($user_first_name); ?>
                                    <?php if ($user_nin_verified): ?>
                                        <span class="nav-verified-badge" title="NIN Verified">✓</span>
                                    <?php endif; ?>
                                </span>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a href="<?php echo $is_auth_page ? '../user/profile.php' : '/findajob/pages/user/profile.php'; ?>">Settings</a></li>
                                <li><a href="<?php echo $is_auth_page ? '../payment/plans.php' : '/findajob/pages/payment/plans.php'; ?>">Subscription</a></li>
                                <li class="dropdown-divider"></li>
                                <li><a href="<?php echo $is_auth_page ? 'logout.php' : '/findajob/pages/auth/logout.php'; ?>">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li><a href="<?php echo $is_auth_page ? '../jobs/browse.php' : '/findajob/pages/jobs/browse.php'; ?>" class="nav-link">Browse Jobs</a></li>
                        <li><a href="<?php echo $is_auth_page ? '../user/job-centres.php' : '/findajob/pages/user/job-centres.php'; ?>" class="nav-link">Job Centres</a></li>
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

.nav-avatar-initials {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
}

.nav-verified-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 16px;
    height: 16px;
    background: #1877f2;
    border-radius: 50%;
    color: white;
    font-size: 10px;
    font-weight: bold;
    margin-left: 4px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
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