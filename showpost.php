<?php
header('Content-Type: text/plain; charset=UTF-8');
require __DIR__ . '/db.php';
require __DIR__ . '/send_email.php';

// Get form data
$first      = $_POST['firstName'] ?? '';
$last       = $_POST['lastName'] ?? '';
$email      = $_POST['email'] ?? '';
$phone      = $_POST['phone'] ?? '';
$date       = $_POST['date'] ?? '';   // yyyy-mm-dd
$time       = $_POST['time'] ?? '';
$clinic     = $_POST['clinic'] ?? '';
$service    = $_POST['service'] ?? '';
$experience = $_POST['experience'] ?? '';
$msg        = $_POST['message'] ?? '';
$consent    = (($_POST['consent'] ?? '0') === '1') ? 1 : 0;

// Generate reschedule token and expiry
$rescheduleToken = generateRescheduleToken();
$tokenExpiresAt = getTokenExpiryDate();

// Prepare and execute database insert
$stmt = $conn->prepare(
  "INSERT INTO appointments
   (first_name, last_name, email, phone, date, time, clinic, service, experience, message, consent, reschedule_token, token_expires_at)
   VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)"
);
$stmt->bind_param('sssssssssssss',
  $first, $last, $email, $phone, $date, $time, $clinic, $service, $experience, $msg, $consent, $rescheduleToken, $tokenExpiresAt
);

if ($stmt->execute()) {
  $appointmentId = $stmt->insert_id;
  
  // Prepare appointment data for email
  $appointmentData = [
    'id' => $appointmentId,
    'first_name' => $first,
    'last_name' => $last,
    'email' => $email,
    'phone' => $phone,
    'date' => $date,
    'time' => $time,
    'clinic' => $clinic,
    'service' => $service,
    'experience' => $experience,
    'message' => $msg,
    'reschedule_token' => $rescheduleToken,
    'token_expires_at' => $tokenExpiresAt,
    'created_at' => date('Y-m-d H:i:s')
  ];
  
  // Send confirmation email
  $emailSent = sendBookingConfirmation($appointmentData);
  
  if ($emailSent) {
    echo "✅ Booking confirmed! Reference: #{$appointmentId}\nA confirmation email has been sent to {$email}";
  } else {
    echo "✅ Booking confirmed! Reference: #{$appointmentId}\n⚠️ Email notification could not be sent. Please check your inbox or contact us directly.";
  }
} else {
  http_response_code(500);
  echo "❌ Save failed: " . $conn->error;
}