<?php
require_once '../config/database.php';

// Get slug from URL (supports both ?slug=name and /mini/name formats)
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (preg_match('#/mini/([a-z0-9-]+)#i', $uri, $matches)) {
        $slug = $matches[1];
    }
}

if (empty($slug)) {
    header('Location: /findajob');
    exit;
}

// Fetch employer data
$stmt = $pdo->prepare("
    SELECT ep.*, u.email, u.user_type
    FROM employer_profiles ep
    JOIN users u ON ep.user_id = u.id
    WHERE ep.mini_jobsite_slug = ? AND ep.mini_jobsite_enabled = 1
");
$stmt->execute([$slug]);
$employer = $stmt->fetch();

if (!$employer) {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Mini Jobsite Not Found - FindAJob Nigeria</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            }
            .error-container {
                background: white;
                padding: 4rem 3rem;
                border-radius: 20px;
                text-align: center;
                max-width: 500px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            }
            .error-icon { font-size: 5rem; color: #cbd5e1; margin-bottom: 2rem; }
            h1 { font-size: 2rem; color: #1a202c; margin-bottom: 1rem; }
            p { color: #64748b; margin-bottom: 2rem; line-height: 1.6; }
            .btn {
                display: inline-block;
                background: linear-gradient(135deg, #667eea, #764ba2);
                color: white;
                padding: 1rem 2.5rem;
                border-radius: 10px;
                text-decoration: none;
                font-weight: 600;
                transition: transform 0.3s ease;
            }
            .btn:hover { transform: translateY(-2px); }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon"><i class="fas fa-search"></i></div>
            <h1>Mini Jobsite Not Found</h1>
            <p>The mini jobsite you're looking for doesn't exist or has been disabled.</p>
            <a href="/findajob" class="btn"><i class="fas fa-home"></i> Go to FindAJob Homepage</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Increment view count
$update_views = $pdo->prepare("UPDATE employer_profiles SET mini_jobsite_views = mini_jobsite_views + 1 WHERE mini_jobsite_slug = ?");
$update_views->execute([$slug]);

// Get active jobs
$jobs_stmt = $pdo->prepare("
    SELECT j.*, 
           (SELECT COUNT(*) FROM job_applications WHERE job_id = j.id) as application_count
    FROM jobs j
    WHERE j.employer_id = ? AND j.status = 'active'
    ORDER BY j.created_at DESC
");
$jobs_stmt->execute([$employer['user_id']]);
$jobs = $jobs_stmt->fetchAll();

// Theme colors
$themes = [
    'default' => ['primary' => '#dc2626', 'secondary' => '#b91c1c'],
    'blue' => ['primary' => '#2563eb', 'secondary' => '#1d4ed8'],
    'green' => ['primary' => '#059669', 'secondary' => '#047857'],
    'purple' => ['primary' => '#7c3aed', 'secondary' => '#6d28d9'],
    'orange' => ['primary' => '#ea580c', 'secondary' => '#c2410c'],
    'teal' => ['primary' => '#0d9488', 'secondary' => '#0f766e']
];

$theme = $themes[$employer['mini_jobsite_theme']] ?? $themes['default'];

// Convert hex to RGB for CSS
$hex = str_replace('#', '', $theme['primary']);
$r = hexdec(substr($hex, 0, 2));
$g = hexdec(substr($hex, 2, 2));
$b = hexdec(substr($hex, 4, 2));
$primaryRgb = "$r, $g, $b";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($employer['company_name']); ?> - Careers</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: <?php echo $theme['primary']; ?>;
            --primary-rgb: <?php echo $primaryRgb; ?>;
            --secondary: <?php echo $theme['secondary']; ?>;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #2d3748;
            background: #f8f9fa;
        }
        
        /* Sidebar Layout */
        .page-wrapper {
            display: grid;
            grid-template-columns: 350px 1fr;
            min-height: 100vh;
        }
        
        /* Left Sidebar */
        .sidebar {
            background: linear-gradient(180deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 3rem 2.5rem;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar-logo {
            width: 120px;
            height: 120px;
            margin: 0 auto 2rem;
            border-radius: 20px;
            overflow: hidden;
            background: white;
            padding: 1rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .sidebar-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .sidebar-company-name {
            font-size: 1.8rem;
            font-weight: 800;
            text-align: center;
            margin-bottom: 1rem;
            line-height: 1.2;
        }
        
        .sidebar-tagline {
            text-align: center;
            opacity: 0.9;
            margin-bottom: 2.5rem;
            font-size: 0.95rem;
            line-height: 1.6;
        }
        
        .sidebar-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.2);
            margin: 2rem 0;
        }
        
        .sidebar-section {
            margin-bottom: 2.5rem;
        }
        
        .sidebar-section-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            opacity: 0.7;
            margin-bottom: 1rem;
            font-weight: 700;
        }
        
        .sidebar-info-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1.25rem;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .sidebar-info-item:hover {
            background: rgba(255, 255, 255, 0.15);
        }
        
        .sidebar-info-item i {
            font-size: 1.1rem;
            margin-top: 0.2rem;
            flex-shrink: 0;
        }
        
        .sidebar-info-item span,
        .sidebar-info-item a {
            color: white;
            text-decoration: none;
            font-size: 0.9rem;
            line-height: 1.5;
            word-break: break-word;
        }
        
        .sidebar-info-item a:hover {
            text-decoration: underline;
        }
        
        .sidebar-social {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        
        .sidebar-social-link {
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 1.2rem;
        }
        
        .sidebar-social-link:hover {
            background: white;
            color: var(--primary);
            transform: translateY(-3px);
        }
        
        /* Main Content Area */
        .main-area {
            background: #f8f9fa;
            display: flex;
            flex-direction: column;
        }
        
        /* Top Banner */
        .top-banner {
            background: white;
            padding: 3rem 4rem;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 10;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .banner-content {
            max-width: 1200px;
        }
        
        .banner-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: #1a202c;
            margin-bottom: 0.5rem;
        }
        
        .banner-subtitle {
            color: #64748b;
            font-size: 1.1rem;
        }
        
        /* Content Wrapper */
        .content-wrapper {
            padding: 3rem 4rem;
            max-width: 1200px;
        }
        
        /* Welcome Message Card */
        .welcome-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem;
            border-radius: 20px;
            margin-bottom: 3rem;
            position: relative;
            overflow: hidden;
        }
        
        .welcome-card::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .welcome-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }
        
        .welcome-card p {
            font-size: 1.05rem;
            line-height: 1.8;
            opacity: 0.95;
            position: relative;
            z-index: 1;
        }
        
        /* Jobs Section */
        .jobs-section {
            margin-bottom: 3rem;
        }
        
        .section-title {
            font-size: 2rem;
            font-weight: 800;
            color: #1a202c;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .section-title::before {
            content: '';
            width: 5px;
            height: 40px;
            background: linear-gradient(180deg, var(--primary), var(--secondary));
            border-radius: 3px;
        }
        
        .jobs-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        /* Job Card - Horizontal List Style */
        .job-item {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 2rem;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        
        .job-item:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
            border-left-color: var(--primary);
            transform: translateX(8px);
        }
        
        .job-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1), rgba(var(--primary-rgb), 0.05));
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: var(--primary);
            flex-shrink: 0;
        }
        
        .job-details {
            flex: 1;
        }
        
        .job-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 0.75rem;
            flex-wrap: wrap;
        }
        
        .job-title-text {
            font-size: 1.4rem;
            font-weight: 700;
            color: #1a202c;
            margin: 0;
        }
        
        .job-type-badge {
            background: rgba(var(--primary-rgb), 0.1);
            color: var(--primary);
            padding: 0.35rem 1rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .job-meta-row {
            display: flex;
            gap: 2rem;
            margin-bottom: 0.75rem;
            flex-wrap: wrap;
        }
        
        .job-meta-single {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #64748b;
            font-size: 0.9rem;
        }
        
        .job-meta-single i {
            color: var(--primary);
        }
        
        .job-desc {
            color: #64748b;
            font-size: 0.95rem;
            line-height: 1.6;
            margin-top: 0.75rem;
        }
        
        .job-action {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.75rem;
        }
        
        .job-apply-btn {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 0.85rem 2.5rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.3);
            white-space: nowrap;
        }
        
        .job-apply-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(var(--primary-rgb), 0.4);
        }
        
        .job-posted {
            color: #94a3b8;
            font-size: 0.85rem;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 5rem 2rem;
            background: white;
            border-radius: 20px;
            border: 2px dashed #cbd5e1;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #cbd5e1;
            margin-bottom: 1.5rem;
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            color: #475569;
            margin-bottom: 0.5rem;
        }
        
        .empty-state p {
            color: #94a3b8;
        }
        
        /* Footer */
        .footer {
            background: #1a202c;
            color: white;
            padding: 2rem 4rem;
            text-align: center;
            margin-top: auto;
        }
        
        .footer p {
            margin: 0.5rem 0;
            opacity: 0.8;
        }
        
        .footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
        
        /* Responsive Design */
        @media (max-width: 1024px) {
            .page-wrapper {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                position: relative;
                height: auto;
            }
            
            .top-banner {
                position: relative;
            }
            
            .job-item {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .job-action {
                align-items: stretch;
            }
            
            .job-apply-btn {
                width: 100%;
                text-align: center;
            }
        }
        
        @media (max-width: 768px) {
            .content-wrapper,
            .top-banner,
            .footer {
                padding: 2rem 1.5rem;
            }
            
            .sidebar {
                padding: 2rem 1.5rem;
            }
            
            .banner-title {
                font-size: 1.8rem;
            }
            
            .job-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }
            
            .job-title-text {
                font-size: 1.2rem;
            }
            
            .welcome-card {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <!-- Left Sidebar -->
        <aside class="sidebar">
            <?php if (!empty($employer['company_logo'])): ?>
            <div class="sidebar-logo">
                <img src="/findajob/<?php echo htmlspecialchars($employer['company_logo']); ?>" 
                     alt="<?php echo htmlspecialchars($employer['company_name']); ?>">
            </div>
            <?php else: ?>
            <div class="sidebar-logo">
                <i class="fas fa-building" style="font-size: 3rem; color: var(--primary);"></i>
            </div>
            <?php endif; ?>
            
            <h1 class="sidebar-company-name"><?php echo htmlspecialchars($employer['company_name']); ?></h1>
            
            <?php if (!empty($employer['description'])): ?>
            <p class="sidebar-tagline"><?php echo nl2br(htmlspecialchars($employer['description'])); ?></p>
            <?php endif; ?>
            
            <div class="sidebar-divider"></div>
            
            <div class="sidebar-section">
                <div class="sidebar-section-title">Company Info</div>
                
                <?php if ($employer['industry']): ?>
                <div class="sidebar-info-item">
                    <i class="fas fa-industry"></i>
                    <span><?php echo htmlspecialchars($employer['industry']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($employer['company_size']): ?>
                <div class="sidebar-info-item">
                    <i class="fas fa-users"></i>
                    <span><?php echo htmlspecialchars($employer['company_size']); ?> employees</span>
                </div>
                <?php endif; ?>
                
                <?php if ($employer['city'] || $employer['state']): ?>
                <div class="sidebar-info-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span><?php echo htmlspecialchars(trim($employer['city'] . ', ' . $employer['state'], ', ')); ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($employer['mini_jobsite_show_contact']): ?>
            <div class="sidebar-divider"></div>
            
            <div class="sidebar-section">
                <div class="sidebar-section-title">Contact Us</div>
                
                <?php if ($employer['email']): ?>
                <div class="sidebar-info-item">
                    <i class="fas fa-envelope"></i>
                    <a href="mailto:<?php echo htmlspecialchars($employer['email']); ?>">
                        <?php echo htmlspecialchars($employer['email']); ?>
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if ($employer['website']): ?>
                <div class="sidebar-info-item">
                    <i class="fas fa-globe"></i>
                    <a href="<?php echo htmlspecialchars($employer['website']); ?>" target="_blank">
                        <?php echo htmlspecialchars($employer['website']); ?>
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if ($employer['address']): ?>
                <div class="sidebar-info-item">
                    <i class="fas fa-map-marked-alt"></i>
                    <span><?php echo nl2br(htmlspecialchars($employer['address'])); ?></span>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($employer['mini_jobsite_show_social'] && 
                     ($employer['social_linkedin'] || $employer['social_twitter'] || 
                      $employer['social_facebook'] || $employer['social_instagram'])): ?>
            <div class="sidebar-divider"></div>
            
            <div class="sidebar-section">
                <div class="sidebar-section-title">Follow Us</div>
                <div class="sidebar-social">
                    <?php if ($employer['social_linkedin']): ?>
                    <a href="<?php echo htmlspecialchars($employer['social_linkedin']); ?>" 
                       target="_blank" class="sidebar-social-link" title="LinkedIn">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($employer['social_twitter']): ?>
                    <a href="<?php echo htmlspecialchars($employer['social_twitter']); ?>" 
                       target="_blank" class="sidebar-social-link" title="Twitter">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($employer['social_facebook']): ?>
                    <a href="<?php echo htmlspecialchars($employer['social_facebook']); ?>" 
                       target="_blank" class="sidebar-social-link" title="Facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($employer['social_instagram']): ?>
                    <a href="<?php echo htmlspecialchars($employer['social_instagram']); ?>" 
                       target="_blank" class="sidebar-social-link" title="Instagram">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </aside>
        
        <!-- Main Content -->
        <main class="main-area">
            <div class="top-banner">
                <div class="banner-content">
                    <h2 class="banner-title">Career Opportunities</h2>
                    <p class="banner-subtitle">Explore open positions and join our team</p>
                </div>
            </div>
            
            <div class="content-wrapper">
                <?php if (!empty($employer['mini_jobsite_custom_message'])): ?>
                <div class="welcome-card">
                    <h3><i class="fas fa-quote-left"></i> Welcome Message</h3>
                    <p><?php echo nl2br(htmlspecialchars($employer['mini_jobsite_custom_message'])); ?></p>
                </div>
                <?php endif; ?>
                
                <div class="jobs-section">
                    <h2 class="section-title">Open Positions</h2>
                    
                    <?php if (count($jobs) > 0): ?>
                    <div class="jobs-list">
                        <?php foreach ($jobs as $job): ?>
                        <div class="job-item">
                            <div class="job-icon">
                                <i class="fas fa-briefcase"></i>
                            </div>
                            
                            <div class="job-details">
                                <div class="job-header">
                                    <h3 class="job-title-text"><?php echo htmlspecialchars($job['title']); ?></h3>
                                    <span class="job-type-badge"><?php echo htmlspecialchars($job['job_type']); ?></span>
                                </div>
                                
                                <div class="job-meta-row">
                                    <?php if ($job['city'] || $job['state']): ?>
                                    <div class="job-meta-single">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?php echo htmlspecialchars(trim($job['city'] . ', ' . $job['state'], ', ')); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($job['salary_min'] || $job['salary_max']): ?>
                                    <div class="job-meta-single">
                                        <i class="fas fa-money-bill-wave"></i>
                                        <span>
                                            ₦<?php echo number_format($job['salary_min']); ?> - 
                                            ₦<?php echo number_format($job['salary_max']); ?>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($job['application_count'] > 0): ?>
                                    <div class="job-meta-single">
                                        <i class="fas fa-users"></i>
                                        <span><?php echo $job['application_count']; ?> applicants</span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($job['description']): ?>
                                <div class="job-desc">
                                    <?php echo htmlspecialchars(substr($job['description'], 0, 150)); ?>
                                    <?php echo strlen($job['description']) > 150 ? '...' : ''; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="job-action">
                                <a href="/findajob/pages/jobs/details.php?id=<?php echo $job['id']; ?>" 
                                   class="job-apply-btn">
                                    <i class="fas fa-arrow-right"></i> View & Apply
                                </a>
                                <div class="job-posted">
                                    Posted <?php 
                                        $posted = new DateTime($job['created_at']);
                                        $now = new DateTime();
                                        $diff = $posted->diff($now);
                                        if ($diff->days == 0) echo 'Today';
                                        elseif ($diff->days == 1) echo 'Yesterday';
                                        else echo $diff->days . ' days ago';
                                    ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>No Open Positions</h3>
                        <p>There are currently no job openings. Check back later!</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <footer class="footer">
                <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($employer['company_name']); ?>. All rights reserved.</p>
                <p>Powered by <a href="/findajob">FindAJob Nigeria</a></p>
            </footer>
        </main>
    </div>
</body>
</html>
