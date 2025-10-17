<?php 
require_once '../../includes/functions.php';
require_once '../../config/database.php';
require_once '../../config/session.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is employer
if (!isLoggedIn() || !isEmployer()) {
    header('Location: ../auth/login-employer.php');
    exit;
}

$userId = getCurrentUserId();
$success_message = '';
$error_message = '';

// Double-check user is actually an employer in database
$stmt = $pdo->prepare("SELECT user_type FROM users WHERE id = ?");
$stmt->execute([$userId]);
$userType = $stmt->fetchColumn();

if ($userType !== 'employer') {
    header('Location: ../auth/login-employer.php?error=not_employer');
    exit;
}

// Handle form submission
if ($_POST && isset($_POST['submit_job'])) {
    try {
        // Validate required fields
        $required_fields = ['job_title', 'description', 'requirements', 'job_type', 'location'];
        $errors = [];
        
        foreach ($required_fields as $field) {
            if (empty(trim($_POST[$field] ?? ''))) {
                $field_name = ucwords(str_replace('_', ' ', str_replace('job_', '', $field)));
                $errors[] = $field_name . ' is required';
            }
        }
        
        // Additional validation
        if (strlen(trim($_POST['job_title'] ?? '')) < 5) {
            $errors[] = 'Job title must be at least 5 characters long';
        }
        
        if (strlen(trim($_POST['description'] ?? '')) < 50) {
            $errors[] = 'Job description must be at least 50 characters long';
        }
        
        if (empty($errors)) {
            // Generate slug from title
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $_POST['job_title']));
            $slug = trim($slug, '-');
            
            // Insert job into database
            $stmt = $pdo->prepare("
                INSERT INTO jobs (
                    employer_id, title, slug, job_type, employment_type,
                    description, requirements, responsibilities, benefits,
                    salary_min, salary_max, salary_currency, salary_period,
                    location_type, state, city, address,
                    experience_level, education_level, application_deadline,
                    application_email, company_name, STATUS, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            // Get company name from user session or database
            $company_stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
            $company_stmt->execute([$userId]);
            $company_info = $company_stmt->fetch();
            $company_name = ($company_info['first_name'] ?? '') . ' ' . ($company_info['last_name'] ?? 'Company');
            
            // Prepare data for insertion
            $job_data = [
                $userId,                                        // employer_id
                trim($_POST['job_title']),                     // title
                $slug,                                         // slug
                $_POST['job_type'],                            // job_type
                $_POST['employment_type'] ?? 'full_time',      // employment_type
                trim($_POST['description']),                   // description
                trim($_POST['requirements']),                  // requirements (now required)
                trim($_POST['responsibilities'] ?? ''),        // responsibilities (optional)
                trim($_POST['benefits'] ?? ''),               // benefits (optional)
                !empty($_POST['salary_min']) ? (int)$_POST['salary_min'] : null,  // salary_min
                !empty($_POST['salary_max']) ? (int)$_POST['salary_max'] : null,  // salary_max
                'NGN',                                         // salary_currency
                $_POST['salary_period'] ?? 'monthly',         // salary_period
                $_POST['location_type'] ?? 'onsite',          // location_type
                trim($_POST['location']),                      // state (using location field)
                trim($_POST['location']),                      // city (using location field)
                trim($_POST['job_address'] ?? ''),            // address
                $_POST['experience_level'] ?? 'entry',        // experience_level
                $_POST['education_level'] ?? 'any',           // education_level
                !empty($_POST['application_deadline']) ? $_POST['application_deadline'] : null,  // application_deadline
                !empty($_POST['application_email']) ? $_POST['application_email'] : null,        // application_email
                trim($company_name),                           // company_name
                'active'                                       // STATUS
            ];
            
            $result = $stmt->execute($job_data);
            
            if ($result) {
                $job_id = $pdo->lastInsertId();
                $success_message = "Job posted successfully! Job ID: #$job_id";
                
                // Clear form data after successful submission
                $_POST = [];
            } else {
                $error_message = "Failed to post job. Please try again.";
            }
        } else {
            $error_message = "Please fix the following errors: " . implode(', ', $errors);
        }
    } catch (PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
        error_log("Job posting PDO error: " . $e->getMessage());
        error_log("Job posting data: " . print_r($_POST, true));
    } catch (Exception $e) {
        $error_message = "System error: " . $e->getMessage();
        error_log("Job posting general error: " . $e->getMessage());
    }
}

// Get user info from session or database
$user = $_SESSION['user'] ?? [
    'name' => 'Company Name',
    'email' => 'company@example.com',
    'subscription_type' => 'free'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post a Job - FindAJob Nigeria</title>
    
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
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="../../assets/css/main.css">
    
    <style>
        :root {
            --primary: #dc2626;
            --primary-light: #fecaca;
            --primary-dark: #991b1b;
            --secondary: #64748b;
            --accent: #059669;
            --warning: #d97706;
            --background: #f8fafc;
            --surface: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #9ca3af;
            --border-color: #e2e8f0;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--background);
            color: var(--text-primary);
            margin: 0;
            line-height: 1.6;
        }
        
        .page-container {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--background) 0%, rgba(220, 38, 38, 0.02) 100%);
        }
    </style>
