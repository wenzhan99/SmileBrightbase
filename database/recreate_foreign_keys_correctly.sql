-- Complete script to recreate bookings table with correct foreign keys
-- This will DROP and recreate the bookings table with proper foreign key references to smilebrightbase
-- WARNING: This will DELETE all existing booking data!

USE smilebright;

-- Drop the bookings table completely
DROP TABLE IF EXISTS bookings;

-- Recreate bookings table with correct foreign keys to smilebrightbase
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference_id VARCHAR(20) NOT NULL UNIQUE,
    doctor_slug VARCHAR(50) NOT NULL,
    doctor_name VARCHAR(100) NOT NULL,
    location_slug VARCHAR(50) NOT NULL,
    location_name VARCHAR(100) NOT NULL,
    service_key VARCHAR(50) NOT NULL,
    patient_type ENUM('first-time', 'regular', 'returning') NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(40) NOT NULL,
    notes TEXT NULL,
    status ENUM('confirmed', 'rescheduled', 'cancelled', 'completed') NOT NULL DEFAULT 'confirmed',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_doctor_slot (doctor_slug, date, time),
    -- CORRECT: Foreign keys reference smilebrightbase (NOT smilebright)
    CONSTRAINT fk_b_service FOREIGN KEY (service_key) REFERENCES smilebrightbase.services(service_key),
    CONSTRAINT fk_b_doctor FOREIGN KEY (doctor_slug) REFERENCES smilebrightbase.doctors(slug),
    CONSTRAINT fk_b_location FOREIGN KEY (location_slug) REFERENCES smilebrightbase.clinics(slug)
) ENGINE=InnoDB;

-- Verify the foreign keys are correct
SELECT 
    'Foreign Key Check' as check_type,
    CONSTRAINT_NAME,
    TABLE_SCHEMA as 'bookings_database',
    TABLE_NAME,
    REFERENCED_TABLE_SCHEMA as 'referenced_database',
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'smilebright'
  AND TABLE_NAME = 'bookings'
  AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY CONSTRAINT_NAME;

-- Expected result: All REFERENCED_TABLE_SCHEMA should be 'smilebrightbase'

