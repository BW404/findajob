<?php
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/flutterwave.php';

if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = getCurrentUserId();

// Get user info with verification status
$stmt = $pdo->prepare("
    SELECT u.user_type, u.first_name, u.last_name, u.email,
           jsp.nin_verified as js_nin_verified, jsp.verification_boosted as js_verification_boosted,
           ep.company_cac_verified, ep.provider_nin_verified, ep.verification_boosted as ep_verification_boosted
    FROM users u
    LEFT JOIN job_seeker_profiles jsp ON u.id = jsp.user_id AND u.user_type = 'job_seeker'
    LEFT JOIN employer_profiles ep ON u.id = ep.user_id AND u.user_type = 'employer'
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$is_employer = ($user['user_type'] === 'employer');
$is_job_seeker = ($user['user_type'] === 'job_seeker');

// Check if already verified
$is_verified = false;
if ($is_job_seeker) {
    $is_verified = ($user['js_nin_verified'] == 1) || ($user['js_verification_boosted'] == 1);
} elseif ($is_employer) {
    $is_verified = ($user['provider_nin_verified'] == 1) || ($user['company_cac_verified'] == 1) || ($user['ep_verification_boosted'] == 1);
}

// Filter pricing plans based on user type
$user_plans = array_filter(PRICING_PLANS, function($plan) use ($is_employer, $is_job_seeker) {
    if ($is_employer) {
        return $plan['user_type'] === 'employer';
    } elseif ($is_job_seeker) {
        return $plan['user_type'] === 'job_seeker';
    }
    return false;
});

$page_title = 'Pricing Plans';
require_once '../../includes/header.php';
?>
    <link rel="stylesheet" href="../../assets/css/main.css">
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
            padding-bottom: 80px;
        }

        .page-header {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
            margin-top: 70px;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-header h1 {
            font-size: 42px;
            margin-bottom: 10px;
        }

        .page-header p {
            font-size: 18px;
            opacity: 0.9;
        }

        .user-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 8px 20px;
            border-radius: 20px;
            margin-top: 15px;
            font-size: 14px;
            font-weight: 600;
        }

        .container {
            max-width: 1200px;
            margin: -40px auto 60px;
            padding: 0 20px;
        }

        .section-title {
            text-align: center;
            margin: 60px 0 30px;
            font-size: 32px;
            color: #1a1a2e;
        }

        .section-subtitle {
            text-align: center;
            color: #6b7280;
            margin-bottom: 40px;
            font-size: 16px;
        }

        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }

        .pricing-card {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .pricing-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        }

        .pricing-card.featured {
            border: 3px solid #dc2626;
            transform: scale(1.05);
        }

        .featured-badge {
            position: absolute;
            top: 20px;
            right: -35px;
            background: #dc2626;
            color: white;
            padding: 5px 40px;
            transform: rotate(45deg);
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .plan-name {
            font-size: 24px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 15px;
        }

        .plan-price {
            font-size: 48px;
            font-weight: 800;
            color: #dc2626;
            margin-bottom: 5px;
        }

        .plan-price small {
            font-size: 18px;
            color: #6b7280;
            font-weight: 400;
        }

        .plan-duration {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .savings-badge {
            display: inline-block;
            background: #10b981;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .plan-features {
            list-style: none;
            margin: 30px 0;
            padding: 0;
        }

        .plan-features li {
            padding: 12px 0;
            color: #4b5563;
            font-size: 15px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .plan-features li i {
            color: #10b981;
            margin-top: 3px;
            flex-shrink: 0;
        }

        .plan-button {
            width: 100%;
            padding: 16px;
            background: #dc2626;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .plan-button:hover {
            background: #b91c1c;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(220, 38, 38, 0.3);
        }

        .plan-button:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
        }

        .plan-button.free {
            background: #10b981;
        }

        .plan-button.free:hover {
            background: #059669;
        }

        .loading-spinner {
            display: none;
            margin-left: 10px;
        }

        .loading-spinner.active {
            display: inline-block;
        }

        .back-link {
            display: inline-block;
            margin: 20px 0;
            color: #dc2626;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .back-link:hover {
            color: #b91c1c;
            gap: 10px;
        }

        .back-link i {
            margin-right: 8px;
        }

        .booster-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }

        .booster-card {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.2);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .booster-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(220, 38, 38, 0.3);
        }

        .booster-card h3 {
            font-size: 22px;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .booster-card .price {
            font-size: 36px;
            font-weight: 800;
            margin: 15px 0;
        }

        .booster-card .duration {
            opacity: 0.9;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 20px;
        }

        .booster-benefits {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
            backdrop-filter: blur(10px);
        }

        .booster-benefit-item {
            display: flex;
            align-items: flex-start;
            margin: 12px 0;
            font-size: 14px;
            line-height: 1.5;
        }

        .booster-benefit-item i {
            color: #10b981;
            background: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            margin-right: 12px;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .booster-card p {
            opacity: 0.9;
            margin-bottom: 20px;
        }

        .booster-card button {
            background: white;
            color: #dc2626;
            font-weight: 600;
        }

        .booster-card button:hover {
            background: #f9fafb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        @media (max-width: 768px) {
            .pricing-grid {
                grid-template-columns: 1fr;
            }
            
            .pricing-card.featured {
                transform: scale(1);
            }
        }
    </style>
</head>
<body>
    <div class="page-header">
        <div class="header-content">
            <h1>Choose Your Plan</h1>
            <p>Unlock your full potential with our premium plans</p>
            <span class="user-badge">
                <?= $is_employer ? '<i class="fas fa-building"></i> Employer' : '<i class="fas fa-user"></i> Job Seeker' ?>
            </span>
        </div>
    </div>

    <div class="container">
        <a href="<?= $is_employer ? '../company/dashboard.php' : '../user/dashboard.php' ?>" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <!-- Subscription Plans -->
        <h2 class="section-title">Subscription Plans</h2>
        <p class="section-subtitle">Choose the plan that works best for you</p>

        <div class="pricing-grid">
            <?php
            $subscriptions = array_filter($user_plans, function($plan) {
                return $plan['type'] === 'subscription';
            });
            
            foreach ($subscriptions as $plan_key => $plan):
                $is_pro_yearly = strpos($plan_key, 'yearly') !== false;
            ?>
                <div class="pricing-card <?= $is_pro_yearly ? 'featured' : '' ?>">
                    <?php if ($is_pro_yearly): ?>
                        <div class="featured-badge">Best Value</div>
                    <?php endif; ?>
                    
                    <div class="plan-name"><?= htmlspecialchars($plan['name']) ?></div>
                    
                    <div class="plan-price">
                        ₦<?= number_format($plan['price']) ?>
                        <?php if ($plan['price'] > 0): ?>
                            <small>/<?= strpos($plan_key, 'monthly') !== false ? 'month' : 'year' ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="plan-duration"><?= htmlspecialchars($plan['duration']) ?></div>
                    
                    <?php if (isset($plan['savings'])): ?>
                        <div class="savings-badge">💰 <?= htmlspecialchars($plan['savings']) ?></div>
                    <?php endif; ?>
                    
                    <ul class="plan-features">
                        <?php if ($is_employer): ?>
                            <?php if ($plan['price'] == 0): ?>
                                <li><i class="fas fa-check"></i> Post up to 3 jobs</li>
                                <li><i class="fas fa-check"></i> Basic job listings</li>
                                <li><i class="fas fa-check"></i> Standard support</li>
                                <li><i class="fas fa-check"></i> 30-day job visibility</li>
                            <?php else: ?>
                                <li><i class="fas fa-check"></i> Unlimited job postings</li>
                                <li><i class="fas fa-check"></i> Featured company profile</li>
                                <li><i class="fas fa-check"></i> Priority applicant access</li>
                                <li><i class="fas fa-check"></i> Advanced analytics</li>
                                <li><i class="fas fa-check"></i> Priority support</li>
                                <li><i class="fas fa-check"></i> 60-day job visibility</li>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if ($plan['price'] == 0): ?>
                                <li><i class="fas fa-check"></i> Basic job search</li>
                                <li><i class="fas fa-check"></i> Apply to jobs</li>
                                <li><i class="fas fa-check"></i> Save favorite jobs</li>
                                <li><i class="fas fa-check"></i> Basic profile</li>
                            <?php else: ?>
                                <li><i class="fas fa-check"></i> Priority application status</li>
                                <li><i class="fas fa-check"></i> Profile boost in searches</li>
                                <li><i class="fas fa-check"></i> Unlimited job applications</li>
                                <li><i class="fas fa-check"></i> Advanced CV builder</li>
                                <li><i class="fas fa-check"></i> Email job alerts</li>
                                <li><i class="fas fa-check"></i> Priority support</li>
                            <?php endif; ?>
                        <?php endif; ?>
                    </ul>
                    
                    <?php if ($plan['price'] == 0): ?>
                        <button class="plan-button free" disabled>Current Plan</button>
                    <?php else: ?>
                        <button 
                            class="plan-button" 
                            onclick="initiatePayment('<?= $plan_key ?>', <?= $plan['price'] ?>, '<?= htmlspecialchars($plan['name']) ?>')">
                            Subscribe Now
                            <span class="loading-spinner"><i class="fas fa-spinner fa-spin"></i></span>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Boosters -->
        <h2 class="section-title">Boost Your <?= $is_employer ? 'Jobs' : 'Profile' ?></h2>
        <p class="section-subtitle">One-time purchases to enhance your visibility</p>

        <div class="booster-grid">
            <?php
            $boosters = array_filter($user_plans, function($plan) {
                return in_array($plan['type'], ['booster', 'job_booster']);
            });
            
            foreach ($boosters as $plan_key => $plan):
                // Check if this is a verification booster and user is already verified
                $is_verification_booster = in_array($plan_key, ['job_seeker_verification_booster', 'employer_verification_booster']);
                $already_verified = $is_verification_booster && $is_verified;
            ?>
                <div class="booster-card">
                    <h3><?= htmlspecialchars($plan['name']) ?></h3>
                    <div class="price">₦<?= number_format($plan['price']) ?></div>
                    <p class="duration"><?= htmlspecialchars($plan['duration']) ?></p>
                    
                    <?php if (isset($plan['benefits'])): ?>
                        <div class="booster-benefits">
                            <?php
                            $benefits = explode(' • ', $plan['benefits']);
                            foreach ($benefits as $benefit):
                            ?>
                                <div class="booster-benefit-item">
                                    <i class="fas fa-check"></i>
                                    <span><?= htmlspecialchars(trim($benefit)) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($plan['jobs_count'])): ?>
                        <div style="background: rgba(255, 255, 255, 0.15); padding: 12px; border-radius: 8px; margin: 15px 0;">
                            <strong style="font-size: 18px;"><?= $plan['jobs_count'] ?> Job<?= $plan['jobs_count'] > 1 ? 's' : '' ?></strong>
                            <div style="font-size: 12px; opacity: 0.9; margin-top: 5px;">Boosted to top</div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($already_verified): ?>
                        <button class="plan-button free" disabled style="background: white; color: #10b981; border: 2px solid #10b981; cursor: not-allowed;">
                            <i class="fas fa-check-circle"></i> Already Verified
                        </button>
                    <?php else: ?>
                        <button 
                            class="plan-button" 
                            onclick="initiatePayment('<?= $plan_key ?>', <?= $plan['price'] ?>, '<?= htmlspecialchars($plan['name']) ?>')">
                            Buy Now
                            <span class="loading-spinner"><i class="fas fa-spinner fa-spin"></i></span>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        async function initiatePayment(serviceType, amount, serviceName) {
            const button = event.target;
            const spinner = button.querySelector('.loading-spinner');
            
            button.disabled = true;
            if (spinner) spinner.classList.add('active');

            try {
                const formData = new FormData();
                formData.append('action', 'initialize_payment');
                formData.append('amount', amount);
                formData.append('service_type', serviceType);
                formData.append('description', 'Payment for ' + serviceName);

                const response = await fetch('../../api/payment.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    // Redirect to Flutterwave payment page
                    window.location.href = data.data.payment_link;
                } else {
                    alert('Error: ' + data.message);
                    button.disabled = false;
                    if (spinner) spinner.classList.remove('active');
                }
            } catch (error) {
                console.error('Payment error:', error);
                alert('An error occurred. Please try again.');
                button.disabled = false;
                if (spinner) spinner.classList.remove('active');
            }
        }
    </script>

    <!-- Bottom Navigation for Mobile -->
    <nav class="app-bottom-nav">
        <?php if ($is_job_seeker): ?>
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
                <div class="app-bottom-nav-icon">📄</div>
                <div class="app-bottom-nav-label">Applications</div>
            </a>
            <a href="../user/dashboard.php" class="app-bottom-nav-item">
                <div class="app-bottom-nav-icon">👤</div>
                <div class="app-bottom-nav-label">Profile</div>
            </a>
        <?php else: ?>
            <a href="../../index.php" class="app-bottom-nav-item">
                <div class="app-bottom-nav-icon">🏠</div>
                <div class="app-bottom-nav-label">Home</div>
            </a>
            <a href="../company/post-job.php" class="app-bottom-nav-item">
                <div class="app-bottom-nav-icon">➕</div>
                <div class="app-bottom-nav-label">Post Job</div>
            </a>
            <a href="../company/jobs.php" class="app-bottom-nav-item">
                <div class="app-bottom-nav-icon">💼</div>
                <div class="app-bottom-nav-label">My Jobs</div>
            </a>
            <a href="../company/applicants.php" class="app-bottom-nav-item">
                <div class="app-bottom-nav-icon">👥</div>
                <div class="app-bottom-nav-label">Applicants</div>
            </a>
            <a href="../company/dashboard.php" class="app-bottom-nav-item">
                <div class="app-bottom-nav-icon">📊</div>
                <div class="app-bottom-nav-label">Dashboard</div>
            </a>
        <?php endif; ?>
    </nav>

<?php require_once '../../includes/footer.php'; ?>
