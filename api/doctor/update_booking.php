<?php
/**
 * Doctor Booking Update Handler - Base Version Compliant
 * Processes booking updates from doctor dashboard
 * Sends email notifications to patient and clinic
 */

session_start();
require_once __DIR__ . '/../config.php';

// Check if doctor is logged in
if (!isset($_SESSION['doctor_name'])) {
  header('Location: /SmileBrightbase/public/booking/doctor_login.php?error=not_logged_in');
  exit();
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: /SmileBrightbase/public/booking/doctor_dashboard.php?error=invalid_method');
  exit();
}

ini_set('display_errors', '0');
error_reporting(E_ALL);

function h($str) {
  return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

try {
  // Get form data
  $referenceId = trim($_POST['reference_id'] ?? '');
  $newDoctorName = trim($_POST['doctor_name'] ?? '');
  $newDate = trim($_POST['date'] ?? '');
  $newTime = trim($_POST['time'] ?? '');
  $newNotes = trim($_POST['notes'] ?? '');
  
  // Validate
  if (empty($referenceId)) {
    throw new Exception('Reference ID is required');
  }
  if (empty($newDoctorName)) {
    throw new Exception('Doctor name is required');
  }
  if (empty($newDate)) {
    throw new Exception('Date is required');
  }
  if (empty($newTime)) {
    throw new Exception('Time is required');
  }
  
  // Validate date format
  $dateObj = DateTime::createFromFormat('Y-m-d', $newDate);
  if (!$dateObj || $dateObj->format('Y-m-d') !== $newDate) {
    throw new Exception('Invalid date format');
  }
  
  // Validate time format (HH:MM)
  $timeObj = DateTime::createFromFormat('H:i', $newTime);
  if (!$timeObj || $timeObj->format('H:i') !== $newTime) {
    throw new Exception('Invalid time format');
  }
  
  // Get existing booking
  $stmt = $mysqli->prepare("SELECT * FROM bookings WHERE reference_id = ?");
  $stmt->bind_param('s', $referenceId);
  $stmt->execute();
  $existing = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  
  if (!$existing) {
    throw new Exception('Booking not found');
  }
  
  // Check if doctor name changed, get new clinic location
  $newLocationName = $existing['location_name'];
  if ($newDoctorName !== $existing['doctor_name']) {
    // Look up new doctor's clinic
    $stmt = $mysqli->prepare("SELECT c.name as clinic_name 
                             FROM doctors d 
                             JOIN clinics c ON d.clinic_slug = c.slug 
                             WHERE d.name = ?");
    $stmt->bind_param('s', $newDoctorName);
    $stmt->execute();
    $result = $stmt->get_result();
    $doctorInfo = $result->fetch_assoc();
    $stmt->close();
    
    if (!$doctorInfo) {
      throw new Exception('Selected doctor not found in database');
    }
    $newLocationName = $doctorInfo['clinic_name'];
  }
  
  // Check for duplicate slot if date/time changed
  if ($newDate !== $existing['date'] || $newTime !== substr($existing['time'], 0, 5) || $newDoctorName !== $existing['doctor_name']) {
    $checkStmt = $mysqli->prepare("SELECT reference_id FROM bookings WHERE doctor_name = ? AND date = ? AND time = ? AND reference_id != ?");
    $checkStmt->bind_param('ssss', $newDoctorName, $newDate, $newTime, $referenceId);
    $checkStmt->execute();
    $conflict = $checkStmt->get_result()->fetch_assoc();
    $checkStmt->close();
    
    if ($conflict) {
      throw new Exception('This time slot is already booked for ' . $newDoctorName . '. Please select another date or time.');
    }
  }
  
  // Store old values for email notification
  $oldData = [
    'doctor_name' => $existing['doctor_name'],
    'location_name' => $existing['location_name'],
    'date' => $existing['date'],
    'time' => $existing['time'],
    'notes' => $existing['notes'] ?? ''
  ];
  
  // Update booking
  $stmt = $mysqli->prepare("UPDATE bookings SET 
                            doctor_name = ?, 
                            location_name = ?,
                            date = ?, 
                            time = ?, 
                            notes = ?,
                            updated_at = CURRENT_TIMESTAMP
                            WHERE reference_id = ?");
  $stmt->bind_param('ssssss', $newDoctorName, $newLocationName, $newDate, $newTime, $newNotes, $referenceId);
  
  if (!$stmt->execute()) {
    throw new Exception('Failed to update booking: ' . $mysqli->error);
  }
  $stmt->close();
  
  // Get updated booking data
  $stmt = $mysqli->prepare("SELECT * FROM bookings WHERE reference_id = ?");
  $stmt->bind_param('s', $referenceId);
  $stmt->execute();
  $updatedBooking = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  
  // Service name mapping
  $serviceNames = [
    'general' => 'General Checkup',
    'cleaning' => 'Teeth Cleaning',
    'filling' => 'Dental Filling',
    'extraction' => 'Tooth Extraction',
    'braces' => 'Braces Consultation',
    'whitening' => 'Teeth Whitening',
    'implant' => 'Dental Implant',
    'others' => 'Others'
  ];
  
  // Prepare booking data for email
  $bookingData = [
    'email' => $updatedBooking['email'],
    'full_name' => $updatedBooking['first_name'] . ' ' . $updatedBooking['last_name'],
    'first_name' => $updatedBooking['first_name'],
    'last_name' => $updatedBooking['last_name'],
    'phone' => $updatedBooking['phone'],
    'preferred_date' => $updatedBooking['date'],
    'preferred_time' => $updatedBooking['time'],
    'preferred_clinic' => $updatedBooking['location_name'], // Required for email template
    'doctor_name' => $updatedBooking['doctor_name'],
    'clinic_name' => $updatedBooking['location_name'],
    'service' => $serviceNames[$updatedBooking['service_key']] ?? ucfirst($updatedBooking['service_key']),
    'reference_id' => $referenceId,
    'notes' => $updatedBooking['notes'] ?: 'None',
    'id' => $referenceId,
    'reschedule_token' => bin2hex(random_bytes(16)) // Required for email template
  ];
  
  // Send email notifications (to patient and clinic)
  // Load email config FIRST (before email service, which also requires it)
  require_once __DIR__ . '/../../src/config/email.php';
  
  try {
    require_once __DIR__ . '/../../src/services/native_email_service.php';
    
    $emailService = new SmileBrightEmailService();
    
    // Check what changed
    $changes = [];
    if ($oldData['doctor_name'] !== $updatedBooking['doctor_name']) {
      $changes[] = 'Doctor: ' . $oldData['doctor_name'] . ' → ' . $updatedBooking['doctor_name'];
    }
    if ($oldData['location_name'] !== $updatedBooking['location_name']) {
      $changes[] = 'Clinic: ' . $oldData['location_name'] . ' → ' . $updatedBooking['location_name'];
    }
    if ($oldData['date'] !== $updatedBooking['date']) {
      $changes[] = 'Date: ' . date('M j, Y', strtotime($oldData['date'])) . ' → ' . date('M j, Y', strtotime($updatedBooking['date']));
    }
    if (substr($oldData['time'], 0, 5) !== substr($updatedBooking['time'], 0, 5)) {
      $changes[] = 'Time: ' . date('g:i A', strtotime($oldData['time'])) . ' → ' . date('g:i A', strtotime($updatedBooking['time']));
    }
    
    // 1. Send update notification to PATIENT
    $patientResult = $emailService->sendBookingConfirmation($bookingData);
    if ($patientResult['success']) {
      error_log('✅ Patient email sent to: ' . $updatedBooking['email']);
    } else {
      error_log('❌ Patient email failed: ' . $patientResult['message']);
    }
    
    // 2. Send separate notification directly to CLINIC
    $clinicEmail = defined('EMAIL_BCC_ADMIN') && !empty(EMAIL_BCC_ADMIN) ? EMAIL_BCC_ADMIN : 'smilebrightsg.info@gmail.com';
    
    $changeSummary = !empty($changes) ? implode("\n", $changes) : 'No changes specified';
    $patientName = $updatedBooking['first_name'] . ' ' . $updatedBooking['last_name'];
    $serviceName = $serviceNames[$updatedBooking['service_key']] ?? ucfirst($updatedBooking['service_key']);
    $doctorName = $updatedBooking['doctor_name'];
    $clinicName = $updatedBooking['location_name'];
    $appointmentDate = date('M j, Y', strtotime($updatedBooking['date']));
    $appointmentTime = date('g:i A', strtotime($updatedBooking['time']));
    
    $clinicSubject = "Booking Updated - Ref {$referenceId} - {$patientName}";
    $clinicHtml = "
      <!DOCTYPE html>
      <html>
      <head>
        <meta charset='UTF-8'>
        <style>
          body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
          .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f5f7fb; }
          .card { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
          .header { background: #1f4f86; color: white; padding: 20px; border-radius: 8px 8px 0 0; margin: -20px -20px 20px -20px; }
          .detail { margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 4px; }
          .changes { background: #fff7ed; border-left: 4px solid #f59e0b; padding: 15px; margin: 15px 0; }
        </style>
      </head>
      <body>
        <div class='container'>
          <div class='card'>
            <div class='header'>
              <h1 style='margin:0;'>Booking Updated</h1>
              <p style='margin:5px 0 0 0;'>Reference: {$referenceId}</p>
            </div>
            
            <h2>Booking Details</h2>
            <div class='detail'><strong>Patient:</strong> {$patientName}</div>
            <div class='detail'><strong>Email:</strong> {$updatedBooking['email']}</div>
            <div class='detail'><strong>Phone:</strong> {$updatedBooking['phone']}</div>
            <div class='detail'><strong>Doctor:</strong> {$doctorName}</div>
            <div class='detail'><strong>Clinic:</strong> {$clinicName}</div>
            <div class='detail'><strong>Date:</strong> {$appointmentDate}</div>
            <div class='detail'><strong>Time:</strong> {$appointmentTime}</div>
            <div class='detail'><strong>Service:</strong> {$serviceName}</div>
            
            <div class='changes'>
              <h3>Changes Made:</h3>
              " . (!empty($changes) ? "<ul><li>" . implode("</li><li>", array_map('htmlspecialchars', $changes)) . "</li></ul>" : "<p>No specific changes logged</p>") . "
            </div>
            
            <p style='margin-top:20px; color:#6b7a90; font-size:14px;'>
              This booking was updated by: {$_SESSION['doctor_name']}<br>
              Updated at: " . date('M j, Y g:i A') . "
            </p>
          </div>
        </div>
      </body>
      </html>";
    $clinicText = "Booking Updated - Ref {$referenceId}\n\nPatient: {$patientName}\nEmail: {$updatedBooking['email']}\nPhone: {$updatedBooking['phone']}\nDoctor: {$doctorName}\nClinic: {$clinicName}\nDate: {$appointmentDate}\nTime: {$appointmentTime}\n\nChanges:\n{$changeSummary}\n\nUpdated by: {$_SESSION['doctor_name']} at " . date('M j, Y g:i A');
    
    $clinicResult = $emailService->sendClinicNotification($clinicSubject, $clinicHtml, $clinicText, $clinicEmail);
    if ($clinicResult['success']) {
      error_log('✅ Clinic email sent to: ' . $clinicEmail);
    } else {
      error_log('❌ Clinic email failed: ' . $clinicResult['message']);
    }
    
  } catch (Exception $e) {
    // Log error but don't fail the update
    error_log('❌ Email notification failed for booking ' . $referenceId . ': ' . $e->getMessage());
    error_log('   Exception trace: ' . $e->getTraceAsString());
  }
  
  // Redirect back to dashboard with success message
  header('Location: /SmileBrightbase/public/booking/doctor_dashboard.php?updated=1&ref=' . urlencode($referenceId));
  exit();
  
} catch (Exception $e) {
  // Redirect back to edit page with error
  $errorMsg = urlencode($e->getMessage());
  header('Location: /SmileBrightbase/public/booking/doctor_edit_booking.php?ref=' . urlencode($referenceId ?? '') . '&error=' . $errorMsg);
  exit();
}

