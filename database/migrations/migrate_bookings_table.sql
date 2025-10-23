-- Migration script to add missing columns to existing bookings table
-- Run this in phpMyAdmin or MySQL command line

USE `smilebright`;

-- Add missing columns to bookings table
ALTER TABLE bookings 
ADD COLUMN reschedule_token CHAR(64) NULL AFTER created_at,
ADD COLUMN token_expires_at DATETIME NULL AFTER reschedule_token,
ADD COLUMN terms_accepted TINYINT(1) NOT NULL DEFAULT 0 AFTER token_expires_at;

-- Create unique index on reschedule_token
CREATE UNIQUE INDEX ux_bookings_reschedule_token ON bookings(reschedule_token);

-- Backfill existing records with tokens and reference IDs
UPDATE bookings 
SET 
    reschedule_token = SHA2(CONCAT(id,'-',RAND(),'-',NOW()), 256),
    token_expires_at = DATE_ADD(NOW(), INTERVAL 30 DAY)
WHERE reschedule_token IS NULL;

-- Generate reference IDs for existing records if they don't have them
UPDATE bookings 
SET reference_id = CONCAT('SB', DATE_FORMAT(created_at,'%y%m'), LPAD(id,4,'0'))
WHERE (reference_id IS NULL OR reference_id = '');

-- Verify the changes
DESCRIBE bookings;
