<?php
require_once '../../config/database.php';
require_once '../../config/session.php';

requireEmployer();

$userId = getCurrentUserId();

// Get user data for header
$stmt = $pdo->prepare("SELECT u.*, ep.* FROM users u LEFT JOIN employer_profiles ep ON u.id = ep.user_id WHERE u.id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Check if employer has Pro subscription
$isPro = ($user['subscription_type'] === 'pro' && 
          (!$user['subscription_end'] || strtotime($user['subscription_end']) > time()));

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$job_filter = $_GET['job_id'] ?? 'all';
$sort_by = $_GET['sort'] ?? 'newest';
$search_query = $_GET['search'] ?? '';

// Build query with filters
$query = "SELECT ja.*, 
          j.title as job_title, 
          j.id as job_id,
          u.first_name, u.last_name, u.email, u.phone,
          jsp.skills, jsp.years_of_experience
          FROM job_applications ja
          INNER JOIN jobs j ON ja.job_id = j.id
          INNER JOIN users u ON ja.job_seeker_id = u.id
          LEFT JOIN job_seeker_profiles jsp ON u.id = jsp.user_id
          WHERE j.employer_id = ?";

$params = [$userId];

if ($status_filter !== 'all') {
    $query .= " AND ja.application_status = ?";
    $params[] = $status_filter;
}

if ($job_filter !== 'all') {
    $query .= " AND ja.job_id = ?";
    $params[] = $job_filter;
}

if (!empty($search_query)) {
    $query .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR j.title LIKE ?)";
    $search_param = "%{$search_query}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

// Add sorting
switch ($sort_by) {
    case 'oldest':
        $query .= " ORDER BY ja.applied_at ASC";
        break;
    case 'job':
        $query .= " ORDER BY j.title ASC, ja.applied_at DESC";
        break;
    case 'name':
        $query .= " ORDER BY u.first_name ASC, u.last_name ASC";
        break;
    default: // newest
        $query .= " ORDER BY ja.applied_at DESC";
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$applications = $stmt->fetchAll();

// Get statistics
$stats_query = "SELECT 
                COUNT(DISTINCT ja.id) as total_applications,
                COUNT(DISTINCT CASE WHEN ja.application_status = 'applied' THEN ja.id END) as new_applications,
                COUNT(DISTINCT CASE WHEN ja.application_status = 'viewed' THEN ja.id END) as viewed,
                COUNT(DISTINCT CASE WHEN ja.application_status = 'shortlisted' THEN ja.id END) as shortlisted,
                COUNT(DISTINCT CASE WHEN ja.application_status = 'interviewed' THEN ja.id END) as interviewed,
                COUNT(DISTINCT CASE WHEN ja.application_status = 'offered' THEN ja.id END) as offered,
                COUNT(DISTINCT CASE WHEN ja.application_status = 'hired' THEN ja.id END) as hired,
                COUNT(DISTINCT CASE WHEN ja.application_status = 'rejected' THEN ja.id END) as rejected
                FROM job_applications ja
                INNER JOIN jobs j ON ja.job_id = j.id
                WHERE j.employer_id = ?";
$stats_stmt = $pdo->prepare($stats_query);
$stats_stmt->execute([$userId]);
$stats = $stats_stmt->fetch();

// Get jobs list for filter dropdown
$jobs_query = "SELECT j.id, j.title, COUNT(ja.id) as app_count 
               FROM jobs j
               LEFT JOIN job_applications ja ON j.id = ja.job_id
               WHERE j.employer_id = ?
               GROUP BY j.id
               ORDER BY j.created_at DESC";
$jobs_stmt = $pdo->prepare($jobs_query);
$jobs_stmt->execute([$userId]);
$jobs = $jobs_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Applications - FindAJob Nigeria</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="has-bottom-nav">
    <?php include '../../includes/employer-header.php'; ?>
    </header>

    <main class="container" style="padding: 3rem 0;">
        <!-- Page Header -->
        <div style="margin-bottom: 2rem;">
            <h1 style="margin: 0 0 0.5rem 0; font-size: 2.5rem; font-weight: 700; color: var(--text-primary);">
                <i class="fas fa-file-alt" style="color: var(--primary); margin-right: 0.5rem;"></i>
                All Applications
            </h1>
            <p style="margin: 0; color: var(--text-secondary); font-size: 1.1rem;">
                Review and manage all job applications across your postings
            </p>
        </div>

        <!-- Statistics Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
            <div style="background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); color: white; padding: 1.5rem; border-radius: 12px; text-align: center;">
                <div style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;"><?php echo $stats['total_applications']; ?></div>
                <div style="font-size: 0.85rem; opacity: 0.9;">Total</div>
            </div>
            <div style="background: linear-gradient(135deg, #059669 0%, #047857 100%); color: white; padding: 1.5rem; border-radius: 12px; text-align: center;">
                <div style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;"><?php echo $stats['new_applications']; ?></div>
                <div style="font-size: 0.85rem; opacity: 0.9;">New</div>
            </div>
            <div style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: white; padding: 1.5rem; border-radius: 12px; text-align: center;">
                <div style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;"><?php echo $stats['shortlisted']; ?></div>
                <div style="font-size: 0.85rem; opacity: 0.9;">Shortlisted</div>
            </div>
            <div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 1.5rem; border-radius: 12px; text-align: center;">
                <div style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;"><?php echo $stats['interviewed']; ?></div>
                <div style="font-size: 0.85rem; opacity: 0.9;">Interviewed</div>
            </div>
            <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 1.5rem; border-radius: 12px; text-align: center;">
                <div style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;"><?php echo $stats['hired']; ?></div>
                <div style="font-size: 0.85rem; opacity: 0.9;">Hired</div>
            </div>
        </div>

        <!-- Filters and Search -->
        <div style="background: var(--surface); padding: 2rem; border-radius: 16px; margin-bottom: 2rem; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
            <form method="GET" action="" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 1rem; align-items: end;">
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-primary);">
                        <i class="fas fa-search"></i> Search Applicants
                    </label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" 
                           placeholder="Search by name, email, or job title..." 
                           style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: 8px; font-size: 1rem;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-primary);">
                        <i class="fas fa-briefcase"></i> Job
                    </label>
                    <select name="job_id" style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: 8px; font-size: 1rem;">
                        <option value="all" <?php echo $job_filter === 'all' ? 'selected' : ''; ?>>All Jobs</option>
                        <?php foreach ($jobs as $job): ?>
                            <option value="<?php echo $job['id']; ?>" <?php echo $job_filter == $job['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($job['title']); ?> (<?php echo $job['app_count']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-primary);">
                        <i class="fas fa-filter"></i> Status
                    </label>
                    <select name="status" style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: 8px; font-size: 1rem;">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="applied" <?php echo $status_filter === 'applied' ? 'selected' : ''; ?>>New (<?php echo $stats['new_applications']; ?>)</option>
                        <option value="viewed" <?php echo $status_filter === 'viewed' ? 'selected' : ''; ?>>Viewed (<?php echo $stats['viewed']; ?>)</option>
                        <option value="shortlisted" <?php echo $status_filter === 'shortlisted' ? 'selected' : ''; ?>>Shortlisted (<?php echo $stats['shortlisted']; ?>)</option>
                        <option value="interviewed" <?php echo $status_filter === 'interviewed' ? 'selected' : ''; ?>>Interviewed (<?php echo $stats['interviewed']; ?>)</option>
                        <option value="offered" <?php echo $status_filter === 'offered' ? 'selected' : ''; ?>>Offered (<?php echo $stats['offered']; ?>)</option>
                        <option value="hired" <?php echo $status_filter === 'hired' ? 'selected' : ''; ?>>Hired (<?php echo $stats['hired']; ?>)</option>
                        <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected (<?php echo $stats['rejected']; ?>)</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-primary);">
                        <i class="fas fa-sort"></i> Sort By
                    </label>
                    <select name="sort" style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: 8px; font-size: 1rem;">
                        <option value="newest" <?php echo $sort_by === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="oldest" <?php echo $sort_by === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                        <option value="job" <?php echo $sort_by === 'job' ? 'selected' : ''; ?>>By Job</option>
                        <option value="name" <?php echo $sort_by === 'name' ? 'selected' : ''; ?>>By Name</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2rem;">
                    <i class="fas fa-search"></i> Filter
                </button>
            </form>
        </div>

        <!-- Applications List -->
        <div style="background: var(--surface); padding: 2.5rem; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
            <?php if (empty($applications)): ?>
                <div style="text-align: center; padding: 4rem 2rem; color: var(--text-secondary);">
                    <i class="fas fa-inbox" style="font-size: 4rem; margin-bottom: 1.5rem; opacity: 0.3;"></i>
                    <h3 style="margin: 0 0 1rem 0; color: var(--text-primary);">No applications found</h3>
                    <p style="margin: 0 0 1.5rem 0;">
                        <?php if (!empty($search_query) || $status_filter !== 'all' || $job_filter !== 'all'): ?>
                            Try adjusting your filters or search terms
                        <?php else: ?>
                            Applications will appear here once candidates start applying to your jobs
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($search_query) || $status_filter !== 'all' || $job_filter !== 'all'): ?>
                        <a href="all-applications.php" class="btn btn-outline">Clear Filters</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div style="margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid var(--border-color);">
                    <p style="margin: 0; color: var(--text-secondary);">
                        <i class="fas fa-info-circle"></i> Showing <strong><?php echo count($applications); ?></strong> 
                        application<?php echo count($applications) !== 1 ? 's' : ''; ?>
                    </p>
                </div>

                <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <?php foreach ($applications as $app): ?>
                        <?php
                        $statusColors = [
                            'applied' => ['bg' => '#059669', 'text' => 'white', 'icon' => 'fa-file-alt'],
                            'viewed' => ['bg' => '#6366f1', 'text' => 'white', 'icon' => 'fa-eye'],
                            'shortlisted' => ['bg' => '#f59e0b', 'text' => 'white', 'icon' => 'fa-star'],
                            'interviewed' => ['bg' => '#8b5cf6', 'text' => 'white', 'icon' => 'fa-comments'],
                            'offered' => ['bg' => '#10b981', 'text' => 'white', 'icon' => 'fa-handshake'],
                            'hired' => ['bg' => '#059669', 'text' => 'white', 'icon' => 'fa-check-circle'],
                            'rejected' => ['bg' => '#ef4444', 'text' => 'white', 'icon' => 'fa-times-circle']
                        ];
                        $statusStyle = $statusColors[$app['application_status']] ?? ['bg' => '#6b7280', 'text' => 'white', 'icon' => 'fa-question'];
                        ?>
                        <div style="
                            border: 2px solid var(--border-color); 
                            border-radius: 12px; 
                            padding: 2rem; 
                            transition: all 0.3s ease;
                        " onmouseover="this.style.borderColor='var(--primary)'; this.style.boxShadow='0 8px 20px rgba(220,38,38,0.1)';" 
                           onmouseout="this.style.borderColor='var(--border-color)'; this.style.boxShadow='none';">
                            
                            <div style="display: grid; grid-template-columns: 1fr auto; gap: 2rem; margin-bottom: 1.5rem;">
                                <div>
                                    <h3 style="margin: 0 0 0.5rem 0; font-size: 1.4rem; font-weight: 700; color: var(--text-primary);">
                                        <?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?>
                                    </h3>
                                    <div style="color: var(--text-secondary); margin-bottom: 1rem;">
                                        Applied for: <strong style="color: var(--primary);">
                                            <a href="../jobs/details.php?id=<?php echo $app['job_id']; ?>" style="color: var(--primary); text-decoration: none;">
                                                <?php echo htmlspecialchars($app['job_title']); ?>
                                            </a>
                                        </strong>
                                    </div>
                                    <div style="display: flex; gap: 2rem; flex-wrap: wrap; color: var(--text-secondary); font-size: 0.95rem;">
                                        <span><i class="fas fa-envelope" style="color: var(--primary); margin-right: 0.5rem;"></i>
                                            <?php echo htmlspecialchars($app['email']); ?>
                                        </span>
                                        <?php if ($app['phone']): ?>
                                            <span><i class="fas fa-phone" style="color: var(--primary); margin-right: 0.5rem;"></i>
                                                <?php echo htmlspecialchars($app['phone']); ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($app['years_of_experience'])): ?>
                                            <span><i class="fas fa-briefcase" style="color: var(--primary); margin-right: 0.5rem;"></i>
                                                <?php echo htmlspecialchars($app['years_of_experience']); ?> years exp.
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="margin-bottom: 1rem;">
                                        <span style="
                                            background: <?php echo $statusStyle['bg']; ?>; 
                                            color: <?php echo $statusStyle['text']; ?>; 
                                            padding: 0.5rem 1rem; 
                                            border-radius: 20px; 
                                            font-size: 0.85rem; 
                                            font-weight: 600;
                                            display: inline-flex;
                                            align-items: center;
                                            gap: 0.5rem;
                                        ">
                                            <i class="fas <?php echo $statusStyle['icon']; ?>"></i>
                                            <?php echo ucfirst($app['application_status']); ?>
                                        </span>
                                    </div>
                                    <div style="font-size: 0.85rem; color: var(--text-secondary);">
                                        Applied: <?php echo date('M j, Y', strtotime($app['applied_at'])); ?>
                                    </div>
                                </div>
                            </div>

                            <?php if ($app['cover_letter']): ?>
                                <div style="background: rgba(99,102,241,0.05); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border-left: 3px solid var(--primary);">
                                    <div style="font-weight: 600; margin-bottom: 0.5rem; color: var(--text-primary);">
                                        <i class="fas fa-file-alt"></i> Cover Letter:
                                    </div>
                                    <div style="color: var(--text-secondary); font-size: 0.95rem; line-height: 1.6;">
                                        <?php echo nl2br(htmlspecialchars(substr($app['cover_letter'], 0, 200))); ?>
                                        <?php if (strlen($app['cover_letter']) > 200): ?>
                                            <span style="color: var(--text-muted);">...</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Action Buttons -->
                            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                                <a href="applicants.php?id=<?php echo $app['id']; ?>" 
                                   class="btn btn-primary" style="flex: 1; text-align: center;">
                                    <i class="fas fa-eye"></i> View Full Application
                                </a>
                                <?php if ($app['cv_id']): ?>
                                    <a href="../user/cv-download.php?id=<?php echo $app['cv_id']; ?>" 
                                       class="btn btn-outline" style="flex: 1; text-align: center;" target="_blank">
                                        <i class="fas fa-download"></i> Download CV
                                    </a>
                                <?php endif; ?>
                                <a href="mailto:<?php echo htmlspecialchars($app['email']); ?>" 
                                   class="btn btn-outline" style="flex: 1; text-align: center;">
                                    <i class="fas fa-envelope"></i> Contact
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include '../../includes/footer.php'; ?>
</body>
</html>
