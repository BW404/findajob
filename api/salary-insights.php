<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleSalaryInsightsRequest();
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

function handleSalaryInsightsRequest() {
    global $pdo;
    
    $action = $_GET['action'] ?? 'overview';
    $location = $_GET['location'] ?? '';
    $category = $_GET['category'] ?? '';
    
    switch ($action) {
        case 'overview':
            getSalaryOverview();
            break;
        case 'by_location':
            getSalaryByLocation($location);
            break;
        case 'by_category':
            getSalaryByCategory($category);
            break;
        case 'comparison':
            getSalaryComparison($location, $category);
            break;
        case 'trends':
            getSalaryTrends();
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
}

function getSalaryOverview() {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_jobs,
            AVG((salary_min + salary_max) / 2) as avg_salary,
            MIN(salary_min) as min_salary,
            MAX(salary_max) as max_salary,
            PERCENTILE_CONT(0.25) WITHIN GROUP (ORDER BY (salary_min + salary_max) / 2) as p25_salary,
            PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY (salary_min + salary_max) / 2) as median_salary,
            PERCENTILE_CONT(0.75) WITHIN GROUP (ORDER BY (salary_min + salary_max) / 2) as p75_salary
        FROM jobs 
        WHERE status = 'active' 
        AND salary_min IS NOT NULL 
        AND salary_max IS NOT NULL
        AND salary_min > 0
    ");
    $stmt->execute();
    $overview = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get top paying states
    $topStatesStmt = $pdo->prepare("
        SELECT 
            state,
            COUNT(*) as job_count,
            AVG((salary_min + salary_max) / 2) as avg_salary,
            MIN(salary_min) as min_salary,
            MAX(salary_max) as max_salary
        FROM jobs 
        WHERE status = 'active' 
        AND salary_min IS NOT NULL 
        AND salary_max IS NOT NULL
        AND salary_min > 0
        AND state IS NOT NULL
        GROUP BY state
        HAVING job_count >= 5
        ORDER BY avg_salary DESC
        LIMIT 10
    ");
    $topStatesStmt->execute();
    $topStates = $topStatesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get top paying categories
    $topCategoriesStmt = $pdo->prepare("
        SELECT 
            jc.name as category,
            jc.icon,
            COUNT(*) as job_count,
            AVG((j.salary_min + j.salary_max) / 2) as avg_salary
        FROM jobs j
        JOIN job_categories jc ON j.category_id = jc.id
        WHERE j.status = 'active' 
        AND j.salary_min IS NOT NULL 
        AND j.salary_max IS NOT NULL
        AND j.salary_min > 0
        GROUP BY jc.id, jc.name, jc.icon
        HAVING job_count >= 3
        ORDER BY avg_salary DESC
        LIMIT 10
    ");
    $topCategoriesStmt->execute();
    $topCategories = $topCategoriesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'overview' => $overview,
            'top_paying_states' => $topStates,
            'top_paying_categories' => $topCategories
        ]
    ]);
}

function getSalaryByLocation($location) {
    global $pdo;
    
    if (empty($location)) {
        // Get all states with salary data
        $stmt = $pdo->prepare("
            SELECT 
                j.state,
                ns.region,
                COUNT(*) as job_count,
                AVG((j.salary_min + j.salary_max) / 2) as avg_salary,
                MIN(j.salary_min) as min_salary,
                MAX(j.salary_max) as max_salary,
                PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY (j.salary_min + j.salary_max) / 2) as median_salary
            FROM jobs j
            LEFT JOIN nigeria_states ns ON j.state = ns.name
            WHERE j.status = 'active' 
            AND j.salary_min IS NOT NULL 
            AND j.salary_max IS NOT NULL
            AND j.salary_min > 0
            AND j.state IS NOT NULL
            GROUP BY j.state, ns.region
            ORDER BY avg_salary DESC
        ");
        $stmt->execute();
    } else {
        // Get salary data for specific location
        $stmt = $pdo->prepare("
            SELECT 
                j.state,
                j.city,
                ns.region,
                COUNT(*) as job_count,
                AVG((j.salary_min + j.salary_max) / 2) as avg_salary,
                MIN(j.salary_min) as min_salary,
                MAX(j.salary_max) as max_salary,
                PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY (j.salary_min + j.salary_max) / 2) as median_salary
            FROM jobs j
            LEFT JOIN nigeria_states ns ON j.state = ns.name
            WHERE j.status = 'active' 
            AND j.salary_min IS NOT NULL 
            AND j.salary_max IS NOT NULL
            AND j.salary_min > 0
            AND (j.state LIKE ? OR j.city LIKE ?)
            GROUP BY j.state, j.city, ns.region
            ORDER BY avg_salary DESC
        ");
        $locationParam = '%' . $location . '%';
        $stmt->execute([$locationParam, $locationParam]);
    }
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $results,
        'location_filter' => $location
    ]);
}

