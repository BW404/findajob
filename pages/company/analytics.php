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
$overall_stmt->execute([$userId]);
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
$status_stmt->execute([$userId]);
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
$top_jobs_stmt->execute([$userId]);
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
$activity_stmt->execute([$userId]);
$activity = $activity_stmt->fetchAll();

// ===== ADVANCED ANALYTICS =====

// 1. APPLICATION TRACKING - Hiring funnel metrics
$funnel_query = "SELECT 
                 SUM(CASE WHEN ja.application_status = 'applied' THEN 1 ELSE 0 END) as applied,
                 SUM(CASE WHEN ja.application_status = 'viewed' THEN 1 ELSE 0 END) as viewed,
                 SUM(CASE WHEN ja.application_status = 'shortlisted' THEN 1 ELSE 0 END) as shortlisted,
                 SUM(CASE WHEN ja.application_status = 'interviewed' THEN 1 ELSE 0 END) as interviewed,
                 SUM(CASE WHEN ja.application_status = 'offered' THEN 1 ELSE 0 END) as offered,
                 SUM(CASE WHEN ja.application_status = 'hired' THEN 1 ELSE 0 END) as hired,
                 SUM(CASE WHEN ja.application_status = 'rejected' THEN 1 ELSE 0 END) as rejected
                 FROM job_applications ja
                 INNER JOIN jobs j ON ja.job_id = j.id
                 WHERE j.employer_id = ?";
$funnel_stmt = $pdo->prepare($funnel_query);
$funnel_stmt->execute([$userId]);
$funnel = $funnel_stmt->fetch();

// Calculate conversion rates for hiring funnel
$funnel_total = $funnel['applied'] + $funnel['viewed'] + $funnel['shortlisted'] + 
                $funnel['interviewed'] + $funnel['offered'] + $funnel['hired'];
$funnel_percentages = [
    'applied' => $funnel_total > 0 ? round(($funnel['applied'] / $funnel_total) * 100, 1) : 0,
    'viewed' => $funnel_total > 0 ? round(($funnel['viewed'] / $funnel_total) * 100, 1) : 0,
    'shortlisted' => $funnel_total > 0 ? round(($funnel['shortlisted'] / $funnel_total) * 100, 1) : 0,
    'interviewed' => $funnel_total > 0 ? round(($funnel['interviewed'] / $funnel_total) * 100, 1) : 0,
    'offered' => $funnel_total > 0 ? round(($funnel['offered'] / $funnel_total) * 100, 1) : 0,
    'hired' => $funnel_total > 0 ? round(($funnel['hired'] / $funnel_total) * 100, 1) : 0,
];

// 2. PERFORMANCE METRICS - Time to hire and response rates
$performance_query = "SELECT 
                      AVG(TIMESTAMPDIFF(DAY, ja.applied_at, 
                          CASE 
                              WHEN ja.application_status = 'hired' THEN ja.updated_at 
                              ELSE NULL 
                          END)) as avg_time_to_hire,
                      AVG(TIMESTAMPDIFF(HOUR, ja.applied_at, ja.updated_at)) as avg_response_time,
                      COUNT(CASE WHEN ja.updated_at <= DATE_ADD(ja.applied_at, INTERVAL 24 HOUR) THEN 1 END) as responses_within_24h,
                      COUNT(*) as total_responses
                      FROM job_applications ja
                      INNER JOIN jobs j ON ja.job_id = j.id
                      WHERE j.employer_id = ? 
                      AND ja.updated_at > ja.applied_at";
$performance_stmt = $pdo->prepare($performance_query);
$performance_stmt->execute([$userId]);
$performance = $performance_stmt->fetch();

$response_rate_24h = $performance['total_responses'] > 0 
    ? round(($performance['responses_within_24h'] / $performance['total_responses']) * 100, 1) 
    : 0;

// 3. HIRING ANALYTICS - Candidate quality and source effectiveness
$quality_query = "SELECT 
                  COUNT(CASE WHEN ja.application_status IN ('hired', 'offered') THEN 1 END) as quality_candidates,
                  COUNT(*) as total_candidates,
                  AVG(jsp.years_of_experience) as avg_experience,
                  COUNT(CASE WHEN jsp.nin_verified = 1 THEN 1 END) as verified_candidates
                  FROM job_applications ja
                  INNER JOIN jobs j ON ja.job_id = j.id
                  LEFT JOIN job_seeker_profiles jsp ON ja.job_seeker_id = jsp.user_id
                  WHERE j.employer_id = ?";
