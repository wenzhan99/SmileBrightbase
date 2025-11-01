<?php
// Appointment submit: processes booking form and renders confirmation page
// /api/booking/appointment_submit.php

require_once __DIR__ . '/../config.php';

// Security & headers
ini_set('display_errors', '0');
header('X-Content-Type-Options: nosniff');

// Allow only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Method Not Allowed</title></head><body><h1>405 - Method Not Allowed</h1></body></html>';
  exit();
}

// Helper function to escape output
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

// Collect inputs
$dentist = trim($_POST['dentist'] ?? '');
$location = trim($_POST['location'] ?? '');
$date = trim($_POST['date'] ?? '');
$time = trim($_POST['time'] ?? '');
$firstName = trim($_POST['firstName'] ?? '');
$lastName = trim($_POST['lastName'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$serviceRequired = trim($_POST['serviceRequired'] ?? '');
$serviceOther = trim($_POST['serviceOther'] ?? '');
$previousDentalExperience = trim($_POST['previousDentalExperience'] ?? '');
$additionalNotes = trim($_POST['additionalNotes'] ?? '');
$consentPrivacy = isset($_POST['consentPrivacy']) ? 1 : 0;
$consentTerms = isset($_POST['consentTerms']) ? 1 : 0;

// Server-side validation
$errors = [];
if ($dentist === '') $errors[] = 'Please select a dentist';
if ($location === '') $errors[] = 'Location is required';
if ($date === '') $errors[] = 'Please select a date';
if ($time === '') $errors[] = 'Please select a time slot';
if ($firstName === '') $errors[] = 'First name is required';
if ($lastName === '') $errors[] = 'Last name is required';
if ($email === '') $errors[] = 'Email is required';
if ($phone === '') $errors[] = 'Phone number is required';
if ($consentPrivacy === 0) $errors[] = 'You must agree to the privacy policy';
if ($consentTerms === 0) $errors[] = 'You must agree to the terms and conditions';

// Validate email
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  $errors[] = 'Please enter a valid email address';
}

// Validate phone
if ($phone !== '' && !preg_match('/^\+?\d[\d\s\-]{7,}$/', $phone)) {
  $errors[] = 'Please enter a valid phone number';
}

// If "Others" selected but no specification provided
if ($serviceRequired === 'others' && $serviceOther === '') {
  $errors[] = 'Please specify the service';
}

// Validate date format
if ($date !== '') {
  $dateObj = DateTime::createFromFormat('Y-m-d', $date);
  if (!$dateObj || $dateObj->format('Y-m-d') !== $date) {
    $errors[] = 'Invalid date format';
  }
}

// Validate time format
if ($time !== '') {
  $timeObj = DateTime::createFromFormat('H:i', $time);
  if (!$timeObj || $timeObj->format('H:i') !== $time) {
    $errors[] = 'Invalid time format';
  }
}

// If errors, render simple error page
if ($errors) {
  http_response_code(400);
  echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1" />'
     . '<title>Booking Error</title>'
     . '<link rel="stylesheet" href="/SmileBrightbase/public/css/footer.css" />'
     . '<style>body{font:16px/1.55 Segoe UI,Arial;background:#f5f7fb;color:#243042;margin:0} .wrap{max-width:880px;margin:32px auto;padding:0 16px} .card{background:#fff;border:1px solid #e5e9f3;border-radius:12px;box-shadow:0 6px 20px rgba(22,39,76,.06);padding:22px}</style>'
     . '</head><body><div class="wrap"><h1>There were problems with your submission</h1><div class="card"><ul>';
  foreach ($errors as $e) {
    echo '<li>' . h($e) . '</li>';
  }
  echo '</ul><p><a href="/SmileBrightbase/public/booking/book_appointmentbase.html">Go back to the form</a></p></div></div>'
     . '<footer class="site-footer"><div class="footer-inner"><div class="footer-col"><h3>Smile Bright Dental</h3><p>Please correct the errors above and try again.</p></div></div><div class="footer-bottom">© 2025 Smile Bright Dental</div></footer>'
     . '</body></html>';
  exit();
}

// Generate a simple reference
$referenceId = 'SB-' . date('Ymd') . '-' . strtoupper(substr(sha1(uniqid('', true)), 0, 6));

// Check database connection
if ($mysqli->connect_errno) {
  http_response_code(500);
  echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Database Error</title></head><body><h1>Database connection failed</h1></body></html>';
  exit();
}

// Prepare full name and handle service
$fullName = $firstName . ' ' . $lastName;
$finalService = ($serviceRequired === 'others' && $serviceOther !== '') ? $serviceOther : $serviceRequired;

// Insert booking row
$stmt = $mysqli->prepare("INSERT INTO bookings (
  reference_id, first_name, last_name, email, phone, dentist_name, clinic_name, 
  preferred_date, preferred_time, service, notes, agree_policy, agree_terms, status
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'scheduled')");

if (!$stmt) {
  http_response_code(500);
  echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Database Error</title></head><body><h1>Failed to prepare insert</h1></body></html>';
  exit();
}

$stmt->bind_param('ssssssssssiii', $referenceId, $firstName, $lastName, $email, $phone, $dentist, $location, $date, $time, $finalService, $additionalNotes, $consentPrivacy, $consentTerms);

if (!$stmt->execute()) {
  http_response_code(500);
  echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Database Error</title></head><body><h1>Failed to save booking</h1></body></html>';
  $stmt->close();
  exit();
}
$stmt->close();

// SELECT back the row for display
$stmt = $mysqli->prepare("SELECT reference_id, first_name, last_name, email, phone, dentist_name, clinic_name, preferred_date, preferred_time, service, notes FROM bookings WHERE reference_id = ?");
$stmt->bind_param('s', $referenceId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc() ?: [];
$stmt->close();

// Render server-generated confirmation page
$displayName = ($row['first_name'] ?? $firstName) . ' ' . ($row['last_name'] ?? $lastName);
echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1" />'
   . '<title>Booking Confirmation — ' . h($referenceId) . '</title>'
   . '<link rel="stylesheet" href="/SmileBrightbase/public/css/footer.css" />'
   . '<style>body{font:16px/1.55 Segoe UI,Arial;background:#f5f7fb;color:#243042;margin:0} .wrap{max-width:880px;margin:32px auto;padding:0 16px} .card{background:#fff;border:1px solid #e5e9f3;border-radius:12px;box-shadow:0 6px 20px rgba(22,39,76,.06);padding:22px} table{width:100%;border-collapse:collapse;margin-top:12px} th,td{border:1px solid #e5e9f3;padding:10px;text-align:left} th{background:#f8faff}</style>'
   . '</head><body>'
   . '<div class="wrap">'
   .   '<h1>Your Booking is Confirmed</h1>'
   .   '<div class="card">'
   .     '<p>Thank you, ' . h($firstName) . '! Your appointment has been scheduled. Your reference number is <strong>' . h($referenceId) . '</strong>.</p>'
   .     '<table aria-label="Booking Details">'
   .       '<tr><th>Reference</th><td>' . h($referenceId) . '</td></tr>'
   .       '<tr><th>Name</th><td>' . h($displayName) . '</td></tr>'
   .       '<tr><th>Email</th><td>' . h($row['email'] ?? $email) . '</td></tr>'
   .       '<tr><th>Phone</th><td>' . h($row['phone'] ?? $phone) . '</td></tr>'
   .       '<tr><th>Dentist</th><td>' . h($row['dentist_name'] ?? $dentist) . '</td></tr>'
   .       '<tr><th>Clinic</th><td>' . h($row['clinic_name'] ?? $location) . '</td></tr>'
   .       ($finalService ? ('<tr><th>Service</th><td>' . h($row['service'] ?? $finalService) . '</td></tr>') : '')
   .       '<tr><th>Date</th><td>' . h($row['preferred_date'] ?? $date) . '</td></tr>'
   .       '<tr><th>Time</th><td>' . h($row['preferred_time'] ?? $time) . '</td></tr>'
   .       ($additionalNotes ? ('<tr><th>Notes</th><td>' . h($row['notes'] ?? $additionalNotes) . '</td></tr>') : '')
   .     '</table>'
   .     '<p style="margin-top:12px"><a href="/SmileBrightbase/public/index.html">Return to Home</a></p>'
   .   '</div>'
   . '</div>'
   . '<footer class="site-footer"><div class="footer-inner"><div class="footer-col"><h3>Smile Bright Dental</h3><p>We look forward to seeing you.</p></div></div><div class="footer-bottom">© 2025 Smile Bright Dental</div></footer>'
   . '</body></html>';

?>

