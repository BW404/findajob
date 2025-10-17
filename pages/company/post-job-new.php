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
$job_id = null;

// Double-check user is actually an employer in database
try {
    $stmt = $pdo->prepare("SELECT user_type, first_name, last_name, email FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userInfo = $stmt->fetch();

    if (!$userInfo || $userInfo['user_type'] !== 'employer') {
        header('Location: ../auth/login-employer.php?error=not_employer');
        exit;
    }
} catch (PDOException $e) {
    error_log("User verification error: " . $e->getMessage());
    $error_message = "System error. Please try again.";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_job'])) {
    try {
        // Comprehensive validation
        $errors = [];
        
        // Required fields validation
        $required_fields = [
            'job_title' => 'Job Title',
            'description' => 'Job Description', 
            'requirements' => 'Requirements',
            'job_type' => 'Job Type',
            'location' => 'Location'
        ];
        
        foreach ($required_fields as $field => $label) {
            if (empty(trim($_POST[$field] ?? ''))) {
                $errors[] = $label . ' is required';
            }
        }
        
        // Length validations
        if (!empty($_POST['job_title']) && strlen(trim($_POST['job_title'])) < 5) {
            $errors[] = 'Job title must be at least 5 characters';
        }
        
        if (!empty($_POST['description']) && strlen(trim($_POST['description'])) < 50) {
            $errors[] = 'Job description must be at least 50 characters';
        }
        
        if (!empty($_POST['requirements']) && strlen(trim($_POST['requirements'])) < 10) {
            $errors[] = 'Requirements must be at least 10 characters';
        }
        
        // Salary validation
        if (!empty($_POST['salary_min']) && !empty($_POST['salary_max'])) {
            $salary_min = (int)$_POST['salary_min'];
            $salary_max = (int)$_POST['salary_max'];
            if ($salary_min > $salary_max) {
                $errors[] = 'Minimum salary cannot be higher than maximum salary';
            }
        }
        
        // Email validation if provided
        if (!empty($_POST['application_email']) && !filter_var($_POST['application_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid application email address';
        }
        
        // If no errors, proceed with job creation
        if (empty($errors)) {
            // Generate unique slug
            $base_slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', trim($_POST['job_title'])));
            $base_slug = trim($base_slug, '-');
            
            // Check if slug exists and make it unique
            $slug = $base_slug;
            $counter = 1;
            while (true) {
                $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE slug = ?");
                $check_stmt->execute([$slug]);
                if ($check_stmt->fetchColumn() == 0) {
                    break;
                }
                $slug = $base_slug . '-' . $counter;
                $counter++;
            }
            
            // Get company name
            $company_name = trim(($userInfo['first_name'] ?? '') . ' ' . ($userInfo['last_name'] ?? ''));
            if (empty(trim($company_name))) {
                $company_name = 'Employer Company';
            }
            
            // Prepare job data
            $job_data = [
                'employer_id' => $userId,
                'title' => trim($_POST['job_title']),
                'slug' => $slug,
                'job_type' => $_POST['job_type'],
                'employment_type' => $_POST['employment_type'] ?? 'full_time',
                'description' => trim($_POST['description']),
                'requirements' => trim($_POST['requirements']),
                'responsibilities' => trim($_POST['responsibilities'] ?? ''),
                'benefits' => trim($_POST['benefits'] ?? ''),
                'salary_min' => !empty($_POST['salary_min']) ? (int)$_POST['salary_min'] : null,
                'salary_max' => !empty($_POST['salary_max']) ? (int)$_POST['salary_max'] : null,
                'salary_currency' => 'NGN',
                'salary_period' => $_POST['salary_period'] ?? 'monthly',
                'location_type' => $_POST['location_type'] ?? 'onsite',
                'state' => $_POST['location'],
                'city' => $_POST['location'],  // Using same as state for now
                'address' => trim($_POST['job_address'] ?? ''),
                'experience_level' => $_POST['experience_level'] ?? 'entry',
                'education_level' => $_POST['education_level'] ?? 'any',
                'application_deadline' => !empty($_POST['application_deadline']) ? $_POST['application_deadline'] : null,
                'application_email' => !empty($_POST['application_email']) ? $_POST['application_email'] : null,
                'application_url' => trim($_POST['application_url'] ?? ''),
                'company_name' => $company_name,
                'is_featured' => 0,
                'is_urgent' => isset($_POST['is_urgent']) ? 1 : 0,
                'is_remote_friendly' => isset($_POST['remote_friendly']) ? 1 : 0,
                'views_count' => 0,
                'applications_count' => 0,
                'STATUS' => 'active'  // Jobs go live immediately - no admin approval needed
            ];
            
            // Insert job into database
            $sql = "INSERT INTO jobs (
                employer_id, title, slug, job_type, employment_type,
                description, requirements, responsibilities, benefits,
                salary_min, salary_max, salary_currency, salary_period,
                location_type, state, city, address,
                experience_level, education_level, application_deadline,
                application_email, application_url, company_name,
                is_featured, is_urgent, is_remote_friendly,
                views_count, applications_count, STATUS, created_at, updated_at
            ) VALUES (
                :employer_id, :title, :slug, :job_type, :employment_type,
                :description, :requirements, :responsibilities, :benefits,
                :salary_min, :salary_max, :salary_currency, :salary_period,
                :location_type, :state, :city, :address,
                :experience_level, :education_level, :application_deadline,
                :application_email, :application_url, :company_name,
                :is_featured, :is_urgent, :is_remote_friendly,
                :views_count, :applications_count, :STATUS, NOW(), NOW()
            )";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($job_data);
            
            if ($result) {
                $job_id = $pdo->lastInsertId();
                $success_message = "Job posted successfully! Your job is now live and visible to candidates.";
                
                // Log successful job posting
                error_log("Job posted successfully: ID $job_id, Title: " . $job_data['title'] . ", Employer: $userId");
                
                // Clear form data after successful submission
                $_POST = [];
            } else {
                $error_message = "Failed to post job. Please try again.";
                error_log("Job insertion failed: " . print_r($stmt->errorInfo(), true));
            }
        } else {
            $error_message = "Please fix the following errors:\n• " . implode("\n• ", $errors);
        }
        
    } catch (PDOException $e) {
        $error_message = "Database error occurred. Please try again.";
        error_log("Job posting PDO error: " . $e->getMessage());
        error_log("Posted data: " . print_r($_POST, true));
    } catch (Exception $e) {
        $error_message = "System error occurred. Please try again.";
        error_log("Job posting general error: " . $e->getMessage());
    }
}

// Get user info for display
$user_display_name = trim(($userInfo['first_name'] ?? '') . ' ' . ($userInfo['last_name'] ?? ''));
if (empty($user_display_name)) {
    $user_display_name = $userInfo['email'] ?? 'Employer';
}
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
            --border-color: #e2e8f0;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--background) 0%, #f1f5f9 100%);
            color: var(--text-primary);
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .header {
            background: var(--surface);
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .job-form {
            background: var(--surface);
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        
        label {
            display: block;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 1rem;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 1rem;
            background: var(--background);
            transition: all 0.2s ease;
            box-sizing: border-box;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
        }
        
        textarea {
            resize: vertical;
            font-family: inherit;
            line-height: 1.6;
        }
        
        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 38, 38, 0.3);
        }
        
        .btn-secondary {
            background: var(--secondary);
            color: white;
        }
        
        .success-message {
            background: rgba(5, 150, 105, 0.1);
            border: 2px solid rgba(5, 150, 105, 0.2);
            color: var(--accent);
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        
        .error-message {
            background: rgba(220, 38, 38, 0.1);
            border: 2px solid rgba(220, 38, 38, 0.2);
            color: var(--primary);
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            white-space: pre-line;
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--border-color);
        }
        
        .required {
            color: var(--primary);
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: space-between;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid var(--border-color);
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1 style="margin: 0 0 0.5rem 0; color: var(--primary);">
                <i class="fas fa-briefcase"></i> Post a Job
            </h1>
            <p style="margin: 0; color: var(--text-secondary);">
                Welcome, <?php echo htmlspecialchars($user_display_name); ?>. Create a job posting that will go live immediately.
            </p>
        </div>
        
        <!-- Success Message -->
        <?php if ($success_message): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <strong>Success!</strong> <?php echo htmlspecialchars($success_message); ?>
                <?php if ($job_id): ?>
                    <br><br>
                    <a href="dashboard.php" class="btn btn-primary" style="margin-top: 1rem;">
                        <i class="fas fa-tachometer-alt"></i> View Dashboard
                    </a>
                    <a href="../jobs/browse.php" class="btn btn-secondary" style="margin-top: 1rem; margin-left: 1rem;">
                        <i class="fas fa-search"></i> View Job Listing
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Error Message -->
        <?php if ($error_message): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Job Posting Form -->
        <form method="POST" class="job-form">
            <!-- Basic Information -->
            <div class="section-title">
                <i class="fas fa-info-circle"></i> Basic Information
            </div>
            
            <div class="form-group">
                <label for="job_title">
                    <i class="fas fa-briefcase"></i> Job Title <span class="required">*</span>
                </label>
                <input type="text" id="job_title" name="job_title" required 
                       value="<?php echo htmlspecialchars($_POST['job_title'] ?? ''); ?>"
                       placeholder="e.g. Senior Software Developer">
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="job_type">
                        <i class="fas fa-clock"></i> Job Type <span class="required">*</span>
                    </label>
                    <select id="job_type" name="job_type" required>
                        <option value="">Select job type</option>
                        <option value="permanent" <?php echo ($_POST['job_type'] ?? '') === 'permanent' ? 'selected' : ''; ?>>Permanent</option>
                        <option value="contract" <?php echo ($_POST['job_type'] ?? '') === 'contract' ? 'selected' : ''; ?>>Contract</option>
                        <option value="temporary" <?php echo ($_POST['job_type'] ?? '') === 'temporary' ? 'selected' : ''; ?>>Temporary</option>
                        <option value="internship" <?php echo ($_POST['job_type'] ?? '') === 'internship' ? 'selected' : ''; ?>>Internship</option>
                        <option value="part_time" <?php echo ($_POST['job_type'] ?? '') === 'part_time' ? 'selected' : ''; ?>>Part Time</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="employment_type">
                        <i class="fas fa-user-tie"></i> Employment Type
                    </label>
                    <select id="employment_type" name="employment_type">
                        <option value="full_time" <?php echo ($_POST['employment_type'] ?? 'full_time') === 'full_time' ? 'selected' : ''; ?>>Full Time</option>
                        <option value="part_time" <?php echo ($_POST['employment_type'] ?? '') === 'part_time' ? 'selected' : ''; ?>>Part Time</option>
                        <option value="contract" <?php echo ($_POST['employment_type'] ?? '') === 'contract' ? 'selected' : ''; ?>>Contract</option>
                        <option value="freelance" <?php echo ($_POST['employment_type'] ?? '') === 'freelance' ? 'selected' : ''; ?>>Freelance</option>
                        <option value="internship" <?php echo ($_POST['employment_type'] ?? '') === 'internship' ? 'selected' : ''; ?>>Internship</option>
                    </select>
                </div>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="location">
                        <i class="fas fa-map-marker-alt"></i> Location <span class="required">*</span>
                    </label>
                    <select id="location" name="location" required>
                        <option value="">Select location</option>
                        <option value="Lagos" <?php echo ($_POST['location'] ?? '') === 'Lagos' ? 'selected' : ''; ?>>Lagos</option>
                        <option value="Abuja" <?php echo ($_POST['location'] ?? '') === 'Abuja' ? 'selected' : ''; ?>>Abuja (FCT)</option>
                        <option value="Port Harcourt" <?php echo ($_POST['location'] ?? '') === 'Port Harcourt' ? 'selected' : ''; ?>>Port Harcourt</option>
                        <option value="Kano" <?php echo ($_POST['location'] ?? '') === 'Kano' ? 'selected' : ''; ?>>Kano</option>
                        <option value="Ibadan" <?php echo ($_POST['location'] ?? '') === 'Ibadan' ? 'selected' : ''; ?>>Ibadan</option>
                        <option value="Kaduna" <?php echo ($_POST['location'] ?? '') === 'Kaduna' ? 'selected' : ''; ?>>Kaduna</option>
                        <option value="Benin City" <?php echo ($_POST['location'] ?? '') === 'Benin City' ? 'selected' : ''; ?>>Benin City</option>
                        <option value="Jos" <?php echo ($_POST['location'] ?? '') === 'Jos' ? 'selected' : ''; ?>>Jos</option>
                        <option value="Enugu" <?php echo ($_POST['location'] ?? '') === 'Enugu' ? 'selected' : ''; ?>>Enugu</option>
                        <option value="Remote" <?php echo ($_POST['location'] ?? '') === 'Remote' ? 'selected' : ''; ?>>Remote</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="location_type">
                        <i class="fas fa-building"></i> Work Type
                    </label>
                    <select id="location_type" name="location_type">
                        <option value="onsite" <?php echo ($_POST['location_type'] ?? 'onsite') === 'onsite' ? 'selected' : ''; ?>>On-site</option>
                        <option value="remote" <?php echo ($_POST['location_type'] ?? '') === 'remote' ? 'selected' : ''; ?>>Remote</option>
                        <option value="hybrid" <?php echo ($_POST['location_type'] ?? '') === 'hybrid' ? 'selected' : ''; ?>>Hybrid</option>
                    </select>
                </div>
            </div>
            
            <!-- Job Description -->
            <div class="section-title">
                <i class="fas fa-file-alt"></i> Job Details
            </div>
            
            <div class="form-group">
                <label for="description">
                    <i class="fas fa-align-left"></i> Job Description <span class="required">*</span>
                </label>
                <textarea id="description" name="description" required rows="6"
                          placeholder="Describe the role, responsibilities, company culture, and what makes this position exciting..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="requirements">
                    <i class="fas fa-check-circle"></i> Requirements <span class="required">*</span>
                </label>
                <textarea id="requirements" name="requirements" required rows="4"
                          placeholder="List required qualifications, skills, experience, certifications..."><?php echo htmlspecialchars($_POST['requirements'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="responsibilities">
                    <i class="fas fa-tasks"></i> Key Responsibilities
                </label>
                <textarea id="responsibilities" name="responsibilities" rows="4"
                          placeholder="Outline the main duties and responsibilities for this position..."><?php echo htmlspecialchars($_POST['responsibilities'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="benefits">
                    <i class="fas fa-gift"></i> Benefits & Perks
                </label>
                <textarea id="benefits" name="benefits" rows="3"
                          placeholder="Health insurance, flexible hours, remote work, learning budget, etc..."><?php echo htmlspecialchars($_POST['benefits'] ?? ''); ?></textarea>
            </div>
            
            <!-- Compensation -->
            <div class="section-title">
                <i class="fas fa-money-bill-wave"></i> Compensation
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="salary_min">
                        <i class="fas fa-coins"></i> Minimum Salary (₦)
                    </label>
                    <input type="number" id="salary_min" name="salary_min" min="0"
                           value="<?php echo htmlspecialchars($_POST['salary_min'] ?? ''); ?>"
                           placeholder="e.g. 150000">
                </div>
                
                <div class="form-group">
                    <label for="salary_max">
                        <i class="fas fa-coins"></i> Maximum Salary (₦)
                    </label>
                    <input type="number" id="salary_max" name="salary_max" min="0"
                           value="<?php echo htmlspecialchars($_POST['salary_max'] ?? ''); ?>"
                           placeholder="e.g. 300000">
                </div>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="salary_period">
                        <i class="fas fa-calendar"></i> Pay Period
                    </label>
                    <select id="salary_period" name="salary_period">
                        <option value="monthly" <?php echo ($_POST['salary_period'] ?? 'monthly') === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                        <option value="yearly" <?php echo ($_POST['salary_period'] ?? '') === 'yearly' ? 'selected' : ''; ?>>Yearly</option>
                        <option value="hourly" <?php echo ($_POST['salary_period'] ?? '') === 'hourly' ? 'selected' : ''; ?>>Hourly</option>
                        <option value="daily" <?php echo ($_POST['salary_period'] ?? '') === 'daily' ? 'selected' : ''; ?>>Daily</option>
                        <option value="weekly" <?php echo ($_POST['salary_period'] ?? '') === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="experience_level">
                        <i class="fas fa-star"></i> Experience Level
                    </label>
                    <select id="experience_level" name="experience_level">
                        <option value="entry" <?php echo ($_POST['experience_level'] ?? 'entry') === 'entry' ? 'selected' : ''; ?>>Entry Level (0-2 years)</option>
                        <option value="mid" <?php echo ($_POST['experience_level'] ?? '') === 'mid' ? 'selected' : ''; ?>>Mid Level (2-5 years)</option>
                        <option value="senior" <?php echo ($_POST['experience_level'] ?? '') === 'senior' ? 'selected' : ''; ?>>Senior Level (5+ years)</option>
                        <option value="executive" <?php echo ($_POST['experience_level'] ?? '') === 'executive' ? 'selected' : ''; ?>>Executive Level</option>
                    </select>
                </div>
            </div>
            
            <!-- Application Settings -->
            <div class="section-title">
                <i class="fas fa-paper-plane"></i> Application Settings
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="application_email">
                        <i class="fas fa-envelope"></i> Application Email
                    </label>
                    <input type="email" id="application_email" name="application_email"
                           value="<?php echo htmlspecialchars($_POST['application_email'] ?? ''); ?>"
                           placeholder="jobs@company.com">
                </div>
                
                <div class="form-group">
                    <label for="application_deadline">
                        <i class="fas fa-calendar-times"></i> Application Deadline
                    </label>
                    <input type="date" id="application_deadline" name="application_deadline"
                           value="<?php echo htmlspecialchars($_POST['application_deadline'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="education_level">
                    <i class="fas fa-graduation-cap"></i> Required Education Level
                </label>
                <select id="education_level" name="education_level">
                    <option value="any" <?php echo ($_POST['education_level'] ?? 'any') === 'any' ? 'selected' : ''; ?>>Any qualification</option>
                    <option value="ssce" <?php echo ($_POST['education_level'] ?? '') === 'ssce' ? 'selected' : ''; ?>>SSCE/WAEC</option>
                    <option value="ond" <?php echo ($_POST['education_level'] ?? '') === 'ond' ? 'selected' : ''; ?>>OND</option>
                    <option value="hnd" <?php echo ($_POST['education_level'] ?? '') === 'hnd' ? 'selected' : ''; ?>>HND</option>
                    <option value="bsc" <?php echo ($_POST['education_level'] ?? '') === 'bsc' ? 'selected' : ''; ?>>Bachelor's Degree</option>
                    <option value="msc" <?php echo ($_POST['education_level'] ?? '') === 'msc' ? 'selected' : ''; ?>>Master's Degree</option>
                    <option value="phd" <?php echo ($_POST['education_level'] ?? '') === 'phd' ? 'selected' : ''; ?>>PhD</option>
                </select>
            </div>
            
            <!-- Additional Options -->
            <div class="section-title">
                <i class="fas fa-sliders-h"></i> Additional Options
            </div>
            
            <div class="checkbox-group">
                <input type="checkbox" id="remote_friendly" name="remote_friendly" 
                       <?php echo isset($_POST['remote_friendly']) ? 'checked' : ''; ?>>
                <label for="remote_friendly">Remote-friendly position</label>
            </div>
            
            <div class="checkbox-group">
                <input type="checkbox" id="is_urgent" name="is_urgent" 
                       <?php echo isset($_POST['is_urgent']) ? 'checked' : ''; ?>>
                <label for="is_urgent">Mark as urgent hiring</label>
            </div>
            
            <!-- Form Actions -->
            <div class="form-actions">
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                
                <button type="submit" name="submit_job" class="btn btn-primary">
                    <i class="fas fa-rocket"></i> Post Job Now
                </button>
            </div>
        </form>
    </div>
    
    <script>
        // Auto-set deadline to 30 days from now if not set
        document.addEventListener('DOMContentLoaded', function() {
            const deadlineInput = document.getElementById('application_deadline');
            if (deadlineInput && !deadlineInput.value) {
                const futureDate = new Date();
                futureDate.setDate(futureDate.getDate() + 30);
                deadlineInput.value = futureDate.toISOString().split('T')[0];
            }
            
            // Form validation
            document.querySelector('.job-form').addEventListener('submit', function(e) {
                const title = document.getElementById('job_title').value.trim();
                const description = document.getElementById('description').value.trim();
                const requirements = document.getElementById('requirements').value.trim();
                
                if (title.length < 5) {
                    alert('Job title must be at least 5 characters long');
                    e.preventDefault();
                    return;
                }
                
                if (description.length < 50) {
                    alert('Job description must be at least 50 characters long');
                    e.preventDefault();
                    return;
                }
                
                if (requirements.length < 10) {
                    alert('Requirements must be at least 10 characters long');
                    e.preventDefault();
                    return;
                }
                
                // Show loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Posting Job...';
                submitBtn.disabled = true;
            });
        });
    </script>
</body>
</html>