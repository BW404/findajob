<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/constants.php';

requireJobSeeker();

$userId = getCurrentUserId();

// Get upcoming interviews
$stmt = $pdo->prepare("
    SELECT ja.*, 
           j.title as job_title,
           j.state,
           j.city,
           j.job_type,
           ep.company_name,
           ep.company_logo,
           ep.website
    FROM job_applications ja
    JOIN jobs j ON ja.job_id = j.id
    LEFT JOIN employer_profiles ep ON j.employer_id = ep.user_id
    WHERE ja.job_seeker_id = ? 
    AND ja.interview_date IS NOT NULL
    ORDER BY ja.interview_date ASC
");
$stmt->execute([$userId]);
$interviews = $stmt->fetchAll();

// Separate into upcoming and past
$now = new DateTime();
$upcomingInterviews = [];
$pastInterviews = [];

foreach ($interviews as $interview) {
    $interviewDate = new DateTime($interview['interview_date']);
    if ($interviewDate >= $now) {
        $upcomingInterviews[] = $interview;
    } else {
        $pastInterviews[] = $interview;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Interviews - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>

    <main style="max-width: 1200px; margin: 2rem auto; padding: 0 1rem;">
        <div class="page-header" style="margin-bottom: 2rem;">
            <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">
                <i class="fas fa-calendar-alt" style="color: var(--primary);"></i>
                My Interviews
            </h1>
            <p style="color: var(--text-secondary);">View and manage your scheduled interviews</p>
        </div>

        <!-- Upcoming Interviews -->
        <div style="margin-bottom: 3rem;">
            <h2 style="font-size: 1.5rem; margin-bottom: 1rem; color: #10b981;">
                <i class="fas fa-clock"></i> Upcoming Interviews (<?php echo count($upcomingInterviews); ?>)
            </h2>
            
            <?php if (empty($upcomingInterviews)): ?>
                <div style="background: var(--surface); padding: 3rem; text-align: center; border-radius: 12px; border: 2px dashed var(--border-color);">
                    <i class="fas fa-calendar-check" style="font-size: 4rem; color: var(--text-secondary); opacity: 0.3; margin-bottom: 1rem;"></i>
                    <h3 style="color: var(--text-secondary); margin-bottom: 0.5rem;">No Upcoming Interviews</h3>
                    <p style="color: var(--text-secondary);">When employers schedule interviews, they'll appear here.</p>
                    <a href="../jobs/browse.php" class="btn btn-primary" style="margin-top: 1rem;">
                        <i class="fas fa-search"></i> Browse Jobs
                    </a>
                </div>
            <?php else: ?>
                <div style="display: grid; gap: 1.5rem;">
                    <?php foreach ($upcomingInterviews as $interview): 
                        $interviewDate = new DateTime($interview['interview_date']);
                        $daysUntil = $now->diff($interviewDate)->days;
                        $isToday = $daysUntil === 0;
                        $isTomorrow = $daysUntil === 1;
                    ?>
                        <div class="interview-card" style="background: var(--surface); padding: 1.5rem; border-radius: 12px; border: 2px solid <?php echo $isToday ? '#10b981' : 'var(--border-color)'; ?>; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                            <div style="display: flex; gap: 1.5rem; flex-wrap: wrap;">
                                <!-- Date Badge -->
                                <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 1rem; border-radius: 12px; text-align: center; min-width: 120px; height: fit-content;">
                                    <div style="font-size: 2rem; font-weight: 700; line-height: 1;">
                                        <?php echo $interviewDate->format('j'); ?>
                                    </div>
                                    <div style="font-size: 0.9rem; margin-top: 0.25rem;">
                                        <?php echo $interviewDate->format('M Y'); ?>
                                    </div>
                                    <div style="font-size: 1.1rem; font-weight: 600; margin-top: 0.5rem; border-top: 1px solid rgba(255,255,255,0.3); padding-top: 0.5rem;">
                                        <?php echo $interviewDate->format('g:i A'); ?>
                                    </div>
                                </div>

                                <!-- Interview Details -->
                                <div style="flex: 1; min-width: 300px;">
                                    <?php if ($isToday): ?>
                                        <div style="background: #10b981; color: white; display: inline-block; padding: 0.25rem 0.75rem; border-radius: 4px; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.5rem;">
                                            <i class="fas fa-clock"></i> TODAY
                                        </div>
                                    <?php elseif ($isTomorrow): ?>
                                        <div style="background: #f59e0b; color: white; display: inline-block; padding: 0.25rem 0.75rem; border-radius: 4px; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.5rem;">
                                            <i class="fas fa-clock"></i> TOMORROW
                                        </div>
                                    <?php endif; ?>

                                    <h3 style="font-size: 1.3rem; font-weight: 700; margin: 0 0 0.5rem 0;">
                                        <?php echo htmlspecialchars($interview['job_title']); ?>
                                    </h3>
                                    
                                    <p style="color: var(--text-secondary); margin: 0 0 1rem 0; font-size: 1.1rem;">
                                        <i class="fas fa-building"></i>
                                        <?php echo htmlspecialchars($interview['company_name']); ?>
                                    </p>

                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.75rem; margin-bottom: 1rem;">
                                        <div>
                                            <div style="font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.25rem;">
                                                Interview Type
                                            </div>
                                            <div style="font-weight: 600;">
                                                <?php
                                                $typeIcons = [
                                                    'phone' => '📞 Phone',
                                                    'video' => '🎥 Video Call',
                                                    'in_person' => '🏢 In-Person',
                                                    'online' => '💻 Online'
                                                ];
                                                echo $typeIcons[$interview['interview_type']] ?? ucfirst($interview['interview_type']);
                                                ?>
                                            </div>
                                        </div>
                                        
                                        <?php 
                                        $location = [];
                                        if (!empty($interview['city'])) $location[] = $interview['city'];
                                        if (!empty($interview['state'])) $location[] = $interview['state'];
                                        if (count($location) > 0): 
                                        ?>
                                            <div>
                                                <div style="font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.25rem;">
                                                    Location
                                                </div>
                                                <div style="font-weight: 600;">
                                                    <?php echo htmlspecialchars(implode(', ', $location)); ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div>
                                            <div style="font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.25rem;">
                                                Time Until
                                            </div>
                                            <div style="font-weight: 600; color: #10b981;">
                                                <?php 
                                                if ($isToday) {
                                                    echo 'Today!';
                                                } elseif ($isTomorrow) {
                                                    echo 'Tomorrow';
                                                } elseif ($daysUntil < 7) {
                                                    echo $daysUntil . ' days';
                                                } else {
                                                    echo ceil($daysUntil / 7) . ' weeks';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if (!empty($interview['interview_link'])): ?>
                                        <a href="<?php echo htmlspecialchars($interview['interview_link']); ?>" 
                                           target="_blank" 
                                           class="btn btn-primary" 
                                           style="width: 100%; display: block; text-align: center;">
                                            <i class="fas fa-video"></i> Join Interview Meeting
                                        </a>
                                    <?php endif; ?>

                                    <?php if (!empty($interview['employer_notes'])): ?>
                                        <div style="margin-top: 1rem; padding: 1rem; background: #fffbeb; border-left: 4px solid #f59e0b; border-radius: 8px;">
                                            <div style="font-weight: 600; color: #92400e; margin-bottom: 0.5rem;">
                                                <i class="fas fa-sticky-note"></i> Additional Instructions:
                                            </div>
                                            <div style="color: #78350f; white-space: pre-wrap;">
                                                <?php echo htmlspecialchars($interview['employer_notes']); ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Past Interviews -->
        <?php if (!empty($pastInterviews)): ?>
            <div>
                <h2 style="font-size: 1.5rem; margin-bottom: 1rem; color: var(--text-secondary);">
                    <i class="fas fa-history"></i> Past Interviews (<?php echo count($pastInterviews); ?>)
                </h2>
                
                <div style="display: grid; gap: 1rem;">
                    <?php foreach (array_slice($pastInterviews, 0, 5) as $interview): 
                        $interviewDate = new DateTime($interview['interview_date']);
                    ?>
                        <div style="background: var(--surface); padding: 1rem; border-radius: 8px; opacity: 0.7; border: 1px solid var(--border-color);">
                            <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: 1rem;">
                                <div style="flex: 1;">
                                    <h4 style="font-size: 1.1rem; margin: 0 0 0.25rem 0;">
                                        <?php echo htmlspecialchars($interview['job_title']); ?>
                                    </h4>
                                    <p style="color: var(--text-secondary); margin: 0; font-size: 0.9rem;">
                                        <?php echo htmlspecialchars($interview['company_name']); ?> • 
                                        <?php echo $interviewDate->format('M j, Y \a\t g:i A'); ?>
                                    </p>
                                </div>
                                <div>
                                    <?php
                                    $statusColors = [
                                        'interviewed' => '#06b6d4',
                                        'offered' => '#10b981',
                                        'hired' => '#059669',
                                        'rejected' => '#ef4444'
                                    ];
                                    $statusColor = $statusColors[$interview['application_status']] ?? '#6b7280';
                                    ?>
                                    <span style="background: <?php echo $statusColor; ?>; color: white; padding: 0.25rem 0.75rem; border-radius: 4px; font-size: 0.85rem; font-weight: 500;">
                                        <?php echo ucfirst($interview['application_status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <?php include '../../includes/footer.php'; ?>
</body>
</html>
