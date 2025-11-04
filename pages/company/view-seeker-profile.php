<?php
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/constants.php';

requireEmployer();

$employer_id = getCurrentUserId();
$seeker_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$seeker_id) {
    header('Location: search-cvs.php');
    exit();
}

// Get job seeker profile data with all available information
$stmt = $pdo->prepare("
    SELECT 
        u.id, u.first_name, u.last_name, u.email, u.phone,
        u.email_verified, u.is_active,
        u.created_at as user_created_at, u.updated_at as user_updated_at,
        jsp.id as profile_id,
        jsp.date_of_birth, jsp.gender, 
        jsp.state_of_origin, jsp.lga_of_origin, 
        jsp.current_state, jsp.current_city,
        jsp.education_level, jsp.years_of_experience, jsp.job_status,
        jsp.salary_expectation_min, jsp.salary_expectation_max, 
        jsp.skills, jsp.bio, jsp.profile_picture,
        jsp.nin, jsp.bvn, jsp.is_verified, jsp.verification_status,
        jsp.nin_verified, jsp.nin_verified_at,
        jsp.subscription_type, jsp.subscription_expires,
        jsp.created_at as profile_created_at, 
        jsp.updated_at as profile_updated_at
    FROM users u 
    LEFT JOIN job_seeker_profiles jsp ON u.id = jsp.user_id 
    WHERE u.id = ? AND u.user_type = 'job_seeker'
");
$stmt->execute([$seeker_id]);
$seeker = $stmt->fetch();

if (!$seeker) {
    header('Location: search-cvs.php');
    exit();
}

// Get seeker's CVs
$cvStmt = $pdo->prepare("
    SELECT id, title, file_type, created_at, updated_at
    FROM cvs
    WHERE user_id = ?
    ORDER BY is_primary DESC, created_at DESC
");
$cvStmt->execute([$seeker_id]);
$cvs = $cvStmt->fetchAll();

// Get application history (if they applied to any of employer's jobs)
$appStmt = $pdo->prepare("
    SELECT 
        ja.id, ja.application_status, ja.applied_at,
        j.title as job_title, j.id as job_id
    FROM job_applications ja
    JOIN jobs j ON ja.job_id = j.id
    WHERE ja.job_seeker_id = ? AND j.employer_id = ?
    ORDER BY ja.applied_at DESC
");
$appStmt->execute([$seeker_id, $employer_id]);
$applications = $appStmt->fetchAll();

$page_title = htmlspecialchars($seeker['first_name'] . ' ' . $seeker['last_name']) . ' - Job Seeker Profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .profile-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .profile-header {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: white;
            padding: 3rem 2rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(220, 38, 38, 0.3);
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: white;
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: bold;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .profile-info h1 {
            margin: 0 0 0.5rem 0;
            font-size: 2rem;
        }

        .profile-meta {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }

        .profile-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
            opacity: 0.95;
        }

        .profile-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        .profile-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
        }

        .profile-card h2 {
            margin: 0 0 1.5rem 0;
            font-size: 1.5rem;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .profile-card h2 i {
            color: var(--primary);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        .info-item {
            padding: 1rem;
            background: var(--background);
            border-radius: 8px;
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
            gap: 0.75rem;
        }

        .skill-tag {
            background: var(--primary-light);
            color: var(--primary);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .cv-item {
            padding: 1.25rem;
            background: var(--background);
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .application-item {
            padding: 1.25rem;
            background: var(--background);
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid var(--primary);
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-applied { background: #e0e7ff; color: #4f46e5; }
        .status-viewed { background: #dbeafe; color: #2563eb; }
        .status-shortlisted { background: #fef3c7; color: #d97706; }
        .status-interviewed { background: #fce7f3; color: #be185d; }
        .status-offered { background: #dcfce7; color: #059669; }
        .status-hired { background: #dcfce7; color: #047857; }
        .status-rejected { background: #fee2e2; color: #dc2626; }

        @media (max-width: 768px) {
            .profile-content {
                grid-template-columns: 1fr;
            }

            .profile-header {
                flex-direction: column;
                text-align: center;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="container">
            <nav class="site-nav">
                <a href="/findajob" class="site-logo">
                    <img src="/findajob/assets/images/logo_full.png" alt="FindAJob Nigeria" class="site-logo-img">
                </a>
                <div class="nav-links">
                    <a href="dashboard.php" class="btn btn-outline">← Back to Dashboard</a>
                </div>
            </nav>
        </div>
    </header>

    <div class="profile-container">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar">
                <?php if (!empty($seeker['profile_picture'])): ?>
                    <?php
                    // Normalize profile picture path
                    $profile_pic_url = $seeker['profile_picture'];
                    if (strpos($profile_pic_url, '/') === 0 || preg_match('#^https?://#i', $profile_pic_url)) {
                        // Already absolute path or full URL
                        $profile_pic_url = $profile_pic_url;
                    } else {
                        // Relative path - prepend base path
                        $profile_pic_url = '/findajob/' . ltrim($profile_pic_url, '/');
                    }
                    ?>
                    <img src="<?php echo htmlspecialchars($profile_pic_url); ?>" alt="<?php echo htmlspecialchars($seeker['first_name']); ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                <?php else: ?>
                    <?php echo strtoupper(substr($seeker['first_name'], 0, 1) . substr($seeker['last_name'], 0, 1)); ?>
                <?php endif; ?>
            </div>
            <div class="profile-info">
                <h1>
                    <?php echo htmlspecialchars($seeker['first_name'] . ' ' . $seeker['last_name']); ?>
                    <?php if (!empty($seeker['nin_verified'])): ?>
                        <span class="verified-badge" style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; background: #1877f2; border-radius: 50%; margin-left: 8px; position: relative; top: -2px;" title="NIN Verified">
                            <i class="fas fa-check" style="color: white; font-size: 12px;"></i>
                        </span>
                    <?php endif; ?>
                </h1>
                <div class="profile-meta">
                    <?php if ($seeker['years_of_experience']): ?>
                        <div class="profile-meta-item">
                            <i class="fas fa-briefcase"></i>
                            <?php echo $seeker['years_of_experience']; ?> years experience
                        </div>
                    <?php endif; ?>
                    <?php if ($seeker['education_level']): ?>
                        <div class="profile-meta-item">
                            <i class="fas fa-graduation-cap"></i>
                            <?php echo strtoupper($seeker['education_level']); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($seeker['current_city'] || $seeker['current_state']): ?>
                        <div class="profile-meta-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <?php echo htmlspecialchars(trim($seeker['current_city'] . ', ' . $seeker['current_state'], ', ')); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="profile-content">
            <!-- Main Content -->
            <div class="main-content">
                <!-- About -->
                <?php if ($seeker['bio']): ?>
                    <div class="profile-card">
                        <h2><i class="fas fa-user"></i> About</h2>
                        <p style="line-height: 1.8; color: var(--text-primary);">
                            <?php echo nl2br(htmlspecialchars($seeker['bio'])); ?>
                        </p>
                    </div>
                <?php endif; ?>

                <!-- Skills -->
                <?php if ($seeker['skills']): ?>
                    <div class="profile-card">
                        <h2><i class="fas fa-tools"></i> Skills</h2>
                        <div class="skills-list">
                            <?php 
                            $skills = explode(',', $seeker['skills']);
                            foreach ($skills as $skill): 
                                $skill = trim($skill);
                                if ($skill):
                            ?>
                                <span class="skill-tag"><?php echo htmlspecialchars($skill); ?></span>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Personal Information -->
                <div class="profile-card">
                    <h2><i class="fas fa-info-circle"></i> Personal Information</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Full Name</div>
                            <div class="info-value"><?php echo htmlspecialchars($seeker['first_name'] . ' ' . $seeker['last_name']); ?></div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Email</div>
                            <div class="info-value" style="word-break: break-word;"><?php echo htmlspecialchars($seeker['email']); ?></div>
                        </div>

                        <?php if ($seeker['phone']): ?>
                            <div class="info-item">
                                <div class="info-label">Phone</div>
                                <div class="info-value"><?php echo htmlspecialchars($seeker['phone']); ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if ($seeker['gender']): ?>
                            <div class="info-item">
                                <div class="info-label">Gender</div>
                                <div class="info-value"><?php echo ucfirst($seeker['gender']); ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($seeker['date_of_birth']): ?>
                            <div class="info-item">
                                <div class="info-label">Date of Birth</div>
                                <div class="info-value">
                                    <?php echo date('F j, Y', strtotime($seeker['date_of_birth'])); ?>
                                </div>
                            </div>

                            <div class="info-item">
                                <div class="info-label">Age</div>
                                <div class="info-value">
                                    <?php 
                                    $dob = new DateTime($seeker['date_of_birth']);
                                    $now = new DateTime();
                                    $age = $now->diff($dob)->y;
                                    echo $age . ' years';
                                    ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($seeker['state_of_origin']): ?>
                            <div class="info-item">
                                <div class="info-label">State of Origin</div>
                                <div class="info-value"><?php echo htmlspecialchars($seeker['state_of_origin']); ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if ($seeker['lga_of_origin']): ?>
                            <div class="info-item">
                                <div class="info-label">LGA of Origin</div>
                                <div class="info-value"><?php echo htmlspecialchars($seeker['lga_of_origin']); ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if ($seeker['current_state']): ?>
                            <div class="info-item">
                                <div class="info-label">Current State</div>
                                <div class="info-value"><?php echo htmlspecialchars($seeker['current_state']); ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if ($seeker['current_city']): ?>
                            <div class="info-item">
                                <div class="info-label">Current City</div>
                                <div class="info-value"><?php echo htmlspecialchars($seeker['current_city']); ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if ($seeker['education_level']): ?>
                            <div class="info-item">
                                <div class="info-label">Education Level</div>
                                <div class="info-value"><?php echo strtoupper($seeker['education_level']); ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if ($seeker['years_of_experience']): ?>
                            <div class="info-item">
                                <div class="info-label">Years of Experience</div>
                                <div class="info-value"><?php echo $seeker['years_of_experience']; ?> years</div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($seeker['job_status']): ?>
                            <div class="info-item">
                                <div class="info-label">Job Status</div>
                                <div class="info-value">
                                    <?php 
                                    $status_map = [
                                        'looking' => 'Actively Looking',
                                        'employed_but_looking' => 'Employed but Looking',
                                        'not_looking' => 'Not Looking'
                                    ];
                                    echo $status_map[$seeker['job_status']] ?? ucfirst($seeker['job_status']);
                                    ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($seeker['salary_expectation_min'] || $seeker['salary_expectation_max']): ?>
                            <div class="info-item">
                                <div class="info-label">Salary Expectation (Monthly)</div>
                                <div class="info-value">
                                    ₦<?php echo number_format($seeker['salary_expectation_min'] ?? 0); ?> - 
                                    ₦<?php echo number_format($seeker['salary_expectation_max'] ?? 0); ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="info-item">
                            <div class="info-label">Account Status</div>
                            <div class="info-value">
                                <?php if ($seeker['is_active']): ?>
                                    <span style="color: var(--accent);"><i class="fas fa-check-circle"></i> Active</span>
                                <?php else: ?>
                                    <span style="color: var(--error);"><i class="fas fa-times-circle"></i> Inactive</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Email Verified</div>
                            <div class="info-value">
                                <?php if ($seeker['email_verified']): ?>
                                    <span style="color: var(--accent);"><i class="fas fa-check-circle"></i> Verified</span>
                                <?php else: ?>
                                    <span style="color: var(--warning);"><i class="fas fa-exclamation-circle"></i> Unverified</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($seeker['is_verified']): ?>
                            <div class="info-item">
                                <div class="info-label">Profile Verification</div>
                                <div class="info-value">
                                    <?php 
                                    $verif_colors = [
                                        'verified' => 'var(--accent)',
                                        'pending' => 'var(--warning)',
                                        'rejected' => 'var(--error)'
                                    ];
                                    $verif_icons = [
                                        'verified' => 'check-circle',
                                        'pending' => 'clock',
                                        'rejected' => 'times-circle'
                                    ];
                                    $status = $seeker['verification_status'] ?? 'pending';
                                    $color = isset($verif_colors[$status]) ? $verif_colors[$status] : 'var(--text-secondary)';
                                    $icon = isset($verif_icons[$status]) ? $verif_icons[$status] : 'question-circle';
                                    ?>
                                    <span style="color: <?php echo $color; ?>;">
                                        <i class="fas fa-<?php echo $icon; ?>"></i> 
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($seeker['subscription_type']): ?>
                            <div class="info-item">
                                <div class="info-label">Subscription Plan</div>
                                <div class="info-value">
                                    <?php echo ucfirst($seeker['subscription_type']); ?>
                                    <?php if ($seeker['subscription_type'] === 'pro' && $seeker['subscription_expires']): ?>
                                        <div style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.25rem;">
                                            Expires: <?php echo date('M j, Y', strtotime($seeker['subscription_expires'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="info-item">
                            <div class="info-label">Member Since</div>
                            <div class="info-value"><?php echo date('F j, Y', strtotime($seeker['user_created_at'])); ?></div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Profile Last Updated</div>
                            <div class="info-value">
                                <?php 
                                if ($seeker['profile_updated_at']) {
                                    echo date('M j, Y', strtotime($seeker['profile_updated_at']));
                                } else {
                                    echo 'Never';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Application History -->
                <?php if (!empty($applications)): ?>
                    <div class="profile-card">
                        <h2><i class="fas fa-history"></i> Application History</h2>
                        <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">
                            This candidate has applied to <?php echo count($applications); ?> of your job postings
                        </p>
                        <?php foreach ($applications as $app): ?>
                            <div class="application-item">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                                    <div>
                                        <h4 style="margin: 0 0 0.5rem 0; font-size: 1.1rem;">
                                            <a href="../jobs/details.php?id=<?php echo $app['job_id']; ?>" style="color: var(--text-primary); text-decoration: none;">
                                                <?php echo htmlspecialchars($app['job_title']); ?>
                                            </a>
                                        </h4>
                                        <div style="font-size: 0.85rem; color: var(--text-secondary);">
                                            Applied <?php echo date('M j, Y', strtotime($app['applied_at'])); ?>
                                        </div>
                                    </div>
                                    <span class="status-badge status-<?php echo $app['application_status']; ?>">
                                        <?php echo ucfirst($app['application_status']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Contact Information -->
                <div class="profile-card">
                    <h2><i class="fas fa-envelope"></i> Contact Actions</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <a href="mailto:<?php echo htmlspecialchars($seeker['email']); ?>" class="btn btn-primary" style="text-decoration: none; text-align: center;">
                            <i class="fas fa-envelope"></i> Send Email
                        </a>
                        <?php if ($seeker['phone']): ?>
                            <a href="tel:<?php echo htmlspecialchars($seeker['phone']); ?>" class="btn btn-outline" style="text-decoration: none; text-align: center;">
                                <i class="fas fa-phone"></i> Call
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- CVs -->
                <?php if (!empty($cvs)): ?>
                    <div class="profile-card">
                        <h2><i class="fas fa-file-alt"></i> CVs</h2>
                        <?php foreach ($cvs as $cv): ?>
                            <div class="cv-item">
                                <div>
                                    <div style="font-weight: 500; margin-bottom: 0.25rem;">
                                        <?php echo htmlspecialchars($cv['title']); ?>
                                    </div>
                                    <div style="font-size: 0.85rem; color: var(--text-secondary);">
                                        Updated <?php echo date('M j, Y', strtotime($cv['updated_at'])); ?>
                                    </div>
                                </div>
                                <a href="../user/cv-download.php?id=<?php echo $cv['id']; ?>&action=preview" class="btn btn-sm btn-outline" target="_blank">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Quick Stats -->
                <div class="profile-card">
                    <h2><i class="fas fa-chart-bar"></i> Quick Stats</h2>
                    <div class="info-item" style="margin-bottom: 1rem;">
                        <div class="info-label">Member Since</div>
                        <div class="info-value"><?php echo date('M Y', strtotime($seeker['user_created_at'])); ?></div>
                    </div>
                    <div class="info-item" style="margin-bottom: 1rem;">
                        <div class="info-label">CVs Uploaded</div>
                        <div class="info-value"><?php echo count($cvs); ?></div>
                    </div>
                    <div class="info-item" style="margin-bottom: 1rem;">
                        <div class="info-label">Applications to Your Jobs</div>
                        <div class="info-value"><?php echo count($applications); ?></div>
                    </div>
                    <div class="info-item" style="margin-bottom: 1rem;">
                        <div class="info-label">Account Status</div>
                        <div class="info-value">
                            <?php if ($seeker['is_active']): ?>
                                <span style="color: var(--accent);"><i class="fas fa-check-circle"></i> Active</span>
                            <?php else: ?>
                                <span style="color: var(--error);"><i class="fas fa-times-circle"></i> Inactive</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="info-item" style="margin-bottom: 1rem;">
                        <div class="info-label">Email Status</div>
                        <div class="info-value">
                            <?php if ($seeker['email_verified']): ?>
                                <span style="color: var(--accent);"><i class="fas fa-check-circle"></i> Verified</span>
                            <?php else: ?>
                                <span style="color: var(--warning);"><i class="fas fa-exclamation-circle"></i> Unverified</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">NIN Verification</div>
                        <div class="info-value">
                            <?php if (!empty($seeker['nin_verified'])): ?>
                                <span style="color: var(--accent);">
                                    <i class="fas fa-check-circle"></i> Verified
                                    <?php if (!empty($seeker['nin_verified_at'])): ?>
                                        <small style="color: var(--text-secondary); margin-left: 8px;">
                                            (<?php echo date('M d, Y', strtotime($seeker['nin_verified_at'])); ?>)
                                        </small>
                                    <?php endif; ?>
                                </span>
                            <?php else: ?>
                                <span style="color: var(--warning);">
                                    <i class="fas fa-exclamation-circle"></i> Not Verified
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
</body>
</html>
