<?php require_once '../../includes/functions.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CV Creator - FindAJob Nigeria</title>
    
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
                <h1 class="page-title">CV Creator</h1>
            </div>
        </header>

        <main class="main-content">
            <!-- CV Creator Hero -->
            <section class="hero-section">
                <div class="hero-content">
                    <h2 class="hero-title">Create Your Professional CV</h2>
                    <p class="hero-subtitle">Build a standout CV with our AI-powered creator. Get noticed by top employers in Nigeria.</p>
                </div>
            </section>

            <!-- CV Templates -->
            <section class="templates-section">
                <div class="section-header">
                    <h2>Choose Your Template</h2>
                    <p class="section-subtitle">Select a professional template that matches your industry</p>
                </div>

                <div class="templates-grid">
                    <div class="template-card">
                        <div class="template-preview">
                            <div class="template-thumb">
                                <div class="template-mock">
                                    <div class="mock-header"></div>
                                    <div class="mock-content">
                                        <div class="mock-line"></div>
                                        <div class="mock-line short"></div>
                                        <div class="mock-line"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="template-info">
                            <h3 class="template-name">Professional</h3>
                            <p class="template-desc">Clean and modern design perfect for corporate roles</p>
                            <div class="template-tags">
                                <span class="template-tag">Corporate</span>
                                <span class="template-tag">Banking</span>
                            </div>
                            <button class="btn-select-template">Use This Template</button>
                        </div>
                    </div>

                    <div class="template-card">
                        <div class="template-preview">
                            <div class="template-thumb">
                                <div class="template-mock creative">
                                    <div class="mock-header colored"></div>
                                    <div class="mock-content">
                                        <div class="mock-line"></div>
                                        <div class="mock-line short"></div>
                                        <div class="mock-line"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="template-info">
                            <h3 class="template-name">Creative</h3>
                            <p class="template-desc">Eye-catching design for creative and tech professionals</p>
                            <div class="template-tags">
                                <span class="template-tag">Tech</span>
                                <span class="template-tag">Design</span>
                            </div>
                            <button class="btn-select-template">Use This Template</button>
                        </div>
                    </div>

                    <div class="template-card">
                        <div class="template-preview">
                            <div class="template-thumb">
                                <div class="template-mock minimal">
                                    <div class="mock-header"></div>
                                    <div class="mock-content">
                                        <div class="mock-line"></div>
                                        <div class="mock-line short"></div>
                                        <div class="mock-line"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="template-info">
                            <h3 class="template-name">Minimal</h3>
                            <p class="template-desc">Simple and elegant design that highlights your content</p>
                            <div class="template-tags">
                                <span class="template-tag">Education</span>
                                <span class="template-tag">Healthcare</span>
                            </div>
                            <button class="btn-select-template">Use This Template</button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- CV Builder Features -->
            <section class="features-section">
                <div class="section-header">
                    <h2>Why Use Our CV Creator?</h2>
                </div>

                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">🤖</div>
                        <h3 class="feature-title">AI-Powered</h3>
                        <p class="feature-desc">Our AI suggests content improvements and optimizes your CV for Nigerian employers</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">⚡</div>
                        <h3 class="feature-title">Quick & Easy</h3>
                        <p class="feature-desc">Create a professional CV in under 10 minutes with our step-by-step builder</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">📱</div>
                        <h3 class="feature-title">Mobile Optimized</h3>
                        <p class="feature-desc">Build and edit your CV on any device - desktop, tablet, or smartphone</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">💾</div>
                        <h3 class="feature-title">Multiple Formats</h3>
                        <p class="feature-desc">Download your CV as PDF, Word document, or share online with employers</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">🎯</div>
                        <h3 class="feature-title">Nigerian Market</h3>
                        <p class="feature-desc">Templates designed specifically for the Nigerian job market and local employers</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">🔒</div>
                        <h3 class="feature-title">Secure & Private</h3>
                        <p class="feature-desc">Your personal information is encrypted and never shared without permission</p>
                    </div>
                </div>
            </section>

            <!-- Pricing Section -->
            <section class="pricing-section">
                <div class="section-header">
                    <h2>Choose Your Plan</h2>
                    <p class="section-subtitle">Start creating your professional CV today</p>
                </div>

                <div class="pricing-grid">
                    <div class="pricing-card">
                        <div class="pricing-header">
                            <h3 class="plan-name">Basic CV</h3>
                            <div class="plan-price">
                                <span class="price-currency">₦</span>
                                <span class="price-amount">0</span>
                                <span class="price-period">Free</span>
                            </div>
                        </div>
                        <div class="pricing-features">
                            <ul class="features-list">
                                <li>✅ 1 Basic Template</li>
                                <li>✅ PDF Download</li>
                                <li>✅ Basic Editing</li>
                                <li>❌ Premium Templates</li>
                                <li>❌ AI Suggestions</li>
                                <li>❌ Multiple CVs</li>
                            </ul>
                        </div>
                        <button class="btn-select-plan">Get Started Free</button>
                    </div>

                    <div class="pricing-card featured">
                        <div class="pricing-badge">Most Popular</div>
                        <div class="pricing-header">
                            <h3 class="plan-name">Professional CV</h3>
                            <div class="plan-price">
                                <span class="price-currency">₦</span>
                                <span class="price-amount">15,500</span>
                                <span class="price-period">One-time</span>
                            </div>
                        </div>
                        <div class="pricing-features">
                            <ul class="features-list">
                                <li>✅ All Premium Templates</li>
                                <li>✅ AI Content Suggestions</li>
                                <li>✅ Multiple CV Versions</li>
                                <li>✅ Cover Letter Generator</li>
                                <li>✅ LinkedIn Optimization</li>
                                <li>✅ 1-Year Updates</li>
                            </ul>
                        </div>
                        <button class="btn-select-plan primary">Choose Professional</button>
                    </div>

                    <div class="pricing-card">
                        <div class="pricing-header">
                            <h3 class="plan-name">Premium CV</h3>
                            <div class="plan-price">
                                <span class="price-currency">₦</span>
                                <span class="price-amount">33,500</span>
                                <span class="price-period">One-time</span>
                            </div>
                        </div>
                        <div class="pricing-features">
                            <ul class="features-list">
                                <li>✅ Everything in Professional</li>
                                <li>✅ Personal Career Consultant</li>
                                <li>✅ Interview Preparation</li>
                                <li>✅ Job Search Strategy</li>
                                <li>✅ LinkedIn Profile Setup</li>
                                <li>✅ Lifetime Updates</li>
                            </ul>
                        </div>
                        <button class="btn-select-plan">Choose Premium</button>
                    </div>
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
        <a href="../jobs/browse.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">🔍</div>
            <div class="app-bottom-nav-label">Jobs</div>
        </a>
        <a href="cv-creator.php" class="app-bottom-nav-item active">
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

        // Template selection handlers
        document.querySelectorAll('.btn-select-template').forEach(button => {
            button.addEventListener('click', function() {
                const templateCard = this.closest('.template-card');
                const templateName = templateCard.querySelector('.template-name').textContent;
                
                // In a real app, this would navigate to the CV builder
                alert(`Opening CV builder with ${templateName} template...`);
            });
        });

        // Plan selection handlers
        document.querySelectorAll('.btn-select-plan').forEach(button => {
            button.addEventListener('click', function() {
                const planCard = this.closest('.pricing-card');
                const planName = planCard.querySelector('.plan-name').textContent;
                
                if (planName === 'Basic CV') {
                    // Free plan - direct to CV builder
                    alert('Opening free CV builder...');
                } else {
                    // Paid plans - redirect to payment
                    alert(`Redirecting to payment for ${planName}...`);
                }
            });
        });
    </script>
</body>
</html>