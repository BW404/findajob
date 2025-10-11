<?php
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/constants.php';

requireJobSeeker();

$userId = getCurrentUserId();

// Get user profile data
$stmt = $pdo->prepare("
    SELECT u.*, jsp.* 
    FROM users u 
    LEFT JOIN job_seeker_profiles jsp ON u.id = jsp.user_id 
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/main.css">
</head>
<body>
    <header class="site-header">
        <div class="container">
            <nav class="site-nav">
                <a href="/findajob" class="site-logo"><?php echo SITE_NAME; ?></a>
                <div>
                    <span>Welcome, <?php echo htmlspecialchars($user['first_name']); ?>!</span>
                    <?php if ($_SERVER['SERVER_NAME'] === 'localhost'): ?>
                        <a href="/findajob/temp_mail.php" target="_blank" class="btn btn-secondary" style="margin-right: 1rem;">📧 Dev Emails</a>
                    <?php endif; ?>
                    <a href="../auth/logout.php" class="btn btn-secondary">Logout</a>
                </div>
            </nav>
        </div>
    </header>

    <main class="container">
        <div style="padding: 2rem 0;">
            <h1>Job Seeker Dashboard</h1>
            
            <?php if (!$user['email_verified']): ?>
                <div class="alert alert-info">
                    <strong>Please verify your email address.</strong>
                    Your account is not fully activated until you verify your email.
                    <button onclick="resendVerification('<?php echo $user['email']; ?>')" class="btn btn-secondary mt-2">
                        Resend Verification Email
                    </button>
                </div>
            <?php endif; ?>

            <!-- Dashboard Stats Cards -->
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-icon">📋</div>
                    <div class="stat-content">
                        <h3>Applications</h3>
                        <div class="stat-number">12</div>
                        <div class="stat-change positive">+3 this week</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">👁️</div>
                    <div class="stat-content">
                        <h3>Profile Views</h3>
                        <div class="stat-number">45</div>
                        <div class="stat-change positive">+8 this week</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">💼</div>
                    <div class="stat-content">
                        <h3>Job Matches</h3>
                        <div class="stat-number">28</div>
                        <div class="stat-change neutral">New today</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">⭐</div>
                    <div class="stat-content">
                        <h3>Profile Score</h3>
                        <div class="stat-number">85%</div>
                        <div class="stat-change neutral">Complete profile</div>
                    </div>
                </div>
            </div>

            <!-- Main Dashboard Content -->
            <div class="dashboard-content">
                <!-- Left Column -->
                <div class="dashboard-left">
                    <!-- Profile Summary Card -->
                    <div class="dashboard-card profile-summary">
                        <div class="card-header">
                            <h3>Profile Summary</h3>
                            <?php if ($user['email_verified']): ?>
                                <span class="status-badge verified">✓ Verified</span>
                            <?php else: ?>
                                <span class="status-badge unverified">⚠ Unverified</span>
                            <?php endif; ?>
                        </div>
                        <div class="profile-info">
                            <div class="profile-avatar">
                                <img src="../../assets/images/default-avatar.png" alt="Profile" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="avatar-placeholder">
                                    <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                </div>
                            </div>
                            <div class="profile-details">
                                <h4><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                                <p class="profile-title">Software Developer</p>
                                <p class="profile-location">📍 Lagos, Nigeria</p>
                                <div class="profile-tags">
                                    <span class="tag">PHP</span>
                                    <span class="tag">JavaScript</span>
                                    <span class="tag">React</span>
                                </div>
                            </div>
                        </div>
                        <div class="profile-progress">
                            <div class="progress-header">
                                <span>Profile Completion</span>
                                <span>85%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 85%"></div>
                            </div>
                            <p class="progress-tip">Add your skills and experience to reach 100%</p>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>Quick Actions</h3>
                        </div>
                        <div class="quick-actions">
                            <a href="../jobs/browse.php" class="action-btn primary">
                                <div class="action-icon">🔍</div>
                                <div class="action-content">
                                    <div class="action-title">Browse Jobs</div>
                                    <div class="action-desc">Find your next opportunity</div>
                                </div>
                            </a>
                            
                            <a href="profile.php" class="action-btn">
                                <div class="action-icon">👤</div>
                                <div class="action-content">
                                    <div class="action-title">Update Profile</div>
                                    <div class="action-desc">Keep your info current</div>
                                </div>
                            </a>
                            
                            <a href="cv-manager.php" class="action-btn">
                                <div class="action-icon">📄</div>
                                <div class="action-content">
                                    <div class="action-title">Manage CVs</div>
                                    <div class="action-desc">Upload and organize resumes</div>
                                </div>
                            </a>
                            
                            <a href="subscription.php" class="action-btn upgrade">
                                <div class="action-icon">⭐</div>
                                <div class="action-content">
                                    <div class="action-title">Upgrade to Pro</div>
                                    <div class="action-desc">Unlock premium features</div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="dashboard-right">
                    <!-- Recent Applications -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>Recent Applications</h3>
                            <a href="applications.php" class="view-all">View All</a>
                        </div>
                        <div class="applications-list">
                            <div class="application-item">
                                <div class="application-info">
                                    <h4>Senior PHP Developer</h4>
                                    <p class="company">TechCorp Nigeria</p>
                                    <span class="application-date">Applied 2 days ago</span>
                                </div>
                                <div class="application-status">
                                    <span class="status-badge viewed">Viewed</span>
                                </div>
                            </div>
                            
                            <div class="application-item">
                                <div class="application-info">
                                    <h4>Full Stack Developer</h4>
                                    <p class="company">StartupHub Lagos</p>
                                    <span class="application-date">Applied 5 days ago</span>
                                </div>
                                <div class="application-status">
                                    <span class="status-badge shortlisted">Shortlisted</span>
                                </div>
                            </div>
                            
                            <div class="application-item">
                                <div class="application-info">
                                    <h4>Backend Developer</h4>
                                    <p class="company">FinTech Solutions</p>
                                    <span class="application-date">Applied 1 week ago</span>
                                </div>
                                <div class="application-status">
                                    <span class="status-badge applied">Applied</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recommended Jobs -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>Recommended for You</h3>
                            <span class="ai-badge">🤖 AI Matched</span>
                        </div>
                        <div class="jobs-list">
                            <div class="job-item">
                                <div class="job-header">
                                    <h4>Laravel Developer</h4>
                                    <span class="match-score">95% match</span>
                                </div>
                                <p class="job-company">🏢 Digital Agency Ltd</p>
                                <p class="job-location">📍 Victoria Island, Lagos</p>
                                <div class="job-details">
                                    <span class="job-salary">₦300K - ₦500K</span>
                                    <span class="job-type">Full-time</span>
                                </div>
                                <div class="job-actions">
                                    <button class="btn-apply">Quick Apply</button>
                                    <button class="btn-save">💖</button>
                                </div>
                            </div>
                            
                            <div class="job-item">
                                <div class="job-header">
                                    <h4>React Developer</h4>
                                    <span class="match-score">88% match</span>
                                </div>
                                <p class="job-company">🏢 Innovation Hub</p>
                                <p class="job-location">📍 Ikeja, Lagos</p>
                                <div class="job-details">
                                    <span class="job-salary">₦250K - ₦400K</span>
                                    <span class="job-type">Remote</span>
                                </div>
                                <div class="job-actions">
                                    <button class="btn-apply">Quick Apply</button>
                                    <button class="btn-save">💖</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>Recent Activity</h3>
                        </div>
                        <div class="activity-list">
                            <div class="activity-item">
                                <div class="activity-icon viewed">👁️</div>
                                <div class="activity-content">
                                    <p><strong>TechCorp Nigeria</strong> viewed your profile</p>
                                    <span class="activity-time">2 hours ago</span>
                                </div>
                            </div>
                            
                            <div class="activity-item">
                                <div class="activity-icon applied">📋</div>
                                <div class="activity-content">
                                    <p>You applied to <strong>Senior PHP Developer</strong></p>
                                    <span class="activity-time">2 days ago</span>
                                </div>
                            </div>
                            
                            <div class="activity-item">
                                <div class="activity-icon match">🎯</div>
                                <div class="activity-content">
                                    <p><strong>5 new job matches</strong> found for you</p>
                                    <span class="activity-time">3 days ago</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Bottom Navigation for Mobile -->
    <nav class="app-bottom-nav">
        <a href="../../index.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">🏠</div>
            <div class="app-bottom-nav-label">Home</div>
        </a>
        <a href="../jobs/browse.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">🔍</div>
            <div class="app-bottom-nav-label">Jobs</div>
        </a>
        <a href="dashboard.php" class="app-bottom-nav-item active">
            <div class="app-bottom-nav-icon">📊</div>
            <div class="app-bottom-nav-label">Dashboard</div>
        </a>
        <a href="profile.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">👤</div>
            <div class="app-bottom-nav-label">Profile</div>
        </a>
    </nav>

    <script src="../../assets/js/auth.js"></script>
    <script src="../../assets/js/pwa.js"></script>
    <script>
        async function resendVerification(email) {
            try {
                const formData = new FormData();
                formData.append('action', 'resend_verification');
                formData.append('email', email);

                const response = await fetch('/findajob/api/auth.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                alert(result.message);
            } catch (error) {
                alert('Failed to resend verification email.');
            }
        }

        // Dashboard interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Quick apply functionality
            const applyButtons = document.querySelectorAll('.btn-apply');
            applyButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const jobTitle = this.closest('.job-item').querySelector('h4').textContent;
                    if (confirm(`Apply to ${jobTitle}?`)) {
                        // Add loading state
                        this.textContent = 'Applying...';
                        this.disabled = true;
                        
                        // Simulate application process
                        setTimeout(() => {
                            this.textContent = 'Applied ✓';
                            this.style.background = '#059669';
                            
                            // Show success message
                            showNotification('Application submitted successfully!', 'success');
                        }, 1500);
                    }
                });
            });

            // Save job functionality
            const saveButtons = document.querySelectorAll('.btn-save');
            saveButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const isSaved = this.textContent === '❤️';
                    
                    if (isSaved) {
                        this.textContent = '💖';
                        this.style.background = '#fecaca';
                        showNotification('Job removed from saved', 'info');
                    } else {
                        this.textContent = '❤️';
                        this.style.background = '#dcfce7';
                        showNotification('Job saved successfully!', 'success');
                    }
                });
            });

            // Animate stats on load
            const statNumbers = document.querySelectorAll('.stat-number');
            statNumbers.forEach((stat, index) => {
                const finalValue = parseInt(stat.textContent);
                stat.textContent = '0';
                
                setTimeout(() => {
                    animateNumber(stat, finalValue);
                }, index * 200);
            });
        });

        function animateNumber(element, target) {
            const duration = 1000;
            const start = 0;
            const increment = target / (duration / 16);
            let current = start;

            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    element.textContent = target + (element.textContent.includes('%') ? '%' : '');
                    clearInterval(timer);
                } else {
                    element.textContent = Math.floor(current) + (element.textContent.includes('%') ? '%' : '');
                }
            }, 16);
        }

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <div class="notification-content">
                    <span>${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()">×</button>
                </div>
            `;
            
            // Add notification styles if not exist
            if (!document.querySelector('#notification-styles')) {
                const styles = document.createElement('style');
                styles.id = 'notification-styles';
                styles.textContent = `
                    .notification {
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        background: white;
                        border-radius: 8px;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                        border-left: 4px solid var(--primary);
                        z-index: 1000;
                        animation: slideIn 0.3s ease;
                    }
                    .notification-success { border-left-color: var(--success); }
                    .notification-info { border-left-color: var(--primary); }
                    .notification-content {
                        padding: 1rem;
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        gap: 1rem;
                    }
                    .notification button {
                        background: none;
                        border: none;
                        font-size: 1.5rem;
                        cursor: pointer;
                        color: var(--text-secondary);
                    }
                    @keyframes slideIn {
                        from { transform: translateX(100%); opacity: 0; }
                        to { transform: translateX(0); opacity: 1; }
                    }
                `;
                document.head.appendChild(styles);
            }
            
            document.body.appendChild(notification);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 3000);
        }

        // Add body class for bottom nav
        document.body.classList.add('has-bottom-nav');
    </script>
</body>
</html>