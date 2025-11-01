-- QUICK FIX: Remove slugs from bookings table
-- Run this in phpMyAdmin SQL tab to fix the foreign key error

USE smilebrightbase;

-- Step 1: Drop foreign key constraints
ALTER TABLE bookings 
  DROP FOREIGN KEY IF EXISTS fk_b_doctor,
  DROP FOREIGN KEY IF EXISTS fk_b_location;

-- Step 2: Drop the slug columns
ALTER TABLE bookings
  DROP COLUMN IF EXISTS doctor_slug,
  DROP COLUMN IF EXISTS location_slug;

-- Step 3: Drop old unique constraint (based on doctor_slug)
ALTER TABLE bookings 
  DROP INDEX IF EXISTS uq_doctor_slot;

-- Step 4: Add new unique constraint (based on doctor_name)
ALTER TABLE bookings
  ADD UNIQUE KEY uq_doctor_slot (doctor_name, date, time);

-- Verify it worked
DESCRIBE bookings;

