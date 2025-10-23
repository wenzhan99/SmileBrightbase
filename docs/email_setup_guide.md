# Smile Bright Dental - Email Confirmation System Setup Guide

## Overview
This system automatically sends beautiful, professional appointment confirmation emails to patients after they book an appointment through your website.

## Features
✅ Automatic email confirmation after booking  
✅ Professional HTML email template with plain text fallback  
✅ Reschedule and cancel links with secure tokens  
✅ 30-day token expiry for security  
✅ Clinic address mapping  
✅ Patient notes included in confirmation  

---

## Database Setup

### Option 1: Fresh Installation
If you're setting up the database from scratch:
```sql
-- Run this in phpMyAdmin or MySQL command line
source setup_database.sql
```

### Option 2: Update Existing Database
If you already have the `appointments` table:
```sql
-- Run the migration script
source migration_add_reschedule_tokens.sql
```

This adds two new columns:
- `reschedule_token` - Secure token for reschedule/cancel links
- `token_expires_at` - Expiry date for the token (30 days)

---

## PHP Configuration

### 1. Enable PHP Mail Function

#### For XAMPP (Development):
1. Open `php.ini` (usually at `C:\xampp\php\php.ini`)
2. Find and update these settings:

```ini
[mail function]
; For Windows
SMTP = smtp.gmail.com
smtp_port = 587
sendmail_from = your-email@gmail.com
sendmail_path = "\"C:\xampp\sendmail\sendmail.exe\" -t"
```

3. Open `sendmail.ini` (at `C:\xampp\sendmail\sendmail.ini`)
4. Configure SMTP settings:

```ini
[sendmail]
smtp_server=smtp.gmail.com
smtp_port=587
auth_username=your-email@gmail.com
auth_password=your-app-password
force_sender=your-email@gmail.com
```

