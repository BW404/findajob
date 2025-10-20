<?php
require_once '../../config/database.php';
require_once '../../config/session.php';

requireEmployer();

$employer_id = getCurrentUserId();

// Get overall statistics
$overall_query = "SELECT 
                  COUNT(DISTINCT j.id) as total_jobs,
                  COUNT(DISTINCT CASE WHEN j.status = 'active' THEN j.id END) as active_jobs,
                  COUNT(DISTINCT ja.id) as total_applications,
                  SUM(j.views_count) as total_views
                  FROM jobs j
                  LEFT JOIN job_applications ja ON j.id = ja.job_id
                  WHERE j.employer_id = ?";
$overall_stmt = $pdo->prepare($overall_query);
$overall_stmt->execute([$employer_id]);
$overall = $overall_stmt->fetch();

// Calculate conversion rate
$conversion_rate = $overall['total_views'] > 0 
    ? round(($overall['total_applications'] / $overall['total_views']) * 100, 1) 
    : 0;

// Get application status breakdown
$status_query = "SELECT 
                 ja.application_status,
                 COUNT(*) as count
                 FROM job_applications ja
                 INNER JOIN jobs j ON ja.job_id = j.id
                 WHERE j.employer_id = ?
                 GROUP BY ja.application_status";
$status_stmt = $pdo->prepare($status_query);
$status_stmt->execute([$employer_id]);
$status_breakdown = $status_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Get top performing jobs
$top_jobs_query = "SELECT j.id, j.title, j.views_count, 
                   COUNT(ja.id) as application_count,
                   CASE WHEN j.views_count > 0 
                        THEN ROUND((COUNT(ja.id) / j.views_count) * 100, 1) 
                        ELSE 0 
                   END as conversion_rate
                   FROM jobs j
                   LEFT JOIN job_applications ja ON j.id = ja.job_id
                   WHERE j.employer_id = ?
                   GROUP BY j.id
                   ORDER BY application_count DESC
                   LIMIT 5";
$top_jobs_stmt = $pdo->prepare($top_jobs_query);
$top_jobs_stmt->execute([$employer_id]);
$top_jobs = $top_jobs_stmt->fetchAll();

// Get recent activity (last 30 days)
$activity_query = "SELECT 
                   DATE(ja.applied_at) as date,
                   COUNT(*) as applications
                   FROM job_applications ja
                   INNER JOIN jobs j ON ja.job_id = j.id
                   WHERE j.employer_id = ? 
                   AND ja.applied_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                   GROUP BY DATE(ja.applied_at)
                   ORDER BY date DESC";
