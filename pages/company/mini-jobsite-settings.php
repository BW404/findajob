<?php
require_once '../../config/database.php';
require_once '../../config/session.php';

requireEmployer();

$userId = getCurrentUserId();

// Get user and employer profile data
$stmt = $pdo->prepare("
    SELECT u.*, ep.* 
    FROM users u 
    LEFT JOIN employer_profiles ep ON u.id = ep.user_id 
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Check if employer has Pro subscription
$isPro = ($user['subscription_type'] === 'pro' && 
          (!$user['subscription_end'] || strtotime($user['subscription_end']) > time()));

$success = '';
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enabled = isset($_POST['mini_jobsite_enabled']) ? 1 : 0;
    $slug = trim($_POST['mini_jobsite_slug'] ?? '');
    $theme = $_POST['mini_jobsite_theme'] ?? 'default';
    $custom_message = trim($_POST['mini_jobsite_custom_message'] ?? '');
    $show_contact = isset($_POST['mini_jobsite_show_contact']) ? 1 : 0;
    $show_social = isset($_POST['mini_jobsite_show_social']) ? 1 : 0;
    
    // Social media links
    $social_linkedin = trim($_POST['social_linkedin'] ?? '');
    $social_twitter = trim($_POST['social_twitter'] ?? '');
    $social_facebook = trim($_POST['social_facebook'] ?? '');
    $social_instagram = trim($_POST['social_instagram'] ?? '');
    
    // Validate slug
    if (empty($slug)) {
        $errors[] = "URL slug is required";
    } elseif (!preg_match('/^[a-z0-9-]+$/', $slug)) {
        $errors[] = "URL slug can only contain lowercase letters, numbers, and hyphens";
    } else {
        // Check if slug is already taken by another employer
        $check_stmt = $pdo->prepare("SELECT user_id FROM employer_profiles WHERE mini_jobsite_slug = ? AND user_id != ?");
        $check_stmt->execute([$slug, $userId]);
        if ($check_stmt->fetch()) {
            $errors[] = "This URL slug is already taken. Please choose another.";
        }
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE employer_profiles 
                SET mini_jobsite_enabled = ?,
                    mini_jobsite_slug = ?,
                    mini_jobsite_theme = ?,
                    mini_jobsite_custom_message = ?,
                    mini_jobsite_show_contact = ?,
                    mini_jobsite_show_social = ?,
                    social_linkedin = ?,
                    social_twitter = ?,
                    social_facebook = ?,
                    social_instagram = ?,
                    mini_jobsite_updated_at = NOW()
                WHERE user_id = ?
            ");
            
            $stmt->execute([
                $enabled, $slug, $theme, $custom_message, 
                $show_contact, $show_social,
                $social_linkedin, $social_twitter, $social_facebook, $social_instagram,
                $userId
            ]);
            
            $success = "Mini Jobsite settings saved successfully!";
            
            // Refresh user data
            $stmt = $pdo->prepare("SELECT u.*, ep.* FROM users u LEFT JOIN employer_profiles ep ON u.id = ep.user_id WHERE u.id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
        } catch (Exception $e) {
            $errors[] = "Failed to save settings: " . $e->getMessage();
        }
    }
}

// Generate default slug if not set
if (empty($user['mini_jobsite_slug']) && !empty($user['company_name'])) {
    $default_slug = strtolower(preg_replace('/[^a-z0-9-]/', '-', 
                    preg_replace('/\s+/', '-', $user['company_name']))) . '-' . $userId;
    $default_slug = preg_replace('/-+/', '-', trim($default_slug, '-'));
} else {
    $default_slug = $user['mini_jobsite_slug'] ?? '';
}

// Get active jobs count
$jobs_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM jobs WHERE employer_id = ? AND status = 'active'");
$jobs_stmt->execute([$userId]);
$active_jobs_count = $jobs_stmt->fetchColumn();

