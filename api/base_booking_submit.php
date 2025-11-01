<?php
// Base booking submit: processes standard POST form and renders an HTML confirmation page (no AJAX)

require_once __DIR__ . '/config.php';

// Security & headers
ini_set('display_errors', '0');
header('X-Content-Type-Options: nosniff');

// Allow only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Method Not Allowed</title></head><body><h1>405 - Method Not Allowed</h1></body></html>';
  exit();
}

// Helper
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

// Collect inputs
$firstName   = trim($_POST['first_name'] ?? '');
$lastName    = trim($_POST['last_name'] ?? '');
$email       = trim($_POST['email'] ?? '');
$phone       = trim($_POST['phone'] ?? '');
$service     = trim($_POST['service'] ?? '');
$clinic      = trim($_POST['clinic'] ?? '');
$dateIso     = trim($_POST['date_iso'] ?? '');
$time24      = trim($_POST['time_24'] ?? '');
$notes       = trim($_POST['notes'] ?? '');
$agreePolicy = isset($_POST['agree_policy']) ? 1 : 0;
$agreeTerms  = isset($_POST['agree_terms']) ? 1 : 0;

// Server-side validation
$errors = [];
if ($firstName === '') $errors[] = 'First name is required';
if ($lastName === '')  $errors[] = 'Last name is required';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required';
if (!preg_match('/^\+?\d[\d\s\-]{7,}$/', $phone)) $errors[] = 'A valid phone is required';
if ($service === '') $errors[] = 'Please select a service';
if ($clinic === '')  $errors[] = 'Please select a clinic';
// Date must be Y-m-d
if ($dateIso === '' || !DateTime::createFromFormat('Y-m-d', $dateIso) || (DateTime::createFromFormat('Y-m-d', $dateIso))->format('Y-m-d') !== $dateIso) {
  $errors[] = 'Invalid date format';
}
// Time must be H:i
if ($time24 === '' || !DateTime::createFromFormat('H:i', $time24) || (DateTime::createFromFormat('H:i', $time24))->format('H:i') !== $time24) {
  $errors[] = 'Invalid time format';
}
if (!$agreePolicy || !$agreeTerms) $errors[] = 'You must agree to policy and terms';

// If errors, render simple error page
if ($errors) {
  http_response_code(400);
  echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1" />'
     . '<title>Booking Error</title>'
     . '<link rel="stylesheet" href="/SmileBright/public/css/footer.css" />'
     . '<style>body{font:16px/1.55 Segoe UI,Arial;background:#f5f7fb;color:#243042;margin:0} .wrap{max-width:880px;margin:32px auto;padding:0 16px} .card{background:#fff;border:1px solid #e5e9f3;border-radius:12px;box-shadow:0 6px 20px rgba(22,39,76,.06);padding:22px}</style>'
     . '</head><body><div class="wrap"><h1>There were problems with your submission</h1><div class="card"><ul>';
  foreach ($errors as $e) {
    echo '<li>' . h($e) . '</li>';
  }
  echo '</ul><p><a href="/SmileBright/public/base_booking_form.html">Go back to the form</a></p></div></div>'
     . '<footer class="site-footer"><div class="footer-inner"><div class="footer-col"><h3>Smile Bright Dental</h3><p>Please correct the errors above and try again.</p></div></div><div class="footer-bottom">© 2025 Smile Bright Dental</div></footer>'
     . '</body></html>';
  exit();
}

// Generate a simple reference
$referenceId = 'SB-' . date('Ymd') . '-' . strtoupper(substr(sha1(uniqid('', true)), 0, 6));
// Derive labels
$serviceLabelMap = [
  'general' => 'General Dentistry',
  'cosmetic' => 'Cosmetic Dentistry',
  'orthodontics' => 'Orthodontics',
  'implants' => 'Dental Implants',
];
$clinicLabelMap = [
  'orchard' => 'Orchard',
  'marina_bay' => 'Marina Bay',
  'bukit_t' => 'Bukit Timah',
  'tampines' => 'Tampines',
  'jurong' => 'Jurong',
];
$serviceLabel = $serviceLabelMap[$service] ?? ucfirst($service);
$clinicLabel  = $clinicLabelMap[$clinic] ?? ucfirst(str_replace('_',' ',$clinic));

