<?php
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/constants.php';
require_once '../../includes/functions.php';
require_once '../../includes/pro-features.php';

requireJobSeeker();

$userId = getCurrentUserId();

// Get user subscription
$subscription = getUserSubscription($pdo, $userId);
$isPro = $subscription['is_pro'];
$limits = getFeatureLimits($isPro);

// Track daily applications (Pro feature limit)
$today_start = date('Y-m-d 00:00:00');
$today_end = date('Y-m-d 23:59:59');
$todayStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM job_applications 
    WHERE job_seeker_id = ? 
    AND applied_at BETWEEN ? AND ?
");
$todayStmt->execute([$userId, $today_start, $today_end]);
$applications_today = $todayStmt->fetchColumn();

$daily_limit = $limits['applications_per_day'];
$approaching_daily_limit = !$isPro && $applications_today >= ($daily_limit * 0.8);
$daily_limit_reached = !$isPro && $applications_today >= $daily_limit;

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$sort_by = $_GET['sort'] ?? 'newest';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query based on filters
$whereClause = "ja.job_seeker_id = ?";
$params = [$userId];

if ($status_filter) {
    $whereClause .= " AND ja.application_status = ?";
    $params[] = $status_filter;
}

// Determine sort order
$orderBy = match($sort_by) {
    'oldest' => 'ja.applied_at ASC',
    'status' => 'ja.application_status ASC, ja.applied_at DESC',
    'company' => 'j.company_name ASC',
    default => 'ja.applied_at DESC' // newest
};

// Get total count
$countStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM job_applications ja 
    JOIN jobs j ON ja.job_id = j.id 
    WHERE {$whereClause}
");
$countStmt->execute($params);
$total_applications = $countStmt->fetchColumn();
$total_pages = ceil($total_applications / $per_page);

