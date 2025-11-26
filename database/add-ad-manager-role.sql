-- Add AD Manager Admin Role
-- Created: 2025-11-27
-- Purpose: Create specialized admin role for managing platform advertisements

-- Insert AD Manager role
INSERT INTO admin_roles (role_name, role_slug, description, is_active) 
VALUES ('AD Manager', 'ad_manager', 'Manages platform advertisements and campaigns', 1)
ON DUPLICATE KEY UPDATE 
    role_name = VALUES(role_name),
    description = VALUES(description);

-- Get the role_id for AD Manager
SET @ad_manager_role_id = (SELECT id FROM admin_roles WHERE role_slug = 'ad_manager');

-- Assign advertisement permissions to AD Manager role
INSERT INTO admin_role_permissions (role_id, permission_id)
SELECT @ad_manager_role_id, id 
FROM admin_permissions 
WHERE name IN ('view_ads', 'create_ads', 'edit_ads', 'delete_ads')
ON DUPLICATE KEY UPDATE role_id = VALUES(role_id);

-- Verify the role and permissions
SELECT 
    ar.id,
    ar.role_name,
    ar.role_slug,
    COUNT(arp.permission_id) as permission_count
FROM admin_roles ar
LEFT JOIN admin_role_permissions arp ON ar.id = arp.role_id
WHERE ar.role_slug = 'ad_manager'
GROUP BY ar.id, ar.role_name, ar.role_slug;

-- Show all permissions for AD Manager
SELECT 
    ar.role_name,
    ap.name as permission,
    ap.description
FROM admin_role_permissions arp
JOIN admin_roles ar ON arp.role_id = ar.id
JOIN admin_permissions ap ON arp.permission_id = ap.id
WHERE ar.role_slug = 'ad_manager'
ORDER BY ap.name;
