<?php 
// Enable error reporting for debugging
if (isset($_GET['debug']) || isset($_POST['debug_mode'])) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

try {
    require_once '../../includes/functions.php';
} catch (Exception $e) {
    die("Error loading functions: " . $e->getMessage());
}

try {
    require_once '../../config/database.php';
} catch (Exception $e) {
    die("Error loading database config: " . $e->getMessage());
}

try {
    require_once '../../config/session.php';
} catch (Exception $e) {
    die("Error loading session config: " . $e->getMessage());
}

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
$debug_mode = isset($_GET['debug']) || isset($_POST['debug_mode']);
$debug_info = [];

// Initialize variables with defaults
$current_jobs = 0;
$is_premium = false;
$job_limit = 5;

// Double-check user is actually an employer in database
try {
    $stmt = $pdo->prepare("SELECT user_type, first_name, last_name, email FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userInfo = $stmt->fetch();

    if ($debug_mode) {
        $debug_info[] = "User ID: $userId";
        $debug_info[] = "User Info: " . json_encode($userInfo);
    }

    if (!$userInfo || $userInfo['user_type'] !== 'employer') {
        if ($debug_mode) {
            $debug_info[] = "ERROR: User verification failed - not employer or user not found";
        }
        header('Location: ../auth/login-employer.php?error=not_employer');
        exit;
    }
    
    if ($debug_mode) {
        $debug_info[] = "✅ User verified as employer: " . $userInfo['email'];
    }
    
    // Check for premium plan restrictions
    $stmt = $pdo->prepare("SELECT COUNT(*) as job_count FROM jobs WHERE employer_id = ? AND STATUS = 'active'");
    $stmt->execute([$userId]);
    $current_jobs = $stmt->fetchColumn();
    
    // Check if user has premium subscription (table may not exist yet)
    $is_premium = false;
    $subscription = null;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM user_subscriptions WHERE user_id = ? AND status = 'active' AND end_date > NOW() ORDER BY end_date DESC LIMIT 1");
        $stmt->execute([$userId]);
        $subscription = $stmt->fetch();
        $is_premium = $subscription ? true : false;
    } catch (PDOException $sub_e) {
        // user_subscriptions table doesn't exist yet - treat as free user
        if ($debug_mode) {
            $debug_info[] = "⚠️ user_subscriptions table not found - treating as free user";
            $debug_info[] = "Subscription error: " . $sub_e->getMessage();
        }
        $is_premium = false;
    }
    
    $job_limit = $is_premium ? 999 : 5; // Free users can post up to 5 jobs, premium unlimited
    
    if ($debug_mode) {
        $debug_info[] = "Premium status: " . ($is_premium ? 'YES' : 'NO');
        $debug_info[] = "Current active jobs: $current_jobs";
        $debug_info[] = "Job limit: $job_limit";
        $debug_info[] = "Subscription info: " . json_encode($subscription);
    }
    
} catch (PDOException $e) {
    error_log("User verification error: " . $e->getMessage());
    if ($debug_mode) {
        $debug_info[] = "❌ Database error during user verification: " . $e->getMessage();
        $debug_info[] = "Error code: " . $e->getCode();
        $debug_info[] = "SQL State: " . $e->errorInfo[0] ?? 'N/A';
    }
    $error_message = "System error during user verification. Please try again. (Error: " . substr(md5($e->getMessage()), 0, 8) . ")";
}

