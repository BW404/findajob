-- Admin Users Table Migration
-- Creates a separate table for admin users with enhanced security and role-based permissions

-- Create admin_users table
CREATE TABLE admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    role ENUM('super_admin', 'admin', 'moderator') DEFAULT 'admin',
    permissions JSON,
    phone VARCHAR(20),
    avatar VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    email_verified BOOLEAN DEFAULT FALSE,
    last_login DATETIME,
    login_attempts INT DEFAULT 0,
    locked_until DATETIME NULL,
    password_reset_token VARCHAR(255),
    password_reset_expires DATETIME,
    two_factor_enabled BOOLEAN DEFAULT FALSE,
    two_factor_secret VARCHAR(32),
    session_token VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_is_active (is_active),
    INDEX idx_session_token (session_token),
    
    FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
);

-- Create admin_permissions table for granular permissions
CREATE TABLE admin_permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    module VARCHAR(50) NOT NULL,
    action VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_module (module),
    INDEX idx_action (action)
);

-- Create admin_role_permissions table for role-based access
CREATE TABLE admin_role_permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role ENUM('super_admin', 'admin', 'moderator') NOT NULL,
    permission_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (permission_id) REFERENCES admin_permissions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_role_permission (role, permission_id)
);

-- Insert default permissions
INSERT INTO admin_permissions (name, description, module, action) VALUES
-- Dashboard permissions
('dashboard.view', 'View admin dashboard', 'dashboard', 'view'),
('analytics.view', 'View analytics and reports', 'analytics', 'view'),

-- User management permissions
('users.view', 'View users list', 'users', 'view'),
('users.create', 'Create new users', 'users', 'create'),
('users.edit', 'Edit user details', 'users', 'edit'),
('users.delete', 'Delete users', 'users', 'delete'),
('users.verify', 'Verify user accounts', 'users', 'verify'),
('users.suspend', 'Suspend/unsuspend users', 'users', 'suspend'),

-- Job management permissions
('jobs.view', 'View jobs list', 'jobs', 'view'),
('jobs.create', 'Create new jobs', 'jobs', 'create'),
('jobs.edit', 'Edit job details', 'jobs', 'edit'),
('jobs.delete', 'Delete jobs', 'jobs', 'delete'),
('jobs.moderate', 'Moderate job postings', 'jobs', 'moderate'),
('jobs.feature', 'Feature/unfeature jobs', 'jobs', 'feature'),

-- Payment management permissions
('payments.view', 'View payments and transactions', 'payments', 'view'),
('payments.process', 'Process payments', 'payments', 'process'),
('payments.refund', 'Issue refunds', 'payments', 'refund'),
('subscriptions.manage', 'Manage subscriptions', 'subscriptions', 'manage'),

-- System management permissions
('system.settings', 'Manage system settings', 'system', 'settings'),
('system.backup', 'Create and manage backups', 'system', 'backup'),
('system.logs', 'View system logs', 'system', 'logs'),
('system.maintenance', 'System maintenance mode', 'system', 'maintenance'),

-- Admin management permissions (super admin only)
('admins.view', 'View admin users', 'admins', 'view'),
('admins.create', 'Create new admin users', 'admins', 'create'),
('admins.edit', 'Edit admin user details', 'admins', 'edit'),
('admins.delete', 'Delete admin users', 'admins', 'delete'),
('admins.permissions', 'Manage admin permissions', 'admins', 'permissions');

-- Assign permissions to roles
-- Super Admin gets all permissions
INSERT INTO admin_role_permissions (role, permission_id)
SELECT 'super_admin', id FROM admin_permissions;

-- Admin gets most permissions except admin management
INSERT INTO admin_role_permissions (role, permission_id)
SELECT 'admin', id FROM admin_permissions 
WHERE module NOT IN ('admins');

-- Moderator gets limited permissions
INSERT INTO admin_role_permissions (role, permission_id)
SELECT 'moderator', id FROM admin_permissions 
WHERE module IN ('dashboard', 'users', 'jobs') 
AND action IN ('view', 'moderate', 'verify');

-- Update admin_logs table to reference admin_users
ALTER TABLE admin_logs 
ADD COLUMN admin_user_id INT AFTER admin_id,
ADD FOREIGN KEY (admin_user_id) REFERENCES admin_users(id) ON DELETE SET NULL;