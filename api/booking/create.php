<?php
/**
 * SmileBright Booking API - Create Booking Endpoint
 * Version: 2025-10-31-1 (No-AJAX Flow)
 * 
 * Handles POST form submission from book_appointmentbase.html
 * Generates reference ID, validates slot availability, inserts booking
 * Redirects to booking_success.php on success
 */

require_once __DIR__ . '/../config.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Method Not Allowed</title></head><body><h1>405 - Method Not Allowed</h1></body></html>';
  exit();
}

// Disable error display
ini_set('display_errors', '0');
error_reporting(E_ALL);

try {
  // Collect POST inputs
  $dentistName = trim($_POST['dentist'] ?? '');
  $date = trim($_POST['date'] ?? '');
  $time = trim($_POST['time'] ?? '');
  $firstName = trim($_POST['firstName'] ?? '');
  $lastName = trim($_POST['lastName'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $serviceName = trim($_POST['serviceRequired'] ?? 'General Checkup');
  $previousDentalExperience = trim($_POST['previousDentalExperience'] ?? 'first-time');
  $additionalNotes = trim($_POST['additionalNotes'] ?? '');
  $consentPrivacy = isset($_POST['consentPrivacy']);
  $consentTerms = isset($_POST['consentTerms']);

  // Handle "Others" service
  if ($serviceName === 'others' || $serviceName === 'Others') {
    $serviceOther = trim($_POST['serviceOther'] ?? '');
    if (empty($serviceOther)) {
      throw new Exception('Please specify the service when selecting "Others"');
    }
    // Use the custom service text
    $serviceName = $serviceOther;
  }

  // Validate required fields
  if (empty($dentistName)) throw new Exception('Please select a dentist');
  if (empty($date)) throw new Exception('Please select a date');
  if (empty($time)) throw new Exception('Please select a time slot');
  if (empty($firstName)) throw new Exception('First name is required');
  if (empty($lastName)) throw new Exception('Last name is required');
  if (empty($email)) throw new Exception('Email is required');
  if (empty($phone)) throw new Exception('Phone number is required');
  if (!$consentPrivacy) throw new Exception('You must agree to the privacy policy');
  if (!$consentTerms) throw new Exception('You must agree to the terms and conditions');

  // Validate time slot
  $allowedTimeSlots = ['09:00', '11:00', '14:00', '16:00'];
  if (!in_array($time, $allowedTimeSlots)) {
    throw new Exception('Invalid time slot selected');
  }

  // Validate date format and ensure it's not in the past
  $dateObj = DateTime::createFromFormat('Y-m-d', $date);
  if (!$dateObj || $dateObj->format('Y-m-d') !== $date) {
    throw new Exception('Invalid date format');
  }
  
  // Check if date is today or in the future
  $today = new DateTime();
  $today->setTime(0, 0, 0);
  $dateObj->setTime(0, 0, 0);
  if ($dateObj < $today) {
    throw new Exception('Appointment date must be today or in the future');
  }

  // Validate email
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    throw new Exception('Invalid email address');
  }

  // Check database connection
  if ($mysqli->connect_errno) {
    throw new Exception('Database connection failed');
  }

  // Look up doctor details from database
  // Handle both slug (dr-name) and full name (Dr. Name)
  $doctor = null;
  
  // First try: exact name match
  $stmt = $mysqli->prepare("SELECT d.name as doctor_name, c.name as clinic_name 
                           FROM doctors d 
                           JOIN clinics c ON d.clinic_slug = c.slug 
                           WHERE d.name = ?");
  $stmt->bind_param('s', $dentistName);
  $stmt->execute();
  $result = $stmt->get_result();
  $doctor = $result->fetch_assoc();
  $stmt->close();

  // Second try: case-insensitive name match
  if (!$doctor) {
    $stmt = $mysqli->prepare("SELECT d.name as doctor_name, c.name as clinic_name 
                             FROM doctors d 
                             JOIN clinics c ON d.clinic_slug = c.slug 
                             WHERE LOWER(d.name) = LOWER(?)");
    $stmt->bind_param('s', $dentistName);
    $stmt->execute();
    $result = $stmt->get_result();
    $doctor = $result->fetch_assoc();
    $stmt->close();
  }

  // Third try: slug match (if received slug like "dr-aisha-rahman")
  if (!$doctor) {
    $stmt = $mysqli->prepare("SELECT d.name as doctor_name, c.name as clinic_name 
                             FROM doctors d 
                             JOIN clinics c ON d.clinic_slug = c.slug 
                             WHERE d.slug = ?");
    $stmt->bind_param('s', $dentistName);
    $stmt->execute();
    $result = $stmt->get_result();
    $doctor = $result->fetch_assoc();
    $stmt->close();
  }

  if (!$doctor) {
    // Debug: log what was received
    error_log('Dentist lookup failed. Received: ' . $dentistName);
    // Try to show available doctors
    $allDoctors = $mysqli->query("SELECT name, slug FROM doctors LIMIT 10");
    $doctorsList = [];
    if ($allDoctors) {
      while ($row = $allDoctors->fetch_assoc()) {
        $doctorsList[] = $row['name'] . ' (slug: ' . $row['slug'] . ')';
      }
    }
    throw new Exception('Invalid dentist selected: "' . htmlspecialchars($dentistName) . '". Available: ' . implode(', ', $doctorsList));
  }

  $doctorName = $doctor['doctor_name'];
  $locationName = $doctor['clinic_name'];
  
  // Convert service name to service_key for database
  $serviceKeyMap = [
    'General Checkup' => 'general',
    'Teeth Cleaning' => 'cleaning',
    'Dental Filling' => 'filling',
    'Tooth Extraction' => 'extraction',
    'Braces Consultation' => 'braces',
    'Teeth Whitening' => 'whitening',
    'Dental Implant' => 'implant',
    'Others' => 'others'
  ];
  $serviceKey = $serviceKeyMap[$serviceName] ?? $serviceName; // fallback to name if not in map

  // Generate reference ID
  function generateReferenceId() {
    do {
      $randomBytes = random_bytes(3);
      $hexSuffix = strtoupper(bin2hex($randomBytes));
      $ref = 'SB-' . date('Ymd') . '-' . $hexSuffix;
      
      // Check uniqueness by attempting insert with a transaction
      global $mysqli;
      $checkStmt = $mysqli->prepare("SELECT COUNT(*) as cnt FROM bookings WHERE reference_id = ?");
      $checkStmt->bind_param('s', $ref);
      $checkStmt->execute();
      $checkResult = $checkStmt->get_result();
      $checkRow = $checkResult->fetch_assoc();
      $checkStmt->close();
      
      if ($checkRow['cnt'] === 0) {
        return $ref;
      }
    } while (true);
  }

  // Check for duplicate slot BEFORE attempting insert
  $checkStmt = $mysqli->prepare("SELECT reference_id FROM bookings WHERE doctor_name = ? AND date = ? AND time = ? LIMIT 1");
  $checkStmt->bind_param('sss', $doctorName, $date, $time);
  $checkStmt->execute();
  $existingBooking = $checkStmt->get_result()->fetch_assoc();
  $checkStmt->close();
  
  if ($existingBooking) {
    throw new Exception('This time slot is already booked. Please select another date or time for ' . $doctorName . '.');
  }

  $referenceId = generateReferenceId();

  // Insert booking with duplicate slot check using pure names
  $stmt = $mysqli->prepare("INSERT INTO bookings (
    reference_id, doctor_name, location_name,
    service_key, patient_type, date, time,
    first_name, last_name, email, phone, notes, status
  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed')");

  $stmt->bind_param('ssssssssssss', 
    $referenceId, $doctorName, $locationName,
    $serviceKey, $previousDentalExperience, $date, $time,
    $firstName, $lastName, $email, $phone, $additionalNotes
  );

  if (!$stmt->execute()) {
    // Double-check if it's a duplicate slot error (shouldn't happen if pre-check worked)
    if ($mysqli->errno === 1062) {
      throw new Exception('This time slot was just booked by another user. Please select another time.');
    }
    throw new Exception('Failed to save booking: ' . $mysqli->error . ' (Error code: ' . $mysqli->errno . ')');
  }

  $stmt->close();
  
  // Send confirmation email using native PHP mail() (Base Version - No external dependencies)
  try {
    require_once __DIR__ . '/../../src/services/native_email_service.php';
    
    // Prepare booking data for email service
    $bookingData = [
      'email' => $email,
      'full_name' => $firstName . ' ' . $lastName,
      'first_name' => $firstName,
      'last_name' => $lastName,
      'phone' => $phone,
      'preferred_date' => $date,
      'preferred_time' => $time,
      'preferred_clinic' => $locationName,
      'doctor_name' => $doctorName,
      'clinic_name' => $locationName,
      'service' => $serviceName,
      'reference_id' => $referenceId,
      'reschedule_token' => bin2hex(random_bytes(16)),
      'id' => $referenceId,
      'notes' => $additionalNotes ?: 'None'
    ];
    
    // Send email using native PHP mail() service
    $emailService = new SmileBrightEmailService();
    
    // 1. Send confirmation to PATIENT
    $patientResult = $emailService->sendBookingConfirmation($bookingData);
    if ($patientResult['success']) {
      error_log('✅ Patient email sent successfully for booking ' . $referenceId . ' to ' . $email);
    } else {
      error_log('❌ Patient email sending failed for booking ' . $referenceId . ' to ' . $email . ': ' . $patientResult['message']);
    }
    
    // 2. Send separate notification to CLINIC
    $clinicEmail = defined('EMAIL_BCC_ADMIN') && !empty(EMAIL_BCC_ADMIN) ? EMAIL_BCC_ADMIN : 'smilebrightsg.info@gmail.com';
    
    $patientName = $firstName . ' ' . $lastName;
    $appointmentDate = date('M j, Y', strtotime($date));
    $appointmentTime = date('g:i A', strtotime($time));
    
    $clinicSubject = "New Booking - Ref {$referenceId} - {$patientName}";
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
        <h1 style='margin:0;'>New Booking Received</h1>
        <p style='margin:5px 0 0 0;'>Reference: {$referenceId}</p>
      </div>
      
      <h2>Booking Details</h2>
      <div class='detail'><strong>Patient:</strong> {$patientName}</div>
      <div class='detail'><strong>Email:</strong> {$email}</div>
      <div class='detail'><strong>Phone:</strong> {$phone}</div>
      <div class='detail'><strong>Doctor:</strong> {$doctorName}</div>
      <div class='detail'><strong>Clinic:</strong> {$locationName}</div>
      <div class='detail'><strong>Date:</strong> {$appointmentDate}</div>
      <div class='detail'><strong>Time:</strong> {$appointmentTime}</div>
      <div class='detail'><strong>Service:</strong> {$serviceName}</div>
      " . (!empty($additionalNotes) ? "<div class='detail'><strong>Notes:</strong> " . htmlspecialchars($additionalNotes) . "</div>" : "") . "
      
      <p style='margin-top:20px; color:#6b7a90; font-size:14px;'>
        Booking created at: " . date('M j, Y g:i A') . "
      </p>
    </div>
  </div>
</body>
</html>";
    $clinicText = "New Booking - Ref {$referenceId}\n\nPatient: {$patientName}\nEmail: {$email}\nPhone: {$phone}\nDoctor: {$doctorName}\nClinic: {$locationName}\nDate: {$appointmentDate}\nTime: {$appointmentTime}\nService: {$serviceName}\n\n" . (!empty($additionalNotes) ? "Notes: {$additionalNotes}\n\n" : "") . "Booking created at: " . date('M j, Y g:i A');
    
    $clinicResult = $emailService->sendClinicNotification($clinicSubject, $clinicHtml, $clinicText, $clinicEmail);
    if ($clinicResult['success']) {
      error_log('✅ Clinic email sent to: ' . $clinicEmail);
    } else {
      error_log('❌ Clinic email failed: ' . $clinicResult['message']);
    }
  } catch (Exception $e) {
    // Log error but don't fail the booking
    error_log('❌ Email sending exception for booking ' . $referenceId . ' to ' . $email . ': ' . $e->getMessage());
    error_log('   Exception trace: ' . $e->getTraceAsString());
  }

  // Redirect to success page
  $redirectUrl = '/SmileBrightbase/public/booking/booking_success.php?ref=' . urlencode($referenceId);
  
  // For testing: output reference ID if no redirect capability
  http_response_code(200);
  header('Location: ' . $redirectUrl);
  exit();

} catch (Exception $e) {
  // Output error page
  http_response_code(400);
  echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1" />'
     . '<title>Booking Error</title>'
     . '<link rel="stylesheet" href="/SmileBrightbase/public/css/footer.css" />'
     . '<style>body{font:16px/1.55 Segoe UI,Arial;background:#f5f7fb;color:#243042;margin:0} .wrap{max-width:880px;margin:32px auto;padding:0 16px} .card{background:#fff;border:1px solid #e5e9f3;border-radius:12px;box-shadow:0 6px 20px rgba(22,39,76,.06);padding:22px} h1{color:#c33}</style>'
     . '</head><body>'
     . '<div class="wrap">'
     .   '<div class="card">'
     .     '<h1>Booking Error</h1>'
     .     '<p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>'
     .     '<p><a href="/SmileBrightbase/public/booking/book_appointmentbase.html">Go back to the form</a></p>'
     .   '</div>'
     . '</div>'
     . '</body></html>';
  exit();
}

?>
