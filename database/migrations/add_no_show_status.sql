-- Add 'no-show' status to bookings table
-- Run this migration to support no-show status in the doctor dashboard

USE smilebright;

-- Check if no-show is already in the enum
-- If not, we need to modify the enum
ALTER TABLE bookings MODIFY COLUMN status ENUM('confirmed', 'rescheduled', 'cancelled', 'completed', 'no-show') NOT NULL DEFAULT 'confirmed';

-- Verify the change
SHOW COLUMNS FROM bookings WHERE Field = 'status';

