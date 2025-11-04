<?php
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/constants.php';

requireEmployer();

$employer_id = getCurrentUserId();

// Get filter parameters
$search_query = $_GET['search'] ?? '';
$skills_filter = $_GET['skills'] ?? '';
$experience_filter = $_GET['experience'] ?? '';
$location_filter = $_GET['location'] ?? '';
$education_filter = $_GET['education'] ?? '';
$sort_by = $_GET['sort'] ?? 'newest';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query with filters
$query = "SELECT DISTINCT
          cv.id as cv_id,
          cv.title as cv_title,
          cv.file_type,
          cv.created_at,
          cv.updated_at,
          u.id as user_id,
          u.first_name,
          u.last_name,
          u.email,
          u.phone,
          jsp.skills,
          jsp.years_of_experience,
          jsp.education_level,
          jsp.current_city,
          jsp.current_state,
          jsp.bio,
          jsp.nin_verified,
          jsp.profile_picture
          FROM cvs cv
          INNER JOIN users u ON cv.user_id = u.id
          LEFT JOIN job_seeker_profiles jsp ON u.id = jsp.user_id
          WHERE u.user_type = 'job_seeker'
          AND u.is_active = 1";

$params = [];

// Search query filter
if (!empty($search_query)) {
    $query .= " AND (
        cv.title LIKE ? OR
        jsp.skills LIKE ? OR
        jsp.bio LIKE ?
    )";
    $search_param = "%{$search_query}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

// Skills filter
if (!empty($skills_filter)) {
    $query .= " AND jsp.skills LIKE ?";
    $params[] = "%{$skills_filter}%";
}

// Experience filter
if (!empty($experience_filter)) {
    switch ($experience_filter) {
        case '0-1':
            $query .= " AND jsp.years_of_experience <= 1";
            break;
        case '1-3':
            $query .= " AND jsp.years_of_experience BETWEEN 1 AND 3";
            break;
        case '3-5':
            $query .= " AND jsp.years_of_experience BETWEEN 3 AND 5";
            break;
        case '5-10':
            $query .= " AND jsp.years_of_experience BETWEEN 5 AND 10";
            break;
        case '10+':
            $query .= " AND jsp.years_of_experience >= 10";
            break;
    }
}

// Location filter
if (!empty($location_filter)) {
    $query .= " AND (jsp.current_state LIKE ? OR jsp.current_city LIKE ?)";
    $location_param = "%{$location_filter}%";
    $params = array_merge($params, [$location_param, $location_param]);
}

// Education filter
if (!empty($education_filter)) {
    $query .= " AND jsp.education_level = ?";
    $params[] = $education_filter;
}

// Sorting
switch ($sort_by) {
    case 'oldest':
        $query .= " ORDER BY cv.created_at ASC";
        break;
    case 'experience_high':
        $query .= " ORDER BY jsp.years_of_experience DESC";
        break;
    case 'experience_low':
        $query .= " ORDER BY jsp.years_of_experience ASC";
        break;
    default: // newest
        $query .= " ORDER BY cv.created_at DESC";
}

// Get total count for pagination
$count_query = "SELECT COUNT(DISTINCT cv.id) as total FROM cvs cv
                INNER JOIN users u ON cv.user_id = u.id
                LEFT JOIN job_seeker_profiles jsp ON u.id = jsp.user_id
                WHERE u.user_type = 'job_seeker'
                AND u.is_active = 1";

if (!empty($search_query)) {
    $count_query .= " AND (
        cv.title LIKE ? OR
        jsp.skills LIKE ? OR
        jsp.bio LIKE ?
    )";
}

if (!empty($skills_filter)) {
    $count_query .= " AND jsp.skills LIKE ?";
}

