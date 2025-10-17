<?php
// Simple test to verify profile completion synchronization
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'config/constants.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    die("Please log in first to test profile completion");
}

$userId = getCurrentUserId();

// Get user data (same query as both pages)
$stmt = $pdo->prepare("
    SELECT 
        u.id, u.user_type, u.email, u.first_name, u.last_name, u.phone, 
        u.email_verified, u.is_active, u.created_at as user_created_at, u.updated_at as user_updated_at,
        jsp.id as profile_id, jsp.user_id, jsp.date_of_birth, jsp.gender, 
        jsp.state_of_origin, jsp.lga_of_origin, jsp.current_state, jsp.current_city,
        jsp.education_level, jsp.years_of_experience, jsp.job_status,
        jsp.salary_expectation_min, jsp.salary_expectation_max, jsp.skills, jsp.bio,
        jsp.profile_picture, jsp.nin, jsp.bvn, jsp.is_verified, jsp.verification_status,
        jsp.subscription_type, jsp.subscription_expires,
        jsp.created_at as profile_created_at, jsp.updated_at as profile_updated_at
    FROM users u 
    LEFT JOIN job_seeker_profiles jsp ON u.id = jsp.user_id 
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Calculate completion
$completion = calculateProfileCompletion($user);

echo "<h2>✅ Profile Completion Test</h2>";
echo "<p><strong>User:</strong> " . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . "</p>";
echo "<p><strong>Profile Completion:</strong> {$completion}%</p>";

if ($completion > 0) {
    echo "<p style='color: green;'>✅ Profile completion calculation is working correctly!</p>";
    echo "<p>Both dashboard and profile pages should now show <strong>{$completion}%</strong> completion.</p>";
} else {
    echo "<p style='color: red;'>❌ Profile completion is still 0%. There may be an issue with the function.</p>";
}

echo "<hr>";
echo "<p><a href='pages/user/dashboard.php'>Go to Dashboard</a> | <a href='pages/user/profile.php'>Go to Profile</a></p>";
?>