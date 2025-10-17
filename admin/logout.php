<?php
require_once '../config/session.php';
require_once '../config/database.php';

// Check if admin is logged in
if (!isLoggedIn() || !isAdmin()) {
    header('Location: login.php');
    exit;
}

// Log the logout activity
try {
    $stmt = $pdo->prepare("
        INSERT INTO admin_logs (admin_user_id, action, details, ip_address, user_agent) 
        VALUES (?, 'logout', 'Admin logged out', ?, ?)
    ");
    $stmt->execute([
        $_SESSION['admin_id'] ?? null,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
} catch (Exception $e) {
    error_log("Admin logout logging error: " . $e->getMessage());
}

// Clear session
session_unset();
session_destroy();

// Redirect to login page
header('Location: login.php?message=logged_out');
exit;
?>