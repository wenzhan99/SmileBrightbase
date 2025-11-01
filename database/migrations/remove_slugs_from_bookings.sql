-- Migration: Remove doctor_slug and location_slug from bookings table
-- This makes the bookings table use pure names (NO SLUGS)
-- Run this in phpMyAdmin or MySQL command line

USE smilebrightbase;

-- Step 1: Drop the foreign key constraints
ALTER TABLE bookings 
  DROP FOREIGN KEY IF EXISTS fk_b_doctor,
  DROP FOREIGN KEY IF EXISTS fk_b_location;

-- Step 2: Drop the doctor_slug and location_slug columns
ALTER TABLE bookings
  DROP COLUMN IF EXISTS doctor_slug,
  DROP COLUMN IF EXISTS location_slug;

-- Step 3: Modify unique constraint to use doctor_name instead of doctor_slug
-- First drop the old constraint
ALTER TABLE bookings 
  DROP INDEX IF EXISTS uq_doctor_slot;

-- Add new constraint using doctor_name
ALTER TABLE bookings
  ADD UNIQUE KEY uq_doctor_slot (doctor_name, date, time);

-- Verify the changes
DESCRIBE bookings;

-- Verify foreign keys (should only have fk_b_service now)
SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'smilebrightbase'
  AND TABLE_NAME = 'bookings'
  AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY CONSTRAINT_NAME;

