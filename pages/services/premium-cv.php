<?php
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/constants.php';
require_once '../../includes/functions.php';

requireJobSeeker();

$user_id = getCurrentUserId();
$success_message = '';
$error_message = '';

// Get user info
$stmt = $pdo->prepare("SELECT first_name, last_name, email, phone FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get user's CVs
$cvStmt = $pdo->prepare("SELECT id, title, file_path FROM cvs WHERE user_id = ? ORDER BY is_primary DESC, created_at DESC");
$cvStmt->execute([$user_id]);
$user_cvs = $cvStmt->fetchAll(PDO::FETCH_ASSOC);

// Get CV plans
require_once '../../config/flutterwave.php';
$cv_plans = array_filter(PRICING_PLANS, function($plan) {
    return isset($plan['type']) && $plan['type'] === 'cv_service';
});

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['plan_type'])) {
    $plan_type = $_POST['plan_type'];
    $contact_phone = trim($_POST['contact_phone'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? $user['email']);
    $current_cv_id = !empty($_POST['current_cv']) ? (int)$_POST['current_cv'] : null;
    $additional_notes = trim($_POST['additional_notes'] ?? '');
    
    // Validate plan
    if (!array_key_exists($plan_type, $cv_plans)) {
        $error_message = 'Invalid CV plan selected';
    } else {
        $plan = $cv_plans[$plan_type];
        $amount = $plan['price'];
        
        // Get CV file path if selected
        $cv_file = null;
        if ($current_cv_id) {
            $cvFileStmt = $pdo->prepare("SELECT file_path FROM cvs WHERE id = ? AND user_id = ?");
            $cvFileStmt->execute([$current_cv_id, $user_id]);
            $cvData = $cvFileStmt->fetch();
            if ($cvData) {
                $cv_file = $cvData['file_path'];
            }
        }
        
        // Insert request
        try {
            $stmt = $pdo->prepare("
                INSERT INTO premium_cv_requests 
                (user_id, plan_type, amount, contact_phone, contact_email, current_cv_file, additional_notes, status, payment_status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', 'pending')
            ");
            $stmt->execute([
                $user_id,
                $plan_type,
                $amount,
                $contact_phone,
                $contact_email,
                $cv_file,
                $additional_notes
            ]);
            
            $request_id = $pdo->lastInsertId();
            
            // Store request ID in session for payment completion
            $_SESSION['pending_cv_request_id'] = $request_id;
            $_SESSION['pending_cv_plan'] = $plan_type;
            
            // Set success message and show payment button
            $success_message = 'Request created successfully! Please proceed to payment.';
            $show_payment = true;
            $payment_plan_key = $plan_type;
            $payment_amount = $amount;
            $payment_description = $plan['name'];
            
        } catch (PDOException $e) {
            error_log("Premium CV request error: " . $e->getMessage());
            $error_message = 'Failed to create request. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium CV Writing Service - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .cv-plans-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }
        
        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin: 2rem 0;
        }
        
        .plan-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            border: 3px solid transparent;
        }
        
        .plan-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        }
        
        .plan-card.popular {
            border-color: #dc2626;
            position: relative;
        }
        
        .plan-card.popular::before {
            content: '⭐ MOST POPULAR';
            position: absolute;
            top: -12px;
            right: 20px;
            background: #dc2626;
            color: white;
            padding: 0.35rem 1rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        
        .plan-header {
            text-align: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid #f3f4f6;
        }
        
        .plan-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0.5rem;
        }
        
        .plan-price {
            font-size: 2.5rem;
            font-weight: 800;
            color: #dc2626;
            margin-bottom: 0.25rem;
        }
        
        .plan-duration {
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .plan-features {
            list-style: none;
            padding: 0;
            margin: 0 0 1.5rem 0;
        }
        
        .plan-features li {
            padding: 0.75rem 0;
            display: flex;
            align-items: start;
            gap: 0.75rem;
            color: #374151;
        }
        
        .plan-features i {
            color: #10b981;
            margin-top: 0.25rem;
        }
        
        .plan-button {
            width: 100%;
            padding: 1rem;
            background: #dc2626;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .plan-button:hover {
            background: #b91c1c;
        }
        
        .request-form {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-top: 3rem;
            display: none;
        }
        
        .request-form.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="cv-plans-container">
        <div style="text-align: center; margin-bottom: 3rem;">
            <h1 style="font-size: 2.5rem; margin-bottom: 1rem; color: #111827;">
                <i class="fas fa-crown"></i> Professional CV Writing Service
            </h1>
            <p style="font-size: 1.125rem; color: #6b7280; max-width: 700px; margin: 0 auto;">
                Let our expert CV writers create a compelling, ATS-optimized resume that gets you noticed. 
                Choose the plan that works best for you.
            </p>
        </div>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error" style="margin-bottom: 2rem;">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success" style="margin-bottom: 2rem;">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
            <?php if (isset($show_payment) && $show_payment): ?>
                <div style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); text-align: center;">
                    <h3 style="margin-bottom: 1rem;">Complete Your Payment</h3>
                    <p style="color: #6b7280; margin-bottom: 1.5rem;">Click the button below to proceed with payment for your <?php echo htmlspecialchars($payment_description); ?> service.</p>
                    <button onclick="initiatePayment('<?php echo $payment_plan_key; ?>', <?php echo $payment_amount; ?>, '<?php echo htmlspecialchars($payment_description); ?>')" 
                            class="btn btn-primary" style="padding: 1rem 3rem; font-size: 1.125rem;">
                        <i class="fas fa-credit-card"></i> Pay ₦<?php echo number_format($payment_amount); ?>
                    </button>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <!-- Free Option -->
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem; border-radius: 16px; margin-bottom: 2rem; text-align: center;">
            <h2 style="margin: 0 0 0.75rem 0; font-size: 1.75rem;">
                <i class="fas fa-magic"></i> CV Basic (Free)
            </h2>
            <p style="margin: 0 0 1.5rem 0; opacity: 0.95;">
                Create your own professional CV using our AI-powered wizard
            </p>
            <a href="cv-generator.php" class="btn" style="background: white; color: #667eea; padding: 0.875rem 2rem; text-decoration: none; display: inline-block; border-radius: 8px; font-weight: 600;">
                <i class="fas fa-sparkles"></i> Start Creating for Free
            </a>
        </div>
        
        <!-- Premium Plans -->
        <h2 style="text-align: center; font-size: 2rem; margin-bottom: 2rem; color: #111827;">
            Premium Plans
        </h2>
        
        <div class="plans-grid">
            <?php foreach ($cv_plans as $plan_key => $plan): ?>
                <div class="plan-card <?php echo $plan_key === 'cv_pro' ? 'popular' : ''; ?>">
                    <div class="plan-header">
                        <div class="plan-name"><?php echo htmlspecialchars($plan['name']); ?></div>
                        <div class="plan-price">₦<?php echo number_format($plan['price']); ?></div>
                        <div class="plan-duration"><?php echo htmlspecialchars($plan['duration']); ?></div>
                    </div>
                    
                    <ul class="plan-features">
                        <?php foreach ($plan['features'] as $feature): ?>
                            <li>
                                <i class="fas fa-check-circle"></i>
                                <span><?php echo htmlspecialchars($feature); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <button class="plan-button" onclick="selectPlan('<?php echo $plan_key; ?>', '<?php echo htmlspecialchars($plan['name']); ?>')">
                        <i class="fas fa-shopping-cart"></i> Order Now
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Request Form -->
        <div id="request-form" class="request-form">
            <h2 style="margin-bottom: 1.5rem; color: #111827;">
                <i class="fas fa-file-alt"></i> Complete Your Order
            </h2>
            
            <form method="POST" action="">
                <input type="hidden" name="plan_type" id="selected_plan" value="">
                
                <div class="form-group">
                    <label>Selected Plan</label>
                    <input type="text" id="plan_display" readonly style="background: #f3f4f6;">
                </div>
                
                <div class="form-group">
                    <label>Contact Phone <span style="color: #dc2626;">*</span></label>
                    <input type="tel" name="contact_phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required placeholder="e.g. 08012345678">
                </div>
                
                <div class="form-group">
                    <label>Contact Email <span style="color: #dc2626;">*</span></label>
                    <input type="email" name="contact_email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Upload Your Current CV (Optional)</label>
                    <select name="current_cv">
                        <option value="">-- None (Start from scratch) --</option>
                        <?php foreach ($user_cvs as $cv): ?>
                            <option value="<?php echo $cv['id']; ?>">
                                <?php echo htmlspecialchars($cv['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: #6b7280; display: block; margin-top: 0.5rem;">
                        Upload a current CV to help our writers understand your background
                    </small>
                </div>
                
                <div class="form-group">
                    <label>Additional Notes</label>
                    <textarea name="additional_notes" placeholder="Tell us about your target role, industry preferences, specific requirements, or any other information that will help us create the perfect CV for you..."></textarea>
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="button" onclick="cancelRequest()" class="btn btn-secondary" style="flex: 1;">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" style="flex: 2;">
                        <i class="fas fa-credit-card"></i> Proceed to Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
    
    <script>
        function selectPlan(planKey, planName) {
            document.getElementById('selected_plan').value = planKey;
            document.getElementById('plan_display').value = planName;
            document.getElementById('request-form').classList.add('active');
            document.getElementById('request-form').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        
        function cancelRequest() {
            document.getElementById('request-form').classList.remove('active');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        async function initiatePayment(planKey, amount, description) {
            try {
                // Show loading state
                const button = event.target;
                const originalText = button.innerHTML;
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                
                // Get the request ID from session (set by PHP when form is submitted)
                const cvRequestId = <?php echo isset($_SESSION['pending_cv_request_id']) ? $_SESSION['pending_cv_request_id'] : 'null'; ?>;
                
                const formData = new URLSearchParams({
                    action: 'initialize_payment',
                    service_type: planKey,
                    amount: amount,
                    description: description,
                    redirect_url: window.location.href
                });
                
                // Add CV request ID if available
                if (cvRequestId) {
                    formData.append('cv_request_id', cvRequestId);
                }
                
                const response = await fetch('/findajob/api/payment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success && data.data && data.data.payment_link) {
                    // Redirect to Flutterwave payment page
                    window.location.href = data.data.payment_link;
                } else {
                    // Show error message
                    button.disabled = false;
                    button.innerHTML = originalText;
                    alert(data.message || 'Failed to initialize payment. Please try again.');
                }
            } catch (error) {
                console.error('Payment error:', error);
                alert('An error occurred. Please try again.');
                const button = event.target;
                button.disabled = false;
            }
        }
    </script>
</body>
</html>
