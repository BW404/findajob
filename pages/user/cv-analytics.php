<?php
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/constants.php';

requireJobSeeker();

$userId = getCurrentUserId();

// Get all CVs with analytics
$stmt = $pdo->prepare("
    SELECT 
        c.*,
        COALESCE(c.view_count, 0) as view_count,
        COALESCE(c.download_count, 0) as download_count,
        (SELECT COUNT(*) FROM job_applications WHERE cv_id = c.id) as applications_count
    FROM cvs c
    WHERE c.user_id = ?
    ORDER BY c.is_primary DESC, c.view_count DESC
");
$stmt->execute([$userId]);
$cvs = $stmt->fetchAll();

// Calculate totals
$totalViews = array_sum(array_column($cvs, 'view_count'));
$totalDownloads = array_sum(array_column($cvs, 'download_count'));
$totalApplications = array_sum(array_column($cvs, 'applications_count'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CV Analytics - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .analytics-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid var(--primary-color);
        }

        .stat-card.views {
            border-left-color: #3b82f6;
        }

        .stat-card.downloads {
            border-left-color: #10b981;
        }

        .stat-card.applications {
            border-left-color: #f59e0b;
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            opacity: 0.8;
        }

        .stat-card.views .stat-icon {
            color: #3b82f6;
        }

        .stat-card.downloads .stat-icon {
            color: #10b981;
        }

        .stat-card.applications .stat-icon {
            color: #f59e0b;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #111827;
            margin: 0.5rem 0;
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .cv-analytics-table {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow-x: auto;
        }

        .cv-analytics-table h2 {
            margin: 0 0 1.5rem 0;
            font-size: 1.5rem;
            color: #111827;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 1rem;
            background: #f9fafb;
            color: #374151;
            font-weight: 600;
            border-bottom: 2px solid #e5e7eb;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid #f3f4f6;
        }

        tr:hover {
            background: #f9fafb;
        }

        .cv-title {
            font-weight: 600;
            color: #111827;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .primary-badge {
            background: #10b981;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .metric {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6b7280;
        }

        .metric i {
            color: #9ca3af;
        }

        .chart-container {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            max-height: 400px;
        }

        .no-data {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }

        .no-data i {
            font-size: 4rem;
            color: #e5e7eb;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            table {
                font-size: 0.9rem;
            }

            th, td {
                padding: 0.75rem 0.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>

    <div class="analytics-container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h1><i class="fas fa-chart-line"></i> CV Analytics</h1>
            <a href="cv-manager.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to CV Manager
            </a>
        </div>

        <?php if (empty($cvs)): ?>
            <div class="no-data">
                <i class="fas fa-chart-bar"></i>
                <h3>No CV Data Yet</h3>
                <p>Upload your first CV to start tracking analytics!</p>
                <a href="cv-manager.php" class="btn btn-primary" style="margin-top: 1rem;">
                    <i class="fas fa-upload"></i> Upload CV
                </a>
            </div>
        <?php else: ?>
            <div class="stats-grid">
                <div class="stat-card views">
                    <div class="stat-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div class="stat-value"><?php echo number_format($totalViews); ?></div>
                    <div class="stat-label">Total Views</div>
                </div>

                <div class="stat-card downloads">
                    <div class="stat-icon">
                        <i class="fas fa-download"></i>
                    </div>
                    <div class="stat-value"><?php echo number_format($totalDownloads); ?></div>
                    <div class="stat-label">Total Downloads</div>
                </div>

                <div class="stat-card applications">
                    <div class="stat-icon">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <div class="stat-value"><?php echo number_format($totalApplications); ?></div>
                    <div class="stat-label">Job Applications</div>
                </div>
            </div>

            <div class="chart-container">
                <h2>CV Performance Comparison</h2>
                <canvas id="cvChart"></canvas>
            </div>

            <div class="cv-analytics-table">
                <h2>Detailed CV Statistics</h2>
                <table>
                    <thead>
                        <tr>
                            <th>CV Name</th>
                            <th><i class="fas fa-eye"></i> Views</th>
                            <th><i class="fas fa-download"></i> Downloads</th>
                            <th><i class="fas fa-briefcase"></i> Applications</th>
                            <th>Last Activity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cvs as $cv): ?>
                            <tr>
                                <td>
                                    <div class="cv-title">
                                        <i class="fas fa-file-pdf" style="color: #dc2626;"></i>
                                        <span><?php echo htmlspecialchars($cv['title']); ?></span>
                                        <?php if ($cv['is_primary']): ?>
                                            <span class="primary-badge">PRIMARY</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="metric">
                                        <strong><?php echo number_format($cv['view_count']); ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <div class="metric">
                                        <strong><?php echo number_format($cv['download_count']); ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <div class="metric">
                                        <strong><?php echo number_format($cv['applications_count']); ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($cv['last_viewed_at']): ?>
                                        <small><?php echo date('M d, Y', strtotime($cv['last_viewed_at'])); ?></small>
                                    <?php else: ?>
                                        <small style="color: #9ca3af;">Never</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <script>
        <?php if (!empty($cvs)): ?>
        // Prepare chart data
        const cvNames = <?php echo json_encode(array_map(function($cv) {
            return substr($cv['title'], 0, 30) . (strlen($cv['title']) > 30 ? '...' : '');
        }, $cvs)); ?>;
        
        const viewsData = <?php echo json_encode(array_column($cvs, 'view_count')); ?>;
        const downloadsData = <?php echo json_encode(array_column($cvs, 'download_count')); ?>;
        const applicationsData = <?php echo json_encode(array_column($cvs, 'applications_count')); ?>;

        // Create chart
        const ctx = document.getElementById('cvChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: cvNames,
                datasets: [
                    {
                        label: 'Views',
                        data: viewsData,
                        backgroundColor: 'rgba(59, 130, 246, 0.5)',
                        borderColor: 'rgb(59, 130, 246)',
                        borderWidth: 2
                    },
                    {
                        label: 'Downloads',
                        data: downloadsData,
                        backgroundColor: 'rgba(16, 185, 129, 0.5)',
                        borderColor: 'rgb(16, 185, 129)',
                        borderWidth: 2
                    },
                    {
                        label: 'Applications',
                        data: applicationsData,
                        backgroundColor: 'rgba(245, 158, 11, 0.5)',
                        borderColor: 'rgb(245, 158, 11)',
                        borderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
