<?php
require_once '../../config/database.php';
require_once '../../config/session.php';

// Check if user is logged in and is a job seeker
if (!isLoggedIn() || !isJobSeeker()) {
    header('Location: ../auth/login.php');
    exit;
}

$userId = getCurrentUserId();
$offerId = $_GET['id'] ?? null;

if (!$offerId) {
    header('Location: private-offers.php');
    exit;
}

// Fetch offer details and mark as viewed
$stmt = $pdo->prepare("
    SELECT pjo.*, 
           u.first_name as employer_first_name, u.last_name as employer_last_name, u.email as employer_email,
           ep.company_name, ep.company_logo, ep.industry, ep.website, ep.company_size
    FROM private_job_offers pjo
    LEFT JOIN users u ON pjo.employer_id = u.id
    LEFT JOIN employer_profiles ep ON u.id = ep.user_id
    WHERE pjo.id = ? AND pjo.job_seeker_id = ?
");
$stmt->execute([$offerId, $userId]);
$offer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$offer) {
    header('Location: private-offers.php');
    exit;
}

// Mark as viewed if status is pending
if ($offer['status'] === 'pending') {
    $stmt = $pdo->prepare("UPDATE private_job_offers SET status = 'viewed', viewed_at = NOW() WHERE id = ?");
    $stmt->execute([$offerId]);
    
    // Create notification for employer
    $stmt = $pdo->prepare("
        INSERT INTO private_offer_notifications (offer_id, user_id, notification_type)
        VALUES (?, ?, 'offer_viewed')
    ");
    $stmt->execute([$offerId, $offer['employer_id']]);
    
    $offer['status'] = 'viewed';
    $offer['viewed_at'] = date('Y-m-d H:i:s');
}

// Format data
$companyName = $offer['company_name'] ?? ($offer['employer_first_name'] . ' ' . $offer['employer_last_name']);
$skills = $offer['required_skills'] ? explode(',', $offer['required_skills']) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($offer['job_title']); ?> - Private Offer</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .offer-details-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary);
            text-decoration: none;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .offer-header {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1.5rem;
            flex-wrap: wrap;
        }
        
        .offer-header-content {
            flex: 1;
        }
        
        .offer-header h1 {
            margin: 0 0 0.5rem 0;
            color: var(--text-primary);
        }
        
        .company-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .company-logo {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.5rem;
            color: var(--primary);
        }
        
        .company-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .company-details h3 {
            margin: 0 0 0.25rem 0;
            font-size: 1.1rem;
            color: var(--text-primary);
        }
        
        .company-details p {
            margin: 0;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-viewed { background: #dbeafe; color: #1e40af; }
        .status-accepted { background: #d1fae5; color: #065f46; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        .status-expired { background: #e5e7eb; color: #374151; }
        
        .new-badge {
            display: inline-block;
            background: var(--primary);
            color: white;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            margin-left: 0.5rem;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .main-content {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .sidebar {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            height: fit-content;
        }
        
        .section {
            margin-bottom: 2rem;
        }
        
        .section:last-child {
            margin-bottom: 0;
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem;
            background: #f9fafb;
            border-radius: 8px;
        }
        
        .info-icon {
            width: 40px;
            height: 40px;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .info-content {
            flex: 1;
        }
        
        .info-label {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-bottom: 0.25rem;
        }
        
        .info-value {
            font-size: 1rem;
            color: var(--text-primary);
            font-weight: 500;
        }
        
        .skills-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .skill-tag {
            background: var(--primary-light);
            color: var(--primary);
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .personal-message {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-left: 4px solid #f59e0b;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .personal-message h3 {
            margin: 0 0 1rem 0;
            color: #92400e;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .personal-message p {
            margin: 0;
            color: #78350f;
            line-height: 1.6;
            white-space: pre-wrap;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .action-buttons .btn {
            flex: 1;
        }
        
        .deadline-warning {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .deadline-warning p {
            margin: 0;
            color: #991b1b;
            font-weight: 600;
        }
        
        .response-form {
            background: #f9fafb;
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 1.5rem;
        }
        
        .response-form h3 {
            margin: 0 0 1rem 0;
            color: var(--text-primary);
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
        }
        
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-family: inherit;
            resize: vertical;
            min-height: 100px;
        }
        
        .timeline-item {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .timeline-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .timeline-icon {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 0.9rem;
        }
        
        .timeline-icon.sent {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .timeline-icon.viewed {
            background: #e0e7ff;
            color: #4338ca;
        }
        
        .timeline-icon.responded {
            background: #d1fae5;
            color: #065f46;
        }
        
        .timeline-content {
            flex: 1;
        }
        
        .timeline-content strong {
            display: block;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }
        
        .timeline-content span {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="offer-details-container">
        <a href="private-offers.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Offers
        </a>
        
        <div class="offer-header">
            <div class="offer-header-content">
                <h1>
                    <?php echo htmlspecialchars($offer['job_title']); ?>
                    <?php if ($offer['status'] === 'pending'): ?>
                    <span class="new-badge">NEW</span>
                    <?php endif; ?>
                </h1>
                
                <div class="company-info">
                    <div class="company-logo">
                        <?php if ($offer['company_logo']): ?>
                        <img src="../../uploads/company-logos/<?php echo htmlspecialchars($offer['company_logo']); ?>" alt="Logo">
                        <?php else: ?>
                        <?php echo strtoupper(substr($companyName, 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <div class="company-details">
                        <h3><?php echo htmlspecialchars($companyName); ?></h3>
                        <?php if ($offer['industry']): ?>
                        <p><i class="fas fa-industry"></i> <?php echo htmlspecialchars($offer['industry']); ?></p>
                        <?php endif; ?>
                        <?php if ($offer['website']): ?>
                        <p><i class="fas fa-globe"></i> <a href="<?php echo htmlspecialchars($offer['website']); ?>" target="_blank"><?php echo htmlspecialchars($offer['website']); ?></a></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <span class="status-badge status-<?php echo $offer['status']; ?>">
                <?php echo ucfirst($offer['status']); ?>
            </span>
        </div>
        
        <?php 
        $deadline = new DateTime($offer['deadline']);
        $now = new DateTime();
        $daysLeft = $deadline->diff($now)->days;
        $isExpiringSoon = $daysLeft <= 3 && $offer['status'] !== 'accepted' && $offer['status'] !== 'rejected';
        ?>
        
        <?php if ($isExpiringSoon): ?>
        <div class="deadline-warning">
            <p><i class="fas fa-exclamation-triangle"></i> This offer expires in <?php echo $daysLeft; ?> day<?php echo $daysLeft > 1 ? 's' : ''; ?>! Respond soon to secure this opportunity.</p>
        </div>
        <?php endif; ?>
        
        <div class="content-grid">
            <div class="main-content">
                <!-- Personal Message -->
                <?php if (!empty($offer['offer_message'])): ?>
                <div class="personal-message">
                    <h3><i class="fas fa-envelope"></i> Personal Message from Employer</h3>
                    <p><?php echo htmlspecialchars($offer['offer_message']); ?></p>
                </div>
                <?php endif; ?>
                
                <!-- Job Description -->
                <div class="section">
                    <h2 class="section-title">Job Description</h2>
                    <p style="line-height: 1.6; color: var(--text-secondary); white-space: pre-wrap;"><?php echo htmlspecialchars($offer['job_description']); ?></p>
                </div>
                
                <!-- Benefits -->
                <?php if (!empty($offer['benefits'])): ?>
                <div class="section">
                    <h2 class="section-title">Benefits & Perks</h2>
                    <p style="line-height: 1.6; color: var(--text-secondary); white-space: pre-wrap;"><?php echo htmlspecialchars($offer['benefits']); ?></p>
                </div>
                <?php endif; ?>
                
                <!-- Job Details -->
                <div class="section">
                    <h2 class="section-title">Job Details</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-briefcase"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Job Type</div>
                                <div class="info-value"><?php echo ucwords(str_replace('-', ' ', $offer['job_type'])); ?></div>
                            </div>
                        </div>
                        
                        <?php if ($offer['category']): ?>
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-tag"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Category</div>
                                <div class="info-value"><?php echo htmlspecialchars($offer['category']); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Location</div>
                                <div class="info-value">
                                    <?php 
                                    echo ucfirst($offer['location_type']);
                                    if ($offer['city']) echo ' - ' . htmlspecialchars($offer['city']);
                                    if ($offer['state']) echo ', ' . htmlspecialchars($offer['state']);
                                    ?>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($offer['salary_min'] && $offer['salary_max']): ?>
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Salary Range</div>
                                <div class="info-value">
                                    ₦<?php echo number_format($offer['salary_min']); ?> - ₦<?php echo number_format($offer['salary_max']); ?>
                                    <?php if ($offer['salary_period']): ?>
                                    <span style="font-size: 0.85rem; color: var(--text-secondary);">/ <?php echo $offer['salary_period']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($offer['experience_level']): ?>
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Experience Level</div>
                                <div class="info-value"><?php echo ucwords(str_replace('-', ' ', $offer['experience_level'])); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($offer['education_level']): ?>
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Education Required</div>
                                <div class="info-value"><?php echo strtoupper($offer['education_level']); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($offer['start_date']): ?>
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Expected Start Date</div>
                                <div class="info-value"><?php echo date('M j, Y', strtotime($offer['start_date'])); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Response Deadline</div>
                                <div class="info-value"><?php echo date('M j, Y', strtotime($offer['deadline'])); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Required Skills -->
                <?php if (!empty($skills)): ?>
                <div class="section">
                    <h2 class="section-title">Required Skills</h2>
                    <div class="skills-list">
                        <?php foreach ($skills as $skill): ?>
                        <span class="skill-tag"><?php echo htmlspecialchars(trim($skill)); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Response Form -->
                <?php if ($offer['status'] === 'pending' || $offer['status'] === 'viewed'): ?>
                <div class="response-form">
                    <h3>Respond to This Offer</h3>
                    <div class="form-group">
                        <label for="responseMessage">Your Message (Optional)</label>
                        <textarea id="responseMessage" placeholder="Add a message with your response..."></textarea>
                    </div>
                    <div class="action-buttons">
                        <button onclick="respondToOffer('accepted')" class="btn btn-success">
                            <i class="fas fa-check"></i> Accept Offer
                        </button>
                        <button onclick="respondToOffer('rejected')" class="btn btn-danger">
                            <i class="fas fa-times"></i> Decline Offer
                        </button>
                    </div>
                </div>
                <?php elseif ($offer['status'] === 'accepted'): ?>
                <div style="background: #d1fae5; border-left: 4px solid #10b981; padding: 1.5rem; border-radius: 8px;">
                    <h3 style="margin: 0 0 0.5rem 0; color: #065f46;">
                        <i class="fas fa-check-circle"></i> You Accepted This Offer
                    </h3>
                    <p style="margin: 0; color: #047857;">The employer will contact you soon with next steps.</p>
                    <?php if ($offer['response_message']): ?>
                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #a7f3d0;">
                        <strong>Your Message:</strong>
                        <p style="margin: 0.5rem 0 0 0; white-space: pre-wrap;"><?php echo htmlspecialchars($offer['response_message']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php elseif ($offer['status'] === 'rejected'): ?>
                <div style="background: #fee2e2; border-left: 4px solid #ef4444; padding: 1.5rem; border-radius: 8px;">
                    <h3 style="margin: 0 0 0.5rem 0; color: #991b1b;">
                        <i class="fas fa-times-circle"></i> You Declined This Offer
                    </h3>
                    <p style="margin: 0; color: #b91c1c;">This offer is no longer active.</p>
                    <?php if ($offer['response_message']): ?>
                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #fecaca;">
                        <strong>Your Message:</strong>
                        <p style="margin: 0.5rem 0 0 0; white-space: pre-wrap;"><?php echo htmlspecialchars($offer['response_message']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="sidebar">
                <!-- Timeline -->
                <div style="margin-bottom: 1.5rem;">
                    <h3 style="font-size: 1rem; margin-bottom: 1rem; color: var(--text-primary);">Offer Timeline</h3>
                    <div>
                        <div class="timeline-item">
                            <div class="timeline-icon sent">
                                <i class="fas fa-paper-plane"></i>
                            </div>
                            <div class="timeline-content">
                                <strong>Offer Sent</strong>
                                <span><?php echo date('M j, Y g:i A', strtotime($offer['created_at'])); ?></span>
                            </div>
                        </div>
                        
                        <?php if ($offer['viewed_at']): ?>
                        <div class="timeline-item">
                            <div class="timeline-icon viewed">
                                <i class="fas fa-eye"></i>
                            </div>
                            <div class="timeline-content">
                                <strong>Viewed by You</strong>
                                <span><?php echo date('M j, Y g:i A', strtotime($offer['viewed_at'])); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($offer['responded_at']): ?>
                        <div class="timeline-item">
                            <div class="timeline-icon responded">
                                <i class="fas fa-reply"></i>
                            </div>
                            <div class="timeline-content">
                                <strong>You <?php echo ucfirst($offer['status']); ?></strong>
                                <span><?php echo date('M j, Y g:i A', strtotime($offer['responded_at'])); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="timeline-item">
                            <div class="timeline-icon" style="background: #fef3c7; color: #92400e;">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="timeline-content">
                                <strong>Deadline</strong>
                                <span><?php echo date('M j, Y', strtotime($offer['deadline'])); ?> (<?php echo $daysLeft; ?> days<?php echo $daysLeft == 0 ? ' - Today!' : ' left'; ?>)</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <?php if ($offer['status'] === 'pending' || $offer['status'] === 'viewed'): ?>
                <div style="background: #f9fafb; padding: 1rem; border-radius: 8px;">
                    <h4 style="margin: 0 0 0.75rem 0; font-size: 0.9rem; color: var(--text-secondary);">Quick Actions</h4>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <button onclick="window.scrollTo({top: document.querySelector('.response-form').offsetTop - 100, behavior: 'smooth'})" class="btn btn-primary btn-sm" style="width: 100%;">
                            <i class="fas fa-reply"></i> Respond Now
                        </button>
                        <?php if ($offer['website']): ?>
                        <a href="<?php echo htmlspecialchars($offer['website']); ?>" target="_blank" class="btn btn-secondary btn-sm" style="width: 100%; text-align: center;">
                            <i class="fas fa-external-link-alt"></i> Visit Company Website
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
    
    <script>
    async function respondToOffer(action) {
        const message = document.getElementById('responseMessage').value;
        
        const confirmText = action === 'accepted' 
            ? 'Are you sure you want to accept this job offer?' 
            : 'Are you sure you want to decline this job offer?';
        
        if (!confirm(confirmText)) return;
        
        try {
            const response = await fetch('../../api/private-job-offers.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'respond_to_offer',
                    offer_id: <?php echo $offerId; ?>,
                    response: action,
                    message: message
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert(action === 'accepted' ? 'Offer accepted successfully!' : 'Offer declined');
                window.location.reload();
            } else {
                alert(data.error || 'Failed to respond to offer');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred');
        }
    }
    </script>
</body>
</html>
