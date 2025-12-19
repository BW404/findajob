<?php
require_once '../../config/database.php';
require_once '../../config/session.php';

// Check if user is logged in and is an employer
if (!isLoggedIn() || !isEmployer()) {
    header('Location: ../auth/login.php');
    exit;
}

$userId = getCurrentUserId();

// Check if employer has Pro subscription
$stmt = $pdo->prepare("SELECT subscription_type, subscription_end FROM users WHERE id = ?");
$stmt->execute([$userId]);
$subscription = $stmt->fetch(PDO::FETCH_ASSOC);

$isPro = ($subscription['subscription_type'] === 'pro' && 
          (!$subscription['subscription_end'] || strtotime($subscription['subscription_end']) > time()));

if (!$isPro) {
    $_SESSION['upgrade_message'] = 'Private Job Offers are a Pro feature. Upgrade to view your private offers!';
    header('Location: ../payment/plans.php');
    exit;
}

$offerId = $_GET['id'] ?? null;

if (!$offerId) {
    header('Location: private-offers.php');
    exit;
}

// Fetch offer details
$stmt = $pdo->prepare("
    SELECT pjo.*, 
           u.first_name, u.last_name, u.email,
           jsp.profile_picture, jsp.years_of_experience, jsp.education_level, jsp.skills,
           jsp.bio
    FROM private_job_offers pjo
    LEFT JOIN users u ON pjo.job_seeker_id = u.id
    LEFT JOIN job_seeker_profiles jsp ON u.id = jsp.user_id
    WHERE pjo.id = ? AND pjo.employer_id = ?
");
$stmt->execute([$offerId, $userId]);
$offer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$offer) {
    header('Location: private-offers.php');
    exit;
}