#### For Gmail:
1. Enable 2-Factor Authentication on your Google account
2. Generate an [App Password](https://myaccount.google.com/apppasswords)
3. Use the app password in `sendmail.ini`

#### For Production:
Use a transactional email service like:
- SendGrid (recommended)
- Mailgun
- Amazon SES
- PostmarkApp

---

## File Structure

```
SmileBright/
├── db.php                              # Database connection
├── showpost.php                        # Form submission handler (updated)
├── send_email.php                      # Email sending functions (NEW)
├── bookingForm.jsx                     # React booking form
├── Book-Appointment.html               # Booking page
├── setup_database.sql                  # Fresh database setup
└── migration_add_reschedule_tokens.sql # Migration for existing DBs (NEW)
```

---

## Email Template Customization

### Update Clinic Addresses
Edit `send_email.php`, function `getClinicAddress()`:

```php
function getClinicAddress($clinicName) {
    $addresses = [
        'Novena' => 'Your actual address here',
        'Tampines' => 'Your actual address here',
        // ... add more clinics
    ];
    return $addresses[$clinicName] ?? 'Address not available';
}
```

### Update Support Contact
Find and replace in `send_email.php`:
- `+65 6XXX XXXX` → Your actual phone number
- `reception@smilebrightdental.sg` → Your actual email
- `appointments@smilebrightdental.sg` → Your actual sender email

### Change Email Styling
Edit the `$htmlBody` variable in `sendBookingConfirmation()` function.

The email uses inline CSS for maximum email client compatibility.

---

## Testing

### 1. Test Database Connection
```php
php -r "require 'db.php'; echo 'Connected: ' . $conn->ping();"
```

### 2. Test Email Sending
Create `test_email.php`:
```php
<?php
require 'send_email.php';

$testData = [
    'id' => 999,
    'first_name' => 'Test',
    'last_name' => 'Patient',
    'email' => 'your-test-email@example.com',
    'phone' => '12345678',
    'date' => '2025-10-15',
    'time' => '14:30:00',
    'clinic' => 'Novena',
    'service' => 'Scaling & Polishing',
    'experience' => 'First time patient',
    'message' => 'Looking forward to visit',
    'reschedule_token' => generateRescheduleToken(),
    'token_expires_at' => getTokenExpiryDate(),
    'created_at' => date('Y-m-d H:i:s')
];

if (sendBookingConfirmation($testData)) {
    echo "✅ Test email sent successfully!";
} else {
    echo "❌ Email failed to send. Check your PHP mail configuration.";
}
```

Run: `php test_email.php`

### 3. Test Full Booking Flow
1. Go to `Book-Appointment.html`
2. Fill out the form completely
3. Submit
4. Check:
   - Database record created with token
   - Success message displayed
   - Email received (check spam folder too)

---

## Email Template Preview

The confirmation email includes:

### Header Section
- Professional branding with Smile Bright colors (#1f4f86)
- Clear "Your appointment is booked" headline

### Appointment Details
- Date and time in readable format
- Clinic name and address
- Service requested
- Notes shared (experience + message)

### Action Buttons
- **Reschedule Appointment** - Prominent blue button
- **Cancel link** - Subtle red link
- Token expiry notice

### Security Notice
- Warning if booking wasn't made by recipient
- Contact information for support

### Footer
- Appointment ID and creation timestamp
- Professional sign-off

---

## Reschedule/Cancel Implementation

The email includes reschedule and cancel URLs with secure tokens:

```
https://smilebrightdental.sg/appointments/reschedule?appt_id=123&token=abc123...
https://smilebrightdental.sg/appointments/cancel?appt_id=123&token=abc123...
```

### TODO: Create these pages
You'll need to create:
1. `appointments/reschedule.php` - Verify token, show form to change date/time
2. `appointments/cancel.php` - Verify token, confirm cancellation

Example token verification:
```php
$apptId = $_GET['appt_id'];
$token = $_GET['token'];

$stmt = $conn->prepare("
    SELECT * FROM appointments 
    WHERE id = ? 
    AND reschedule_token = ? 
    AND token_expires_at > NOW()
");
$stmt->bind_param('is', $apptId, $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Invalid or expired reschedule link.');
}
```

---

## Troubleshooting

### Emails not sending?
1. Check `php.ini` mail configuration
2. Verify SMTP credentials in `sendmail.ini`
3. Check spam/junk folder
4. Enable error logging in PHP
5. Try using a different email provider

### Database errors?
1. Run the migration script if upgrading
2. Check database connection in `db.php`
3. Verify table structure matches schema

### Token errors?
1. Ensure `reschedule_token` column exists
2. Check token is being generated in `showpost.php`
3. Verify token isn't NULL in database

---

## Production Checklist

Before going live:

- [ ] Update all email addresses (remove example domains)
- [ ] Add real clinic addresses
- [ ] Add real phone numbers
- [ ] Test email delivery with real email addresses
- [ ] Configure production SMTP service (SendGrid/Mailgun)
- [ ] Implement reschedule and cancel pages
- [ ] Add email delivery logging
- [ ] Test on multiple email clients (Gmail, Outlook, Apple Mail)
- [ ] Check mobile email rendering
- [ ] Set up email bounce handling
- [ ] Configure SPF, DKIM, DMARC records for your domain

---

## Email Template Variables

The system uses these variables from the booking form:

| Form Field | Database Column | Email Variable |
|------------|----------------|----------------|
| firstName | first_name | {{first_name}} |
| lastName | last_name | {{last_name}} |
| email | email | {{email}} |
| phone | phone | {{phone}} |
| date | date | {{preferred_date}} |
| time | time | {{preferred_time}} |
| clinic | clinic | {{clinic_name}} |
| service | service | {{service_name}} |
| experience | experience | {{experience_notes}} |
| message | message | {{message_notes}} |
| - | id | {{appointment_id}} |
| - | reschedule_token | {{reschedule_token}} |
| - | created_at | {{created_at}} |

---

## Support

For issues or questions:
- Check the troubleshooting section above
- Review PHP error logs
- Test email configuration separately
- Verify database schema matches

---

## License & Credits

Created for Smile Bright Dental (Singapore) Pte Ltd  
© 2025 All Rights Reserved


