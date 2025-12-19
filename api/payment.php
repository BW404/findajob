<?php
/**
 * Payment API - Initialize and Process Payments
 * Handles Flutterwave payment initialization
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/flutterwave.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please login.']);
    exit;
}

$user_id = getCurrentUserId();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    // Get user details
    $stmt = $pdo->prepare("SELECT first_name, last_name, email, phone FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    switch ($action) {
        case 'initialize_payment':
            // Validate input
            $amount = floatval($_POST['amount'] ?? 0);
            $service_type = $_POST['service_type'] ?? '';
            $description = $_POST['description'] ?? '';
            
            if ($amount <= 0) {
                throw new Exception('Invalid amount');
            }
            
            // Validate service type against PRICING_PLANS
            require_once '../config/flutterwave.php';
            $valid_service_keys = array_keys(PRICING_PLANS);
            
            // Also accept legacy service types for backwards compatibility
            $legacy_services = ['job_posting', 'cv_service', 'featured_listing', 'subscription', 'nin_verification', 'job_booster', 'other'];
            
            if (!in_array($service_type, $valid_service_keys) && !in_array($service_type, $legacy_services)) {
                throw new Exception('Invalid service type: ' . $service_type);
            }
            
            // Generate transaction reference
            $tx_ref = generateTransactionRef('FINDAJOB');
            
            // Create transaction record
            $stmt = $pdo->prepare("
                INSERT INTO transactions (
                    user_id, 
                    tx_ref,
                    transaction_reference,
                    amount, 
                    currency, 
                    service_type, 
                    status,
                    payment_gateway,
                    customer_email,
                    customer_name,
                    customer_phone,
                    description,
                    metadata
                ) VALUES (?, ?, ?, ?, ?, ?, 'pending', 'flutterwave', ?, ?, ?, ?, ?)
            ");
            
            $customer_name = $user['first_name'] . ' ' . $user['last_name'];
            $metadata_array = [
                'user_id' => $user_id,
                'service_type' => $service_type,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Add CV request ID if provided
            if (!empty($_POST['cv_request_id'])) {
                $metadata_array['cv_request_id'] = intval($_POST['cv_request_id']);
            }
            
            $metadata = json_encode($metadata_array);
            
            $stmt->execute([
                $user_id,
                $tx_ref,
                $tx_ref, // Also use as transaction_reference for compatibility
                $amount,
                FLUTTERWAVE_CURRENCY,
                $service_type,
                $user['email'],
                $customer_name,
                $user['phone'] ?? '',
                $description,
                $metadata
            ]);
            
            $transaction_id = $pdo->lastInsertId();
            
            // Initialize Flutterwave payment
            $payment_data = [
                'tx_ref' => $tx_ref,
                'amount' => $amount,
                'customer_email' => $user['email'],
                'customer_name' => $customer_name,
                'customer_phone' => $user['phone'] ?? '',
                'title' => 'FindAJob Nigeria - ' . ucwords(str_replace('_', ' ', $service_type)),
                'description' => $description ?: 'Payment for ' . $service_type,
                'redirect_url' => FLUTTERWAVE_REDIRECT_URL . '?tx_ref=' . $tx_ref,
                'meta' => [
                    'user_id' => $user_id,
                    'transaction_id' => $transaction_id,
                    'service_type' => $service_type
                ]
            ];
            
            $response = initializeFlutterwavePayment($payment_data);
            
            if ($response['status'] === 'success') {
                // Update transaction with Flutterwave response
                $stmt = $pdo->prepare("
                    UPDATE transactions 
                    SET flw_response = ? 
                    WHERE id = ?
                ");
                $stmt->execute([json_encode($response), $transaction_id]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Payment initialized successfully',
                    'data' => [
                        'payment_link' => $response['data']['link'],
                        'tx_ref' => $tx_ref,
                        'amount' => formatAmount($amount)
                    ]
                ]);
            } else {
                throw new Exception($response['message'] ?? 'Payment initialization failed');
            }
            break;
            
        case 'verify_payment':
            $tx_ref = $_GET['tx_ref'] ?? $_POST['tx_ref'] ?? '';
            
            if (empty($tx_ref)) {
                throw new Exception('Transaction reference is required');
            }
            
            // Get transaction from database
            $stmt = $pdo->prepare("
                SELECT * FROM transactions 
                WHERE tx_ref = ? AND user_id = ?
            ");
            $stmt->execute([$tx_ref, $user_id]);
            $transaction = $stmt->fetch();
            
            if (!$transaction) {
                throw new Exception('Transaction not found');
            }
            
            // If already verified, return success
            if ($transaction['status'] === 'successful' || $transaction['status'] === 'completed') {
                echo json_encode([
                    'success' => true,
                    'message' => 'Payment already verified',
                    'data' => [
                        'status' => $transaction['status'],
                        'amount' => formatAmount($transaction['amount']),
                        'tx_ref' => $tx_ref
                    ]
                ]);
                break;
            }
            
            // Verify with Flutterwave
            $transaction_id = $_GET['transaction_id'] ?? $_POST['transaction_id'] ?? '';
            
            if (empty($transaction_id)) {
                throw new Exception('Flutterwave transaction ID is required');
            }
            
            $verification = verifyFlutterwavePayment($transaction_id);
            
            if ($verification['status'] === 'success') {
                $flw_data = $verification['data'];
                
                // Check if payment was successful
                if ($flw_data['status'] === 'successful' && $flw_data['tx_ref'] === $tx_ref) {
                    // Verify amount
                    if (floatval($flw_data['amount']) >= floatval($transaction['amount'])) {
                        // Update transaction
                        $stmt = $pdo->prepare("
                            UPDATE transactions 
                            SET 
                                status = 'successful',
                                transaction_id = ?,
                                flw_ref = ?,
                                payment_method = ?,
                                flw_response = ?,
                                verified_at = NOW()
                            WHERE tx_ref = ?
                        ");
                        
                        $stmt->execute([
                            $flw_data['id'],
                            $flw_data['flw_ref'],
                            $flw_data['payment_type'] ?? 'card',
                            json_encode($verification),
                            $tx_ref
                        ]);
                        
                        // Process the service (activate job posting, CV service, etc.)
                        processPaymentService($transaction['service_type'], $user_id, $transaction);
                        
                        echo json_encode([
                            'success' => true,
                            'message' => 'Payment verified successfully',
                            'data' => [
                                'status' => 'successful',
                                'amount' => formatAmount($flw_data['amount']),
                                'tx_ref' => $tx_ref,
                                'payment_method' => $flw_data['payment_type'] ?? 'card'
                            ]
                        ]);
                    } else {
                        throw new Exception('Payment amount mismatch');
                    }
                } else {
                    // Payment failed
                    $stmt = $pdo->prepare("
                        UPDATE transactions 
                        SET 
                            status = 'failed',
                            transaction_id = ?,
                            flw_response = ?
                        WHERE tx_ref = ?
                    ");
                    
                    $stmt->execute([
                        $flw_data['id'] ?? null,
                        json_encode($verification),
                        $tx_ref
                    ]);
                    
                    throw new Exception('Payment verification failed: ' . ($flw_data['status'] ?? 'Unknown error'));
                }
            } else {
                throw new Exception($verification['message'] ?? 'Verification failed');
            }
            break;
            
        case 'get_transaction':
            $tx_ref = $_GET['tx_ref'] ?? '';
            
            if (empty($tx_ref)) {
                throw new Exception('Transaction reference is required');
            }
            
            $stmt = $pdo->prepare("
                SELECT 
                    id,
                    tx_ref,
                    amount,
                    currency,
                    service_type,
                    status,
                    payment_method,
                    customer_email,
                    customer_name,
                    description,
                    created_at,
                    verified_at
                FROM transactions 
                WHERE tx_ref = ? AND user_id = ?
            ");
            $stmt->execute([$tx_ref, $user_id]);
            $transaction = $stmt->fetch();
            
            if (!$transaction) {
                throw new Exception('Transaction not found');
            }
            
            echo json_encode([
                'success' => true,
                'data' => $transaction
            ]);
            break;
            
        case 'get_user_transactions':
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $per_page = 20;
            $offset = ($page - 1) * $per_page;
            
            $stmt = $pdo->prepare("
                SELECT 
                    id,
                    tx_ref,
                    amount,
                    currency,
                    service_type,
                    status,
                    payment_method,
                    description,
                    created_at
                FROM transactions 
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$user_id, $per_page, $offset]);
            $transactions = $stmt->fetchAll();
            
            // Get total count
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $total = $stmt->fetchColumn();
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'transactions' => $transactions,
                    'total' => $total,
                    'page' => $page,
                    'per_page' => $per_page,
                    'total_pages' => ceil($total / $per_page)
                ]
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    error_log("Payment API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Process payment service after successful payment
 */