function getSalaryByCategory($category) {
    global $pdo;
    
    if (empty($category)) {
        // Get all categories with salary data
        $stmt = $pdo->prepare("
            SELECT 
                jc.name as category,
                jc.slug,
                jc.icon,
                COUNT(*) as job_count,
                AVG((j.salary_min + j.salary_max) / 2) as avg_salary,
                MIN(j.salary_min) as min_salary,
                MAX(j.salary_max) as max_salary,
                PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY (j.salary_min + j.salary_max) / 2) as median_salary
            FROM jobs j
            JOIN job_categories jc ON j.category_id = jc.id
            WHERE j.status = 'active' 
            AND j.salary_min IS NOT NULL 
            AND j.salary_max IS NOT NULL
            AND j.salary_min > 0
            GROUP BY jc.id, jc.name, jc.slug, jc.icon
            ORDER BY avg_salary DESC
        ");
        $stmt->execute();
    } else {
        // Get salary data for specific category
        $stmt = $pdo->prepare("
            SELECT 
                jc.name as category,
                j.state,
                COUNT(*) as job_count,
                AVG((j.salary_min + j.salary_max) / 2) as avg_salary,
                MIN(j.salary_min) as min_salary,
                MAX(j.salary_max) as max_salary
            FROM jobs j
            JOIN job_categories jc ON j.category_id = jc.id
            WHERE j.status = 'active' 
            AND j.salary_min IS NOT NULL 
            AND j.salary_max IS NOT NULL
            AND j.salary_min > 0
            AND jc.slug = ?
            GROUP BY jc.name, j.state
            ORDER BY avg_salary DESC
        ");
        $stmt->execute([$category]);
    }
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $results,
        'category_filter' => $category
    ]);
}

function getSalaryComparison($location, $category) {
    global $pdo;
    
    // Base query for comparison
    $baseWhere = "j.status = 'active' AND j.salary_min IS NOT NULL AND j.salary_max IS NOT NULL AND j.salary_min > 0";
    $params = [];
    
    // National average
    $nationalStmt = $pdo->prepare("
        SELECT 
            'Nigeria' as location,
            'All Categories' as category,
            COUNT(*) as job_count,
            AVG((salary_min + salary_max) / 2) as avg_salary
        FROM jobs 
        WHERE $baseWhere
    ");
    $nationalStmt->execute();
    $national = $nationalStmt->fetch(PDO::FETCH_ASSOC);
    
    $comparisons = [$national];
    
    // Location comparison
    if (!empty($location)) {
        $locationStmt = $pdo->prepare("
            SELECT 
                ? as location,
                'All Categories' as category,
                COUNT(*) as job_count,
                AVG((salary_min + salary_max) / 2) as avg_salary
            FROM jobs 
            WHERE $baseWhere AND (state LIKE ? OR city LIKE ?)
        ");
        $locationParam = '%' . $location . '%';
        $locationStmt->execute([$location, $locationParam, $locationParam]);
        $locationData = $locationStmt->fetch(PDO::FETCH_ASSOC);
        if ($locationData && $locationData['job_count'] > 0) {
            $comparisons[] = $locationData;
        }
    }
    
    // Category comparison
    if (!empty($category)) {
        $categoryStmt = $pdo->prepare("
            SELECT 
                'Nigeria' as location,
                jc.name as category,
                COUNT(*) as job_count,
                AVG((j.salary_min + j.salary_max) / 2) as avg_salary
            FROM jobs j
            JOIN job_categories jc ON j.category_id = jc.id
            WHERE $baseWhere AND jc.slug = ?
        ");
        $categoryStmt->execute([$category]);
        $categoryData = $categoryStmt->fetch(PDO::FETCH_ASSOC);
        if ($categoryData && $categoryData['job_count'] > 0) {
            $comparisons[] = $categoryData;
        }
    }
    
    // Both location and category
    if (!empty($location) && !empty($category)) {
        $bothStmt = $pdo->prepare("
            SELECT 
                ? as location,
                jc.name as category,
                COUNT(*) as job_count,
                AVG((j.salary_min + j.salary_max) / 2) as avg_salary
            FROM jobs j
            JOIN job_categories jc ON j.category_id = jc.id
            WHERE $baseWhere 
            AND (j.state LIKE ? OR j.city LIKE ?)
            AND jc.slug = ?
        ");
        $locationParam = '%' . $location . '%';
        $bothStmt->execute([$location, $locationParam, $locationParam, $category]);
        $bothData = $bothStmt->fetch(PDO::FETCH_ASSOC);
        if ($bothData && $bothData['job_count'] > 0) {
            $comparisons[] = $bothData;
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $comparisons,
        'filters' => [
            'location' => $location,
            'category' => $category
        ]
    ]);
}

function getSalaryTrends() {
    global $pdo;
    
    // Monthly salary trends (last 12 months)
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as job_count,
            AVG((salary_min + salary_max) / 2) as avg_salary,
            MIN(salary_min) as min_salary,
            MAX(salary_max) as max_salary
        FROM jobs 
        WHERE status = 'active' 
        AND salary_min IS NOT NULL 
        AND salary_max IS NOT NULL
        AND salary_min > 0
        AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $stmt->execute();
    $trends = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $trends
    ]);
}

// Helper function to format currency
function formatNaira($amount) {
    if ($amount >= 1000000) {
        return '₦' . number_format($amount / 1000000, 1) . 'M';
    } elseif ($amount >= 1000) {
        return '₦' . number_format($amount / 1000, 0) . 'K';
    } else {
        return '₦' . number_format($amount, 0);
    }
}
?>