const fetch = require('node-fetch');

// Test data matching the API contract
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
    notes: "Patient prefers morning appointments if possible",
    consent: {
        agreePolicy: true,
        agreeTerms: true
    }
};

async function testEmailService() {
    const url = 'http://localhost:4001/send-booking-emails';
    const headers = {
        'Content-Type': 'application/json',
        'X-Email-Token': 'sb_email_token_use_this_exact_string'
    };
    
    try {
        console.log('🧪 Testing Smile Bright Email Service...');
        console.log('📤 Sending test booking data...');
        
        const response = await fetch(url, {
            method: 'POST',
            headers: headers,
            body: JSON.stringify(testData)
        });
        
        const result = await response.json();
        
        if (response.ok) {
            console.log('✅ Email service test successful!');
            console.log('📧 Response:', result);
        } else {
            console.log('❌ Email service test failed');
            console.log('📧 Error:', result);
        }
        
    } catch (error) {
        console.error('🚨 Test error:', error.message);
    }
}

// Run test if this file is executed directly
if (require.main === module) {
    testEmailService();
}

module.exports = { testEmailService, testData };
