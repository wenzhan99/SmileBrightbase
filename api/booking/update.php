<?php
/**
 * SmileBright Booking API - Update Booking Endpoint (Base Version Compliant)
 * /api/booking/update.php
 * 
 * Base Version: Accepts POST form data, no JSON/AJAX
 * Returns HTML redirects or error pages
 */

require_once __DIR__ . '/../config.php';

ini_set('display_errors', '0');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Method Not Allowed</title></head><body><h1>405 - Method Not Allowed</h1></body></html>';
  exit();
}

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

// Collect POST inputs (Base Version - form data, not JSON)
$referenceId = trim($_POST['reference_id'] ?? '');
$dateIso     = isset($_POST['date_iso']) ? trim($_POST['date_iso']) : null;
$time24      = isset($_POST['time_24']) ? trim($_POST['time_24']) : null;
$notes       = isset($_POST['notes']) ? trim($_POST['notes']) : null;
$status      = isset($_POST['status']) ? trim($_POST['status']) : null;

$errors = [];
if ($referenceId === '') $errors[] = 'Reference ID is required';
if ($dateIso !== null && $dateIso !== '') {
  $d = DateTime::createFromFormat('Y-m-d', $dateIso);
  if (!$d || $d->format('Y-m-d') !== $dateIso) $errors[] = 'Invalid date format (Y-m-d)';
  // Check if date is in the future
  if ($d && $d < new DateTime()) $errors[] = 'Appointment date must be in the future';
}
if ($time24 !== null && $time24 !== '') {
  $t = DateTime::createFromFormat('H:i', $time24);
  if (!$t || $t->format('H:i') !== $time24) $errors[] = 'Invalid time format (HH:MM)';
}
if ($status !== null && $status !== '') {
  $allowed = ['scheduled','confirmed','cancelled','completed','rescheduled'];
  if (!in_array(strtolower($status), $allowed, true)) $errors[] = 'Invalid status value';
}

if ($errors) {
  http_response_code(400);
  echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1" />'
     . '<title>Update Error</title>'
     . '<style>body{font:16px/1.55 Segoe UI,Arial;background:#f5f7fb;color:#243042;margin:0}.wrap{max-width:880px;margin:32px auto;padding:0 16px}.card{background:#fff;border:1px solid #e5e9f3;border-radius:12px;box-shadow:0 6px 20px rgba(22,39,76,.06);padding:22px}</style>'
     . '</head><body><div class="wrap"><h1>There were problems with your update</h1><div class="card"><ul>';
  foreach ($errors as $e) echo '<li>'.h($e).'</li>';
  echo '</ul><p><a href="/SmileBrightbase/public/booking/manage_booking.html?ref='.h($referenceId).'">Go back</a></p></div></div></body></html>';
  exit();
}

// Check database connection
if ($mysqli->connect_errno) {
  http_response_code(500);
  echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Database Error</title></head><body><h1>Database connection failed</h1></body></html>';
  exit();
}

// Fetch existing booking
$stmt = $mysqli->prepare('SELECT * FROM bookings WHERE reference_id = ?');
if (!$stmt) {
  http_response_code(500);
  echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Database Error</title></head><body><h1>Database prepare failed</h1></body></html>';
  exit();
}

$stmt->bind_param('s', $referenceId);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$existing) {
  http_response_code(404);
  echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Not Found</title></head><body><h1>Booking not found</h1><p><a href="/SmileBrightbase/public/booking/manage_booking.html">Go back</a></p></body></html>';
  exit();
}

// Build update fields
$fields = [];
$values = [];
$types = '';

if ($dateIso !== null && $dateIso !== '') { 
  $fields[] = 'date = ?'; 
  $values[] = $dateIso; 
  $types .= 's'; 
}
if ($time24 !== null && $time24 !== '') { 
  $fields[] = 'time = ?'; 
  $values[] = $time24; 
  $types .= 's'; 
}
if ($notes !== null) { 
  $fields[] = 'notes = ?'; 
  $values[] = $notes; 
  $types .= 's'; 
}
if ($status !== null && $status !== '') { 
  $fields[] = 'status = ?'; 
  $values[] = strtolower($status); 
  $types .= 's'; 
}

if (!$fields) {
  http_response_code(400);
  echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>No Changes</title></head><body><h1>No changes provided</h1><p><a href="/SmileBrightbase/public/booking/manage_booking.html?ref='.h($referenceId).'">Back</a></p></body></html>';
  exit();
}

// Check for booking conflicts if date/time changed
if (($dateIso || $time24) && isset($existing['doctor_name'])) {
  $checkDate = $dateIso ?? $existing['date'];
  $checkTime = $time24 ?? $existing['time'];
  $checkDoctor = $existing['doctor_name'];
  
  $stmt = $mysqli->prepare("SELECT reference_id FROM bookings WHERE doctor_name = ? AND date = ? AND time = ? AND reference_id != ?");
  if ($stmt) {
    $stmt->bind_param('ssss', $checkDoctor, $checkDate, $checkTime, $referenceId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
      $stmt->close();
      http_response_code(409);
      echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Conflict</title></head><body><h1>Time slot is already booked for this dentist</h1><p><a href="/SmileBrightbase/public/booking/manage_booking.html?ref='.h($referenceId).'">Go back</a></p></body></html>';
      exit();
    }
    $stmt->close();
  }
}

