<?php
// Internship Badge Display Component
// Include this on job seeker profile pages

function displayInternshipBadges($userId, $pdo, $showAll = true) {
    // Get all badges for this job seeker
    $stmt = $pdo->prepare("
        SELECT 
            ib.*,
            i.status as internship_status
        FROM internship_badges ib
        JOIN internships i ON ib.internship_id = i.id
        WHERE ib.job_seeker_id = ? AND ib.is_visible = 1
        ORDER BY ib.awarded_at DESC
    ");
    $stmt->execute([$userId]);
    $badges = $stmt->fetchAll();
    
    if (count($badges) === 0) {
        return;
    }
    ?>
    
    <div class="internship-badges-section" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); padding: 2rem; border-radius: 16px; margin: 2rem 0; border: 2px solid #fbbf24;">
        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
            <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #f59e0b, #d97706); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-award" style="color: white; font-size: 1.5rem;"></i>
            </div>
            <div>
                <h3 style="margin: 0; color: #78350f; font-size: 1.5rem; font-weight: 800;">
                    <i class="fas fa-graduation-cap"></i> Internship Certificates
                </h3>
                <p style="margin: 0; color: #92400e; font-size: 0.9rem;">
                    Completed <?php echo count($badges); ?> verified internship<?php echo count($badges) > 1 ? 's' : ''; ?>
                </p>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
            <?php foreach ($badges as $badge): ?>
            <div class="badge-card" style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-left: 4px solid #f59e0b; transition: transform 0.3s;">
                <div style="display: flex; align-items: start; gap: 1rem; margin-bottom: 1rem;">
                    <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #fbbf24, #f59e0b); border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas fa-certificate" style="color: white; font-size: 1.2rem;"></i>
                    </div>
                    <div style="flex: 1;">
                        <div style="margin-bottom: 0.5rem; padding: 0.25rem 0.75rem; background: linear-gradient(135deg, #fbbf24, #f59e0b); color: white; border-radius: 20px; display: inline-block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                            <i class="fas fa-certificate"></i> Internship Certificate
                        </div>
                        <h4 style="margin: 0 0 0.25rem 0; color: #1a202c; font-size: 1.1rem; font-weight: 700;">
                            <?php echo htmlspecialchars($badge['job_title']); ?>
                        </h4>
                        <p style="margin: 0; color: #f59e0b; font-weight: 600; font-size: 0.95rem;">
                            <i class="fas fa-building"></i> <?php echo htmlspecialchars($badge['company_name']); ?>
                        </p>
                    </div>
                </div>
                
                <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; color: #64748b; font-size: 0.85rem;">
                        <i class="fas fa-calendar" style="color: #f59e0b; width: 16px;"></i>
                        <span><?php echo date('M Y', strtotime($badge['start_date'])); ?> - <?php echo date('M Y', strtotime($badge['end_date'])); ?></span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem; color: #64748b; font-size: 0.85rem;">
                        <i class="fas fa-clock" style="color: #f59e0b; width: 16px;"></i>
                        <span><?php echo $badge['duration_months']; ?> month<?php echo $badge['duration_months'] > 1 ? 's' : ''; ?> duration</span>
                    </div>
                    <?php if ($badge['performance_rating']): ?>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-star" style="color: #fbbf24; width: 16px;"></i>
                        <div style="display: flex; gap: 2px;">
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star" style="color: <?php echo $i <= $badge['performance_rating'] ? '#fbbf24' : '#e5e7eb'; ?>; font-size: 0.9rem;"></i>
                            <?php endfor; ?>
                        </div>
                        <span style="color: #64748b; font-size: 0.85rem; margin-left: 0.25rem;">(<?php echo $badge['performance_rating']; ?>/5)</span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($badge['employer_feedback']): ?>
                <div style="padding: 1rem; background: #fef3c7; border-radius: 8px; border-left: 3px solid #fbbf24;">
                    <p style="margin: 0; color: #78350f; font-size: 0.85rem; line-height: 1.6; font-style: italic;">
                        "<?php echo htmlspecialchars(substr($badge['employer_feedback'], 0, 150)); ?><?php echo strlen($badge['employer_feedback']) > 150 ? '...' : ''; ?>"
                    </p>
                </div>
                <?php endif; ?>
                
                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between;">
                    <span style="color: #94a3b8; font-size: 0.8rem;">
                        <i class="fas fa-shield-alt"></i> Verified
                    </span>
                    <span style="color: #94a3b8; font-size: 0.8rem;">
                        Awarded <?php echo date('M d, Y', strtotime($badge['awarded_at'])); ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <style>
    .badge-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.15) !important;
    }
    </style>
    
    <?php
}

function getInternshipBadgeCount($userId, $pdo) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM internship_badges WHERE job_seeker_id = ? AND is_visible = 1");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    return $result['count'] ?? 0;
}

function hasInternshipBadge($userId, $pdo) {
    return getInternshipBadgeCount($userId, $pdo) > 0;
}
?>
