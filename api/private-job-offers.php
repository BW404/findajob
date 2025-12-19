<?php
/**
 * Private Job Offers API
 * Handles direct job offers from employers to specific job seekers
 */

require_once '../config/database.php';
require_once '../config/session.php';

header('Content-Type: application/json');

// Check authentication
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$userId = getCurrentUserId();
$userType = $_SESSION['user_type'] ?? null;
$action = $_POST['action'] ?? $_GET['action'] ?? null;

// For employer actions, check Pro subscription
if ($userType === 'employer' && in_array($action, ['create_offer', 'get_offers', 'get_offer_details', 'withdraw_offer'])) {
    $stmt = $pdo->prepare("SELECT subscription_type, subscription_end FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $isPro = ($subscription['subscription_type'] === 'pro' && 
              (!$subscription['subscription_end'] || strtotime($subscription['subscription_end']) > time()));
    
    if (!$isPro) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Private Job Offers require Pro subscription. Please upgrade to access this feature.']);
        exit;
    }
}

try {
    switch ($action) {
        case 'create_offer':
            // Employer creates a private job offer
            if ($userType !== 'employer') {
                throw new Exception('Only employers can create private job offers');
            }
            
            $jobSeekerId = $_POST['job_seeker_id'] ?? null;
            $jobTitle = trim($_POST['job_title'] ?? '');
            $jobDescription = trim($_POST['job_description'] ?? '');
            $jobType = $_POST['job_type'] ?? 'full-time';
            $category = $_POST['category'] ?? null;
            
            $state = $_POST['state'] ?? null;
            $city = $_POST['city'] ?? null;
            $locationType = $_POST['location_type'] ?? 'onsite';
            
            $salaryMin = !empty($_POST['salary_min']) ? floatval($_POST['salary_min']) : null;
            $salaryMax = !empty($_POST['salary_max']) ? floatval($_POST['salary_max']) : null;
            $salaryPeriod = $_POST['salary_period'] ?? 'monthly';
            
            $experienceLevel = $_POST['experience_level'] ?? 'intermediate';
            $educationLevel = $_POST['education_level'] ?? null;
            $requiredSkills = $_POST['required_skills'] ?? null;
            
            $offerMessage = trim($_POST['offer_message'] ?? '');
            $benefits = $_POST['benefits'] ?? null;
            $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
            $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : date('Y-m-d', strtotime('+30 days'));
            
            // Validation
            if (!$jobSeekerId || !$jobTitle || !$jobDescription) {
                throw new Exception('Job seeker, title, and description are required');
            }
            
            // Verify job seeker exists and is active
            $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND user_type = 'job_seeker' AND is_active = 1");
            $stmt->execute([$jobSeekerId]);
            if (!$stmt->fetch()) {
                throw new Exception('Invalid job seeker');
            }
            
            // Create the offer
            $expiresAt = date('Y-m-d H:i:s', strtotime($deadline . ' 23:59:59'));
            
            $stmt = $pdo->prepare("
                INSERT INTO private_job_offers (
                    employer_id, job_seeker_id, job_title, job_description, job_type, category,
                    state, city, location_type, salary_min, salary_max, salary_period,
                    experience_level, education_level, required_skills, offer_message,
                    benefits, start_date, deadline, expires_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $userId, $jobSeekerId, $jobTitle, $jobDescription, $jobType, $category,
                $state, $city, $locationType, $salaryMin, $salaryMax, $salaryPeriod,
                $experienceLevel, $educationLevel, $requiredSkills, $offerMessage,
                $benefits, $startDate, $deadline, $expiresAt
            ]);
            
            $offerId = $pdo->lastInsertId();
            
            // Create notification for job seeker
            $stmt = $pdo->prepare("
                INSERT INTO private_offer_notifications (offer_id, user_id, notification_type)
                VALUES (?, ?, 'new_offer')
            ");
            $stmt->execute([$offerId, $jobSeekerId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Private job offer sent successfully',
                'offer_id' => $offerId
            ]);
            break;
            
        case 'get_offers':
            // Get offers for current user (different queries for employer vs job seeker)
            if ($userType === 'employer') {
                // Employer sees all offers they've sent
                $status = $_GET['status'] ?? 'all';
                
                $sql = "
                    SELECT pjo.*, 
                           u.first_name, u.last_name, u.email,
                           jsp.profile_picture, jsp.years_of_experience, jsp.education_level
                    FROM private_job_offers pjo
                    LEFT JOIN users u ON pjo.job_seeker_id = u.id
                    LEFT JOIN job_seeker_profiles jsp ON u.id = jsp.user_id
                    WHERE pjo.employer_id = ?
                ";
                
                if ($status !== 'all') {
                    $sql .= " AND pjo.status = ?";
                    $stmt = $pdo->prepare($sql . " ORDER BY pjo.created_at DESC");
                    $stmt->execute([$userId, $status]);
                } else {
                    $stmt = $pdo->prepare($sql . " ORDER BY pjo.created_at DESC");
                    $stmt->execute([$userId]);
                }
                
            } else {
                // Job seeker sees offers sent to them
                $status = $_GET['status'] ?? 'all';
                
                $sql = "
                    SELECT pjo.*, 
                           u.first_name, u.last_name, u.email,
                           ep.company_name, ep.company_logo, ep.industry, ep.website
                    FROM private_job_offers pjo
                    LEFT JOIN users u ON pjo.employer_id = u.id
                    LEFT JOIN employer_profiles ep ON u.id = ep.user_id
                    WHERE pjo.job_seeker_id = ?
                ";
                
                if ($status !== 'all') {
                    $sql .= " AND pjo.status = ?";
                    $stmt = $pdo->prepare($sql . " ORDER BY pjo.created_at DESC");
                    $stmt->execute([$userId, $status]);
                } else {
                    $stmt = $pdo->prepare($sql . " ORDER BY pjo.created_at DESC");
                    $stmt->execute([$userId]);
                }
            }
            
            $offers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'offers' => $offers
            ]);
            break;
            
        case 'get_offer_details':
            // Get single offer details
            $offerId = $_GET['offer_id'] ?? null;
            
            if (!$offerId) {
                throw new Exception('Offer ID is required');
            }
            
            if ($userType === 'employer') {
                $stmt = $pdo->prepare("
                    SELECT pjo.*, 
                           u.first_name, u.last_name, u.email, u.phone,
                           jsp.profile_picture, jsp.current_job_title, jsp.years_of_experience,
                           jsp.skills, jsp.bio
                    FROM private_job_offers pjo
                    LEFT JOIN users u ON pjo.job_seeker_id = u.id
                    LEFT JOIN job_seeker_profiles jsp ON u.id = jsp.user_id
                    WHERE pjo.id = ? AND pjo.employer_id = ?
                ");
                $stmt->execute([$offerId, $userId]);
            } else {
                $stmt = $pdo->prepare("
                    SELECT pjo.*, 
                           u.first_name, u.last_name, u.email, u.phone,
                           ep.company_name, ep.company_logo, ep.industry, ep.website,
                           ep.company_size, ep.description as company_description
                    FROM private_job_offers pjo
                    LEFT JOIN users u ON pjo.employer_id = u.id
                    LEFT JOIN employer_profiles ep ON u.id = ep.user_id
                    WHERE pjo.id = ? AND pjo.job_seeker_id = ?
                ");
                $stmt->execute([$offerId, $userId]);
                
                // Mark as viewed if job seeker and not viewed yet
                $offer = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($offer && $offer['status'] === 'pending') {
                    $updateStmt = $pdo->prepare("
                        UPDATE private_job_offers 
                        SET status = 'viewed', viewed_at = NOW() 
                        WHERE id = ?
                    ");
                    $updateStmt->execute([$offerId]);
                    
                    // Notify employer
                    $notifyStmt = $pdo->prepare("
                        INSERT INTO private_offer_notifications (offer_id, user_id, notification_type)
                        VALUES (?, ?, 'offer_viewed')
                    ");
                    $notifyStmt->execute([$offerId, $offer['employer_id']]);
                }
                
                echo json_encode([
                    'success' => true,
                    'offer' => $offer
                ]);
                exit;
            }
            
            $offer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$offer) {
                throw new Exception('Offer not found');
            }
            
            echo json_encode([
                'success' => true,
                'offer' => $offer
            ]);
            break;
            
        case 'respond_to_offer':
            // Job seeker accepts or rejects offer
            if ($userType !== 'job_seeker') {
                throw new Exception('Only job seekers can respond to offers');
            }
            
            $offerId = $_POST['offer_id'] ?? null;
            $response = $_POST['response'] ?? null; // 'accepted' or 'rejected'
            $responseMessage = trim($_POST['response_message'] ?? '');
            
            if (!$offerId || !in_array($response, ['accepted', 'rejected'])) {
                throw new Exception('Invalid response');
            }
            
            // Verify offer belongs to this job seeker
            $stmt = $pdo->prepare("
                SELECT employer_id FROM private_job_offers 
                WHERE id = ? AND job_seeker_id = ? AND status IN ('pending', 'viewed')
            ");
            $stmt->execute([$offerId, $userId]);
            $offer = $stmt->fetch();
            
            if (!$offer) {
                throw new Exception('Offer not found or already responded');
            }
            
            // Update offer status
            $stmt = $pdo->prepare("
                UPDATE private_job_offers 
                SET status = ?, responded_at = NOW(), response_message = ?
                WHERE id = ?
            ");
            $stmt->execute([$response, $responseMessage, $offerId]);
            
            // Notify employer
            $notificationType = $response === 'accepted' ? 'offer_accepted' : 'offer_rejected';
            $stmt = $pdo->prepare("
                INSERT INTO private_offer_notifications (offer_id, user_id, notification_type)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$offerId, $offer['employer_id'], $notificationType]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Response submitted successfully'
            ]);
            break;
            
        case 'withdraw_offer':
            // Employer withdraws an offer
            if ($userType !== 'employer') {
                throw new Exception('Only employers can withdraw offers');
            }
            
            $offerId = $_POST['offer_id'] ?? null;
            
            if (!$offerId) {
                throw new Exception('Offer ID is required');
            }
            
            $stmt = $pdo->prepare("
                UPDATE private_job_offers 
                SET status = 'withdrawn', updated_at = NOW()
                WHERE id = ? AND employer_id = ? AND status IN ('pending', 'viewed')
            ");
            $stmt->execute([$offerId, $userId]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('Offer not found or cannot be withdrawn');
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Offer withdrawn successfully'
            ]);
            break;
            
        case 'get_unread_count':
            // Get count of unread offers
            if ($userType === 'job_seeker') {
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as count 
                    FROM private_job_offers 
                    WHERE job_seeker_id = ? AND status = 'pending'
                ");
                $stmt->execute([$userId]);
                $result = $stmt->fetch();
                
                echo json_encode([
                    'success' => true,
                    'count' => $result['count']
                ]);
            } else {
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as count 
                    FROM private_offer_notifications 
                    WHERE user_id = ? AND is_read = 0
                ");
                $stmt->execute([$userId]);
                $result = $stmt->fetch();
                
                echo json_encode([
                    'success' => true,
                    'count' => $result['count']
                ]);
            }
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
