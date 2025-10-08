<?php
header('Content-Type: text/plain; charset=UTF-8');
require __DIR__ . '/db.php';

$first   = $_POST['firstName'] ?? '';
$last    = $_POST['lastName'] ?? '';
$email   = $_POST['email'] ?? '';
$phone   = $_POST['phone'] ?? '';
$date    = $_POST['date'] ?? '';   // yyyy-mm-dd
$time    = $_POST['time'] ?? '';
$clinic  = $_POST['clinic'] ?? '';
$service = $_POST['service'] ?? '';
$msg     = $_POST['message'] ?? '';
$consent = (($_POST['consent'] ?? '0') === '1') ? 1 : 0;

$stmt = $conn->prepare(
  "INSERT INTO appointments
   (first_name, last_name, email, phone, date, time, clinic, service, message, consent)
   VALUES (?,?,?,?,?,?,?,?,?,?)"
);
$stmt->bind_param('sssssssssi',
  $first, $last, $email, $phone, $date, $time, $clinic, $service, $msg, $consent
);

if ($stmt->execute()) {
  echo "Thanks, your booking was received. Reference: #" . $stmt->insert_id;
} else {
  http_response_code(500);
  echo "Save failed: " . $conn->error;
}