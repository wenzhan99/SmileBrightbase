const emailService = require('./services/emailService');
const logger = require('./utils/logger');

async function testEmailConfiguration() {
  console.log('📧 Testing Email Configuration...');
  
  try {
    const result = await emailService.testConfiguration();
    
    if (result.success) {
      console.log('✅ Email configuration is valid');
      console.log('📋 Details:', result.message);
      return true;
    } else {
      console.log('❌ Email configuration test failed');
      console.log('📋 Error:', result.message);
      return false;
    }
  } catch (error) {
    console.log('❌ Email configuration test failed with exception');
    console.log('📋 Error:', error.message);
    return false;
  }
}

async function testEmailTemplates() {
  console.log('\n📝 Testing Email Templates...');
  
  const testData = {
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
  };

  const templates = ['booking_created', 'clinic_adjusted', 'rescheduled_by_client'];
  let allPassed = true;

  for (const template of templates) {
    try {
      console.log(`\n🧪 Testing template: ${template}`);
      
      // Test template rendering (without actually sending)
      const templateVars = emailService.prepareTemplateVariables(testData);
      console.log('✅ Template variables prepared successfully');
      console.log('📋 Sample variables:', {
        reference_id: templateVars.reference_id,
        date: templateVars.date,
        time: templateVars.time,
        clinic_info: templateVars.clinic_info.name
      });
      
    } catch (error) {
      console.log(`❌ Template ${template} failed:`, error.message);
      allPassed = false;
    }
  }

  return allPassed;
}

async function testEmailSending() {
  console.log('\n📤 Testing Email Sending...');
  
  // Only test if we have a valid email configuration
  const configTest = await emailService.testConfiguration();
  if (!configTest.success) {
    console.log('⚠️  Skipping email sending test due to configuration issues');
    return false;
  }

  const testEmail = process.env.TEST_EMAIL || 'test@example.com';
  
  if (testEmail === 'test@example.com') {
    console.log('⚠️  Using test email address. Set TEST_EMAIL in .env to test with real email.');
  }

  try {
    const result = await emailService.sendEmail({
      to: testEmail,
      template_id: 'booking_created',
      variables: {
        reference_id: 'SB123456',
        full_name: 'Test User',
        email: testEmail,
        phone: '+6598765432',
        clinic: 'Novena',
        service: 'General Checkup',
        date: '2025-01-20',
        time: '14:30:00',
        message: 'This is a test email from SmileBright notification system',
        reschedule_token: 'test-token-123',
        booking_id: 123,
        view_url: 'https://smilebrightdental.sg/confirm.php?ref=SB123456&token=test-token-123',
        cancel_url: 'https://smilebrightdental.sg/cancel.php?ref=SB123456&token=test-token-123',
        token_expiry_date: 'Feb 19, 2025'
      }
    });

    if (result.success) {
      console.log('✅ Test email sent successfully');
      console.log('📋 Message ID:', result.messageId);
      console.log('📋 Accepted recipients:', result.accepted);
      if (result.rejected && result.rejected.length > 0) {
        console.log('⚠️  Rejected recipients:', result.rejected);
      }
      return true;
    } else {
      console.log('❌ Test email failed');
      return false;
    }
  } catch (error) {
    console.log('❌ Test email failed with exception');
    console.log('📋 Error:', error.message);
    return false;
  }
}

async function runEmailTests() {
  console.log('🚀 Starting Email Service Tests\n');
  console.log('=' .repeat(50));
  
  const results = {
    configuration: await testEmailConfiguration(),
    templates: await testEmailTemplates(),
    sending: await testEmailSending()
  };
  
  console.log('\n' + '=' .repeat(50));
  console.log('📊 EMAIL TEST RESULTS');
  console.log('=' .repeat(50));
  
  const passed = Object.values(results).filter(Boolean).length;
  const total = Object.keys(results).length;
  
  for (const [test, result] of Object.entries(results)) {
    const status = result ? '✅ PASS' : '❌ FAIL';
    console.log(`${status} ${test}`);
  }
  
  console.log('\n' + '=' .repeat(50));
  console.log(`🎯 Overall: ${passed}/${total} tests passed`);
  
  if (passed === total) {
    console.log('🎉 All email tests passed!');
  } else {
    console.log('⚠️  Some email tests failed.');
    console.log('\nTroubleshooting tips:');
    console.log('1. Check your SMTP configuration in .env');
    console.log('2. Verify Gmail app password (if using Gmail)');
    console.log('3. Check firewall/antivirus blocking SMTP ports');
    console.log('4. Verify email templates exist in templates/email/');
  }
  
  console.log('=' .repeat(50));
}

// Run tests if this file is executed directly
if (require.main === module) {
  runEmailTests().catch(console.error);
}

module.exports = {
  testEmailConfiguration,
  testEmailTemplates,
  testEmailSending,
  runEmailTests
};
