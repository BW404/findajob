-- Add payment_transactions table
-- This table stores all payment transactions from Flutterwave

CREATE TABLE IF NOT EXISTS `payment_transactions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `transaction_ref` VARCHAR(100) NOT NULL COMMENT 'Our internal reference',
  `flutterwave_ref` VARCHAR(100) DEFAULT NULL COMMENT 'Flutterwave transaction reference',
  `flutterwave_id` VARCHAR(100) DEFAULT NULL COMMENT 'Flutterwave transaction ID',
  `service_type` VARCHAR(100) NOT NULL COMMENT 'Type of service purchased',
  `service_name` VARCHAR(255) DEFAULT NULL COMMENT 'Human readable service name',
  `amount` DECIMAL(10,2) NOT NULL COMMENT 'Transaction amount',
  `currency` VARCHAR(10) DEFAULT 'NGN' COMMENT 'Currency code',
  `status` ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending' COMMENT 'Transaction status',
  `payment_method` VARCHAR(50) DEFAULT NULL COMMENT 'Payment method used',
  `customer_email` VARCHAR(255) DEFAULT NULL COMMENT 'Customer email',
  `customer_phone` VARCHAR(20) DEFAULT NULL COMMENT 'Customer phone',
  `metadata` TEXT DEFAULT NULL COMMENT 'Additional transaction metadata (JSON)',
  `response_data` TEXT DEFAULT NULL COMMENT 'Full payment gateway response (JSON)',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transaction_ref` (`transaction_ref`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_flutterwave_ref` (`flutterwave_ref`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_payment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Payment transactions from Flutterwave';
