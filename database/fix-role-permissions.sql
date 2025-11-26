-- Fix admin_role_permissions table structure to use role_id instead of role enum
-- This migration updates the existing table to work with the admin_roles table

-- Drop the old table and recreate with proper structure
DROP TABLE IF EXISTS admin_role_permissions;

CREATE TABLE admin_role_permissions (
  id int(11) NOT NULL AUTO_INCREMENT,
  role_id int(11) NOT NULL,
  permission_id int(11) NOT NULL,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY unique_role_permission (role_id, permission_id),
  KEY role_id (role_id),
  KEY permission_id (permission_id),
  CONSTRAINT fk_arp_role FOREIGN KEY (role_id) REFERENCES admin_roles (id) ON DELETE CASCADE,
  CONSTRAINT fk_arp_permission FOREIGN KEY (permission_id) REFERENCES admin_permissions (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Now populate with role-permission mappings
-- Super Admin gets ALL permissions
INSERT INTO admin_role_permissions (role_id, permission_id)
SELECT 1, id FROM admin_permissions;

-- Content Manager permissions
INSERT INTO admin_role_permissions (role_id, permission_id)
SELECT 2, id FROM admin_permissions
WHERE name IN (
  'view_jobs', 'edit_jobs', 'delete_jobs', 'approve_jobs',
  'view_cvs', 'download_cvs', 'delete_cvs',
  'view_ads', 'create_ads', 'edit_ads', 'delete_ads',
  'view_job_seekers', 'view_employers'
);

-- Accountant permissions
INSERT INTO admin_role_permissions (role_id, permission_id)
SELECT 3, id FROM admin_permissions
WHERE name IN (
  'view_transactions', 'process_refunds', 'view_revenue', 'export_financial_data',
  'view_analytics', 'view_reports', 'export_reports'
);

-- User Manager permissions
INSERT INTO admin_role_permissions (role_id, permission_id)
SELECT 4, id FROM admin_permissions
WHERE name IN (
  'view_admin_users', 'create_admin_users', 'edit_admin_users',
  'view_job_seekers', 'edit_job_seekers', 'delete_job_seekers',
  'view_employers', 'edit_employers', 'delete_employers'
);

-- Analytics Manager permissions
INSERT INTO admin_role_permissions (role_id, permission_id)
SELECT 5, id FROM admin_permissions
WHERE name IN (
  'view_analytics', 'view_reports', 'export_reports',
  'view_jobs', 'view_job_seekers', 'view_employers',
  'view_transactions', 'view_revenue'
);

-- Support Agent permissions
INSERT INTO admin_role_permissions (role_id, permission_id)
SELECT 6, id FROM admin_permissions
WHERE name IN (
  'view_job_seekers', 'view_employers', 'view_jobs', 'view_cvs'
);

-- Update all existing admin users to Super Admin role
UPDATE users 
SET admin_role_id = 1
WHERE user_type = 'admin' AND (admin_role_id IS NULL OR admin_role_id = 0);

SELECT 'Admin role-permission mappings created successfully!' AS status;
SELECT 
  ar.role_name,
  COUNT(arp.permission_id) as permission_count
FROM admin_roles ar
LEFT JOIN admin_role_permissions arp ON ar.id = arp.role_id
GROUP BY ar.id
ORDER BY ar.id;
