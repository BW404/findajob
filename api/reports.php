<?php
/**
 * Reports API
 * Handles submission and management of user reports
 */

require_once '../config/database.php';
require_once '../config/session.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'You must be logged in to submit a report']);
    exit;
}

$user_id = getCurrentUserId();
$user_type = $_SESSION['user_type'];

// Only job seekers and employers can submit reports
if (!in_array($user_type, ['job_seeker', 'employer'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid user type']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'submit':
            submitReport($pdo, $user_id, $user_type);
            break;
            
        case 'get_reasons':
            getReportReasons();
            break;
            
        case 'my_reports':
            getMyReports($pdo, $user_id);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    error_log("Reports API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'An error occurred. Please try again.']);
}

function submitReport($pdo, $reporter_id, $reporter_type) {
    // Validate required fields
    $required = ['entity_type', 'reason', 'description'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "Missing required field: $field"]);
            exit;
        }
    }
    
    $entity_type = $_POST['entity_type'];
    $entity_id = $_POST['entity_id'] ?? null;
    $reason = $_POST['reason'];
    $description = trim($_POST['description']);
    
    // Validate entity type
    $valid_entity_types = ['job', 'user', 'company', 'application', 'other'];
    if (!in_array($entity_type, $valid_entity_types)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid entity type']);
        exit;
    }
    
    // Validate reason
    $valid_reasons = [
        'fake_profile', 'fake_job', 'inappropriate_content', 'harassment',
        'spam', 'scam', 'misleading_information', 'copyright_violation',
        'discrimination', 'offensive_language', 'duplicate_posting',
        'privacy_violation', 'payment_issues', 'other'
    ];
    if (!in_array($reason, $valid_reasons)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid reason']);
        exit;
    }
    
    // Validate description length
    if (strlen($description) < 10) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Description must be at least 10 characters']);
        exit;
    }
    
    if (strlen($description) > 2000) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Description must not exceed 2000 characters']);
        exit;
    }
    
    // Check for duplicate reports (same user, entity, and reason within 24 hours)
    $stmt = $pdo->prepare("
        SELECT id FROM reports 
        WHERE reporter_id = ? 
        AND reported_entity_type = ? 
        AND reported_entity_id = ?
        AND reason = ?
        AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $stmt->execute([$reporter_id, $entity_type, $entity_id, $reason]);
    
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'You have already submitted a similar report recently']);
        exit;
    }
    
    // Verify entity exists (if entity_id provided)
    if ($entity_id !== null) {
        $entity_exists = false;
        
        switch ($entity_type) {
            case 'job':
                $stmt = $pdo->prepare("SELECT id FROM jobs WHERE id = ?");
                $stmt->execute([$entity_id]);
                $entity_exists = (bool)$stmt->fetch();
                break;
                
            case 'user':
            case 'company':
                $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
                $stmt->execute([$entity_id]);
                $entity_exists = (bool)$stmt->fetch();
                break;
                
            case 'application':
                $stmt = $pdo->prepare("SELECT id FROM job_applications WHERE id = ?");
                $stmt->execute([$entity_id]);
                $entity_exists = (bool)$stmt->fetch();
                break;
        }
        
        if (!$entity_exists && $entity_type !== 'other') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'The reported entity does not exist']);
            exit;
        }
    }
    
    // Insert report
    $stmt = $pdo->prepare("
        INSERT INTO reports (
            reporter_id, reporter_type, reported_entity_type, 
            reported_entity_id, reason, description, status
        ) VALUES (?, ?, ?, ?, ?, ?, 'pending')
    ");
    
    $stmt->execute([
        $reporter_id,
        $reporter_type,
        $entity_type,
        $entity_id,
        $reason,
        $description
    ]);
    
    // Update report count on the entity
    if ($entity_id !== null) {
        try {
            if ($entity_type === 'job') {
                $pdo->prepare("UPDATE jobs SET report_count = report_count + 1 WHERE id = ?")
                    ->execute([$entity_id]);
            } elseif ($entity_type === 'user' || $entity_type === 'company') {
                $pdo->prepare("UPDATE users SET report_count = report_count + 1 WHERE id = ?")
                    ->execute([$entity_id]);
            }
        } catch (Exception $e) {
            error_log("Failed to update report count: " . $e->getMessage());
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Report submitted successfully. Our admin team will review it shortly.',
        'report_id' => $pdo->lastInsertId()
    ]);
}

function getReportReasons() {
    $reasons = [
        'fake_profile' => 'Fake Profile',
        'fake_job' => 'Fake Job Posting',
        'inappropriate_content' => 'Inappropriate Content',
        'harassment' => 'Harassment or Bullying',
        'spam' => 'Spam',
        'scam' => 'Scam or Fraudulent Activity',
        'misleading_information' => 'Misleading Information',
        'copyright_violation' => 'Copyright Violation',
        'discrimination' => 'Discrimination',
        'offensive_language' => 'Offensive Language',
        'duplicate_posting' => 'Duplicate Posting',
        'privacy_violation' => 'Privacy Violation',
        'payment_issues' => 'Payment Issues',
        'other' => 'Other'
    ];
    
    echo json_encode([
        'success' => true,
        'reasons' => $reasons
    ]);
}

function getMyReports($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT 
            r.id,
            r.reported_entity_type,
            r.reported_entity_id,
            r.reason,
            r.description,
            r.status,
            r.admin_notes,
            r.created_at,
            r.reviewed_at,
            CASE 
                WHEN r.reported_entity_type = 'job' THEN (SELECT title FROM jobs WHERE id = r.reported_entity_id)
                WHEN r.reported_entity_type = 'user' THEN (SELECT CONCAT(first_name, ' ', last_name) FROM users WHERE id = r.reported_entity_id)
                ELSE NULL
            END as entity_name
        FROM reports r
        WHERE r.reporter_id = ?
        ORDER BY r.created_at DESC
        LIMIT 50
    ");
    
    $stmt->execute([$user_id]);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'reports' => $reports
    ]);
}
?>