$quality_stmt = $pdo->prepare($quality_query);
$quality_stmt->execute([$userId]);
$quality = $quality_stmt->fetch();

$quality_rate = $quality['total_candidates'] > 0 
    ? round(($quality['quality_candidates'] / $quality['total_candidates']) * 100, 1) 
    : 0;

$verification_rate = $quality['total_candidates'] > 0 
    ? round(($quality['verified_candidates'] / $quality['total_candidates']) * 100, 1) 
    : 0;

// Get hiring success rate by job
$success_by_job_query = "SELECT j.title, j.id,
                         COUNT(ja.id) as total_apps,
                         COUNT(CASE WHEN ja.application_status = 'hired' THEN 1 END) as hired_count,
                         CASE WHEN COUNT(ja.id) > 0 
                              THEN ROUND((COUNT(CASE WHEN ja.application_status = 'hired' THEN 1 END) / COUNT(ja.id)) * 100, 1)
                              ELSE 0 
                         END as success_rate
                         FROM jobs j
                         LEFT JOIN job_applications ja ON j.id = ja.job_id
                         WHERE j.employer_id = ?
                         GROUP BY j.id
                         HAVING total_apps > 0
                         ORDER BY success_rate DESC
                         LIMIT 5";
$success_stmt = $pdo->prepare($success_by_job_query);
$success_stmt->execute([$userId]);
$success_by_job = $success_stmt->fetchAll();
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
<body class="has-bottom-nav">
    <?php include '../../includes/employer-header.php'; ?>

    <main class="container" style="padding: 2rem 0;">
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

        <!-- Advanced Analytics Section -->
        <div style="margin-top: 3rem;">
            <h2 style="margin: 0 0 2rem 0; font-size: 2rem; font-weight: 800; color: var(--text-primary); text-align: center;">
                <i class="fas fa-chart-bar" style="color: var(--primary); margin-right: 0.75rem;"></i>
                Advanced Analytics
            </h2>
            
            <!-- 1. Application Tracking - Hiring Funnel -->
            <div style="background: var(--surface); padding: 2.5rem; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 2rem;">
                <h3 style="margin: 0 0 1.5rem 0; font-size: 1.5rem; font-weight: 700; color: var(--text-primary); display: flex; align-items: center;">
                    <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #059669 0%, #047857 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-right: 1rem;">
                        <i class="fas fa-chart-line" style="font-size: 1.5rem; color: white;"></i>
                    </div>
                    Application Tracking - Hiring Funnel
                </h3>
                <p style="margin: 0 0 2rem 0; color: var(--text-secondary);">
                    Monitor application flows and identify bottlenecks in your hiring process
                </p>
                
                <?php if ($funnel_total > 0): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                    <div style="background: linear-gradient(135deg, rgba(5,150,105,0.1) 0%, rgba(5,150,105,0.05) 100%); padding: 1.5rem; border-radius: 12px; text-align: center; border-left: 4px solid #059669;">
                        <div style="font-size: 2rem; font-weight: 700; color: #059669; margin-bottom: 0.5rem;"><?php echo $funnel['applied']; ?></div>
                        <div style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Applied</div>
                        <div style="font-size: 0.85rem; font-weight: 600; color: #059669;"><?php echo $funnel_percentages['applied']; ?>%</div>
                    </div>
                    
                    <div style="background: linear-gradient(135deg, rgba(99,102,241,0.1) 0%, rgba(99,102,241,0.05) 100%); padding: 1.5rem; border-radius: 12px; text-align: center; border-left: 4px solid #6366f1;">
                        <div style="font-size: 2rem; font-weight: 700; color: #6366f1; margin-bottom: 0.5rem;"><?php echo $funnel['viewed']; ?></div>
                        <div style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Viewed</div>
                        <div style="font-size: 0.85rem; font-weight: 600; color: #6366f1;"><?php echo $funnel_percentages['viewed']; ?>%</div>
                    </div>
                    
                    <div style="background: linear-gradient(135deg, rgba(245,158,11,0.1) 0%, rgba(245,158,11,0.05) 100%); padding: 1.5rem; border-radius: 12px; text-align: center; border-left: 4px solid #f59e0b;">
                        <div style="font-size: 2rem; font-weight: 700; color: #f59e0b; margin-bottom: 0.5rem;"><?php echo $funnel['shortlisted']; ?></div>
                        <div style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Shortlisted</div>
                        <div style="font-size: 0.85rem; font-weight: 600; color: #f59e0b;"><?php echo $funnel_percentages['shortlisted']; ?>%</div>
                    </div>
                    
                    <div style="background: linear-gradient(135deg, rgba(139,92,246,0.1) 0%, rgba(139,92,246,0.05) 100%); padding: 1.5rem; border-radius: 12px; text-align: center; border-left: 4px solid #8b5cf6;">
                        <div style="font-size: 2rem; font-weight: 700; color: #8b5cf6; margin-bottom: 0.5rem;"><?php echo $funnel['interviewed']; ?></div>
                        <div style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Interviewed</div>
                        <div style="font-size: 0.85rem; font-weight: 600; color: #8b5cf6;"><?php echo $funnel_percentages['interviewed']; ?>%</div>
                    </div>
                    
                    <div style="background: linear-gradient(135deg, rgba(16,185,129,0.1) 0%, rgba(16,185,129,0.05) 100%); padding: 1.5rem; border-radius: 12px; text-align: center; border-left: 4px solid #10b981;">
                        <div style="font-size: 2rem; font-weight: 700; color: #10b981; margin-bottom: 0.5rem;"><?php echo $funnel['offered']; ?></div>
                        <div style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Offered</div>
                        <div style="font-size: 0.85rem; font-weight: 600; color: #10b981;"><?php echo $funnel_percentages['offered']; ?>%</div>
                    </div>
                    
                    <div style="background: linear-gradient(135deg, rgba(220,38,38,0.1) 0%, rgba(220,38,38,0.05) 100%); padding: 1.5rem; border-radius: 12px; text-align: center; border-left: 4px solid #dc2626;">
                        <div style="font-size: 2rem; font-weight: 700; color: #dc2626; margin-bottom: 0.5rem;"><?php echo $funnel['hired']; ?></div>
                        <div style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Hired</div>
                        <div style="font-size: 0.85rem; font-weight: 600; color: #dc2626;"><?php echo $funnel_percentages['hired']; ?>%</div>
                    </div>
                </div>
                
                <div style="background: linear-gradient(135deg, rgba(220,38,38,0.05) 0%, rgba(220,38,38,0.02) 100%); padding: 1.5rem; border-radius: 12px; border-left: 4px solid var(--primary);">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <i class="fas fa-info-circle" style="font-size: 1.5rem; color: var(--primary);"></i>
                        <div>
                            <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 0.25rem;">Funnel Insights</div>
                            <div style="font-size: 0.9rem; color: var(--text-secondary);">
                                <?php 
                                $conversion_to_hired = $funnel['applied'] > 0 ? round(($funnel['hired'] / $funnel['applied']) * 100, 1) : 0;
                                echo "Your application-to-hire conversion rate is <strong>{$conversion_to_hired}%</strong>. ";
                                if ($conversion_to_hired < 5) {
                                    echo "Consider improving your screening process or job descriptions.";
                                } elseif ($conversion_to_hired < 15) {
                                    echo "Good conversion rate! Keep optimizing your hiring process.";
                                } else {
                                    echo "Excellent conversion rate! Your hiring process is highly effective.";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div style="text-align: center; padding: 3rem; color: var(--text-secondary);">
                    <i class="fas fa-chart-line" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                    <p>No application data available yet. Start receiving applications to see your hiring funnel.</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- 2. Performance Metrics -->
            <div style="background: var(--surface); padding: 2.5rem; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 2rem;">
                <h3 style="margin: 0 0 1.5rem 0; font-size: 1.5rem; font-weight: 700; color: var(--text-primary); display: flex; align-items: center;">
                    <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-right: 1rem;">
                        <i class="fas fa-tachometer-alt" style="font-size: 1.5rem; color: white;"></i>
                    </div>
                    Performance Metrics
                </h3>
                <p style="margin: 0 0 2rem 0; color: var(--text-secondary);">
                    Measure time-to-hire, response rates, and candidate engagement metrics
                </p>
                
                <?php if ($performance['total_responses'] > 0): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                    <div style="background: linear-gradient(135deg, rgba(99,102,241,0.1) 0%, rgba(99,102,241,0.05) 100%); padding: 2rem; border-radius: 12px; border-left: 4px solid #6366f1;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                            <div style="font-size: 0.9rem; color: var(--text-secondary); font-weight: 500;">Avg. Time to Hire</div>
                            <i class="fas fa-clock" style="font-size: 1.5rem; color: #6366f1; opacity: 0.3;"></i>
                        </div>
                        <div style="font-size: 2.5rem; font-weight: 700; color: #6366f1;">
                            <?php echo $performance['avg_time_to_hire'] ? round($performance['avg_time_to_hire']) : '-'; ?>
                        </div>
                        <div style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.5rem;">
                            <?php echo $performance['avg_time_to_hire'] ? 'days' : 'No hires yet'; ?>
                        </div>
                    </div>
                    
                    <div style="background: linear-gradient(135deg, rgba(245,158,11,0.1) 0%, rgba(245,158,11,0.05) 100%); padding: 2rem; border-radius: 12px; border-left: 4px solid #f59e0b;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                            <div style="font-size: 0.9rem; color: var(--text-secondary); font-weight: 500;">Avg. Response Time</div>
                            <i class="fas fa-stopwatch" style="font-size: 1.5rem; color: #f59e0b; opacity: 0.3;"></i>
                        </div>
                        <div style="font-size: 2.5rem; font-weight: 700; color: #f59e0b;">
                            <?php echo round($performance['avg_response_time']); ?>
                        </div>
                        <div style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.5rem;">hours</div>
                    </div>
                    
                    <div style="background: linear-gradient(135deg, rgba(5,150,105,0.1) 0%, rgba(5,150,105,0.05) 100%); padding: 2rem; border-radius: 12px; border-left: 4px solid #059669;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                            <div style="font-size: 0.9rem; color: var(--text-secondary); font-weight: 500;">24h Response Rate</div>
                            <i class="fas fa-bolt" style="font-size: 1.5rem; color: #059669; opacity: 0.3;"></i>
                        </div>
                        <div style="font-size: 2.5rem; font-weight: 700; color: #059669;">
                            <?php echo $response_rate_24h; ?>%
                        </div>
                        <div style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.5rem;">
                            <?php echo $performance['responses_within_24h']; ?> of <?php echo $performance['total_responses']; ?> responses
                        </div>
                    </div>
                </div>
                
                <div style="background: linear-gradient(135deg, rgba(99,102,241,0.05) 0%, rgba(99,102,241,0.02) 100%); padding: 1.5rem; border-radius: 12px; border-left: 4px solid #6366f1; margin-top: 1.5rem;">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <i class="fas fa-lightbulb" style="font-size: 1.5rem; color: #6366f1;"></i>
                        <div>
                            <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 0.25rem;">Performance Tip</div>
                            <div style="font-size: 0.9rem; color: var(--text-secondary);">
                                <?php 
                                if ($response_rate_24h < 40) {
                                    echo "Try to respond to applications within 24 hours. Candidates are 4x more likely to stay interested with quick responses.";
                                } elseif ($response_rate_24h < 70) {
                                    echo "Good response time! Keep up the momentum to maintain candidate interest.";
                                } else {
                                    echo "Excellent response rate! Your quick responses help maintain high candidate engagement.";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div style="text-align: center; padding: 3rem; color: var(--text-secondary);">
                    <i class="fas fa-tachometer-alt" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                    <p>No performance data available yet. Interact with applications to see metrics.</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- 3. Hiring Analytics -->
            <div style="background: var(--surface); padding: 2.5rem; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 2rem;">
                <h3 style="margin: 0 0 1.5rem 0; font-size: 1.5rem; font-weight: 700; color: var(--text-primary); display: flex; align-items: center;">
                    <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-right: 1rem;">
                        <i class="fas fa-filter" style="font-size: 1.5rem; color: white;"></i>
                    </div>
                    Hiring Analytics
                </h3>
                <p style="margin: 0 0 2rem 0; color: var(--text-secondary);">
                    Analyze candidate quality, source effectiveness, and hiring success rates
                </p>
                
                <?php if ($quality['total_candidates'] > 0): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                    <div style="background: linear-gradient(135deg, rgba(220,38,38,0.1) 0%, rgba(220,38,38,0.05) 100%); padding: 2rem; border-radius: 12px; border-left: 4px solid #dc2626;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                            <div style="font-size: 0.9rem; color: var(--text-secondary); font-weight: 500;">Quality Rate</div>
                            <i class="fas fa-star" style="font-size: 1.5rem; color: #dc2626; opacity: 0.3;"></i>
                        </div>
                        <div style="font-size: 2.5rem; font-weight: 700; color: #dc2626;">
                            <?php echo $quality_rate; ?>%
                        </div>
                        <div style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.5rem;">
                            <?php echo $quality['quality_candidates']; ?> hired/offered candidates
                        </div>
                    </div>
                    
                    <div style="background: linear-gradient(135deg, rgba(16,185,129,0.1) 0%, rgba(16,185,129,0.05) 100%); padding: 2rem; border-radius: 12px; border-left: 4px solid #10b981;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                            <div style="font-size: 0.9rem; color: var(--text-secondary); font-weight: 500;">Verification Rate</div>
                            <i class="fas fa-shield-alt" style="font-size: 1.5rem; color: #10b981; opacity: 0.3;"></i>
                        </div>
                        <div style="font-size: 2.5rem; font-weight: 700; color: #10b981;">
                            <?php echo $verification_rate; ?>%
                        </div>
                        <div style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.5rem;">
                            <?php echo $quality['verified_candidates']; ?> verified candidates
                        </div>
                    </div>
                    
                    <div style="background: linear-gradient(135deg, rgba(139,92,246,0.1) 0%, rgba(139,92,246,0.05) 100%); padding: 2rem; border-radius: 12px; border-left: 4px solid #8b5cf6;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                            <div style="font-size: 0.9rem; color: var(--text-secondary); font-weight: 500;">Avg. Experience</div>
                            <i class="fas fa-briefcase" style="font-size: 1.5rem; color: #8b5cf6; opacity: 0.3;"></i>
                        </div>
                        <div style="font-size: 2.5rem; font-weight: 700; color: #8b5cf6;">
                            <?php echo $quality['avg_experience'] ? round($quality['avg_experience'], 1) : '-'; ?>
                        </div>
                        <div style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.5rem;">
                            <?php echo $quality['avg_experience'] ? 'years of experience' : 'No data'; ?>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($success_by_job)): ?>
                <div>
                    <h4 style="margin: 0 0 1rem 0; font-size: 1.1rem; font-weight: 600; color: var(--text-primary);">
                        Hiring Success Rate by Job
                    </h4>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <?php foreach ($success_by_job as $job): ?>
                        <div style="background: linear-gradient(135deg, rgba(220,38,38,0.05) 0%, rgba(220,38,38,0.02) 100%); padding: 1.25rem; border-radius: 12px; border-left: 4px solid var(--primary);">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <div style="flex: 1;">
                                    <div style="font-weight: 700; color: var(--text-primary); margin-bottom: 0.5rem;">
                                        <?php echo htmlspecialchars($job['title']); ?>
                                    </div>
                                    <div style="font-size: 0.9rem; color: var(--text-secondary);">
                                        <?php echo $job['hired_count']; ?> hired from <?php echo $job['total_apps']; ?> applications
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 2rem; font-weight: 700; color: var(--primary);">
                                        <?php echo $job['success_rate']; ?>%
                                    </div>
                                    <div style="font-size: 0.8rem; color: var(--text-secondary);">success rate</div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div style="background: linear-gradient(135deg, rgba(220,38,38,0.05) 0%, rgba(220,38,38,0.02) 100%); padding: 1.5rem; border-radius: 12px; border-left: 4px solid var(--primary); margin-top: 1.5rem;">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <i class="fas fa-chart-bar" style="font-size: 1.5rem; color: var(--primary);"></i>
                        <div>
                            <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 0.25rem;">Quality Insights</div>
                            <div style="font-size: 0.9rem; color: var(--text-secondary);">
                                <?php 
                                if ($verification_rate > 50) {
                                    echo "Great! Over half your candidates are verified, indicating high trust and quality.";
                                } elseif ($verification_rate > 20) {
                                    echo "Consider prioritizing verified candidates for better quality and reduced risk.";
                                } else {
                                    echo "Encourage candidates to verify their profiles for better hiring confidence.";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div style="text-align: center; padding: 3rem; color: var(--text-secondary);">
                    <i class="fas fa-filter" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                    <p>No hiring analytics available yet. Start hiring to see quality metrics.</p>
                </div>
                <?php endif; ?>
            </div>
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
