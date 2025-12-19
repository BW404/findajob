<?php
/**
 * Maintenance Mode Check
 * Include this file at the top of public pages to enforce maintenance mode
 */

// Check if maintenance mode is enabled
if (!defined('SKIP_MAINTENANCE_CHECK')) {
    try {
        require_once __DIR__ . '/database.php';
        require_once __DIR__ . '/session.php';
        
        $stmt = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'maintenance_mode' LIMIT 1");
        $result = $stmt->fetch();
        
        if ($result && $result['setting_value'] === '1') {
            // Check if this is an admin panel page
            $request_uri = $_SERVER['REQUEST_URI'] ?? '';
            $is_admin_panel = strpos($request_uri, '/admin/') !== false;
            
            // Allow admin users to access admin panel only
            if (isLoggedIn() && (isAdmin() || isSuperAdmin(getCurrentUserId()))) {
                // Admins can access admin panel, but not public site
                if (!$is_admin_panel && basename($_SERVER['PHP_SELF']) !== 'maintenance.php') {
                    header('Location: /findajob/maintenance.php');
                    exit;
                }
                // Show maintenance banner for admins in admin panel
                define('MAINTENANCE_MODE_ACTIVE', true);
            } else {
                // Redirect non-admin users to maintenance page
                if (basename($_SERVER['PHP_SELF']) !== 'maintenance.php') {
                    header('Location: /findajob/maintenance.php');
                    exit;
                }
            }
        }
    } catch (Exception $e) {
        // If database is not available, don't block access
        error_log("Maintenance check error: " . $e->getMessage());
    }
}
