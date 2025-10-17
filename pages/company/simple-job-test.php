<?php
session_start();
require_once '../../config/database.php';

// Set up session for testing
$_SESSION['user_id'] = 2;
$_SESSION['user_type'] = 'employer';

$message = '';
$debug_info = [];
$debug_mode = true;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_test_job'])) {
    $debug_info[] = "=== SIMPLE JOB POSTING TEST ===";
    $debug_info[] = "Form submitted at: " . date('Y-m-d H:i:s');
    
    try {
        // Get user info
        $userId = 2;
        $stmt = $pdo->prepare("SELECT user_type, first_name, last_name, email FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $userInfo = $stmt->fetch();
        
        if (!$userInfo) {
            throw new Exception("User not found");
        }
        
        $debug_info[] = "✅ User verified: " . $userInfo['email'];
        
        // Check job count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE employer_id = ? AND STATUS = 'active'");
        $stmt->execute([$userId]);
        $current_jobs = $stmt->fetchColumn();
        
        $debug_info[] = "✅ Current jobs: $current_jobs";
        
        // Simple validation
        $required_fields = ['job_title', 'category', 'job_type', 'location', 'description', 'requirements'];
        $errors = [];
        
        foreach ($required_fields as $field) {
            if (empty(trim($_POST[$field] ?? ''))) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }
        
        if (!empty($errors)) {
            $debug_info[] = "❌ Validation errors: " . implode(', ', $errors);
            throw new Exception("Validation failed: " . implode(', ', $errors));
        }
        
        $debug_info[] = "✅ Validation passed";
        
        // Generate simple slug
        $slug = strtolower(str_replace(' ', '-', trim($_POST['job_title']))) . '-' . time();
        $debug_info[] = "✅ Generated slug: $slug";
        
        // Prepare minimal job data
        $job_data = [
            'employer_id' => $userId,
            'title' => trim($_POST['job_title']),
            'slug' => $slug,
            'category_id' => (int)$_POST['category'],
            'job_type' => $_POST['job_type'] === 'full-time' ? 'permanent' : $_POST['job_type'],
            'employment_type' => 'full_time',
            'description' => trim($_POST['description']),
            'requirements' => trim($_POST['requirements']),
            'responsibilities' => trim($_POST['responsibilities'] ?? ''),
            'benefits' => trim($_POST['benefits'] ?? ''),
            'salary_min' => !empty($_POST['salary_min']) ? (int)$_POST['salary_min'] : 0,
            'salary_max' => !empty($_POST['salary_max']) ? (int)$_POST['salary_max'] : 0,
            'salary_currency' => 'NGN',
            'salary_period' => 'monthly',
            'location_type' => 'onsite',
            'state' => trim($_POST['location']),
            'city' => trim($_POST['location']),
            'address' => '',
            'experience_level' => 'entry',
            'education_level' => 'any',
            'application_deadline' => date('Y-m-d', strtotime('+30 days')),
            'application_email' => $userInfo['email'],
            'application_url' => '',
            'company_name' => trim($userInfo['first_name'] . ' ' . $userInfo['last_name']),
            'is_featured' => 0,
            'is_urgent' => 0,
            'is_remote_friendly' => 0,
            'views_count' => 0,
            'applications_count' => 0,
            'STATUS' => 'active'
        ];
        
        $debug_info[] = "✅ Job data prepared";
        $debug_info[] = "Job title: " . $job_data['title'];
        $debug_info[] = "Category ID: " . $job_data['category_id'];
        $debug_info[] = "Job type: " . $job_data['job_type'];
        
        // Insert into database
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
        
        $debug_info[] = "✅ SQL prepared";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($job_data);
        
        if ($result) {
            $job_id = $pdo->lastInsertId();
            $debug_info[] = "✅ SUCCESS: Job inserted with ID $job_id";
            $message = "✅ Job posted successfully! Job ID: #$job_id";
            
            // Verify insertion
            $verify_stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ?");
            $verify_stmt->execute([$job_id]);
            $inserted_job = $verify_stmt->fetch();
            
            if ($inserted_job) {
                $debug_info[] = "✅ Job verified in database";
                $debug_info[] = "Inserted title: " . $inserted_job['title'];
                $debug_info[] = "Status: " . $inserted_job['STATUS'];
            } else {
                $debug_info[] = "❌ Job NOT found after insertion";
            }
        } else {
            $error_info = $stmt->errorInfo();
            $debug_info[] = "❌ INSERT FAILED";
            $debug_info[] = "Error: " . json_encode($error_info);
            throw new Exception("Database insertion failed: " . $error_info[2]);
        }
        
    } catch (Exception $e) {
        $message = "❌ Error: " . $e->getMessage();
        $debug_info[] = "❌ EXCEPTION: " . $e->getMessage();
        $debug_info[] = "File: " . $e->getFile() . " Line: " . $e->getLine();
    }
}

