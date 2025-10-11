<?php
/**
 * Logo Path Test
 * Test if logo files are accessible from different page locations
 */

require_once 'config/constants.php';
require_once 'includes/functions.php';

// Only accessible in development mode
if (!isDevelopmentMode()) {
    die('This page is only available in development mode.');
}

$logo_tests = [
    'Main Logo' => '/findajob/assets/images/logo_full.png',
    'Icon Logo' => '/findajob/assets/images/icons/icon-192x192.svg',
    'Relative Path (from root)' => 'assets/images/logo_full.png',
    'Relative Path (icon)' => 'assets/images/icons/icon-192x192.svg'
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logo Path Test - FindAJob Nigeria</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="assets/images/icons/icon-192x192.svg">
    
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 2rem;
            background: #f8fafc;
            color: #1e293b;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .test-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            margin-bottom: 1rem;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        
        .test-success {
            border-left: 4px solid #059669;
            background: #f0fdf4;
        }
        
        .test-error {
            border-left: 4px solid #dc2626;
            background: #fef2f2;
        }
        
        .logo-preview {
            min-width: 120px;
            text-align: center;
        }
        
        .logo-preview img {
            max-height: 40px;
            width: auto;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .status-success {
            background: #059669;
            color: white;
        }
        
        .status-error {
            background: #dc2626;
            color: white;
        }
        
        .current-page-test {
            background: #eff6ff;
            border: 2px solid #3b82f6;
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔗 Logo Path Test</h1>
        <p>Testing logo accessibility from different paths and page locations</p>
        
        <div>
            <h3>📁 Logo File Tests</h3>
            <?php foreach ($logo_tests as $name => $path): ?>
                <?php 
                $full_path = $_SERVER['DOCUMENT_ROOT'] . $path;
                $exists = file_exists($full_path);
                $accessible = $exists && is_readable($full_path);
                ?>
                <div class="test-item <?php echo $accessible ? 'test-success' : 'test-error'; ?>">
                    <div class="logo-preview">
                        <?php if ($accessible): ?>
                            <img src="<?php echo $path; ?>" alt="<?php echo $name; ?>" onerror="this.style.display='none'">
                        <?php else: ?>
                            <span style="color: #dc2626;">❌</span>
                        <?php endif; ?>
                    </div>
                    <div style="flex: 1;">
                        <strong><?php echo $name; ?></strong><br>
                        <code><?php echo $path; ?></code><br>
                        <small style="color: #64748b;">
                            Full path: <?php echo $full_path; ?>
                        </small>
                    </div>
                    <div class="status-badge <?php echo $accessible ? 'status-success' : 'status-error'; ?>">
                        <?php echo $accessible ? 'ACCESSIBLE' : 'NOT FOUND'; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="current-page-test">
            <h3>🌐 Current Page Test</h3>
            <p><strong>Current URL:</strong> <?php echo $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?></p>
            <p><strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT']; ?></p>
            <p><strong>Current Directory:</strong> <?php echo __DIR__; ?></p>
            
            <div style="margin-top: 1rem;">
                <strong>Test Logo Display from Current Location:</strong><br>
                <img src="assets/images/logo_full.png" alt="Test Logo" style="height: 40px; margin: 0.5rem 0;" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                <span style="display: none; color: #dc2626;">❌ Logo not accessible from current path</span>
            </div>
        </div>
        
        <div style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #e2e8f0;">
            <h3>🔧 Quick Actions</h3>
            <a href="index.php" style="margin-right: 1rem; color: #dc2626; text-decoration: none;">🏠 Test Main Page</a>
            <a href="pages/user/dashboard.php" style="margin-right: 1rem; color: #dc2626; text-decoration: none;">📊 Test Dashboard</a>
            <a href="logo_test.php" style="margin-right: 1rem; color: #dc2626; text-decoration: none;">🎨 Logo Display Test</a>
        </div>
    </div>
</body>
</html>