</head>
<body>
    <div class="page-container">
        <!-- Enhanced Header -->
        <?php include '../../includes/header.php'; ?>
        
        <!-- Main Content -->
        <main style="padding: 2rem 0; min-height: calc(100vh - 80px);">
            <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 1rem;">
                <!-- Page Header -->
                <div style="text-align: center; margin-bottom: 3rem;">
                    <div style="display: inline-flex; align-items: center; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: white; padding: 1rem 2rem; border-radius: 50px; margin-bottom: 1.5rem; box-shadow: 0 8px 25px rgba(220, 38, 38, 0.25);">
                        <i class="fas fa-plus-circle" style="font-size: 1.5rem; margin-right: 0.75rem;"></i>
                        <span style="font-size: 1.1rem; font-weight: 600;">Post New Job</span>
                    </div>
                    <h1 style="font-size: 2.5rem; font-weight: 800; color: var(--text-primary); margin: 0 0 1rem; line-height: 1.2;">
                        Find Your Next Great Hire
                    </h1>
                    <p style="font-size: 1.2rem; color: var(--text-secondary); max-width: 600px; margin: 0 auto; line-height: 1.6;">
                        Reach thousands of qualified professionals across Nigeria with our comprehensive job posting platform.
                    </p>
                </div>

                <!-- Progress Indicators -->
                <div style="display: flex; justify-content: center; margin-bottom: 3rem;">
                    <div class="progress-steps" style="display: flex; align-items: center; gap: 2rem;">
                        <div class="step active" data-step="1" style="display: flex; flex-direction: column; align-items: center;">
                            <div style="width: 48px; height: 48px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; margin-bottom: 0.5rem;">
                                1
                            </div>
                            <span style="font-size: 0.9rem; color: var(--primary); font-weight: 500;">Job Details</span>
                        </div>
                        <div style="width: 40px; height: 2px; background: var(--border-color); margin-top: -20px;"></div>
                        <div class="step" data-step="2" style="display: flex; flex-direction: column; align-items: center;">
                            <div style="width: 48px; height: 48px; border-radius: 50%; background: var(--border-color); color: var(--text-secondary); display: flex; align-items: center; justify-content: center; font-weight: 600; margin-bottom: 0.5rem;">
                                2
                            </div>
                            <span style="font-size: 0.9rem; color: var(--text-secondary);">Requirements</span>
                        </div>
                        <div style="width: 40px; height: 2px; background: var(--border-color); margin-top: -20px;"></div>
                        <div class="step" data-step="3" style="display: flex; flex-direction: column; align-items: center;">
                            <div style="width: 48px; height: 48px; border-radius: 50%; background: var(--border-color); color: var(--text-secondary); display: flex; align-items: center; justify-content: center; font-weight: 600; margin-bottom: 0.5rem;">
                                3
                            </div>
                            <span style="font-size: 0.9rem; color: var(--text-secondary);">Publish</span>
                        </div>
                    </div>
                </div>

                <!-- Form Container -->
                <div style="max-width: 800px; margin: 0 auto;">
                    <?php if ($success_message): ?>
                        <div style="background: linear-gradient(135deg, rgba(5, 150, 105, 0.1) 0%, rgba(5, 150, 105, 0.05) 100%); border: 2px solid rgba(5, 150, 105, 0.2); color: #059669; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; display: flex; align-items: center; gap: 1rem;">
                            <i class="fas fa-check-circle" style="font-size: 1.5rem;"></i>
                            <div>
                                <strong>Success!</strong><br>
                                <?php echo htmlspecialchars($success_message); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                        <div style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.05) 100%); border: 2px solid rgba(239, 68, 68, 0.2); color: #ef4444; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; display: flex; align-items: center; gap: 1rem;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 1.5rem;"></i>
                            <div>
                                <strong>Error!</strong><br>
                                <?php echo htmlspecialchars($error_message); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <form class="job-form" method="POST" style="background: var(--surface); border-radius: 20px; padding: 3rem; box-shadow: 0 20px 50px rgba(0,0,0,0.08); border: 1px solid rgba(0,0,0,0.05);">
                        
                        <!-- Step 1: Job Details -->
                        <div class="form-step" id="step-1">
                            <div class="form-section-header" style="text-align: center; margin-bottom: 3rem;">
                                <h3 style="font-size: 1.8rem; font-weight: 700; color: var(--text-primary); margin: 0 0 1rem; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-briefcase" style="margin-right: 0.75rem; color: var(--primary);"></i>
                                    Job Information
                                </h3>
                                <p style="color: var(--text-secondary); font-size: 1.1rem; margin: 0;">Tell us about the position you're hiring for</p>
                            </div>

                            <!-- Job Title -->
                            <div class="form-group" style="margin-bottom: 2rem;">
                                <label for="job-title" style="display: flex; align-items: center; font-weight: 600; color: var(--text-primary); margin-bottom: 0.75rem; font-size: 1rem;">
                                    <i class="fas fa-tag" style="margin-right: 0.5rem; color: var(--primary); font-size: 0.9rem;"></i>
                                    Job Title <span style="color: var(--primary);">*</span>
                                </label>
                                <input type="text" id="job-title" name="job_title" required 
                                       placeholder="e.g. Senior Software Developer, Digital Marketing Manager"
                                       style="width: 100%; padding: 1rem; border: 2px solid var(--border-color); border-radius: 12px; font-size: 1rem; transition: all 0.3s ease; background: var(--background);">
                            </div>

                            <!-- Form Grid -->
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-bottom: 2rem;">
                                <div class="form-group">
                                    <label for="job-type" style="display: flex; align-items: center; font-weight: 600; color: var(--text-primary); margin-bottom: 0.75rem; font-size: 1rem;">
                                        <i class="fas fa-clock" style="margin-right: 0.5rem; color: var(--primary); font-size: 0.9rem;"></i>
                                        Job Type <span style="color: var(--primary);">*</span>
                                    </label>
                                    <select id="job-type" name="job_type" required style="width: 100%; padding: 1rem; border: 2px solid var(--border-color); border-radius: 12px; font-size: 1rem; background: var(--background); color: var(--text-primary);">
                                        <option value="">Select job type</option>
                                        <option value="full-time">🕘 Full-time</option>
                                        <option value="part-time">🕐 Part-time</option>
                                        <option value="contract">📋 Contract</option>
                                        <option value="temporary">⏰ Temporary</option>
                                        <option value="internship">🎓 Internship</option>
                                        <option value="nysc">🏛️ NYSC Placement</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="category" style="display: flex; align-items: center; font-weight: 600; color: var(--text-primary); margin-bottom: 0.75rem; font-size: 1rem;">
                                        <i class="fas fa-layer-group" style="margin-right: 0.5rem; color: var(--primary); font-size: 0.9rem;"></i>
                                        Category <span style="color: var(--primary);">*</span>
                                    </label>
                                    <select id="category" name="category" required style="width: 100%; padding: 1rem; border: 2px solid var(--border-color); border-radius: 12px; font-size: 1rem; background: var(--background); color: var(--text-primary);">
                                        <option value="">Select category</option>
                                        <option value="technology">💻 Technology</option>
                                        <option value="banking">🏦 Banking & Finance</option>
                                        <option value="oil-gas">⛽ Oil & Gas</option>
                                        <option value="healthcare">🏥 Healthcare</option>
                                        <option value="education">📚 Education</option>
                                        <option value="marketing">📈 Marketing & Sales</option>
                                        <option value="engineering">⚙️ Engineering</option>
                                        <option value="government">🏛️ Government</option>
                                        <option value="agriculture">🌾 Agriculture</option>
                                        <option value="manufacturing">🏭 Manufacturing</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Location & Remote -->
                            <div style="display: grid; grid-template-columns: 1fr auto; gap: 1rem; align-items: end; margin-bottom: 2rem;">
                                <div class="form-group">
                                    <label for="location" style="display: flex; align-items: center; font-weight: 600; color: var(--text-primary); margin-bottom: 0.75rem; font-size: 1rem;">
                                        <i class="fas fa-map-marker-alt" style="margin-right: 0.5rem; color: var(--primary); font-size: 0.9rem;"></i>
                                        Location <span style="color: var(--primary);">*</span>
                                    </label>
                                    <select id="location" name="location" required style="width: 100%; padding: 1rem; border: 2px solid var(--border-color); border-radius: 12px; font-size: 1rem; background: var(--background); color: var(--text-primary);">
                                        <option value="">Select location</option>
                                        <option value="lagos">🏙️ Lagos</option>
                                        <option value="abuja">🏛️ Abuja (FCT)</option>
                                        <option value="port-harcourt">⛽ Port Harcourt</option>
                                        <option value="kano">🕌 Kano</option>
                                        <option value="ibadan">🌳 Ibadan</option>
                                        <option value="kaduna">🏭 Kaduna</option>
                                        <option value="benin">👑 Benin City</option>
                                        <option value="jos">🏔️ Jos</option>
                                        <option value="enugu">🌄 Enugu</option>
                                        <option value="remote">🌐 Remote</option>
                                    </select>
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.5rem; padding: 1rem; background: rgba(220, 38, 38, 0.05); border-radius: 12px; border: 2px solid rgba(220, 38, 38, 0.1);">
                                    <input type="checkbox" id="remote-friendly" name="remote_friendly" style="margin: 0;">
                                    <label for="remote-friendly" style="margin: 0; color: var(--primary); font-weight: 500; font-size: 0.9rem;">Remote OK</label>
                                </div>
                            </div>

                            <!-- Compensation Section -->
                            <div style="background: linear-gradient(135deg, rgba(5, 150, 105, 0.05) 0%, rgba(5, 150, 105, 0.02) 100%); padding: 2rem; border-radius: 16px; border: 2px solid rgba(5, 150, 105, 0.1); margin-bottom: 2rem;">
                                <h4 style="display: flex; align-items: center; font-size: 1.3rem; font-weight: 700; color: var(--text-primary); margin: 0 0 1.5rem;">
                                    <i class="fas fa-money-bill-wave" style="margin-right: 0.75rem; color: var(--accent);"></i>
                                    Compensation & Benefits
                                </h4>
                                
                                <!-- Salary Range -->
                                <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 1rem; align-items: end; margin-bottom: 1.5rem;">
                                    <div class="form-group">
                                        <label for="salary-min" style="display: block; font-weight: 600; color: var(--text-primary); margin-bottom: 0.5rem;">
                                            Minimum Salary
                                        </label>
                                        <div style="position: relative;">
                                            <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--accent); font-weight: 600;">₦</span>
                                            <input type="number" id="salary-min" name="salary_min" 
                                                   placeholder="200,000" min="0"
                                                   style="width: 100%; padding: 1rem 1rem 1rem 2.5rem; border: 2px solid var(--border-color); border-radius: 12px; font-size: 1rem; background: var(--surface);">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="salary-max" style="display: block; font-weight: 600; color: var(--text-primary); margin-bottom: 0.5rem;">
                                            Maximum Salary
                                        </label>
                                        <div style="position: relative;">
                                            <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--accent); font-weight: 600;">₦</span>
                                            <input type="number" id="salary-max" name="salary_max" 
                                                   placeholder="500,000" min="0"
                                                   style="width: 100%; padding: 1rem 1rem 1rem 2.5rem; border: 2px solid var(--border-color); border-radius: 12px; font-size: 1rem; background: var(--surface);">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="salary-period" style="display: block; font-weight: 600; color: var(--text-primary); margin-bottom: 0.5rem;">
                                            Period
                                        </label>
                                        <select id="salary-period" name="salary_period" style="width: 100%; padding: 1rem; border: 2px solid var(--border-color); border-radius: 12px; font-size: 1rem; background: var(--surface);">
                                            <option value="monthly">Monthly</option>
                                            <option value="annually">Annually</option>
                                            <option value="hourly">Hourly</option>
                                            <option value="project">Per Project</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Salary Options -->
                                <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
                                    <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 500;">
                                        <input type="checkbox" name="salary_negotiable" style="margin: 0;">
                                        <span>Salary is negotiable</span>
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 500;">
                                        <input type="checkbox" name="hide_salary" style="margin: 0;">
                                        <span>Hide salary from job listing</span>
                                    </label>
                                </div>

                                <!-- Benefits -->
                                <div class="form-group">
                                    <label for="benefits" style="display: flex; align-items: center; font-weight: 600; color: var(--text-primary); margin-bottom: 0.75rem;">
                                        <i class="fas fa-gift" style="margin-right: 0.5rem; color: var(--accent); font-size: 0.9rem;"></i>
                                        Benefits & Perks
                                    </label>
                                    <textarea id="benefits" name="benefits" rows="3"
                                           placeholder="e.g. Health insurance, Transport allowance, Remote work, Learning budget, Flexible hours, Career development opportunities..."
                                           style="width: 100%; padding: 1rem; border: 2px solid var(--border-color); border-radius: 12px; font-size: 1rem; background: var(--surface); resize: vertical; font-family: inherit; line-height: 1.6;"></textarea>
                                    <div style="margin-top: 0.5rem; display: flex; flex-wrap: wrap;">
                                        <span class="benefit-tag" onclick="addBenefit('Health Insurance')" style="padding: 0.25rem 0.75rem; background: rgba(5, 150, 105, 0.1); color: var(--accent); border-radius: 20px; font-size: 0.8rem; cursor: pointer; border: 1px solid rgba(5, 150, 105, 0.2); margin: 0.25rem 0.5rem 0.25rem 0;">+ Health Insurance</span>
                                        <span class="benefit-tag" onclick="addBenefit('Remote Work')" style="padding: 0.25rem 0.75rem; background: rgba(5, 150, 105, 0.1); color: var(--accent); border-radius: 20px; font-size: 0.8rem; cursor: pointer; border: 1px solid rgba(5, 150, 105, 0.2); margin: 0.25rem 0.5rem 0.25rem 0;">+ Remote Work</span>
                                        <span class="benefit-tag" onclick="addBenefit('Transport Allowance')" style="padding: 0.25rem 0.75rem; background: rgba(5, 150, 105, 0.1); color: var(--accent); border-radius: 20px; font-size: 0.8rem; cursor: pointer; border: 1px solid rgba(5, 150, 105, 0.2); margin: 0.25rem 0.5rem 0.25rem 0;">+ Transport Allowance</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Step Navigation -->
                            <div style="display: flex; justify-content: space-between; padding-top: 2rem; border-top: 2px solid var(--border-color);">
                                <div></div>
                                <button type="button" class="next-step-btn" onclick="nextStep()" style="
                                    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
                                    color: white;
                                    border: none;
                                    padding: 1rem 2rem;
                                    border-radius: 12px;
                                    font-weight: 600;
                                    font-size: 1rem;
                                    cursor: pointer;
                                    display: flex;
                                    align-items: center;
                                    gap: 0.5rem;
                                    transition: all 0.3s ease;
                                    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
                                ">
                                    Next: Requirements <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Step 2: Job Requirements -->
                        <div class="form-step" id="step-2" style="display: none;">
                            <div class="form-section-header" style="text-align: center; margin-bottom: 3rem;">
                                <h3 style="font-size: 1.8rem; font-weight: 700; color: var(--text-primary); margin: 0 0 1rem; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-clipboard-list" style="margin-right: 0.75rem; color: var(--primary);"></i>
                                    Job Requirements
                                </h3>
                                <p style="color: var(--text-secondary); font-size: 1.1rem; margin: 0;">Describe the role and what you're looking for</p>
                            </div>

                            <!-- Job Description -->
                            <div class="form-group" style="margin-bottom: 2rem;">
                                <label for="description" style="display: flex; align-items: center; font-weight: 600; color: var(--text-primary); margin-bottom: 0.75rem; font-size: 1rem;">
                                    <i class="fas fa-file-alt" style="margin-right: 0.5rem; color: var(--primary); font-size: 0.9rem;"></i>
                                    Job Description <span style="color: var(--primary);">*</span>
                                </label>
                                <textarea id="description" name="description" required rows="6"
                                          placeholder="Describe the job responsibilities, company culture, day-to-day activities, and what makes this role exciting..."
                                          style="width: 100%; padding: 1rem; border: 2px solid var(--border-color); border-radius: 12px; font-size: 1rem; resize: vertical; background: var(--background); font-family: inherit; line-height: 1.6;"></textarea>
                                <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.5rem;">
                                    <i class="fas fa-lightbulb" style="margin-right: 0.25rem;"></i>
                                    Tip: Be specific about daily tasks and company culture to attract the right candidates
                                </div>
                            </div>

                            <!-- Requirements -->
                            <div class="form-group" style="margin-bottom: 2rem;">
                                <label for="requirements" style="display: flex; align-items: center; font-weight: 600; color: var(--text-primary); margin-bottom: 0.75rem; font-size: 1rem;">
                                    <i class="fas fa-check-circle" style="margin-right: 0.5rem; color: var(--primary); font-size: 0.9rem;"></i>
                                    Requirements <span style="color: var(--primary);">*</span>
                                </label>
                                <textarea id="requirements" name="requirements" required rows="4"
                                          placeholder="List the required qualifications, skills, experience, and any mandatory certifications..."
                                          style="width: 100%; padding: 1rem; border: 2px solid var(--border-color); border-radius: 12px; font-size: 1rem; resize: vertical; background: var(--background); font-family: inherit; line-height: 1.6;"></textarea>
                            </div>

                            <!-- Responsibilities -->
                            <div class="form-group" style="margin-bottom: 2rem;">
                                <label for="responsibilities" style="display: flex; align-items: center; font-weight: 600; color: var(--text-primary); margin-bottom: 0.75rem; font-size: 1rem;">
                                    <i class="fas fa-tasks" style="margin-right: 0.5rem; color: var(--primary); font-size: 0.9rem;"></i>
                                    Key Responsibilities
                                </label>
                                <textarea id="responsibilities" name="responsibilities" rows="4"
                                          placeholder="Describe the main duties and responsibilities for this position..."
                                          style="width: 100%; padding: 1rem; border: 2px solid var(--border-color); border-radius: 12px; font-size: 1rem; resize: vertical; background: var(--background); font-family: inherit; line-height: 1.6;"></textarea>
                            </div>

                            <!-- Skills -->
                            <div class="form-group" style="margin-bottom: 2rem;">
                                <label for="skills" style="display: flex; align-items: center; font-weight: 600; color: var(--text-primary); margin-bottom: 0.75rem; font-size: 1rem;">
                                    <i class="fas fa-cogs" style="margin-right: 0.5rem; color: var(--primary); font-size: 0.9rem;"></i>
                                    Required Skills
                                </label>
                                <input type="text" id="skills" name="skills" 
                                       placeholder="e.g. JavaScript, React, Project Management, Communication, Problem Solving"
                                       style="width: 100%; padding: 1rem; border: 2px solid var(--border-color); border-radius: 12px; font-size: 1rem; background: var(--background);">
                                <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.5rem;">
                                    Separate skills with commas. Include both technical and soft skills.
                                </div>
                            </div>

                            <!-- Experience & Education Grid -->
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-bottom: 2rem;">
                                <div class="form-group">
                                    <label for="experience" style="display: flex; align-items: center; font-weight: 600; color: var(--text-primary); margin-bottom: 0.75rem; font-size: 1rem;">
                                        <i class="fas fa-user-tie" style="margin-right: 0.5rem; color: var(--primary); font-size: 0.9rem;"></i>
                                        Experience Level
                                    </label>
                                    <select id="experience" name="experience_level" style="width: 100%; padding: 1rem; border: 2px solid var(--border-color); border-radius: 12px; font-size: 1rem; background: var(--background);">
                                        <option value="">Select experience level</option>
                                        <option value="entry">🌱 Entry Level (0-2 years)</option>
                                        <option value="mid">💼 Mid Level (2-5 years)</option>
                                        <option value="senior">👨‍💼 Senior Level (5+ years)</option>
                                        <option value="executive">🎯 Executive Level</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="education" style="display: flex; align-items: center; font-weight: 600; color: var(--text-primary); margin-bottom: 0.75rem; font-size: 1rem;">
                                        <i class="fas fa-graduation-cap" style="margin-right: 0.5rem; color: var(--primary); font-size: 0.9rem;"></i>
                                        Education Level
                                    </label>
                                    <select id="education" name="education_level" style="width: 100%; padding: 1rem; border: 2px solid var(--border-color); border-radius: 12px; font-size: 1rem; background: var(--background);">
                                        <option value="">Select education level</option>
                                        <option value="ssce">📄 SSCE/WAEC</option>
                                        <option value="ond">📋 OND (Ordinary National Diploma)</option>
                                        <option value="hnd">📊 HND (Higher National Diploma)</option>
                                        <option value="bsc">🎓 Bachelor's Degree</option>
                                        <option value="msc">🎓 Master's Degree</option>
                                        <option value="phd">👨‍🎓 PhD</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Application Settings -->
                            <div style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(99, 102, 241, 0.02) 100%); padding: 2rem; border-radius: 16px; border: 2px solid rgba(99, 102, 241, 0.1); margin-bottom: 2rem;">
                                <h4 style="display: flex; align-items: center; font-size: 1.3rem; font-weight: 700; color: var(--text-primary); margin: 0 0 1.5rem;">
                                    <i class="fas fa-paper-plane" style="margin-right: 0.75rem; color: #6366f1;"></i>
                                    Application Settings
                                </h4>

                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">
                                    <div class="form-group">
                                        <label for="application-email" style="display: flex; align-items: center; font-weight: 600; color: var(--text-primary); margin-bottom: 0.75rem;">
                                            <i class="fas fa-envelope" style="margin-right: 0.5rem; color: #6366f1; font-size: 0.9rem;"></i>
                                            Application Email
                                        </label>
                                        <input type="email" id="application-email" name="application_email" 
                                               placeholder="careers@yourcompany.com" value="<?php echo $user['email']; ?>"
                                               style="width: 100%; padding: 1rem; border: 2px solid var(--border-color); border-radius: 12px; font-size: 1rem; background: var(--surface);">
                                    </div>

                                    <div class="form-group">
                                        <label for="application-deadline" style="display: flex; align-items: center; font-weight: 600; color: var(--text-primary); margin-bottom: 0.75rem;">
                                            <i class="fas fa-calendar-alt" style="margin-right: 0.5rem; color: #6366f1; font-size: 0.9rem;"></i>
                                            Application Deadline
                                        </label>
                                        <input type="date" id="application-deadline" name="application_deadline"
                                               style="width: 100%; padding: 1rem; border: 2px solid var(--border-color); border-radius: 12px; font-size: 1rem; background: var(--surface);">
                                    </div>
                                </div>

                                <!-- Application Options -->
                                <div style="margin-top: 1.5rem; display: flex; flex-wrap: wrap;">
                                    <label style="display: flex; align-items: center; font-weight: 500; margin-right: 1rem; margin-bottom: 0.5rem;">
                                        <input type="checkbox" name="cv_required" checked style="margin: 0 0.5rem 0 0;">
                                        <span>CV/Resume required</span>
                                    </label>
                                    <label style="display: flex; align-items: center; font-weight: 500; margin-right: 1rem; margin-bottom: 0.5rem;">
                                        <input type="checkbox" name="cover_letter_required" style="margin: 0 0.5rem 0 0;">
                                        <span>Cover letter required</span>
                                    </label>
                                    <label style="display: flex; align-items: center; font-weight: 500; margin-right: 1rem; margin-bottom: 0.5rem;">
                                        <input type="checkbox" name="portfolio_required" style="margin: 0 0.5rem 0 0;">
                                        <span>Portfolio required</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Step Navigation -->
                            <div style="display: flex; justify-content: space-between; padding-top: 2rem; border-top: 2px solid var(--border-color);">
                                <button type="button" class="prev-step-btn" onclick="prevStep()" style="
                                    background: var(--surface);
                                    color: var(--text-secondary);
                                    border: 2px solid var(--border-color);
                                    padding: 1rem 2rem;
                                    border-radius: 12px;
                                    font-weight: 600;
                                    font-size: 1rem;
                                    cursor: pointer;
                                    display: flex;
                                    align-items: center;
                                    gap: 0.5rem;
                                    transition: all 0.3s ease;
                                ">
                                    <i class="fas fa-arrow-left"></i> Back: Job Details
                                </button>
                                <button type="button" class="next-step-btn" onclick="nextStep()" style="
                                    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
                                    color: white;
                                    border: none;
                                    padding: 1rem 2rem;
                                    border-radius: 12px;
                                    font-weight: 600;
                                    font-size: 1rem;
                                    cursor: pointer;
                                    display: flex;
                                    align-items: center;
                                    gap: 0.5rem;
                                    transition: all 0.3s ease;
                                    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
                                ">
                                    Next: Publish <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Step 3: Publish Job -->
                        <div class="form-step" id="step-3" style="display: none;">
                            <div class="form-section-header" style="text-align: center; margin-bottom: 3rem;">
                                <h3 style="font-size: 1.8rem; font-weight: 700; color: var(--text-primary); margin: 0 0 1rem; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-rocket" style="margin-right: 0.75rem; color: var(--primary);"></i>
                                    Publish Your Job
                                </h3>
                                <p style="color: var(--text-secondary); font-size: 1.1rem; margin: 0;">Choose how you want to promote your job listing</p>
                            </div>

                            <!-- Boost Options -->
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-bottom: 3rem;">
                                <!-- Basic Posting -->
                                <div class="boost-card" onclick="selectBoost('free')" style="
                                    background: var(--surface);
                                    border: 3px solid var(--primary);
                                    border-radius: 20px;
                                    padding: 2rem;
                                    text-align: center;
                                    cursor: pointer;
                                    transition: all 0.3s ease;
                                    position: relative;
                                    box-shadow: 0 8px 25px rgba(220, 38, 38, 0.15);
                                ">
                                    <div style="position: absolute; top: -15px; left: 50%; transform: translateX(-50%); background: var(--primary); color: white; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                                        Selected
                                    </div>
                                    <div style="margin-bottom: 1.5rem;">
                                        <h4 style="font-size: 1.4rem; font-weight: 700; color: var(--text-primary); margin: 0 0 0.5rem;">
                                            Basic Posting
                                        </h4>
                                        <div style="font-size: 2rem; font-weight: 800; color: var(--accent);">FREE</div>
                                    </div>
                                    <ul style="list-style: none; padding: 0; margin: 0 0 2rem; text-align: left;">
                                        <li style="padding: 0.5rem 0; display: flex; align-items: center; color: var(--text-secondary);">
                                            <i class="fas fa-check" style="color: var(--accent); margin-right: 0.75rem; font-size: 0.9rem;"></i>
                                            Standard job listing
                                        </li>
                                        <li style="padding: 0.5rem 0; display: flex; align-items: center; color: var(--text-secondary);">
                                            <i class="fas fa-check" style="color: var(--accent); margin-right: 0.75rem; font-size: 0.9rem;"></i>
                                            30 days visibility
                                        </li>
                                        <li style="padding: 0.5rem 0; display: flex; align-items: center; color: var(--text-muted);">
                                            <i class="fas fa-times" style="color: var(--text-muted); margin-right: 0.75rem; font-size: 0.9rem;"></i>
                                            Featured placement
                                        </li>
                                        <li style="padding: 0.5rem 0; display: flex; align-items: center; color: var(--text-muted);">
                                            <i class="fas fa-times" style="color: var(--text-muted); margin-right: 0.75rem; font-size: 0.9rem;"></i>
                                            Social media promotion
                                        </li>
                                    </ul>
                                    <input type="radio" name="boost_type" value="free" id="boost-free" checked style="display: none;">
                                </div>

                                <!-- Premium Boost -->
                                <div class="boost-card" onclick="selectBoost('premium')" style="
                                    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
                                    color: white;
                                    border: 3px solid transparent;
                                    border-radius: 20px;
                                    padding: 2rem;
                                    text-align: center;
                                    cursor: pointer;
                                    transition: all 0.3s ease;
                                    position: relative;
                                    box-shadow: 0 12px 35px rgba(245, 158, 11, 0.25);
                                    transform: scale(1.05);
                                ">
                                    <div style="position: absolute; top: -15px; left: 50%; transform: translateX(-50%); background: white; color: var(--warning); padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                                        Recommended
                                    </div>
                                    <div style="margin-bottom: 1.5rem;">
                                        <h4 style="font-size: 1.4rem; font-weight: 700; margin: 0 0 0.5rem;">
                                            Premium Boost
                                        </h4>
                                        <div style="font-size: 2rem; font-weight: 800;">₦5,000</div>
                                    </div>
                                    <ul style="list-style: none; padding: 0; margin: 0 0 2rem; text-align: left;">
                                        <li style="padding: 0.5rem 0; display: flex; align-items: center; opacity: 0.95;">
                                            <i class="fas fa-check" style="margin-right: 0.75rem; font-size: 0.9rem;"></i>
                                            Featured placement
                                        </li>
                                        <li style="padding: 0.5rem 0; display: flex; align-items: center; opacity: 0.95;">
                                            <i class="fas fa-check" style="margin-right: 0.75rem; font-size: 0.9rem;"></i>
                                            60 days visibility
                                        </li>
                                        <li style="padding: 0.5rem 0; display: flex; align-items: center; opacity: 0.95;">
                                            <i class="fas fa-check" style="margin-right: 0.75rem; font-size: 0.9rem;"></i>
                                            Social media promotion
                                        </li>
                                        <li style="padding: 0.5rem 0; display: flex; align-items: center; opacity: 0.95;">
                                            <i class="fas fa-check" style="margin-right: 0.75rem; font-size: 0.9rem;"></i>
                                            Email to relevant candidates
                                        </li>
                                    </ul>
                                    <input type="radio" name="boost_type" value="premium" id="boost-premium" style="display: none;">
                                </div>

                                <!-- Super Boost -->
                                <div class="boost-card" onclick="selectBoost('super')" style="
                                    background: var(--surface);
                                    border: 3px solid var(--border-color);
                                    border-radius: 20px;
                                    padding: 2rem;
                                    text-align: center;
                                    cursor: pointer;
                                    transition: all 0.3s ease;
                                    position: relative;
                                    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
                                ">
                                    <div style="margin-bottom: 1.5rem;">
                                        <h4 style="font-size: 1.4rem; font-weight: 700; color: var(--text-primary); margin: 0 0 0.5rem;">
                                            Super Boost
                                        </h4>
                                        <div style="font-size: 2rem; font-weight: 800; color: var(--primary);">₦15,000</div>
                                    </div>
                                    <ul style="list-style: none; padding: 0; margin: 0 0 2rem; text-align: left;">
                                        <li style="padding: 0.5rem 0; display: flex; align-items: center; color: var(--text-secondary);">
                                            <i class="fas fa-check" style="color: var(--accent); margin-right: 0.75rem; font-size: 0.9rem;"></i>
                                            Top placement (homepage)
                                        </li>
                                        <li style="padding: 0.5rem 0; display: flex; align-items: center; color: var(--text-secondary);">
                                            <i class="fas fa-check" style="color: var(--accent); margin-right: 0.75rem; font-size: 0.9rem;"></i>
                                            90 days visibility
                                        </li>
                                        <li style="padding: 0.5rem 0; display: flex; align-items: center; color: var(--text-secondary);">
                                            <i class="fas fa-check" style="color: var(--accent); margin-right: 0.75rem; font-size: 0.9rem;"></i>
                                            Multi-platform promotion
                                        </li>
                                        <li style="padding: 0.5rem 0; display: flex; align-items: center; color: var(--text-secondary);">
                                            <i class="fas fa-check" style="color: var(--accent); margin-right: 0.75rem; font-size: 0.9rem;"></i>
                                            Priority support & analytics
                                        </li>
                                    </ul>
                                    <input type="radio" name="boost_type" value="super" id="boost-super" style="display: none;">
                                </div>
                            </div>

                            <!-- Final Actions -->
                            <div style="text-align: center; padding: 2rem; background: rgba(0,0,0,0.02); border-radius: 16px; margin-bottom: 2rem;">
                                <h4 style="margin: 0 0 1rem; color: var(--text-primary); font-weight: 600;">Ready to publish?</h4>
                                <p style="margin: 0 0 1.5rem; color: var(--text-secondary);">Your job will be reviewed and published within 24 hours for free postings, or immediately for boosted jobs.</p>
                                <div style="display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;">
                                    <button type="button" class="save-draft-btn" style="
                                        background: var(--surface);
                                        color: var(--text-secondary);
                                        border: 2px solid var(--border-color);
                                        padding: 1rem 2rem;
                                        border-radius: 12px;
                                        font-weight: 600;
                                        cursor: pointer;
                                        transition: all 0.3s ease;
                                        display: flex;
                                        align-items: center;
                                        gap: 0.5rem;
                                    ">
                                        <i class="fas fa-save"></i> Save as Draft
                                    </button>
                                    <button type="submit" name="submit_job" style="
                                        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
                                        color: white;
                                        border: none;
                                        padding: 1rem 2rem;
                                        border-radius: 12px;
                                        font-weight: 600;
                                        font-size: 1.1rem;
                                        cursor: pointer;
                                        transition: all 0.3s ease;
                                        box-shadow: 0 8px 25px rgba(220, 38, 38, 0.3);
                                        display: flex;
                                        align-items: center;
                                        gap: 0.75rem;
                                    ">
                                        <i class="fas fa-rocket"></i> Publish Job
                                    </button>
                                </div>
                            </div>

                            <!-- Step Navigation -->
                            <div style="display: flex; justify-content: space-between; padding-top: 2rem; border-top: 2px solid var(--border-color);">
                                <button type="button" class="prev-step-btn" onclick="prevStep()" style="
                                    background: var(--surface);
                                    color: var(--text-secondary);
                                    border: 2px solid var(--border-color);
                                    padding: 1rem 2rem;
                                    border-radius: 12px;
                                    font-weight: 600;
                                    font-size: 1rem;
                                    cursor: pointer;
                                    display: flex;
                                    align-items: center;
                                    gap: 0.5rem;
                                    transition: all 0.3s ease;
                                ">
                                    <i class="fas fa-arrow-left"></i> Back: Requirements
                                </button>
                                <div style="display: flex; align-items: center; color: var(--text-secondary); font-size: 0.9rem;">
                                    <i class="fas fa-check-circle" style="color: var(--accent); margin-right: 0.5rem;"></i>
                                    Ready to publish!
                                </div>
                            </div>
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

        // Multi-step form management
        let currentStep = 1;
        const totalSteps = 3;

        function showStep(step) {
            // Hide all steps
            for (let i = 1; i <= totalSteps; i++) {
                const stepElement = document.getElementById(`step-${i}`);
                if (stepElement) {
                    stepElement.style.display = 'none';
                }
            }
            
            // Show current step
            const currentStepElement = document.getElementById(`step-${step}`);
            if (currentStepElement) {
                currentStepElement.style.display = 'block';
            }
            
            // Update progress indicators
            updateProgressIndicators(step);
        }

        function updateProgressIndicators(activeStep) {
            const steps = document.querySelectorAll('.step');
            const lines = document.querySelectorAll('.progress-steps > div:nth-child(even)');
            
            steps.forEach((step, index) => {
                const stepNumber = index + 1;
                const stepCircle = step.querySelector('div');
                const stepLabel = step.querySelector('span');
                
                if (stepNumber <= activeStep) {
                    stepCircle.style.background = 'var(--primary)';
                    stepCircle.style.color = 'white';
                    stepLabel.style.color = 'var(--primary)';
                    stepLabel.style.fontWeight = '600';
                    step.classList.add('active');
                } else {
                    stepCircle.style.background = 'var(--border-color)';
                    stepCircle.style.color = 'var(--text-secondary)';
                    stepLabel.style.color = 'var(--text-secondary)';
                    stepLabel.style.fontWeight = '400';
                    step.classList.remove('active');
                }
            });
            
            // Update connection lines
            lines.forEach((line, index) => {
                if (index + 1 < activeStep) {
                    line.style.background = 'var(--primary)';
                } else {
                    line.style.background = 'var(--border-color)';
                }
            });
        }

        function nextStep() {
            if (validateCurrentStep() && currentStep < totalSteps) {
                currentStep++;
                showStep(currentStep);
                scrollToTop();
            }
        }

        function prevStep() {
            if (currentStep > 1) {
                currentStep--;
                showStep(currentStep);
                scrollToTop();
            }
        }

        function validateCurrentStep() {
            let isValid = true;
            const currentStepElement = document.getElementById(`step-${currentStep}`);
            
            if (currentStep === 1) {
                const requiredFields = ['job_title', 'job_type', 'category', 'location'];
                requiredFields.forEach(field => {
                    const input = currentStepElement.querySelector(`[name="${field}"]`);
                    if (input && !input.value.trim()) {
                        showFieldError(input, 'This field is required');
                        isValid = false;
                    } else if (input) {
                        clearFieldError(input);
                    }
                });
            } else if (currentStep === 2) {
                const requiredFields = ['description', 'requirements'];
                requiredFields.forEach(field => {
                    const input = currentStepElement.querySelector(`[name="${field}"]`);
                    if (input && !input.value.trim()) {
                        showFieldError(input, 'This field is required');
                        isValid = false;
                    } else if (input) {
                        clearFieldError(input);
                    }
                });
            }
            
            return isValid;
        }

        function showFieldError(input, message) {
            input.style.borderColor = 'var(--primary)';
            input.style.boxShadow = '0 0 0 3px rgba(220, 38, 38, 0.1)';
            
            // Remove existing error message
            const existingError = input.parentElement.querySelector('.error-message');
            if (existingError) {
                existingError.remove();
            }
            
            // Add error message
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.style.color = 'var(--primary)';
            errorDiv.style.fontSize = '0.85rem';
            errorDiv.style.marginTop = '0.5rem';
            errorDiv.style.display = 'flex';
            errorDiv.style.alignItems = 'center';
            errorDiv.innerHTML = `<i class="fas fa-exclamation-circle" style="margin-right: 0.5rem;"></i>${message}`;
            input.parentElement.appendChild(errorDiv);
        }

        function clearFieldError(input) {
            input.style.borderColor = 'var(--border-color)';
            input.style.boxShadow = 'none';
            
            const errorMessage = input.parentElement.querySelector('.error-message');
            if (errorMessage) {
                errorMessage.remove();
            }
        }

        function scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Boost selection functionality
        function selectBoost(type) {
            // Remove selection from all cards
            document.querySelectorAll('.boost-card').forEach(card => {
                card.style.transform = card.classList.contains('boost-card') && !card.innerHTML.includes('Premium Boost') ? 'scale(1)' : 'scale(1.05)';
                card.style.border = '3px solid var(--border-color)';
                card.style.boxShadow = '0 8px 25px rgba(0,0,0,0.1)';
                
                const badge = card.querySelector('div[style*="position: absolute"]');
                if (badge && !badge.innerHTML.includes('Recommended')) {
                    badge.style.display = 'none';
                }
            });
            
            // Highlight selected card
            const selectedCard = document.querySelector(`#boost-${type}`).closest('.boost-card');
            if (type !== 'premium') {
                selectedCard.style.transform = 'scale(1.05)';
                selectedCard.style.border = '3px solid var(--primary)';
                selectedCard.style.boxShadow = '0 12px 35px rgba(220, 38, 38, 0.25)';
            }
            
            // Show selected badge
            const badge = selectedCard.querySelector('div[style*="position: absolute"]');
            if (badge) {
                badge.style.display = 'block';
                if (type === 'free') {
                    badge.innerHTML = 'Selected';
                    badge.style.background = 'var(--primary)';
                } else if (type === 'super') {
                    badge.innerHTML = 'Selected';
                    badge.style.background = 'var(--primary)';
                }
            }
            
            // Update radio button
            document.getElementById(`boost-${type}`).checked = true;
        }

        // Benefit tag functionality
        function addBenefit(benefit) {
            const benefitsInput = document.getElementById('benefits');
            const currentValue = benefitsInput.value;
            
            if (currentValue) {
                benefitsInput.value = currentValue + ', ' + benefit;
            } else {
                benefitsInput.value = benefit;
            }
            
            // Animate the tag
            event.target.style.background = 'var(--accent)';
            event.target.style.color = 'white';
            setTimeout(() => {
                event.target.style.background = 'rgba(5, 150, 105, 0.1)';
                event.target.style.color = 'var(--accent)';
            }, 300);
        }

        // Form submission
        document.querySelector('.job-form').addEventListener('submit', function(e) {
            if (!validateCurrentStep()) {
                e.preventDefault();
                return;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Publishing...';
            submitBtn.disabled = true;
            
            // Allow form to submit normally to PHP backend
            // The form will be processed by the PHP code at the top of this file
        });

        // Save as draft
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('save-draft-btn')) {
                const formData = new FormData(document.querySelector('.job-form'));
                
                // Simulate saving
                e.target.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                setTimeout(() => {
                    alert('💾 Job saved as draft! You can complete it later from your dashboard.');
                    e.target.innerHTML = '<i class="fas fa-save"></i> Save as Draft';
                }, 1000);
            }
        });

        // Initialize form
        document.addEventListener('DOMContentLoaded', function() {
            showStep(1);
            
            // Add focus effects to form inputs
            const inputs = document.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.style.borderColor = 'var(--primary)';
                    this.style.boxShadow = '0 0 0 3px rgba(220, 38, 38, 0.1)';
                });
                
                input.addEventListener('blur', function() {
                    if (!this.value) {
                        this.style.borderColor = 'var(--border-color)';
                        this.style.boxShadow = 'none';
                    }
                });
            });
            
            // Set default application deadline (30 days from now)
            const deadlineInput = document.getElementById('application-deadline');
            if (deadlineInput) {
                const futureDate = new Date();
                futureDate.setDate(futureDate.getDate() + 30);
                deadlineInput.value = futureDate.toISOString().split('T')[0];
            }
        });
    </script>
</body>
</html>