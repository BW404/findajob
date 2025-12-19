<?php
require_once 'config/database.php';

// Get the successful cv_pro transaction
$stmt = $pdo->query("
    SELECT id, tx_ref, service_type, status, metadata, user_id 
    FROM transactions 
    WHERE service_type = 'cv_pro' AND status = 'successful'
    ORDER BY created_at DESC 
    LIMIT 1
");
$transaction = $stmt->fetch(PDO::FETCH_ASSOC);

if ($transaction) {
    echo "Transaction found:\n";
    echo "ID: " . $transaction['id'] . "\n";
    echo "TX Ref: " . $transaction['tx_ref'] . "\n";
    echo "Service: " . $transaction['service_type'] . "\n";
    echo "Status: " . $transaction['status'] . "\n";
    echo "User ID: " . $transaction['user_id'] . "\n";
    echo "Metadata: " . $transaction['metadata'] . "\n\n";
    
    $metadata = json_decode($transaction['metadata'], true);
    
    if (isset($metadata['cv_request_id'])) {
        echo "CV Request ID found in metadata: " . $metadata['cv_request_id'] . "\n";
        
        // Update the premium CV request
        $stmt = $pdo->prepare("UPDATE premium_cv_requests SET payment_status = 'paid', updated_at = NOW() WHERE id = ? AND user_id = ?");
        $stmt->execute([$metadata['cv_request_id'], $transaction['user_id']]);
        
        echo "✓ Updated premium CV request payment status to 'paid'\n";
    } else {
        echo "No cv_request_id in metadata. Checking session-based approach...\n";
        
        // Try to find matching CV request by user_id, amount, and date
        $stmt = $pdo->prepare("
            SELECT id FROM premium_cv_requests 
            WHERE user_id = ? 
            AND plan_type = 'cv_pro' 
            AND payment_status = 'pending'
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$transaction['user_id']]);
        $cv_request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($cv_request) {
            echo "Found matching CV request ID: " . $cv_request['id'] . "\n";
            $stmt = $pdo->prepare("UPDATE premium_cv_requests SET payment_status = 'paid', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$cv_request['id']]);
            echo "✓ Updated premium CV request payment status to 'paid'\n";
        } else {
            echo "No matching CV request found\n";
        }
    }
} else {
    echo "No successful cv_pro transaction found\n";
}
?>
