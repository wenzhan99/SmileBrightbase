# SmileBright Base Version - Project Specification

**Base Version Compliant** - No JSON/AJAX, No Mailto, No FRAMES/IFRAME, No External Frameworks

---

## Project Configuration

```json
{
  "project": {
    "name": "smilebrightbase",
    "base_url": "http://localhost/smilebrightbase",
    "composer_require": ["phpmailer/phpmailer"],
    "mail_from": {
      "email": "smilebrightsg.info@gmail.com",
      "name": "SmileBright Dental"
    },
    "mail_app_name": "smilebrightmailer"
  }
}
```

---

## Database Schema

### Database Name
`smilebrightbase`

### Schema SQL
```sql
CREATE TABLE IF NOT EXISTS bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  reference_id VARCHAR(32) UNIQUE NOT NULL,
  patient_first_name VARCHAR(100) NOT NULL,
  patient_last_name  VARCHAR(100) NOT NULL,
  patient_email      VARCHAR(255) NOT NULL,
  patient_phone      VARCHAR(50),
  doctor_name        VARCHAR(100) NOT NULL,
  clinic_name        VARCHAR(100) NOT NULL,
  service_name       VARCHAR(100) NOT NULL,
  appointment_date   DATE NOT NULL,
  appointment_time   TIME NOT NULL,
  status             ENUM('booked','rescheduled','cancelled') DEFAULT 'booked',
  notes              TEXT,
  created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

---

## Environment Configuration (.env)

**⚠️ SECURITY: Never commit .env file to version control**

Create `.env` file from template:

```ini
# SMTP (Gmail/Workspace)
# App Name: smilebrightmailer
# App Password: papnfdspuajheazp
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_SECURE=tls
SMTP_USER=smilebrightsg.info@gmail.com
SMTP_PASS=papnfdspuajheazp

# Mail "from" identity
EMAIL_FROM=smilebrightsg.info@gmail.com
EMAIL_FROM_NAME=SmileBright Dental

# Database (XAMPP typical — change if needed)
DB_HOST=localhost
DB_NAME=smilebrightbase
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4

# App URLs (adjust to your local path)
APP_BASE_URL=http://localhost/smilebrightbase
MANAGE_URL=http://localhost/smilebrightbase/public/booking/manage_booking.html
```

---

## File Structure

```
smilebrightbase/
├── public/
│   └── booking/
│       ├── book_appointmentbase.html    # Booking form (POST to create.php)
│       ├── booking_success.html         # Success page
│       ├── manage_booking.html          # Update form (POST to update.php)
│       └── doctor_dashboard.html        # Admin form (POST to admin endpoint)
│
├── api/
│   └── booking/
│       ├── create.php                   # Creates booking, redirects to success
│       └── update.php                   # Updates booking, redirects to manage page
│
├── config/
│   ├── config.php                       # Environment loader
│   └── mail.php                         # Email configuration
│
├── lib/
│   ├── db.php                           # Database connection (PDO)
│   ├── mailer.php                       # PHPMailer wrapper
│   └── email_templates.php              # Email template functions
│
├── vendor/                              # Composer dependencies (PHPMailer)
├── .env                                 # Environment variables (DO NOT COMMIT)
└── .env.template                        # Environment template
```

---

## API Endpoints

### ✅ `/api/booking/create.php` (Base Version Compliant)

**Method:** `POST` (form data, not JSON)

**Form Fields:**
- `patient_first_name` (required)
- `patient_last_name` (required)
- `patient_email` (required)
- `patient_phone` (optional)
- `doctor_name` (required)
- `clinic_name` (required)
- `service_name` (required)
- `appointment_date` (required, YYYY-MM-DD)
- `appointment_time` (required, HH:MM)
- `notes` (optional)

**Response:** 
- **Success:** HTTP 302 Redirect to `/public/booking/booking_success.html?ref=SB-YYYYMMDD-XXXXXX`
- **Error:** HTTP 400/500 with HTML error page

**Email Events:**
- Patient receives confirmation email
- Clinic receives copy of confirmation

---

### ✅ `/api/booking/update.php` (Base Version Compliant)

**Method:** `POST` (form data, not JSON)

**Form Fields:**
- `reference_id` (required)
- `appointment_date` (optional, YYYY-MM-DD)
- `appointment_time` (optional, HH:MM)
- `notes` (optional)
- `status` (optional: scheduled|confirmed|cancelled|completed|rescheduled)

**Response:**
- **Success:** HTTP 302 Redirect to `/public/booking/manage_booking.html?ref=REF&updated=1`
- **Error:** HTTP 400/404/409/500 with HTML error page

**Email Events:**
- Patient receives update confirmation email
- Clinic receives copy of update

---

## Base Version Compliance Rules

### ✅ Allowed
- HTML forms with `method="POST"` and `action="..."` pointing to PHP scripts
- PHP server-side processing
- HTTP redirects using `header('Location: ...')`
- HTML error pages
- PHPMailer (server-side library)
- Standard JavaScript (no AJAX/JSON parsing)
- CSS styling

### ❌ NOT Allowed (Violations)
- ❌ JSON responses from PHP APIs
- ❌ AJAX (fetch, XMLHttpRequest, jQuery)
- ❌ Client-side JSON parsing
- ❌ Mailto: links as form actions
- ❌ FRAMES/IFRAME
- ❌ Bootstrap, Foundation, Dreamweaver templates
- ❌ Links to PayPal, Facebook, Twitter, Gmail, Yahoo Mail

---

## Frontend Forms

### Booking Form (`book_appointmentbase.html`)

```html
<form method="POST" action="/smilebrightbase/api/booking/create.php">
  <!-- Form fields -->
  <button type="submit">Book Appointment</button>
