<?php
// Database setup for new schema
echo "<h1>Setting up SmileBright Database</h1>";

try {
    // Connect to MySQL without specifying database
    $mysqli = new mysqli('127.0.0.1', 'root', '', '', 3306);
    
    if ($mysqli->connect_errno) {
        throw new Exception('MySQL connection failed: ' . $mysqli->connect_error);
    }
    
    echo "<p>✅ MySQL connection successful!</p>";
    
    // Create database
    $sql = "CREATE DATABASE IF NOT EXISTS smilebright CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    if ($mysqli->query($sql)) {
        echo "<p>✅ Database 'smilebright' created/verified!</p>";
    } else {
        throw new Exception('Failed to create database: ' . $mysqli->error);
    }
    
    // Select database
    $mysqli->select_db('smilebright');
    
    // Drop existing table
    $mysqli->query("DROP TABLE IF EXISTS bookings");
    echo "<p>✅ Old bookings table dropped!</p>";
    
    // Create new bookings table
    $sql = "CREATE TABLE `bookings` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    if ($mysqli->query($sql)) {
        echo "<p>✅ New bookings table created!</p>";
    } else {
        throw new Exception('Failed to create bookings table: ' . $mysqli->error);
    }
    
    // Insert test data
    $testRef = 'SB-' . date('Ymd') . '-TEST';
    $sql = "INSERT INTO bookings (
        reference_id, dentist_id, dentist_name, clinic_id, clinic_name,
        service_code, service_label, experience_code, experience_label,
        preferred_date, preferred_time, first_name, last_name, email, phone, notes,
        agree_policy, agree_terms, status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $mysqli->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('sssssssssssssssssss',
            $testRef,
            'dr-aisha-rahman',
            'Dr. Aisha Rahman',
            'tampines',
            'Tampines Clinic',
            'general_checkup',
            'General Checkup',
            'first_time',
            'First time patient',
            '2025-01-15',
            '14:00:00',
            'Test',
            'User',
            'test@example.com',
            '+65 1234 5678',
            'Test booking',
            1,
            1,
            'scheduled'
        );
        
        if ($stmt->execute()) {
            echo "<p>✅ Test booking inserted! ID: " . $mysqli->insert_id . "</p>";
        } else {
            echo "<p>⚠️ Test insert failed: " . $stmt->error . "</p>";
        }
        $stmt->close();
    }
    
    echo "<h2>✅ Database setup completed!</h2>";
    echo "<p><a href='public/booking/booking_form.html?dentist=dr-aisha&dentistName=Dr. Aisha Rahman&date=2025-10-30&time=14:00'>Test Booking Form</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
