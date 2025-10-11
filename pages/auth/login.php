<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_type'] === 'job_seeker') {
        header('Location: ../user/dashboard.php');
    } else {
        header('Location: ../company/dashboard.php');
    }
    exit();
}

$page_title = 'Choose Login Type - FindAJob Nigeria';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="../../assets/images/icons/icon-192x192.svg">
    <link rel="alternate icon" href="../../assets/images/icons/icon-192x192.svg">
    <link rel="shortcut icon" href="../../assets/images/icons/icon-192x192.svg">
    
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/components.css">
    <style>
        .login-selector {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 2rem;
        }
        
        .selector-container {
            max-width: 800px;
            width: 100%;
            text-align: center;
        }
        
        .selector-header {
            margin-bottom: 3rem;
        }
        
        .selector-header h1 {
            font-size: 2.5rem;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }
        
        .selector-header p {
            font-size: 1.2rem;
            color: var(--text-secondary);
        }
        
        .login-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .login-option {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
            border: 2px solid transparent;
        }
        
        .login-option:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        }
        
        .login-option.jobseeker {
            border-color: var(--primary);
        }
        
        .login-option.jobseeker:hover {
            border-color: var(--primary-dark);
        }
        
        .login-option.employer {
            border-color: #1e40af;
        }
        
        .login-option.employer:hover {
            border-color: #1e3a8a;
        }
        
        .option-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .option-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }
        
        .option-description {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        
        .option-features {
            list-style: none;
            padding: 0;
            text-align: left;
        }
        
        .option-features li {
            padding: 0.25rem 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .option-features li::before {
            content: '•';
            color: var(--primary);
            margin-right: 0.5rem;
        }
        
        .employer .option-features li::before {
            color: #1e40af;
        }
        
        .or-divider {
            margin: 2rem 0;
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            .login-options {
                grid-template-columns: 1fr;
            }
            
            .selector-header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-selector">
        <div class="selector-container">
            <div class="selector-header">
                <a href="../../index.php" style="display: inline-flex; align-items: center; gap: 0.5rem; color: var(--primary); text-decoration: none; font-weight: 500; margin-bottom: 1rem; font-size: 1rem;">
                    ← Return to Home
                </a>
                <h1>Welcome to FindAJob Nigeria</h1>
                <p>Choose your account type to continue</p>
            </div>
            
            <div class="login-options">
                <a href="login-jobseeker.php" class="login-option jobseeker">
                    <div class="option-icon">👤</div>
                    <div class="option-title">Job Seeker</div>
                    <div class="option-description">
                        Looking for your next career opportunity? Access thousands of verified jobs.
                    </div>
                    <ul class="option-features">
                        <li>Browse verified job postings</li>
                        <li>Get AI-powered job matches</li>
                        <li>Manage multiple CVs</li>
                        <li>Track your applications</li>
                    </ul>
                </a>
                
                <a href="login-employer.php" class="login-option employer">
                    <div class="option-icon">🏢</div>
                    <div class="option-title">Employer</div>
                    <div class="option-description">
                        Ready to hire top talent? Connect with qualified candidates effortlessly.
                    </div>
                    <ul class="option-features">
                        <li>Post unlimited jobs</li>
                        <li>Search candidate profiles</li>
                        <li>Schedule interviews</li>
                        <li>Build your company page</li>
                    </ul>
                </a>
            </div>
            
            <div class="or-divider">New to FindAJob?</div>
            
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-bottom: 2rem;">
                <a href="register-jobseeker.php" style="color: var(--primary); text-decoration: none; font-weight: 500;">Create Job Seeker Account</a>
                <span style="color: var(--text-secondary);">|</span>
                <a href="register-employer.php" style="color: #1e40af; text-decoration: none; font-weight: 500;">Create Employer Account</a>
            </div>
            
            <div style="text-align: center; margin-top: 1rem;">
                <a href="../../index.php" style="display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background: white; border: 2px solid var(--primary); border-radius: 8px; color: var(--primary); text-decoration: none; font-weight: 500; transition: all 0.3s;">
                    🏠 Browse Jobs Without Login
                </a>
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
        <a href="../services/cv-creator.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">📄</div>
            <div class="app-bottom-nav-label">CV</div>
        </a>
        <a href="login.php" class="app-bottom-nav-item active">
            <div class="app-bottom-nav-icon">👤</div>
            <div class="app-bottom-nav-label">Login</div>
        </a>
    </nav>
    
    <script>
        // Add body class for bottom nav
        document.body.classList.add('has-bottom-nav');
    </script>
</body>
</html>