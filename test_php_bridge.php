<?php
/**
 * Test PHP Bridge Integration
 * Tests the connection between PHP and Node.js notification service
 */

require_once 'notification_bridge.php';

function testNotificationBridge() {
    echo "🚀 Testing PHP Notification Bridge\n";
    echo str_repeat('=', 50) . "\n";
    
    $bridge = new NotificationBridge();
    
    // Test 1: Connection to Node.js service
    echo "\n🏥 Testing connection to Node.js service...\n";
    $connectionResult = $bridge->testConnection();
    
    if ($connectionResult['success']) {
        echo "✅ Connection successful!\n";
        echo "📋 Service status: " . $connectionResult['data']['status'] . "\n";
        echo "📋 Services enabled: " . json_encode($connectionResult['data']['services']) . "\n";
    } else {
        echo "❌ Connection failed: " . $connectionResult['error'] . "\n";
        echo "\nTroubleshooting tips:\n";
        echo "1. Make sure Node.js service is running: npm start\n";
        echo "2. Check if port 3001 is accessible\n";
        echo "3. Verify firewall settings\n";
        return false;
    }
    
    // Test 2: Test booking notification
    echo "\n📧 Testing booking notification...\n";
    $testBookingData = [
        'id' => 123,
        'reference_id' => 'SB123456',
        'full_name' => 'Test User',
        'email' => 'test@example.com',
        'phone' => '+6598765432',
        'preferred_clinic' => 'Novena',
        'service' => 'General Checkup',
        'preferred_date' => '2025-01-20',
        'preferred_time' => '14:30:00',
        'message' => 'Test booking from PHP bridge',
        'reschedule_token' => 'test-token-123',
        'token_expires_at' => date('Y-m-d H:i:s', strtotime('+30 days'))
    ];
    
    $notificationResult = $bridge->sendBookingCreated($testBookingData);
    
    if ($notificationResult) {
        echo "✅ Booking notification sent successfully!\n";
        echo "📋 Response: " . json_encode($notificationResult) . "\n";
    } else {
        echo "❌ Booking notification failed\n";
        echo "Check the logs for more details\n";
    }
    
    // Test 3: Test clinic adjustment notification
    echo "\n📅 Testing clinic adjustment notification...\n";
    $testAdjustedData = [
        'id' => 123,
        'reference_id' => 'SB123456',
        'full_name' => 'Test User',
        'email' => 'test@example.com',
        'phone' => '+6598765432',
        'preferred_clinic' => 'Tampines',
        'service' => 'General Checkup',
        'preferred_date' => '2025-01-21',
        'preferred_time' => '15:30:00',
        'message' => 'Test clinic adjustment',
        'reschedule_token' => 'test-token-123',
        'token_expires_at' => date('Y-m-d H:i:s', strtotime('+30 days'))
    ];
    
    $oldData = [
        'preferred_date' => '2025-01-20',
        'preferred_time' => '14:30:00',
        'preferred_clinic' => 'Novena',
        'reason' => 'Schedule conflict'
    ];
    
    $adjustmentResult = $bridge->sendClinicAdjusted($testAdjustedData, $oldData);
    
    if ($adjustmentResult) {
        echo "✅ Clinic adjustment notification sent successfully!\n";
        echo "📋 Response: " . json_encode($adjustmentResult) . "\n";
    } else {
        echo "❌ Clinic adjustment notification failed\n";
    }
    
    // Test 4: Test reschedule confirmation
    echo "\n🔄 Testing reschedule confirmation...\n";
    $testRescheduleData = [
        'id' => 123,
        'reference_id' => 'SB123456',
        'full_name' => 'Test User',
        'email' => 'test@example.com',
        'phone' => '+6598765432',
        'preferred_clinic' => 'Novena',
        'service' => 'General Checkup',
        'preferred_date' => '2025-01-22',
        'preferred_time' => '16:30:00',
        'message' => 'Test reschedule confirmation',
        'reschedule_token' => 'test-token-123',
        'token_expires_at' => date('Y-m-d H:i:s', strtotime('+30 days'))
    ];
    
    $rescheduleResult = $bridge->sendRescheduleConfirmed($testRescheduleData);
    
    if ($rescheduleResult) {
        echo "✅ Reschedule confirmation sent successfully!\n";
        echo "📋 Response: " . json_encode($rescheduleResult) . "\n";
    } else {
        echo "❌ Reschedule confirmation failed\n";
    }
    
    // Test 5: Test helper function
    echo "\n🔧 Testing helper function...\n";
    $helperResult = sendBookingNotification($testBookingData, 'booking_created');
    
    if ($helperResult) {
        echo "✅ Helper function works correctly!\n";
    } else {
        echo "❌ Helper function failed\n";
    }
    
    // Summary
    echo "\n" . str_repeat('=', 50) . "\n";
    echo "📊 PHP Bridge Test Summary\n";
    echo str_repeat('=', 50) . "\n";
    
    $tests = [
        'Connection' => $connectionResult['success'],
        'Booking Notification' => $notificationResult !== false,
        'Clinic Adjustment' => $adjustmentResult !== false,
        'Reschedule Confirmation' => $rescheduleResult !== false,
        'Helper Function' => $helperResult !== false
    ];
    
    $passed = array_sum($tests);
    $total = count($tests);
    
    foreach ($tests as $test => $result) {
        $status = $result ? '✅ PASS' : '❌ FAIL';
        echo "$status $test\n";
    }
    
    echo "\n🎯 Overall: $passed/$total tests passed\n";
    
    if ($passed === $total) {
        echo "🎉 All PHP bridge tests passed!\n";
        echo "Your PHP to Node.js integration is working correctly.\n";
    } else {
        echo "⚠️  Some tests failed. Check the configuration and try again.\n";
    }
    
    echo str_repeat('=', 50) . "\n";
    
    return $passed === $total;
}

// Run the test if this file is executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    testNotificationBridge();
}
?>
