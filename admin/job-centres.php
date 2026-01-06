<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/permissions.php';

// Check if user is admin
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

// Handle form submissions
$message = '';
$error = '';

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM job_centres WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $message = "Job centre deleted successfully!";
    } catch (PDOException $e) {
        $error = "Error deleting job centre: " . $e->getMessage();
    }
}

// Handle toggle active status
if (isset($_GET['action']) && $_GET['action'] === 'toggle_status' && isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("UPDATE job_centres SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $message = "Job centre status updated successfully!";
    } catch (PDOException $e) {
        $error = "Error updating status: " . $e->getMessage();
    }
}

// Handle toggle verified status
if (isset($_GET['action']) && $_GET['action'] === 'toggle_verified' && isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("UPDATE job_centres SET is_verified = NOT is_verified WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $message = "Verification status updated successfully!";
    } catch (PDOException $e) {
        $error = "Error updating verification: " . $e->getMessage();
    }
}

// Get all job centres with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Apply filters
$where_conditions = [];
$params = [];

if (!empty($_GET['search'])) {
    $where_conditions[] = "(name LIKE ? OR city LIKE ? OR state LIKE ?)";
    $search_term = '%' . $_GET['search'] . '%';
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if (!empty($_GET['state'])) {
    $where_conditions[] = "state = ?";
    $params[] = $_GET['state'];
}

if (isset($_GET['category']) && $_GET['category'] !== '') {
    $where_conditions[] = "category = ?";
    $params[] = $_GET['category'];
}

if (isset($_GET['is_verified']) && $_GET['is_verified'] !== '') {
    $where_conditions[] = "is_verified = ?";
    $params[] = $_GET['is_verified'];
}

if (isset($_GET['is_government']) && $_GET['is_government'] !== '') {
    $where_conditions[] = "is_government = ?";
    $params[] = $_GET['is_government'];
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Count total records
$count_sql = "SELECT COUNT(*) FROM job_centres $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $per_page);

// Get job centres
$sql = "SELECT * FROM job_centres $where_clause ORDER BY created_at DESC LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$job_centres = $stmt->fetchAll();

// Get all states for filter
$states = $pdo->query("SELECT DISTINCT name FROM nigeria_states ORDER BY name")->fetchAll();

$pageTitle = 'Manage Job Centres';
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
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; background: #f5f7fa; min-height: 100vh; }
        
        .admin-layout { display: flex; min-height: 100vh; }
        
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
        
        .nav-link .badge {
            margin-left: auto;
            background: #dc2626;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .admin-content { flex: 1; margin-left: 260px; padding: 2rem; background: #f5f7fa; }
        .page-header { background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .page-header h1 { margin: 0 0 0.5rem 0; color: #1a1a2e; font-size: 24px; }
        .page-header .breadcrumb { color: #6b7280; font-size: 14px; }
        
        .action-bar { background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; display: flex; gap: 1rem; flex-wrap: wrap; align-items: center; justify-content: space-between; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .action-buttons { display: flex; gap: 1rem; }
        
        .btn { padding: 0.625rem 1.25rem; border-radius: 6px; font-size: 14px; font-weight: 500; cursor: pointer; border: none; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; transition: all 0.2s; }
        .btn-primary { background: #dc2626; color: white; }
        .btn-primary:hover { background: #b91c1c; }
        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; }
        .btn-secondary { background: #6b7280; color: white; }
        .btn-secondary:hover { background: #4b5563; }
        .btn-sm { padding: 0.375rem 0.75rem; font-size: 13px; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; }
        .btn-warning { background: #f59e0b; color: white; }
        .btn-warning:hover { background: #d97706; }
        
        .filters { display: flex; gap: 1rem; flex-wrap: wrap; flex: 1; }
        .filter-group { display: flex; flex-direction: column; gap: 0.25rem; }
        .filter-group label { font-size: 12px; font-weight: 500; color: #6b7280; }
        .filter-group input, .filter-group select { padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; }
        
        .data-table { background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .data-table table { width: 100%; border-collapse: collapse; }
        .data-table th { background: #f9fafb; padding: 1rem; text-align: left; font-weight: 600; font-size: 13px; color: #374151; border-bottom: 1px solid #e5e7eb; }
        .data-table td { padding: 1rem; border-bottom: 1px solid #f3f4f6; font-size: 14px; color: #1f2937; }
        .data-table tr:hover { background: #f9fafb; }
        
        .badge { padding: 0.25rem 0.625rem; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .badge-secondary { background: #e5e7eb; color: #374151; }
        
        .pagination { display: flex; gap: 0.5rem; justify-content: center; margin-top: 1.5rem; }
        .pagination a, .pagination span { padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; text-decoration: none; color: #374151; }
        .pagination a:hover { background: #f3f4f6; }
        .pagination .active { background: #dc2626; color: white; border-color: #dc2626; }
        
        .alert { padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; }
        .alert-success { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
        .alert-error { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; border-radius: 8px; padding: 2rem; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .modal-header h2 { margin: 0; font-size: 20px; }
        .modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #6b7280; }
        
        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 14px; color: #374151; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.625rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; }
        .form-group textarea { min-height: 100px; resize: vertical; }
        .form-group small { display: block; margin-top: 0.25rem; font-size: 12px; color: #6b7280; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .stat-card { background: white; padding: 1.25rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .stat-card .label { font-size: 13px; color: #6b7280; margin-bottom: 0.5rem; }
        .stat-card .value { font-size: 24px; font-weight: 700; color: #1a1a2e; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="admin-content">
            <div class="page-header">
                <h1><i class="fas fa-building"></i> Manage Job Centres</h1>
                <div class="breadcrumb">Admin / Job Centres</div>
            </div>
            
            <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="label">Total Job Centres</div>
                    <div class="value"><?= number_format($total_records) ?></div>
                </div>
                <div class="stat-card">
                    <div class="label">Verified Centres</div>
                    <div class="value">
                        <?php
                        $verified = $pdo->query("SELECT COUNT(*) FROM job_centres WHERE is_verified = 1")->fetchColumn();
                        echo number_format($verified);
                        ?>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="label">Government Centres</div>
                    <div class="value">
                        <?php
                        $government = $pdo->query("SELECT COUNT(*) FROM job_centres WHERE is_government = 1")->fetchColumn();
                        echo number_format($government);
                        ?>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="label">Active Centres</div>
                    <div class="value">
                        <?php
                        $active = $pdo->query("SELECT COUNT(*) FROM job_centres WHERE is_active = 1")->fetchColumn();
                        echo number_format($active);
                        ?>
                    </div>
                </div>
            </div>
            
            <!-- Action Bar -->
            <div class="action-bar">
                <div class="action-buttons">
                    <button onclick="showAddModal()" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Job Centre
                    </button>
                    <button onclick="showBulkUploadModal()" class="btn btn-success">
                        <i class="fas fa-upload"></i> Bulk Upload
                    </button>
                    <a href="#" onclick="downloadTemplate()" class="btn btn-secondary">
                        <i class="fas fa-download"></i> Download CSV Template
                    </a>
                </div>
            </div>
            
            <!-- Filters -->
            <form method="GET" class="action-bar">
                <div class="filters">
                    <div class="filter-group">
                        <label>Search</label>
                        <input type="text" name="search" placeholder="Name, city, state..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    </div>
                    <div class="filter-group">
                        <label>State</label>
                        <select name="state">
                            <option value="">All States</option>
                            <?php foreach ($states as $state): ?>
                            <option value="<?= htmlspecialchars($state['name']) ?>" <?= (($_GET['state'] ?? '') === $state['name']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($state['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Category</label>
                        <select name="category">
                            <option value="">All Categories</option>
                            <option value="online" <?= (($_GET['category'] ?? '') === 'online') ? 'selected' : '' ?>>Online</option>
                            <option value="offline" <?= (($_GET['category'] ?? '') === 'offline') ? 'selected' : '' ?>>Offline</option>
                            <option value="both" <?= (($_GET['category'] ?? '') === 'both') ? 'selected' : '' ?>>Both</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Verified</label>
                        <select name="is_verified">
                            <option value="">All</option>
                            <option value="1" <?= (($_GET['is_verified'] ?? '') === '1') ? 'selected' : '' ?>>Verified</option>
                            <option value="0" <?= (($_GET['is_verified'] ?? '') === '0') ? 'selected' : '' ?>>Not Verified</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Type</label>
                        <select name="is_government">
                            <option value="">All</option>
                            <option value="1" <?= (($_GET['is_government'] ?? '') === '1') ? 'selected' : '' ?>>Government</option>
                            <option value="0" <?= (($_GET['is_government'] ?? '') === '0') ? 'selected' : '' ?>>Private</option>
                        </select>
                    </div>
                </div>
                <div class="action-buttons">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="job-centres.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
            </form>
            
            <!-- Data Table -->
            <div class="data-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Location</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Views</th>
                            <th>Rating</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($job_centres)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 2rem; color: #6b7280;">
                                <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 1rem; display: block;"></i>
                                No job centres found. Add your first job centre to get started.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($job_centres as $centre): ?>
                        <tr>
                            <td><?= $centre['id'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($centre['name']) ?></strong>
                                <?php if ($centre['is_verified']): ?>
                                <i class="fas fa-check-circle" style="color: #10b981;" title="Verified"></i>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($centre['city'] . ', ' . $centre['state']) ?></td>
                            <td>
                                <?php
                                $category_badges = [
                                    'online' => 'badge-info',
                                    'offline' => 'badge-secondary',
                                    'both' => 'badge-success'
                                ];
                                ?>
                                <span class="badge <?= $category_badges[$centre['category']] ?? 'badge-secondary' ?>">
                                    <?= ucfirst($centre['category']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= $centre['is_government'] ? 'badge-info' : 'badge-secondary' ?>">
                                    <?= $centre['is_government'] ? 'Government' : 'Private' ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= $centre['is_active'] ? 'badge-success' : 'badge-danger' ?>">
                                    <?= $centre['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td><?= number_format($centre['views_count']) ?></td>
                            <td>
                                <?php if ($centre['rating_count'] > 0): ?>
                                <span title="<?= $centre['rating_count'] ?> reviews">
                                    ⭐ <?= number_format($centre['rating_avg'], 1) ?>
                                </span>
                                <?php else: ?>
                                <span style="color: #9ca3af;">No ratings</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <button onclick="editCentre(<?= $centre['id'] ?>)" class="btn btn-sm btn-primary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?action=toggle_verified&id=<?= $centre['id'] ?>" class="btn btn-sm btn-warning" title="Toggle Verification" onclick="return confirm('Toggle verification status?')">
                                        <i class="fas fa-<?= $centre['is_verified'] ? 'shield-alt' : 'shield' ?>"></i>
                                    </a>
                                    <a href="?action=toggle_status&id=<?= $centre['id'] ?>" class="btn btn-sm btn-secondary" title="Toggle Status" onclick="return confirm('Toggle active status?')">
                                        <i class="fas fa-<?= $centre['is_active'] ? 'eye-slash' : 'eye' ?>"></i>
                                    </a>
                                    <a href="?action=delete&id=<?= $centre['id'] ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this job centre?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?><?= !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <a href="?page=<?= $i ?><?= !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>" 
                   class="<?= $i === $page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page + 1 ?><?= !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Add/Edit Modal -->
    <div id="addEditModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add Job Centre</h2>
                <button class="modal-close" onclick="closeModal('addEditModal')">&times;</button>
            </div>
            <form id="jobCentreForm" method="POST" action="api/job-centres-admin.php">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="centreId">
                
                <div class="form-group">
                    <label>Name <span style="color: red;">*</span></label>
                    <input type="text" name="name" id="name" required>
                </div>
                
                <div class="form-group">
                    <label>Category <span style="color: red;">*</span></label>
                    <select name="category" id="category" required>
                        <option value="offline">Offline (Physical Location)</option>
                        <option value="online">Online (Virtual Platform)</option>
                        <option value="both">Both (Online & Offline)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="description" rows="4"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" id="address" rows="2"></textarea>
                </div>
                
                <div class="form-group">
                    <label>State <span style="color: red;">*</span></label>
                    <select name="state" id="state" required>
                        <option value="">Select State</option>
                        <?php foreach ($states as $state): ?>
                        <option value="<?= htmlspecialchars($state['name']) ?>">
                            <?= htmlspecialchars($state['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>City <span style="color: red;">*</span></label>
                    <input type="text" name="city" id="city" required>
                </div>
                
                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="text" name="contact_number" id="contact_number" placeholder="08012345678">
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="email">
                </div>
                
                <div class="form-group">
                    <label>Website</label>
                    <input type="url" name="website" id="website" placeholder="https://example.com">
                </div>
                
                <div class="form-group">
                    <label>Services Offered</label>
                    <textarea name="services" id="services" rows="3" placeholder="Enter services separated by commas: Job Placement, Career Counseling, Skills Training"></textarea>
                    <small>Enter services separated by commas</small>
                </div>
                
                <div class="form-group">
                    <label>Operating Hours</label>
                    <input type="text" name="operating_hours" id="operating_hours" placeholder="Mon-Fri: 9AM-5PM">
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_verified" id="is_verified" value="1">
                        Verified Job Centre
                    </label>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_government" id="is_government" value="1">
                        Government Organization
                    </label>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_active" id="is_active" value="1" checked>
                        Active Status
                    </label>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" onclick="closeModal('addEditModal')" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Job Centre
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Bulk Upload Modal -->
    <div id="bulkUploadModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Bulk Upload Job Centres</h2>
                <button class="modal-close" onclick="closeModal('bulkUploadModal')">&times;</button>
            </div>
            <form id="bulkUploadForm" method="POST" action="api/job-centres-admin.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="bulk_upload">
                
                <div class="alert alert-info" style="background: #dbeafe; color: #1e40af; border-left: 4px solid #3b82f6; margin-bottom: 1.5rem;">
                    <strong>Instructions:</strong><br>
                    1. Download the CSV template below<br>
                    2. Fill in your job centre data<br>
                    3. Upload the completed CSV file<br>
                    4. Review and confirm the import
                </div>
                
                <div class="form-group">
                    <label>CSV File <span style="color: red;">*</span></label>
                    <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
                    <small>Maximum file size: 5MB. Must be in CSV format.</small>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="skip_duplicates" value="1" checked>
                        Skip duplicate entries (based on name and state)
                    </label>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" onclick="closeModal('bulkUploadModal')" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-upload"></i> Upload CSV
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Job Centre';
            document.getElementById('formAction').value = 'add';
            document.getElementById('jobCentreForm').reset();
            document.getElementById('is_active').checked = true;
            document.getElementById('addEditModal').classList.add('active');
        }
        
        function showBulkUploadModal() {
            document.getElementById('bulkUploadModal').classList.add('active');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        function editCentre(id) {
            // Fetch centre data via AJAX
            fetch(`api/job-centres-admin.php?action=get&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const centre = data.centre;
                        document.getElementById('modalTitle').textContent = 'Edit Job Centre';
                        document.getElementById('formAction').value = 'edit';
                        document.getElementById('centreId').value = centre.id;
                        document.getElementById('name').value = centre.name;
                        document.getElementById('category').value = centre.category;
                        document.getElementById('description').value = centre.description || '';
                        document.getElementById('address').value = centre.address || '';
                        document.getElementById('state').value = centre.state || '';
                        document.getElementById('city').value = centre.city || '';
                        document.getElementById('contact_number').value = centre.contact_number || '';
                        document.getElementById('email').value = centre.email || '';
                        document.getElementById('website').value = centre.website || '';
                        
                        // Parse services JSON
                        try {
                            const services = JSON.parse(centre.services || '[]');
                            document.getElementById('services').value = services.join(', ');
                        } catch (e) {
                            document.getElementById('services').value = centre.services || '';
                        }
                        
                        document.getElementById('operating_hours').value = centre.operating_hours || '';
                        document.getElementById('is_verified').checked = centre.is_verified == 1;
                        document.getElementById('is_government').checked = centre.is_government == 1;
                        document.getElementById('is_active').checked = centre.is_active == 1;
                        
                        document.getElementById('addEditModal').classList.add('active');
                    } else {
                        alert('Error loading job centre data');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading job centre data');
                });
        }
        
        function downloadTemplate() {
            const csv = `name,category,description,address,state,city,contact_number,email,website,services,operating_hours,is_verified,is_government,is_active
National Directorate of Employment (NDE),offline,"Government employment agency providing job placement and training","Plot 1, NDE House, Alausa",Lagos,Ikeja,08012345678,info@nde.gov.ng,https://nde.gov.ng,"Job Placement, Vocational Training, Career Counseling",Mon-Fri: 8AM-4PM,1,1,1
Career Services Ltd,both,"Private recruitment and career advisory firm","15 Victoria Street",Lagos,Victoria Island,08098765432,contact@careerservices.ng,https://careerservices.ng,"Recruitment, CV Writing, Interview Prep",Mon-Fri: 9AM-5PM,1,0,1`;
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'job_centres_template.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }
        
        // Handle form submission
        document.getElementById('jobCentreForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('api/job-centres-admin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving the job centre');
            });
        });
        
        // Handle bulk upload form submission
        document.getElementById('bulkUploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('api/job-centres-admin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Success! ${data.imported} job centres imported, ${data.skipped} skipped, ${data.errors} errors.`);
                    window.location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred during bulk upload');
            });
        });
        
        // Close modal when clicking outside
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>
