<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleLocationRequest();
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

function handleLocationRequest() {
    global $pdo;
    
    $action = $_GET['action'] ?? 'states';
    $query = $_GET['q'] ?? '';
    
    switch ($action) {
        case 'states':
            getStates($query);
            break;
        case 'lgas':
            $stateId = $_GET['state_id'] ?? '';
            getLGAs($stateId, $query);
            break;
        case 'search':
            searchLocations($query);
            break;
        case 'popular':
            getPopularLocations();
            break;
        case 'regions':
            getRegions();
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
}

function getStates($searchQuery = '') {
    global $pdo;
    
    $sql = "SELECT id, name, code, region FROM nigeria_states";
    $params = [];
    
    if (!empty($searchQuery)) {
        $sql .= " WHERE name LIKE ? OR code LIKE ?";
        $searchParam = '%' . $searchQuery . '%';
        $params = [$searchParam, $searchParam];
    }
    
    $sql .= " ORDER BY name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $states = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $states,
        'count' => count($states)
    ]);
}

function getLGAs($stateId, $searchQuery = '') {
    global $pdo;
    
    if (empty($stateId)) {
        echo json_encode(['error' => 'State ID is required']);
        return;
    }
    
    $sql = "SELECT l.id, l.name, s.name as state_name, s.code as state_code 
            FROM nigeria_lgas l 
            JOIN nigeria_states s ON l.state_id = s.id 
            WHERE l.state_id = ?";
    $params = [$stateId];
    
    if (!empty($searchQuery)) {
        $sql .= " AND l.name LIKE ?";
        $params[] = '%' . $searchQuery . '%';
    }
    
    $sql .= " ORDER BY l.name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $lgas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $lgas,
        'count' => count($lgas)
    ]);
}

function searchLocations($query) {
    global $pdo;
    
    if (empty($query)) {
        echo json_encode(['success' => true, 'data' => [], 'count' => 0]);
        return;
    }
    
    // Search both states and LGAs
    $searchParam = '%' . $query . '%';
    
    // Get matching states
    $stateStmt = $pdo->prepare("
        SELECT 'state' as type, id, name, code as extra, region as category 
        FROM nigeria_states 
        WHERE name LIKE ? 
        ORDER BY name 
        LIMIT 10
    ");
    $stateStmt->execute([$searchParam]);
    $states = $stateStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get matching LGAs
    $lgaStmt = $pdo->prepare("
        SELECT 'lga' as type, l.id, l.name, s.name as extra, s.region as category
        FROM nigeria_lgas l 
        JOIN nigeria_states s ON l.state_id = s.id 
        WHERE l.name LIKE ? 
        ORDER BY l.name 
        LIMIT 15
    ");
    $lgaStmt->execute([$searchParam]);
    $lgas = $lgaStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $results = array_merge($states, $lgas);
    
    echo json_encode([
        'success' => true,
        'data' => $results,
        'count' => count($results),
        'breakdown' => [
            'states' => count($states),
            'lgas' => count($lgas)
        ]
    ]);
}

function getPopularLocations() {
    global $pdo;
    
    // Get locations with the most job postings
    $stmt = $pdo->prepare("
        SELECT 
            j.state,
            COUNT(*) as job_count,
            s.region,
            s.code
        FROM jobs j
        LEFT JOIN nigeria_states s ON j.state = s.name
        WHERE j.status = 'active' AND j.state IS NOT NULL AND j.state != ''
        GROUP BY j.state, s.region, s.code
        ORDER BY job_count DESC
        LIMIT 20
    ");
    $stmt->execute();
    $popular = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $popular,
        'count' => count($popular)
    ]);
}

function getRegions() {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            region,
            COUNT(*) as state_count,
            GROUP_CONCAT(name ORDER BY name) as states
        FROM nigeria_states 
        GROUP BY region
        ORDER BY 
            CASE region
                WHEN 'south_west' THEN 1
                WHEN 'south_south' THEN 2
                WHEN 'south_east' THEN 3
                WHEN 'north_central' THEN 4
                WHEN 'north_west' THEN 5
                WHEN 'north_east' THEN 6
                ELSE 7
            END
    ");
    $stmt->execute();
    $regions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format region names
    foreach ($regions as &$region) {
        $region['display_name'] = ucwords(str_replace('_', ' ', $region['region']));
        $region['states'] = explode(',', $region['states']);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $regions,
        'count' => count($regions)
    ]);
}
?>