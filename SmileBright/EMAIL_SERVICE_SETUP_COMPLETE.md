# Smile Bright Email Service - Complete Integration Guide

## ✅ What Has Been Implemented

### 1. Node.js Email Service (`email-service/`)
- **Location**: `C:\xampp\htdocs\SmileBright\email-service\`
- **Port**: 4001
- **Endpoint**: `http://localhost:4001/send-booking-emails`

### 2. Files Created:
- `package.json` - Dependencies and scripts
- `server.js` - Main email service with Nodemailer
- `.env` - Environment configuration
- `README.md` - Complete documentation
- `test.js` - Test script
- `integration-test.js` - Integration test

### 3. PHP Integration Updated:
- **File**: `api/bookings.php`
- **Integration**: Added cURL call to email service after successful booking creation
- **Error Handling**: Graceful failure - booking succeeds even if email fails

## 🚀 How to Complete the Setup

### Step 1: Configure Gmail Credentials
1. **Enable 2-Factor Authentication** on your Gmail account
2. **Generate App Password**:
   - Go to Google Account → Security → 2-Step Verification → App passwords
   - Generate password for "Mail"
3. **Update `.env` file**:
   ```env
   SMTP_USER=your-actual-gmail@gmail.com
   SMTP_PASS=your-generated-app-password
   FROM_EMAIL=your-actual-gmail@gmail.com
   CLINIC_EMAIL=your-team-inbox@example.com
   ```

### Step 2: Start the Email Service
```bash
cd C:\xampp\htdocs\SmileBright\email-service
npm start
```

### Step 3: Test the Integration
1. **Health Check**: Visit `http://localhost:4001/health`
2. **Create a booking** through your web interface
3. **Check logs** for email service responses

## 📧 Email Features Implemented

### Patient Confirmation Email:
- ✅ Professional HTML template
- ✅ Appointment details (dentist, clinic, date, time, service)
- ✅ Reference ID display
- ✅ Calendar attachment (.ics file)
- ✅ Manage appointment link
- ✅ Important instructions

### Clinic Notification Email:
- ✅ Patient information
- ✅ Appointment details
- ✅ Reference ID
- ✅ Calendar attachment
- ✅ Action items for clinic staff

## 🔧 API Contract

### Request Format:
```json
{
  "referenceId": "SB-20250123-0001",
  "patient": {
    "firstName": "John",
    "lastName": "Doe",
    "email": "john.doe@example.com",
    "phone": "+65 9123 4567"
  },
  "appointment": {
    "dentistId": "dr-chua-wen-zhan",
    "dentistName": "Dr. Chua Wen Zhan",
    "clinicId": "orchard",
    "clinicName": "Orchard",
    "serviceCode": "general",
    "serviceLabel": "General Dentistry",
    "experienceCode": "first-time",
    "experienceLabel": "First Time Patient",
    "dateIso": "2025-01-25",
    "time24": "14:00",
    "dateDisplay": "Saturday, 25 January 2025",
    "timeDisplay": "2:00 PM"
  },
  "notes": "Optional patient notes",
  "consent": {
    "agreePolicy": true,
    "agreeTerms": true
  }
}
```

### Headers Required:
- `Content-Type: application/json`
- `X-Email-Token: sb_email_token_use_this_exact_string`

## 🧪 Testing Commands

### Health Check:
```powershell
Invoke-RestMethod -Uri "http://localhost:4001/health" -Method GET
```

### Email Service Test:
```powershell
$testData = @{
    referenceId = "SB-20250123-0001"
    patient = @{
        firstName = "John"
        lastName = "Doe"
        email = "john.doe@example.com"
        phone = "+65 9123 4567"
    }
    appointment = @{
        dentistId = "dr-chua-wen-zhan"
        dentistName = "Dr. Chua Wen Zhan"
        clinicId = "orchard"
        clinicName = "Orchard"
        serviceCode = "general"
        serviceLabel = "General Dentistry"
        experienceCode = "first-time"
        experienceLabel = "First Time Patient"
        dateIso = "2025-01-25"
        time24 = "14:00"
        dateDisplay = "Saturday, 25 January 2025"
        timeDisplay = "2:00 PM"
    }
    notes = "Test appointment"
    consent = @{
        agreePolicy = $true
        agreeTerms = $true
    }
}

$jsonData = $testData | ConvertTo-Json -Depth 3
$headers = @{'Content-Type'='application/json'; 'X-Email-Token'='sb_email_token_use_this_exact_string'}
Invoke-RestMethod -Uri "http://localhost:4001/send-booking-emails" -Method POST -Body $jsonData -Headers $headers
```

## 🔍 Troubleshooting

### Service Won't Start:
- Check if port 4001 is available
- Verify Node.js is installed
- Run `npm install` in email-service directory

### SMTP Authentication Failed:
- Verify Gmail app password is correct
- Ensure 2FA is enabled on Gmail account
- Check SMTP_USER and SMTP_PASS in .env

### Email Service Not Responding:
- Check if service is running: `netstat -an | findstr :4001`
- Restart service: `npm start`
- Check console logs for errors

## 📋 Current Status

✅ **Email Service**: Created and running on port 4001  
✅ **PHP Integration**: Updated to call email service  
✅ **Health Check**: Working  
✅ **API Endpoint**: Responding correctly  
⚠️ **SMTP**: Needs real Gmail credentials  
✅ **Error Handling**: Graceful failure implemented  

## 🎯 Next Steps

1. **Update Gmail credentials** in `.env` file
2. **Set CLINIC_EMAIL** to your team's inbox
3. **Test with real booking** through web interface
4. **Monitor logs** for email delivery status

The integration is complete and ready for production use once SMTP credentials are configured!
