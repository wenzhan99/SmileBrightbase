<?php
/**
 * Update Booking Status - Base Version Compliant
 * Handles status updates from doctor dashboard
 */

session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../src/config/email.php';
require_once __DIR__ . '/../../src/services/native_email_service.php';

// Check if doctor is logged in
if (!isset($_SESSION['doctor_name'])) {
  header('Location: /SmileBrightbase/public/booking/doctor_login.php?error=not_logged_in');
  exit();
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: /SmileBrightbase/public/booking/doctor_dashboard.php?error=invalid_method');
  exit();
}

function h($str) {
  return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Get form data
$referenceId = trim($_POST['reference_id'] ?? '');
$newStatus = trim($_POST['status'] ?? '');

// Validate inputs
$allowedStatuses = ['confirmed', 'rescheduled', 'cancelled', 'completed', 'no-show'];
if (empty($referenceId) || empty($newStatus)) {
  header('Location: /SmileBrightbase/public/booking/doctor_dashboard.php?error=missing_fields');
  exit();
}

if (!in_array(strtolower($newStatus), $allowedStatuses, true)) {
  header('Location: /SmileBrightbase/public/booking/doctor_dashboard.php?error=invalid_status&status=' . urlencode($newStatus));
  exit();
}

try {
  // Fetch existing booking
  $stmt = $mysqli->prepare("SELECT * FROM bookings WHERE reference_id = ?");
  if (!$stmt) {
    throw new Exception('Database prepare failed: ' . $mysqli->error);
  }
  
  $stmt->bind_param('s', $referenceId);
  $stmt->execute();
  $existing = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  
  if (!$existing) {
    throw new Exception('Booking not found.');
  }
  
  // Store old status for email comparison
  $oldStatus = $existing['status'];
  
  // Update status
  $stmt = $mysqli->prepare("UPDATE bookings SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE reference_id = ?");
  if (!$stmt) {
    throw new Exception('Database prepare failed: ' . $mysqli->error);
  }
  
  $stmt->bind_param('ss', $newStatus, $referenceId);
  
  if (!$stmt->execute()) {
    throw new Exception('Failed to update status: ' . $mysqli->error);
  }
  $stmt->close();
  
  // Get updated booking data
  $stmt = $mysqli->prepare("SELECT * FROM bookings WHERE reference_id = ?");
  $stmt->bind_param('s', $referenceId);
  $stmt->execute();
  $updated = $stmt->get_result()->fetch_assoc();
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
  
  // Send email notifications if status changed
  if ($oldStatus !== $newStatus) {
    try {
      // Prepare booking data for email
      $bookingData = [
        'email' => $updated['email'],
        'full_name' => $updated['first_name'] . ' ' . $updated['last_name'],
        'first_name' => $updated['first_name'],
        'last_name' => $updated['last_name'],
        'phone' => $updated['phone'],
        'preferred_date' => $updated['date'],
        'preferred_time' => $updated['time'],
        'preferred_clinic' => $updated['location_name'],
        'doctor_name' => $updated['doctor_name'],
        'clinic_name' => $updated['location_name'],
        'service' => $serviceNames[$updated['service_key']] ?? ucfirst($updated['service_key']),
        'reference_id' => $referenceId,
        'notes' => $updated['notes'] ?: 'None',
        'id' => $referenceId,
        'reschedule_token' => bin2hex(random_bytes(16))
      ];
      
      // 1. Send notification to PATIENT using native email service
      $emailService = new SmileBrightEmailService();
      $bookingData['service_key'] = $updated['service_key'];
      
      $patientResult = $emailService->sendStatusUpdateNotification($bookingData, $oldStatus, $newStatus);
      if ($patientResult['success']) {
        error_log('✅ Patient status email sent to: ' . $updated['email']);
      } else {
        error_log('❌ Patient status email failed: ' . $patientResult['message']);
      }
      
      // 2. Send notification to CLINIC
      $clinicEmail = defined('EMAIL_BCC_ADMIN') && !empty(EMAIL_BCC_ADMIN) ? EMAIL_BCC_ADMIN : 'smilebrightsg.info@gmail.com';
      
      $appointmentDate = date('M j, Y', strtotime($updated['date']));
      $appointmentTime = date('g:i A', strtotime($updated['time']));
      
      $clinicSubject = "Status Updated - Ref {$referenceId} - {$updated['first_name']} {$updated['last_name']}";
      $clinicHtml = "<!DOCTYPE html>
<html>
<head>
  <meta charset='UTF-8'>
  <style>
    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
    .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f5f7fb; }
    .card { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .header { background: #1f4f86; color: white; padding: 20px; border-radius: 8px 8px 0 0; margin: -20px -20px 20px -20px; }
    .detail { margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 4px; }
    .status-change { background: #fff7ed; border-left: 4px solid #f59e0b; padding: 15px; margin: 15px 0; }
  </style>
</head>
<body>
  <div class='container'>
    <div class='card'>
      <div class='header'>
        <h1 style='margin:0;'>Status Updated</h1>
        <p style='margin:5px 0 0 0;'>Reference: {$referenceId}</p>
      </div>
      
      <h2>Booking Details</h2>
      <div class='detail'><strong>Patient:</strong> {$bookingData['full_name']}</div>
      <div class='detail'><strong>Email:</strong> {$updated['email']}</div>
      <div class='detail'><strong>Phone:</strong> {$updated['phone']}</div>
      <div class='detail'><strong>Doctor:</strong> {$updated['doctor_name']}</div>
      <div class='detail'><strong>Clinic:</strong> {$updated['location_name']}</div>
      <div class='detail'><strong>Date:</strong> {$appointmentDate}</div>
      <div class='detail'><strong>Time:</strong> {$appointmentTime}</div>
      
      <div class='status-change'>
        <h3>Status Change:</h3>
        <p><strong>{$oldStatus}</strong> → <strong>{$newStatus}</strong></p>
        <p>Updated by: {$_SESSION['doctor_name']}</p>
        <p>Updated at: " . date('M j, Y g:i A') . "</p>
      </div>
    </div>
  </div>
</body>
</html>";
      $clinicText = "Status Updated - Ref {$referenceId}\n\nPatient: {$bookingData['full_name']}\nEmail: {$updated['email']}\nPhone: {$updated['phone']}\nDoctor: {$updated['doctor_name']}\nClinic: {$updated['location_name']}\nDate: {$appointmentDate}\nTime: {$appointmentTime}\n\nStatus Change: {$oldStatus} → {$newStatus}\nUpdated by: {$_SESSION['doctor_name']} at " . date('M j, Y g:i A');
      
      $clinicResult = $emailService->sendClinicNotification($clinicSubject, $clinicHtml, $clinicText, $clinicEmail);
      if ($clinicResult['success']) {
        error_log('✅ Clinic status email sent to: ' . $clinicEmail);
      } else {
        error_log('❌ Clinic status email failed: ' . $clinicResult['message']);
      }
      
    } catch (Exception $e) {
      error_log('❌ Email notification failed for status update ' . $referenceId . ': ' . $e->getMessage());
    }
  }
  
  // Redirect back to dashboard with success message
  header('Location: /SmileBrightbase/public/booking/doctor_dashboard.php?status_updated=1&ref=' . urlencode($referenceId) . '&old_status=' . urlencode($oldStatus) . '&new_status=' . urlencode($newStatus));
  exit();
  
} catch (Exception $e) {
  error_log('Status update error: ' . $e->getMessage());
  header('Location: /SmileBrightbase/public/booking/doctor_dashboard.php?error=' . urlencode($e->getMessage()));
  exit();
}

