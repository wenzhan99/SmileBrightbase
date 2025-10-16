<?php
/**
 * Test Script for Email Confirmation System
 * Run this to send a test email and verify the system is working
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "============================================\n";
echo "Smile Bright Dental - Email System Test\n";
echo "============================================\n\n";

// Include required files
require_once __DIR__ . '/email_config.php';
require_once __DIR__ . '/send_email.php';

// Test configuration loading
echo "✓ Configuration loaded\n";
echo "  - Email From: " . EMAIL_FROM . "\n";
echo "  - Support Email: " . SUPPORT_EMAIL . "\n";
echo "  - Timezone: " . TIMEZONE . "\n\n";

// Test clinic address function
echo "Testing clinic address mapping...\n";
$clinics = ['Novena', 'Tampines', 'Jurong East', 'Woodlands', 'Punggol'];
foreach ($clinics as $clinic) {
    $info = getClinicInfo($clinic);
    echo "  ✓ {$clinic}: {$info['address']}\n";
}
echo "\n";

// Prepare test appointment data
echo "Preparing test appointment data...\n";
$testData = [
    'id' => 99999,
    'first_name' => 'Test',
    'last_name' => 'Patient',
    'email' => 'your-email@example.com', // CHANGE THIS TO YOUR EMAIL
    'phone' => '+65 9123 4567',
    'date' => date('Y-m-d', strtotime('+7 days')), // 7 days from now
    'time' => '14:30:00',
    'clinic' => 'Novena',
    'service' => 'Scaling & Polishing',
    'experience' => 'First time patient, regular dental checkups every 6 months',
    'message' => 'Prefer afternoon appointments if possible',
    'reschedule_token' => generateRescheduleToken(),
    'token_expires_at' => getTokenExpiryDate(),
    'created_at' => date('Y-m-d H:i:s')
];

echo "✓ Test data prepared\n";
echo "  - Appointment ID: #{$testData['id']}\n";
echo "  - Patient: {$testData['first_name']} {$testData['last_name']}\n";
echo "  - Email: {$testData['email']}\n";
echo "  - Date: {$testData['date']} at {$testData['time']}\n";
echo "  - Clinic: {$testData['clinic']}\n";
echo "  - Service: {$testData['service']}\n\n";

// Generate URLs
$rescheduleUrl = getRescheduleUrl($testData['id'], $testData['reschedule_token']);
$cancelUrl = getCancelUrl($testData['id'], $testData['reschedule_token']);

echo "Generated URLs:\n";
echo "  - Reschedule: {$rescheduleUrl}\n";
echo "  - Cancel: {$cancelUrl}\n\n";

// Confirm before sending
echo "============================================\n";
echo "IMPORTANT: Update the email address above!\n";
echo "============================================\n\n";

if ($testData['email'] === 'your-email@example.com') {
    echo "❌ ERROR: Please change the test email address in this script!\n";
    echo "   Edit line 33 and replace 'your-email@example.com' with your actual email.\n\n";
    exit(1);
}

echo "Ready to send test email to: {$testData['email']}\n";
echo "\nSending email...\n";

// Send the email
$success = sendBookingConfirmation($testData);

echo "\n============================================\n";
if ($success) {
    echo "✅ SUCCESS: Test email sent!\n\n";
    echo "Check your inbox (and spam folder) for:\n";
    echo "  To: {$testData['email']}\n";
    echo "  Subject: ✔ Appointment booked — " . formatEmailDate($testData['date']) . " " . formatEmailTime($testData['time']) . " at {$testData['clinic']}\n\n";
    echo "If you don't receive it:\n";
    echo "  1. Check your spam/junk folder\n";
    echo "  2. Verify PHP mail() is configured (see EMAIL_SETUP_GUIDE.md)\n";
    echo "  3. Check PHP error logs for mail errors\n";
    echo "  4. Try using a different email provider (Gmail, Outlook, etc.)\n";
} else {
    echo "❌ FAILED: Email could not be sent\n\n";
    echo "Troubleshooting steps:\n";
    echo "  1. Check PHP error logs for mail errors\n";
    echo "  2. Verify php.ini mail configuration\n";
    echo "  3. For XAMPP, check sendmail.ini configuration\n";
    echo "  4. Ensure your SMTP credentials are correct\n";
    echo "  5. See EMAIL_SETUP_GUIDE.md for detailed setup instructions\n";
}
echo "============================================\n";









