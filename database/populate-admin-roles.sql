-- Insert Admin Roles and Permissions Data
-- This file populates the roles and permissions tables

-- Check if users table has admin_role_id column, if not add it
SET @column_exists = (
  SELECT COUNT(*) 
  FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'findajob_ng' 
  AND TABLE_NAME = 'users' 
  AND COLUMN_NAME = 'admin_role_id'
);

SET @sql = IF(@column_exists = 0,
  'ALTER TABLE users ADD COLUMN admin_role_id int(11) NULL AFTER user_type, ADD KEY admin_role_id (admin_role_id), ADD CONSTRAINT fk_users_admin_role FOREIGN KEY (admin_role_id) REFERENCES admin_roles (id) ON DELETE SET NULL',
  'SELECT "Column already exists" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Clear existing data
TRUNCATE TABLE admin_role_permissions;
DELETE FROM admin_roles;
DELETE FROM admin_permissions;

-- Reset auto increment
ALTER TABLE admin_roles AUTO_INCREMENT = 1;
ALTER TABLE admin_permissions AUTO_INCREMENT = 1;
ALTER TABLE admin_role_permissions AUTO_INCREMENT = 1;

-- Insert default admin roles
INSERT INTO admin_roles (role_name, role_slug, description, is_active) VALUES
('Super Admin', 'super_admin', 'Full system access with all permissions', 1),
('Content Manager', 'content_manager', 'Manage jobs, CVs, and advertisements', 1),
('Accountant', 'accountant', 'Manage transactions, payments, and financial reports', 1),
('User Manager', 'user_manager', 'Manage job seekers and employers', 1),
('Analytics Manager', 'analytics_manager', 'View reports and analytics', 1),
('Support Agent', 'support_agent', 'Limited access for customer support', 1);

-- Insert all permissions using existing table structure (name, module, action, description)
INSERT INTO admin_permissions (name, module, action, description) VALUES
-- User Management Permissions
('view_admin_users', 'users', 'view', 'View list of admin users'),
('create_admin_users', 'users', 'create', 'Create new admin users'),
('edit_admin_users', 'users', 'edit', 'Edit existing admin users'),
('delete_admin_users', 'users', 'delete', 'Delete admin users'),
('manage_roles', 'users', 'manage', 'Create and manage admin roles'),
('view_job_seekers', 'users', 'view', 'View job seeker accounts'),
('edit_job_seekers', 'users', 'edit', 'Edit job seeker accounts'),
('delete_job_seekers', 'users', 'delete', 'Delete job seeker accounts'),
('view_employers', 'users', 'view', 'View employer accounts'),
('edit_employers', 'users', 'edit', 'Edit employer accounts'),
('delete_employers', 'users', 'delete', 'Delete employer accounts'),

-- Job Management Permissions
('view_jobs', 'jobs', 'view', 'View all job postings'),
('edit_jobs', 'jobs', 'edit', 'Edit job postings'),
('delete_jobs', 'jobs', 'delete', 'Delete job postings'),
('approve_jobs', 'jobs', 'approve', 'Approve/reject job postings'),

-- Content Management Permissions
('view_cvs', 'content', 'view', 'View uploaded CVs'),
('download_cvs', 'content', 'download', 'Download CV files'),
('delete_cvs', 'content', 'delete', 'Delete CVs'),
('view_ads', 'content', 'view', 'View advertisements'),
('create_ads', 'content', 'create', 'Create new advertisements'),
('edit_ads', 'content', 'edit', 'Edit advertisements'),
('delete_ads', 'content', 'delete', 'Delete advertisements'),

-- Financial Permissions
('view_transactions', 'finance', 'view', 'View payment transactions'),
('process_refunds', 'finance', 'process', 'Process refund requests'),
('view_revenue', 'finance', 'view', 'View revenue reports'),
('export_financial_data', 'finance', 'export', 'Export financial reports'),

-- Analytics Permissions
('view_analytics', 'analytics', 'view', 'View platform analytics'),
('view_reports', 'analytics', 'view', 'View all reports'),
('export_reports', 'analytics', 'export', 'Export report data'),

-- System Permissions
('view_settings', 'system', 'view', 'View system settings'),
('edit_settings', 'system', 'edit', 'Modify system settings'),
('manage_api', 'system', 'manage', 'Manage API integrations'),
('view_logs', 'system', 'view', 'View system logs'),
('clear_cache', 'system', 'clear', 'Clear system cache');

-- Assign all permissions to Super Admin role
INSERT INTO admin_role_permissions (role_id, permission_id)
SELECT 
  (SELECT id FROM admin_roles WHERE role_slug = 'super_admin'),
  id
FROM admin_permissions;

-- Assign Content Manager permissions
INSERT INTO admin_role_permissions (role_id, permission_id)
SELECT 
  (SELECT id FROM admin_roles WHERE role_slug = 'content_manager'),
  id
FROM admin_permissions
WHERE name IN (
  'view_jobs', 'edit_jobs', 'delete_jobs', 'approve_jobs',
  'view_cvs', 'download_cvs', 'delete_cvs',
  'view_ads', 'create_ads', 'edit_ads', 'delete_ads',
  'view_job_seekers', 'view_employers'
);

-- Assign Accountant permissions
INSERT INTO admin_role_permissions (role_id, permission_id)
SELECT 
  (SELECT id FROM admin_roles WHERE role_slug = 'accountant'),
  id
FROM admin_permissions
WHERE name IN (
  'view_transactions', 'process_refunds', 'view_revenue', 'export_financial_data',
  'view_analytics', 'view_reports', 'export_reports'
);

-- Assign User Manager permissions
INSERT INTO admin_role_permissions (role_id, permission_id)
SELECT 
  (SELECT id FROM admin_roles WHERE role_slug = 'user_manager'),
  id
FROM admin_permissions
WHERE name IN (
  'view_admin_users', 'create_admin_users', 'edit_admin_users',
  'view_job_seekers', 'edit_job_seekers', 'delete_job_seekers',
  'view_employers', 'edit_employers', 'delete_employers'
);

-- Assign Analytics Manager permissions
INSERT INTO admin_role_permissions (role_id, permission_id)
SELECT 
  (SELECT id FROM admin_roles WHERE role_slug = 'analytics_manager'),
  id
FROM admin_permissions
WHERE name IN (
  'view_analytics', 'view_reports', 'export_reports',
  'view_jobs', 'view_job_seekers', 'view_employers',
  'view_transactions', 'view_revenue'
);

-- Assign Support Agent permissions
INSERT INTO admin_role_permissions (role_id, permission_id)
SELECT 
  (SELECT id FROM admin_roles WHERE role_slug = 'support_agent'),
  id
FROM admin_permissions
WHERE name IN (
  'view_job_seekers', 'view_employers', 'view_jobs', 'view_cvs'
);

-- Update existing admin users to Super Admin role
UPDATE users 
SET admin_role_id = (SELECT id FROM admin_roles WHERE role_slug = 'super_admin')
WHERE user_type = 'admin' AND (admin_role_id IS NULL OR admin_role_id = 0);

SELECT 'Admin roles and permissions setup complete!' AS status;
