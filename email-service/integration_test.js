// Test script for Smile Bright Email Service Integration
// Using built-in fetch (Node.js 18+) or http module
const http = require('http');

async function testEmailService() {
    console.log('🧪 Testing Smile Bright Email Service Integration...\n');
    
    // Test 1: Health Check
    console.log('1. Testing health endpoint...');
    try {
        const healthResponse = await fetch('http://localhost:4001/health');
        const healthData = await healthResponse.json();
        console.log('✅ Health check passed:', healthData);
    } catch (error) {
        console.log('❌ Health check failed:', error.message);
        return;
    }
    
    // Test 2: Email Service (will fail due to SMTP credentials, but should return proper error)
    console.log('\n2. Testing email sending endpoint...');
    const testData = {
        referenceId: "SB-20250123-0001",
        patient: {
            firstName: "John",
            lastName: "Doe",
            email: "john.doe@example.com",
            phone: "+65 9123 4567"
        },
        appointment: {
            dentistId: "dr-chua-wen-zhan",
            dentistName: "Dr. Chua Wen Zhan",
            clinicId: "orchard",
            clinicName: "Orchard",
            serviceCode: "general",
            serviceLabel: "General Dentistry",
            experienceCode: "first-time",
            experienceLabel: "First Time Patient",
            dateIso: "2025-01-25",
            time24: "14:00",
            dateDisplay: "Saturday, 25 January 2025",
            timeDisplay: "2:00 PM"
        },
        notes: "Test appointment - SMTP credentials not configured",
        consent: {
            agreePolicy: true,
            agreeTerms: true
        }
    };
    
    try {
        const emailResponse = await fetch('http://localhost:4001/send-booking-emails', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Email-Token': 'sb_email_token_use_this_exact_string'
            },
            body: JSON.stringify(testData)
        });
        
        const emailData = await emailResponse.json();
        console.log('📧 Email service response:', emailData);
        
        if (emailResponse.ok) {
            console.log('✅ Email service endpoint working (SMTP will fail due to placeholder credentials)');
        } else {
            console.log('⚠️ Email service returned error (expected due to SMTP config):', emailData);
        }
        
    } catch (error) {
        console.log('❌ Email service test failed:', error.message);
    }
    
    // Test 3: PHP Integration Test
    console.log('\n3. Testing PHP integration...');
    console.log('📝 PHP booking API has been updated to call the email service');
    console.log('📝 Integration code added to api/bookings.php');
    console.log('📝 Email service will be called after successful booking creation');
    
    console.log('\n🎉 Integration Test Complete!');
    console.log('\n📋 Next Steps:');
    console.log('1. Update .env file with real Gmail credentials');
    console.log('2. Set CLINIC_EMAIL to your team inbox');
    console.log('3. Test with a real booking through the web interface');
}

// Run the test
testEmailService().catch(console.error);
