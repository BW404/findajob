-- Add Flutterwave payment fields to existing transactions table
-- Created: 2025-11-28

-- Add new columns for Flutterwave integration
ALTER TABLE transactions 
ADD COLUMN IF NOT EXISTS tx_ref VARCHAR(100) UNIQUE AFTER transaction_reference,
ADD COLUMN IF NOT EXISTS flw_ref VARCHAR(100) AFTER tx_ref,
ADD COLUMN IF NOT EXISTS transaction_id VARCHAR(100) AFTER flw_ref,
ADD COLUMN IF NOT EXISTS customer_email VARCHAR(255) AFTER payment_gateway,
ADD COLUMN IF NOT EXISTS customer_name VARCHAR(255) AFTER customer_email,
ADD COLUMN IF NOT EXISTS customer_phone VARCHAR(20) AFTER customer_name,
ADD COLUMN IF NOT EXISTS metadata JSON AFTER description,
ADD COLUMN IF NOT EXISTS flw_response JSON AFTER metadata,
ADD COLUMN IF NOT EXISTS verified_at TIMESTAMP NULL AFTER updated_at;

-- Add new payment types for Flutterwave
ALTER TABLE transactions 
MODIFY COLUMN service_type ENUM(
    'nin_verification',
    'subscription',
    'job_booster',
    'cv_service',
    'job_posting',
    'featured_listing',
    'other'
) NOT NULL;

-- Update status enum to match Flutterwave responses
ALTER TABLE transactions 
MODIFY COLUMN status ENUM(
    'pending',
    'completed',
    'successful',
    'failed',
    'cancelled',
    'refunded'
) DEFAULT 'pending';

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_tx_ref ON transactions(tx_ref);
CREATE INDEX IF NOT EXISTS idx_flw_ref ON transactions(flw_ref);
CREATE INDEX IF NOT EXISTS idx_transaction_id ON transactions(transaction_id);

-- Verify the changes
DESCRIBE transactions;

SELECT 'Flutterwave payment fields added successfully!' as status;
