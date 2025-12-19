<?php
/**
 * Pro Feature Access Control Helper Functions
 * Use these functions to check and restrict Pro features
 */

/**
 * Check if current user has Pro subscription
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 * @return bool True if user is Pro, false otherwise
 */
function isProUser($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT subscription_plan, subscription_status FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) return false;
    
    $subscription_plan = $user['subscription_plan'] ?? 'basic';
    $subscription_status = $user['subscription_status'] ?? 'free';
    
    return (strpos($subscription_plan, 'pro') !== false) && $subscription_status === 'active';
}

/**
 * Get user subscription details
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 * @return array Subscription details
 */
function getUserSubscription($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT subscription_plan, subscription_status, subscription_type, 
               subscription_start, subscription_end 
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    return [
        'plan' => $user['subscription_plan'] ?? 'basic',
        'status' => $user['subscription_status'] ?? 'free',
        'type' => $user['subscription_type'] ?? null,
        'start' => $user['subscription_start'] ?? null,
        'end' => $user['subscription_end'] ?? null,
        'is_pro' => (strpos($user['subscription_plan'] ?? '', 'pro') !== false) && 
                    ($user['subscription_status'] ?? 'free') === 'active'
    ];
}

/**
 * Display Pro upgrade prompt
 * @param string $feature Feature name (e.g., "multiple CVs", "cover letter generator")
 * @param string $description Feature description
 * @return string HTML for upgrade prompt
 */
function displayProUpgradePrompt($feature, $description = '') {
    $html = '
    <div class="pro-upgrade-prompt" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-left: 4px solid #f59e0b; padding: 1.5rem; border-radius: 8px; margin: 1.5rem 0;">
        <div style="display: flex; align-items: start; gap: 1rem;">
            <div style="font-size: 2rem;">🔒</div>
            <div style="flex: 1;">
                <h3 style="margin: 0 0 0.5rem 0; color: #92400e; font-size: 1.125rem;">
                    <i class="fas fa-crown"></i> Pro Feature: ' . htmlspecialchars($feature) . '
                </h3>
                <p style="margin: 0 0 1rem 0; color: #78350f;">';
    
    if ($description) {
        $html .= htmlspecialchars($description);
    } else {
        $html .= 'This feature is only available to Pro members. Upgrade now to unlock this and many more premium features!';
    }
    
    $html .= '
                </p>
                <a href="../payment/plans.php" class="btn btn-primary" style="background: #f59e0b; border-color: #f59e0b; color: white; text-decoration: none; padding: 0.5rem 1rem; border-radius: 6px; display: inline-block;">
                    <i class="fas fa-crown"></i> Upgrade to Pro
                </a>
            </div>
        </div>
    </div>';
    
    return $html;
}

/**
 * Check feature access and redirect if not Pro
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 * @param string $feature Feature name for error message
 * @param string $redirect_url URL to redirect to (default: plans page)
 */
function requireProFeature($pdo, $user_id, $feature, $redirect_url = '../payment/plans.php') {
    if (!isProUser($pdo, $user_id)) {
        $_SESSION['upgrade_message'] = "This feature ($feature) requires a Pro subscription.";
        header("Location: $redirect_url");
        exit();
    }
}

/**
 * Get Pro feature limits
 * @param bool $is_pro Whether user is Pro
 * @return array Feature limits
 */
function getFeatureLimits($is_pro) {
    return [
        'cv_uploads' => $is_pro ? 999 : 1,  // Unlimited vs 1
        'applications_per_day' => $is_pro ? 999 : 10,  // Unlimited vs 10
        'saved_jobs' => $is_pro ? 999 : 50,  // Unlimited vs 50
        'cover_letters' => $is_pro ? 999 : 0,  // Unlimited vs 0
        'profile_views' => $is_pro ? true : false,  // Analytics access
        'job_alerts' => $is_pro ? true : false,  // Daily email/SMS
        'verified_badge' => $is_pro ? true : false,  // Green tick eligibility
        'priority_support' => $is_pro ? true : false
    ];
}

/**
 * Display feature limit warning
 * @param string $feature Feature name
 * @param int $current Current usage
 * @param int $limit Maximum limit
 * @return string HTML for warning
 */
function displayLimitWarning($feature, $current, $limit) {
    if ($current >= $limit) {
        return '
        <div class="alert alert-warning" style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
            <strong>⚠️ Limit Reached:</strong> You have used ' . $current . ' of ' . $limit . ' ' . htmlspecialchars($feature) . ' allowed on the Basic plan.
            <a href="../payment/plans.php" style="color: #f59e0b; font-weight: 600; text-decoration: underline;">Upgrade to Pro</a> for unlimited access.
        </div>';
    } elseif ($current >= ($limit * 0.8)) {
        return '
        <div class="alert alert-info" style="background: #dbeafe; border-left: 4px solid #3b82f6; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
            <strong>ℹ️ Approaching Limit:</strong> You have used ' . $current . ' of ' . $limit . ' ' . htmlspecialchars($feature) . '.
        </div>';
    }
    return '';
}

/**
 * Pro features list
 * @return array List of Pro features with descriptions
 */
function getProFeaturesList() {
    return [
        'Multiple CVs' => 'Upload and manage unlimited CV versions',
        'Cover Letter Generator' => 'AI-powered cover letter creation',
        'Advanced Profile Data' => 'Extended profile fields and analytics',
        'Verified ID Badge' => 'Green checkmark badge on your profile',
        'Top of Search Results' => 'Appear first in employer searches',
        'Daily Job Recommendations' => 'Personalized jobs via email & SMS',
        'Application Tracking' => 'Advanced tracking and analytics',
        'Priority Support' => '24/7 dedicated customer support'
    ];
}
