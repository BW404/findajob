<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logo & Favicon Test - FindAJob Nigeria</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="assets/images/icons/icon-192x192.svg">
    <link rel="alternate icon" href="assets/images/icons/icon-192x192.svg">
    <link rel="shortcut icon" href="assets/images/icons/icon-192x192.svg">
    
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
        
        .logo-showcase {
            display: grid;
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .logo-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        
        .logo-sizes {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 1rem;
            text-align: center;
        }
        
        .logo-size-item {
            padding: 1rem;
            background: white;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }
        
        .favicon-test {
            background: #fef3c7;
            padding: 1rem;
            border-radius: 6px;
            border: 1px solid #f59e0b;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎨 Logo & Favicon Test</h1>
        <p>Testing the FindAJob Nigeria logo and favicon implementation</p>
        
        <div class="favicon-test">
            <strong>📋 Favicon Test:</strong> Check your browser tab to see if the favicon (FAJ logo) is displaying correctly.
        </div>
        
        <div class="logo-showcase">
            <h2>📱 Logo Display Tests</h2>
            
            <div class="logo-item">
                <img src="assets/images/icons/icon-192x192.svg" alt="FindAJob Logo" style="width: 32px; height: 32px;">
                <div>
                    <strong>Header Size (32x32px)</strong><br>
                    <span style="color: #64748b;">Used in navigation header</span>
                </div>
            </div>
            
            <div class="logo-item">
                <img src="assets/images/icons/icon-192x192.svg" alt="FindAJob Logo" style="width: 40px; height: 40px;">
                <div>
                    <strong>Footer Size (40x40px)</strong><br>
                    <span style="color: #64748b;">Used in footer branding</span>
                </div>
            </div>
            
            <div class="logo-item">
                <img src="assets/images/icons/icon-192x192.svg" alt="FindAJob Logo" style="width: 64px; height: 64px;">
                <div>
                    <strong>Large Display (64x64px)</strong><br>
                    <span style="color: #64748b;">For larger branding areas</span>
                </div>
            </div>
            
            <div class="logo-item">
                <img src="assets/images/icons/icon-192x192.svg" alt="FindAJob Logo" style="width: 96px; height: 96px;">
                <div>
                    <strong>Extra Large (96x96px)</strong><br>
                    <span style="color: #64748b;">For splash screens and large displays</span>
                </div>
            </div>
        </div>
        
        <div style="margin-top: 2rem;">
            <h3>✅ Logo Features</h3>
            <ul>
                <li>✅ <strong>SVG Format:</strong> Scalable vector graphics for crisp display at any size</li>
                <li>✅ <strong>Brand Colors:</strong> Uses FindAJob red (#dc2626) color scheme</li>
                <li>✅ <strong>FAJ Branding:</strong> Clear "FAJ" text for brand recognition</li>
                <li>✅ <strong>Clean Design:</strong> Simple, professional appearance</li>
                <li>✅ <strong>PWA Ready:</strong> Suitable for Progressive Web App icons</li>
            </ul>
        </div>
        
        <div style="margin-top: 2rem;">
            <h3>🌐 Implementation Status</h3>
            <ul>
                <li>✅ <strong>Favicon:</strong> Browser tab icon updated</li>
                <li>✅ <strong>Header Logo:</strong> Navigation branding updated</li>
                <li>✅ <strong>Footer Logo:</strong> Footer branding updated</li>
                <li>✅ <strong>PWA Manifest:</strong> App icons configured</li>
                <li>✅ <strong>All Pages:</strong> Auth pages and development tools updated</li>
            </ul>
        </div>
        
        <div style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #e2e8f0;">
            <h3>🔗 Quick Links</h3>
            <a href="index.php" style="margin-right: 1rem; color: #dc2626; text-decoration: none;">🏠 Home</a>
            <a href="pages/auth/register-jobseeker.php" style="margin-right: 1rem; color: #dc2626; text-decoration: none;">📝 Register</a>
            <a href="pages/auth/login.php" style="margin-right: 1rem; color: #dc2626; text-decoration: none;">🔐 Login</a>
            <a href="temp_mail.php" style="margin-right: 1rem; color: #dc2626; text-decoration: none;">📧 Dev Tools</a>
        </div>
    </div>
</body>
</html>