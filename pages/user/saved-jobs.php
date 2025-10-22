<?php
require_once '../../config/database.php';
require_once '../../config/session.php';

requireJobSeeker();

$user_id = getCurrentUserId();

// Get filter parameters
$sort_by = $_GET['sort'] ?? 'newest';
$search_query = $_GET['search'] ?? '';

// Build query
$query = "SELECT j.*, 
          sj.saved_at,
          COUNT(DISTINCT ja.id) as application_count
          FROM saved_jobs sj
          INNER JOIN jobs j ON sj.job_id = j.id
          LEFT JOIN job_applications ja ON j.id = ja.job_id
          WHERE sj.user_id = ?";

$params = [$user_id];

if (!empty($search_query)) {
    $query .= " AND (j.title LIKE ? OR j.description LIKE ? OR j.company_name LIKE ?)";
    $search_param = "%{$search_query}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$query .= " GROUP BY j.id";

// Add sorting
switch ($sort_by) {
    case 'oldest':
        $query .= " ORDER BY sj.saved_at ASC";
        break;
    case 'title':
        $query .= " ORDER BY j.title ASC";
        break;
    default: // newest
        $query .= " ORDER BY sj.saved_at DESC";
}

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $saved_jobs = $stmt->fetchAll();
} catch (PDOException $e) {
    // Handle case where saved_jobs table doesn't exist yet
    $saved_jobs = [];
}

