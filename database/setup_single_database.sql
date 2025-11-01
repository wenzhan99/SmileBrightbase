-- SmileBright Booking Rebuild Database Setup (Single Database Version)
-- Version: 2025-10-31-1 (All tables in smilebrightbase)
-- Run this script in phpMyAdmin or MySQL command line

-- ============================================
-- Create smilebrightbase database with all tables
-- ============================================
DROP DATABASE IF EXISTS smilebrightbase;
CREATE DATABASE smilebrightbase CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smilebrightbase;

-- Create clinics table
CREATE TABLE clinics (
    slug VARCHAR(50) PRIMARY KEY,
    name VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

-- Create doctors table
CREATE TABLE doctors (
    slug VARCHAR(50) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    clinic_slug VARCHAR(50) NOT NULL,
    CONSTRAINT fk_doctor_clinic FOREIGN KEY (clinic_slug) REFERENCES clinics(slug)
) ENGINE=InnoDB;

-- Create services table
CREATE TABLE services (
    service_key VARCHAR(50) PRIMARY KEY,
    label VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

-- Insert clinic data
INSERT INTO clinics (slug, name) VALUES
('orchard', 'Orchard Clinic'),
('marina-bay', 'Marina Bay Clinic'),
('bukit-timah', 'Bukit Timah Clinic'),
('tampines', 'Tampines Clinic'),
('jurong', 'Jurong Clinic');

-- Insert doctor data
INSERT INTO doctors (slug, name, clinic_slug) VALUES
('dr-chua-wen-zhan', 'Dr. Chua Wen Zhan', 'orchard'),
('dr-lau-gwen', 'Dr. Lau Gwen', 'orchard'),
('dr-sarah-tan', 'Dr. Sarah Tan', 'marina-bay'),
('dr-james-lim', 'Dr. James Lim', 'bukit-timah'),
('dr-aisha-rahman', 'Dr. Aisha Rahman', 'tampines'),
('dr-alex-lee', 'Dr. Alex Lee', 'jurong');

-- Insert service data
INSERT INTO services (service_key, label) VALUES
('general', 'General Checkup'),
('cleaning', 'Teeth Cleaning'),
('filling', 'Dental Filling'),
('extraction', 'Tooth Extraction'),
('braces', 'Braces Consultation'),
('whitening', 'Teeth Whitening'),
('implant', 'Dental Implant'),
('others', 'Others');

-- Create bookings table in same database
-- Using pure names (NO SLUGS in bookings table)
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference_id VARCHAR(20) NOT NULL UNIQUE,
    doctor_name VARCHAR(100) NOT NULL,
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
    -- Unique constraint uses doctor_name instead of doctor_slug
    UNIQUE KEY uq_doctor_slot (doctor_name, date, time),
    -- Foreign key only for service_key (since we don't have doctor_slug/location_slug)
    CONSTRAINT fk_b_service FOREIGN KEY (service_key) REFERENCES services(service_key)
) ENGINE=InnoDB;

-- Verify all tables exist
SHOW TABLES;

-- Verify foreign keys
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

