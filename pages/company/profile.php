<?php
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/constants.php';

// Allow viewing with ID parameter (for admins) or require employer login
if (isset($_GET['id'])) {
    // Admin viewing someone else's profile
    if (!isLoggedIn() || !isAdmin()) {
        header('Location: ../../admin/login.php');
        exit;
    }
    $userId = (int)$_GET['id'];
} else {
    // Employer viewing their own profile
    requireEmployer();
    $userId = getCurrentUserId();
}

// Get employer profile data
$stmt = $pdo->prepare("
    SELECT u.id, u.user_type, u.email, u.first_name, u.last_name, u.phone,
           u.email_verified, u.phone_verified, u.phone_verified_at,
           u.is_active, u.created_at, u.updated_at,
           u.subscription_status, u.subscription_plan, u.subscription_type, u.subscription_start, u.subscription_end,
           ep.id as profile_id, ep.company_name, ep.description as company_description,
           ep.website, ep.industry, ep.company_size, ep.address,
           ep.state, ep.city,
           ep.provider_first_name, ep.provider_last_name, ep.provider_phone,
           ep.provider_phone_verified, ep.provider_phone_verified_at,
           ep.provider_date_of_birth, ep.provider_gender,
           ep.provider_state_of_origin, ep.provider_lga_of_origin, 
           ep.provider_city_of_birth, ep.provider_religion,
           ep.provider_profile_picture, ep.provider_nin, ep.provider_nin_verified,
           ep.provider_nin_verified_at,
           ep.company_logo, ep.company_cac_number,
           ep.company_cac_verified, ep.company_cac_verified_at,
           ep.verification_boosted, ep.verification_boost_date, ep.job_boost_credits,
           u.profile_picture
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
if ($_POST) {
    // Company Information
    $company_name = trim($_POST['company_name'] ?? '');
    $company_description = trim($_POST['company_description'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $industry = trim($_POST['industry'] ?? '');
    $company_size = trim($_POST['company_size'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    // Provider/Representative Information
    $provider_first_name = trim($_POST['provider_first_name'] ?? '');
    $provider_last_name = trim($_POST['provider_last_name'] ?? '');
    $provider_phone = trim($_POST['provider_phone'] ?? '');
    $provider_gender = trim($_POST['provider_gender'] ?? '');
    $provider_dob = trim($_POST['provider_date_of_birth'] ?? '');
    
    // Validation
    if (empty($company_name)) {
        $errors[] = "Company name is required";
    }
    
    if (empty($provider_first_name)) {
        $errors[] = "Representative first name is required";
    }
    
    if (empty($provider_last_name)) {
        $errors[] = "Representative last name is required";
    }
    
    if (!empty($website) && !filter_var($website, FILTER_VALIDATE_URL)) {
        $errors[] = "Please enter a valid website URL";
    }
    
    if (!empty($provider_phone) && !preg_match('/^[\d\s\-\+\(\)]+$/', $provider_phone)) {
        $errors[] = "Please enter a valid phone number";
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Update users table (email remains same, update first_name, last_name for consistency)
            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ? WHERE id = ?");
            $stmt->execute([$provider_first_name, $provider_last_name, $provider_phone, $userId]);
            
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
                    state = ?, city = ?, address = ?,
                    provider_first_name = ?, provider_last_name = ?, provider_phone = ?,
                    provider_gender = ?, provider_date_of_birth = ?, 
                    updated_at = NOW()
                    WHERE user_id = ?
                ");
                $stmt->execute([
                    $company_name, $company_description, $website, 
                    $industry, $company_size,
                    $state, $city, $address,
                    $provider_first_name, $provider_last_name, $provider_phone,
                    $provider_gender, $provider_dob ?: null,
                    $userId
                ]);
            } else {
                // Insert new profile
                $stmt = $pdo->prepare("
                    INSERT INTO employer_profiles 
                    (user_id, company_name, description, website, industry, company_size, 
                     state, city, address, provider_first_name, provider_last_name, 
                     provider_phone, provider_gender, provider_date_of_birth, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([
                    $userId, $company_name, $company_description, $website, 
                    $industry, $company_size, $state, $city, $address,
                    $provider_first_name, $provider_last_name, $provider_phone,
                    $provider_gender, $provider_dob ?: null
                ]);
            }
            
            $pdo->commit();
            $success = "Profile updated successfully!";
            
            // Refresh user data
            $stmt = $pdo->prepare("
                SELECT u.id, u.user_type, u.email, u.first_name, u.last_name, u.phone,
                       u.email_verified, u.is_active, u.created_at, u.updated_at,
                       ep.id as profile_id, ep.company_name, ep.description as company_description,
                       ep.website, ep.industry, ep.company_size, ep.address,
                       ep.state, ep.city,
                       ep.provider_first_name, ep.provider_last_name, ep.provider_phone,
                       ep.provider_date_of_birth, ep.provider_gender,
                       ep.provider_profile_picture,
                       ep.company_logo,
                       u.profile_picture
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
    <?php include '../../includes/employer-header.php'; ?>

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
            
            <?php 
            $verificationBoosted = $user['verification_boosted'] ?? 0;
            $ninVerified = $user['provider_nin_verified'] ?? 0;
            $cacVerified = $user['company_cac_verified'] ?? 0;
            ?>
            
            <?php if (!$ninVerified && !$verificationBoosted && !$cacVerified): ?>
            <!-- Verification Booster Banner -->
            <div style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border-left: 4px solid #1e40af; padding: 1.25rem; border-radius: 8px; margin-bottom: 1.5rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 250px;">
                        <h3 style="margin: 0 0 0.5rem 0; font-size: 1rem; color: #1e3a8a; display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-size: 1.5rem;">✅</span>
                            Get Verified Company Badge
                        </h3>
                        <p style="margin: 0; color: #1e40af; font-size: 0.875rem;">
                            Show job seekers you're a verified employer. Add a verification badge to your company profile for ₦1,000.
                        </p>
                    </div>
                    <div>
                        <button onclick="initializePayment('employer_verification_booster', 1000, 'Verification Badge')" class="btn btn-primary" style="white-space: nowrap; background: #1e40af; border-color: #1e40af;">
                            ✅ Get Verified (₦1,000)
                        </button>
                    </div>
                </div>
            </div>
            <?php elseif ($verificationBoosted): ?>
            <!-- Verified Badge Active -->
            <div style="background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); border-left: 4px solid #059669; padding: 1.25rem; border-radius: 8px; margin-bottom: 1.5rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <span style="font-size: 2rem;">✅</span>
                    <div style="flex: 1;">
                        <h3 style="margin: 0 0 0.25rem 0; font-size: 1rem; color: #065f46; font-weight: 600;">
                            Verified Company Badge Active
                        </h3>
                        <p style="margin: 0; color: #047857; font-size: 0.875rem;">
                            Your company has a verified badge that appears on all your job postings and company profile.
                        </p>
                    </div>
                </div>
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

                            <!-- CAC Verification -->
                            <div style="background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); padding: 1.5rem; border-radius: 8px; border-left: 4px solid #dc2626;">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                    <div>
                                        <h3 style="margin: 0 0 0.5rem 0; color: #dc2626; font-size: 1.125rem;">
                                            <i class="fas fa-building"></i> CAC Verification
                                        </h3>
                                        <?php if (!empty($user['company_cac_verified'])): ?>
                                            <p style="margin: 0; color: #059669; font-weight: 500; display: flex; align-items: center; gap: 0.5rem;">
                                                <i class="fas fa-check-circle"></i> Verified
                                            </p>
                                        <?php else: ?>
                                            <p style="margin: 0; color: #6b7280; font-size: 0.875rem;">
                                                Verify your company registration with CAC
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (empty($user['company_cac_verified'])): ?>
                                        <button type="button" onclick="openCACModal()" 
                                                style="padding: 0.625rem 1.25rem; background: #dc2626; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; white-space: nowrap; font-size: 0.875rem; transition: all 0.2s;">
                                            <i class="fas fa-shield-alt"></i> Verify Now
                                        </button>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (!empty($user['company_cac_verified'])): ?>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(220, 38, 38, 0.2);">
                                        <div>
                                            <small style="color: #6b7280; font-weight: 500;">RC Number</small>
                                            <div style="color: #1f2937; font-weight: 600;"><?php echo htmlspecialchars($user['company_cac_number'] ?? 'N/A'); ?></div>
                                        </div>
                                        <div>
                                            <small style="color: #6b7280; font-weight: 500;">Company Type</small>
                                            <div style="color: #1f2937; font-weight: 600;"><?php echo htmlspecialchars(str_replace('_', ' ', $user['company_type'] ?? 'N/A')); ?></div>
                                        </div>
                                        <div>
                                            <small style="color: #6b7280; font-weight: 500;">Verified On</small>
                                            <div style="color: #1f2937; font-weight: 600;">
                                                <?php 
                                                if (!empty($user['company_cac_verified_at'])) {
                                                    echo date('M d, Y', strtotime($user['company_cac_verified_at']));
                                                } else {
                                                    echo 'N/A';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div style="margin-top: 1rem; padding: 1rem; background: white; border-radius: 6px;">
                                        <ul style="margin: 0; padding-left: 1.5rem; color: #6b7280; font-size: 0.875rem; line-height: 1.75;">
                                            <li>Builds trust with job seekers</li>
                                            <li>Verification is <strong style="color: #059669;">FREE</strong></li>
                                            <li>Takes less than 2 minutes</li>
                                            <li>One-time verification</li>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Website -->
                            <div>
                                <label for="website" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                                    Company Website
                                </label>
                                <input type="url" id="website" name="website" 
                                       value="<?php echo htmlspecialchars($user['website'] ?? ''); ?>"
                                       style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 6px;"
                                       placeholder="https://www.yourcompany.com">
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

                            <!-- Divider with Icon -->
                            <div style="border-top: 2px solid #e5e7eb; margin: 2rem 0;"></div>
                            
                            <!-- Representative Information Section Header -->
                            <div id="nin-verification" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                                <h3 style="margin: 0; color: #1f2937; font-size: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fas fa-user-tie" style="color: #dc2626;"></i> 
                                    <span>Company Representative</span>
                                    <?php if (!empty($user['provider_nin_verified'])): ?>
                                        <span style="display: inline-flex; align-items: center; gap: 0.35rem; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; font-size: 0.7rem; padding: 0.3rem 0.6rem; border-radius: 12px; font-weight: 600; box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);">
                                            <i class="fas fa-check-circle"></i> NIN Verified
                                        </span>
                                    <?php endif; ?>
                                </h3>
                            </div>
                            <p style="margin: 0 0 1.5rem 0; color: #6b7280; font-size: 0.875rem; line-height: 1.5;">
                                Information about the person managing this account on behalf of the company
                                <?php if (!empty($user['provider_nin_verified'])): ?>
                                    <br><span style="color: #10b981; font-weight: 500; font-size: 0.8rem;">
                                        <i class="fas fa-shield-check"></i> Personal information has been verified with NIN
                                    </span>
                                <?php endif; ?>
                            </p>

                            <!-- Provider Name -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                <div>
                                    <label for="provider_first_name" style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; font-weight: 500; color: #374151; font-size: 0.875rem;">
                                        First Name 
                                        <span style="color: #dc2626;">*</span>
                                        <?php if (!empty($user['provider_nin_verified'])): ?>
                                            <i class="fas fa-lock" style="color: #10b981; font-size: 0.75rem;" title="Locked after NIN verification"></i>
                                        <?php endif; ?>
                                    </label>
                                    <input type="text" id="provider_first_name" name="provider_first_name" 
                                           value="<?php echo htmlspecialchars($user['provider_first_name'] ?? $user['first_name'] ?? ''); ?>"
                                           style="width: 100%; padding: 0.625rem 0.875rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; transition: all 0.2s; <?php echo !empty($user['provider_nin_verified']) ? 'background: #f9fafb; color: #6b7280; cursor: not-allowed;' : 'background: white;'; ?>"
                                           placeholder="e.g., Jalal Uddin"
                                           <?php echo !empty($user['provider_nin_verified']) ? 'readonly' : 'required'; ?>>
                                    <?php if (!empty($user['provider_nin_verified'])): ?>
                                        <small style="display: block; margin-top: 0.25rem; color: #10b981; font-size: 0.75rem; font-weight: 500;">
                                            <i class="fas fa-check-circle" style="font-size: 0.7rem;"></i> Verified from NIN data
                                        </small>
                                    <?php endif; ?>
                                </div>

                                <div>
                                    <label for="provider_last_name" style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; font-weight: 500; color: #374151; font-size: 0.875rem;">
                                        Last Name 
                                        <span style="color: #dc2626;">*</span>
                                        <?php if (!empty($user['provider_nin_verified'])): ?>
                                            <i class="fas fa-lock" style="color: #10b981; font-size: 0.75rem;" title="Locked after NIN verification"></i>
                                        <?php endif; ?>
                                    </label>
                                    <input type="text" id="provider_last_name" name="provider_last_name" 
                                           value="<?php echo htmlspecialchars($user['provider_last_name'] ?? $user['last_name'] ?? ''); ?>"
                                           style="width: 100%; padding: 0.625rem 0.875rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; transition: all 0.2s; <?php echo !empty($user['provider_nin_verified']) ? 'background: #f9fafb; color: #6b7280; cursor: not-allowed;' : 'background: white;'; ?>"
                                           placeholder="e.g., Taj"
                                           <?php echo !empty($user['provider_nin_verified']) ? 'readonly' : 'required'; ?>>
                                    <?php if (!empty($user['provider_nin_verified'])): ?>
                                        <small style="display: block; margin-top: 0.25rem; color: #10b981; font-size: 0.75rem; font-weight: 500;">
                                            <i class="fas fa-check-circle" style="font-size: 0.7rem;"></i> Verified from NIN data
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Provider Contact and Personal Info -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                <div>
                                    <label for="provider_phone" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem; font-weight: 500; color: #374151; font-size: 0.875rem;">
                                        <span>Phone Number</span>
                                        <?php if (!empty($user['provider_phone_verified'])): ?>
                                            <span style="display: inline-flex; align-items: center; gap: 0.35rem; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; font-size: 0.65rem; padding: 0.25rem 0.5rem; border-radius: 10px; font-weight: 600;">
                                                <i class="fas fa-check-circle"></i> Verified
                                            </span>
                                        <?php else: ?>
                                            <button type="button" onclick="openPhoneModal()" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; padding: 0.25rem 0.65rem; border-radius: 10px; font-size: 0.65rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 0.3rem;">
                                                <i class="fas fa-shield-alt"></i> Verify
                                            </button>
                                        <?php endif; ?>
                                    </label>
                                    <input type="tel" id="provider_phone" name="provider_phone" 
                                           value="<?php echo htmlspecialchars($user['provider_phone'] ?? $user['phone'] ?? ''); ?>"
                                           style="width: 100%; padding: 0.625rem 0.875rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; <?php echo !empty($user['provider_phone_verified']) ? 'background: #f9fafb; color: #6b7280; cursor: not-allowed;' : 'background: white;'; ?>"
                                           placeholder="+234 xxx xxx xxxx"
                                           <?php echo !empty($user['provider_phone_verified']) ? 'readonly' : ''; ?>>
                                    <?php if (!empty($user['provider_phone_verified'])): ?>
                                        <small style="display: block; margin-top: 0.25rem; color: #10b981; font-size: 0.7rem; font-weight: 500;">
                                            <i class="fas fa-check-circle" style="font-size: 0.65rem;"></i> Verified on <?php echo date('M d, Y', strtotime($user['provider_phone_verified_at'])); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>

                                <div>
                                    <label for="provider_gender" style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #374151; font-size: 0.875rem;">
                                        Gender
                                    </label>
                                    <select id="provider_gender" name="provider_gender" 
                                            style="width: 100%; padding: 0.625rem 0.875rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; background: white;">
                                        <option value="">Select Gender</option>
                                        <option value="male" <?php echo ($user['provider_gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="female" <?php echo ($user['provider_gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                        <option value="other" <?php echo ($user['provider_gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Provider Date of Birth -->
                            <div style="margin-bottom: 1rem;">
                                <label for="provider_date_of_birth" style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; font-weight: 500; color: #374151; font-size: 0.875rem;">
                                    Date of Birth
                                    <?php if (!empty($user['provider_nin_verified'])): ?>
                                        <i class="fas fa-lock" style="color: #10b981; font-size: 0.75rem;" title="Locked after NIN verification"></i>
                                    <?php endif; ?>
                                </label>
                                <input type="date" id="provider_date_of_birth" name="provider_date_of_birth" 
                                       value="<?php echo htmlspecialchars($user['provider_date_of_birth'] ?? ''); ?>"
                                       style="width: 100%; padding: 0.625rem 0.875rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; <?php echo !empty($user['provider_nin_verified']) ? 'background: #f9fafb; color: #6b7280; cursor: not-allowed;' : 'background: white;'; ?>"
                                       max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>"
                                       <?php echo !empty($user['provider_nin_verified']) ? 'readonly' : ''; ?>>
                                <?php if (!empty($user['provider_nin_verified'])): ?>
                                    <small style="display: block; margin-top: 0.25rem; color: #10b981; font-size: 0.75rem; font-weight: 500;">
                                        <i class="fas fa-check-circle" style="font-size: 0.7rem;"></i> Verified from NIN data
                                    </small>
                                <?php else: ?>
                                    <small style="display: block; margin-top: 0.25rem; color: #6b7280; font-size: 0.75rem;">
                                        Must be at least 18 years old
                                    </small>
                                <?php endif; ?>
                            </div>

                            <!-- Submit Button -->
                            <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb;">
                                <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2rem; font-weight: 600; border-radius: 6px; background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); border: none; box-shadow: 0 2px 4px rgba(220, 38, 38, 0.2); transition: all 0.2s;">
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
                            <?php if (!empty($user['company_logo'])): ?>
                                <img src="../../uploads/profile-pictures/<?php echo htmlspecialchars($user['company_logo']); ?>" 
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
        
        // Payment initialization function
        function initializePayment(serviceType, amount, description) {
            const button = event.target;
            const originalText = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            const formData = new FormData();
            formData.append('action', 'initialize_payment');
            formData.append('amount', amount);
            formData.append('service_type', serviceType);
            formData.append('description', description);
            
            fetch('/findajob/api/payment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.payment_link) {
                    // Redirect to Flutterwave payment page
                    window.location.href = data.data.payment_link;
                } else {
                    alert('Error: ' + (data.error || 'Failed to initialize payment'));
                    button.disabled = false;
                    button.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Payment error:', error);
                alert('Network error. Please try again.');
                button.disabled = false;
                button.innerHTML = originalText;
            });
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
                            <img src="../../uploads/profile-pictures/${data.filename}" alt="Company Logo" id="companyLogoPreview"
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

    <?php include '../../includes/phone-verification-modal.php'; ?>
    <?php include '../../includes/cac-verification-modal.php'; ?>
</body>
</html>