-- Fix Foreign Keys: Ensure bookings table references smilebrightbase (not smilebright)
-- Run this script in phpMyAdmin after running setup_spec_compliant.sql

USE smilebright;

-- Step 1: Drop existing foreign keys if they exist (they might point to wrong database)
ALTER TABLE bookings 
  DROP FOREIGN KEY IF EXISTS fk_b_service,
  DROP FOREIGN KEY IF EXISTS fk_b_doctor,
  DROP FOREIGN KEY IF EXISTS fk_b_location;

-- Step 2: Add correct foreign keys pointing to smilebrightbase
ALTER TABLE bookings
  ADD CONSTRAINT fk_b_service FOREIGN KEY (service_key) 
    REFERENCES smilebrightbase.services(service_key),
  ADD CONSTRAINT fk_b_doctor FOREIGN KEY (doctor_slug) 
    REFERENCES smilebrightbase.doctors(slug),
  ADD CONSTRAINT fk_b_location FOREIGN KEY (location_slug) 
    REFERENCES smilebrightbase.clinics(slug);

-- Step 3: Verify the foreign keys
SELECT 
    CONSTRAINT_NAME,
    TABLE_SCHEMA,
    TABLE_NAME,
    REFERENCED_TABLE_SCHEMA,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'smilebright'
  AND TABLE_NAME = 'bookings'
  AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY CONSTRAINT_NAME;

-- Expected output should show:
-- fk_b_service -> smilebrightbase.services
-- fk_b_doctor -> smilebrightbase.doctors  
-- fk_b_location -> smilebrightbase.clinics

