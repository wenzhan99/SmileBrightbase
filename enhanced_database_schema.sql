-- Enhanced database schema for SmileBright booking system
-- Version 1.0 with reschedule policy and admin adjustments

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `smilebright` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- Use the database
USE `smilebright`;

-- Enhanced bookings table with all required fields
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference_id` VARCHAR(20) NOT NULL UNIQUE,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `preferred_clinic` VARCHAR(100) NOT NULL,
  `service` VARCHAR(120) NOT NULL,
  `preferred_date` DATE NOT NULL,
  `preferred_time` TIME NOT NULL,
  `message` TEXT NULL,
  `status` ENUM('confirmed', 'rescheduled', 'cancelled', 'completed') DEFAULT 'confirmed',
  `reschedule_token` VARCHAR(64) NOT NULL UNIQUE,
  `token_expires_at` DATETIME NOT NULL,
  `terms_accepted` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_reference_id` (`reference_id`),
  INDEX `idx_reschedule_token` (`reschedule_token`),
  INDEX `idx_email` (`email`),
  INDEX `idx_preferred_date` (`preferred_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Admin adjustments audit log
CREATE TABLE IF NOT EXISTS `booking_adjustments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id` INT UNSIGNED NOT NULL,
  `adjustment_type` ENUM('client_reschedule', 'admin_adjustment') NOT NULL,
  `old_date` DATE NOT NULL,
  `old_time` TIME NOT NULL,
  `new_date` DATE NOT NULL,
  `new_time` TIME NOT NULL,
  `reason` VARCHAR(255) NOT NULL,
  `reason_note` TEXT NULL,
  `adjusted_by` VARCHAR(100) NULL, -- NULL for client reschedules
  `email_sent_at` TIMESTAMP NULL,
  `sms_sent_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
  INDEX `idx_booking_id` (`booking_id`),
  INDEX `idx_adjustment_type` (`adjustment_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Admin users table
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `role` ENUM('admin', 'staff') DEFAULT 'staff',
  `is_active` BOOLEAN DEFAULT TRUE,
  `last_login` TIMESTAMP NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default admin user (password: admin123)
INSERT INTO `admin_users` (`username`, `password_hash`, `full_name`, `email`, `role`) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@smilebright.com', 'admin')
ON DUPLICATE KEY UPDATE `username` = `username`;

-- Function to generate reference ID
DELIMITER //
CREATE FUNCTION IF NOT EXISTS generate_reference_id() RETURNS VARCHAR(20)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE ref_id VARCHAR(20);
    DECLARE done INT DEFAULT FALSE;
    
    REPEAT
        SET ref_id = CONCAT('SB', LPAD(FLOOR(RAND() * 999999), 6, '0'));
        
        SELECT COUNT(*) INTO done FROM `bookings` WHERE `reference_id` = ref_id;
    UNTIL done = 0 END REPEAT;
    
    RETURN ref_id;
END//
DELIMITER ;

-- Function to generate reschedule token
DELIMITER //
CREATE FUNCTION IF NOT EXISTS generate_reschedule_token() RETURNS VARCHAR(64)
READS SQL DATA
DETERMINISTIC
BEGIN
    RETURN SHA2(CONCAT(NOW(), RAND(), UUID()), 256);
END//
DELIMITER ;
