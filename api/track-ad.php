<?php
/**
 * Advertisement Tracking API
 * Handles impression and click tracking for advertisements
 */

require_once '../config/database.php';

header('Content-Type: application/json');

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['action']) || !isset($data['ad_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$action = $data['action'];
$ad_id = (int)$data['ad_id'];

try {
    switch ($action) {
        case 'impression':
            $stmt = $pdo->prepare("
                UPDATE advertisements 
                SET impression_count = impression_count + 1 
                WHERE id = ? AND is_active = 1
            ");
            $stmt->execute([$ad_id]);
            
            echo json_encode(['success' => true, 'message' => 'Impression recorded']);
            break;
            
        case 'click':
            $stmt = $pdo->prepare("
                UPDATE advertisements 
                SET click_count = click_count + 1 
                WHERE id = ? AND is_active = 1
            ");
            $stmt->execute([$ad_id]);
            
            echo json_encode(['success' => true, 'message' => 'Click recorded']);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log("Ad tracking error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Tracking failed']);
}
