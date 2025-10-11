<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warm Color Scheme Test - FindAJob Nigeria</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    
    <link rel="stylesheet" href="assets/css/main.css">
    
    <style>
        .demo-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .section {
            margin: 3rem 0;
            padding: 2rem;
            border-radius: 12px;
            background: #fffbeb;
            border: 1px solid #fed7aa;
        }
        
        .button-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }
        
        .color-swatch {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 0.5rem 0;
            padding: 0.75rem;
            background: white;
            border-radius: 8px;
            border: 1px solid #fed7aa;
        }
        
        .color-box {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            border: 1px solid rgba(0,0,0,0.1);
        }
        
        .before-after {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin: 2rem 0;
        }
        
        .before, .after {
            padding: 1.5rem;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
        }
        
        .before {
            background: #dbeafe;
            border-color: #93c5fd;
        }
        
        .after {
            background: #fffbeb;
            border-color: #fed7aa;
        }
        
        .demo-search {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin: 2rem 0;
            border: 2px solid #fed7aa;
        }
        
        .search-demo-form {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .search-demo-input {
            flex: 1;
            padding: 1rem;
            border: 2px solid #fed7aa;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        h1, h2, h3 {
            color: #1e293b;
        }
        
        .improvement {
            background: #fffbeb;
            border-left: 4px solid #f59e0b;
            padding: 1rem;
            margin: 1rem 0;
        }
        
        .warm-gradient {
            background: linear-gradient(135deg, #fef3c7 0%, #fed7aa 50%, #fb923c 100%);
            padding: 2rem;
            border-radius: 16px;
            text-align: center;
            color: #92400e;
        }
        
        @media (max-width: 768px) {
            .before-after {
                grid-template-columns: 1fr;
            }
            
            .button-grid {
                grid-template-columns: 1fr;
            }
            
            .search-demo-form {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
    <div class="demo-container">
        <div class="warm-gradient">
            <h1 style="color: #7c2d12; margin-bottom: 1rem;">🌅 Warm Color Scheme</h1>
            <p style="font-size: 1.2rem; margin-bottom: 0;">
                Replaced cool blues with warm amber and yellow tones for a more inviting experience
            </p>
        </div>
        
        <!-- New Color Palette -->
        <div class="section">
            <h2 style="color: #d97706;">🎨 Updated Warm Color Palette</h2>
            <p>Replaced blue with warmer, more inviting colors that feel friendly and approachable:</p>
            
            <div class="color-swatch">
                <div class="color-box" style="background: #dc2626;"></div>
                <div>
                    <strong style="color: #dc2626;">Red (#dc2626)</strong><br>
                    <small>Reserved for important actions, errors, and brand elements</small>
                </div>
            </div>
            
            <div class="color-swatch">
                <div class="color-box" style="background: #f59e0b;"></div>
                <div>
                    <strong style="color: #d97706;">Amber (#f59e0b)</strong><br>
                    <small>Login buttons, search actions - warm and inviting</small>
                </div>
            </div>
            
            <div class="color-swatch">
                <div class="color-box" style="background: #eab308;"></div>
                <div>
                    <strong style="color: #ca8a04;">Yellow (#eab308)</strong><br>
                    <small>Apply buttons, job actions - energetic and optimistic</small>
                </div>
            </div>
            
            <div class="color-swatch">
                <div class="color-box" style="background: #059669;"></div>
                <div>
                    <strong style="color: #047857;">Green (#059669)</strong><br>
                    <small>Registration, success actions, positive feedback</small>
                </div>
            </div>
            
            <div class="color-swatch">
                <div class="color-box" style="background: #ea580c;"></div>
                <div>
                    <strong style="color: #c2410c;">Orange (#ea580c)</strong><br>
                    <small>Action buttons, call-to-actions</small>
                </div>
            </div>
            
            <div class="color-swatch">
                <div class="color-box" style="background: #0d9488;"></div>
                <div>
                    <strong style="color: #0f766e;">Teal (#0d9488)</strong><br>
                    <small>Save actions, secondary functions</small>
                </div>
            </div>
        </div>

        <!-- Button Demonstration -->
        <div class="section">
            <h2 style="color: #d97706;">🔘 Warm Button Color Scheme</h2>
            <p>Each button now uses warm, inviting colors that feel friendly and approachable:</p>
            
            <div class="button-grid">
                <button class="btn btn-login">🔑 Login (Amber)</button>
                <button class="btn btn-register">✅ Register (Green)</button>
                <button class="btn btn-apply-job">📝 Apply for Job (Yellow)</button>
                <button class="btn btn-save">💾 Save Job (Teal)</button>
                <button class="btn btn-amber">🌟 Amber Button</button>
                <button class="btn btn-yellow">☀️ Yellow Button</button>
                <button class="btn btn-green">✓ Green Button</button>
                <button class="btn btn-orange">⚡ Orange Button</button>
                <button class="btn btn-teal">🔧 Teal Button</button>
                <button class="btn btn-primary">🚨 Red (Important Only)</button>
            </div>
        </div>

        <!-- Search Demo -->
        <div class="section">
            <h2 style="color: #d97706;">🔍 Warm Search Experience</h2>
            <p>The search button now uses warm amber instead of cold blue - more inviting and friendly:</p>
            
            <div class="demo-search">
                <div class="search-demo-form">
                    <input type="text" class="search-demo-input" placeholder="Search for jobs..." value="Software Developer">
                    <select class="search-demo-input" style="flex: 0 0 200px;">
                        <option>Lagos, Nigeria</option>
                    </select>
                    <button class="btn search-btn">🔍 Search Jobs</button>
                </div>
            </div>
        </div>

        <!-- Before vs After -->
        <div class="section">
            <h2 style="color: #d97706;">📊 Cool vs Warm Comparison</h2>
            <div class="before-after">
                <div class="before">
                    <h3 style="color: #1d4ed8;">❄️ Cool Blues (Before)</h3>
                    <div style="margin: 1rem 0;">
                        <button class="btn" style="background: #2563eb; color: white; margin: 0.25rem;">Login</button>
                        <button class="btn" style="background: #059669; color: white; margin: 0.25rem;">Register</button>
                        <button class="btn" style="background: #2563eb; color: white; margin: 0.25rem;">Search</button>
                        <button class="btn" style="background: #ea580c; color: white; margin: 0.25rem;">Apply</button>
                        <button class="btn" style="background: #0d9488; color: white; margin: 0.25rem;">Save</button>
                    </div>
                    <p style="color: #1e40af;"><strong>Cool Blue Feeling:</strong></p>
                    <ul style="color: #1e3a8a;">
                        <li>Corporate and formal</li>
                        <li>Distant and cold</li>
                        <li>Professional but not inviting</li>
                        <li>May feel impersonal</li>
                    </ul>
                </div>
                
                <div class="after">
                    <h3 style="color: #d97706;">🌅 Warm Amber/Yellow (After)</h3>
                    <div style="margin: 1rem 0;">
                        <button class="btn btn-login" style="margin: 0.25rem;">Login</button>
                        <button class="btn btn-register" style="margin: 0.25rem;">Register</button>
                        <button class="btn search-btn" style="margin: 0.25rem; padding: 0.75rem 1.5rem;">Search</button>
                        <button class="btn btn-apply-job" style="margin: 0.25rem;">Apply</button>
                        <button class="btn btn-save" style="margin: 0.25rem;">Save</button>
                    </div>
                    <p style="color: #d97706;"><strong>Warm Color Benefits:</strong></p>
                    <ul style="color: #c2410c;">
                        <li>Friendly and approachable</li>
                        <li>Energetic and optimistic</li>
                        <li>Inviting and welcoming</li>
                        <li>Builds confidence and trust</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Color Psychology -->
        <div class="section">
            <h2 style="color: #d97706;">🧠 Warm Color Psychology</h2>
            
            <div class="improvement">
                <h4 style="color: #d97706; margin-top: 0;">✅ Why Warm Colors Work Better for Job Searching</h4>
                <ul style="color: #92400e;">
                    <li><strong>Amber (Login/Search):</strong> Warmth, accessibility, and trust without being cold</li>
                    <li><strong>Yellow (Apply/Jobs):</strong> Optimism, energy, and hope - perfect for job seekers</li>
                    <li><strong>Green (Register/Success):</strong> Growth, new beginnings, positive outcomes</li>
                    <li><strong>Orange (Actions):</strong> Enthusiasm and confidence without aggression</li>
                    <li><strong>Teal (Save/Secondary):</strong> Balance and clarity for supportive actions</li>
                    <li><strong>Red (Important only):</strong> Urgency and importance when truly needed</li>
                </ul>
            </div>
            
            <div style="background: #fef3c7; padding: 1rem; border-radius: 8px; border-left: 4px solid #f59e0b;">
                <h4 style="color: #d97706; margin-top: 0;">💡 Job Platform Benefits</h4>
                <p style="color: #92400e; margin: 0;">
                    Warm colors like amber and yellow create a more optimistic, hopeful atmosphere that's perfect for 
                    job seekers. They feel encouraged and motivated rather than intimidated by corporate blues. This 
                    emotional connection can improve user engagement and conversion rates.
                </p>
            </div>
        </div>

        <!-- Usage Guidelines -->
        <div class="section">
            <h2 style="color: #d97706;">📋 Warm Color Usage Guidelines</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                <div style="background: white; padding: 1rem; border-radius: 8px; border-left: 4px solid #f59e0b;">
                    <strong style="color: #d97706;">Amber Buttons</strong>
                    <ul style="margin: 0.5rem 0 0 0; color: #92400e;">
                        <li>Login forms</li>
                        <li>Search functionality</li>
                        <li>Primary navigation</li>
                        <li>Information access</li>
                    </ul>
                </div>
                
                <div style="background: white; padding: 1rem; border-radius: 8px; border-left: 4px solid #eab308;">
                    <strong style="color: #ca8a04;">Yellow Buttons</strong>
                    <ul style="margin: 0.5rem 0 0 0; color: #a16207;">
                        <li>Job applications</li>
                        <li>Apply actions</li>
                        <li>Opportunity actions</li>
                        <li>Optimistic interactions</li>
                    </ul>
                </div>
                
                <div style="background: white; padding: 1rem; border-radius: 8px; border-left: 4px solid #059669;">
                    <strong style="color: #047857;">Green Buttons</strong>
                    <ul style="margin: 0.5rem 0 0 0; color: #065f46;">
                        <li>Registration</li>
                        <li>Confirmation actions</li>
                        <li>Success messages</li>
                        <li>Positive feedback</li>
                    </ul>
                </div>
                
                <div style="background: white; padding: 1rem; border-radius: 8px; border-left: 4px solid #dc2626;">
                    <strong style="color: #dc2626;">Red Buttons (Limited Use)</strong>
                    <ul style="margin: 0.5rem 0 0 0; color: #991b1b;">
                        <li>Delete actions</li>
                        <li>Error states</li>
                        <li>Critical warnings</li>
                        <li>Brand highlights only</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Final Summary -->
        <div class="warm-gradient" style="margin: 3rem 0;">
            <h2 style="color: #7c2d12; margin-bottom: 1rem;">🌟 Warm & Welcoming Design Achieved!</h2>
            <p style="color: #92400e; font-size: 1.1rem; max-width: 600px; margin: 0 auto;">
                FindAJob Nigeria now features a warm, inviting color palette that makes job searching feel 
                hopeful and encouraging. The amber and yellow tones create an optimistic atmosphere perfect 
                for career growth and opportunity discovery.
            </p>
        </div>
    </div>
</body>
</html>