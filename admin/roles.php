<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/permissions.php';

// Check admin authentication
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = getCurrentUserId();

// Check if user is Super Admin
if (!isSuperAdmin($user_id)) {
    header('Location: dashboard.php');
    exit;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'create_role':
                $role_name = trim($_POST['role_name']);
                $role_slug = strtolower(str_replace(' ', '_', trim($_POST['role_slug'])));
                $description = trim($_POST['description']);
                $permissions = $_POST['permissions'] ?? [];
                
                // Create role
                $stmt = $pdo->prepare("
                    INSERT INTO admin_roles (role_name, role_slug, description, is_active)
                    VALUES (?, ?, ?, 1)
                ");
                $stmt->execute([$role_name, $role_slug, $description]);
                $role_id = $pdo->lastInsertId();
                
                // Assign permissions
                foreach ($permissions as $permission_id) {
                    $stmt = $pdo->prepare("
                        INSERT INTO admin_role_permissions (role_id, permission_id)
                        VALUES (?, ?)
                    ");
                    $stmt->execute([$role_id, $permission_id]);
                }
                
                echo json_encode(['success' => true, 'message' => 'Role created successfully']);
                exit;
                
            case 'update_role':
                $role_id = (int)$_POST['role_id'];
                $role_name = trim($_POST['role_name']);
                $description = trim($_POST['description']);
                $permissions = $_POST['permissions'] ?? [];
                
                // Prevent editing super_admin role
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
                foreach ($permissions as $permission_id) {
                    $stmt = $pdo->prepare("
                        INSERT INTO admin_role_permissions (role_id, permission_id)
                        VALUES (?, ?)
                    ");
                    $stmt->execute([$role_id, $permission_id]);
                }
                
                echo json_encode(['success' => true, 'message' => 'Role updated successfully']);
                exit;
                
            case 'delete_role':
                $role_id = (int)$_POST['role_id'];
                
                // Prevent deleting super_admin role
                $stmt = $pdo->prepare("SELECT role_slug FROM admin_roles WHERE id = ?");
                $stmt->execute([$role_id]);
                $role = $stmt->fetch();
                
                if ($role && $role['role_slug'] === 'super_admin') {
                    echo json_encode(['success' => false, 'message' => 'Cannot delete Super Admin role']);
                    exit;
                }
                
                // Check if role is assigned to users
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
                exit;
                
            case 'toggle_role_status':
                $role_id = (int)$_POST['role_id'];
                
                $stmt = $pdo->prepare("UPDATE admin_roles SET is_active = NOT is_active WHERE id = ?");
                $stmt->execute([$role_id]);
                
                echo json_encode(['success' => true, 'message' => 'Role status updated']);
                exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Get all roles with permission counts
$roles = $pdo->query("
    SELECT 
        ar.*,
        COUNT(DISTINCT arp.permission_id) as permission_count,
        COUNT(DISTINCT u.id) as user_count
    FROM admin_roles ar
    LEFT JOIN admin_role_permissions arp ON ar.id = arp.role_id
    LEFT JOIN users u ON ar.id = u.admin_role_id
    GROUP BY ar.id
    ORDER BY 
        CASE ar.role_slug 
            WHEN 'super_admin' THEN 1 
            ELSE 2 
        END,
        ar.role_name
")->fetchAll();

// Get all permissions grouped by module
$permissions_grouped = getAllPermissionsGrouped();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Roles & Permissions - FindAJob Admin</title>
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

        /* Sidebar Styles */
        .admin-sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 260px;
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            overflow-y: auto;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-header h2 {
            font-size: 24px;
            font-weight: 700;
            color: #fff;
        }

        .sidebar-nav {
            padding: 20px 0;
        }

        .nav-section {
            margin-bottom: 25px;
        }

        .nav-section-title {
            padding: 0 20px 10px;
            font-size: 11px;
            text-transform: uppercase;
            color: rgba(255,255,255,0.5);
            font-weight: 600;
            letter-spacing: 1px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }

        .nav-link:hover {
            background: rgba(255,255,255,0.05);
            color: white;
        }

        .nav-link.active {
            background: rgba(220,38,38,0.2);
            border-left-color: #dc2626;
            color: white;
        }

        .nav-link i {
            width: 20px;
            margin-right: 12px;
            font-size: 16px;
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            padding: 30px;
            min-height: 100vh;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 28px;
            color: #1a1a2e;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: #dc2626;
            color: white;
        }

        .btn-primary:hover {
            background: #b91c1c;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-warning {
            background: #f59e0b;
            color: white;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-info {
            background: #3b82f6;
            color: white;
        }

        /* Roles Grid */
        .roles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .role-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            position: relative;
        }

        .role-card.super-admin {
            border: 2px solid #dc2626;
            background: linear-gradient(135deg, #fff 0%, #fee2e2 100%);
        }

        .role-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }

        .role-name {
            font-size: 20px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 5px;
        }

        .role-slug {
            font-size: 12px;
            color: #6b7280;
            font-family: monospace;
            background: #f3f4f6;
            padding: 2px 8px;
            border-radius: 4px;
        }

        .role-description {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .role-stats {
            display: flex;
            gap: 20px;
            padding: 15px 0;
            border-top: 1px solid #e5e7eb;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 15px;
        }

        .role-stat {
            text-align: center;
        }

        .role-stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #dc2626;
        }

        .role-stat-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
        }

        .role-actions {
            display: flex;
            gap: 8px;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 10px;
        }

        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            max-width: 700px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .modal-header h2 {
            font-size: 24px;
            color: #1a1a2e;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #6b7280;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: #374151;
            font-weight: 500;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .permissions-section {
            margin-top: 25px;
        }

        .permission-group {
            margin-bottom: 20px;
            background: #f9fafb;
            padding: 15px;
            border-radius: 8px;
        }

        .permission-group-title {
            font-size: 14px;
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 12px;
            text-transform: capitalize;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .permission-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px;
            margin-bottom: 5px;
        }

        .permission-item label {
            margin: 0;
            font-size: 13px;
            color: #4b5563;
            cursor: pointer;
            flex: 1;
        }

        .permission-item input[type="checkbox"] {
            width: auto;
            cursor: pointer;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }

        .module-icon {
            width: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>Admin Roles & Permissions</h1>
                <p style="color: #6b7280; margin-top: 5px;">Manage admin roles and their permissions</p>
            </div>
            <button class="btn btn-primary" onclick="openCreateModal()">
                <i class="fas fa-plus"></i> Create New Role
            </button>
        </div>

        <div class="roles-grid">
            <?php foreach ($roles as $role): ?>
                <div class="role-card <?= $role['role_slug'] === 'super_admin' ? 'super-admin' : '' ?>">
                    <div class="role-header">
                        <div>
                            <div class="role-name">
                                <?= htmlspecialchars($role['role_name']) ?>
                                <?php if ($role['role_slug'] === 'super_admin'): ?>
                                    <i class="fas fa-crown" style="color: #dc2626; font-size: 18px;"></i>
                                <?php endif; ?>
                            </div>
                            <div class="role-slug"><?= htmlspecialchars($role['role_slug']) ?></div>
                        </div>
                        <span class="badge badge-<?= $role['is_active'] ? 'success' : 'danger' ?>">
                            <?= $role['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </div>

                    <div class="role-description">
                        <?= htmlspecialchars($role['description']) ?>
                    </div>

                    <div class="role-stats">
                        <div class="role-stat">
                            <div class="role-stat-value"><?= $role['permission_count'] ?></div>
                            <div class="role-stat-label">Permissions</div>
                        </div>
                        <div class="role-stat">
                            <div class="role-stat-value"><?= $role['user_count'] ?></div>
                            <div class="role-stat-label">Users</div>
                        </div>
                    </div>

                    <div class="role-actions">
                        <button class="btn btn-sm btn-info" onclick="viewRole(<?= $role['id'] ?>)">
                            <i class="fas fa-eye"></i> View
                        </button>
                        <?php if ($role['role_slug'] !== 'super_admin'): ?>
                            <button class="btn btn-sm btn-warning" onclick="editRole(<?= $role['id'] ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteRole(<?= $role['id'] ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Create/Edit Role Modal -->
    <div id="roleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Create New Role</h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            
            <form id="roleForm">
                <input type="hidden" id="roleId" name="role_id">
                <input type="hidden" id="formAction" name="action" value="create_role">
                
                <div class="form-group">
                    <label>Role Name *</label>
                    <input type="text" id="roleName" name="role_name" required placeholder="e.g., Content Manager">
                </div>

                <div class="form-group">
                    <label>Role Slug *</label>
                    <input type="text" id="roleSlug" name="role_slug" required placeholder="e.g., content_manager">
                    <small style="display: block; margin-top: 5px; color: #6b7280;">Lowercase letters, numbers, and underscores only</small>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea id="roleDescription" name="description" placeholder="Describe this role's purpose"></textarea>
                </div>

                <div class="permissions-section">
                    <label style="font-size: 16px; font-weight: 600; margin-bottom: 15px; display: block;">Permissions</label>
                    
                    <?php foreach ($permissions_grouped as $module => $perms): ?>
                        <div class="permission-group">
                            <div class="permission-group-title">
                                <span class="module-icon">
                                    <?php 
                                    $icons = [
                                        'users' => 'fa-users',
                                        'jobs' => 'fa-briefcase',
                                        'content' => 'fa-file-alt',
                                        'finance' => 'fa-dollar-sign',
                                        'analytics' => 'fa-chart-line',
                                        'system' => 'fa-cog'
                                    ];
                                    ?>
                                    <i class="fas <?= $icons[$module] ?? 'fa-circle' ?>"></i>
                                </span>
                                <?= ucfirst($module) ?>
                            </div>
                            
                            <?php foreach ($perms as $perm): ?>
                                <div class="permission-item">
                                    <input type="checkbox" 
                                           id="perm_<?= $perm['id'] ?>" 
                                           name="permissions[]" 
                                           value="<?= $perm['id'] ?>">
                                    <label for="perm_<?= $perm['id'] ?>">
                                        <?= htmlspecialchars($perm['description']) ?>
                                        <small style="display: block; color: #9ca3af; font-size: 11px;"><?= htmlspecialchars($perm['name']) ?></small>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Role
                    </button>
                    <button type="button" class="btn" style="background: #6b7280; color: white;" onclick="closeModal()">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const roles = <?= json_encode($roles) ?>;

        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Create New Role';
            document.getElementById('formAction').value = 'create_role';
            document.getElementById('roleForm').reset();
            document.getElementById('roleId').value = '';
            document.getElementById('roleSlug').disabled = false;
            document.getElementById('roleModal').classList.add('active');
        }

        function editRole(roleId) {
            const role = roles.find(r => r.id == roleId);
            if (!role) return;

            document.getElementById('modalTitle').textContent = 'Edit Role';
            document.getElementById('formAction').value = 'update_role';
            document.getElementById('roleId').value = role.id;
            document.getElementById('roleName').value = role.role_name;
            document.getElementById('roleSlug').value = role.role_slug;
            document.getElementById('roleSlug').disabled = true;
            document.getElementById('roleDescription').value = role.description || '';

            // Load existing permissions
            fetch(`../api/admin-actions.php?action=get_role_permissions&role_id=${roleId}`)
                .then(r => r.json())
                .then(data => {
                    // Uncheck all first
                    document.querySelectorAll('input[name="permissions[]"]').forEach(cb => cb.checked = false);
                    
                    // Check assigned permissions
                    if (data.success && data.permissions) {
                        data.permissions.forEach(permId => {
                            const checkbox = document.getElementById(`perm_${permId}`);
                            if (checkbox) checkbox.checked = true;
                        });
                    }
                });

            document.getElementById('roleModal').classList.add('active');
        }

        function viewRole(roleId) {
            window.location.href = `role-details.php?id=${roleId}`;
        }

        function deleteRole(roleId) {
            if (!confirm('Are you sure you want to delete this role? This action cannot be undone.')) {
                return;
            }

            fetch('../api/admin-actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete_role', role_id: roleId })
            })
            .then(r => r.json())
            .then(data => {
                alert(data.message);
                if (data.success) location.reload();
            });
        }

        function closeModal() {
            document.getElementById('roleModal').classList.remove('active');
        }

        document.getElementById('roleForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {};
            formData.forEach((value, key) => {
                if (key === 'permissions[]') {
                    if (!data.permissions) data.permissions = [];
                    data.permissions.push(value);
                } else {
                    data[key] = value;
                }
            });

            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams(formData)
            })
            .then(r => r.json())
            .then(result => {
                alert(result.message);
                if (result.success) {
                    location.reload();
                }
            });
        });

        // Auto-generate slug from name
        document.getElementById('roleName').addEventListener('input', function() {
            if (document.getElementById('formAction').value === 'create_role') {
                const slug = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');
                document.getElementById('roleSlug').value = slug;
            }
        });
    </script>
</body>
</html>
