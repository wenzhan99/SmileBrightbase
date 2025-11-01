<?php
// Base booking update via HTML form (no AJAX). Validates input, updates DB, renders HTML confirmation.

require_once __DIR__ . '/config.php';

ini_set('display_errors', '0');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Method Not Allowed</title></head><body><h1>405 - Method Not Allowed</h1></body></html>';
  exit();
}

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

// Collect inputs
$referenceId = trim($_POST['reference_id'] ?? '');
$email       = isset($_POST['email']) ? trim($_POST['email']) : null;
$phone       = isset($_POST['phone']) ? trim($_POST['phone']) : null;
$dateIso     = isset($_POST['date_iso']) ? trim($_POST['date_iso']) : null;
$time24      = isset($_POST['time_24']) ? trim($_POST['time_24']) : null;
$notes       = isset($_POST['notes']) ? trim($_POST['notes']) : null;
$status      = isset($_POST['status']) ? trim($_POST['status']) : null; // scheduled|confirmed|cancelled|completed|rescheduled

$errors = [];
if ($referenceId === '') $errors[] = 'Reference ID is required';
if ($email !== null && $email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email';
if ($phone !== null && $phone !== '' && !preg_match('/^\+?\d[\d\s\-]{7,}$/', $phone)) $errors[] = 'Invalid phone';
if ($dateIso !== null && $dateIso !== '') {
  $d = DateTime::createFromFormat('Y-m-d', $dateIso);
  if (!$d || $d->format('Y-m-d') !== $dateIso) $errors[] = 'Invalid date format (Y-m-d)';
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
     . '<link rel="stylesheet" href="/SmileBrightbase/public/css/footer.css" />'
     . '<style>body{font:16px/1.55 Segoe UI,Arial;background:#f5f7fb;color:#243042;margin:0}.wrap{max-width:880px;margin:32px auto;padding:0 16px}.card{background:#fff;border:1px solid #e5e9f3;border-radius:12px;box-shadow:0 6px 20px rgba(22,39,76,.06);padding:22px}</style>'
     . '</head><body><div class="wrap"><h1>There were problems with your update</h1><div class="card"><ul>';
  foreach ($errors as $e) echo '<li>'.h($e).'</li>';
  echo '</ul><p><a href="/SmileBrightbase/public/booking/manage_booking.html">Go back</a></p></div></div>'
     . '<footer class="site-footer"><div class="footer-inner"><div class="footer-col"><h3>Smile Bright Dental</h3></div></div><div class="footer-bottom">© 2025 Smile Bright Dental</div></footer>'
     . '</body></html>';
  exit();
}

// Check database connection
if ($mysqli->connect_errno) {
  http_response_code(500);
  echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Database Error</title></head><body><h1>Database connection failed</h1></body></html>';
  exit();
}

// Fetch existing row
$stmt = $mysqli->prepare('SELECT * FROM bookings WHERE reference_id = ?');
$stmt->bind_param('s', $referenceId);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$existing) {
  http_response_code(404);
  echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Not Found</title></head><body><h1>Booking not found</h1></body></html>';
  exit();
}

// Build update
$fields = [];$values = [];$types = '';
if ($email !== null && $email !== '') { $fields[] = 'email = ?'; $values[] = $email; $types .= 's'; }
if ($phone !== null && $phone !== '') { $fields[] = 'phone = ?'; $values[] = $phone; $types .= 's'; }
if ($dateIso !== null && $dateIso !== '') { $fields[] = 'preferred_date = ?'; $values[] = $dateIso; $types .= 's'; }
if ($time24 !== null && $time24 !== '') { $fields[] = 'preferred_time = ?'; $values[] = $time24; $types .= 's'; }
if ($notes !== null) { $fields[] = 'message = ?'; $values[] = $notes; $types .= 's'; }
if ($status !== null && $status !== '') { $fields[] = 'status = ?'; $values[] = strtolower($status); $types .= 's'; }

if (!$fields) {
  echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>No Changes</title></head><body><h1>No changes provided</h1><p><a href="/SmileBrightbase/public/booking/manage_booking.html">Back</a></p></body></html>';
  exit();
}

$fields[] = 'updated_at = CURRENT_TIMESTAMP';
$sql = 'UPDATE bookings SET '.implode(', ', $fields).' WHERE reference_id = ?';
$values[] = $referenceId; $types .= 's';

$stmt = $mysqli->prepare($sql);
if (!$stmt) {
  http_response_code(500);
  echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Database Error</title></head><body><h1>Failed to prepare update</h1></body></html>';
  exit();
}
$stmt->bind_param($types, ...$values);
$ok = $stmt->execute();
$stmt->close();

// Fetch updated for display
$stmt = $mysqli->prepare('SELECT reference_id, full_name, email, phone, preferred_clinic, service, preferred_date, preferred_time, message, status FROM bookings WHERE reference_id = ?');
$stmt->bind_param('s', $referenceId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ok) {
  http_response_code(500);
}

echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1" />'
   . '<title>Booking Updated — '.h($referenceId).'</title>'
   . '<link rel="stylesheet" href="/SmileBrightbase/public/css/footer.css" />'
   . '<style>body{font:16px/1.55 Segoe UI,Arial;background:#f5f7fb;color:#243042;margin:0}.wrap{max-width:880px;margin:32px auto;padding:0 16px}.card{background:#fff;border:1px solid #e5e9f3;border-radius:12px;box-shadow:0 6px 20px rgba(22,39,76,.06);padding:22px}table{width:100%;border-collapse:collapse;margin-top:12px}th,td{border:1px solid #e5e9f3;padding:10px;text-align:left}th{background:#f8faff}</style>'
   . '</head><body>'
   . '<div class="wrap">'
   .   '<h1>Booking Updated</h1>'
   .   '<div class="card">'
   .     '<p>Reference <strong>'.h($referenceId).'</strong> has been updated.</p>'
   .     '<table>'
   .       '<tr><th>Reference</th><td>'.h($row['reference_id'] ?? $referenceId).'</td></tr>'
   .       '<tr><th>Name</th><td>'.h($row['full_name'] ?? '').'</td></tr>'
   .       '<tr><th>Email</th><td>'.h($row['email'] ?? '').'</td></tr>'
   .       '<tr><th>Phone</th><td>'.h($row['phone'] ?? '').'</td></tr>'
   .       '<tr><th>Clinic</th><td>'.h($row['preferred_clinic'] ?? '').'</td></tr>'
   .       '<tr><th>Service</th><td>'.h($row['service'] ?? '').'</td></tr>'
   .       '<tr><th>Date</th><td>'.h($row['preferred_date'] ?? '').'</td></tr>'
   .       '<tr><th>Time</th><td>'.h($row['preferred_time'] ?? '').'</td></tr>'
   .       '<tr><th>Status</th><td>'.h($row['status'] ?? '').'</td></tr>'
   .       '<tr><th>Notes</th><td>'.h($row['message'] ?? '').'</td></tr>'
   .     '</table>'
   .     '<p style="margin-top:12px"><a href="/SmileBrightbase/public/index.html">Return to Home</a></p>'
   .   '</div>'
   . '</div>'
   . '<footer class="site-footer"><div class="footer-inner"><div class="footer-col"><h3>Smile Bright Dental</h3></div></div><div class="footer-bottom">© 2025 Smile Bright Dental</div></footer>'
   . '</body></html>';

?>


