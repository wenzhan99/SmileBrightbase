const messagingService = require('./services/messagingService');
const logger = require('./utils/logger');

async function testMessagingConfiguration() {
  console.log('📱 Testing Messaging Configuration...');
  
  try {
    const result = await messagingService.testConfiguration();
    
    if (result.success) {
      console.log('✅ Messaging configuration is valid');
      console.log('📋 Results:', result.results);
      return true;
    } else {
      console.log('❌ Messaging configuration test failed');
      console.log('📋 Error:', result.message);
      return false;
    }
  } catch (error) {
    console.log('❌ Messaging configuration test failed with exception');
    console.log('📋 Error:', error.message);
    return false;
  }
}

async function testPhoneNumberFormatting() {
  console.log('\n📞 Testing Phone Number Formatting...');
  
  const testNumbers = [
    '98765432',           // Singapore local
    '+6598765432',        // Singapore with country code
    '6598765432',         // Singapore without +
    '1234567890',         // US number
    '+1234567890',        // US with country code
    'invalid',            // Invalid number
    ''                    // Empty
  ];

  let allPassed = true;

  for (const number of testNumbers) {
    try {
      const formatted = messagingService.formatPhoneNumber(number);
      console.log(`📱 ${number} → ${formatted || 'INVALID'}`);
      
      if (number === 'invalid' || number === '') {
        if (formatted === null) {
          console.log('✅ Correctly identified as invalid');
        } else {
          console.log('❌ Should be invalid but got:', formatted);
          allPassed = false;
        }
      } else {
        if (formatted && formatted.startsWith('+')) {
          console.log('✅ Correctly formatted');
        } else {
          console.log('❌ Should be valid E.164 format');
          allPassed = false;
        }
      }
    } catch (error) {
      console.log(`❌ Error formatting ${number}:`, error.message);
      allPassed = false;
    }
  }

  return allPassed;
}

async function testMessageTemplates() {
  console.log('\n📝 Testing Message Templates...');
  
  const testData = {
    reference_id: 'SB123456',
    full_name: 'John Doe',
    phone: '+6598765432',
    clinic: 'Novena',
    service: 'General Checkup',
    date: '2025-01-20',
    time: '14:30:00',
    old_date: '2025-01-19',
    old_time: '13:30:00',
    view_url: 'https://smilebrightdental.sg/ref/SB123456'
  };

  const templates = ['booking_created_sms', 'clinic_adjusted_sms', 'rescheduled_by_client_sms'];
  let allPassed = true;

  for (const template of templates) {
    try {
      console.log(`\n🧪 Testing template: ${template}`);
      
      // Test template rendering
      const templateVars = messagingService.prepareTemplateVariables(testData);
      const templateObj = messagingService.templates.get(template);
      
      if (!templateObj) {
        console.log(`❌ Template ${template} not found`);
        allPassed = false;
        continue;
      }

      const rendered = messagingService.renderTemplate(templateObj.text, templateVars);
      console.log('✅ Template rendered successfully');
      console.log('📋 Rendered text:', rendered);
      console.log('📋 Length:', rendered.length, 'characters');
      
      if (rendered.length > templateObj.maxLength) {
        console.log(`⚠️  Template exceeds recommended length (${templateObj.maxLength} chars)`);
      }
      
    } catch (error) {
      console.log(`❌ Template ${template} failed:`, error.message);
      allPassed = false;
    }
  }

  return allPassed;
}

