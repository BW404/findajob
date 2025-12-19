<?php
require_once '../config/database.php';

// Get slug from URL (supports both /mini/slug and /mini/index.php?slug=slug)
$slug = $_GET['slug'] ?? '';

// If no slug in query string, try to get it from PATH_INFO or REQUEST_URI
if (empty($slug)) {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    // Extract slug from /findajob/mini/slug-name format
    if (preg_match('#/mini/([a-z0-9-]+)#i', $uri, $matches)) {
        $slug = $matches[1];
    }
}

if (empty($slug) || $slug === 'index.php') {
    // Redirect to main site if no valid slug
    header('Location: /findajob');
    exit;
}

// Get employer data by slug
$stmt = $pdo->prepare("
    SELECT u.id as employer_id, u.email, u.created_at as member_since,
           ep.company_name, ep.description, ep.website, ep.industry, ep.company_size,
           ep.state, ep.city, ep.address, ep.company_logo,
           ep.mini_jobsite_enabled, ep.mini_jobsite_slug, ep.mini_jobsite_theme,
           ep.mini_jobsite_custom_message, ep.mini_jobsite_show_contact, ep.mini_jobsite_show_social,
           ep.social_linkedin, ep.social_twitter, ep.social_facebook, ep.social_instagram,
           ep.mini_jobsite_views
    FROM employer_profiles ep
    INNER JOIN users u ON ep.user_id = u.id
    WHERE ep.mini_jobsite_slug = ? AND ep.mini_jobsite_enabled = 1
");
$stmt->execute([$slug]);
$employer = $stmt->fetch();

if (!$employer) {
    header('HTTP/1.0 404 Not Found');
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Mini Jobsite Not Found</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: #f9fafb;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                margin: 0;
                padding: 1rem;
            }
            .error-container {
                text-align: center;
                max-width: 500px;
            }
            .error-icon {
                font-size: 5rem;
                color: #dc2626;
                margin-bottom: 1.5rem;
            }
            h1 {
                font-size: 2rem;
                color: #1f2937;
                margin-bottom: 1rem;
            }
            p {
                font-size: 1.1rem;
                color: #6b7280;
                margin-bottom: 2rem;
            }
            .btn {
                display: inline-block;
                background: #dc2626;
                color: white;
                padding: 0.75rem 2rem;
                border-radius: 8px;
                text-decoration: none;
                font-weight: 600;
                transition: background 0.3s;
            }
            .btn:hover {
                background: #b91c1c;
            }
            .slug-info {
                background: white;
                padding: 1rem;
                border-radius: 8px;
                margin-bottom: 2rem;
                font-family: monospace;
                color: #dc2626;
                border: 2px dashed #fca5a5;
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">
                <i class="fas fa-search"></i>
            </div>
            <h1>Mini Jobsite Not Found</h1>
            <div class="slug-info">
                Slug: <?php echo htmlspecialchars($slug); ?>
            </div>
            <p>The mini jobsite you're looking for doesn't exist or has been disabled.</p>
            <a href="/findajob" class="btn">
                <i class="fas fa-home"></i> Go to FindAJob Homepage
            </a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Increment view count
$update_views = $pdo->prepare("UPDATE employer_profiles SET mini_jobsite_views = mini_jobsite_views + 1 WHERE mini_jobsite_slug = ?");
$update_views->execute([$slug]);

// Get active jobs for this employer
$jobs_stmt = $pdo->prepare("
    SELECT j.*, 
           (SELECT COUNT(*) FROM job_applications WHERE job_id = j.id) as application_count
    FROM jobs j
    WHERE j.employer_id = ? AND j.status = 'active'
    ORDER BY j.created_at DESC
");
$jobs_stmt->execute([$employer['employer_id']]);
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($employer['company_name']); ?> - Careers</title>
    <meta name="description" content="<?php echo htmlspecialchars($employer['description'] ?? 'Join our team! View open positions at ' . $employer['company_name']); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', sans-serif;
            line-height: 1.6;
            color: #1f2937;
            background: #f8fafc;
            overflow-x: hidden;
        }
        
        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }
        
        /* Header Section */
        .header {
            background: linear-gradient(135deg, <?php echo $theme['primary']; ?> 0%, <?php echo $theme['secondary']; ?> 100%);
            color: white;
            padding: 4rem 0 5rem;
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 500px;
            height: 500px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            animation: float 20s infinite ease-in-out;
        }
        
        .header::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -5%;
            width: 400px;
            height: 400px;
            background: rgba(255,255,255,0.08);
            border-radius: 50%;
            animation: float 15s infinite ease-in-out reverse;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(180deg); }
        }
        
        .header-content {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 2rem;
            align-items: center;
        }
        
        .company-logo {
            width: 140px;
            height: 140px;
            background: white;
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 20px 50px rgba(0,0,0,0.25);
            overflow: hidden;
            border: 6px solid rgba(255,255,255,0.2);
        }
        
        .company-logo img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .company-header-info {
            flex: 1;
        }
        
        .company-name {
            font-size: 3rem;
            font-weight: 900;
            margin-bottom: 1rem;
            text-shadow: 0 2px 10px rgba(0,0,0,0.1);
            letter-spacing: -0.5px;
        }
        
        .company-tagline {
            font-size: 1.25rem;
            opacity: 0.95;
            font-weight: 400;
            margin-bottom: 1.5rem;
            max-width: 700px;
        }
        
        .company-info {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            font-size: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .company-info-item {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            background: rgba(255,255,255,0.15);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            backdrop-filter: blur(10px);
        }
        
        .company-info-item i {
            font-size: 1.1rem;
        }
        
        .social-links {
            display: flex;
            gap: 0.75rem;
            margin-top: 2rem;
        }
        
        .social-link {
            width: 50px;
            height: 50px;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            font-size: 1.3rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid rgba(255,255,255,0.2);
        }
        
        .social-link:hover {
            background: white;
            color: <?php echo $theme['primary']; ?>;
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        /* Main Content */
        .main-content {
            padding: 3rem 0;
            margin-top: -2rem;
            position: relative;
            z-index: 2;
        }
        
        .custom-message {
            background: white;
            padding: 2.5rem;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            margin-bottom: 4rem;
            border-left: 5px solid <?php echo $theme['primary']; ?>;
            position: relative;
        }
        
        .custom-message::before {
            content: '\f10d';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            top: 2rem;
            right: 2rem;
            font-size: 3rem;
            color: <?php echo $theme['primary']; ?>;
            opacity: 0.1;
        }
        
        .custom-message-content {
            position: relative;
            z-index: 1;
            font-size: 1.1rem;
            line-height: 1.8;
            color: #374151;
        }
        
        .section-header {
            margin-bottom: 3rem;
            text-align: center;
        }
        
        .section-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.75rem;
            color: #111827;
            display: inline-flex;
            align-items: center;
            gap: 1rem;
        }
        
        .section-subtitle {
            font-size: 1.1rem;
            color: #6b7280;
            font-weight: 400;
        }
        
        .jobs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 2rem;
        }
        
        .job-card {
            background: white;
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid transparent;
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
            background: linear-gradient(90deg, <?php echo $theme['primary']; ?>, <?php echo $theme['secondary']; ?>);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .job-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 60px rgba(0,0,0,0.12);
            border-color: <?php echo $theme['primary']; ?>30;
        }
        
        .job-card:hover::before {
            transform: scaleX(1);
        }
        
        .job-badge {
            display: inline-block;
            background: <?php echo $theme['primary']; ?>15;
            color: <?php echo $theme['primary']; ?>;
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 1rem;
        }
        
        .job-title {
            font-size: 1.6rem;
            font-weight: 800;
            color: #111827;
            margin-bottom: 1.25rem;
            line-height: 1.3;
        }
        
        .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }
        
        .job-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
            color: #6b7280;
        }
        
        .job-meta-item i {
            color: <?php echo $theme['primary']; ?>;
            font-size: 1rem;
        }
        
        .job-description {
            color: #4b5563;
            line-height: 1.7;
            margin-bottom: 2rem;
            font-size: 0.95rem;
        }
        
        .job-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1.5rem;
            border-top: 1px solid #e5e7eb;
        }
        
        .job-date {
            font-size: 0.85rem;
            color: #9ca3af;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-apply {
            background: linear-gradient(135deg, <?php echo $theme['primary']; ?>, <?php echo $theme['secondary']; ?>);
            color: white;
            padding: 0.9rem 2rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            box-shadow: 0 4px 15px <?php echo $theme['primary']; ?>40;
            font-size: 0.95rem;
        }
        
        .btn-apply:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px <?php echo $theme['primary']; ?>50;
        }
        
        .btn-apply i {
            transition: transform 0.3s;
        }
        
        .btn-apply:hover i {
            transform: translateX(3px);
        }
        
        .contact-section {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            margin-top: 4rem;
            border: 1px solid #e5e7eb;
        }
        
        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .contact-item {
            display: flex;
            align-items: flex-start;
            gap: 1.25rem;
            padding: 1.5rem;
            background: white;
            border-radius: 12px;
            transition: all 0.3s;
        }
        
        .contact-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .contact-icon {
            width: 55px;
            height: 55px;
            background: linear-gradient(135deg, <?php echo $theme['primary']; ?>20, <?php echo $theme['primary']; ?>10);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: <?php echo $theme['primary']; ?>;
            font-size: 1.4rem;
            flex-shrink: 0;
        }
        
        .contact-info {
            flex: 1;
        }
        
        .contact-label {
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #111827;
            font-size: 1rem;
        }
        
        .contact-value {
            color: #6b7280;
            font-size: 0.95rem;
        }
        
        .contact-value a {
            color: <?php echo $theme['primary']; ?>;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        
        .contact-value a:hover {
            color: <?php echo $theme['secondary']; ?>;
            text-decoration: underline;
        }
        
        .no-jobs {
            text-align: center;
            padding: 5rem 2rem;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
        }
        
        .no-jobs-icon {
            width: 120px;
            height: 120px;
            background: <?php echo $theme['primary']; ?>10;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
        }
        
        .no-jobs i {
            font-size: 3.5rem;
            color: <?php echo $theme['primary']; ?>;
            opacity: 0.5;
        }
        
        .no-jobs h3 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: #111827;
        }
        
        .no-jobs p {
            color: #6b7280;
            font-size: 1.1rem;
        }
        
        .footer {
            background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
            color: white;
            padding: 3rem 0;
            text-align: center;
            margin-top: 5rem;
            border-top: 4px solid <?php echo $theme['primary']; ?>;
        }
        
        .footer-content {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .footer p {
            margin: 0;
            opacity: 0.9;
        }
        
        .footer a {
            color: <?php echo $theme['primary']; ?>;
            text-decoration: none;
            font-weight: 700;
            transition: color 0.3s;
        }
        
        .footer a:hover {
            color: white;
        }
        
        /* Responsive Design */
        @media (max-width: 968px) {
            .header-content {
                grid-template-columns: 1fr;
                text-align: center;
                justify-items: center;
            }
            
            .company-name {
                font-size: 2.25rem;
            }
            
            .company-info {
                justify-content: center;
            }
            
            .social-links {
                justify-content: center;
            }
            
            .jobs-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 640px) {
            .company-name {
                font-size: 1.75rem;
            }
            
            .section-title {
                font-size: 2rem;
            }
            
            .job-card {
                padding: 1.75rem;
            }
            
            .contact-section {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="container">
            <div class="header-content">
                <?php if (!empty($employer['company_logo'])): ?>
                <div class="company-logo">
                    <img src="/findajob/<?php echo htmlspecialchars($employer['company_logo']); ?>" 
                         alt="<?php echo htmlspecialchars($employer['company_name']); ?>">
                </div>
                <?php else: ?>
                <div class="company-logo">
                    <i class="fas fa-building" style="font-size: 3.5rem; color: <?php echo $theme['primary']; ?>;"></i>
                </div>
                <?php endif; ?>
                
                <div class="company-header-info">
                    <h1 class="company-name"><?php echo htmlspecialchars($employer['company_name']); ?></h1>
                    
                    <?php if (!empty($employer['description'])): ?>
                    <p class="company-tagline">
                        <?php echo nl2br(htmlspecialchars($employer['description'])); ?>
                    </p>
                    <?php endif; ?>
                    
                    <div class="company-info">
                        <?php if ($employer['industry']): ?>
                        <div class="company-info-item">
                            <i class="fas fa-industry"></i>
                            <span><?php echo htmlspecialchars($employer['industry']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($employer['company_size']): ?>
                        <div class="company-info-item">
                            <i class="fas fa-users"></i>
                            <span><?php echo htmlspecialchars($employer['company_size']); ?> employees</span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($employer['city'] || $employer['state']): ?>
                        <div class="company-info-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>
                                <?php 
                                echo htmlspecialchars(trim($employer['city'] . ', ' . $employer['state'], ', ')); 
                                ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($employer['mini_jobsite_show_social'] && 
                             ($employer['social_linkedin'] || $employer['social_twitter'] || 
                              $employer['social_facebook'] || $employer['social_instagram'])): ?>
                    <div class="social-links">
                        <?php if ($employer['social_linkedin']): ?>
                        <a href="<?php echo htmlspecialchars($employer['social_linkedin']); ?>" 
                           target="_blank" class="social-link" title="LinkedIn">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($employer['social_twitter']): ?>
                        <a href="<?php echo htmlspecialchars($employer['social_twitter']); ?>" 
                           target="_blank" class="social-link" title="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($employer['social_facebook']): ?>
                        <a href="<?php echo htmlspecialchars($employer['social_facebook']); ?>" 
                           target="_blank" class="social-link" title="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($employer['social_instagram']): ?>
                        <a href="<?php echo htmlspecialchars($employer['social_instagram']); ?>" 
                           target="_blank" class="social-link" title="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <!-- Custom Message -->
            <?php if (!empty($employer['mini_jobsite_custom_message'])): ?>
            <div class="custom-message">
                <div style="display: flex; align-items: flex-start; gap: 1rem;">
                    <div style="font-size: 2rem; color: <?php echo $theme['primary']; ?>;">
                        <i class="fas fa-quote-left"></i>
                    </div>
                    <div style="flex: 1;">
                        <?php echo nl2br(htmlspecialchars($employer['mini_jobsite_custom_message'])); ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Open Positions -->
            <h2 class="section-title">
                <i class="fas fa-briefcase" style="color: <?php echo $theme['primary']; ?>;"></i>
                Open Positions (<?php echo count($jobs); ?>)
            </h2>

            <?php if (empty($jobs)): ?>
            <div class="no-jobs">
                <i class="fas fa-briefcase"></i>
                <h3 style="margin-bottom: 0.5rem;">No Open Positions</h3>
                <p style="color: #6b7280;">Check back later for new opportunities!</p>
            </div>
            <?php else: ?>
            <div class="jobs-grid">
                <?php foreach ($jobs as $job): ?>
                <div class="job-card">
                    <h3 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h3>
                    
                    <div class="job-meta">
                        <?php if ($job['job_type']): ?>
                        <div class="job-meta-item">
                            <i class="fas fa-clock"></i>
                            <span><?php echo htmlspecialchars(ucfirst($job['job_type'])); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($job['city'] || $job['state']): ?>
                        <div class="job-meta-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo htmlspecialchars(trim($job['city'] . ', ' . $job['state'], ', ')); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($job['salary_min'] || $job['salary_max']): ?>
                        <div class="job-meta-item">
                            <i class="fas fa-money-bill-wave"></i>
                            <span>
                                ₦<?php echo number_format($job['salary_min'] ?? 0); ?> - 
                                ₦<?php echo number_format($job['salary_max'] ?? 0); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="job-description">
                        <?php 
                        $desc = strip_tags($job['description']);
                        echo htmlspecialchars(mb_substr($desc, 0, 150) . (mb_strlen($desc) > 150 ? '...' : '')); 
                        ?>
                    </div>
                    
                    <div class="job-footer">
                        <div style="font-size: 0.85rem; color: #9ca3af;">
                            <i class="fas fa-clock"></i>
                            Posted <?php echo date('M d, Y', strtotime($job['created_at'])); ?>
                        </div>
                        <a href="/findajob/pages/jobs/details.php?id=<?php echo $job['id']; ?>" 
                           class="btn-apply">
                            View Details <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Contact Information -->
            <?php if ($employer['mini_jobsite_show_contact']): ?>
            <div class="contact-section">
                <h2 class="section-title">
                    <i class="fas fa-envelope" style="color: <?php echo $theme['primary']; ?>;"></i>
                    Contact Us
                </h2>
                
                <div class="contact-grid">
                    <?php if ($employer['email']): ?>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div>
                            <div style="font-weight: 600; margin-bottom: 0.25rem;">Email</div>
                            <a href="mailto:<?php echo htmlspecialchars($employer['email']); ?>" 
                               style="color: <?php echo $theme['primary']; ?>; text-decoration: none;">
                                <?php echo htmlspecialchars($employer['email']); ?>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($employer['website']): ?>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-globe"></i>
                        </div>
                        <div>
                            <div style="font-weight: 600; margin-bottom: 0.25rem;">Website</div>
                            <a href="<?php echo htmlspecialchars($employer['website']); ?>" 
                               target="_blank"
                               style="color: <?php echo $theme['primary']; ?>; text-decoration: none;">
                                Visit Website
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($employer['address']): ?>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div>
                            <div style="font-weight: 600; margin-bottom: 0.25rem;">Address</div>
                            <div style="color: #4b5563;">
                                <?php echo nl2br(htmlspecialchars($employer['address'])); ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="container">
            <p style="margin-bottom: 0.5rem;">
                © <?php echo date('Y'); ?> <?php echo htmlspecialchars($employer['company_name']); ?>. All rights reserved.
            </p>
            <p style="font-size: 0.9rem; opacity: 0.8;">
                Powered by <a href="/findajob" style="color: white; text-decoration: none; font-weight: 600;">FindAJob Nigeria</a>
            </p>
        </div>
    </div>
</body>
</html>
