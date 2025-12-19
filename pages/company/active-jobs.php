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
$sort_by = $_GET['sort'] ?? 'newest';
$search_query = $_GET['search'] ?? '';

// Build query with filters - use city and state columns from jobs table
$query = "SELECT j.*, 
          j.STATUS as status,
          COUNT(DISTINCT ja.id) as application_count,
          SUM(CASE WHEN ja.application_status = 'applied' THEN 1 ELSE 0 END) as new_applications
          FROM jobs j
          LEFT JOIN job_applications ja ON j.id = ja.job_id
          WHERE j.employer_id = ?";

$params = [$userId];

if ($status_filter !== 'all') {
    $query .= " AND j.status = ?";
    $params[] = $status_filter;
}

if (!empty($search_query)) {
    $query .= " AND (j.title LIKE ? OR j.description LIKE ?)";
    $search_param = "%{$search_query}%";
    $params[] = $search_param;
    $params[] = $search_param;
}

$query .= " GROUP BY j.id";

// Add sorting
switch ($sort_by) {
    case 'oldest':
        $query .= " ORDER BY j.created_at ASC";
        break;
    case 'applications':
        $query .= " ORDER BY application_count DESC";
        break;
    case 'title':
        $query .= " ORDER BY j.title ASC";
        break;
    default: // newest
        $query .= " ORDER BY j.created_at DESC";
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

// Get statistics
$stats_query = "SELECT 
                COUNT(DISTINCT j.id) as total_jobs,
                COUNT(DISTINCT CASE WHEN j.STATUS = 'active' THEN j.id END) as active_jobs,
                COUNT(DISTINCT CASE WHEN j.STATUS = 'inactive' THEN j.id END) as inactive_jobs,
                COUNT(DISTINCT CASE WHEN j.STATUS = 'draft' THEN j.id END) as draft_jobs,
                COUNT(DISTINCT ja.id) as total_applications
                FROM jobs j
                LEFT JOIN job_applications ja ON j.id = ja.job_id
                WHERE j.employer_id = ?";
$stats_stmt = $pdo->prepare($stats_query);
$stats_stmt->execute([$userId]);
$stats = $stats_stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Jobs - FindAJob Nigeria</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="has-bottom-nav">
    <?php include '../../includes/employer-header.php'; ?>

    <main class="container" style="padding: 3rem 0;">
        <!-- Page Header -->
        <div style="margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <div>
                    <h1 style="margin: 0 0 0.5rem 0; font-size: 2.5rem; font-weight: 700; color: var(--text-primary);">
                        <i class="fas fa-briefcase" style="color: var(--primary); margin-right: 0.5rem;"></i>
                        Active Jobs
                    </h1>
                    <p style="margin: 0; color: var(--text-secondary); font-size: 1.1rem;">
                        Manage all your job postings in one place
                    </p>
                </div>
                <a href="post-job.php" class="btn btn-primary" style="font-size: 1.1rem; padding: 0.75rem 1.5rem;">
                    <i class="fas fa-plus"></i> Post New Job
                </a>
            </div>
        </div>

        <!-- Statistics Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
            <div style="background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); color: white; padding: 1.5rem; border-radius: 12px; text-align: center;">
                <div style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;"><?php echo $stats['total_jobs']; ?></div>
                <div style="font-size: 0.9rem; opacity: 0.9;">Total Jobs</div>
            </div>
            <div style="background: linear-gradient(135deg, #059669 0%, #047857 100%); color: white; padding: 1.5rem; border-radius: 12px; text-align: center;">
                <div style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;"><?php echo $stats['active_jobs']; ?></div>
                <div style="font-size: 0.9rem; opacity: 0.9;">Active</div>
            </div>
            <div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 1.5rem; border-radius: 12px; text-align: center;">
                <div style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;"><?php echo $stats['inactive_jobs']; ?></div>
                <div style="font-size: 0.9rem; opacity: 0.9;">Inactive</div>
            </div>
            <div style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: white; padding: 1.5rem; border-radius: 12px; text-align: center;">
                <div style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;"><?php echo $stats['draft_jobs']; ?></div>
                <div style="font-size: 0.9rem; opacity: 0.9;">Drafts</div>
            </div>
        </div>

        <!-- Filters and Search -->
        <div style="background: var(--surface); padding: 2rem; border-radius: 16px; margin-bottom: 2rem; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
            <form method="GET" action="" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 1rem; align-items: end;">
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-primary);">
                        <i class="fas fa-search"></i> Search Jobs
                    </label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" 
                           placeholder="Search by title or description..." 
                           style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: 8px; font-size: 1rem;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-primary);">
                        <i class="fas fa-filter"></i> Status
                    </label>
                    <select name="status" style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: 8px; font-size: 1rem;">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="draft" <?php echo $status_filter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-primary);">
                        <i class="fas fa-sort"></i> Sort By
                    </label>
                    <select name="sort" style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: 8px; font-size: 1rem;">
                        <option value="newest" <?php echo $sort_by === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="oldest" <?php echo $sort_by === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                        <option value="applications" <?php echo $sort_by === 'applications' ? 'selected' : ''; ?>>Most Applications</option>
                        <option value="title" <?php echo $sort_by === 'title' ? 'selected' : ''; ?>>Title (A-Z)</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2rem;">
                    <i class="fas fa-search"></i> Filter
                </button>
            </form>
        </div>

        <!-- Jobs List -->
        <div style="background: var(--surface); padding: 2.5rem; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
            <?php if (empty($jobs)): ?>
                <div style="text-align: center; padding: 4rem 2rem; color: var(--text-secondary);">
                    <i class="fas fa-briefcase" style="font-size: 4rem; margin-bottom: 1.5rem; opacity: 0.3;"></i>
                    <h3 style="margin: 0 0 1rem 0; color: var(--text-primary);">No jobs found</h3>
                    <p style="margin: 0 0 1.5rem 0;">
                        <?php if (!empty($search_query) || $status_filter !== 'all'): ?>
                            Try adjusting your filters or search terms
                        <?php else: ?>
                            Start by posting your first job to attract qualified candidates
                        <?php endif; ?>
                    </p>
                    <?php if (empty($search_query) && $status_filter === 'all'): ?>
                        <a href="post-job.php" class="btn btn-primary">Post Your First Job</a>
                    <?php else: ?>
                        <a href="active-jobs.php" class="btn btn-outline">Clear Filters</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <?php foreach ($jobs as $job): ?>
                        <div style="
                            border: 2px solid var(--border-color); 
                            border-radius: 12px; 
                            padding: 2rem; 
                            transition: all 0.3s ease;
                            position: relative;
                        " onmouseover="this.style.borderColor='var(--primary)'; this.style.boxShadow='0 8px 20px rgba(220,38,38,0.1)';" 
                           onmouseout="this.style.borderColor='var(--border-color)'; this.style.boxShadow='none';">
                            
                            <!-- Status Badge -->
                            <?php
                            $job_status = $job['status'] ?? $job['STATUS'] ?? 'draft'; // Handle both lowercase and uppercase
                            $statusColors = [
                                'active' => ['bg' => '#059669', 'text' => 'white'],
                                'inactive' => ['bg' => '#f59e0b', 'text' => 'white'],
                                'paused' => ['bg' => '#f59e0b', 'text' => 'white'],
                                'closed' => ['bg' => '#ef4444', 'text' => 'white'],
                                'expired' => ['bg' => '#6b7280', 'text' => 'white'],
                                'draft' => ['bg' => '#6b7280', 'text' => 'white']
                            ];
                            $statusStyle = $statusColors[$job_status] ?? ['bg' => '#6b7280', 'text' => 'white'];
                            ?>
                            <div style="position: absolute; top: 1rem; right: 1rem;">
                                <span style="
                                    background: <?php echo $statusStyle['bg']; ?>; 
                                    color: <?php echo $statusStyle['text']; ?>; 
                                    padding: 0.5rem 1rem; 
                                    border-radius: 20px; 
                                    font-size: 0.85rem; 
                                    font-weight: 600;
                                ">
                                    <?php echo ucfirst($job_status); ?>
                                </span>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr auto; gap: 2rem; margin-bottom: 1.5rem;">
                                <div>
                                    <h3 style="margin: 0 0 1rem 0; font-size: 1.5rem; font-weight: 700;">
                                        <a href="../jobs/details.php?id=<?php echo $job['id']; ?>" 
                                           style="color: var(--text-primary); text-decoration: none;">
                                            <?php echo htmlspecialchars($job['title']); ?>
                                        </a>
                                    </h3>
                                    <div style="display: flex; gap: 2rem; flex-wrap: wrap; color: var(--text-secondary); font-size: 0.95rem;">
                                        <?php 
                                        // Build location from city and state columns
                                        $location = trim(($job['city'] ?? '') . (($job['city'] && $job['state']) ? ', ' : '') . ($job['state'] ?? ''));
                                        if (!empty($location)):
                                        ?>
                                        <span><i class="fas fa-map-marker-alt" style="color: var(--primary); margin-right: 0.5rem;"></i>
                                            <?php echo htmlspecialchars($location); ?>
                                        </span>
                                        <?php endif; ?>
                                        <?php if ($job['job_type']): ?>
                                            <span><i class="fas fa-briefcase" style="color: var(--primary); margin-right: 0.5rem;"></i>
                                                <?php echo htmlspecialchars($job['job_type']); ?>
                                            </span>
                                        <?php endif; ?>
                                        <span><i class="fas fa-calendar" style="color: var(--primary); margin-right: 0.5rem;"></i>
                                            Posted <?php echo date('M j, Y', strtotime($job['created_at'])); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Stats Row -->
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color);">
                                <div style="text-align: center; padding: 1rem; background: rgba(220,38,38,0.05); border-radius: 8px;">
                                    <div style="font-size: 1.75rem; font-weight: 700; color: var(--primary); margin-bottom: 0.25rem;">
                                        <?php echo $job['application_count']; ?>
                                    </div>
                                    <div style="font-size: 0.85rem; color: var(--text-secondary);">Total Applications</div>
                                </div>
                                <div style="text-align: center; padding: 1rem; background: rgba(5,150,105,0.05); border-radius: 8px;">
                                    <div style="font-size: 1.75rem; font-weight: 700; color: #059669; margin-bottom: 0.25rem;">
                                        <?php echo $job['new_applications']; ?>
                                    </div>
                                    <div style="font-size: 0.85rem; color: var(--text-secondary);">New Applications</div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                                <a href="../jobs/details.php?id=<?php echo $job['id']; ?>" 
                                   class="btn btn-outline" style="flex: 1; text-align: center;">
                                    <i class="fas fa-eye"></i> View Job
                                </a>
                                <a href="applicants.php?job_id=<?php echo $job['id']; ?>" 
                                   class="btn btn-primary" style="flex: 1; text-align: center;">
                                    <i class="fas fa-users"></i> View Applicants (<?php echo $job['application_count']; ?>)
                                </a>
                                <a href="post-job.php?edit=<?php echo $job['id']; ?>" 
                                   class="btn btn-outline" style="flex: 1; text-align: center;">
                                    <i class="fas fa-edit"></i> Edit
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
