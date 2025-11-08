<?php
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/constants.php';

requireJobSeeker();

$userId = getCurrentUserId();

// Get user profile data
$stmt = $pdo->prepare("
    SELECT u.*, jsp.* 
    FROM users u 
    LEFT JOIN job_seeker_profiles jsp ON u.id = jsp.user_id 
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Redirect if already verified
if ($user['nin_verified']) {
    header('Location: ../user/profile.php');
    exit();
}

// Handle form submission
if ($_POST && isset($_POST['nin'])) {
    $nin = trim($_POST['nin']);
    
    // Validate NIN format (11 digits)
    if (preg_match('/^\d{11}$/', $nin)) {
        try {
            $pdo->beginTransaction();
            
            // Update NIN in profile
            if ($user['user_id']) {
                // Update existing profile
                $updateStmt = $pdo->prepare("UPDATE job_seeker_profiles SET nin = ? WHERE user_id = ?");
                $updateStmt->execute([$nin, $userId]);
            } else {
                // Create new profile with NIN
                $insertStmt = $pdo->prepare("INSERT INTO job_seeker_profiles (user_id, nin) VALUES (?, ?)");
                $insertStmt->execute([$userId, $nin]);
            }
            
            // Mark as verified (in real implementation, this would be after actual NIN verification)
            $verifyStmt = $pdo->prepare("UPDATE job_seeker_profiles SET nin_verified = 1, nin_verified_at = NOW(), verification_status = 'nin_verified' WHERE user_id = ?");
            $verifyStmt->execute([$userId]);
            
            // Record transaction (you would integrate with actual payment gateway)
            $transactionStmt = $pdo->prepare("
                INSERT INTO transactions (user_id, amount, currency, service_type, status, transaction_reference) 
                VALUES (?, 1000, 'NGN', 'nin_verification', 'completed', ?)
            ");
            $transactionRef = 'NIN_' . time() . '_' . $userId;
            $transactionStmt->execute([$userId, $transactionRef]);
            
            $pdo->commit();
            
            // Redirect to success page
            header('Location: nin-verification.php?success=1');
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = "Verification failed. Please try again.";
        }
    } else {
        $error_message = "Invalid NIN format. Please enter an 11-digit NIN.";
    }
}

$success = isset($_GET['success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NIN Verification - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <style>
        .verification-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .verification-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .success-card {
            border: 2px solid var(--accent);
            background: linear-gradient(135deg, #f0f9f0, #e8f5e8);
        }
        
        .verification-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .verification-title {
            color: var(--primary);
            margin-bottom: 1rem;
            font-size: 1.8rem;
        }
        
        .success-title {
            color: var(--accent);
        }
        
        .price-display {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary);
            margin: 1rem 0;
        }
        
        .price-display span {
            font-size: 1rem;
            color: var(--text-secondary);
            font-weight: normal;
        }
        
        .features-list {
            text-align: left;
            margin: 2rem 0;
            padding: 1.5rem;
            background: var(--primary-light);
            border-radius: 8px;
        }
        
        .features-list h4 {
            color: var(--primary-dark);
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .features-list ul {
            list-style: none;
            padding: 0;
        }
        
        .features-list li {
            padding: 0.5rem 0;
            color: var(--text-primary);
            position: relative;
            padding-left: 2rem;
        }
        
        .features-list li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--accent);
            font-weight: bold;
        }
        
        .nin-form {
            margin: 2rem 0;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }
        
        .form-input {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1.1rem;
            text-align: center;
            letter-spacing: 0.1em;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .btn-verify {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 1.2rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            margin-top: 1rem;
        }
        
        .btn-verify:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(220, 38, 38, 0.3);
        }
        
        .btn-secondary {
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
            padding: 1rem 2rem;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            margin-top: 1rem;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-secondary:hover {
            background: var(--primary);
            color: white;
        }
        
        .security-note {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-top: 1.5rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 4px solid var(--primary);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .alert.error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .badge-preview {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--accent);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin: 1rem 0;
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="container">
            <nav class="site-nav">
                <a href="/findajob" class="site-logo">
                    <img src="/findajob/assets/images/logo_full.png" alt="FindAJob Nigeria" class="site-logo-img">
                </a>
                <div>
                    <a href="../user/profile.php" class="btn btn-secondary">← Back to Profile</a>
                    <a href="../auth/logout.php" class="btn btn-secondary">Logout</a>
                </div>
            </nav>
        </div>
    </header>

    <main class="verification-container">
        <?php if ($success): ?>
            <!-- Success State -->
            <div class="verification-card success-card">
                <div class="verification-icon">🎉</div>
                <h1 class="verification-title success-title">NIN Verification Successful!</h1>
                
                <div class="badge-preview">
                    ✓ Verified Profile
                </div>
                
                <p style="font-size: 1.1rem; color: var(--text-primary); margin: 1.5rem 0;">
                    Congratulations! Your NIN has been successfully verified. Your profile now has a verified badge that will increase your credibility with employers.
                </p>
                
                <div class="features-list">
                    <h4>What you've unlocked:</h4>
                    <ul>
                        <li>Verified badge on your profile</li>
                        <li>Higher credibility with employers</li>
                        <li>Priority in job search results</li>
                        <li>Increased interview chances</li>
                        <li>Professional profile status</li>
                    </ul>
                </div>
                
                <a href="../user/profile.php" class="btn-verify">
                    View My Verified Profile
                </a>
                
                <a href="../user/dashboard.php" class="btn-secondary">
                    Go to Dashboard
                </a>
            </div>
            
        <?php else: ?>
            <!-- Payment/Verification Form -->
            <div class="verification-card">
                <div class="verification-icon">🛡️</div>
                <h1 class="verification-title">NIN Verification Service</h1>
                
                <div class="price-display">
                    ₦1,000 <span>one-time payment</span>
                </div>
                
                <div class="features-list">
                    <h4>What you get:</h4>
                    <ul>
                        <li>Verified badge on your profile</li>
                        <li>Increased credibility with employers</li>
                        <li>Stand out from other job seekers</li>
                        <li>Higher chance of getting interviews</li>
                        <li>Priority in search results</li>
                        <li>Professional profile status</li>
                    </ul>
                </div>

                <?php if (isset($error_message)): ?>
                    <div class="alert error"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <form method="POST" class="nin-form">
                    <div class="form-group">
                        <label class="form-label" for="nin">Enter your 11-digit NIN:</label>
                        <input type="text" id="nin" name="nin" class="form-input" 
                               placeholder="12345678901" maxlength="11" required>
                    </div>
                    
                    <button type="submit" class="btn-verify">
                        🔒 Pay ₦1,000 & Verify Now
                    </button>
                </form>
                
                <div class="security-note">
                    <strong>🔒 Your Security Matters:</strong><br>
                    Your NIN is encrypted and stored securely. We comply with Nigerian data protection laws and never share your personal information with third parties.
                </div>
                
                <a href="../user/profile.php" class="btn-secondary">
                    Maybe Later
                </a>
            </div>
        <?php endif; ?>
    </main>

    <!-- Bottom Navigation -->
    <nav class="app-bottom-nav">
        <a href="../../index.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">🏠</div>
            <div class="app-bottom-nav-label">Home</div>
        </a>
        <a href="../jobs/browse.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">🔍</div>
            <div class="app-bottom-nav-label">Jobs</div>
        </a>
        <a href="../user/saved-jobs.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">❤️</div>
            <div class="app-bottom-nav-label">Saved</div>
        </a>
        <a href="../user/applications.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">📋</div>
            <div class="app-bottom-nav-label">Applications</div>
        </a>
        <a href="../user/dashboard.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">👤</div>
            <div class="app-bottom-nav-label">Profile</div>
        </a>
    </nav>

    <script>
        // NIN input validation
        document.getElementById('nin').addEventListener('input', function() {
            // Only allow digits and limit to 11 characters
            this.value = this.value.replace(/\D/g, '').slice(0, 11);
            
            // Visual feedback
            if (this.value.length === 11) {
                this.style.borderColor = 'var(--accent)';
                this.style.backgroundColor = '#f0f9f0';
            } else if (this.value.length > 0) {
                this.style.borderColor = 'var(--warning)';
                this.style.backgroundColor = '#fff8e1';
            } else {
                this.style.borderColor = '#e2e8f0';
                this.style.backgroundColor = 'white';
            }
        });

        // Form submission confirmation
        document.querySelector('form').addEventListener('submit', function(e) {
            const nin = document.getElementById('nin').value;
            
            if (nin.length !== 11) {
                e.preventDefault();
                alert('Please enter a valid 11-digit NIN.');
                return;
            }
            
            if (!confirm(`Confirm NIN Verification\n\nNIN: ${nin}\nAmount: ₦1,000\n\nProceed with payment?`)) {
                e.preventDefault();
            }
        });

        // Add body class for bottom nav
        document.body.classList.add('has-bottom-nav');
    </script>
</body>
</html>