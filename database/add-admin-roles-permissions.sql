-- Admin Roles and Permissions System
-- Run this migration to add role-based access control for admin users

-- Create admin roles table
CREATE TABLE IF NOT EXISTS `admin_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  `role_slug` varchar(50) NOT NULL,
  `description` text,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_slug` (`role_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create permissions table
CREATE TABLE IF NOT EXISTS `admin_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `permission_name` varchar(100) NOT NULL,
  `permission_slug` varchar(100) NOT NULL,
  `permission_group` varchar(50) NOT NULL COMMENT 'users, jobs, content, finance, analytics, system',
  `description` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permission_slug` (`permission_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create role_permissions junction table
CREATE TABLE IF NOT EXISTS `admin_role_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_permission_unique` (`role_id`, `permission_id`),
  KEY `role_id` (`role_id`),
  KEY `permission_id` (`permission_id`),
  CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `admin_roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `admin_permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add admin_role_id column to users table
ALTER TABLE `users` ADD COLUMN `admin_role_id` int(11) NULL AFTER `user_type`;
ALTER TABLE `users` ADD KEY `admin_role_id` (`admin_role_id`);
ALTER TABLE `users` ADD CONSTRAINT `fk_users_admin_role` FOREIGN KEY (`admin_role_id`) REFERENCES `admin_roles` (`id`) ON DELETE SET NULL;

-- Insert default admin roles
INSERT INTO `admin_roles` (`role_name`, `role_slug`, `description`, `is_active`) VALUES
('Super Admin', 'super_admin', 'Full system access with all permissions', 1),
('Content Manager', 'content_manager', 'Manage jobs, CVs, and advertisements', 1),
('Accountant', 'accountant', 'Manage transactions, payments, and financial reports', 1),
('User Manager', 'user_manager', 'Manage job seekers and employers', 1),
('Analytics Manager', 'analytics_manager', 'View reports and analytics', 1),
('Support Agent', 'support_agent', 'Limited access for customer support', 1);

-- Insert all permissions
INSERT INTO `admin_permissions` (`permission_name`, `permission_slug`, `permission_group`, `description`) VALUES
-- User Management Permissions
('View Admin Users', 'view_admin_users', 'users', 'View list of admin users'),
('Create Admin Users', 'create_admin_users', 'users', 'Create new admin users'),
('Edit Admin Users', 'edit_admin_users', 'users', 'Edit existing admin users'),
('Delete Admin Users', 'delete_admin_users', 'users', 'Delete admin users'),
('Manage Roles', 'manage_roles', 'users', 'Create and manage admin roles'),
('View Job Seekers', 'view_job_seekers', 'users', 'View job seeker accounts'),
('Edit Job Seekers', 'edit_job_seekers', 'users', 'Edit job seeker accounts'),
('Delete Job Seekers', 'delete_job_seekers', 'users', 'Delete job seeker accounts'),
('View Employers', 'view_employers', 'users', 'View employer accounts'),
('Edit Employers', 'edit_employers', 'users', 'Edit employer accounts'),
('Delete Employers', 'delete_employers', 'users', 'Delete employer accounts'),

-- Job Management Permissions
('View Jobs', 'view_jobs', 'jobs', 'View all job postings'),
('Edit Jobs', 'edit_jobs', 'jobs', 'Edit job postings'),
('Delete Jobs', 'delete_jobs', 'jobs', 'Delete job postings'),
('Approve Jobs', 'approve_jobs', 'jobs', 'Approve/reject job postings'),

-- Content Management Permissions
('View CVs', 'view_cvs', 'content', 'View uploaded CVs'),
('Download CVs', 'download_cvs', 'content', 'Download CV files'),
('Delete CVs', 'delete_cvs', 'content', 'Delete CVs'),
('View Ads', 'view_ads', 'content', 'View advertisements'),
('Create Ads', 'create_ads', 'content', 'Create new advertisements'),
('Edit Ads', 'edit_ads', 'content', 'Edit advertisements'),
('Delete Ads', 'delete_ads', 'content', 'Delete advertisements'),

-- Financial Permissions
('View Transactions', 'view_transactions', 'finance', 'View payment transactions'),
('Process Refunds', 'process_refunds', 'finance', 'Process refund requests'),
('View Revenue', 'view_revenue', 'finance', 'View revenue reports'),
('Export Financial Data', 'export_financial_data', 'finance', 'Export financial reports'),

-- Analytics Permissions
('View Analytics', 'view_analytics', 'analytics', 'View platform analytics'),
('View Reports', 'view_reports', 'analytics', 'View all reports'),
('Export Reports', 'export_reports', 'analytics', 'Export report data'),

-- System Permissions
('View Settings', 'view_settings', 'system', 'View system settings'),
('Edit Settings', 'edit_settings', 'system', 'Modify system settings'),
('Manage API', 'manage_api', 'system', 'Manage API integrations'),
('View Logs', 'view_logs', 'system', 'View system logs'),
('Clear Cache', 'clear_cache', 'system', 'Clear system cache');

-- Assign all permissions to Super Admin role
INSERT INTO `admin_role_permissions` (`role_id`, `permission_id`)
SELECT 
  (SELECT id FROM admin_roles WHERE role_slug = 'super_admin'),
  id
FROM admin_permissions;

-- Assign Content Manager permissions
INSERT INTO `admin_role_permissions` (`role_id`, `permission_id`)
SELECT 
  (SELECT id FROM admin_roles WHERE role_slug = 'content_manager'),
  id
FROM admin_permissions
WHERE permission_slug IN (
  'view_jobs', 'edit_jobs', 'delete_jobs', 'approve_jobs',
  'view_cvs', 'download_cvs', 'delete_cvs',
  'view_ads', 'create_ads', 'edit_ads', 'delete_ads',
  'view_job_seekers', 'view_employers'
);

-- Assign Accountant permissions
INSERT INTO `admin_role_permissions` (`role_id`, `permission_id`)
SELECT 
  (SELECT id FROM admin_roles WHERE role_slug = 'accountant'),
  id
FROM admin_permissions
WHERE permission_slug IN (
  'view_transactions', 'process_refunds', 'view_revenue', 'export_financial_data',
  'view_analytics', 'view_reports', 'export_reports'
);

-- Assign User Manager permissions
INSERT INTO `admin_role_permissions` (`role_id`, `permission_id`)
SELECT 
  (SELECT id FROM admin_roles WHERE role_slug = 'user_manager'),
  id
FROM admin_permissions
WHERE permission_slug IN (
  'view_admin_users', 'create_admin_users', 'edit_admin_users',
  'view_job_seekers', 'edit_job_seekers', 'delete_job_seekers',
  'view_employers', 'edit_employers', 'delete_employers'
);

-- Assign Analytics Manager permissions
INSERT INTO `admin_role_permissions` (`role_id`, `permission_id`)
SELECT 
  (SELECT id FROM admin_roles WHERE role_slug = 'analytics_manager'),
  id
FROM admin_permissions
WHERE permission_slug IN (
  'view_analytics', 'view_reports', 'export_reports',
  'view_jobs', 'view_job_seekers', 'view_employers',
  'view_transactions', 'view_revenue'
);

-- Assign Support Agent permissions
INSERT INTO `admin_role_permissions` (`role_id`, `permission_id`)
SELECT 
  (SELECT id FROM admin_roles WHERE role_slug = 'support_agent'),
  id
FROM admin_permissions
WHERE permission_slug IN (
  'view_job_seekers', 'view_employers', 'view_jobs', 'view_cvs'
);

-- Update existing admin users to Super Admin role
UPDATE users 
SET admin_role_id = (SELECT id FROM admin_roles WHERE role_slug = 'super_admin')
WHERE user_type = 'admin' AND admin_role_id IS NULL;

-- Create indexes for better performance
CREATE INDEX idx_role_active ON admin_roles(is_active);
CREATE INDEX idx_permission_group ON admin_permissions(permission_group);
