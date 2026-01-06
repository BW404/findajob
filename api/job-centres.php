<?php
/**
 * Job Centres API
 * Handles job centre listings, search, reviews, and bookmarks
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    switch ($action) {
        case 'list':
            listJobCentres();
            break;
        case 'get':
            getJobCentre();
            break;
        case 'search':
            searchJobCentres();
            break;
        case 'bookmark':
            bookmarkJobCentre();
            break;
        case 'remove_bookmark':
            removeBookmark();
            break;
        case 'add_review':
            addReview();
            break;
        case 'get_reviews':
            getReviews();
            break;
        case 'increment_view':
            incrementView();
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log("Job Centres API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'An error occurred']);
}

/**
 * List job centres with filters
 */
function listJobCentres() {
    global $pdo;
    
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $per_page = 12;
    $offset = ($page - 1) * $per_page;
    
    // Filters
    $state = $_GET['state'] ?? '';
    $category = $_GET['category'] ?? '';
    $is_government = isset($_GET['is_government']) ? intval($_GET['is_government']) : null;
    $sort = $_GET['sort'] ?? 'rating'; // rating, name, newest
    
    // Build query
    $where = ['is_active = 1'];
    $params = [];
    
    if (!empty($state)) {
        $where[] = 'state = ?';
        $params[] = $state;
    }
    
    if (!empty($category)) {
        $where[] = "(category = ? OR category = 'both')";
        $params[] = $category;
    }
    
    if ($is_government !== null) {
        $where[] = 'is_government = ?';
        $params[] = $is_government;
    }
    
    $where_sql = implode(' AND ', $where);
    
    // Sorting
    $order_by = match($sort) {
        'name' => 'name ASC',
        'newest' => 'created_at DESC',
        'views' => 'views_count DESC',
        default => 'rating_avg DESC, rating_count DESC'
    };
    
    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM job_centres WHERE $where_sql";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];
    
    // Get job centres
    $sql = "
        SELECT 
            jc.*,
            (SELECT COUNT(*) FROM job_centre_bookmarks WHERE job_centre_id = jc.id) as bookmark_count
        FROM job_centres jc
        WHERE $where_sql
        ORDER BY $order_by
        LIMIT $per_page OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $centres = $stmt->fetchAll();
    
    // Parse services JSON
    foreach ($centres as &$centre) {
        $centre['services'] = json_decode($centre['services'], true) ?: [];
    }
    
    // Check bookmarks if user is logged in
    if (isLoggedIn()) {
        $user_id = getCurrentUserId();
        $centre_ids = array_column($centres, 'id');
        
        if (!empty($centre_ids)) {
            $placeholders = str_repeat('?,', count($centre_ids) - 1) . '?';
            $stmt = $pdo->prepare("SELECT job_centre_id FROM job_centre_bookmarks WHERE user_id = ? AND job_centre_id IN ($placeholders)");
            $stmt->execute(array_merge([$user_id], $centre_ids));
            $bookmarked = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($centres as &$centre) {
                $centre['is_bookmarked'] = in_array($centre['id'], $bookmarked);
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'centres' => $centres,
        'pagination' => [
            'page' => $page,
            'per_page' => $per_page,
            'total' => $total,
            'total_pages' => ceil($total / $per_page)
        ]
    ]);
}

/**
 * Get single job centre details
 */
function getJobCentre() {
    global $pdo;
    
    $id = $_GET['id'] ?? 0;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Job centre ID required']);
        return;
    }
    
    $sql = "
        SELECT 
            jc.*,
            (SELECT COUNT(*) FROM job_centre_bookmarks WHERE job_centre_id = jc.id) as bookmark_count
        FROM job_centres jc
        WHERE jc.id = ? AND jc.is_active = 1
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $centre = $stmt->fetch();
    
    if (!$centre) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Job centre not found']);
        return;
    }
    
    // Parse services
    $centre['services'] = json_decode($centre['services'], true) ?: [];
    
    // Check if bookmarked
    if (isLoggedIn()) {
        $user_id = getCurrentUserId();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM job_centre_bookmarks WHERE user_id = ? AND job_centre_id = ?");
        $stmt->execute([$user_id, $id]);
        $centre['is_bookmarked'] = $stmt->fetchColumn() > 0;
    } else {
        $centre['is_bookmarked'] = false;
    }
    
    echo json_encode(['success' => true, 'centre' => $centre]);
}

/**
 * Search job centres
 */
