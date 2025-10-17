<?php
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/constants.php';

requireEmployer();

$userId = getCurrentUserId();

// Get employer's jobs with application counts
$stmt = $pdo->prepare("
    SELECT j.*, 
           COALESCE(app_count.count, 0) as application_count,
           j.state as state_name,
           j.city as lga_name
    FROM jobs j 
    LEFT JOIN (
        SELECT job_id, COUNT(*) as count 
        FROM job_applications 
        GROUP BY job_id
    ) app_count ON j.id = app_count.job_id
    WHERE j.employer_id = ?
    ORDER BY j.created_at DESC
");
$stmt->execute([$userId]);
$jobs = $stmt->fetchAll();

// Handle job actions (activate, deactivate, delete)
if ($_POST && isset($_POST['action']) && isset($_POST['job_id'])) {
    $jobId = (int)$_POST['job_id'];
    $action = $_POST['action'];
    
    // Verify job belongs to current employer
    $stmt = $pdo->prepare("SELECT id FROM jobs WHERE id = ? AND employer_id = ?");
    $stmt->execute([$jobId, $userId]);
    
    if ($stmt->fetch()) {
        switch ($action) {
            case 'activate':
                $stmt = $pdo->prepare("UPDATE jobs SET STATUS = 'active' WHERE id = ?");
                $stmt->execute([$jobId]);
                break;
            case 'deactivate':
                $stmt = $pdo->prepare("UPDATE jobs SET STATUS = 'inactive' WHERE id = ?");
                $stmt->execute([$jobId]);
                break;
            case 'delete':
                $stmt = $pdo->prepare("UPDATE jobs SET STATUS = 'deleted' WHERE id = ?");
                $stmt->execute([$jobId]);
                break;
        }
        
        // Refresh page to show updated status
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Jobs - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header class="site-header">
        <div class="container">
            <nav class="site-nav">
                <a href="/findajob" class="site-logo">
                    <img src="/findajob/assets/images/logo_full.png" alt="FindAJob Nigeria" class="site-logo-img">
                </a>
                <div>
                    <a href="dashboard.php" class="btn btn-outline">Dashboard</a>
                    <a href="../auth/logout.php" class="btn btn-secondary">Logout</a>
                </div>
            </nav>
        </div>
    </header>

    <main class="container">
        <div style="padding: 2rem 0;">
            <!-- Page Header -->
            <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <div>
                    <h1 style="margin: 0; font-size: 2.5rem; font-weight: 700; color: var(--text-primary);">
                        Manage Jobs
                    </h1>
                    <p style="margin: 0.5rem 0 0 0; color: var(--text-secondary); font-size: 1.1rem;">
                        View and manage all your job postings
                    </p>
                </div>
                <a href="post-job.php" class="btn btn-primary" style="font-size: 1.1rem; padding: 0.75rem 1.5rem;">
                    <i class="fas fa-plus"></i> Post New Job
                </a>
            </div>

            <!-- Jobs Summary -->
            <div class="jobs-summary" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                <?php
                $totalJobs = count($jobs);
                $activeJobs = count(array_filter($jobs, fn($j) => $j['STATUS'] === 'active'));
                $inactiveJobs = count(array_filter($jobs, fn($j) => $j['STATUS'] === 'inactive'));
                $draftJobs = count(array_filter($jobs, fn($j) => $j['STATUS'] === 'draft'));
                ?>
                
                <div class="summary-card" style="background: var(--surface); padding: 1.5rem; border-radius: 8px; text-align: center; border-left: 4px solid var(--primary);">
                    <div style="font-size: 2rem; font-weight: bold; color: var(--primary); margin-bottom: 0.5rem;"><?php echo $totalJobs; ?></div>
                    <div style="color: var(--text-secondary);">Total Jobs</div>
                </div>
                
                <div class="summary-card" style="background: var(--surface); padding: 1.5rem; border-radius: 8px; text-align: center; border-left: 4px solid var(--accent);">
                    <div style="font-size: 2rem; font-weight: bold; color: var(--accent); margin-bottom: 0.5rem;"><?php echo $activeJobs; ?></div>
                    <div style="color: var(--text-secondary);">Active</div>
                </div>
                
                <div class="summary-card" style="background: var(--surface); padding: 1.5rem; border-radius: 8px; text-align: center; border-left: 4px solid var(--warning);">
                    <div style="font-size: 2rem; font-weight: bold; color: var(--warning); margin-bottom: 0.5rem;"><?php echo $inactiveJobs; ?></div>
                    <div style="color: var(--text-secondary);">Inactive</div>
                </div>
                
                <div class="summary-card" style="background: var(--surface); padding: 1.5rem; border-radius: 8px; text-align: center; border-left: 4px solid var(--text-secondary);">
                    <div style="font-size: 2rem; font-weight: bold; color: var(--text-secondary); margin-bottom: 0.5rem;"><?php echo $draftJobs; ?></div>
                    <div style="color: var(--text-secondary);">Drafts</div>
                </div>
            </div>

            <!-- Jobs List -->
            <div class="jobs-container" style="background: var(--surface); border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <?php if (empty($jobs)): ?>
                    <div style="padding: 4rem; text-align: center; color: var(--text-secondary);">
                        <i class="fas fa-briefcase" style="font-size: 4rem; margin-bottom: 2rem; color: var(--text-secondary); opacity: 0.5;"></i>
                        <h3>No jobs posted yet</h3>
                        <p>Start by posting your first job to attract qualified candidates.</p>
                        <a href="post-job.php" class="btn btn-primary" style="margin-top: 1rem;">
                            <i class="fas fa-plus"></i> Post Your First Job
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Jobs Header -->
                    <div style="padding: 1.5rem; border-bottom: 1px solid var(--border-color); background: var(--background); font-weight: 600; color: var(--text-secondary);">
                        <div style="display: grid; grid-template-columns: 3fr 1fr 1fr 1fr 1fr; gap: 1rem; align-items: center;">
                            <div>Job Details</div>
                            <div>Status</div>
                            <div>Applications</div>
                            <div>Posted</div>
                            <div>Actions</div>
                        </div>
                    </div>

                    <!-- Jobs List -->
                    <div class="jobs-list">
                        <?php foreach ($jobs as $index => $job): ?>
                            <?php if ($job['STATUS'] === 'deleted') continue; ?>
                            
                            <div class="job-row" style="padding: 1.5rem; border-bottom: 1px solid var(--border-color); <?php echo $index % 2 === 0 ? 'background: var(--surface);' : 'background: var(--background);'; ?>">
                                <div style="display: grid; grid-template-columns: 3fr 1fr 1fr 1fr 1fr; gap: 1rem; align-items: center;">
                                    <!-- Job Details -->
                                    <div>
                                        <h4 style="margin: 0 0 0.5rem 0; font-size: 1.2rem; font-weight: 600;">
                                            <a href="job-details.php?id=<?php echo $job['id']; ?>" style="color: var(--text-primary); text-decoration: none;">
                                                <?php echo htmlspecialchars($job['title']); ?>
                                            </a>
                                        </h4>
                                        <p style="margin: 0; color: var(--text-secondary); font-size: 0.9rem;">
                                            <i class="fas fa-map-marker-alt"></i> 
                                            <?php echo htmlspecialchars(($job['lga_name'] ?? '') . ', ' . ($job['state_name'] ?? '')); ?>
                                        </p>
                                        <p style="margin: 0.25rem 0 0 0; color: var(--text-secondary); font-size: 0.9rem;">
                                            <i class="fas fa-briefcase"></i> 
                                            <?php echo htmlspecialchars($job['job_type'] ?? 'Full-time'); ?>
                                            <?php if (!empty($job['salary_min']) && !empty($job['salary_max'])): ?>
                                                • ₦<?php echo number_format($job['salary_min']); ?> - ₦<?php echo number_format($job['salary_max']); ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>

                                    <!-- Status -->
                                    <div>
                                        <?php
                                        $statusColors = [
                                            'active' => 'var(--accent)',
                                            'inactive' => 'var(--warning)',
                                            'draft' => 'var(--text-secondary)',
                                            'expired' => 'var(--error)'
                                        ];
                                        $statusColor = $statusColors[$job['STATUS']] ?? 'var(--text-secondary)';
                                        ?>
                                        <span style="background: <?php echo $statusColor; ?>; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 500;">
                                            <?php echo ucfirst($job['STATUS']); ?>
                                        </span>
                                    </div>

                                    <!-- Applications -->
                                    <div style="text-align: center;">
                                        <span style="font-size: 1.2rem; font-weight: 600; color: var(--primary);">
                                            <?php echo $job['application_count']; ?>
                                        </span>
                                        <div style="font-size: 0.8rem; color: var(--text-secondary);">applicants</div>
                                    </div>

                                    <!-- Posted Date -->
                                    <div style="font-size: 0.9rem; color: var(--text-secondary);">
                                        <?php echo date('M j, Y', strtotime($job['created_at'])); ?>
                                    </div>

                                    <!-- Actions -->
                                    <div style="display: flex; gap: 0.5rem;">
                                        <div class="dropdown" style="position: relative;">
                                            <button class="btn btn-outline btn-sm dropdown-toggle" onclick="toggleDropdown(<?php echo $job['id']; ?>)">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <div class="dropdown-menu" id="dropdown-<?php echo $job['id']; ?>" style="display: none; position: absolute; right: 0; top: 100%; background: var(--surface); border: 1px solid var(--border-color); border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); z-index: 1000; min-width: 150px;">
                                                <a href="edit-job.php?id=<?php echo $job['id']; ?>" class="dropdown-item" style="display: block; padding: 0.75rem 1rem; color: var(--text-primary); text-decoration: none;">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <a href="job-applicants.php?id=<?php echo $job['id']; ?>" class="dropdown-item" style="display: block; padding: 0.75rem 1rem; color: var(--text-primary); text-decoration: none;">
                                                    <i class="fas fa-users"></i> View Applicants
                                                </a>
                                                <?php if ($job['STATUS'] === 'active'): ?>
                                                    <form method="POST" style="margin: 0;" onsubmit="return confirm('Are you sure you want to deactivate this job?')">
                                                        <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                                        <input type="hidden" name="action" value="deactivate">
                                                        <button type="submit" class="dropdown-item" style="width: 100%; text-align: left; padding: 0.75rem 1rem; background: none; border: none; color: var(--warning);">
                                                            <i class="fas fa-pause"></i> Deactivate
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" style="margin: 0;">
                                                        <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                                        <input type="hidden" name="action" value="activate">
                                                        <button type="submit" class="dropdown-item" style="width: 100%; text-align: left; padding: 0.75rem 1rem; background: none; border: none; color: var(--accent);">
                                                            <i class="fas fa-play"></i> Activate
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <form method="POST" style="margin: 0;" onsubmit="return confirm('Are you sure you want to delete this job? This action cannot be undone.')">
                                                    <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <button type="submit" class="dropdown-item" style="width: 100%; text-align: left; padding: 0.75rem 1rem; background: none; border: none; color: var(--error);">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        function toggleDropdown(jobId) {
            const dropdown = document.getElementById(`dropdown-${jobId}`);
            const allDropdowns = document.querySelectorAll('.dropdown-menu');
            
            // Close all other dropdowns
            allDropdowns.forEach(dd => {
                if (dd.id !== `dropdown-${jobId}`) {
                    dd.style.display = 'none';
                }
            });
            
            // Toggle current dropdown
            dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown-menu').forEach(dd => {
                    dd.style.display = 'none';
                });
            }
        });
    </script>

    <style>
        .dropdown-item:hover {
            background: var(--background);
        }
        
        .job-row:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transform: translateY(-1px);
            transition: all 0.2s ease;
        }
    </style>
</body>
</html>