</form>
```

**On Submit:**
- Browser navigates to `create.php`
- PHP processes and redirects to success page
- No JavaScript/AJAX involved

---

### Manage Booking Form (`manage_booking.html`)

```html
<form method="POST" action="/smilebrightbase/api/booking/update.php">
  <input type="hidden" name="reference_id" value="SB-...">
  <!-- Update fields -->
  <button type="submit">Update Booking</button>
</form>
```

**On Submit:**
- Browser navigates to `update.php`
- PHP processes and redirects back to manage page
- No JavaScript/AJAX involved

---

## Email Configuration

### SMTP Settings
- **Host:** `smtp.gmail.com`
- **Port:** `587` (TLS)
- **User:** `smilebrightsg.info@gmail.com`
- **Password:** `papnfdspuajheazp` (Gmail App Password)
- **App Name:** `smilebrightmailer`

### Gmail Setup
1. Enable 2-Factor Authentication on Google account
2. Generate App Password: Google Account → Security → 2-Step Verification → App passwords
3. Select "Mail" and generate password
4. Use generated password in `SMTP_PASS`

---

## Email Templates

Located in: `lib/email_templates.php`

Functions:
- `tpl_new_booking($data)` - New booking confirmation
- `tpl_patient_reschedule($data, $old)` - Patient-initiated reschedule
- `tpl_doctor_reschedule($data, $old)` - Doctor-initiated reschedule

---

## Testing Checklist

- [ ] Form submission works without JavaScript
- [ ] All redirects work (no JSON responses)
- [ ] Email sending works with Gmail App Password
- [ ] Error pages display as HTML (not JSON)
- [ ] No AJAX/fetch/XMLHttpRequest in frontend
- [ ] No JSON.parse/JSON.stringify in frontend
- [ ] All forms use `action="..."` pointing to PHP scripts

---

## Security Notes

1. **Never commit `.env` file** - Contains real credentials
2. **Gmail App Password** - Already configured, use only for this project
3. **Enable 2FA** - Required for Gmail App Passwords
4. **Production:** Configure SPF, DKIM, DMARC records for domain
5. **Database:** Use prepared statements (already implemented)

---

## Implementation Status

✅ **Base Version Compliant**
- All endpoints use HTML redirects (no JSON)
- All forms use standard POST (no AJAX)
- Email configured with PHPMailer
- No framework dependencies
- No external service links

---

**Last Updated:** 2025-01-25
**Version:** 1.0.0 (Base Version Compliant)

