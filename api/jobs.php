<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../config/session.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGetJobs();
            break;
        case 'POST':
            // Check if this is a save/unsave action
            $action = $_POST['action'] ?? '';
            if ($action === 'save' || $action === 'unsave') {
                handleSaveJob($action);
            } else {
                handleCreateJob();
            }
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

function handleSaveJob($action) {
    global $pdo;
    
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'You must be logged in to save jobs']);
        return;
    }
    
    if (!isJobSeeker()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Only job seekers can save jobs']);
        return;
    }
    
    $user_id = getCurrentUserId();
    $job_id = intval($_POST['job_id'] ?? 0);
    
    if ($job_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid job ID']);
        return;
    }
    
    try {
        if ($action === 'save') {
            // Check if job exists and is active
            $checkJob = $pdo->prepare("SELECT id FROM jobs WHERE id = ? AND STATUS = 'active'");
            $checkJob->execute([$job_id]);
            if (!$checkJob->fetch()) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Job not found or not active']);
                return;
            }
            
            // Insert (will fail silently if already exists due to UNIQUE constraint)
            $stmt = $pdo->prepare("INSERT IGNORE INTO saved_jobs (user_id, job_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $job_id]);
            
            echo json_encode(['success' => true, 'message' => 'Job saved successfully', 'action' => 'saved']);
            
        } else if ($action === 'unsave') {
            // Remove from saved jobs
            $stmt = $pdo->prepare("DELETE FROM saved_jobs WHERE user_id = ? AND job_id = ?");
            $stmt->execute([$user_id, $job_id]);
            
            echo json_encode(['success' => true, 'message' => 'Job removed from saved list', 'action' => 'unsaved']);
        }
    } catch (PDOException $e) {
        // Handle case where saved_jobs table doesn't exist yet
        if (strpos($e->getMessage(), "doesn't exist") !== false) {
            http_response_code(503);
            echo json_encode(['success' => false, 'message' => 'Saved jobs feature is not yet available. Please run the database migration.']);
        } else {
            throw $e;
        }
    }
}

