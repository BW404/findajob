<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'config/constants.php';

// If already logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    if (isEmployer()) {
        header('Location: /findajob/pages/company/dashboard.php');
    } else {
        header('Location: /findajob/pages/user/dashboard.php');
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo SITE_NAME; ?> - Find Your Dream Job in Nigeria</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <meta name="description" content="Find your dream job in Nigeria. Connect with top employers, browse thousands of job opportunities, and build your career with FindAJob Nigeria.">
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/findajob/assets/images/icons/icon-192x192.svg">
    <link rel="alternate icon" href="/findajob/assets/images/icons/icon-192x192.svg">
    <link rel="shortcut icon" href="/findajob/assets/images/icons/icon-192x192.svg">
    
    <!-- PWA Meta Tags -->
    <link rel="manifest" href="/findajob/manifest.json">
    <meta name="theme-color" content="#dc2626">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="FindAJob">
    <link rel="apple-touch-icon" href="/findajob/assets/images/icons/icon-192x192.svg">
    
    <!-- Android specific -->
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="FindAJob">
    
    <!-- Windows specific -->
    <meta name="msapplication-TileColor" content="#dc2626">
    <meta name="msapplication-TileImage" content="/findajob/assets/images/icons/icon-192x192.svg">
</head>
<body>
    <header class="site-header">
        <div class="container">
            <nav class="site-nav">
                <a href="/" class="site-logo">
                    <img src="/findajob/assets/images/logo_full.png" alt="FindAJob Nigeria" class="site-logo-img">
                </a>
                <div class="nav-actions">
                    <?php if ($_SERVER['SERVER_NAME'] === 'localhost'): ?>
                        <a href="temp_mail.php" target="_blank" class="btn btn-secondary dev-email-btn">📧 Dev Emails</a>
                    <?php endif; ?>
                    <a href="pages/auth/login.php" class="btn btn-outline">Login</a>
                    <a href="pages/auth/register.php" class="btn btn-primary">Get Started</a>
                </div>
            </nav>
        </div>
    </header>

    <main>
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="container text-center">
                <h1 class="hero-title">
                    Find Your Dream Job in Nigeria
                </h1>
                <p class="hero-subtitle">
                    Connect with top employers, discover opportunities, and build your career
                </p>
                
                <!-- Job Search Form -->
                <div class="search-form-container">
                    <form action="pages/jobs/browse.php" method="GET" class="job-search-form">
                        <div class="search-inputs">
                            <input type="text" name="q" placeholder="Job title, keywords, or company" 
                                   class="search-input">
                            <input type="text" name="location" placeholder="Location" 
                                   class="search-input">
                        </div>
                        <button type="submit" class="btn btn-primary search-btn">
                            Search Jobs
                        </button>
                    </form>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section style="padding: 4rem 0; background: white;">
            <div class="container">
                <h2 style="text-align: center; font-size: 2.5rem; margin-bottom: 3rem; color: var(--text-primary);">
                    Why Choose FindAJob Nigeria?
                </h2>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                    <div class="auth-card" style="text-align: center;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">🔒</div>
                        <h3 style="color: var(--primary); margin-bottom: 1rem;">Secure & Verified</h3>
                        <p>All employers and job seekers are verified through NIN/BVN for your safety and security.</p>
                    </div>
                    
                    <div class="auth-card" style="text-align: center;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">🤖</div>
                        <h3 style="color: var(--primary); margin-bottom: 1rem;">AI-Powered Matching</h3>
                        <p>Our smart algorithm matches you with relevant opportunities based on your skills and preferences.</p>
                    </div>
                    
                    <div class="auth-card" style="text-align: center;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">💼</div>
                        <h3 style="color: var(--primary); margin-bottom: 1rem;">Multiple Job Types</h3>
                        <p>Find permanent, contract, temporary, internship, and NYSC placement opportunities.</p>
                    </div>
                    
                    <div class="auth-card" style="text-align: center;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">📝</div>
                        <h3 style="color: var(--primary); margin-bottom: 1rem;">CV Builder</h3>
                        <p>Create professional CVs with our AI-powered builder and professional writing services.</p>
                    </div>
                    
                    <div class="auth-card" style="text-align: center;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">🎓</div>
                        <h3 style="color: var(--primary); margin-bottom: 1rem;">Career Guidance</h3>
                        <p>Access training videos, self-employment guides, and career development resources.</p>
                    </div>
                    
                    <div class="auth-card" style="text-align: center;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">🏢</div>
                        <h3 style="color: var(--primary); margin-bottom: 1rem;">Employer Mini-Sites</h3>
                        <p>Companies get their own branded mini-websites to showcase their culture and opportunities.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section style="background: var(--background); padding: 4rem 0;">
            <div class="container text-center">
                <h2 style="font-size: 2.5rem; margin-bottom: 1rem; color: var(--text-primary);">
                    Ready to Start Your Journey?
                </h2>
                <p style="font-size: 1.125rem; color: var(--text-secondary); margin-bottom: 2rem;">
                    Join thousands of Nigerians who have found their dream jobs through our platform
                </p>
                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="pages/auth/register.php?type=job_seeker" class="btn btn-primary" style="padding: 1rem 2rem; font-size: 1.125rem;">
                        I'm Looking for a Job
                    </a>
                    <a href="pages/auth/register.php?type=employer" class="btn btn-outline" style="padding: 1rem 2rem; font-size: 1.125rem;">
                        I'm Hiring Talent
                    </a>
                </div>
            </div>
        </section>
    </main>

    <footer style="background: var(--text-primary); color: white; padding: 2rem 0;">
        <div class="container text-center">
            <p>&copy; 2025 <?php echo SITE_NAME; ?>. All rights reserved.</p>
            <p style="margin-top: 0.5rem; opacity: 0.8;">
                Connecting Nigerian talent with opportunities
            </p>
        </div>
    </footer>
    
    <!-- PWA Install Prompt -->
    <div id="pwa-install-prompt" class="pwa-install-prompt">
        <div class="pwa-install-content">
            <div class="pwa-install-icon">📱</div>
            <div class="pwa-install-text">
                <div class="pwa-install-title">Install FindAJob App</div>
                <div class="pwa-install-subtitle">Get quick access to jobs on your phone</div>
            </div>
            <div class="pwa-install-actions">
                <button id="pwa-install-btn" class="btn btn-primary btn-sm">Install</button>
                <button class="btn btn-secondary btn-sm" onclick="document.getElementById('pwa-install-prompt').style.display='none'">Not Now</button>
            </div>
        </div>
    </div>
    
    <!-- Bottom Navigation for Mobile -->
    <nav class="app-bottom-nav">
        <a href="/findajob/" class="app-bottom-nav-item active">
            <div class="app-bottom-nav-icon">🏠</div>
            <div class="app-bottom-nav-label">Home</div>
        </a>
        <a href="/findajob/pages/jobs/browse.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">🔍</div>
            <div class="app-bottom-nav-label">Jobs</div>
        </a>
        <a href="/findajob/pages/services/cv-creator.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">📄</div>
            <div class="app-bottom-nav-label">CV</div>
        </a>
        <a href="/findajob/pages/auth/login.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">👤</div>
            <div class="app-bottom-nav-label">Profile</div>
        </a>
    </nav>
    
    <!-- PWA Scripts -->
    <script src="assets/js/pwa.js"></script>
    <script>
        // Add body class for bottom nav
        document.body.classList.add('has-bottom-nav');
        
        // Simple install app function
        function installApp() {
            if (window.pwaManager) {
                window.pwaManager.installApp();
            }
        }
    </script>
</body>
</html>