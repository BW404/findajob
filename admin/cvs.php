<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/permissions.php';

// Check if user is admin
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Check if user has admin role (from users table with user_type)
$user_id = getCurrentUserId();
$stmt = $pdo->prepare("SELECT user_type FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user || $user['user_type'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Check permission
if (!hasPermission($user_id, 'view_cvs')) {
    header('Location: dashboard.php?error=access_denied');
    exit;
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$format_filter = isset($_GET['format']) ? $_GET['format'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';

// Build query
$where_conditions = ["1=1"];
$params = [];

if ($search) {
    $where_conditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR cv.file_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($format_filter) {
    $where_conditions[] = "cv.file_type = ?";
    $params[] = $format_filter;
}

if ($date_filter) {
    switch ($date_filter) {
        case 'today':
            $where_conditions[] = "DATE(cv.uploaded_at) = CURDATE()";
            break;
        case 'week':
            $where_conditions[] = "cv.uploaded_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $where_conditions[] = "cv.uploaded_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
    }
}

$where_sql = implode(' AND ', $where_conditions);

try {
    // Get total count
    $count_sql = "SELECT COUNT(*) FROM cvs cv 
                  LEFT JOIN users u ON cv.user_id = u.id 
                  WHERE $where_sql";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_records = $stmt->fetchColumn();
    $total_pages = ceil($total_records / $per_page);

    // Get CVs
    $sql = "
        SELECT 
            cv.*,
            u.first_name,
            u.last_name,
            u.email,
            u.phone,
            jsp.job_status,
            COUNT(DISTINCT ja.id) as applications_count
        FROM cvs cv
        LEFT JOIN users u ON cv.user_id = u.id
        LEFT JOIN job_seeker_profiles jsp ON u.id = jsp.user_id
        LEFT JOIN job_applications ja ON u.id = ja.job_seeker_id
        WHERE $where_sql
        GROUP BY cv.id
        ORDER BY cv.uploaded_at DESC
        LIMIT $per_page OFFSET $offset
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $cvs = $stmt->fetchAll();

    // Get statistics
    $stats_sql = "
        SELECT 
            COUNT(*) as total_cvs,
            COUNT(DISTINCT user_id) as total_users,
            SUM(CASE WHEN DATE(uploaded_at) = CURDATE() THEN 1 ELSE 0 END) as today_uploads,
            SUM(CASE WHEN file_type = 'application/pdf' THEN 1 ELSE 0 END) as pdf_count,
            SUM(file_size) as total_size
        FROM cvs
    ";
    $stats = $pdo->query($stats_sql)->fetch();

} catch (Exception $e) {
    error_log("CV Manager Error: " . $e->getMessage());
    $cvs = [];
    $total_pages = 0;
    $stats = [
        'total_cvs' => 0,
        'total_users' => 0,
        'today_uploads' => 0,
        'pdf_count' => 0,
        'total_size' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CV Manager - FindAJob Admin</title>
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
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 28px;
            color: #1a1a2e;
            margin-bottom: 5px;
        }

        .page-header p {
            color: #6b7280;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
            font-size: 28px;
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
        .stat-card.info .stat-value { color: #3b82f6; }
        .stat-card.warning .stat-value { color: #f59e0b; }

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

        .filter-group input,
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

        /* Table */
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f9fafb;
            padding: 15px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e5e7eb;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 14px;
            color: #1f2937;
        }

        tr:hover {
            background: #f9fafb;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 16px;
        }

        .user-details .name {
            font-weight: 600;
            color: #1a1a2e;
        }

        .user-details .email {
            font-size: 12px;
            color: #6b7280;
        }

        .file-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .file-icon {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .file-icon.pdf {
            background: #fee2e2;
            color: #dc2626;
        }

        .file-icon.doc {
            background: #dbeafe;
            color: #2563eb;
        }

        .file-details .filename {
            font-weight: 500;
            color: #1a1a2e;
            font-size: 13px;
        }

        .file-details .filesize {
            font-size: 11px;
            color: #6b7280;
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

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }

        /* Action Buttons */
        .action-btns {
            display: flex;
            gap: 8px;
        }

        .btn {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-sm {
            padding: 6px 10px;
            font-size: 12px;
        }

        .btn-primary {
            background: #dc2626;
            color: white;
        }

        .btn-primary:hover {
            background: #b91c1c;
        }

        .btn-info {
            background: #3b82f6;
            color: white;
        }

        .btn-info:hover {
            background: #2563eb;
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 20px;
            padding: 20px;
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
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.3;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1>CV Manager</h1>
            <p>Browse, download, and manage uploaded CVs</p>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-value"><?= number_format($stats['total_cvs']) ?></div>
                <div class="stat-label">Total CVs</div>
            </div>
            <div class="stat-card success">
                <div class="stat-value"><?= number_format($stats['total_users']) ?></div>
                <div class="stat-label">Users with CVs</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-value"><?= number_format($stats['today_uploads']) ?></div>
                <div class="stat-label">Uploaded Today</div>
            </div>
            <div class="stat-card info">
                <div class="stat-value"><?= round($stats['total_size'] / 1024 / 1024, 1) ?> MB</div>
                <div class="stat-label">Total Storage</div>
            </div>
        </div>

        <!-- Filters -->
        <form method="GET" class="filters-bar">
            <div class="filter-group">
                <label>Search</label>
                <input type="text" name="search" placeholder="Name, email, or filename..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="filter-group">
                <label>Format</label>
                <select name="format">
                    <option value="">All Formats</option>
                    <option value="application/pdf" <?= $format_filter === 'application/pdf' ? 'selected' : '' ?>>PDF</option>
                    <option value="application/msword" <?= $format_filter === 'application/msword' ? 'selected' : '' ?>>Word Doc</option>
                    <option value="application/vnd.openxmlformats-officedocument.wordprocessingml.document" <?= $format_filter === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' ? 'selected' : '' ?>>Word Docx</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Upload Date</label>
                <select name="date">
                    <option value="">All Time</option>
                    <option value="today" <?= $date_filter === 'today' ? 'selected' : '' ?>>Today</option>
                    <option value="week" <?= $date_filter === 'week' ? 'selected' : '' ?>>Last 7 Days</option>
                    <option value="month" <?= $date_filter === 'month' ? 'selected' : '' ?>>Last 30 Days</option>
                </select>
            </div>
            <div class="filter-group">
                <button type="submit"><i class="fas fa-filter"></i> Apply Filters</button>
            </div>
        </form>

        <!-- CVs Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>CV File</th>
                        <th>Job Status</th>
                        <th>Applications</th>
                        <th>Uploaded</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cvs)): ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <i class="fas fa-file-alt"></i>
                                    <p>No CVs found</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($cvs as $cv): ?>
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <?= strtoupper(substr($cv['first_name'], 0, 1)) ?>
                                        </div>
                                        <div class="user-details">
                                            <div class="name"><?= htmlspecialchars($cv['first_name'] . ' ' . $cv['last_name']) ?></div>
                                            <div class="email"><?= htmlspecialchars($cv['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="file-info">
                                        <div class="file-icon <?= strpos($cv['file_type'], 'pdf') !== false ? 'pdf' : 'doc' ?>">
                                            <i class="fas fa-file-<?= strpos($cv['file_type'], 'pdf') !== false ? 'pdf' : 'word' ?>"></i>
                                        </div>
                                        <div class="file-details">
                                            <div class="filename"><?= htmlspecialchars($cv['file_name']) ?></div>
                                            <div class="filesize"><?= round($cv['file_size'] / 1024, 1) ?> KB</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($cv['job_status']): ?>
                                        <?php
                                        $status_class = [
                                            'looking' => 'success',
                                            'not_looking' => 'warning',
                                            'employed_but_looking' => 'info'
                                        ][$cv['job_status']] ?? 'info';
                                        ?>
                                        <span class="badge badge-<?= $status_class ?>">
                                            <?= ucwords(str_replace('_', ' ', $cv['job_status'])) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Not Set</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $cv['applications_count'] ?> applications</td>
                                <td><small><?= date('M d, Y', strtotime($cv['uploaded_at'])) ?></small></td>
                                <td>
                                    <div class="action-btns">
                                        <a href="../<?= htmlspecialchars($cv['file_path']) ?>" target="_blank" class="btn btn-sm btn-info" title="View CV">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="../<?= htmlspecialchars($cv['file_path']) ?>" download class="btn btn-sm btn-success" title="Download CV">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <button class="btn btn-sm btn-danger" onclick="deleteCV(<?= $cv['id'] ?>)" title="Delete CV">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $format_filter ? '&format=' . urlencode($format_filter) : '' ?><?= $date_filter ? '&date=' . urlencode($date_filter) : '' ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="active"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $format_filter ? '&format=' . urlencode($format_filter) : '' ?><?= $date_filter ? '&date=' . urlencode($date_filter) : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $format_filter ? '&format=' . urlencode($format_filter) : '' ?><?= $date_filter ? '&date=' . urlencode($date_filter) : '' ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function deleteCV(cvId) {
            if (!confirm('Are you sure you want to delete this CV? This action cannot be undone.')) {
                return;
            }

            fetch('../api/admin-actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'delete_cv',
                    cv_id: cvId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('CV deleted successfully');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to delete CV'));
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
            });
        }
    </script>
</body>
</html>
