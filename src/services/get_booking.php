<?php
require __DIR__ . '/db.php';

header('Content-Type: application/json');

$reference_id = $_GET['ref'] ?? '';
$token = $_GET['token'] ?? '';

if (!$reference_id || !$token) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing reference ID or token']);
    exit;
}

// Get booking details
$stmt = $mysqli->prepare('
    SELECT 
        id, reference_id, full_name, email, phone, preferred_clinic, 
        service, preferred_date, preferred_time, message, 
        created_at, reschedule_token, token_expires_at
    FROM bookings 
    WHERE reference_id = ? AND reschedule_token = ? AND token_expires_at > NOW()
');

$stmt->bind_param('ss', $reference_id, $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Booking not found or token expired']);
    exit;
}

$booking = $result->fetch_assoc();
$stmt->close();

// Add default status if not present
if (!isset($booking['status'])) {
    $booking['status'] = 'confirmed';
}

// Return booking details
echo json_encode([
    'success' => true,
    'booking' => $booking
]);
?>
