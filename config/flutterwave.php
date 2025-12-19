<?php
/**
 * Flutterwave Payment Gateway Configuration
 * Documentation: https://developer.flutterwave.com/docs
 */

// Try to load settings from database first
$db_settings = [];
try {
    require_once __DIR__ . '/database.php';
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'flutterwave_%'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $db_settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    // Database not available or table doesn't exist, use defaults
    error_log("Could not load Flutterwave settings from database: " . $e->getMessage());
}

// Flutterwave API Credentials - Check database first, then environment, then defaults
define('FLUTTERWAVE_PUBLIC_KEY', 
    $db_settings['flutterwave_public_key'] ?? 
    getenv('FLUTTERWAVE_PUBLIC_KEY') ?: 
    'FLWPUBK_TEST-22f24c499184047fee7003b68e0ad9d3-X'
);

define('FLUTTERWAVE_SECRET_KEY', 
    $db_settings['flutterwave_secret_key'] ?? 
    getenv('FLUTTERWAVE_SECRET_KEY') ?: 
    'FLWSECK_TEST-36067985891ec3bb7dd1bcbb0719fdbc-X'
);

define('FLUTTERWAVE_ENCRYPTION_KEY', 
    $db_settings['flutterwave_encryption_key'] ?? 
    getenv('FLUTTERWAVE_ENCRYPTION_KEY') ?: 
    'FLWSECK_TEST6cfd4e1962bb'
);

// Environment (test or live)
define('FLUTTERWAVE_ENVIRONMENT', 
    $db_settings['flutterwave_environment'] ?? 
    getenv('FLUTTERWAVE_ENVIRONMENT') ?: 
    'test'
);

// API Endpoints
if (FLUTTERWAVE_ENVIRONMENT === 'live') {
    define('FLUTTERWAVE_API_URL', 'https://api.flutterwave.com/v3');
} else {
    define('FLUTTERWAVE_API_URL', 'https://api.flutterwave.com/v3'); // Same for test
}

// Payment Settings
define('FLUTTERWAVE_CURRENCY', 'NGN');
define('FLUTTERWAVE_COUNTRY', 'NG');

// Webhook URL - Check database first
define('FLUTTERWAVE_WEBHOOK_URL', 
    $db_settings['flutterwave_webhook_url'] ?? 
    'https://yourdomain.com/api/flutterwave-webhook.php'
);

