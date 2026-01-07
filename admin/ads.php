<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/permissions.php';

// Check if user is admin
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Check if user has admin role
$user_id = getCurrentUserId();
$stmt = $pdo->prepare("SELECT user_type FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user || $user['user_type'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Check permission
if (!hasPermission($user_id, 'view_ads')) {
    header('Location: dashboard.php?error=access_denied');
    exit;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'create_ad':
                if (!hasPermission($user_id, 'create_ads')) {
                    echo json_encode(['success' => false, 'message' => 'Permission denied']);
                    exit;
                }

                $title = trim($_POST['title']);
                $description = trim($_POST['description']);
                $ad_type = $_POST['ad_type'];
                $placement = $_POST['placement'];
                $target_url = trim($_POST['target_url'] ?? '');
                $start_date = $_POST['start_date'];
                $end_date = $_POST['end_date'] ?? null;
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                $ad_position = $_POST['ad_position'] ?? 'center';
                $priority = (int)($_POST['priority'] ?? 0);
                $custom_code = trim($_POST['custom_code'] ?? '');
                
                // Handle image upload
                $image_path = null;
                if (isset($_FILES['ad_image']) && $_FILES['ad_image']['error'] === 0) {
                    $upload_dir = '../uploads/ads/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_ext = strtolower(pathinfo($_FILES['ad_image']['name'], PATHINFO_EXTENSION));
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    
                    if (!in_array($file_ext, $allowed_types)) {
                        echo json_encode(['success' => false, 'message' => 'Invalid image format']);
                        exit;
                    }
                    
                    $filename = 'ad_' . uniqid() . '.' . $file_ext;
                    $target_path = $upload_dir . $filename;
                    
                    if (move_uploaded_file($_FILES['ad_image']['tmp_name'], $target_path)) {
                        $image_path = 'uploads/ads/' . $filename;
                    }
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO advertisements (title, description, ad_type, placement, image_path, custom_code, ad_position, priority, target_url, start_date, end_date, is_active, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$title, $description, $ad_type, $placement, $image_path, $custom_code, $ad_position, $priority, $target_url, $start_date, $end_date, $is_active, $user_id]);
                
                echo json_encode(['success' => true, 'message' => 'Advertisement created successfully']);
                exit;
                
            case 'update_ad':
                if (!hasPermission($user_id, 'edit_ads')) {
                    echo json_encode(['success' => false, 'message' => 'Permission denied']);
                    exit;
                }

                $ad_id = (int)$_POST['ad_id'];
                $title = trim($_POST['title']);
                $description = trim($_POST['description']);
                $ad_type = $_POST['ad_type'];
                $placement = $_POST['placement'];
                $target_url = trim($_POST['target_url'] ?? '');
                $start_date = $_POST['start_date'];
                $end_date = $_POST['end_date'] ?? null;
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                $ad_position = $_POST['ad_position'] ?? 'center';
                $priority = (int)($_POST['priority'] ?? 0);
                $custom_code = trim($_POST['custom_code'] ?? '');
                
                // Handle image upload
                if (isset($_FILES['ad_image']) && $_FILES['ad_image']['error'] === 0) {
                    // Delete old image
                    $stmt = $pdo->prepare("SELECT image_path FROM advertisements WHERE id = ?");
                    $stmt->execute([$ad_id]);
                    $old_ad = $stmt->fetch();
                    if ($old_ad && $old_ad['image_path'] && file_exists('../' . $old_ad['image_path'])) {
                        unlink('../' . $old_ad['image_path']);
                    }
                    
                    $upload_dir = '../uploads/ads/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_ext = strtolower(pathinfo($_FILES['ad_image']['name'], PATHINFO_EXTENSION));
                    $filename = 'ad_' . uniqid() . '.' . $file_ext;
                    $target_path = $upload_dir . $filename;
                    
                    if (move_uploaded_file($_FILES['ad_image']['tmp_name'], $target_path)) {
                        $image_path = 'uploads/ads/' . $filename;
                        $stmt = $pdo->prepare("
                            UPDATE advertisements 
                            SET title = ?, description = ?, ad_type = ?, placement = ?, image_path = ?, custom_code = ?, ad_position = ?, priority = ?, target_url = ?, start_date = ?, end_date = ?, is_active = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([$title, $description, $ad_type, $placement, $image_path, $custom_code, $ad_position, $priority, $target_url, $start_date, $end_date, $is_active, $ad_id]);
                    }
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE advertisements 
                        SET title = ?, description = ?, ad_type = ?, placement = ?, custom_code = ?, ad_position = ?, priority = ?, target_url = ?, start_date = ?, end_date = ?, is_active = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$title, $description, $ad_type, $placement, $custom_code, $ad_position, $priority, $target_url, $start_date, $end_date, $is_active, $ad_id]);
                }
                
                echo json_encode(['success' => true, 'message' => 'Advertisement updated successfully']);
                exit;
                
            case 'delete_ad':
                if (!hasPermission($user_id, 'delete_ads')) {
                    echo json_encode(['success' => false, 'message' => 'Permission denied']);
                    exit;
                }

                $ad_id = (int)$_POST['ad_id'];
                
                // Get image path and delete file
                $stmt = $pdo->prepare("SELECT image_path FROM advertisements WHERE id = ?");
                $stmt->execute([$ad_id]);
                $ad = $stmt->fetch();
                
                if ($ad && $ad['image_path'] && file_exists('../' . $ad['image_path'])) {
                    unlink('../' . $ad['image_path']);
                }
                
                $stmt = $pdo->prepare("DELETE FROM advertisements WHERE id = ?");
                $stmt->execute([$ad_id]);
                
                echo json_encode(['success' => true, 'message' => 'Advertisement deleted successfully']);
                exit;
                
            case 'toggle_status':
                if (!hasPermission($user_id, 'edit_ads')) {
                    echo json_encode(['success' => false, 'message' => 'Permission denied']);
                    exit;
                }

                $ad_id = (int)$_POST['ad_id'];
                
                $stmt = $pdo->prepare("UPDATE advertisements SET is_active = NOT is_active WHERE id = ?");
                $stmt->execute([$ad_id]);
                
                echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
                exit;
        }
    } catch (Exception $e) {
        error_log("AD Manager Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred']);
        exit;
    }
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$placement_filter = isset($_GET['placement']) ? $_GET['placement'] : '';

// Build query
$where_conditions = ["1=1"];
$params = [];

if ($status_filter === 'active') {
    $where_conditions[] = "is_active = 1 AND start_date <= CURDATE() AND (end_date IS NULL OR end_date >= CURDATE())";
} elseif ($status_filter === 'inactive') {
    $where_conditions[] = "is_active = 0";
} elseif ($status_filter === 'expired') {
    $where_conditions[] = "end_date < CURDATE()";
}

if ($type_filter) {
    $where_conditions[] = "ad_type = ?";
    $params[] = $type_filter;
}

if ($placement_filter) {
    $where_conditions[] = "placement = ?";
    $params[] = $placement_filter;
}

$where_sql = implode(' AND ', $where_conditions);

try {
    // Get total count
    $count_sql = "SELECT COUNT(*) FROM advertisements WHERE $where_sql";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_records = $stmt->fetchColumn();
    $total_pages = ceil($total_records / $per_page);

    // Get ads
    $sql = "
        SELECT 
            a.*,
            u.first_name,
            u.last_name
        FROM advertisements a
        LEFT JOIN users u ON a.created_by = u.id
        WHERE $where_sql
        ORDER BY a.created_at DESC
        LIMIT $per_page OFFSET $offset
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $ads = $stmt->fetchAll();

    // Get statistics
    $stats = [
        'total_ads' => $pdo->query("SELECT COUNT(*) FROM advertisements")->fetchColumn(),
        'active_ads' => $pdo->query("SELECT COUNT(*) FROM advertisements WHERE is_active = 1 AND start_date <= CURDATE() AND (end_date IS NULL OR end_date >= CURDATE())")->fetchColumn(),
        'expired_ads' => $pdo->query("SELECT COUNT(*) FROM advertisements WHERE end_date < CURDATE()")->fetchColumn(),
        'total_clicks' => $pdo->query("SELECT COALESCE(SUM(click_count), 0) FROM advertisements")->fetchColumn(),
    ];

} catch (Exception $e) {
    error_log("AD Manager Query Error: " . $e->getMessage());
    $ads = [];
    $total_pages = 0;
    $stats = ['total_ads' => 0, 'active_ads' => 0, 'expired_ads' => 0, 'total_clicks' => 0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AD Manager - FindAJob Admin</title>
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

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .stat-card .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 5px;
        }

        .stat-card .stat-label {
            font-size: 13px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card.primary .stat-value { color: #dc2626; }
        .stat-card.success .stat-value { color: #10b981; }
        .stat-card.warning .stat-value { color: #f59e0b; }
        .stat-card.info .stat-value { color: #3b82f6; }

        /* Filters */
        .filters-bar {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-size: 13px;
            color: #6b7280;
            font-weight: 500;
        }

        .filter-group select {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
        }

        .filter-group button {
            padding: 10px 20px;
            background: #dc2626;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            margin-top: 23px;
        }

        /* Ads Grid */
        .ads-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .ad-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
            position: relative;
        }

        .ad-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            background: #f3f4f6;
        }

        .ad-content {
            padding: 20px;
        }

        .ad-title {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 10px;
        }

        .ad-description {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .ad-meta {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }

        .ad-dates {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 15px;
        }

        .ad-actions {
            display: flex;
            gap: 8px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
        }

        .ad-status-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            z-index: 10;
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
            max-width: 600px;
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
        .form-group select,
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
            min-height: 100px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-group input {
            width: auto;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 30px;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            color: #6b7280;
            text-decoration: none;
            font-size: 14px;
        }

        .pagination a:hover {
            background: #f9fafb;
            border-color: #dc2626;
            color: #dc2626;
        }

        .pagination .active {
            background: #dc2626;
            color: white;
            border-color: #dc2626;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>Advertisement Manager</h1>
                <p style="color: #6b7280; margin-top: 5px;">Manage platform advertisements and campaigns</p>
            </div>
            <?php if (hasPermission($user_id, 'create_ads')): ?>
                <button class="btn btn-primary" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i> Create Advertisement
                </button>
            <?php endif; ?>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-value"><?= number_format($stats['total_ads']) ?></div>
                <div class="stat-label">Total Ads</div>
            </div>
            <div class="stat-card success">
                <div class="stat-value"><?= number_format($stats['active_ads']) ?></div>
                <div class="stat-label">Active Ads</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-value"><?= number_format($stats['expired_ads']) ?></div>
                <div class="stat-label">Expired Ads</div>
            </div>
            <div class="stat-card info">
                <div class="stat-value"><?= number_format($stats['total_clicks']) ?></div>
                <div class="stat-label">Total Clicks</div>
            </div>
        </div>

        <!-- Filters -->
        <form method="GET" class="filters-bar">
            <div class="filter-group">
                <label>Status</label>
                <select name="status">
                    <option value="">All Status</option>
                    <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $status_filter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    <option value="expired" <?= $status_filter === 'expired' ? 'selected' : '' ?>>Expired</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Type</label>
                <select name="type">
                    <option value="">All Types</option>
                    <option value="banner" <?= $type_filter === 'banner' ? 'selected' : '' ?>>Banner</option>
                    <option value="sidebar" <?= $type_filter === 'sidebar' ? 'selected' : '' ?>>Sidebar</option>
                    <option value="inline" <?= $type_filter === 'inline' ? 'selected' : '' ?>>Inline</option>
                    <option value="popup" <?= $type_filter === 'popup' ? 'selected' : '' ?>>Popup</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Placement</label>
                <select name="placement">
                    <option value="">All Placements</option>
                    <option value="homepage" <?= $placement_filter === 'homepage' ? 'selected' : '' ?>>Homepage</option>
                    <option value="jobs_page" <?= $placement_filter === 'jobs_page' ? 'selected' : '' ?>>Jobs Page</option>
                    <option value="job_details" <?= $placement_filter === 'job_details' ? 'selected' : '' ?>>Job Details</option>
                    <option value="dashboard" <?= $placement_filter === 'dashboard' ? 'selected' : '' ?>>Dashboard</option>
                </select>
            </div>
            <div class="filter-group">
                <button type="submit"><i class="fas fa-filter"></i> Apply Filters</button>
            </div>
        </form>

        <!-- Ads Grid -->
        <?php if (empty($ads)): ?>
            <div class="empty-state">
                <i class="fas fa-ad"></i>
                <h3>No Advertisements Found</h3>
                <p>Create your first advertisement campaign</p>
            </div>
        <?php else: ?>
            <div class="ads-grid">
                <?php foreach ($ads as $ad): ?>
                    <?php
                    $is_active = $ad['is_active'] && 
                                 strtotime($ad['start_date']) <= time() && 
                                 (empty($ad['end_date']) || strtotime($ad['end_date']) >= time());
                    $is_expired = !empty($ad['end_date']) && strtotime($ad['end_date']) < time();
                    ?>
                    <div class="ad-card">
                        <div class="ad-status-badge">
                            <?php if ($is_expired): ?>
                                <span class="badge badge-danger">Expired</span>
                            <?php elseif ($is_active): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Inactive</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($ad['image_path']): ?>
                            <img src="../<?= htmlspecialchars($ad['image_path']) ?>" alt="Ad Image" class="ad-image">
                        <?php else: ?>
                            <div class="ad-image" style="display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-image" style="font-size: 48px; color: #d1d5db;"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="ad-content">
                            <div class="ad-title"><?= htmlspecialchars($ad['title']) ?></div>
                            <div class="ad-description"><?= htmlspecialchars(substr($ad['description'], 0, 100)) ?>...</div>
                            
                            <div class="ad-meta">
                                <span class="badge badge-info"><?= ucfirst($ad['ad_type']) ?></span>
                                <span class="badge badge-info"><?= ucwords(str_replace('_', ' ', $ad['placement'])) ?></span>
                            </div>
                            
                            <div class="ad-dates">
                                <i class="fas fa-calendar"></i> 
                                <?= date('M d, Y', strtotime($ad['start_date'])) ?> - 
                                <?= $ad['end_date'] ? date('M d, Y', strtotime($ad['end_date'])) : 'No End Date' ?>
                            </div>
                            
                            <div class="ad-dates">
                                <i class="fas fa-mouse-pointer"></i> 
                                <?= number_format($ad['click_count'] ?? 0) ?> clicks
                                &nbsp;|&nbsp;
                                <i class="fas fa-eye"></i> 
                                <?= number_format($ad['impression_count'] ?? 0) ?> impressions
                            </div>
                            
                            <div class="ad-actions">
                                <?php if (hasPermission($user_id, 'edit_ads')): ?>
                                    <button class="btn btn-sm btn-warning" onclick='editAd(<?= json_encode($ad) ?>)'>
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-sm btn-info" onclick="toggleAdStatus(<?= $ad['id'] ?>)">
                                        <i class="fas fa-power-off"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if (hasPermission($user_id, 'delete_ads')): ?>
                                    <button class="btn btn-sm btn-danger" onclick="deleteAd(<?= $ad['id'] ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?><?= $status_filter ? '&status=' . $status_filter : '' ?><?= $type_filter ? '&type=' . $type_filter : '' ?><?= $placement_filter ? '&placement=' . $placement_filter : '' ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="active"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?page=<?= $i ?><?= $status_filter ? '&status=' . $status_filter : '' ?><?= $type_filter ? '&type=' . $type_filter : '' ?><?= $placement_filter ? '&placement=' . $placement_filter : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?><?= $status_filter ? '&status=' . $status_filter : '' ?><?= $type_filter ? '&type=' . $type_filter : '' ?><?= $placement_filter ? '&placement=' . $placement_filter : '' ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Create/Edit Modal -->
    <div id="adModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Create Advertisement</h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            
            <form id="adForm" enctype="multipart/form-data">
                <input type="hidden" id="adId" name="ad_id">
                <input type="hidden" id="formAction" name="action" value="create_ad">
                
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" id="adTitle" name="title" required>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea id="adDescription" name="description"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Type *</label>
                        <select id="adType" name="ad_type" required onchange="toggleAdFields()">
                            <option value="banner">Banner Ad</option>
                            <option value="sidebar">Sidebar Ad</option>
                            <option value="inline">Inline Ad</option>
                            <option value="popup">Popup Ad</option>
                            <option value="google_adsense">Google AdSense</option>
                            <option value="custom_code">Custom Code</option>
                            <option value="video">Video Ad</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Placement *</label>
                        <select id="adPlacement" name="placement" required>
                            <option value="homepage">Homepage</option>
                            <option value="jobs_page">Jobs Page</option>
                            <option value="job_details">Job Details</option>
                            <option value="dashboard">Dashboard</option>
                            <option value="search_results">Search Results</option>
                            <option value="profile_page">Profile Page</option>
                            <option value="cv_page">CV Page</option>
                            <option value="company_page">Company Page</option>
                            <option value="all_pages">All Pages</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Position</label>
                        <select id="adPosition" name="ad_position">
                            <option value="top">Top</option>
                            <option value="center" selected>Center</option>
                            <option value="bottom">Bottom</option>
                            <option value="left">Left</option>
                            <option value="right">Right</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Priority (0-100)</label>
                        <input type="number" id="adPriority" name="priority" value="0" min="0" max="100">
                        <small style="display:block; margin-top:5px; color:#666;">Higher priority ads are shown first</small>
                    </div>
                </div>

                <div class="form-group" id="imageField">
                    <label>Advertisement Image</label>
                    <input type="file" name="ad_image" accept="image/*">
                    <small style="display:block; margin-top:5px; color:#666;">Supported formats: JPG, PNG, GIF, WebP (Max 5MB)</small>
                </div>
                
                <div class="form-group" id="customCodeField" style="display:none;">
                    <label>Custom Code (HTML/JavaScript) *</label>
                    <textarea id="customCode" name="custom_code" rows="8" placeholder="<script>&#10;  // Google AdSense or other ad network code&#10;</script>"></textarea>
                    <small style="display:block; margin-top:5px; color:#666;">Paste your Google AdSense code or custom HTML/JavaScript here</small>
                </div>

                <div class="form-group" id="urlField">
                    <label>Target URL</label>
                    <input type="url" id="adUrl" name="target_url" placeholder="https://example.com">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Start Date *</label>
                        <input type="date" id="startDate" name="start_date" required>
                    </div>

                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" id="endDate" name="end_date">
                    </div>
                </div>

                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="isActive" name="is_active" checked>
                        <label for="isActive" style="margin: 0;">Active</label>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Advertisement
                    </button>
                    <button type="button" class="btn" style="background: #6b7280; color: white;" onclick="closeModal()">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleAdFields() {
            const adType = document.getElementById('adType').value;
            const imageField = document.getElementById('imageField');
            const customCodeField = document.getElementById('customCodeField');
            const urlField = document.getElementById('urlField');
            
            // Reset visibility
            imageField.style.display = 'block';
            customCodeField.style.display = 'none';
            urlField.style.display = 'block';
            
            // Show/hide fields based on ad type
            if (adType === 'google_adsense' || adType === 'custom_code') {
                imageField.style.display = 'none';
                customCodeField.style.display = 'block';
                urlField.style.display = 'none';
            }
        }
        
        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Create Advertisement';
            document.getElementById('formAction').value = 'create_ad';
            document.getElementById('adForm').reset();
            document.getElementById('adId').value = '';
            document.getElementById('startDate').value = new Date().toISOString().split('T')[0];
            document.getElementById('adModal').classList.add('active');
            toggleAdFields();
        }

        function editAd(ad) {
            document.getElementById('modalTitle').textContent = 'Edit Advertisement';
            document.getElementById('formAction').value = 'update_ad';
            document.getElementById('adId').value = ad.id;
            document.getElementById('adTitle').value = ad.title;
            document.getElementById('adDescription').value = ad.description || '';
            document.getElementById('adType').value = ad.ad_type;
            document.getElementById('adPlacement').value = ad.placement;
            
            // Set new fields
            if (ad.ad_position) {
                document.getElementById('adPosition').value = ad.ad_position;
            }
            if (ad.priority) {
                document.getElementById('adPriority').value = ad.priority;
            }
            if (ad.custom_code) {
                document.getElementById('customCode').value = ad.custom_code;
            }
            
            document.getElementById('adUrl').value = ad.target_url || '';
            document.getElementById('startDate').value = ad.start_date;
            document.getElementById('endDate').value = ad.end_date || '';
            document.getElementById('isActive').checked = ad.is_active == 1;
            document.getElementById('adModal').classList.add('active');
            toggleAdFields();
        }

        function closeModal() {
            document.getElementById('adModal').classList.remove('active');
        }

        function deleteAd(adId) {
            if (!confirm('Are you sure you want to delete this advertisement?')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'delete_ad');
            formData.append('ad_id', adId);

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    location.reload();
                }
            });
        }

        function toggleAdStatus(adId) {
            const formData = new FormData();
            formData.append('action', 'toggle_status');
            formData.append('ad_id', adId);

            fetch('', {
                method: 'POST',
                body: formData
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

        document.getElementById('adForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    location.reload();
                }
            });
        });
    </script>
</body>
</html>
