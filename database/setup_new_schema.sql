-- SmileBright Booking Rebuild (No-AJAX Flow) Database Setup
-- Version: 2025-10-31-1
-- Run this script in phpMyAdmin or MySQL command line

-- ============================================
-- DATABASE 1: smilebrightbase
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

-- Insert data into clinics
INSERT INTO clinics (slug, name) VALUES
('orchard', 'Orchard Clinic'),
('marina-bay', 'Marina Bay Clinic'),
('bukit-timah', 'Bukit Timah Clinic'),
('tampines', 'Tampines Clinic'),
('jurong', 'Jurong Clinic');

-- Insert data into doctors
INSERT INTO doctors (slug, name, clinic_slug) VALUES
('dr-chua-wen-zhan', 'Dr. Chua Wen Zhan', 'orchard'),
('dr-lau-gwen', 'Dr. Lau Gwen', 'orchard'),
('dr-sarah-tan', 'Dr. Sarah Tan', 'marina-bay'),
('dr-james-lim', 'Dr. James Lim', 'bukit-timah'),
('dr-aisha-rahman', 'Dr. Aisha Rahman', 'tampines'),
('dr-alex-lee', 'Dr. Alex Lee', 'jurong');

-- Insert data into services
INSERT INTO services (service_key, label) VALUES
('general', 'General Checkup'),
('cleaning', 'Teeth Cleaning'),
('filling', 'Dental Filling'),
('extraction', 'Tooth Extraction'),
('braces', 'Braces Consultation'),
('whitening', 'Teeth Whitening'),
('implant', 'Dental Implant'),
('others', 'Others');

-- ============================================
-- DATABASE 2: smilebright
-- ============================================

DROP DATABASE IF EXISTS smilebright;
CREATE DATABASE smilebright CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smilebright;

-- Create bookings table
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
  
  -- Constraints
  UNIQUE KEY uq_doctor_slot (doctor_slug, date, time),
  
  -- Foreign key constraints to smilebrightbase
  CONSTRAINT fk_b_service FOREIGN KEY (service_key) REFERENCES smilebrightbase.services(service_key),
  CONSTRAINT fk_b_doctor FOREIGN KEY (doctor_slug) REFERENCES smilebrightbase.doctors(slug),
  CONSTRAINT fk_b_location FOREIGN KEY (location_slug) REFERENCES smilebrightbase.clinics(slug)
) ENGINE=InnoDB;

-- Verify tables
SELECT 'smilebrightbase.clinics' as table_name, COUNT(*) as count FROM smilebrightbase.clinics
UNION ALL
SELECT 'smilebrightbase.doctors', COUNT(*) FROM smilebrightbase.doctors
UNION ALL
SELECT 'smilebrightbase.services', COUNT(*) FROM smilebrightbase.services
UNION ALL
SELECT 'smilebright.bookings', COUNT(*) FROM smilebright.bookings;