// Pricing Plans (in Naira)
define('PRICING_PLANS', [
    // Job Seeker Plans
    'job_seeker_basic' => [
        'name' => 'Job Seeker Basic Plan',
        'price' => 0,
        'duration' => 'lifetime',
        'type' => 'subscription',
        'user_type' => 'job_seeker'
    ],
    'job_seeker_pro_monthly' => [
        'name' => 'Job Seeker Pro (Monthly)',
        'price' => 6000,
        'duration' => '30 days',
        'type' => 'subscription',
        'user_type' => 'job_seeker',
        'features' => [
            'Multiple CV uploads and management',
            'Advanced profile fields and data',
            'Top of employer search results',
            'AI-powered job recommendations',
            'Daily job alerts via email & SMS',
            'Advanced application tracking',
            'Priority customer support',
            'Profile analytics and insights'
        ]
    ],
    'job_seeker_pro_yearly' => [
        'name' => 'Job Seeker Pro (Yearly)',
        'price' => 10000,
        'duration' => '365 days',
        'type' => 'subscription',
        'user_type' => 'job_seeker',
        'savings' => '12,000 savings!',
        'features' => [
            'Multiple CV uploads and management',
            'Advanced profile fields and data',
            'Top of employer search results',
            'AI-powered job recommendations',
            'Daily job alerts via email & SMS',
            'Advanced application tracking',
            'Priority customer support',
            'Profile analytics and insights'
        ]
    ],
    'job_seeker_verification_booster' => [
        'name' => 'Job Seeker Verification',
        'price' => 1000,
        'duration' => 'one-time',
        'type' => 'booster',
        'user_type' => 'job_seeker',
        'benefits' => 'Get a verified ID badge on your profile • Increase trust with employers • Stand out from other applicants • Higher application success rate'
    ],
    'job_seeker_profile_booster' => [
        'name' => 'Job Seeker Profile Booster',
        'price' => 500,
        'duration' => '30 days',
        'type' => 'booster',
        'user_type' => 'job_seeker',
        'benefits' => 'Appear at the top of employer searches • 5x more profile views • Priority in application lists • Highlighted profile badge'
    ],
    
    // Premium CV Writing Services
    'cv_pro' => [
        'name' => 'CV Pro',
        'price' => 15500,
        'duration' => '5-7 working days',
        'type' => 'cv_service',
        'user_type' => 'job_seeker',
        'features' => [
            'Professionally written CV',
            'Cover letter included',
            '30 minute phone consultation',
            'Delivered in 5-7 working days',
            'ATS-optimized format',
            'Industry-specific optimization'
        ]
    ],
    'cv_pro_plus' => [
        'name' => 'CV Pro+',
        'price' => 33500,
        'duration' => '10 working days',
        'type' => 'cv_service',
        'user_type' => 'job_seeker',
        'features' => [
            'Professionally written CV',
            'Cover letter included',
            '30 minute phone consultation',
            'LinkedIn profile review',
            'Delivered in 10 working days',
            'ATS-optimized format',
            'Industry-specific optimization',
            'Premium design templates'
        ]
    ],
    'remote_working_cv' => [
        'name' => 'Remote Working CV',
        'price' => 16000,
        'duration' => '5-7 working days',
        'type' => 'cv_service',
        'user_type' => 'job_seeker',
        'features' => [
            'Professionally written CV optimized for remote positions',
            'Cover letter included',
            'LinkedIn profile review',
            'Delivered in 5-7 working days',
            'Remote work skills highlighted',
            'International format compatibility'
        ]
    ],
    
    // Employer Plans
    'employer_basic' => [
        'name' => 'Employer Basic Plan',
        'price' => 0,
        'duration' => 'lifetime',
        'type' => 'subscription',
        'user_type' => 'employer'
    ],
    'employer_pro_monthly' => [
        'name' => 'Employer Pro (Monthly)',
        'price' => 30000,
        'duration' => '30 days',
        'type' => 'subscription',
        'user_type' => 'employer',
        'features' => [
            'Unlimited job postings',
            'Featured job listings',
            'Advanced candidate search filters',
            'Priority in search results',
            'Access to premium CV database',
            'Application tracking & analytics',
            'Bulk job posting',
            'Priority customer support',
            'Company branding & logo',
            'Email & SMS job alerts to candidates'
        ]
    ],
    'employer_pro_yearly' => [
        'name' => 'Employer Pro (Yearly)',
        'price' => 300000,
        'duration' => '365 days',
        'type' => 'subscription',
        'user_type' => 'employer',
        'savings' => '60,000 savings (2 months free)!',
        'features' => [
            'Unlimited job postings',
            'Featured job listings',
            'Advanced candidate search filters',
            'Priority in search results',
            'Access to premium CV database',
            'Application tracking & analytics',
            'Bulk job posting',
            'Priority customer support',
            'Company branding & logo',
            'Email & SMS job alerts to candidates',
            'Dedicated account manager',
            'Quarterly hiring insights report'
        ]
    ],
    'employer_verification_booster' => [
        'name' => 'Company Verification Badge',
        'price' => 1000,
        'duration' => 'one-time',
        'type' => 'booster',
        'user_type' => 'employer',
        'benefits' => 'Get a verified badge on your company profile • Build trust with job seekers • Stand out from competitors • Increase application rates by 3x'
    ],
    'employer_job_booster_1' => [
        'name' => 'Job Posting Booster (1 Job)',
        'price' => 5000,
        'duration' => '30 days per job',
        'type' => 'job_booster',
        'user_type' => 'employer',
        'jobs_count' => 1,
        'benefits' => 'Boost 1 job to top of search results • 5x more visibility • Featured badge • 30 days boost duration'
    ],
    'employer_job_booster_3' => [
        'name' => 'Job Posting Booster (3 Jobs)',
        'price' => 10000,
        'duration' => '30 days per job',
        'type' => 'job_booster',
        'user_type' => 'employer',
        'jobs_count' => 3,
        'savings' => 'Save ₦5,000',
        'benefits' => 'Boost 3 jobs to top of search results • 5x more visibility • Featured badge • 30 days boost duration per job'
    ],
    'employer_job_booster_5' => [
        'name' => 'Job Posting Booster (5 Jobs)',
        'price' => 15000,
        'duration' => '30 days per job',
        'type' => 'job_booster',
        'user_type' => 'employer',
        'jobs_count' => 5,
        'savings' => 'Save ₦10,000 (Best Value)',
        'benefits' => 'Boost 5 jobs to top of search results • 5x more visibility • Featured badge • 30 days boost duration per job'
    ],
]);

