<?php
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/constants.php';

$showResetForm = isset($_GET['token']) && !empty($_GET['token']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $showResetForm ? 'Reset Password' : 'Forgot Password'; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/main.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <?php if ($showResetForm): ?>
                <!-- Reset Password Form -->
                <div class="auth-header">
                    <h1>Reset Password</h1>
                    <p>Enter your new password below</p>
                </div>

                <form id="resetForm" method="POST">
                    <div class="form-group">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" id="password" name="password" class="form-input" required minlength="8">
                        <small class="text-secondary">Must be at least 8 characters long</small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
                </form>
            <?php else: ?>
                <!-- Request Password Reset Form -->
                <div class="auth-header">
                    <h1>Forgot Password?</h1>
                    <p>Enter your email address and we'll send you a link to reset your password</p>
                </div>

                <form id="requestResetForm" method="POST">
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" id="email" name="email" class="form-input" required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Send Reset Link</button>
                </form>
            <?php endif; ?>

            <div class="auth-footer">
                <p><a href="login.php">← Back to Login</a></p>
                <?php if (!$showResetForm): ?>
                    <p>Don't have an account? <a href="register.php">Sign up here</a></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../../assets/js/auth.js"></script>
    <script>
        // Password confirmation validation for reset form
        document.addEventListener('DOMContentLoaded', function() {
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            
            if (password && confirmPassword) {
                confirmPassword.addEventListener('input', function() {
                    if (password.value !== confirmPassword.value) {
                        confirmPassword.setCustomValidity('Passwords do not match');
                    } else {
                        confirmPassword.setCustomValidity('');
                    }
                });
            }
        });
    </script>
</body>
</html>