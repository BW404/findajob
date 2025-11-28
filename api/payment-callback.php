<?php
/**
 * Flutterwave Payment Callback
 * Handles redirect after payment
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/flutterwave.php';

$tx_ref = $_GET['tx_ref'] ?? '';
$transaction_id = $_GET['transaction_id'] ?? '';
$status = $_GET['status'] ?? '';

// Redirect to verification page
if ($tx_ref && $transaction_id) {
    header('Location: ../pages/payment/verify.php?tx_ref=' . urlencode($tx_ref) . '&transaction_id=' . urlencode($transaction_id) . '&status=' . urlencode($status));
} else {
    header('Location: ../pages/payment/failed.php?error=missing_params');
}
exit;
