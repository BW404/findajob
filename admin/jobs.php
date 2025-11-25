<?php
require_once '../config/database.php';
require_once '../config/session.php';

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

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Build query
$where_conditions = ["1=1"];
$params = [];

if ($search) {
    $where_conditions[] = "(j.title LIKE ? OR ep.company_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($status_filter) {
    $where_conditions[] = "j.STATUS = ?";
    $params[] = $status_filter;
}

if ($category_filter) {
    $where_conditions[] = "j.category_id = ?";
    $params[] = $category_filter;
}

$where_sql = implode(' AND ', $where_conditions);

// Get total count
$count_sql = "SELECT COUNT(*) FROM jobs j LEFT JOIN employer_profiles ep ON j.employer_id = ep.user_id WHERE $where_sql";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $per_page);

// Get jobs
$sql = "
    SELECT 
        j.*,
        ep.company_name,
        u.email as employer_email,
        COUNT(DISTINCT ja.id) as application_count,
        jc.name as category_name
    FROM jobs j
    LEFT JOIN employer_profiles ep ON j.employer_id = ep.user_id
    LEFT JOIN users u ON j.employer_id = u.id
    LEFT JOIN job_applications ja ON j.id = ja.job_id
    LEFT JOIN job_categories jc ON j.category_id = jc.id
    WHERE $where_sql
    GROUP BY j.id
    ORDER BY j.created_at DESC
    LIMIT $per_page OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

// Get categories for filter
$categories = $pdo->query("SELECT id, name FROM job_categories ORDER BY name")->fetchAll();

$pageTitle = 'Jobs Manager';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - FindAJob Admin</title>
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
        
        .admin-main { margin-left: 260px; flex: 1; padding: 24px; }
        
        .page-header { background: white; padding: 24px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); margin-bottom: 24px; }
        .page-header h2 { font-size: 24px; color: #1a1a2e; }
        
        .filters-bar { background: white; padding: 20px 24px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); margin-bottom: 24px; display: flex; gap: 12px; flex-wrap: wrap; }
        .filter-group { flex: 1; min-width: 200px; }
        .filter-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
        .form-control { width: 100%; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px; }
        .btn { padding: 10px 20px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: linear-gradient(135deg, #dc2626, #991b1b); color: white; }
        
        .content-card { background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        thead { background: #f9fafb; }
        th { padding: 14px 16px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; }
        td { padding: 14px 16px; border-bottom: 1px solid #f3f4f6; font-size: 14px; }
        tbody tr:hover { background: #f9fafb; }
        
        .badge { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        
        .action-btns { display: flex; gap: 6px; }
        .btn-sm { padding: 6px 10px; font-size: 12px; }
        .btn-info { background: #3b82f6; color: white; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-success { background: #10b981; color: white; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="admin-main">
            <div class="page-header">
                <h2><i class="fas fa-briefcase"></i> Jobs Manager</h2>
                <p>Manage and moderate all job postings</p>
            </div>
            
            <div class="filters-bar">
                <form method="GET" style="display: flex; gap: 12px; flex: 1; flex-wrap: wrap;">
                    <div class="filter-group">
                        <label>Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Job title or company..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="draft" <?= $status_filter === 'draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="closed" <?= $status_filter === 'closed' ? 'selected' : '' ?>>Closed</option>
                            <option value="expired" <?= $status_filter === 'expired' ? 'selected' : '' ?>>Expired</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Category</label>
                        <select name="category" class="form-control">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $category_filter == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
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
                                <th>Job Title</th>
                                <th>Company</th>
                                <th>Category</th>
                                <th>Location</th>
                                <th>Type</th>
                                <th>Applications</th>
                                <th>Status</th>
                                <th>Posted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($jobs as $job): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($job['title']) ?></strong>
                                        <br><small style="color: #9ca3af;">#<?= $job['id'] ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($job['company_name'] ?? 'Unknown') ?></td>
                                    <td><?= htmlspecialchars($job['category_name'] ?? '-') ?></td>
                                    <td>
                                        <?php 
                                        $location = [];
                                        if (!empty($job['city'])) $location[] = $job['city'];
                                        if (!empty($job['state'])) $location[] = $job['state'];
                                        echo htmlspecialchars(implode(', ', $location) ?: '-');
                                        ?>
                                    </td>
                                    <td><?= htmlspecialchars($job['job_type'] ?? '-') ?></td>
                                    <td style="text-align: center;">
                                        <strong style="color: #dc2626;"><?= $job['application_count'] ?></strong>
                                    </td>
                                    <td>
                                        <?php
                                        $status_badges = [
                                            'active' => 'success',
                                            'draft' => 'warning',
                                            'closed' => 'danger',
                                            'expired' => 'info'
                                        ];
                                        $badge_class = $status_badges[$job['STATUS']] ?? 'info';
                                        ?>
                                        <span class="badge badge-<?= $badge_class ?>"><?= ucfirst($job['STATUS']) ?></span>
                                    </td>
                                    <td><small><?= date('M d, Y', strtotime($job['created_at'])) ?></small></td>
                                    <td>
                                        <div class="action-btns">
                                            <a href="../pages/jobs/details.php?id=<?= $job['id'] ?>" target="_blank" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($job['STATUS'] === 'active'): ?>
                                                <button class="btn btn-sm btn-danger" onclick="closeJob(<?= $job['id'] ?>)">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-success" onclick="activateJob(<?= $job['id'] ?>)">
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
            </div>
        </div>
    </div>
    
    <script>
        function closeJob(jobId) {
            if (!confirm('Close this job posting?')) return;
            updateJobStatus(jobId, 'closed');
        }
        
        function activateJob(jobId) {
            if (!confirm('Activate this job posting?')) return;
            updateJobStatus(jobId, 'active');
        }
        
        function updateJobStatus(jobId, status) {
            fetch('../api/admin-actions.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=update_job_status&job_id=${jobId}&status=${status}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Error updating job status');
                }
            });
        }
    </script>
</body>
</html>