// Get mini jobsite views
$views = $user['mini_jobsite_views'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mini Jobsite Settings - FindAJob Nigeria</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="has-bottom-nav">
    <?php include '../../includes/employer-header.php'; ?>
    
    <main class="container" style="padding: 2rem 0;">
        <!-- Page Header -->
        <div style="margin-bottom: 2rem;">
            <h1 style="margin: 0 0 0.5rem 0; font-size: 2rem; font-weight: 700; color: var(--text-primary);">
                <i class="fas fa-globe" style="color: var(--primary); margin-right: 0.5rem;"></i>
                Mini Jobsite
            </h1>
            <p style="margin: 0; color: var(--text-secondary); font-size: 1rem;">
                Create your personalized mini-website to showcase your company and all job openings
            </p>
        </div>

        <?php if ($success): ?>
        <div class="alert alert-success" style="margin-bottom: 2rem; padding: 1rem; background: #d1fae5; border: 1px solid #059669; border-radius: 8px; color: #065f46;">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-error" style="margin-bottom: 2rem; padding: 1rem; background: #fee2e2; border: 1px solid #dc2626; border-radius: 8px; color: #991b1b;">
            <i class="fas fa-exclamation-circle"></i>
            <ul style="margin: 0.5rem 0 0 1.5rem; padding: 0;">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
            <!-- Settings Form -->
            <div>
                <form method="POST" style="background: var(--surface); padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <!-- Enable/Disable -->
                    <div style="margin-bottom: 2rem; padding: 1.5rem; background: linear-gradient(135deg, rgba(220,38,38,0.05) 0%, rgba(220,38,38,0.02) 100%); border-radius: 8px; border-left: 4px solid var(--primary);">
                        <label style="display: flex; align-items: center; cursor: pointer;">
                            <input type="checkbox" name="mini_jobsite_enabled" value="1" 
                                   <?php echo ($user['mini_jobsite_enabled'] ?? 1) ? 'checked' : ''; ?>
                                   style="width: 20px; height: 20px; margin-right: 1rem; cursor: pointer;">
                            <div>
                                <div style="font-weight: 700; color: var(--text-primary); margin-bottom: 0.25rem;">
                                    Enable Mini Jobsite
                                </div>
                                <div style="font-size: 0.9rem; color: var(--text-secondary);">
                                    Make your company jobsite publicly accessible
                                </div>
                            </div>
                        </label>
                    </div>

                    <!-- URL Slug -->
                    <div style="margin-bottom: 2rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-primary);">
                            Mini Jobsite URL *
                        </label>
                        <div style="display: flex; align-items: center; background: #f3f4f6; border-radius: 8px; overflow: hidden;">
                            <span style="padding: 0.75rem 1rem; color: var(--text-secondary); background: #e5e7eb; font-size: 0.9rem;">
                                localhost/findajob/mini/
                            </span>
                            <input type="text" name="mini_jobsite_slug" 
                                   value="<?php echo htmlspecialchars($default_slug); ?>"
                                   placeholder="your-company-name"
                                   pattern="[a-z0-9-]+"
                                   style="flex: 1; padding: 0.75rem 1rem; border: none; background: white; font-family: monospace;"
                                   required>
                        </div>
                        <p style="margin: 0.5rem 0 0 0; font-size: 0.85rem; color: var(--text-secondary);">
                            Only lowercase letters, numbers, and hyphens allowed
                        </p>
                    </div>

                    <!-- Theme Selection -->
                    <div style="margin-bottom: 2rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-primary);">
                            Color Theme
                        </label>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                            <?php
                            $themes = [
                                'default' => ['name' => 'Red', 'color' => '#dc2626'],
                                'blue' => ['name' => 'Blue', 'color' => '#2563eb'],
                                'green' => ['name' => 'Green', 'color' => '#059669'],
                                'purple' => ['name' => 'Purple', 'color' => '#7c3aed'],
                                'orange' => ['name' => 'Orange', 'color' => '#ea580c'],
                                'teal' => ['name' => 'Teal', 'color' => '#0d9488']
                            ];
                            foreach ($themes as $key => $theme):
                                $selected = ($user['mini_jobsite_theme'] ?? 'default') === $key;
                            ?>
                            <label style="cursor: pointer;">
                                <input type="radio" name="mini_jobsite_theme" value="<?php echo $key; ?>"
                                       <?php echo $selected ? 'checked' : ''; ?>
                                       style="display: none;">
                                <div style="padding: 1rem; border: 2px solid <?php echo $selected ? $theme['color'] : '#e5e7eb'; ?>; border-radius: 8px; text-align: center; transition: all 0.2s; background: <?php echo $selected ? $theme['color'] . '10' : 'white'; ?>">
                                    <div style="width: 40px; height: 40px; background: <?php echo $theme['color']; ?>; border-radius: 50%; margin: 0 auto 0.5rem;"></div>
                                    <div style="font-weight: 600; font-size: 0.9rem;"><?php echo $theme['name']; ?></div>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Custom Welcome Message -->
                    <div style="margin-bottom: 2rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-primary);">
                            Welcome Message
                        </label>
                        <textarea name="mini_jobsite_custom_message" rows="4"
                                  style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; font-family: inherit;"
                                  placeholder="Welcome to our careers page! We're always looking for talented individuals to join our team..."><?php echo htmlspecialchars($user['mini_jobsite_custom_message'] ?? ''); ?></textarea>
                        <p style="margin: 0.5rem 0 0 0; font-size: 0.85rem; color: var(--text-secondary);">
                            This message will appear at the top of your mini jobsite
                        </p>
                    </div>

                    <!-- Display Options -->
                    <div style="margin-bottom: 2rem;">
                        <label style="display: block; margin-bottom: 1rem; font-weight: 600; color: var(--text-primary);">
                            Display Options
                        </label>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <label style="display: flex; align-items: center; cursor: pointer; padding: 1rem; background: #f9fafb; border-radius: 8px;">
                                <input type="checkbox" name="mini_jobsite_show_contact" value="1"
                                       <?php echo ($user['mini_jobsite_show_contact'] ?? 1) ? 'checked' : ''; ?>
                                       style="width: 18px; height: 18px; margin-right: 0.75rem; cursor: pointer;">
                                <div>
                                    <div style="font-weight: 600;">Show Contact Information</div>
                                    <div style="font-size: 0.85rem; color: var(--text-secondary);">Display email, phone, and address</div>
                                </div>
                            </label>
                            
                            <label style="display: flex; align-items: center; cursor: pointer; padding: 1rem; background: #f9fafb; border-radius: 8px;">
                                <input type="checkbox" name="mini_jobsite_show_social" value="1"
                                       <?php echo ($user['mini_jobsite_show_social'] ?? 1) ? 'checked' : ''; ?>
                                       style="width: 18px; height: 18px; margin-right: 0.75rem; cursor: pointer;">
                                <div>
                                    <div style="font-weight: 600;">Show Social Media Links</div>
                                    <div style="font-size: 0.85rem; color: var(--text-secondary);">Display LinkedIn, Twitter, Facebook, Instagram</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Social Media Links -->
                    <div style="margin-bottom: 2rem;">
                        <label style="display: block; margin-bottom: 1rem; font-weight: 600; color: var(--text-primary);">
                            Social Media Links
                        </label>
                        <div style="display: grid; gap: 1rem;">
                            <div>
                                <div style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                                    <i class="fab fa-linkedin" style="width: 24px; color: #0077b5; margin-right: 0.5rem;"></i>
                                    <span style="font-weight: 500;">LinkedIn</span>
                                </div>
                                <input type="url" name="social_linkedin" 
                                       value="<?php echo htmlspecialchars($user['social_linkedin'] ?? ''); ?>"
                                       placeholder="https://linkedin.com/company/your-company"
                                       style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px;">
                            </div>
                            
                            <div>
                                <div style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                                    <i class="fab fa-twitter" style="width: 24px; color: #1da1f2; margin-right: 0.5rem;"></i>
                                    <span style="font-weight: 500;">Twitter</span>
                                </div>
                                <input type="url" name="social_twitter" 
                                       value="<?php echo htmlspecialchars($user['social_twitter'] ?? ''); ?>"
                                       placeholder="https://twitter.com/yourcompany"
                                       style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px;">
                            </div>
                            
                            <div>
                                <div style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                                    <i class="fab fa-facebook" style="width: 24px; color: #1877f2; margin-right: 0.5rem;"></i>
                                    <span style="font-weight: 500;">Facebook</span>
                                </div>
                                <input type="url" name="social_facebook" 
                                       value="<?php echo htmlspecialchars($user['social_facebook'] ?? ''); ?>"
                                       placeholder="https://facebook.com/yourcompany"
                                       style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px;">
                            </div>
                            
                            <div>
                                <div style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                                    <i class="fab fa-instagram" style="width: 24px; color: #e4405f; margin-right: 0.5rem;"></i>
                                    <span style="font-weight: 500;">Instagram</span>
                                </div>
                                <input type="url" name="social_instagram" 
                                       value="<?php echo htmlspecialchars($user['social_instagram'] ?? ''); ?>"
                                       placeholder="https://instagram.com/yourcompany"
                                       style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px;">
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.05rem; font-weight: 600;">
                        <i class="fas fa-save"></i> Save Settings
                    </button>
                </form>
            </div>

            <!-- Preview & Stats -->
            <div>
                <!-- Quick Stats -->
                <div style="background: var(--surface); padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 1.5rem;">
                    <h3 style="margin: 0 0 1rem 0; font-size: 1.1rem; font-weight: 700; color: var(--text-primary);">
                        <i class="fas fa-chart-line" style="color: var(--primary);"></i> Quick Stats
                    </h3>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #f9fafb; border-radius: 8px;">
                            <span style="color: var(--text-secondary);">
                                <i class="fas fa-eye"></i> Total Views
                            </span>
                            <span style="font-size: 1.5rem; font-weight: 700; color: var(--primary);">
                                <?php echo number_format($views); ?>
                            </span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #f9fafb; border-radius: 8px;">
                            <span style="color: var(--text-secondary);">
                                <i class="fas fa-briefcase"></i> Active Jobs
                            </span>
                            <span style="font-size: 1.5rem; font-weight: 700; color: var(--primary);">
                                <?php echo $active_jobs_count; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Preview Link -->
                <?php if (!empty($user['mini_jobsite_slug'])): ?>
                <div style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); color: white; text-align: center; margin-bottom: 1.5rem;">
                    <div style="margin-bottom: 1rem;">
                        <i class="fas fa-globe" style="font-size: 2.5rem; opacity: 0.9;"></i>
                    </div>
                    <h3 style="margin: 0 0 0.5rem 0; font-size: 1.1rem; font-weight: 700;">
                        Your Mini Jobsite
                    </h3>
                    <p style="margin: 0 0 1rem 0; font-size: 0.85rem; opacity: 0.9;">
                        Share this link with candidates
                    </p>
                    <a href="/findajob/mini/<?php echo htmlspecialchars($user['mini_jobsite_slug']); ?>" 
                       target="_blank"
                       style="display: inline-block; padding: 0.75rem 1.5rem; background: white; color: var(--primary); border-radius: 8px; text-decoration: none; font-weight: 600; margin-bottom: 1rem;">
                        <i class="fas fa-external-link-alt"></i> Preview Site
                    </a>
                    <div style="background: rgba(255,255,255,0.2); padding: 0.75rem; border-radius: 6px; font-family: monospace; font-size: 0.85rem; word-break: break-all;">
                        localhost/findajob/mini/<?php echo htmlspecialchars($user['mini_jobsite_slug']); ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Tips -->
                <div style="background: linear-gradient(135deg, rgba(99,102,241,0.1) 0%, rgba(99,102,241,0.05) 100%); padding: 1.5rem; border-radius: 12px; border: 2px solid rgba(99,102,241,0.2);">
                    <h3 style="margin: 0 0 1rem 0; font-size: 1.1rem; font-weight: 700; color: var(--text-primary);">
                        <i class="fas fa-lightbulb" style="color: #6366f1;"></i> Tips
                    </h3>
                    <ul style="margin: 0; padding-left: 1.5rem; color: var(--text-secondary); font-size: 0.9rem; line-height: 1.8;">
                        <li>Choose a memorable URL slug</li>
                        <li>Add a welcoming custom message</li>
                        <li>Complete your company profile</li>
                        <li>Upload your company logo</li>
                        <li>Keep your job listings updated</li>
                        <li>Share the link on social media</li>
                    </ul>
                </div>
            </div>
        </div>
    </main>

    <?php include '../../includes/footer.php'; ?>
    
    <script>
    // Handle theme selection clicks
    document.querySelectorAll('input[name="mini_jobsite_theme"]').forEach(radio => {
        const label = radio.closest('label');
        const themeDiv = label.querySelector('div[style*="padding: 1rem"]');
        
        label.addEventListener('click', function(e) {
            // Uncheck all other radios and reset their styles
            document.querySelectorAll('input[name="mini_jobsite_theme"]').forEach(r => {
                if (r !== radio) {
                    r.checked = false;
                    const otherLabel = r.closest('label');
                    const otherDiv = otherLabel.querySelector('div[style*="padding: 1rem"]');
                    otherDiv.style.borderColor = '#e5e7eb';
                    otherDiv.style.background = 'white';
                }
            });
            
            // Check this radio and update its style
            radio.checked = true;
            const color = radio.value === 'default' ? '#dc2626' :
                         radio.value === 'blue' ? '#2563eb' :
                         radio.value === 'green' ? '#059669' :
                         radio.value === 'purple' ? '#7c3aed' :
                         radio.value === 'orange' ? '#ea580c' :
                         radio.value === 'teal' ? '#0d9488' : '#dc2626';
            
            themeDiv.style.borderColor = color;
            themeDiv.style.background = color + '10';
        });
    });
    </script>
</body>
</html>
