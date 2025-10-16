-- Migration: Add reschedule tokens to existing appointments table
-- Run this if you already have the appointments table created

USE smilebright;

-- Add reschedule_token column if it doesn't exist
ALTER TABLE appointments 
ADD COLUMN IF NOT EXISTS reschedule_token VARCHAR(64),
ADD COLUMN IF NOT EXISTS token_expires_at TIMESTAMP NULL,
ADD INDEX IF NOT EXISTS idx_reschedule_token (reschedule_token);

-- Update existing appointments to have reschedule tokens (optional)
-- This generates tokens for old appointments that don't have them yet
UPDATE appointments 
SET 
    reschedule_token = MD5(CONCAT(id, email, NOW(), RAND())),
    token_expires_at = DATE_ADD(created_at, INTERVAL 30 DAY)
WHERE reschedule_token IS NULL OR reschedule_token = '';

SELECT 'Migration completed successfully!' AS status;


