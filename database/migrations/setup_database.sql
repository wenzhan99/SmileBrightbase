-- SmileBright Dental Database Setup
-- Run this script in phpMyAdmin or MySQL command line

-- Create the database
CREATE DATABASE IF NOT EXISTS smilebright CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Use the database
USE smilebright;

-- Create the appointments table
CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    clinic VARCHAR(100) NOT NULL,
    service VARCHAR(100) NOT NULL,
    experience TEXT NOT NULL,
    message TEXT,
    consent TINYINT(1) NOT NULL DEFAULT 0,
    reschedule_token VARCHAR(64),
    token_expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Add indexes for better performance
    INDEX idx_email (email),
    INDEX idx_date (date),
    INDEX idx_clinic (clinic),
    INDEX idx_created_at (created_at),
    INDEX idx_reschedule_token (reschedule_token)
);

-- Insert some sample data (optional)
INSERT INTO appointments (first_name, last_name, email, phone, date, time, clinic, service, experience, message, consent) VALUES
('John', 'Doe', 'john.doe@example.com', '12345678', '2024-02-15', '10:30:00', 'Novena', 'Scaling & Polishing', 'Regular dental checkups every 6 months', 'Please confirm appointment', 1),
('Jane', 'Smith', 'jane.smith@example.com', '87654321', '2024-02-16', '14:00:00', 'Tampines', 'Dental Filling', 'First time visiting this clinic', 'Need urgent appointment', 1);

-- Show the created table structure
DESCRIBE appointments;

