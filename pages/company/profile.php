<?php
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/constants.php';

requireEmployer();

$userId = getCurrentUserId();

// Get employer profile data
$stmt = $pdo->prepare("
    SELECT u.id, u.user_type, u.email, u.first_name, u.last_name, u.phone,
           u.email_verified, u.is_active, u.created_at, u.updated_at,
           ep.id as profile_id, ep.company_name, ep.description as company_description,
           ep.website, ep.industry, ep.company_size, ep.address,
           ep.state, ep.city,
           u.profile_picture as logo
    FROM users u 
    LEFT JOIN employer_profiles ep ON u.id = ep.user_id 
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$success = '';
$errors = [];

// Handle form submission
if ($_POST) {
    $company_name = trim($_POST['company_name'] ?? '');
    $company_description = trim($_POST['company_description'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $industry = trim($_POST['industry'] ?? '');
    $company_size = trim($_POST['company_size'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    // Validation
    if (empty($company_name)) {
        $errors[] = "Company name is required";
    }
    
    if (!empty($website) && !filter_var($website, FILTER_VALIDATE_URL)) {
        $errors[] = "Please enter a valid website URL";
    }
    
    if (!empty($phone) && !preg_match('/^[\d\s\-\+\(\)]+$/', $phone)) {
        $errors[] = "Please enter a valid phone number";
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Update users table (phone number)
            if (!empty($phone)) {
                $stmt = $pdo->prepare("UPDATE users SET phone = ? WHERE id = ?");
                $stmt->execute([$phone, $userId]);
            }
            
            // Check if employer profile exists
            $stmt = $pdo->prepare("SELECT user_id FROM employer_profiles WHERE user_id = ?");
            $stmt->execute([$userId]);
            $profileExists = $stmt->fetch();
            
            if ($profileExists) {
                // Update existing profile
                $stmt = $pdo->prepare("
                    UPDATE employer_profiles SET 
                    company_name = ?, description = ?, website = ?, 
                    industry = ?, company_size = ?, 
                    state = ?, city = ?, address = ?, updated_at = NOW()
                    WHERE user_id = ?
                ");
                $stmt->execute([
                    $company_name, $company_description, $website, 
                    $industry, $company_size,
                    $state, $city, $address, $userId
                ]);
            } else {
                // Insert new profile
                $stmt = $pdo->prepare("
                    INSERT INTO employer_profiles 
                    (user_id, company_name, description, website, industry, company_size, state, city, address, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([
                    $userId, $company_name, $company_description, $website, 
                    $industry, $company_size, $state, $city, $address
                ]);
            }
            
            $pdo->commit();
            $success = "Profile updated successfully!";
            
            // Refresh user data
            $stmt = $pdo->prepare("
                SELECT u.*, ep.* 
                FROM users u 
                LEFT JOIN employer_profiles ep ON u.id = ep.user_id 
                WHERE u.id = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Failed to update profile. Please try again.";
        }
    }
}

// Get states for dropdown
$stmt = $pdo->query("SELECT id, name FROM nigeria_states ORDER BY name");
$states = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Profile - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="has-bottom-nav">
    <header class="site-header">
        <div class="container">
            <nav class="site-nav">
                <a href="/findajob" class="site-logo">
                    <img src="/findajob/assets/images/logo_full.png" alt="FindAJob Nigeria" class="site-logo-img">
                </a>
                <div>
                    <a href="dashboard.php" class="btn btn-outline">Dashboard</a>
                    <a href="../auth/logout.php" class="btn btn-secondary">Logout</a>
                </div>
            </nav>
        </div>
    </header>

    <main class="container">
        <div style="padding: 2rem 0;">
            <!-- Page Header -->
            <div class="page-header" style="margin-bottom: 2rem;">
                <h1 style="margin: 0; font-size: 2.5rem; font-weight: 700; color: var(--text-primary);">
                    Company Profile
                </h1>
                <p style="margin: 0.5rem 0 0 0; color: var(--text-secondary); font-size: 1.1rem;">
                    Manage your company information and build trust with candidates
                </p>
            </div>

            <!-- Success/Error Messages -->
            <?php if ($success): ?>
                <div class="alert alert-success" style="margin-bottom: 2rem;">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error" style="margin-bottom: 2rem;">
                    <i class="fas fa-exclamation-circle"></i>
                    <ul style="margin: 0.5rem 0 0 0; padding-left: 1.5rem;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
                <!-- Profile Form -->
                <div class="form-container" style="background: var(--surface); padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <h2 style="margin: 0 0 2rem 0; color: var(--text-primary);">Company Information</h2>
                    
                    <form method="POST">
                        <div class="form-grid" style="display: grid; gap: 1.5rem;">
                            <!-- Company Name -->
                            <div>
                                <label for="company_name" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                                    Company Name *
                                </label>
                                <input type="text" id="company_name" name="company_name" 
                                       value="<?php echo htmlspecialchars($user['company_name'] ?? ''); ?>"
                                       style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 6px;"
                                       required>
                            </div>

                            <!-- Industry and Company Size -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div>
                                    <label for="industry" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                                        Industry
                                    </label>
                                    <select id="industry" name="industry" 
                                            style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 6px;">
                                        <option value="">Select Industry</option>
                                        <option value="technology" <?php echo ($user['industry'] ?? '') === 'technology' ? 'selected' : ''; ?>>Technology</option>
                                        <option value="finance" <?php echo ($user['industry'] ?? '') === 'finance' ? 'selected' : ''; ?>>Finance & Banking</option>
                                        <option value="oil_gas" <?php echo ($user['industry'] ?? '') === 'oil_gas' ? 'selected' : ''; ?>>Oil & Gas</option>
                                        <option value="telecommunications" <?php echo ($user['industry'] ?? '') === 'telecommunications' ? 'selected' : ''; ?>>Telecommunications</option>
                                        <option value="healthcare" <?php echo ($user['industry'] ?? '') === 'healthcare' ? 'selected' : ''; ?>>Healthcare</option>
                                        <option value="education" <?php echo ($user['industry'] ?? '') === 'education' ? 'selected' : ''; ?>>Education</option>
                                        <option value="manufacturing" <?php echo ($user['industry'] ?? '') === 'manufacturing' ? 'selected' : ''; ?>>Manufacturing</option>
                                        <option value="agriculture" <?php echo ($user['industry'] ?? '') === 'agriculture' ? 'selected' : ''; ?>>Agriculture</option>
                                        <option value="retail" <?php echo ($user['industry'] ?? '') === 'retail' ? 'selected' : ''; ?>>Retail</option>
                                        <option value="government" <?php echo ($user['industry'] ?? '') === 'government' ? 'selected' : ''; ?>>Government</option>
                                        <option value="other" <?php echo ($user['industry'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="company_size" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                                        Company Size
                                    </label>
                                    <select id="company_size" name="company_size" 
                                            style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 6px;">
                                        <option value="">Select Size</option>
                                        <option value="1-10" <?php echo ($user['company_size'] ?? '') === '1-10' ? 'selected' : ''; ?>>1-10 employees</option>
                                        <option value="11-50" <?php echo ($user['company_size'] ?? '') === '11-50' ? 'selected' : ''; ?>>11-50 employees</option>
                                        <option value="51-200" <?php echo ($user['company_size'] ?? '') === '51-200' ? 'selected' : ''; ?>>51-200 employees</option>
                                        <option value="201-500" <?php echo ($user['company_size'] ?? '') === '201-500' ? 'selected' : ''; ?>>201-500 employees</option>
                                        <option value="501-1000" <?php echo ($user['company_size'] ?? '') === '501-1000' ? 'selected' : ''; ?>>501-1000 employees</option>
                                        <option value="1000+" <?php echo ($user['company_size'] ?? '') === '1000+' ? 'selected' : ''; ?>>1000+ employees</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Company Description -->
                            <div>
                                <label for="company_description" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                                    Company Description
                                </label>
                                <textarea id="company_description" name="company_description" rows="4"
                                          style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 6px; resize: vertical;"
                                          placeholder="Describe your company, mission, and what makes it a great place to work..."><?php echo htmlspecialchars($user['company_description'] ?? ''); ?></textarea>
                            </div>

                            <!-- Website and Phone -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div>
                                    <label for="website" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                                        Company Website
                                    </label>
                                    <input type="url" id="website" name="website" 
                                           value="<?php echo htmlspecialchars($user['website'] ?? ''); ?>"
                                           style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 6px;"
                                           placeholder="https://www.yourcompany.com">
                                </div>

                                <div>
                                    <label for="phone" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                                        Phone Number
                                    </label>
                                    <input type="tel" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                           style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 6px;"
                                           placeholder="+234 xxx xxxx xxx">
                                </div>
                            </div>

                            <!-- Location -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div>
                                    <label for="state" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                                        State
                                    </label>
                                    <input type="text" id="state" name="state" 
                                           value="<?php echo htmlspecialchars($user['state'] ?? ''); ?>"
                                           style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 6px;"
                                           placeholder="e.g., Lagos">
                                </div>

                                <div>
                                    <label for="city" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                                        City
                                    </label>
                                    <input type="text" id="city" name="city" 
                                           value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>"
                                           style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 6px;"
                                           placeholder="e.g., Ikeja">
                                </div>
                            </div>

                            <!-- Address -->
                            <div>
                                <label for="address" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                                    Office Address
                                </label>
                                <textarea id="address" name="address" rows="2"
                                          style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 6px; resize: vertical;"
                                          placeholder="Enter your office address..."><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>

                            <!-- Submit Button -->
                            <div style="margin-top: 1rem;">
                                <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2rem;">
                                    <i class="fas fa-save"></i> Update Profile
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Profile Preview -->
                <div class="profile-preview" style="background: var(--surface); padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); height: fit-content;">
                    <h3 style="margin: 0 0 1.5rem 0;">Profile Preview</h3>
                    
                    <div style="text-align: center; margin-bottom: 2rem;">
                        <div style="width: 120px; height: 120px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 2.5rem; font-weight: bold; position: relative; cursor: pointer; border: 4px solid rgba(220, 38, 38, 0.2); transition: all 0.3s ease; overflow: hidden;"
                             onclick="document.getElementById('companyLogoInput').click()"
                             onmouseover="this.style.transform='scale(1.05)'; this.style.borderColor='rgba(220, 38, 38, 0.4)'"
                             onmouseout="this.style.transform='scale(1)'; this.style.borderColor='rgba(220, 38, 38, 0.2)'">
                            <?php if (!empty($user['logo'])): ?>
                                <img src="/findajob/uploads/profile-pictures/<?php echo htmlspecialchars($user['logo']); ?>" 
                                     alt="Company Logo" id="companyLogoPreview"
                                     style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <span id="companyInitials"><?php echo strtoupper(substr($user['company_name'] ?? 'C', 0, 1)); ?></span>
                            <?php endif; ?>
                            <div style="position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.7); color: white; padding: 0.5rem; font-size: 0.75rem; opacity: 0; transition: opacity 0.3s ease;" 
                                 onmouseover="this.style.opacity='1'"
                                 onmouseout="this.style.opacity='0'">
                                <i class="fas fa-camera"></i> Change Logo
                            </div>
                        </div>
                        <input type="file" id="companyLogoInput" accept="image/*" style="display: none;">
                        <h4 style="margin: 0; color: var(--text-primary);">
                            <?php echo htmlspecialchars($user['company_name'] ?? 'Company Name'); ?>
                        </h4>
                        <?php if ($user['industry']): ?>
                            <p style="margin: 0.5rem 0 0; color: var(--text-secondary);">
                                <?php echo htmlspecialchars(ucfirst(str_replace('_', ' & ', $user['industry']))); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="profile-details" style="border-top: 1px solid var(--border-color); padding-top: 1.5rem;">
                        <?php if ($user['email_verified']): ?>
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; color: var(--accent);">
                                <i class="fas fa-check-circle"></i>
                                <span>Verified Company</span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($user['company_size']): ?>
                            <div style="margin-bottom: 0.5rem;">
                                <strong>Size:</strong> <?php echo htmlspecialchars($user['company_size']); ?> employees
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($user['website']): ?>
                            <div style="margin-bottom: 0.5rem;">
                                <strong>Website:</strong> 
                                <a href="<?php echo htmlspecialchars($user['website']); ?>" target="_blank" 
                                   style="color: var(--primary);">Visit Site</a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($user['phone']): ?>
                            <div style="margin-bottom: 0.5rem;">
                                <strong>Phone:</strong> <?php echo htmlspecialchars($user['phone']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div style="margin-top: 2rem; text-align: center;">
                        <a href="dashboard.php" class="btn btn-outline" style="width: 100%;">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        async function loadLGAs() {
            const stateId = document.getElementById('state_id').value;
            const lgaSelect = document.getElementById('lga_id');
            
            // Clear current options
            lgaSelect.innerHTML = '<option value="">Select LGA</option>';
            
            if (!stateId) return;
            
            try {
                const response = await fetch(`/findajob/api/locations.php?action=lgas&state_id=${stateId}`);
                const data = await response.json();
                
                if (data.success && data.data) {
                    data.data.forEach(lga => {
                        const option = document.createElement('option');
                        option.value = lga.id;
                        option.textContent = lga.name;
                        lgaSelect.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Failed to load LGAs:', error);
            }
        }
        
        // Company Logo Upload Handler
        document.getElementById('companyLogoInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            // Validate file type
            if (!file.type.startsWith('image/')) {
                alert('Please select an image file');
                return;
            }
            
            // Validate file size (5MB max)
            if (file.size > 5 * 1024 * 1024) {
                alert('File size must be less than 5MB');
                return;
            }
            
            // Show loading state
            const logoContainer = document.querySelector('.profile-preview > div > div');
            const originalContent = logoContainer.innerHTML;
            logoContainer.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size: 2rem;"></i>';
            
            // Upload file
            const formData = new FormData();
            formData.append('profile_picture', file);
            
            fetch('/findajob/api/upload-profile-picture.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update logo with new image
                    const img = document.getElementById('companyLogoPreview');
                    
                    if (img) {
                        img.src = data.url + '?' + new Date().getTime(); // Cache bust
                    } else {
                        // Replace initials with image
                        logoContainer.innerHTML = `
                            <img src="${data.url}" alt="Company Logo" id="companyLogoPreview"
                                 style="width: 100%; height: 100%; object-fit: cover;">
                            <div style="position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.7); color: white; padding: 0.5rem; font-size: 0.75rem; opacity: 0; transition: opacity 0.3s ease;">
                                <i class="fas fa-camera"></i> Change Logo
                            </div>
                        `;
                    }
                    
                    // Show success message
                    alert('Company logo updated successfully!');
                    
                    // Reload page to update all instances
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert('Error: ' + data.error);
                    logoContainer.innerHTML = originalContent;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to upload company logo');
                logoContainer.innerHTML = originalContent;
            });
        });
    </script>
    
    <!-- Bottom Navigation for PWA -->
    <nav class="app-bottom-nav">
        <a href="../../index.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">🏠</div>
            <div class="app-bottom-nav-label">Home</div>
        </a>
        <a href="post-job.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">📝</div>
            <div class="app-bottom-nav-label">Post Job</div>
        </a>
        <a href="dashboard.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">📊</div>
            <div class="app-bottom-nav-label">Dashboard</div>
        </a>
        <a href="profile.php" class="app-bottom-nav-item active">
            <div class="app-bottom-nav-icon">🏢</div>
            <div class="app-bottom-nav-label">Company</div>
        </a>
    </nav>
</body>
</html>