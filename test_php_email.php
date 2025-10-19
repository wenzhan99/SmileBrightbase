<?php
/**
 * Test PHP Email Service with Gmail SMTP
 * Tests the PHPMailer integration with secure Gmail configuration
 */

require_once 'php_email_service.php';

function testPHPEmailService() {
    echo "🔐 Testing PHP Email Service with Gmail SMTP\n";
    echo str_repeat('=', 60) . "\n";
    
    // Test 1: Configuration Test
    echo "\n📧 Testing Gmail SMTP Configuration...\n";
    $configTest = testEmailConfiguration();
    
    if ($configTest['success']) {
        echo "✅ Gmail SMTP configuration test PASSED!\n";
        echo "📋 Details:\n";
        echo "   - Host: {$configTest['config']['host']}\n";
        echo "   - Port: {$configTest['config']['port']}\n";
        echo "   - Secure: " . ($configTest['config']['secure'] ? 'Yes (SSL)' : 'No (STARTTLS)') . "\n";
        echo "   - User: {$configTest['config']['user']}\n";
        echo "   - Test email sent to: {$configTest['to']}\n";
    } else {
        echo "❌ Gmail SMTP configuration test FAILED!\n";
        echo "📋 Error: {$configTest['message']}\n";
        
        echo "\n🔧 Troubleshooting:\n";
        if (strpos($configTest['message'], 'Username and Password not accepted') !== false) {
            echo "   • Verify you're using the NEW Gmail App Password\n";
            echo "   • Remove spaces from the password\n";
            echo "   • Ensure 2-Step Verification is enabled\n";
        } else if (strpos($configTest['message'], 'TLS') !== false || strpos($configTest['message'], 'handshake') !== false) {
            echo "   • Check port/secure configuration\n";
            echo "   • Use 465 + secure=true (or 587 + secure=false)\n";
        } else if (strpos($configTest['message'], 'ENOTFOUND') !== false || strpos($configTest['message'], 'ECONNREFUSED') !== false) {
            echo "   • Check internet connection\n";
            echo "   • Verify firewall/antivirus settings\n";
        }
        
        echo "\n📋 Next steps:\n";
        echo "   1. Check your .env file configuration\n";
        echo "   2. Verify the Gmail App Password\n";
        echo "   3. Test again with: php test_php_email.php\n";
        
        return false;
    }
    
    // Test 2: Booking Confirmation Email
    echo "\n📝 Testing Booking Confirmation Email...\n";
    $testBookingData = [
        'id' => 123,
        'reference_id' => 'SB123456',
        'full_name' => 'Test User',
        'email' => 'test@example.com', // Change this to your test email
        'preferred_clinic' => 'Novena',
        'service' => 'General Checkup',
        'preferred_date' => '2025-01-20',
        'preferred_time' => '14:30:00',
        'message' => 'This is a test booking from PHP email service',
        'reschedule_token' => 'test-token-123'
    ];
    
    $bookingResult = sendBookingEmail($testBookingData);
    
    if ($bookingResult['success']) {
        echo "✅ Booking confirmation email test PASSED!\n";
        echo "📋 Email sent to: {$bookingResult['to']}\n";
        echo "📋 Subject: {$bookingResult['subject']}\n";
    } else {
        echo "❌ Booking confirmation email test FAILED!\n";
        echo "📋 Error: {$bookingResult['message']}\n";
    }
    
    // Test 3: Clinic Adjustment Email
    echo "\n📅 Testing Clinic Adjustment Email...\n";
    try {
        $emailService = new SmileBrightEmailService();
        $adjustmentResult = $emailService->sendClinicAdjustment($testBookingData, [
            'preferred_date' => '2025-01-19',
            'preferred_time' => '13:30:00',
            'preferred_clinic' => 'Tampines',
            'reason' => 'Schedule conflict'
        ]);
        
        if ($adjustmentResult['success']) {
            echo "✅ Clinic adjustment email test PASSED!\n";
            echo "📋 Email sent to: {$adjustmentResult['to']}\n";
        } else {
            echo "❌ Clinic adjustment email test FAILED!\n";
            echo "📋 Error: {$adjustmentResult['message']}\n";
        }
    } catch (Exception $e) {
        echo "❌ Clinic adjustment email test FAILED!\n";
        echo "📋 Error: " . $e->getMessage() . "\n";
    }
    
    // Test 4: Reschedule Confirmation Email
    echo "\n🔄 Testing Reschedule Confirmation Email...\n";
    try {
        $emailService = new SmileBrightEmailService();
        $rescheduleResult = $emailService->sendRescheduleConfirmation($testBookingData);
        
        if ($rescheduleResult['success']) {
            echo "✅ Reschedule confirmation email test PASSED!\n";
            echo "📋 Email sent to: {$rescheduleResult['to']}\n";
        } else {
            echo "❌ Reschedule confirmation email test FAILED!\n";
            echo "📋 Error: {$rescheduleResult['message']}\n";
        }
    } catch (Exception $e) {
        echo "❌ Reschedule confirmation email test FAILED!\n";
        echo "📋 Error: " . $e->getMessage() . "\n";
    }
    
    // Summary
    echo "\n" . str_repeat('=', 60) . "\n";
    echo "📊 PHP Email Service Test Summary\n";
    echo str_repeat('=', 60) . "\n";
    
    $tests = [
        'Gmail SMTP Configuration' => $configTest['success'],
        'Booking Confirmation' => $bookingResult['success'] ?? false,
        'Clinic Adjustment' => $adjustmentResult['success'] ?? false,
        'Reschedule Confirmation' => $rescheduleResult['success'] ?? false
    ];
    
    $passed = array_sum($tests);
    $total = count($tests);
    
    foreach ($tests as $test => $result) {
        $status = $result ? '✅ PASS' : '❌ FAIL';
        echo "$status $test\n";
    }
    
    echo "\n🎯 Overall: $passed/$total tests passed\n";
    
    if ($passed === $total) {
        echo "🎉 All PHP email tests passed!\n";
        echo "Your PHP email service is working correctly with Gmail SMTP.\n";
        echo "\n📧 Check your inbox for test emails\n";
        echo "📧 If not received, check spam folder\n";
    } else {
        echo "⚠️  Some tests failed. Check the configuration and try again.\n";
        echo "\nTroubleshooting tips:\n";
        echo "1. Verify your .env file has the correct Gmail App Password\n";
        echo "2. Ensure 2-Step Verification is enabled on Gmail\n";
        echo "3. Check that OpenSSL extension is enabled in PHP\n";
        echo "4. Restart Apache after any PHP configuration changes\n";
    }
    
    echo str_repeat('=', 60) . "\n";
    
    return $passed === $total;
}

// Run the test if this file is executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    testPHPEmailService();
}
?>
