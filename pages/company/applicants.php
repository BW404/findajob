<?php
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/constants.php';
require_once '../../includes/email-notifications.php';

requireEmployer();

$userId = getCurrentUserId();

// Check if employer has Pro subscription
$stmt = $pdo->prepare("SELECT subscription_type, subscription_end FROM users WHERE id = ?");
$stmt->execute([$userId]);
$subscription = $stmt->fetch(PDO::FETCH_ASSOC);

$isPro = ($subscription['subscription_type'] === 'pro' && 
          (!$subscription['subscription_end'] || strtotime($subscription['subscription_end']) > time()));

// Get job ID if specified
$jobId = isset($_GET['job_id']) ? (int)$_GET['job_id'] : null;

// Get employer's jobs for filter dropdown
$stmt = $pdo->prepare("
    SELECT id, title 
    FROM jobs 
    WHERE employer_id = ? AND status != 'deleted'
    ORDER BY title
");
$stmt->execute([$userId]);
$employerJobs = $stmt->fetchAll();

// Build query for applications
$whereClause = "j.employer_id = ?";
$params = [$userId];

if ($jobId) {
    $whereClause .= " AND ja.job_id = ?";
    $params[] = $jobId;
}

// Get applications with job seeker details
$stmt = $pdo->prepare("
    SELECT ja.*, 
           j.title as job_title,
           u.first_name, u.last_name, u.email, u.phone,
           jsp.years_of_experience, jsp.job_status, jsp.education_level,
           jsp.nin_verified,
           ja.application_status as status,
           ja.applicant_name, ja.applicant_email, ja.applicant_phone,
           ja.application_message,
           ja.cv_id,
           cv.file_path as cv_file,
           cv.title as cv_title
    FROM job_applications ja
    JOIN jobs j ON ja.job_id = j.id
    JOIN users u ON ja.job_seeker_id = u.id
    LEFT JOIN job_seeker_profiles jsp ON u.id = jsp.user_id
    LEFT JOIN cvs cv ON ja.cv_id = cv.id
    WHERE {$whereClause}
    ORDER BY ja.applied_at DESC
");
$stmt->execute($params);
$applications = $stmt->fetchAll();

// Handle application status updates
if ($_POST && isset($_POST['action']) && isset($_POST['application_id'])) {
    $applicationId = (int)$_POST['application_id'];
    $action = $_POST['action'];
    
    // Verify application belongs to current employer's job
    $stmt = $pdo->prepare("
        SELECT ja.id 
        FROM job_applications ja
        JOIN jobs j ON ja.job_id = j.id 
        WHERE ja.id = ? AND j.employer_id = ?
    ");
    $stmt->execute([$applicationId, $userId]);
    
    if ($stmt->fetch()) {
        $validStatuses = ['applied', 'viewed', 'shortlisted', 'interviewed', 'offered', 'hired', 'rejected'];
        
        if (in_array($action, $validStatuses)) {
            $stmt = $pdo->prepare("UPDATE job_applications SET application_status = ?, responded_at = NOW() WHERE id = ?");
            $stmt->execute([$action, $applicationId]);
            
            // Send email notification to job seeker
            $emailResult = sendApplicationStatusEmail($applicationId, $action, $pdo);
            error_log("Email notification sent for application $applicationId, status: $action, result: " . ($emailResult ? 'success' : 'failed'));
            
            // Redirect to refresh page
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Applicants - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include '../../includes/employer-header.php'; ?>

    <main class="container">
        <div style="padding: 2rem 0;">
            <!-- Page Header -->
            <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <div>
                    <h1 style="margin: 0; font-size: 2.5rem; font-weight: 700; color: var(--text-primary);">
                        Manage Applicants
                    </h1>
                    <p style="margin: 0.5rem 0 0 0; color: var(--text-secondary); font-size: 1.1rem;">
                        Review and manage job applications
                    </p>
                </div>
            </div>

            <!-- Filters -->
            <div class="filters-section" style="background: var(--surface); padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <form method="GET" style="display: flex; gap: 1rem; align-items: end; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 250px;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Filter by Job:</label>
                        <select name="job_id" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 6px;">
                            <option value="">All Jobs</option>
                            <?php foreach ($employerJobs as $job): ?>
                                <option value="<?php echo $job['id']; ?>" <?php echo $jobId == $job['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($job['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <a href="?" class="btn btn-outline">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </form>
            </div>

            <!-- Applications Summary -->
            <div class="applications-summary" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                <?php
                $totalApplications = count($applications);
                $pendingApplications = count(array_filter($applications, fn($a) => $a['status'] === 'applied'));
                $reviewedApplications = count(array_filter($applications, fn($a) => in_array($a['status'], ['viewed', 'shortlisted', 'interviewed'])));
                $hiredApplications = count(array_filter($applications, fn($a) => $a['status'] === 'hired'));
                ?>
                
                <div class="summary-card" style="background: var(--surface); padding: 1.5rem; border-radius: 8px; text-align: center; border-left: 4px solid var(--primary);">
                    <div style="font-size: 2rem; font-weight: bold; color: var(--primary); margin-bottom: 0.5rem;"><?php echo $totalApplications; ?></div>
                    <div style="color: var(--text-secondary);">Total Applications</div>
                </div>
                
                <div class="summary-card" style="background: var(--surface); padding: 1.5rem; border-radius: 8px; text-align: center; border-left: 4px solid var(--warning);">
                    <div style="font-size: 2rem; font-weight: bold; color: var(--warning); margin-bottom: 0.5rem;"><?php echo $pendingApplications; ?></div>
                    <div style="color: var(--text-secondary);">Pending Review</div>
                </div>
                
                <div class="summary-card" style="background: var(--surface); padding: 1.5rem; border-radius: 8px; text-align: center; border-left: 4px solid var(--accent);">
                    <div style="font-size: 2rem; font-weight: bold; color: var(--accent); margin-bottom: 0.5rem;"><?php echo $reviewedApplications; ?></div>
                    <div style="color: var(--text-secondary);">Under Review</div>
                </div>
                
                <div class="summary-card" style="background: var(--surface); padding: 1.5rem; border-radius: 8px; text-align: center; border-left: 4px solid #10b981;">
                    <div style="font-size: 2rem; font-weight: bold; color: #10b981; margin-bottom: 0.5rem;"><?php echo $hiredApplications; ?></div>
                    <div style="color: var(--text-secondary);">Hired</div>
                </div>
            </div>

            <!-- Applications List -->
            <div class="applications-container" style="background: var(--surface); border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <?php if (empty($applications)): ?>
                    <div style="padding: 4rem; text-align: center; color: var(--text-secondary);">
                        <i class="fas fa-inbox" style="font-size: 4rem; margin-bottom: 2rem; color: var(--text-secondary); opacity: 0.5;"></i>
                        <h3>No applications yet</h3>
                        <p><?php echo $jobId ? 'This job hasn\'t received any applications yet.' : 'You haven\'t received any applications for your jobs yet.'; ?></p>
                        <?php if (!$jobId): ?>
                            <a href="post-job.php" class="btn btn-primary" style="margin-top: 1rem;">
                                <i class="fas fa-plus"></i> Post a Job
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="applications-list">
                        <?php foreach ($applications as $index => $application): ?>
                            <div class="application-row" style="padding: 2rem; border-bottom: 1px solid var(--border-color); <?php echo $index % 2 === 0 ? 'background: var(--surface);' : 'background: var(--background);'; ?>">
                                <div style="display: grid; grid-template-columns: 1fr auto; gap: 2rem; align-items: start;">
                                    <!-- Application Details -->
                                    <div>
                                        <!-- Applicant Info -->
                                        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                                            <div style="width: 60px; height: 60px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: bold;">
                                                <?php 
                                                    $displayName = !empty($application['applicant_name']) ? $application['applicant_name'] : ($application['first_name'] . ' ' . $application['last_name']);
                                                    $nameParts = explode(' ', trim($displayName));
                                                    $initials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));
                                                    echo $initials;
                                                ?>
                                            </div>
                                            <div>
                                                <h4 style="margin: 0; font-size: 1.3rem; font-weight: 600;">
                                                    <?php echo htmlspecialchars($displayName); ?>
                                                    <?php if (!empty($application['nin_verified'])): ?>
                                                        <span class="verified-badge" style="display: inline-flex; align-items: center; justify-content: center; width: 20px; height: 20px; background: #1877f2; border-radius: 50%; margin-left: 6px; position: relative; top: -1px;" title="NIN Verified">
                                                            <i class="fas fa-check" style="color: white; font-size: 11px;"></i>
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($application['applicant_name'])): ?>
                                                        <span style="background: #dcfce7; color: #166534; font-size: 0.75rem; padding: 0.25rem 0.5rem; border-radius: 4px; margin-left: 0.5rem; font-weight: 500;">Easy Apply</span>
                                                    <?php endif; ?>
                                                </h4>
                                                <p style="margin: 0.25rem 0; color: var(--text-secondary);">
                                                    <?php echo htmlspecialchars($application['job_status'] ?? 'Job Seeker'); ?>
                                                    <?php if (!empty($application['years_of_experience'])): ?>
                                                        • <?php echo $application['years_of_experience']; ?> years exp.
                                                    <?php endif; ?>
                                                </p>
                                                <p style="margin: 0; color: var(--text-secondary); font-size: 0.9rem;">
                                                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars(!empty($application['applicant_email']) ? $application['applicant_email'] : $application['email']); ?>
                                                    <?php 
                                                    $displayPhone = !empty($application['applicant_phone']) ? $application['applicant_phone'] : $application['phone'];
                                                    if ($displayPhone): 
                                                    ?>
                                                        • <i class="fas fa-phone"></i> <?php echo htmlspecialchars($displayPhone); ?>
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Job and Application Details -->
                                        <div style="background: var(--background); padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                                <div>
                                                    <strong>Job:</strong> <?php echo htmlspecialchars($application['job_title']); ?>
                                                </div>
                                                <div>
                                                    <strong>Applied:</strong> <?php echo date('M j, Y g:i A', strtotime($application['applied_at'])); ?>
                                                </div>
                                                <?php if (!empty($application['years_of_experience'])): ?>
                                                    <div>
                                                        <strong>Experience:</strong> <?php echo $application['years_of_experience']; ?> years
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (!empty($application['education_level'])): ?>
                                                    <div>
                                                        <strong>Education:</strong> <?php echo strtoupper(htmlspecialchars($application['education_level'])); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (!empty($application['cv_title'])): ?>
                                                    <div>
                                                        <strong>CV:</strong> <?php echo htmlspecialchars($application['cv_title']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Application Message / Cover Letter -->
                                        <?php 
                                        $displayMessage = !empty($application['application_message']) ? $application['application_message'] : $application['cover_letter'];
                                        if ($displayMessage): 
                                        ?>
                                            <div>
                                                <strong><?php echo !empty($application['application_message']) ? 'Application Message:' : 'Cover Letter:'; ?></strong>
                                                <div style="margin-top: 0.5rem; padding: 1rem; background: var(--background); border-radius: 8px; border-left: 4px solid var(--primary);">
                                                    <p style="margin: 0; line-height: 1.6; white-space: pre-wrap;">
                                                        <?php echo nl2br(htmlspecialchars(substr($displayMessage, 0, 300))); ?>
                                                        <?php if (strlen($displayMessage) > 300): ?>
                                                            <span id="more-<?php echo $application['id']; ?>" style="display: none;">
                                                                <?php echo nl2br(htmlspecialchars(substr($displayMessage, 300))); ?>
                                                            </span>
                                                            <a href="#" onclick="toggleMore(<?php echo $application['id']; ?>); return false;" style="color: var(--primary); font-weight: 500;">
                                                                <span id="toggle-text-<?php echo $application['id']; ?>">... Read more</span>
                                                            </a>
                                                        <?php endif; ?>
                                                    </p>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Status and Actions -->
                                    <div style="text-align: right; min-width: 200px;">
                                        <!-- Current Status -->
                                        <div style="margin-bottom: 1rem;">
                                            <?php
                                            $statusColors = [
                                                'applied' => '#f59e0b',
                                                'viewed' => '#3b82f6',
                                                'shortlisted' => '#8b5cf6',
                                                'interviewed' => '#06b6d4',
                                                'offered' => '#10b981',
                                                'hired' => '#10b981',
                                                'rejected' => '#ef4444'
                                            ];
                                            $statusColor = $statusColors[$application['status']] ?? '#6b7280';
                                            ?>
                                            <span style="background: <?php echo $statusColor; ?>; color: white; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.9rem; font-weight: 500;">
                                                <?php echo ucfirst($application['status']); ?>
                                            </span>
                                        </div>

                                        <!-- Action Buttons -->
                                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                            <?php if (!empty($application['cv_file'])): ?>
                                                <a href="/findajob/uploads/cvs/<?php echo htmlspecialchars($application['cv_file']); ?>" 
                                                   target="_blank" class="btn btn-outline btn-sm">
                                                    <i class="fas fa-file-pdf"></i> View CV
                                                </a>
                                            <?php elseif (!empty($application['cv_id'])): ?>
                                                <a href="/findajob/uploads/cvs/<?php echo $application['cv_id']; ?>.pdf" 
                                                   target="_blank" class="btn btn-outline btn-sm">
                                                    <i class="fas fa-file-pdf"></i> View CV
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($isPro): ?>
                                                <a href="send-private-offer.php?job_seeker_id=<?php echo $application['job_seeker_id']; ?>" 
                                                   class="btn btn-success btn-sm">
                                                    <i class="fas fa-envelope"></i> Send Private Offer
                                                </a>
                                            <?php else: ?>
                                                <a href="../payment/plans.php" 
                                                   class="btn btn-outline btn-sm" 
                                                   title="Upgrade to Pro to send private offers"
                                                   style="border-color: #f59e0b; color: #f59e0b;">
                                                    <i class="fas fa-crown"></i> Send Private Offer (Pro)
                                                </a>
                                            <?php endif; ?>
                                            
                                            <div class="dropdown" style="position: relative;">
                                                <button class="btn btn-primary btn-sm dropdown-toggle" onclick="toggleStatusDropdown(<?php echo $application['id']; ?>)">
                                                    <i class="fas fa-edit"></i> Update Status
                                                </button>
                                                <div class="dropdown-menu" id="status-dropdown-<?php echo $application['id']; ?>" style="display: none; position: absolute; right: 0; top: 100%; background: var(--surface); border: 1px solid var(--border-color); border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); z-index: 1000; min-width: 150px; margin-top: 0.25rem;">
                                                    <?php
                                                    $statuses = [
                                                        'applied' => 'Applied',
                                                        'viewed' => 'Viewed',
                                                        'shortlisted' => 'Shortlisted',
                                                        'interviewed' => 'Interviewed',
                                                        'offered' => 'Offered',
                                                        'hired' => 'Hired',
                                                        'rejected' => 'Rejected'
                                                    ];
                                                    
                                                    foreach ($statuses as $statusValue => $statusLabel):
                                                        if ($statusValue === $application['status']) continue;
                                                    ?>
                                                        <form method="POST" style="margin: 0;">
                                                            <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                                                            <input type="hidden" name="action" value="<?php echo $statusValue; ?>">
                                                            <button type="submit" class="dropdown-item" style="width: 100%; text-align: left; padding: 0.75rem 1rem; background: none; border: none; color: <?php echo $statusColors[$statusValue]; ?>;">
                                                                <?php echo $statusLabel; ?>
                                                            </button>
                                                        </form>
                                                    <?php endforeach; ?>
                                                </div>
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
        function toggleStatusDropdown(applicationId) {
            const dropdown = document.getElementById(`status-dropdown-${applicationId}`);
            const allDropdowns = document.querySelectorAll('.dropdown-menu');
            
            // Close all other dropdowns
            allDropdowns.forEach(dd => {
                if (dd.id !== `status-dropdown-${applicationId}`) {
                    dd.style.display = 'none';
                }
            });
            
            // Toggle current dropdown
            dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        }

        function toggleMore(applicationId) {
            const more = document.getElementById(`more-${applicationId}`);
            const toggleText = document.getElementById(`toggle-text-${applicationId}`);
            
            if (more.style.display === 'none') {
                more.style.display = 'inline';
                toggleText.textContent = ' Read less';
            } else {
                more.style.display = 'none';
                toggleText.textContent = '... Read more';
            }
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
        
        .application-row:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transform: translateY(-1px);
            transition: all 0.2s ease;
        }
    </style>
</body>
</html>