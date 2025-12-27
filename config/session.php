<?php
// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        return false;
    }
    
    // Check if user is suspended (only if logged in)
    checkSuspensionStatus();
    
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Check if current user's account is suspended
function checkSuspensionStatus() {
    if (!isset($_SESSION['user_id'])) {
        return;
    }
    
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT is_suspended, suspension_expires, suspension_reason 
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user && $user['is_suspended']) {
            // Check if suspension has expired
            if ($user['suspension_expires'] && strtotime($user['suspension_expires']) < time()) {
                // Auto-unsuspend
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET is_suspended = 0,
                        suspension_reason = NULL,
                        suspended_at = NULL,
                        suspended_by = NULL,
                        suspension_expires = NULL
                    WHERE id = ?
                ");
                $stmt->execute([$_SESSION['user_id']]);
            } else {
                // Force logout suspended user
                $_SESSION['suspension_message'] = $user['suspension_reason'] ?: 'Your account has been suspended.';
                $_SESSION['suspension_expires'] = $user['suspension_expires'];
                logoutUser();
                
                // Redirect to login with suspension message
                header('Location: /findajob/pages/auth/login.php?suspended=1');
                exit();
            }
        }
    } catch (Exception $e) {
        error_log("Suspension check error: " . $e->getMessage());
    }
}

// Check if user is job seeker
function isJobSeeker() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'job_seeker';
}

// Check if user is employer
function isEmployer() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'employer';
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

// Check admin role (for future role system)
function isAdminRole($role) {
    return isAdmin() && isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === $role;
}

// Check if super admin (checks role from database)
function isSuperAdmin($userId = null) {
    global $pdo;
    
    if (!$userId) {
        $userId = getCurrentUserId();
    }
    
    if (!$userId) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT ar.role_slug 
            FROM users u
            JOIN admin_roles ar ON u.admin_role_id = ar.id
            WHERE u.id = ? AND u.user_type = 'admin'
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        return $result && $result['role_slug'] === 'super_admin';
    } catch (Exception $e) {
        error_log("Super admin check error: " . $e->getMessage());
        // Fallback: if table doesn't exist yet, all admins are super admin
        return isAdmin();
    }
}

// Get admin ID
function getAdminId() {
    return isAdmin() ? $_SESSION['user_id'] : null;
}

// Get admin role (default to super_admin)
function getAdminRole() {
    return isAdmin() ? ($_SESSION['admin_role'] ?? 'super_admin') : null;
}

// Get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Get current user type
function getCurrentUserType() {
    return $_SESSION['user_type'] ?? null;
}

// Login user
function loginUser($userId, $userType, $email, $firstName = '') {
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_type'] = $userType;
    $_SESSION['email'] = $email;
    $_SESSION['first_name'] = $firstName;
    $_SESSION['login_time'] = time();
}

// Logout user
function logoutUser() {
    session_unset();
    session_destroy();
}

// Check if email is verified
function isEmailVerified() {
    return isset($_SESSION['email_verified']) && $_SESSION['email_verified'] === true;
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /findajob/pages/auth/login.php');
        exit();
    }
}

// Redirect if not job seeker
function requireJobSeeker() {
    requireLogin();
    if (!isJobSeeker()) {
        header('Location: /findajob/pages/auth/login.php');
        exit();
    }
}

// Redirect if not employer
function requireEmployer() {
    requireLogin();
    if (!isEmployer()) {
        header('Location: /findajob/pages/auth/login.php');
        exit();
    }
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Validate CSRF token
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>