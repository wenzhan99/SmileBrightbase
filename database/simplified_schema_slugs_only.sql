-- Simplified Schema: Store ONLY slugs (no denormalized names)
-- WARNING: This will require JOINs when displaying booking information
-- Trade-off: Simpler schema vs. more complex queries

USE smilebrightbase;

-- Drop and recreate bookings table with only slugs
DROP TABLE IF EXISTS bookings;

CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference_id VARCHAR(20) NOT NULL UNIQUE,
    doctor_slug VARCHAR(50) NOT NULL,
    location_slug VARCHAR(50) NOT NULL,
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
    -- Foreign keys reference tables in the same database
    CONSTRAINT fk_b_service FOREIGN KEY (service_key) REFERENCES services(service_key),
    CONSTRAINT fk_b_doctor FOREIGN KEY (doctor_slug) REFERENCES doctors(slug),
    CONSTRAINT fk_b_location FOREIGN KEY (location_slug) REFERENCES clinics(slug)
) ENGINE=InnoDB;

-- Example query to get booking with names (requires JOIN):
-- SELECT b.*, d.name as doctor_name, c.name as location_name
-- FROM bookings b
-- JOIN doctors d ON b.doctor_slug = d.slug
-- JOIN clinics c ON b.location_slug = c.slug
-- WHERE b.reference_id = ?