// Check database connection
if ($mysqli->connect_errno) {
  http_response_code(500);
  echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Database Error</title></head><body><h1>Database connection failed</h1></body></html>';
  exit();
}

// Insert booking row (basic subset of columns common in project)
$stmt = $mysqli->prepare("INSERT INTO bookings (
  reference_id, full_name, email, phone, preferred_clinic, service, preferred_date, preferred_time, message, agree_policy, agree_terms, status
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'scheduled')");
if (!$stmt) {
  http_response_code(500);
  echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Database Error</title></head><body><h1>Failed to prepare insert</h1></body></html>';
  exit();
}
$fullName = $firstName . ' ' . $lastName;
$stmt->bind_param('ssssssssssii', $referenceId, $fullName, $email, $phone, $clinicLabel, $serviceLabel, $dateIso, $time24, $notes, $agreePolicy, $agreeTerms);
if (!$stmt->execute()) {
  http_response_code(500);
  echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Database Error</title></head><body><h1>Failed to save booking</h1></body></html>';
  $stmt->close();
  exit();
}
$stmt->close();

// SELECT back the row for display (demonstrates SELECT)
$stmt = $mysqli->prepare("SELECT reference_id, full_name, email, phone, preferred_clinic, service, preferred_date, preferred_time, message FROM bookings WHERE reference_id = ?");
$stmt->bind_param('s', $referenceId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc() ?: [];
$stmt->close();

// Render server-generated confirmation page with a table
echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1" />'
   . '<title>Booking Confirmation — ' . h($referenceId) . '</title>'
   . '<link rel="stylesheet" href="/SmileBright/public/css/footer.css" />'
   . '<style>body{font:16px/1.55 Segoe UI,Arial;background:#f5f7fb;color:#243042;margin:0} .wrap{max-width:880px;margin:32px auto;padding:0 16px} .card{background:#fff;border:1px solid #e5e9f3;border-radius:12px;box-shadow:0 6px 20px rgba(22,39,76,.06);padding:22px} table{width:100%;border-collapse:collapse;margin-top:12px} th,td{border:1px solid #e5e9f3;padding:10px;text-align:left} th{background:#f8faff}</style>'
   . '</head><body>'
   . '<div class="wrap">'
   .   '<h1>Your Booking is Confirmed</h1>'
   .   '<div class="card">'
   .     '<p>Thank you, ' . h($firstName) . '. Your reference number is <strong>' . h($referenceId) . '</strong>.</p>'
   .     '<table aria-label="Booking Details">'
   .       '<tr><th>Reference</th><td>' . h($row['reference_id'] ?? $referenceId) . '</td></tr>'
   .       '<tr><th>Name</th><td>' . h($row['full_name'] ?? $fullName) . '</td></tr>'
   .       '<tr><th>Email</th><td>' . h($row['email'] ?? $email) . '</td></tr>'
   .       '<tr><th>Phone</th><td>' . h($row['phone'] ?? $phone) . '</td></tr>'
   .       '<tr><th>Clinic</th><td>' . h($row['preferred_clinic'] ?? $clinicLabel) . '</td></tr>'
   .       '<tr><th>Service</th><td>' . h($row['service'] ?? $serviceLabel) . '</td></tr>'
   .       '<tr><th>Date</th><td>' . h($row['preferred_date'] ?? $dateIso) . '</td></tr>'
   .       '<tr><th>Time</th><td>' . h($row['preferred_time'] ?? $time24) . '</td></tr>'
   .       '<tr><th>Notes</th><td>' . h($row['message'] ?? $notes) . '</td></tr>'
   .     '</table>'
   .     '<p style="margin-top:12px"><a href="/SmileBright/public/index.html">Return to Home</a></p>'
   .   '</div>'
   . '</div>'
   . '<footer class="site-footer"><div class="footer-inner"><div class="footer-col"><h3>Smile Bright Dental</h3><p>We look forward to seeing you.</p></div></div><div class="footer-bottom">© 2025 Smile Bright Dental</div></footer>'
   . '</body></html>';

?>



