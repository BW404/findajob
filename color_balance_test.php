<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Color Balance Test - FindAJob Nigeria</title>
    
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
            background: #f8fafc;
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
            border: 1px solid #e2e8f0;
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
            background: #fef2f2;
            border-color: #fecaca;
        }
        
        .after {
            background: #f0fdf4;
            border-color: #bbf7d0;
        }
        
        .demo-search {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin: 2rem 0;
        }
        
        .search-demo-form {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .search-demo-input {
            flex: 1;
            padding: 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        h1, h2, h3 {
            color: #1e293b;
        }
        
        .improvement {
            background: #ecfdf5;
            border-left: 4px solid #059669;
            padding: 1rem;
            margin: 1rem 0;
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
        <h1 style="text-align: center; color: #dc2626; margin-bottom: 1rem;">🎨 Color Balance Improvement</h1>
        <p style="text-align: center; color: #64748b; font-size: 1.2rem; margin-bottom: 3rem;">
            Reducing red overwhelming by introducing softer, purpose-driven button colors
        </p>
        
        <!-- Color Palette -->
        <div class="section">
            <h2>🎨 New Color Palette</h2>
            <p>Added complementary colors to reduce red dominance while maintaining brand identity:</p>
            
            <div class="color-swatch">
                <div class="color-box" style="background: #dc2626;"></div>
                <div>
                    <strong>Red (#dc2626)</strong><br>
                    <small>Reserved for important actions, errors, and brand elements</small>
                </div>
            </div>
            
            <div class="color-swatch">
                <div class="color-box" style="background: #2563eb;"></div>
                <div>
                    <strong>Blue (#2563eb)</strong><br>
                    <small>Login buttons, search actions, information</small>
                </div>
            </div>
            
            <div class="color-swatch">
                <div class="color-box" style="background: #059669;"></div>
                <div>
                    <strong>Green (#059669)</strong><br>
                    <small>Registration, success actions, positive feedback</small>
                </div>
            </div>
            
            <div class="color-swatch">
                <div class="color-box" style="background: #ea580c;"></div>
                <div>
                    <strong>Orange (#ea580c)</strong><br>
                    <small>Apply buttons, job-related actions</small>
                </div>
            </div>
            
            <div class="color-swatch">
                <div class="color-box" style="background: #0d9488;"></div>
                <div>
                    <strong>Teal (#0d9488)</strong><br>
                    <small>Save actions, secondary functions</small>
                </div>
            </div>
            
            <div class="color-swatch">
                <div class="color-box" style="background: #7c3aed;"></div>
                <div>
                    <strong>Purple (#7c3aed)</strong><br>
                    <small>Premium features, special actions</small>
                </div>
            </div>
        </div>

        <!-- Button Demonstration -->
        <div class="section">
            <h2>🔘 New Button Types</h2>
            <p>Each button type now has a specific color purpose, reducing red fatigue:</p>
            
            <div class="button-grid">
                <button class="btn btn-login">🔑 Login</button>
                <button class="btn btn-register">✅ Register</button>
                <button class="btn btn-apply-job">📝 Apply for Job</button>
                <button class="btn btn-save">💾 Save Job</button>
                <button class="btn btn-blue">ℹ️ Information</button>
                <button class="btn btn-green">✓ Confirm</button>
                <button class="btn btn-orange">⚡ Action</button>
                <button class="btn btn-teal">🔧 Settings</button>
                <button class="btn btn-purple">⭐ Premium</button>
                <button class="btn btn-primary">🚨 Important (Red)</button>
            </div>
        </div>

        <!-- Search Demo -->
        <div class="section">
            <h2>🔍 Search Button Improvement</h2>
            <p>The search button now uses calming blue instead of aggressive red:</p>
            
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
            <h2>📊 Before vs After Comparison</h2>
            <div class="before-after">
                <div class="before">
                    <h3 style="color: #dc2626;">❌ Before: Red Overwhelming</h3>
                    <div style="margin: 1rem 0;">
                        <button class="btn" style="background: #dc2626; color: white; margin: 0.25rem;">Login</button>
                        <button class="btn" style="background: #dc2626; color: white; margin: 0.25rem;">Register</button>
                        <button class="btn" style="background: #dc2626; color: white; margin: 0.25rem;">Search</button>
                        <button class="btn" style="background: #dc2626; color: white; margin: 0.25rem;">Apply</button>
                        <button class="btn" style="background: #dc2626; color: white; margin: 0.25rem;">Save</button>
                    </div>
                    <p style="color: #991b1b;"><strong>Issues:</strong></p>
                    <ul style="color: #7f1d1d;">
                        <li>Aggressive appearance</li>
                        <li>No visual hierarchy</li>
                        <li>User fatigue</li>
                        <li>Everything seems urgent</li>
                    </ul>
                </div>
                
                <div class="after">
                    <h3 style="color: #059669;">✅ After: Balanced Colors</h3>
                    <div style="margin: 1rem 0;">
                        <button class="btn btn-login" style="margin: 0.25rem;">Login</button>
                        <button class="btn btn-register" style="margin: 0.25rem;">Register</button>
                        <button class="btn search-btn" style="margin: 0.25rem; padding: 0.75rem 1.5rem;">Search</button>
                        <button class="btn btn-apply-job" style="margin: 0.25rem;">Apply</button>
                        <button class="btn btn-save" style="margin: 0.25rem;">Save</button>
                    </div>
                    <p style="color: #047857;"><strong>Improvements:</strong></p>
                    <ul style="color: #065f46;">
                        <li>Each color has meaning</li>
                        <li>Clear visual hierarchy</li>
                        <li>More inviting interface</li>
                        <li>Red reserved for important actions</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Color Psychology -->
        <div class="section">
            <h2>🧠 Color Psychology & UX</h2>
            
            <div class="improvement">
                <h4 style="color: #059669; margin-top: 0;">✅ User Experience Improvements</h4>
                <ul>
                    <li><strong>Blue (Login/Search):</strong> Trust, reliability, and calmness</li>
                    <li><strong>Green (Register/Success):</strong> Growth, positive actions, safety</li>
                    <li><strong>Orange (Apply/Jobs):</strong> Energy, enthusiasm, action without aggression</li>
                    <li><strong>Teal (Save/Secondary):</strong> Balance, clarity, supportive actions</li>
                    <li><strong>Purple (Premium):</strong> Sophistication, quality, special features</li>
                    <li><strong>Red (Important only):</strong> Urgent actions, errors, brand highlights</li>
                </ul>
            </div>
            
            <div style="background: #dbeafe; padding: 1rem; border-radius: 8px; border-left: 4px solid #2563eb;">
                <h4 style="color: #1d4ed8; margin-top: 0;">💡 Implementation Strategy</h4>
                <p style="color: #1e40af; margin: 0;">
                    By giving each action type its own color, users can quickly identify and feel comfortable with different 
                    interface elements. This reduces cognitive load and creates a more professional, welcoming experience.
                </p>
            </div>
        </div>

        <!-- Usage Guidelines -->
        <div class="section">
            <h2>📋 Button Usage Guidelines</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                <div style="background: white; padding: 1rem; border-radius: 8px; border-left: 4px solid #2563eb;">
                    <strong style="color: #2563eb;">Blue Buttons</strong>
                    <ul style="margin: 0.5rem 0 0 0; color: #1e40af;">
                        <li>Login forms</li>
                        <li>Search functionality</li>
                        <li>Information actions</li>
                        <li>Navigation links</li>
                    </ul>
                </div>
                
                <div style="background: white; padding: 1rem; border-radius: 8px; border-left: 4px solid #059669;">
                    <strong style="color: #059669;">Green Buttons</strong>
                    <ul style="margin: 0.5rem 0 0 0; color: #047857;">
                        <li>Registration</li>
                        <li>Confirmation actions</li>
                        <li>Success messages</li>
                        <li>Positive feedback</li>
                    </ul>
                </div>
                
                <div style="background: white; padding: 1rem; border-radius: 8px; border-left: 4px solid #ea580c;">
                    <strong style="color: #ea580c;">Orange Buttons</strong>
                    <ul style="margin: 0.5rem 0 0 0; color: #c2410c;">
                        <li>Job applications</li>
                        <li>Active engagements</li>
                        <li>Call-to-actions</li>
                        <li>Featured actions</li>
                    </ul>
                </div>
                
                <div style="background: white; padding: 1rem; border-radius: 8px; border-left: 4px solid #dc2626;">
                    <strong style="color: #dc2626;">Red Buttons (Selective Use)</strong>
                    <ul style="margin: 0.5rem 0 0 0; color: #991b1b;">
                        <li>Delete actions</li>
                        <li>Error states</li>
                        <li>Critical warnings</li>
                        <li>Brand elements only</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Final Summary -->
        <div style="text-align: center; margin: 3rem 0; padding: 2rem; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border-radius: 12px;">
            <h2 style="color: #0369a1; margin-bottom: 1rem;">🎉 Color Balance Achieved!</h2>
            <p style="color: #075985; font-size: 1.1rem; max-width: 600px; margin: 0 auto;">
                The FindAJob platform now features a sophisticated color system that reduces visual fatigue while 
                maintaining strong brand identity. Each color serves a specific purpose, creating a more intuitive 
                and welcoming user experience.
            </p>
        </div>
    </div>
</body>
</html>