function handleGetJobs() {
    global $pdo;
    
    // Get search parameters
    $keywords = $_GET['keywords'] ?? '';
    $location = $_GET['location'] ?? '';
    $category = $_GET['category'] ?? '';
    $job_type = $_GET['job_type'] ?? '';
    $salary_min = $_GET['salary_min'] ?? '';
    $salary_max = $_GET['salary_max'] ?? '';
    $experience_level = $_GET['experience_level'] ?? '';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(50, max(10, intval($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    
    // Build the search query
    $whereConditions = ["j.status = 'active'"];
    $params = [];
    
    // Keyword search (title, company, description)
    if (!empty($keywords)) {
        $whereConditions[] = "(j.title LIKE ? OR j.company_name LIKE ? OR j.description LIKE ?)";
        $keywordParam = '%' . $keywords . '%';
        $params[] = $keywordParam;
        $params[] = $keywordParam;
        $params[] = $keywordParam;
    }
    
    // Location filter - enhanced for Nigerian locations
    if (!empty($location)) {
        // Check if location matches exact state name first, then fuzzy match
        $whereConditions[] = "(
            j.state = ? OR 
            j.state LIKE ? OR 
            j.city LIKE ? OR
            EXISTS (
                SELECT 1 FROM nigeria_states ns 
                WHERE (ns.name LIKE ? OR ns.code LIKE ?) 
                AND j.state = ns.name
            ) OR
            EXISTS (
                SELECT 1 FROM nigeria_lgas nl 
                WHERE nl.name LIKE ? 
                AND j.city = nl.name
            )
        )";
        
        $locationParam = '%' . $location . '%';
        $params[] = $location; // Exact state match
        $params[] = $locationParam; // State fuzzy match
        $params[] = $locationParam; // City fuzzy match
        $params[] = $locationParam; // State name/code match
        $params[] = strtoupper($location) . '%'; // State code match
        $params[] = $locationParam; // LGA match
    }
    
    // Category filter
    if (!empty($category)) {
        $whereConditions[] = "jc.slug = ?";
        $params[] = $category;
    }
    
    // Job type filter
    if (!empty($job_type)) {
        $whereConditions[] = "j.job_type = ?";
        $params[] = $job_type;
    }
    
    // Salary range filter
    if (!empty($salary_min)) {
        $whereConditions[] = "j.salary_max >= ?";
        $params[] = intval($salary_min);
    }
    
    if (!empty($salary_max)) {
        $whereConditions[] = "j.salary_min <= ?";
        $params[] = intval($salary_max);
    }
    
    // Experience level filter
    if (!empty($experience_level)) {
        $whereConditions[] = "j.experience_level = ?";
        $params[] = $experience_level;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Count total results
    $countQuery = "
        SELECT COUNT(DISTINCT j.id) as total 
        FROM jobs j 
        LEFT JOIN job_categories jc ON j.category_id = jc.id 
        LEFT JOIN employer_profiles ep ON j.employer_id = ep.user_id 
        WHERE $whereClause
    ";
    
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();
    
    // Get jobs with pagination
    $query = "
        SELECT 
            j.id,
            j.title,
            j.slug,
            j.job_type,
            j.employment_type,
            j.description,
            j.requirements,
            j.salary_min,
            j.salary_max,
            j.salary_currency,
            j.salary_period,
            j.location_type,
            j.state,
            j.city,
            j.experience_level,
            j.education_level,
            j.application_deadline,
            j.company_name,
            j.is_featured,
            j.is_urgent,
            j.is_remote_friendly,
            j.views_count,
            j.applications_count,
            j.created_at,
            jc.name as category_name,
            jc.slug as category_slug,
            jc.icon as category_icon,
            ep.company_name as employer_company_name,
            ep.is_verified as employer_verified
        FROM jobs j 
        LEFT JOIN job_categories jc ON j.category_id = jc.id 
        LEFT JOIN employer_profiles ep ON j.employer_id = ep.user_id 
        WHERE $whereClause
        ORDER BY j.is_featured DESC, j.created_at DESC
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the jobs data
    foreach ($jobs as &$job) {
        // Format salary
        if ($job['salary_min'] || $job['salary_max']) {
            $job['salary_formatted'] = formatSalaryRange(
                $job['salary_min'], 
                $job['salary_max'], 
                $job['salary_currency'], 
                $job['salary_period']
            );
        } else {
            $job['salary_formatted'] = 'Salary not specified';
        }
        
        // Format dates
        $job['created_at_formatted'] = timeAgo($job['created_at']);
        
        if ($job['application_deadline']) {
            $job['deadline_formatted'] = date('M d, Y', strtotime($job['application_deadline']));
            $job['days_until_deadline'] = daysUntilDeadline($job['application_deadline']);
        }
        
        // Clean description for preview
        $job['description_preview'] = truncateText(strip_tags($job['description']), 150);
        
        // Format job type
        $job['job_type_formatted'] = ucwords(str_replace('_', ' ', $job['job_type']));
        $job['employment_type_formatted'] = ucwords(str_replace('_', ' ', $job['employment_type']));
        $job['experience_level_formatted'] = ucwords($job['experience_level']);
        
        // Company logo fallback
        if (empty($job['company_logo']) && !empty($job['employer_logo'])) {
            $job['company_logo'] = $job['employer_logo'];
        }
        
        // Location formatted
        $location_parts = array_filter([$job['city'], $job['state']]);
        $job['location_formatted'] = implode(', ', $location_parts) ?: 'Nigeria';
    }
    
    // Calculate pagination
    $totalPages = ceil($total / $limit);
    $hasNext = $page < $totalPages;
    $hasPrev = $page > 1;
    
    $response = [
        'success' => true,
        'jobs' => $jobs,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_items' => $total,
            'items_per_page' => $limit,
            'has_next' => $hasNext,
            'has_prev' => $hasPrev
        ],
        'filters' => [
            'keywords' => $keywords,
            'location' => $location,
            'category' => $category,
            'job_type' => $job_type,
            'salary_min' => $salary_min,
            'salary_max' => $salary_max,
            'experience_level' => $experience_level
        ]
    ];
    
    echo json_encode($response);
}

function handleCreateJob() {
    // Check if user is logged in and is an employer
    if (!isLoggedIn() || !isEmployer()) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        return;
    }
    
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required = ['title', 'description', 'job_type', 'employment_type'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Field '$field' is required"]);
            return;
        }
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO jobs (
                employer_id, title, slug, category_id, job_type, employment_type,
                description, requirements, responsibilities, benefits,
                salary_min, salary_max, salary_currency, salary_period,
                location_type, state, city, address,
                experience_level, education_level, application_deadline,
                application_email, application_url, company_name,
                is_featured, is_urgent, is_remote_friendly, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $slug = generateSlug($input['title']);
        $employer_id = getCurrentUserId();
        
        $stmt->execute([
            $employer_id,
            $input['title'],
            $slug,
            $input['category_id'] ?? null,
            $input['job_type'],
            $input['employment_type'],
            $input['description'],
            $input['requirements'] ?? null,
            $input['responsibilities'] ?? null,
            $input['benefits'] ?? null,
            $input['salary_min'] ?? null,
            $input['salary_max'] ?? null,
            $input['salary_currency'] ?? 'NGN',
            $input['salary_period'] ?? 'monthly',
            $input['location_type'] ?? 'onsite',
            $input['state'] ?? null,
            $input['city'] ?? null,
            $input['address'] ?? null,
            $input['experience_level'] ?? 'entry',
            $input['education_level'] ?? 'any',
            $input['application_deadline'] ?? null,
            $input['application_email'] ?? null,
            $input['application_url'] ?? null,
            $input['company_name'] ?? null,
            $input['is_featured'] ?? false,
            $input['is_urgent'] ?? false,
            $input['is_remote_friendly'] ?? false,
            $input['status'] ?? 'active'
        ]);
        
        $job_id = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'job_id' => $job_id,
            'message' => 'Job created successfully'
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

// Helper functions
function formatSalaryRange($min, $max, $currency = 'NGN', $period = 'monthly') {
    $symbol = $currency === 'NGN' ? '₦' : '$';
    
    if ($min && $max) {
        if ($min === $max) {
            return $symbol . number_format($min) . '/' . $period;
        } else {
            return $symbol . number_format($min) . ' - ' . $symbol . number_format($max) . '/' . $period;
        }
    } elseif ($min) {
        return 'From ' . $symbol . number_format($min) . '/' . $period;
    } elseif ($max) {
        return 'Up to ' . $symbol . number_format($max) . '/' . $period;
    }
    
    return 'Competitive salary';
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . 'm ago';
    if ($time < 86400) return floor($time/3600) . 'h ago';
    if ($time < 2592000) return floor($time/86400) . 'd ago';
    if ($time < 31536000) return floor($time/2592000) . 'mo ago';
    
    return floor($time/31536000) . 'y ago';
}

function daysUntilDeadline($deadline) {
    $now = new DateTime();
    $deadlineDate = new DateTime($deadline);
    $diff = $now->diff($deadlineDate);
    
    if ($deadlineDate < $now) {
        return -1; // Expired
    }
    
    return $diff->days;
}

function truncateText($text, $length = 150) {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length) . '...';
}

function generateSlug($title) {
    $slug = strtolower($title);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    $slug = trim($slug, '-');
    
    // Add timestamp for uniqueness
    return $slug . '-' . time();
}
?>