// Get categories
try {
    $stmt = $pdo->prepare("SELECT id, name FROM job_categories WHERE is_active = 1 ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch (Exception $e) {
    $categories = [];
    $debug_info[] = "❌ Failed to load categories: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Job Posting Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        textarea {
            height: 100px;
            resize: vertical;
        }
        
        .btn {
            background: #dc2626;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        
        .btn:hover {
            background: #b91c1c;
        }
        
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: bold;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .debug {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            font-family: monospace;
            font-size: 12px;
        }
        
        .debug h4 {
            margin: 0 0 10px;
            color: #dc2626;
        }
        
        .debug-item {
            margin-bottom: 5px;
            padding: 3px;
        }
        
        .required {
            color: red;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Simple Job Posting Test Form</h1>
        <p>This is a simplified test form to debug the job posting process.</p>
        
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, '✅') !== false ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="job_title">Job Title <span class="required">*</span></label>
                <input type="text" id="job_title" name="job_title" 
                       value="<?php echo htmlspecialchars($_POST['job_title'] ?? ''); ?>"
                       placeholder="e.g. Software Developer">
            </div>
            
            <div class="form-group">
                <label for="category">Category <span class="required">*</span></label>
                <select id="category" name="category" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>"
                                <?php echo (isset($_POST['category']) && $_POST['category'] == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="job_type">Job Type <span class="required">*</span></label>
                <select id="job_type" name="job_type" required>
                    <option value="">Select Job Type</option>
                    <option value="full-time" <?php echo (isset($_POST['job_type']) && $_POST['job_type'] == 'full-time') ? 'selected' : ''; ?>>Full Time</option>
                    <option value="part-time" <?php echo (isset($_POST['job_type']) && $_POST['job_type'] == 'part-time') ? 'selected' : ''; ?>>Part Time</option>
                    <option value="contract" <?php echo (isset($_POST['job_type']) && $_POST['job_type'] == 'contract') ? 'selected' : ''; ?>>Contract</option>
                    <option value="temporary" <?php echo (isset($_POST['job_type']) && $_POST['job_type'] == 'temporary') ? 'selected' : ''; ?>>Temporary</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="location">Location <span class="required">*</span></label>
                <input type="text" id="location" name="location" 
                       value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>"
                       placeholder="e.g. Lagos, Abuja">
            </div>
            
            <div class="form-group">
                <label for="description">Job Description <span class="required">*</span></label>
                <textarea id="description" name="description" required 
                          placeholder="Describe the job role and what the candidate will be doing..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="requirements">Requirements <span class="required">*</span></label>
                <textarea id="requirements" name="requirements" required 
                          placeholder="List the qualifications and skills needed..."><?php echo htmlspecialchars($_POST['requirements'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="responsibilities">Responsibilities (Optional)</label>
                <textarea id="responsibilities" name="responsibilities" 
                          placeholder="What will the employee be responsible for?"><?php echo htmlspecialchars($_POST['responsibilities'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="benefits">Benefits (Optional)</label>
                <textarea id="benefits" name="benefits" 
                          placeholder="What benefits do you offer?"><?php echo htmlspecialchars($_POST['benefits'] ?? ''); ?></textarea>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label for="salary_min">Minimum Salary (Optional)</label>
                    <input type="number" id="salary_min" name="salary_min" 
                           value="<?php echo htmlspecialchars($_POST['salary_min'] ?? ''); ?>"
                           placeholder="50000">
                </div>
                
                <div class="form-group">
                    <label for="salary_max">Maximum Salary (Optional)</label>
                    <input type="number" id="salary_max" name="salary_max" 
                           value="<?php echo htmlspecialchars($_POST['salary_max'] ?? ''); ?>"
                           placeholder="150000">
                </div>
            </div>
            
            <button type="submit" name="submit_test_job" class="btn">
                🚀 Post Test Job
            </button>
        </form>
        
        <?php if (!empty($debug_info)): ?>
            <div class="debug">
                <h4>🐛 Debug Information</h4>
                <?php foreach ($debug_info as $info): ?>
                    <div class="debug-item"><?php echo htmlspecialchars($info); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 30px; padding: 15px; background: #e3f2fd; border-radius: 5px;">
            <h4>🔗 Links</h4>
            <ul>
                <li><a href="post-job.php">Main Job Posting Form</a></li>
                <li><a href="post-job.php?debug=1">Main Form with Debug</a></li>
                <li><a href="dashboard.php">Dashboard</a></li>
            </ul>
        </div>
    </div>
</body>
</html>