<?php
// Test the actual Find My Booking form submission
require __DIR__ . '/db.php';

echo "Testing Find My Booking Form Submission\n";
echo "======================================\n\n";

// Get the latest booking
$result = $mysqli->query("SELECT * FROM bookings ORDER BY id DESC LIMIT 1");
if ($result && $result->num_rows > 0) {
    $booking = $result->fetch_assoc();
    
    echo "Testing with latest booking:\n";
    echo "Reference ID: " . $booking['reference_id'] . "\n";
    echo "Email: " . $booking['email'] . "\n\n";
    
    // Simulate form submission to find_booking_api.php
    echo "1. Simulating form submission...\n";
    
    $_POST = [
        'referenceId' => $booking['reference_id'],
        'email' => $booking['email']
    ];
    $_SERVER['REQUEST_METHOD'] = 'POST';
    
    // Capture output
    ob_start();
    include 'find_booking_api.php';
    $response = ob_get_clean();
    
    echo "API Response: " . $response . "\n";
    
    $data = json_decode($response, true);
    if ($data && $data['success']) {
        echo "✓ Form submission successful!\n";
        echo "Booking found: " . $data['booking']['full_name'] . "\n";
        
        // Generate the redirect URL that the form would use
        $redirect_url = "confirm.php?ref=" . $data['booking']['reference_id'] . "&token=" . $data['booking']['reschedule_token'];
        echo "\nRedirect URL that would be used:\n";
        echo "http://localhost/SmileBright/" . $redirect_url . "\n\n";
        
        echo "2. Testing if this URL works...\n";
        
        // Test the URL by calling get_booking.php directly
        $_GET = [
            'ref' => $data['booking']['reference_id'],
            'token' => $data['booking']['reschedule_token']
        ];
        
        ob_start();
        include 'get_booking.php';
        $confirm_response = ob_get_clean();
        
        echo "Confirm page response: " . $confirm_response . "\n";
        
        $confirm_data = json_decode($confirm_response, true);
        if ($confirm_data && $confirm_data['success']) {
            echo "✓ Redirect URL works perfectly!\n";
            echo "Confirmation page would load successfully.\n";
        } else {
            echo "✗ Redirect URL failed!\n";
            echo "Error: " . ($confirm_data['error'] ?? 'Unknown error') . "\n";
        }
        
    } else {
        echo "✗ Form submission failed!\n";
        echo "Error: " . ($data['error'] ?? 'Unknown error') . "\n";
    }
    
} else {
    echo "No bookings found. Please create a booking first.\n";
}
?>