function searchJobCentres() {
    global $pdo;
    
    $query = trim($_GET['q'] ?? '');
    
    if (empty($query)) {
        echo json_encode(['success' => true, 'results' => []]);
        return;
    }
    
    $search_term = "%{$query}%";
    
    $sql = "
        SELECT 
            id, 
            name, 
            category, 
            state, 
            city,
            rating_avg,
            rating_count,
            is_government,
            is_verified
        FROM job_centres
        WHERE is_active = 1
        AND (
            name LIKE ? 
            OR city LIKE ?
            OR state LIKE ?
            OR services LIKE ?
        )
        ORDER BY 
            is_verified DESC,
            rating_avg DESC,
            name ASC
        LIMIT 20
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$search_term, $search_term, $search_term, $search_term]);
    $results = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'results' => $results]);
}

/**
 * Bookmark a job centre
 */
function bookmarkJobCentre() {
    global $pdo;
    
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Please log in to bookmark']);
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $job_centre_id = $data['job_centre_id'] ?? 0;
    $user_id = getCurrentUserId();
    
    if (!$job_centre_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Job centre ID required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO job_centre_bookmarks (user_id, job_centre_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $job_centre_id]);
        
        echo json_encode(['success' => true, 'message' => 'Job centre bookmarked successfully']);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Duplicate entry
            echo json_encode(['success' => false, 'error' => 'Already bookmarked']);
        } else {
            throw $e;
        }
    }
}

/**
 * Remove bookmark
 */
function removeBookmark() {
    global $pdo;
    
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Please log in']);
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $job_centre_id = $data['job_centre_id'] ?? 0;
    $user_id = getCurrentUserId();
    
    $stmt = $pdo->prepare("DELETE FROM job_centre_bookmarks WHERE user_id = ? AND job_centre_id = ?");
    $stmt->execute([$user_id, $job_centre_id]);
    
    echo json_encode(['success' => true, 'message' => 'Bookmark removed']);
}

/**
 * Add review for job centre
 */
function addReview() {
    global $pdo;
    
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Please log in to review']);
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $job_centre_id = $data['job_centre_id'] ?? 0;
    $rating = intval($data['rating'] ?? 0);
    $review = trim($data['review'] ?? '');
    $user_id = getCurrentUserId();
    
    // Validation
    if (!$job_centre_id || $rating < 1 || $rating > 5) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid data. Rating must be between 1 and 5.']);
        return;
    }
    
    // Sanitize review
    $review = strip_tags($review);
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO job_centre_reviews (job_centre_id, user_id, rating, review)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE rating = ?, review = ?, updated_at = NOW()
        ");
        $stmt->execute([$job_centre_id, $user_id, $rating, $review, $rating, $review]);
        
        echo json_encode(['success' => true, 'message' => 'Review submitted successfully']);
    } catch (PDOException $e) {
        error_log("Review error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to submit review']);
    }
}

/**
 * Get reviews for a job centre
 */
function getReviews() {
    global $pdo;
    
    $job_centre_id = $_GET['job_centre_id'] ?? 0;
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $per_page = 10;
    $offset = ($page - 1) * $per_page;
    
    if (!$job_centre_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Job centre ID required']);
        return;
    }
    
    // Get reviews
    $sql = "
        SELECT 
            r.*,
            CONCAT(u.first_name, ' ', u.last_name) as reviewer_name,
            u.profile_picture,
            jsp.years_of_experience
        FROM job_centre_reviews r
        JOIN users u ON r.user_id = u.id
        LEFT JOIN job_seeker_profiles jsp ON u.id = jsp.user_id
        WHERE r.job_centre_id = ?
        ORDER BY r.created_at DESC
        LIMIT $per_page OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$job_centre_id]);
    $reviews = $stmt->fetchAll();
    
    // Get total count
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM job_centre_reviews WHERE job_centre_id = ?");
    $stmt->execute([$job_centre_id]);
    $total = $stmt->fetch()['total'];
    
    echo json_encode([
        'success' => true,
        'reviews' => $reviews,
        'pagination' => [
            'page' => $page,
            'per_page' => $per_page,
            'total' => $total,
            'total_pages' => ceil($total / $per_page)
        ]
    ]);
}

/**
 * Increment view count
 */
function incrementView() {
    global $pdo;
    
    $job_centre_id = $_POST['job_centre_id'] ?? 0;
    
    if ($job_centre_id) {
        $stmt = $pdo->prepare("UPDATE job_centres SET views_count = views_count + 1 WHERE id = ?");
        $stmt->execute([$job_centre_id]);
    }
    
    echo json_encode(['success' => true]);
}
