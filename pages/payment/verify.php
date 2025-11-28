<?php
require_once '../../config/database.php';
require_once '../../config/session.php';

if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

$tx_ref = $_GET['tx_ref'] ?? '';
$transaction_id = $_GET['transaction_id'] ?? '';
$status = $_GET['status'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Payment - FindAJob</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .verification-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
        }

        .loader {
            width: 80px;
            height: 80px;
            border: 8px solid #f3f3f3;
            border-top: 8px solid #dc2626;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 30px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .status-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }

        .success-icon {
            color: #10b981;
        }

        .error-icon {
            color: #ef4444;
        }

        h1 {
            font-size: 28px;
            margin-bottom: 15px;
            color: #1a1a2e;
        }

        p {
            font-size: 16px;
            color: #6b7280;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .transaction-details {
            background: #f9fafb;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: #6b7280;
            font-size: 14px;
        }

        .detail-value {
            color: #1a1a2e;
            font-weight: 600;
            font-size: 14px;
        }

        .btn {
            display: inline-block;
            padding: 14px 30px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            margin: 10px 5px;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #dc2626;
            color: white;
        }

        .btn-primary:hover {
            background: #b91c1c;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        #verification-content {
            display: none;
        }

        #verification-content.show {
            display: block;
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <div id="loading-state">
            <div class="loader"></div>
            <h1>Verifying Payment</h1>
            <p>Please wait while we confirm your payment...</p>
        </div>

        <div id="verification-content"></div>
    </div>

    <script>
        const txRef = '<?= htmlspecialchars($tx_ref) ?>';
        const transactionId = '<?= htmlspecialchars($transaction_id) ?>';
        const status = '<?= htmlspecialchars($status) ?>';

        async function verifyPayment() {
            try {
                const response = await fetch(`../../api/payment.php?action=verify_payment&tx_ref=${txRef}&transaction_id=${transactionId}`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();

                document.getElementById('loading-state').style.display = 'none';
                const content = document.getElementById('verification-content');
                content.classList.add('show');

                if (data.success) {
                    content.innerHTML = `
                        <div class="status-icon success-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h1>Payment Successful!</h1>
                        <p>${data.message}</p>
                        <div class="transaction-details">
                            <div class="detail-row">
                                <span class="detail-label">Transaction Reference</span>
                                <span class="detail-value">${data.data.tx_ref}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Amount</span>
                                <span class="detail-value">${data.data.amount}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Payment Method</span>
                                <span class="detail-value">${data.data.payment_method || 'Card'}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Status</span>
                                <span class="detail-value" style="color: #10b981;">Success</span>
                            </div>
                        </div>
                        <a href="../user/dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                        <a href="../user/transactions.php" class="btn btn-secondary">View Transactions</a>
                    `;
                } else {
                    content.innerHTML = `
                        <div class="status-icon error-icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <h1>Payment Verification Failed</h1>
                        <p>${data.message}</p>
                        <a href="../user/dashboard.php" class="btn btn-primary">Back to Dashboard</a>
                        <a href="javascript:location.reload()" class="btn btn-secondary">Try Again</a>
                    `;
                }
            } catch (error) {
                console.error('Verification error:', error);
                document.getElementById('loading-state').style.display = 'none';
                const content = document.getElementById('verification-content');
                content.classList.add('show');
                content.innerHTML = `
                    <div class="status-icon error-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <h1>Verification Error</h1>
                    <p>An error occurred while verifying your payment. Please contact support.</p>
                    <a href="../user/dashboard.php" class="btn btn-primary">Back to Dashboard</a>
                `;
            }
        }

        // Start verification
        if (txRef && transactionId) {
            verifyPayment();
        } else {
            document.getElementById('loading-state').style.display = 'none';
            const content = document.getElementById('verification-content');
            content.classList.add('show');
            content.innerHTML = `
                <div class="status-icon error-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h1>Invalid Request</h1>
                <p>Missing payment information.</p>
                <a href="../user/dashboard.php" class="btn btn-primary">Back to Dashboard</a>
            `;
        }
    </script>
</body>
</html>
