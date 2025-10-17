<?php
require __DIR__ . '/db.php';

// Handle both React form field names and your specified field names
$full_name = $_POST['full_name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$preferred_clinic = $_POST['preferred_clinic'] ?? '';
$service = $_POST['service'] ?? '';
$preferred_date = $_POST['preferred_date'] ?? '';
$preferred_time = $_POST['preferred_time'] ?? '';
$message = $_POST['message'] ?? null;

// If using React form, map the field names
if (!$full_name && isset($_POST['firstName']) && isset($_POST['lastName'])) {
    $full_name = trim($_POST['firstName'] . ' ' . $_POST['lastName']);
}
if (!$preferred_clinic && isset($_POST['clinic'])) {
    $preferred_clinic = $_POST['clinic'];
}
if (!$preferred_date && isset($_POST['date'])) {
    $preferred_date = $_POST['date'];
}
if (!$preferred_time && isset($_POST['time'])) {
    $preferred_time = $_POST['time'];
}

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