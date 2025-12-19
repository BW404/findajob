<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../config/session.php';

try {
    // Check if user is logged in
    if (!isLoggedIn()) {
        throw new Exception('Authentication required');
    }
    
    $userId = getCurrentUserId();
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? $_GET['action'] ?? null;
    
    switch ($action) {
        case 'mark_read':
            $notificationId = $data['notification_id'] ?? null;
            
            if (!$notificationId) {
                throw new Exception('Notification ID required');
            }
            
            // Mark notification as read (only for private offer notifications)
            $stmt = $pdo->prepare("
                UPDATE private_offer_notifications 
                SET is_read = 1 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$notificationId, $userId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Notification marked as read'
            ]);
            break;
            
        case 'mark_all_read':
            // Mark all notifications as read for the user
            $stmt = $pdo->prepare("
                UPDATE private_offer_notifications 
                SET is_read = 1 
                WHERE user_id = ? AND is_read = 0
            ");
            $stmt->execute([$userId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'All notifications marked as read'
            ]);
            break;
            
        case 'get_unread_count':
            // Get unread notification count
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as unread_count
                FROM private_offer_notifications
                WHERE user_id = ? AND is_read = 0
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'unread_count' => $result['unread_count']
            ]);
            break;
            
        case 'delete':
            $notificationId = $data['notification_id'] ?? null;
            
            if (!$notificationId) {
                throw new Exception('Notification ID required');
            }
            
            // Delete notification
            $stmt = $pdo->prepare("
                DELETE FROM private_offer_notifications 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$notificationId, $userId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Notification deleted'
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
