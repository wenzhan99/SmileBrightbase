const nodemailer = require('nodemailer');
const dotenv = require('dotenv');

// Load environment variables
dotenv.config();

async function testGmailConfiguration() {
  console.log('🔐 Testing Gmail SMTP Configuration...');
  console.log('=' .repeat(50));
  
  // Check if .env file exists
  if (!process.env.SMTP_USER || !process.env.SMTP_PASS) {
    console.log('❌ ERROR: .env file not found or SMTP credentials missing');
    console.log('📋 Please:');
    console.log('   1. Copy env.example to .env');
    console.log('   2. Update SMTP_PASS with your NEW Gmail App Password');
    console.log('   3. Remove spaces from the password');
    return false;
  }
  
  // Display configuration (with password redacted)
  console.log('📧 SMTP Configuration:');
  console.log(`   Host: ${process.env.SMTP_HOST}`);
  console.log(`   Port: ${process.env.SMTP_PORT}`);
  console.log(`   Secure: ${process.env.SMTP_SECURE}`);
  console.log(`   User: ${process.env.SMTP_USER}`);
  console.log(`   Pass: ${process.env.SMTP_PASS ? '***' + process.env.SMTP_PASS.slice(-4) : 'NOT SET'}`);
  console.log();
  
  // Create transporter
  const transporter = nodemailer.createTransporter({
    host: process.env.SMTP_HOST,
    port: parseInt(process.env.SMTP_PORT),
    secure: process.env.SMTP_SECURE === 'true',
    auth: {
      user: process.env.SMTP_USER,
      pass: process.env.SMTP_PASS
    },
    tls: {
      rejectUnauthorized: false // For development/testing
    }
  });
  
  try {
    // Test connection
    console.log('🔗 Testing SMTP connection...');
    await transporter.verify();
    console.log('✅ SMTP connection successful!');
    
    // Test sending email
    console.log('\n📤 Testing email sending...');
    const testEmail = process.env.TEST_EMAIL || process.env.SMTP_USER;
    
    const mailOptions = {
      from: process.env.EMAIL_FROM || `"SmileBright Clinic" <${process.env.SMTP_USER}>`,
      to: testEmail,
      subject: '🔐 Gmail Configuration Test - SmileBright',
      html: `
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
          <h2 style="color: #1f4f86;">✅ Gmail Configuration Test Successful!</h2>
          <p>This email confirms that your Gmail SMTP configuration is working correctly.</p>
          
          <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3>Configuration Details:</h3>
            <ul>
              <li><strong>SMTP Host:</strong> ${process.env.SMTP_HOST}</li>
              <li><strong>Port:</strong> ${process.env.SMTP_PORT}</li>
              <li><strong>Secure:</strong> ${process.env.SMTP_SECURE}</li>
              <li><strong>From:</strong> ${process.env.SMTP_USER}</li>
              <li><strong>Test Time:</strong> ${new Date().toLocaleString()}</li>
            </ul>
          </div>
          
          <p style="color: #28a745; font-weight: bold;">🎉 Your SmileBright notification system is ready!</p>
          
          <hr style="margin: 30px 0;">
          <p style="color: #6c757d; font-size: 12px;">
            This is an automated test email from SmileBright Dental notification system.
          </p>
        </div>
      `,
      text: `
Gmail Configuration Test Successful!

This email confirms that your Gmail SMTP configuration is working correctly.

Configuration Details:
- SMTP Host: ${process.env.SMTP_HOST}
- Port: ${process.env.SMTP_PORT}
- Secure: ${process.env.SMTP_SECURE}
- From: ${process.env.SMTP_USER}
- Test Time: ${new Date().toLocaleString()}

🎉 Your SmileBright notification system is ready!

---
This is an automated test email from SmileBright Dental notification system.
      `
    };
    
    const result = await transporter.sendMail(mailOptions);
    
    console.log('✅ Test email sent successfully!');
    console.log(`📋 Message ID: ${result.messageId}`);
    console.log(`📋 To: ${testEmail}`);
    console.log(`📋 Subject: ${mailOptions.subject}`);
    
    console.log('\n' + '=' .repeat(50));
    console.log('🎉 Gmail Configuration Test PASSED!');
    console.log('=' .repeat(50));
    console.log('✅ SMTP connection verified');
    console.log('✅ Email sending successful');
    console.log('✅ New App Password working');
    console.log('\n📧 Check your inbox for the test email');
    console.log('📧 If not received, check spam folder');
    
    return true;
    
  } catch (error) {
    console.log('❌ Gmail configuration test failed!');
    console.log('📋 Error:', error.message);
    
    console.log('\n🔧 Troubleshooting:');
    if (error.message.includes('Username and Password not accepted')) {
      console.log('   • Verify you\'re using the NEW App Password');
      console.log('   • Remove spaces from the password');
      console.log('   • Ensure 2-Step Verification is enabled');
    } else if (error.message.includes('TLS') || error.message.includes('handshake')) {
      console.log('   • Check port/secure configuration');
      console.log('   • Use 465 + secure=true (or 587 + secure=false)');
    } else if (error.message.includes('ENOTFOUND') || error.message.includes('ECONNREFUSED')) {
      console.log('   • Check internet connection');
      console.log('   • Verify firewall/antivirus settings');
    }
    
    console.log('\n📋 Next steps:');
    console.log('   1. Double-check your .env file');
    console.log('   2. Verify the new App Password');
    console.log('   3. Test again with: node test_gmail_config.js');
    
    return false;
  }
}

// Run test if this file is executed directly
if (require.main === module) {
  testGmailConfiguration().catch(console.error);
}

module.exports = { testGmailConfiguration };
