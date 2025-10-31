<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../config/session.php';

// Handle CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['action']) || !isset($data['cv_id'])) {
        throw new Exception('Missing required parameters');
    }

    $action = $data['action'];
    $cvId = (int)$data['cv_id'];
    
    // Verify CV exists and belongs to current user (for security)
    if (isLoggedIn()) {
        $userId = getCurrentUserId();
        $stmt = $pdo->prepare("SELECT id FROM cvs WHERE id = ? AND user_id = ?");
        $stmt->execute([$cvId, $userId]);
        
        if (!$stmt->fetch()) {
            throw new Exception('CV not found or access denied');
        }
    }

    switch ($action) {
        case 'view':
            // Increment view count
            $stmt = $pdo->prepare("
                UPDATE cvs 
                SET view_count = COALESCE(view_count, 0) + 1,
                    last_viewed_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$cvId]);
            
            // Log the view
            if (isLoggedIn()) {
                $stmt = $pdo->prepare("
                    INSERT INTO cv_analytics (cv_id, action_type, user_id, created_at)
                    VALUES (?, 'view', ?, NOW())
                ");
                $stmt->execute([$cvId, $userId]);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'View tracked'
            ]);
            break;
            
        case 'download':
            // Increment download count
            $stmt = $pdo->prepare("
                UPDATE cvs 
                SET download_count = COALESCE(download_count, 0) + 1,
                    last_downloaded_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$cvId]);
            
            // Log the download
            if (isLoggedIn()) {
                $stmt = $pdo->prepare("
                    INSERT INTO cv_analytics (cv_id, action_type, user_id, created_at)
                    VALUES (?, 'download', ?, NOW())
                ");
                $stmt->execute([$cvId, $userId]);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Download tracked'
            ]);
            break;
            
        case 'stats':
            // Get CV statistics
            $stmt = $pdo->prepare("
                SELECT 
                    view_count,
                    download_count,
                    last_viewed_at,
                    last_downloaded_at,
                    created_at,
                    (SELECT COUNT(*) FROM job_applications WHERE cv_id = ?) as application_count
                FROM cvs 
                WHERE id = ?
            ");
            $stmt->execute([$cvId, $cvId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$stats) {
                throw new Exception('CV not found');
            }
            
            // Get recent activity
            $stmt = $pdo->prepare("
                SELECT action_type, created_at 
                FROM cv_analytics 
                WHERE cv_id = ? 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $stmt->execute([$cvId]);
            $recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'stats' => $stats,
                'recent_activity' => $recentActivity
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
