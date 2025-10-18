<?php
// Test the complete Find My Booking flow
require __DIR__ . '/db.php';

echo "Testing Complete Find My Booking Flow\n";
echo "====================================\n\n";

// Get the latest booking
$result = $mysqli->query("SELECT * FROM bookings ORDER BY id DESC LIMIT 1");
if ($result && $result->num_rows > 0) {
    $booking = $result->fetch_assoc();
    
    echo "Testing with latest booking:\n";
    echo "Reference ID: " . $booking['reference_id'] . "\n";
    echo "Email: " . $booking['email'] . "\n";
    echo "Token: " . substr($booking['reschedule_token'], 0, 16) . "...\n\n";
    
    // Test 1: Test find_booking_api.php
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
        echo "✓ Find booking API successful!\n";
        echo "Found booking: " . $data['booking']['full_name'] . "\n";
        
        // Test 2: Test the redirect URL that would be generated
        echo "\n2. Testing redirect URL...\n";
        $redirect_url = "confirm.php?ref=" . $data['booking']['reference_id'] . "&token=" . $data['booking']['reschedule_token'];
        echo "Redirect URL: " . $redirect_url . "\n";
        
        // Test 3: Test if confirm.php can handle this URL
        echo "\n3. Testing confirm.php with redirect parameters...\n";
        $_GET = [
            'ref' => $data['booking']['reference_id'],
            'token' => $data['booking']['reschedule_token']
        ];
        
        ob_start();
        include 'get_booking.php';
        $confirm_response = ob_get_clean();
        
        echo "Confirm lookup response: " . $confirm_response . "\n";
        
        $confirm_data = json_decode($confirm_response, true);
        if ($confirm_data && $confirm_data['success']) {
            echo "✓ Confirm page lookup successful!\n";
            echo "Booking details loaded: " . $confirm_data['booking']['full_name'] . "\n";
        } else {
            echo "✗ Confirm page lookup failed!\n";
            echo "Error: " . ($confirm_data['error'] ?? 'Unknown error') . "\n";
        }
        
    } else {
        echo "✗ Find booking API failed!\n";
        echo "Error: " . ($data['error'] ?? 'Unknown error') . "\n";
    }
    
} else {
    echo "No bookings found. Please create a booking first.\n";
}
?>
