<?php
require_once '../config/database.php';
require_once '../config/session.php';

// Check admin authentication
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = getCurrentUserId();
$stmt = $pdo->prepare("SELECT user_type FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user || $user['user_type'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Get admin info
$stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$admin = $stmt->fetch();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'create_admin':
                $first_name = trim($_POST['first_name']);
                $last_name = trim($_POST['last_name']);
                $email = trim($_POST['email']);
                $password = $_POST['password'];
                $role = $_POST['role']; // Can use this for future role system
                
                // Check if email exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    echo json_encode(['success' => false, 'message' => 'Email already exists']);
                    exit;
                }
                
                // Create admin user
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO users (user_type, email, password_hash, first_name, last_name, email_verified, is_active)
                    VALUES ('admin', ?, ?, ?, ?, 1, 1)
                ");
                $stmt->execute([$email, $password_hash, $first_name, $last_name]);
                
                echo json_encode(['success' => true, 'message' => 'Admin user created successfully']);
                exit;
                
            case 'update_admin':
                $admin_id = (int)$_POST['admin_id'];
                $first_name = trim($_POST['first_name']);
                $last_name = trim($_POST['last_name']);
                $email = trim($_POST['email']);
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET first_name = ?, last_name = ?, email = ?, is_active = ?
                    WHERE id = ? AND user_type = 'admin'
                ");
                $stmt->execute([$first_name, $last_name, $email, $is_active, $admin_id]);
                
                echo json_encode(['success' => true, 'message' => 'Admin user updated successfully']);
                exit;
                
            case 'delete_admin':
                $admin_id = (int)$_POST['admin_id'];
                
                // Prevent self-deletion
                if ($admin_id == $user_id) {
                    echo json_encode(['success' => false, 'message' => 'You cannot delete your own account']);
                    exit;
                }
                
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND user_type = 'admin'");
                $stmt->execute([$admin_id]);
                
                echo json_encode(['success' => true, 'message' => 'Admin user deleted successfully']);
                exit;
                
            case 'toggle_status':
                $admin_id = (int)$_POST['admin_id'];
                
                // Prevent self-deactivation
                if ($admin_id == $user_id) {
                    echo json_encode(['success' => false, 'message' => 'You cannot deactivate your own account']);
                    exit;
                }
                
                $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ? AND user_type = 'admin'");
                $stmt->execute([$admin_id]);
                
                echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
                exit;
        }
    } catch (PDOException $e) {
        error_log("Admin users error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
        exit;
    }
}

