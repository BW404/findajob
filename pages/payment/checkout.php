<?php
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/flutterwave.php';

if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = getCurrentUserId();

// Get user info
$stmt = $pdo->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Pricing plans
$pricing = [
    'job_posting' => [
        'name' => 'Job Posting',
        'price' => 5000,
        'description' => 'Post a job for 30 days'
    ],
    'featured_listing' => [
        'name' => 'Featured Listing',
        'price' => 10000,
        'description' => 'Feature your job for 30 days'
    ],
    'cv_service' => [
        'name' => 'Professional CV Writing',
        'price' => 15000,
        'description' => 'Get a professionally written CV'
    ],
    'subscription' => [
        'name' => 'Premium Subscription',
        'price' => 20000,
        'description' => 'Unlimited job postings for 30 days'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make Payment - FindAJob</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/main.css">
    <style>
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            padding: 40px 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .pricing-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .pricing-card:hover {
            transform: translateY(-5px);
        }

        .pricing-card h3 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #1a1a2e;
        }

        .pricing-card .price {
            font-size: 36px;
            font-weight: 700;
            color: #dc2626;
            margin: 15px 0;
        }

        .pricing-card p {
            color: #6b7280;
            margin-bottom: 20px;
        }

        .pay-btn {
            width: 100%;
            padding: 14px;
            background: #dc2626;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .pay-btn:hover {
            background: #b91c1c;
        }

        .pay-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }

        .page-header {
            text-align: center;
            padding: 60px 20px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .page-header h1 {
            font-size: 42px;
            margin-bottom: 10px;
        }

        .loading {
            display: none;
        }

        .loading.active {
            display: inline-block;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="page-header">
        <h1>Choose Your Service</h1>
        <p>Select a payment option to continue</p>
    </div>

    <div class="pricing-grid">
        <?php foreach ($pricing as $service_type => $plan): ?>
            <div class="pricing-card">
                <h3><?= htmlspecialchars($plan['name']) ?></h3>
                <div class="price"><?= formatAmount($plan['price']) ?></div>
                <p><?= htmlspecialchars($plan['description']) ?></p>
                <button 
                    class="pay-btn" 
                    onclick="initiatePayment('<?= $service_type ?>', <?= $plan['price'] ?>, '<?= htmlspecialchars($plan['name']) ?>')">
                    Pay Now <span class="loading"><i class="fas fa-spinner fa-spin"></i></span>
                </button>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
        async function initiatePayment(serviceType, amount, serviceName) {
            const button = event.target;
            button.disabled = true;
            button.querySelector('.loading').classList.add('active');

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
                    button.querySelector('.loading').classList.remove('active');
                }
            } catch (error) {
                console.error('Payment error:', error);
                alert('An error occurred. Please try again.');
                button.disabled = false;
                button.querySelector('.loading').classList.remove('active');
            }
        }
    </script>
</body>
</html>
