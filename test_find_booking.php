<?php
// Test find booking functionality
require __DIR__ . '/db.php';

echo "Testing Find Booking Functionality\n";
echo "==================================\n\n";

// Get the latest booking
$result = $mysqli->query("SELECT * FROM bookings ORDER BY id DESC LIMIT 1");
if ($result && $result->num_rows > 0) {
    $booking = $result->fetch_assoc();
    
    echo "Testing with latest booking:\n";
    echo "Reference ID: " . $booking['reference_id'] . "\n";
    echo "Email: " . $booking['email'] . "\n\n";
    
    // Test find_booking_api.php
    echo "1. Testing find_booking_api.php...\n";
    
    $_POST = [
        'referenceId' => $booking['reference_id'],
        'email' => $booking['email']
    ];
    $_SERVER['REQUEST_METHOD'] = 'POST';
    
    ob_start();
    include 'find_booking_api.php';
    $response = ob_get_clean();
    
    echo "Response: " . $response . "\n";
    
    $data = json_decode($response, true);
    if ($data && $data['success']) {
        echo "✓ Find booking successful!\n";
        echo "Found booking: " . $data['booking']['full_name'] . "\n";
        echo "Redirect URL would be: reschedule.php?ref=" . $data['booking']['reference_id'] . "&token=" . $data['booking']['reschedule_token'] . "\n";
    } else {
        echo "✗ Find booking failed!\n";
        echo "Error: " . ($data['error'] ?? 'Unknown error') . "\n";
    }
    
    echo "\n2. Testing with wrong email...\n";
    $_POST = [
        'referenceId' => $booking['reference_id'],
        'email' => 'wrong@example.com'
    ];
    $_SERVER['REQUEST_METHOD'] = 'POST';
    
    ob_start();
    include 'find_booking_api.php';
    $response = ob_get_clean();
    
    echo "Response: " . $response . "\n";
    
    $data = json_decode($response, true);
    if (!$data || !$data['success']) {
        echo "✓ Correctly rejected wrong email\n";
    } else {
        echo "✗ Should have rejected wrong email\n";
    }
    
} else {
    echo "No bookings found. Please create a booking first.\n";
}
?>