$activity_stmt = $pdo->prepare($activity_query);
$activity_stmt->execute([$employer_id]);
$activity = $activity_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - FindAJob Nigeria</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header class="site-header">
        <div class="container">
            <nav class="site-nav">
                <a href="/findajob" class="site-logo">
                    <img src="/findajob/assets/images/logo_full.png" alt="FindAJob Nigeria" class="site-logo-img">
                </a>
                <div class="nav-links" style="display: flex; align-items: center; gap: 1.5rem;">
                    <a href="dashboard.php" class="nav-link">Dashboard</a>
                    <a href="post-job.php" class="nav-link">Post Job</a>
                    <a href="active-jobs.php" class="nav-link">Active Jobs</a>
                    <a href="all-applications.php" class="nav-link">Applications</a>
                    <a href="analytics.php" class="nav-link" style="color: var(--primary); font-weight: 600;">Analytics</a>
                    <a href="profile.php" class="nav-link">Profile</a>
                    <a href="../auth/logout.php" class="btn btn-secondary">Logout</a>
                </div>
            </nav>
        </div>
    </header>

    <main class="container" style="padding: 3rem 0;">
        <!-- Page Header -->
        <div style="margin-bottom: 2rem;">
            <h1 style="margin: 0 0 0.5rem 0; font-size: 2.5rem; font-weight: 700; color: var(--text-primary);">
                <i class="fas fa-chart-line" style="color: var(--primary); margin-right: 0.5rem;"></i>
                Analytics Dashboard
            </h1>
            <p style="margin: 0; color: var(--text-secondary); font-size: 1.1rem;">
                Track your hiring performance and application metrics
            </p>
        </div>

        <!-- Key Metrics -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
            <div style="background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); color: white; padding: 2rem; border-radius: 16px; box-shadow: 0 8px 20px rgba(220,38,38,0.2);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                    <div>
                        <div style="font-size: 0.9rem; opacity: 0.9; font-weight: 500; margin-bottom: 0.5rem;">Total Jobs</div>
                        <div style="font-size: 2.5rem; font-weight: 700;"><?php echo $overall['total_jobs']; ?></div>
                    </div>
                    <div style="width: 60px; height: 60px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-briefcase" style="font-size: 1.75rem;"></i>
                    </div>
                </div>
                <div style="font-size: 0.85rem; opacity: 0.8;">
                    <i class="fas fa-check-circle"></i> <?php echo $overall['active_jobs']; ?> currently active
                </div>
            </div>

            <div style="background: linear-gradient(135deg, #059669 0%, #047857 100%); color: white; padding: 2rem; border-radius: 16px; box-shadow: 0 8px 20px rgba(5,150,105,0.2);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                    <div>
                        <div style="font-size: 0.9rem; opacity: 0.9; font-weight: 500; margin-bottom: 0.5rem;">Total Applications</div>
                        <div style="font-size: 2.5rem; font-weight: 700;"><?php echo $overall['total_applications']; ?></div>
                    </div>
                    <div style="width: 60px; height: 60px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-users" style="font-size: 1.75rem;"></i>
                    </div>
                </div>
                <div style="font-size: 0.85rem; opacity: 0.8;">
                    <i class="fas fa-arrow-up"></i> Across all job postings
                </div>
            </div>

            <div style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: white; padding: 2rem; border-radius: 16px; box-shadow: 0 8px 20px rgba(99,102,241,0.2);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                    <div>
                        <div style="font-size: 0.9rem; opacity: 0.9; font-weight: 500; margin-bottom: 0.5rem;">Total Views</div>
                        <div style="font-size: 2.5rem; font-weight: 700;"><?php echo number_format($overall['total_views'] ?? 0); ?></div>
                    </div>
                    <div style="width: 60px; height: 60px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-eye" style="font-size: 1.75rem;"></i>
                    </div>
                </div>
                <div style="font-size: 0.85rem; opacity: 0.8;">
                    <i class="fas fa-chart-bar"></i> Job post impressions
                </div>
            </div>

            <div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 2rem; border-radius: 16px; box-shadow: 0 8px 20px rgba(245,158,11,0.2);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                    <div>
                        <div style="font-size: 0.9rem; opacity: 0.9; font-weight: 500; margin-bottom: 0.5rem;">Conversion Rate</div>
                        <div style="font-size: 2.5rem; font-weight: 700;"><?php echo $conversion_rate; ?>%</div>
                    </div>
                    <div style="width: 60px; height: 60px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-percentage" style="font-size: 1.75rem;"></i>
                    </div>
                </div>
                <div style="font-size: 0.85rem; opacity: 0.8;">
                    <i class="fas fa-info-circle"></i> Views to applications
                </div>
            </div>
        </div>

        <!-- Two Column Layout -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 3rem;">
            <!-- Application Status Breakdown -->
            <div style="background: var(--surface); padding: 2.5rem; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                <h2 style="margin: 0 0 2rem 0; font-size: 1.5rem; font-weight: 700; color: var(--text-primary); display: flex; align-items: center;">
                    <i class="fas fa-chart-pie" style="color: var(--primary); margin-right: 0.75rem;"></i>
                    Application Status Breakdown
                </h2>
                <canvas id="statusChart" style="max-height: 300px;"></canvas>
            </div>

            <!-- Top Performing Jobs -->
            <div style="background: var(--surface); padding: 2.5rem; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                <h2 style="margin: 0 0 2rem 0; font-size: 1.5rem; font-weight: 700; color: var(--text-primary); display: flex; align-items: center;">
                    <i class="fas fa-trophy" style="color: var(--primary); margin-right: 0.75rem;"></i>
                    Top Performing Jobs
                </h2>
                <?php if (empty($top_jobs)): ?>
                    <div style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                        <i class="fas fa-briefcase" style="font-size: 2.5rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                        <p>No job data available yet</p>
                    </div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <?php foreach ($top_jobs as $index => $job): ?>
                            <div style="padding: 1.25rem; background: linear-gradient(135deg, rgba(220,38,38,0.05) 0%, rgba(220,38,38,0.02) 100%); border-radius: 12px; border-left: 4px solid var(--primary);">
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                                    <div style="flex: 1;">
                                        <div style="font-weight: 700; color: var(--text-primary); margin-bottom: 0.25rem; font-size: 1.05rem;">
                                            #<?php echo $index + 1; ?>. 
                                            <a href="../jobs/details.php?id=<?php echo $job['id']; ?>" style="color: var(--text-primary); text-decoration: none;">
                                                <?php echo htmlspecialchars($job['title']); ?>
                                            </a>
                                        </div>
                                    </div>
                                    <div style="background: var(--primary); color: white; padding: 0.5rem 1rem; border-radius: 20px; font-weight: 700; font-size: 1.1rem;">
                                        <?php echo $job['application_count']; ?>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 2rem; font-size: 0.9rem; color: var(--text-secondary);">
                                    <span><i class="fas fa-eye"></i> <?php echo number_format($job['views_count']); ?> views</span>
                                    <span><i class="fas fa-percentage"></i> <?php echo $job['conversion_rate']; ?>% conversion</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Application Activity (Last 30 Days) -->
        <div style="background: var(--surface); padding: 2.5rem; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
            <h2 style="margin: 0 0 2rem 0; font-size: 1.5rem; font-weight: 700; color: var(--text-primary); display: flex; align-items: center;">
                <i class="fas fa-calendar-alt" style="color: var(--primary); margin-right: 0.75rem;"></i>
                Application Activity (Last 30 Days)
            </h2>
            <canvas id="activityChart" style="max-height: 300px;"></canvas>
        </div>

        <!-- Insights and Tips -->
        <div style="margin-top: 3rem; background: linear-gradient(135deg, rgba(99,102,241,0.1) 0%, rgba(99,102,241,0.05) 100%); padding: 2.5rem; border-radius: 16px; border: 2px solid rgba(99,102,241,0.2);">
            <h2 style="margin: 0 0 1.5rem 0; font-size: 1.5rem; font-weight: 700; color: var(--text-primary); display: flex; align-items: center;">
                <i class="fas fa-lightbulb" style="color: #6366f1; margin-right: 0.75rem;"></i>
                Hiring Insights & Tips
            </h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div style="background: white; padding: 1.5rem; border-radius: 12px;">
                    <div style="font-weight: 600; color: var(--primary); margin-bottom: 0.5rem;">
                        <i class="fas fa-check-circle"></i> Improve Your Job Posts
                    </div>
                    <p style="margin: 0; font-size: 0.95rem; color: var(--text-secondary); line-height: 1.6;">
                        Jobs with detailed descriptions get 3x more quality applications. Include skills, requirements, and benefits clearly.
                    </p>
                </div>
                <div style="background: white; padding: 1.5rem; border-radius: 12px;">
                    <div style="font-weight: 600; color: var(--primary); margin-bottom: 0.5rem;">
                        <i class="fas fa-clock"></i> Respond Quickly
                    </div>
                    <p style="margin: 0; font-size: 0.95rem; color: var(--text-secondary); line-height: 1.6;">
                        Candidates who receive responses within 24 hours are 4x more likely to remain interested in the position.
                    </p>
                </div>
            </div>
        </div>
    </main>

    <?php include '../../includes/footer.php'; ?>

    <script>
        // Status Breakdown Chart
        const statusData = <?php echo json_encode($status_breakdown); ?>;
        const statusLabels = Object.keys(statusData).map(status => status.charAt(0).toUpperCase() + status.slice(1));
        const statusValues = Object.values(statusData);
        
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusValues,
                    backgroundColor: [
                        '#059669', // applied
                        '#6366f1', // viewed
                        '#f59e0b', // shortlisted
                        '#8b5cf6', // interviewed
                        '#10b981', // offered
                        '#059669', // hired
                        '#ef4444'  // rejected
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }
        });

        // Activity Chart
        const activityData = <?php echo json_encode(array_reverse($activity)); ?>;
        const activityLabels = activityData.map(item => {
            const date = new Date(item.date);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        });
        const activityValues = activityData.map(item => item.applications);

        const activityCtx = document.getElementById('activityChart').getContext('2d');
        new Chart(activityCtx, {
            type: 'line',
            data: {
                labels: activityLabels,
                datasets: [{
                    label: 'Applications',
                    data: activityValues,
                    borderColor: '#dc2626',
                    backgroundColor: 'rgba(220, 38, 38, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#dc2626',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
