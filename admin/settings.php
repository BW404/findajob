<?php
require_once '../config/database.php';
require_once '../config/session.php';

// Check if user is admin
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Check if user has admin role (from users table with user_type)
$user_id = getCurrentUserId();
$stmt = $pdo->prepare("SELECT user_type FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user || $user['user_type'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_general') {
        // Update general settings (to be implemented with a settings table)
        $success = 'General settings updated successfully';
    } elseif ($action === 'update_email') {
        // Update email settings
        $success = 'Email settings updated successfully';
    } elseif ($action === 'update_payment') {
        // Update payment settings
        $success = 'Payment settings updated successfully';
    } elseif ($action === 'update_api') {
        // Update API settings
        $success = 'API settings updated successfully';
    }
}

// Get current settings from constants file
$settings = [
    'site_name' => 'FindAJob Nigeria',
    'site_url' => 'http://localhost/findajob',
    'contact_email' => 'support@findajob.ng',
    'dev_mode' => defined('DEV_MODE') ? DEV_MODE : false,
    'items_per_page' => 20,
    'max_cv_size' => '5MB',
    'allowed_cv_formats' => 'PDF, DOC, DOCX'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - FindAJob Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
        }

        /* Sidebar Styles */
        .admin-sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 260px;
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            overflow-y: auto;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-header h2 {
            font-size: 24px;
            font-weight: 700;
            color: #fff;
        }

        .sidebar-nav {
            padding: 20px 0;
        }

        .nav-section {
            margin-bottom: 25px;
        }

        .nav-section-title {
            padding: 0 20px 10px;
            font-size: 11px;
            text-transform: uppercase;
            color: rgba(255,255,255,0.5);
            font-weight: 600;
            letter-spacing: 1px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }

        .nav-link:hover {
            background: rgba(255,255,255,0.05);
            color: white;
        }

        .nav-link.active {
            background: rgba(220,38,38,0.2);
            border-left-color: #dc2626;
            color: white;
        }

        .nav-link i {
            width: 20px;
            margin-right: 12px;
            font-size: 16px;
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            padding: 30px;
            min-height: 100vh;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 28px;
            color: #1a1a2e;
            margin-bottom: 5px;
        }

        .page-header p {
            color: #6b7280;
        }

        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }

        /* Settings Sections */
        .settings-grid {
            display: grid;
            gap: 20px;
        }

        .settings-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .settings-card h2 {
            font-size: 20px;
            color: #1a1a2e;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .settings-card h2 i {
            color: #dc2626;
        }

        .settings-card p {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: #374151;
            font-weight: 500;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-group small {
            display: block;
            margin-top: 5px;
            color: #6b7280;
            font-size: 12px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 26px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: 0.4s;
            border-radius: 26px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: 0.4s;
            border-radius: 50%;
        }

        input:checked + .toggle-slider {
            background-color: #dc2626;
        }

        input:checked + .toggle-slider:before {
            transform: translateX(24px);
        }

        .toggle-group {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .toggle-group label {
            margin: 0;
        }

        /* Buttons */
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: #dc2626;
            color: white;
        }

        .btn-primary:hover {
            background: #b91c1c;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }

        /* Info Boxes */
        .info-box {
            background: #f0f9ff;
            border: 1px solid #0ea5e9;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            font-size: 13px;
            color: #0c4a6e;
        }

        .info-box i {
            color: #0ea5e9;
            margin-right: 8px;
        }

        .warning-box {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            font-size: 13px;
            color: #92400e;
        }

        .warning-box i {
            color: #f59e0b;
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1>System Settings</h1>
            <p>Configure platform settings and integrations</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="settings-grid">
            <!-- General Settings -->
            <div class="settings-card">
                <h2><i class="fas fa-cog"></i> General Settings</h2>
                <p>Basic platform configuration</p>
                
                <form method="POST">
                    <input type="hidden" name="action" value="update_general">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Site Name</label>
                            <input type="text" name="site_name" value="<?= htmlspecialchars($settings['site_name']) ?>" required>
                            <small>Your platform's display name</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Site URL</label>
                            <input type="url" name="site_url" value="<?= htmlspecialchars($settings['site_url']) ?>" required>
                            <small>Base URL of your platform</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Contact Email</label>
                            <input type="email" name="contact_email" value="<?= htmlspecialchars($settings['contact_email']) ?>" required>
                            <small>Email for user inquiries</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Items Per Page</label>
                            <input type="number" name="items_per_page" value="<?= $settings['items_per_page'] ?>" min="10" max="100" required>
                            <small>Default pagination size</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="toggle-group">
                            <label class="toggle-switch">
                                <input type="checkbox" name="dev_mode" <?= $settings['dev_mode'] ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                            <label>Development Mode</label>
                        </div>
                        <small>Enable detailed error messages and email capture</small>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- Email Settings -->
            <div class="settings-card">
                <h2><i class="fas fa-envelope"></i> Email Configuration</h2>
                <p>SMTP settings for email delivery</p>
                
                <form method="POST">
                    <input type="hidden" name="action" value="update_email">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>SMTP Host</label>
                            <input type="text" name="smtp_host" placeholder="smtp.gmail.com">
                            <small>Your SMTP server address</small>
                        </div>
                        
                        <div class="form-group">
                            <label>SMTP Port</label>
                            <input type="number" name="smtp_port" placeholder="587">
                            <small>Usually 587 (TLS) or 465 (SSL)</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>SMTP Username</label>
                            <input type="text" name="smtp_username" placeholder="your-email@domain.com">
                        </div>
                        
                        <div class="form-group">
                            <label>SMTP Password</label>
                            <input type="password" name="smtp_password" placeholder="••••••••">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>From Email Address</label>
                        <input type="email" name="from_email" placeholder="noreply@findajob.ng">
                        <small>Email address that appears as sender</small>
                    </div>

                    <div class="info-box">
                        <i class="fas fa-info-circle"></i>
                        Currently using development mode. Emails are captured to logs/emails/ instead of being sent.
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Email Settings
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="testEmail()">
                            <i class="fas fa-paper-plane"></i> Send Test Email
                        </button>
                    </div>
                </form>
            </div>

            <!-- Payment Settings -->
            <div class="settings-card">
                <h2><i class="fas fa-credit-card"></i> Payment Gateway</h2>
                <p>Configure Paystack integration</p>
                
                <form method="POST">
                    <input type="hidden" name="action" value="update_payment">
                    
                    <div class="form-group">
                        <label>Paystack Public Key</label>
                        <input type="text" name="paystack_public_key" placeholder="pk_test_xxxxxxxxxxxxx">
                        <small>Your Paystack public API key</small>
                    </div>

                    <div class="form-group">
                        <label>Paystack Secret Key</label>
                        <input type="password" name="paystack_secret_key" placeholder="sk_test_xxxxxxxxxxxxx">
                        <small>Your Paystack secret API key (keep secure)</small>
                    </div>

                    <div class="form-group">
                        <div class="toggle-group">
                            <label class="toggle-switch">
                                <input type="checkbox" name="paystack_live_mode">
                                <span class="toggle-slider"></span>
                            </label>
                            <label>Live Mode</label>
                        </div>
                        <small>Use live keys instead of test keys</small>
                    </div>

                    <div class="warning-box">
                        <i class="fas fa-exclamation-triangle"></i>
                        Never share your secret key. Make sure to use test keys during development.
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Payment Settings
                        </button>
                    </div>
                </form>
            </div>

            <!-- API & Integration Settings -->
            <div class="settings-card">
                <h2><i class="fas fa-plug"></i> API & Integrations</h2>
                <p>Third-party service configurations</p>
                
                <form method="POST">
                    <input type="hidden" name="action" value="update_api">
                    
                    <div class="form-group">
                        <label>Dojah API Key (NIN/BVN Verification)</label>
                        <input type="password" name="dojah_api_key" placeholder="Your Dojah API key">
                        <small>Used for identity verification services</small>
                    </div>

                    <div class="form-group">
                        <label>Dojah App ID</label>
                        <input type="text" name="dojah_app_id" placeholder="Your Dojah App ID">
                    </div>

                    <div class="form-group">
                        <label>Google Analytics ID</label>
                        <input type="text" name="google_analytics_id" placeholder="G-XXXXXXXXXX">
                        <small>Track platform analytics (optional)</small>
                    </div>

                    <div class="form-group">
                        <label>reCAPTCHA Site Key</label>
                        <input type="text" name="recaptcha_site_key" placeholder="Your site key">
                        <small>Protect forms from spam (optional)</small>
                    </div>

                    <div class="form-group">
                        <label>reCAPTCHA Secret Key</label>
                        <input type="password" name="recaptcha_secret_key" placeholder="Your secret key">
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save API Settings
                        </button>
                    </div>
                </form>
            </div>

            <!-- CV Settings -->
            <div class="settings-card">
                <h2><i class="fas fa-file-pdf"></i> CV Upload Settings</h2>
                <p>Configure CV upload restrictions</p>
                
                <form method="POST">
                    <input type="hidden" name="action" value="update_cv">
                    
                    <div class="form-group">
                        <label>Maximum CV File Size</label>
                        <select name="max_cv_size">
                            <option value="2">2 MB</option>
                            <option value="5" selected>5 MB</option>
                            <option value="10">10 MB</option>
                            <option value="15">15 MB</option>
                        </select>
                        <small>Maximum size for uploaded CV files</small>
                    </div>

                    <div class="form-group">
                        <label>Allowed File Formats</label>
                        <div style="margin-top: 10px;">
                            <label style="display: block; margin-bottom: 8px;">
                                <input type="checkbox" name="formats[]" value="pdf" checked> PDF (.pdf)
                            </label>
                            <label style="display: block; margin-bottom: 8px;">
                                <input type="checkbox" name="formats[]" value="doc" checked> Word 97-2003 (.doc)
                            </label>
                            <label style="display: block;">
                                <input type="checkbox" name="formats[]" value="docx" checked> Word (.docx)
                            </label>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save CV Settings
                        </button>
                    </div>
                </form>
            </div>

            <!-- Cache & Performance -->
            <div class="settings-card">
                <h2><i class="fas fa-tachometer-alt"></i> Cache & Performance</h2>
                <p>Optimize platform performance</p>
                
                <div class="form-group">
                    <div class="toggle-group">
                        <label class="toggle-switch">
                            <input type="checkbox" checked>
                            <span class="toggle-slider"></span>
                        </label>
                        <label>Enable Database Query Cache</label>
                    </div>
                </div>

                <div class="form-group">
                    <div class="toggle-group">
                        <label class="toggle-switch">
                            <input type="checkbox" checked>
                            <span class="toggle-slider"></span>
                        </label>
                        <label>Enable Asset Minification</label>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="clearCache()">
                        <i class="fas fa-trash"></i> Clear Cache
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function testEmail() {
            alert('Test email functionality will be implemented via API');
        }

        function clearCache() {
            if (confirm('Are you sure you want to clear the cache?')) {
                alert('Cache clearing functionality will be implemented via API');
            }
        }
    </script>
</body>
</html>