// Get applications with pagination
$stmt = $pdo->prepare("
    SELECT 
        ja.*,
        j.id as job_id,
        j.title,
        j.company_name,
        j.state,
        j.city,
        j.job_type,
        j.salary_min,
        j.salary_max,
        j.status as job_status,
        ja.application_status as status,
        ja.applied_at,
        ja.viewed_at,
        ja.responded_at
    FROM job_applications ja
    JOIN jobs j ON ja.job_id = j.id
    WHERE {$whereClause}
    ORDER BY {$orderBy}
    LIMIT {$per_page} OFFSET {$offset}
");
$stmt->execute($params);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats = [
    'total' => $total_applications,
    'applied' => 0,
    'viewed' => 0,
    'shortlisted' => 0,
    'interviewed' => 0,
    'offered' => 0,
    'hired' => 0,
    'rejected' => 0
];

$statsStmt = $pdo->prepare("
    SELECT application_status, COUNT(*) as count
    FROM job_applications
    WHERE job_seeker_id = ?
    GROUP BY application_status
");
$statsStmt->execute([$userId]);
$statResults = $statsStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($statResults as $stat) {
    $stats[$stat['application_status']] = $stat['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#dc2626">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="FindAJob NG">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="../../manifest.json">
    
    <!-- App Icons -->
    <link rel="icon" type="image/svg+xml" href="../../assets/images/icons/icon-192x192.svg">
    <link rel="apple-touch-icon" href="../../assets/images/icons/icon-192x192.svg">
    
    <style>
        .applications-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: var(--text-secondary);
            font-size: 1rem;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-box {
            background: var(--surface);
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            border-left: 4px solid var(--primary);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-box:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .stat-box.applied { border-left-color: #f59e0b; }
        .stat-box.viewed { border-left-color: #3b82f6; }
        .stat-box.shortlisted { border-left-color: #8b5cf6; }
        .stat-box.interviewed { border-left-color: #06b6d4; }
        .stat-box.offered { border-left-color: #10b981; }
        .stat-box.hired { border-left-color: #10b981; }
        .stat-box.rejected { border-left-color: #ef4444; }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.85rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filters-bar {
            background: var(--surface);
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-group label {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .filter-select {
            padding: 0.5rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .applications-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .application-card {
            background: var(--surface);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid var(--border-color);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .application-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .application-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .application-title {
            flex: 1;
        }

        .application-title h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0 0 0.5rem 0;
            color: var(--text-primary);
        }

        .application-title h3 a {
            color: var(--text-primary);
            text-decoration: none;
        }

        .application-title h3 a:hover {
            color: var(--primary);
        }

        .company-name {
            font-size: 1rem;
            color: var(--text-secondary);
            margin: 0;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-badge.applied { background: #fef3c7; color: #92400e; }
        .status-badge.viewed { background: #dbeafe; color: #1e40af; }
        .status-badge.shortlisted { background: #ede9fe; color: #5b21b6; }
        .status-badge.interviewed { background: #cffafe; color: #155e75; }
        .status-badge.offered { background: #d1fae5; color: #065f46; }
        .status-badge.hired { background: #d1fae5; color: #065f46; }
        .status-badge.rejected { background: #fee2e2; color: #991b1b; }

        .application-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            padding: 1rem;
            background: var(--background);
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .detail-icon {
            color: var(--primary);
        }

        .application-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
        }

        .application-meta {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .application-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--surface);
            border-radius: 12px;
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .pagination a,
        .pagination span {
            padding: 0.5rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            text-decoration: none;
            color: var(--text-primary);
        }

        .pagination a:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .pagination .active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        @media (max-width: 768px) {
            .applications-container {
                padding: 1rem;
                padding-bottom: 100px; /* Space for bottom nav */
            }

            .application-header {
                flex-direction: column;
                gap: 1rem;
            }

            .application-details {
                grid-template-columns: 1fr;
            }

            .application-footer {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }

            .filters-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-group {
                width: 100%;
            }

            .filter-select {
                width: 100%;
            }

            body.has-bottom-nav {
                padding-bottom: 80px;
            }
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>

    <div class="applications-container">
        <div class="page-header">
            <h1>My Applications</h1>
            <p>Track and manage all your job applications in one place</p>
        </div>

        <!-- Pro Feature Limits Warning -->
        <?php if (!$isPro): ?>
            <!-- Daily Limit Reached Warning -->
            <?php if ($daily_limit_reached): ?>
                <div style="background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); color: white; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                        <i class="fas fa-lock" style="font-size: 2rem;"></i>
                        <div style="flex: 1;">
                            <h3 style="margin: 0 0 0.5rem 0; font-size: 1.25rem; font-weight: 700;">🔒 Daily Application Limit Reached</h3>
                            <p style="margin: 0; opacity: 0.95;">You've reached the maximum of <?php echo $daily_limit; ?> applications per day on the Basic plan. Try again tomorrow or upgrade to Pro for unlimited applications.</p>
                        </div>
                    </div>
                    <a href="../payment/plans.php" class="btn" style="background: white; color: #dc2626; padding: 0.75rem 2rem; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-block;">
                        <i class="fas fa-crown"></i> Upgrade to Pro
                    </a>
                </div>
            <!-- Approaching Limit Warning -->
            <?php elseif ($approaching_daily_limit): ?>
                <div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 1.5rem;"></i>
                        <div style="flex: 1;">
                            <p style="margin: 0; font-weight: 600;">⚠️ Almost at your daily application limit: <?php echo $applications_today; ?>/<?php echo $daily_limit; ?> applications today</p>
                            <p style="margin: 0.5rem 0 0 0; opacity: 0.95; font-size: 0.9rem;">Upgrade to Pro for unlimited applications per day and more features!</p>
                        </div>
                        <a href="../payment/plans.php" class="btn" style="background: white; color: #f59e0b; padding: 0.5rem 1.5rem; border-radius: 8px; font-weight: 600; text-decoration: none; white-space: nowrap;">
                            <i class="fas fa-crown"></i> Go Pro
                        </a>
                    </div>
                </div>
            <!-- Normal Counter -->
            <?php else: ?>
                <div style="background: #f3f4f6; padding: 1rem 1.5rem; border-radius: 8px; margin-bottom: 2rem; border-left: 4px solid #6b7280;">
                    <p style="margin: 0; color: #4b5563; font-weight: 600;">
                        📋 <?php echo $applications_today; ?>/<?php echo $daily_limit; ?> applications today
                        <a href="../payment/plans.php" style="color: #dc2626; text-decoration: none; margin-left: 1rem;">
                            <i class="fas fa-crown"></i> Upgrade for unlimited
                        </a>
                    </p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <!-- Pro Badge -->
            <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 1rem 1.5rem; border-radius: 8px; margin-bottom: 2rem; display: flex; align-items: center; gap: 0.75rem; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);">
                <i class="fas fa-crown" style="font-size: 1.25rem;"></i>
                <p style="margin: 0; font-weight: 600;">👑 Pro - Unlimited Applications Per Day</p>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-row">
            <div class="stat-box">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total</div>
            </div>
            <div class="stat-box applied">
                <div class="stat-number"><?php echo $stats['applied']; ?></div>
                <div class="stat-label">Applied</div>
            </div>
            <div class="stat-box viewed">
                <div class="stat-number"><?php echo $stats['viewed']; ?></div>
                <div class="stat-label">Viewed</div>
            </div>
            <div class="stat-box shortlisted">
                <div class="stat-number"><?php echo $stats['shortlisted']; ?></div>
                <div class="stat-label">Shortlisted</div>
            </div>
            <div class="stat-box interviewed">
                <div class="stat-number"><?php echo $stats['interviewed']; ?></div>
                <div class="stat-label">Interviewed</div>
            </div>
            <div class="stat-box hired">
                <div class="stat-number"><?php echo $stats['hired']; ?></div>
                <div class="stat-label">Hired</div>
            </div>
            <div class="stat-box rejected">
                <div class="stat-number"><?php echo $stats['rejected']; ?></div>
                <div class="stat-label">Rejected</div>
            </div>
        </div>

        <!-- Filters -->
        <form method="GET" class="filters-bar">
            <div class="filter-group">
                <label for="status">Status:</label>
                <select name="status" id="status" class="filter-select" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="applied" <?php echo $status_filter === 'applied' ? 'selected' : ''; ?>>Applied</option>
                    <option value="viewed" <?php echo $status_filter === 'viewed' ? 'selected' : ''; ?>>Viewed</option>
                    <option value="shortlisted" <?php echo $status_filter === 'shortlisted' ? 'selected' : ''; ?>>Shortlisted</option>
                    <option value="interviewed" <?php echo $status_filter === 'interviewed' ? 'selected' : ''; ?>>Interviewed</option>
                    <option value="offered" <?php echo $status_filter === 'offered' ? 'selected' : ''; ?>>Offered</option>
                    <option value="hired" <?php echo $status_filter === 'hired' ? 'selected' : ''; ?>>Hired</option>
                    <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="sort">Sort by:</label>
                <select name="sort" id="sort" class="filter-select" onchange="this.form.submit()">
                    <option value="newest" <?php echo $sort_by === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                    <option value="oldest" <?php echo $sort_by === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                    <option value="status" <?php echo $sort_by === 'status' ? 'selected' : ''; ?>>By Status</option>
                    <option value="company" <?php echo $sort_by === 'company' ? 'selected' : ''; ?>>By Company</option>
                </select>
            </div>

            <?php if ($status_filter || $sort_by !== 'newest'): ?>
                <a href="applications.php" class="btn btn-outline btn-sm">Clear Filters</a>
            <?php endif; ?>
        </form>

        <!-- Applications List -->
        <?php if (count($applications) > 0): ?>
            <div class="applications-list">
                <?php foreach ($applications as $app): ?>
                    <div class="application-card">
                        <div class="application-header">
                            <div class="application-title">
                                <h3>
                                    <a href="../jobs/details.php?id=<?php echo $app['job_id']; ?>">
                                        <?php echo htmlspecialchars($app['title']); ?>
                                    </a>
                                </h3>
                                <p class="company-name">
                                    <i class="fas fa-building"></i> <?php echo htmlspecialchars($app['company_name']); ?>
                                </p>
                            </div>
                            <span class="status-badge <?php echo strtolower($app['status']); ?>">
                                <?php echo ucfirst($app['status']); ?>
                            </span>
                        </div>

                        <div class="application-details">
                            <div class="detail-item">
                                <i class="fas fa-map-marker-alt detail-icon"></i>
                                <span>
                                    <?php 
                                    $location = [];
                                    if (!empty($app['city'])) $location[] = $app['city'];
                                    if (!empty($app['state'])) $location[] = $app['state'];
                                    echo htmlspecialchars(implode(', ', $location) ?: 'Not specified'); 
                                    ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-briefcase detail-icon"></i>
                                <span><?php echo ucfirst($app['job_type'] ?? 'Full-time'); ?></span>
                            </div>
                            <?php if ($app['salary_min'] && $app['salary_max']): ?>
                                <div class="detail-item">
                                    <i class="fas fa-money-bill-wave detail-icon"></i>
                                    <span>₦<?php echo number_format($app['salary_min']); ?> - ₦<?php echo number_format($app['salary_max']); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="detail-item">
                                <i class="fas fa-clock detail-icon"></i>
                                <span>Applied <?php echo timeAgo($app['applied_at']); ?></span>
                            </div>
                        </div>

                        <div class="application-footer">
                            <div class="application-meta">
                                <?php if ($app['viewed_at']): ?>
                                    <i class="fas fa-eye"></i> Viewed <?php echo timeAgo($app['viewed_at']); ?>
                                <?php elseif ($app['responded_at']): ?>
                                    <i class="fas fa-reply"></i> Response <?php echo timeAgo($app['responded_at']); ?>
                                <?php else: ?>
                                    <i class="fas fa-hourglass-half"></i> Awaiting review
                                <?php endif; ?>
                            </div>
                            <div class="application-actions">
                                <a href="../jobs/details.php?id=<?php echo $app['job_id']; ?>" class="btn btn-outline btn-sm">
                                    <i class="fas fa-eye"></i> View Job
                                </a>
                                <button class="btn btn-outline btn-sm report-trigger" 
                                        data-entity-type="job" 
                                        data-entity-id="<?php echo $app['job_id']; ?>" 
                                        data-entity-name="<?php echo htmlspecialchars($app['job_title']); ?>" 
                                        title="Report this job"
                                        style="color: #dc2626; border-color: #dc2626;">
                                    <i class="fas fa-flag"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $sort_by !== 'newest' ? '&sort=' . $sort_by : ''; ?>">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <?php if ($i === $page): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $sort_by !== 'newest' ? '&sort=' . $sort_by : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $sort_by !== 'newest' ? '&sort=' . $sort_by : ''; ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">📋</div>
                <h3>No Applications Yet</h3>
                <p>
                    <?php if ($status_filter): ?>
                        No applications found with status "<?php echo ucfirst($status_filter); ?>".
                        <br><a href="applications.php">View all applications</a>
                    <?php else: ?>
                        You haven't applied to any jobs yet. Start your job search today!
                    <?php endif; ?>
                </p>
                <a href="../jobs/browse.php" class="btn btn-primary">
                    <i class="fas fa-search"></i> Browse Jobs
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../../includes/footer.php'; ?>

    <!-- Bottom Navigation for Mobile (PWA) -->
    <nav class="app-bottom-nav">
        <a href="../../index.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">🏠</div>
            <div class="app-bottom-nav-label">Home</div>
        </a>
        <a href="../jobs/browse.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">🔍</div>
            <div class="app-bottom-nav-label">Jobs</div>
        </a>
        <a href="saved-jobs.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">❤️</div>
            <div class="app-bottom-nav-label">Saved</div>
        </a>
        <a href="applications.php" class="app-bottom-nav-item active">
            <div class="app-bottom-nav-icon">📋</div>
            <div class="app-bottom-nav-label">Applications</div>
        </a>
        <a href="dashboard.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">👤</div>
            <div class="app-bottom-nav-label">Profile</div>
        </a>
    </nav>

    <!-- PWA Scripts -->
    <script src="../../assets/js/pwa.js"></script>
    <script>
        // Initialize PWA features
        if ('PWAManager' in window) {
            const pwa = new PWAManager();
            pwa.init();
        }

        // Add body class for bottom nav
        document.body.classList.add('has-bottom-nav');
    </script>

</body>
</html>
