<?php
/**
 * Interview Scheduling API
 * Handles scheduling and managing interviews between employers and job seekers
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/email-notifications.php';

header('Content-Type: application/json');

// Ensure $pdo is available (from database.php)
if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection error']);
    exit;
}

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'You must be logged in']);
    exit;
}

$user_id = getCurrentUserId();
$user_type = $_SESSION['user_type'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'schedule_interview':
            scheduleInterview($pdo, $user_id, $user_type);
            break;
            
        case 'update_interview':
            updateInterview($pdo, $user_id, $user_type);
            break;
            
        case 'cancel_interview':
            cancelInterview($pdo, $user_id, $user_type);
            break;
            
        case 'get_interview':
            getInterviewDetails($pdo, $user_id, $user_type);
            break;
            
        case 'get_my_interviews':
            getMyInterviews($pdo, $user_id, $user_type);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    error_log("Interview API Error: " . $e->getMessage() . " | User: " . $user_id . " | Action: " . $action);
    http_response_code(500);
    
    if (defined('DEV_MODE') && DEV_MODE) {
        echo json_encode(['success' => false, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
    } else {
        echo json_encode(['success' => false, 'error' => 'An error occurred. Please try again.']);
    }
}

function scheduleInterview($pdo, $user_id, $user_type) {
    // Only employers can schedule interviews
    if ($user_type !== 'employer') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Only employers can schedule interviews']);
        exit;
    }
    
    // Validate required fields
    $required = ['application_id', 'interview_date', 'interview_time', 'interview_type'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "Missing required field: $field"]);
            exit;
        }
    }
    
    $application_id = intval($_POST['application_id']);
    $interview_date = trim($_POST['interview_date']);
    $interview_time = trim($_POST['interview_time']);
    $interview_type = trim($_POST['interview_type']);
    $interview_link = isset($_POST['interview_link']) ? trim($_POST['interview_link']) : null;
    $interview_notes = isset($_POST['interview_notes']) ? trim(strip_tags($_POST['interview_notes'])) : null;
    
    // Validate interview type
    $valid_types = ['phone', 'video', 'in_person', 'online'];
    if (!in_array($interview_type, $valid_types)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid interview type']);
        exit;
    }
    
    // Validate interview link for video/online interviews
    if (in_array($interview_type, ['video', 'online']) && empty($interview_link)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Interview link is required for video/online interviews']);
        exit;
    }
    
    // Validate URL format for interview link
    if ($interview_link && !filter_var($interview_link, FILTER_VALIDATE_URL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid interview link URL']);
        exit;
    }
    
    // Combine date and time
    $interview_datetime = $interview_date . ' ' . $interview_time;
    
    // Validate datetime format and future date
    $datetime_obj = DateTime::createFromFormat('Y-m-d H:i', $interview_datetime);
    if (!$datetime_obj) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid date/time format']);
        exit;
    }
    
    if ($datetime_obj <= new DateTime()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Interview must be scheduled for a future date/time']);
        exit;
    }
    
    // Verify application exists and belongs to employer's job
    $stmt = $pdo->prepare("
        SELECT ja.*, j.title as job_title, j.employer_id,
               u.first_name, u.last_name, u.email,
               ep.company_name
        FROM job_applications ja
        JOIN jobs j ON ja.job_id = j.id
        JOIN users u ON ja.job_seeker_id = u.id
        LEFT JOIN employer_profiles ep ON j.employer_id = ep.user_id
        WHERE ja.id = ? AND j.employer_id = ?
    ");
    $stmt->execute([$application_id, $user_id]);
    $application = $stmt->fetch();
    
    if (!$application) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Application not found or unauthorized']);
        exit;
    }
    
    // Update application with interview details
    $stmt = $pdo->prepare("
        UPDATE job_applications 
        SET interview_date = ?,
            interview_type = ?,
            interview_link = ?,
            employer_notes = ?,
            application_status = 'interviewed',
            responded_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->execute([
        $interview_datetime,
        $interview_type,
        $interview_link,
        $interview_notes,
        $application_id
    ]);
    
    // Send email notification to job seeker
    try {
        sendInterviewScheduledEmail(
            $application['email'],
            $application['first_name'] . ' ' . $application['last_name'],
            $application['job_title'],
            $application['company_name'] ?? 'the company',
            $interview_datetime,
            $interview_type,
            $interview_link,
            $interview_notes
        );
    } catch (Exception $e) {
        error_log("Failed to send interview email: " . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Interview scheduled successfully',
        'interview' => [
            'application_id' => $application_id,
            'date' => $interview_datetime,
            'type' => $interview_type,
            'link' => $interview_link
        ]
    ]);
}

function updateInterview($pdo, $user_id, $user_type) {
    // Only employers can update interviews
    if ($user_type !== 'employer') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Only employers can update interviews']);
        exit;
    }
    
    $application_id = intval($_POST['application_id'] ?? 0);
    
    if (!$application_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Application ID required']);
        exit;
    }
    
    // Verify ownership
    $stmt = $pdo->prepare("
        SELECT ja.id 
        FROM job_applications ja
        JOIN jobs j ON ja.job_id = j.id
        WHERE ja.id = ? AND j.employer_id = ?
    ");
    $stmt->execute([$application_id, $user_id]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Application not found']);
        exit;
    }
    
    // Build update query dynamically
    $updates = [];
    $params = [];
    
    if (isset($_POST['interview_date']) && isset($_POST['interview_time'])) {
        $interview_datetime = trim($_POST['interview_date']) . ' ' . trim($_POST['interview_time']);
        $updates[] = "interview_date = ?";
        $params[] = $interview_datetime;
    }
    
    if (isset($_POST['interview_type'])) {
        $updates[] = "interview_type = ?";
        $params[] = trim($_POST['interview_type']);
    }
    
    if (isset($_POST['interview_link'])) {
        $updates[] = "interview_link = ?";
        $params[] = trim($_POST['interview_link']);
    }
    
    if (isset($_POST['interview_notes'])) {
        $updates[] = "employer_notes = ?";
        $params[] = trim(strip_tags($_POST['interview_notes']));
    }
    
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No fields to update']);
        exit;
    }
    
    $params[] = $application_id;
    
    $sql = "UPDATE job_applications SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    echo json_encode(['success' => true, 'message' => 'Interview updated successfully']);
}

function cancelInterview($pdo, $user_id, $user_type) {
    $application_id = intval($_POST['application_id'] ?? 0);
    
    if (!$application_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Application ID required']);
        exit;
    }
    
    // Verify ownership based on user type
    if ($user_type === 'employer') {
        $stmt = $pdo->prepare("
            SELECT ja.id 
            FROM job_applications ja
            JOIN jobs j ON ja.job_id = j.id
            WHERE ja.id = ? AND j.employer_id = ?
        ");
        $stmt->execute([$application_id, $user_id]);
    } else {
        $stmt = $pdo->prepare("
            SELECT id FROM job_applications 
            WHERE id = ? AND job_seeker_id = ?
        ");
        $stmt->execute([$application_id, $user_id]);
    }
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Application not found']);
        exit;
    }
    
    // Clear interview details
    $stmt = $pdo->prepare("
        UPDATE job_applications 
        SET interview_date = NULL,
            interview_type = 'video',
            interview_link = NULL,
            application_status = 'shortlisted'
        WHERE id = ?
    ");
    $stmt->execute([$application_id]);
    
    echo json_encode(['success' => true, 'message' => 'Interview cancelled successfully']);
}

function getInterviewDetails($pdo, $user_id, $user_type) {
    $application_id = intval($_GET['application_id'] ?? 0);
    
    if (!$application_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Application ID required']);
        exit;
    }
    
    // Build query based on user type
    if ($user_type === 'employer') {
        $stmt = $pdo->prepare("
            SELECT ja.*, j.title as job_title, j.employer_id,
                   u.first_name, u.last_name, u.email, u.phone
            FROM job_applications ja
            JOIN jobs j ON ja.job_id = j.id
            JOIN users u ON ja.job_seeker_id = u.id
            WHERE ja.id = ? AND j.employer_id = ?
        ");
        $stmt->execute([$application_id, $user_id]);
    } else {
        $stmt = $pdo->prepare("
            SELECT ja.*, j.title as job_title,
                   ep.company_name
            FROM job_applications ja
            JOIN jobs j ON ja.job_id = j.id
            LEFT JOIN employer_profiles ep ON j.employer_id = ep.user_id
            WHERE ja.id = ? AND ja.job_seeker_id = ?
        ");
        $stmt->execute([$application_id, $user_id]);
    }
    
    $interview = $stmt->fetch();
    
    if (!$interview) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Interview not found']);
        exit;
    }
    
    echo json_encode(['success' => true, 'interview' => $interview]);
}

function getMyInterviews($pdo, $user_id, $user_type) {
    if ($user_type === 'employer') {
        $stmt = $pdo->prepare("
            SELECT ja.*, j.title as job_title,
                   u.first_name, u.last_name, u.email, u.phone
            FROM job_applications ja
            JOIN jobs j ON ja.job_id = j.id
            JOIN users u ON ja.job_seeker_id = u.id
            WHERE j.employer_id = ? 
            AND ja.interview_date IS NOT NULL
            AND ja.interview_date >= NOW()
            ORDER BY ja.interview_date ASC
        ");
        $stmt->execute([$user_id]);
    } else {
        $stmt = $pdo->prepare("
            SELECT ja.*, j.title as job_title,
                   ep.company_name, ep.company_logo
            FROM job_applications ja
            JOIN jobs j ON ja.job_id = j.id
            LEFT JOIN employer_profiles ep ON j.employer_id = ep.user_id
            WHERE ja.job_seeker_id = ? 
            AND ja.interview_date IS NOT NULL
            AND ja.interview_date >= NOW()
            ORDER BY ja.interview_date ASC
        ");
        $stmt->execute([$user_id]);
    }
    
    $interviews = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'interviews' => $interviews]);
}
