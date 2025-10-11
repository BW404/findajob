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

$page_title = 'Create Employer Account - FindAJob Nigeria';
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
        .register-container {
            min-height: 100vh;
            display: flex;
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
        }
        
        .register-left {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            color: white;
        }
        
        .register-content {
            max-width: 500px;
            text-align: center;
        }
        
        .register-content h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }
        
        .register-content p {
            font-size: 1.1rem;
            opacity: 0.9;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        
        .benefits-list {
            list-style: none;
            padding: 0;
            text-align: left;
        }
        
        .benefits-list li {
            padding: 0.75rem 0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .benefits-list li::before {
            content: '✓';
            background: rgba(255,255,255,0.2);
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .register-right {
            flex: 1;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            overflow-y: auto;
        }
        
        .register-form {
            width: 100%;
            max-width: 450px;
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
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
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
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #1e40af;
        }
        
        .btn-register {
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
        
        .btn-register:hover {
            background: #1e3a8a;
        }
        
        .btn-register:disabled {
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
        
        .alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #059669;
        }
        
        .password-requirements {
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-top: 0.5rem;
        }
        
        .password-requirements ul {
            margin: 0.5rem 0;
            padding-left: 1rem;
        }
        
        @media (max-width: 768px) {
            .register-container {
                flex-direction: column;
            }
            
            .register-left {
                min-height: 30vh;
            }
            
            .register-content h1 {
                font-size: 2rem;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-left">
            <div class="register-content">
                <h1>Build Your Dream Team</h1>
                <p>Connect with Nigeria's top talent and streamline your hiring process with our advanced recruitment platform.</p>
                
                <ul class="benefits-list">
                    <li>Access to 50,000+ verified candidates</li>
                    <li>AI-powered candidate matching</li>
                    <li>Advanced resume search and filtering</li>
                    <li>Integrated interview scheduling</li>
                    <li>Company branding and mini-sites</li>
                    <li>Analytics and hiring insights</li>
                </ul>
            </div>
        </div>
        
        <div class="register-right">
            <form class="register-form" id="registerForm">
                <div class="form-header">
                    <span class="user-type-badge">🏢 Employer Account</span>
                    <h2>Create Your Account</h2>
                    <p>Start hiring the best talent today</p>
                </div>
                
                <div id="alertContainer"></div>
                
                <div class="form-group">
                    <label for="company_name">Company Name *</label>
                    <input type="text" id="company_name" name="company_name" required placeholder="Your Company Ltd">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" required placeholder="John">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" required placeholder="Doe">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">Company Email *</label>
                    <input type="email" id="email" name="email" required placeholder="hr@yourcompany.com">
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number *</label>
                    <input type="tel" id="phone" name="phone" required placeholder="+234 800 000 0000">
                </div>
                
                <div class="form-group">
                    <label for="industry">Industry</label>
                    <select id="industry" name="industry">
                        <option value="">Select Industry</option>
                        <option value="technology">Technology</option>
                        <option value="banking">Banking & Finance</option>
                        <option value="oil_gas">Oil & Gas</option>
                        <option value="manufacturing">Manufacturing</option>
                        <option value="healthcare">Healthcare</option>
                        <option value="education">Education</option>
                        <option value="retail">Retail</option>
                        <option value="construction">Construction</option>
                        <option value="agriculture">Agriculture</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required placeholder="Create a strong password">
                    <div class="password-requirements">
                        <ul>
                            <li>At least 8 characters long</li>
                            <li>Include uppercase and lowercase letters</li>
                            <li>Include at least one number</li>
                        </ul>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm your password">
                </div>
                
                <button type="submit" class="btn-register" id="registerBtn">Create Employer Account</button>
                
                <div class="form-links">
                    <p>Already have an account? <a href="login-employer.php">Sign in here</a></p>
                </div>
                
                <div class="switch-user-type">
                    <p>Looking for a job?</p>
                    <a href="register-jobseeker.php">Create Job Seeker Account</a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const registerBtn = document.getElementById('registerBtn');
            const alertContainer = document.getElementById('alertContainer');
            
            // Clear previous alerts
            alertContainer.innerHTML = '';
            
            // Validate passwords match
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                alertContainer.innerHTML = `
                    <div class="alert alert-error">
                        Passwords do not match.
                    </div>
                `;
                return;
            }
            
            // Show loading state
            registerBtn.textContent = 'Creating Account...';
            registerBtn.disabled = true;
            
            const formData = new FormData();
            formData.append('action', 'register');
            formData.append('user_type', 'employer');
            formData.append('company_name', document.getElementById('company_name').value);
            formData.append('first_name', document.getElementById('first_name').value);
            formData.append('last_name', document.getElementById('last_name').value);
            formData.append('email', document.getElementById('email').value);
            formData.append('phone', document.getElementById('phone').value);
            formData.append('industry', document.getElementById('industry').value);
            formData.append('password', password);
            formData.append('confirm_password', confirmPassword);
            
            try {
                const response = await fetch('../../api/auth.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alertContainer.innerHTML = `
                        <div class="alert alert-success">
                            Employer account created successfully! Please check your email to verify your account.
                        </div>
                    `;
                    
                    // Clear form
                    document.getElementById('registerForm').reset();
                    
                    // Redirect to login after 3 seconds
                    setTimeout(() => {
                        window.location.href = 'login-employer.php';
                    }, 3000);
                } else {
                    let errorMessage = 'Registration failed. Please try again.';
                    
                    if (result.errors) {
                        if (typeof result.errors === 'object') {
                            errorMessage = Object.values(result.errors).join('<br>');
                        } else {
                            errorMessage = result.errors;
                        }
                    } else if (result.message) {
                        errorMessage = result.message;
                    }
                    
                    alertContainer.innerHTML = `
                        <div class="alert alert-error">
                            ${errorMessage}
                        </div>
                    `;
                }
            } catch (error) {
                alertContainer.innerHTML = `
                    <div class="alert alert-error">
                        Registration failed. Please try again.
                    </div>
                `;
            } finally {
                // Reset button
                registerBtn.textContent = 'Create Employer Account';
                registerBtn.disabled = false;
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
        <a href="register-employer.php" class="app-bottom-nav-item active">
            <div class="app-bottom-nav-icon">🏢</div>
            <div class="app-bottom-nav-label">Register</div>
        </a>
    </nav>
    
    <script>
        // Add body class for bottom nav
        document.body.classList.add('has-bottom-nav');
    </script>
</body>
</html>