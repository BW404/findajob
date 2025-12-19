<?php 
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/maintenance-check.php';
require_once '../../includes/functions.php';

// Get filter parameters
$keywords = $_GET['keywords'] ?? $_GET['q'] ?? '';
$location = $_GET['location'] ?? '';
$category = $_GET['category'] ?? '';
$job_type = $_GET['job_type'] ?? '';
$experience_level = $_GET['experience_level'] ?? '';
$salary_min = $_GET['salary_min'] ?? '';
$salary_max = $_GET['salary_max'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));

// Get job categories for filter
$categoriesStmt = $pdo->prepare("SELECT name, slug, icon FROM job_categories WHERE is_active = TRUE ORDER BY name");
$categoriesStmt->execute();
$categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

// Get popular locations
$locationsStmt = $pdo->prepare("
    SELECT state, COUNT(*) as job_count 
    FROM jobs 
    WHERE status = 'active' AND state IS NOT NULL 
    GROUP BY state 
    ORDER BY job_count DESC 
    LIMIT 10
");
$locationsStmt->execute();
$popular_locations = $locationsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Jobs - FindAJob Nigeria</title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#dc2626">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="FindAJob NG">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="../../manifest.json">
    
    <!-- App Icons -->
    <link rel="icon" type="image/svg+xml" href="../../assets/images/icons/icon-192x192.svg">
    <link rel="apple-touch-icon" href="../../assets/images/icons/icon-192x192.svg">
    
    <link rel="stylesheet" href="../../assets/css/main.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container">
        <main class="main-content">
            <!-- Search Header -->
            <section class="search-header">
                <div class="search-hero">
                    <h1 class="search-title">Find Your Dream Job</h1>
                    <p class="search-subtitle">Discover thousands of opportunities from top employers in Nigeria</p>
                </div>
                
                <!-- Advanced Search Form -->
                <div class="search-form-container">
                    <form class="advanced-search-form" method="GET" id="jobSearchForm">
                        <div class="search-row primary-search">
                            <div class="search-field">
                                <label for="keywords">Job Title or Keywords</label>
                                <div class="input-with-icon">
                                    <input type="text" 
                                           id="keywords"
                                           name="keywords" 
                                           placeholder="e.g. Software Developer, Marketing Manager..." 
                                           class="search-input"
                                           value="<?php echo htmlspecialchars($keywords); ?>">
                                    <span class="input-icon">💼</span>
                                </div>
                            </div>
                            
                            <div class="search-field">
                                <label for="location">Location</label>
                                <div class="input-with-icon">
                                    <input type="text" 
                                           id="location"
                                           name="location" 
                                           placeholder="e.g. Lagos, Abuja, Port Harcourt..." 
                                           class="search-input"
                                           value="<?php echo htmlspecialchars($location); ?>">
                                    <span class="input-icon">📍</span>
                                </div>
                            </div>
                            
                            <button type="submit" class="search-btn primary">
                                🔍 Search Jobs
                            </button>
                        </div>
                        
                        <!-- Advanced Filters -->
                        <div class="search-row filters-row">
                            <div class="filter-group">
                                <select name="category" class="filter-select">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['slug']; ?>" 
                                                <?php echo $category === $cat['slug'] ? 'selected' : ''; ?>>
                                            <?php echo $cat['icon'] . ' ' . $cat['name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <select name="job_type" class="filter-select">
                                    <option value="">All Job Types</option>
                                    <option value="permanent" <?php echo $job_type === 'permanent' ? 'selected' : ''; ?>>Permanent</option>
                                    <option value="contract" <?php echo $job_type === 'contract' ? 'selected' : ''; ?>>Contract</option>
                                    <option value="temporary" <?php echo $job_type === 'temporary' ? 'selected' : ''; ?>>Temporary</option>
                                    <option value="internship" <?php echo $job_type === 'internship' ? 'selected' : ''; ?>>Internship</option>
                                    <option value="nysc" <?php echo $job_type === 'nysc' ? 'selected' : ''; ?>>NYSC</option>
                                    <option value="part_time" <?php echo $job_type === 'part_time' ? 'selected' : ''; ?>>Part Time</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <select name="experience_level" class="filter-select">
                                    <option value="">All Experience Levels</option>
                                    <option value="entry" <?php echo $experience_level === 'entry' ? 'selected' : ''; ?>>Entry Level</option>
                                    <option value="mid" <?php echo $experience_level === 'mid' ? 'selected' : ''; ?>>Mid Level</option>
                                    <option value="senior" <?php echo $experience_level === 'senior' ? 'selected' : ''; ?>>Senior Level</option>
                                    <option value="executive" <?php echo $experience_level === 'executive' ? 'selected' : ''; ?>>Executive</option>
                                </select>
                            </div>
                            
                            <div class="salary-container">
                                <div class="salary-field">
                                    <label>Min Salary</label>
                                    <input type="number" 
                                           name="salary_min" 
                                           placeholder="₦ 50,000"
                                           class="filter-select"
                                           value="<?php echo htmlspecialchars($salary_min); ?>">
                                </div>
                                <div class="salary-field">
                                    <label>Max Salary</label>
                                    <input type="number" 
                                           name="salary_max" 
                                           placeholder="₦ 500,000"
                                           class="filter-select"
                                           value="<?php echo htmlspecialchars($salary_max); ?>">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </section>

            <!-- Results Section -->
            <section class="results-section">
                <div class="results-header">
                    <div class="results-info">
                        <h2 id="resultsCount">Loading jobs...</h2>
                        <div class="active-filters" id="activeFilters"></div>
                    </div>
                    
                    <div class="results-controls">
                        <div class="sort-options">
                            <select id="sortBy" class="sort-select">
                                <option value="newest">Newest First</option>
                                <option value="oldest">Oldest First</option>
                                <option value="salary_high">Salary: High to Low</option>
                                <option value="salary_low">Salary: Low to High</option>
                                <option value="featured">Featured Jobs</option>
                            </select>
                        </div>
                        
                        <div class="view-toggle">
                            <button class="view-btn active" data-view="list">📋</button>
                            <button class="view-btn" data-view="grid">⊞</button>
                        </div>
                    </div>
                </div>
                
                <!-- Job Listings -->
                <div class="jobs-container" id="jobsContainer">
                    <div class="loading-spinner" id="loadingSpinner">
                        <div class="spinner"></div>
                        <p>Loading jobs...</p>
                    </div>
                </div>
                
                <!-- Pagination -->
                <div class="pagination-container" id="paginationContainer">
                    <!-- Pagination will be inserted here -->
                </div>
            </section>
        </main>
        
        <!-- Quick Filters Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-section">
                <h3>Popular Categories</h3>
                <div class="category-list">
                    <?php foreach (array_slice($categories, 0, 8) as $cat): ?>
                        <a href="?category=<?php echo $cat['slug']; ?>" class="category-item">
                            <span class="category-icon"><?php echo $cat['icon']; ?></span>
                            <span class="category-name"><?php echo $cat['name']; ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="sidebar-section">
                <h3>Popular Locations</h3>
                <div class="location-list">
                    <?php foreach ($popular_locations as $loc): ?>
                        <a href="?location=<?php echo urlencode($loc['state']); ?>" class="location-item">
                            📍 <?php echo $loc['state']; ?>
                            <span class="job-count">(<?php echo $loc['job_count']; ?>)</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="sidebar-section">
                <h3>Quick Tips</h3>
                <div class="tips-list">
                    <div class="tip-item">
                        💡 Use specific keywords for better results
                    </div>
                    <div class="tip-item">
                        🎯 Set up job alerts to get notified of new opportunities
                    </div>
                    <div class="tip-item">
                        📝 Keep your profile updated for better matches
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <!-- Job Search Styles -->
    <style>
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 1rem;
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 2.5rem;
        }

        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        .main-content {
            min-width: 0;
        }

        /* Professional Search Header */
        .search-header {
            background: linear-gradient(135deg, var(--primary) 0%, #991b1b 100%);
            color: white;
            padding: 4rem 2rem;
            border-radius: 20px;
            margin-bottom: 2.5rem;
            box-shadow: 0 20px 40px rgba(220, 38, 38, 0.15);
            position: relative;
            overflow: hidden;
        }

        .search-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .search-hero {
            text-align: center;
            margin-bottom: 2.5rem;
            position: relative;
            z-index: 1;
        }

        .search-title {
            font-size: 3rem;
            font-weight: 900;
            margin: 0 0 1rem;
            text-shadow: 2px 4px 8px rgba(0, 0, 0, 0.3);
            letter-spacing: -0.02em;
        }

        .search-subtitle {
            font-size: 1.25rem;
            opacity: 0.95;
            margin: 0;
            font-weight: 400;
            text-shadow: 1px 2px 4px rgba(0, 0, 0, 0.2);
        }

        /* Professional Search Form */
        .search-form-container {
            background: white;
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 15px 50px rgba(0,0,0,0.12);
            border: 1px solid rgba(255, 255, 255, 0.8);
            position: relative;
            z-index: 2;
        }

        .advanced-search-form .search-row {
            display: grid;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .primary-search {
            grid-template-columns: 1fr 1fr auto;
            align-items: end;
        }

        .filters-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }

        @media (max-width: 900px) {
            .filters-row {
                grid-template-columns: 1fr 1fr;
            }
            
            .salary-container {
                grid-column: span 2;
            }
        }

        @media (max-width: 600px) {
            .filters-row {
                grid-template-columns: 1fr;
            }
            
            .salary-container {
                grid-column: span 1;
            }
        }

        .search-field {
            display: flex;
            flex-direction: column;
        }

        .search-field label {
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: var(--text-primary);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-with-icon {
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 1.2rem 3.5rem 1.2rem 1.2rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            background: #fafafa;
        }

        .search-input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.1);
            background: white;
            transform: translateY(-1px);
        }

        .search-input::placeholder {
            color: #9ca3af;
            font-weight: 400;
        }

        .input-icon {
            position: absolute;
            right: 1.2rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.3rem;
            color: #6b7280;
            transition: color 0.3s ease;
        }

        .search-input:focus + .input-icon {
            color: var(--primary);
        }

        .search-btn.primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            padding: 1.2rem 2.5rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            height: fit-content;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 8px 25px rgba(220, 38, 38, 0.3);
            position: relative;
            overflow: hidden;
        }

        .search-btn.primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .search-btn.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(220, 38, 38, 0.4);
        }

        .search-btn.primary:hover::before {
            left: 100%;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            width: 100%;
            box-sizing: border-box;
        }

        .filter-select {
            padding: 1rem 1.2rem;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            background: #fafafa;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.3s ease;
            box-sizing: border-box;
            width: 100%;
        }

        .filter-select:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
            background: white;
        }

        .salary-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .salary-field {
            display: flex;
            flex-direction: column;
        }

        .salary-field label {
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: var(--text-primary);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Professional Results Section */
        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1.5rem 0;
            border-bottom: 2px solid #f1f5f9;
        }

        #resultsCount {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--text-primary);
            margin: 0 0 0.5rem;
        }

        .active-filters {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .filter-tag {
            background: var(--primary-light);
            color: var(--primary);
            padding: 0.4rem 1rem;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid rgba(220, 38, 38, 0.2);
            transition: all 0.3s ease;
        }

        .filter-tag:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2);
        }

        .results-controls {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .sort-select {
            padding: 0.75rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            background: white;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .sort-select:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
        }

        .view-toggle {
            display: flex;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
            background: white;
        }

        .view-btn {
            padding: 0.75rem 1rem;
            border: none;
            background: transparent;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            color: var(--text-secondary);
        }

        .view-btn.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.3);
        }

        .view-btn:hover:not(.active) {
            background: var(--primary-light);
            color: var(--primary);
        }

        /* Loading Spinner */
        .loading-spinner {
            text-align: center;
            padding: 3rem;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #e5e7eb;
            border-top: 4px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Job Cards */
        .jobs-container {
            min-height: 400px;
        }

        .job-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            border: 1px solid #f1f5f9;
            position: relative;
            overflow: hidden;
        }

        .job-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.12);
            border-color: rgba(220, 38, 38, 0.1);
        }

        .job-card:hover::before {
            transform: scaleX(1);
        }

        .job-card-header {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .company-logo {
            width: 70px;
            height: 70px;
            border-radius: 14px;
            overflow: hidden;
            flex-shrink: 0;
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
            border: 2px solid rgba(255, 255, 255, 0.8);
        }

        .company-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .job-actions {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .save-job-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.5rem;
            padding: 0.5rem;
            transition: transform 0.2s ease;
            line-height: 1;
        }

        .save-job-btn:hover {
            transform: scale(1.2);
        }

        .save-job-btn.saved .save-icon {
            animation: heartbeat 0.3s ease;
        }

        .report-job-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.2rem;
            padding: 0.5rem;
            transition: all 0.2s ease;
            line-height: 1;
            color: #dc2626;
            opacity: 0.8;
        }

        .report-job-btn:hover {
            opacity: 1;
            transform: scale(1.15);
            color: #b91c1c;
        }

        @keyframes heartbeat {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.3); }
        }

        .job-info {
            flex: 1;
            min-width: 0;
        }

        .job-title {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--text-primary);
            margin: 0 0 0.5rem;
            line-height: 1.3;
        }

        .job-title:hover {
            color: var(--primary);
        }

        .company-name {
            color: var(--text-secondary);
            margin: 0 0 0.75rem;
            font-weight: 600;
            font-size: 1.05rem;
        }

        .job-location {
            color: var(--text-secondary);
            margin: 0;
            font-size: 0.9rem;
        }

        .job-badges {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }

        .job-badge {
            background: var(--primary-light);
            color: var(--primary);
            padding: 0.4rem 1rem;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid rgba(220, 38, 38, 0.2);
            transition: all 0.3s ease;
        }

        .job-badge:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2);
        }

        .job-badge.featured {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            border-color: #f59e0b;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
        }

        .job-badge.urgent {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border-color: #ef4444;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .job-description {
            color: var(--text-secondary);
            line-height: 1.6;
            margin: 0 0 1.5rem;
        }

        .job-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1.5rem;
            margin-top: 1.5rem;
            border-top: 1px solid #e5e7eb;
            gap: 1rem;
        }

        .job-meta {
            display: flex;
            gap: 1.5rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
            flex: 1;
        }

        .job-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .apply-btn {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            padding: 0.875rem 2rem;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.25);
            white-space: nowrap;
            flex-shrink: 0;
        }

        .apply-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 38, 38, 0.35);
        }

        /* Professional Sidebar */
        .sidebar {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            height: fit-content;
            position: sticky;
            top: 2rem;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            border: 1px solid #f1f5f9;
        }

        .sidebar-section {
            margin-bottom: 2.5rem;
        }

        .sidebar-section:last-child {
            margin-bottom: 0;
        }

        .sidebar-section h3 {
            font-size: 1.2rem;
            font-weight: 800;
            margin: 0 0 1.5rem;
            color: var(--text-primary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--primary-light);
        }

        .category-list, .location-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .category-item, .location-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            border-radius: 8px;
            text-decoration: none;
            color: var(--text-primary);
            transition: background 0.2s ease;
        }

        .category-item:hover, .location-item:hover {
            background: var(--background);
        }

        .job-count {
            margin-left: auto;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .tips-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .tip-item {
            padding: 0.75rem;
            background: var(--background);
            border-radius: 8px;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        /* Pagination */
        .pagination-container {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
        }

        .pagination {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .page-btn {
            padding: 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            background: white;
            color: var(--text-primary);
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .page-btn:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .page-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .page-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
                padding: 1rem 0.5rem;
            }

            .sidebar {
                order: -1;
                position: static;
            }

            .search-header {
                padding: 2rem 1rem;
            }

            .search-title {
                font-size: 1.8rem;
            }

            .primary-search {
                grid-template-columns: 1fr;
            }

            .filters-row {
                grid-template-columns: 1fr;
            }

            .results-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .salary-container {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .job-footer {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
                padding-top: 1rem;
                margin-top: 1rem;
            }
            
            .job-meta {
                justify-content: center;
                flex-wrap: wrap;
            }
            
            .apply-btn {
                width: 100%;
                justify-self: stretch;
            }
        }
        
        /* Autocomplete Dropdown Styles */
        .input-with-icon {
            position: relative;
        }
        
        .autocomplete-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-top: none;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            margin-top: -8px;
        }
        
        .autocomplete-item {
            padding: 0.75rem 1rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #f3f4f6;
            transition: all 0.2s ease;
        }
        
        .autocomplete-item:last-child {
            border-bottom: none;
        }
        
        .autocomplete-item:hover {
            background-color: #fef2f2;
            padding-left: 1.25rem;
        }
        
        .suggestion-text {
            font-size: 0.9rem;
            color: #1f2937;
            font-weight: 500;
        }
        
        .suggestion-meta {
            font-size: 0.8rem;
            color: #6b7280;
            background: #f3f4f6;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
        }
        
        .autocomplete-dropdown::-webkit-scrollbar {
            width: 6px;
        }
        
        .autocomplete-dropdown::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 0 0 8px 0;
        }
        
        .autocomplete-dropdown::-webkit-scrollbar-thumb {
            background: #dc2626;
            border-radius: 3px;
        }
        
        .autocomplete-dropdown::-webkit-scrollbar-thumb:hover {
            background: #b91c1c;
        }
    </style>

    <!-- Job Search JavaScript -->
    <script src="../../assets/js/job-search.js"></script>
    <script>
        // Initialize job search
        document.addEventListener('DOMContentLoaded', function() {
            const jobSearch = new JobSearch();
            jobSearch.init();
        });
    </script>

    <!-- Bottom Navigation for Mobile -->
    <nav class="app-bottom-nav">
        <a href="../../index.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">🏠</div>
            <div class="app-bottom-nav-label">Home</div>
        </a>
        <a href="browse.php" class="app-bottom-nav-item active">
            <div class="app-bottom-nav-icon">🔍</div>
            <div class="app-bottom-nav-label">Jobs</div>
        </a>
        <a href="../user/saved-jobs.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">❤️</div>
            <div class="app-bottom-nav-label">Saved</div>
        </a>
        <a href="../user/applications.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">�</div>
            <div class="app-bottom-nav-label">Applications</div>
        </a>
        <a href="../user/dashboard.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">👤</div>
            <div class="app-bottom-nav-label">Profile</div>
        </a>
    </nav>

    <!-- PWA Scripts -->
    <script src="../../assets/js/pwa.js"></script>
    <script>
        // Initialize PWA features
        if ('PWAManager' in window) {
            const pwa = new PWAManager();
            pwa.init();
        }

        // Add body class for bottom nav
        document.body.classList.add('has-bottom-nav');

        // Simple job search functionality
        document.querySelector('.search-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const keywords = document.querySelector('input[name="keywords"]').value;
            const location = document.querySelector('select[name="location"]').value;
            const category = document.querySelector('select[name="category"]').value;
            
            // In a real app, this would make an API call
            console.log('Searching for:', { keywords, location, category });
            
            // Show loading state
            const searchBtn = document.querySelector('.search-btn');
            searchBtn.innerHTML = '<span class="search-icon">⏳</span>';
            
            // Simulate search delay
            setTimeout(() => {
                searchBtn.innerHTML = '<span class="search-icon">🔍</span>';
                // Update results would happen here
            }, 1000);
        });

        // Apply button handlers
        document.querySelectorAll('.btn-apply').forEach(button => {
            button.addEventListener('click', function() {
                // In a real app, this would open application form
                alert('Application form would open here');
            });
        });
    </script>

    <!-- Report Modal -->
    <?php include '../../includes/report-modal.php'; ?>

</body>
</html>