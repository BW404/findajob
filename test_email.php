<?php
/**
 * Test Development Email System
 * Quick test to verify email capture is working
 */

require_once 'config/constants.php';
require_once 'includes/functions.php';

// Test sending an email
if (isset($_GET['send'])) {
    $to = 'testuser@example.com';
    $subject = 'Test Registration Email - FindAJob Nigeria';
    $message = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; }
            .header { background: #dc2626; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { padding: 30px; }
            .button { background: #dc2626; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Welcome to FindAJob Nigeria!</h1>
            </div>
            <div class="content">
                <h2>Hello Test User,</h2>
                <p>Thank you for registering with FindAJob Nigeria. To complete your registration, please verify your email address by clicking the button below:</p>
                <p style="text-align: center; margin: 30px 0;">
                    <a href="' . SITE_URL . '/api/auth.php?verify_email&token=test123token" class="button">Verify Email Address</a>
                </p>
                <p>If the button above does not work, you can copy and paste the following link into your browser:</p>
                <p style="color: #666; font-size: 14px;">' . SITE_URL . '/api/auth.php?verify_email&token=test123token</p>
                <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;">
                <p style="color: #888; font-size: 12px;">
                    This is an automated email from FindAJob Nigeria. Please do not reply to this email.
                    <br>© ' . date('Y') . ' FindAJob Nigeria. All rights reserved.
                </p>
            </div>
        </div>
    </body>
    </html>';
    
    // Store the email in development
    $result = devStoreEmail($to, $subject, $message, 'verification');
    
    if ($result) {
        $success = "✅ Test email sent successfully! Check the <a href='temp_mail.php'>Development Email Inbox</a>";
    } else {
        $error = "❌ Failed to send email. Make sure DEV_MODE is enabled.";
    }
}

// Test password reset email
if (isset($_GET['reset'])) {
    $to = 'testuser@example.com';
    $subject = 'Password Reset Request - FindAJob Nigeria';
    $message = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; }
            .header { background: #dc2626; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { padding: 30px; }
            .button { background: #dc2626; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block; }
            .warning { background: #fef2f2; border: 1px solid #fecaca; padding: 15px; border-radius: 6px; color: #991b1b; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Password Reset Request</h1>
            </div>
            <div class="content">
                <h2>Hello Test User,</h2>
                <p>We received a request to reset your password for your FindAJob Nigeria account. If you made this request, click the button below to reset your password:</p>
                <p style="text-align: center; margin: 30px 0;">
                    <a href="' . SITE_URL . '/pages/auth/reset.php?token=reset456token" class="button">Reset Password</a>
                </p>
                <div class="warning">
                    <strong>⚠️ Security Notice:</strong> This password reset link will expire in 1 hour for your security.
                </div>
                <p>If you did not request a password reset, you can safely ignore this email. Your password will remain unchanged.</p>
                <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;">
                <p style="color: #888; font-size: 12px;">
                    This is an automated email from FindAJob Nigeria. Please do not reply to this email.
                    <br>© ' . date('Y') . ' FindAJob Nigeria. All rights reserved.
                </p>
            </div>
        </div>
    </body>
    </html>';
    
    $result = devStoreEmail($to, $subject, $message, 'password_reset');
    
    if ($result) {
        $success = "✅ Password reset email sent successfully! Check the <a href='temp_mail.php'>Development Email Inbox</a>";
    } else {
        $error = "❌ Failed to send email. Make sure DEV_MODE is enabled.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Development Email System - FindAJob Nigeria</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f8fafc;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }
        
        .header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .header h1 {
            color: #dc2626;
            margin-bottom: 0.5rem;
        }
        
        .status {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        
        .success {
            background: #dcfce7;
            border: 1px solid #bbf7d0;
            color: #166534;
        }
        
        .error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }
        
        .buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            background: #dc2626;
            color: white;
            padding: 0.75rem 1.5rem;
            text-decoration: none;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }
        
        .btn:hover {
            background: #991b1b;
        }
        
        .btn-secondary {
            background: #64748b;
        }
        
        .btn-secondary:hover {
            background: #475569;
        }
        
        .info {
            background: #f1f5f9;
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 2rem;
        }
        
        .info h3 {
            margin-top: 0;
            color: #334155;
        }
        
        .info ul {
            margin-bottom: 0;
            color: #64748b;
        }
        
        .config-status {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 Development Email System Test</h1>
            <p>Test the email capture system for FindAJob Nigeria</p>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="status success">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="status error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="config-status">
            <strong>Configuration Status:</strong><br>
            DEV_MODE: <?php echo defined('DEV_MODE') && DEV_MODE ? '✅ Enabled' : '❌ Disabled'; ?><br>
            DEV_EMAIL_CAPTURE: <?php echo defined('DEV_EMAIL_CAPTURE') && DEV_EMAIL_CAPTURE ? '✅ Enabled' : '❌ Disabled'; ?><br>
            Development Environment: <?php echo isDevelopmentMode() ? '✅ Detected' : '❌ Not detected'; ?>
        </div>
        
        <div class="buttons">
            <a href="?send=1" class="btn">📧 Send Registration Email</a>
            <a href="?reset=1" class="btn">🔒 Send Password Reset Email</a>
            <a href="temp_mail.php" class="btn btn-secondary">📬 View Email Inbox</a>
            <a href="index.php" class="btn btn-secondary">🏠 Back to Home</a>
        </div>
        
        <div class="info">
            <h3>📋 How It Works</h3>
            <ul>
                <li><strong>Development Mode:</strong> When DEV_MODE is enabled, all emails are captured and stored locally instead of being sent.</li>
                <li><strong>Email Storage:</strong> Emails are stored in <code>temp_emails.json</code> file in the root directory.</li>
                <li><strong>Email Viewer:</strong> Access <code>temp_mail.php</code> to view all captured emails with a clean interface.</li>
                <li><strong>Auto-Detection:</strong> The system automatically detects XAMPP/localhost environment.</li>
                <li><strong>Email Types:</strong> Supports different email types (verification, password_reset, welcome, etc.) with proper categorization.</li>
            </ul>
            
            <h3>🔧 Testing Steps</h3>
            <ol>
                <li>Click "Send Registration Email" or "Send Password Reset Email" above</li>
                <li>Check for success message</li>
                <li>Click "View Email Inbox" to see the captured email</li>
                <li>Click on any email to view its full content with proper formatting</li>
                <li>Test the actual registration/login system to see real emails being captured</li>
            </ol>
        </div>
    </div>
</body>
</html>