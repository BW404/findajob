<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/constants.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: login.php');
    exit();
}

$success_message = '';
$error_message = '';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $request_id = (int)$_POST['request_id'];
    
    if ($_POST['action'] === 'update_status') {
        $new_status = $_POST['status'];
        $admin_notes = trim($_POST['admin_notes'] ?? '');
        
        $stmt = $pdo->prepare("UPDATE premium_cv_requests SET status = ?, admin_notes = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$new_status, $admin_notes, $request_id]);
        $success_message = 'Request status updated successfully';
        
    } elseif ($_POST['action'] === 'schedule_consultation') {
        $consultation_date = $_POST['consultation_date'];
        $stmt = $pdo->prepare("UPDATE premium_cv_requests SET consultation_scheduled = ?, status = 'in_progress', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$consultation_date, $request_id]);
        $success_message = 'Consultation scheduled successfully';
        
    } elseif ($_POST['action'] === 'set_delivery') {
        $delivery_date = $_POST['delivery_date'];
        $stmt = $pdo->prepare("UPDATE premium_cv_requests SET delivery_date = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$delivery_date, $request_id]);
        $success_message = 'Delivery date set successfully';
        
    } elseif ($_POST['action'] === 'mark_completed') {
        $stmt = $pdo->prepare("UPDATE premium_cv_requests SET status = 'completed', delivery_date = NOW(), updated_at = NOW() WHERE id = ?");
        $stmt->execute([$request_id]);
        $success_message = 'Request marked as completed';
    }
}

// Get filter
$status_filter = $_GET['status'] ?? '';
$payment_filter = $_GET['payment'] ?? '';

// Build query
$where_clauses = [];
$params = [];

if ($status_filter) {
    $where_clauses[] = "pcr.status = ?";
    $params[] = $status_filter;
}

if ($payment_filter) {
    $where_clauses[] = "pcr.payment_status = ?";
    $params[] = $payment_filter;
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Get all requests
$stmt = $pdo->prepare("
    SELECT 
        pcr.*,
        u.first_name, u.last_name, u.email, u.phone,
        CASE 
            WHEN pcr.plan_type = 'cv_pro' THEN 'CV Pro'
            WHEN pcr.plan_type = 'cv_pro_plus' THEN 'CV Pro+'
            WHEN pcr.plan_type = 'remote_working_cv' THEN 'Remote Working CV'
        END as plan_name
    FROM premium_cv_requests pcr
    JOIN users u ON pcr.user_id = u.id
    $where_sql
    ORDER BY pcr.created_at DESC
");
$stmt->execute($params);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get stats
$statsStmt = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END) as total_revenue
    FROM premium_cv_requests
");
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium CV Requests - Admin</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f7fa; }
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
        .sidebar-header { padding: 24px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header h1 { font-size: 20px; font-weight: 700; color: #fff; margin-bottom: 4px; }
        .sidebar-header p { font-size: 13px; color: rgba(255,255,255,0.6); }
        .sidebar-nav { padding: 20px 0; }
        .nav-section { margin-bottom: 24px; }
        .nav-section-title { padding: 0 20px 8px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: rgba(255,255,255,0.5); }
        .nav-link { display: flex; align-items: center; padding: 12px 20px; color: rgba(255,255,255,0.8); text-decoration: none; transition: all 0.2s; }
        .nav-link:hover { background: rgba(255,255,255,0.1); color: white; }
        .nav-link.active { background: rgba(220, 38, 38, 0.2); color: white; border-left: 3px solid #dc2626; }
        .nav-link i { width: 20px; margin-right: 12px; font-size: 16px; }
        
        .admin-main { 
            margin-left: 260px; 
            flex: 1; 
            padding: 24px; 
            width: calc(100% - 260px); 
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #dc2626;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .filters {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .requests-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
        }
        
        .badge {
            padding: 0.35rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-in-progress { background: #dbeafe; color: #1e40af; }
        .badge-completed { background: #d1fae5; color: #065f46; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }
        .badge-paid { background: #d1fae5; color: #065f46; }
        .badge-unpaid { background: #fee2e2; color: #991b1b; }
        
        .action-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            margin-right: 0.5rem;
        }
        
        .btn-primary { background: #dc2626; color: white; }
        .btn-secondary { background: #6b7280; color: white; }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 12px;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="admin-main">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h1><i class="fas fa-file-invoice"></i> Premium CV Requests</h1>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success" style="margin-bottom: 2rem;">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Requests</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['in_progress']; ?></div>
                <div class="stat-label">In Progress</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['completed']; ?></div>
                <div class="stat-label">Completed</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">₦<?php echo number_format($stats['total_revenue']); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filters">
            <form method="GET" style="display: flex; gap: 1rem; flex-wrap: wrap; flex: 1;">
                <select name="status" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
                
                <select name="payment" onchange="this.form.submit()">
                    <option value="">All Payments</option>
                    <option value="paid" <?php echo $payment_filter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                    <option value="pending" <?php echo $payment_filter === 'pending' ? 'selected' : ''; ?>>Unpaid</option>
                </select>
                
                <?php if ($status_filter || $payment_filter): ?>
                    <a href="premium-cv-manager.php" class="btn btn-secondary">Clear Filters</a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Requests Table -->
        <div class="requests-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Client</th>
                        <th>Plan</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 3rem; color: #6b7280;">
                                No requests found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td>#<?php echo $request['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></strong><br>
                                    <small style="color: #6b7280;"><?php echo htmlspecialchars($request['email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($request['plan_name']); ?></td>
                                <td><strong>₦<?php echo number_format($request['amount']); ?></strong></td>
                                <td>
                                    <span class="badge badge-<?php echo $request['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $request['payment_status'] === 'paid' ? 'paid' : 'unpaid'; ?>">
                                        <?php echo ucfirst($request['payment_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($request['created_at'])); ?></td>
                                <td>
                                    <button onclick="viewRequest(<?php echo $request['id']; ?>)" class="action-btn btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- View Request Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div id="modalContent"></div>
        </div>
    </div>
    
    <script>
        function viewRequest(id) {
            // This would fetch and display full request details
            window.location.href = 'view-cv-request.php?id=' + id;
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('viewModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
        </div>
    </div>
</body>
</html>
