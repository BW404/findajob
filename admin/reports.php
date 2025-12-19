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

// Get admin info
$stmt = $pdo->prepare("
    SELECT u.first_name, u.last_name, u.email, ar.role_name 
    FROM users u 
    LEFT JOIN admin_roles ar ON u.admin_role_id = ar.id 
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$admin = $stmt->fetch();

// Handle report actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $report_id = $_POST['report_id'] ?? null;
    $action = $_POST['action'];
    
    if ($report_id) {
        try {
            if ($action === 'review') {
                $stmt = $pdo->prepare("
                    UPDATE reports 
                    SET status = 'under_review',
                        reviewed_by = ?,
                        reviewed_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$user_id, $report_id]);
                $success = "Report marked as under review";
                
            } elseif ($action === 'resolve') {
                $admin_notes = $_POST['admin_notes'] ?? '';
                $stmt = $pdo->prepare("
                    UPDATE reports 
                    SET status = 'resolved',
                        admin_notes = ?,
                        reviewed_by = ?,
                        reviewed_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$admin_notes, $user_id, $report_id]);
                $success = "Report marked as resolved";
                
            } elseif ($action === 'dismiss') {
                $admin_notes = $_POST['admin_notes'] ?? '';
                $stmt = $pdo->prepare("
                    UPDATE reports 
                    SET status = 'dismissed',
                        admin_notes = ?,
                        reviewed_by = ?,
                        reviewed_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$admin_notes, $user_id, $report_id]);
                $success = "Report dismissed";
            }
        } catch (Exception $e) {
            $error = "Failed to update report: " . $e->getMessage();
        }
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$reason_filter = $_GET['reason'] ?? 'all';
$entity_filter = $_GET['entity'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where = [];
$params = [];

if ($status_filter !== 'all') {
    $where[] = "r.status = ?";
    $params[] = $status_filter;
}

if ($reason_filter !== 'all') {
    $where[] = "r.reason = ?";
    $params[] = $reason_filter;
}

if ($entity_filter !== 'all') {
    $where[] = "r.reported_entity_type = ?";
    $params[] = $entity_filter;
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$count_query = "SELECT COUNT(*) FROM reports r $where_clause";
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_reports = $stmt->fetchColumn();
$total_pages = ceil($total_reports / $per_page);

// Get reports
$query = "
    SELECT 
        r.*,
        u.first_name as reporter_first_name,
        u.last_name as reporter_last_name,
        u.email as reporter_email,
        admin.first_name as admin_first_name,
        admin.last_name as admin_last_name,
        CASE 
            WHEN r.reported_entity_type = 'job' THEN (SELECT title FROM jobs WHERE id = r.reported_entity_id)
            WHEN r.reported_entity_type = 'user' OR r.reported_entity_type = 'company' THEN (SELECT CONCAT(first_name, ' ', last_name) FROM users WHERE id = r.reported_entity_id)
            ELSE NULL
        END as entity_name
    FROM reports r
    LEFT JOIN users u ON r.reporter_id = u.id
    LEFT JOIN users admin ON r.reviewed_by = admin.id
    $where_clause
    ORDER BY 
        CASE r.status
            WHEN 'pending' THEN 1
            WHEN 'under_review' THEN 2
            WHEN 'resolved' THEN 3
            WHEN 'dismissed' THEN 4
        END,
        r.created_at DESC
    LIMIT $per_page OFFSET $offset
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$reports = $stmt->fetchAll();

// Get statistics
$stats_query = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'under_review' THEN 1 ELSE 0 END) as under_review,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
        SUM(CASE WHEN status = 'dismissed' THEN 1 ELSE 0 END) as dismissed
    FROM reports
";
$stmt = $pdo->query($stats_query);
$stats = $stmt->fetch();

$page_title = 'Reports Management - FindAJob Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #f3f4f6;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        .admin-sidebar {
            width: 250px;
            background: #1f2937;
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h1 {
            font-size: 1.25rem;
            margin-bottom: 0.25rem;
        }
        
        .sidebar-header p {
            font-size: 0.875rem;
            opacity: 0.7;
        }
        
        .sidebar-nav {
            padding: 1rem 0;
        }
        
        .nav-section {
            margin-bottom: 1.5rem;
        }
        
        .nav-section-title {
            padding: 0.5rem 1.5rem;
            font-size: 0.75rem;
            text-transform: uppercase;
            opacity: 0.5;
            font-weight: 600;
            letter-spacing: 0.05em;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.2s;
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
            text-align: center;
        }
        
        .main-content {
            flex: 1;
            padding: 2rem;
            margin-left: 250px;
            max-width: 100%;
        }
        
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }
            
            .main-content {
                margin-left: 0;
            }
        }
        
        .page-header {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .page-header h1 {
            margin: 0 0 0.5rem 0;
            color: #111827;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .page-header p {
            color: #6b7280;
            margin: 0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-left: 4px solid;
        }
        
        .stat-card.total { border-left-color: #3b82f6; }
        .stat-card.pending { border-left-color: #f59e0b; }
        .stat-card.review { border-left-color: #8b5cf6; }
        .stat-card.resolved { border-left-color: #10b981; }
        .stat-card.dismissed { border-left-color: #6b7280; }
        
        .stat-card h3 {
            font-size: 0.875rem;
            color: #6b7280;
            margin: 0 0 0.5rem 0;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .stat-card .value {
            font-size: 2rem;
            font-weight: 700;
            color: #111827;
        }
        
        .filters-section {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .filter-group label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }
        
        .filter-group select {
            width: 100%;
            padding: 0.625rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.9375rem;
        }
        
        .reports-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #f9fafb;
        }
        
        th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 2px solid #e5e7eb;
        }
        
        td {
            padding: 1rem;
            border-bottom: 1px solid #f3f4f6;
        }
        
        tr:hover {
            background: #f9fafb;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-under_review {
            background: #ede9fe;
            color: #5b21b6;
        }
        
        .status-resolved {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-dismissed {
            background: #f3f4f6;
            color: #1f2937;
        }
        
        .reason-badge {
            display: inline-block;
            padding: 0.25rem 0.625rem;
            background: #e0e7ff;
            color: #3730a3;
            border-radius: 6px;
            font-size: 0.8125rem;
            font-weight: 500;
        }
        
        .entity-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.25rem 0.625rem;
            background: #dbeafe;
            color: #1e40af;
            border-radius: 6px;
            font-size: 0.8125rem;
            font-weight: 500;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.8125rem;
        }
        
        .btn-primary {
            background: #dc2626;
            color: white;
        }
        
        .btn-primary:hover {
            background: #b91c1c;
        }
        
        .btn-success {
            background: #10b981;
            color: white;
        }
        
        .btn-success:hover {
            background: #059669;
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        .btn-outline {
            background: white;
            color: #374151;
            border: 2px solid #e5e7eb;
        }
        
        .btn-outline:hover {
            background: #f9fafb;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }
        
        .pagination a,
        .pagination span {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            color: #374151;
            background: white;
            border: 2px solid #e5e7eb;
        }
        
        .pagination a:hover {
            background: #f9fafb;
        }
        
        .pagination .active {
            background: #dc2626;
            color: white;
            border-color: #dc2626;
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }
        
        .report-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.6);
            z-index: 10000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .report-modal.active {
            display: flex;
        }
        
        .report-modal-content {
            background: white;
            border-radius: 16px;
            max-width: 800px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: white;
            padding: 1.5rem 2rem;
            border-radius: 16px 16px 0 0;
        }
        
        .modal-body {
            padding: 2rem;
        }
        
        .modal-footer {
            padding: 1.5rem 2rem;
            background: #f9fafb;
            border-radius: 0 0 16px 16px;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-family: inherit;
            resize: vertical;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .info-item {
            padding: 1rem;
            background: #f9fafb;
            border-radius: 8px;
        }
        
        .info-item label {
            display: block;
            font-size: 0.75rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.25rem;
        }
        
        .info-item .value {
            font-weight: 600;
            color: #111827;
        }
        
        .description-box {
            background: #f9fafb;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #dc2626;
            margin-bottom: 1.5rem;
        }
        
        .description-box h4 {
            margin: 0 0 0.5rem 0;
            color: #374151;
            font-size: 0.875rem;
            text-transform: uppercase;
        }
        
        .description-box p {
            margin: 0;
            color: #1f2937;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="page-header">
                <h1>
                    <i class="fas fa-flag"></i>
                    Reports Management
                </h1>
                <p>Review and manage user-submitted reports</p>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card total">
                    <h3>Total Reports</h3>
                    <div class="value"><?php echo number_format($stats['total']); ?></div>
                </div>
                <div class="stat-card pending">
                    <h3>Pending</h3>
                    <div class="value"><?php echo number_format($stats['pending']); ?></div>
                </div>
                <div class="stat-card review">
                    <h3>Under Review</h3>
                    <div class="value"><?php echo number_format($stats['under_review']); ?></div>
                </div>
                <div class="stat-card resolved">
                    <h3>Resolved</h3>
                    <div class="value"><?php echo number_format($stats['resolved']); ?></div>
                </div>
                <div class="stat-card dismissed">
                    <h3>Dismissed</h3>
                    <div class="value"><?php echo number_format($stats['dismissed']); ?></div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="filters-section">
                <h3 style="margin: 0 0 1rem 0; font-size: 1rem;">
                    <i class="fas fa-filter"></i> Filters
                </h3>
                <form method="GET" class="filters-grid">
                    <div class="filter-group">
                        <label>Status</label>
                        <select name="status" onchange="this.form.submit()">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="under_review" <?php echo $status_filter === 'under_review' ? 'selected' : ''; ?>>Under Review</option>
                            <option value="resolved" <?php echo $status_filter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                            <option value="dismissed" <?php echo $status_filter === 'dismissed' ? 'selected' : ''; ?>>Dismissed</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Entity Type</label>
                        <select name="entity" onchange="this.form.submit()">
                            <option value="all" <?php echo $entity_filter === 'all' ? 'selected' : ''; ?>>All Types</option>
                            <option value="job" <?php echo $entity_filter === 'job' ? 'selected' : ''; ?>>Jobs</option>
                            <option value="user" <?php echo $entity_filter === 'user' ? 'selected' : ''; ?>>Users</option>
                            <option value="company" <?php echo $entity_filter === 'company' ? 'selected' : ''; ?>>Companies</option>
                            <option value="application" <?php echo $entity_filter === 'application' ? 'selected' : ''; ?>>Applications</option>
                            <option value="other" <?php echo $entity_filter === 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Reason</label>
                        <select name="reason" onchange="this.form.submit()">
                            <option value="all" <?php echo $reason_filter === 'all' ? 'selected' : ''; ?>>All Reasons</option>
                            <option value="fake_profile">Fake Profile</option>
                            <option value="fake_job">Fake Job</option>
                            <option value="inappropriate_content">Inappropriate Content</option>
                            <option value="harassment">Harassment</option>
                            <option value="spam">Spam</option>
                            <option value="scam">Scam</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="filter-group" style="display: flex; align-items: flex-end;">
                        <?php if ($status_filter !== 'all' || $entity_filter !== 'all' || $reason_filter !== 'all'): ?>
                            <a href="reports.php" class="btn btn-outline" style="width: 100%;">
                                <i class="fas fa-times"></i> Clear Filters
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Reports Table -->
            <div class="reports-table">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Reporter</th>
                                <th>Entity</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reports)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 3rem; color: #6b7280;">
                                        <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
                                        No reports found
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($reports as $report): ?>
                                    <tr>
                                        <td><strong>#<?php echo $report['id']; ?></strong></td>
                                        <td>
                                            <div><?php echo htmlspecialchars($report['reporter_first_name'] . ' ' . $report['reporter_last_name']); ?></div>
                                            <div style="font-size: 0.8125rem; color: #6b7280;">
                                                <?php echo ucfirst($report['reporter_type']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="entity-badge">
                                                <i class="fas fa-<?php 
                                                    echo $report['reported_entity_type'] === 'job' ? 'briefcase' :
                                                        ($report['reported_entity_type'] === 'user' ? 'user' :
                                                        ($report['reported_entity_type'] === 'company' ? 'building' : 'file'));
                                                ?>"></i>
                                                <?php echo ucfirst($report['reported_entity_type']); ?>
                                            </span>
                                            <?php if ($report['entity_name']): ?>
                                                <div style="font-size: 0.8125rem; color: #6b7280; margin-top: 0.25rem;">
                                                    <?php echo htmlspecialchars($report['entity_name']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="reason-badge">
                                                <?php echo ucfirst(str_replace('_', ' ', $report['reason'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $report['status']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $report['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div><?php echo date('M j, Y', strtotime($report['created_at'])); ?></div>
                                            <div style="font-size: 0.8125rem; color: #6b7280;">
                                                <?php echo date('g:i A', strtotime($report['created_at'])); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button onclick="viewReport(<?php echo $report['id']; ?>)" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&entity=<?php echo $entity_filter; ?>&reason=<?php echo $reason_filter; ?>">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <a href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&entity=<?php echo $entity_filter; ?>&reason=<?php echo $reason_filter; ?>" 
                           class="<?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&entity=<?php echo $entity_filter; ?>&reason=<?php echo $reason_filter; ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Report Details Modal -->
    <div id="reportModal" class="report-modal">
        <div class="report-modal-content">
            <div class="modal-header">
                <h3 style="margin: 0;">Report Details</h3>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Content loaded dynamically -->
            </div>
        </div>
    </div>
    
    <script>
    async function viewReport(reportId) {
        try {
            const response = await fetch(`../api/admin-actions.php?action=get_report&report_id=${reportId}`);
            const data = await response.json();
            
            if (data.success) {
                const report = data.report;
                const modalBody = document.getElementById('modalBody');
                
                // Format reason
                const reasonText = report.reason.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                
                // Format status
                const statusText = report.status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                const statusClass = 'status-' + report.status;
                
                modalBody.innerHTML = `
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Report ID</label>
                            <div class="value">#${report.id}</div>
                        </div>
                        <div class="info-item">
                            <label>Reporter</label>
                            <div class="value">${report.reporter_name}</div>
                        </div>
                        <div class="info-item">
                            <label>Reporter Type</label>
                            <div class="value">${report.reporter_type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}</div>
                        </div>
                        <div class="info-item">
                            <label>Entity Type</label>
                            <div class="value">${report.reported_entity_type.replace(/\b\w/g, l => l.toUpperCase())}</div>
                        </div>
                        <div class="info-item">
                            <label>Reason</label>
                            <div class="value">${reasonText}</div>
                        </div>
                        <div class="info-item">
                            <label>Status</label>
                            <div class="value"><span class="status-badge ${statusClass}">${statusText}</span></div>
                        </div>
                        <div class="info-item">
                            <label>Submitted</label>
                            <div class="value">${new Date(report.created_at).toLocaleString()}</div>
                        </div>
                        ${report.reviewed_by ? `
                            <div class="info-item">
                                <label>Reviewed By</label>
                                <div class="value">${report.reviewer_name}</div>
                            </div>
                        ` : ''}
                    </div>
                    
                    <div class="description-box">
                        <h4>Report Description</h4>
                        <p>${report.description}</p>
                    </div>
                    
                    ${report.admin_notes ? `
                        <div class="description-box" style="border-left-color: #10b981;">
                            <h4>Admin Notes</h4>
                            <p>${report.admin_notes}</p>
                        </div>
                    ` : ''}
                    
                    ${report.entity_name ? `
                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Reported Entity</label>
                            <div style="padding: 0.75rem; background: #f9fafb; border-radius: 8px;">
                                ${report.entity_name}
                            </div>
                        </div>
                    ` : ''}
                    
                    ${report.status === 'pending' || report.status === 'under_review' ? `
                        <form id="actionForm" style="margin-top: 1.5rem;">
                            <div class="form-group">
                                <label>Admin Notes (Optional)</label>
                                <textarea name="admin_notes" rows="3" placeholder="Add notes about your decision...">${report.admin_notes || ''}</textarea>
                            </div>
                            <input type="hidden" name="report_id" value="${report.id}">
                        </form>
                    ` : ''}
                `;
                
                // Add action buttons to footer
                if (report.status === 'pending' || report.status === 'under_review') {
                    modalBody.innerHTML += `
                        <div class="modal-footer">
                            <button onclick="closeModal()" class="btn btn-outline">
                                <i class="fas fa-times"></i> Close
                            </button>
                            ${report.status === 'pending' ? `
                                <button onclick="updateReportStatus(${report.id}, 'review')" class="btn btn-secondary">
                                    <i class="fas fa-eye"></i> Mark as Under Review
                                </button>
                            ` : ''}
                            <button onclick="updateReportStatus(${report.id}, 'dismiss')" class="btn btn-secondary">
                                <i class="fas fa-ban"></i> Dismiss
                            </button>
                            <button onclick="updateReportStatus(${report.id}, 'resolve')" class="btn btn-success">
                                <i class="fas fa-check"></i> Mark as Resolved
                            </button>
                        </div>
                    `;
                } else {
                    modalBody.innerHTML += `
                        <div class="modal-footer">
                            <button onclick="closeModal()" class="btn btn-primary">
                                <i class="fas fa-times"></i> Close
                            </button>
                        </div>
                    `;
                }
                
                document.getElementById('reportModal').classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        } catch (error) {
            console.error('Error loading report:', error);
            alert('Failed to load report details');
        }
    }
    
    function closeModal() {
        document.getElementById('reportModal').classList.remove('active');
        document.body.style.overflow = '';
    }
    
    async function updateReportStatus(reportId, action) {
        const form = document.getElementById('actionForm');
        const formData = new FormData(form);
        formData.append('action', action);
        formData.append('report_id', reportId);
        
        try {
            const response = await fetch('reports.php', {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                location.reload();
            }
        } catch (error) {
            console.error('Error updating report:', error);
            alert('Failed to update report');
        }
    }
    
    // Close modal on outside click
    document.getElementById('reportModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
    </script>
</body>
</html>