// Payment redirect URLs - dynamically set based on current host
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base_url = $protocol . '://' . $host . '/findajob';

define('FLUTTERWAVE_REDIRECT_URL', $base_url . '/pages/payment/verify.php');
define('FLUTTERWAVE_CALLBACK_URL', $base_url . '/api/payment-callback.php');

/**
 * Initialize Flutterwave Payment
 * 
 * @param array $data Payment data
 * @return array Response from Flutterwave API
 */
function initializeFlutterwavePayment($data) {
    $url = FLUTTERWAVE_API_URL . '/payments';
    
    $payload = [
        'tx_ref' => $data['tx_ref'],
        'amount' => $data['amount'],
        'currency' => FLUTTERWAVE_CURRENCY,
        'redirect_url' => $data['redirect_url'] ?? FLUTTERWAVE_REDIRECT_URL,
        'payment_options' => $data['payment_options'] ?? 'card,banktransfer,ussd',
        'customer' => [
            'email' => $data['customer_email'],
            'name' => $data['customer_name'],
            'phonenumber' => $data['customer_phone'] ?? '',
        ],
        'customizations' => [
            'title' => $data['title'] ?? 'FindAJob Nigeria Payment',
            'description' => $data['description'] ?? 'Payment for services',
            'logo' => $data['logo'] ?? 'https://yourdomain.com/assets/images/logo.png',
        ],
        'meta' => $data['meta'] ?? []
    ];
    
    return makeFlutterwaveRequest('POST', $url, $payload);
}

/**
 * Verify Flutterwave Payment
 * 
 * @param int $transaction_id Transaction ID from Flutterwave
 * @return array Response from Flutterwave API
 */
function verifyFlutterwavePayment($transaction_id) {
    $url = FLUTTERWAVE_API_URL . '/transactions/' . $transaction_id . '/verify';
    return makeFlutterwaveRequest('GET', $url);
}

/**
 * Get Transaction Details
 * 
 * @param int $transaction_id Transaction ID
 * @return array Response from Flutterwave API
 */
function getFlutterwaveTransaction($transaction_id) {
    $url = FLUTTERWAVE_API_URL . '/transactions/' . $transaction_id;
    return makeFlutterwaveRequest('GET', $url);
}

/**
 * Make HTTP Request to Flutterwave API
 * 
 * @param string $method HTTP method
 * @param string $url API endpoint
 * @param array $data Request payload
 * @return array Response
 */
function makeFlutterwaveRequest($method, $url, $data = null) {
    $ch = curl_init();
    
    $headers = [
        'Authorization: Bearer ' . FLUTTERWAVE_SECRET_KEY,
        'Content-Type: application/json',
    ];
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For development only
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($error) {
        error_log("Flutterwave API Error: " . $error);
        return [
            'status' => 'error',
            'message' => 'Connection error',
            'error' => $error
        ];
    }
    
    $result = json_decode($response, true);
    
    if (!$result) {
        error_log("Flutterwave API Invalid Response: " . $response);
        return [
            'status' => 'error',
            'message' => 'Invalid response from payment gateway'
        ];
    }
    
    return $result;
}

/**
 * Generate Transaction Reference
 * 
 * @param string $prefix Prefix for reference
 * @return string Transaction reference
 */
function generateTransactionRef($prefix = 'FINDAJOB') {
    return $prefix . '_' . time() . '_' . bin2hex(random_bytes(5));
}

/**
 * Format Amount for Display
 * 
 * @param float $amount Amount
 * @return string Formatted amount
 */
function formatAmount($amount) {
    return '₦' . number_format($amount, 2);
}

/**
 * Verify Webhook Signature
 * 
 * @param string $signature Signature from webhook
 * @param string $payload Request body
 * @return bool Valid or not
 */
function verifyFlutterwaveWebhook($signature, $payload) {
    $secretHash = FLUTTERWAVE_SECRET_KEY;
    $computedSignature = hash_hmac('sha256', $payload, $secretHash);
    return hash_equals($signature, $computedSignature);
}
