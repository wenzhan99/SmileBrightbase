<?php
// Test script to verify booking submission works
require __DIR__ . '/db.php';

echo "Testing booking submission...\n";

// Simulate POST data
$_POST = [
    'firstName' => 'John',
    'lastName' => 'Doe', 
    'email' => 'john.doe@example.com',
    'phone' => '91234567',
    'clinic' => 'Thomson',
    'service' => 'General Checkup',
    'date' => '2025-01-15',
    'time' => '10:00',
    'message' => 'Test booking',
    'termsAccepted' => '1',
    'consent' => '1'
];

// Capture output
ob_start();
include 'showpost.php';
$output = ob_get_clean();

echo "Response: " . $output . "\n";

// Check if booking was created
$result = $mysqli->query("SELECT * FROM bookings ORDER BY id DESC LIMIT 1");
if ($result && $result->num_rows > 0) {
    $booking = $result->fetch_assoc();
    echo "✓ Booking created successfully!\n";
    echo "Reference ID: " . $booking['reference_id'] . "\n";
    echo "Reschedule Token: " . substr($booking['reschedule_token'], 0, 16) . "...\n";
    echo "Terms Accepted: " . ($booking['terms_accepted'] ? 'Yes' : 'No') . "\n";
} else {
    echo "✗ No booking found\n";
}
?>
