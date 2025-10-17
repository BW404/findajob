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

// Get job categories for dropdown
$categories = [];
try {
    $stmt = $pdo->prepare("SELECT id, name FROM job_categories WHERE is_active = 1 ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Categories fetch error: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_job'])) {
    try {
        // Comprehensive validation
        $errors = [];
        
        // Required fields validation
        $required_fields = [
            'job_title' => 'Job Title',
            'category' => 'Job Category',
            'job_type' => 'Job Type',
            'location' => 'Location',
            'description' => 'Job Description',
            'requirements' => 'Requirements'
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
            
            // Map form job types to database enum values
            $job_type_mapping = [
                'full-time' => 'permanent',
                'part-time' => 'part_time',
                'contract' => 'contract',
                'temporary' => 'temporary',
                'internship' => 'internship',
                'nysc' => 'nysc'
            ];
            
            $db_job_type = $job_type_mapping[$_POST['job_type']] ?? 'permanent';
            
            // Prepare job data
            $job_data = [
                'employer_id' => $userId,
                'title' => trim($_POST['job_title']),
                'slug' => $slug,
                'category_id' => (int)$_POST['category'],
                'job_type' => $db_job_type,
                'employment_type' => 'full_time', // Default for now
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
                'experience_level' => $_POST['experience'] ?? 'entry',
                'education_level' => $_POST['education'] ?? 'any',
                'application_deadline' => !empty($_POST['application_deadline']) ? $_POST['application_deadline'] : null,
                'application_email' => !empty($_POST['application_email']) ? $_POST['application_email'] : null,
                'application_url' => trim($_POST['application_url'] ?? ''),
                'company_name' => $company_name,
                'is_featured' => isset($_POST['boost_type']) && $_POST['boost_type'] !== 'free' ? 1 : 0,
                'is_urgent' => isset($_POST['is_urgent']) ? 1 : 0,
                'is_remote_friendly' => isset($_POST['remote_friendly']) ? 1 : 0,
                'views_count' => 0,
                'applications_count' => 0,
                'STATUS' => 'active'  // Jobs go live immediately
            ];
            
            // Insert job into database
            $sql = "INSERT INTO jobs (
                employer_id, title, slug, category_id, job_type, employment_type,
                description, requirements, responsibilities, benefits,
                salary_min, salary_max, salary_currency, salary_period,
                location_type, state, city, address,
                experience_level, education_level, application_deadline,
                application_email, application_url, company_name,
                is_featured, is_urgent, is_remote_friendly,
                views_count, applications_count, STATUS, created_at, updated_at
            ) VALUES (
                :employer_id, :title, :slug, :category_id, :job_type, :employment_type,
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .form-step {
            min-height: 500px;
        }
        
        .form-step.active {
            display: block;
        }
        
        .form-step:not(.active) {
            display: none;
        }
        
        .progress-steps .step.active div {
            background: var(--primary) !important;
            color: white !important;
        }
        
        .progress-steps .step.active span {
            color: var(--primary) !important;
        }
        
        .progress-steps .step.completed div {
            background: var(--accent) !important;
            color: white !important;
        }
        
        .progress-steps .step.completed span {
            color: var(--accent) !important;
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
            transition: all 0.3s ease;
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
            transition: all 0.3s ease;
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
        
        .btn-secondary:hover {
            background: #475569;
        }
        
        .success-message {
            background: linear-gradient(135deg, rgba(5, 150, 105, 0.1) 0%, rgba(5, 150, 105, 0.05) 100%);
            border: 2px solid rgba(5, 150, 105, 0.2);
            color: var(--accent);
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .error-message {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.05) 100%);
            border: 2px solid rgba(239, 68, 68, 0.2);
            color: var(--primary);
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            white-space: pre-line;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
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
        
        .boost-option {
            border: 2px solid var(--border-color);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .boost-option:hover {
            border-color: var(--primary);
            box-shadow: 0 4px 20px rgba(220, 38, 38, 0.1);
        }
        
        .boost-option.selected {
            border-color: var(--primary);
            background: rgba(220, 38, 38, 0.05);
        }
        
        .boost-badge {
            position: absolute;
            top: -8px;
            right: 16px;
            background: var(--border-color);
            color: var(--text-secondary);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .progress-steps {
                flex-direction: column;
                gap: 1rem !important;
            }
            
            .progress-steps div[style*="width: 40px"] {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
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
                <div class="success-message">
                    <i class="fas fa-check-circle" style="font-size: 1.5rem;"></i>
                    <div>
                        <strong>Success!</strong><br>
                        <?php echo htmlspecialchars($success_message); ?>
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
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle" style="font-size: 1.5rem; margin-top: 0.25rem;"></i>
                    <div>
                        <strong>Error!</strong><br>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <form class="job-form" method="POST" style="background: var(--surface); border-radius: 20px; padding: 3rem; box-shadow: 0 20px 50px rgba(0,0,0,0.08); border: 1px solid rgba(0,0,0,0.05);">
                
                <!-- Step 1: Job Details -->
                <div class="form-step active" id="step-1">
                    <div class="form-section-header" style="text-align: center; margin-bottom: 3rem;">
                        <h3 style="font-size: 1.8rem; font-weight: 700; color: var(--text-primary); margin: 0 0 1rem; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-briefcase" style="margin-right: 0.75rem; color: var(--primary);"></i>
                            Job Information
                        </h3>
                        <p style="color: var(--text-secondary); font-size: 1.1rem; margin: 0;">Tell us about the position you're hiring for</p>
                    </div>

                    <!-- Job Title -->
                    <div class="form-group">
                        <label for="job-title">
                            <i class="fas fa-tag" style="margin-right: 0.5rem; color: var(--primary);"></i>
                            Job Title <span style="color: var(--primary);">*</span>
                        </label>
                        <input type="text" id="job-title" name="job_title" required 
                               value="<?php echo htmlspecialchars($_POST['job_title'] ?? ''); ?>"
                               placeholder="e.g. Senior Software Developer, Digital Marketing Manager">
                    </div>

                    <!-- Form Grid -->
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="job-type">
                                <i class="fas fa-clock" style="margin-right: 0.5rem; color: var(--primary);"></i>
                                Job Type <span style="color: var(--primary);">*</span>
                            </label>
                            <select id="job-type" name="job_type" required>
                                <option value="">Select job type</option>
                                <option value="full-time" <?php echo ($_POST['job_type'] ?? '') === 'full-time' ? 'selected' : ''; ?>>🕘 Full-time</option>
                                <option value="part-time" <?php echo ($_POST['job_type'] ?? '') === 'part-time' ? 'selected' : ''; ?>>🕐 Part-time</option>
                                <option value="contract" <?php echo ($_POST['job_type'] ?? '') === 'contract' ? 'selected' : ''; ?>>📋 Contract</option>
                                <option value="temporary" <?php echo ($_POST['job_type'] ?? '') === 'temporary' ? 'selected' : ''; ?>>⏰ Temporary</option>
                                <option value="internship" <?php echo ($_POST['job_type'] ?? '') === 'internship' ? 'selected' : ''; ?>>🎓 Internship</option>
                                <option value="nysc" <?php echo ($_POST['job_type'] ?? '') === 'nysc' ? 'selected' : ''; ?>>🏛️ NYSC Placement</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="category">
                                <i class="fas fa-layer-group" style="margin-right: 0.5rem; color: var(--primary);"></i>
                                Job Category <span style="color: var(--primary);">*</span>
                            </label>
                            <select id="category" name="category" required>
                                <option value="">Select category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo ($_POST['category'] ?? '') == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="location">
                            <i class="fas fa-map-marker-alt" style="margin-right: 0.5rem; color: var(--primary);"></i>
                            Location <span style="color: var(--primary);">*</span>
                        </label>
                        <select id="location" name="location" required>
                            <option value="">Select location</option>
                            <option value="Lagos" <?php echo ($_POST['location'] ?? '') === 'Lagos' ? 'selected' : ''; ?>>🏙️ Lagos</option>
                            <option value="Abuja" <?php echo ($_POST['location'] ?? '') === 'Abuja' ? 'selected' : ''; ?>>🏛️ Abuja (FCT)</option>
                            <option value="Port Harcourt" <?php echo ($_POST['location'] ?? '') === 'Port Harcourt' ? 'selected' : ''; ?>>⛽ Port Harcourt</option>
                            <option value="Kano" <?php echo ($_POST['location'] ?? '') === 'Kano' ? 'selected' : ''; ?>>🕌 Kano</option>
                            <option value="Ibadan" <?php echo ($_POST['location'] ?? '') === 'Ibadan' ? 'selected' : ''; ?>>🌳 Ibadan</option>
                            <option value="Kaduna" <?php echo ($_POST['location'] ?? '') === 'Kaduna' ? 'selected' : ''; ?>>🏭 Kaduna</option>
                            <option value="Benin City" <?php echo ($_POST['location'] ?? '') === 'Benin City' ? 'selected' : ''; ?>>👑 Benin City</option>
                            <option value="Jos" <?php echo ($_POST['location'] ?? '') === 'Jos' ? 'selected' : ''; ?>>🏔️ Jos</option>
                            <option value="Enugu" <?php echo ($_POST['location'] ?? '') === 'Enugu' ? 'selected' : ''; ?>>🌄 Enugu</option>
                            <option value="Remote" <?php echo ($_POST['location'] ?? '') === 'Remote' ? 'selected' : ''; ?>>🌐 Remote</option>
                        </select>
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" id="remote-friendly" name="remote_friendly" 
                               <?php echo isset($_POST['remote_friendly']) ? 'checked' : ''; ?>>
                        <label for="remote-friendly" style="margin: 0; color: var(--primary); font-weight: 500;">Remote OK</label>
                    </div>

                    <!-- Compensation Section -->
                    <div style="margin-top: 2rem;">
                        <h4 style="color: var(--text-primary); margin-bottom: 1rem;">💰 Compensation (Optional)</h4>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="salary-min">Minimum Salary (₦)</label>
                                <input type="number" id="salary-min" name="salary_min" min="0"
                                       value="<?php echo htmlspecialchars($_POST['salary_min'] ?? ''); ?>"
                                       placeholder="e.g. 150000">
                            </div>
                            <div class="form-group">
                                <label for="salary-max">Maximum Salary (₦)</label>
                                <input type="number" id="salary-max" name="salary_max" min="0"
                                       value="<?php echo htmlspecialchars($_POST['salary_max'] ?? ''); ?>"
                                       placeholder="e.g. 300000">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="salary-period">Pay Period</label>
                            <select id="salary-period" name="salary_period">
                                <option value="monthly" <?php echo ($_POST['salary_period'] ?? 'monthly') === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                <option value="yearly" <?php echo ($_POST['salary_period'] ?? '') === 'yearly' ? 'selected' : ''; ?>>Yearly</option>
                                <option value="weekly" <?php echo ($_POST['salary_period'] ?? '') === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                <option value="daily" <?php echo ($_POST['salary_period'] ?? '') === 'daily' ? 'selected' : ''; ?>>Daily</option>
                                <option value="hourly" <?php echo ($_POST['salary_period'] ?? '') === 'hourly' ? 'selected' : ''; ?>>Hourly</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="benefits">Benefits & Perks</label>
                            <textarea id="benefits" name="benefits" rows="3"
                                      placeholder="e.g. Health insurance, Transport allowance, Remote work, Learning budget"><?php echo htmlspecialchars($_POST['benefits'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- Step Navigation -->
                    <div style="display: flex; justify-content: space-between; padding-top: 2rem; border-top: 2px solid var(--border-color);">
                        <div></div>
                        <button type="button" onclick="nextStep()" class="btn btn-primary">
                            Next: Requirements <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 2: Job Requirements -->
                <div class="form-step" id="step-2">
                    <div class="form-section-header" style="text-align: center; margin-bottom: 3rem;">
                        <h3 style="font-size: 1.8rem; font-weight: 700; color: var(--text-primary); margin: 0 0 1rem; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-clipboard-list" style="margin-right: 0.75rem; color: var(--primary);"></i>
                            Job Requirements
                        </h3>
                        <p style="color: var(--text-secondary); font-size: 1.1rem; margin: 0;">Describe what you're looking for in the ideal candidate</p>
                    </div>

                    <!-- Job Description -->
                    <div class="form-group">
                        <label for="description">
                            <i class="fas fa-file-alt" style="margin-right: 0.5rem; color: var(--primary);"></i>
                            Job Description <span style="color: var(--primary);">*</span>
                        </label>
                        <textarea id="description" name="description" required rows="6"
                                  placeholder="Describe the job responsibilities, company culture, day-to-day activities, and what makes this role exciting..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.5rem;">
                            <i class="fas fa-lightbulb" style="margin-right: 0.25rem;"></i>
                            Tip: Be specific about daily tasks and company culture to attract the right candidates
                        </div>
                    </div>

                    <!-- Requirements -->
                    <div class="form-group">
                        <label for="requirements">
                            <i class="fas fa-check-circle" style="margin-right: 0.5rem; color: var(--primary);"></i>
                            Requirements <span style="color: var(--primary);">*</span>
                        </label>
                        <textarea id="requirements" name="requirements" required rows="4"
                                  placeholder="List the required qualifications, skills, experience, and any mandatory certifications..."><?php echo htmlspecialchars($_POST['requirements'] ?? ''); ?></textarea>
                    </div>

                    <!-- Responsibilities -->
                    <div class="form-group">
                        <label for="responsibilities">
                            <i class="fas fa-tasks" style="margin-right: 0.5rem; color: var(--primary);"></i>
                            Key Responsibilities
                        </label>
                        <textarea id="responsibilities" name="responsibilities" rows="4"
                                  placeholder="Describe the main duties and responsibilities for this position..."><?php echo htmlspecialchars($_POST['responsibilities'] ?? ''); ?></textarea>
                    </div>

                    <!-- Experience & Education Grid -->
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="experience">
                                <i class="fas fa-user-tie" style="margin-right: 0.5rem; color: var(--primary);"></i>
                                Experience Level
                            </label>
                            <select id="experience" name="experience">
                                <option value="entry" <?php echo ($_POST['experience'] ?? 'entry') === 'entry' ? 'selected' : ''; ?>>🌱 Entry Level (0-2 years)</option>
                                <option value="mid" <?php echo ($_POST['experience'] ?? '') === 'mid' ? 'selected' : ''; ?>>💼 Mid Level (2-5 years)</option>
                                <option value="senior" <?php echo ($_POST['experience'] ?? '') === 'senior' ? 'selected' : ''; ?>>👨‍💼 Senior Level (5+ years)</option>
                                <option value="executive" <?php echo ($_POST['experience'] ?? '') === 'executive' ? 'selected' : ''; ?>>🎯 Executive Level</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="education">
                                <i class="fas fa-graduation-cap" style="margin-right: 0.5rem; color: var(--primary);"></i>
                                Education Level
                            </label>
                            <select id="education" name="education">
                                <option value="any" <?php echo ($_POST['education'] ?? 'any') === 'any' ? 'selected' : ''; ?>>Any qualification</option>
                                <option value="ssce" <?php echo ($_POST['education'] ?? '') === 'ssce' ? 'selected' : ''; ?>>SSCE/WAEC</option>
                                <option value="ond" <?php echo ($_POST['education'] ?? '') === 'ond' ? 'selected' : ''; ?>>OND</option>
                                <option value="hnd" <?php echo ($_POST['education'] ?? '') === 'hnd' ? 'selected' : ''; ?>>HND</option>
                                <option value="bsc" <?php echo ($_POST['education'] ?? '') === 'bsc' ? 'selected' : ''; ?>>Bachelor's Degree</option>
                                <option value="msc" <?php echo ($_POST['education'] ?? '') === 'msc' ? 'selected' : ''; ?>>Master's Degree</option>
                                <option value="phd" <?php echo ($_POST['education'] ?? '') === 'phd' ? 'selected' : ''; ?>>PhD</option>
                            </select>
                        </div>
                    </div>

                    <!-- Application Settings -->
                    <div style="margin-top: 2rem; padding-top: 2rem; border-top: 2px solid var(--border-color);">
                        <h4 style="color: var(--text-primary); margin-bottom: 1rem;">📧 Application Settings</h4>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="application-email">Application Email</label>
                                <input type="email" id="application-email" name="application_email"
                                       value="<?php echo htmlspecialchars($_POST['application_email'] ?? ''); ?>"
                                       placeholder="jobs@company.com">
                            </div>
                            <div class="form-group">
                                <label for="application-deadline">Application Deadline</label>
                                <input type="date" id="application-deadline" name="application_deadline"
                                       value="<?php echo htmlspecialchars($_POST['application_deadline'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Step Navigation -->
                    <div style="display: flex; justify-content: space-between; padding-top: 2rem; border-top: 2px solid var(--border-color);">
                        <button type="button" onclick="prevStep()" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                        <button type="button" onclick="nextStep()" class="btn btn-primary">
                            Next: Publish <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 3: Publish Job -->
                <div class="form-step" id="step-3">
                    <div class="form-section-header" style="text-align: center; margin-bottom: 3rem;">
                        <h3 style="font-size: 1.8rem; font-weight: 700; color: var(--text-primary); margin: 0 0 1rem; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-rocket" style="margin-right: 0.75rem; color: var(--primary);"></i>
                            Publish Your Job
                        </h3>
                        <p style="color: var(--text-secondary); font-size: 1.1rem; margin: 0;">Choose your job posting package and publish</p>
                    </div>

                    <!-- Job Boost Options -->
                    <div style="margin-bottom: 3rem;">
                        <h4 style="color: var(--text-primary); margin-bottom: 1.5rem;">🚀 Boost Your Job Post</h4>
                        
                        <!-- Free Boost -->
                        <div class="boost-option selected" onclick="selectBoost('free')">
                            <div class="boost-badge">Selected</div>
                            <input type="radio" name="boost_type" value="free" id="boost-free" checked style="display: none;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                                <h5 style="margin: 0; color: var(--text-primary); font-size: 1.2rem;">📝 Standard Post</h5>
                                <span style="font-size: 1.5rem; font-weight: 700; color: var(--accent);">FREE</span>
                            </div>
                            <ul style="margin: 0; padding-left: 1.5rem; color: var(--text-secondary);">
                                <li>✅ Job appears in search results</li>
                                <li>✅ Basic job listing features</li>
                                <li>✅ Email applications</li>
                                <li>✅ 30-day visibility</li>
                            </ul>
                        </div>

                        <!-- Premium Boost -->
                        <div class="boost-option" onclick="selectBoost('premium')">
                            <div class="boost-badge">Popular</div>
                            <input type="radio" name="boost_type" value="premium" id="boost-premium" style="display: none;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                                <h5 style="margin: 0; color: var(--text-primary); font-size: 1.2rem;">⭐ Premium Boost</h5>
                                <span style="font-size: 1.5rem; font-weight: 700; color: var(--primary);">₦5,000</span>
                            </div>
                            <ul style="margin: 0; padding-left: 1.5rem; color: var(--text-secondary);">
                                <li>✅ Everything in Standard</li>
                                <li>⭐ Featured in search results</li>
                                <li>📊 Enhanced job analytics</li>
                                <li>🎯 Priority placement</li>
                                <li>⏰ 60-day visibility</li>
                            </ul>
                        </div>

                        <!-- Super Boost -->
                        <div class="boost-option" onclick="selectBoost('super')">
                            <div class="boost-badge">Best Value</div>
                            <input type="radio" name="boost_type" value="super" id="boost-super" style="display: none;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                                <h5 style="margin: 0; color: var(--text-primary); font-size: 1.2rem;">🚀 Super Boost</h5>
                                <span style="font-size: 1.5rem; font-weight: 700; color: var(--primary);">₦15,000</span>
                            </div>
                            <ul style="margin: 0; padding-left: 1.5rem; color: var(--text-secondary);">
                                <li>✅ Everything in Premium</li>
                                <li>🚀 Top of search results</li>
                                <li>📱 Social media promotion</li>
                                <li>💼 Dedicated account manager</li>
                                <li>📈 Advanced hiring tools</li>
                                <li>⏰ 90-day visibility</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Step Navigation -->
                    <div style="display: flex; justify-content: space-between; padding-top: 2rem; border-top: 2px solid var(--border-color);">
                        <button type="button" onclick="prevStep()" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                        <button type="submit" name="submit_job" class="btn btn-primary" style="font-size: 1.1rem; padding: 1.25rem 2.5rem;">
                            <i class="fas fa-rocket"></i> Publish Job Now
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentStep = 1;
        const totalSteps = 3;

        function showStep(step) {
            // Hide all steps
            for (let i = 1; i <= totalSteps; i++) {
                const stepElement = document.getElementById(`step-${i}`);
                if (stepElement) {
                    stepElement.classList.remove('active');
                }
            }

            // Show current step
            const currentStepElement = document.getElementById(`step-${step}`);
            if (currentStepElement) {
                currentStepElement.classList.add('active');
            }

            // Update progress indicators
            updateProgressIndicators(step);
            
            // Scroll to top
            scrollToTop();
        }

        function updateProgressIndicators(step) {
            const steps = document.querySelectorAll('.progress-steps .step');
            steps.forEach((stepEl, index) => {
                const stepNumber = index + 1;
                const circle = stepEl.querySelector('div');
                const label = stepEl.querySelector('span');
                
                // Remove all classes
                stepEl.classList.remove('active', 'completed');
                
                if (stepNumber < step) {
                    // Completed step
                    stepEl.classList.add('completed');
                    circle.style.background = 'var(--accent)';
                    circle.style.color = 'white';
                    label.style.color = 'var(--accent)';
                } else if (stepNumber === step) {
                    // Active step
                    stepEl.classList.add('active');
                    circle.style.background = 'var(--primary)';
                    circle.style.color = 'white';
                    label.style.color = 'var(--primary)';
                } else {
                    // Future step
                    circle.style.background = 'var(--border-color)';
                    circle.style.color = 'var(--text-secondary)';
                    label.style.color = 'var(--text-secondary)';
                }
            });
        }

        function nextStep() {
            if (validateCurrentStep() && currentStep < totalSteps) {
                currentStep++;
                showStep(currentStep);
            }
        }

        function prevStep() {
            if (currentStep > 1) {
                currentStep--;
                showStep(currentStep);
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
                
                // Check minimum lengths
                const title = document.querySelector('[name="job_title"]').value.trim();
                const description = document.querySelector('[name="description"]').value.trim();
                const requirements = document.querySelector('[name="requirements"]').value.trim();
                
                if (title.length < 5) {
                    showFieldError(document.querySelector('[name="job_title"]'), 'Job title must be at least 5 characters');
                    isValid = false;
                }
                
                if (description.length < 50) {
                    showFieldError(document.querySelector('[name="description"]'), 'Description must be at least 50 characters');
                    isValid = false;
                }
                
                if (requirements.length < 10) {
                    showFieldError(document.querySelector('[name="requirements"]'), 'Requirements must be at least 10 characters');
                    isValid = false;
                }
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
            errorDiv.className = 'field-error-message';
            errorDiv.style.color = 'var(--primary)';
            errorDiv.style.fontSize = '0.85rem';
            errorDiv.style.marginTop = '0.5rem';
            errorDiv.textContent = message;
            input.parentElement.appendChild(errorDiv);
        }

        function clearFieldError(input) {
            input.style.borderColor = 'var(--border-color)';
            input.style.boxShadow = 'none';
            
            const errorMessage = input.parentElement.querySelector('.field-error-message');
            if (errorMessage) {
                errorMessage.remove();
            }
        }

        function selectBoost(type) {
            // Remove selected class from all options
            document.querySelectorAll('.boost-option').forEach(option => {
                option.classList.remove('selected');
                const badge = option.querySelector('.boost-badge');
                if (type === 'free') {
                    badge.innerHTML = 'Selected';
                    badge.style.background = 'var(--primary)';
                    badge.style.color = 'white';
                } else if (type === 'premium') {
                    badge.innerHTML = 'Popular';
                    badge.style.background = 'var(--border-color)';
                    badge.style.color = 'var(--text-secondary)';
                } else if (type === 'super') {
                    badge.innerHTML = 'Best Value';
                    badge.style.background = 'var(--border-color)';
                    badge.style.color = 'var(--text-secondary)';
                }
            });
            
            // Add selected class to clicked option
            const selectedOption = document.querySelector(`#boost-${type}`).closest('.boost-option');
            selectedOption.classList.add('selected');
            
            // Update badge for selected option
            const selectedBadge = selectedOption.querySelector('.boost-badge');
            if (type === 'free') {
                selectedBadge.innerHTML = 'Selected';
                selectedBadge.style.background = 'var(--primary)';
                selectedBadge.style.color = 'white';
            } else if (type === 'premium') {
                selectedBadge.innerHTML = 'Selected';
                selectedBadge.style.background = 'var(--primary)';
                selectedBadge.style.color = 'white';
            } else if (type === 'super') {
                selectedBadge.innerHTML = 'Selected';
                selectedBadge.style.background = 'var(--primary)';
                selectedBadge.style.color = 'white';
            }
            
            // Update radio button
            document.getElementById(`boost-${type}`).checked = true;
        }

        function scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Form submission handling
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
        });

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            showStep(1);
            
            // Set default application deadline (30 days from now)
            const deadlineInput = document.getElementById('application-deadline');
            if (deadlineInput && !deadlineInput.value) {
                const futureDate = new Date();
                futureDate.setDate(futureDate.getDate() + 30);
                deadlineInput.value = futureDate.toISOString().split('T')[0];
            }
        });
    </script>
</body>
</html>