// Get job categories for dropdown
$categories = [];
try {
    $stmt = $pdo->prepare("SELECT id, name FROM job_categories WHERE is_active = 1 ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll();
    
    if ($debug_mode) {
        $debug_info[] = "Categories loaded: " . count($categories) . " active categories";
        $debug_info[] = "Categories: " . json_encode(array_map(function($cat) { return ['id' => $cat['id'], 'name' => $cat['name']]; }, $categories));
    }
} catch (PDOException $e) {
    error_log("Categories fetch error: " . $e->getMessage());
    if ($debug_mode) {
        $debug_info[] = "ERROR: Failed to load categories: " . $e->getMessage();
    }
}

// Debug: Check what's being submitted (debug mode only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $debug_mode) {
    $debug_info[] = "=== POST REQUEST RECEIVED ===";
    $debug_info[] = "POST keys: " . implode(', ', array_keys($_POST));
    $debug_info[] = "Looking for submit_job: " . (isset($_POST['submit_job']) ? 'FOUND' : 'NOT FOUND');
}

// Handle form submission - improved detection for reliability
$is_job_submission = ($_SERVER['REQUEST_METHOD'] === 'POST' && 
                      (isset($_POST['submit_job']) || 
                       (isset($_POST['job_title']) && isset($_POST['category']) && isset($_POST['description']))));

