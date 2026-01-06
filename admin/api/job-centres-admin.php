<?php
/**
 * Admin Job Centres API
 * Handles CRUD operations and bulk upload for job centres
 */

require_once '../../config/database.php';
require_once '../../config/session.php';

header('Content-Type: application/json');

// Check if user is admin
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = getCurrentUserId();
$stmt = $pdo->prepare("SELECT user_type FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user || $user['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'add':
            addJobCentre();
            break;
            
        case 'edit':
            editJobCentre();
            break;
            
        case 'get':
            getJobCentre();
            break;
            
        case 'bulk_upload':
            bulkUploadJobCentres();
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    error_log("Job Centres Admin API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function addJobCentre() {
    global $pdo;
    
    // Validate required fields
    $required = ['name', 'category', 'state', 'city'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    // Parse services
    $services = [];
    if (!empty($_POST['services'])) {
        $services_input = $_POST['services'];
        if (is_string($services_input)) {
            // Split by comma and clean up
            $services = array_map('trim', explode(',', $services_input));
            $services = array_filter($services); // Remove empty values
        }
    }
    $services_json = json_encode($services);
    
    // Insert job centre
    $stmt = $pdo->prepare("
        INSERT INTO job_centres (
            name, category, description, address, state, city,
            contact_number, email, website, services, operating_hours,
            is_verified, is_government, is_active
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $_POST['name'],
        $_POST['category'],
        $_POST['description'] ?? null,
        $_POST['address'] ?? null,
        $_POST['state'],
        $_POST['city'],
        $_POST['contact_number'] ?? null,
        $_POST['email'] ?? null,
        $_POST['website'] ?? null,
        $services_json,
        $_POST['operating_hours'] ?? null,
        isset($_POST['is_verified']) ? 1 : 0,
        isset($_POST['is_government']) ? 1 : 0,
        isset($_POST['is_active']) ? 1 : 0
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Job centre added successfully',
        'id' => $pdo->lastInsertId()
    ]);
}

function editJobCentre() {
    global $pdo;
    
    // Validate required fields
    if (empty($_POST['id'])) {
        throw new Exception('Job centre ID is required');
    }
    
    $required = ['name', 'category', 'state', 'city'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    // Parse services
    $services = [];
    if (!empty($_POST['services'])) {
        $services_input = $_POST['services'];
        if (is_string($services_input)) {
            $services = array_map('trim', explode(',', $services_input));
            $services = array_filter($services);
        }
    }
    $services_json = json_encode($services);
    
    // Update job centre
    $stmt = $pdo->prepare("
        UPDATE job_centres SET
            name = ?,
            category = ?,
            description = ?,
            address = ?,
            state = ?,
            city = ?,
            contact_number = ?,
            email = ?,
            website = ?,
            services = ?,
            operating_hours = ?,
            is_verified = ?,
            is_government = ?,
            is_active = ?
        WHERE id = ?
    ");
    
    $stmt->execute([
        $_POST['name'],
        $_POST['category'],
        $_POST['description'] ?? null,
        $_POST['address'] ?? null,
        $_POST['state'],
        $_POST['city'],
        $_POST['contact_number'] ?? null,
        $_POST['email'] ?? null,
        $_POST['website'] ?? null,
        $services_json,
        $_POST['operating_hours'] ?? null,
        isset($_POST['is_verified']) ? 1 : 0,
        isset($_POST['is_government']) ? 1 : 0,
        isset($_POST['is_active']) ? 1 : 0,
        $_POST['id']
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Job centre updated successfully'
    ]);
}

function getJobCentre() {
    global $pdo;
    
    if (empty($_GET['id'])) {
        throw new Exception('Job centre ID is required');
    }
    
    $stmt = $pdo->prepare("SELECT * FROM job_centres WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $centre = $stmt->fetch();
    
    if (!$centre) {
        throw new Exception('Job centre not found');
    }
    
    echo json_encode([
        'success' => true,
        'centre' => $centre
    ]);
}

function bulkUploadJobCentres() {
    global $pdo;
    
    // Check if file was uploaded
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error occurred');
    }
    
    // Validate file type
    $file_ext = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));
    if ($file_ext !== 'csv') {
        throw new Exception('Only CSV files are allowed');
    }
    
    // Validate file size (max 5MB)
    if ($_FILES['csv_file']['size'] > 5 * 1024 * 1024) {
        throw new Exception('File size exceeds 5MB limit');
    }
    
    $skip_duplicates = isset($_POST['skip_duplicates']);
    
    // Read CSV file
    $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
    if (!$file) {
        throw new Exception('Failed to open CSV file');
    }
    
    // Get header row
    $headers = fgetcsv($file);
    if (!$headers) {
        throw new Exception('CSV file is empty or invalid');
    }
    
    // Validate required columns
    $required_columns = ['name', 'category', 'state', 'city'];
    $missing_columns = array_diff($required_columns, $headers);
    if (!empty($missing_columns)) {
        throw new Exception('Missing required columns: ' . implode(', ', $missing_columns));
    }
    
    // Map column names to indices
    $column_map = array_flip($headers);
    
    $imported = 0;
    $skipped = 0;
    $errors = 0;
    $error_details = [];
    
    $pdo->beginTransaction();
    
    try {
        while (($row = fgetcsv($file)) !== false) {
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }
            
            // Validate row has enough columns
            if (count($row) < count($headers)) {
                $errors++;
                $error_details[] = "Row has insufficient columns";
                continue;
            }
            
            // Extract data
            $name = $row[$column_map['name']] ?? '';
            $category = $row[$column_map['category']] ?? 'offline';
            $description = $row[$column_map['description']] ?? null;
            $address = $row[$column_map['address']] ?? null;
            $state = $row[$column_map['state']] ?? '';
            $city = $row[$column_map['city']] ?? '';
            $contact_number = $row[$column_map['contact_number']] ?? null;
            $email = $row[$column_map['email']] ?? null;
            $website = $row[$column_map['website']] ?? null;
            $services = $row[$column_map['services']] ?? '';
            $operating_hours = $row[$column_map['operating_hours']] ?? null;
            $is_verified = isset($column_map['is_verified']) ? ($row[$column_map['is_verified']] == '1' ? 1 : 0) : 0;
            $is_government = isset($column_map['is_government']) ? ($row[$column_map['is_government']] == '1' ? 1 : 0) : 0;
            $is_active = isset($column_map['is_active']) ? ($row[$column_map['is_active']] == '1' ? 1 : 0) : 1;
            
            // Validate required fields
            if (empty($name) || empty($state) || empty($city)) {
                $errors++;
                $error_details[] = "Missing required fields for row: $name";
                continue;
            }
            
            // Validate category
            if (!in_array($category, ['online', 'offline', 'both'])) {
                $category = 'offline'; // Default to offline
            }
            
            // Check for duplicates
            if ($skip_duplicates) {
                $check_stmt = $pdo->prepare("
                    SELECT id FROM job_centres 
                    WHERE name = ? AND state = ?
                ");
                $check_stmt->execute([$name, $state]);
                
                if ($check_stmt->fetch()) {
                    $skipped++;
                    continue;
                }
            }
            
            // Parse services
            $services_array = [];
            if (!empty($services)) {
                $services_array = array_map('trim', explode(',', $services));
                $services_array = array_filter($services_array);
            }
            $services_json = json_encode($services_array);
            
            // Insert job centre
            $insert_stmt = $pdo->prepare("
                INSERT INTO job_centres (
                    name, category, description, address, state, city,
                    contact_number, email, website, services, operating_hours,
                    is_verified, is_government, is_active
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            try {
                $insert_stmt->execute([
                    $name,
                    $category,
                    $description,
                    $address,
                    $state,
                    $city,
                    $contact_number,
                    $email,
                    $website,
                    $services_json,
                    $operating_hours,
                    $is_verified,
                    $is_government,
                    $is_active
                ]);
                
                $imported++;
            } catch (PDOException $e) {
                $errors++;
                $error_details[] = "Error inserting $name: " . $e->getMessage();
            }
        }
        
        $pdo->commit();
        
        fclose($file);
        
        echo json_encode([
            'success' => true,
            'message' => "Bulk upload completed",
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
            'error_details' => $error_details
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        fclose($file);
        throw $e;
    }
}
?>
