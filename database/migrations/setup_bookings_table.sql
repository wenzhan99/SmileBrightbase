-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `smilebright` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- Use the database
USE `smilebright`;

-- Create bookings table
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `preferred_clinic` VARCHAR(100) NOT NULL,
  `service` VARCHAR(120) NOT NULL,
  `preferred_date` DATE NOT NULL,
  `preferred_time` TIME NOT NULL,
  `message` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Test insert (optional - remove this line if you don't want test data)
INSERT INTO `bookings` (full_name,email,phone,preferred_clinic,service,preferred_date,preferred_time,message) VALUES ('Test User','test@example.com','91234567','Thomson','Cleaning','2025-10-31','10:30:00','Hello from phpMyAdmin!');
