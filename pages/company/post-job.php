<?php require_once '../../includes/functions.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post a Job - FindAJob Nigeria</title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#2563eb">
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
                <h1 class="page-title">Post a Job</h1>
            </div>
        </header>

        <main class="main-content">
            <!-- Post Job Hero -->
            <section class="hero-section employer-hero">
                <div class="hero-content">
                    <h2 class="hero-title">Find the Perfect Candidate</h2>
                    <p class="hero-subtitle">Post your job to reach thousands of qualified professionals across Nigeria.</p>
                </div>
            </section>

            <!-- Job Posting Form -->
            <section class="form-section">
                <div class="form-container">
                    <form class="job-form" method="POST">
                        <!-- Basic Job Information -->
                        <div class="form-section-header">
                            <h3>Job Details</h3>
                            <p>Provide basic information about the position</p>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="job-title">Job Title *</label>
                                <input type="text" id="job-title" name="job_title" required 
                                       placeholder="e.g. Software Developer, Marketing Manager">
                            </div>

                            <div class="form-group">
                                <label for="job-type">Job Type *</label>
                                <select id="job-type" name="job_type" required>
                                    <option value="">Select job type</option>
                                    <option value="full-time">Full-time</option>
                                    <option value="part-time">Part-time</option>
                                    <option value="contract">Contract</option>
                                    <option value="temporary">Temporary</option>
                                    <option value="internship">Internship</option>
                                    <option value="nysc">NYSC Placement</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="category">Category *</label>
                                <select id="category" name="category" required>
                                    <option value="">Select category</option>
                                    <option value="technology">Technology</option>
                                    <option value="banking">Banking & Finance</option>
                                    <option value="oil-gas">Oil & Gas</option>
                                    <option value="healthcare">Healthcare</option>
                                    <option value="education">Education</option>
                                    <option value="marketing">Marketing & Sales</option>
                                    <option value="engineering">Engineering</option>
                                    <option value="government">Government</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="location">Location *</label>
                                <select id="location" name="location" required>
                                    <option value="">Select location</option>
                                    <option value="lagos">Lagos</option>
                                    <option value="abuja">Abuja</option>
                                    <option value="port-harcourt">Port Harcourt</option>
                                    <option value="kano">Kano</option>
                                    <option value="ibadan">Ibadan</option>
                                    <option value="kaduna">Kaduna</option>
                                    <option value="benin">Benin City</option>
                                    <option value="remote">Remote</option>
                                </select>
                            </div>
                        </div>

                        <!-- Salary Information -->
                        <div class="form-section-header">
                            <h3>Compensation</h3>
                            <p>Specify salary range and benefits</p>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="salary-min">Minimum Salary (₦)</label>
                                <input type="number" id="salary-min" name="salary_min" 
                                       placeholder="e.g. 200000" min="0">
                            </div>

                            <div class="form-group">
                                <label for="salary-max">Maximum Salary (₦)</label>
                                <input type="number" id="salary-max" name="salary_max" 
                                       placeholder="e.g. 500000" min="0">
                            </div>

                            <div class="form-group">
                                <label for="salary-period">Salary Period</label>
                                <select id="salary-period" name="salary_period">
                                    <option value="monthly">Monthly</option>
                                    <option value="annually">Annually</option>
                                    <option value="hourly">Hourly</option>
                                    <option value="project">Per Project</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="benefits">Benefits</label>
                                <input type="text" id="benefits" name="benefits" 
                                       placeholder="e.g. Health insurance, Transport allowance">
                            </div>
                        </div>

                        <!-- Job Description -->
                        <div class="form-section-header">
                            <h3>Job Description</h3>
                            <p>Describe the role and requirements</p>
                        </div>

                        <div class="form-group">
                            <label for="description">Job Description *</label>
                            <textarea id="description" name="description" required rows="6"
                                      placeholder="Describe the job responsibilities, company culture, and what makes this role exciting..."></textarea>
                        </div>

                        <div class="form-group">
                            <label for="requirements">Requirements *</label>
                            <textarea id="requirements" name="requirements" required rows="4"
                                      placeholder="List the required qualifications, skills, and experience..."></textarea>
                        </div>

                        <div class="form-group">
                            <label for="skills">Required Skills</label>
                            <input type="text" id="skills" name="skills" 
                                   placeholder="e.g. JavaScript, Project Management, Communication">
                        </div>

                        <!-- Experience Level -->
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="experience">Experience Level</label>
                                <select id="experience" name="experience_level">
                                    <option value="">Select experience level</option>
                                    <option value="entry">Entry Level (0-2 years)</option>
                                    <option value="mid">Mid Level (2-5 years)</option>
                                    <option value="senior">Senior Level (5+ years)</option>
                                    <option value="executive">Executive Level</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="education">Education Level</label>
                                <select id="education" name="education_level">
                                    <option value="">Select education level</option>
                                    <option value="ssce">SSCE/WAEC</option>
                                    <option value="ond">OND</option>
                                    <option value="hnd">HND</option>
                                    <option value="bsc">Bachelor's Degree</option>
                                    <option value="msc">Master's Degree</option>
                                    <option value="phd">PhD</option>
                                </select>
                            </div>
                        </div>

                        <!-- Application Settings -->
                        <div class="form-section-header">
                            <h3>Application Settings</h3>
                            <p>How should candidates apply for this job?</p>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="application-email">Application Email</label>
                                <input type="email" id="application-email" name="application_email" 
                                       placeholder="careers@yourcompany.com">
                            </div>

                            <div class="form-group">
                                <label for="application-deadline">Application Deadline</label>
                                <input type="date" id="application-deadline" name="application_deadline">
                            </div>
                        </div>

                        <!-- Job Boost Options -->
                        <div class="form-section-header">
                            <h3>Boost Your Job</h3>
                            <p>Increase visibility and attract more candidates</p>
                        </div>

                        <div class="boost-options">
                            <div class="boost-card">
                                <div class="boost-header">
                                    <h4>Basic Posting</h4>
                                    <span class="boost-price">Free</span>
                                </div>
                                <ul class="boost-features">
                                    <li>✅ Standard listing</li>
                                    <li>✅ 30 days visibility</li>
                                    <li>❌ Featured placement</li>
                                    <li>❌ Social media promotion</li>
                                </ul>
                                <input type="radio" name="boost_type" value="free" id="boost-free" checked>
                                <label for="boost-free" class="boost-select">Select Basic</label>
                            </div>

                            <div class="boost-card featured">
                                <div class="boost-badge">Recommended</div>
                                <div class="boost-header">
                                    <h4>Premium Boost</h4>
                                    <span class="boost-price">₦5,000</span>
                                </div>
                                <ul class="boost-features">
                                    <li>✅ Featured placement</li>
                                    <li>✅ 60 days visibility</li>
                                    <li>✅ Social media promotion</li>
                                    <li>✅ Email to relevant candidates</li>
                                </ul>
                                <input type="radio" name="boost_type" value="premium" id="boost-premium">
                                <label for="boost-premium" class="boost-select">Select Premium</label>
                            </div>

                            <div class="boost-card">
                                <div class="boost-header">
                                    <h4>Super Boost</h4>
                                    <span class="boost-price">₦15,000</span>
                                </div>
                                <ul class="boost-features">
                                    <li>✅ Top placement</li>
                                    <li>✅ 90 days visibility</li>
                                    <li>✅ Multi-platform promotion</li>
                                    <li>✅ Priority support</li>
                                </ul>
                                <input type="radio" name="boost_type" value="super" id="boost-super">
                                <label for="boost-super" class="boost-select">Select Super</label>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="form-actions">
                            <button type="button" class="btn-secondary">Save as Draft</button>
                            <button type="submit" class="btn-primary">Post Job</button>
                        </div>
                    </form>
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
        <a href="post-job.php" class="app-bottom-nav-item active">
            <div class="app-bottom-nav-icon">📝</div>
            <div class="app-bottom-nav-label">Post Job</div>
        </a>
        <a href="dashboard.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">🏢</div>
            <div class="app-bottom-nav-label">Dashboard</div>
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

        // Form validation and submission
        document.querySelector('.job-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Basic validation
            const requiredFields = ['job_title', 'job_type', 'category', 'location', 'description', 'requirements'];
            let isValid = true;
            
            requiredFields.forEach(field => {
                const input = document.querySelector(`[name="${field}"]`);
                if (!input.value.trim()) {
                    input.style.borderColor = '#dc2626';
                    isValid = false;
                } else {
                    input.style.borderColor = '';
                }
            });
            
            if (!isValid) {
                alert('Please fill in all required fields');
                return;
            }
            
            // Get form data
            const formData = new FormData(this);
            const boostType = formData.get('boost_type');
            
            // In a real app, this would submit to the API
            console.log('Submitting job:', Object.fromEntries(formData));
            
            if (boostType === 'free') {
                alert('Job posted successfully! It will be reviewed and published within 24 hours.');
            } else {
                alert(`Job posted with ${boostType} boost! Redirecting to payment...`);
            }
        });

        // Save as draft functionality
        document.querySelector('.btn-secondary').addEventListener('click', function() {
            // In a real app, this would save the form data
            alert('Job saved as draft! You can complete it later from your dashboard.');
        });

        // Boost option selection
        document.querySelectorAll('input[name="boost_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                // Remove active class from all cards
                document.querySelectorAll('.boost-card').forEach(card => {
                    card.classList.remove('selected');
                });
                
                // Add active class to selected card
                this.closest('.boost-card').classList.add('selected');
            });
        });
    </script>
</body>
</html>