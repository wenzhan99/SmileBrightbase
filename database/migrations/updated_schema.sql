-- Updated SmileBright Database Schema
-- Matches the new API payload contract

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `smilebright` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- Use the database
USE `smilebright`;

-- Drop existing bookings table if it exists
DROP TABLE IF EXISTS `bookings`;

-- Create bookings table with new schema
CREATE TABLE `bookings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference_id` VARCHAR(32) NOT NULL UNIQUE,
  `dentist_id` VARCHAR(50) NOT NULL,
  `dentist_name` VARCHAR(100) NOT NULL,
  `clinic_id` VARCHAR(50) NOT NULL,
  `clinic_name` VARCHAR(100) NOT NULL,
  `service_code` VARCHAR(50) NOT NULL,
  `service_label` VARCHAR(100) NOT NULL,
  `experience_code` VARCHAR(50) NOT NULL,
  `experience_label` VARCHAR(100) NOT NULL,
  `preferred_date` DATE NOT NULL,
  `preferred_time` TIME NOT NULL,
  `first_name` VARCHAR(60) NOT NULL,
  `last_name` VARCHAR(60) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `notes` TEXT NULL,
  `agree_policy` TINYINT(1) NOT NULL DEFAULT 0,
  `agree_terms` TINYINT(1) NOT NULL DEFAULT 0,
  `status` VARCHAR(20) NOT NULL DEFAULT 'scheduled',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_reference_id` (`reference_id`),
  INDEX `idx_preferred_date` (`preferred_date`),
  INDEX `idx_dentist_date` (`dentist_id`, `preferred_date`),
  INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample data for testing
INSERT INTO `bookings` (
  `reference_id`, `dentist_id`, `dentist_name`, `clinic_id`, `clinic_name`,
  `service_code`, `service_label`, `experience_code`, `experience_label`,
  `preferred_date`, `preferred_time`, `first_name`, `last_name`, `email`, `phone`, `notes`,
  `agree_policy`, `agree_terms`, `status`
) VALUES (
  'SB-20250115-0001', 'dr-aisha-rahman', 'Dr. Aisha Rahman', 'tampines', 'Tampines Clinic',
  'general_checkup', 'General Checkup', 'first_time', 'First time patient',
  '2025-01-15', '14:00:00', 'John', 'Doe', 'john.doe@example.com', '+65 1234 5678', 'Sample booking',
  1, 1, 'scheduled'
);

-- Show the created table structure
DESCRIBE `bookings`;
