<?php
require __DIR__ . '/db.php';

$full_name = $_POST['full_name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$preferred_clinic = $_POST['preferred_clinic'] ?? '';
$service = $_POST['service'] ?? '';
$preferred_date = $_POST['preferred_date'] ?? '';
$preferred_time = $_POST['preferred_time'] ?? '';
$message = $_POST['message'] ?? null;

// Basic validation
if (!$full_name || !$email || !$phone || !$preferred_clinic || !$service || !$preferred_date || !$preferred_time) {
  http_response_code(422);
  echo 'Missing required fields';
  exit;
}

$stmt = $mysqli->prepare('INSERT INTO bookings (full_name,email,phone,preferred_clinic,service,preferred_date,preferred_time,message) VALUES (?,?,?,?,?,?,?,?)');
$stmt->bind_param('ssssssss', $full_name, $email, $phone, $preferred_clinic, $service, $preferred_date, $preferred_time, $message);

if (!$stmt->execute()) {
  http_response_code(500);
  echo 'Save failed: ' . $stmt->error;
  exit;
}

header('Content-Type: application/json');
echo json_encode(['ok' => true, 'id' => $mysqli->insert_id]);
?>