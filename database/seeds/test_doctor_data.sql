-- Test Data Setup for Doctor Dashboard Testing
-- This script sets up sample bookings for testing the doctor dashboard functionality

USE `smilebright`;

-- Insert sample bookings for Dr. Chua Wen Zhan (dr-chua)
INSERT INTO `bookings` (
  `reference_id`, `dentist_id`, `dentist_name`, `clinic_id`, `clinic_name`,
  `service_code`, `service_label`, `experience_code`, `experience_label`,
  `preferred_date`, `preferred_time`, `first_name`, `last_name`, `email`, `phone`, `notes`,
  `agree_policy`, `agree_terms`, `status`
) VALUES 
-- Today's appointments
('SB-20250125-0001', 'dr-chua', 'Dr. Chua Wen Zhan', 'orchard', 'Orchard Clinic',
 'general_checkup', 'General Checkup', 'first_time', 'First time patient',
 '2025-01-25', '09:00:00', 'Alice', 'Johnson', 'alice.johnson@example.com', '+65 9123 4567', 'Regular checkup',
 1, 1, 'scheduled'),

('SB-20250125-0002', 'dr-chua', 'Dr. Chua Wen Zhan', 'orchard', 'Orchard Clinic',
 'cleaning', 'Teeth Cleaning', 'returning', 'Returning patient',
 '2025-01-25', '10:30:00', 'Bob', 'Smith', 'bob.smith@example.com', '+65 9234 5678', 'Quarterly cleaning',
 1, 1, 'scheduled'),

('SB-20250125-0003', 'dr-chua', 'Dr. Chua Wen Zhan', 'orchard', 'Orchard Clinic',
 'filling', 'Dental Filling', 'returning', 'Returning patient',
 '2025-01-25', '14:00:00', 'Carol', 'Davis', 'carol.davis@example.com', '+65 9345 6789', 'Cavity filling',
 1, 1, 'scheduled'),

-- Tomorrow's appointments
('SB-20250126-0001', 'dr-chua', 'Dr. Chua Wen Zhan', 'orchard', 'Orchard Clinic',
 'consultation', 'Consultation', 'first_time', 'First time patient',
 '2025-01-26', '09:30:00', 'David', 'Wilson', 'david.wilson@example.com', '+65 9456 7890', 'Initial consultation',
 1, 1, 'scheduled'),

('SB-20250126-0002', 'dr-chua', 'Dr. Chua Wen Zhan', 'orchard', 'Orchard Clinic',
 'extraction', 'Tooth Extraction', 'returning', 'Returning patient',
 '2025-01-26', '11:00:00', 'Emma', 'Brown', 'emma.brown@example.com', '+65 9567 8901', 'Wisdom tooth extraction',
 1, 1, 'scheduled'),

-- Next week's appointments
('SB-20250130-0001', 'dr-chua', 'Dr. Chua Wen Zhan', 'orchard', 'Orchard Clinic',
 'cleaning', 'Teeth Cleaning', 'returning', 'Returning patient',
 '2025-01-30', '10:00:00', 'Frank', 'Miller', 'frank.miller@example.com', '+65 9678 9012', 'Monthly cleaning',
 1, 1, 'scheduled'),

-- Completed appointments
('SB-20250120-0001', 'dr-chua', 'Dr. Chua Wen Zhan', 'orchard', 'Orchard Clinic',
 'checkup', 'Follow-up Checkup', 'returning', 'Returning patient',
 '2025-01-20', '15:30:00', 'Grace', 'Taylor', 'grace.taylor@example.com', '+65 9789 0123', 'Post-treatment checkup',
 1, 1, 'completed'),

-- Cancelled appointments
('SB-20250122-0001', 'dr-chua', 'Dr. Chua Wen Zhan', 'orchard', 'Orchard Clinic',
 'consultation', 'Consultation', 'first_time', 'First time patient',
 '2025-01-22', '16:00:00', 'Henry', 'Anderson', 'henry.anderson@example.com', '+65 9890 1234', 'Patient cancelled',
 1, 1, 'cancelled');

-- Insert some bookings for other doctors to test filtering
INSERT INTO `bookings` (
  `reference_id`, `dentist_id`, `dentist_name`, `clinic_id`, `clinic_name`,
  `service_code`, `service_label`, `experience_code`, `experience_label`,
  `preferred_date`, `preferred_time`, `first_name`, `last_name`, `email`, `phone`, `notes`,
  `agree_policy`, `agree_terms`, `status`
) VALUES 
('SB-20250125-0004', 'dr-lau', 'Dr. Lau Gwen', 'marina_bay', 'Marina Bay Clinic',
 'cleaning', 'Teeth Cleaning', 'returning', 'Returning patient',
 '2025-01-25', '11:00:00', 'Iris', 'Thomas', 'iris.thomas@example.com', '+65 9901 2345', 'Regular cleaning',
 1, 1, 'scheduled'),

('SB-20250125-0005', 'dr-sarah', 'Dr. Sarah Tan', 'bukit_timah', 'Bukit Timah Clinic',
 'consultation', 'Consultation', 'first_time', 'First time patient',
 '2025-01-25', '14:30:00', 'Jack', 'Jackson', 'jack.jackson@example.com', '+65 9012 3456', 'New patient consultation',
 1, 1, 'scheduled');

-- Show the inserted data
SELECT 
  reference_id,
  dentist_name,
  clinic_name,
  preferred_date,
  preferred_time,
  CONCAT(first_name, ' ', last_name) as patient_name,
  service_label,
  status
FROM bookings 
WHERE dentist_id = 'dr-chua'
ORDER BY preferred_date, preferred_time;
