<?php
// Test the full booking flow
require __DIR__ . '/db.php';

echo "Testing Full Booking Flow\n";
echo "========================\n\n";

// Simulate POST data
$_POST = [
    'firstName' => 'Jane',
    'lastName' => 'Smith', 
    'email' => 'jane.smith@example.com',
    'phone' => '98765432',
    'clinic' => 'Orchard',
    'service' => 'Cleaning',
    'date' => '2025-01-20',
    'time' => '14:00',
    'message' => 'Regular cleaning appointment',
    'termsAccepted' => '1',
    'consent' => '1'
];

echo "1. Submitting booking...\n";

// Capture output from showpost.php
ob_start();
include 'showpost.php';
$response = ob_get_clean();

echo "Response: " . $response . "\n";

// Parse the JSON response
$data = json_decode($response, true);
if ($data && $data['ok']) {
    echo "✓ Booking created successfully!\n";
    echo "Reference ID: " . $data['reference_id'] . "\n";
    echo "Confirmation URL: " . $data['confirmation_url'] . "\n\n";
    
    echo "2. Testing confirmation lookup...\n";
    
    // Extract token from confirmation URL
    $token = '';
    if (preg_match('/token=([a-f0-9]+)/', $data['confirmation_url'], $matches)) {
        $token = $matches[1];
    }
    
    echo "Testing: get_booking.php?ref={$data['reference_id']}&token={$token}\n";
    
    // Test the get_booking.php endpoint
    $ref = $data['reference_id'];
    
    // Simulate GET request
    $_GET = ['ref' => $ref, 'token' => $token];
    
    ob_start();
    include 'get_booking.php';
    $booking_response = ob_get_clean();
    
    echo "Booking lookup response: " . $booking_response . "\n";
    
    $booking_data = json_decode($booking_response, true);
    if ($booking_data && $booking_data['success']) {
        echo "✓ Confirmation lookup successful!\n";
        echo "Booking found: " . $booking_data['booking']['full_name'] . "\n";
    } else {
        echo "✗ Confirmation lookup failed!\n";
        echo "Error: " . ($booking_data['error'] ?? 'Unknown error') . "\n";
        
        // Debug: Check what's in the database
        echo "\n3. Debugging database...\n";
        $stmt = $mysqli->prepare("SELECT id, reference_id, reschedule_token, token_expires_at FROM bookings WHERE reference_id = ?");
        $stmt->bind_param('s', $ref);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            echo "Database record found:\n";
            echo "ID: " . $row['id'] . "\n";
            echo "Reference ID: " . $row['reference_id'] . "\n";
            echo "Token (first 16 chars): " . substr($row['reschedule_token'], 0, 16) . "...\n";
            echo "Token expires: " . $row['token_expires_at'] . "\n";
            echo "Expected token: " . substr($token, 0, 16) . "...\n";
        } else {
            echo "No database record found for reference ID: $ref\n";
        }
    }
    
} else {
    echo "✗ Booking creation failed!\n";
    echo "Response: " . $response . "\n";
}
?>