$fields[] = 'updated_at = CURRENT_TIMESTAMP';
$sql = 'UPDATE bookings SET '.implode(', ', $fields).' WHERE reference_id = ?';
$values[] = $referenceId; 
$types .= 's';

$stmt = $mysqli->prepare($sql);
if (!$stmt) {
  http_response_code(500);
  echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Database Error</title></head><body><h1>Failed to prepare update</h1></body></html>';
  exit();
}

$stmt->bind_param($types, ...$values);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
  http_response_code(500);
  echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Update Failed</title></head><body><h1>Failed to update booking</h1><p><a href="/SmileBrightbase/public/booking/manage_booking.html?ref='.h($referenceId).'">Go back</a></p></body></html>';
  exit();
}

// Try to send email notification (fail silently if email fails)
try {
  require_once __DIR__ . '/../../src/config/email.php';
  require_once __DIR__ . '/../../src/services/native_email_service.php';
  
  // Get updated booking
  $stmt = $mysqli->prepare("SELECT * FROM bookings WHERE reference_id = ?");
  $stmt->bind_param('s', $referenceId);
  $stmt->execute();
  $updated = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  
  if ($updated) {
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
    
    // Prepare booking data for email service
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
    
    $emailService = new SmileBrightEmailService();
    
    // 1. Send confirmation to PATIENT
    $patientResult = $emailService->sendBookingConfirmation($bookingData);
    if ($patientResult['success']) {
      error_log('✅ Patient email sent to: ' . $updated['email']);
    } else {
      error_log('❌ Patient email failed: ' . $patientResult['message']);
    }
    
    // 2. Send notification to CLINIC
    $clinicEmail = defined('EMAIL_BCC_ADMIN') && !empty(EMAIL_BCC_ADMIN) ? EMAIL_BCC_ADMIN : 'smilebrightsg.info@gmail.com';
    
    $patientName = $updated['first_name'] . ' ' . $updated['last_name'];
    $serviceName = $serviceNames[$updated['service_key']] ?? ucfirst($updated['service_key']);
    $doctorName = $updated['doctor_name'];
    $clinicName = $updated['location_name'];
    $appointmentDate = date('M j, Y', strtotime($updated['date']));
    $appointmentTime = date('g:i A', strtotime($updated['time']));
    
    $clinicSubject = "Patient Updated Booking - Ref {$referenceId} - {$patientName}";
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
  </style>
</head>
<body>
  <div class='container'>
    <div class='card'>
      <div class='header'>
        <h1 style='margin:0;'>Patient Updated Booking</h1>
        <p style='margin:5px 0 0 0;'>Reference: {$referenceId}</p>
      </div>
      
      <h2>Updated Booking Details</h2>
      <div class='detail'><strong>Patient:</strong> {$patientName}</div>
      <div class='detail'><strong>Email:</strong> {$updated['email']}</div>
      <div class='detail'><strong>Phone:</strong> {$updated['phone']}</div>
      <div class='detail'><strong>Doctor:</strong> {$doctorName}</div>
      <div class='detail'><strong>Clinic:</strong> {$clinicName}</div>
      <div class='detail'><strong>Date:</strong> {$appointmentDate}</div>
      <div class='detail'><strong>Time:</strong> {$appointmentTime}</div>
      <div class='detail'><strong>Service:</strong> {$serviceName}</div>
      
      <p style='margin-top:20px; color:#6b7a90; font-size:14px;'>
        This booking was updated by the patient via manage_booking.php<br>
        Updated at: " . date('M j, Y g:i A') . "
      </p>
    </div>
  </div>
</body>
</html>";
    $clinicText = "Patient Updated Booking - Ref {$referenceId}\n\nPatient: {$patientName}\nEmail: {$updated['email']}\nPhone: {$updated['phone']}\nDoctor: {$doctorName}\nClinic: {$clinicName}\nDate: {$appointmentDate}\nTime: {$appointmentTime}\n\nUpdated by patient at " . date('M j, Y g:i A');
    
    $clinicResult = $emailService->sendClinicNotification($clinicSubject, $clinicHtml, $clinicText, $clinicEmail);
    if ($clinicResult['success']) {
      error_log('✅ Clinic email sent to: ' . $clinicEmail);
    } else {
      error_log('❌ Clinic email failed: ' . $clinicResult['message']);
    }
  }
} catch (Exception $e) {
  error_log('Email sending failed: ' . $e->getMessage());
  // Continue even if email fails
}

// Redirect to success page (Base Version - server-side redirect, no JSON)
header('Location: /SmileBrightbase/public/booking/manage_booking.html?ref=' . urlencode($referenceId) . '&updated=1');
exit();

?>
