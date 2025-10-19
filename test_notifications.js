const axios = require('axios');
const dotenv = require('dotenv');

// Load environment variables
dotenv.config();

const BASE_URL = `http://localhost:${process.env.PORT || 3001}`;

// Test data
const testBookingData = {
  to: 'test@example.com',
  template_id: 'booking_created',
  variables: {
    reference_id: 'SB123456',
    full_name: 'John Doe',
    email: 'test@example.com',
    phone: '+6598765432',
    clinic: 'Novena',
    service: 'General Checkup',
    date: '2025-01-20',
    time: '14:30:00',
    message: 'Regular checkup appointment',
    reschedule_token: 'test-token-123',
    booking_id: 123,
    view_url: 'https://smilebrightdental.sg/confirm.php?ref=SB123456&token=test-token-123',
    cancel_url: 'https://smilebrightdental.sg/cancel.php?ref=SB123456&token=test-token-123',
    token_expiry_date: 'Feb 19, 2025'
  }
};

const testClinicAdjustedData = {
  to: 'test@example.com',
  template_id: 'clinic_adjusted',
  variables: {
    reference_id: 'SB123456',
    full_name: 'John Doe',
    email: 'test@example.com',
    phone: '+6598765432',
    clinic: 'Tampines',
    service: 'General Checkup',
    date: '2025-01-21',
    time: '15:30:00',
    old_date: '2025-01-20',
    old_time: '14:30:00',
    old_clinic: 'Novena',
    reason: 'Schedule conflict',
    view_url: 'https://smilebrightdental.sg/confirm.php?ref=SB123456&token=test-token-123'
  }
};

const testRescheduleData = {
  to: 'test@example.com',
  template_id: 'rescheduled_by_client',
  variables: {
    reference_id: 'SB123456',
    full_name: 'John Doe',
    email: 'test@example.com',
    phone: '+6598765432',
    clinic: 'Novena',
    service: 'General Checkup',
    date: '2025-01-22',
    time: '16:30:00',
    view_url: 'https://smilebrightdental.sg/confirm.php?ref=SB123456&token=test-token-123'
  }
};

async function testHealthCheck() {
  console.log('🏥 Testing health check...');
  try {
    const response = await axios.get(`${BASE_URL}/health`);
    console.log('✅ Health check passed:', response.data);
    return true;
  } catch (error) {
    console.log('❌ Health check failed:', error.message);
    return false;
  }
}

async function testBookingCreated() {
  console.log('\n📧 Testing booking created notification...');
  try {
    const response = await axios.post(`${BASE_URL}/api/notifications/booking-created`, testBookingData);
    console.log('✅ Booking created notification sent:', response.data);
    return true;
  } catch (error) {
    console.log('❌ Booking created notification failed:', error.response?.data || error.message);
    return false;
  }
}

async function testClinicAdjusted() {
  console.log('\n📅 Testing clinic adjustment notification...');
  try {
    const response = await axios.post(`${BASE_URL}/api/notifications/clinic-adjusted`, testClinicAdjustedData);
    console.log('✅ Clinic adjustment notification sent:', response.data);
    return true;
  } catch (error) {
    console.log('❌ Clinic adjustment notification failed:', error.response?.data || error.message);
    return false;
  }
}

async function testRescheduleConfirmed() {
  console.log('\n🔄 Testing reschedule confirmation notification...');
  try {
    const response = await axios.post(`${BASE_URL}/api/notifications/rescheduled-by-client`, testRescheduleData);
    console.log('✅ Reschedule confirmation notification sent:', response.data);
    return true;
  } catch (error) {
    console.log('❌ Reschedule confirmation notification failed:', error.response?.data || error.message);
    return false;
  }
}

async function testWebhookHealth() {
  console.log('\n🔗 Testing webhook health...');
  try {
    const response = await axios.get(`${BASE_URL}/webhooks/health`);
    console.log('✅ Webhook health check passed:', response.data);
    return true;
  } catch (error) {
    console.log('❌ Webhook health check failed:', error.response?.data || error.message);
    return false;
  }
}

async function testMessageStatus() {
  console.log('\n📊 Testing message status endpoint...');
  try {
    const response = await axios.get(`${BASE_URL}/webhooks/status/test-message-123`);
    console.log('✅ Message status endpoint working:', response.data);
    return true;
  } catch (error) {
    console.log('❌ Message status endpoint failed:', error.response?.data || error.message);
    return false;
  }
}

async function runAllTests() {
  console.log('🚀 Starting SmileBright Notification System Tests\n');
  console.log('=' .repeat(60));
  
  const results = {
    healthCheck: await testHealthCheck(),
    bookingCreated: await testBookingCreated(),
    clinicAdjusted: await testClinicAdjusted(),
    rescheduleConfirmed: await testRescheduleConfirmed(),
    webhookHealth: await testWebhookHealth(),
    messageStatus: await testMessageStatus()
  };
  
  console.log('\n' + '=' .repeat(60));
  console.log('📊 TEST RESULTS SUMMARY');
  console.log('=' .repeat(60));
  
  const passed = Object.values(results).filter(Boolean).length;
  const total = Object.keys(results).length;
  
  for (const [test, result] of Object.entries(results)) {
    const status = result ? '✅ PASS' : '❌ FAIL';
    console.log(`${status} ${test}`);
  }
  
  console.log('\n' + '=' .repeat(60));
  console.log(`🎯 Overall: ${passed}/${total} tests passed`);
  
  if (passed === total) {
    console.log('🎉 All tests passed! Your notification system is working correctly.');
  } else {
    console.log('⚠️  Some tests failed. Check the configuration and try again.');
    console.log('\nTroubleshooting tips:');
    console.log('1. Make sure Node.js service is running: npm start');
    console.log('2. Check your .env configuration');
    console.log('3. Verify SMTP and messaging provider credentials');
    console.log('4. Check the logs: tail -f logs/combined.log');
  }
  
  console.log('=' .repeat(60));
}

// Run tests if this file is executed directly
if (require.main === module) {
  runAllTests().catch(console.error);
}

module.exports = {
  testHealthCheck,
  testBookingCreated,
  testClinicAdjusted,
  testRescheduleConfirmed,
  testWebhookHealth,
  testMessageStatus,
  runAllTests
};
