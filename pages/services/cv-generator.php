<?php
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/constants.php';
require_once '../../includes/pro-features.php';

requireJobSeeker();

$userId = getCurrentUserId();

// Get user subscription
$subscription = getUserSubscription($pdo, $userId);
$isPro = $subscription['is_pro'];
$limits = getFeatureLimits($isPro);

// Get user profile data for pre-filling
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Get existing CV data if editing
$editingCvId = isset($_GET['edit']) ? intval($_GET['edit']) : null;
$cvData = null;

if ($editingCvId) {
    $stmt = $pdo->prepare("SELECT * FROM cvs WHERE id = ? AND user_id = ?");
    $stmt->execute([$editingCvId, $userId]);
    $cvData = $stmt->fetch();
    
    if ($cvData && $cvData['cv_data']) {
        $cvData['cv_data'] = json_decode($cvData['cv_data'], true);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI CV Generator - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .generator-container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .generator-layout {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 2rem;
            align-items: start;
        }

        /* Progress Steps */
        .progress-sidebar {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: sticky;
            top: 2rem;
        }

        .progress-step {
            display: flex;
            align-items: center;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .progress-step:hover {
            background: #f9fafb;
        }

        .progress-step.active {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
        }

        .progress-step.completed {
            background: #f0fdf4;
        }

        .step-number {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #e5e7eb;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        .progress-step.active .step-number {
            background: #3b82f6;
            color: white;
        }

        .progress-step.completed .step-number {
            background: #10b981;
            color: white;
        }

        .step-info {
            flex: 1;
        }

        .step-title {
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.25rem;
        }

        .step-desc {
            font-size: 0.85rem;
            color: #6b7280;
        }

        /* Form Content */
        .form-content {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            min-height: 600px;
        }

        .step-content {
            display: none;
        }

        .step-content.active {
            display: block;
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e5e7eb;
        }

        .form-header h2 {
            color: #111827;
            margin: 0 0 0.5rem 0;
            font-size: 1.75rem;
        }

        .form-header p {
            color: #6b7280;
            margin: 0;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
        }

        .form-group label .required {
            color: #dc2626;
            margin-left: 2px;
            font-weight: 700;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .ai-suggestion {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 0.5rem;
        }

        .ai-suggestion-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            color: #1e40af;
            font-weight: 600;
        }

        .ai-suggestion-text {
            color: #1e3a8a;
            line-height: 1.6;
        }

        .btn-ai {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: transform 0.2s;
        }

        .btn-ai:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-ai:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        /* Repeatable Sections */
        .repeatable-section {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            position: relative;
        }

        .repeatable-section .remove-btn {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: #ef4444;
            color: white;
            border: none;
            padding: 0.5rem;
            border-radius: 6px;
            cursor: pointer;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .add-more-btn {
            background: #10b981;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
        }

        .add-more-btn:hover {
            background: #059669;
        }

        /* Navigation Buttons */
        .form-navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid #e5e7eb;
        }

        .btn-nav {
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-prev {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
        }

        .btn-prev:hover {
            background: #e5e7eb;
        }

        .btn-next {
            background: #3b82f6;
            color: white;
            border: none;
        }

        .btn-next:hover {
            background: #2563eb;
        }

        /* Template Selection */
        .template-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .template-card {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }

        .template-card:hover {
            border-color: #3b82f6;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
            transform: translateY(-4px);
        }

        .template-card.selected {
            border-color: #3b82f6;
            background: #eff6ff;
        }

        .template-preview {
            width: 100%;
            height: 300px;
            background: #f9fafb;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .template-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .template-info h3 {
            margin: 0 0 0.5rem 0;
            color: #111827;
        }

        .template-info p {
            margin: 0;
            color: #6b7280;
            font-size: 0.9rem;
        }

        .template-badge {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            background: #10b981;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .loading-spinner {
            display: none;
            text-align: center;
            padding: 2rem;
        }

        .loading-spinner.active {
            display: block;
        }

        .spinner {
            border: 4px solid #f3f4f6;
            border-top: 4px solid #3b82f6;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 1024px) {
            .generator-layout {
                grid-template-columns: 1fr;
            }

            .progress-sidebar {
                position: relative;
                top: 0;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>

    <div class="generator-container">
        <div style="margin-bottom: 2rem;">
            <h1><i class="fas fa-magic"></i> AI-Powered CV Generator</h1>
            <p style="color: #6b7280;">Create a professional CV with AI assistance in minutes</p>
        </div>

        <!-- Pro Feature Info -->
        <?php if (!$isPro): ?>
            <div style="background: #f3f4f6; padding: 1rem 1.5rem; border-radius: 8px; margin-bottom: 2rem; border-left: 4px solid #6b7280; display: flex; align-items: center; gap: 1rem;">
                <i class="fas fa-info-circle" style="color: #6b7280; font-size: 1.25rem;"></i>
                <div style="flex: 1;">
                    <p style="margin: 0; color: #4b5563; font-weight: 600;">
                        ✨ Free AI CV Generator - Available to all users
                    </p>
                    <p style="margin: 0.25rem 0 0 0; color: #6b7280; font-size: 0.9rem;">
                        Create professional CVs with AI assistance. For expert-written CVs, check out our <a href="premium-cv.php" style="color: #dc2626; font-weight: 600;">Premium CV Service</a>
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <div class="generator-layout">
            <!-- Progress Sidebar -->
            <div class="progress-sidebar">
                <h3 style="margin: 0 0 1.5rem 0; color: #111827;">Progress</h3>
                
                <div class="progress-step active" data-step="1">
                    <div class="step-number">1</div>
                    <div class="step-info">
                        <div class="step-title">Personal Info</div>
                        <div class="step-desc">Basic details</div>
                    </div>
                </div>

                <div class="progress-step" data-step="2">
                    <div class="step-number">2</div>
                    <div class="step-info">
                        <div class="step-title">Professional Summary</div>
                        <div class="step-desc">About you</div>
                    </div>
                </div>

                <div class="progress-step" data-step="3">
                    <div class="step-number">3</div>
                    <div class="step-info">
                        <div class="step-title">Work Experience</div>
                        <div class="step-desc">Optional</div>
                    </div>
                </div>

                <div class="progress-step" data-step="4">
                    <div class="step-number">4</div>
                    <div class="step-info">
                        <div class="step-title">Education</div>
                        <div class="step-desc">Academic background</div>
                    </div>
                </div>

                <div class="progress-step" data-step="5">
                    <div class="step-number">5</div>
                    <div class="step-info">
                        <div class="step-title">Skills</div>
                        <div class="step-desc">Core competencies</div>
                    </div>
                </div>

                <div class="progress-step" data-step="6">
                    <div class="step-number">6</div>
                    <div class="step-info">
                        <div class="step-title">References</div>
                        <div class="step-desc">Professional refs</div>
                    </div>
                </div>

                <div class="progress-step" data-step="7">
                    <div class="step-number">7</div>
                    <div class="step-info">
                        <div class="step-title">Creative Template</div>
                        <div class="step-desc">Design & preview</div>
                    </div>
                </div>

                <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb;">
                    <a href="../user/cv-manager.php" class="btn btn-secondary" style="width: 100%; text-align: center;">
                        <i class="fas fa-arrow-left"></i> Back to CV Manager
                    </a>
                </div>
            </div>

            <!-- Form Content -->
            <div class="form-content">
                <form id="cvGeneratorForm">
                    <!-- Step 1: Personal Information -->
                    <div class="step-content active" data-step="1">
                        <div class="form-header">
                            <h2><i class="fas fa-user"></i> Personal Information</h2>
                            <p>Let's start with your basic information</p>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>First Name <span class="required">*</span></label>
                                <input type="text" name="first_name" required value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Last Name <span class="required">*</span></label>
                                <input type="text" name="last_name" required value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Professional Title <span class="required">*</span></label>
                            <input type="text" name="professional_title" required placeholder="e.g. Senior Software Engineer, Marketing Manager">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Email Address <span class="required">*</span></label>
                                <input type="email" name="email" required value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Phone Number <span class="required">*</span></label>
                                <input type="tel" name="phone" required value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Location <span class="required">*</span></label>
                            <input type="text" name="location" required placeholder="City, State/Country">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>LinkedIn Profile (optional)</label>
                                <input type="url" name="linkedin" placeholder="https://linkedin.com/in/yourprofile">
                            </div>
                            <div class="form-group">
                                <label>Portfolio/Website (optional)</label>
                                <input type="url" name="website" placeholder="https://yourwebsite.com">
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Professional Summary -->
                    <div class="step-content" data-step="2">
                        <div class="form-header">
                            <h2><i class="fas fa-file-alt"></i> Professional Summary</h2>
                            <p>Tell us about your professional background and career goals</p>
                        </div>

                        <div class="form-group">
                            <label>Years of Experience <span class="required">*</span></label>
                            <select name="years_experience" required>
                                <option value="">Select...</option>
                                <option value="0-1">0-1 years</option>
                                <option value="1-3">1-3 years</option>
                                <option value="3-5">3-5 years</option>
                                <option value="5-10">5-10 years</option>
                                <option value="10+">10+ years</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Industry/Field <span class="required">*</span></label>
                            <input type="text" name="industry" required placeholder="e.g. Technology, Healthcare, Finance">
                        </div>

                        <div class="form-group">
                            <label>Professional Summary <span class="required">*</span></label>
                            <textarea name="professional_summary" required placeholder="Write a brief professional summary (3-5 sentences) highlighting your key achievements, skills, and career objectives"></textarea>
                            
                            <button type="button" class="btn-ai" id="generateSummary">
                                <i class="fas fa-magic"></i> Generate with AI
                            </button>
                            
                            <div class="ai-suggestion" id="summaryAiSuggestion" style="display: none;">
                                <div class="ai-suggestion-header">
                                    <i class="fas fa-robot"></i>
                                    <span>AI Suggestion</span>
                                </div>
                                <div class="ai-suggestion-text" id="summaryAiText"></div>
                                <button type="button" class="btn btn-primary btn-sm" style="margin-top: 0.5rem;" onclick="useSuggestion('professional_summary', 'summaryAiText')">
                                    Use This
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Work Experience -->
                    <div class="step-content" data-step="3">
                        <div class="form-header">
                            <h2><i class="fas fa-briefcase"></i> Work Experience</h2>
                            <p>Add your employment history (start with most recent) - <strong>Skip this step if you're a fresh graduate</strong></p>
                        </div>

                        <div style="background: #fffbeb; border: 1px solid #fbbf24; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; margin: 0;">
                                <input type="checkbox" id="skipExperience" onchange="toggleExperienceSection()">
                                <span style="font-weight: 600;">I don't have work experience yet (Fresh Graduate / Career Starter)</span>
                            </label>
                        </div>

                        <div id="experienceContainer">
                            <div class="repeatable-section" data-index="0">
                                <div class="form-group">
                                    <label>Job Title</label>
                                    <input type="text" name="experience[0][title]" placeholder="e.g. Senior Software Engineer">
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Company Name</label>
                                        <input type="text" name="experience[0][company]">
                                    </div>
                                    <div class="form-group">
                                        <label>Location</label>
                                        <input type="text" name="experience[0][location]" placeholder="City, Country">
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Start Date</label>
                                        <input type="month" name="experience[0][start_date]">
                                    </div>
                                    <div class="form-group">
                                        <label>End Date</label>
                                        <input type="month" name="experience[0][end_date]" placeholder="Leave empty if current">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="experience[0][current]" onchange="toggleCurrentJob(0)">
                                        I currently work here
                                    </label>
                                </div>

                                <div class="form-group">
                                    <label>Key Responsibilities & Achievements</label>
                                    <textarea name="experience[0][description]" placeholder="• Led a team of 5 developers&#10;• Increased sales by 40%&#10;• Implemented new processes"></textarea>
                                    
                                    <button type="button" class="btn-ai" onclick="enhanceJobDescription(0, event)">
                                        <i class="fas fa-magic"></i> Enhance with AI
                                    </button>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="add-more-btn" id="addExperienceBtn" onclick="addExperience()">
                            <i class="fas fa-plus"></i> Add Another Position
                        </button>
                    </div>

                    <!-- Step 4: Education -->
                    <div class="step-content" data-step="4">
                        <div class="form-header">
                            <h2><i class="fas fa-graduation-cap"></i> Education</h2>
                            <p>Add your educational background</p>
                        </div>

                        <div id="educationContainer">
                            <div class="repeatable-section" data-index="0">
                                <div class="form-group">
                                    <label>Degree/Qualification <span class="required">*</span></label>
                                    <input type="text" name="education[0][degree]" required placeholder="e.g. Bachelor of Science in Computer Science">
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Institution <span class="required">*</span></label>
                                        <input type="text" name="education[0][institution]" required placeholder="University/College name">
                                    </div>
                                    <div class="form-group">
                                        <label>Location <span class="required">*</span></label>
                                        <input type="text" name="education[0][location]" required>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Start Year <span class="required">*</span></label>
                                        <input type="number" name="education[0][start_year]" required min="1950" max="2030">
                                    </div>
                                    <div class="form-group">
                                        <label>Graduation Year</label>
                                        <input type="number" name="education[0][end_year]" min="1950" max="2030" placeholder="Leave empty if current">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="education[0][current]" onchange="toggleCurrentStudy(0)">
                                        I am currently studying here
                                    </label>
                                </div>

                                <div class="form-group">
                                    <label>GPA/Grade (optional)</label>
                                    <input type="text" name="education[0][gpa]" placeholder="e.g. 3.8/4.0 or First Class">
                                </div>

                                <div class="form-group">
                                    <label>Achievements/Activities (optional)</label>
                                    <textarea name="education[0][description]" placeholder="Honors, awards, relevant coursework, clubs"></textarea>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="add-more-btn" onclick="addEducation()">
                            <i class="fas fa-plus"></i> Add Another Degree
                        </button>
                    </div>

                    <!-- Step 5: Skills -->
                    <div class="step-content" data-step="5">
                        <div class="form-header">
                            <h2><i class="fas fa-tools"></i> Skills & Competencies</h2>
                            <p>List your key skills and expertise</p>
                        </div>

                        <div class="form-group">
                            <label>Job Role for AI Suggestions</label>
                            <input type="text" id="skillsJobRole" placeholder="e.g. Data Analyst, Frontend Developer">
                            <button type="button" class="btn-ai" style="margin-top: 0.5rem;" id="suggestSkills">
                                <i class="fas fa-magic"></i> Suggest Skills for This Role
                            </button>
                        </div>

                        <div class="form-group">
                            <label>Technical Skills <span class="required">*</span></label>
                            <textarea name="technical_skills" required placeholder="e.g. Python, JavaScript, React, SQL, AWS, Docker (separate with commas)"></textarea>
                        </div>

                        <div class="form-group">
                            <label>Soft Skills <span class="required">*</span></label>
                            <textarea name="soft_skills" required placeholder="e.g. Leadership, Communication, Problem Solving, Team Collaboration (separate with commas)"></textarea>
                        </div>

                        <div class="form-group">
                            <label>Languages <span class="required">*</span></label>
                            <textarea name="languages" required placeholder="e.g. English (Native), Spanish (Fluent), French (Intermediate)"></textarea>
                        </div>

                        <div class="form-group">
                            <label>Certifications (optional)</label>
                            <textarea name="certifications" placeholder="e.g. AWS Certified Solutions Architect, PMP, Google Analytics"></textarea>
                        </div>
                    </div>

                    <!-- Step 6: References -->
                    <div class="step-content" data-step="6">
                        <div class="form-header">
                            <h2><i class="fas fa-user-friends"></i> Professional References</h2>
                            <p>Add references who can vouch for your work (optional but recommended)</p>
                        </div>

                        <div class="form-group">
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" id="includeReferences" name="include_references" checked style="width: auto;">
                                Include references section in CV
                            </label>
                            <small style="color: #6b7280; margin-left: 1.5rem;">Uncheck if you prefer to provide references upon request</small>
                        </div>

                        <div id="referencesContainer" style="margin-top: 1.5rem;">
                            <!-- Reference 1 -->
                            <div class="repeatable-section" data-index="0">
                                <h4 style="color: #374151; margin-bottom: 1rem;">Reference 1</h4>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Full Name</label>
                                        <input type="text" name="references[0][name]" placeholder="e.g. Dr. John Adebayo">
                                    </div>
                                    <div class="form-group">
                                        <label>Job Title</label>
                                        <input type="text" name="references[0][title]" placeholder="e.g. Senior Manager">
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Company/Organization</label>
                                        <input type="text" name="references[0][company]" placeholder="e.g. ABC Technologies Ltd">
                                    </div>
                                    <div class="form-group">
                                        <label>Relationship</label>
                                        <select name="references[0][relationship]">
                                            <option value="">Select...</option>
                                            <option value="Direct Supervisor">Direct Supervisor</option>
                                            <option value="Manager">Manager</option>
                                            <option value="Colleague">Colleague</option>
                                            <option value="Client">Client</option>
                                            <option value="Mentor">Mentor</option>
                                            <option value="HR Manager">HR Manager</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Phone Number</label>
                                        <input type="tel" name="references[0][phone]" placeholder="+234 xxx xxx xxxx">
                                    </div>
                                    <div class="form-group">
                                        <label>Email Address</label>
                                        <input type="email" name="references[0][email]" placeholder="john.adebayo@company.com">
                                    </div>
                                </div>
                            </div>

                            <!-- Reference 2 -->
                            <div class="repeatable-section" data-index="1" style="margin-top: 2rem;">
                                <h4 style="color: #374151; margin-bottom: 1rem;">Reference 2</h4>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Full Name</label>
                                        <input type="text" name="references[1][name]" placeholder="e.g. Mrs. Sarah Okafor">
                                    </div>
                                    <div class="form-group">
                                        <label>Job Title</label>
                                        <input type="text" name="references[1][title]" placeholder="e.g. Team Lead">
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Company/Organization</label>
                                        <input type="text" name="references[1][company]" placeholder="e.g. XYZ Solutions">
                                    </div>
                                    <div class="form-group">
                                        <label>Relationship</label>
                                        <select name="references[1][relationship]">
                                            <option value="">Select...</option>
                                            <option value="Direct Supervisor">Direct Supervisor</option>
                                            <option value="Manager">Manager</option>
                                            <option value="Colleague">Colleague</option>
                                            <option value="Client">Client</option>
                                            <option value="Mentor">Mentor</option>
                                            <option value="HR Manager">HR Manager</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Phone Number</label>
                                        <input type="tel" name="references[1][phone]" placeholder="+234 xxx xxx xxxx">
                                    </div>
                                    <div class="form-group">
                                        <label>Email Address</label>
                                        <input type="email" name="references[1][email]" placeholder="sarah.okafor@company.com">
                                    </div>
                                </div>

                                <button type="button" class="btn btn-secondary" onclick="removeReference(this)" style="margin-top: 1rem;">
                                    <i class="fas fa-times"></i> Remove Reference
                                </button>
                            </div>
                        </div>

                        <button type="button" id="addReferenceBtn" class="btn btn-outline" style="margin-top: 1.5rem;">
                            <i class="fas fa-plus"></i> Add Another Reference
                        </button>

                        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 1rem; margin-top: 1.5rem;">
                            <p style="margin: 0 0 0.5rem 0; font-weight: 600; color: #1e40af;">💡 Reference Tips:</p>
                            <ul style="margin: 0; padding-left: 1.25rem; color: #1e3a8a; font-size: 0.9rem;">
                                <li>Always ask permission before listing someone as a reference</li>
                                <li>Choose references who can speak positively about your work</li>
                                <li>Include a mix of supervisors and colleagues if possible</li>
                                <li>Provide current and accurate contact information</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Step 7: Template Selection -->
                    <div class="step-content" data-step="7">
                        <div class="form-header">
                            <h2><i class="fas fa-palette"></i> Your Creative Template</h2>
                            <p>Professional creative design for your CV</p>
                        </div>

                        <div class="template-grid">
                            <div class="template-card selected" data-template="creative">
                                <div class="template-badge">RECOMMENDED</div>
                                <div class="template-preview">
                                    <i class="fas fa-paint-brush" style="font-size: 4rem; color: #8b5cf6;"></i>
                                </div>
                                <div class="template-info">
                                    <h3>Creative</h3>
                                    <p>Eye-catching design for creative professionals and designers</p>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="template" id="selectedTemplate" value="creative">

                        <div class="form-group" style="margin-top: 2rem;">
                            <label>CV Title <span class="required">*</span></label>
                            <input type="text" name="cv_title" required placeholder="e.g. Senior Developer CV - Tech Companies 2025">
                        </div>

                        <div class="loading-spinner" id="generatingSpinner">
                            <div class="spinner"></div>
                            <p>Generating your professional CV...</p>
                        </div>
                    </div>

                    <!-- Navigation -->
                    <div class="form-navigation">
                        <button type="button" class="btn-nav btn-prev" id="prevBtn" onclick="changeStep(-1)" style="display: none;">
                            <i class="fas fa-arrow-left"></i> Previous
                        </button>
                        
                        <div></div>

                        <button type="button" class="btn-nav btn-next" id="nextBtn" onclick="changeStep(1)">
                            Next <i class="fas fa-arrow-right"></i>
                        </button>

                        <button type="button" class="btn-nav btn-next" id="generateBtn" onclick="generateCV()" style="display: none;">
                            <i class="fas fa-magic"></i> Generate CV
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let currentStep = 1;
        const totalSteps = 7;
        let experienceCount = 1;
        let educationCount = 1;
        let referenceCount = 2;

        // Step Navigation
        function changeStep(direction) {
            const newStep = currentStep + direction;
            
            if (newStep < 1 || newStep > totalSteps) return;
            
            // Validate current step before moving forward
            if (direction > 0 && !validateStep(currentStep)) {
                return;
            }
            
            // Hide current step
            document.querySelector(`.step-content[data-step="${currentStep}"]`).classList.remove('active');
            document.querySelector(`.progress-step[data-step="${currentStep}"]`).classList.remove('active');
            document.querySelector(`.progress-step[data-step="${currentStep}"]`).classList.add('completed');
            
            // Show new step
            currentStep = newStep;
            document.querySelector(`.step-content[data-step="${currentStep}"]`).classList.add('active');
            document.querySelector(`.progress-step[data-step="${currentStep}"]`).classList.add('active');
            
            // Update buttons
            document.getElementById('prevBtn').style.display = currentStep === 1 ? 'none' : 'inline-flex';
            document.getElementById('nextBtn').style.display = currentStep === totalSteps ? 'none' : 'inline-flex';
            document.getElementById('generateBtn').style.display = currentStep === totalSteps ? 'inline-flex' : 'none';
            
            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Step validation
        function validateStep(step) {
            // Special handling for Work Experience step (step 3)
            if (step === 3) {
                const skipExperience = document.getElementById('skipExperience');
                if (skipExperience && skipExperience.checked) {
                    // Skip validation if user has no experience
                    return true;
                }
            }
            
            const stepContent = document.querySelector(`.step-content[data-step="${step}"]`);
            const requiredFields = stepContent.querySelectorAll('[required]');
            
            for (let field of requiredFields) {
                if (!field.value.trim()) {
                    field.focus();
                    alert('Please fill in all required fields before continuing.');
                    return false;
                }
            }
            
            return true;
        }

        // Click on progress step
        document.querySelectorAll('.progress-step').forEach(step => {
            step.addEventListener('click', function() {
                const targetStep = parseInt(this.dataset.step);
                if (targetStep < currentStep || this.classList.contains('completed')) {
                    // Allow going back to previous/completed steps
                    document.querySelector(`.step-content[data-step="${currentStep}"]`).classList.remove('active');
                    document.querySelector(`.progress-step[data-step="${currentStep}"]`).classList.remove('active');
                    
                    currentStep = targetStep;
                    document.querySelector(`.step-content[data-step="${currentStep}"]`).classList.add('active');
                    document.querySelector(`.progress-step[data-step="${currentStep}"]`).classList.add('active');
                    
                    document.getElementById('prevBtn').style.display = currentStep === 1 ? 'none' : 'inline-flex';
                    document.getElementById('nextBtn').style.display = currentStep === totalSteps ? 'none' : 'inline-flex';
                    document.getElementById('generateBtn').style.display = currentStep === totalSteps ? 'inline-flex' : 'none';
                }
            });
        });

        // Template Selection
        document.querySelectorAll('.template-card').forEach(card => {
            card.addEventListener('click', function() {
                document.querySelectorAll('.template-card').forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                document.getElementById('selectedTemplate').value = this.dataset.template;
            });
        });

        // Toggle Work Experience Section
        function toggleExperienceSection() {
            const skipCheckbox = document.getElementById('skipExperience');
            const experienceContainer = document.getElementById('experienceContainer');
            const addBtn = document.getElementById('addExperienceBtn');
            const inputs = experienceContainer.querySelectorAll('input, textarea');
            
            if (skipCheckbox.checked) {
                // Hide and disable all experience fields
                experienceContainer.style.opacity = '0.4';
                experienceContainer.style.pointerEvents = 'none';
                addBtn.style.display = 'none';
                
                inputs.forEach(input => {
                    input.disabled = true;
                    input.removeAttribute('required');
                });
            } else {
                // Show and enable all experience fields
                experienceContainer.style.opacity = '1';
                experienceContainer.style.pointerEvents = 'auto';
                addBtn.style.display = 'inline-flex';
                
                inputs.forEach(input => {
                    input.disabled = false;
                });
            }
        }

        // Add/Remove Experience
        function addExperience() {
            const container = document.getElementById('experienceContainer');
            const newSection = container.querySelector('.repeatable-section').cloneNode(true);
            
            newSection.dataset.index = experienceCount;
            newSection.querySelectorAll('input, textarea').forEach(input => {
                input.name = input.name.replace('[0]', `[${experienceCount}]`);
                input.value = '';
                if (input.type === 'checkbox') input.checked = false;
            });
            
            // Update enhance button onclick with new index and event
            const enhanceBtn = newSection.querySelector('.btn-ai');
            if (enhanceBtn) {
                enhanceBtn.setAttribute('onclick', `enhanceJobDescription(${experienceCount}, event)`);
            }
            
            // Update toggle checkbox
            const currentCheckbox = newSection.querySelector('input[type="checkbox"]');
            if (currentCheckbox) {
                currentCheckbox.setAttribute('onchange', `toggleCurrentJob(${experienceCount})`);
            }
            
            newSection.innerHTML = `
                <button type="button" class="remove-btn" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            ` + newSection.innerHTML;
            
            container.appendChild(newSection);
            experienceCount++;
        }

        function toggleCurrentJob(index) {
            const checkbox = document.querySelector(`input[name="experience[${index}][current]"]`);
            const endDateInput = document.querySelector(`input[name="experience[${index}][end_date]"]`);
            
            if (checkbox.checked) {
                endDateInput.disabled = true;
                endDateInput.value = '';
                endDateInput.placeholder = 'Present';
            } else {
                endDateInput.disabled = false;
                endDateInput.placeholder = 'Leave empty if current';
            }
        }

        function toggleCurrentStudy(index) {
            const checkbox = document.querySelector(`input[name="education[${index}][current]"]`);
            const endYearInput = document.querySelector(`input[name="education[${index}][end_year]"]`);
            
            if (checkbox.checked) {
                endYearInput.disabled = true;
                endYearInput.value = '';
                endYearInput.placeholder = 'Present';
                endYearInput.removeAttribute('required');
            } else {
                endYearInput.disabled = false;
                endYearInput.placeholder = 'Leave empty if current';
            }
        }

        // Add/Remove Education
        function addEducation() {
            const container = document.getElementById('educationContainer');
            const newSection = container.querySelector('.repeatable-section').cloneNode(true);
            
            newSection.dataset.index = educationCount;
            newSection.querySelectorAll('input, textarea').forEach(input => {
                input.name = input.name.replace('[0]', `[${educationCount}]`);
                input.value = '';
            });
            
            // Update currently studying checkbox handler
            const currentCheckbox = newSection.querySelector('input[name="education[0][current]"]');
            if (currentCheckbox) {
                currentCheckbox.name = `education[${educationCount}][current]`;
                currentCheckbox.setAttribute('onchange', `toggleCurrentStudy(${educationCount})`);
                currentCheckbox.checked = false;
            }
            
            newSection.innerHTML = `
                <button type="button" class="remove-btn" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            ` + newSection.innerHTML;
            
            container.appendChild(newSection);
            educationCount++;
        }

        // Add/Remove References
        function addReference() {
            const container = document.getElementById('referencesContainer');
            const newSection = document.createElement('div');
            newSection.className = 'repeatable-section';
            newSection.dataset.index = referenceCount;
            newSection.style.marginTop = '2rem';
            
            newSection.innerHTML = `
                <h4 style="color: #374151; margin-bottom: 1rem;">Reference ${referenceCount + 1}</h4>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="references[${referenceCount}][name]" placeholder="e.g. Dr. John Adebayo">
                    </div>
                    <div class="form-group">
                        <label>Job Title</label>
                        <input type="text" name="references[${referenceCount}][title]" placeholder="e.g. Senior Manager">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Company/Organization</label>
                        <input type="text" name="references[${referenceCount}][company]" placeholder="e.g. ABC Technologies Ltd">
                    </div>
                    <div class="form-group">
                        <label>Relationship</label>
                        <select name="references[${referenceCount}][relationship]">
                            <option value="">Select...</option>
                            <option value="Direct Supervisor">Direct Supervisor</option>
                            <option value="Manager">Manager</option>
                            <option value="Colleague">Colleague</option>
                            <option value="Client">Client</option>
                            <option value="Mentor">Mentor</option>
                            <option value="HR Manager">HR Manager</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" name="references[${referenceCount}][phone]" placeholder="+234 xxx xxx xxxx">
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="references[${referenceCount}][email]" placeholder="john.adebayo@company.com">
                    </div>
                </div>

                <button type="button" class="btn btn-secondary" onclick="removeReference(this)" style="margin-top: 1rem;">
                    <i class="fas fa-times"></i> Remove Reference
                </button>
            `;
            
            container.appendChild(newSection);
            referenceCount++;
        }

        function removeReference(btn) {
            const section = btn.closest('.repeatable-section');
            section.remove();
        }

        // Toggle References Section
        function toggleReferencesSection() {
            const checkbox = document.getElementById('includeReferences');
            const container = document.getElementById('referencesContainer');
            const addBtn = document.getElementById('addReferenceBtn');
            
            if (checkbox.checked) {
                container.style.display = 'block';
                addBtn.style.display = 'inline-flex';
            } else {
                container.style.display = 'none';
                addBtn.style.display = 'none';
            }
        }

        // Add event listeners
        document.getElementById('addReferenceBtn').addEventListener('click', addReference);
        document.getElementById('includeReferences').addEventListener('change', toggleReferencesSection);

        // AI Features - Dynamic Summary Generator
        document.getElementById('generateSummary').addEventListener('click', function() {
            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
            
            const industry = document.querySelector('input[name="industry"]').value.toLowerCase();
            const experience = document.querySelector('select[name="years_experience"]').value;
            const title = document.querySelector('input[name="professional_title"]').value;
            
            // Simulate AI generation with variety
            setTimeout(() => {
                const suggestion = generateDynamicSummary(title, experience, industry);
                
                document.getElementById('summaryAiText').textContent = suggestion;
                document.getElementById('summaryAiSuggestion').style.display = 'block';
                
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-magic"></i> Generate with AI';
            }, 1500);
        });

        // Dynamic Professional Summary Generator
        function generateDynamicSummary(title, experience, industry) {
            // Check if fresh graduate
            const isFreshGraduate = experience === '0-1';
            
            // Opening phrases (varied)
            const openings = isFreshGraduate ? [
                `Ambitious and eager ${title}`,
                `Enthusiastic ${title} graduate`,
                `Motivated recent graduate and aspiring ${title}`,
                `Passionate ${title}`,
                `Dedicated ${title} with fresh perspective`,
                `Goal-driven ${title}`,
                `Energetic and committed ${title}`,
                `Recent graduate pursuing a career as ${title}`
            ] : [
                `Dynamic and results-oriented ${title}`,
                `Highly motivated ${title}`,
                `Accomplished ${title}`,
                `Experienced ${title}`,
                `Strategic ${title}`,
                `Innovative ${title}`,
                `Dedicated ${title}`,
                `Performance-driven ${title}`,
                `Goal-focused ${title}`,
                `Proactive ${title}`
            ];

            // Experience descriptions
            const experienceDescriptions = isFreshGraduate ? [
                `with strong academic foundation and eagerness to learn`,
                `seeking to apply theoretical knowledge in practical settings`,
                `with relevant coursework and project experience`,
                `equipped with up-to-date academic training`,
                `ready to contribute fresh ideas and dedication`
            ] : [
                `with ${experience} of progressive experience`,
                `bringing ${experience} of proven expertise`,
                `with ${experience} of demonstrated success`,
                `offering ${experience} of professional excellence`,
                `with a strong track record spanning ${experience}`
            ];

            // Industry-specific strengths
            const industryStrengths = {
                'technology': [
                    'delivering innovative technology solutions and driving digital transformation',
                    'implementing scalable software solutions and optimizing system performance',
                    'leading technical teams and fostering a culture of continuous innovation',
                    'architecting robust solutions and enhancing user experiences through technology',
                    'developing cutting-edge applications and streamlining business processes'
                ],
                'healthcare': [
                    'improving patient outcomes and ensuring clinical excellence',
                    'managing healthcare operations and maintaining regulatory compliance',
                    'advancing quality care initiatives and promoting evidence-based practices',
                    'coordinating patient care programs and optimizing healthcare delivery',
                    'implementing health information systems and improving care coordination'
                ],
                'finance': [
                    'driving financial performance and maximizing shareholder value',
                    'managing complex financial portfolios and mitigating investment risks',
                    'providing strategic financial guidance and ensuring fiscal responsibility',
                    'analyzing market trends and developing data-driven investment strategies',
                    'optimizing financial operations and enhancing profitability'
                ],
                'marketing': [
                    'developing strategic marketing campaigns and driving brand awareness',
                    'increasing customer engagement and maximizing ROI on marketing initiatives',
                    'creating compelling content strategies and building strong market presence',
                    'leveraging digital marketing tools and analyzing consumer behavior',
                    'executing integrated marketing programs and growing market share'
                ],
                'education': [
                    'fostering student success and creating engaging learning environments',
                    'implementing innovative teaching methodologies and enhancing curriculum',
                    'mentoring students and colleagues while promoting academic excellence',
                    'developing educational programs and assessing learning outcomes',
                    'utilizing educational technology and promoting inclusive learning'
                ],
                'sales': [
                    'exceeding sales targets and building lasting client relationships',
                    'driving revenue growth and expanding market penetration',
                    'developing sales strategies and managing high-performing teams',
                    'negotiating complex deals and closing high-value accounts',
                    'identifying new business opportunities and maximizing customer retention'
                ],
                'engineering': [
                    'designing innovative engineering solutions and optimizing technical processes',
                    'managing complex projects and ensuring quality standards',
                    'implementing best practices and driving operational excellence',
                    'solving complex technical challenges and improving system efficiency',
                    'leading engineering teams and delivering projects on time and budget'
                ],
                'default': [
                    'driving organizational success through strategic planning and execution',
                    'delivering exceptional results and exceeding performance expectations',
                    'leading teams to achieve ambitious goals and maintain high standards',
                    'implementing best practices and fostering continuous improvement',
                    'managing complex projects and ensuring stakeholder satisfaction'
                ]
            };

            // Key competencies (varied)
            const competencies = [
                'Proven track record of leadership, collaboration, and problem-solving',
                'Strong analytical skills with a strategic mindset and attention to detail',
                'Excellent communication abilities and stakeholder management experience',
                'Demonstrated expertise in team leadership and cross-functional collaboration',
                'Known for adaptability, innovation, and delivering under pressure',
                'Strong project management skills with a focus on results and quality',
                'Expertise in process optimization and change management',
                'Skilled in data analysis, strategic planning, and decision-making'
            ];

            // Closing statements
            const closings = [
                'Committed to driving growth and achieving organizational objectives.',
                'Passionate about excellence and continuous professional development.',
                'Eager to leverage expertise to contribute to organizational success.',
                'Dedicated to making meaningful impact and creating value.',
                'Focused on innovation, quality, and sustainable results.',
                'Ready to take on new challenges and deliver exceptional outcomes.',
                'Seeking to apply skills and experience to drive positive change.',
                'Committed to fostering collaboration and achieving mutual success.'
            ];

            // Find industry strengths or use default
            let strengthsList = industryStrengths['default'];
            for (let key in industryStrengths) {
                if (industry.includes(key)) {
                    strengthsList = industryStrengths[key];
                    break;
                }
            }

            // Randomly select components
            const opening = openings[Math.floor(Math.random() * openings.length)];
            const expDesc = experienceDescriptions[Math.floor(Math.random() * experienceDescriptions.length)];
            const strength = strengthsList[Math.floor(Math.random() * strengthsList.length)];
            const competency = competencies[Math.floor(Math.random() * competencies.length)];
            const closing = closings[Math.floor(Math.random() * closings.length)];

            // Construct the summary
            return `${opening} ${expDesc} in ${strength}. ${competency}. ${closing}`;
        }

        function useSuggestion(fieldName, suggestionId) {
            const suggestionText = document.getElementById(suggestionId).textContent;
            document.querySelector(`[name="${fieldName}"]`).value = suggestionText;
        }

        function enhanceJobDescription(index, event) {
            const description = document.querySelector(`textarea[name="experience[${index}][description]"]`);
            const title = document.querySelector(`input[name="experience[${index}][title]"]`).value;
            const company = document.querySelector(`input[name="experience[${index}][company]"]`).value;
            
            if (!description.value.trim()) {
                alert('Please enter some basic responsibilities first, then AI will enhance them with action verbs and quantifiable achievements.');
                return;
            }

            if (!title) {
                alert('Please enter your job title first.');
                return;
            }

            // Show loading state
            const enhanceBtn = event.target.closest('button');
            const originalText = enhanceBtn.innerHTML;
            enhanceBtn.disabled = true;
            enhanceBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enhancing...';

            // Simulate AI enhancement with actual improvements
            setTimeout(() => {
                const enhanced = enhanceJobDescriptionText(description.value, title, company);
                description.value = enhanced;
                
                enhanceBtn.disabled = false;
                enhanceBtn.innerHTML = originalText;
                
                // Show success message
                const successMsg = document.createElement('div');
                successMsg.style.cssText = 'background: #d1fae5; color: #065f46; padding: 10px; border-radius: 6px; margin-top: 10px; font-size: 0.9rem;';
                successMsg.innerHTML = '<i class="fas fa-check-circle"></i> Description enhanced with action verbs and impact metrics!';
                description.parentElement.appendChild(successMsg);
                
                setTimeout(() => successMsg.remove(), 3000);
            }, 1500);
        }

        function enhanceJobDescriptionText(originalText, jobTitle, company) {
            // Action verbs by category
            const actionVerbs = {
                leadership: ['Led', 'Directed', 'Managed', 'Coordinated', 'Supervised', 'Mentored', 'Guided', 'Spearheaded', 'Orchestrated'],
                achievement: ['Achieved', 'Exceeded', 'Delivered', 'Accomplished', 'Attained', 'Secured', 'Generated', 'Produced'],
                improvement: ['Improved', 'Enhanced', 'Optimized', 'Streamlined', 'Increased', 'Reduced', 'Accelerated', 'Strengthened'],
                creation: ['Developed', 'Created', 'Designed', 'Implemented', 'Built', 'Established', 'Launched', 'Pioneered'],
                analysis: ['Analyzed', 'Evaluated', 'Assessed', 'Investigated', 'Researched', 'Identified', 'Determined'],
                communication: ['Presented', 'Communicated', 'Collaborated', 'Negotiated', 'Facilitated', 'Consulted']
            };

            // Quantifiable metrics examples
            const metrics = ['by 40%', 'by 25%', 'resulting in 30% efficiency gain', 'saving $50K annually', 
                           'across 5+ departments', 'for 100+ stakeholders', 'within 6 months', 
                           'exceeding targets by 35%', 'reducing costs by 20%', 'increasing revenue by 45%'];

            // Split into lines
            const lines = originalText.split('\n').filter(line => line.trim());
            const enhanced = [];

            lines.forEach((line, idx) => {
                let enhancedLine = line.trim();
                
                // Remove existing bullets
                enhancedLine = enhancedLine.replace(/^[•\-\*]\s*/, '');
                
                // Get a random action verb category
                const categories = Object.keys(actionVerbs);
                const randomCategory = categories[Math.floor(Math.random() * categories.length)];
                const verbList = actionVerbs[randomCategory];
                const actionVerb = verbList[Math.floor(Math.random() * verbList.length)];
                
                // Check if line already starts with strong action verb
                const startsWithActionVerb = Object.values(actionVerbs)
                    .flat()
                    .some(verb => enhancedLine.toLowerCase().startsWith(verb.toLowerCase()));
                
                if (!startsWithActionVerb) {
                    // Replace weak verbs with strong ones
                    enhancedLine = enhancedLine
                        .replace(/^(was responsible for|responsible for|did|made|helped|worked on)/i, actionVerb)
                        .replace(/^(handled|managed to|tried to)/i, actionVerb);
                    
                    // If still no action verb at start, prepend one
                    if (!/^[A-Z][a-z]+ed/.test(enhancedLine)) {
                        enhancedLine = `${actionVerb} ${enhancedLine.charAt(0).toLowerCase()}${enhancedLine.slice(1)}`;
                    }
                }
                
                // Add metrics if line doesn't have any numbers
                if (!/\d/.test(enhancedLine) && idx < 3) {
                    const metric = metrics[Math.floor(Math.random() * metrics.length)];
                    // Add metric before the period or at the end
                    if (enhancedLine.endsWith('.')) {
                        enhancedLine = enhancedLine.slice(0, -1) + ' ' + metric + '.';
                    } else {
                        enhancedLine += ' ' + metric;
                    }
                }
                
                // Ensure proper capitalization
                enhancedLine = enhancedLine.charAt(0).toUpperCase() + enhancedLine.slice(1);
                
                // Ensure it ends properly
                if (!enhancedLine.match(/[.!]$/)) {
                    enhancedLine += '.';
                }
                
                enhanced.push('• ' + enhancedLine);
            });

            // Add a leadership/impact line if only 1-2 bullets
            if (enhanced.length < 3) {
                const impactStatements = [
                    `• Collaborated with cross-functional teams to deliver projects on time and within budget.`,
                    `• Recognized for exceptional performance and contribution to team success.`,
                    `• Mentored junior team members and contributed to knowledge sharing initiatives.`,
                    `• Maintained high quality standards while meeting aggressive deadlines.`
                ];
                enhanced.push(impactStatements[Math.floor(Math.random() * impactStatements.length)]);
            }

            return enhanced.join('\n');
        }

        document.getElementById('suggestSkills').addEventListener('click', function() {
            const jobRole = document.getElementById('skillsJobRole').value;
            if (!jobRole) {
                alert('Please enter a job role first');
                return;
            }
            
            // Simulate skill suggestions
            const skillSuggestions = {
                'Data Analyst': {
                    technical: 'Python, SQL, Excel, Tableau, Power BI, R, Statistical Analysis, Data Visualization, ETL',
                    soft: 'Analytical Thinking, Problem Solving, Attention to Detail, Communication, Critical Thinking'
                },
                'Frontend Developer': {
                    technical: 'JavaScript, React, HTML5, CSS3, TypeScript, Redux, Git, Responsive Design, REST APIs',
                    soft: 'Creativity, Collaboration, Time Management, Adaptability, Problem Solving'
                },
                'Project Manager': {
                    technical: 'Agile/Scrum, JIRA, MS Project, Risk Management, Budget Management, Stakeholder Management',
                    soft: 'Leadership, Communication, Negotiation, Decision Making, Team Building'
                }
            };
            
            const suggestions = skillSuggestions[jobRole] || skillSuggestions['Data Analyst'];
            
            if (confirm('Apply AI-suggested skills for ' + jobRole + '?')) {
                document.querySelector('[name="technical_skills"]').value = suggestions.technical;
                document.querySelector('[name="soft_skills"]').value = suggestions.soft;
            }
        });

        // Generate CV
        function generateCV() {
            if (!validateStep(6)) return;
            
            document.getElementById('generatingSpinner').classList.add('active');
            document.getElementById('generateBtn').disabled = true;
            
            const formData = new FormData(document.getElementById('cvGeneratorForm'));
            
            fetch('../../api/generate-cv.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirect to preview page for download
                    window.location.href = '../user/cv-preview.php';
                } else {
                    const errorMsg = data.error || 'Failed to generate CV';
                    const details = data.details ? '\n\nDetails: ' + data.details : '';
                    alert('Error: ' + errorMsg + details);
                    console.error('CV Generation Error:', data);
                    document.getElementById('generatingSpinner').classList.remove('active');
                    document.getElementById('generateBtn').disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while generating your CV. Check console for details.');
                document.getElementById('generatingSpinner').classList.remove('active');
                document.getElementById('generateBtn').disabled = false;
            });
        }
    </script>
</body>
</html>
