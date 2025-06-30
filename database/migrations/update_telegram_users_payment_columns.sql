-- Add payment verification columns to telegram_users table
ALTER TABLE telegram_users 
ADD COLUMN IF NOT EXISTS awaiting_sender_wallet BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS awaiting_screenshot BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS payment_step INT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS sender_wallet_address VARCHAR(255) DEFAULT NULL;

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_awaiting_sender_wallet ON telegram_users(awaiting_sender_wallet);
CREATE INDEX IF NOT EXISTS idx_awaiting_screenshot ON telegram_users(awaiting_screenshot);
CREATE INDEX IF NOT EXISTS idx_payment_step ON telegram_users(payment_step);
