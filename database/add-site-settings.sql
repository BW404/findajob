-- Create site_settings table for storing application settings
CREATE TABLE IF NOT EXISTS site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type VARCHAR(50) DEFAULT 'string', -- string, number, boolean, json
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default Flutterwave settings
INSERT INTO site_settings (setting_key, setting_value, setting_type, description) VALUES
('flutterwave_public_key', 'FLWPUBK_TEST-22f24c499184047fee7003b68e0ad9d3-X', 'string', 'Flutterwave Public API Key'),
('flutterwave_secret_key', 'FLWSECK_TEST-36067985891ec3bb7dd1bcbb0719fdbc-X', 'string', 'Flutterwave Secret API Key'),
('flutterwave_encryption_key', 'FLWSECK_TEST6cfd4e1962bb', 'string', 'Flutterwave Encryption Key'),
('flutterwave_environment', 'test', 'string', 'Flutterwave Environment (test or live)'),
('flutterwave_webhook_url', '', 'string', 'Flutterwave Webhook URL')
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

SELECT 'Site settings table created and Flutterwave defaults inserted!' as status;
