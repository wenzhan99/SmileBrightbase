<?php
// Simple test to verify the confirmation URL works
require __DIR__ . '/db.php';

echo "Testing Confirmation URL Generation\n";
echo "===================================\n\n";

// Get the latest booking
$result = $mysqli->query("SELECT * FROM bookings ORDER BY id DESC LIMIT 1");
if ($result && $result->num_rows > 0) {
    $booking = $result->fetch_assoc();
    
    echo "Latest booking found:\n";
    echo "Reference ID: " . $booking['reference_id'] . "\n";
    echo "Token: " . substr($booking['reschedule_token'], 0, 16) . "...\n";
    echo "Full Name: " . $booking['full_name'] . "\n";
    echo "Email: " . $booking['email'] . "\n\n";
    
    echo "Confirmation URL:\n";
    echo "http://localhost/SmileBright/confirm.php?ref=" . $booking['reference_id'] . "&token=" . $booking['reschedule_token'] . "\n\n";
    
    echo "Test this URL in your browser to verify the confirmation page loads correctly.\n";
    
} else {
    echo "No bookings found. Please create a booking first.\n";
}
?>
