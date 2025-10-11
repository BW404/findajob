<?php
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/constants.php';
require_once '../../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit();
}

// Check if user is job seeker
if (!isJobSeeker()) {
    header('Location: ../company/dashboard.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error_message = '';
$success_message = '';

// Handle CV upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_cv'])) {
    $cv_name = trim($_POST['cv_name']);
    $cv_description = trim($_POST['cv_description']);
    $is_primary = isset($_POST['is_primary']) ? 1 : 0;
    
    // Validate input
    if (empty($cv_name)) {
        $error_message = 'Please provide a name for your CV';
    } elseif (empty($_FILES['cv_file']['name'])) {
        $error_message = 'Please select a CV file to upload';
    } else {
        $file = $_FILES['cv_file'];
        $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowed_types)) {
            $error_message = 'Only PDF, DOC, and DOCX files are allowed';
        } elseif ($file['size'] > $max_size) {
            $error_message = 'File size must be less than 5MB';
        } else {
            // Create uploads directory if it doesn't exist
            $upload_dir = '../../uploads/cvs/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $unique_filename = 'cv_' . $user_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $unique_filename;
            
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // If this is set as primary, unset other primary CVs
                if ($is_primary) {
                    $stmt = $pdo->prepare("UPDATE cvs SET is_primary = 0 WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                }
                
                // Get file type
                $file_type = mime_content_type($file['tmp_name']);
                
                // Insert CV record into database
                $stmt = $pdo->prepare("
                    INSERT INTO cvs (user_id, title, description, file_path, file_name, original_filename, file_size, file_type, is_primary, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                if ($stmt->execute([$user_id, $cv_name, $cv_description, $unique_filename, $unique_filename, $file['name'], $file['size'], $file_type, $is_primary])) {
                    $success_message = 'CV uploaded successfully!';
                } else {
                    $error_message = 'Failed to save CV information to database';
                    unlink($upload_path); // Delete uploaded file if database insert fails
                }
            } else {
                $error_message = 'Failed to upload file';
            }
        }
    }
}

// Handle CV deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_cv'])) {
    $cv_id = (int)$_POST['cv_id'];
    
    // Get CV info before deletion
    $stmt = $pdo->prepare("SELECT file_path FROM cvs WHERE id = ? AND user_id = ?");
    $stmt->execute([$cv_id, $user_id]);
    $cv = $stmt->fetch();
    
    if ($cv) {
        // Delete file
        $file_path = '../../uploads/cvs/' . $cv['file_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // Delete database record
        $stmt = $pdo->prepare("DELETE FROM cvs WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$cv_id, $user_id])) {
            $success_message = 'CV deleted successfully!';
        } else {
            $error_message = 'Failed to delete CV';
        }
    }
}

// Handle set as primary
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_primary'])) {
    $cv_id = (int)$_POST['cv_id'];
    
    // Unset all primary CVs for user
    $stmt = $pdo->prepare("UPDATE cvs SET is_primary = 0 WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    // Set new primary CV
    $stmt = $pdo->prepare("UPDATE cvs SET is_primary = 1 WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$cv_id, $user_id])) {
        $success_message = 'Primary CV updated successfully!';
    } else {
        $error_message = 'Failed to update primary CV';
    }
}

// Get user's CVs
$stmt = $pdo->prepare("
    SELECT id, title as cv_name, description as cv_description, original_filename, file_path, file_size, is_primary, created_at
    FROM cvs 
    WHERE user_id = ? 
    ORDER BY is_primary DESC, created_at DESC
");
$stmt->execute([$user_id]);
$user_cvs = $stmt->fetchAll();

$page_title = 'CV Manager - FindAJob Nigeria';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="../../favicon.svg">
    
    <link rel="stylesheet" href="../../assets/css/main.css">
    
    <style>
        .cv-manager-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .cv-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .upload-section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .cv-grid {
            display: grid;
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .cv-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .cv-card.primary {
            border-color: var(--success);
            background: linear-gradient(135deg, #ecfdf5 0%, #ffffff 100%);
        }
        
        .cv-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .cv-header-info {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .cv-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0 0 0.25rem 0;
        }
        
        .cv-primary-badge {
            background: var(--success);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .cv-description {
            color: var(--text-secondary);
            margin-bottom: 1rem;
        }
        
        .cv-meta {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        
        .cv-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        
        .cv-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        
        .upload-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        
        .form-group-full {
            grid-column: 1 / -1;
        }
        
        .file-upload-area {
            border: 2px dashed var(--border);
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            background: var(--background);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .file-upload-area:hover {
            border-color: var(--primary);
            background: var(--primary-light);
        }
        
        .file-upload-area.dragover {
            border-color: var(--success);
            background: rgba(5, 150, 105, 0.1);
        }
        
        .upload-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.6;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .empty-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.6;
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert-success {
            background: var(--green-light);
            border: 1px solid var(--green);
            color: var(--green-dark);
        }
        
        .alert-error {
            background: var(--primary-light);
            border: 1px solid var(--primary);
            color: var(--primary-dark);
        }
        
        .alert-warning {
            background: var(--amber-light);
            border: 1px solid var(--amber);
            color: var(--amber-dark);
        }
        
        @media (max-width: 768px) {
            .cv-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .upload-form {
                grid-template-columns: 1fr;
            }
            
            .cv-actions {
                justify-content: center;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="cv-manager-container">
        <!-- Header -->
        <div class="cv-header">
            <div>
                <h1 style="margin: 0 0 0.5rem 0; color: var(--text-primary);">📄 CV Manager</h1>
                <p style="margin: 0; color: var(--text-secondary);">Upload and manage multiple CVs for different job applications</p>
            </div>
            <a href="../user/dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($user_cvs); ?></div>
                <div class="stat-label">Total CVs</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($user_cvs, function($cv) { return $cv['is_primary']; })); ?></div>
                <div class="stat-label">Primary CV</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">5</div>
                <div class="stat-label">Max CVs Allowed</div>
            </div>
        </div>
        
        <!-- Messages -->
        <?php if ($error_message): ?>
            <div class="alert alert-error" style="margin-bottom: 2rem;">
                <strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success" style="margin-bottom: 2rem;">
                <strong>Success:</strong> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Upload Section -->
        <?php if (count($user_cvs) < 5): ?>
        <div class="upload-section">
            <h2 style="margin: 0 0 1.5rem 0; color: var(--text-primary);">📤 Upload New CV</h2>
            
            <form method="POST" enctype="multipart/form-data" class="upload-form">
                <div class="form-group">
                    <label for="cv_name">CV Name *</label>
                    <input type="text" id="cv_name" name="cv_name" required 
                           placeholder="e.g., Software Developer CV" class="form-control">
                    <small class="form-text">Give your CV a descriptive name</small>
                </div>
                
                <div class="form-group">
                    <label for="cv_description">Description</label>
                    <textarea id="cv_description" name="cv_description" 
                              placeholder="Brief description of this CV version" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-group-full">
                    <label for="cv_file">CV File *</label>
                    <div class="file-upload-area" onclick="document.getElementById('cv_file').click()">
                        <div class="upload-icon">📄</div>
                        <div style="font-weight: 600; margin-bottom: 0.5rem;">Click to select your CV file</div>
                        <div style="color: var(--text-secondary); font-size: 0.875rem;">
                            Supported formats: PDF, DOC, DOCX (Max size: 5MB)
                        </div>
                    </div>
                    <input type="file" id="cv_file" name="cv_file" accept=".pdf,.doc,.docx" required style="display: none;">
                </div>
                
                <div class="form-group-full">
                    <div class="form-check">
                        <input type="checkbox" id="is_primary" name="is_primary">
                        <label for="is_primary">Set as primary CV</label>
                        <small class="form-text">Primary CV will be used by default for job applications</small>
                    </div>
                </div>
                
                <div class="form-group-full">
                    <button type="submit" name="upload_cv" class="btn btn-primary">
                        📤 Upload CV
                    </button>
                </div>
            </form>
        </div>
        <?php else: ?>
        <div class="alert alert-warning" style="margin-bottom: 2rem;">
            <strong>CV Limit Reached:</strong> You have reached the maximum of 5 CVs. Please delete a CV to upload a new one.
        </div>
        <?php endif; ?>
        
        <!-- CVs List -->
        <div class="cv-grid">
            <?php if (empty($user_cvs)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📄</div>
                    <h3 style="margin: 0 0 1rem 0; color: var(--text-primary);">No CVs uploaded yet</h3>
                    <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">
                        Upload your first CV to start applying for jobs. You can upload up to 5 different CV versions.
                    </p>
                    <a href="#" onclick="document.getElementById('cv_name').focus()" class="btn btn-primary">
                        📤 Upload Your First CV
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($user_cvs as $cv): ?>
                    <div class="cv-card <?php echo $cv['is_primary'] ? 'primary' : ''; ?>">
                        <div class="cv-header-info">
                            <div>
                                <h3 class="cv-title"><?php echo htmlspecialchars($cv['cv_name']); ?></h3>
                                <?php if ($cv['is_primary']): ?>
                                    <span class="cv-primary-badge">⭐ Primary CV</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($cv['cv_description']): ?>
                            <p class="cv-description"><?php echo htmlspecialchars($cv['cv_description']); ?></p>
                        <?php endif; ?>
                        
                        <div class="cv-meta">
                            <div class="cv-meta-item">
                                📁 <span><?php echo htmlspecialchars($cv['original_filename']); ?></span>
                            </div>
                            <div class="cv-meta-item">
                                📅 <span>Uploaded <?php echo date('M j, Y', strtotime($cv['created_at'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="cv-actions">
                            <a href="cv-download.php?id=<?php echo $cv['id']; ?>&action=preview" 
                               target="_blank" class="btn btn-blue btn-sm">
                                👁️ Preview
                            </a>
                            <a href="cv-download.php?id=<?php echo $cv['id']; ?>&action=download" 
                               class="btn btn-teal btn-sm">
                                💾 Download
                            </a>
                            <?php if (!$cv['is_primary']): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="cv_id" value="<?php echo $cv['id']; ?>">
                                    <button type="submit" name="set_primary" class="btn btn-amber btn-sm">
                                        ⭐ Set Primary
                                    </button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" style="display: inline;" 
                                  onsubmit="return confirm('Are you sure you want to delete this CV? This action cannot be undone.')">
                                <input type="hidden" name="cv_id" value="<?php echo $cv['id']; ?>">
                                <button type="submit" name="delete_cv" class="btn btn-primary btn-sm">
                                    🗑️ Delete
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Tips Section -->
        <div style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-top: 2rem;">
            <h3 style="margin: 0 0 1rem 0; color: var(--text-primary);">💡 CV Management Tips</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                <div>
                    <h4 style="color: var(--success); margin: 0 0 0.5rem 0;">📝 Multiple CVs Strategy</h4>
                    <p style="margin: 0; color: var(--text-secondary); font-size: 0.875rem;">
                        Create different CV versions for different job types. For example, one for tech roles, 
                        another for management positions.
                    </p>
                </div>
                <div>
                    <h4 style="color: var(--orange); margin: 0 0 0.5rem 0;">⭐ Primary CV</h4>
                    <p style="margin: 0; color: var(--text-secondary); font-size: 0.875rem;">
                        Your primary CV will be used automatically when applying for jobs. Choose your most 
                        comprehensive and updated version.
                    </p>
                </div>
                <div>
                    <h4 style="color: var(--purple); margin: 0 0 0.5rem 0;">🔄 Keep Updated</h4>
                    <p style="margin: 0; color: var(--text-secondary); font-size: 0.875rem;">
                        Regularly update your CVs with new skills, experiences, and achievements to stay 
                        competitive in the job market.
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // File upload enhancements
        const fileInput = document.getElementById('cv_file');
        const uploadArea = document.querySelector('.file-upload-area');
        
        // Update upload area when file is selected
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                const file = this.files[0];
                uploadArea.innerHTML = `
                    <div class="upload-icon">✅</div>
                    <div style="font-weight: 600; margin-bottom: 0.5rem;">File selected: ${file.name}</div>
                    <div style="color: var(--text-secondary); font-size: 0.875rem;">
                        Size: ${(file.size / 1024 / 1024).toFixed(2)} MB
                    </div>
                `;
                uploadArea.style.borderColor = 'var(--success)';
                uploadArea.style.background = 'var(--success-light)';
            }
        });
        
        // Drag and drop functionality
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                fileInput.dispatchEvent(new Event('change'));
            }
        });
    </script>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>