// Format data
$fullName = ($offer['first_name'] ?? '') . ' ' . ($offer['last_name'] ?? '');
$skills = $offer['skills'] ? explode(',', $offer['skills']) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($offer['job_title']); ?> - Private Offer Details</title>
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
        }
        
        .offer-header h1 {
            margin: 0 0 1rem 0;
            color: var(--text-primary);
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
        .status-withdrawn { background: #e5e7eb; color: #374151; }
        
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
        
        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .info-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
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
        
        .candidate-card {
            text-align: center;
            padding: 1.5rem;
            background: linear-gradient(135deg, var(--primary-light) 0%, #fff 100%);
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }
        
        .candidate-avatar-large {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: bold;
            margin: 0 auto 1rem auto;
            border: 4px solid white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .candidate-name {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .candidate-email {
            color: var(--text-secondary);
            font-size: 0.9rem;
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
        
        .response-box {
            background: #f9fafb;
            border-left: 4px solid var(--primary);
            padding: 1.5rem;
            border-radius: 8px;
        }
        
        .response-box.accepted {
            border-left-color: #10b981;
            background: #f0fdf4;
        }
        
        .response-box.rejected {
            border-left-color: #ef4444;
            background: #fef2f2;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
    </style>
</head>
<body>
    <?php include '../../includes/employer-header.php'; ?>
    
    <div class="offer-details-container">
        <a href="private-offers.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Offers
        </a>
        
        <div class="offer-header">
            <h1><?php echo htmlspecialchars($offer['job_title']); ?></h1>
            <span class="status-badge status-<?php echo $offer['status']; ?>">
                <?php echo ucfirst($offer['status']); ?>
            </span>
        </div>
        
        <div class="content-grid">
            <div class="main-content">
                <!-- Job Description -->
                <div class="section">
                    <h2 class="section-title">Job Description</h2>
                    <p style="line-height: 1.6; color: var(--text-secondary); white-space: pre-wrap;"><?php echo htmlspecialchars($offer['job_description']); ?></p>
                </div>
                
                <!-- Personal Message -->
                <?php if (!empty($offer['offer_message'])): ?>
                <div class="section">
                    <h2 class="section-title">Your Message to Candidate</h2>
                    <div style="background: #f9fafb; padding: 1.5rem; border-radius: 8px;">
                        <p style="line-height: 1.6; color: var(--text-secondary); white-space: pre-wrap; margin: 0;"><?php echo htmlspecialchars($offer['offer_message']); ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Benefits -->
                <?php if (!empty($offer['benefits'])): ?>
                <div class="section">
                    <h2 class="section-title">Benefits</h2>
                    <p style="line-height: 1.6; color: var(--text-secondary); white-space: pre-wrap;"><?php echo htmlspecialchars($offer['benefits']); ?></p>
                </div>
                <?php endif; ?>
                
                <!-- Response -->
                <?php if (!empty($offer['response_message']) && ($offer['status'] === 'accepted' || $offer['status'] === 'rejected')): ?>
                <div class="section">
                    <h2 class="section-title">Candidate Response</h2>
                    <div class="response-box <?php echo $offer['status']; ?>">
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                            <i class="fas fa-<?php echo $offer['status'] === 'accepted' ? 'check-circle' : 'times-circle'; ?>" 
                               style="color: <?php echo $offer['status'] === 'accepted' ? '#10b981' : '#ef4444'; ?>; font-size: 1.5rem;"></i>
                            <strong style="font-size: 1.1rem;">Offer <?php echo ucfirst($offer['status']); ?></strong>
                        </div>
                        <p style="line-height: 1.6; margin: 0; color: var(--text-secondary); white-space: pre-wrap;"><?php echo htmlspecialchars($offer['response_message']); ?></p>
                        <?php if ($offer['responded_at']): ?>
                        <p style="margin-top: 1rem; font-size: 0.85rem; color: var(--text-secondary);">
                            Responded on <?php echo date('F j, Y \a\t g:i A', strtotime($offer['responded_at'])); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Job Details -->
                <div class="section">
                    <h2 class="section-title">Job Details</h2>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
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
                                <div class="info-label">Application Deadline</div>
                                <div class="info-value"><?php echo date('M j, Y', strtotime($offer['deadline'])); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Required Skills -->
                <?php if (!empty($offer['required_skills'])): ?>
                <div class="section">
                    <h2 class="section-title">Required Skills</h2>
                    <div class="skills-list">
                        <?php 
                        $requiredSkills = explode(',', $offer['required_skills']);
                        foreach ($requiredSkills as $skill): 
                        ?>
                        <span class="skill-tag"><?php echo htmlspecialchars(trim($skill)); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="sidebar">
                <!-- Candidate Info -->
                <div class="candidate-card">
                    <div class="candidate-avatar-large">
                        <?php if ($offer['profile_picture']): ?>
                        <img src="../../uploads/profile-pictures/<?php echo htmlspecialchars($offer['profile_picture']); ?>" 
                             style="width:100%; height:100%; object-fit:cover; border-radius:50%;">
                        <?php else: ?>
                        <?php echo strtoupper(substr($offer['first_name'] ?? 'U', 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <div class="candidate-name"><?php echo htmlspecialchars($fullName); ?></div>
                    <div class="candidate-email"><?php echo htmlspecialchars($offer['email']); ?></div>
                </div>
                
                <!-- Candidate Details -->
                <div style="margin-bottom: 1.5rem;">
                    <h3 style="font-size: 1rem; margin-bottom: 1rem; color: var(--text-primary);">Candidate Information</h3>
                    
                    <?php if ($offer['years_of_experience']): ?>
                    <div class="info-item">
                        <div class="info-icon" style="width: 35px; height: 35px; font-size: 0.9rem;">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Experience</div>
                            <div class="info-value" style="font-size: 0.95rem;"><?php echo $offer['years_of_experience']; ?> years</div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($offer['education_level']): ?>
                    <div class="info-item">
                        <div class="info-icon" style="width: 35px; height: 35px; font-size: 0.9rem;">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Education</div>
                            <div class="info-value" style="font-size: 0.95rem;"><?php echo strtoupper($offer['education_level']); ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Candidate Skills -->
                <?php if (!empty($skills)): ?>
                <div style="margin-bottom: 1.5rem;">
                    <h3 style="font-size: 1rem; margin-bottom: 1rem; color: var(--text-primary);">Skills</h3>
                    <div class="skills-list">
                        <?php foreach ($skills as $skill): ?>
                        <span class="skill-tag" style="font-size: 0.8rem;"><?php echo htmlspecialchars(trim($skill)); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Timeline -->
                <div>
                    <h3 style="font-size: 1rem; margin-bottom: 1rem; color: var(--text-primary);">Timeline</h3>
                    <div style="font-size: 0.85rem; color: var(--text-secondary);">
                        <div style="margin-bottom: 0.75rem;">
                            <i class="fas fa-paper-plane" style="color: var(--primary); margin-right: 0.5rem;"></i>
                            Sent: <?php echo date('M j, Y', strtotime($offer['created_at'])); ?>
                        </div>
                        <?php if ($offer['viewed_at']): ?>
                        <div style="margin-bottom: 0.75rem;">
                            <i class="fas fa-eye" style="color: #3b82f6; margin-right: 0.5rem;"></i>
                            Viewed: <?php echo date('M j, Y', strtotime($offer['viewed_at'])); ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($offer['responded_at']): ?>
                        <div style="margin-bottom: 0.75rem;">
                            <i class="fas fa-reply" style="color: #10b981; margin-right: 0.5rem;"></i>
                            Responded: <?php echo date('M j, Y', strtotime($offer['responded_at'])); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Actions -->
                <?php if ($offer['status'] === 'pending' || $offer['status'] === 'viewed'): ?>
                <div class="action-buttons">
                    <button onclick="withdrawOffer()" class="btn btn-danger btn-sm" style="width: 100%;">
                        <i class="fas fa-times"></i> Withdraw Offer
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
    
    <script>
    async function withdrawOffer() {
        if (!confirm('Are you sure you want to withdraw this offer?')) return;
        
        try {
            const response = await fetch('../../api/private-job-offers.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'withdraw_offer',
                    offer_id: <?php echo $offerId; ?>
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert('Offer withdrawn successfully');
                window.location.href = 'private-offers.php';
            } else {
                alert(data.error || 'Failed to withdraw offer');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred');
        }
    }
    </script>
</body>
</html>
