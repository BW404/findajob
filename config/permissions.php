<?php
/**
 * Helper functions for admin roles and permissions
 */

require_once __DIR__ . '/database.php';

/**
 * Check if admin has a specific permission
 */
function hasPermission($userId, $permissionName) {
    global $pdo;
    
    try {
        // Get user's role
        $stmt = $pdo->prepare("SELECT admin_role_id FROM users WHERE id = ? AND user_type = 'admin'");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user || !$user['admin_role_id']) {
            return false;
        }
        
        // Check if role has the permission
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as has_permission
            FROM admin_role_permissions arp
            JOIN admin_permissions ap ON arp.permission_id = ap.id
            WHERE arp.role_id = ? AND ap.name = ?
        ");
        $stmt->execute([$user['admin_role_id'], $permissionName]);
        $result = $stmt->fetch();
        
        return $result['has_permission'] > 0;
    } catch (Exception $e) {
        error_log("Permission check error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user's role information
 */
function getUserRole($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT ar.* 
            FROM users u
            JOIN admin_roles ar ON u.admin_role_id = ar.id
            WHERE u.id = ? AND u.user_type = 'admin'
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Get user role error: " . $e->getMessage());
        return null;
    }
}

/**
 * Get all permissions for a role
 */
function getRolePermissions($roleId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT ap.* 
            FROM admin_role_permissions arp
            JOIN admin_permissions ap ON arp.permission_id = ap.id
            WHERE arp.role_id = ?
            ORDER BY ap.module, ap.name
        ");
        $stmt->execute([$roleId]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Get role permissions error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all permissions grouped by module
 */
function getAllPermissionsGrouped() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("
            SELECT * FROM admin_permissions 
            ORDER BY module, name
        ");
        $permissions = $stmt->fetchAll();
        
        $grouped = [];
        foreach ($permissions as $perm) {
            $grouped[$perm['module']][] = $perm;
        }
        
        return $grouped;
    } catch (Exception $e) {
        error_log("Get all permissions error: " . $e->getMessage());
        return [];
    }
}

/**
 * Check multiple permissions (any)
 */
function hasAnyPermission($userId, array $permissions) {
    foreach ($permissions as $permission) {
        if (hasPermission($userId, $permission)) {
            return true;
        }
    }
    return false;
}

/**
 * Check multiple permissions (all)
 */
function hasAllPermissions($userId, array $permissions) {
    foreach ($permissions as $permission) {
        if (!hasPermission($userId, $permission)) {
            return false;
        }
    }
    return true;
}

/**
 * Get user's permissions as array
 */
function getUserPermissions($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT ap.name
            FROM users u
            JOIN admin_role_permissions arp ON u.admin_role_id = arp.role_id
            JOIN admin_permissions ap ON arp.permission_id = ap.id
            WHERE u.id = ? AND u.user_type = 'admin'
        ");
        $stmt->execute([$userId]);
        
        $permissions = [];
        while ($row = $stmt->fetch()) {
            $permissions[] = $row['name'];
        }
        
        return $permissions;
    } catch (Exception $e) {
        error_log("Get user permissions error: " . $e->getMessage());
        return [];
    }
}
