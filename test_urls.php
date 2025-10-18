<?php
// Generate test URLs for manual testing
require __DIR__ . '/db.php';

echo "Find My Booking - Test URLs\n";
echo "===========================\n\n";

// Get the latest booking
$result = $mysqli->query("SELECT * FROM bookings ORDER BY id DESC LIMIT 1");
if ($result && $result->num_rows > 0) {
    $booking = $result->fetch_assoc();
    
    echo "Latest booking details:\n";
    echo "Reference ID: " . $booking['reference_id'] . "\n";
    echo "Email: " . $booking['email'] . "\n";
    echo "Full Name: " . $booking['full_name'] . "\n";
    echo "Token: " . substr($booking['reschedule_token'], 0, 16) . "...\n\n";
    
    echo "Test URLs:\n";
    echo "==========\n";
    echo "1. Find Booking Page:\n";
    echo "   http://localhost/SmileBright/find-booking.php\n\n";
    
    echo "2. Confirmation Page (direct):\n";
    echo "   http://localhost/SmileBright/confirm.php?ref=" . $booking['reference_id'] . "&token=" . $booking['reschedule_token'] . "\n\n";
    
    echo "3. Test the flow:\n";
    echo "   a) Go to: http://localhost/SmileBright/find-booking.php\n";
    echo "   b) Enter Reference ID: " . $booking['reference_id'] . "\n";
    echo "   c) Enter Email: " . $booking['email'] . "\n";
    echo "   d) Click 'Find My Booking'\n";
    echo "   e) Should redirect to the confirmation page above\n\n";
    
    echo "If you get a 404, check:\n";
    echo "- The exact URL in the address bar\n";
    echo "- Whether confirm.php exists in the SmileBright folder\n";
    echo "- Whether the token is complete (64 characters)\n";
    
} else {
    echo "No bookings found. Please create a booking first.\n";
}
?>
