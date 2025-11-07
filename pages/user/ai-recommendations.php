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
        :root {
            --ai-primary: #dc2626;
            --ai-primary-dark: #991b1b;
            --ai-primary-light: #fecaca;
        }

        body {
            background: #f8fafc;
            min-height: 100vh;
            padding-bottom: 4rem;
        }

        /* Hero Section */
        .ai-hero {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            padding: 4rem 0 6rem;
            position: relative;
            overflow: hidden;
        }

        .ai-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><rect fill="%23ffffff" fill-opacity="0.05" width="50" height="50"/></svg>');
            animation: slide 20s linear infinite;
        }

        @keyframes slide {
            0% { transform: translateX(0); }
            100% { transform: translateX(50px); }
        }

        .ai-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
            position: relative;
            z-index: 1;
        }

        .ai-header {
            text-align: center;
            color: white;
            margin-bottom: 2rem;
        }

        .ai-header h1 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1rem;
            text-shadow: 0 4px 12px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }

        .ai-header .ai-icon {
            font-size: 3.5rem;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .ai-header p {
            font-size: 1.3rem;
            opacity: 0.95;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* Profile Completeness Card */
        .profile-completeness {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            margin: -3rem auto 2rem;
            max-width: 800px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            border: 1px solid rgba(255,255,255,0.9);
        }

        .completeness-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .completeness-header h3 {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .completeness-percentage {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #dc2626, #991b1b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .completeness-bar {
            background: #e5e7eb;
            height: 16px;
            border-radius: 16px;
            overflow: hidden;
            position: relative;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
        }

        .completeness-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981 0%, #3b82f6 50%, #dc2626 100%);
            transition: width 1s ease;
            position: relative;
            overflow: hidden;
        }

        .completeness-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .completeness-tip {
            margin-top: 1rem;
            padding: 1rem;
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border-radius: 12px;
            font-size: 0.95rem;
            color: #92400e;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        /* Filter Section */
        .filter-section {
            background: white;
            border-radius: 16px;
            padding: 1.5rem 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
            border: 1px solid #f3f4f6;
        }

        .filter-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
            font-weight: 700;
            color: var(--text-primary);
            font-size: 1.1rem;
        }

        .filter-options {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 0.7rem 1.5rem;
            border: 2px solid #e5e7eb;
            border-radius: 30px;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-btn:hover {
            border-color: #dc2626;
            background: linear-gradient(135deg, rgba(220, 38, 38, 0.1), rgba(153, 27, 27, 0.1));
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2);
        }

        .filter-btn.active {
            border-color: #dc2626;
            background: linear-gradient(135deg, #dc2626, #991b1b);
            color: white;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }

        /* Recommendations Grid */
        .recommendations-grid {
            display: grid;
            gap: 1.5rem;
        }

        .job-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border-left: 5px solid #e5e7eb;
            position: relative;
            overflow: hidden;
        }

        .job-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.03), rgba(118, 75, 162, 0.03));
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .job-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.12);
        }

        .job-card:hover::before {
            opacity: 1;
        }

        .job-card.excellent {
            border-left-color: #10b981;
        }

        .job-card.excellent:hover {
            box-shadow: 0 12px 40px rgba(16, 185, 129, 0.2);
        }

        .job-card.good {
            border-left-color: #3b82f6;
        }

        .job-card.good:hover {
            box-shadow: 0 12px 40px rgba(59, 130, 246, 0.2);
        }

        .job-card.fair {
            border-left-color: #f59e0b;
        }

        .job-card.fair:hover {
            box-shadow: 0 12px 40px rgba(245, 158, 11, 0.2);
        }

        .job-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
            gap: 1rem;
        }

        .job-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            line-height: 1.3;
            transition: color 0.3s ease;
        }

        .job-title:hover {
            color: #dc2626;
        }

        .match-badge {
            padding: 0.6rem 1.2rem;
            border-radius: 30px;
            font-weight: 700;
            font-size: 0.9rem;
            white-space: nowrap;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .match-badge.excellent {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
        }

        .match-badge.good {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #1e40af;
        }

        .match-badge.fair {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #92400e;
        }

        .job-company {
            font-size: 1.1rem;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .job-meta {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            margin: 1.5rem 0;
            font-size: 0.95rem;
            color: var(--text-secondary);
        }

        .job-meta span {
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .match-reasons {
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            padding: 1.25rem;
            border-radius: 12px;
            margin: 1.5rem 0;
            border-left: 4px solid #3b82f6;
        }

        .match-reasons-title {
            font-weight: 700;
            color: #1e40af;
            margin-bottom: 0.75rem;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .match-reasons div {
            color: #1e3a8a;
            line-height: 1.6;
        }

        .job-tags {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin: 1rem 0;
        }

        .tag {
            background: #f3f4f6;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            color: var(--text-secondary);
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .tag:hover {
            background: #e5e7eb;
            transform: translateY(-2px);
        }

        /* Action Buttons */
        .job-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .btn-primary-action {
            flex: 1;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #dc2626, #991b1b);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary-action:hover {
            background: linear-gradient(135deg, #b91c1c, #7f1d1d);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.4);
        }

        .btn-primary-action:active {
            transform: translateY(0);
        }

        .btn-secondary-action {
            padding: 1rem 1.5rem;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-secondary-action:hover {
            border-color: var(--primary);
            background: #fef2f2;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2);
        }

        /* Loading State */
        .loading-container {
            text-align: center;
            padding: 4rem 2rem;
        }

        .spinner {
            border: 4px solid rgba(220, 38, 38, 0.1);
            border-top: 4px solid #dc2626;
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

        /* Empty State */
        .empty-state {
            background: white;
            border-radius: 20px;
            padding: 4rem 2rem;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
        }

        .empty-icon {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.8; transform: scale(1.05); }
        }

        .empty-state h3 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .ai-header h1 {
                font-size: 2rem;
            }

            .ai-header .ai-icon {
                font-size: 2.5rem;
            }

            .profile-completeness {
                margin-top: -2rem;
                padding: 1.5rem;
            }

            .completeness-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .filter-section {
                padding: 1.25rem;
            }

            .job-card {
                padding: 1.5rem;
            }

            .job-header {
                flex-direction: column;
            }

            .match-badge {
                align-self: flex-start;
            }

            .job-actions {
                flex-direction: column;
            }

            .btn-secondary-action {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>

    <!-- Hero Section -->
    <div class="ai-hero">
        <div class="ai-container">
            <div class="ai-header">
                <h1>
                    <span class="ai-icon">🤖</span>
                    AI Job Recommendations
                </h1>
                <p>Personalized job matches powered by artificial intelligence, tailored just for you</p>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="ai-container">
        <!-- Profile Completeness Card -->
        <div class="profile-completeness">
            <div class="completeness-header">
                <h3>
                    <i class="fas fa-user-check" style="color: #dc2626;"></i>
                    Profile Strength
                </h3>
                <span class="completeness-percentage"><?php echo $completeness; ?>%</span>
            </div>
            <div class="completeness-bar">
                <div class="completeness-fill" style="width: <?php echo $completeness; ?>%;"></div>
            </div>
            <?php if ($completeness < 50): ?>
                <div class="completeness-tip">
                    <i class="fas fa-exclamation-circle" style="font-size: 1.5rem;"></i>
                    <div>
                        <strong>Action Required:</strong> Complete your profile to unlock better AI recommendations. 
                        Add your skills, experience, and preferences.
                    </div>
                </div>
            <?php elseif ($completeness < 80): ?>
                <div class="completeness-tip" style="background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #1e40af;">
                    <i class="fas fa-info-circle" style="font-size: 1.5rem;"></i>
                    <div>
                        <strong>Good Progress!</strong> Add more details like salary expectations and location preferences for even better matches.
                    </div>
                </div>
            <?php else: ?>
                <div class="completeness-tip" style="background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #065f46;">
                    <i class="fas fa-check-circle" style="font-size: 1.5rem;"></i>
                    <div>
                        <strong>Excellent!</strong> Your profile is optimized for AI matching. You'll get the most relevant job recommendations.
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($completeness < 100): ?>
                <div style="margin-top: 1.5rem; text-align: center;">
                    <a href="profile.php" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.9rem 2rem;">
                        <i class="fas fa-user-edit"></i>
                        Complete Your Profile
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="filter-header">
                <i class="fas fa-filter"></i>
                Filter Recommendations
            </div>
            <div class="filter-options">
                <button class="filter-btn active" data-filter="all">
                    <i class="fas fa-list"></i> All Matches
                </button>
                <button class="filter-btn" data-filter="excellent">
                    <i class="fas fa-star"></i> Excellent Match (80%+)
                </button>
                <button class="filter-btn" data-filter="good">
                    <i class="fas fa-thumbs-up"></i> Good Match (60%+)
                </button>
                <button class="filter-btn" data-filter="remote">
                    <i class="fas fa-home"></i> Remote Friendly
                </button>
                <button class="filter-btn" data-filter="urgent">
                    <i class="fas fa-fire"></i> Urgent
                </button>
            </div>
        </div>

        <!-- Recommendations Grid -->
        <div id="recommendations-container" class="recommendations-grid">
            <div class="loading-container">
                <div class="spinner"></div>
                <h3 style="color: var(--text-primary); font-weight: 700; margin-top: 1rem;">
                    <i class="fas fa-robot" style="color: #dc2626;"></i>
                    AI is analyzing your profile...
                </h3>
                <p style="color: var(--text-secondary); margin-top: 0.75rem; font-size: 1.1rem;">
                    Finding the perfect job matches tailored just for you
                </p>
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
                        <i class="fas fa-star"></i> ${job.match_score}% Match
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
