<?php
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/constants.php';

requireJobSeeker();

$userId = getCurrentUserId();

// Get user profile
$stmt = $pdo->prepare("
    SELECT u.first_name, u.last_name, u.profile_picture,
           jsp.skills, jsp.years_of_experience, jsp.education_level,
           jsp.current_state, jsp.current_city, jsp.job_status,
           jsp.salary_expectation_min, jsp.salary_expectation_max
    FROM users u
    LEFT JOIN job_seeker_profiles jsp ON u.id = jsp.user_id
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculate profile completeness
$completeness = 0;
$total_fields = 8;
$filled_fields = 0;

if (!empty($profile['skills'])) $filled_fields++;
if (!empty($profile['years_of_experience'])) $filled_fields++;
if (!empty($profile['education_level'])) $filled_fields++;
if (!empty($profile['current_state'])) $filled_fields++;
if (!empty($profile['current_city'])) $filled_fields++;
if (!empty($profile['job_status'])) $filled_fields++;
if (!empty($profile['salary_expectation_min'])) $filled_fields++;
if (!empty($profile['salary_expectation_max'])) $filled_fields++;

$completeness = round(($filled_fields / $total_fields) * 100);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Job Recommendations - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding-bottom: 4rem;
        }

        .ai-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .ai-header {
            text-align: center;
            padding: 3rem 1rem;
            color: white;
        }

        .ai-header h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .ai-header p {
            font-size: 1.2rem;
            opacity: 0.95;
        }

        .profile-completeness {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }

        .completeness-bar {
            background: #e5e7eb;
            height: 12px;
            border-radius: 12px;
            overflow: hidden;
            margin: 1rem 0;
        }

        .completeness-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981 0%, #3b82f6 100%);
            transition: width 0.5s ease;
        }

        .filter-section {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
        }

        .filter-options {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }

        .filter-btn {
            padding: 0.6rem 1.2rem;
            border: 2px solid #e5e7eb;
            border-radius: 24px;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
        }

        .filter-btn:hover,
        .filter-btn.active {
            border-color: var(--primary);
            background: #fef2f2;
            color: var(--primary);
        }

        .recommendations-grid {
            display: grid;
            gap: 1.5rem;
        }

        .job-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border-left: 4px solid #e5e7eb;
        }

        .job-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 32px rgba(0,0,0,0.12);
        }

        .job-card.excellent {
            border-left-color: #10b981;
        }

        .job-card.good {
            border-left-color: #3b82f6;
        }

        .job-card.fair {
            border-left-color: #f59e0b;
        }

        .job-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .job-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .match-badge {
            padding: 0.5rem 1rem;
            border-radius: 24px;
            font-weight: 700;
            font-size: 0.9rem;
            white-space: nowrap;
        }

        .match-badge.excellent {
            background: #d1fae5;
            color: #065f46;
        }

        .match-badge.good {
            background: #dbeafe;
            color: #1e40af;
        }

        .match-badge.fair {
            background: #fef3c7;
            color: #92400e;
        }

        .job-company {
            font-size: 1.1rem;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }

        .job-meta {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            margin: 1rem 0;
            font-size: 0.95rem;
            color: var(--text-secondary);
        }

        .match-reasons {
            background: #f9fafb;
            padding: 1rem;
            border-radius: 12px;
            margin: 1rem 0;
            border-left: 3px solid var(--primary);
        }

        .match-reasons-title {
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .job-tags {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin: 1rem 0;
        }

        .tag {
            background: #f3f4f6;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .job-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .btn-primary-action {
            flex: 1;
            padding: 0.9rem 1.5rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary-action:hover {
            background: #b91c1c;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }

        .btn-secondary-action {
            padding: 0.9rem 1.5rem;
            background: white;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-secondary-action:hover {
            border-color: var(--primary);
            background: #fef2f2;
        }

        .loading-container {
            text-align: center;
            padding: 4rem 2rem;
        }

        .spinner {
            border: 4px solid #f3f4f6;
            border-top: 4px solid var(--primary);
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1.5rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 16px;
        }

        .empty-icon {
            font-size: 5rem;
            margin-bottom: 1.5rem;
        }

        @media (max-width: 768px) {
            .ai-header h1 {
                font-size: 1.8rem;
            }

            .job-header {
                flex-direction: column;
                gap: 1rem;
            }

            .match-badge {
                align-self: flex-start;
            }
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>

    <div class="ai-container">
        <div class="ai-header">
            <h1>🤖 AI Job Recommendations</h1>
            <p>Personalized matches powered by artificial intelligence</p>
        </div>

        <!-- Profile Completeness -->
        <div class="profile-completeness">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3 style="margin: 0;">Profile Strength</h3>
                <span style="font-weight: 700; font-size: 1.5rem; color: var(--primary);"><?php echo $completeness; ?>%</span>
            </div>
            <div class="completeness-bar">
                <div class="completeness-fill" style="width: <?php echo $completeness; ?>%;"></div>
            </div>
            <p style="color: var(--text-secondary); margin-top: 0.5rem;">
                <?php if ($completeness < 50): ?>
                    ⚠️ Complete your profile to get better AI recommendations
                <?php elseif ($completeness < 80): ?>
                    👍 Good! Add more details for even better matches
                <?php else: ?>
                    ✨ Excellent! Your profile is optimized for AI matching
                <?php endif; ?>
            </p>
            <?php if ($completeness < 100): ?>
                <a href="profile.php" class="btn btn-primary" style="margin-top: 1rem; display: inline-block;">Complete Profile</a>
            <?php endif; ?>
        </div>

        <!-- Filters -->
        <div class="filter-section">
            <h3 style="margin-bottom: 0.5rem;">Filter Recommendations</h3>
            <div class="filter-options">
                <button class="filter-btn active" data-filter="all">All Matches</button>
                <button class="filter-btn" data-filter="excellent">Excellent Match (80%+)</button>
                <button class="filter-btn" data-filter="good">Good Match (60%+)</button>
                <button class="filter-btn" data-filter="remote">Remote Friendly</button>
                <button class="filter-btn" data-filter="urgent">Urgent</button>
            </div>
        </div>

        <!-- Recommendations Grid -->
        <div id="recommendations-container" class="recommendations-grid">
            <div class="loading-container">
                <div class="spinner"></div>
                <h3>AI is analyzing thousands of jobs...</h3>
                <p style="color: var(--text-secondary);">Finding the perfect matches for your profile</p>
            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>

    <script>
        let allRecommendations = [];
        let currentFilter = 'all';

        // Load recommendations on page load
        document.addEventListener('DOMContentLoaded', () => {
            loadRecommendations();
            setupFilters();
        });

        async function loadRecommendations() {
            try {
                const response = await fetch('/findajob/api/ai-job-recommendations.php');
                const data = await response.json();

                if (data.success && data.recommendations.length > 0) {
                    allRecommendations = data.recommendations;
                    renderRecommendations(allRecommendations);
                } else {
                    renderEmptyState();
                }
            } catch (error) {
                console.error('Failed to load recommendations:', error);
                renderErrorState();
            }
        }

        function setupFilters() {
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    currentFilter = btn.dataset.filter;
                    filterRecommendations();
                });
            });
        }

        function filterRecommendations() {
            let filtered = [...allRecommendations];

            if (currentFilter === 'excellent') {
                filtered = filtered.filter(job => job.match_score >= 80);
            } else if (currentFilter === 'good') {
                filtered = filtered.filter(job => job.match_score >= 60);
            } else if (currentFilter === 'remote') {
                filtered = filtered.filter(job => job.remote_friendly);
            } else if (currentFilter === 'urgent') {
                filtered = filtered.filter(job => job.is_urgent);
            }

            renderRecommendations(filtered);
        }

        function renderRecommendations(recommendations) {
            const container = document.getElementById('recommendations-container');
            container.innerHTML = '';

            if (recommendations.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-icon">🔍</div>
                        <h3>No matches found for this filter</h3>
                        <p style="color: var(--text-secondary); margin: 1rem 0;">Try a different filter or update your profile preferences</p>
                        <button onclick="location.reload()" class="btn-primary-action" style="display: inline-block; flex: none;">Show All Recommendations</button>
                    </div>
                `;
                return;
            }

            recommendations.forEach(job => {
                const card = createJobCard(job);
                container.appendChild(card);
            });
        }

        function createJobCard(job) {
            const div = document.createElement('div');
            div.className = `job-card ${job.match_level}`;
            
            div.innerHTML = `
                <div class="job-header">
                    <div style="flex: 1;">
                        <h2 class="job-title">
                            <a href="${job.job_url}" style="text-decoration: none; color: inherit;">
                                ${escapeHtml(job.title)}
                            </a>
                            ${job.is_urgent ? '<span class="tag" style="background: #fee2e2; color: #991b1b; margin-left: 0.5rem;">🔥 URGENT</span>' : ''}
                        </h2>
                        <div class="job-company">
                            <i class="fas fa-building"></i> ${escapeHtml(job.company_name)}
                        </div>
                    </div>
                    <div class="match-badge ${job.match_level}">
                        ${job.match_score}% Match
                    </div>
                </div>

                <div class="job-meta">
                    <span><i class="fas fa-map-marker-alt"></i> ${escapeHtml(job.location)}</span>
                    <span><i class="fas fa-money-bill-wave"></i> ${job.formatted_salary}</span>
                    <span><i class="fas fa-briefcase"></i> ${capitalizeFirst(job.employment_type)}</span>
                    <span><i class="fas fa-clock"></i> ${job.time_ago}</span>
                    ${job.days_left !== null ? `<span style="color: ${job.days_left <= 7 ? '#ef4444' : 'inherit'}"><i class="fas fa-calendar-alt"></i> ${job.days_left} days left</span>` : ''}
                </div>

                ${job.match_reasons ? `
                    <div class="match-reasons">
                        <div class="match-reasons-title"><i class="fas fa-check-circle"></i> Why this job matches you:</div>
                        <div>${escapeHtml(job.match_reasons)}</div>
                    </div>
                ` : ''}

                ${job.remote_friendly ? '<div class="tag" style="background: #d1fae5; color: #065f46; display: inline-block;"><i class="fas fa-home"></i> Remote Friendly</div>' : ''}

                <div class="job-actions">
                    <button class="btn-primary-action" onclick="viewJobDetails(${job.id}, '${job.slug || ''}')">
                        <i class="fas fa-eye"></i> View Details
                    </button>
                    <button class="btn-secondary-action" onclick="saveJob(${job.id}, this)" title="Save for later">
                        <i class="fas fa-heart"></i>
                    </button>
                </div>
            `;

            return div;
        }

        function renderEmptyState() {
            const container = document.getElementById('recommendations-container');
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">🤖</div>
                    <h3>No AI Recommendations Yet</h3>
                    <p style="color: var(--text-secondary); margin: 1.5rem 0; max-width: 500px; margin-left: auto; margin-right: auto;">
                        Complete your profile with skills, experience, location preferences, and salary expectations to receive personalized AI-powered job recommendations.
                    </p>
                    <button onclick="window.location.href='profile.php'" class="btn-primary-action" style="display: inline-block; flex: none;">
                        <i class="fas fa-user-edit"></i> Complete Your Profile
                    </button>
                </div>
            `;
        }

        function renderErrorState() {
            const container = document.getElementById('recommendations-container');
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">⚠️</div>
                    <h3>Oops! Something went wrong</h3>
                    <p style="color: var(--text-secondary); margin: 1rem 0;">We couldn't load your recommendations. Please try again.</p>
                    <button onclick="location.reload()" class="btn-primary-action" style="display: inline-block; flex: none;">
                        <i class="fas fa-redo"></i> Retry
                    </button>
                </div>
            `;
        }

        function applyToJob(jobId) {
            window.location.href = '/findajob/pages/jobs/apply.php?id=' + jobId;
        }

        function viewJobDetails(jobId, slug) {
            window.location.href = '/findajob/pages/jobs/details.php?id=' + jobId;
        }

        async function saveJob(jobId, button) {
            try {
                const response = await fetch('/findajob/api/save-job.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ job_id: jobId })
                });

                const data = await response.json();

                if (data.success) {
                    button.innerHTML = '<i class="fas fa-heart" style="color: var(--primary);"></i>';
                    button.style.borderColor = 'var(--primary)';
                    showNotification('Job saved successfully!');
                } else {
                    showNotification(data.message || 'Failed to save job');
                }
            } catch (error) {
                showNotification('Failed to save job');
            }
        }

        function showNotification(message) {
            // Simple notification
            const notification = document.createElement('div');
            notification.style.cssText = 'position: fixed; top: 20px; right: 20px; background: white; padding: 1rem 1.5rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 9999; border-left: 4px solid var(--primary);';
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => notification.remove(), 3000);
        }

        function capitalizeFirst(str) {
            if (!str) return '';
            return str.charAt(0).toUpperCase() + str.slice(1).replace(/_/g, ' ');
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
