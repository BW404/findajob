<?php
/**
 * Flutterwave Webhook Handler
 * Receives payment notifications from Flutterwave
 */

require_once '../config/database.php';
require_once '../config/flutterwave.php';

// Get webhook signature
$signature = $_SERVER['HTTP_VERIF_HASH'] ?? '';

// Get request body
$payload = file_get_contents('php://input');

// Log webhook for debugging
error_log("Flutterwave Webhook Received: " . $payload);

// Verify webhook signature
if (empty($signature) || $signature !== FLUTTERWAVE_SECRET_KEY) {
    error_log("Flutterwave Webhook: Invalid signature");
    http_response_code(401);
    exit('Invalid signature');
}

try {
    $data = json_decode($payload, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON payload');
    }
    
    // Extract transaction details
    $event = $data['event'] ?? '';
    $tx_ref = $data['data']['tx_ref'] ?? '';
    $transaction_id = $data['data']['id'] ?? '';
    $amount = $data['data']['amount'] ?? 0;
    $status = $data['data']['status'] ?? '';
    $flw_ref = $data['data']['flw_ref'] ?? '';
    $payment_type = $data['data']['payment_type'] ?? '';
    
    if (empty($tx_ref)) {
        throw new Exception('Missing transaction reference');
    }
    
    // Find transaction in database
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE tx_ref = ?");
    $stmt->execute([$tx_ref]);
    $transaction = $stmt->fetch();
    
    if (!$transaction) {
        error_log("Flutterwave Webhook: Transaction not found - {$tx_ref}");
        http_response_code(404);
        exit('Transaction not found');
    }
    
    // Process based on event type
    if ($event === 'charge.completed' && $status === 'successful') {
        // Verify amount matches
        if (floatval($amount) >= floatval($transaction['amount'])) {
            // Update transaction status
            $stmt = $pdo->prepare("
                UPDATE transactions 
                SET 
                    status = 'successful',
                    transaction_id = ?,
                    flw_ref = ?,
                    payment_method = ?,
                    flw_response = ?,
                    verified_at = NOW()
                WHERE tx_ref = ? AND status = 'pending'
            ");
            
            $stmt->execute([
                $transaction_id,
                $flw_ref,
                $payment_type,
                $payload,
                $tx_ref
            ]);
            
            // Process the service
            processPaymentService($transaction, $data['data']);
            
            error_log("Flutterwave Webhook: Payment successful - {$tx_ref}");
        } else {
            error_log("Flutterwave Webhook: Amount mismatch - Expected: {$transaction['amount']}, Got: {$amount}");
        }
    } elseif ($status === 'failed') {
        // Update as failed
        $stmt = $pdo->prepare("
            UPDATE transactions 
            SET 
                status = 'failed',
                transaction_id = ?,
                flw_response = ?
            WHERE tx_ref = ?
        ");
        
        $stmt->execute([
            $transaction_id,
            $payload,
            $tx_ref
        ]);
        
        error_log("Flutterwave Webhook: Payment failed - {$tx_ref}");
    }
    
    http_response_code(200);
    echo json_encode(['status' => 'success']);
    
} catch (Exception $e) {
    error_log("Flutterwave Webhook Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

/**
 * Process payment service after successful payment
 */
function processPaymentService($transaction, $flw_data) {
    global $pdo;
    
    $service_type = $transaction['service_type'];
    $user_id = $transaction['user_id'];
    
    try {
        switch ($service_type) {
            case 'job_posting':
                // Add job posting credits or activate specific job
                $metadata = json_decode($transaction['metadata'], true);
                if (isset($metadata['job_id'])) {
                    $stmt = $pdo->prepare("UPDATE jobs SET status = 'active', payment_status = 'paid' WHERE id = ? AND user_id = ?");
                    $stmt->execute([$metadata['job_id'], $user_id]);
                }
                break;
                
            case 'featured_listing':
                // Make job featured
                $metadata = json_decode($transaction['metadata'], true);
                if (isset($metadata['job_id'])) {
                    $featured_until = date('Y-m-d H:i:s', strtotime('+30 days'));
                    $stmt = $pdo->prepare("UPDATE jobs SET is_featured = 1, featured_until = ? WHERE id = ? AND user_id = ?");
                    $stmt->execute([$featured_until, $metadata['job_id'], $user_id]);
                }
                break;
                
            case 'cv_service':
                // Activate CV service access
                break;
                
            case 'subscription':
                // Activate premium subscription
                $subscription_end = date('Y-m-d H:i:s', strtotime('+30 days'));
                $stmt = $pdo->prepare("UPDATE users SET subscription_status = 'active', subscription_end = ? WHERE id = ?");
                $stmt->execute([$subscription_end, $user_id]);
                break;
                
            case 'nin_verification':
                // Process NIN verification
                break;
                
            case 'job_booster':
                // Boost job in search rankings
                $metadata = json_decode($transaction['metadata'], true);
                if (isset($metadata['job_id'])) {
                    $boosted_until = date('Y-m-d H:i:s', strtotime('+7 days'));
                    $stmt = $pdo->prepare("UPDATE jobs SET is_boosted = 1, boosted_until = ? WHERE id = ? AND user_id = ?");
                    $stmt->execute([$boosted_until, $metadata['job_id'], $user_id]);
                }
                break;
        }
        
        error_log("Payment service processed via webhook: {$service_type} for user {$user_id}");
        
    } catch (Exception $e) {
        error_log("Error processing payment service in webhook: " . $e->getMessage());
    }
}
