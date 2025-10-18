<?php
require __DIR__ . '/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$reference_id = $_POST['referenceId'] ?? '';
$email = $_POST['email'] ?? '';

if (!$reference_id || !$email) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing reference ID or email']);
    exit;
}

// Validate reference ID format
if (!preg_match('/^SB[0-9]{6}$/', $reference_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid reference ID format']);
    exit;
}

// Get booking details
$stmt = $mysqli->prepare('
    SELECT 
        id, reference_id, full_name, email, phone, preferred_clinic, 
        service, preferred_date, preferred_time, message, status, 
        created_at, updated_at, reschedule_token, token_expires_at
    FROM bookings 
    WHERE reference_id = ? AND email = ? AND token_expires_at > NOW()
');

$stmt->bind_param('ss', $reference_id, $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Booking not found or expired']);
    exit;
}

$booking = $result->fetch_assoc();
$stmt->close();

// Return booking details
echo json_encode([
    'success' => true,
    'booking' => $booking
]);
?>
