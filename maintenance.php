<?php
// Skip maintenance check for this page
define('SKIP_MAINTENANCE_CHECK', true);
require_once 'config/database.php';

// Get maintenance message
$maintenance_message = 'We are currently performing scheduled maintenance. Please check back soon.';
try {
    $stmt = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'maintenance_message' LIMIT 1");
    $result = $stmt->fetch();
    if ($result) {
        $maintenance_message = $result['setting_value'];
    }
} catch (Exception $e) {
    // Use default message
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Under Maintenance - FindAJob Nigeria</title>
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

        .maintenance-container {
            max-width: 600px;
            background: white;
            border-radius: 20px;
            padding: 60px 40px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .maintenance-icon {
            font-size: 80px;
            color: #dc2626;
            margin-bottom: 30px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        h1 {
            font-size: 32px;
            color: #1a1a2e;
            margin-bottom: 20px;
        }

        p {
            font-size: 18px;
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .status-info {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            border-radius: 8px;
            margin-top: 30px;
            text-align: left;
        }

        .status-info strong {
            color: #92400e;
            display: block;
            margin-bottom: 5px;
        }

        .status-info p {
            color: #92400e;
            font-size: 14px;
            margin: 0;
        }

        .btn-home {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 30px;
            background: #dc2626;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-home:hover {
            background: #b91c1c;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }

        .social-links {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #e5e7eb;
        }

        .social-links p {
            font-size: 14px;
            color: #9ca3af;
            margin-bottom: 15px;
        }

        .social-links a {
            display: inline-block;
            margin: 0 10px;
            color: #6b7280;
            font-size: 24px;
            transition: color 0.3s;
        }

        .social-links a:hover {
            color: #dc2626;
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <div class="maintenance-icon">
            <i class="fas fa-tools"></i>
        </div>
        
        <h1>We'll be back soon!</h1>
        <p><?= htmlspecialchars($maintenance_message) ?></p>

        <div class="status-info">
            <strong><i class="fas fa-info-circle"></i> What's happening?</strong>
            <p>Our team is performing scheduled maintenance to improve your experience. We expect to be back online shortly.</p>
        </div>

        <a href="/" class="btn-home">
            <i class="fas fa-home"></i> Return to Homepage
        </a>

        <div class="social-links">
            <p>Stay connected with us:</p>
            <a href="#" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
            <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
            <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin"></i></a>
            <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
        </div>
    </div>
</body>
</html>
