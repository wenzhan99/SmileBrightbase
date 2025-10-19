<?php
require __DIR__ . '/db.php';
require __DIR__ . '/notification_bridge.php';

// Handle both React form field names and your specified field names
$full_name = $_POST['full_name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$preferred_clinic = $_POST['preferred_clinic'] ?? '';
$service = $_POST['service'] ?? '';
$preferred_date = $_POST['preferred_date'] ?? '';
$preferred_time = $_POST['preferred_time'] ?? '';
$message = $_POST['message'] ?? null;
$terms_accepted = ($_POST['termsAccepted'] ?? '0') === '1';

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

if (!$terms_accepted) {
  http_response_code(422);
  echo 'Terms & Conditions must be accepted';
  exit;
}

// Generate reference ID and reschedule token
$reference_id = 'SB' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
$reschedule_token = hash('sha256', uniqid(rand(), true));
$token_expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));

// Ensure reference ID is unique
$stmt_check = $mysqli->prepare('SELECT COUNT(*) FROM bookings WHERE reference_id = ?');
$stmt_check->bind_param('s', $reference_id);
$stmt_check->execute();
$stmt_check->bind_result($count);
$stmt_check->fetch();
$stmt_check->close();

while ($count > 0) {
    $reference_id = 'SB' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
    $stmt_check = $mysqli->prepare('SELECT COUNT(*) FROM bookings WHERE reference_id = ?');
    $stmt_check->bind_param('s', $reference_id);
    $stmt_check->execute();
    $stmt_check->bind_result($count);
    $stmt_check->fetch();
    $stmt_check->close();
}

$stmt = $mysqli->prepare('INSERT INTO bookings (reference_id, full_name, email, phone, preferred_clinic, service, preferred_date, preferred_time, message, reschedule_token, token_expires_at, terms_accepted) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
$stmt->bind_param('sssssssssssi', $reference_id, $full_name, $email, $phone, $preferred_clinic, $service, $preferred_date, $preferred_time, $message, $reschedule_token, $token_expires_at, $terms_accepted);

if (!$stmt->execute()) {
  http_response_code(500);
  echo 'Save failed: ' . $stmt->error;
  exit;
}

$booking_id = $mysqli->insert_id;

// Prepare booking data for notifications
$bookingData = [
    'id' => $booking_id,
    'reference_id' => $reference_id,
    'full_name' => $full_name,
    'email' => $email,
    'phone' => $phone,
    'preferred_clinic' => $preferred_clinic,
    'service' => $service,
    'preferred_date' => $preferred_date,
    'preferred_time' => $preferred_time,
    'message' => $message,
    'reschedule_token' => $reschedule_token,
    'token_expires_at' => $token_expires_at
];

// Send notification via Node.js service (non-blocking)
try {
    $notificationResult = sendBookingNotification($bookingData, 'booking_created');
    if ($notificationResult) {
        error_log("Booking notification sent successfully for reference: $reference_id");
    } else {
        error_log("Failed to send booking notification for reference: $reference_id");
    }
} catch (Exception $e) {
    error_log("Notification error for booking $reference_id: " . $e->getMessage());
    // Don't fail the booking if notification fails
}

header('Content-Type: application/json');
echo json_encode([
    'ok' => true, 
    'id' => $booking_id,
    'reference_id' => $reference_id,
    'confirmation_url' => "confirm.php?ref={$reference_id}&token={$reschedule_token}",
    'notification_sent' => $notificationResult ?? false
]);
?>