// Get all admin users
$stmt = $pdo->query("
    SELECT id, first_name, last_name, email, is_active, email_verified, created_at
    FROM users 
    WHERE user_type = 'admin'
    ORDER BY created_at DESC
");
$admins = $stmt->fetchAll();

$pageTitle = 'Admin Users Manager';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - FindAJob Admin</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
        }
        
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .admin-sidebar {
            width: 260px;
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 24px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h1 {
            font-size: 20px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 4px;
        }
        
        .sidebar-header p {
            font-size: 13px;
            color: rgba(255,255,255,0.6);
        }
        
        .sidebar-nav {
            padding: 20px 0;
        }
        
        .nav-section {
            margin-bottom: 24px;
        }
        
        .nav-section-title {
            padding: 0 20px 8px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: rgba(255,255,255,0.5);
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.2s;
            position: relative;
        }
        
        .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .nav-link.active {
            background: rgba(220, 38, 38, 0.2);
            color: white;
            border-left: 3px solid #dc2626;
        }
        
        .nav-link i {
            width: 20px;
            margin-right: 12px;
            font-size: 16px;
        }
        
        .admin-main {
            margin-left: 260px;
            flex: 1;
            padding: 24px;
            width: calc(100% - 260px);
        }
        
        .page-header {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-header h2 {
            font-size: 24px;
            color: #1a1a2e;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #dc2626, #991b1b);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.4);
        }
        
        .content-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #f9fafb;
            border-bottom: 2px solid #e5e7eb;
        }
        
        th {
            padding: 16px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        td {
            padding: 16px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 14px;
            color: #1a1a2e;
        }
        
        tbody tr:hover {
            background: #f9fafb;
        }
        
        .badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .action-btns {
            display: flex;
            gap: 8px;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }
        
        .btn-info {
            background: #3b82f6;
            color: white;
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .btn-warning {
            background: #f59e0b;
            color: white;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            font-size: 18px;
            color: #1a1a2e;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #6b7280;
            cursor: pointer;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
        }
        
        .modal-close:hover {
            background: #f3f4f6;
        }
        
        .modal-body {
            padding: 24px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #dc2626;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="admin-main">
            <div class="page-header">
                <h2><i class="fas fa-user-shield"></i> Admin Users Manager</h2>
                <button class="btn btn-primary" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i> Add Admin User
                </button>
            </div>
            
            <div class="content-card">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Email Verified</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins as $a): ?>
                                <tr>
                                    <td>#<?= $a['id'] ?></td>
                                    <td><?= htmlspecialchars($a['first_name'] . ' ' . $a['last_name']) ?></td>
                                    <td><?= htmlspecialchars($a['email']) ?></td>
                                    <td>
                                        <?php if ($a['is_active']): ?>
                                            <span class="badge badge-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($a['email_verified']): ?>
                                            <i class="fas fa-check-circle" style="color: #10b981;"></i>
                                        <?php else: ?>
                                            <i class="fas fa-times-circle" style="color: #ef4444;"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($a['created_at'])) ?></td>
                                    <td>
                                        <div class="action-btns">
                                            <button class="btn btn-sm btn-info" onclick="editAdmin(<?= htmlspecialchars(json_encode($a)) ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-warning" onclick="toggleStatus(<?= $a['id'] ?>)">
                                                <i class="fas fa-power-off"></i>
                                            </button>
                                            <?php if ($a['id'] != $user_id): ?>
                                                <button class="btn btn-sm btn-danger" onclick="deleteAdmin(<?= $a['id'] ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Create/Edit Modal -->
    <div id="adminModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add Admin User</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="alertContainer"></div>
                <form id="adminForm">
                    <input type="hidden" id="adminId" name="admin_id">
                    <input type="hidden" id="formAction" name="action">
                    
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="first_name" id="firstName" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="last_name" id="lastName" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" id="email" class="form-control" required>
                    </div>
                    
                    <div class="form-group" id="passwordGroup">
                        <label>Password</label>
                        <input type="password" name="password" id="password" class="form-control">
                    </div>
                    
                    <div class="form-group" id="statusGroup" style="display: none;">
                        <label>
                            <input type="checkbox" name="is_active" id="isActive">
                            Active
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-save"></i> Save Admin User
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Add Admin User';
            document.getElementById('adminForm').reset();
            document.getElementById('formAction').value = 'create_admin';
            document.getElementById('adminId').value = '';
            document.getElementById('passwordGroup').style.display = 'block';
            document.getElementById('password').required = true;
            document.getElementById('statusGroup').style.display = 'none';
            document.getElementById('alertContainer').innerHTML = '';
            document.getElementById('adminModal').classList.add('active');
        }
        
        function editAdmin(admin) {
            document.getElementById('modalTitle').textContent = 'Edit Admin User';
            document.getElementById('formAction').value = 'update_admin';
            document.getElementById('adminId').value = admin.id;
            document.getElementById('firstName').value = admin.first_name;
            document.getElementById('lastName').value = admin.last_name;
            document.getElementById('email').value = admin.email;
            document.getElementById('isActive').checked = admin.is_active == 1;
            document.getElementById('passwordGroup').style.display = 'none';
            document.getElementById('password').required = false;
            document.getElementById('statusGroup').style.display = 'block';
            document.getElementById('alertContainer').innerHTML = '';
            document.getElementById('adminModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('adminModal').classList.remove('active');
        }
        
        function toggleStatus(adminId) {
            if (!confirm('Are you sure you want to toggle this admin status?')) return;
            
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=toggle_status&admin_id=${adminId}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message);
                }
            });
        }
        
        function deleteAdmin(adminId) {
            if (!confirm('Are you sure you want to delete this admin user? This action cannot be undone.')) return;
            
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=delete_admin&admin_id=${adminId}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message);
                }
            });
        }
        
        document.getElementById('adminForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('', {
                method: 'POST',
                body: new URLSearchParams(formData)
            })
            .then(r => r.json())
            .then(data => {
                const alertContainer = document.getElementById('alertContainer');
                if (data.success) {
                    alertContainer.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                    setTimeout(() => location.reload(), 1500);
                } else {
                    alertContainer.innerHTML = `<div class="alert alert-error">${data.message}</div>`;
                }
            });
        });
        
        // Close modal on outside click
        document.getElementById('adminModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>
</body>
</html>