if ($is_job_submission) {
    if ($debug_mode) {
        $debug_info[] = "=== JOB POSTING FORM SUBMISSION ===";
        $debug_info[] = "POST data received: " . json_encode($_POST);
        $debug_info[] = "Form validation starting...";
    }
    
    // Check premium plan limits first
    if ($current_jobs >= $job_limit) {
        $error_message = "❌ Job posting limit reached! " . ($is_premium ? "Please contact support." : "Free accounts can post up to $job_limit active jobs. Upgrade to Premium for unlimited job postings.");
        if ($debug_mode) {
            $debug_info[] = "❌ Job posting blocked: Limit reached ($current_jobs >= $job_limit)";
            $debug_info[] = "Premium status: " . ($is_premium ? 'YES' : 'NO');
            $debug_info[] = "❌ FORM PROCESSING STOPPED - Job limit exceeded";
        }
    } else {
        if ($debug_mode) {
            $debug_info[] = "✅ Job posting limit check passed ($current_jobs < $job_limit)";
            $debug_info[] = "✅ Proceeding with job creation...";
        }
    
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
                if ($debug_mode) {
                    $debug_info[] = "❌ Validation error: $label is required (field: $field)";
                }
            } else if ($debug_mode) {
                $debug_info[] = "✅ $label validation passed";
            }
        }
        
        // Length validations
        if (!empty($_POST['job_title']) && strlen(trim($_POST['job_title'])) < 5) {
            $errors[] = 'Job title must be at least 5 characters';
            if ($debug_mode) {
                $debug_info[] = "❌ Job title too short: " . strlen(trim($_POST['job_title'])) . " characters";
            }
        }
        
        if (!empty($_POST['description']) && strlen(trim($_POST['description'])) < 50) {
            $errors[] = 'Job description must be at least 50 characters';
            if ($debug_mode) {
                $debug_info[] = "❌ Description too short: " . strlen(trim($_POST['description'])) . " characters";
            }
        }
        
        if (!empty($_POST['requirements']) && strlen(trim($_POST['requirements'])) < 10) {
            $errors[] = 'Requirements must be at least 10 characters';
            if ($debug_mode) {
                $debug_info[] = "❌ Requirements too short: " . strlen(trim($_POST['requirements'])) . " characters";
            }
        }
        
        if ($debug_mode && empty($errors)) {
            $debug_info[] = "✅ All length validations passed";
        }
        
        // Salary validation
        if (!empty($_POST['salary_min']) && !empty($_POST['salary_max'])) {
            $salary_min = (int)$_POST['salary_min'];
            $salary_max = (int)$_POST['salary_max'];
            if ($salary_min > $salary_max) {
                $errors[] = 'Minimum salary cannot be higher than maximum salary';
                if ($debug_mode) {
                    $debug_info[] = "❌ Salary validation failed: min($salary_min) > max($salary_max)";
                }
            } else if ($debug_mode) {
                $debug_info[] = "✅ Salary validation passed: ₦$salary_min - ₦$salary_max";
            }
        }
        
        // Email validation if provided
        if (!empty($_POST['application_email']) && !filter_var($_POST['application_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid application email address';
            if ($debug_mode) {
                $debug_info[] = "❌ Invalid email: " . $_POST['application_email'];
            }
        } else if (!empty($_POST['application_email']) && $debug_mode) {
            $debug_info[] = "✅ Email validation passed: " . $_POST['application_email'];
        }
        
        // If no errors, proceed with job creation
        if (empty($errors)) {
            if ($debug_mode) {
                $debug_info[] = "✅ All validations passed, proceeding with job creation...";
            }
            
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
            
            if ($debug_mode) {
                $debug_info[] = "✅ Generated unique slug: $slug";
            }
            
            // Get company name
            $company_name = trim(($userInfo['first_name'] ?? '') . ' ' . ($userInfo['last_name'] ?? ''));
            if (empty(trim($company_name))) {
                $company_name = 'Employer Company';
            }
            
            if ($debug_mode) {
                $debug_info[] = "✅ Company name: $company_name";
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
            
            if ($debug_mode) {
                $debug_info[] = "✅ Job type mapping: " . $_POST['job_type'] . " -> $db_job_type";
            }
            
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
            
            if ($debug_mode) {
                $debug_info[] = "✅ Job data prepared:";
                $debug_info[] = json_encode($job_data, JSON_PRETTY_PRINT);
            }
            
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
            
            if ($debug_mode) {
                $debug_info[] = "Database insertion attempt...";
                $debug_info[] = "SQL: $sql";
                $debug_info[] = "Result: " . ($result ? 'SUCCESS' : 'FAILED');
                if (!$result) {
                    $debug_info[] = "SQL Error: " . json_encode($stmt->errorInfo());
                }
            }
            
            if ($result) {
                $job_id = $pdo->lastInsertId();
                $success_message = "🎉 Job posted successfully! Your job (#$job_id) is now live and visible to candidates.";
                
                if ($debug_mode) {
                    $debug_info[] = "✅ SUCCESS: Job ID $job_id created";
                    
                    // Verify the job was actually inserted
                    $verify_stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ?");
                    $verify_stmt->execute([$job_id]);
                    $inserted_job = $verify_stmt->fetch();
                    
                    if ($inserted_job) {
                        $debug_info[] = "✅ Job verification successful in database";
                        $debug_info[] = "Job title in DB: " . $inserted_job['title'];
                        $debug_info[] = "Job status in DB: " . $inserted_job['STATUS'];
                    } else {
                        $debug_info[] = "❌ Job verification FAILED - not found in database";
                    }
                }
                
                // Log successful job posting
                error_log("Job posted successfully: ID $job_id, Title: " . $job_data['title'] . ", Employer: $userId");
                
                // Clear form data after successful submission
                $_POST = [];
            } else {
                $error_message = "❌ Failed to post job. Database insertion error. Please try again.";
                if ($debug_mode) {
                    $debug_info[] = "❌ Database insertion failed";
                    $debug_info[] = "Error info: " . json_encode($stmt->errorInfo());
                }
                error_log("Job insertion failed: " . print_r($stmt->errorInfo(), true));
            }
        } else {
            $error_message = "❌ Please fix the following errors:";
            foreach ($errors as $error) {
                $error_message .= "\n• " . $error;
            }
            
            if ($debug_mode) {
                $debug_info[] = "❌ Validation failed with " . count($errors) . " errors:";
                foreach ($errors as $error) {
                    $debug_info[] = "   • $error";
                }
            }
        }
        
    } catch (PDOException $e) {
        $error_message = "❌ Database error occurred. Please try again. (Error code: " . substr(md5($e->getMessage()), 0, 8) . ")";
        error_log("Job posting PDO error: " . $e->getMessage());
        error_log("Posted data: " . print_r($_POST, true));
        
        if ($debug_mode) {
            $debug_info[] = "❌ PDO Exception: " . $e->getMessage();
            $debug_info[] = "Error code: " . $e->getCode();
            $debug_info[] = "File: " . $e->getFile() . " Line: " . $e->getLine();
        }
    } catch (Exception $e) {
        $error_message = "❌ System error occurred. Please try again. (Error code: " . substr(md5($e->getMessage()), 0, 8) . ")";
        error_log("Job posting general error: " . $e->getMessage());
        
        if ($debug_mode) {
            $debug_info[] = "❌ General Exception: " . $e->getMessage();
            $debug_info[] = "Error code: " . $e->getCode();
            $debug_info[] = "File: " . $e->getFile() . " Line: " . $e->getLine();
        }
    }
    } // End of premium limit check
} else if ($_SERVER['REQUEST_METHOD'] === 'POST' && $debug_mode) {
    // Debug: Form was submitted but submit_job button not found
    $debug_info[] = "❌ Form submitted but 'submit_job' button not detected";
    $debug_info[] = "This suggests a JavaScript or form structure issue";
    $debug_info[] = "Try submitting with debug mode to bypass JavaScript validation";
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
<body class="has-bottom-nav">
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

        <!-- Job Usage Status -->
        <div style="max-width: 800px; margin: 0 auto 2rem;">
            <div style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); border-radius: 12px; padding: 1.5rem; border: 1px solid #cbd5e1;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <h4 style="margin: 0 0 0.5rem; color: #1e293b; font-size: 1.1rem;">
                            <i class="fas fa-chart-pie" style="margin-right: 0.5rem; color: #dc2626;"></i>
                            Job Posting Usage
                        </h4>
                        <p style="margin: 0; color: #64748b; font-size: 0.95rem;">
                            You have used <strong><?php echo $current_jobs; ?></strong> of <strong><?php echo $job_limit; ?></strong> available job slots
                            <?php if (!$is_premium): ?>
                                (Free Plan)
                            <?php else: ?>
                                (Premium Plan)
                            <?php endif; ?>
                        </p>
                    </div>
                    
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <!-- Progress Bar -->
                        <div style="width: 120px; height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden;">
                            <div style="width: <?php echo ($current_jobs / $job_limit) * 100; ?>%; height: 100%; background: <?php echo ($current_jobs >= $job_limit) ? '#ef4444' : '#10b981'; ?>; transition: width 0.3s ease;"></div>
                        </div>
                        
                        <?php if ($current_jobs < $job_limit): ?>
                            <span style="color: #10b981; font-weight: 600; font-size: 0.9rem;">
                                <i class="fas fa-check-circle"></i> <?php echo $job_limit - $current_jobs; ?> slots available
                            </span>
                        <?php else: ?>
                            <span style="color: #ef4444; font-weight: 600; font-size: 0.9rem;">
                                <i class="fas fa-exclamation-circle"></i> Limit reached
                            </span>
                        <?php endif; ?>
                    </div>
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
                        <?php echo nl2br(htmlspecialchars($error_message)); ?>
                        
                        <?php if ($current_jobs >= $job_limit && !$is_premium): ?>
                            <div style="margin-top: 1.5rem; padding: 1rem; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #dc2626;">
                                <h4 style="margin: 0 0 1rem; color: #dc2626;">💡 Solutions:</h4>
                                
                                <div style="display: grid; gap: 1rem; margin-bottom: 1rem;">
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <i class="fas fa-crown" style="color: #f59e0b; font-size: 1.2rem;"></i>
                                        <div>
                                            <strong>Upgrade to Premium</strong>
                                            <div style="font-size: 0.9rem; color: #6b7280;">Unlimited job postings + advanced features</div>
                                        </div>
                                    </div>
                                    
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <i class="fas fa-tachometer-alt" style="color: #3b82f6; font-size: 1.2rem;"></i>
                                        <div>
                                            <strong>Manage Existing Jobs</strong>
                                            <div style="font-size: 0.9rem; color: #6b7280;">Deactivate completed positions to free up slots</div>
                                        </div>
                                    </div>
                                    
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <i class="fas fa-trash-alt" style="color: #ef4444; font-size: 1.2rem;"></i>
                                        <div>
                                            <strong>Delete Old Jobs</strong>
                                            <div style="font-size: 0.9rem; color: #6b7280;">Remove outdated job postings permanently</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                                    <a href="dashboard.php" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 0.5rem;">
                                        <i class="fas fa-tachometer-alt"></i> Manage Jobs
                                    </a>
                                    <a href="#" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 0.5rem;">
                                        <i class="fas fa-crown"></i> Upgrade to Premium
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Debug Panel -->
            <?php if ($debug_mode): ?>
                <div style="background: #f8f9fa; border: 2px solid #e9ecef; border-radius: 10px; padding: 1.5rem; margin-bottom: 2rem; font-family: 'Courier New', monospace; font-size: 0.85rem;">
                    <h4 style="color: #dc2626; margin: 0 0 1rem; display: flex; align-items: center;">
                        <i class="fas fa-bug" style="margin-right: 0.5rem;"></i>
                        DEBUG MODE ACTIVE
                    </h4>
                    <div style="max-height: 400px; overflow-y: auto; background: white; padding: 1rem; border-radius: 5px; border: 1px solid #dee2e6;">
                        <?php foreach ($debug_info as $info): ?>
                            <div style="margin-bottom: 0.5rem; padding: 0.25rem; border-left: 3px solid #dc2626; padding-left: 0.75rem;">
                                <?php echo htmlspecialchars($info); ?>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if (empty($debug_info)): ?>
                            <div style="color: #6c757d; font-style: italic;">No debug information available yet. Submit a form to see debug data.</div>
                        <?php endif; ?>
                    </div>
                    <div style="margin-top: 1rem; font-size: 0.8rem; color: #6c757d;">
                        <strong>Debug Mode:</strong> Add ?debug=1 to URL or check the Debug Mode checkbox below to enable detailed logging.
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Debug Toggle -->
            <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 1rem; margin-bottom: 2rem; text-align: center;">
                <label style="display: inline-flex; align-items: center; cursor: pointer; font-weight: 500;">
                    <input type="checkbox" name="debug_mode" value="1" <?php echo $debug_mode ? 'checked' : ''; ?> style="margin-right: 0.5rem;">
                    <i class="fas fa-code" style="margin-right: 0.5rem; color: #dc2626;"></i>
                    Enable Debug Mode (Shows detailed posting process)
                </label>
                <div style="font-size: 0.85rem; color: #856404; margin-top: 0.5rem;">
                    Check this box to see exactly what happens during job posting - useful for troubleshooting issues.
                </div>
            </div>
            
            <!-- Quick Submit Test (Bypass JavaScript) -->
            <?php if ($debug_mode): ?>
            <div style="background: #e3f2fd; border: 1px solid #2196f3; border-radius: 8px; padding: 1rem; margin-bottom: 2rem; text-align: center;">
                <h4 style="margin: 0 0 1rem; color: #1976d2;">🚀 Quick Submit Test</h4>
                <form method="POST" style="display: inline-block;">
                    <input type="hidden" name="job_title" value="Debug Test Job">
                    <input type="hidden" name="category" value="1">
                    <input type="hidden" name="job_type" value="full-time">
                    <input type="hidden" name="location" value="Lagos">
                    <input type="hidden" name="description" value="This is a debug test job to verify the backend processing works correctly.">
                    <input type="hidden" name="requirements" value="Basic requirements for testing purposes.">
                    <input type="hidden" name="debug_mode" value="1">
                    <button type="submit" name="submit_job" style="background: #2196f3; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">
                        🧪 Test Backend Directly (Bypass JS)
                    </button>
                </form>
                <div style="font-size: 0.85rem; color: #1976d2; margin-top: 0.5rem;">
                    This bypasses all JavaScript validation and tests the PHP backend directly.
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
                            <div class="boost-badge">Free</div>
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
            // Remove selected class from all options and reset badges to default
            document.querySelectorAll('.boost-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Reset all badges to their default state
            const freeOption = document.querySelector('#boost-free').closest('.boost-option');
            const freeBadge = freeOption.querySelector('.boost-badge');
            freeBadge.innerHTML = 'Free';
            freeBadge.style.background = 'var(--border-color)';
            freeBadge.style.color = 'var(--text-secondary)';
            
            const premiumOption = document.querySelector('#boost-premium').closest('.boost-option');
            const premiumBadge = premiumOption.querySelector('.boost-badge');
            premiumBadge.innerHTML = 'Popular';
            premiumBadge.style.background = 'var(--border-color)';
            premiumBadge.style.color = 'var(--text-secondary)';
            
            const superOption = document.querySelector('#boost-super').closest('.boost-option');
            const superBadge = superOption.querySelector('.boost-badge');
            superBadge.innerHTML = 'Best Value';
            superBadge.style.background = 'var(--border-color)';
            superBadge.style.color = 'var(--text-secondary)';
            
            // Add selected class to clicked option
            const selectedOption = document.querySelector(`#boost-${type}`).closest('.boost-option');
            selectedOption.classList.add('selected');
            
            // Update badge for selected option to show "Selected"
            const selectedBadge = selectedOption.querySelector('.boost-badge');
            selectedBadge.innerHTML = 'Selected';
            selectedBadge.style.background = 'var(--primary)';
            selectedBadge.style.color = 'white';
            
            // Update radio button
            document.getElementById(`boost-${type}`).checked = true;
        }

        function scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Form submission handling
        document.querySelector('.job-form').addEventListener('submit', function(e) {
            // Check if debug mode is enabled (bypass validation)
            const urlParams = new URLSearchParams(window.location.search);
            const isDebugMode = urlParams.has('debug') || document.querySelector('input[name="debug_mode"]:checked');
            
            if (isDebugMode) {
                // Show loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Publishing...';
                submitBtn.disabled = true;
                return; // Allow submission without validation
            }
            
            // For normal mode, validate only if we're on the last step
            if (currentStep === 3) {
                // Validate all steps before final submission
                let allValid = true;
                for (let step = 1; step <= 3; step++) {
                    const stepElement = document.getElementById(`step-${step}`);
                    if (step === 1) {
                        const fields = ['job_title', 'job_type', 'category', 'location'];
                        fields.forEach(field => {
                            const input = stepElement.querySelector(`[name="${field}"]`);
                            if (input && !input.value.trim()) {
                                allValid = false;
                            }
                        });
                    } else if (step === 2) {
                        const fields = ['description', 'requirements'];
                        fields.forEach(field => {
                            const input = stepElement.querySelector(`[name="${field}"]`);
                            if (input && !input.value.trim()) {
                                allValid = false;
                            }
                        });
                    }
                }
                
                if (!allValid) {
                    alert('Please fill in all required fields in all steps before submitting.');
                    e.preventDefault();
                    return;
                }
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
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
    
    <!-- Bottom Navigation for PWA -->
    <nav class="app-bottom-nav">
        <a href="../../index.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">🏠</div>
            <div class="app-bottom-nav-label">Home</div>
        </a>
        <a href="post-job.php" class="app-bottom-nav-item active">
            <div class="app-bottom-nav-icon">📝</div>
            <div class="app-bottom-nav-label">Post Job</div>
        </a>
        <a href="dashboard.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">📊</div>
            <div class="app-bottom-nav-label">Dashboard</div>
        </a>
        <a href="profile.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">🏢</div>
            <div class="app-bottom-nav-label">Company</div>
        </a>
    </nav>
</body>
</html>