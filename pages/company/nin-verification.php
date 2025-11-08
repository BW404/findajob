<?php
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/constants.php';

requireEmployer();

$userId = getCurrentUserId();

// Get employer profile data with provider NIN verification status
$stmt = $pdo->prepare("
    SELECT u.*, ep.* 
    FROM users u 
    LEFT JOIN employer_profiles ep ON u.id = ep.user_id 
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Representative NIN Verification - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <style>
        .verification-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        
        .verification-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 3rem 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .verification-header h1 {
            margin: 0 0 1rem 0;
            font-size: 2.5rem;
        }
        
        .verification-header p {
            font-size: 1.1rem;
            opacity: 0.95;
            margin: 0;
        }
        
        .verification-card {
            background: white;
            border-radius: 12px;
            padding: 3rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .verified-status {
            text-align: center;
            padding: 2rem;
        }
        
        .verification-badge {
            display: inline-block;
            background: var(--accent);
            color: white;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .verification-details {
            background: #f0fdf4;
            border: 2px solid #86efac;
            border-radius: 12px;
            padding: 2rem;
            margin-top: 2rem;
        }
        
        .verification-details p {
            margin: 0.5rem 0;
            color: #166534;
            font-size: 1.05rem;
        }
        
        .verification-service {
            text-align: center;
        }
        
        .service-header h2 {
            color: var(--primary);
            margin-bottom: 0.5rem;
            font-size: 1.8rem;
        }
        
        .service-price {
            font-size: 3rem;
            font-weight: bold;
            color: var(--primary);
            margin: 1rem 0;
        }
        
        .service-price span {
            font-size: 1.2rem;
            color: var(--text-secondary);
            font-weight: normal;
        }
        
        .service-benefits {
            text-align: left;
            margin: 2rem 0;
            background: #f9fafb;
            padding: 2rem;
            border-radius: 8px;
        }
        
        .service-benefits h3 {
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .service-benefits ul {
            list-style: none;
            padding: 0;
            display: grid;
            gap: 1rem;
        }
        
        .service-benefits li {
            padding: 0.75rem 0;
            color: var(--text-primary);
            font-size: 1.05rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .service-benefits li::before {
            content: '✓';
            background: var(--accent);
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            flex-shrink: 0;
        }
        
        .verification-process {
            margin: 2rem 0;
            text-align: left;
        }
        
        .verification-process h3 {
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .process-steps {
            display: grid;
            gap: 1.5rem;
        }
        
        .step {
            display: flex;
            align-items: flex-start;
            gap: 1.5rem;
            background: #fafafa;
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid var(--primary);
        }
        
        .step-number {
            width: 40px;
            height: 40px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        
        .step-content h4 {
            margin: 0 0 0.5rem 0;
            color: var(--text-primary);
        }
        
        .step-content p {
            margin: 0;
            color: var(--text-secondary);
        }
        
        .btn-verify-nin {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 1.25rem 3rem;
            border: none;
            border-radius: 50px;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 2rem;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .btn-verify-nin:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(220, 38, 38, 0.3);
        }
        
        .security-note {
            background: #eff6ff;
            border: 1px solid #93c5fd;
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 2rem;
            text-align: left;
        }
        
        .security-note h4 {
            color: #1e40af;
            margin: 0 0 1rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .security-note p {
            color: #1e40af;
            margin: 0;
            line-height: 1.6;
        }
        
        /* NIN Verification Modal */
        .nin-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(4px);
            z-index: 10000;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.2s ease;
        }
        
        .nin-modal.active {
            display: flex;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .nin-modal-content {
            background: white;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideUp 0.3s ease;
        }
        
        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .nin-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .nin-modal-header h3 {
            margin: 0;
            color: var(--primary);
            font-size: 1.5rem;
        }
        
        .nin-modal-close {
            background: none;
            border: none;
            font-size: 2rem;
            color: #6b7280;
            cursor: pointer;
            line-height: 1;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s;
        }
        
        .nin-modal-close:hover {
            background: #f3f4f6;
            color: var(--primary);
        }
        
        .nin-modal-body {
            padding: 2rem;
        }
        
        .nin-alert {
            padding: 1rem 1.25rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: none;
        }
        
        .nin-alert.active {
            display: block;
        }
        
        .nin-alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .nin-alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .nin-form-group {
            margin-bottom: 1.5rem;
        }
        
        .nin-form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .nin-form-group input {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1.1rem;
            transition: border-color 0.2s;
            letter-spacing: 1px;
        }
        
        .nin-form-group input:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .verification-cost {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
        }
        
        .cost-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 1.1rem;
        }
        
        .cost-item strong {
            color: var(--primary);
            font-size: 1.3rem;
        }
        
        .btn-submit-verification {
            width: 100%;
            background: var(--primary);
            color: white;
            border: none;
            padding: 1.25rem;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn-submit-verification:hover:not(:disabled) {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(220, 38, 38, 0.3);
        }
        
        .btn-submit-verification:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .loading-spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .btn-submit-verification.loading .loading-spinner {
            display: block;
        }
        
        @media (max-width: 768px) {
            .verification-container {
                padding: 1rem;
            }
            
            .verification-card {
                padding: 2rem 1.5rem;
            }
            
            .verification-header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body class="has-bottom-nav">
    <header class="site-header">
        <div class="container">
            <nav class="site-nav">
                <a href="/findajob" class="site-logo">
                    <img src="/findajob/assets/images/logo_full.png" alt="FindAJob Nigeria" class="site-logo-img">
                </a>
                <div>
                    <a href="dashboard.php" class="btn btn-outline">Dashboard</a>
                    <a href="profile.php" class="btn btn-outline">Profile</a>
                    <a href="../auth/logout.php" class="btn btn-secondary">Logout</a>
                </div>
            </nav>
        </div>
    </header>

    <main class="verification-container">
        <div class="verification-header">
            <h1>🛡️ Representative NIN Verification</h1>
            <p>Verify your company representative's identity and build trust with job seekers</p>
        </div>

        <?php if ($user['provider_nin_verified']): ?>
            <!-- Verified Status -->
            <div class="verification-card">
                <div class="verified-status">
                    <div class="verification-badge">
                        ✓ Representative NIN Verified
                    </div>
                    <h2>Congratulations!</h2>
                    <p>Your company representative's NIN has been successfully verified.</p>
                    
                    <div class="verification-details">
                        <p><strong>Representative:</strong> <?php echo htmlspecialchars($user['provider_first_name'] . ' ' . $user['provider_last_name']); ?></p>
                        <p><strong>NIN:</strong> <?php echo htmlspecialchars(substr($user['provider_nin'], 0, 4) . '****' . substr($user['provider_nin'], -3)); ?></p>
                        <p><strong>Verified on:</strong> <?php echo date('F j, Y', strtotime($user['provider_nin_verified_at'])); ?></p>
                    </div>
                    
                    <p style="color: var(--accent); margin-top: 2rem; font-size: 1.05rem;">
                        <strong>Your company profile now has enhanced credibility!</strong> Job seekers will see that your representative is verified, increasing trust and application rates.
                    </p>
                </div>
            </div>
        <?php else: ?>
            <!-- Verification Service -->
            <div class="verification-card">
                <div class="verification-service">
                    <div class="service-header">
                        <h2>🏆 Get Your Verified Representative Badge</h2>
                        <div class="service-price">₦<?php echo number_format(NIN_VERIFICATION_FEE, 0); ?> <span>one-time fee</span></div>
                    </div>
                    
                    <div class="service-benefits">
                        <h3>Why Verify Your Representative's NIN?</h3>
                        <ul>
                            <li>Build trust with job seekers by verifying your representative's identity</li>
                            <li>Get a verified badge on your company profile</li>
                            <li>Stand out from other employers on the platform</li>
                            <li>Attract higher quality applicants</li>
                            <li>Secure and confidential verification process</li>
                            <li>Compliance with identity verification standards</li>
                        </ul>
                    </div>
                    
                    <div class="verification-process">
                        <h3>How It Works:</h3>
                        <div class="process-steps">
                            <div class="step">
                                <div class="step-number">1</div>
                                <div class="step-content">
                                    <h4>Click "Verify Representative NIN"</h4>
                                    <p>Start the verification process by clicking the button below</p>
                                </div>
                            </div>
                            <div class="step">
                                <div class="step-number">2</div>
                                <div class="step-content">
                                    <h4>Enter Representative's NIN</h4>
                                    <p>Provide the 11-digit National Identification Number</p>
                                </div>
                            </div>
                            <div class="step">
                                <div class="step-number">3</div>
                                <div class="step-content">
                                    <h4>Instant Verification</h4>
                                    <p>Our system verifies the NIN with NIMC database instantly</p>
                                </div>
                            </div>
                            <div class="step">
                                <div class="step-number">4</div>
                                <div class="step-content">
                                    <h4>Get Verified Badge</h4>
                                    <p>Your profile gets a verified badge immediately</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" class="btn-verify-nin" onclick="openNINModal()">
                        <span>🛡️</span>
                        <span>Verify Representative NIN Now</span>
                    </button>
                    
                    <div class="security-note">
                        <h4>🔒 Your Privacy & Security</h4>
                        <p>
                            Your representative's NIN and personal data are encrypted and stored securely. 
                            We comply with Nigerian Data Protection Regulations (NDPR) and only use this 
                            information for identity verification purposes. Your data is never shared with 
                            third parties without your consent.
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 2rem;">
            <a href="profile.php" class="btn btn-outline">← Back to Profile</a>
        </div>
    </main>

    <!-- NIN Verification Modal -->
    <div class="nin-modal" id="ninModal">
        <div class="nin-modal-content">
            <div class="nin-modal-header">
                <h3>🛡️ Verify Representative NIN</h3>
                <button class="nin-modal-close" onclick="closeNINModal()">&times;</button>
            </div>
            <div class="nin-modal-body">
                <div class="nin-alert nin-alert-success" id="successAlert"></div>
                <div class="nin-alert nin-alert-error" id="errorAlert"></div>
                
                <form id="ninVerificationForm">
                    <div class="nin-form-group">
                        <label for="nin">Representative's NIN (11 digits)</label>
                        <input type="text" 
                               id="nin" 
                               name="nin" 
                               maxlength="11" 
                               pattern="\d{11}"
                               placeholder="12345678901"
                               required>
                        <small style="color: var(--text-secondary); display: block; margin-top: 0.5rem;">
                            Enter the 11-digit National Identification Number
                        </small>
                    </div>
                    
                    <div class="verification-cost">
                        <div class="cost-item">
                            <span>Verification Fee:</span>
                            <strong>₦<?php echo number_format(NIN_VERIFICATION_FEE, 0); ?></strong>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-submit-verification" id="submitBtn">
                        <span class="loading-spinner"></span>
                        <span id="submitBtnText">Verify Now</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>

    <script>
        function openNINModal() {
            document.getElementById('ninModal').classList.add('active');
            document.getElementById('nin').focus();
        }
        
        function closeNINModal() {
            document.getElementById('ninModal').classList.remove('active');
            document.getElementById('ninVerificationForm').reset();
            hideAlerts();
        }
        
        function showSuccessAlert(message) {
            const alert = document.getElementById('successAlert');
            alert.textContent = message;
            alert.classList.add('active');
            document.getElementById('errorAlert').classList.remove('active');
        }
        
        function showErrorAlert(message) {
            const alert = document.getElementById('errorAlert');
            alert.textContent = message;
            alert.classList.add('active');
            document.getElementById('successAlert').classList.remove('active');
        }
        
        function hideAlerts() {
            document.getElementById('successAlert').classList.remove('active');
            document.getElementById('errorAlert').classList.remove('active');
        }
        
        // Handle NIN verification form submission
        document.getElementById('ninVerificationForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const ninInput = document.getElementById('nin');
            const nin = ninInput.value.trim();
            const submitBtn = document.getElementById('submitBtn');
            const submitBtnText = document.getElementById('submitBtnText');
            
            // Validate NIN
            if (!/^\d{11}$/.test(nin)) {
                showErrorAlert('Please enter a valid 11-digit NIN');
                return;
            }
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.classList.add('loading');
            submitBtnText.textContent = 'Verifying...';
            hideAlerts();
            
            try {
                const response = await fetch('/findajob/api/verify-employer-nin.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ nin: nin })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSuccessAlert('✓ NIN verified successfully! Redirecting...');
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    showErrorAlert(result.error || 'Verification failed. Please try again.');
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('loading');
                    submitBtnText.textContent = 'Verify Now';
                }
            } catch (error) {
                console.error('Verification error:', error);
                showErrorAlert('An error occurred. Please try again later.');
                submitBtn.disabled = false;
                submitBtn.classList.remove('loading');
                submitBtnText.textContent = 'Verify Now';
            }
        });
        
        // Close modal on outside click
        document.getElementById('ninModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeNINModal();
            }
        });
        
        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeNINModal();
            }
        });
    </script>
</body>
</html>
