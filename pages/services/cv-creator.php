<?php
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/constants.php';
require_once '../../includes/functions.php';

// Get current user if logged in
$user = null;
if (isLoggedIn()) {
    $userId = getCurrentUserId();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CV Creator - <?php echo SITE_NAME; ?></title>
    
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
<body class="has-bottom-nav">
    <?php include '../../includes/header.php'; ?>

    <div class="cv-creator-container">

        <main class="main-content">
            <!-- CV Creator Hero -->
            <section class="hero-section">
                <div class="hero-content">
                    <div class="hero-text">
                        <h2 class="hero-title">Create Your Professional CV</h2>
                        <p class="hero-subtitle">Choose between our free AI-powered CV builder or get a professionally crafted CV by our experts.</p>
                    </div>
                </div>
            </section>

            <!-- Service Options -->
            <section class="service-options-section">
                <div class="section-header">
                    <h2>Choose Your CV Creation Service</h2>
                    <p class="section-subtitle">Select the option that best fits your needs and budget</p>
                </div>

                <div class="service-options-grid">
                    <!-- Free AI CV Builder -->
                    <div class="service-option free-service">
                        <div class="service-badge">
                            <span class="badge-text">🤖 FREE</span>
                        </div>
                        <div class="service-content">
                            <h3 class="service-title">AI CV Builder</h3>
                            <div class="service-price">
                                <span class="price">₦0</span>
                                <span class="price-desc">Completely Free</span>
                            </div>
                            <ul class="service-features">
                                <li>✅ AI-powered content suggestions</li>
                                <li>✅ 3 professional templates</li>
                                <li>✅ Step-by-step guided builder</li>
                                <li>✅ PDF & Word download</li>
                                <li>✅ Mobile-friendly interface</li>
                                <li>✅ Auto-save functionality</li>
                                <li>✅ Nigerian job market optimized</li>
                            </ul>
                            <div class="service-actions">
                                <button class="btn btn-primary service-select-btn" data-service="free">
                                    🚀 Start Building Now
                                </button>
                                <p class="service-note">Perfect for getting started quickly with professional results</p>
                            </div>
                        </div>
                    </div>

                    <!-- Professional CV Service -->
                    <div class="service-option professional-service">
                        <div class="service-badge premium">
                            <span class="badge-text">👑 PREMIUM</span>
                        </div>
                        <div class="service-content">
                            <h3 class="service-title">Expert CV Writing Service</h3>
                            <div class="service-price">
                                <span class="price">₦15,500 - ₦33,500</span>
                                <span class="price-desc">One-time payment</span>
                            </div>
                            <ul class="service-features">
                                <li>✅ Professional CV writer assigned</li>
                                <li>✅ 1-on-1 consultation call</li>
                                <li>✅ Industry-specific optimization</li>
                                <li>✅ ATS-friendly formatting</li>
                                <li>✅ Cover letter included</li>
                                <li>✅ LinkedIn profile optimization</li>
                                <li>✅ 2 rounds of revisions</li>
                                <li>✅ 7-day delivery guarantee</li>
                            </ul>
                            <div class="service-actions">
                                <button class="btn btn-premium service-select-btn" data-service="professional">
                                    💼 Get Expert CV
                                </button>
                                <p class="service-note">Get a professionally crafted CV that stands out to employers</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Service Comparison -->
                <div class="service-comparison">
                    <h3>Which Service Is Right for You?</h3>
                    <div class="comparison-grid">
                        <div class="comparison-item">
                            <div class="comparison-icon">⚡</div>
                            <h4>Need it fast?</h4>
                            <p>Use our <strong>Free AI Builder</strong> - Create your CV in under 15 minutes</p>
                        </div>
                        <div class="comparison-item">
                            <div class="comparison-icon">🎯</div>
                            <h4>Targeting specific roles?</h4>
                            <p>Choose <strong>Expert Service</strong> - Get industry-specific optimization</p>
                        </div>
                        <div class="comparison-item">
                            <div class="comparison-icon">📈</div>
                            <h4>Want maximum impact?</h4>
                            <p>Go <strong>Professional</strong> - Expert writers know what employers want</p>
                        </div>
                    </div>
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

            <!-- CV Builder Interface (Hidden by default) -->
            <section class="cv-builder-section" id="cvBuilderSection" style="display: none;">
                <div class="builder-header">
                    <div class="builder-title">
                        <h2>Build Your CV</h2>
                        <p>Follow the steps below to create your professional CV</p>
                    </div>
                    <div class="builder-progress">
                        <div class="progress-steps">
                            <div class="step active" data-step="1">
                                <span class="step-number">1</span>
                                <span class="step-label">Personal Info</span>
                            </div>
                            <div class="step" data-step="2">
                                <span class="step-number">2</span>
                                <span class="step-label">Experience</span>
                            </div>
                            <div class="step" data-step="3">
                                <span class="step-number">3</span>
                                <span class="step-label">Education</span>
                            </div>
                            <div class="step" data-step="4">
                                <span class="step-number">4</span>
                                <span class="step-label">Skills</span>
                            </div>
                            <div class="step" data-step="5">
                                <span class="step-number">5</span>
                                <span class="step-label">References</span>
                            </div>
                            <div class="step" data-step="6">
                                <span class="step-number">6</span>
                                <span class="step-label">Review</span>
                            </div>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 16.67%"></div>
                        </div>
                    </div>
                </div>

                <div class="builder-content">
                    <div class="builder-form">
                        <!-- Step 1: Personal Information -->
                        <div class="builder-step active" id="step1">
                            <h3>Personal Information</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="firstName">First Name *</label>
                                    <input type="text" id="firstName" name="firstName" value="<?php echo $user ? htmlspecialchars($user['first_name']) : ''; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="lastName">Last Name *</label>
                                    <input type="text" id="lastName" name="lastName" value="<?php echo $user ? htmlspecialchars($user['last_name']) : ''; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="email">Email Address *</label>
                                    <input type="email" id="email" name="email" value="<?php echo $user ? htmlspecialchars($user['email']) : ''; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="phone">Phone Number *</label>
                                    <input type="tel" id="phone" name="phone" placeholder="+234" required>
                                </div>
                                <div class="form-group full-width">
                                    <label for="address">Address</label>
                                    <input type="text" id="address" name="address" placeholder="City, State, Nigeria">
                                </div>
                                <div class="form-group full-width">
                                    <label for="professional-summary">Professional Summary</label>
                                    <textarea id="professional-summary" name="professional-summary" rows="4" placeholder="Brief overview of your professional background and career objectives..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Work Experience -->
                        <div class="builder-step" id="step2">
                            <h3>Work Experience</h3>
                            <div id="experience-container">
                                <div class="experience-entry">
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label for="job-title-1">Job Title *</label>
                                            <input type="text" id="job-title-1" name="job-title[]" placeholder="e.g. Software Developer" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="company-1">Company Name *</label>
                                            <input type="text" id="company-1" name="company[]" placeholder="e.g. ABC Technologies" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="start-date-1">Start Date *</label>
                                            <input type="date" id="start-date-1" name="start-date[]" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="end-date-1">End Date</label>
                                            <input type="date" id="end-date-1" name="end-date[]">
                                            <label class="checkbox-label">
                                                <input type="checkbox" name="current-job[]"> Currently working here
                                            </label>
                                        </div>
                                        <div class="form-group full-width">
                                            <label for="job-description-1">Job Description</label>
                                            <textarea id="job-description-1" name="job-description[]" rows="4" placeholder="Describe your key responsibilities and achievements..."></textarea>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-secondary remove-experience">Remove</button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline add-experience">+ Add Another Job</button>
                        </div>

                        <!-- Step 3: Education -->
                        <div class="builder-step" id="step3">
                            <h3>Education</h3>
                            <div id="education-container">
                                <div class="education-entry">
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label for="degree-1">Degree/Certificate *</label>
                                            <input type="text" id="degree-1" name="degree[]" placeholder="e.g. Bachelor of Science" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="field-1">Field of Study *</label>
                                            <input type="text" id="field-1" name="field[]" placeholder="e.g. Computer Science" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="school-1">School/Institution *</label>
                                            <input type="text" id="school-1" name="school[]" placeholder="e.g. University of Lagos" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="grad-year-1">Graduation Year</label>
                                            <input type="number" id="grad-year-1" name="grad-year[]" min="1950" max="2030" placeholder="2023">
                                        </div>
                                        <div class="form-group">
                                            <label for="gpa-1">Grade/GPA (Optional)</label>
                                            <input type="text" id="gpa-1" name="gpa[]" placeholder="e.g. First Class, 3.8/4.0">
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-secondary remove-education">Remove</button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline add-education">+ Add Another Education</button>
                        </div>

                        <!-- Step 4: Skills -->
                        <div class="builder-step" id="step4">
                            <h3>Skills & Qualifications</h3>
                            <div class="form-grid">
                                <div class="form-group full-width">
                                    <label for="technical-skills">Technical Skills</label>
                                    <textarea id="technical-skills" name="technical-skills" rows="3" placeholder="e.g. JavaScript, Python, SQL, Microsoft Office, Adobe Photoshop..."></textarea>
                                    <small>Separate skills with commas</small>
                                </div>
                                <div class="form-group full-width">
                                    <label for="soft-skills">Soft Skills</label>
                                    <textarea id="soft-skills" name="soft-skills" rows="3" placeholder="e.g. Leadership, Communication, Problem Solving, Team Work..."></textarea>
                                    <small>Separate skills with commas</small>
                                </div>
                                <div class="form-group full-width">
                                    <label for="languages">Languages</label>
                                    <textarea id="languages" name="languages" rows="2" placeholder="e.g. English (Native), Yoruba (Fluent), French (Basic)..."></textarea>
                                </div>
                                <div class="form-group full-width">
                                    <label for="certifications">Certifications & Awards</label>
                                    <textarea id="certifications" name="certifications" rows="3" placeholder="e.g. AWS Certified Developer (2023), Best Employee of the Year (2022)..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Step 5: References -->
                        <div class="builder-step" id="step5">
                            <h3>References</h3>
                            <p class="section-description">Add professional references who can vouch for your work experience and character. Include supervisors, colleagues, or clients who know your work well.</p>
                            
                            <div class="reference-options">
                                <div class="reference-toggle">
                                    <label class="checkbox-label">
                                        <input type="checkbox" id="references-available" name="references-available" checked>
                                        Include references section
                                    </label>
                                    <small>Uncheck if you prefer to provide references upon request</small>
                                </div>
                            </div>

                            <div id="references-container" class="references-section">
                                <div class="reference-entry">
                                    <h4>Reference 1</h4>
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label for="ref-name-1">Full Name *</label>
                                            <input type="text" id="ref-name-1" name="ref-name[]" placeholder="e.g. Dr. John Adebayo" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="ref-title-1">Job Title *</label>
                                            <input type="text" id="ref-title-1" name="ref-title[]" placeholder="e.g. Senior Manager" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="ref-company-1">Company/Organization *</label>
                                            <input type="text" id="ref-company-1" name="ref-company[]" placeholder="e.g. ABC Technologies Ltd" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="ref-relationship-1">Professional Relationship *</label>
                                            <select id="ref-relationship-1" name="ref-relationship[]" required>
                                                <option value="">Select relationship</option>
                                                <option value="direct-supervisor">Direct Supervisor</option>
                                                <option value="manager">Manager</option>
                                                <option value="colleague">Colleague</option>
                                                <option value="client">Client</option>
                                                <option value="mentor">Mentor</option>
                                                <option value="hr-manager">HR Manager</option>
                                                <option value="other">Other</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="ref-phone-1">Phone Number *</label>
                                            <input type="tel" id="ref-phone-1" name="ref-phone[]" placeholder="+234" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="ref-email-1">Email Address *</label>
                                            <input type="email" id="ref-email-1" name="ref-email[]" placeholder="john.adebayo@company.com" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="ref-years-1">Years Known</label>
                                            <input type="text" id="ref-years-1" name="ref-years[]" placeholder="e.g. 3 years">
                                        </div>
                                        <div class="form-group">
                                            <label for="ref-permission-1">Permission Status</label>
                                            <select id="ref-permission-1" name="ref-permission[]">
                                                <option value="granted">Permission granted</option>
                                                <option value="pending">Permission pending</option>
                                                <option value="not-asked">Not yet asked</option>
                                            </select>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-secondary remove-reference">Remove Reference</button>
                                </div>

                                <div class="reference-entry">
                                    <h4>Reference 2</h4>
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label for="ref-name-2">Full Name</label>
                                            <input type="text" id="ref-name-2" name="ref-name[]" placeholder="e.g. Mrs. Sarah Okafor">
                                        </div>
                                        <div class="form-group">
                                            <label for="ref-title-2">Job Title</label>
                                            <input type="text" id="ref-title-2" name="ref-title[]" placeholder="e.g. Team Lead">
                                        </div>
                                        <div class="form-group">
                                            <label for="ref-company-2">Company/Organization</label>
                                            <input type="text" id="ref-company-2" name="ref-company[]" placeholder="e.g. XYZ Solutions">
                                        </div>
                                        <div class="form-group">
                                            <label for="ref-relationship-2">Professional Relationship</label>
                                            <select id="ref-relationship-2" name="ref-relationship[]">
                                                <option value="">Select relationship</option>
                                                <option value="direct-supervisor">Direct Supervisor</option>
                                                <option value="manager">Manager</option>
                                                <option value="colleague">Colleague</option>
                                                <option value="client">Client</option>
                                                <option value="mentor">Mentor</option>
                                                <option value="hr-manager">HR Manager</option>
                                                <option value="other">Other</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="ref-phone-2">Phone Number</label>
                                            <input type="tel" id="ref-phone-2" name="ref-phone[]" placeholder="+234">
                                        </div>
                                        <div class="form-group">
                                            <label for="ref-email-2">Email Address</label>
                                            <input type="email" id="ref-email-2" name="ref-email[]" placeholder="sarah.okafor@company.com">
                                        </div>
                                        <div class="form-group">
                                            <label for="ref-years-2">Years Known</label>
                                            <input type="text" id="ref-years-2" name="ref-years[]" placeholder="e.g. 2 years">
                                        </div>
                                        <div class="form-group">
                                            <label for="ref-permission-2">Permission Status</label>
                                            <select id="ref-permission-2" name="ref-permission[]">
                                                <option value="granted">Permission granted</option>
                                                <option value="pending">Permission pending</option>
                                                <option value="not-asked">Not yet asked</option>
                                            </select>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-secondary remove-reference">Remove Reference</button>
                                </div>
                            </div>

                            <button type="button" class="btn btn-outline add-reference">+ Add Another Reference</button>
                            
                            <div class="reference-note">
                                <p><strong>💡 Reference Tips:</strong></p>
                                <ul>
                                    <li>Always ask permission before listing someone as a reference</li>
                                    <li>Choose references who can speak positively about your work performance</li>
                                    <li>Include a mix of supervisors and colleagues if possible</li>
                                    <li>Provide current and accurate contact information</li>
                                    <li>Consider professional references over personal ones</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Step 6: Review -->
                        <div class="builder-step" id="step6">
                            <h3>Review Your CV</h3>
                            <div class="cv-preview-container">
                                <div class="cv-preview" id="cvPreview">
                                    <div class="preview-loading">
                                        <div class="spinner"></div>
                                        <p>Generating your CV preview...</p>
                                    </div>
                                </div>
                            </div>
                            <div class="download-options">
                                <button type="button" class="btn btn-primary download-pdf">📄 Download PDF</button>
                                <button type="button" class="btn btn-outline download-docx">📄 Download Word</button>
                                <button type="button" class="btn btn-secondary save-draft">💾 Save Draft</button>
                            </div>
                        </div>
                    </div>

                    <div class="builder-navigation">
                        <button type="button" class="btn btn-secondary prev-step" disabled>← Previous</button>
                        <button type="button" class="btn btn-primary next-step">Next →</button>
                    </div>
                </div>
            </section>

            <!-- Professional CV Service Booking -->
            <section class="professional-booking-section" id="professionalBookingSection" style="display: none;">
                <div class="booking-header">
                    <h2>Professional CV Writing Service</h2>
                    <p>Get paired with a CV expert who will create your professional CV offline</p>
                </div>

                <div class="booking-content">
                    <div class="booking-grid">
                        <!-- Service Packages -->
                        <div class="booking-packages">
                            <h3>Choose Your Package</h3>
                            <div class="packages-list">
                                <div class="package-card" data-price="15500">
                                    <h4>Essential CV Package</h4>
                                    <div class="package-price">₦15,500</div>
                                    <ul class="package-features">
                                        <li>✅ Professional CV writing</li>
                                        <li>✅ 1 phone consultation (30 min)</li>
                                        <li>✅ Basic cover letter</li>
                                        <li>✅ 1 revision round</li>
                                        <li>✅ 5-day delivery</li>
                                    </ul>
                                    <button class="btn btn-outline package-select-btn">Select Package</button>
                                </div>

                                <div class="package-card popular" data-price="24500">
                                    <div class="popular-badge">Most Popular</div>
                                    <h4>Professional CV Package</h4>
                                    <div class="package-price">₦24,500</div>
                                    <ul class="package-features">
                                        <li>✅ Professional CV writing</li>
                                        <li>✅ 1-hour consultation call</li>
                                        <li>✅ Tailored cover letter</li>
                                        <li>✅ LinkedIn profile optimization</li>
                                        <li>✅ 2 revision rounds</li>
                                        <li>✅ 3-day delivery</li>
                                    </ul>
                                    <button class="btn btn-primary package-select-btn">Select Package</button>
                                </div>

                                <div class="package-card" data-price="33500">
                                    <h4>Executive CV Package</h4>
                                    <div class="package-price">₦33,500</div>
                                    <ul class="package-features">
                                        <li>✅ Senior CV specialist</li>
                                        <li>✅ 90-minute consultation</li>
                                        <li>✅ Executive cover letter</li>
                                        <li>✅ LinkedIn + portfolio optimization</li>
                                        <li>✅ Interview coaching (1 session)</li>
                                        <li>✅ Unlimited revisions</li>
                                        <li>✅ 24-hour delivery</li>
                                    </ul>
                                    <button class="btn btn-premium package-select-btn">Select Package</button>
                                </div>
                            </div>
                        </div>

                        <!-- Booking Form -->
                        <div class="booking-form-container">
                            <h3>Book Your CV Expert</h3>
                            <form id="professionalBookingForm" class="booking-form">
                                <div class="form-group">
                                    <label for="client-name">Full Name *</label>
                                    <input type="text" id="client-name" name="client-name" value="<?php echo $user ? htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) : ''; ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="client-email">Email Address *</label>
                                    <input type="email" id="client-email" name="client-email" value="<?php echo $user ? htmlspecialchars($user['email']) : ''; ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="client-phone">Phone Number *</label>
                                    <input type="tel" id="client-phone" name="client-phone" placeholder="+234" required>
                                </div>

                                <div class="form-group">
                                    <label for="target-industry">Target Industry *</label>
                                    <select id="target-industry" name="target-industry" required>
                                        <option value="">Select your industry</option>
                                        <option value="technology">Technology & IT</option>
                                        <option value="banking">Banking & Finance</option>
                                        <option value="oil-gas">Oil & Gas</option>
                                        <option value="telecommunications">Telecommunications</option>
                                        <option value="healthcare">Healthcare</option>
                                        <option value="education">Education</option>
                                        <option value="manufacturing">Manufacturing</option>
                                        <option value="construction">Construction</option>
                                        <option value="agriculture">Agriculture</option>
                                        <option value="government">Government/Public Sector</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="experience-level">Experience Level *</label>
                                    <select id="experience-level" name="experience-level" required>
                                        <option value="">Select your level</option>
                                        <option value="entry">Entry Level (0-2 years)</option>
                                        <option value="mid">Mid Level (3-5 years)</option>
                                        <option value="senior">Senior Level (6-10 years)</option>
                                        <option value="executive">Executive (10+ years)</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="current-cv">Upload Current CV (Optional)</label>
                                    <input type="file" id="current-cv" name="current-cv" accept=".pdf,.doc,.docx">
                                    <small>Upload your existing CV for reference (PDF, DOC, DOCX)</small>
                                </div>

                                <div class="form-group">
                                    <label for="special-requirements">Special Requirements</label>
                                    <textarea id="special-requirements" name="special-requirements" rows="4" placeholder="Any specific requirements, target companies, or additional information..."></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="preferred-contact">Preferred Contact Time</label>
                                    <select id="preferred-contact" name="preferred-contact">
                                        <option value="morning">Morning (9 AM - 12 PM)</option>
                                        <option value="afternoon">Afternoon (12 PM - 5 PM)</option>
                                        <option value="evening">Evening (5 PM - 8 PM)</option>
                                        <option value="flexible">Flexible</option>
                                    </select>
                                </div>

                                <div class="booking-summary">
                                    <h4>Order Summary</h4>
                                    <div class="summary-item">
                                        <span id="selected-package-name">No package selected</span>
                                        <span id="selected-package-price">₦0</span>
                                    </div>
                                    <div class="summary-total">
                                        <span>Total Amount:</span>
                                        <span id="total-amount">₦0</span>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-premium btn-large" id="proceed-to-payment" disabled>
                                    💳 Proceed to Payment
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- How It Works -->
                    <div class="process-explanation">
                        <h3>How Our Professional CV Service Works</h3>
                        <div class="process-steps">
                            <div class="process-step">
                                <div class="step-number">1</div>
                                <div class="step-content">
                                    <h4>Book & Pay</h4>
                                    <p>Choose your package and complete payment. You'll receive confirmation within 30 minutes.</p>
                                </div>
                            </div>
                            <div class="process-step">
                                <div class="step-number">2</div>
                                <div class="step-content">
                                    <h4>Expert Assignment</h4>
                                    <p>We'll match you with a CV expert specialized in your industry and experience level.</p>
                                </div>
                            </div>
                            <div class="process-step">
                                <div class="step-number">3</div>
                                <div class="step-content">
                                    <h4>Consultation Call</h4>
                                    <p>Your expert will contact you for a detailed discussion about your career goals and experience.</p>
                                </div>
                            </div>
                            <div class="process-step">
                                <div class="step-number">4</div>
                                <div class="step-content">
                                    <h4>CV Creation</h4>
                                    <p>Your expert crafts your professional CV offline, optimized for Nigerian employers and ATS systems.</p>
                                </div>
                            </div>
                            <div class="process-step">
                                <div class="step-number">5</div>
                                <div class="step-content">
                                    <h4>Review & Revisions</h4>
                                    <p>Receive your CV, provide feedback, and get revisions until you're completely satisfied.</p>
                                </div>
                            </div>
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

    </div>

    <!-- CV Builder Styles -->
    <style>
        .cv-creator-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0;
            min-height: 100vh;
        }

        .main-content {
            padding: 2rem 1rem;
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: white;
            padding: 4rem 2rem;
            margin: 0;
            border-radius: 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 20"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="20" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 800px;
            margin: 0 auto;
        }

        .hero-text {
            margin: 0 auto;
        }

        .hero-title {
            font-size: 3rem;
            font-weight: 800;
            margin: 0 0 1rem;
            line-height: 1.2;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .hero-subtitle {
            font-size: 1.2rem;
            margin: 0;
            opacity: 0.95;
            line-height: 1.6;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Section Headers */
        .section-header {
            text-align: center;
            margin-bottom: 3rem;
            padding: 0 2rem;
        }

        .section-header h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0 0 1rem;
        }

        .section-subtitle {
            font-size: 1.1rem;
            color: var(--text-secondary);
            margin: 0;
            line-height: 1.6;
        }

        /* Service Options Styles */
        .service-options-section {
            margin: 3rem 0;
        }

        .service-options-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            margin: 2rem 0;
            align-items: stretch;
        }

        .service-option {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
            overflow: hidden;
            position: relative;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            min-height: 100%;
        }

        .service-option:hover {
            transform: translateY(-8px);
            box-shadow: 0 16px 40px rgba(0,0,0,0.15);
        }

        .service-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            z-index: 10;
        }

        .badge-text {
            background: linear-gradient(45deg, #10b981, #059669);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .service-badge.premium .badge-text {
            background: linear-gradient(45deg, #f59e0b, #d97706);
        }

        .service-content {
            padding: 2rem;
            display: flex;
            flex-direction: column;
            flex: 1;
            min-height: 600px;
        }

        .service-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .service-price {
            margin-bottom: 2rem;
        }

        .service-price .price {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary);
            display: block;
        }

        .service-price .price-desc {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .service-features {
            list-style: none;
            padding: 0;
            margin: 2rem 0;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .service-features li {
            padding: 0.5rem 0;
            color: var(--text-primary);
            font-weight: 500;
        }

        .service-actions {
            margin-top: auto;
            padding-top: 1rem;
        }

        .service-select-btn {
            width: 100%;
            padding: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .btn-premium {
            background: linear-gradient(45deg, #f59e0b, #d97706);
            color: white;
            border: none;
        }

        .btn-premium:hover {
            background: linear-gradient(45deg, #d97706, #b45309);
        }

        .service-note {
            color: var(--text-secondary);
            font-size: 0.9rem;
            text-align: center;
            margin: 0;
        }

        .service-comparison {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 2rem;
            margin-top: 3rem;
        }

        .service-comparison h3 {
            text-align: center;
            color: var(--text-primary);
            margin-bottom: 2rem;
        }

        .comparison-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .comparison-item {
            text-align: center;
        }

        .comparison-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .comparison-item h4 {
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .comparison-item p {
            color: var(--text-secondary);
            line-height: 1.6;
        }

        /* Professional Booking Styles */
        .professional-booking-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin: 2rem 0;
            overflow: hidden;
        }

        .booking-header {
            background: linear-gradient(45deg, #f59e0b, #d97706);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .booking-header h2 {
            margin: 0 0 0.5rem;
            font-size: 2rem;
        }

        .booking-content {
            padding: 2rem;
        }

        .booking-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .packages-list {
            display: grid;
            gap: 1.5rem;
        }

        .package-card {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .package-card.popular {
            border-color: var(--primary);
            background: linear-gradient(135deg, #fef2f2, #ffffff);
        }

        .package-card.selected {
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2);
        }

        .popular-badge {
            position: absolute;
            top: -10px;
            right: 1rem;
            background: var(--primary);
            color: white;
            padding: 0.25rem 1rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .package-card h4 {
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .package-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .package-features {
            list-style: none;
            padding: 0;
            margin: 1rem 0;
        }

        .package-features li {
            padding: 0.25rem 0;
            font-size: 0.9rem;
            color: var(--text-primary);
        }

        .package-select-btn {
            width: 100%;
            margin-top: 1rem;
        }

        .booking-form {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
        }

        .booking-summary {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            margin: 1.5rem 0;
            border: 1px solid #e9ecef;
        }

        .booking-summary h4 {
            margin: 0 0 1rem;
            color: var(--text-primary);
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e9ecef;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--primary);
        }

        .btn-large {
            padding: 1rem 2rem;
            font-size: 1.1rem;
        }

        .process-explanation {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 2rem;
        }

        .process-explanation h3 {
            text-align: center;
            color: var(--text-primary);
            margin-bottom: 2rem;
        }

        .process-steps {
            display: grid;
            gap: 2rem;
        }

        .process-step {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }

        .step-number {
            background: var(--primary);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            flex-shrink: 0;
        }

        .step-content h4 {
            color: var(--text-primary);
            margin: 0 0 0.5rem;
        }

        .step-content p {
            color: var(--text-secondary);
            margin: 0;
            line-height: 1.5;
        }

        /* Template Selection Styles */
        .templates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin: 2rem 0;
        }

        .template-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .template-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .template-preview {
            height: 200px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .template-mock {
            width: 120px;
            height: 160px;
            background: white;
            border-radius: 4px;
            padding: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .mock-header {
            height: 20px;
            background: #e9ecef;
            border-radius: 2px;
            margin-bottom: 8px;
        }

        .mock-header.colored {
            background: linear-gradient(45deg, #dc2626, #f59e0b);
        }

        .mock-line {
            height: 8px;
            background: #e9ecef;
            border-radius: 2px;
            margin-bottom: 4px;
        }

        .mock-line.short {
            width: 60%;
        }

        .template-info {
            padding: 1.5rem;
        }

        .template-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .template-desc {
            color: var(--text-secondary);
            margin-bottom: 1rem;
            line-height: 1.5;
        }

        .template-tags {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .template-tag {
            background: var(--primary-light);
            color: var(--primary);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .btn-select-template {
            width: 100%;
            background: var(--primary);
            color: white;
            padding: 0.75rem;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .btn-select-template:hover {
            background: var(--primary-dark);
        }

        /* CV Builder Interface Styles */
        .cv-builder-section {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            margin: 3rem 0;
            overflow: hidden;
        }

        .builder-header {
            background: linear-gradient(135deg, var(--primary) 0%, #991b1b 100%);
            color: white;
            padding: 3rem 2rem;
            text-align: center;
            position: relative;
        }

        .builder-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 20"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="20" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

        .builder-title {
            position: relative;
            z-index: 2;
            margin-bottom: 2rem;
        }

        .builder-title h2 {
            margin: 0 0 0.5rem;
            font-size: 2.5rem;
            font-weight: 800;
        }

        .builder-title p {
            margin: 0;
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .builder-progress {
            position: relative;
            z-index: 2;
        }

        .progress-steps {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }

        .step {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 30px;
            padding: 0.75rem 1.25rem;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }

        .step-number {
            background: rgba(255,255,255,0.2);
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .step.active {
            background: white;
            color: var(--primary);
            font-weight: 600;
            transform: scale(1.05);
        }

        .step.active .step-number {
            background: var(--primary);
            color: white;
        }

        .step.completed {
            background: rgba(16, 185, 129, 0.9);
            color: white;
        }

        .step.completed .step-number {
            background: white;
            color: #10b981;
        }

        .progress-bar {
            height: 4px;
            background: rgba(255,255,255,0.2);
            border-radius: 2px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981, #059669);
            border-radius: 2px;
            transition: width 0.3s ease;
        }

        .builder-content {
            padding: 2rem;
        }

        .builder-step {
            display: none;
        }

        .builder-step.active {
            display: block;
        }

        .builder-step h3 {
            color: var(--text-primary);
            margin-bottom: 2rem;
            font-size: 1.5rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .form-group input,
        .form-group textarea {
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
        }

        .form-group small {
            color: var(--text-secondary);
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
            font-weight: normal;
        }

        .experience-entry,
        .education-entry {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            position: relative;
        }

        .remove-experience,
        .remove-education {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
        }

        .add-experience,
        .add-education {
            margin-top: 1rem;
        }

        .cv-preview-container {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 2rem;
            min-height: 600px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cv-preview {
            background: white;
            width: 100%;
            max-width: 600px;
            min-height: 800px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-radius: 8px;
            padding: 2rem;
        }

        .preview-loading {
            text-align: center;
            color: var(--text-secondary);
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #e9ecef;
            border-top: 4px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .download-options {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .builder-navigation {
            display: flex;
            justify-content: space-between;
            padding-top: 2rem;
            border-top: 1px solid #e9ecef;
            margin-top: 2rem;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin: 2rem 0;
        }

        .feature-card {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .feature-title {
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .feature-desc {
            color: var(--text-secondary);
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .cv-creator-container {
                padding: 0;
            }

            .main-content {
                padding: 1rem 0.5rem;
            }

            .hero-section {
                padding: 2rem 1rem;
            }

            .hero-title {
                font-size: 2rem;
            }

            .hero-subtitle {
                font-size: 1rem;
            }

            .section-header h2 {
                font-size: 1.8rem;
            }

            .service-options-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .service-content {
                padding: 1.5rem;
                min-height: auto;
            }

            .comparison-grid {
                grid-template-columns: 1fr;
            }

            .builder-header {
                padding: 2rem 1rem;
            }

            .builder-title h2 {
                font-size: 1.8rem;
            }

            .progress-steps {
                gap: 0.25rem;
            }

            .step {
                padding: 0.5rem 0.75rem;
                font-size: 0.8rem;
            }

            .step-label {
                display: none;
            }

            .step-number {
                width: 20px;
                height: 20px;
                font-size: 0.7rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .builder-content {
                padding: 1.5rem 1rem;
            }

            .builder-navigation {
                flex-direction: column;
                gap: 1rem;
            }

            .download-options {
                flex-direction: column;
            }

            .booking-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .templates-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .hero-title {
                font-size: 1.75rem;
            }

            .progress-steps {
                justify-content: flex-start;
                overflow-x: auto;
                padding-bottom: 0.5rem;
            }

            .step {
                flex-shrink: 0;
            }
        }
    </style>

    <!-- PWA Scripts -->
    <script src="../../assets/js/pwa.js"></script>
    <script>
        // CV Builder JavaScript
        let currentStep = 1;
        const totalSteps = 6;
        let selectedTemplate = 'Professional';
        let cvData = {};

        // Initialize CV Builder
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize PWA features
            if ('PWAManager' in window) {
                const pwa = new PWAManager();
                pwa.init();
            }

            initializeEventListeners();
            
            // Pre-fill user data if available
            <?php if ($user): ?>
            document.getElementById('firstName').value = '<?php echo addslashes($user["first_name"]); ?>';
            document.getElementById('lastName').value = '<?php echo addslashes($user["last_name"]); ?>';
            document.getElementById('email').value = '<?php echo addslashes($user["email"]); ?>';
            <?php endif; ?>
        });

        function initializeEventListeners() {
            // Service selection
            document.querySelectorAll('.service-select-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const service = this.dataset.service;
                    
                    if (service === 'free') {
                        // Show free AI CV builder
                        showFreeAIBuilder();
                    } else if (service === 'professional') {
                        // Show professional service booking
                        showProfessionalBooking();
                    }
                });
            });

            // Template selection (for free service)
            document.querySelectorAll('.btn-select-template').forEach(button => {
                button.addEventListener('click', function() {
                    const templateCard = this.closest('.template-card');
                    selectedTemplate = templateCard.querySelector('.template-name').textContent;
                    
                    // Show CV builder
                    document.querySelector('.templates-section').style.display = 'none';
                    document.querySelector('.features-section').style.display = 'none';
                    document.getElementById('cvBuilderSection').style.display = 'block';
                    
                    // Smooth scroll to builder
                    document.getElementById('cvBuilderSection').scrollIntoView({
                        behavior: 'smooth'
                    });
                });
            });

            // Professional service package selection
            document.querySelectorAll('.package-select-btn').forEach(button => {
                button.addEventListener('click', function() {
                    // Remove previous selections
                    document.querySelectorAll('.package-card').forEach(card => {
                        card.classList.remove('selected');
                    });
                    
                    // Select current package
                    const packageCard = this.closest('.package-card');
                    packageCard.classList.add('selected');
                    
                    // Update booking summary
                    updateBookingSummary(packageCard);
                });
            });

            // Professional booking form
            const bookingForm = document.getElementById('professionalBookingForm');
            if (bookingForm) {
                bookingForm.addEventListener('submit', handleProfessionalBooking);
            }

            // Navigation buttons
            document.querySelector('.next-step').addEventListener('click', nextStep);
            document.querySelector('.prev-step').addEventListener('click', prevStep);

            // Dynamic form additions
            document.querySelector('.add-experience').addEventListener('click', addExperienceEntry);
            document.querySelector('.add-education').addEventListener('click', addEducationEntry);
            document.querySelector('.add-reference').addEventListener('click', addReferenceEntry);

            // References toggle
            document.getElementById('references-available').addEventListener('change', toggleReferencesSection);

            // Form auto-save
            setInterval(autoSave, 30000); // Auto-save every 30 seconds

            // Download buttons
            document.querySelector('.download-pdf').addEventListener('click', () => downloadCV('pdf'));
            document.querySelector('.download-docx').addEventListener('click', () => downloadCV('docx'));
            document.querySelector('.save-draft').addEventListener('click', saveDraft);
        }

        function nextStep() {
            if (currentStep < totalSteps) {
                // Validate current step
                if (validateStep(currentStep)) {
                    currentStep++;
                    updateStep();
                    
                    if (currentStep === 6) {
                        generatePreview();
                    }
                }
            }
        }

        function prevStep() {
            if (currentStep > 1) {
                currentStep--;
                updateStep();
            }
        }

        function updateStep() {
            // Hide all steps
            document.querySelectorAll('.builder-step').forEach(step => {
                step.classList.remove('active');
            });
            
            // Show current step
            document.getElementById(`step${currentStep}`).classList.add('active');
            
            // Update progress
            document.querySelectorAll('.step').forEach((step, index) => {
                const stepNum = index + 1;
                step.classList.remove('active', 'completed');
                
                if (stepNum === currentStep) {
                    step.classList.add('active');
                } else if (stepNum < currentStep) {
                    step.classList.add('completed');
                }
            });
            
            // Update navigation buttons
            const prevBtn = document.querySelector('.prev-step');
            const nextBtn = document.querySelector('.next-step');
            
            prevBtn.disabled = currentStep === 1;
            nextBtn.textContent = currentStep === totalSteps ? 'Finish' : 'Next →';
        }

        function validateStep(step) {
            const requiredFields = document.querySelectorAll(`#step${step} input[required], #step${step} textarea[required]`);
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = '#dc3545';
                    isValid = false;
                } else {
                    field.style.borderColor = '#e9ecef';
                }
            });
            
            if (!isValid) {
                alert('Please fill in all required fields.');
            }
            
            return isValid;
        }

        function addExperienceEntry() {
            const container = document.getElementById('experience-container');
            const entryCount = container.children.length + 1;
            
            const newEntry = document.createElement('div');
            newEntry.className = 'experience-entry';
            newEntry.innerHTML = `
                <div class="form-grid">
                    <div class="form-group">
                        <label for="job-title-${entryCount}">Job Title *</label>
                        <input type="text" id="job-title-${entryCount}" name="job-title[]" placeholder="e.g. Software Developer" required>
                    </div>
                    <div class="form-group">
                        <label for="company-${entryCount}">Company Name *</label>
                        <input type="text" id="company-${entryCount}" name="company[]" placeholder="e.g. ABC Technologies" required>
                    </div>
                    <div class="form-group">
                        <label for="start-date-${entryCount}">Start Date *</label>
                        <input type="date" id="start-date-${entryCount}" name="start-date[]" required>
                    </div>
                    <div class="form-group">
                        <label for="end-date-${entryCount}">End Date</label>
                        <input type="date" id="end-date-${entryCount}" name="end-date[]">
                        <label class="checkbox-label">
                            <input type="checkbox" name="current-job[]"> Currently working here
                        </label>
                    </div>
                    <div class="form-group full-width">
                        <label for="job-description-${entryCount}">Job Description</label>
                        <textarea id="job-description-${entryCount}" name="job-description[]" rows="4" placeholder="Describe your key responsibilities and achievements..."></textarea>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary remove-experience">Remove</button>
            `;
            
            container.appendChild(newEntry);
            
            // Add remove functionality
            newEntry.querySelector('.remove-experience').addEventListener('click', function() {
                newEntry.remove();
            });
        }

        function addEducationEntry() {
            const container = document.getElementById('education-container');
            const entryCount = container.children.length + 1;
            
            const newEntry = document.createElement('div');
            newEntry.className = 'education-entry';
            newEntry.innerHTML = `
                <div class="form-grid">
                    <div class="form-group">
                        <label for="degree-${entryCount}">Degree/Certificate *</label>
                        <input type="text" id="degree-${entryCount}" name="degree[]" placeholder="e.g. Bachelor of Science" required>
                    </div>
                    <div class="form-group">
                        <label for="field-${entryCount}">Field of Study *</label>
                        <input type="text" id="field-${entryCount}" name="field[]" placeholder="e.g. Computer Science" required>
                    </div>
                    <div class="form-group">
                        <label for="school-${entryCount}">School/Institution *</label>
                        <input type="text" id="school-${entryCount}" name="school[]" placeholder="e.g. University of Lagos" required>
                    </div>
                    <div class="form-group">
                        <label for="grad-year-${entryCount}">Graduation Year</label>
                        <input type="number" id="grad-year-${entryCount}" name="grad-year[]" min="1950" max="2030" placeholder="2023">
                    </div>
                    <div class="form-group">
                        <label for="gpa-${entryCount}">Grade/GPA (Optional)</label>
                        <input type="text" id="gpa-${entryCount}" name="gpa[]" placeholder="e.g. First Class, 3.8/4.0">
                    </div>
                </div>
                <button type="button" class="btn btn-secondary remove-education">Remove</button>
            `;
            
            container.appendChild(newEntry);
            
            // Add remove functionality
            newEntry.querySelector('.remove-education').addEventListener('click', function() {
                newEntry.remove();
            });
        }

        function addReferenceEntry() {
            const container = document.getElementById('references-container');
            const entryCount = container.children.length + 1;
            
            const newEntry = document.createElement('div');
            newEntry.className = 'reference-entry';
            newEntry.innerHTML = `
                <h4>Reference ${entryCount}</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="ref-name-${entryCount}">Full Name *</label>
                        <input type="text" id="ref-name-${entryCount}" name="ref-name[]" placeholder="e.g. Dr. John Adebayo" required>
                    </div>
                    <div class="form-group">
                        <label for="ref-title-${entryCount}">Job Title *</label>
                        <input type="text" id="ref-title-${entryCount}" name="ref-title[]" placeholder="e.g. Senior Manager" required>
                    </div>
                    <div class="form-group">
                        <label for="ref-company-${entryCount}">Company/Organization *</label>
                        <input type="text" id="ref-company-${entryCount}" name="ref-company[]" placeholder="e.g. ABC Technologies Ltd" required>
                    </div>
                    <div class="form-group">
                        <label for="ref-relationship-${entryCount}">Professional Relationship *</label>
                        <select id="ref-relationship-${entryCount}" name="ref-relationship[]" required>
                            <option value="">Select relationship</option>
                            <option value="direct-supervisor">Direct Supervisor</option>
                            <option value="manager">Manager</option>
                            <option value="colleague">Colleague</option>
                            <option value="client">Client</option>
                            <option value="mentor">Mentor</option>
                            <option value="hr-manager">HR Manager</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="ref-phone-${entryCount}">Phone Number *</label>
                        <input type="tel" id="ref-phone-${entryCount}" name="ref-phone[]" placeholder="+234" required>
                    </div>
                    <div class="form-group">
                        <label for="ref-email-${entryCount}">Email Address *</label>
                        <input type="email" id="ref-email-${entryCount}" name="ref-email[]" placeholder="reference@company.com" required>
                    </div>
                    <div class="form-group">
                        <label for="ref-years-${entryCount}">Years Known</label>
                        <input type="text" id="ref-years-${entryCount}" name="ref-years[]" placeholder="e.g. 3 years">
                    </div>
                    <div class="form-group">
                        <label for="ref-permission-${entryCount}">Permission Status</label>
                        <select id="ref-permission-${entryCount}" name="ref-permission[]">
                            <option value="granted">Permission granted</option>
                            <option value="pending">Permission pending</option>
                            <option value="not-asked">Not yet asked</option>
                        </select>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary remove-reference">Remove Reference</button>
            `;
            
            container.appendChild(newEntry);
            
            // Add remove functionality
            newEntry.querySelector('.remove-reference').addEventListener('click', function() {
                newEntry.remove();
            });
        }

        function toggleReferencesSection() {
            const referencesContainer = document.getElementById('references-container');
            const addReferenceBtn = document.querySelector('.add-reference');
            const isChecked = document.getElementById('references-available').checked;
            
            if (isChecked) {
                referencesContainer.style.display = 'block';
                addReferenceBtn.style.display = 'block';
            } else {
                referencesContainer.style.display = 'none';
                addReferenceBtn.style.display = 'none';
            }
        }

        function generatePreview() {
            const preview = document.getElementById('cvPreview');
            
            // Collect all form data
            cvData = collectFormData();
            
            // Generate CV HTML based on selected template
            setTimeout(() => {
                preview.innerHTML = generateCVHTML(cvData, selectedTemplate);
            }, 1500);
        }

        function collectFormData() {
            return {
                personal: {
                    firstName: document.getElementById('firstName').value,
                    lastName: document.getElementById('lastName').value,
                    email: document.getElementById('email').value,
                    phone: document.getElementById('phone').value,
                    address: document.getElementById('address').value,
                    summary: document.getElementById('professional-summary').value
                },
                experience: collectMultipleEntries('experience'),
                education: collectMultipleEntries('education'),
                skills: {
                    technical: document.getElementById('technical-skills').value,
                    soft: document.getElementById('soft-skills').value,
                    languages: document.getElementById('languages').value,
                    certifications: document.getElementById('certifications').value
                },
                references: {
                    includeReferences: document.getElementById('references-available').checked,
                    referenceList: collectReferences()
                }
            };
        }

        function collectMultipleEntries(type) {
            const container = document.getElementById(`${type}-container`);
            const entries = [];
            
            container.querySelectorAll(`.${type}-entry`).forEach(entry => {
                const formData = new FormData();
                entry.querySelectorAll('input, textarea').forEach(field => {
                    formData.append(field.name, field.value);
                });
                
                entries.push(Object.fromEntries(formData));
            });
            
            return entries;
        }

        function collectReferences() {
            const container = document.getElementById('references-container');
            const references = [];
            
            container.querySelectorAll('.reference-entry').forEach(entry => {
                const refData = {};
                entry.querySelectorAll('input, select').forEach(field => {
                    if (field.name && field.value) {
                        const fieldName = field.name.replace('[]', '');
                        refData[fieldName] = field.value;
                    }
                });
                
                // Only include references with at least name and contact info
                if (refData['ref-name'] && (refData['ref-phone'] || refData['ref-email'])) {
                    references.push(refData);
                }
            });
            
            return references;
        }

        function generateCVHTML(data, template) {
            // This is a simplified CV template generator
            return `
                <div class="cv-header">
                    <h1>${data.personal.firstName} ${data.personal.lastName}</h1>
                    <div class="contact-info">
                        <p>${data.personal.email} | ${data.personal.phone}</p>
                        <p>${data.personal.address}</p>
                    </div>
                </div>
                
                ${data.personal.summary ? `
                <div class="cv-section">
                    <h2>Professional Summary</h2>
                    <p>${data.personal.summary}</p>
                </div>
                ` : ''}
                
                <div class="cv-section">
                    <h2>Work Experience</h2>
                    ${data.experience.map(exp => `
                        <div class="experience-item">
                            <h3>${exp['job-title[]']} - ${exp['company[]']}</h3>
                            <p class="date-range">${exp['start-date[]']} - ${exp['end-date[]'] || 'Present'}</p>
                            <p>${exp['job-description[]']}</p>
                        </div>
                    `).join('')}
                </div>
                
                <div class="cv-section">
                    <h2>Education</h2>
                    ${data.education.map(edu => `
                        <div class="education-item">
                            <h3>${edu['degree[]']} in ${edu['field[]']}</h3>
                            <p>${edu['school[]']} - ${edu['grad-year[]']}</p>
                            ${edu['gpa[]'] ? `<p>Grade: ${edu['gpa[]']}</p>` : ''}
                        </div>
                    `).join('')}
                </div>
                
                <div class="cv-section">
                    <h2>Skills</h2>
                    ${data.skills.technical ? `<p><strong>Technical Skills:</strong> ${data.skills.technical}</p>` : ''}
                    ${data.skills.soft ? `<p><strong>Soft Skills:</strong> ${data.skills.soft}</p>` : ''}
                    ${data.skills.languages ? `<p><strong>Languages:</strong> ${data.skills.languages}</p>` : ''}
                    ${data.skills.certifications ? `<p><strong>Certifications:</strong> ${data.skills.certifications}</p>` : ''}
                </div>
                
                ${data.references && data.references.length > 0 ? `
                <div class="cv-section">
                    <h2>References</h2>
                    ${data.references.map(ref => `
                        <div class="reference-item">
                            <h3>${ref['ref-name']}</h3>
                            ${ref['ref-position'] && ref['ref-company'] ? `<p class="ref-title">${ref['ref-position']} at ${ref['ref-company']}</p>` : ''}
                            ${ref['ref-position'] && !ref['ref-company'] ? `<p class="ref-title">${ref['ref-position']}</p>` : ''}
                            ${!ref['ref-position'] && ref['ref-company'] ? `<p class="ref-title">${ref['ref-company']}</p>` : ''}
                            <div class="ref-contact">
                                ${ref['ref-phone'] ? `<span>📞 ${ref['ref-phone']}</span>` : ''}
                                ${ref['ref-email'] ? `<span>✉️ ${ref['ref-email']}</span>` : ''}
                            </div>
                            ${ref['ref-relationship'] ? `<p class="ref-relationship">Relationship: ${ref['ref-relationship']}</p>` : ''}
                        </div>
                    `).join('')}
                </div>
                ` : ''}
                
                <style>
                    .cv-header { text-align: center; margin-bottom: 2rem; }
                    .cv-header h1 { color: var(--primary); margin-bottom: 0.5rem; }
                    .cv-section { margin-bottom: 2rem; }
                    .cv-section h2 { color: var(--primary); border-bottom: 2px solid var(--primary); padding-bottom: 0.5rem; }
                    .experience-item, .education-item, .reference-item { margin-bottom: 1.5rem; }
                    .date-range { color: var(--text-secondary); font-style: italic; }
                    .reference-item h3 { color: var(--text-primary); margin-bottom: 0.25rem; }
                    .ref-title { color: var(--text-secondary); font-style: italic; margin-bottom: 0.5rem; }
                    .ref-contact { display: flex; gap: 1rem; margin-bottom: 0.5rem; }
                    .ref-contact span { background: var(--primary-light); padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85rem; }
                    .ref-relationship { color: var(--text-secondary); font-size: 0.9rem; }
                </style>
            `;
        }

        function downloadCV(format) {
            if (format === 'pdf') {
                // In a real application, this would generate a PDF
                alert('PDF download functionality would be implemented here.');
            } else if (format === 'docx') {
                // In a real application, this would generate a Word document
                alert('Word document download functionality would be implemented here.');
            }
        }

        function saveDraft() {
            cvData = collectFormData();
            localStorage.setItem('cvDraft', JSON.stringify(cvData));
            alert('CV draft saved locally!');
        }

        function autoSave() {
            if (document.getElementById('cvBuilderSection').style.display !== 'none') {
                cvData = collectFormData();
                localStorage.setItem('cvDraftAutoSave', JSON.stringify(cvData));
            }
        }

        // Service selection functions
        function showFreeAIBuilder() {
            // Hide service selection and show template selection
            document.querySelector('.service-options-section').style.display = 'none';
            document.querySelector('.templates-section').style.display = 'block';
            document.querySelector('.features-section').style.display = 'block';
            
            // Update hero subtitle for AI service
            document.querySelector('.hero-subtitle').textContent = 'Build a standout CV with our free AI-powered creator. Get noticed by top employers in Nigeria.';
            
            // Smooth scroll to templates
            document.querySelector('.templates-section').scrollIntoView({
                behavior: 'smooth'
            });
        }

        function showProfessionalBooking() {
            // Hide service selection and show professional booking
            document.querySelector('.service-options-section').style.display = 'none';
            document.querySelector('.templates-section').style.display = 'none';
            document.querySelector('.features-section').style.display = 'none';
            document.getElementById('professionalBookingSection').style.display = 'block';
            
            // Update hero subtitle for professional service
            document.querySelector('.hero-subtitle').textContent = 'Get a professionally crafted CV by our expert writers. Perfect for making a strong impression.';
            
            // Smooth scroll to booking
            document.getElementById('professionalBookingSection').scrollIntoView({
                behavior: 'smooth'
            });
        }

        function updateBookingSummary(packageCard) {
            const packageName = packageCard.querySelector('h4').textContent;
            const packagePrice = packageCard.dataset.price;
            
            document.getElementById('selected-package-name').textContent = packageName;
            document.getElementById('selected-package-price').textContent = '₦' + parseInt(packagePrice).toLocaleString();
            document.getElementById('total-amount').textContent = '₦' + parseInt(packagePrice).toLocaleString();
            
            // Enable payment button
            document.getElementById('proceed-to-payment').disabled = false;
        }

        function handleProfessionalBooking(event) {
            event.preventDefault();
            
            // Collect form data
            const formData = new FormData(event.target);
            const selectedPackage = document.querySelector('.package-card.selected');
            
            if (!selectedPackage) {
                alert('Please select a package first.');
                return;
            }
            
            // Get package details
            const packageName = selectedPackage.querySelector('h4').textContent;
            const packagePrice = selectedPackage.dataset.price;
            
            // Prepare booking data
            const bookingData = {
                package: packageName,
                price: packagePrice,
                clientName: formData.get('client-name'),
                clientEmail: formData.get('client-email'),
                clientPhone: formData.get('client-phone'),
                targetIndustry: formData.get('target-industry'),
                experienceLevel: formData.get('experience-level'),
                specialRequirements: formData.get('special-requirements'),
                preferredContact: formData.get('preferred-contact'),
                timestamp: new Date().toISOString()
            };
            
            // Store booking data for payment processing
            localStorage.setItem('professionalCVBooking', JSON.stringify(bookingData));
            
            // In a real implementation, this would redirect to payment gateway
            alert(`Booking Details:\n\nPackage: ${packageName}\nPrice: ₦${parseInt(packagePrice).toLocaleString()}\nClient: ${bookingData.clientName}\n\nRedirecting to payment...`);
            
            // Simulate payment process
            setTimeout(() => {
                alert('Payment successful! Our team will contact you within 30 minutes to assign your CV expert.\n\nYou will receive an email confirmation shortly.');
                
                // Reset form
                event.target.reset();
                selectedPackage.classList.remove('selected');
                document.getElementById('proceed-to-payment').disabled = true;
                document.getElementById('selected-package-name').textContent = 'No package selected';
                document.getElementById('selected-package-price').textContent = '₦0';
                document.getElementById('total-amount').textContent = '₦0';
            }, 2000);
        }

        // Load saved draft on page load
        window.addEventListener('load', function() {
            const savedDraft = localStorage.getItem('cvDraft');
            if (savedDraft) {
                // Load saved data - implementation would go here
                console.log('Saved draft available');
            }
        });
    </script>

    <?php include '../../includes/footer.php'; ?>
    
    <!-- Bottom Navigation for PWA -->
    <nav class="app-bottom-nav">
        <a href="../../index.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">🏠</div>
            <div class="app-bottom-nav-label">Home</div>
        </a>
        <a href="../jobs/browse.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">🔍</div>
            <div class="app-bottom-nav-label">Jobs</div>
        </a>
        <?php if (isLoggedIn() && isJobSeeker()): ?>
        <a href="../user/saved-jobs.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">❤️</div>
            <div class="app-bottom-nav-label">Saved</div>
        </a>
        <?php endif; ?>
        <a href="cv-creator.php" class="app-bottom-nav-item active">
            <div class="app-bottom-nav-icon">📄</div>
            <div class="app-bottom-nav-label">CV</div>
        </a>
        <?php if (isLoggedIn()): ?>
            <?php if (isJobSeeker()): ?>
        <a href="../user/dashboard.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">👤</div>
            <div class="app-bottom-nav-label">Profile</div>
        </a>
            <?php else: ?>
        <a href="../company/dashboard.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">🏢</div>
            <div class="app-bottom-nav-label">Company</div>
        </a>
            <?php endif; ?>
        <?php else: ?>
        <a href="../auth/login.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">👤</div>
            <div class="app-bottom-nav-label">Login</div>
        </a>
        <?php endif; ?>
    </nav>
</body>
</html>