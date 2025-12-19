<?php
/**
 * Admin Actions API
 * Handles administrative operations including role management, user actions, etc.
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/permissions.php';

header('Content-Type: application/json');

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$user_id = getCurrentUserId();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        // Role Management
        case 'get_role_permissions':
            $role_id = (int)($_GET['role_id'] ?? 0);
            
            $stmt = $pdo->prepare("
                SELECT permission_id 
                FROM admin_role_permissions 
                WHERE role_id = ?
            ");
            $stmt->execute([$role_id]);
            $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo json_encode([
                'success' => true,
                'permissions' => $permissions
            ]);
            break;

        case 'create_role':
            if (!isSuperAdmin($user_id)) {
                echo json_encode(['success' => false, 'message' => 'Only Super Admin can manage roles']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            
            $role_name = trim($data['role_name']);
            $role_slug = strtolower(str_replace(' ', '_', trim($data['role_slug'])));
            $description = trim($data['description'] ?? '');
            $permissions = $data['permissions'] ?? [];
            
            // Check if slug exists
            $stmt = $pdo->prepare("SELECT id FROM admin_roles WHERE role_slug = ?");
            $stmt->execute([$role_slug]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Role slug already exists']);
                exit;
            }
            
            // Create role
            $stmt = $pdo->prepare("
                INSERT INTO admin_roles (role_name, role_slug, description, is_active)
                VALUES (?, ?, ?, 1)
            ");
            $stmt->execute([$role_name, $role_slug, $description]);
            $role_id = $pdo->lastInsertId();
            
            // Assign permissions
            if (!empty($permissions)) {
                $stmt = $pdo->prepare("
                    INSERT INTO admin_role_permissions (role_id, permission_id)
                    VALUES (?, ?)
                ");
                foreach ($permissions as $permission_id) {
                    $stmt->execute([$role_id, $permission_id]);
                }
            }
            
            echo json_encode(['success' => true, 'message' => 'Role created successfully', 'role_id' => $role_id]);
            break;

        case 'update_role':
            if (!isSuperAdmin($user_id)) {
                echo json_encode(['success' => false, 'message' => 'Only Super Admin can manage roles']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            
            $role_id = (int)$data['role_id'];
            $role_name = trim($data['role_name']);
            $description = trim($data['description'] ?? '');
            $permissions = $data['permissions'] ?? [];
            
            // Prevent editing super_admin
            $stmt = $pdo->prepare("SELECT role_slug FROM admin_roles WHERE id = ?");
            $stmt->execute([$role_id]);
            $role = $stmt->fetch();
            
            if ($role && $role['role_slug'] === 'super_admin') {
                echo json_encode(['success' => false, 'message' => 'Cannot edit Super Admin role']);
                exit;
            }
            
            // Update role
            $stmt = $pdo->prepare("
                UPDATE admin_roles 
                SET role_name = ?, description = ?
                WHERE id = ?
            ");
            $stmt->execute([$role_name, $description, $role_id]);
            
            // Delete existing permissions
            $stmt = $pdo->prepare("DELETE FROM admin_role_permissions WHERE role_id = ?");
            $stmt->execute([$role_id]);
            
            // Assign new permissions
            if (!empty($permissions)) {
                $stmt = $pdo->prepare("
                    INSERT INTO admin_role_permissions (role_id, permission_id)
                    VALUES (?, ?)
                ");
                foreach ($permissions as $permission_id) {
                    $stmt->execute([$role_id, $permission_id]);
                }
            }
            
            echo json_encode(['success' => true, 'message' => 'Role updated successfully']);
            break;

        case 'delete_role':
            if (!isSuperAdmin($user_id)) {
                echo json_encode(['success' => false, 'message' => 'Only Super Admin can manage roles']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $role_id = (int)$data['role_id'];
            
            // Prevent deleting super_admin
            $stmt = $pdo->prepare("SELECT role_slug FROM admin_roles WHERE id = ?");
            $stmt->execute([$role_id]);
            $role = $stmt->fetch();
            
            if ($role && $role['role_slug'] === 'super_admin') {
                echo json_encode(['success' => false, 'message' => 'Cannot delete Super Admin role']);
                exit;
            }
            
            // Check if role is assigned
            $stmt = $pdo->prepare("SELECT COUNT(*) as user_count FROM users WHERE admin_role_id = ?");
            $stmt->execute([$role_id]);
            $result = $stmt->fetch();
            
            if ($result['user_count'] > 0) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete role assigned to ' . $result['user_count'] . ' user(s)']);
                exit;
            }
            
            $stmt = $pdo->prepare("DELETE FROM admin_roles WHERE id = ?");
            $stmt->execute([$role_id]);
            
            echo json_encode(['success' => true, 'message' => 'Role deleted successfully']);
            break;

        // CV Management
        case 'delete_cv':
            if (!hasPermission($user_id, 'delete_cvs')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $cv_id = (int)$data['cv_id'];
            
            // Get CV file path
            $stmt = $pdo->prepare("SELECT file_path FROM cvs WHERE id = ?");
            $stmt->execute([$cv_id]);
            $cv = $stmt->fetch();
            
            if ($cv) {
                // Delete file
                $file_path = '../' . $cv['file_path'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                
                // Delete database record
                $stmt = $pdo->prepare("DELETE FROM cvs WHERE id = ?");
                $stmt->execute([$cv_id]);
                
                echo json_encode(['success' => true, 'message' => 'CV deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'CV not found']);
            }
            break;

        // Job Management
        case 'close_job':
            if (!hasPermission($user_id, 'edit_jobs')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $job_id = (int)$data['job_id'];
            
            $stmt = $pdo->prepare("UPDATE jobs SET STATUS = 'closed' WHERE id = ?");
            $stmt->execute([$job_id]);
            
            echo json_encode(['success' => true, 'message' => 'Job closed successfully']);
            break;

        case 'activate_job':
            if (!hasPermission($user_id, 'edit_jobs')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $job_id = (int)$data['job_id'];
            
            $stmt = $pdo->prepare("UPDATE jobs SET STATUS = 'active' WHERE id = ?");
            $stmt->execute([$job_id]);
            
            echo json_encode(['success' => true, 'message' => 'Job activated successfully']);
            break;

        // User Management
        case 'suspend_user':
            if (!hasAnyPermission($user_id, ['edit_job_seekers', 'edit_employers'])) {
                echo json_encode(['success' => false, 'message' => 'Permission denied']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $target_user_id = (int)$data['user_id'];
            
            $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$target_user_id]);
            
            echo json_encode(['success' => true, 'message' => 'User status updated']);
            break;

        case 'verify_user':
            if (!hasPermission($user_id, 'manage_roles')) {
                echo json_encode(['success' => false, 'message' => 'Permission denied']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $target_user_id = (int)$data['user_id'];
            $verification_type = $data['type']; // email, phone, nin
            
            $column = $verification_type . '_verified';
            $stmt = $pdo->prepare("UPDATE users SET $column = 1 WHERE id = ?");
            $stmt->execute([$target_user_id]);
            
            echo json_encode(['success' => true, 'message' => ucfirst($verification_type) . ' verified successfully']);
            break;

        // Statistics
        case 'get_dashboard_stats':
            // Any admin can view dashboard
            $stats = [];
            
            // User stats
            $stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            $stats['total_job_seekers'] = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'job_seeker'")->fetchColumn();
            $stats['total_employers'] = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'employer'")->fetchColumn();
            
            // Job stats
            $stats['active_jobs'] = $pdo->query("SELECT COUNT(*) FROM jobs WHERE STATUS = 'active'")->fetchColumn();
            $stats['total_applications'] = $pdo->query("SELECT COUNT(*) FROM job_applications")->fetchColumn();
            
            // Revenue (if has permission)
            if (hasPermission($user_id, 'view_revenue')) {
                $stats['total_revenue'] = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE status = 'completed'")->fetchColumn();
            }
            
            echo json_encode(['success' => true, 'stats' => $stats]);
            break;

        case 'get_report':
            // Get detailed report information
            $report_id = (int)($_GET['report_id'] ?? 0);
            
            $stmt = $pdo->prepare("
                SELECT 
                    r.*,
                    CONCAT(u.first_name, ' ', u.last_name) as reporter_name,
                    u.email as reporter_email,
                    CONCAT(admin.first_name, ' ', admin.last_name) as reviewer_name,
                    CASE 
                        WHEN r.reported_entity_type = 'job' THEN (SELECT title FROM jobs WHERE id = r.reported_entity_id)
                        WHEN r.reported_entity_type = 'user' OR r.reported_entity_type = 'company' THEN (SELECT CONCAT(first_name, ' ', last_name) FROM users WHERE id = r.reported_entity_id)
                        ELSE NULL
                    END as entity_name
                FROM reports r
                LEFT JOIN users u ON r.reporter_id = u.id
                LEFT JOIN users admin ON r.reviewed_by = admin.id
                WHERE r.id = ?
            ");
            $stmt->execute([$report_id]);
            $report = $stmt->fetch();
            
            if ($report) {
                echo json_encode(['success' => true, 'report' => $report]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Report not found']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }

} catch (Exception $e) {
    error_log("Admin API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => defined('DEV_MODE') && DEV_MODE ? $e->getMessage() : 'An error occurred']);
}
