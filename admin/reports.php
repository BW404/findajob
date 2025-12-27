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
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh and try again.']);
        exit;
    }
    
    $report_id = isset($_POST['report_id']) ? intval($_POST['report_id']) : null;
    $action = trim($_POST['action']);
    
    if ($report_id) {
        // Verify report exists before processing
        $stmt = $pdo->prepare("SELECT id FROM reports WHERE id = ?");
        $stmt->execute([$report_id]);
        if (!$stmt->fetch()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Report not found']);
            exit;
        }
        
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
                $admin_notes = trim(strip_tags($_POST['admin_notes'] ?? ''));
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
                $admin_notes = trim(strip_tags($_POST['admin_notes'] ?? ''));
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
                
            } elseif ($action === 'suspend_user') {
                $suspension_reason = trim(strip_tags($_POST['suspension_reason'] ?? 'Suspended due to report violations'));
                $suspension_days = max(1, min(365, intval($_POST['suspension_days'] ?? 7))); // Limit 1-365 days
                
                // Get the reported user ID
                $stmt = $pdo->prepare("SELECT reported_entity_type, reported_entity_id FROM reports WHERE id = ?");
                $stmt->execute([$report_id]);
                $report = $stmt->fetch();
                
                $target_user_id = null;
                
                if ($report['reported_entity_type'] === 'user') {
                    $target_user_id = $report['reported_entity_id'];
                } elseif ($report['reported_entity_type'] === 'job') {
                    // Get job owner
                    $stmt = $pdo->prepare("SELECT employer_id FROM jobs WHERE id = ?");
                    $stmt->execute([$report['reported_entity_id']]);
                    $job = $stmt->fetch();
                    if ($job) $target_user_id = $job['employer_id'];
                } elseif ($report['reported_entity_type'] === 'application') {
                    // Get applicant
                    $stmt = $pdo->prepare("SELECT user_id FROM job_applications WHERE id = ?");
                    $stmt->execute([$report['reported_entity_id']]);
                    $app = $stmt->fetch();
                    if ($app) $target_user_id = $app['user_id'];
                }
                
                if ($target_user_id) {
                    $suspension_expires = date('Y-m-d H:i:s', strtotime("+{$suspension_days} days"));
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET is_suspended = 1,
                            suspension_reason = ?,
                            suspended_at = NOW(),
                            suspended_by = ?,
                            suspension_expires = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$suspension_reason, $user_id, $suspension_expires, $target_user_id]);
                    
                    // Log suspension action
                    error_log("SUSPENSION: Admin {$user_id} suspended user {$target_user_id} for {$suspension_days} days. Report ID: {$report_id}. Reason: {$suspension_reason}");
                    
                    // Update report status
                    $stmt = $pdo->prepare("
                        UPDATE reports 
                        SET status = 'suspended',
                            admin_notes = CONCAT('User suspended for ', ?, ' days. ', COALESCE(admin_notes, '')),
                            reviewed_by = ?,
                            reviewed_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$suspension_days, $user_id, $report_id]);
                    
                    $success = "User account suspended for {$suspension_days} days";
                } else {
                    $error = "Could not determine user to suspend";
                }
                
            } elseif ($action === 'unsuspend_user') {
                // Get the reported user ID
                $stmt = $pdo->prepare("SELECT reported_entity_type, reported_entity_id FROM reports WHERE id = ?");
                $stmt->execute([$report_id]);
                $report = $stmt->fetch();
                
                $target_user_id = null;
                
                if ($report['reported_entity_type'] === 'user') {
                    $target_user_id = $report['reported_entity_id'];
                } elseif ($report['reported_entity_type'] === 'job') {
                    $stmt = $pdo->prepare("SELECT employer_id FROM jobs WHERE id = ?");
                    $stmt->execute([$report['reported_entity_id']]);
                    $job = $stmt->fetch();
                    if ($job) $target_user_id = $job['employer_id'];
                } elseif ($report['reported_entity_type'] === 'application') {
                    $stmt = $pdo->prepare("SELECT job_seeker_id FROM job_applications WHERE id = ?");
                    $stmt->execute([$report['reported_entity_id']]);
                    $app = $stmt->fetch();
                    if ($app) $target_user_id = $app['job_seeker_id'];
                }
                
                if ($target_user_id) {
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET is_suspended = 0,
                            suspension_reason = NULL,
                            suspended_at = NULL,
                            suspended_by = NULL,
                            suspension_expires = NULL
                        WHERE id = ?
                    ");
                    $stmt->execute([$target_user_id]);
                    
                    // Log unsuspension action
                    error_log("UNSUSPENSION: Admin {$user_id} unsuspended user {$target_user_id}. Report ID: {$report_id}");
                    
                    // Update report status back to resolved
                    $stmt = $pdo->prepare("
                        UPDATE reports 
                        SET status = 'resolved',
                            admin_notes = CONCAT(COALESCE(admin_notes, ''), ' [Unsuspended on ', NOW(), ']')
                        WHERE id = ?
                    ");
                    $stmt->execute([$report_id]);
                    
                    $success = "User account unsuspended successfully";
                } else {
                    $error = "Could not determine user to unsuspend";
                }
            }
        } catch (Exception $e) {
            error_log("Admin Reports Error: " . $e->getMessage() . " | User: " . $user_id . " | Report: " . $report_id);
            
            if (defined('DEV_MODE') && DEV_MODE) {
                $error = "Failed to update report: " . $e->getMessage();
            } else {
                $error = "Failed to update report. Please try again.";
            }
        }
    }
    
    // Return JSON response for AJAX requests
    if (isset($success) || isset($error)) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => isset($success),
            'message' => $success ?? $error ?? 'Unknown error'
        ]);
        exit;
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
        
        .status-suspended {
            background: #fee2e2;
            color: #991b1b;
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
                            <option value="suspended" <?php echo $status_filter === 'suspended' ? 'selected' : ''; ?>>🚫 Suspended</option>
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
        console.log('ViewReport called with ID:', reportId);
        try {
            const response = await fetch(`../api/admin-actions.php?action=get_report&report_id=${reportId}`, {
                credentials: 'same-origin'
            });
            console.log('Response status:', response.status);
            const data = await response.json();
            console.log('Response data:', data);
            
            if (data.success) {
                const report = data.report;
                const modalBody = document.getElementById('modalBody');
                
                // Format reason
                const reasonText = report.reason.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                
                // Format status
                const statusText = report.status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                const statusClass = 'status-' + report.status;
                
                // Verification icons helper
                const verificationIcon = (verified) => verified ? '<i class="fas fa-check-circle" style="color: #10b981;"></i>' : '<i class="fas fa-times-circle" style="color: #dc2626;"></i>';
                
                modalBody.innerHTML = `
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Report ID</label>
                            <div class="value">#${report.id}</div>
                        </div>
                        <div class="info-item">
                            <label>Reporter</label>
                            <div class="value">
                                ${report.reporter_name}
                                <div style="display: flex; gap: 8px; margin-top: 4px;">
                                    ${verificationIcon(report.reporter_email_verified)} Email
                                    ${verificationIcon(report.reporter_phone_verified)} Phone
                                    ${report.reporter_is_suspended ? '<span style="color: #dc2626; font-size: 11px;">🚫 Suspended</span>' : ''}
                                </div>
                                <a href="/findajob/${report.reporter_user_type === 'employer' ? 'pages/company/profile.php' : 'admin/view-job-seeker.php'}?id=${report.reporter_id}" 
                                   target="_blank" class="btn btn-sm btn-info" style="margin-top: 6px; font-size: 12px;">
                                    <i class="fas fa-external-link-alt"></i> View Full Profile
                                </a>
                            </div>
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
                        <div class="description-box" style="border-left-color: #f59e0b; background: #fffbeb;">
                            <h4 style="color: #92400e;">Reported Entity: ${report.entity_name}</h4>
                            ${report.entity_details ? `
                                ${report.reported_entity_type === 'user' ? `
                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-top: 12px;">
                                        <div><strong>Email:</strong> ${report.entity_details.email}</div>
                                        <div><strong>Type:</strong> ${report.entity_details.user_type}</div>
                                        <div><strong>Status:</strong> ${report.entity_details.is_active ? '✓ Active' : '❌ Inactive'}</div>
                                        <div><strong>Suspended:</strong> ${report.entity_details.is_suspended ? '🚫 Yes' : '✓ No'}</div>
                                        <div><strong>Email Verified:</strong> ${report.entity_details.email_verified ? '✓' : '❌'}</div>
                                        <div><strong>Phone Verified:</strong> ${report.entity_details.phone_verified ? '✓' : '❌'}</div>
                                        <div><strong>Member Since:</strong> ${new Date(report.entity_details.created_at).toLocaleDateString()}</div>
                                        ${report.entity_details.user_type === 'employer' ? `
                                            <div><strong>Posted Jobs:</strong> ${report.entity_details.posted_jobs}</div>
                                        ` : `
                                            <div><strong>Applications:</strong> ${report.entity_details.applications_count}</div>
                                        `}
                                    </div>
                                    <a href="/findajob/${report.entity_details.user_type === 'employer' ? 'pages/company/profile.php' : 'admin/view-job-seeker.php'}?id=${report.entity_details.id}" 
                                       target="_blank" class="btn btn-primary" style="margin-top: 12px; display: inline-block;">
                                        <i class="fas fa-user"></i> View Full Profile
                                    </a>
                                ` : report.reported_entity_type === 'job' ? `
                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-top: 12px;">
                                        <div><strong>Company:</strong> ${report.entity_details.company_name || 'N/A'}</div>
                                        <div><strong>Status:</strong> ${report.entity_details.status}</div>
                                        <div><strong>Location:</strong> ${report.entity_details.location || 'N/A'}</div>
                                        <div><strong>Type:</strong> ${report.entity_details.job_type || 'N/A'}</div>
                                        <div><strong>Salary:</strong> ₦${parseInt(report.entity_details.salary_min || 0).toLocaleString()} - ₦${parseInt(report.entity_details.salary_max || 0).toLocaleString()}</div>
                                        <div><strong>Applications:</strong> ${report.entity_details.applications_count}</div>
                                        <div><strong>Posted:</strong> ${new Date(report.entity_details.created_at).toLocaleDateString()}</div>
                                    </div>
                                    <h5 style="margin-top: 16px; color: #1f2937;">Employer Information</h5>
                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-top: 8px;">
                                        <div><strong>Name:</strong> ${report.entity_details.employer_name}</div>
                                        <div><strong>Email:</strong> ${report.entity_details.email}</div>
                                        <div><strong>Suspended:</strong> ${report.entity_details.employer_suspended ? '🚫 Yes' : '✓ No'}</div>
                                        <div><strong>Suspended:</strong> ${report.entity_details.employer_suspended ? '🚫 Yes' : '✓ No'}</div>
                                        <div><strong>Email Verified:</strong> ${report.entity_details.email_verified ? '✓' : '❌'}</div>
                                        <div><strong>Phone Verified:</strong> ${report.entity_details.phone_verified ? '✓' : '❌'}</div>
                                        <div><strong>Total Jobs:</strong> ${report.entity_details.employer_total_jobs}</div>
                                    <div style="margin-top: 12px; display: flex; gap: 8px;">
                                        <a href="/findajob/pages/jobs/details.php?id=${report.reported_entity_id}" 
                                           target="_blank" class="btn btn-primary">
                                            <i class="fas fa-briefcase"></i> View Job Details
                                        </a>
                                        <a href="/findajob/pages/company/profile.php?id=${report.entity_details.employer_id}" 
                                           target="_blank" class="btn btn-secondary">
                                            <i class="fas fa-user"></i> View Employer Profile
                                        </a>
                                    </div>
                                ` : ''}
                            ` : `
                                <div style="padding: 0.75rem; background: #f9fafb; border-radius: 8px; margin-top: 8px;">
                                    ${report.entity_name}
                                </div>
                            `}
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
                        <div class="modal-footer" style="flex-direction: column; gap: 1rem;">
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; width: 100%;">
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
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; width: 100%; padding-top: 0.5rem; border-top: 1px solid #e5e7eb;">
                                <button onclick="showSuspendForm(${report.id})" class="btn btn-primary" style="background: #dc2626;">
                                    <i class="fas fa-user-slash"></i> Suspend Account
                                </button>
                                <button onclick="unsuspendUser(${report.id})" class="btn btn-outline">
                                    <i class="fas fa-user-check"></i> Unsuspend Account
                                </button>
                            </div>
                        </div>
                    `;
                } else {
                    // For resolved/suspended/dismissed reports, show close and action buttons
                    let actionButtons = '';
                    
                    if (report.status === 'suspended' || (report.admin_notes && report.admin_notes.includes('User suspended') && !report.admin_notes.includes('[Unsuspended'))) {
                        // User is currently suspended - show unsuspend button
                        actionButtons = `
                            <button onclick="unsuspendUser(${report.id})" class="btn btn-outline" style="border-color: #10b981; color: #10b981;">
                                <i class="fas fa-user-check"></i> Unsuspend Account
                            </button>
                        `;
                    } else if (report.admin_notes && report.admin_notes.includes('[Unsuspended')) {
                        // User was suspended but now unsuspended - show suspend again button
                        actionButtons = `
                            <button onclick="showSuspendForm(${report.id})" class="btn btn-outline" style="border-color: #dc2626; color: #dc2626;">
                                <i class="fas fa-user-slash"></i> Suspend Again
                            </button>
                        `;
                    } else if (report.reported_entity_type === 'user' || report.reported_entity_type === 'job' || report.reported_entity_type === 'application') {
                        // For reports about users, jobs, or applications - show suspend option
                        actionButtons = `
                            <button onclick="showSuspendForm(${report.id})" class="btn btn-outline" style="border-color: #dc2626; color: #dc2626;">
                                <i class="fas fa-user-slash"></i> Suspend Account
                            </button>
                        `;
                    }
                    
                    modalBody.innerHTML += `
                        <div class="modal-footer">
                            <button onclick="closeModal()" class="btn btn-primary">
                                <i class="fas fa-times"></i> Close
                            </button>
                            ${actionButtons}
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
        formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');
        
        try {
            const response = await fetch('reports.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                location.reload();
            } else {
                alert(result.message || 'Failed to update report');
            }
        } catch (error) {
            console.error('Error updating report:', error);
            alert('Failed to update report');
        }
    }
    
    function showSuspendForm(reportId) {
        const days = prompt('Enter number of days to suspend the account (1-365):', '7');
        if (days && !isNaN(days) && days > 0 && days <= 365) {
            const reason = prompt('Enter suspension reason:', 'Account suspended due to report violations');
            if (reason && reason.trim().length > 0) {
                suspendUser(reportId, days, reason.trim());
            } else {
                alert('Suspension reason is required');
            }
        } else if (days !== null) {
            alert('Please enter a valid number of days between 1 and 365');
        }
    }
    
    async function suspendUser(reportId, days, reason) {
        const formData = new FormData();
        formData.append('action', 'suspend_user');
        formData.append('report_id', reportId);
        formData.append('suspension_days', days);
        formData.append('suspension_reason', reason);
        formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');
        
        try {
            const response = await fetch('reports.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert(result.message || 'User account suspended successfully');
                location.reload();
            } else {
                alert(result.message || 'Failed to suspend user');
            }
        } catch (error) {
            console.error('Error suspending user:', error);
            alert('Failed to suspend user');
        }
    }
    
    async function unsuspendUser(reportId) {
        if (!confirm('Are you sure you want to unsuspend this account?')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'unsuspend_user');
        formData.append('report_id', reportId);
        formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');
        
        try {
            const response = await fetch('reports.php', {
                method: 'POST',
                body: formData
            });
            
            const text = await response.text();
            console.log('Response:', text);
            
            let result;
            try {
                result = JSON.parse(text);
            } catch (e) {
                console.error('JSON parse error:', e);
                alert('Server error: Invalid response format');
                return;
            }
            
            if (result.success) {
                alert(result.message || 'User account unsuspended successfully');
                location.reload();
            } else {
                alert(result.message || 'Failed to unsuspend user');
            }
        } catch (error) {
            console.error('Error unsuspending user:', error);
            alert('Failed to unsuspend user');
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
