<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/permissions.php';

// Check if user is Super Admin
if (!isSuperAdmin(getCurrentUserId())) {
    header('Location: dashboard.php?error=access_denied');
    exit();
}

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_payment_settings'])) {
    try {
        $pdo->beginTransaction();
        
        // Get settings to update
        $settings = [
            'flutterwave_public_key' => trim($_POST['flutterwave_public_key'] ?? ''),
            'flutterwave_secret_key' => trim($_POST['flutterwave_secret_key'] ?? ''),
            'flutterwave_encryption_key' => trim($_POST['flutterwave_encryption_key'] ?? ''),
            'flutterwave_environment' => trim($_POST['flutterwave_environment'] ?? 'test'),
            'flutterwave_webhook_url' => trim($_POST['flutterwave_webhook_url'] ?? '')
        ];
        
        // Validate environment
        if (!in_array($settings['flutterwave_environment'], ['test', 'live'])) {
            throw new Exception('Invalid environment. Must be "test" or "live".');
        }
        
        // Validate keys are not empty
        if (empty($settings['flutterwave_public_key']) || empty($settings['flutterwave_secret_key']) || empty($settings['flutterwave_encryption_key'])) {
            throw new Exception('All API keys are required.');
        }
        
        // Update or insert settings
        foreach ($settings as $key => $value) {
            $stmt = $pdo->prepare("
                INSERT INTO site_settings (setting_key, setting_value, updated_at) 
                VALUES (?, ?, NOW()) 
                ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
            ");
            $stmt->execute([$key, $value, $value]);
        }
        
        $pdo->commit();
        $success = 'Payment settings saved successfully! Changes are now active.';
        
        // Log the action
        error_log("Flutterwave settings updated by admin user ID: " . getCurrentUserId() . " - Environment: " . $settings['flutterwave_environment']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Error saving settings: ' . $e->getMessage();
        error_log("Payment settings update error: " . $e->getMessage());
    }
}

// Fetch current settings
$currentSettings = [];
$stmt = $pdo->query("
    SELECT setting_key, setting_value 
    FROM site_settings 
    WHERE setting_key LIKE 'flutterwave_%'
");
while ($row = $stmt->fetch()) {
    $currentSettings[$row['setting_key']] = $row['setting_value'];
}

// Set defaults if not in database
$settings = [
    'flutterwave_public_key' => $currentSettings['flutterwave_public_key'] ?? '',
    'flutterwave_secret_key' => $currentSettings['flutterwave_secret_key'] ?? '',
    'flutterwave_encryption_key' => $currentSettings['flutterwave_encryption_key'] ?? '',
    'flutterwave_environment' => $currentSettings['flutterwave_environment'] ?? 'test',
    'flutterwave_webhook_url' => $currentSettings['flutterwave_webhook_url'] ?? ''
];

$pageTitle = 'Payment Settings';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .settings-container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .settings-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .settings-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .settings-header i {
            font-size: 2rem;
            color: var(--primary);
        }
        
        .settings-header h2 {
            margin: 0;
            color: var(--text-primary);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
        }
        
        .form-help {
            display: block;
            margin-top: 0.25rem;
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .environment-toggle {
            display: flex;
            gap: 1rem;
            margin-top: 0.5rem;
        }
        
        .environment-option {
            flex: 1;
            position: relative;
        }
        
        .environment-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }
        
        .environment-option label {
            display: block;
            padding: 1.25rem;
            border: 2px solid #d1d5db;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s;
        }
        
        .environment-option input[type="radio"]:checked + label {
            border-color: var(--primary);
            background: rgba(220, 38, 38, 0.05);
            font-weight: 600;
        }
        
        .environment-option label:hover {
            border-color: var(--primary);
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert-success {
            background: #dcfce7;
            color: #166534;
            border-left: 4px solid #16a34a;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }
        
        .alert-warning {
            background: #fef3c7;
            color: #92400e;
            border-left: 4px solid #f59e0b;
        }
        
        .warning-box {
            background: #fef3c7;
            border: 2px solid #f59e0b;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .warning-box h3 {
            margin: 0 0 0.5rem 0;
            color: #92400e;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .warning-box ul {
            margin: 0.5rem 0 0 1.5rem;
            color: #78350f;
        }
        
        .test-card-info {
            background: #f3f4f6;
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .test-card-info h4 {
            margin: 0 0 1rem 0;
            color: var(--text-primary);
        }
        
        .test-card-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        .test-card-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem;
            background: white;
            border-radius: 4px;
        }
        
        .test-card-item strong {
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        .test-card-item code {
            background: #e5e7eb;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }
        
        .password-wrapper {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            padding: 0.5rem;
            font-size: 1rem;
        }
        
        .password-toggle:hover {
            color: var(--primary);
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="page-header">
            <h1><i class="fas fa-credit-card"></i> Payment Settings</h1>
            <p>Configure Flutterwave payment gateway settings</p>
        </div>
        
        <div class="settings-container">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($settings['flutterwave_environment'] === 'live'): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>LIVE MODE ACTIVE</strong> - Real payments are being processed!
                </div>
            <?php endif; ?>
            
            <div class="warning-box">
                <h3>
                    <i class="fas fa-shield-alt"></i>
                    Security Notice
                </h3>
                <p style="margin: 0.5rem 0; color: #78350f;">
                    These settings contain sensitive API keys. Only Super Admins can access this page.
                </p>
                <ul style="margin-bottom: 0;">
                    <li>Never share your API keys publicly</li>
                    <li>Use TEST keys for development and testing</li>
                    <li>Switch to LIVE keys only when ready for production</li>
                    <li>Keep a backup of your keys in a secure location</li>
                </ul>
            </div>
            
            <form method="POST" action="">
                <!-- Flutterwave API Keys -->
                <div class="settings-card">
                    <div class="settings-header">
                        <i class="fas fa-key"></i>
                        <div style="flex: 1;">
                            <h2>Flutterwave API Keys</h2>
                            <p style="margin: 0.25rem 0 0 0; color: #6b7280; font-size: 0.875rem;">
                                Get your keys from <a href="https://dashboard.flutterwave.com/settings/apis" target="_blank" style="color: var(--primary); text-decoration: underline;">Flutterwave Dashboard</a>
                            </p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="flutterwave_public_key">
                            Public Key <span style="color: #dc2626;">*</span>
                        </label>
                        <input type="text" 
                               id="flutterwave_public_key" 
                               name="flutterwave_public_key" 
                               class="form-input" 
                               value="<?php echo htmlspecialchars($settings['flutterwave_public_key']); ?>"
                               placeholder="FLWPUBK_TEST-XXXXXXXXXXXXX-X"
                               required>
                        <small class="form-help">Used for client-side payment initialization</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="flutterwave_secret_key">
                            Secret Key <span style="color: #dc2626;">*</span>
                        </label>
                        <div class="password-wrapper">
                            <input type="password" 
                                   id="flutterwave_secret_key" 
                                   name="flutterwave_secret_key" 
                                   class="form-input" 
                                   value="<?php echo htmlspecialchars($settings['flutterwave_secret_key']); ?>"
                                   placeholder="FLWSECK_TEST-XXXXXXXXXXXXX-X"
                                   required>
                            <button type="button" class="password-toggle" onclick="togglePassword('flutterwave_secret_key')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="form-help">Used for server-side API calls (keep secret)</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="flutterwave_encryption_key">
                            Encryption Key <span style="color: #dc2626;">*</span>
                        </label>
                        <div class="password-wrapper">
                            <input type="password" 
                                   id="flutterwave_encryption_key" 
                                   name="flutterwave_encryption_key" 
                                   class="form-input" 
                                   value="<?php echo htmlspecialchars($settings['flutterwave_encryption_key']); ?>"
                                   placeholder="FLWSECK_TEST-XXXXXXXXXXXXX-X"
                                   required>
                            <button type="button" class="password-toggle" onclick="togglePassword('flutterwave_encryption_key')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="form-help">Used for data encryption</small>
                    </div>
                </div>
                
                <!-- Environment Settings -->
                <div class="settings-card">
                    <div class="settings-header">
                        <i class="fas fa-globe"></i>
                        <div style="flex: 1;">
                            <h2>Environment Mode</h2>
                            <p style="margin: 0.25rem 0 0 0; color: #6b7280; font-size: 0.875rem;">
                                Select whether to use test or live payment processing
                            </p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            Payment Environment <span style="color: #dc2626;">*</span>
                        </label>
                        <div class="environment-toggle">
                            <div class="environment-option">
                                <input type="radio" 
                                       id="env_test" 
                                       name="flutterwave_environment" 
                                       value="test"
                                       <?php echo $settings['flutterwave_environment'] === 'test' ? 'checked' : ''; ?>>
                                <label for="env_test">
                                    <i class="fas fa-flask" style="font-size: 1.5rem; color: #f59e0b;"></i>
                                    <div style="margin-top: 0.5rem; font-size: 1.125rem;">Test Mode</div>
                                    <small style="color: #6b7280;">For development & testing</small>
                                </label>
                            </div>
                            <div class="environment-option">
                                <input type="radio" 
                                       id="env_live" 
                                       name="flutterwave_environment" 
                                       value="live"
                                       <?php echo $settings['flutterwave_environment'] === 'live' ? 'checked' : ''; ?>>
                                <label for="env_live">
                                    <i class="fas fa-check-circle" style="font-size: 1.5rem; color: #16a34a;"></i>
                                    <div style="margin-top: 0.5rem; font-size: 1.125rem;">Live Mode</div>
                                    <small style="color: #6b7280;">Real payments processed</small>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="flutterwave_webhook_url">
                            Webhook URL (Optional)
                        </label>
                        <input type="url" 
                               id="flutterwave_webhook_url" 
                               name="flutterwave_webhook_url" 
                               class="form-input" 
                               value="<?php echo htmlspecialchars($settings['flutterwave_webhook_url']); ?>"
                               placeholder="https://yourdomain.com/api/flutterwave-webhook.php">
                        <small class="form-help">Configure this URL in your Flutterwave dashboard to receive payment notifications</small>
                    </div>
                </div>
                
                <!-- Test Card Information -->
                <?php if ($settings['flutterwave_environment'] === 'test'): ?>
                <div class="settings-card" id="test-card-section">
                    <div class="settings-header">
                        <i class="fas fa-credit-card"></i>
                        <div style="flex: 1;">
                            <h2>Test Card Details</h2>
                            <p style="margin: 0.25rem 0 0 0; color: #6b7280; font-size: 0.875rem;">
                                Use these details to test payments in test mode
                            </p>
                        </div>
                    </div>
                    
                    <div class="test-card-info">
                        <h4><i class="fas fa-info-circle"></i> Test Payment Details</h4>
                        <div class="test-card-grid">
                            <div class="test-card-item">
                                <strong>Card Number:</strong>
                                <code>5531 8866 5214 2950</code>
                            </div>
                            <div class="test-card-item">
                                <strong>CVV:</strong>
                                <code>564</code>
                            </div>
                            <div class="test-card-item">
                                <strong>PIN:</strong>
                                <code>3310</code>
                            </div>
                            <div class="test-card-item">
                                <strong>OTP:</strong>
                                <code>12345</code>
                            </div>
                        </div>
                        <p style="margin: 1rem 0 0 0; color: #6b7280; font-size: 0.875rem;">
                            <i class="fas fa-lightbulb"></i> 
                            Expiry: Use any future date | No real money is charged in test mode
                        </p>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Save Button -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 2rem;">
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                    <button type="submit" name="save_payment_settings" class="btn btn-primary" style="font-size: 1rem; padding: 0.875rem 2rem;">
                        <i class="fas fa-save"></i> Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const button = event.currentTarget;
            const icon = button.querySelector('i');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Show/hide test card info based on environment selection
        document.querySelectorAll('input[name="flutterwave_environment"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const testCardSection = document.getElementById('test-card-section');
                if (testCardSection) {
                    if (this.value === 'test') {
                        testCardSection.style.display = 'block';
                    } else {
                        testCardSection.style.display = 'none';
                    }
                }
            });
        });
        
        // Confirmation for switching to live mode
        document.getElementById('env_live')?.addEventListener('click', function(e) {
            const currentEnv = '<?php echo $settings['flutterwave_environment']; ?>';
            if (currentEnv === 'test') {
                if (!confirm('⚠️ WARNING: Switching to LIVE mode will process REAL payments!\n\nMake sure you have:\n✓ Updated to LIVE API keys\n✓ Tested thoroughly in test mode\n✓ Configured webhook URL\n\nAre you sure you want to continue?')) {
                    e.preventDefault();
                    document.getElementById('env_test').checked = true;
                    return false;
                }
            }
        });
    </script>
</body>
</html>