function processPaymentService($service_type, $user_id, $transaction) {
    global $pdo;
    
    try {
        $metadata = json_decode($transaction['metadata'] ?? '{}', true);
        
        switch ($service_type) {
            // Job Seeker Subscriptions
            case 'job_seeker_pro_monthly':
                $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET subscription_status = 'active', 
                        subscription_plan = 'pro',
                        subscription_type = 'monthly',
                        subscription_start = NOW(),
                        subscription_end = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$expiry, $user_id]);
                break;
                
            case 'job_seeker_pro_yearly':
                $expiry = date('Y-m-d H:i:s', strtotime('+365 days'));
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET subscription_status = 'active', 
                        subscription_plan = 'pro',
                        subscription_type = 'yearly',
                        subscription_start = NOW(),
                        subscription_end = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$expiry, $user_id]);
                break;
                
            case 'job_seeker_verification_booster':
                // Mark profile as verification boosted (one-time)
                $stmt = $pdo->prepare("
                    UPDATE job_seeker_profiles 
                    SET verification_boosted = 1,
                        verification_boost_date = NOW()
                    WHERE user_id = ?
                ");
                $stmt->execute([$user_id]);
                break;
                
            case 'job_seeker_profile_booster':
                // Boost profile in search results for 30 days
                $boost_expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
                $stmt = $pdo->prepare("
                    UPDATE job_seeker_profiles 
                    SET profile_boosted = 1,
                        profile_boost_until = ?
                    WHERE user_id = ?
                ");
                $stmt->execute([$boost_expiry, $user_id]);
                break;
                
            // Employer Subscriptions
            case 'employer_pro_monthly':
                $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET subscription_status = 'active', 
                        subscription_plan = 'pro',
                        subscription_type = 'monthly',
                        subscription_start = NOW(),
                        subscription_end = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$expiry, $user_id]);
                break;
                
            case 'employer_pro_yearly':
                $expiry = date('Y-m-d H:i:s', strtotime('+365 days'));
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET subscription_status = 'active', 
                        subscription_plan = 'pro',
                        subscription_type = 'yearly',
                        subscription_start = NOW(),
                        subscription_end = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$expiry, $user_id]);
                break;
                
            case 'employer_verification_booster':
                // Mark company as verification boosted (one-time)
                $stmt = $pdo->prepare("
                    UPDATE employer_profiles 
                    SET verification_boosted = 1,
                        verification_boost_date = NOW()
                    WHERE user_id = ?
                ");
                $stmt->execute([$user_id]);
                break;
                
            case 'employer_job_booster_1':
            case 'employer_job_booster_3':
            case 'employer_job_booster_5':
                // Add job boost credits
                $credits = [
                    'employer_job_booster_1' => 1,
                    'employer_job_booster_3' => 3,
                    'employer_job_booster_5' => 5
                ][$service_type];
                
                $stmt = $pdo->prepare("
                    UPDATE employer_profiles 
                    SET job_boost_credits = COALESCE(job_boost_credits, 0) + ?
                    WHERE user_id = ?
                ");
                $stmt->execute([$credits, $user_id]);
                
                // If specific job ID in metadata, boost it immediately
                if (isset($metadata['job_id'])) {
                    $boost_until = date('Y-m-d H:i:s', strtotime('+30 days'));
                    $stmt = $pdo->prepare("
                        UPDATE jobs 
                        SET is_boosted = 1, 
                            boosted_until = ?
                        WHERE id = ? AND user_id = ?
                    ");
                    $stmt->execute([$boost_until, $metadata['job_id'], $user_id]);
                }
                break;
                
            case 'nin_verification':
                // Process NIN verification
                break;
                
            case 'cv_service':
            case 'cv_pro':
            case 'cv_pro_plus':
            case 'remote_working_cv':
                // Update premium CV request payment status
                if (isset($metadata['cv_request_id'])) {
                    $stmt = $pdo->prepare("UPDATE premium_cv_requests SET payment_status = 'paid', updated_at = NOW() WHERE id = ? AND user_id = ?");
                    $stmt->execute([$metadata['cv_request_id'], $user_id]);
                    error_log("Premium CV request {$metadata['cv_request_id']} marked as paid for user {$user_id}");
                } else {
                    error_log("Warning: CV payment processed but no cv_request_id in metadata for transaction {$transaction['tx_ref']}");
                }
                break;
        }
        
        error_log("Payment service processed: {$service_type} for user {$user_id}");
        
    } catch (Exception $e) {
        error_log("Error processing payment service: " . $e->getMessage());
    }
}
