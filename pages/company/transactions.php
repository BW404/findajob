<?php
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/maintenance-check.php';
require_once '../../config/constants.php';

requireEmployer();

$user_id = getCurrentUserId();

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Filters
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query
$where_conditions = ["user_id = ?"];
$params = [$user_id];

if ($status_filter) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

if ($date_from) {
    $where_conditions[] = "DATE(created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where_conditions[] = "DATE(created_at) <= ?";
    $params[] = $date_to;
}

$where_sql = 'WHERE ' . implode(' AND ', $where_conditions);

// Get total count
$count_query = "SELECT COUNT(*) as total FROM payment_transactions $where_sql";
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_transactions = $stmt->fetch()['total'];
$total_pages = ceil($total_transactions / $per_page);

// Get transactions
$query = "
    SELECT *
    FROM payment_transactions
    $where_sql
    ORDER BY created_at DESC
    LIMIT $per_page OFFSET $offset
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Get statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_count,
        SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_spent,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful_count,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count
    FROM payment_transactions
    WHERE user_id = ?
";
$stmt = $pdo->prepare($stats_query);
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

$page_title = 'My Transactions';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f5f7fa;
            padding-top: 70px;
            padding-bottom: 80px;
        }

        .transactions-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
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

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #dc2626;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 1.5rem;
            transition: gap 0.3s;
        }

        .back-link:hover {
            gap: 0.75rem;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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

        .stat-icon.spent {
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

        .stat-icon.total {
            background: #f3e8ff;
            color: #9333ea;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #1a1a2e;
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

        /* Transactions */
        .transactions-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .card-header h3 {
            font-size: 18px;
            color: #1a1a2e;
        }

        .transaction-item {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            transition: background 0.3s;
        }

        .transaction-item:hover {
            background: #f9fafb;
        }

        .transaction-item:last-child {
            border-bottom: none;
        }

        .transaction-main {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.75rem;
        }

        .transaction-info h4 {
            font-size: 16px;
            color: #1a1a2e;
            margin-bottom: 0.25rem;
        }

        .transaction-ref {
            font-size: 13px;
            color: #9ca3af;
            font-family: monospace;
        }

        .transaction-amount {
            font-size: 24px;
            font-weight: 700;
            color: #1a1a2e;
        }

        .transaction-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            font-size: 14px;
            color: #6b7280;
        }

        .transaction-meta span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-badge {
            display: inline-block;
            padding: 0.375rem 0.75rem;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-badge.completed {
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

        .status-badge.cancelled {
            background: #f3f4f6;
            color: #6b7280;
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
            margin-bottom: 1.5rem;
        }

        @media (max-width: 768px) {
            .transaction-main {
                flex-direction: column;
                gap: 0.75rem;
            }

            .transaction-amount {
                font-size: 20px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .filter-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="transactions-container">
        <a href="dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <div class="page-header">
            <h1><i class="fas fa-receipt"></i> My Transactions</h1>
            <p>View your payment history and transaction details</p>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Total Spent</span>
                    <div class="stat-icon spent">
                        <i class="fas fa-naira-sign"></i>
                    </div>
                </div>
                <div class="stat-value">₦<?= number_format($stats['total_spent'] ?? 0) ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Successful</span>
                    <div class="stat-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <div class="stat-value"><?= number_format($stats['successful_count'] ?? 0) ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Pending</span>
                    <div class="stat-icon pending">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <div class="stat-value"><?= number_format($stats['pending_count'] ?? 0) ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Total Transactions</span>
                    <div class="stat-icon total">
                        <i class="fas fa-list"></i>
                    </div>
                </div>
                <div class="stat-value"><?= number_format($stats['total_count'] ?? 0) ?></div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-card">
            <div class="filters-header">
                <h3><i class="fas fa-filter"></i> Filter Transactions</h3>
            </div>
            
            <form method="GET" action="">
                <div class="filter-grid">
                    <div class="filter-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="">All Statuses</option>
                            <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="failed" <?= $status_filter === 'failed' ? 'selected' : '' ?>>Failed</option>
                            <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
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

        <!-- Transactions List -->
        <div class="transactions-card">
            <div class="card-header">
                <h3>Transaction History (<?= number_format($total_transactions) ?>)</h3>
            </div>

            <?php if (empty($transactions)): ?>
                <div class="empty-state">
                    <i class="fas fa-receipt"></i>
                    <h3>No transactions yet</h3>
                    <p>You haven't made any payments yet. Upgrade your plan to post more jobs and reach more candidates!</p>
                    <a href="../payment/plans.php" class="btn btn-primary">
                        <i class="fas fa-crown"></i> View Plans
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($transactions as $transaction): ?>
                    <div class="transaction-item">
                        <div class="transaction-main">
                            <div class="transaction-info">
                                <h4><?= htmlspecialchars($transaction['service_name'] ?? (!empty($transaction['service_type']) ? ucwords(str_replace('_', ' ', $transaction['service_type'])) : 'Payment')) ?></h4>
                                <div class="transaction-ref"><?= htmlspecialchars($transaction['transaction_ref']) ?></div>
                            </div>
                            <div class="transaction-amount">₦<?= number_format($transaction['amount']) ?></div>
                        </div>
                        
                        <div class="transaction-meta">
                            <span>
                                <i class="fas fa-calendar"></i>
                                <?= date('M d, Y h:i A', strtotime($transaction['created_at'])) ?>
                            </span>
                            <span>
                                <i class="fas fa-credit-card"></i>
                                <?= htmlspecialchars($transaction['payment_method'] ?? 'Card') ?>
                            </span>
                            <span class="status-badge <?= $transaction['status'] ?>">
                                <?= ucfirst($transaction['status']) ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?><?= $status_filter ? '&status=' . $status_filter : '' ?><?= $date_from ? '&date_from=' . $date_from : '' ?><?= $date_to ? '&date_to=' . $date_to : '' ?>">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="active"><?= $i ?></span>
                            <?php else: ?>
                                <a href="?page=<?= $i ?><?= $status_filter ? '&status=' . $status_filter : '' ?><?= $date_from ? '&date_from=' . $date_from : '' ?><?= $date_to ? '&date_to=' . $date_to : '' ?>"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?= $page + 1 ?><?= $status_filter ? '&status=' . $status_filter : '' ?><?= $date_from ? '&date_from=' . $date_from : '' ?><?= $date_to ? '&date_to=' . $date_to : '' ?>">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <nav class="app-bottom-nav">
        <a href="../../index.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">🏠</div>
            <div class="app-bottom-nav-label">Home</div>
        </a>
        <a href="post-job.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">➕</div>
            <div class="app-bottom-nav-label">Post Job</div>
        </a>
        <a href="jobs.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">💼</div>
            <div class="app-bottom-nav-label">My Jobs</div>
        </a>
        <a href="applicants.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">👥</div>
            <div class="app-bottom-nav-label">Applicants</div>
        </a>
        <a href="dashboard.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">📊</div>
            <div class="app-bottom-nav-label">Dashboard</div>
        </a>
    </nav>

    <script src="../../assets/js/pwa.js"></script>
</body>
</html>
