// Authentication JavaScript
class Auth {
    constructor() {
        this.initializeAuth();
    }

    initializeAuth() {
        // Initialize form handlers
        this.initializeLoginForm();
        this.initializeRegisterForm();
        this.initializePasswordResetForm();
        this.initializeUserTypeSelector();
    }

    initializeLoginForm() {
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => this.handleLogin(e));
        }
    }

    initializeRegisterForm() {
        const registerForm = document.getElementById('registerForm');
        if (registerForm) {
            registerForm.addEventListener('submit', (e) => this.handleRegister(e));
            
            // Password confirmation validation
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            
            if (password && confirmPassword) {
                confirmPassword.addEventListener('input', () => {
                    if (password.value !== confirmPassword.value) {
                        this.showFieldError('confirm_password', 'Passwords do not match');
                    } else {
                        this.clearFieldError('confirm_password');
                    }
                });
            }
        }
    }

    initializePasswordResetForm() {
        const resetForm = document.getElementById('resetForm');
        if (resetForm) {
            resetForm.addEventListener('submit', (e) => this.handlePasswordReset(e));
        }

        const requestResetForm = document.getElementById('requestResetForm');
        if (requestResetForm) {
            requestResetForm.addEventListener('submit', (e) => this.handlePasswordResetRequest(e));
        }
    }

    initializeUserTypeSelector() {
        const userTypeOptions = document.querySelectorAll('.user-type-option');
        userTypeOptions.forEach(option => {
            option.addEventListener('click', () => {
                // Remove selected class from all options
                userTypeOptions.forEach(opt => opt.classList.remove('selected'));
                
                // Add selected class to clicked option
                option.classList.add('selected');
                
                // Check the radio button
                const radio = option.querySelector('input[type="radio"]');
                if (radio) {
                    radio.checked = true;
                }

                // Show/hide company name field for employers
                this.toggleCompanyNameField();
            });
        });
    }

    toggleCompanyNameField() {
        const employerRadio = document.getElementById('employer');
        const companyNameGroup = document.getElementById('companyNameGroup');
        
        if (employerRadio && companyNameGroup) {
            if (employerRadio.checked) {
                companyNameGroup.style.display = 'block';
                document.getElementById('company_name').required = true;
            } else {
                companyNameGroup.style.display = 'none';
                document.getElementById('company_name').required = false;
            }
        }
    }

    async handleLogin(e) {
        e.preventDefault();
        
        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        const formData = new FormData(form);
        formData.append('action', 'login');

        this.setLoading(submitBtn, true);
        this.clearAllErrors();

        try {
            const response = await fetch('/findajob/api/auth.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                // Check if email is verified
                if (!result.email_verified) {
                    this.showAlert('Please verify your email address before logging in. Check your inbox for verification link.', 'info');
                    this.showResendVerificationOption(formData.get('email'));
                } else {
                    // Redirect based on user type
                    const redirectUrl = result.user_type === 'employer' 
                        ? '/findajob/pages/company/dashboard.php'
                        : '/findajob/pages/user/dashboard.php';
                    
                    window.location.href = redirectUrl;
                }
            } else {
                this.showAlert(result.error, 'error');
            }
        } catch (error) {
            this.showAlert('Login failed. Please try again.', 'error');
        } finally {
            this.setLoading(submitBtn, false);
        }
    }

    async handleRegister(e) {
        e.preventDefault();
        
        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        const formData = new FormData(form);
        formData.append('action', 'register');

        this.setLoading(submitBtn, true);
        this.clearAllErrors();

        try {
            const response = await fetch('/findajob/api/auth.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.showAlert(result.message, 'success');
                form.reset();
                
                // Redirect to login page after 2 seconds
                setTimeout(() => {
                    window.location.href = '/findajob/pages/auth/login.php';
                }, 2000);
            } else {
                if (result.errors) {
                    Object.keys(result.errors).forEach(field => {
                        this.showFieldError(field, result.errors[field]);
                    });
                }
            }
        } catch (error) {
            this.showAlert('Registration failed. Please try again.', 'error');
        } finally {
            this.setLoading(submitBtn, false);
        }
    }

    async handlePasswordResetRequest(e) {
        e.preventDefault();
        
        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        const formData = new FormData(form);
        formData.append('action', 'request_password_reset');

        this.setLoading(submitBtn, true);
        this.clearAllErrors();

        try {
            const response = await fetch('/findajob/api/auth.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            this.showAlert(result.message, result.success ? 'success' : 'error');
            
            if (result.success) {
                form.reset();
            }
        } catch (error) {
            this.showAlert('Failed to send reset email. Please try again.', 'error');
        } finally {
            this.setLoading(submitBtn, false);
        }
    }

    async handlePasswordReset(e) {
        e.preventDefault();
        
        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        const formData = new FormData(form);
        formData.append('action', 'reset_password');

        // Get token from URL
        const urlParams = new URLSearchParams(window.location.search);
        const token = urlParams.get('token');
        if (token) {
            formData.append('token', token);
        }

        this.setLoading(submitBtn, true);
        this.clearAllErrors();

        try {
            const response = await fetch('/findajob/api/auth.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.showAlert(result.message, 'success');
                form.reset();
                
                // Redirect to login page after 2 seconds
                setTimeout(() => {
                    window.location.href = '/findajob/pages/auth/login.php';
                }, 2000);
            } else {
                this.showAlert(result.error, 'error');
            }
        } catch (error) {
            this.showAlert('Password reset failed. Please try again.', 'error');
        } finally {
            this.setLoading(submitBtn, false);
        }
    }

    async resendVerification(email) {
        try {
            const formData = new FormData();
            formData.append('action', 'resend_verification');
            formData.append('email', email);

            const response = await fetch('/findajob/api/auth.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            this.showAlert(result.message, result.success ? 'success' : 'error');
        } catch (error) {
            this.showAlert('Failed to resend verification email.', 'error');
        }
    }

    showResendVerificationOption(email) {
        const alertDiv = document.querySelector('.alert');
        if (alertDiv) {
            const resendBtn = document.createElement('button');
            resendBtn.textContent = 'Resend Verification Email';
            resendBtn.className = 'btn btn-secondary mt-2';
            resendBtn.onclick = () => this.resendVerification(email);
            alertDiv.appendChild(resendBtn);
        }
    }

    showAlert(message, type = 'info') {
        // Remove existing alerts
        const existingAlerts = document.querySelectorAll('.alert');
        existingAlerts.forEach(alert => alert.remove());

        // Create new alert
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.textContent = message;

        // Insert at the top of the auth card
        const authCard = document.querySelector('.auth-card');
        if (authCard) {
            authCard.insertBefore(alertDiv, authCard.firstChild);
        }
    }

    showFieldError(fieldName, message) {
        const field = document.getElementById(fieldName);
        if (field) {
            field.classList.add('error');
            
            // Remove existing error message
            const existingError = field.parentNode.querySelector('.error-message');
            if (existingError) {
                existingError.remove();
            }

            // Add new error message
            const errorDiv = document.createElement('span');
            errorDiv.className = 'error-message';
            errorDiv.textContent = message;
            field.parentNode.appendChild(errorDiv);
        }
    }

    clearFieldError(fieldName) {
        const field = document.getElementById(fieldName);
        if (field) {
            field.classList.remove('error');
            const errorMessage = field.parentNode.querySelector('.error-message');
            if (errorMessage) {
                errorMessage.remove();
            }
        }
    }

    clearAllErrors() {
        const errorMessages = document.querySelectorAll('.error-message');
        errorMessages.forEach(error => error.remove());

        const errorFields = document.querySelectorAll('.form-input.error');
        errorFields.forEach(field => field.classList.remove('error'));

        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => alert.remove());
    }

    setLoading(button, loading) {
        if (loading) {
            button.disabled = true;
            button.classList.add('loading');
            button.dataset.originalText = button.textContent;
            button.textContent = 'Please wait...';
        } else {
            button.disabled = false;
            button.classList.remove('loading');
            button.textContent = button.dataset.originalText || button.textContent;
        }
    }

    // Utility method to get URL parameters
    getUrlParameter(name) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(name);
    }
}

// Initialize authentication when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new Auth();

    // Handle URL parameters for messages
    const verified = new URLSearchParams(window.location.search).get('verified');
    const error = new URLSearchParams(window.location.search).get('error');

    if (verified) {
        const auth = new Auth();
        auth.showAlert('Email verified successfully! You can now log in.', 'success');
    }

    if (error) {
        const auth = new Auth();
        auth.showAlert(decodeURIComponent(error), 'error');
    }
});

// Logout function
function logout() {
    if (confirm('Are you sure you want to log out?')) {
        window.location.href = '/findajob/pages/auth/logout.php';
    }
}