<?php
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/constants.php';
require_once '../../includes/functions.php';

requireJobSeeker();

$userId = getCurrentUserId();

// Get user profile data with explicit column selection to avoid conflicts
$stmt = $pdo->prepare("
    SELECT 
        u.id, u.user_type, u.email, u.first_name, u.last_name, u.phone, 
        u.email_verified, u.is_active, u.created_at as user_created_at, u.updated_at as user_updated_at,
        jsp.id as profile_id, jsp.user_id, jsp.date_of_birth, jsp.gender, 
        jsp.state_of_origin, jsp.lga_of_origin, jsp.current_state, jsp.current_city,
        jsp.education_level, jsp.years_of_experience, jsp.job_status,
        jsp.salary_expectation_min, jsp.salary_expectation_max, jsp.skills, jsp.bio,
        COALESCE(jsp.profile_picture, u.profile_picture) as profile_picture, 
        jsp.nin, jsp.nin_verified, jsp.nin_verified_at, jsp.bvn, jsp.is_verified, jsp.verification_status,
        jsp.subscription_type, jsp.subscription_expires,
        jsp.created_at as profile_created_at, jsp.updated_at as profile_updated_at
    FROM users u 
    LEFT JOIN job_seeker_profiles jsp ON u.id = jsp.user_id 
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Check if job_seeker_profiles record exists, create if not
$profileCheckStmt = $pdo->prepare("SELECT COUNT(*) FROM job_seeker_profiles WHERE user_id = ?");
$profileCheckStmt->execute([$userId]);
$profileExists = $profileCheckStmt->fetchColumn();

if (!$profileExists) {
    try {
        $createProfileStmt = $pdo->prepare("
            INSERT IGNORE INTO job_seeker_profiles (user_id) VALUES (?)
        ");
        $createProfileStmt->execute([$userId]);
        
        // Fetch user data again with the new profile
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error creating job seeker profile: " . $e->getMessage());
    }
}

// Get Nigerian states for dropdown
$statesStmt = $pdo->prepare("SELECT * FROM nigeria_states ORDER BY name");
$statesStmt->execute();
$states = $statesStmt->fetchAll();

// Get job categories for preferences
$categoriesStmt = $pdo->prepare("SELECT * FROM job_categories WHERE is_active = 1 ORDER BY name");
$categoriesStmt->execute();
$categories = $categoriesStmt->fetchAll();

// Get user education records
$educationStmt = $pdo->prepare("SELECT * FROM user_education WHERE user_id = ? ORDER BY end_year DESC, start_year DESC");
$educationStmt->execute([$userId]);
$education_records = $educationStmt->fetchAll();

// Get user work experience records
$experienceStmt = $pdo->prepare("SELECT * FROM user_work_experience WHERE user_id = ? ORDER BY is_current DESC, end_date DESC, start_date DESC");
$experienceStmt->execute([$userId]);
$experience_records = $experienceStmt->fetchAll();

// Helper function to get education level name
function getEducationLevelName($level) {
    $levels = [
        'ssce' => 'SSCE/O\'Levels',
        'ond' => 'OND',
        'hnd' => 'HND',
        'bsc' => 'B.Sc/B.A',
        'msc' => 'M.Sc/M.A',
        'phd' => 'PhD',
        'other' => 'Other'
    ];
    return $levels[$level] ?? 'Unknown';
}

// Handle form submission
if ($_POST) {
    try {
        $pdo->beginTransaction();
        
        // Update users table
        $updateUserStmt = $pdo->prepare("
            UPDATE users 
            SET first_name = ?, last_name = ?, phone = ? 
            WHERE id = ?
        ");
        $updateUserStmt->execute([
            $_POST['first_name'],
            $_POST['last_name'], 
            $_POST['phone'],
            $userId
        ]);
        
        // Check if profile exists
        $profileCheckStmt = $pdo->prepare("SELECT id FROM job_seeker_profiles WHERE user_id = ?");
        $profileCheckStmt->execute([$userId]);
        $profileExists = $profileCheckStmt->fetchColumn();
        
        if ($profileExists) {
            // Update existing profile
            $updateProfileStmt = $pdo->prepare("
                UPDATE job_seeker_profiles 
                SET date_of_birth = ?, gender = ?, state_of_origin = ?, 
                    current_state = ?, current_city = ?,
                    years_of_experience = ?, job_status = ?, 
                    salary_expectation_min = ?, salary_expectation_max = ?,
                    skills = ?, bio = ?
                WHERE user_id = ?
            ");
            $updateProfileStmt->execute([
                $_POST['date_of_birth'],
                $_POST['gender'],
                $_POST['state_of_origin'],
                $_POST['current_state'],
                $_POST['current_city'],
                $_POST['years_of_experience'],
                $_POST['job_status'],
                $_POST['salary_expectation_min'],
                $_POST['salary_expectation_max'],
                $_POST['skills'],
                $_POST['bio'],
                $userId
            ]);
        } else {
            // Insert new profile
            $insertProfileStmt = $pdo->prepare("
                INSERT INTO job_seeker_profiles 
                (user_id, date_of_birth, gender, state_of_origin, current_state, 
                 current_city, years_of_experience, job_status,
                 salary_expectation_min, salary_expectation_max, skills, bio)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insertProfileStmt->execute([
                $userId,
                $_POST['date_of_birth'],
                $_POST['gender'],
                $_POST['state_of_origin'],
                $_POST['current_state'],
                $_POST['current_city'],
                $_POST['years_of_experience'],
                $_POST['job_status'],
                $_POST['salary_expectation_min'],
                $_POST['salary_expectation_max'],
                $_POST['skills'],
                $_POST['bio']
            ]);
        }
        
        // Handle education entries
        if (isset($_POST['education']) && is_array($_POST['education'])) {
            // First, delete existing education records for this user
            $deleteEducationStmt = $pdo->prepare("DELETE FROM user_education WHERE user_id = ?");
            $deleteEducationStmt->execute([$userId]);
            
            // Insert new education records
            $insertEducationStmt = $pdo->prepare("
                INSERT INTO user_education 
                (user_id, education_level, institution_name, field_of_study, start_year, end_year, grade_result, is_current) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($_POST['education'] as $education) {
                if (!empty($education['education_level']) && !empty($education['institution_name'])) {
                    $insertEducationStmt->execute([
                        $userId,
                        $education['education_level'],
                        $education['institution_name'],
                        $education['field_of_study'] ?? null,
                        !empty($education['start_year']) ? $education['start_year'] : null,
                        !empty($education['end_year']) ? $education['end_year'] : null,
                        $education['grade_result'] ?? null,
                        isset($education['is_current']) ? 1 : 0
                    ]);
                }
            }
        }
        
        // Handle work experience entries
        if (isset($_POST['experience']) && is_array($_POST['experience'])) {
            // First, delete existing work experience records for this user
            $deleteExperienceStmt = $pdo->prepare("DELETE FROM user_work_experience WHERE user_id = ?");
            $deleteExperienceStmt->execute([$userId]);
            
            // Insert new work experience records
            $insertExperienceStmt = $pdo->prepare("
                INSERT INTO user_work_experience 
                (user_id, job_title, company_name, industry, employment_type, location, 
                 start_date, end_date, is_current, job_description, key_achievements, skills_used, salary_range) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($_POST['experience'] as $experience) {
                if (!empty($experience['job_title']) && !empty($experience['company_name'])) {
                    $insertExperienceStmt->execute([
                        $userId,
                        $experience['job_title'],
                        $experience['company_name'],
                        $experience['industry'] ?? null,
                        $experience['employment_type'] ?? 'full_time',
                        $experience['location'] ?? null,
                        !empty($experience['start_date']) ? $experience['start_date'] : null,
                        (!empty($experience['end_date']) && !isset($experience['is_current'])) ? $experience['end_date'] : null,
                        isset($experience['is_current']) ? 1 : 0,
                        $experience['job_description'] ?? null,
                        $experience['key_achievements'] ?? null,
                        $experience['skills_used'] ?? null,
                        $experience['salary_range'] ?? null
                    ]);
                }
            }
        }
        
        $pdo->commit();
        
        // Refresh user data, education records, and experience records
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        $educationStmt->execute([$userId]);
        $education_records = $educationStmt->fetchAll();
        
        $experienceStmt->execute([$userId]);
        $experience_records = $experienceStmt->fetchAll();
        
        $success_message = "Profile updated successfully!";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "Error updating profile: " . $e->getMessage();
    }
}

// Calculate profile completion percentage using shared function
$profileCompletion = calculateProfileCompletion($user);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin: 0 auto 1rem;
            position: relative;
            overflow: hidden;
            border: 4px solid rgba(255,255,255,0.3);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .profile-avatar:hover {
            transform: scale(1.05);
            border-color: rgba(255,255,255,0.5);
        }
        
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .profile-avatar-upload {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 0.5rem;
            font-size: 0.75rem;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .profile-avatar:hover .profile-avatar-upload {
            opacity: 1;
        }
        
        #profilePictureInput {
            display: none;
        }
        
        .completion-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-top: 1rem;
        }
        
        .profile-sections {
            display: grid;
            gap: 2rem;
        }
        
        .profile-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .section-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--primary-light);
        }
        
        .section-icon {
            font-size: 1.5rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }
        
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .skills-input {
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.99 1.99 0 013 12V7a2 2 0 012-2z" /></svg>');
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 20px;
        }
        
        .verification-status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .verification-status.verified {
            background: var(--accent);
            color: white;
        }
        
        .verification-status.pending {
            background: var(--warning);
            color: white;
        }
        
        .verification-status.unverified {
            background: var(--text-secondary);
            color: white;
        }
        
        .verification-success {
            text-align: center;
            padding: 2rem;
        }
        
        .verification-badge.verified {
            font-size: 1.2rem;
            padding: 1rem 2rem;
            border-radius: 50px;
            margin-bottom: 1rem;
        }
        
        .service-card {
            border: 2px solid var(--primary-light);
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
        }
        
        .service-header {
            margin-bottom: 2rem;
        }
        
        .service-header h3 {
            color: var(--primary);
            margin-bottom: 0.5rem;
            font-size: 1.5rem;
        }
        
        .service-price {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary);
        }
        
        .service-price span {
            font-size: 1rem;
            color: var(--text-secondary);
            font-weight: normal;
        }
        
        .service-benefits {
            text-align: left;
            margin-bottom: 2rem;
        }
        
        .service-benefits h4 {
            color: var(--text-primary);
            margin-bottom: 1rem;
        }
        
        .service-benefits ul {
            list-style: none;
            padding: 0;
        }
        
        .service-benefits li {
            padding: 0.5rem 0;
            color: var(--text-primary);
        }
        
        .verification-process {
            text-align: left;
            margin-bottom: 2rem;
        }
        
        .verification-process h4 {
            color: var(--text-primary);
            margin-bottom: 1rem;
        }
        
        .process-steps {
            display: grid;
            gap: 1rem;
        }
        
        .step {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .step-number {
            width: 30px;
            height: 30px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            flex-shrink: 0;
        }
        
        .btn-verify {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            margin-top: 1rem;
        }
        
        .btn-verify:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(220, 38, 38, 0.3);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .btn-add-education {
            background: var(--accent);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .btn-add-education:hover {
            background: #047857;
        }
        
        .education-entry {
            border: 2px solid var(--primary-light);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            background: #fefefe;
        }
        
        .education-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--primary-light);
        }
        
        .education-header h4 {
            color: var(--primary);
            margin: 0;
        }
        
        .btn-remove {
            background: #ef4444;
            color: white;
            border: none;
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .btn-remove:hover {
            background: #dc2626;
        }
        
        .form-checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: normal;
            cursor: pointer;
        }
        
        .form-checkbox input[type="checkbox"] {
            width: auto;
            margin: 0;
        }
        
        .no-education {
            text-align: center;
            padding: 2rem;
            border: 2px dashed var(--primary-light);
            border-radius: 8px;
            background: #fafafa;
        }
        
        .btn-add-experience {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .btn-add-experience:hover {
            background: #2563eb;
        }
        
        .experience-entry {
            border: 2px solid #bfdbfe;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            background: #fefefe;
        }
        
        .experience-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #bfdbfe;
        }
        
        .experience-header h4 {
            color: #1e40af;
            margin: 0;
            font-size: 1rem;
        }
        
        .no-experience {
            text-align: center;
            padding: 2rem;
            border: 2px dashed #bfdbfe;
            border-radius: 8px;
            background: #fafafa;
        }
        
        .salary-range {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 1rem;
            align-items: center;
        }
        
        .btn-save {
            background: var(--primary);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
            width: 100%;
            margin-top: 2rem;
        }
        
        .btn-save:hover {
            background: var(--primary-dark);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* NIN Verification Modal Styles */
        .nin-verification-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.2s ease;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        .nin-modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
        }
        
        .nin-modal-content {
            position: relative;
            background: white;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease;
        }
        
        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .nin-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .nin-modal-header h3 {
            margin: 0;
            color: var(--primary);
            font-size: 1.25rem;
        }
        
        .nin-modal-close {
            background: none;
            border: none;
            font-size: 2rem;
            color: #6b7280;
            cursor: pointer;
            line-height: 1;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: all 0.2s;
        }
        
        .nin-modal-close:hover {
            background: #f3f4f6;
            color: var(--primary);
        }
        
        .nin-modal-body {
            padding: 1.5rem;
        }
        
        .nin-alert {
            padding: 0.75rem 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        .nin-alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .nin-alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .nin-alert-info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }
        
        .verification-cost-summary {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
        }
        
        .cost-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }
        
        .cost-item strong {
            color: var(--primary);
            font-size: 1.25rem;
        }
        
        .cost-note {
            text-align: center;
            color: var(--text-secondary);
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px solid #e5e7eb;
        }
        
        .btn-block {
            width: 100%;
        }
        
        .btn-verify {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.875rem 1.5rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn-verify:hover:not(:disabled) {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }
        
        .btn-verify:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .verification-details {
            background: #f0fdf4;
            border: 1px solid #86efac;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .verification-details p {
            margin: 0.5rem 0;
            color: #166534;
        }
        
        /* Verified Badge Checkmark (like Facebook/LinkedIn) */
        .verified-checkmark {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            background: #1877f2; /* Facebook blue */
            border-radius: 50%;
            color: white;
            font-size: 14px;
            font-weight: bold;
            margin-left: 8px;
            vertical-align: middle;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            position: relative;
            top: -2px;
        }
        
        .verified-checkmark.large {
            width: 32px;
            height: 32px;
            font-size: 18px;
            margin-left: 12px;
        }
        
        .profile-header h1 {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        @media (max-width: 768px) {
            .profile-container {
                padding: 1rem;
            }
            
            .profile-section {
                padding: 1.5rem;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .salary-range {
                grid-template-columns: 1fr;
            }
            
            .nin-modal-content {
                width: 95%;
                margin: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>

    <main class="profile-container">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar" onclick="document.getElementById('profilePictureInput').click()">
                <?php if (!empty($user['profile_picture'])): ?>
                    <img src="/findajob/uploads/profile-pictures/<?php echo htmlspecialchars($user['profile_picture']); ?>" 
                         alt="Profile Picture" id="profilePicturePreview">
                <?php else: ?>
                    <span id="profileInitials"><?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?></span>
                <?php endif; ?>
                <div class="profile-avatar-upload">
                    <i class="fas fa-camera"></i> Change Photo
                </div>
            </div>
            <input type="file" id="profilePictureInput" accept="image/*">
            <h1>
                <span><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
                <?php if ($user['nin_verified']): ?>
                    <span class="verified-checkmark large" title="NIN Verified">✓</span>
                <?php endif; ?>
            </h1>
            <p><?php echo htmlspecialchars($user['email']); ?></p>
            
            <div class="completion-badge">
                Profile <?php echo $profileCompletion; ?>% Complete
            </div>
            
            <?php if ($user['nin_verified']): ?>
                <div class="verification-status verified">
                    ✓ NIN Verified
                </div>
            <?php else: ?>
                <div class="verification-status unverified">
                    ⚠ Verify Your NIN
                </div>
            <?php endif; ?>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form method="POST" class="profile-sections">
            <!-- Personal Information -->
            <div class="profile-section">
                <div class="section-header">
                    <span class="section-icon">👤</span>
                    <h2>Personal Information</h2>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" class="form-input" 
                               value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" class="form-input" 
                               value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="form-input" 
                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                               placeholder="+234 801 234 5678">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="date_of_birth">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" class="form-input" 
                               value="<?php echo $user['date_of_birth'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="gender">Gender</label>
                        <select id="gender" name="gender" class="form-select">
                            <option value="">Select Gender</option>
                            <option value="male" <?php echo ($user['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?php echo ($user['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                            <option value="other" <?php echo ($user['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Location Information -->
            <div class="profile-section">
                <div class="section-header">
                    <span class="section-icon">📍</span>
                    <h2>Location Information</h2>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="state_of_origin">State of Origin</label>
                        <select id="state_of_origin" name="state_of_origin" class="form-select">
                            <option value="">Select State of Origin</option>
                            <?php foreach ($states as $state): ?>
                                <option value="<?php echo htmlspecialchars($state['name']); ?>" 
                                        <?php echo ($user['state_of_origin'] ?? '') === $state['name'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($state['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="current_state">Current State</label>
                        <select id="current_state" name="current_state" class="form-select">
                            <option value="">Select Current State</option>
                            <?php foreach ($states as $state): ?>
                                <option value="<?php echo htmlspecialchars($state['name']); ?>" 
                                        <?php echo ($user['current_state'] ?? '') === $state['name'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($state['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group full-width">
                        <label class="form-label" for="current_city">Current City</label>
                        <input type="text" id="current_city" name="current_city" class="form-input" 
                               value="<?php echo htmlspecialchars($user['current_city'] ?? ''); ?>" 
                               placeholder="e.g., Lagos, Abuja, Port Harcourt">
                    </div>
                </div>
            </div>

            <!-- Education History -->
            <div class="profile-section">
                <div class="section-header">
                    <span class="section-icon">🎓</span>
                    <h2>Education History</h2>
                    <button type="button" class="btn-add-education" onclick="addEducationEntry()">
                        + Add Education
                    </button>
                </div>
                
                <div id="education-container">
                    <?php if (empty($education_records)): ?>
                        <div class="no-education">
                            <p style="text-align: center; color: var(--text-secondary); margin: 2rem 0;">
                                No education records added yet. Click "Add Education" to get started.
                            </p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($education_records as $index => $education): ?>
                            <div class="education-entry" data-index="<?php echo $index; ?>">
                                <div class="education-header">
                                    <h4><?php echo getEducationLevelName($education['education_level']); ?></h4>
                                    <button type="button" class="btn-remove" onclick="removeEducationEntry(<?php echo $index; ?>)">Remove</button>
                                </div>
                                
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label class="form-label">Education Level</label>
                                        <select name="education[<?php echo $index; ?>][education_level]" class="form-select" required>
                                            <option value="">Select Level</option>
                                            <option value="ssce" <?php echo $education['education_level'] === 'ssce' ? 'selected' : ''; ?>>SSCE/O'Levels</option>
                                            <option value="ond" <?php echo $education['education_level'] === 'ond' ? 'selected' : ''; ?>>OND</option>
                                            <option value="hnd" <?php echo $education['education_level'] === 'hnd' ? 'selected' : ''; ?>>HND</option>
                                            <option value="bsc" <?php echo $education['education_level'] === 'bsc' ? 'selected' : ''; ?>>B.Sc/B.A</option>
                                            <option value="msc" <?php echo $education['education_level'] === 'msc' ? 'selected' : ''; ?>>M.Sc/M.A</option>
                                            <option value="phd" <?php echo $education['education_level'] === 'phd' ? 'selected' : ''; ?>>PhD</option>
                                            <option value="other" <?php echo $education['education_level'] === 'other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group full-width">
                                        <label class="form-label">Institution Name</label>
                                        <input type="text" name="education[<?php echo $index; ?>][institution_name]" class="form-input" 
                                               value="<?php echo htmlspecialchars($education['institution_name']); ?>" required>
                                    </div>
                                    
                                    <div class="form-group full-width">
                                        <label class="form-label">Field of Study</label>
                                        <input type="text" name="education[<?php echo $index; ?>][field_of_study]" class="form-input" 
                                               value="<?php echo htmlspecialchars($education['field_of_study'] ?? ''); ?>" 
                                               placeholder="e.g., Computer Science, Business Administration">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Start Year</label>
                                        <input type="number" name="education[<?php echo $index; ?>][start_year]" class="form-input" 
                                               value="<?php echo $education['start_year']; ?>" min="1950" max="<?php echo date('Y'); ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">End Year</label>
                                        <input type="number" name="education[<?php echo $index; ?>][end_year]" class="form-input" 
                                               value="<?php echo $education['end_year']; ?>" min="1950" max="<?php echo date('Y') + 10; ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Grade/Result</label>
                                        <input type="text" name="education[<?php echo $index; ?>][grade_result]" class="form-input" 
                                               value="<?php echo htmlspecialchars($education['grade_result'] ?? ''); ?>" 
                                               placeholder="e.g., First Class, 2:1, Distinction">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-checkbox">
                                            <input type="checkbox" name="education[<?php echo $index; ?>][is_current]" 
                                                   <?php echo $education['is_current'] ? 'checked' : ''; ?>>
                                            Currently studying here
                                        </label>
                                    </div>
                                </div>
                                
                                <input type="hidden" name="education[<?php echo $index; ?>][id]" value="<?php echo $education['id']; ?>">
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Work Experience -->
            <div class="profile-section">
                <div class="section-header">
                    <span class="section-icon">💼</span>
                    <h2>Work Experience</h2>
                    <button type="button" class="btn-add-experience" onclick="addExperienceEntry()">
                        + Add Experience
                    </button>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="years_of_experience">Total Years of Experience</label>
                    <select id="years_of_experience" name="years_of_experience" class="form-select">
                        <option value="0" <?php echo ($user['years_of_experience'] ?? 0) == 0 ? 'selected' : ''; ?>>Fresh Graduate</option>
                        <option value="1" <?php echo ($user['years_of_experience'] ?? 0) == 1 ? 'selected' : ''; ?>>1 Year</option>
                        <option value="2" <?php echo ($user['years_of_experience'] ?? 0) == 2 ? 'selected' : ''; ?>>2 Years</option>
                        <option value="3" <?php echo ($user['years_of_experience'] ?? 0) == 3 ? 'selected' : ''; ?>>3 Years</option>
                        <option value="4" <?php echo ($user['years_of_experience'] ?? 0) == 4 ? 'selected' : ''; ?>>4 Years</option>
                        <option value="5" <?php echo ($user['years_of_experience'] ?? 0) == 5 ? 'selected' : ''; ?>>5 Years</option>
                        <option value="10" <?php echo ($user['years_of_experience'] ?? 0) >= 10 ? 'selected' : ''; ?>>10+ Years</option>
                    </select>
                </div>
                
                <div id="experience-container">
                    <?php if (empty($experience_records)): ?>
                        <div class="no-experience">
                            <p style="text-align: center; color: var(--text-secondary); margin: 2rem 0;">
                                No work experience added yet. Click "Add Experience" to get started.
                            </p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($experience_records as $index => $experience): ?>
                            <div class="experience-entry" data-index="<?php echo $index; ?>">
                                <div class="experience-header">
                                    <h4><?php echo htmlspecialchars($experience['job_title'] . ' at ' . $experience['company_name']); ?></h4>
                                    <button type="button" class="btn-remove" onclick="removeExperienceEntry(<?php echo $index; ?>)">Remove</button>
                                </div>
                                
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label class="form-label">Job Title</label>
                                        <input type="text" name="experience[<?php echo $index; ?>][job_title]" class="form-input" 
                                               value="<?php echo htmlspecialchars($experience['job_title']); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Company Name</label>
                                        <input type="text" name="experience[<?php echo $index; ?>][company_name]" class="form-input" 
                                               value="<?php echo htmlspecialchars($experience['company_name']); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Industry</label>
                                        <input type="text" name="experience[<?php echo $index; ?>][industry]" class="form-input" 
                                               value="<?php echo htmlspecialchars($experience['industry'] ?? ''); ?>" 
                                               placeholder="e.g., Technology, Banking, Healthcare">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Employment Type</label>
                                        <select name="experience[<?php echo $index; ?>][employment_type]" class="form-select">
                                            <option value="full_time" <?php echo ($experience['employment_type'] ?? '') === 'full_time' ? 'selected' : ''; ?>>Full Time</option>
                                            <option value="part_time" <?php echo ($experience['employment_type'] ?? '') === 'part_time' ? 'selected' : ''; ?>>Part Time</option>
                                            <option value="contract" <?php echo ($experience['employment_type'] ?? '') === 'contract' ? 'selected' : ''; ?>>Contract</option>
                                            <option value="freelance" <?php echo ($experience['employment_type'] ?? '') === 'freelance' ? 'selected' : ''; ?>>Freelance</option>
                                            <option value="internship" <?php echo ($experience['employment_type'] ?? '') === 'internship' ? 'selected' : ''; ?>>Internship</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group full-width">
                                        <label class="form-label">Location</label>
                                        <input type="text" name="experience[<?php echo $index; ?>][location]" class="form-input" 
                                               value="<?php echo htmlspecialchars($experience['location'] ?? ''); ?>" 
                                               placeholder="e.g., Lagos, Nigeria">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Start Date</label>
                                        <input type="date" name="experience[<?php echo $index; ?>][start_date]" class="form-input" 
                                               value="<?php echo $experience['start_date']; ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">End Date</label>
                                        <input type="date" name="experience[<?php echo $index; ?>][end_date]" class="form-input" 
                                               value="<?php echo $experience['end_date']; ?>"
                                               <?php echo $experience['is_current'] ? 'disabled' : ''; ?>>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-checkbox">
                                            <input type="checkbox" name="experience[<?php echo $index; ?>][is_current]" 
                                                   <?php echo $experience['is_current'] ? 'checked' : ''; ?>
                                                   onchange="toggleEndDate(<?php echo $index; ?>, this.checked)">
                                            Currently working here
                                        </label>
                                    </div>
                                    
                                    <div class="form-group full-width">
                                        <label class="form-label">Job Description</label>
                                        <textarea name="experience[<?php echo $index; ?>][job_description]" class="form-textarea" 
                                                  placeholder="Describe your role, responsibilities, and what you do/did in this position..."><?php echo htmlspecialchars($experience['job_description'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div class="form-group full-width">
                                        <label class="form-label">Key Achievements</label>
                                        <textarea name="experience[<?php echo $index; ?>][key_achievements]" class="form-textarea" 
                                                  placeholder="List your major accomplishments, projects completed, awards received, etc..."><?php echo htmlspecialchars($experience['key_achievements'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Skills Used</label>
                                        <input type="text" name="experience[<?php echo $index; ?>][skills_used]" class="form-input" 
                                               value="<?php echo htmlspecialchars($experience['skills_used'] ?? ''); ?>" 
                                               placeholder="e.g., Project Management, Excel, Sales">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Salary Range</label>
                                        <input type="text" name="experience[<?php echo $index; ?>][salary_range]" class="form-input" 
                                               value="<?php echo htmlspecialchars($experience['salary_range'] ?? ''); ?>" 
                                               placeholder="e.g., ₦150,000 - ₦200,000">
                                    </div>
                                </div>
                                
                                <input type="hidden" name="experience[<?php echo $index; ?>][id]" value="<?php echo $experience['id']; ?>">
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Job Preferences -->
            <div class="profile-section">
                <div class="section-header">
                    <span class="section-icon">💼</span>
                    <h2>Job Preferences</h2>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="job_status">Job Status</label>
                        <select id="job_status" name="job_status" class="form-select">
                            <option value="looking" <?php echo ($user['job_status'] ?? '') === 'looking' ? 'selected' : ''; ?>>Actively Looking</option>
                            <option value="employed_but_looking" <?php echo ($user['job_status'] ?? '') === 'employed_but_looking' ? 'selected' : ''; ?>>Employed but Looking</option>
                            <option value="not_looking" <?php echo ($user['job_status'] ?? '') === 'not_looking' ? 'selected' : ''; ?>>Not Looking</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Salary Expectation (Monthly in ₦)</label>
                    <div class="salary-range">
                        <input type="number" name="salary_expectation_min" class="form-input" 
                               placeholder="Minimum" value="<?php echo $user['salary_expectation_min'] ?? ''; ?>">
                        <span>to</span>
                        <input type="number" name="salary_expectation_max" class="form-input" 
                               placeholder="Maximum" value="<?php echo $user['salary_expectation_max'] ?? ''; ?>">
                    </div>
                </div>
            </div>

            <!-- Skills & Bio -->
            <div class="profile-section">
                <div class="section-header">
                    <span class="section-icon">🏷️</span>
                    <h2>Skills & About</h2>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="skills">Skills (comma-separated)</label>
                    <input type="text" id="skills" name="skills" class="form-input skills-input" 
                           value="<?php echo htmlspecialchars($user['skills'] ?? ''); ?>" 
                           placeholder="e.g., PHP, JavaScript, Project Management, Communication">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="bio">Professional Bio</label>
                    <textarea id="bio" name="bio" class="form-textarea" 
                              placeholder="Tell employers about yourself, your experience, and what makes you unique..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                </div>
            </div>

            <!-- NIN Verification Service -->
            <div class="profile-section">
                <div class="section-header">
                    <span class="section-icon">🛡️</span>
                    <h2>NIN Verification</h2>
                </div>
                
                <?php if ($user['nin_verified']): ?>
                    <div class="verification-success">
                        <div class="verification-badge verified">
                            ✓ Your NIN has been successfully verified!
                        </div>
                        <div class="verification-details">
                            <p><strong>NIN:</strong> <?php echo htmlspecialchars(substr($user['nin'], 0, 4) . '****' . substr($user['nin'], -3)); ?></p>
                            <p><strong>Verified on:</strong> <?php echo date('F j, Y', strtotime($user['nin_verified_at'])); ?></p>
                        </div>
                        <p style="color: var(--accent); margin-top: 1rem;">
                            <strong>Congratulations!</strong> Your profile now has a verified badge that increases your credibility with employers.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="verification-service">
                        <div class="service-card">
                            <div class="service-header">
                                <h3>🏆 Get Your Verified Badge</h3>
                                <div class="service-price">₦<?php echo number_format(NIN_VERIFICATION_FEE, 0); ?> <span>one-time</span></div>
                            </div>
                            
                            <div class="service-benefits">
                                <h4>Why verify your NIN?</h4>
                                <ul>
                                    <li>✓ Get a verified badge on your profile</li>
                                    <li>✓ Increase your credibility with employers</li>
                                    <li>✓ Stand out from other job seekers</li>
                                    <li>✓ Higher chance of getting job interviews</li>
                                    <li>✓ Secure and confidential process</li>
                                </ul>
                            </div>
                            
                            <div class="verification-process">
                                <h4>How it works:</h4>
                                <div class="process-steps">
                                    <div class="step">
                                        <span class="step-number">1</span>
                                        <span>Click "Verify My NIN"</span>
                                    </div>
                                    <div class="step">
                                        <span class="step-number">2</span>
                                        <span>Enter your 11-digit NIN</span>
                                    </div>
                                    <div class="step">
                                        <span class="step-number">3</span>
                                        <span>Confirm payment of ₦<?php echo number_format(NIN_VERIFICATION_FEE, 0); ?></span>
                                    </div>
                                    <div class="step">
                                        <span class="step-number">4</span>
                                        <span>Get verified instantly</span>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="button" class="btn-verify" onclick="openNINVerificationModal()">
                                🛡️ Verify My NIN - ₦<?php echo number_format(NIN_VERIFICATION_FEE, 0); ?>
                            </button>
                            
                            <p style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 1rem; text-align: center;">
                                Your NIN information is encrypted and secure. We comply with Nigerian data protection laws.
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn-save">
                Save Profile Changes
            </button>
        </form>
    </main>

    <!-- Bottom Navigation -->
    <nav class="app-bottom-nav">
        <a href="../../index.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">🏠</div>
            <div class="app-bottom-nav-label">Home</div>
        </a>
        <a href="../jobs/browse.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">🔍</div>
            <div class="app-bottom-nav-label">Jobs</div>
        </a>
        <a href="dashboard.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">📊</div>
            <div class="app-bottom-nav-label">Dashboard</div>
        </a>
        <a href="profile.php" class="app-bottom-nav-item active">
            <div class="app-bottom-nav-icon">👤</div>
            <div class="app-bottom-nav-label">Profile</div>
        </a>
    </nav>

    <script>
        // Form validation and enhancement
        document.addEventListener('DOMContentLoaded', function() {
            
            // Phone number formatting
            const phoneInput = document.getElementById('phone');
            phoneInput.addEventListener('input', function() {
                let value = this.value.replace(/\D/g, '');
                if (value.startsWith('234')) {
                    value = '+' + value;
                } else if (value.startsWith('0')) {
                    value = '+234' + value.slice(1);
                } else if (value.length > 0 && !value.startsWith('+')) {
                    value = '+234' + value;
                }
                this.value = value;
            });
            
            // Skills input enhancement
            const skillsInput = document.getElementById('skills');
            skillsInput.addEventListener('blur', function() {
                // Clean up skills format
                let skills = this.value.split(',').map(skill => skill.trim()).filter(skill => skill);
                this.value = skills.join(', ');
            });
            
            // Form submission validation
            document.querySelector('form').addEventListener('submit', function(e) {
                const requiredFields = ['first_name', 'last_name'];
                let hasError = false;
                
                requiredFields.forEach(fieldName => {
                    const field = document.getElementById(fieldName);
                    if (!field.value.trim()) {
                        field.style.borderColor = '#dc2626';
                        hasError = true;
                    }
                });
                
                if (hasError) {
                    e.preventDefault();
                    alert('Please fill in all required fields.');
                }
            });
        });

        // Education Management
        let educationIndex = <?php echo count($education_records); ?>;
        
        // Work Experience Management
        let experienceIndex = <?php echo count($experience_records); ?>;
        
        function addEducationEntry() {
            const container = document.getElementById('education-container');
            const noEducation = container.querySelector('.no-education');
            
            // Remove no education message if it exists
            if (noEducation) {
                noEducation.remove();
            }
            
            const educationEntry = document.createElement('div');
            educationEntry.className = 'education-entry';
            educationEntry.setAttribute('data-index', educationIndex);
            
            educationEntry.innerHTML = `
                <div class="education-header">
                    <h4>New Education Entry</h4>
                    <button type="button" class="btn-remove" onclick="removeEducationEntry(${educationIndex})">Remove</button>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Education Level</label>
                        <select name="education[${educationIndex}][education_level]" class="form-select" required onchange="updateEducationTitle(${educationIndex}, this.value)">
                            <option value="">Select Level</option>
                            <option value="ssce">SSCE/O'Levels</option>
                            <option value="ond">OND</option>
                            <option value="hnd">HND</option>
                            <option value="bsc">B.Sc/B.A</option>
                            <option value="msc">M.Sc/M.A</option>
                            <option value="phd">PhD</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group full-width">
                        <label class="form-label">Institution Name</label>
                        <input type="text" name="education[${educationIndex}][institution_name]" class="form-input" required>
                    </div>
                    
                    <div class="form-group full-width">
                        <label class="form-label">Field of Study</label>
                        <input type="text" name="education[${educationIndex}][field_of_study]" class="form-input" 
                               placeholder="e.g., Computer Science, Business Administration">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Start Year</label>
                        <input type="number" name="education[${educationIndex}][start_year]" class="form-input" 
                               min="1950" max="${new Date().getFullYear()}">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">End Year</label>
                        <input type="number" name="education[${educationIndex}][end_year]" class="form-input" 
                               min="1950" max="${new Date().getFullYear() + 10}">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Grade/Result</label>
                        <input type="text" name="education[${educationIndex}][grade_result]" class="form-input" 
                               placeholder="e.g., First Class, 2:1, Distinction">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-checkbox">
                            <input type="checkbox" name="education[${educationIndex}][is_current]">
                            Currently studying here
                        </label>
                    </div>
                </div>
                
                <input type="hidden" name="education[${educationIndex}][id]" value="">
            `;
            
            container.appendChild(educationEntry);
            educationIndex++;
        }
        
        function removeEducationEntry(index) {
            if (confirm('Are you sure you want to remove this education entry?')) {
                const entry = document.querySelector(`[data-index="${index}"]`);
                if (entry) {
                    entry.remove();
                    
                    // Check if no education entries remain
                    const container = document.getElementById('education-container');
                    if (container.children.length === 0) {
                        container.innerHTML = `
                            <div class="no-education">
                                <p style="text-align: center; color: var(--text-secondary); margin: 2rem 0;">
                                    No education records added yet. Click "Add Education" to get started.
                                </p>
                            </div>
                        `;
                    }
                }
            }
        }
        
        function updateEducationTitle(index, level) {
            const levels = {
                'ssce': 'SSCE/O\'Levels',
                'ond': 'OND',
                'hnd': 'HND',
                'bsc': 'B.Sc/B.A',
                'msc': 'M.Sc/M.A',
                'phd': 'PhD',
                'other': 'Other'
            };
            
            const header = document.querySelector(`[data-index="${index}"] .education-header h4`);
            if (header && levels[level]) {
                header.textContent = levels[level];
            }
        }
        
        function addExperienceEntry() {
            const container = document.getElementById('experience-container');
            const noExperience = container.querySelector('.no-experience');
            
            // Remove no experience message if it exists
            if (noExperience) {
                noExperience.remove();
            }
            
            const experienceEntry = document.createElement('div');
            experienceEntry.className = 'experience-entry';
            experienceEntry.setAttribute('data-index', experienceIndex);
            
            experienceEntry.innerHTML = `
                <div class="experience-header">
                    <h4>New Work Experience</h4>
                    <button type="button" class="btn-remove" onclick="removeExperienceEntry(${experienceIndex})">Remove</button>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Job Title</label>
                        <input type="text" name="experience[${experienceIndex}][job_title]" class="form-input" required onchange="updateExperienceTitle(${experienceIndex})">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Company Name</label>
                        <input type="text" name="experience[${experienceIndex}][company_name]" class="form-input" required onchange="updateExperienceTitle(${experienceIndex})">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Industry</label>
                        <input type="text" name="experience[${experienceIndex}][industry]" class="form-input" 
                               placeholder="e.g., Technology, Banking, Healthcare">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Employment Type</label>
                        <select name="experience[${experienceIndex}][employment_type]" class="form-select">
                            <option value="full_time">Full Time</option>
                            <option value="part_time">Part Time</option>
                            <option value="contract">Contract</option>
                            <option value="freelance">Freelance</option>
                            <option value="internship">Internship</option>
                        </select>
                    </div>
                    
                    <div class="form-group full-width">
                        <label class="form-label">Location</label>
                        <input type="text" name="experience[${experienceIndex}][location]" class="form-input" 
                               placeholder="e.g., Lagos, Nigeria">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="experience[${experienceIndex}][start_date]" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">End Date</label>
                        <input type="date" name="experience[${experienceIndex}][end_date]" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-checkbox">
                            <input type="checkbox" name="experience[${experienceIndex}][is_current]" 
                                   onchange="toggleEndDate(${experienceIndex}, this.checked)">
                            Currently working here
                        </label>
                    </div>
                    
                    <div class="form-group full-width">
                        <label class="form-label">Job Description</label>
                        <textarea name="experience[${experienceIndex}][job_description]" class="form-textarea" 
                                  placeholder="Describe your role, responsibilities, and what you do/did in this position..."></textarea>
                    </div>
                    
                    <div class="form-group full-width">
                        <label class="form-label">Key Achievements</label>
                        <textarea name="experience[${experienceIndex}][key_achievements]" class="form-textarea" 
                                  placeholder="List your major accomplishments, projects completed, awards received, etc..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Skills Used</label>
                        <input type="text" name="experience[${experienceIndex}][skills_used]" class="form-input" 
                               placeholder="e.g., Project Management, Excel, Sales">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Salary Range</label>
                        <input type="text" name="experience[${experienceIndex}][salary_range]" class="form-input" 
                               placeholder="e.g., ₦150,000 - ₦200,000">
                    </div>
                </div>
                
                <input type="hidden" name="experience[${experienceIndex}][id]" value="">
            `;
            
            container.appendChild(experienceEntry);
            experienceIndex++;
        }
        
        function removeExperienceEntry(index) {
            if (confirm('Are you sure you want to remove this work experience entry?')) {
                const entry = document.querySelector(`[data-index="${index}"]`);
                if (entry && entry.classList.contains('experience-entry')) {
                    entry.remove();
                    
                    // Check if no experience entries remain
                    const container = document.getElementById('experience-container');
                    if (container.children.length === 0) {
                        container.innerHTML = `
                            <div class="no-experience">
                                <p style="text-align: center; color: var(--text-secondary); margin: 2rem 0;">
                                    No work experience added yet. Click "Add Experience" to get started.
                                </p>
                            </div>
                        `;
                    }
                }
            }
        }
        
        function updateExperienceTitle(index) {
            const jobTitle = document.querySelector(`[name="experience[${index}][job_title]"]`).value;
            const companyName = document.querySelector(`[name="experience[${index}][company_name]"]`).value;
            const header = document.querySelector(`[data-index="${index}"] .experience-header h4`);
            
            if (header && (jobTitle || companyName)) {
                let title = 'New Work Experience';
                if (jobTitle && companyName) {
                    title = `${jobTitle} at ${companyName}`;
                } else if (jobTitle) {
                    title = jobTitle;
                } else if (companyName) {
                    title = companyName;
                }
                header.textContent = title;
            }
        }
        
        function toggleEndDate(index, isCurrent) {
            const endDateInput = document.querySelector(`[name="experience[${index}][end_date]"]`);
            if (endDateInput) {
                endDateInput.disabled = isCurrent;
                if (isCurrent) {
                    endDateInput.value = '';
                }
            }
        }

        // NIN Verification Service
        function openNINVerificationModal() {
            // Create modal dynamically
            const modal = document.createElement('div');
            modal.className = 'nin-verification-modal';
            modal.id = 'ninVerificationModal';
            
            modal.innerHTML = `
                <div class="nin-modal-overlay" onclick="closeNINVerificationModal()"></div>
                <div class="nin-modal-content">
                    <div class="nin-modal-header">
                        <h3>🛡️ NIN Verification</h3>
                        <button class="nin-modal-close" onclick="closeNINVerificationModal()">×</button>
                    </div>
                    
                    <div class="nin-modal-body">
                        <p style="margin-bottom: 1.5rem; color: var(--text-secondary);">
                            Enter your 11-digit National Identification Number to verify your identity and get a verified badge on your profile.
                        </p>
                        
                        <div id="ninVerificationAlert" class="nin-alert" style="display: none;"></div>
                        
                        <form id="ninVerificationForm" onsubmit="submitNINVerification(event)">
                            <div class="form-group">
                                <label class="form-label" for="ninInput">National Identification Number (NIN)</label>
                                <input type="text" id="ninInput" name="nin" class="form-input" 
                                       placeholder="Enter your 11-digit NIN" 
                                       pattern="[0-9]{11}" 
                                       maxlength="11"
                                       required>
                                <small style="color: var(--text-secondary);">Example: 12345678901</small>
                            </div>
                            
                            <div class="verification-cost-summary">
                                <div class="cost-item">
                                    <span>Verification Fee:</span>
                                    <strong>₦<?php echo number_format(NIN_VERIFICATION_FEE, 2); ?></strong>
                                </div>
                                <div class="cost-note">
                                    <small>⚡ Instant verification • Secure & encrypted • One-time payment</small>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-checkbox">
                                    <input type="checkbox" id="ninAgreeTerms" required>
                                    <span>I agree to the <a href="/findajob/pages/legal/terms.php" target="_blank">Terms of Service</a> and confirm that the NIN provided is mine</span>
                                </label>
                            </div>
                            
                            <button type="submit" class="btn-verify btn-block" id="ninVerifyButton">
                                <span id="ninVerifyButtonText">Proceed to Verify - ₦<?php echo number_format(NIN_VERIFICATION_FEE, 0); ?></span>
                                <span id="ninVerifyButtonSpinner" style="display: none;">
                                    <i class="fas fa-spinner fa-spin"></i> Verifying...
                                </span>
                            </button>
                        </form>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Focus on NIN input
            setTimeout(() => {
                document.getElementById('ninInput').focus();
            }, 100);
        }
        
        function closeNINVerificationModal() {
            const modal = document.getElementById('ninVerificationModal');
            if (modal) {
                modal.remove();
            }
        }
        
        function showNINAlert(message, type = 'info') {
            const alert = document.getElementById('ninVerificationAlert');
            if (!alert) return;
            
            alert.className = `nin-alert nin-alert-${type}`;
            alert.textContent = message;
            alert.style.display = 'block';
            
            // Auto hide success messages after 5 seconds
            if (type === 'success') {
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 5000);
            }
        }
        
        async function submitNINVerification(event) {
            event.preventDefault();
            
            const button = document.getElementById('ninVerifyButton');
            const buttonText = document.getElementById('ninVerifyButtonText');
            const buttonSpinner = document.getElementById('ninVerifyButtonSpinner');
            const ninInput = document.getElementById('ninInput');
            const nin = ninInput.value.trim();
            
            // Validate NIN
            if (!/^\d{11}$/.test(nin)) {
                showNINAlert('Please enter a valid 11-digit NIN', 'error');
                ninInput.focus();
                return;
            }
            
            // Disable button and show loading state
            button.disabled = true;
            buttonText.style.display = 'none';
            buttonSpinner.style.display = 'inline-block';
            
            try {
                const response = await fetch('/findajob/api/verify-nin.php?action=verify', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `nin=${encodeURIComponent(nin)}`
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNINAlert('✓ NIN verified successfully! Reloading page...', 'success');
                    
                    // Reload page after 2 seconds to show updated verification status
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    showNINAlert(data.error || 'Verification failed. Please try again.', 'error');
                    
                    // Re-enable button
                    button.disabled = false;
                    buttonText.style.display = 'inline-block';
                    buttonSpinner.style.display = 'none';
                }
            } catch (error) {
                console.error('Verification error:', error);
                showNINAlert('Network error. Please check your connection and try again.', 'error');
                
                // Re-enable button
                button.disabled = false;
                buttonText.style.display = 'inline-block';
                buttonSpinner.style.display = 'none';
            }
        }

        // Add body class for bottom nav
        document.body.classList.add('has-bottom-nav');
        
        // Profile Picture Upload Handler
        document.getElementById('profilePictureInput').addEventListener('change', function(e) {
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
            const avatar = document.querySelector('.profile-avatar');
            const originalContent = avatar.innerHTML;
            avatar.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size: 2rem;"></i>';
            
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
                    // Update avatar with new image
                    const img = document.getElementById('profilePicturePreview');
                    const initials = document.getElementById('profileInitials');
                    
                    if (img) {
                        img.src = data.url + '?' + new Date().getTime(); // Cache bust
                    } else {
                        // Replace initials with image
                        avatar.innerHTML = `
                            <img src="${data.url}" alt="Profile Picture" id="profilePicturePreview">
                            <div class="profile-avatar-upload">
                                <i class="fas fa-camera"></i> Change Photo
                            </div>
                        `;
                    }
                    
                    // Show success message
                    alert('Profile picture updated successfully!');
                    
                    // Reload page to update all instances
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert('Error: ' + data.error);
                    avatar.innerHTML = originalContent;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to upload profile picture');
                avatar.innerHTML = originalContent;
            });
        });
    </script>

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
        <a href="saved-jobs.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">❤️</div>
            <div class="app-bottom-nav-label">Saved</div>
        </a>
        <a href="applications.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">📋</div>
            <div class="app-bottom-nav-label">Applications</div>
        </a>
        <a href="dashboard.php" class="app-bottom-nav-item active">
            <div class="app-bottom-nav-icon">👤</div>
            <div class="app-bottom-nav-label">Profile</div>
        </a>
    </nav>

    <?php include '../../includes/footer.php'; ?>
</body>
</html>