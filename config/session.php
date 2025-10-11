<?php
// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
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