-- Add account suspension fields to users table
-- This allows admins to temporarily suspend accounts based on reports

ALTER TABLE users 
ADD COLUMN is_suspended BOOLEAN DEFAULT FALSE AFTER is_active,
ADD COLUMN suspension_reason TEXT DEFAULT NULL AFTER is_suspended,
ADD COLUMN suspended_at TIMESTAMP NULL DEFAULT NULL AFTER suspension_reason,
ADD COLUMN suspended_by INT NULL DEFAULT NULL AFTER suspended_at,
ADD COLUMN suspension_expires TIMESTAMP NULL DEFAULT NULL AFTER suspended_by,
ADD CONSTRAINT fk_suspended_by FOREIGN KEY (suspended_by) REFERENCES users(id) ON DELETE SET NULL;

-- Add index for faster queries
CREATE INDEX idx_is_suspended ON users(is_suspended);
CREATE INDEX idx_suspension_expires ON users(suspension_expires);
