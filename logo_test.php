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
                <img src="assets/images/logo_full.png" alt="FindAJob Logo" style="height: 40px; width: auto; max-width: 180px;">
                <div>
                    <strong>Header Logo (40px height)</strong><br>
                    <span style="color: #64748b;">Used in navigation header - full company logo</span>
                </div>
            </div>
            
            <div class="logo-item">
                <img src="assets/images/logo_full.png" alt="FindAJob Logo" style="height: 50px; width: auto; max-width: 200px;">
                <div>
                    <strong>Footer Logo (50px height)</strong><br>
                    <span style="color: #64748b;">Used in footer branding - full company logo</span>
                </div>
            </div>
            
            <div class="logo-item">
                <img src="assets/images/icons/icon-192x192.svg" alt="FindAJob Icon" style="width: 64px; height: 64px;">
                <div>
                    <strong>Icon Version (64x64px)</strong><br>
                    <span style="color: #64748b;">FAJ icon - used for favicon and app icons</span>
                </div>
            </div>
            
            <div class="logo-item">
                <img src="assets/images/logo_full.png" alt="FindAJob Logo" style="height: 80px; width: auto;">
                <div>
                    <strong>Full Company Logo (80px height)</strong><br>
                    <span style="color: #64748b;">Complete branding for larger displays</span>
                </div>
            </div>
        </div>
        
        <div style="margin-top: 2rem;">
            <h3>✅ Logo System Features</h3>
            <ul>
                <li>✅ <strong>Full Company Logo:</strong> Complete branding with company name (PNG format)</li>
                <li>✅ <strong>Icon Version:</strong> Compact FAJ icon for favicons and app icons (SVG format)</li>
                <li>✅ <strong>Brand Colors:</strong> Consistent FindAJob red (#dc2626) color scheme</li>
                <li>✅ <strong>Professional Design:</strong> Clean, modern appearance</li>
                <li>✅ <strong>Responsive Sizing:</strong> Auto-width with height constraints for different contexts</li>
                <li>✅ <strong>Multi-Format:</strong> PNG for photos, SVG for icons</li>
            </ul>
        </div>
        
        <div style="margin-top: 2rem;">
            <h3>🌐 Implementation Status</h3>
            <ul>
                <li>✅ <strong>Favicon:</strong> FAJ icon (SVG) for browser tabs</li>
                <li>✅ <strong>Header Logo:</strong> Full company logo (PNG) in navigation</li>
                <li>✅ <strong>Footer Logo:</strong> Full company logo (PNG) in footer</li>
                <li>✅ <strong>PWA Manifest:</strong> FAJ icon for app installation</li>
                <li>✅ <strong>Responsive Design:</strong> Auto-width scaling for different screen sizes</li>
                <li>✅ <strong>Brand Consistency:</strong> Proper logo hierarchy throughout site</li>
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