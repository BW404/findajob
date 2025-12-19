<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $query = $_GET['q'] ?? '';
    $type = $_GET['type'] ?? 'all'; // 'jobs', 'companies', 'locations', 'all'
    $limit = min(10, max(1, intval($_GET['limit'] ?? 5)));
    
    $results = [];
    
    if ($type === 'all' || $type === 'jobs') {
        $results['jobs'] = searchJobs($query, $limit);
    }
    
    if ($type === 'all' || $type === 'companies') {
        $results['companies'] = searchCompanies($query, $limit);
    }
    
    if ($type === 'all' || $type === 'locations') {
        $results['locations'] = searchLocations($query, $limit);
    }
    
    if ($type === 'all' || $type === 'categories') {
        $results['categories'] = searchCategories($query, $limit);
    }
    
    if ($type === 'job_seekers') {
        $results = searchJobSeekers($query, $limit);
        echo json_encode([
            'success' => true,
            'query' => $query,
            'results' => $results
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'query' => $query,
        'results' => $results
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Search error: ' . $e->getMessage()]);
}

function searchJobs($query, $limit) {
    global $pdo;
    
    if (empty($query)) return [];
    
    $stmt = $pdo->prepare("
        SELECT 
            j.id,
            j.title,
            j.slug,
            j.company_name,
            j.state,
            j.city,
            j.job_type,
            j.created_at,
            jc.name as category_name,
            jc.icon as category_icon
        FROM jobs j
        LEFT JOIN job_categories jc ON j.category_id = jc.id
        WHERE j.status = 'active' 
        AND (j.title LIKE ? OR j.company_name LIKE ? OR j.description LIKE ?)
        ORDER BY 
            CASE 
                WHEN j.title LIKE ? THEN 1
                WHEN j.company_name LIKE ? THEN 2
                ELSE 3
            END,
            j.is_featured DESC,
            j.created_at DESC
        LIMIT ?
    ");
    
    $searchTerm = '%' . $query . '%';
    $exactTitle = $query . '%';
    $exactCompany = $query . '%';
    
    $stmt->execute([
        $searchTerm, $searchTerm, $searchTerm,
        $exactTitle, $exactCompany,
        $limit
    ]);
    
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($jobs as &$job) {
        $job['url'] = "/findajob/pages/jobs/details.php?id=" . $job['id'];
        $job['location'] = trim(($job['city'] ?? '') . ', ' . ($job['state'] ?? ''), ', ');
        $job['type'] = 'job';
    }
    
    return $jobs;
}

function searchCompanies($query, $limit) {
    global $pdo;
    
    if (empty($query)) return [];
    
    $stmt = $pdo->prepare("
        SELECT DISTINCT
            ep.company_name as name,
            ep.industry,
            ep.city,
            ep.state,
            ep.company_logo as logo,
            ep.company_cac_verified as is_verified,
            COUNT(j.id) as job_count
        FROM employer_profiles ep
        LEFT JOIN jobs j ON ep.user_id = j.employer_id AND j.status = 'active'
        WHERE ep.company_name LIKE ?
        GROUP BY ep.id
        ORDER BY 
            CASE WHEN ep.company_name LIKE ? THEN 1 ELSE 2 END,
            ep.company_cac_verified DESC,
            job_count DESC
        LIMIT ?
    ");
    
    $searchTerm = '%' . $query . '%';
    $exactName = $query . '%';
    
    $stmt->execute([$searchTerm, $exactName, $limit]);
    
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($companies as &$company) {
        $company['url'] = "/findajob/pages/jobs/browse.php?company=" . urlencode($company['name']);
        $company['location'] = trim(($company['city'] ?? '') . ', ' . ($company['state'] ?? ''), ', ');
        $company['type'] = 'company';
    }
    
    return $companies;
}

function searchLocations($query, $limit) {
    global $pdo;
    
    if (empty($query)) return [];
    
    $locations = [];
    
    // Search states
    $stmt = $pdo->prepare("
        SELECT 
            name,
            'state' as location_type,
            COUNT(j.id) as job_count
        FROM nigeria_states ns
        LEFT JOIN jobs j ON (j.state = ns.name AND j.status = 'active')
        WHERE ns.name LIKE ?
        GROUP BY ns.id
        HAVING job_count > 0 OR ns.name LIKE ?
        ORDER BY 
            CASE WHEN ns.name LIKE ? THEN 1 ELSE 2 END,
            job_count DESC
        LIMIT ?
    ");
    
    $searchTerm = '%' . $query . '%';
    $exactName = $query . '%';
    
    $stmt->execute([$searchTerm, $searchTerm, $exactName, $limit]);
    $states = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Search cities if we have space for more results
    if (count($states) < $limit) {
        $remainingLimit = $limit - count($states);
        
        $stmt = $pdo->prepare("
            SELECT DISTINCT
                j.city as name,
                j.state,
                'city' as location_type,
                COUNT(j.id) as job_count
            FROM jobs j
            WHERE j.status = 'active' 
            AND j.city IS NOT NULL 
            AND j.city LIKE ?
            GROUP BY j.city, j.state
            ORDER BY 
                CASE WHEN j.city LIKE ? THEN 1 ELSE 2 END,
                job_count DESC
            LIMIT ?
        ");
        
        $stmt->execute([$searchTerm, $exactName, $remainingLimit]);
        $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $locations = array_merge($states, $cities);
    } else {
        $locations = $states;
    }
    
    foreach ($locations as &$location) {
        if ($location['location_type'] === 'state') {
            $location['url'] = "/findajob/pages/jobs/browse.php?location=" . urlencode($location['name']);
            $location['display_name'] = $location['name'] . " State";
        } else {
            $location['url'] = "/findajob/pages/jobs/browse.php?location=" . urlencode($location['name']);
            $location['display_name'] = $location['name'] . ", " . ($location['state'] ?? '');
        }
        $location['type'] = 'location';
    }
    
    return $locations;
}

function searchCategories($query, $limit) {
    global $pdo;
    
    if (empty($query)) return [];
    
    $stmt = $pdo->prepare("
        SELECT 
            jc.name,
            jc.slug,
            jc.icon,
            jc.description,
            COUNT(j.id) as job_count
        FROM job_categories jc
        LEFT JOIN jobs j ON jc.id = j.category_id AND j.status = 'active'
        WHERE jc.is_active = TRUE 
        AND (jc.name LIKE ? OR jc.description LIKE ?)
        GROUP BY jc.id
        ORDER BY 
            CASE WHEN jc.name LIKE ? THEN 1 ELSE 2 END,
            job_count DESC
        LIMIT ?
    ");
    
    $searchTerm = '%' . $query . '%';
    $exactName = $query . '%';
    
    $stmt->execute([$searchTerm, $searchTerm, $exactName, $limit]);
    
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($categories as &$category) {
        $category['url'] = "/findajob/pages/jobs/browse.php?category=" . $category['slug'];
        $category['type'] = 'category';
    }
    
    return $categories;
}

function searchJobSeekers($query, $limit) {
    global $pdo;
    
    if (empty($query)) return [];
    
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.first_name,
            u.last_name,
            u.email,
            jsp.profile_picture,
            jsp.years_of_experience,
            jsp.skills,
            jsp.education_level,
            jsp.job_status
        FROM users u
        LEFT JOIN job_seeker_profiles jsp ON u.id = jsp.user_id
        WHERE u.user_type = 'job_seeker' 
        AND u.is_active = 1
        AND (
            u.first_name LIKE ? 
            OR u.last_name LIKE ? 
            OR u.email LIKE ?
            OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?
        )
        ORDER BY 
            CASE 
                WHEN u.first_name LIKE ? THEN 1
                WHEN u.last_name LIKE ? THEN 2
                WHEN u.email LIKE ? THEN 3
                ELSE 4
            END,
            u.created_at DESC
        LIMIT ?
    ");
    
    $searchTerm = '%' . $query . '%';
    $exactMatch = $query . '%';
    
    // Bind parameters
    $stmt->bindValue(1, $searchTerm, PDO::PARAM_STR);
    $stmt->bindValue(2, $searchTerm, PDO::PARAM_STR);
    $stmt->bindValue(3, $searchTerm, PDO::PARAM_STR);
    $stmt->bindValue(4, $searchTerm, PDO::PARAM_STR);
    $stmt->bindValue(5, $exactMatch, PDO::PARAM_STR);
    $stmt->bindValue(6, $exactMatch, PDO::PARAM_STR);
    $stmt->bindValue(7, $exactMatch, PDO::PARAM_STR);
    $stmt->bindValue(8, (int)$limit, PDO::PARAM_INT);
    
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>