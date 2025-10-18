<?php
// Clean test of the booking flow
require __DIR__ . '/db.php';

echo "Testing Booking Flow (Clean)\n";
echo "============================\n\n";

// Test 1: Create a booking directly in the database
echo "1. Creating test booking...\n";

$reference_id = 'SB' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
$reschedule_token = hash('sha256', uniqid(rand(), true));
$token_expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));

$stmt = $mysqli->prepare('INSERT INTO bookings (reference_id, full_name, email, phone, preferred_clinic, service, preferred_date, preferred_time, message, reschedule_token, token_expires_at, terms_accepted) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
$full_name = 'Test User';
$email = 'test@example.com';
$phone = '91234567';
$clinic = 'Thomson';
$service = 'General Checkup';
$date = '2025-01-25';
$time = '10:00';
$message = 'Test booking';
$terms_accepted = 1;
$stmt->bind_param('sssssssssssi', $reference_id, $full_name, $email, $phone, $clinic, $service, $date, $time, $message, $reschedule_token, $token_expires_at, $terms_accepted);

if ($stmt->execute()) {
    $booking_id = $mysqli->insert_id;
    echo "✓ Test booking created!\n";
    echo "Reference ID: $reference_id\n";
    echo "Token: " . substr($reschedule_token, 0, 16) . "...\n\n";
    
    // Test 2: Test the get_booking.php endpoint
    echo "2. Testing confirmation lookup...\n";
    
    // Simulate GET request
    $_GET = ['ref' => $reference_id, 'token' => $reschedule_token];
    
    ob_start();
    include 'get_booking.php';
    $response = ob_get_clean();
    
    echo "Response: " . $response . "\n";
    
    $data = json_decode($response, true);
    if ($data && $data['success']) {
        echo "✓ Confirmation lookup successful!\n";
        echo "Booking found: " . $data['booking']['full_name'] . "\n";
        echo "Email: " . $data['booking']['email'] . "\n";
        echo "Clinic: " . $data['booking']['preferred_clinic'] . "\n";
    } else {
        echo "✗ Confirmation lookup failed!\n";
        echo "Error: " . ($data['error'] ?? 'Unknown error') . "\n";
    }
    
    // Test 3: Test with wrong token
    echo "\n3. Testing with wrong token...\n";
    $_GET = ['ref' => $reference_id, 'token' => 'wrong_token'];
    
    ob_start();
    include 'get_booking.php';
    $response = ob_get_clean();
    
    echo "Response: " . $response . "\n";
    
    $data = json_decode($response, true);
    if (!$data || !$data['success']) {
        echo "✓ Correctly rejected wrong token\n";
    } else {
        echo "✗ Should have rejected wrong token\n";
    }
    
    // Clean up
    $mysqli->query("DELETE FROM bookings WHERE id = $booking_id");
    echo "\n✓ Test booking cleaned up\n";
    
} else {
    echo "✗ Failed to create test booking: " . $mysqli->error . "\n";
}
?>
