<?php
/**
 * Development Status Checker
 * Quick way to check if development mode is active from any IP/domain
 * Access: http://your-ip/findajob/dev_status.php
 */

require_once 'config/constants.php';
require_once 'includes/functions.php';

$server_info = [
    'SERVER_NAME' => $_SERVER['SERVER_NAME'] ?? 'Not set',
    'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? 'Not set',
    'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'Not set',
    'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? 'Not set',
    'SERVER_ADDR' => $_SERVER['SERVER_ADDR'] ?? 'Not set'
];

$dev_mode_status = isDevelopmentMode();
$dev_mode_constant = defined('DEV_MODE') ? (DEV_MODE ? 'true' : 'false') : 'Not defined';
$email_capture = defined('DEV_EMAIL_CAPTURE') ? (DEV_EMAIL_CAPTURE ? 'true' : 'false') : 'Not defined';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Development Status - FindAJob Nigeria</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="assets/images/icons/icon-192x192.svg">
    <link rel="alternate icon" href="assets/images/icons/icon-192x192.svg">
    <link rel="shortcut icon" href="assets/images/icons/icon-192x192.svg">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 2rem;
            background: #f8fafc;
            color: #1e293b;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .status-good {
            color: #059669;
            font-weight: bold;
        }
        
        .status-error {
            color: #dc2626;
            font-weight: bold;
        }
        
        .status-warning {
            color: #d97706;
            font-weight: bold;
        }
        
        .info-grid {
            display: grid;
            gap: 1rem;
            margin: 1rem 0;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 1rem;
            background: #f8fafc;
            border-radius: 6px;
            border-left: 4px solid #cbd5e1;
        }
        
        .info-item.active {
            border-left-color: #059669;
            background: #f0fdf4;
        }
        
        .info-item.inactive {
            border-left-color: #dc2626;
            background: #fef2f2;
        }
        
        .actions {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            background: #dc2626;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: background-color 0.2s;
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
        
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin: 1rem 0;
        }
        
        .alert-success {
            background: #f0fdf4;
            border: 1px solid #16a34a;
            color: #15803d;
        }
        
        .alert-error {
            background: #fef2f2;
            border: 1px solid #dc2626;
            color: #dc2626;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛠️ Development Status Check</h1>
            <p>FindAJob Nigeria - Development Environment Status</p>
        </div>
        
        <?php if ($dev_mode_status): ?>
            <div class="alert alert-success">
                <strong>✅ Development Mode is ACTIVE</strong><br>
                Development features like email capture and dev tools are available.
            </div>
        <?php else: ?>
            <div class="alert alert-error">
                <strong>❌ Development Mode is INACTIVE</strong><br>
                Set DEV_MODE = true in config/constants.php to enable development features.
            </div>
        <?php endif; ?>
        
        <h3>📊 Current Configuration</h3>
        <div class="info-grid">
            <div class="info-item <?php echo $dev_mode_status ? 'active' : 'inactive'; ?>">
                <span><strong>Development Mode Status:</strong></span>
                <span class="<?php echo $dev_mode_status ? 'status-good' : 'status-error'; ?>">
                    <?php echo $dev_mode_status ? 'ACTIVE' : 'INACTIVE'; ?>
                </span>
            </div>
            
            <div class="info-item">
                <span><strong>DEV_MODE Constant:</strong></span>
                <span><?php echo $dev_mode_constant; ?></span>
            </div>
            
            <div class="info-item">
                <span><strong>DEV_EMAIL_CAPTURE:</strong></span>
                <span><?php echo $email_capture; ?></span>
            </div>
        </div>
        
        <h3>🌐 Server Information</h3>
        <div class="info-grid">
            <?php foreach ($server_info as $key => $value): ?>
            <div class="info-item">
                <span><strong><?php echo $key; ?>:</strong></span>
                <span><?php echo htmlspecialchars($value); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        
        <h3>🔧 Access Information</h3>
        <div class="info-grid">
            <div class="info-item">
                <span><strong>Current URL:</strong></span>
                <span><?php echo htmlspecialchars($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?></span>
            </div>
            
            <div class="info-item">
                <span><strong>Base URL for Dev Tools:</strong></span>
                <span><?php echo htmlspecialchars('http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI'])); ?></span>
            </div>
        </div>
        
        <div class="actions">
            <?php if ($dev_mode_status): ?>
                <a href="temp_mail.php" class="btn">📧 Email Inbox</a>
                <a href="test_email.php" class="btn btn-secondary">🧪 Test Emails</a>
            <?php endif; ?>
            <a href="index.php" class="btn btn-secondary">🏠 Main Site</a>
            <a href="#" onclick="location.reload();" class="btn btn-secondary">🔄 Refresh</a>
        </div>
        
        <div style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #e2e8f0; font-size: 0.875rem; color: #64748b;">
            <p><strong>Note:</strong> Development mode is controlled by the DEV_MODE constant in config/constants.php. 
            When set to true, it works from any IP address or domain name, making it accessible from:</p>
            <ul>
                <li>localhost</li>
                <li>127.0.0.1</li>
                <li>Your computer's local IP (192.168.x.x)</li>
                <li>Any custom domain or IP you're using</li>
            </ul>
        </div>
    </div>
</body>
</html>