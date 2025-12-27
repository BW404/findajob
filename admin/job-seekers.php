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
if (!hasPermission($user_id, 'view_job_seekers')) {
    header('Location: dashboard.php?error=access_denied');
    exit;
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Search and filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$verification_filter = isset($_GET['verification']) ? $_GET['verification'] : '';
$suspension_filter = isset($_GET['suspension']) ? $_GET['suspension'] : '';

// Build query
$where_conditions = ["u.user_type = 'job_seeker'"];
$params = [];

if ($search) {
    $where_conditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
    $search_param = "%$search%";
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

if ($verification_filter === 'verified') {
    $where_conditions[] = "u.email_verified = 1";
} elseif ($verification_filter === 'unverified') {
    $where_conditions[] = "u.email_verified = 0";
}

$where_sql = implode(' AND ', $where_conditions);

// Get total count
$count_sql = "SELECT COUNT(*) FROM users u WHERE $where_sql";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $per_page);

// Get job seekers with profile data
$sql = "
    SELECT 
        u.id, u.first_name, u.last_name, u.email, u.phone, 
        u.is_active, u.email_verified, u.phone_verified, u.created_at,
        u.subscription_plan, u.subscription_status, u.subscription_type, u.subscription_end,
        u.is_suspended, u.suspension_reason, u.suspension_expires,
        jsp.job_status, jsp.education_level, jsp.years_of_experience,
        jsp.nin_verified, jsp.verification_status,
        jsp.profile_boosted, jsp.profile_boost_until,
        COUNT(DISTINCT ja.id) as application_count,
        COUNT(DISTINCT cv.id) as cv_count
    FROM users u
    LEFT JOIN job_seeker_profiles jsp ON u.id = jsp.user_id
    LEFT JOIN job_applications ja ON u.id = ja.job_seeker_id
    LEFT JOIN cvs cv ON u.id = cv.user_id
    WHERE $where_sql
    GROUP BY u.id
    ORDER BY u.created_at DESC
    LIMIT $per_page OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$job_seekers = $stmt->fetchAll();

$pageTitle = 'Job Seekers Manager';
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
        .page-header p { color: #6b7280; font-size: 14px; }
        
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
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #dc2626, #991b1b);
            color: white;
        }
        
        .content-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            padding: 20px 24px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .stat-box {
            text-align: center;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #dc2626;
            margin-bottom: 4px;
        }
        
        .stat-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        table { width: 100%; border-collapse: collapse; }
        thead { background: #f9fafb; border-bottom: 2px solid #e5e7eb; }
        th {
            padding: 14px 16px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
        }
        td {
            padding: 14px 16px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 14px;
        }
        tbody tr:hover { background: #f9fafb; }
        
        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        
        .action-btns { display: flex; gap: 6px; }
        .btn-sm { padding: 6px 10px; font-size: 12px; }
        .btn-info { background: #3b82f6; color: white; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-success { background: #10b981; color: white; }
        
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
            font-size: 14px;
        }
        
        .pagination a.active {
            background: #dc2626;
            color: white;
            border-color: #dc2626;
        }
        
        .pagination a:hover:not(.active) {
            background: #f3f4f6;
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #dc2626, #991b1b);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 13px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="admin-main">
            <div class="page-header">
                <h2><i class="fas fa-users"></i> Job Seekers Manager</h2>
                <p>Manage and monitor all job seeker accounts</p>
            </div>
            
            <!-- Filters -->
            <div class="filters-bar">
                <form method="GET" style="display: flex; gap: 12px; flex: 1; flex-wrap: wrap;">
                    <div class="filter-group">
                        <label>Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Name or email..." value="<?= htmlspecialchars($search) ?>">
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
                        <label>Verification</label>
                        <select name="verification" class="form-control">
                            <option value="">All</option>
                            <option value="verified" <?= $verification_filter === 'verified' ? 'selected' : '' ?>>Email Verified</option>
                            <option value="unverified" <?= $verification_filter === 'unverified' ? 'selected' : '' ?>>Unverified</option>
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
            
            <!-- Content -->
            <div class="content-card">
                <div class="stats-row">
                    <div class="stat-box">
                        <div class="stat-value"><?= number_format($total_records) ?></div>
                        <div class="stat-label">Total Job Seekers</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value"><?= $page ?>/<?= $total_pages ?></div>
                        <div class="stat-label">Current Page</div>
                    </div>
                </div>
                
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Contact</th>
                                <th>Subscription Plan</th>
                                <th>Job Status</th>
                                <th>Experience</th>
                                <th>Applications</th>
                                <th>CVs</th>
                                <th>Verification</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($job_seekers as $js): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center;">
                                            <div class="user-avatar">
                                                <?= strtoupper(substr($js['first_name'], 0, 1) . substr($js['last_name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <strong><?= htmlspecialchars($js['first_name'] . ' ' . $js['last_name']) ?></strong>
                                                <br><small style="color: #9ca3af;">#<?= $js['id'] ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div><?= htmlspecialchars($js['email']) ?></div>
                                        <?php if ($js['phone']): ?>
                                            <small style="color: #6b7280;"><?= htmlspecialchars($js['phone']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $sub_plan = $js['subscription_plan'] ?? 'basic';
                                        $sub_status = $js['subscription_status'] ?? 'free';
                                        $sub_type = $js['subscription_type'] ?? '';
                                        $sub_end = $js['subscription_end'] ?? null;
                                        $is_pro = strpos($sub_plan, 'pro') !== false && $sub_status === 'active';
                                        
                                        // Check profile boost
                                        $profile_boosted = $js['profile_boosted'] ?? 0;
                                        $profile_boost_until = $js['profile_boost_until'] ?? null;
                                        $boost_active = false;
                                        if ($profile_boost_until) {
                                            $boost_date = new DateTime($profile_boost_until);
                                            $boost_active = $boost_date > new DateTime();
                                        }
                                        ?>
                                        <div style="min-width: 140px;">
                                            <?php if ($is_pro): ?>
                                                <span class="badge badge-success" style="background: linear-gradient(135deg, #059669 0%, #047857 100%); padding: 4px 8px; display: inline-block; margin-bottom: 4px;">
                                                    👑 Pro <?= ucfirst($sub_type) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary" style="padding: 4px 8px; display: inline-block; margin-bottom: 4px;">Basic Plan</span>
                                            <?php endif; ?>
                                            <?php if ($boost_active): ?>
                                                <br><span class="badge" style="background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); color: white; padding: 3px 6px; font-size: 11px; display: inline-block;">🚀 Profile Boosted</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($is_pro && $sub_end): 
                                            $end_date = new DateTime($sub_end);
                                            $now = new DateTime();
                                            $is_expired = $end_date <= $now;
                                        ?>
                                            <small style="color: <?= $is_expired ? '#dc2626' : '#059669' ?>; display: block; margin-top: 4px; font-size: 11px;">
                                                <?php if ($is_expired): ?>
                                                    ⚠️ Expired <?= date('M d, Y', strtotime($sub_end)) ?>
                                                <?php else: 
                                                    $diff = $now->diff($end_date);
                                                ?>
                                                    📅 Until <?= date('M d, Y', strtotime($sub_end)) ?> (<?= $diff->days ?>d)
                                                <?php endif; ?>
                                            </small>
                                        <?php endif; ?>
                                        <?php if ($boost_active): ?>
                                            <small style="color: #7c3aed; display: block; margin-top: 2px; font-size: 11px;">
                                                🚀 Until <?= date('M d, Y', strtotime($profile_boost_until)) ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $status_badges = [
                                            'looking' => ['class' => 'success', 'text' => 'Looking'],
                                            'not_looking' => ['class' => 'danger', 'text' => 'Not Looking'],
                                            'employed_but_looking' => ['class' => 'info', 'text' => 'Employed']
                                        ];
                                        $status = $status_badges[$js['job_status']] ?? ['class' => 'warning', 'text' => 'Unknown'];
                                        ?>
                                        <span class="badge badge-<?= $status['class'] ?>"><?= $status['text'] ?></span>
                                    </td>
                                    <td>
                                        <?php if ($js['education_level']): ?>
                                            <div><?= strtoupper($js['education_level']) ?></div>
                                        <?php endif; ?>
                                        <small style="color: #6b7280;"><?= $js['years_of_experience'] ?? 0 ?> years</small>
                                    </td>
                                    <td style="text-align: center;">
                                        <strong style="color: #dc2626;"><?= $js['application_count'] ?></strong>
                                    </td>
                                    <td style="text-align: center;">
                                        <strong style="color: #3b82f6;"><?= $js['cv_count'] ?></strong>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 4px; font-size: 16px;">
                                            <i class="fas fa-envelope" style="color: <?= $js['email_verified'] ? '#10b981' : '#d1d5db' ?>;" title="Email"></i>
                                            <i class="fas fa-phone" style="color: <?= $js['phone_verified'] ? '#10b981' : '#d1d5db' ?>;" title="Phone"></i>
                                            <i class="fas fa-id-card" style="color: <?= $js['nin_verified'] ? '#10b981' : '#d1d5db' ?>;" title="NIN"></i>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($js['is_suspended']): ?>
                                            <?php
                                            $expired = $js['suspension_expires'] && strtotime($js['suspension_expires']) < time();
                                            ?>
                                            <span class="badge badge-danger" style="background: #dc2626; display: flex; align-items: center; gap: 4px; width: fit-content;">
                                                <i class="fas fa-user-slash"></i> Suspended
                                            </span>
                                            <?php if ($js['suspension_expires']): ?>
                                                <small style="color: <?= $expired ? '#10b981' : '#dc2626' ?>; display: block; margin-top: 4px; font-size: 10px;">
                                                    <?= $expired ? '✓ Expired' : 'Until ' . date('M d, Y', strtotime($js['suspension_expires'])) ?>
                                                </small>
                                            <?php endif; ?>
                                        <?php elseif ($js['is_active']): ?>
                                            <span class="badge badge-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?= date('M d, Y', strtotime($js['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <a href="view-job-seeker.php?id=<?= $js['id'] ?>" class="btn btn-sm btn-info" title="View Profile" target="_blank">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($js['is_suspended']): ?>
                                                <button class="btn btn-sm btn-success" onclick="unsuspendAccount(<?= $js['id'] ?>)" title="Unsuspend">
                                                    <i class="fas fa-user-check"></i>
                                                </button>
                                            <?php elseif ($js['is_active']): ?>
                                                <button class="btn btn-sm btn-danger" onclick="toggleUserStatus(<?= $js['id'] ?>, 0)" title="Suspend">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-success" onclick="toggleUserStatus(<?= $js['id'] ?>, 1)" title="Activate">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>&verification=<?= $verification_filter ?>">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>&verification=<?= $verification_filter ?>" 
                               class="<?= $i == $page ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>&verification=<?= $verification_filter ?>">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function toggleUserStatus(userId, newStatus) {
            const action = newStatus === 1 ? 'activate' : 'suspend';
            const confirmMsg = newStatus === 1 ? 
                'Are you sure you want to activate this user?' : 
                'Are you sure you want to suspend this user?';
            
            if (!confirm(confirmMsg)) return;
            
            // Implement toggle status functionality
            fetch('../api/admin-actions.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=${action}_user&user_id=${userId}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Error updating user status');
                }
            })
            .catch(err => {
                console.error(err);
                alert('Network error. Please try again.');
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
            });
        }
    </script>
</body>
</html>