// Get count
try {
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM saved_jobs WHERE user_id = ?");
    $count_stmt->execute([$user_id]);
    $total_saved = $count_stmt->fetchColumn();
} catch (PDOException $e) {
    $total_saved = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved Jobs - FindAJob Nigeria</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body.has-bottom-nav {
            padding-bottom: 92px !important;
        }
        
        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .container {
                padding: 1rem 0.5rem 2rem !important;
            }
            
            /* Page Header */
            h1 {
                font-size: 1.75rem !important;
            }
            
            h1 .fas {
                font-size: 1.5rem !important;
            }
            
            /* Search and Filters Container */
            .saved-jobs-container {
                padding: 1.5rem 1rem !important;
            }
            
            /* Search Form */
            form {
                grid-template-columns: 1fr !important;
                gap: 1rem !important;
            }
            
            form > div {
                width: 100%;
            }
            
            form button {
                width: 100%;
                padding: 0.875rem !important;
            }
            
            /* Job Cards */
            .job-card {
                padding: 1.5rem 1rem !important;
                border-radius: 12px !important;
            }
            
            .job-card-header {
                grid-template-columns: 1fr !important;
                gap: 1rem !important;
            }
            
            /* Job Info */
            .job-info h3 {
                font-size: 1.25rem !important;
                padding-right: 3rem !important;
            }
            
            /* Unsave Button - Mobile Positioning */
            .unsave-btn {
                top: 1rem !important;
                right: 1rem !important;
                font-size: 1.75rem !important;
                padding: 0.5rem !important;
                background: white !important;
                border-radius: 50% !important;
                width: 3rem !important;
                height: 3rem !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                box-shadow: 0 2px 8px rgba(0,0,0,0.15) !important;
            }
            
            /* Job Status - Move Below Title on Mobile */
            .job-status {
                text-align: left !important;
                margin-top: 1rem;
            }
            
            .status-badge {
                display: inline-block !important;
                font-size: 0.75rem !important;
                padding: 0.4rem 0.75rem !important;
            }
            
            .saved-date {
                margin-top: 0.5rem;
                display: inline-block;
                font-size: 0.8rem !important;
            }
            
            /* Job Meta */
            .job-meta {
                flex-wrap: wrap !important;
                gap: 0.75rem !important;
                font-size: 0.85rem !important;
            }
            
            .job-meta span {
                flex: 0 0 auto;
            }
            
            /* Job Description */
            .job-description {
                font-size: 0.9rem !important;
                margin-bottom: 1.25rem !important;
            }
            
            /* Action Buttons */
            .job-actions {
                flex-direction: column !important;
                gap: 0.75rem !important;
                margin-top: 1rem;
            }
            
            .job-actions a {
                width: 100% !important;
                flex: none !important;
                padding: 0.875rem 1rem !important;
                font-size: 1rem !important;
            }
            
            /* Empty State */
            .empty-state {
                padding: 3rem 1rem !important;
            }
            
            .empty-state .fas {
                font-size: 3rem !important;
            }
            
            .empty-state h3 {
                font-size: 1.25rem !important;
            }
        }
        
        @media (max-width: 480px) {
            h1 {
                font-size: 1.5rem !important;
            }
            
            .job-card {
                padding: 1.25rem 0.875rem !important;
            }
            
            .job-info h3 {
                font-size: 1.1rem !important;
            }
            
            .job-meta {
                font-size: 0.8rem !important;
            }
            
            .unsave-btn {
                font-size: 1.5rem !important;
                width: 2.5rem !important;
                height: 2.5rem !important;
            }
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>

    <main class="container" style="padding: 2rem 0 3rem;">
        <!-- Page Header -->
        <div style="margin-bottom: 2rem;">
            <h1 style="margin: 0 0 0.5rem 0; font-size: 2.5rem; font-weight: 700; color: var(--text-primary);">
                <i class="fas fa-heart" style="color: var(--primary); margin-right: 0.5rem;"></i>
                Saved Jobs
            </h1>
            <p style="margin: 0; color: var(--text-secondary); font-size: 1.1rem;">
                <?php echo $total_saved; ?> job<?php echo $total_saved !== 1 ? 's' : ''; ?> saved for later
            </p>
        </div>

        <!-- Search and Filters -->
        <div style="background: var(--surface); padding: 1.5rem; border-radius: 16px; margin-bottom: 2rem; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
            <form method="GET" action="" style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 1rem; align-items: end;">
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-primary);">
                        <i class="fas fa-search"></i> Search Saved Jobs
                    </label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" 
                           placeholder="Search by title, company, or description..." 
                           style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: 8px; font-size: 1rem;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-primary);">
                        <i class="fas fa-sort"></i> Sort By
                    </label>
                    <select name="sort" style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: 8px; font-size: 1rem;">
                        <option value="newest" <?php echo $sort_by === 'newest' ? 'selected' : ''; ?>>Recently Saved</option>
                        <option value="oldest" <?php echo $sort_by === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                        <option value="title" <?php echo $sort_by === 'title' ? 'selected' : ''; ?>>Title (A-Z)</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2rem;">
                    <i class="fas fa-search"></i> Search
                </button>
            </form>
        </div>

        <!-- Saved Jobs List -->
        <div class="saved-jobs-container" style="background: var(--surface); padding: 2.5rem; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
            <?php if (empty($saved_jobs)): ?>
                <div class="empty-state" style="text-align: center; padding: 4rem 2rem; color: var(--text-secondary);">
                    <i class="fas fa-heart-broken" style="font-size: 4rem; margin-bottom: 1.5rem; opacity: 0.3;"></i>
                    <h3 style="margin: 0 0 1rem 0; color: var(--text-primary);">No saved jobs yet</h3>
                    <p style="margin: 0 0 1.5rem 0;">
                        <?php if (!empty($search_query)): ?>
                            No saved jobs match your search. Try adjusting your search terms.
                        <?php else: ?>
                            Start saving jobs you're interested in by clicking the heart icon <i class="fas fa-heart" style="color: var(--primary);"></i> on job listings.
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($search_query)): ?>
                        <a href="saved-jobs.php" class="btn btn-outline">Clear Search</a>
                    <?php else: ?>
                        <a href="../jobs/browse.php" class="btn btn-primary">Browse Jobs</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="jobs-grid" style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <?php foreach ($saved_jobs as $job): ?>
                        <?php
                        // Build location
                        $location = '';
                        if (!empty($job['city']) && !empty($job['state'])) {
                            $location = $job['city'] . ', ' . $job['state'];
                        } elseif (!empty($job['city'])) {
                            $location = $job['city'];
                        } elseif (!empty($job['state'])) {
                            $location = $job['state'];
                        }
                        
                        // Status color
                        $statusColors = [
                            'active' => '#059669',
                            'inactive' => '#f59e0b',
                            'closed' => '#ef4444',
                            'paused' => '#6b7280',
                            'draft' => '#6b7280',
                            'expired' => '#6b7280'
                        ];
                        $statusColor = $statusColors[$job['STATUS'] ?? 'draft'] ?? '#6b7280';
                        ?>
                        <div class="job-card" style="
                            border: 2px solid var(--border-color); 
                            border-radius: 12px; 
                            padding: 2rem; 
                            transition: all 0.3s ease;
                            position: relative;
                        " onmouseover="this.style.borderColor='var(--primary)'; this.style.boxShadow='0 8px 20px rgba(220,38,38,0.1)';" 
                           onmouseout="this.style.borderColor='var(--border-color)'; this.style.boxShadow='none';">
                            
                            <!-- Unsave Button -->
                            <button onclick="unsaveJob(<?php echo $job['id']; ?>)" 
                                    class="unsave-btn"
                                    style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; cursor: pointer; font-size: 1.5rem; color: var(--primary); transition: transform 0.2s; z-index: 10;"
                                    onmouseover="this.style.transform='scale(1.2)'"
                                    onmouseout="this.style.transform='scale(1)'"
                                    title="Remove from saved jobs">
                                <i class="fas fa-heart"></i>
                            </button>

                            <div class="job-card-header" style="display: grid; grid-template-columns: 1fr auto; gap: 2rem; margin-bottom: 1rem; margin-top: 0.5rem;">
                                <div class="job-info">
                                    <h3 style="margin: 0 0 0.5rem 0; font-size: 1.5rem; font-weight: 700; padding-right: 3rem;">
                                        <a href="../jobs/details.php?id=<?php echo $job['id']; ?>" 
                                           style="color: var(--text-primary); text-decoration: none;">
                                            <?php echo htmlspecialchars($job['title']); ?>
                                        </a>
                                    </h3>
                                    <?php if (!empty($job['company_name'])): ?>
                                        <div style="color: var(--text-secondary); font-size: 1rem; margin-bottom: 0.5rem;">
                                            <i class="fas fa-building"></i> <?php echo htmlspecialchars($job['company_name']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="job-meta" style="display: flex; gap: 1.5rem; flex-wrap: wrap; color: var(--text-secondary); font-size: 0.95rem;">
                                        <?php if (!empty($location)): ?>
                                            <span><i class="fas fa-map-marker-alt" style="color: var(--primary); margin-right: 0.5rem;"></i>
                                                <?php echo htmlspecialchars($location); ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($job['job_type']): ?>
                                            <span><i class="fas fa-briefcase" style="color: var(--primary); margin-right: 0.5rem;"></i>
                                                <?php echo htmlspecialchars($job['job_type']); ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($job['salary_min'] && $job['salary_max']): ?>
                                            <span><i class="fas fa-money-bill-wave" style="color: var(--primary); margin-right: 0.5rem;"></i>
                                                ₦<?php echo number_format($job['salary_min']); ?> - ₦<?php echo number_format($job['salary_max']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="job-status" style="text-align: right;">
                                    <div style="margin-bottom: 0.5rem;">
                                        <span class="status-badge" style="background: <?php echo $statusColor; ?>; color: white; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                                            <?php echo ucfirst($job['STATUS'] ?? 'draft'); ?>
                                        </span>
                                    </div>
                                    <div class="saved-date" style="font-size: 0.85rem; color: var(--text-secondary);">
                                        Saved: <?php echo date('M j, Y', strtotime($job['saved_at'])); ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Job Description Preview -->
                            <?php if (!empty($job['description'])): ?>
                                <div class="job-description" style="color: var(--text-secondary); font-size: 0.95rem; line-height: 1.6; margin-bottom: 1.5rem;">
                                    <?php 
                                    $desc = strip_tags($job['description']);
                                    echo htmlspecialchars(substr($desc, 0, 200));
                                    if (strlen($desc) > 200) echo '...';
                                    ?>
                                </div>
                            <?php endif; ?>

                            <!-- Action Buttons -->
                            <div class="job-actions" style="display: flex; gap: 1rem; flex-wrap: wrap;">
                                <a href="../jobs/details.php?id=<?php echo $job['id']; ?>" 
                                   class="btn btn-primary" style="flex: 1; text-align: center;">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                                <a href="../jobs/apply.php?job_id=<?php echo $job['id']; ?>" 
                                   class="btn btn-outline" style="flex: 1; text-align: center;">
                                    <i class="fas fa-paper-plane"></i> Apply Now
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include '../../includes/footer.php'; ?>

    <!-- PWA Bottom Navigation -->
    <nav class="app-bottom-nav">
        <a href="../../index.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">🏠</div>
            <div class="app-bottom-nav-label">Home</div>
        </a>
        <a href="../jobs/browse.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">🔍</div>
            <div class="app-bottom-nav-label">Jobs</div>
        </a>
        <a href="saved-jobs.php" class="app-bottom-nav-item active">
            <div class="app-bottom-nav-icon">❤️</div>
            <div class="app-bottom-nav-label">Saved</div>
        </a>
        <a href="applications.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">📋</div>
            <div class="app-bottom-nav-label">Applications</div>
        </a>
        <a href="dashboard.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">👤</div>
            <div class="app-bottom-nav-label">Profile</div>
        </a>
    </nav>

    <script>
        document.body.classList.add('has-bottom-nav');

        function unsaveJob(jobId) {
            if (!confirm('Remove this job from your saved list?')) {
                return;
            }
            
            fetch('../../api/jobs.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=unsave&job_id=' + jobId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to unsave job'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }
    </script>
</body>
</html>
