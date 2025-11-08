<?php
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/constants.php';

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/main.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card register-card">
            <div class="auth-header register-header">
                <h1>Join <?php echo SITE_NAME; ?></h1>
                <p>Create your account and start your job search journey</p>
            </div>

            <form id="registerForm" method="POST">
                <!-- User Type Selection -->
                <div class="user-type-selector desktop">
                    <div class="user-type-option" data-type="job_seeker">
                        <input type="radio" id="job_seeker" name="user_type" value="job_seeker" required>
                        <div class="user-type-icon">👤</div>
                        <div class="user-type-label">Job Seeker</div>
                        <div class="user-type-description">Looking for opportunities</div>
                    </div>
                    <div class="user-type-option" data-type="employer">
                        <input type="radio" id="employer" name="user_type" value="employer" required>
                        <div class="user-type-icon">🏢</div>
                        <div class="user-type-label">Employer</div>
                        <div class="user-type-description">Hiring talented people</div>
                    </div>
                </div>

                <div class="register-form-container">
                    <!-- Left Column -->
                    <div class="register-form-left">
                        <!-- Company Name (for employers only) -->
                        <div class="form-group" id="companyNameGroup" style="display: none;">
                            <label for="company_name" class="form-label">Company Name <span style="color: red;">*</span></label>
                            <input type="text" id="company_name" name="company_name" class="form-input" placeholder="e.g., OpsMirror">
                            <small class="text-secondary">The name of your company/organization</small>
                        </div>

                        <div class="form-group">
                            <label for="first_name" class="form-label"><span id="firstNameLabel">First Name</span> <span style="color: red;">*</span></label>
                            <input type="text" id="first_name" name="first_name" class="form-input" required placeholder="e.g., Jalal Uddin">
                            <small class="text-secondary" id="firstNameHelp" style="display: none;">Your first name (company representative)</small>
                        </div>

                        <div class="form-group">
                            <label for="last_name" class="form-label"><span id="lastNameLabel">Last Name</span> <span style="color: red;">*</span></label>
                            <input type="text" id="last_name" name="last_name" class="form-input" required placeholder="e.g., Taj">
                            <small class="text-secondary" id="lastNameHelp" style="display: none;">Your last name (company representative)</small>
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label"><span id="emailLabel">Email Address</span> <span style="color: red;">*</span></label>
                            <input type="email" id="email" name="email" class="form-input" required placeholder="your.email@company.com">
                            <small class="text-secondary" id="emailHelp" style="display: none;">Company email for login and notifications</small>
                        </div>

                        <div class="form-group">
                            <label for="phone" class="form-label"><span id="phoneLabel">Phone Number</span> (Optional)</label>
                            <input type="tel" id="phone" name="phone" class="form-input" placeholder="+234 xxx xxx xxxx">
                            <small class="text-secondary" id="phoneHelp" style="display: none;">Representative's direct contact number</small>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="register-form-right">

                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" id="password" name="password" class="form-input" required minlength="8">
                            <small class="text-secondary">Must be at least 8 characters long</small>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                        </div>

                        <!-- Terms and Conditions -->
                        <div class="form-check">
                            <input type="checkbox" id="terms" name="terms" required>
                            <label for="terms">I agree to the <a href="#" target="_blank">Terms of Service</a> and <a href="#" target="_blank">Privacy Policy</a></label>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="register-submit-section">
                        <button type="submit" class="btn btn-register btn-block">Create Account</button>
                    </div>
                </div>
            </form>

            <div class="auth-footer">
                <p>Already have an account? <a href="login.php">Sign in here</a></p>
            </div>
        </div>
    </div>

    <script src="../../assets/js/auth.js"></script>
    <script>
        // Initialize user type selection
        document.addEventListener('DOMContentLoaded', function() {
            const userTypeOptions = document.querySelectorAll('.user-type-option');
            userTypeOptions.forEach(option => {
                option.addEventListener('click', function() {
                    // Remove selected class from all options
                    userTypeOptions.forEach(opt => opt.classList.remove('selected'));
                    
                    // Add selected class to clicked option
                    this.classList.add('selected');
                    
                    // Check the radio button
                    const radio = this.querySelector('input[type="radio"]');
                    radio.checked = true;
                    
                    // Show/hide company name field and update labels
                    const companyNameGroup = document.getElementById('companyNameGroup');
                    const companyNameInput = document.getElementById('company_name');
                    
                    // Get help text elements
                    const firstNameHelp = document.getElementById('firstNameHelp');
                    const lastNameHelp = document.getElementById('lastNameHelp');
                    const emailHelp = document.getElementById('emailHelp');
                    const phoneHelp = document.getElementById('phoneHelp');
                    
                    if (radio.value === 'employer') {
                        companyNameGroup.style.display = 'block';
                        companyNameInput.required = true;
                        
                        // Show employer-specific help text
                        firstNameHelp.style.display = 'block';
                        lastNameHelp.style.display = 'block';
                        emailHelp.style.display = 'block';
                        phoneHelp.style.display = 'block';
                    } else {
                        companyNameGroup.style.display = 'none';
                        companyNameInput.required = false;
                        
                        // Hide employer-specific help text
                        firstNameHelp.style.display = 'none';
                        lastNameHelp.style.display = 'none';
                        emailHelp.style.display = 'none';
                        phoneHelp.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>