async function testSMSending() {
  console.log('\n📤 Testing SMS Sending...');
  
  // Only test if we have a valid messaging configuration
  const configTest = await messagingService.testConfiguration();
  if (!configTest.success) {
    console.log('⚠️  Skipping SMS sending test due to configuration issues');
    return false;
  }

  const testPhone = process.env.TEST_PHONE || '+6598765432';
  
  if (testPhone === '+6598765432') {
    console.log('⚠️  Using test phone number. Set TEST_PHONE in .env to test with real number.');
  }

  try {
    const result = await messagingService.sendMessage({
      to: testPhone,
      template_id: 'booking_created_sms',
      variables: {
        reference_id: 'SB123456',
        full_name: 'Test User',
        phone: testPhone,
        clinic: 'Novena',
        service: 'General Checkup',
        date: '2025-01-20',
        time: '14:30:00',
        view_url: 'https://smilebrightdental.sg/ref/SB123456'
      }
    });

    if (result.success) {
      console.log('✅ Test SMS sent successfully');
      console.log('📋 Message ID:', result.messageId);
      console.log('📋 Channel:', result.channel);
      console.log('📋 Status:', result.status);
      console.log('📋 Provider:', result.provider);
      return true;
    } else {
      console.log('❌ Test SMS failed');
      return false;
    }
  } catch (error) {
    console.log('❌ Test SMS failed with exception');
    console.log('📋 Error:', error.message);
    return false;
  }
}

async function testWhatsAppSending() {
  console.log('\n💬 Testing WhatsApp Sending...');
  
  // Only test if WhatsApp is enabled
  if (process.env.ENABLE_WHATSAPP !== 'true') {
    console.log('⚠️  WhatsApp is disabled in configuration');
    return true; // Not a failure, just disabled
  }

  const configTest = await messagingService.testConfiguration();
  if (!configTest.success) {
    console.log('⚠️  Skipping WhatsApp sending test due to configuration issues');
    return false;
  }

  const testPhone = process.env.TEST_PHONE || '+6598765432';
  
  try {
    const result = await messagingService.sendMessage({
      to: testPhone,
      template_id: 'booking_created_wa',
      variables: {
        reference_id: 'SB123456',
        full_name: 'Test User',
        phone: testPhone,
        clinic: 'Novena',
        service: 'General Checkup',
        date: '2025-01-20',
        time: '14:30:00',
        view_url: 'https://smilebrightdental.sg/ref/SB123456'
      }
    });

    if (result.success) {
      console.log('✅ Test WhatsApp sent successfully');
      console.log('📋 Message ID:', result.messageId);
      console.log('📋 Channel:', result.channel);
      console.log('📋 Status:', result.status);
      console.log('📋 Provider:', result.provider);
      return true;
    } else {
      console.log('❌ Test WhatsApp failed');
      return false;
    }
  } catch (error) {
    console.log('❌ Test WhatsApp failed with exception');
    console.log('📋 Error:', error.message);
    return false;
  }
}

async function runSMSTests() {
  console.log('🚀 Starting SMS/WhatsApp Service Tests\n');
  console.log('=' .repeat(50));
  
  const results = {
    configuration: await testMessagingConfiguration(),
    phoneFormatting: await testPhoneNumberFormatting(),
    templates: await testMessageTemplates(),
    smsSending: await testSMSending(),
    whatsappSending: await testWhatsAppSending()
  };
  
  console.log('\n' + '=' .repeat(50));
  console.log('📊 MESSAGING TEST RESULTS');
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
    console.log('🎉 All messaging tests passed!');
  } else {
    console.log('⚠️  Some messaging tests failed.');
    console.log('\nTroubleshooting tips:');
    console.log('1. Check your messaging provider configuration in .env');
    console.log('2. Verify Twilio credentials (if using Twilio)');
    console.log('3. Check account balance with your provider');
    console.log('4. Verify phone number format (E.164)');
    console.log('5. Check if messaging is enabled: ENABLE_SMS=true, ENABLE_WHATSAPP=true');
  }
  
  console.log('=' .repeat(50));
}

// Run tests if this file is executed directly
if (require.main === module) {
  runSMSTests().catch(console.error);
}

module.exports = {
  testMessagingConfiguration,
  testPhoneNumberFormatting,
  testMessageTemplates,
  testSMSending,
  testWhatsAppSending,
  runSMSTests
};
