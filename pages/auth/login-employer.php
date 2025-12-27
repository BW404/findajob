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

$page_title = 'Employer Login - FindAJob Nigeria';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/components.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
        }
        
        .login-left {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            color: white;
        }
        
        .login-content {
            max-width: 500px;
            text-align: center;
        }
        
        .login-content h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }
        
        .login-content p {
            font-size: 1.2rem;
            opacity: 0.9;
            line-height: 1.6;
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
            margin: 2rem 0;
            text-align: left;
        }
        
        .feature-list li {
            padding: 0.5rem 0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .feature-list li::before {
            content: '✓';
            background: rgba(255,255,255,0.2);
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .login-right {
            flex: 1;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .login-form {
            width: 100%;
            max-width: 400px;
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .form-header h2 {
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .form-header p {
            color: var(--text-secondary);
        }
        
        .user-type-badge {
            display: inline-block;
            background: #dbeafe;
            color: #1e40af;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #1e40af;
        }
        
        .btn-login {
            width: 100%;
            background: #1e40af;
            color: white;
            padding: 0.75rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-bottom: 1rem;
        }
        
        .btn-login:hover {
            background: #1e3a8a;
        }
        
        .btn-login:disabled {
            background: #94a3b8;
            cursor: not-allowed;
        }
        
        .form-links {
            text-align: center;
            margin-top: 1rem;
        }
        
        .form-links a {
            color: #1e40af;
            text-decoration: none;
        }
        
        .form-links a:hover {
            text-decoration: underline;
        }
        
        .divider {
            margin: 1.5rem 0;
            text-align: center;
            color: var(--text-secondary);
            position: relative;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e2e8f0;
        }
        
        .divider span {
            background: white;
            padding: 0 1rem;
        }
        
        .switch-user-type {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            margin-top: 1rem;
        }
        
        .alert {
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }
        
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
            }
            
            .login-left {
                min-height: 40vh;
            }
            
            .login-content h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <div class="login-content">
                <h1>Hire Top Talent</h1>
                <p>Connect with qualified candidates and build your dream team with FindAJob Nigeria's employer platform.</p>
                
                <ul class="feature-list">
                    <li>Access verified candidate profiles</li>
                    <li>AI-powered candidate matching</li>
                    <li>Unlimited job postings (Pro)</li>
                    <li>Resume search and filtering</li>
                    <li>Interview scheduling tools</li>
                    <li>Company mini-site creation</li>
                </ul>
            </div>
        </div>
        
        <div class="login-right">
            <form class="login-form" id="loginForm">
                <div class="form-header">
                    <span class="user-type-badge">🏢 Employer</span>
                    <h2>Welcome Back!</h2>
                    <p>Access your recruitment dashboard</p>
                </div>
                
                <!-- Suspension Alert -->
                <?php if (isset($_GET['suspended']) || isset($_SESSION['suspension_message'])): ?>
                <div class="alert" style="background: #fee2e2; border: 2px solid #dc2626; color: #991b1b; padding: 1rem; margin-bottom: 1.5rem; border-radius: 8px;">
                    <strong style="display: block; margin-bottom: 0.5rem;">⚠️ Account Suspended</strong>
                    <p style="margin: 0; line-height: 1.6;">
                        <?php 
                        echo htmlspecialchars($_SESSION['suspension_message'] ?? 'Your account has been temporarily suspended.');
                        if (isset($_SESSION['suspension_expires'])) {
                            echo '<br><strong>Expires:</strong> ' . date('F j, Y g:i A', strtotime($_SESSION['suspension_expires']));
                        }
                        unset($_SESSION['suspension_message']);
                        unset($_SESSION['suspension_expires']);
                        ?>
                    </p>
                    <p style="margin: 0.75rem 0 0; font-size: 0.9rem;">
                        Please contact admin at <strong>support@findajob.com.ng</strong> for assistance.
                    </p>
                </div>
                <?php endif; ?>
                
                <div id="alertContainer"></div>
                
                <div class="form-group">
                    <label for="email">Company Email</label>
                    <input type="email" id="email" name="email" required placeholder="Enter company email">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Enter your password">
                </div>
                
                <button type="submit" class="btn-login" id="loginBtn">Access Dashboard</button>
                
                <div class="form-links">
                    <a href="reset.php">Forgot your password?</a>
                </div>
                
                <div class="divider">
                    <span>New to FindAJob?</span>
                </div>
                
                <div class="form-links">
                    <a href="register-employer.php">Create Employer Account</a>
                </div>
                
                <div class="switch-user-type">
                    <p>Looking for a job?</p>
                    <a href="login-jobseeker.php">Switch to Job Seeker Login</a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const loginBtn = document.getElementById('loginBtn');
            const alertContainer = document.getElementById('alertContainer');
            
            // Clear previous alerts
            alertContainer.innerHTML = '';
            
            // Show loading state
            loginBtn.textContent = 'Signing In...';
            loginBtn.disabled = true;
            
            const formData = new FormData();
            formData.append('action', 'login');
            formData.append('email', document.getElementById('email').value);
            formData.append('password', document.getElementById('password').value);
            formData.append('user_type', 'employer');
            
            try {
                const response = await fetch('../../api/auth.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Redirect to company dashboard
                    window.location.href = '../company/dashboard.php';
                } else {
                    // Show error
                    alertContainer.innerHTML = `
                        <div class="alert alert-error">
                            ${result.message}
                        </div>
                    `;
                }
            } catch (error) {
                alertContainer.innerHTML = `
                    <div class="alert alert-error">
                        Login failed. Please try again.
                    </div>
                `;
            } finally {
                // Reset button
                loginBtn.textContent = 'Access Dashboard';
                loginBtn.disabled = false;
            }
        });
    </script>
    
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
        <a href="../company/post-job.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">📝</div>
            <div class="app-bottom-nav-label">Post Job</div>
        </a>
        <a href="login-employer.php" class="app-bottom-nav-item active">
            <div class="app-bottom-nav-icon">🏢</div>
            <div class="app-bottom-nav-label">Login</div>
        </a>
    </nav>
    
    <script>
        // Add body class for bottom nav
        document.body.classList.add('has-bottom-nav');
    </script>
</body>
</html>