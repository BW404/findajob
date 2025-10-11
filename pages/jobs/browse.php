<?php require_once '../../includes/functions.php'; ?>
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
    <div class="container">
        <header class="header">
            <div class="header-content">
                <a href="../../index.php" class="logo">
                    <img src="../../assets/images/logo.png" alt="FindAJob Nigeria" class="logo-img">
                </a>
                <h1 class="page-title">Browse Jobs</h1>
            </div>
        </header>

        <main class="main-content">
            <!-- Search Filters -->
            <section class="search-section">
                <div class="search-container">
                    <div class="search-form-wrapper">
                        <form class="search-form" method="GET">
                            <div class="search-input-group">
                                <input type="text" 
                                       name="keywords" 
                                       placeholder="Job title, company, or keywords..." 
                                       class="search-input"
                                       value="<?php echo htmlspecialchars($_GET['keywords'] ?? ''); ?>">
                                <button type="submit" class="search-btn">
                                    <span class="search-icon">🔍</span>
                                </button>
                            </div>
                            
                            <div class="filters-row">
                                <select name="location" class="filter-select">
                                    <option value="">All Locations</option>
                                    <option value="lagos">Lagos</option>
                                    <option value="abuja">Abuja</option>
                                    <option value="port-harcourt">Port Harcourt</option>
                                    <option value="kano">Kano</option>
                                    <option value="ibadan">Ibadan</option>
                                </select>
                                
                                <select name="category" class="filter-select">
                                    <option value="">All Categories</option>
                                    <option value="technology">Technology</option>
                                    <option value="banking">Banking & Finance</option>
                                    <option value="oil-gas">Oil & Gas</option>
                                    <option value="healthcare">Healthcare</option>
                                    <option value="education">Education</option>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>
            </section>

            <!-- Job Listings -->
            <section class="jobs-section">
                <div class="section-header">
                    <h2>Available Jobs</h2>
                    <div class="results-info">
                        <span class="results-count">254 jobs found</span>
                    </div>
                </div>

                <div class="jobs-grid">
                    <!-- Sample Job Cards -->
                    <div class="job-card">
                        <div class="job-card-header">
                            <div class="company-logo">
                                <img src="../../assets/images/placeholders/company.png" alt="Company">
                            </div>
                            <div class="job-info">
                                <h3 class="job-title">Software Developer</h3>
                                <p class="company-name">TechCorp Nigeria</p>
                                <p class="job-location">📍 Lagos, Nigeria</p>
                            </div>
                        </div>
                        <div class="job-card-body">
                            <div class="job-tags">
                                <span class="job-tag">Full-time</span>
                                <span class="job-tag">₦300k - ₦500k</span>
                                <span class="job-tag">Technology</span>
                            </div>
                            <p class="job-description">
                                We are looking for a skilled software developer to join our growing team...
                            </p>
                        </div>
                        <div class="job-card-footer">
                            <span class="job-posted">Posted 2 days ago</span>
                            <button class="btn-apply">Apply Now</button>
                        </div>
                    </div>

                    <div class="job-card">
                        <div class="job-card-header">
                            <div class="company-logo">
                                <img src="../../assets/images/placeholders/company.png" alt="Company">
                            </div>
                            <div class="job-info">
                                <h3 class="job-title">Marketing Manager</h3>
                                <p class="company-name">GrowthHub Ltd</p>
                                <p class="job-location">📍 Abuja, Nigeria</p>
                            </div>
                        </div>
                        <div class="job-card-body">
                            <div class="job-tags">
                                <span class="job-tag">Full-time</span>
                                <span class="job-tag">₦250k - ₦400k</span>
                                <span class="job-tag">Marketing</span>
                            </div>
                            <p class="job-description">
                                Join our marketing team and help drive growth for leading brands...
                            </p>
                        </div>
                        <div class="job-card-footer">
                            <span class="job-posted">Posted 1 week ago</span>
                            <button class="btn-apply">Apply Now</button>
                        </div>
                    </div>

                    <div class="job-card">
                        <div class="job-card-header">
                            <div class="company-logo">
                                <img src="../../assets/images/placeholders/company.png" alt="Company">
                            </div>
                            <div class="job-info">
                                <h3 class="job-title">Data Analyst</h3>
                                <p class="company-name">DataCorp Solutions</p>
                                <p class="job-location">📍 Lagos, Nigeria</p>
                            </div>
                        </div>
                        <div class="job-card-body">
                            <div class="job-tags">
                                <span class="job-tag">Contract</span>
                                <span class="job-tag">₦200k - ₦350k</span>
                                <span class="job-tag">Data</span>
                            </div>
                            <p class="job-description">
                                Analyze complex datasets and provide insights to drive business decisions...
                            </p>
                        </div>
                        <div class="job-card-footer">
                            <span class="job-posted">Posted 3 days ago</span>
                            <button class="btn-apply">Apply Now</button>
                        </div>
                    </div>
                </div>

                <!-- Load More Button -->
                <div class="load-more-section">
                    <button class="btn-load-more">Load More Jobs</button>
                </div>
            </section>
        </main>
    </div>

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
        <a href="../services/cv-creator.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">📄</div>
            <div class="app-bottom-nav-label">CV</div>
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
</body>
</html>