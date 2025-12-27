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
$stmt = $pdo->prepare("SELECT user_type FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user || $user['user_type'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Check permission
if (!hasPermission($user_id, 'view_employers')) {
    header('Location: dashboard.php?error=access_denied');
    exit;
}

// Pagination and filters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$suspension_filter = isset($_GET['suspension']) ? $_GET['suspension'] : '';

// Build query
$where_conditions = ["u.user_type = 'employer'"];
$params = [];

if ($search) {
    $where_conditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR ep.company_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($status_filter === 'active') {
    $where_conditions[] = "u.is_active = 1 AND (u.is_suspended = 0 OR u.is_suspended IS NULL)";
} elseif ($status_filter === 'inactive') {
    $where_conditions[] = "u.is_active = 0";
}

if ($suspension_filter === 'suspended') {
    $where_conditions[] = "u.is_suspended = 1";
} elseif ($suspension_filter === 'not_suspended') {
    $where_conditions[] = "(u.is_suspended = 0 OR u.is_suspended IS NULL)";
}

$where_sql = implode(' AND ', $where_conditions);

// Get total count
$count_sql = "SELECT COUNT(*) FROM users u LEFT JOIN employer_profiles ep ON u.id = ep.user_id WHERE $where_sql";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $per_page);

// Get employers
$sql = "
    SELECT 
        u.id, u.first_name, u.last_name, u.email, u.phone,
        u.is_active, u.email_verified, u.phone_verified, u.created_at,
        u.is_suspended, u.suspension_reason, u.suspension_expires,
        ep.company_name, ep.company_size, ep.industry,
        ep.company_cac_verified, ep.provider_nin_verified,
        COUNT(DISTINCT j.id) as total_jobs,
        COUNT(DISTINCT CASE WHEN j.status = 'active' THEN j.id END) as active_jobs
    FROM users u
    LEFT JOIN employer_profiles ep ON u.id = ep.user_id
    LEFT JOIN jobs j ON u.id = j.employer_id
    WHERE $where_sql
    GROUP BY u.id
    ORDER BY u.created_at DESC
    LIMIT $per_page OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$employers = $stmt->fetchAll();

$pageTitle = 'Employers Manager';
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
        
        .admin-main { margin-left: 260px; flex: 1; padding: 24px; width: calc(100% - 260px); }
        
        .page-header {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            margin-bottom: 24px;
        }
        
        .page-header h2 { font-size: 24px; color: #1a1a2e; margin-bottom: 8px; }
        
        .filters-bar {
            background: white;
            padding: 20px 24px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            margin-bottom: 24px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .filter-group { flex: 1; min-width: 200px; }
        .filter-group label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
        .form-control { width: 100%; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; }
        
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary { background: linear-gradient(135deg, #dc2626, #991b1b); color: white; }
        
        .content-card { background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); overflow: hidden; }
        
        table { width: 100%; border-collapse: collapse; }
        thead { background: #f9fafb; }
        th { padding: 14px 16px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; }
        td { padding: 14px 16px; border-bottom: 1px solid #f3f4f6; font-size: 14px; }
        tbody tr:hover { background: #f9fafb; }
        
        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        
        .company-logo {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            margin-right: 10px;
        }
        
        .action-btns { display: flex; gap: 6px; }
        .btn-sm { padding: 6px 10px; font-size: 12px; }
        .btn-info { background: #3b82f6; color: white; }
        .btn-danger { background: #ef4444; color: white; }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            padding: 24px;
        }
        
        .pagination a {
            padding: 8px 14px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            text-decoration: none;
            color: #374151;
        }
        
        .pagination a.active { background: #dc2626; color: white; border-color: #dc2626; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="admin-main">
            <div class="page-header">
                <h2><i class="fas fa-building"></i> Employers Manager</h2>
                <p>Manage and monitor all employer accounts</p>
            </div>
            
            <div class="filters-bar">
                <form method="GET" style="display: flex; gap: 12px; flex: 1; flex-wrap: wrap;">
                    <div class="filter-group">
                        <label>Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Company name or email..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $status_filter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Suspension</label>
                        <select name="suspension" class="form-control">
                            <option value="">All</option>
                            <option value="suspended" <?= $suspension_filter === 'suspended' ? 'selected' : '' ?>>🚫 Suspended</option>
                            <option value="not_suspended" <?= $suspension_filter === 'not_suspended' ? 'selected' : '' ?>>✓ Not Suspended</option>
                        </select>
                    </div>
                    
                    <div class="filter-group" style="align-self: flex-end;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="content-card">
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Company</th>
                                <th>Contact Person</th>
                                <th>Email/Phone</th>
                                <th>Industry</th>
                                <th>Size</th>
                                <th>Jobs</th>
                                <th>Verification</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employers as $emp): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center;">
                                            <div class="company-logo">
                                                <?= strtoupper(substr($emp['company_name'] ?? 'C', 0, 2)) ?>
                                            </div>
                                            <div>
                                                <strong><?= htmlspecialchars($emp['company_name'] ?? 'No Company Name') ?></strong>
                                                <br><small style="color: #9ca3af;">#<?= $emp['id'] ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?></td>
                                    <td>
                                        <div><?= htmlspecialchars($emp['email']) ?></div>
                                        <?php if ($emp['phone']): ?>
                                            <small style="color: #6b7280;"><?= htmlspecialchars($emp['phone']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($emp['industry'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($emp['company_size'] ?? '-') ?></td>
                                    <td>
                                        <strong style="color: #dc2626;"><?= $emp['active_jobs'] ?></strong> / <?= $emp['total_jobs'] ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 4px; font-size: 16px;">
                                            <i class="fas fa-envelope" style="color: <?= $emp['email_verified'] ? '#10b981' : '#d1d5db' ?>;" title="Email"></i>
                                            <i class="fas fa-certificate" style="color: <?= $emp['company_cac_verified'] ? '#10b981' : '#d1d5db' ?>;" title="CAC"></i>
                                            <i class="fas fa-id-card" style="color: <?= $emp['provider_nin_verified'] ? '#10b981' : '#d1d5db' ?>;" title="NIN"></i>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($emp['is_suspended']): ?>
                                            <?php
                                            $expired = $emp['suspension_expires'] && strtotime($emp['suspension_expires']) < time();
                                            ?>
                                            <span class="badge badge-danger" style="background: #dc2626; display: flex; align-items: center; gap: 4px; width: fit-content;">
                                                <i class="fas fa-user-slash"></i> Suspended
                                            </span>
                                            <?php if ($emp['suspension_expires']): ?>
                                                <small style="color: <?= $expired ? '#10b981' : '#dc2626' ?>; display: block; margin-top: 4px; font-size: 10px;">
                                                    <?= $expired ? '✓ Expired' : 'Until ' . date('M d, Y', strtotime($emp['suspension_expires'])) ?>
                                                </small>
                                            <?php endif; ?>
                                        <?php elseif ($emp['is_active']): ?>
                                            <span class="badge badge-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><small><?= date('M d, Y', strtotime($emp['created_at'])) ?></small></td>
                                    <td>
                                        <div class="action-btns">
                                            <a href="view-employer.php?id=<?= $emp['id'] ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($emp['is_suspended']): ?>
                                                <button class="btn btn-sm btn-success" onclick="unsuspendAccount(<?= $emp['id'] ?>)" title="Unsuspend">
                                                    <i class="fas fa-user-check"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-danger" onclick="suspendUser(<?= $emp['id'] ?>)">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>" 
                               class="<?= $i == $page ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function suspendUser(userId) {
            if (!confirm('Are you sure you want to suspend this employer?')) return;
            
            fetch('../api/admin-actions.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=suspend_user&user_id=${userId}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Error suspending employer');
                }
            });
        }
        
        function unsuspendAccount(userId) {
            if (!confirm('Are you sure you want to unsuspend this account?')) return;
            
            const formData = new FormData();
            formData.append('action', 'unsuspend_account');
            formData.append('user_id', userId);
            
            fetch('../api/admin-actions.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Account unsuspended successfully');
                    location.reload();
                } else {
                    alert(data.message || 'Failed to unsuspend account');
                }
            })
            .catch(err => {
                console.error(err);
                alert('Network error. Please try again.');
            });
        }
    </script>
</body>
</html>
