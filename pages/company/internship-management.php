<?php
require_once '../../config/database.php';
require_once '../../config/session.php';

requireEmployer();

$userId = getCurrentUserId();

// Get user data
$stmt = $pdo->prepare("SELECT u.*, ep.* FROM users u LEFT JOIN employer_profiles ep ON u.id = ep.user_id WHERE u.id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$isPro = ($user['subscription_type'] === 'pro' && 
          (!$user['subscription_end'] || strtotime($user['subscription_end']) > time()));

// Handle internship actions
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $internship_id = intval($_POST['internship_id'] ?? 0);
    
    try {
        if ($action === 'confirm_intern') {
            // Confirm intern selection and create internship record
            $application_id = intval($_POST['application_id']);
            $job_id = intval($_POST['job_id']);
            $start_date = $_POST['start_date'];
            $duration_months = intval($_POST['duration_months']);
            
            // Calculate end date
            $end_date = date('Y-m-d', strtotime($start_date . " + $duration_months months"));
            
            // Get job seeker ID from application
            $app_stmt = $pdo->prepare("SELECT job_seeker_id FROM job_applications WHERE id = ?");
            $app_stmt->execute([$application_id]);
            $application = $app_stmt->fetch();
            
            if ($application) {
                // Create internship record
                $stmt = $pdo->prepare("
                    INSERT INTO internships 
                    (job_id, application_id, job_seeker_id, employer_id, start_date, end_date, duration_months, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
                ");
                $stmt->execute([$job_id, $application_id, $application['job_seeker_id'], $userId, $start_date, $end_date, $duration_months]);
                
                // Update application status
                $update_stmt = $pdo->prepare("UPDATE job_applications SET application_status = 'hired' WHERE id = ?");
                $update_stmt->execute([$application_id]);
                
                $success = "Internship confirmed successfully!";
            }
        } elseif ($action === 'complete_internship') {
            // Mark internship as completed and award badge
            $performance_rating = intval($_POST['performance_rating']);
            $employer_feedback = trim($_POST['employer_feedback']);
            
            // Update internship record
            $stmt = $pdo->prepare("
                UPDATE internships 
                SET status = 'completed', 
                    completion_confirmed_by_employer = 1,
                    completion_confirmed_at = NOW(),
                    employer_feedback = ?,
                    performance_rating = ?,
                    badge_awarded = 1,
                    badge_awarded_at = NOW()
                WHERE id = ? AND employer_id = ?
            ");
            $stmt->execute([$employer_feedback, $performance_rating, $internship_id, $userId]);
            
            // Get internship details for badge
            $int_stmt = $pdo->prepare("
                SELECT i.*, j.title as job_title, ep.company_name 
                FROM internships i
                JOIN jobs j ON i.job_id = j.id
                JOIN employer_profiles ep ON i.employer_id = ep.user_id
                WHERE i.id = ?
            ");
            $int_stmt->execute([$internship_id]);
            $internship = $int_stmt->fetch();
            
            if ($internship) {
                // Create badge record
                $badge_stmt = $pdo->prepare("
                    INSERT INTO internship_badges 
                    (job_seeker_id, internship_id, company_name, job_title, start_date, end_date, duration_months, performance_rating, employer_feedback)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $badge_stmt->execute([
                    $internship['job_seeker_id'],
                    $internship_id,
                    $internship['company_name'],
                    $internship['job_title'],
                    $internship['start_date'],
                    $internship['end_date'],
                    $internship['duration_months'],
                    $performance_rating,
                    $employer_feedback
                ]);
                
                $success = "Internship completed and badge awarded successfully!";
            }
        } elseif ($action === 'mark_completed') {
            // Simple completion without badge/rating
            $stmt = $pdo->prepare("
                UPDATE internships 
                SET status = 'completed', 
                    completion_confirmed_by_employer = 1,
                    completion_confirmed_at = NOW()
                WHERE id = ? AND employer_id = ?
            ");
            $stmt->execute([$internship_id, $userId]);
            
            $success = "Internship marked as completed.";
        } elseif ($action === 'terminate_internship') {
            $termination_reason = trim($_POST['termination_reason']);
            
            $stmt = $pdo->prepare("
                UPDATE internships 
                SET status = 'terminated', 
                    employer_feedback = ?
                WHERE id = ? AND employer_id = ?
            ");
            $stmt->execute([$termination_reason, $internship_id, $userId]);
            
            $success = "Internship terminated.";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get all internships
$internships_stmt = $pdo->prepare("
    SELECT 
        i.*,
        j.title as job_title,
        j.slug as job_slug,
        CONCAT(u.first_name, ' ', u.last_name) as intern_name,
        u.email as intern_email,
        u.phone as intern_phone
    FROM internships i
    JOIN jobs j ON i.job_id = j.id
    JOIN users u ON i.job_seeker_id = u.id
    WHERE i.employer_id = ?
    ORDER BY i.created_at DESC
");
$internships_stmt->execute([$userId]);
$internships = $internships_stmt->fetchAll();

// Get internship applications (applications for internship type jobs)
$applications_stmt = $pdo->prepare("
    SELECT 
        ja.*,
        j.title as job_title,
        j.id as job_id,
        CONCAT(u.first_name, ' ', u.last_name) as applicant_name,
        u.email as applicant_email
    FROM job_applications ja
    JOIN jobs j ON ja.job_id = j.id
    JOIN users u ON ja.job_seeker_id = u.id
    LEFT JOIN internships i ON ja.id = i.application_id
    WHERE j.employer_id = ? 
    AND j.job_type = 'internship'
    AND ja.application_status NOT IN ('rejected', 'hired', 'offered')
    AND i.id IS NULL
    ORDER BY ja.applied_at DESC
");
$applications_stmt->execute([$userId]);
$pending_applications = $applications_stmt->fetchAll();

// Get hired applications without internship records (orphaned)
$orphaned_stmt = $pdo->prepare("
    SELECT 
        ja.*,
        j.title as job_title,
        j.id as job_id,
        CONCAT(u.first_name, ' ', u.last_name) as applicant_name,
        u.email as applicant_email
    FROM job_applications ja
    JOIN jobs j ON ja.job_id = j.id
    JOIN users u ON ja.job_seeker_id = u.id
    LEFT JOIN internships i ON ja.id = i.application_id
    WHERE j.employer_id = ? 
    AND j.job_type = 'internship'
    AND ja.application_status = 'hired'
    AND i.id IS NULL
    ORDER BY ja.applied_at DESC
");
$orphaned_stmt->execute([$userId]);
$orphaned_hires = $orphaned_stmt->fetchAll();

// Get employer's internship job postings
$internship_jobs_stmt = $pdo->prepare("
    SELECT j.*, (SELECT COUNT(*) FROM job_applications ja WHERE ja.job_id = j.id) as applicant_count
    FROM jobs j
    WHERE j.employer_id = ? AND j.job_type = 'internship'
    ORDER BY j.created_at DESC
");
$internship_jobs_stmt->execute([$userId]);
$internship_jobs = $internship_jobs_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internship Management - FindAJob Nigeria</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    .internship-container {
        max-width: 1400px;
        margin: 2rem auto;
        padding: 0 2rem;
    }
    .tabs {
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
        border-bottom: 2px solid var(--border-color);
    }
    .tab {
        padding: 1rem 2rem;
        background: none;
        border: none;
        cursor: pointer;
        font-size: 1rem;
        font-weight: 600;
        color: var(--text-secondary);
        border-bottom: 3px solid transparent;
        transition: all 0.3s;
    }
    .tab.active {
        color: var(--primary);
        border-bottom-color: var(--primary);
    }
    .tab-content {
        display: none;
    }
    .tab-content.active {
        display: block;
    }
    .internship-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: transform 0.3s;
    }
    .internship-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .status-badge {
        display: inline-block;
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    .status-active { background: #d1fae5; color: #065f46; }
    .status-pending { background: #fef3c7; color: #92400e; }
    .status-completed { background: #dbeafe; color: #1e40af; }
    .status-terminated { background: #fee2e2; color: #991b1b; }
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }
    .modal.active {
        display: flex;
    }
    .modal-content {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        max-width: 600px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
    }
    .rating-stars {
        display: flex;
        gap: 0.5rem;
        font-size: 2rem;
    }
    .rating-stars .star {
        cursor: pointer;
        color: #d1d5db;
        transition: color 0.2s;
    }
    .rating-stars .star.active,
    .rating-stars .star:hover {
        color: #fbbf24;
    }
</style>
</head>
<body class="has-bottom-nav">
    <?php include '../../includes/employer-header.php'; ?>

    <main class="internship-container">
    <div class="page-header" style="margin-bottom: 2rem;">
        <h1 style="font-size: 2rem; color: var(--text-primary); margin-bottom: 0.5rem;">
            <i class="fas fa-graduation-cap"></i> Internship Management
        </h1>
        <p style="color: var(--text-secondary);">Manage internships and award badges to successful interns</p>
    </div>

    <?php if ($success): ?>
    <div style="background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <div class="tabs">
        <button class="tab active" onclick="switchTab('pending')">
            <i class="fas fa-clock"></i> Pending Applications (<?php echo count($pending_applications); ?>)
        </button>
        <button class="tab" onclick="switchTab('jobs')">
            <i class="fas fa-briefcase"></i> Internship Jobs (<?php echo count($internship_jobs); ?>)
        </button>
        <button class="tab" onclick="switchTab('active')">
            <i class="fas fa-user-graduate"></i> Active Internships
        </button>
        <button class="tab" onclick="switchTab('completed')">
            <i class="fas fa-award"></i> Completed
        </button>
        <button class="tab" onclick="switchTab('all')">
            <i class="fas fa-list"></i> All Internships
        </button>
    </div>
    
    <!-- Internship Jobs Tab -->
    <div id="jobs-tab" class="tab-content">
        <h2 style="margin-bottom: 1rem; color: var(--text-primary);">Your Internship Job Postings</h2>
        <?php if (count($internship_jobs) > 0): ?>
            <?php foreach ($internship_jobs as $job): ?>
                <div class="internship-card" style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color); margin-bottom: 1rem;">
                    <div style="flex: 1;">
                        <h3 style="margin-bottom: 0.25rem; color: var(--text-primary);">
                            <a href="../../pages/jobs/view.php?slug=<?php echo urlencode($job['slug']); ?>"><?php echo htmlspecialchars($job['title']); ?></a>
                        </h3>
                        <p style="color: var(--text-secondary); margin: 0; font-size: 0.95rem;">
                            <?php echo htmlspecialchars($job['city'] ?? ''); ?><?php if (!empty($job['city']) && !empty($job['state'])) echo ', '; ?><?php echo htmlspecialchars($job['state'] ?? ''); ?>
                        </p>
                        <p style="color: var(--text-secondary); margin-top: 0.5rem; font-size: 0.95rem;">
                            <strong><?php echo intval($job['applicant_count']); ?></strong> applicant<?php echo intval($job['applicant_count']) !== 1 ? 's' : ''; ?>
                        </p>
                    </div>
                    <div style="display:flex; gap:0.5rem;">
                        <a class="btn" href="../../pages/company/edit-job.php?id=<?php echo intval($job['id']); ?>">Edit</a>
                        <a class="btn btn-outline" href="../../pages/jobs/view.php?slug=<?php echo urlencode($job['slug']); ?>" target="_blank">Preview</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="color: var(--text-secondary);">You don't have any internship job postings yet. <a href="../../pages/company/post-job.php">Post one now</a>.</div>
        <?php endif; ?>
    </div>

    <!-- Pending Applications Tab -->
    <div id="pending-tab" class="tab-content active">
        <h2 style="margin-bottom: 1.5rem; color: var(--text-primary);">Pending Internship Applications</h2>
        <?php if (count($pending_applications) > 0): ?>
            <?php foreach ($pending_applications as $app): ?>
            <div class="internship-card">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div style="flex: 1;">
                        <h3 style="margin-bottom: 0.5rem; color: var(--text-primary);">
                            <?php echo htmlspecialchars($app['applicant_name']); ?>
                        </h3>
                        <p style="color: var(--text-secondary); margin-bottom: 0.5rem;">
                            <i class="fas fa-briefcase"></i> Applied for: <strong><?php echo htmlspecialchars($app['job_title']); ?></strong>
                        </p>
                        <p style="color: var(--text-secondary); font-size: 0.9rem;">
                            <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($app['applicant_email']); ?>
                        </p>
                        <p style="color: var(--text-secondary); font-size: 0.9rem;">
                            <i class="fas fa-calendar"></i> Applied: <?php echo date('M d, Y', strtotime($app['applied_at'])); ?>
                        </p>
                    </div>
                    <div style="text-align: right;">
                        <span class="status-badge status-<?php echo $app['application_status']; ?>">
                            <?php echo ucfirst($app['application_status']); ?>
                        </span>
                        <div style="margin-top: 1rem;">
                            <button onclick="openConfirmModal(<?php echo $app['id']; ?>, <?php echo $app['job_id']; ?>, '<?php echo htmlspecialchars($app['applicant_name']); ?>', '<?php echo htmlspecialchars($app['job_title']); ?>')" 
                                    class="btn btn-primary" style="font-size: 0.9rem;">
                                <i class="fas fa-check"></i> Confirm Intern
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 3rem; background: white; border-radius: 12px;">
                <i class="fas fa-inbox" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 1rem;"></i>
                <p style="color: var(--text-secondary);">No pending internship applications</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Active Internships Tab -->
    <div id="active-tab" class="tab-content">
        <h2 style="margin-bottom: 1.5rem; color: var(--text-primary);">Active Internships</h2>
        
        <?php if (count($orphaned_hires) > 0): ?>
        <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 1rem; border-radius: 8px; margin-bottom: 2rem;">
            <h3 style="color: #92400e; margin-bottom: 0.5rem;"><i class="fas fa-exclamation-triangle"></i> Action Required</h3>
            <p style="color: #78350f; margin-bottom: 1rem;">The following applications were marked as "Hired" but internship details were not recorded. Please confirm them properly:</p>
            
            <?php foreach ($orphaned_hires as $app): ?>
            <div class="internship-card" style="background: white; margin-bottom: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div style="flex: 1;">
                        <h3 style="margin-bottom: 0.5rem; color: var(--text-primary);">
                            <?php echo htmlspecialchars($app['applicant_name']); ?>
                        </h3>
                        <p style="color: var(--text-secondary); margin-bottom: 0.5rem;">
                            <i class="fas fa-briefcase"></i> Applied for: <strong><?php echo htmlspecialchars($app['job_title']); ?></strong>
                        </p>
                        <p style="color: var(--text-secondary); font-size: 0.9rem;">
                            <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($app['applicant_email']); ?>
                        </p>
                        <p style="color: var(--text-secondary); font-size: 0.9rem;">
                            <i class="fas fa-calendar"></i> Applied: <?php echo date('M d, Y', strtotime($app['applied_at'])); ?>
                        </p>
                    </div>
                    <div style="text-align: right;">
                        <span class="status-badge" style="background: #fef3c7; color: #92400e; border: 1px solid #f59e0b;">Needs Confirmation</span>
                        <div style="margin-top: 1rem;">
                            <button onclick="openConfirmModal(<?php echo $app['id']; ?>, <?php echo $app['job_id']; ?>, '<?php echo htmlspecialchars($app['applicant_name']); ?>', '<?php echo htmlspecialchars($app['job_title']); ?>')" 
                                    class="btn btn-primary" style="font-size: 0.9rem;">
                                <i class="fas fa-check-circle"></i> Set Internship Dates
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <?php 
        $active_internships = array_filter($internships, function($i) { return $i['status'] === 'active'; });
        if (count($active_internships) > 0): 
        ?>
            <?php foreach ($active_internships as $intern): ?>
            <div class="internship-card">
                <div style="display: grid; grid-template-columns: 1fr auto; gap: 2rem;">
                    <div>
                        <h3 style="margin-bottom: 0.5rem; color: var(--text-primary);">
                            <?php echo htmlspecialchars($intern['intern_name']); ?>
                        </h3>
                        <p style="color: var(--text-secondary); margin-bottom: 0.5rem;">
                            <i class="fas fa-briefcase"></i> <strong><?php echo htmlspecialchars($intern['job_title']); ?></strong>
                        </p>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-top: 1rem;">
                            <p style="color: var(--text-secondary); font-size: 0.9rem;">
                                <i class="fas fa-calendar-check"></i> Start: <?php echo date('M d, Y', strtotime($intern['start_date'])); ?>
                            </p>
                            <p style="color: var(--text-secondary); font-size: 0.9rem;">
                                <i class="fas fa-calendar-times"></i> End: <?php echo date('M d, Y', strtotime($intern['end_date'])); ?>
                            </p>
                            <p style="color: var(--text-secondary); font-size: 0.9rem;">
                                <i class="fas fa-clock"></i> Duration: <?php echo $intern['duration_months']; ?> months
                            </p>
                            <p style="color: var(--text-secondary); font-size: 0.9rem;">
                                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($intern['intern_email']); ?>
                            </p>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <span class="status-badge status-active">Active</span>
                        <div style="margin-top: 1rem; display: flex; flex-direction: column; gap: 0.5rem;">
                            <button onclick="openCompleteModal(<?php echo $intern['id']; ?>, '<?php echo htmlspecialchars($intern['intern_name']); ?>')" 
                                    class="btn btn-primary" style="font-size: 0.9rem;">
                                <i class="fas fa-award"></i> Complete & Award Badge
                            </button>
                            <button onclick="openTerminateModal(<?php echo $intern['id']; ?>)" 
                                    class="btn btn-secondary" style="font-size: 0.9rem;">
                                <i class="fas fa-times"></i> Terminate
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 3rem; background: white; border-radius: 12px;">
                <i class="fas fa-user-graduate" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 1rem;"></i>
                <p style="color: var(--text-secondary);">No active internships</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Completed Tab -->
    <div id="completed-tab" class="tab-content">
        <h2 style="margin-bottom: 1.5rem; color: var(--text-primary);">Completed Internships</h2>
        <?php 
        $completed_internships = array_filter($internships, function($i) { return $i['status'] === 'completed'; });
        if (count($completed_internships) > 0): 
        ?>
            <?php foreach ($completed_internships as $intern): ?>
            <div class="internship-card">
                <div style="display: grid; grid-template-columns: 1fr auto; gap: 2rem;">
                    <div>
                        <h3 style="margin-bottom: 0.5rem; color: var(--text-primary);">
                            <?php echo htmlspecialchars($intern['intern_name']); ?>
                            <?php if ($intern['badge_awarded']): ?>
                            <i class="fas fa-award" style="color: #fbbf24;" title="Badge Awarded"></i>
                            <?php endif; ?>
                        </h3>
                        <p style="color: var(--text-secondary); margin-bottom: 0.5rem;">
                            <i class="fas fa-briefcase"></i> <strong><?php echo htmlspecialchars($intern['job_title']); ?></strong>
                        </p>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-top: 1rem;">
                            <p style="color: var(--text-secondary); font-size: 0.9rem;">
                                <i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($intern['start_date'])); ?> - <?php echo date('M d, Y', strtotime($intern['end_date'])); ?>
                            </p>
                            <p style="color: var(--text-secondary); font-size: 0.9rem;">
                                <i class="fas fa-clock"></i> <?php echo $intern['duration_months']; ?> months
                            </p>
                            <?php if ($intern['performance_rating']): ?>
                            <p style="color: var(--text-secondary); font-size: 0.9rem;">
                                <i class="fas fa-star" style="color: #fbbf24;"></i> Rating: <?php echo $intern['performance_rating']; ?>/5
                            </p>
                            <?php endif; ?>
                        </div>
                        <?php if ($intern['employer_feedback']): ?>
                        <div style="margin-top: 1rem; padding: 1rem; background: #f9fafb; border-radius: 8px;">
                            <strong style="color: var(--text-primary); font-size: 0.9rem;">Your Feedback:</strong>
                            <p style="color: var(--text-secondary); font-size: 0.9rem; margin-top: 0.5rem;">
                                <?php echo nl2br(htmlspecialchars($intern['employer_feedback'])); ?>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div style="text-align: right;">
                        <span class="status-badge status-completed">Completed</span>
                        <?php if ($intern['badge_awarded']): ?>
                        <p style="color: #065f46; margin-top: 0.5rem; font-size: 0.9rem;">
                            <i class="fas fa-check-circle"></i> Badge Awarded
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 3rem; background: white; border-radius: 12px;">
                <i class="fas fa-award" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 1rem;"></i>
                <p style="color: var(--text-secondary);">No completed internships yet</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- All Internships Tab -->
    <div id="all-tab" class="tab-content">
        <h2 style="margin-bottom: 1.5rem; color: var(--text-primary);">All Internships</h2>
        <?php if (count($internships) > 0): ?>
            <?php foreach ($internships as $intern): ?>
            <div class="internship-card">
                <div style="display: grid; grid-template-columns: 1fr auto; gap: 2rem;">
                    <div>
                        <h3 style="margin-bottom: 0.5rem; color: var(--text-primary);">
                            <?php echo htmlspecialchars($intern['intern_name']); ?>
                        </h3>
                        <p style="color: var(--text-secondary); margin-bottom: 0.5rem;">
                            <i class="fas fa-briefcase"></i> <strong><?php echo htmlspecialchars($intern['job_title']); ?></strong>
                        </p>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-top: 1rem;">
                            <p style="color: var(--text-secondary); font-size: 0.9rem;">
                                <i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($intern['start_date'])); ?> - <?php echo date('M d, Y', strtotime($intern['end_date'])); ?>
                            </p>
                            <p style="color: var(--text-secondary); font-size: 0.9rem;">
                                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($intern['intern_email']); ?>
                            </p>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <span class="status-badge status-<?php echo $intern['status']; ?>">
                            <?php echo ucfirst($intern['status']); ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 3rem; background: white; border-radius: 12px;">
                <i class="fas fa-graduation-cap" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 1rem;"></i>
                <p style="color: var(--text-secondary);">No internships yet</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- Confirm Intern Modal -->
<div id="confirmModal" class="modal">
    <div class="modal-content">
        <h2 style="margin-bottom: 1.5rem; color: var(--text-primary);">
            <i class="fas fa-user-check"></i> Confirm Internship
        </h2>
        <form method="POST">
            <input type="hidden" name="action" value="confirm_intern">
            <input type="hidden" name="application_id" id="confirm_application_id">
            <input type="hidden" name="job_id" id="confirm_job_id">
            
            <div style="margin-bottom: 1.5rem;">
                <p style="color: var(--text-secondary);">
                    You are about to confirm <strong id="confirm_intern_name"></strong> for the <strong id="confirm_job_title"></strong> internship position.
                </p>
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Start Date *</label>
                <input type="date" name="start_date" required 
                       style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px;">
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Duration (Months) *</label>
                <select name="duration_months" required 
                        style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px;">
                    <option value="1">1 Month</option>
                    <option value="2">2 Months</option>
                    <option value="3" selected>3 Months</option>
                    <option value="4">4 Months</option>
                    <option value="5">5 Months</option>
                    <option value="6">6 Months</option>
                    <option value="9">9 Months</option>
                    <option value="12">12 Months</option>
                </select>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" onclick="closeModal('confirmModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check"></i> Confirm Internship
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Complete Internship Modal -->
<div id="completeModal" class="modal">
    <div class="modal-content">
        <h2 style="margin-bottom: 1.5rem; color: var(--text-primary);">
            <i class="fas fa-award"></i> Complete Internship & Award Badge
        </h2>
        <form method="POST">
            <input type="hidden" name="action" value="complete_internship">
            <input type="hidden" name="internship_id" id="complete_internship_id">
            
            <div style="margin-bottom: 1.5rem;">
                <p style="color: var(--text-secondary);">
                    You are about to mark the internship for <strong id="complete_intern_name"></strong> as completed and award them an Internship Badge.
                </p>
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Performance Rating *</label>
                <div class="rating-stars" id="ratingStars">
                    <span class="star" data-rating="1">★</span>
                    <span class="star" data-rating="2">★</span>
                    <span class="star" data-rating="3">★</span>
                    <span class="star" data-rating="4">★</span>
                    <span class="star" data-rating="5">★</span>
                </div>
                <input type="hidden" name="performance_rating" id="performance_rating" required>
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Feedback (Optional)</label>
                <textarea name="employer_feedback" rows="4" 
                          style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px;"
                          placeholder="Share your thoughts about the intern's performance..."></textarea>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" onclick="closeModal('completeModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-award"></i> Complete & Award Badge
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Terminate Modal -->
<div id="terminateModal" class="modal">
    <div class="modal-content">
        <h2 style="margin-bottom: 1.5rem; color: #991b1b;">
            <i class="fas fa-times-circle"></i> Terminate Internship
        </h2>
        <form method="POST">
            <input type="hidden" name="action" value="terminate_internship">
            <input type="hidden" name="internship_id" id="terminate_internship_id">
            
            <div style="margin-bottom: 1.5rem;">
                <p style="color: var(--text-secondary);">
                    Are you sure you want to terminate this internship? This action cannot be undone.
                </p>
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Reason for Termination</label>
                <textarea name="termination_reason" rows="4" 
                          style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px;"
                          placeholder="Please provide a reason..."></textarea>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" onclick="closeModal('terminateModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn" style="background: #dc2626; color: white;">
                    <i class="fas fa-times"></i> Terminate Internship
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function switchTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Show selected tab
    document.getElementById(tabName + '-tab').classList.add('active');
    event.target.closest('.tab').classList.add('active');
}

function openConfirmModal(applicationId, jobId, internName, jobTitle) {
    document.getElementById('confirm_application_id').value = applicationId;
    document.getElementById('confirm_job_id').value = jobId;
    document.getElementById('confirm_intern_name').textContent = internName;
    document.getElementById('confirm_job_title').textContent = jobTitle;
    document.getElementById('confirmModal').classList.add('active');
}

function openCompleteModal(internshipId, internName) {
    document.getElementById('complete_internship_id').value = internshipId;
    document.getElementById('complete_intern_name').textContent = internName;
    document.getElementById('completeModal').classList.add('active');
}

function openTerminateModal(internshipId) {
    document.getElementById('terminate_internship_id').value = internshipId;
    document.getElementById('terminateModal').classList.add('active');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

// Close modal on outside click
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal(this.id);
        }
    });
});

// Rating stars functionality
const stars = document.querySelectorAll('.rating-stars .star');
const ratingInput = document.getElementById('performance_rating');

stars.forEach(star => {
    star.addEventListener('click', function() {
        const rating = this.getAttribute('data-rating');
        ratingInput.value = rating;
        
        stars.forEach(s => {
            if (s.getAttribute('data-rating') <= rating) {
                s.classList.add('active');
            } else {
                s.classList.remove('active');
            }
        });
    });
    
    star.addEventListener('mouseenter', function() {
        const rating = this.getAttribute('data-rating');
        stars.forEach(s => {
            if (s.getAttribute('data-rating') <= rating) {
                s.style.color = '#fbbf24';
            } else {
                s.style.color = '#d1d5db';
            }
        });
    });
});

document.querySelector('.rating-stars').addEventListener('mouseleave', function() {
    const currentRating = ratingInput.value;
    stars.forEach(s => {
        if (currentRating && s.getAttribute('data-rating') <= currentRating) {
            s.style.color = '#fbbf24';
        } else {
            s.style.color = '#d1d5db';
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>
