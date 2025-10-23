# Smile Bright Email Service

A Node.js email service using Nodemailer for sending booking confirmation emails to patients and clinic staff.

## Features

- ✅ Patient confirmation emails with calendar attachments
- ✅ Clinic notification emails
- ✅ ICS calendar event generation
- ✅ HTML email templates
- ✅ SMTP configuration with Gmail
- ✅ Authentication token validation
- ✅ Error handling and logging

## Quick Start

1. **Install dependencies:**
   ```bash
   npm install
   ```

2. **Configure environment:**
   - Copy `.env` file and update with your SMTP credentials
   - Set your Gmail app password in `SMTP_PASS`
   - Update `CLINIC_EMAIL` with your team's inbox

3. **Start the service:**
   ```bash
   npm start
   ```

4. **Test the service:**
   ```bash
   npm test
   ```

## API Endpoint

### POST `/send-booking-emails`

Sends confirmation emails to both patient and clinic.

**Headers:**
- `Content-Type: application/json`
- `X-Email-Token: sb_email_token_use_this_exact_string`

**Request Body:**
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

**Response:**
```json
{
  "ok": true
}
```

## Environment Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `SMTP_HOST` | SMTP server host | `smtp.gmail.com` |
| `SMTP_PORT` | SMTP server port | `465` |
| `SMTP_SECURE` | Use SSL/TLS | `true` |
| `SMTP_USER` | SMTP username | `your-email@gmail.com` |
| `SMTP_PASS` | SMTP password/app password | `your-app-password` |
| `FROM_EMAIL` | Sender email address | `your-email@gmail.com` |
| `FROM_NAME` | Sender name | `Smile Bright Dental` |
| `CLINIC_EMAIL` | Clinic notification email | `team@smilebrightdental.sg` |
| `EMAIL_TOKEN` | API authentication token | `sb_email_token_use_this_exact_string` |
| `BASE_URL` | Base URL for manage links | `http://localhost/SmileBright` |
| `TIMEZONE` | Timezone for calendar events | `Asia/Singapore` |
| `PORT` | Server port | `4001` |

## Gmail Setup

1. Enable 2-factor authentication on your Gmail account
2. Generate an App Password:
   - Go to Google Account settings
   - Security → 2-Step Verification → App passwords
   - Generate password for "Mail"
   - Use this password in `SMTP_PASS`

## Integration with PHP

The PHP booking API should call this service after successful booking creation:

```php
$emailData = [
    'referenceId' => $bookingRef,
    'patient' => [
        'firstName' => $patient['first_name'],
        'lastName' => $patient['last_name'],
        'email' => $patient['email'],
        'phone' => $patient['phone']
    ],
    'appointment' => [
        'dentistId' => $dentistId,
        'dentistName' => $dentistName,
        'clinicId' => $clinicId,
        'clinicName' => $clinicName,
        'serviceCode' => $service,
        'serviceLabel' => ucfirst(str_replace('_', ' ', $service)),
        'experienceCode' => $previousExperience,
        'experienceLabel' => ucfirst(str_replace('_', ' ', $previousExperience)),
        'dateIso' => $dateIso,
        'time24' => $timeHuman,
        'dateDisplay' => $dateHuman,
        'timeDisplay' => $timeHuman
    ],
    'notes' => $notes,
    'consent' => [
        'agreePolicy' => true,
        'agreeTerms' => true
    ]
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:4001/send-booking-emails');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Email-Token: sb_email_token_use_this_exact_string'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $emailSent = true;
} else {
    error_log('Email service failed: ' . $response);
    $emailSent = false;
}
```

## Health Check

### GET `/health`

Returns service status and configuration info.

## Development

- **Start with auto-reload:** `npm run dev`
- **Run tests:** `npm test`
- **Check logs:** Service logs to console

## Troubleshooting

1. **SMTP Authentication Failed:**
   - Verify Gmail app password is correct
   - Ensure 2FA is enabled on Gmail account

2. **Email Token Invalid:**
   - Check `X-Email-Token` header matches `EMAIL_TOKEN` env var

3. **Calendar Attachment Issues:**
   - Verify `TIMEZONE` is set correctly
   - Check date/time format in appointment data

4. **Service Won't Start:**
   - Check if port 4001 is available
   - Verify all environment variables are set