if (!empty($experience_filter)) {
    switch ($experience_filter) {
        case '0-1':
            $count_query .= " AND jsp.years_of_experience <= 1";
            break;
        case '1-3':
            $count_query .= " AND jsp.years_of_experience BETWEEN 1 AND 3";
            break;
        case '3-5':
            $count_query .= " AND jsp.years_of_experience BETWEEN 3 AND 5";
            break;
        case '5-10':
            $count_query .= " AND jsp.years_of_experience BETWEEN 5 AND 10";
            break;
        case '10+':
            $count_query .= " AND jsp.years_of_experience >= 10";
            break;
    }
}

if (!empty($location_filter)) {
    $count_query .= " AND (jsp.current_state LIKE ? OR jsp.current_city LIKE ?)";
}

if (!empty($education_filter)) {
    $count_query .= " AND jsp.education_level = ?";
}

$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total_results = $count_stmt->fetch()['total'];
$total_pages = ceil($total_results / $per_page);

// Add pagination - use direct values instead of binding for LIMIT/OFFSET
$query .= " LIMIT " . (int)$per_page . " OFFSET " . (int)$offset;

// Execute main query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$cvs = $stmt->fetchAll();

$page_title = 'Search CVs - FindAJob Nigeria';
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
        .cv-search-container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .search-header {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: white;
            padding: 3rem 2rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(220, 38, 38, 0.3);
        }

        .search-header h1 {
            margin: 0 0 0.5rem 0;
            font-size: 2.5rem;
        }

        .search-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 1.125rem;
        }

        .search-layout {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
            align-items: start;
        }

        .filters-sidebar {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: sticky;
            top: 2rem;
        }

        .filter-section {
            margin-bottom: 1.5rem;
        }

        .filter-section h3 {
            margin: 0 0 1rem 0;
            font-size: 1rem;
            color: #374151;
        }

        .filter-section input,
        .filter-section select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 1rem;
        }

        .results-container {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e5e7eb;
        }

        .results-count {
            font-size: 1.125rem;
            color: #374151;
            font-weight: 600;
        }

        .sort-select {
            padding: 0.5rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .cv-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s;
        }

        .cv-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .cv-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .cv-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #111827;
            margin: 0 0 0.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .verified-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            background: #1877f2;
            border-radius: 50%;
            color: white;
            font-size: 12px;
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            flex-shrink: 0;
        }

        .cv-meta {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .cv-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .cv-skills {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .skill-tag {
            background: #eff6ff;
            color: #1e40af;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
        }

        .cv-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
        }

        .btn-outline {
            background: white;
            color: #f59e0b;
            border: 2px solid #f59e0b;
        }

        .btn-outline:hover {
            background: #f59e0b;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: white;
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #047857 0%, #065f46 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.4);
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .page-link {
            padding: 0.5rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            text-decoration: none;
            color: #374151;
            transition: all 0.3s;
        }

        .page-link:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .page-link.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            opacity: 0.3;
        }

        @media (max-width: 768px) {
            .search-layout {
                grid-template-columns: 1fr;
            }

            .filters-sidebar {
                position: static;
            }

            .search-header h1 {
                font-size: 1.75rem;
            }

            .results-header {
                flex-direction: column;
                gap: 1rem;
                align-items: start;
            }
        }
    </style>
</head>
<body class="has-bottom-nav">
    <?php include '../../includes/header.php'; ?>

    <div class="cv-search-container">
        <div class="search-header">
            <h1>🔍 Search CVs</h1>
            <p>Find qualified candidates for your positions</p>
        </div>

        <div class="search-layout">
            <!-- Filters Sidebar -->
            <aside class="filters-sidebar">
                <form method="GET" action="">
                    <div class="filter-section">
                        <h3>🔎 Keyword Search</h3>
                        <input type="text" name="search" placeholder="Job title, skills..." value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>

                    <div class="filter-section">
                        <h3>💼 Skills</h3>
                        <input type="text" name="skills" placeholder="e.g. PHP, JavaScript" value="<?php echo htmlspecialchars($skills_filter); ?>">
                    </div>

                    <div class="filter-section">
                        <h3>📊 Experience</h3>
                        <select name="experience">
                            <option value="">All Experience</option>
                            <option value="0-1" <?php echo $experience_filter === '0-1' ? 'selected' : ''; ?>>0-1 years</option>
                            <option value="1-3" <?php echo $experience_filter === '1-3' ? 'selected' : ''; ?>>1-3 years</option>
                            <option value="3-5" <?php echo $experience_filter === '3-5' ? 'selected' : ''; ?>>3-5 years</option>
                            <option value="5-10" <?php echo $experience_filter === '5-10' ? 'selected' : ''; ?>>5-10 years</option>
                            <option value="10+" <?php echo $experience_filter === '10+' ? 'selected' : ''; ?>>10+ years</option>
                        </select>
                    </div>

                    <div class="filter-section">
                        <h3>📍 Location</h3>
                        <input type="text" name="location" placeholder="City or State" value="<?php echo htmlspecialchars($location_filter); ?>">
                    </div>

                    <div class="filter-section">
                        <h3>🎓 Education</h3>
                        <select name="education">
                            <option value="">All Education Levels</option>
                            <option value="ssce" <?php echo $education_filter === 'ssce' ? 'selected' : ''; ?>>SSCE/O-Level</option>
                            <option value="ond" <?php echo $education_filter === 'ond' ? 'selected' : ''; ?>>OND</option>
                            <option value="hnd" <?php echo $education_filter === 'hnd' ? 'selected' : ''; ?>>HND</option>
                            <option value="bsc" <?php echo $education_filter === 'bsc' ? 'selected' : ''; ?>>BSc/BA</option>
                            <option value="msc" <?php echo $education_filter === 'msc' ? 'selected' : ''; ?>>MSc/MA</option>
                            <option value="phd" <?php echo $education_filter === 'phd' ? 'selected' : ''; ?>>PhD</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-success" style="width: 100%; justify-content: center; margin-top: 1rem;">
                        <i class="fas fa-search"></i> Apply Filters
                    </button>

                    <?php if (!empty($search_query) || !empty($skills_filter) || !empty($experience_filter) || !empty($location_filter) || !empty($education_filter)): ?>
                        <a href="search-cvs.php" class="btn btn-outline" style="width: 100%; justify-content: center; margin-top: 0.5rem;">
                            <i class="fas fa-times"></i> Clear All
                        </a>
                    <?php endif; ?>
                </form>
            </aside>

            <!-- Results Container -->
            <main class="results-container">
                <div class="results-header">
                    <div class="results-count">
                        <?php echo number_format($total_results); ?> CV<?php echo $total_results != 1 ? 's' : ''; ?> Found
                    </div>
                    <form method="GET" style="display: inline;">
                        <?php if ($search_query): ?><input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>"><?php endif; ?>
                        <?php if ($skills_filter): ?><input type="hidden" name="skills" value="<?php echo htmlspecialchars($skills_filter); ?>"><?php endif; ?>
                        <?php if ($experience_filter): ?><input type="hidden" name="experience" value="<?php echo htmlspecialchars($experience_filter); ?>"><?php endif; ?>
                        <?php if ($location_filter): ?><input type="hidden" name="location" value="<?php echo htmlspecialchars($location_filter); ?>"><?php endif; ?>
                        <?php if ($education_filter): ?><input type="hidden" name="education" value="<?php echo htmlspecialchars($education_filter); ?>"><?php endif; ?>
                        
                        <select name="sort" class="sort-select" onchange="this.form.submit()">
                            <option value="newest" <?php echo $sort_by === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="oldest" <?php echo $sort_by === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                            <option value="experience_high" <?php echo $sort_by === 'experience_high' ? 'selected' : ''; ?>>Most Experience</option>
                            <option value="experience_low" <?php echo $sort_by === 'experience_low' ? 'selected' : ''; ?>>Least Experience</option>
                        </select>
                    </form>
                </div>

                <?php if (empty($cvs)): ?>
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <h3>No CVs Found</h3>
                        <p>Try adjusting your search filters or keywords.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($cvs as $cv): ?>
                        <div class="cv-card">
                            <div class="cv-header">
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <!-- Profile Picture -->
                                    <div style="flex-shrink: 0;">
                                        <?php if (!empty($cv['profile_picture'])): ?>
                                            <?php
                                            // Normalize profile picture path
                                            $profile_pic_url = $cv['profile_picture'];
                                            if (strpos($profile_pic_url, '/') === 0 || preg_match('#^https?://#i', $profile_pic_url)) {
                                                // Already absolute path or full URL
                                                $profile_pic_url = $profile_pic_url;
                                            } else {
                                                // Relative path - prepend base path
                                                $profile_pic_url = '/findajob/' . ltrim($profile_pic_url, '/');
                                            }
                                            ?>
                                            <img src="<?php echo htmlspecialchars($profile_pic_url); ?>" 
                                                 alt="<?php echo htmlspecialchars($cv['first_name']); ?>" 
                                                 style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid #e5e7eb;">
                                        <?php else: ?>
                                            <div style="width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; font-weight: 600; border: 2px solid #e5e7eb;">
                                                <?php echo strtoupper(substr($cv['first_name'], 0, 1) . substr($cv['last_name'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <!-- Name and Title -->
                                    <div>
                                        <h3 class="cv-title">
                                            <span><?php echo htmlspecialchars($cv['first_name'] . ' ' . $cv['last_name']); ?></span>
                                            <?php if ($cv['nin_verified']): ?>
                                                <span class="verified-badge" title="NIN Verified">✓</span>
                                            <?php endif; ?>
                                        </h3>
                                        <?php if ($cv['cv_title']): ?>
                                            <div style="color: #6b7280; font-size: 1rem; margin-bottom: 0.5rem;">
                                                <?php echo htmlspecialchars($cv['cv_title']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div style="text-align: right; color: #6b7280; font-size: 0.85rem;">
                                    Updated <?php echo date('M j, Y', strtotime($cv['updated_at'])); ?>
                                </div>
                            </div>

                            <div class="cv-meta">
                                <?php if ($cv['years_of_experience']): ?>
                                    <div class="cv-meta-item">
                                        <i class="fas fa-briefcase"></i>
                                        <?php echo $cv['years_of_experience']; ?> years experience
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($cv['education_level']): ?>
                                    <div class="cv-meta-item">
                                        <i class="fas fa-graduation-cap"></i>
                                        <?php echo strtoupper($cv['education_level']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($cv['current_city'] || $cv['current_state']): ?>
                                    <div class="cv-meta-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php echo htmlspecialchars(trim($cv['current_city'] . ', ' . $cv['current_state'], ', ')); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if ($cv['bio']): ?>
                                <p style="color: #4b5563; margin-bottom: 1rem; line-height: 1.6;">
                                    <?php echo htmlspecialchars(substr($cv['bio'], 0, 200)) . (strlen($cv['bio']) > 200 ? '...' : ''); ?>
                                </p>
                            <?php endif; ?>

                            <?php if ($cv['skills']): ?>
                                <div class="cv-skills">
                                    <?php 
                                    $skills = array_slice(explode(',', $cv['skills']), 0, 8);
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
                            <?php endif; ?>

                            <div class="cv-actions">
                                <a href="../user/cv-download.php?id=<?php echo $cv['cv_id']; ?>&action=preview" class="btn btn-primary" target="_blank">
                                    <i class="fas fa-eye"></i> View CV
                                </a>
                                <a href="view-seeker-profile.php?id=<?php echo $cv['user_id']; ?>" class="btn btn-outline" target="_blank">
                                    <i class="fas fa-user"></i> View Profile
                                </a>
                                <a href="mailto:<?php echo htmlspecialchars($cv['email']); ?>" class="btn btn-outline">
                                    <i class="fas fa-envelope"></i> Contact
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="page-link">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                                   class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="page-link">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
</body>
</html>
