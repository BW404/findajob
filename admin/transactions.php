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

// Check permission
if (!hasPermission($user_id, 'view_transactions') && !isSuperAdmin($user_id)) {
    header('Location: dashboard.php?error=access_denied');
    exit;
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Filters
$status_filter = $_GET['status'] ?? '';
$user_type_filter = $_GET['user_type'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "t.status = ?";
    $params[] = $status_filter;
}

if ($user_type_filter) {
    $where_conditions[] = "u.user_type = ?";
    $params[] = $user_type_filter;
}

if ($date_from) {
    $where_conditions[] = "DATE(t.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where_conditions[] = "DATE(t.created_at) <= ?";
    $params[] = $date_to;
}

if ($search) {
    $where_conditions[] = "(t.tx_ref LIKE ? OR u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_sql = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_query = "
    SELECT COUNT(*) as total
    FROM transactions t
    JOIN users u ON t.user_id = u.id
    $where_sql
";
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_transactions = $stmt->fetch()['total'];
$total_pages = ceil($total_transactions / $per_page);

// Get transactions
$query = "
    SELECT 
        t.*,
        u.first_name,
        u.last_name,
        u.email,
        u.user_type
    FROM transactions t
    JOIN users u ON t.user_id = u.id
    $where_sql
    ORDER BY t.created_at DESC
    LIMIT $per_page OFFSET $offset
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Get statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_count,
        SUM(CASE WHEN status = 'successful' THEN amount ELSE 0 END) as total_revenue,
        SUM(CASE WHEN status = 'successful' THEN 1 ELSE 0 END) as successful_count,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count
    FROM transactions
    WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
";
$stats = $pdo->query($stats_query)->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - FindAJob Admin</title>
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

        .sidebar-header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .sidebar-header p {
            font-size: 13px;
            opacity: 0.7;
        }

        .sidebar-nav {
            padding: 20px 0;
        }

        .nav-section {
            margin-bottom: 25px;
        }

        .nav-section-title {
            padding: 0 20px;
            margin-bottom: 10px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.5;
            font-weight: 600;
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
            border-left-color: #dc2626;
        }

        .nav-link.active {
            background: rgba(220, 38, 38, 0.1);
            color: white;
            border-left-color: #dc2626;
        }

        .nav-link i {
            width: 20px;
            margin-right: 12px;
            font-size: 16px;
        }

        .nav-link span {
            font-size: 14px;
            font-weight: 500;
        }

        .main-content {
            margin-left: 260px;
            padding: 2rem;
            min-height: 100vh;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 32px;
            color: #1a1a2e;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: #6b7280;
            font-size: 16px;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .stat-title {
            color: #6b7280;
            font-size: 14px;
            font-weight: 500;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .stat-icon.revenue {
            background: #dcfce7;
            color: #16a34a;
        }

        .stat-icon.success {
            background: #dbeafe;
            color: #2563eb;
        }

        .stat-icon.pending {
            background: #fef3c7;
            color: #d97706;
        }

        .stat-icon.failed {
            background: #fee2e2;
            color: #dc2626;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #1a1a2e;
        }

        .stat-change {
            font-size: 12px;
            color: #10b981;
            margin-top: 0.5rem;
        }

        /* Filters */
        .filters-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .filters-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .filters-header h3 {
            font-size: 18px;
            color: #1a1a2e;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .filter-group input,
        .filter-group select {
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #dc2626;
        }

        .filter-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        /* Transactions Table */
        .transactions-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .table-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .table-header h3 {
            font-size: 18px;
            color: #1a1a2e;
        }

        .transactions-table {
            width: 100%;
            border-collapse: collapse;
        }

        .transactions-table thead {
            background: #f9fafb;
        }

        .transactions-table th {
            padding: 1rem;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .transactions-table td {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
            color: #1a1a2e;
        }

        .transactions-table tbody tr:hover {
            background: #f9fafb;
        }

        .status-badge {
            display: inline-block;
            padding: 0.375rem 0.75rem;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-badge.completed,
        .status-badge.successful {
            background: #dcfce7;
            color: #16a34a;
        }

        .status-badge.pending {
            background: #fef3c7;
            color: #d97706;
        }

        .status-badge.failed {
            background: #fee2e2;
            color: #dc2626;
        }

        .user-type-badge {
            display: inline-block;
            padding: 0.25rem 0.625rem;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
        }

        .user-type-badge.job_seeker {
            background: #dbeafe;
            color: #2563eb;
        }

        .user-type-badge.employer {
            background: #fce7f3;
            color: #ec4899;
        }

        /* Buttons */
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-primary {
            background: #dc2626;
            color: white;
        }

        .btn-primary:hover {
            background: #b91c1c;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 13px;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            padding: 1.5rem;
            border-top: 1px solid #e5e7eb;
        }

        .pagination a,
        .pagination span {
            padding: 0.5rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            text-decoration: none;
            color: #1a1a2e;
            font-size: 14px;
            transition: all 0.3s;
        }

        .pagination a:hover {
            background: #f9fafb;
            border-color: #dc2626;
        }

        .pagination .active {
            background: #dc2626;
            color: white;
            border-color: #dc2626;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }

        .empty-state i {
            font-size: 64px;
            color: #d1d5db;
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            font-size: 20px;
            color: #6b7280;
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: #9ca3af;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-money-bill-wave"></i> Transactions</h1>
            <p>Monitor and manage payment transactions (Last 30 days)</p>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Total Revenue</span>
                    <div class="stat-icon revenue">
                        <i class="fas fa-naira-sign"></i>
                    </div>
                </div>
                <div class="stat-value">₦<?= number_format($stats['total_revenue'] ?? 0) ?></div>
                <div class="stat-change">
                    <i class="fas fa-arrow-up"></i> Last 30 days
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Successful</span>
                    <div class="stat-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <div class="stat-value"><?= number_format($stats['successful_count'] ?? 0) ?></div>
                <div class="stat-change">
                    <i class="fas fa-chart-line"></i> Completed payments
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Pending</span>
                    <div class="stat-icon pending">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <div class="stat-value"><?= number_format($stats['pending_count'] ?? 0) ?></div>
                <div class="stat-change">
                    <i class="fas fa-hourglass-half"></i> Awaiting confirmation
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Failed</span>
                    <div class="stat-icon failed">
                        <i class="fas fa-times-circle"></i>
                    </div>
                </div>
                <div class="stat-value"><?= number_format($stats['failed_count'] ?? 0) ?></div>
                <div class="stat-change" style="color: #dc2626;">
                    <i class="fas fa-exclamation-triangle"></i> Unsuccessful attempts
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-card">
            <div class="filters-header">
                <h3><i class="fas fa-filter"></i> Filters</h3>
            </div>
            
            <form method="GET" action="">
                <div class="filter-grid">
                    <div class="filter-group">
                        <label>Search</label>
                        <input type="text" name="search" placeholder="Ref, email, name..." value="<?= htmlspecialchars($search) ?>">
                    </div>

                    <div class="filter-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="">All Statuses</option>
                            <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="failed" <?= $status_filter === 'failed' ? 'selected' : '' ?>>Failed</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>User Type</label>
                        <select name="user_type">
                            <option value="">All Users</option>
                            <option value="job_seeker" <?= $user_type_filter === 'job_seeker' ? 'selected' : '' ?>>Job Seekers</option>
                            <option value="employer" <?= $user_type_filter === 'employer' ? 'selected' : '' ?>>Employers</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Date From</label>
                        <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
                    </div>

                    <div class="filter-group">
                        <label>Date To</label>
                        <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
                    </div>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-search"></i> Apply Filters
                    </button>
                    <a href="transactions.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Transactions Table -->
        <div class="transactions-card">
            <div class="table-header">
                <h3>All Transactions (<?= number_format($total_transactions) ?>)</h3>
            </div>

            <?php if (empty($transactions)): ?>
                <div class="empty-state">
                    <i class="fas fa-receipt"></i>
                    <h3>No transactions found</h3>
                    <p>No payment transactions match your current filters.</p>
                </div>
            <?php else: ?>
                <table class="transactions-table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>User</th>
                            <th>Service</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($transaction['tx_ref']) ?></strong>
                                    <br>
                                    <small style="color: #9ca3af;">N/A</small>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <div>
                                            <strong><?= htmlspecialchars($transaction['first_name'] . ' ' . $transaction['last_name']) ?></strong>
                                            <br>
                                            <small style="color: #9ca3af;"><?= htmlspecialchars($transaction['email']) ?></small>
                                            <br>
                                            <span class="user-type-badge <?= $transaction['user_type'] ?>">
                                                <?= ucfirst(str_replace('_', ' ', $transaction['user_type'])) ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars(ucwords(str_replace('_', ' ', $transaction['service_type']))) ?></strong>
                                    <br>
                                    <small style="color: #9ca3af;"><?= htmlspecialchars($transaction['description'] ?? 'N/A') ?></small>
                                </td>
                                <td>
                                    <strong style="font-size: 16px;">₦<?= number_format($transaction['amount']) ?></strong>
                                </td>
                                <td>
                                    <span class="status-badge <?= $transaction['status'] ?>">
                                        <?= ucfirst($transaction['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?= date('M d, Y', strtotime($transaction['created_at'])) ?>
                                    <br>
                                    <small style="color: #9ca3af;"><?= date('h:i A', strtotime($transaction['created_at'])) ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?><?= $status_filter ? '&status=' . $status_filter : '' ?><?= $user_type_filter ? '&user_type=' . $user_type_filter : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $date_from ? '&date_from=' . $date_from : '' ?><?= $date_to ? '&date_to=' . $date_to : '' ?>">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="active"><?= $i ?></span>
                            <?php else: ?>
                                <a href="?page=<?= $i ?><?= $status_filter ? '&status=' . $status_filter : '' ?><?= $user_type_filter ? '&user_type=' . $user_type_filter : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $date_from ? '&date_from=' . $date_from : '' ?><?= $date_to ? '&date_to=' . $date_to : '' ?>"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?= $page + 1 ?><?= $status_filter ? '&status=' . $status_filter : '' ?><?= $user_type_filter ? '&user_type=' . $user_type_filter : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $date_from ? '&date_from=' . $date_from : '' ?><?= $date_to ? '&date_to=' . $date_to : '' ?>">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
